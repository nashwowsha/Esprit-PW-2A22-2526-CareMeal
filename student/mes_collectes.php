<?php
require_once __DIR__ . '/../Controller/PlanningCollecteController.php';
require_once __DIR__ . '/../Controller/PdfExport.php';
require_once __DIR__ . '/../config/app.php';

$controller = new PlanningCollecteController();
$realtimeConfig = caremeal_realtime_public_config();

$idUser = isset($_GET['id_user']) ? (int)$_GET['id_user'] : 1;
if ($idUser <= 0) {
    $idUser = 1;
}

$status = trim((string)($_GET['status'] ?? ''));
$selectedId = isset($_GET['selected_id']) ? (int)$_GET['selected_id'] : 0;
$search = trim((string)($_GET['q'] ?? ''));
$statusFilter = strtolower(trim((string)($_GET['status_filter'] ?? '')));
if (!in_array($statusFilter, ['en_attente', 'en_cours_livraison', 'collecte', 'livree', 'annulee'], true)) {
    $statusFilter = '';
}
$sortBy = strtolower(trim((string)($_GET['sort_by'] ?? 'newest')));
$allowedSorts = ['newest', 'oldest', 'montant_desc', 'montant_asc', 'status_asc', 'status_desc', 'heure_desc', 'heure_asc', 'restaurant_asc', 'restaurant_desc'];
if (!in_array($sortBy, $allowedSorts, true)) {
    $sortBy = 'newest';
}

$rows = $controller->getStudentCollectesWithJoin($idUser, $statusFilter, $search, $sortBy);

$statusMessages = [
    'success_collecte_created' => ['class' => 'success', 'text' => 'Collecte creee avec succes.'],
    'success_collecte_cancelled' => ['class' => 'success', 'text' => 'Collecte annulee. La reservation est retiree du stock.'],
    'success_collectes_cleared' => ['class' => 'success', 'text' => 'Collectes supprimees selon le filtre choisi.'],
    'error_cannot_cancel_collecte' => ['class' => 'error', 'text' => 'Cette collecte est deja finalisee et ne peut plus etre annulee.'],
    'error_cannot_delete_active_collecte' => ['class' => 'error', 'text' => 'Impossible de supprimer une collecte active (en attente / en cours).'],
    'error_forbidden_collecte' => ['class' => 'error', 'text' => 'Vous ne pouvez pas annuler cette collecte.'],
    'error_not_found' => ['class' => 'error', 'text' => 'Collecte introuvable.'],
    'error_invalid_request' => ['class' => 'error', 'text' => 'Requete invalide.'],
    'error_db' => ['class' => 'error', 'text' => 'Erreur base de donnees.'],
];

if (!function_exists('student_collecte_status_label')) {
    function student_collecte_status_label($status)
    {
        $map = [
            'en_attente' => 'En attente',
            'en_cours_livraison' => 'En cours de livraison',
            'collecte' => 'Collecte',
            'livree' => 'Livree',
            'annulee' => 'Annulee',
            'pickup' => 'Pickup',
            'delivery' => 'Livraison',
        ];
        $status = trim((string)$status);
        return $map[$status] ?? $status;
    }
}

if (!function_exists('student_collecte_items_display')) {
    function student_collecte_items_display($items)
    {
        if (!is_array($items) || empty($items)) {
            return '-';
        }
        $parts = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $qty = (int)($item['quantity'] ?? 0);
            $name = trim((string)($item['meal_name'] ?? ($item['meal_id'] ?? '')));
            if ($qty > 0 && $name !== '') {
                $parts[] = $qty . ' x ' . $name;
            }
        }
        return empty($parts) ? '-' : implode(', ', $parts);
    }
}

if (!function_exists('student_collecte_can_cancel')) {
    function student_collecte_can_cancel($status)
    {
        $status = trim((string)$status);
        return in_array($status, ['en_attente', 'en_cours_livraison'], true);
    }
}

$studentMapPoints = [];
foreach ($rows as $row) {
    $collecteId = (int)($row['id_collecte'] ?? 0);
    $restaurantName = trim((string)($row['restaurant_nom'] ?? 'Restaurant'));
    $restaurantLocation = trim((string)($row['restaurant_localisation'] ?? ''));
    $statusLabel = student_collecte_status_label($row['statut'] ?? '');
    $modeRaw = strtolower(trim((string)($row['mode_collecte'] ?? 'pickup')));
    $modeLabel = student_collecte_status_label($modeRaw);

    if ($restaurantLocation !== '') {
        $studentMapPoints[] = [
            'collecte_id' => $collecteId,
            'label' => 'Collecte #' . $collecteId . ' - ' . ($restaurantName !== '' ? $restaurantName : 'Restaurant'),
            'location' => $restaurantLocation,
            'status' => $statusLabel,
            'mode' => $modeLabel,
            'kind' => 'restaurant',
        ];
    }

    $deliveryAddress = trim((string)($row['adresse_livraison'] ?? ''));
    if ($modeRaw === 'delivery' && $deliveryAddress !== '') {
        $deliveryLat = isset($row['adresse_lat']) ? (float)$row['adresse_lat'] : null;
        $deliveryLng = isset($row['adresse_lng']) ? (float)$row['adresse_lng'] : null;
        $studentMapPoints[] = [
            'collecte_id' => $collecteId,
            'label' => 'Livraison collecte #' . $collecteId,
            'location' => $deliveryAddress,
            'status' => $statusLabel,
            'mode' => $modeLabel,
            'kind' => 'delivery',
            'lat' => $deliveryLat,
            'lng' => $deliveryLng,
        ];
    }
}

if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    $pdfRows = [];
    foreach ($rows as $row) {
        $itemsCsv = '-';
        $itemsRaw = $row['items'] ?? [];
        if (is_array($itemsRaw) && !empty($itemsRaw)) {
            $parts = [];
            foreach ($itemsRaw as $item) {
                if (!is_array($item)) continue;
                $qty = (int)($item['quantity'] ?? 0);
                $name = trim((string)($item['meal_name'] ?? ($item['meal_id'] ?? '')));
                if ($qty > 0 && $name !== '') $parts[] = $qty . ' x ' . $name;
            }
            if (!empty($parts)) $itemsCsv = implode(', ', $parts);
        }
        $pdfRows[] = [
            (int)($row['id_collecte'] ?? 0),
            (int)($row['id_restaurant'] ?? 0),
            (string)($row['restaurant_nom'] ?? ''),
            $itemsCsv,
            (string)($row['mode_collecte'] ?? ''),
            (string)($row['heure_souhaitee'] ?? ''),
            number_format((float)($row['montant_total'] ?? 0), 2, '.', ''),
            (string)($row['statut'] ?? ''),
        ];
    }

    caremeal_stream_table_pdf(
        'student_collectes_' . (int)$idUser . '_' . date('Ymd_His') . '.pdf',
        'Mes collectes etudiant #' . (int)$idUser,
        ['ID Collecte', 'ID Restaurant', 'Restaurant', 'Items', 'Mode', 'Heure souhaitee', 'Montant total', 'Statut'],
        $pdfRows,
        'landscape'
    );
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Suivez vos collectes et annulez si besoin.">
  <title>Mes Collectes - CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="../assets/vendor/leaflet/leaflet.css">
  <style>
    .stack { display: grid; gap: 16px; }
    .alert-box { border-radius: var(--radius-md); padding: 12px 14px; border: 1px solid transparent; font-size: .9rem; }
    .alert-box.success { color: #0f5132; background: rgba(25,135,84,.18); border-color: rgba(25,135,84,.4); }
    .alert-box.error { color: #842029; background: rgba(220,53,69,.16); border-color: rgba(220,53,69,.4); }
    .toolbar { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
    .input, .select { width: 100%; background: rgba(255,255,255,.04); border: 1px solid var(--color-dark-border); border-radius: var(--radius-md); color: var(--color-white); padding: 10px 12px; }
    .select { min-width: 190px; }
    .table-wrap { overflow: auto; }
    .table { width: 100%; border-collapse: collapse; min-width: 980px; }
    .table th, .table td { border-bottom: 1px solid var(--color-dark-border); padding: 10px; text-align: left; vertical-align: top; }
    .row-selected { background: rgba(249,115,22,.14); }
    .hint { color: var(--color-text-muted); font-size: .82rem; }
    .status-pill {
      display: inline-flex;
      align-items: center;
      padding: 4px 10px;
      border-radius: 999px;
      font-size: .78rem;
      border: 1px solid var(--color-dark-border);
      background: rgba(255,255,255,.04);
    }
    .live-pill {
      display: inline-flex;
      align-items: center;
      margin-top: 6px;
      padding: 3px 9px;
      border-radius: 999px;
      font-size: .74rem;
      border: 1px solid transparent;
    }
    .live-pill.active {
      color: #b6f6d5;
      background: rgba(16, 185, 129, .2);
      border-color: rgba(16, 185, 129, .45);
    }
    .live-pill.stale {
      color: #ffd9b6;
      background: rgba(249, 115, 22, .2);
      border-color: rgba(249, 115, 22, .45);
    }
    .collectes-map {
      width: 100%;
      height: 340px;
      border-radius: var(--radius-md);
      border: 1px solid var(--color-dark-border);
      overflow: hidden;
    }
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
          <div class="page-title">
            <h2>Mes collectes</h2>
            <p>Suivi de vos commandes et annulation si besoin</p>
          </div>
        </div>
        <div class="header-right">
          <button class="header-notification"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button>
          <div class="avatar avatar-sm" id="header-avatar">AA</div>
        </div>
      </header>

      <div class="page-content">
        <div class="stack" id="mes-collectes-section">
          <?php if (isset($statusMessages[$status])): ?>
            <div class="alert-box <?= htmlspecialchars((string)$statusMessages[$status]['class'], ENT_QUOTES, 'UTF-8') ?>">
              <?= htmlspecialchars((string)$statusMessages[$status]['text'], ENT_QUOTES, 'UTF-8') ?>
            </div>
          <?php endif; ?>

          <section class="card">
            <div class="card-header">
              <h3 class="card-title"><i class="fa-solid fa-map-location-dot"></i> Carte des collectes</h3>
            </div>
            <div id="student-collectes-map" class="collectes-map"></div>
          </section>

          <section class="card">
            <div class="card-header" style="justify-content:space-between;align-items:center;">
              <h3 class="card-title"><i class="fa-solid fa-filter"></i> Filtres</h3>
              <a class="btn btn-outline btn-sm" href="mes_collectes.php?id_user=<?= (int)$idUser ?>">Reset</a>
            </div>
            <form method="get" action="mes_collectes.php" class="toolbar">
              <input type="hidden" name="id_user" class="js-id-user" value="<?= (int)$idUser ?>">
              <input class="input" style="width:320px;" type="text" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Chercher collecte, resto, statut...">
              <select class="select" name="status_filter">
                <option value="">Tous statuts</option>
                <?php foreach (['en_attente', 'en_cours_livraison', 'collecte', 'livree', 'annulee'] as $opt): ?>
                  <option value="<?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?>"<?= $statusFilter === $opt ? ' selected' : '' ?>>
                    <?= htmlspecialchars(student_collecte_status_label($opt), ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <select class="select" name="sort_by">
                <option value="newest"<?= $sortBy === 'newest' ? ' selected' : '' ?>>Tri: plus recentes</option>
                <option value="oldest"<?= $sortBy === 'oldest' ? ' selected' : '' ?>>Tri: plus anciennes</option>
                <option value="montant_desc"<?= $sortBy === 'montant_desc' ? ' selected' : '' ?>>Montant decroissant</option>
                <option value="montant_asc"<?= $sortBy === 'montant_asc' ? ' selected' : '' ?>>Montant croissant</option>
                <option value="status_asc"<?= $sortBy === 'status_asc' ? ' selected' : '' ?>>Statut A-Z</option>
                <option value="heure_desc"<?= $sortBy === 'heure_desc' ? ' selected' : '' ?>>Heure plus tard</option>
                <option value="heure_asc"<?= $sortBy === 'heure_asc' ? ' selected' : '' ?>>Heure plus tot</option>
              </select>
              <button class="btn btn-outline btn-sm" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Rechercher</button>
              <a class="btn btn-outline btn-sm" href="mes_collectes.php?<?= htmlspecialchars(http_build_query(['id_user' => $idUser, 'q' => $search, 'status_filter' => $statusFilter, 'sort_by' => $sortBy, 'export' => 'pdf']), ENT_QUOTES, 'UTF-8') ?>">Export PDF</a>
            </form>
            <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;">
              <form method="post" action="../Controller/planning_collecte.php?action=student_clear_collectes" onsubmit="return confirm('Supprimer toutes les collectes livrees/collectees ?');">
                <input type="hidden" name="id_user" class="js-id-user" value="<?= (int)$idUser ?>">
                <input type="hidden" name="scope" value="completed">
                <button class="btn btn-outline btn-sm" type="submit">Clear livree + collectee</button>
              </form>
              <form method="post" action="../Controller/planning_collecte.php?action=student_clear_collectes" onsubmit="return confirm('Supprimer toutes les collectes annulees ?');">
                <input type="hidden" name="id_user" class="js-id-user" value="<?= (int)$idUser ?>">
                <input type="hidden" name="scope" value="cancelled">
                <button class="btn btn-outline btn-sm" type="submit">Clear annulees</button>
              </form>
              <form method="post" action="../Controller/planning_collecte.php?action=student_clear_collectes" onsubmit="return confirm('Supprimer livrees, collectees et annulees ?');">
                <input type="hidden" name="id_user" class="js-id-user" value="<?= (int)$idUser ?>">
                <input type="hidden" name="scope" value="completed_cancelled">
                <button class="btn btn-danger btn-sm" type="submit">Clear all finalisees</button>
              </form>
            </div>
          </section>

          <section class="card">
            <div class="card-header" style="justify-content:space-between;align-items:center;">
              <h3 class="card-title"><i class="fa-solid fa-table-list"></i> Liste de vos collectes</h3>
              <span class="badge badge-info"><?= count($rows) ?> lignes</span>
            </div>
            <p class="hint">Si vous annulez une collecte en attente, les quantites reservees reviennent en disponibilite (back to shelf).</p>
            <div class="table-wrap">
              <table class="table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Restaurant</th>
                    <th>Items</th>
                    <th>Mode</th>
                    <th>Heure</th>
                    <th>Montant</th>
                    <th>Statut</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($rows)): ?>
                    <tr><td colspan="8" style="color:var(--color-text-muted);">Aucune collecte.</td></tr>
                  <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                      <?php
                        $currentStatus = trim((string)($row['statut'] ?? 'en_attente'));
                        $canCancel = student_collecte_can_cancel($currentStatus);
                      ?>
                      <tr class="<?= $selectedId > 0 && (int)$row['id_collecte'] === $selectedId ? 'row-selected' : '' ?>">
                        <td>#<?= (int)$row['id_collecte'] ?></td>
                        <td>
                          #<?= (int)$row['id_restaurant'] ?> - <?= htmlspecialchars((string)($row['restaurant_nom'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?><br>
                          <span class="hint"><?= htmlspecialchars((string)($row['restaurant_localisation'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td><?= htmlspecialchars(student_collecte_items_display($row['items'] ?? []), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(student_collecte_status_label($row['mode_collecte'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($row['heure_souhaitee'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format((float)($row['montant_total'] ?? 0), 2) ?> DT</td>
                        <td>
                          <span class="status-pill"><?= htmlspecialchars(student_collecte_status_label($currentStatus), ENT_QUOTES, 'UTF-8') ?></span>
                          <?php if (strtolower(trim((string)($row['mode_collecte'] ?? ''))) === 'delivery'): ?>
                            <?php
                              $driverFirst = trim((string)($row['delivery_driver_first_name'] ?? ''));
                              $driverLast = trim((string)($row['delivery_driver_last_name'] ?? ''));
                              $driverName = trim($driverFirst . ' ' . $driverLast);
                              $driverContact = trim((string)($row['delivery_driver_contact'] ?? ''));
                              $driverUpdatedAt = trim((string)($row['delivery_driver_updated_at'] ?? ''));
                              $driverUpdatedTs = $driverUpdatedAt !== '' ? strtotime($driverUpdatedAt) : false;
                              $secondsSince = ($driverUpdatedTs !== false) ? max(0, time() - (int)$driverUpdatedTs) : null;
                              $isLiveNow = ($secondsSince !== null && $secondsSince <= 20);
                            ?>
                            <?php if ($driverUpdatedAt !== ''): ?>
                              <div class="live-pill <?= $isLiveNow ? 'active' : 'stale' ?>">
                                <?= $isLiveNow ? 'Livreur en direct' : ('Signal ancien (' . (int)$secondsSince . 's)') ?>
                              </div>
                            <?php endif; ?>
                            <div class="hint" style="margin-top:6px;">
                              Livreur: <?= htmlspecialchars($driverName !== '' ? $driverName : 'non assigne', ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <?php if ($driverContact !== ''): ?>
                              <div class="hint">Contact: <?= htmlspecialchars($driverContact, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                            <?php if ($driverUpdatedAt !== ''): ?>
                              <div class="hint">Derniere maj GPS: <?= htmlspecialchars($driverUpdatedAt, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                          <?php endif; ?>
                        </td>
                        <td>
                          <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                            <a class="btn btn-outline btn-sm" href="collecte_detail.php?id_collecte=<?= (int)$row['id_collecte'] ?>&id_user=<?= (int)$idUser ?>#collecte-detail-card">Voir detail</a>
                            <?php if ($canCancel): ?>
                              <form method="post" action="../Controller/planning_collecte.php?action=student_cancel_collecte" onsubmit="return confirm('Annuler cette collecte ?');">
                                <input type="hidden" name="id_user" class="js-id-user" value="<?= (int)$idUser ?>">
                                <input type="hidden" name="id_collecte" value="<?= (int)$row['id_collecte'] ?>">
                                <button class="btn btn-danger btn-sm" type="submit">Annuler</button>
                              </form>
                            <?php endif; ?>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </section>
        </div>
      </div>
    </main>
  </div>

  <script src="../js/app.js"></script>
  <script src="../js/components.js"></script>
  <script src="../assets/vendor/leaflet/leaflet.js"></script>
  <?php if (!empty($realtimeConfig['enabled'])): ?>
    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
  <?php endif; ?>
  <script src="../js/collectes-map.js"></script>
  <script>
    window.STUDENT_COLLECTES_MAP_POINTS = <?= json_encode($studentMapPoints, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (!App.requireAuth(['student'])) return;

      const user = App.getCurrentUser();
      const match = String((user && user.id) ? user.id : '').match(/(\d+)$/);
      if (match) {
        const resolvedId = parseInt(match[1], 10);
        const url = new URL(window.location.href);
        const queryId = parseInt(url.searchParams.get('id_user') || '0', 10);
        if (!queryId || queryId !== resolvedId) {
          url.searchParams.set('id_user', String(resolvedId));
          window.history.replaceState({}, '', url.toString());
        }
        document.querySelectorAll('.js-id-user').forEach((input) => { input.value = String(resolvedId); });
      }

      if (typeof window.initCollectesMap === 'function') {
        window.initCollectesMap({
          containerId: 'student-collectes-map',
          points: window.STUDENT_COLLECTES_MAP_POINTS || [],
          liveEndpoint: '../Controller/planning_collecte.php?action=live_points&scope=student&id_user=<?= (int)$idUser ?>',
          pusher: {
            enabled: <?= !empty($realtimeConfig['enabled']) ? 'true' : 'false' ?>,
            key: <?= json_encode((string)($realtimeConfig['key'] ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            cluster: <?= json_encode((string)($realtimeConfig['cluster'] ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            channel: <?= json_encode('caremeal-student-' . (int)$idUser . '-live', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            eventName: 'driver-location'
          },
          pollMs: 200,
          markerAnimationMs: 320
        });
      }
    });
  </script>
</body>
</html>
