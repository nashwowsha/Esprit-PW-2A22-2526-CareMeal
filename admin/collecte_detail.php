<?php
require_once __DIR__ . '/../Controller/PlanningCollecteController.php';

$controller = new PlanningCollecteController();
$idCollecte = isset($_GET['id_collecte']) ? (int)$_GET['id_collecte'] : 0;
$status = trim((string)($_GET['status'] ?? ''));
$detail = $idCollecte > 0 ? $controller->getCollecteDetail($idCollecte) : null;

$statusMessages = [
    'success_collecte_updated' => ['class' => 'success', 'text' => 'Statut de collecte mis a jour.'],
    'success_collecte_deleted' => ['class' => 'success', 'text' => 'Collecte supprimee.'],
    'error_invalid_status' => ['class' => 'error', 'text' => 'Statut invalide pour ce mode.'],
    'error_forbidden_collecte' => ['class' => 'error', 'text' => 'Action non autorisee.'],
    'error_not_found' => ['class' => 'error', 'text' => 'Collecte introuvable.'],
    'error_db' => ['class' => 'error', 'text' => 'Erreur base de donnees.'],
];

if (!function_exists('admin_collecte_status_label')) {
    function admin_collecte_status_label($statusValue)
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

if (!function_exists('admin_collecte_status_options')) {
    function admin_collecte_status_options($mode)
    {
        $mode = strtolower(trim((string)$mode));
        if ($mode === 'delivery') {
            return ['en_attente', 'en_cours_livraison', 'livree', 'annulee'];
        }
        return ['en_attente', 'collecte', 'annulee'];
    }
}

if (!function_exists('admin_collecte_items_display')) {
    function admin_collecte_items_display($items)
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

$mode = strtolower((string)($detail['mode_collecte'] ?? 'pickup'));
$currentStatus = trim((string)($detail['statut'] ?? 'en_attente'));
$statusOptions = admin_collecte_status_options($mode);
if ($currentStatus !== '' && !in_array($currentStatus, $statusOptions, true)) {
    $statusOptions[] = $currentStatus;
}

$prefRegime = trim((string)($detail['pref_regime_snapshot'] ?? ''));
if ($prefRegime === '') {
    $prefRegime = trim((string)($detail['regime_alimentaire'] ?? '-'));
}
$prefAllergies = trim((string)($detail['pref_allergies_snapshot'] ?? ''));
if ($prefAllergies === '') {
    $prefAllergies = trim((string)($detail['allergies'] ?? '-'));
}
$prefAddress = trim((string)($detail['pref_localisation_snapshot'] ?? ''));
if ($prefAddress === '') {
    $prefAddress = trim((string)($detail['preference_localisation'] ?? '-'));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Detail Collecte - Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="../css/theme-fix.css">
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
    .status-pill { display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; border:1px solid var(--color-dark-border); background:rgba(255,255,255,.04); font-size:.8rem; }
    .hero { display:grid; grid-template-columns: 1fr auto; gap:12px; align-items:start; }
    .actions { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
    .thumb { width:100%; max-height:220px; object-fit:cover; border-radius:12px; border:1px solid var(--color-dark-border); }
    .timeline { display:flex; gap:8px; flex-wrap:wrap; }
    .step { border:1px solid var(--color-dark-border); border-radius:999px; padding:6px 10px; font-size:.8rem; color:var(--color-text-muted); }
    .step.active { border-color: var(--color-primary); color: var(--color-primary); background: rgba(249,115,22,.12); }
    @media (max-width: 900px) { .grid2, .hero { grid-template-columns: 1fr; } }

    /* Light-mode visibility fixes (collecte detail admin) */
    .meta {
      color: #475569 !important;
    }
    .value {
      color: #0F172A !important;
    }
    .table {
      background: #FFFFFF !important;
    }
    .table th,
    .table td {
      color: #0F172A !important;
      border-bottom-color: #E2E8F0 !important;
    }
    .table thead th {
      background: #F8FAFC !important;
      color: #334155 !important;
    }
    .status-pill {
      color: #0F172A !important;
      background: #F8FAFC !important;
      border-color: #CBD5E1 !important;
    }
    .step {
      color: #475569 !important;
      background: #F8FAFC !important;
      border-color: #E2E8F0 !important;
    }
    .step.active {
      color: #EA580C !important;
      border-color: #FDBA74 !important;
      background: #FFF7ED !important;
    }
    .select {
      background: #FFFFFF !important;
      color: #0F172A !important;
      border-color: #CBD5E1 !important;
    }
    .select option {
      color: #0F172A !important;
      background: #FFFFFF !important;
    }
  </style>
</head>
<body>
  <div class="dashboard-layout">
    <?php
      $activePage = 'planning_collecte';
      require __DIR__ . '/_admin_sidebar.php';
    ?>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle"><i class="fa-solid fa-bars"></i></button>
          <div class="page-title"><h2>Detail collecte</h2><p>Consultation complete + actions admin</p></div>
        </div>
        <div class="header-right">
          <div class="avatar avatar-sm" id="header-avatar" style="background:linear-gradient(135deg,#EF4444,#F87171);">A</div>
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
            <div class="hero">
              <div>
                <h3 class="card-title"><i class="fa-solid fa-receipt"></i> Collecte #<?= (int)$idCollecte ?></h3>
                <?php if (!$detail): ?>
                  <p class="meta">Collecte introuvable.</p>
                <?php else: ?>
                  <p class="meta">User #<?= (int)$detail['id_user'] ?> - <?= htmlspecialchars((string)($detail['user_nom'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></p>
                  <div class="timeline" style="margin-top:8px;">
                    <?php
                      $timeline = admin_collecte_status_options($mode);
                      if ($currentStatus === 'annulee') { $timeline[] = 'annulee'; }
                      foreach ($timeline as $step):
                    ?>
                      <span class="step<?= $step === $currentStatus ? ' active' : '' ?>"><?= htmlspecialchars(admin_collecte_status_label($step), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
              <div class="actions">
                <a class="btn btn-outline btn-sm" href="planning_collecte.php?selected_id=<?= (int)$idCollecte ?>#collecte-list-section"><i class="fa-solid fa-arrow-left"></i> Retour liste</a>
              </div>
            </div>
          </section>

          <?php if ($detail): ?>
            <section class="card">
              <div class="card-header"><h3 class="card-title"><i class="fa-solid fa-circle-info"></i> Resume</h3></div>
              <div class="grid2">
                <div><div class="meta">Restaurant</div><div class="value">#<?= (int)$detail['id_restaurant'] ?> - <?= htmlspecialchars((string)($detail['restaurant_nom'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></div></div>
                <div><div class="meta">Mode</div><div class="value"><?= htmlspecialchars(admin_collecte_status_label($mode), ENT_QUOTES, 'UTF-8') ?></div></div>
                <div><div class="meta">Heure souhaitee</div><div class="value"><?= htmlspecialchars((string)$detail['heure_souhaitee'], ENT_QUOTES, 'UTF-8') ?></div></div>
                <div><div class="meta">Montant total</div><div class="value"><?= number_format((float)($detail['montant_total'] ?? 0), 2) ?> DT</div></div>
                <div><div class="meta">Statut</div><div class="value"><span class="status-pill"><?= htmlspecialchars(admin_collecte_status_label($currentStatus), ENT_QUOTES, 'UTF-8') ?></span></div></div>
                <div><div class="meta">Adresse livraison</div><div class="value"><?= htmlspecialchars((string)($detail['adresse_livraison'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></div></div>
              </div>
            </section>

            <section class="card">
              <div class="card-header"><h3 class="card-title"><i class="fa-solid fa-utensils"></i> Items</h3></div>
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
              <p class="meta" style="margin-top:8px;">Format compact: <?= htmlspecialchars(admin_collecte_items_display($detail['items'] ?? []), ENT_QUOTES, 'UTF-8') ?></p>
            </section>

            <section class="card">
              <div class="card-header"><h3 class="card-title"><i class="fa-solid fa-filter"></i> Preference snapshot</h3></div>
              <div class="grid2">
                <div><div class="meta">ID preference</div><div class="value">#<?= (int)$detail['id_pref'] ?></div></div>
                <div><div class="meta">Regime alimentaire</div><div class="value"><?= htmlspecialchars($prefRegime === '' ? '-' : $prefRegime, ENT_QUOTES, 'UTF-8') ?></div></div>
                <div><div class="meta">Allergies</div><div class="value"><?= htmlspecialchars($prefAllergies === '' ? '-' : $prefAllergies, ENT_QUOTES, 'UTF-8') ?></div></div>
                <div><div class="meta">Adresse preference</div><div class="value"><?= htmlspecialchars($prefAddress === '' ? '-' : $prefAddress, ENT_QUOTES, 'UTF-8') ?></div></div>
              </div>
            </section>

            <section class="card">
              <div class="card-header"><h3 class="card-title"><i class="fa-solid fa-gear"></i> Actions admin</h3></div>
              <div class="actions">
                <form method="post" action="../Controller/planning_collecte.php?action=admin_update_collecte_status" class="actions">
                  <input type="hidden" name="id_collecte" value="<?= (int)$detail['id_collecte'] ?>">
                  <input type="hidden" name="return_to_detail" value="1">
                  <select class="select" name="statut" style="min-width:180px;">
                    <?php foreach ($statusOptions as $statusOption): ?>
                      <option value="<?= htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8') ?>"<?= $statusOption === $currentStatus ? ' selected' : '' ?>>
                        <?= htmlspecialchars(admin_collecte_status_label($statusOption), ENT_QUOTES, 'UTF-8') ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn-outline btn-sm" type="submit">Maj statut</button>
                </form>

                <form method="post" action="../Controller/planning_collecte.php?action=admin_delete_collecte" onsubmit="return confirmAdminCollecteDetailDelete(this);" class="actions">
                  <input type="hidden" name="id_collecte" value="<?= (int)$detail['id_collecte'] ?>">
                  <input type="hidden" name="return_to_detail" value="1">
                  <input type="hidden" name="restock_on_delete" value="1">
                  <input type="hidden" name="current_status" value="<?= htmlspecialchars($currentStatus, ENT_QUOTES, 'UTF-8') ?>">
                  <button class="btn btn-danger btn-sm" type="submit">Supprimer</button>
                </form>
              </div>
            </section>
          <?php endif; ?>
        </div>
      </div>
    </main>
  </div>

  <script src="../js/app.js?v=20260420c"></script>
  <script src="../js/components.js?v=20260421a"></script>
  <script>
    function confirmAdminCollecteDetailDelete(form) {
      var statusInput = form.querySelector('input[name="current_status"]');
      var restockInput = form.querySelector('input[name="restock_on_delete"]');
      var status = statusInput ? String(statusInput.value || '').trim() : '';
      if (status === 'en_attente' || status === 'en_cours_livraison') {
        var restock = window.confirm('Voulez vous remettre cet item dans le stock ?');
        if (restockInput) {
          restockInput.value = restock ? '1' : '0';
        }
      } else if (restockInput) {
        restockInput.value = '0';
      }
      return window.confirm('Confirmer la suppression de cette collecte ?');
    }

    document.addEventListener('DOMContentLoaded', function () {
      if (window.App && typeof window.App.requireAuth === 'function') {
        App.requireAuth(['admin']);
      }
    });
  </script>
</body>
</html>
