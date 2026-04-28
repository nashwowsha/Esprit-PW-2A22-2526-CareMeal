<?php
require_once __DIR__ . '/../Controller/MatchingController.php';

$idUser = isset($_GET['id_user']) ? (int)$_GET['id_user'] : 1;
if ($idUser <= 0) {
    $idUser = 1;
}
$idPref = isset($_GET['id_pref']) ? (int)$_GET['id_pref'] : 0;
$idRestaurant = isset($_GET['id_restaurant']) ? (int)$_GET['id_restaurant'] : 0;

$matchingController = new MatchingController();
$detailResult = $matchingController->getMatchedRestaurantForPreference($idUser, $idPref, $idRestaurant);

$statusMessages = [
    'success' => ['class' => 'success', 'text' => 'Restaurant loaded from matching results.'],
    'error_invalid_user' => ['class' => 'error', 'text' => 'Invalid user.'],
    'error_invalid_preference' => ['class' => 'error', 'text' => 'Invalid preference.'],
    'error_preference_not_found' => ['class' => 'error', 'text' => 'Preference not found.'],
    'error_forbidden_preference' => ['class' => 'error', 'text' => 'Forbidden preference access.'],
    'error_restaurant_not_found' => ['class' => 'error', 'text' => 'Restaurant not found.'],
    'error_restaurant_not_matched' => ['class' => 'error', 'text' => 'This restaurant is not compatible with selected preference.'],
];

$selectedPreference = $detailResult['preference'] ?? null;
$restaurant = $detailResult['restaurant'] ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Meals compatibles pour le restaurant choisi apres matching.">
  <title>Meals compatibles - CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <style>
    .alert-box {
      border-radius: var(--radius-md);
      padding: 12px 14px;
      margin-bottom: 16px;
      border: 1px solid transparent;
      font-size: 0.9rem;
    }
    .alert-box.success {
      color: #0f5132;
      background: rgba(25, 135, 84, 0.18);
      border-color: rgba(25, 135, 84, 0.4);
    }
    .alert-box.error {
      color: #842029;
      background: rgba(220, 53, 69, 0.16);
      border-color: rgba(220, 53, 69, 0.4);
    }
    .meal-table { width: 100%; border-collapse: collapse; }
    .meal-table th, .meal-table td {
      border-bottom: 1px solid var(--color-dark-border);
      padding: 10px;
      text-align: left;
      vertical-align: top;
    }
    .qty-picker { display: inline-flex; align-items: center; gap: 8px; }
    .qty-btn {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      border: 1px solid var(--color-dark-border);
      background: rgba(255,255,255,.05);
      color: var(--color-white);
      cursor: pointer;
    }
    .qty-value { min-width: 24px; text-align: center; font-weight: 700; }
    .restaurant-cover {
      width: 100%;
      max-height: 220px;
      object-fit: cover;
      border-radius: var(--radius-md);
      border: 1px solid var(--color-dark-border);
      margin-bottom: 12px;
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
          <a href="preferences.php?id_user=<?= (int)$idUser ?>" class="sidebar-link active"><span class="link-icon"><i class="fa-solid fa-utensils"></i></span> Preferences</a>
          <a href="events.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> Evenements</a>
          <a href="mes_collectes.php?id_user=<?= (int)$idUser ?>" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-box"></i></span> Mes Collectes</a>
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
            <h2>Meals compatibles</h2>
            <p>Choisissez les quantites a collecter (etape collecte ensuite)</p>
          </div>
        </div>
        <div class="header-right">
          <button class="header-notification"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button>
          <div class="avatar avatar-sm" id="header-avatar">AA</div>
        </div>
      </header>

      <div class="page-content">
        <?php $status = (string)($detailResult['status'] ?? ''); ?>
        <?php if (isset($statusMessages[$status])): ?>
          <div class="alert-box <?= htmlspecialchars((string)$statusMessages[$status]['class'], ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars((string)$statusMessages[$status]['text'], ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>

        <div class="card" style="margin-bottom:16px;">
          <div class="card-header" style="justify-content:space-between;align-items:center;">
            <h3 class="card-title"><i class="fa-solid fa-arrow-left"></i> Navigation</h3>
            <a class="btn btn-outline btn-sm" href="matching.php?id_user=<?= (int)$idUser ?>&id_pref=<?= (int)$idPref ?>">Back to matching list</a>
          </div>
          <?php if ($selectedPreference): ?>
            <p style="color:var(--color-text-muted);font-size:.86rem;margin:0;">
              Preference #<?= (int)$selectedPreference['id_pref'] ?> |
              Regime: <?= htmlspecialchars((string)$selectedPreference['regime_alimentaire'], ENT_QUOTES, 'UTF-8') ?> |
              Allergies: <?= htmlspecialchars((string)$selectedPreference['allergies'], ENT_QUOTES, 'UTF-8') ?>
            </p>
          <?php endif; ?>
        </div>

        <div class="card">
          <?php if (!$detailResult['ok'] || !$restaurant): ?>
            <p style="color:var(--color-text-muted);">Restaurant indisponible pour cette preference.</p>
          <?php else: ?>
            <?php
              $imagePath = trim((string)($restaurant['image_path'] ?? ''));
              if ($imagePath === '') {
                  $imagePath = 'assets/logo.png';
              }
            ?>
            <img class="restaurant-cover" src="../<?= htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') ?>" alt="restaurant">
            <div class="card-header" style="padding:0 0 10px 0;justify-content:space-between;align-items:center;">
              <h3 class="card-title"><?= htmlspecialchars((string)$restaurant['nom'], ENT_QUOTES, 'UTF-8') ?></h3>
              <span class="badge badge-primary">Score <?= (int)$restaurant['score'] ?></span>
            </div>
            <p style="color:var(--color-text-muted);font-size:.85rem;margin-top:0;">
              <i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars((string)$restaurant['localisation'], ENT_QUOTES, 'UTF-8') ?>
              &nbsp; | &nbsp;
              <i class="fa-solid fa-clock"></i> <?= htmlspecialchars((string)$restaurant['horaires'], ENT_QUOTES, 'UTF-8') ?>
            </p>

            <div class="table-container">
              <table class="meal-table" id="matching-meals-table">
                <thead>
                  <tr>
                    <th>Meal</th>
                    <th>Ingredients</th>
                    <th>Stock dispo</th>
                    <th>Prix</th>
                    <th>Choix</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach (($restaurant['matched_meals'] ?? []) as $meal): ?>
                    <tr data-meal-id="<?= htmlspecialchars((string)$meal['meal_id'], ENT_QUOTES, 'UTF-8') ?>" data-stock="<?= (int)$meal['quantity'] ?>" data-price="<?= number_format((float)$meal['price'], 2, '.', '') ?>" data-mode="<?= htmlspecialchars((string)$meal['pricing_mode'], ENT_QUOTES, 'UTF-8') ?>">
                      <td><?= htmlspecialchars((string)$meal['meal_name'], ENT_QUOTES, 'UTF-8') ?></td>
                      <td><?= htmlspecialchars((string)$meal['ingredients'], ENT_QUOTES, 'UTF-8') ?></td>
                      <td><?= (int)$meal['quantity'] ?></td>
                      <td><?= (($meal['pricing_mode'] ?? 'free') === 'paid') ? number_format((float)$meal['price'], 2) . ' DT' : 'Free' ?></td>
                      <td>
                        <div class="qty-picker">
                          <button type="button" class="qty-btn" data-action="minus">-</button>
                          <span class="qty-value">0</span>
                          <button type="button" class="qty-btn" data-action="plus">+</button>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <div style="margin-top:14px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
              <span class="badge badge-info">Montant total: <span id="matching-total">0.00</span> DT</span>
              <button type="button" class="btn btn-primary btn-sm" id="matching-validate-btn">Valider le choix</button>
              <span style="color:var(--color-text-muted);font-size:.82rem;">Apres validation, vous serez redirige vers le formulaire de collecte.</span>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </main>
  </div>

  <script src="../js/app.js"></script>
  <script src="../js/components.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      if (!App.requireAuth(['student'])) return;

      const rows = document.querySelectorAll('#matching-meals-table tbody tr');
      const totalEl = document.getElementById('matching-total');
      const validateBtn = document.getElementById('matching-validate-btn');

      function parseNum(value) {
        const n = Number(value);
        return Number.isFinite(n) ? n : 0;
      }

      function computeTotal() {
        let total = 0;
        rows.forEach((row) => {
          const qty = parseInt(row.querySelector('.qty-value')?.textContent || '0', 10);
          const mode = String(row.getAttribute('data-mode') || 'free');
          const unitPrice = parseNum(row.getAttribute('data-price'));
          if (qty > 0 && mode === 'paid') {
            total += unitPrice * qty;
          }
        });
        if (totalEl) totalEl.textContent = total.toFixed(2);
      }

      rows.forEach((row) => {
        const stock = parseInt(row.getAttribute('data-stock') || '0', 10);
        const valueEl = row.querySelector('.qty-value');
        const minusBtn = row.querySelector('[data-action="minus"]');
        const plusBtn = row.querySelector('[data-action="plus"]');

        function current() {
          return parseInt(valueEl?.textContent || '0', 10);
        }

        function setQty(q) {
          const bounded = Math.max(0, Math.min(stock, q));
          if (valueEl) valueEl.textContent = String(bounded);
          if (minusBtn) minusBtn.disabled = bounded <= 0;
          if (plusBtn) plusBtn.disabled = bounded >= stock;
          computeTotal();
        }

        if (minusBtn) {
          minusBtn.addEventListener('click', () => setQty(current() - 1));
        }
        if (plusBtn) {
          plusBtn.addEventListener('click', () => setQty(current() + 1));
        }
        setQty(0);
      });

      if (validateBtn) {
        validateBtn.addEventListener('click', () => {
          const selected = [];
          rows.forEach((row) => {
            const mealId = String(row.getAttribute('data-meal-id') || '');
            const qty = parseInt(row.querySelector('.qty-value')?.textContent || '0', 10);
            if (mealId && qty > 0) {
              selected.push({ meal_id: mealId, quantity: qty });
            }
          });

          if (selected.length === 0) {
            alert('Choisis au moins un meal.');
            return;
          }
          const redirectForm = document.createElement('form');
          redirectForm.method = 'post';
          redirectForm.action = '/caremeal/student/create_collecte.php';
          redirectForm.style.display = 'none';

          const fields = [
            { name: 'id_user', value: '<?= (int)$idUser ?>' },
            { name: 'id_pref', value: '<?= (int)$idPref ?>' },
            { name: 'id_restaurant', value: '<?= (int)$idRestaurant ?>' },
            { name: 'items_json', value: JSON.stringify(selected) },
            { name: 'montant_total', value: String(totalEl ? totalEl.textContent : '0.00') }
          ];

          fields.forEach((field) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = field.name;
            input.value = field.value;
            redirectForm.appendChild(input);
          });

          document.body.appendChild(redirectForm);
          redirectForm.submit();
        });
      }

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
      }
    });
  </script>
</body>
</html>
