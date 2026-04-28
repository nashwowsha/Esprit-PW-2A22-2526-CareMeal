<?php
require_once __DIR__ . '/../Controller/PlanningCollecteController.php';

$controller = new PlanningCollecteController();
$idUser = isset($_GET['id_user']) ? (int)$_GET['id_user'] : 1;
if ($idUser <= 0) {
    $idUser = 1;
}
$idCollecte = isset($_GET['id_collecte']) ? (int)$_GET['id_collecte'] : 0;
$status = trim((string)($_GET['status'] ?? ''));
$detail = $idCollecte > 0 ? $controller->getStudentCollecteDetail($idCollecte, $idUser) : null;

$statusMessages = [
    'success_collecte_cancelled' => ['class' => 'success', 'text' => 'Collecte annulee. Le stock a ete re-ajuste.'],
    'error_cannot_cancel_collecte' => ['class' => 'error', 'text' => 'Cette collecte est deja finalisee.'],
    'error_forbidden_collecte' => ['class' => 'error', 'text' => 'Vous ne pouvez pas modifier cette collecte.'],
    'error_not_found' => ['class' => 'error', 'text' => 'Collecte introuvable.'],
    'error_db' => ['class' => 'error', 'text' => 'Erreur base de donnees.'],
];

if (!function_exists('student_detail_status_label')) {
    function student_detail_status_label($statusValue)
    {
        $map = [
            'en_attente' => 'En attente',
            'collecte' => 'Collecte',
            'en_cours_livraison' => 'En cours de livraison',
            'livree' => 'Livree',
            'annulee' => 'Annulee',
            'pickup' => 'Pickup',
            'delivery' => 'Livraison',
        ];
        $statusValue = trim((string)$statusValue);
        return $map[$statusValue] ?? $statusValue;
    }
}

if (!function_exists('student_detail_can_cancel')) {
    function student_detail_can_cancel($statusValue)
    {
        $statusValue = trim((string)$statusValue);
        return in_array($statusValue, ['en_attente', 'en_cours_livraison'], true);
    }
}

$mode = strtolower((string)($detail['mode_collecte'] ?? 'pickup'));
$currentStatus = trim((string)($detail['statut'] ?? 'en_attente'));
$canCancel = student_detail_can_cancel($currentStatus);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Detail Collecte - Etudiant</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <style>
    .stack { display:grid; gap:16px; }
    .alert-box { border-radius: var(--radius-md); padding: 12px 14px; border: 1px solid transparent; font-size: .9rem; }
    .alert-box.success { color: #0f5132; background: rgba(25,135,84,.18); border-color: rgba(25,135,84,.4); }
    .alert-box.error { color: #842029; background: rgba(220,53,69,.16); border-color: rgba(220,53,69,.4); }
    .grid2 { display:grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap:12px; }
    .meta { color: var(--color-text-muted); font-size: .82rem; }
    .value { font-weight: 600; color: var(--color-white); }
    .table-wrap { overflow:auto; }
    .table { width:100%; border-collapse: collapse; min-width: 760px; }
    .table th, .table td { border-bottom: 1px solid var(--color-dark-border); padding: 10px; text-align: left; vertical-align: top; }
    .actions { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
    .map-placeholder { border:1px dashed var(--color-dark-border); border-radius:12px; min-height:190px; display:grid; place-items:center; color:var(--color-text-muted); text-align:center; padding:12px; }
    @media (max-width: 900px) { .grid2 { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <div class="dashboard-layout">
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <div class="sidebar-logo"><img src="../assets/logo.png" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;"></div>
        <div class="sidebar-brand">Care<span>Meal</span></div>
      </div>
      <nav class="sidebar-nav">
        <div class="sidebar-section">
          <div class="sidebar-section-title">Menu</div>
          <a href="dashboard.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-house"></i></span> Accueil</a>
          <a href="profile.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-user"></i></span> Mon Profil</a>
          <a href="preferences.php?id_user=<?= (int)$idUser ?>" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-utensils"></i></span> Preferences</a>
          <a href="events.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> Evenements</a>
          <a href="mes_collectes.php?id_user=<?= (int)$idUser ?>" class="sidebar-link active"><span class="link-icon"><i class="fa-solid fa-box"></i></span> Mes Collectes</a>
        </div>
      </nav>
      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="avatar" id="sidebar-user-avatar">AA</div>
          <div class="sidebar-user-info">
            <div class="sidebar-user-name" id="sidebar-user-name">Utilisateur</div>
            <div class="sidebar-user-role" id="sidebar-user-role">Etudiant</div>
          </div>
          <button class="sidebar-logout" data-action="logout" title="Deconnexion"><i class="fa-solid fa-door-open"></i></button>
        </div>
      </div>
    </aside>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle"><i class="fa-solid fa-bars"></i></button>
          <div class="page-title"><h2>Detail collecte</h2><p>Suivi commande, statut et annulation</p></div>
        </div>
        <div class="header-right">
          <div class="avatar avatar-sm" id="header-avatar">AA</div>
        </div>
      </header>

      <div class="page-content">
        <div class="stack">
          <?php if (isset($statusMessages[$status])): ?>
            <div class="alert-box <?= htmlspecialchars($statusMessages[$status]['class'], ENT_QUOTES, 'UTF-8') ?>">
              <?= htmlspecialchars($statusMessages[$status]['text'], ENT_QUOTES, 'UTF-8') ?>
            </div>
          <?php endif; ?>

          <section class="card" id="collecte-detail-card">
            <div class="actions" style="justify-content:space-between;">
              <h3 class="card-title"><i class="fa-solid fa-receipt"></i> Collecte #<?= (int)$idCollecte ?></h3>
              <a class="btn btn-outline btn-sm" href="mes_collectes.php?id_user=<?= (int)$idUser ?>#mes-collectes-section"><i class="fa-solid fa-arrow-left"></i> Retour mes collectes</a>
            </div>
            <?php if (!$detail): ?>
              <p class="meta">Collecte introuvable ou non autorisee.</p>
            <?php else: ?>
              <div class="grid2">
                <div><div class="meta">Restaurant</div><div class="value">#<?= (int)$detail['id_restaurant'] ?> - <?= htmlspecialchars((string)($detail['restaurant_nom'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></div></div>
                <div><div class="meta">Localisation resto</div><div class="value"><?= htmlspecialchars((string)($detail['restaurant_localisation'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div></div>
                <div><div class="meta">Mode</div><div class="value"><?= htmlspecialchars(student_detail_status_label($mode), ENT_QUOTES, 'UTF-8') ?></div></div>
                <div><div class="meta">Heure</div><div class="value"><?= htmlspecialchars((string)($detail['heure_souhaitee'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div></div>
                <div><div class="meta">Montant</div><div class="value"><?= number_format((float)($detail['montant_total'] ?? 0), 2) ?> DT</div></div>
                <div><div class="meta">Statut</div><div class="value"><?= htmlspecialchars(student_detail_status_label($currentStatus), ENT_QUOTES, 'UTF-8') ?></div></div>
              </div>
            <?php endif; ?>
          </section>

          <?php if ($detail): ?>
            <section class="card">
              <div class="card-header"><h3 class="card-title"><i class="fa-solid fa-utensils"></i> Items selectionnes</h3></div>
              <div class="table-wrap">
                <table class="table">
                  <thead><tr><th>Meal</th><th>Quantite</th><th>Mode prix</th><th>Prix unitaire</th><th>Total ligne</th></tr></thead>
                  <tbody>
                    <?php if (empty($detail['items'])): ?>
                      <tr><td colspan="5" class="meta">Aucun item.</td></tr>
                    <?php else: ?>
                      <?php foreach ($detail['items'] as $item): ?>
                        <tr>
                          <td><?= htmlspecialchars((string)($item['meal_name'] ?? ($item['meal_id'] ?? 'Meal')), ENT_QUOTES, 'UTF-8') ?></td>
                          <td><?= (int)($item['quantity'] ?? 0) ?></td>
                          <td><?= htmlspecialchars((string)(($item['pricing_mode'] ?? 'free') === 'paid' ? 'paid' : 'free'), ENT_QUOTES, 'UTF-8') ?></td>
                          <td><?= number_format((float)($item['unit_price'] ?? 0), 2) ?> DT</td>
                          <td><?= number_format((float)($item['line_total'] ?? 0), 2) ?> DT</td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </section>

            <section class="card">
              <div class="card-header"><h3 class="card-title"><i class="fa-solid fa-map-location-dot"></i> Zone tracking (Leaflet ensuite)</h3></div>
              <div class="map-placeholder">
                <div>
                  <strong>Map placeholder</strong><br>
                  <span>Ici tu brancheras Leaflet: point restaurant, point etudiant, trajet livreur en temps reel.</span>
                </div>
              </div>
            </section>

            <?php if ($canCancel): ?>
              <section class="card">
                <div class="card-header"><h3 class="card-title"><i class="fa-solid fa-ban"></i> Action etudiant</h3></div>
                <form method="post" action="../Controller/planning_collecte.php?action=student_cancel_collecte" onsubmit="return confirm('Annuler cette collecte ?');" class="actions">
                  <input type="hidden" name="id_collecte" value="<?= (int)$detail['id_collecte'] ?>">
                  <input type="hidden" name="id_user" class="js-id-user" value="<?= (int)$idUser ?>">
                  <input type="hidden" name="return_to_detail" value="1">
                  <button class="btn btn-danger btn-sm" type="submit">Annuler collecte</button>
                </form>
              </section>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </main>
  </div>

  <script src="../js/app.js"></script>
  <script src="../js/components.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (!App.requireAuth(['student'])) return;
      var user = App.getCurrentUser();
      var match = String((user && user.id) ? user.id : '').match(/(\d+)$/);
      if (match) {
        var resolvedId = parseInt(match[1], 10);
        var url = new URL(window.location.href);
        var queryId = parseInt(url.searchParams.get('id_user') || '0', 10);
        if (!queryId || queryId !== resolvedId) {
          url.searchParams.set('id_user', String(resolvedId));
          window.history.replaceState({}, '', url.toString());
        }
        document.querySelectorAll('.js-id-user').forEach(function (el) { el.value = String(resolvedId); });
      }
    });
  </script>
</body>
</html>
