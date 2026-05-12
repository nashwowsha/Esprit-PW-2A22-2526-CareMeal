<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Service/AIModerationService.php';

$action = $_GET['action'] ?? '';
$pdo = Config::getConnexion();
ensureCommentSchema($pdo);

if ($action === 'list') {
    $id_publication = (int)($_GET['id_publication'] ?? 0);

    $sql = "SELECT * FROM commentaire WHERE id_publication = ? ORDER BY date_commentaire ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_publication]);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($action === 'create') {
    $contenu = trim((string)($_POST['contenu_commentaire'] ?? ''));
    $idPublication = (int)($_POST['id_publication'] ?? 0);
    $auteurType = normalizeAuthorType((string)($_POST['auteur_type'] ?? ''));
    $auteurId = trim((string)($_POST['auteur_id'] ?? ''));

    if ($contenu === '') {
        echo json_encode([
            'success' => false,
            'message' => 'Le commentaire ne peut pas etre vide.'
        ]);
        exit;
    }

    $moderationService = new AIModerationService();
    $decision = $moderationService->moderateText($contenu, 'commentaire');

    $moderationStatusRaw = strtolower(trim((string)($decision['status'] ?? '')));
    if ($moderationStatusRaw !== 'ok') {
        echo json_encode([
            'success' => false,
            'code' => 'MODERATION_UNAVAILABLE',
            'message' => 'Modération IA indisponible. Veuillez réessayer.',
            'moderation' => [
                'allowed' => false,
                'risk' => normalizeModerationRisk($decision['risk'] ?? null, 'high'),
                'reason' => normalizeModerationReason($decision['reason'] ?? null)
                    ?? 'Moderation IA indisponible. Veuillez reessayer.',
                'language' => trim((string)($decision['language'] ?? 'unknown')) ?: 'unknown',
                'provider' => normalizeModerationProvider($decision['provider'] ?? 'gemini'),
                'error_code' => strtolower(trim((string)($decision['error_code'] ?? '')))
            ]
        ]);
        exit;
    }

    $isAllowed = (bool)($decision['allowed'] ?? false);
    $moderationStatus = $isAllowed ? 'approved' : 'rejected';
    $moderationRisk = normalizeModerationRisk($decision['risk'] ?? null, $isAllowed ? 'low' : 'high');
    $moderationReason = normalizeModerationReason($decision['reason'] ?? null);
    $moderationProvider = normalizeModerationProvider($decision['provider'] ?? 'gemini');

    if (!$isAllowed) {
        echo json_encode([
            'success' => false,
            'code' => 'MODERATION_REJECTED',
            'message' => 'Commentaire refusé : contenu inapproprié détecté.',
            'moderation' => [
                'allowed' => false,
                'risk' => $moderationRisk,
                'reason' => $moderationReason,
                'language' => trim((string)($decision['language'] ?? 'unknown')) ?: 'unknown',
                'provider' => $moderationProvider
            ]
        ]);
        exit;
    }

    $success = false;
    try {
        $sql = "INSERT INTO commentaire (
            contenu_commentaire, id_publication, auteur_type, auteur_id,
            moderation_status, moderation_risk, moderation_reason, moderation_provider
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([
            $contenu,
            $idPublication,
            $auteurType,
            $auteurId,
            $moderationStatus,
            $moderationRisk,
            $moderationReason,
            $moderationProvider
        ]);
    } catch (PDOException $e) {
        $sql = "INSERT INTO commentaire (contenu_commentaire, id_publication, auteur_type, auteur_id) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$contenu, $idPublication, $auteurType, $auteurId]);
    }

    echo json_encode([
        'success' => $success,
        'moderation' => [
            'status' => $moderationStatus,
            'risk' => $moderationRisk,
            'reason' => $moderationReason,
            'provider' => $moderationProvider
        ]
    ]);
    exit;
}

if ($action === 'update') {
    $id = (int)($_POST['id_commentaire'] ?? 0);
    $contenu = trim((string)($_POST['contenu_commentaire'] ?? ''));

    $sql = "UPDATE commentaire SET contenu_commentaire = ? WHERE id_commentaire = ?";
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$contenu, $id]);

    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'delete') {
    $id = (int)($_POST['id_commentaire'] ?? 0);

    $sql = "DELETE FROM commentaire WHERE id_commentaire = ?";
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$id]);

    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'list_reactions') {
    try {
        $sql = "SELECT * FROM commentaire_reaction";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        echo json_encode([]);
    }
    exit;
}

if ($action === 'react') {
    $commentId = (int)($_POST['commentaire_id'] ?? 0);
    $auteurType = (string)($_POST['auteur_type'] ?? '');
    $auteurId = (string)($_POST['auteur_id'] ?? '');
    $reaction = (string)($_POST['reaction'] ?? '');

    try {
        $stmt = $pdo->prepare("DELETE FROM commentaire_reaction WHERE commentaire_id = ? AND auteur_type = ? AND auteur_id = ?");
        $stmt->execute([$commentId, $auteurType, $auteurId]);

        if (!empty($reaction)) {
            $stmt = $pdo->prepare("INSERT INTO commentaire_reaction (commentaire_id, auteur_type, auteur_id, reaction) VALUES (?, ?, ?, ?)");
            $stmt->execute([$commentId, $auteurType, $auteurId, $reaction]);
        }
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action invalide']);

function ensureCommentSchema(PDO $pdo): void
{
    static $ensured = false;
    if ($ensured) return;
    $ensured = true;

    ensureCommentModerationColumn($pdo, 'moderation_status', "ALTER TABLE commentaire ADD COLUMN moderation_status VARCHAR(20) NOT NULL DEFAULT 'approved'");
    ensureCommentModerationColumn($pdo, 'moderation_risk', "ALTER TABLE commentaire ADD COLUMN moderation_risk VARCHAR(20) NULL");
    ensureCommentModerationColumn($pdo, 'moderation_reason', "ALTER TABLE commentaire ADD COLUMN moderation_reason VARCHAR(255) NULL");
    ensureCommentModerationColumn($pdo, 'moderation_provider', "ALTER TABLE commentaire ADD COLUMN moderation_provider VARCHAR(50) NOT NULL DEFAULT 'gemini'");
}

function ensureCommentModerationColumn(PDO $pdo, string $columnName, string $alterSql): void
{
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM commentaire LIKE ?");
        $stmt->execute([$columnName]);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$exists) {
            $pdo->exec($alterSql);
        }
    } catch (PDOException $e) {
        // Keep app alive if DB user cannot alter table.
    }
}

function normalizeAuthorType(string $value): string
{
    $raw = strtolower(trim($value));
    if ($raw === 'student' || $raw === 'etudiant') return 'etudiant';
    if ($raw === 'partner' || $raw === 'partenaire') return 'partenaire';
    if ($raw === 'admin' || $raw === 'administrateur') return 'admin';
    return 'etudiant';
}

function normalizeModerationRisk(?string $risk, string $fallback = 'low'): string
{
    $value = strtolower(trim((string)$risk));
    if (in_array($value, ['low', 'medium', 'high'], true)) {
        return $value;
    }
    return $fallback;
}

function normalizeModerationReason(?string $reason): ?string
{
    $value = trim((string)$reason);
    if ($value === '') return null;
    return function_exists('mb_substr') ? mb_substr($value, 0, 255) : substr($value, 0, 255);
}

function normalizeModerationProvider(?string $provider): string
{
    $value = trim((string)$provider);
    if ($value === '') return 'gemini';
    return function_exists('mb_substr') ? mb_substr($value, 0, 50) : substr($value, 0, 50);
}
?>


