<?php
require_once __DIR__ . '/../Controller/PlanningCollecteController.php';
require_once __DIR__ . '/../config/app.php';

$controller = new PlanningCollecteController();
$idOwner = isset($_GET['id_owner']) ? (int)$_GET['id_owner'] : 1;
if ($idOwner <= 0) {
    $idOwner = 1;
}
$idCollecte = isset($_GET['id_collecte']) ? (int)$_GET['id_collecte'] : 0;
$status = trim((string)($_GET['status'] ?? ''));
$detail = $idCollecte > 0 ? $controller->getPartnerCollecteDetail($idCollecte, $idOwner) : null;

$statusMessages = [
    'success_collecte_updated' => ['class' => 'success', 'text' => 'Statut de collecte mis a jour.'],
    'success_collecte_deleted' => ['class' => 'success', 'text' => 'Collecte supprimee.'],
    'success_driver_assigned' => ['class' => 'success', 'text' => 'Livreur assigne. Ouvrez le lien tracking sur le telephone du livreur.'],
    'error_invalid_status' => ['class' => 'error', 'text' => 'Statut invalide pour ce mode.'],
    'error_forbidden_collecte' => ['class' => 'error', 'text' => 'Vous ne pouvez pas modifier cette collecte.'],
    'error_not_found' => ['class' => 'error', 'text' => 'Collecte introuvable.'],
    'error_validation' => ['class' => 'error', 'text' => 'Validation livreur invalide.'],
    'error_db' => ['class' => 'error', 'text' => 'Erreur base de donnees.'],
];

if (!function_exists('partner_detail_status_label')) {
    function partner_detail_status_label($statusValue)
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

if (!function_exists('partner_detail_status_options')) {
    function partner_detail_status_options($mode)
    {
        $mode = strtolower(trim((string)$mode));
        return $mode === 'delivery'
            ? ['en_attente', 'en_cours_livraison', 'livree']
            : ['en_attente', 'collecte'];
    }
}

$mode = strtolower((string)($detail['mode_collecte'] ?? 'pickup'));
$currentStatus = trim((string)($detail['statut'] ?? 'en_attente'));
$statusOptions = partner_detail_status_options($mode);
if ($currentStatus !== '' && !in_array($currentStatus, $statusOptions, true)) {
    $statusOptions[] = $currentStatus;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Detail Collecte - Partenaire</title>
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
    @media (max-width: 900px) { .grid2 { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <div class="dashboard-layout">
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <div class="sidebar-logo"><img src="../assets/logo.png" alt="Logo" style="max-width:100%;max-height:100%;object-fit:contain;"></div>
        <div class="sidebar-brand">Care<span>Meal</span></div>
      </div>
      <nav class="sidebar-nav">
        <div class="sidebar-section">
          <div class="sidebar-section-title">Partenaire</div>
          <a href="dashboard.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-house"></i></span> Mon &Eacute;tablissement</a>
          <a href="restaurants.php" class="sidebar-link active"><span class="link-icon"><i class="fa-solid fa-shop"></i></span> Restaurants</a>
          <a href="offers.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-box"></i></span> Mes Offres</a>
          <a href="events.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> Evenements</a>
          <a href="settings.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-gear"></i></span> Parametres</a>
        </div>
      </nav>
      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="avatar" id="sidebar-user-avatar">P</div>
          <div class="sidebar-user-info">
            <div class="sidebar-user-name" id="sidebar-user-name">Partenaire</div>
            <div class="sidebar-user-role" id="sidebar-user-role">Partenaire</div>
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
          <div class="page-title"><h2>Detail collecte</h2><p>Vue partenaire et gestion statut</p></div>
        </div>
        <div class="header-right">
          <div class="avatar avatar-sm" id="header-avatar">P</div>
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
              <?php if ($detail): ?>
                <a class="btn btn-outline btn-sm" href="restaurants.php?id_owner=<?= (int)$idOwner ?>&selected_id=<?= (int)$detail['id_restaurant'] ?>#restaurant-collectes-section"><i class="fa-solid fa-arrow-left"></i> Retour collectes</a>
              <?php else: ?>
                <a class="btn btn-outline btn-sm" href="restaurants.php?id_owner=<?= (int)$idOwner ?>#restaurant-collectes-section"><i class="fa-solid fa-arrow-left"></i> Retour collectes</a>
              <?php endif; ?>
            </div>
            <?php if (!$detail): ?>
              <p class="meta">Collecte introuvable ou non autorisee.</p>
            <?php else: ?>
              <div class="grid2">
                <div><div class="meta">Restaurant</div><div class="value">#<?= (int)$detail['id_restaurant'] ?> - <?= htmlspecialchars((string)($detail['restaurant_nom'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></div></div>
                <div><div class="meta">Etudiant</div><div class="value">#<?= (int)$detail['id_user'] ?> - <?= htmlspecialchars((string)($detail['user_nom'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></div></div>
                <div><div class="meta">Mode</div><div class="value"><?= htmlspecialchars(partner_detail_status_label($mode), ENT_QUOTES, 'UTF-8') ?></div></div>
                <div><div class="meta">Heure</div><div class="value"><?= htmlspecialchars((string)($detail['heure_souhaitee'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div></div>
                <div><div class="meta">Montant</div><div class="value"><?= number_format((float)($detail['montant_total'] ?? 0), 2) ?> DT</div></div>
                <div><div class="meta">Statut</div><div class="value"><?= htmlspecialchars(partner_detail_status_label($currentStatus), ENT_QUOTES, 'UTF-8') ?></div></div>
              </div>
            <?php endif; ?>
          </section>

          <?php if ($detail): ?>
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
            </section>

            <section class="card">
              <div class="card-header"><h3 class="card-title"><i class="fa-solid fa-gear"></i> Actions partenaire</h3></div>
              <div class="actions">
                <form method="post" action="../Controller/planning_collecte.php?action=partner_update_collecte_status" class="actions">
                  <input type="hidden" name="id_collecte" value="<?= (int)$detail['id_collecte'] ?>">
                  <input type="hidden" name="id_owner" class="js-owner-id" value="<?= (int)$idOwner ?>">
                  <input type="hidden" name="return_to_detail" value="1">
                  <select class="select" name="statut" style="min-width:180px;">
                    <?php foreach ($statusOptions as $statusOption): ?>
                      <option value="<?= htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8') ?>"<?= $statusOption === $currentStatus ? ' selected' : '' ?>>
                        <?= htmlspecialchars(partner_detail_status_label($statusOption), ENT_QUOTES, 'UTF-8') ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn-outline btn-sm" type="submit">Maj statut</button>
                </form>

                <form method="post" action="../Controller/planning_collecte.php?action=partner_delete_collecte" onsubmit="return confirmPartnerCollecteDetailDelete(this);" class="actions">
                  <input type="hidden" name="id_collecte" value="<?= (int)$detail['id_collecte'] ?>">
                  <input type="hidden" name="id_owner" class="js-owner-id" value="<?= (int)$idOwner ?>">
                  <input type="hidden" name="return_to_detail" value="1">
                  <input type="hidden" name="restock_on_delete" value="1">
                  <input type="hidden" name="current_status" value="<?= htmlspecialchars($currentStatus, ENT_QUOTES, 'UTF-8') ?>">
                  <button class="btn btn-danger btn-sm" type="submit">Supprimer</button>
                </form>
              </div>
            </section>

            <?php if ($mode === 'delivery'): ?>
              <section class="card">
                <div class="card-header"><h3 class="card-title"><i class="fa-solid fa-motorcycle"></i> Assignation livreur (tracking live)</h3></div>
                <form method="post" action="../Controller/planning_collecte.php?action=partner_assign_driver" class="actions" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr)) auto;gap:8px;align-items:end;">
                  <input type="hidden" name="id_collecte" value="<?= (int)$detail['id_collecte'] ?>">
                  <input type="hidden" name="id_owner" class="js-owner-id" value="<?= (int)$idOwner ?>">
                  <input type="hidden" name="return_to_detail" value="1">

                  <div>
                    <label>Nom</label>
                    <input class="select" type="text" name="driver_last_name" value="<?= htmlspecialchars((string)($detail['delivery_driver_last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Nom livreur">
                  </div>
                  <div>
                    <label>Prenom</label>
                    <input class="select" type="text" name="driver_first_name" value="<?= htmlspecialchars((string)($detail['delivery_driver_first_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Prenom livreur">
                  </div>
                  <div>
                    <label>Contact</label>
                    <input class="select" type="text" name="driver_contact" value="<?= htmlspecialchars((string)($detail['delivery_driver_contact'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="+216...">
                  </div>
                  <button class="btn btn-outline btn-sm" type="submit">Assigner + activer tracking</button>
                </form>

                <?php if (!empty($detail['delivery_tracking_token'])): ?>
                  <?php
                    $trackingToken = urlencode((string)$detail['delivery_tracking_token']);
                    $trackingUrlRelative = '../public/delivery_live.php?token=' . $trackingToken;
                    $trackingUrlAbsolute = caremeal_public_url('/public/delivery_live.php?token=' . $trackingToken);
                    $trackingQrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . rawurlencode($trackingUrlAbsolute);
                  ?>
                  <div style="margin-top:10px;display:grid;gap:8px;">
                    <div class="meta">Lien a ouvrir sur le telephone du livreur:</div>
                    <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars($trackingUrlRelative, ENT_QUOTES, 'UTF-8') ?>" target="_blank">Ouvrir tracking livreur</a>
                    <a class="btn btn-outline btn-sm" href="<?= htmlspecialchars($trackingUrlAbsolute, ENT_QUOTES, 'UTF-8') ?>" target="_blank">Ouvrir lien public (telephone)</a>
                    <div style="display:flex;gap:8px;align-items:center;">
                      <input id="driverTrackingUrlInput" class="select" type="text" readonly value="<?= htmlspecialchars($trackingUrlAbsolute, ENT_QUOTES, 'UTF-8') ?>">
                      <button class="btn btn-outline btn-sm" type="button" id="copyDriverTrackingUrlBtn">Copier lien</button>
                    </div>
                    <div style="display:grid;gap:6px;justify-content:start;">
                      <div class="meta">Scanner avec le telephone du livreur:</div>
                      <img src="<?= htmlspecialchars($trackingQrUrl, ENT_QUOTES, 'UTF-8') ?>" alt="QR tracking livreur" style="width:140px;height:140px;border-radius:10px;border:1px solid var(--color-dark-border);background:#fff;">
                    </div>
                    <div class="meta">Pour demo externe (ngrok/cloud), ce lien doit commencer par votre domaine public et non localhost.</div>
                    <div class="meta">Derniere position GPS: <?= htmlspecialchars((string)($detail['delivery_driver_updated_at'] ?? 'non recue'), ENT_QUOTES, 'UTF-8') ?></div>
                  </div>
                <?php endif; ?>
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
    function confirmPartnerCollecteDetailDelete(form) {
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
      if (!App.requireAuth(['partner'])) return;
      var user = App.getCurrentUser();
      var match = String((user && user.id) ? user.id : '').match(/(\d+)$/);
      if (match) {
        var resolvedId = parseInt(match[1], 10);
        var url = new URL(window.location.href);
        var queryId = parseInt(url.searchParams.get('id_owner') || '0', 10);
        if (!queryId || queryId !== resolvedId) {
          url.searchParams.set('id_owner', String(resolvedId));
          window.location.replace(url.toString());
          return;
        }
        document.querySelectorAll('.js-owner-id').forEach(function (el) { el.value = String(resolvedId); });
      }

      var copyBtn = document.getElementById('copyDriverTrackingUrlBtn');
      var urlInput = document.getElementById('driverTrackingUrlInput');
      if (copyBtn && urlInput) {
        copyBtn.addEventListener('click', function () {
          var value = String(urlInput.value || '').trim();
          if (!value) {
            return;
          }
          if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(value).then(function () {
              copyBtn.textContent = 'Copie';
              setTimeout(function () {
                copyBtn.textContent = 'Copier lien';
              }, 1200);
            }).catch(function () {
              urlInput.select();
              document.execCommand('copy');
            });
          } else {
            urlInput.select();
            document.execCommand('copy');
            copyBtn.textContent = 'Copie';
            setTimeout(function () {
              copyBtn.textContent = 'Copier lien';
            }, 1200);
          }
        });
      }
    });
  </script>
</body>
</html>
