<?php
require_once __DIR__ . '/../View/session_check.php';
require_once __DIR__ . '/../Controller/MatchingController.php';

$idUser = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($idUser <= 0) {
    header('Location: ../View/FrontOffice/login.php');
    exit;
}
$idPref = isset($_GET['id_pref']) ? (int)$_GET['id_pref'] : 0;
$idRestaurant = isset($_GET['id_restaurant']) ? (int)$_GET['id_restaurant'] : 0;
$safetyMode = isset($_GET['safety_mode']) ? strtolower(trim((string)$_GET['safety_mode'])) : 'strict';
$priceMode = isset($_GET['price_mode']) ? strtolower(trim((string)$_GET['price_mode'])) : 'all';
$sortBy = isset($_GET['sort_by']) ? strtolower(trim((string)$_GET['sort_by'])) : 'score';
if (!in_array($safetyMode, ['strict', 'souple'], true)) {
    $safetyMode = 'strict';
}
if (!in_array($priceMode, ['all', 'free', 'paid'], true)) {
    $priceMode = 'all';
}
if (!in_array($sortBy, ['score', 'location', 'name'], true)) {
    $sortBy = 'score';
}

$matchingController = new MatchingController();
$detailResult = $matchingController->getMatchedRestaurantForPreference($idUser, $idPref, $idRestaurant, [
    'safety_mode' => $safetyMode,
    'price_mode' => $priceMode,
    'sort_by' => $sortBy,
]);

$statusMessages = [
    'success' => ['class' => 'success', 'text' => 'Restaurant loaded from matching results.'],
    'analysis_required' => ['class' => 'error', 'text' => 'Matching indisponible: des meals sont en attente d analyse IA.'],
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
  <link rel="stylesheet" href="../css/theme-fix.css">
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
    .meal-table thead th {
      color: #dbe8ff;
      font-weight: 700;
      font-size: .86rem;
      letter-spacing: .2px;
    }
    .meal-table tbody tr {
      transition: background .18s ease;
    }
    .meal-table tbody tr:hover {
      background: rgba(255,255,255,.03);
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
      transition: all .16s ease;
    }
    .qty-btn:hover {
      border-color: rgba(255,255,255,.34);
      background: rgba(255,255,255,.11);
    }
    .qty-btn:disabled {
      opacity: .45;
      cursor: not-allowed;
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
    .meal-layout {
      display: grid;
      grid-template-columns: minmax(0, 1fr) 320px;
      gap: 14px;
      align-items: start;
    }
    .meal-ai-panel {
      border: 1px solid var(--color-dark-border);
      border-radius: var(--radius-md);
      background: linear-gradient(180deg, rgba(255,255,255,.045), rgba(255,255,255,.018));
      padding: 14px;
      display: grid;
      gap: 10px;
      position: sticky;
      top: 16px;
    }
    .meal-ai-title {
      margin: 0;
      font-size: 1rem;
      color: var(--color-white);
    }
    .meal-ai-text {
      margin: 0;
      color: #c5d7f6;
      font-size: .84rem;
      line-height: 1.5;
    }
    .meal-ai-list {
      margin: 0;
      padding-left: 18px;
      color: var(--color-text-muted);
      font-size: .82rem;
      line-height: 1.45;
    }
    .meal-ai-content {
      display: grid;
      gap: 10px;
    }
    .meal-ai-block {
      border: 1px solid var(--color-dark-border);
      border-radius: 12px;
      padding: 10px 11px;
      background: rgba(9,18,33,.55);
    }
    .meal-ai-block-title {
      margin: 0 0 6px 0;
      font-size: .84rem;
      color: #f3f7ff;
      font-weight: 700;
    }
    .meal-ai-line {
      margin: 0;
      color: #bdd0ee;
      font-size: .82rem;
      line-height: 1.45;
      margin-bottom: 4px;
    }
    .meal-ai-chip-row {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      margin-top: 2px;
    }
    .meal-ai-chip {
      font-size: .73rem;
      border-radius: 999px;
      padding: 4px 9px;
      border: 1px solid var(--color-dark-border);
      color: #dce8ff;
      background: rgba(255,255,255,.04);
    }
    .meal-ai-chip.success {
      border-color: rgba(25,135,84,.45);
      background: rgba(25,135,84,.2);
      color: #baf6cf;
    }
    .meal-ai-chip.warn {
      border-color: rgba(255,193,7,.5);
      background: rgba(255,193,7,.16);
      color: #ffe7a8;
    }
    .meal-ai-chip.risk {
      border-color: rgba(220,53,69,.45);
      background: rgba(220,53,69,.18);
      color: #ffd2d8;
    }
    .meal-ai-warning {
      margin: 0;
      font-size: .8rem;
      line-height: 1.45;
      color: #ffd8d8;
      background: rgba(220,53,69,.13);
      border: 1px solid rgba(220,53,69,.35);
      border-radius: 8px;
      padding: 8px 10px;
    }
    .source-badge {
      border-radius: 999px;
      font-size: .72rem;
      font-weight: 700;
      padding: 5px 9px;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      border: 1px solid var(--color-dark-border);
      color: #dce8ff;
      background: rgba(255,255,255,.05);
    }
    .source-vendor {
      border-color: rgba(25,135,84,.45);
      color: #b9f2cc;
      background: rgba(25,135,84,.18);
    }
    .source-ingredients {
      border-color: rgba(13,110,253,.4);
      color: #bcd6ff;
      background: rgba(13,110,253,.17);
    }
    .source-recipe {
      border-color: rgba(255,193,7,.45);
      color: #ffe4a0;
      background: rgba(255,193,7,.14);
    }
    @media (max-width: 1080px) {
      .meal-layout {
        grid-template-columns: 1fr;
      }
      .meal-ai-panel {
        position: static;
      }
    }

    /* Light-mode readability overrides */
    .meal-table thead th {
      color: #334155 !important;
      background: #F8FAFC;
    }
    .meal-table th,
    .meal-table td {
      border-bottom-color: #E2E8F0 !important;
      color: #0F172A;
    }
    .meal-table tbody tr:hover {
      background: #F8FAFC !important;
    }
    .qty-btn {
      color: #0F172A !important;
      background: #FFFFFF !important;
      border-color: #CBD5E1 !important;
    }
    .meal-ai-panel {
      background: #FFFFFF !important;
      border-color: #E2E8F0 !important;
      box-shadow: 0 4px 16px rgba(15, 23, 42, 0.08);
    }
    .meal-ai-title,
    .meal-ai-text,
    .meal-ai-list,
    .meal-ai-block-title,
    .meal-ai-line {
      color: #0F172A !important;
    }
    .meal-ai-block {
      background: #F8FAFC !important;
      border-color: #E2E8F0 !important;
    }
    .meal-ai-chip {
      color: #334155 !important;
      background: #EEF2FF !important;
      border-color: #CBD5E1 !important;
    }
    .meal-ai-chip.success {
      color: #065F46 !important;
      background: #D1FAE5 !important;
      border-color: #6EE7B7 !important;
    }
    .meal-ai-chip.warn {
      color: #92400E !important;
      background: #FEF3C7 !important;
      border-color: #FCD34D !important;
    }
    .meal-ai-chip.risk {
      color: #991B1B !important;
      background: #FEE2E2 !important;
      border-color: #FCA5A5 !important;
    }
    .meal-ai-warning {
      color: #991B1B !important;
      background: #FEF2F2 !important;
      border-color: #FECACA !important;
    }
    .source-badge {
      color: #1E293B !important;
      background: #F8FAFC !important;
      border-color: #CBD5E1 !important;
    }
    .source-vendor {
      color: #065F46 !important;
      background: #D1FAE5 !important;
      border-color: #6EE7B7 !important;
    }
    .source-ingredients {
      color: #1D4ED8 !important;
      background: #DBEAFE !important;
      border-color: #93C5FD !important;
    }
    .source-recipe {
      color: #92400E !important;
      background: #FEF3C7 !important;
      border-color: #FCD34D !important;
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
            <a class="btn btn-outline btn-sm" href="matching.php?id_user=<?= (int)$idUser ?>&id_pref=<?= (int)$idPref ?>&safety_mode=<?= urlencode($safetyMode) ?>&price_mode=<?= urlencode($priceMode) ?>&sort_by=<?= urlencode($sortBy) ?>">Back to matching list</a>
          </div>
          <?php if ($selectedPreference): ?>
            <p style="color:var(--color-text-muted);font-size:.86rem;margin:0;">
              Preference #<?= (int)$selectedPreference['id_pref'] ?> |
              Regime: <?= htmlspecialchars((string)$selectedPreference['regime_alimentaire'], ENT_QUOTES, 'UTF-8') ?> |
              Allergies: <?= htmlspecialchars((string)$selectedPreference['allergies'], ENT_QUOTES, 'UTF-8') ?> |
              Mode securite: <?= $safetyMode === 'souple' ? 'Souple' : 'Strict' ?>
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
              $matchingBrief = is_array($restaurant['matching_brief'] ?? null) ? $restaurant['matching_brief'] : [];
              $userAllergyInputMap = is_array($matchingBrief['user_allergy_input_map'] ?? null) ? $matchingBrief['user_allergy_input_map'] : [];
              $userAllergiesRaw = (string)($matchingBrief['user_allergies_raw'] ?? ($selectedPreference['allergies'] ?? ''));
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

            <div class="meal-layout">
              <div class="table-container">
                <table class="meal-table" id="matching-meals-table">
                  <thead>
                    <tr>
                      <th>Meal</th>
                      <th>Ingredients</th>
                      <th>Source allergenes</th>
                      <th>Stock dispo</th>
                      <th>Prix</th>
                      <th>Choix</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach (($restaurant['matched_meals'] ?? []) as $meal): ?>
                      <?php
                        $originRaw = (string)($meal['ai_origin_badge'] ?? 'Vendor declared');
                        $originLower = strtolower($originRaw);
                        if (strpos($originLower, 'vendor') !== false) {
                            $originLabel = 'Declare par vendeur';
                            $originClass = 'source-vendor';
                        } elseif (strpos($originLower, 'basic recipe') !== false) {
                            $originLabel = 'IA: recette de base';
                            $originClass = 'source-recipe';
                        } else {
                            $originLabel = 'IA: depuis ingredients';
                            $originClass = 'source-ingredients';
                        }
                        $iaTooltip = 'Source: ' . $originLabel . ' | Cliquez sur le meal pour charger les details IA.';
                      ?>
                      <tr
                        class="js-meal-row"
                        data-meal-id="<?= htmlspecialchars((string)$meal['meal_id'], ENT_QUOTES, 'UTF-8') ?>"
                        data-stock="<?= (int)$meal['quantity'] ?>"
                        data-price="<?= number_format((float)$meal['price'], 2, '.', '') ?>"
                        data-mode="<?= htmlspecialchars((string)$meal['pricing_mode'], ENT_QUOTES, 'UTF-8') ?>"
                        tabindex="0"
                      >
                        <td><?= htmlspecialchars((string)$meal['meal_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$meal['ingredients'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td title="<?= htmlspecialchars($iaTooltip, ENT_QUOTES, 'UTF-8') ?>">
                          <span class="source-badge <?= htmlspecialchars((string)$originClass, ENT_QUOTES, 'UTF-8') ?>">
                            <i class="fa-solid fa-circle-info"></i>
                            <?= htmlspecialchars($originLabel, ENT_QUOTES, 'UTF-8') ?>
                          </span>
                          <?php if (!empty($meal['ai_warning'])): ?>
                            <span class="badge badge-warning"><i class="fa-solid fa-triangle-exclamation"></i></span>
                          <?php endif; ?>
                          <?php if (!empty($meal['warning_unknown'])): ?>
                            <span class="badge badge-danger">Meal inconnu (IA non certaine)</span>
                          <?php endif; ?>
                          <?php if (!empty($meal['conflict_hard'])): ?>
                            <?php if ($safetyMode === 'strict'): ?>
                              <span class="badge badge-danger">Bloque (factuel)</span>
                            <?php else: ?>
                              <span class="badge badge-danger">Risque allergene (fort)</span>
                            <?php endif; ?>
                          <?php elseif (!empty($meal['conflict_soft'])): ?>
                            <span class="badge badge-warning">Risque IA</span>
                          <?php else: ?>
                            <span class="badge badge-success">Safe</span>
                          <?php endif; ?>
                          <?php if (!empty($meal['warning_regime'])): ?>
                            <span class="badge badge-info">Regime non compatible</span>
                          <?php endif; ?>
                        </td>
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

              <aside class="meal-ai-panel" id="meal-ai-panel">
                <h4 class="meal-ai-title"><i class="fa-solid fa-robot"></i> Meal AI assistant</h4>
                <p class="meal-ai-text" id="meal-ai-summary">Clique sur un meal pour charger l'analyse IA detaillee.</p>
                <div class="meal-ai-content" id="meal-ai-content">
                  <div class="meal-ai-block">
                    <p class="meal-ai-block-title">Analyse a la demande</p>
                    <p class="meal-ai-line">L analyse IA n est pas chargee au refresh pour accelerer la page.</p>
                    <p class="meal-ai-line">Selectionne une ligne meal pour lancer l analyse detaillee.</p>
                  </div>
                </div>
              </aside>
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
  <script src="../js/student-layout.js"></script>
  <script src="../js/student-voice-assistant.js?v=20260508"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      if (!App.requireAuth(['student'])) return;

      const rows = document.querySelectorAll('#matching-meals-table tbody tr');
      const totalEl = document.getElementById('matching-total');
      const validateBtn = document.getElementById('matching-validate-btn');
      const aiSummary = document.getElementById('meal-ai-summary');
      const aiContent = document.getElementById('meal-ai-content');
      const mealAiCache = new Map();
      const idUser = <?= (int)$idUser ?>;
      const idPref = <?= (int)$idPref ?>;
      const idRestaurant = <?= (int)$idRestaurant ?>;
      const safetyMode = '<?= htmlspecialchars($safetyMode, ENT_QUOTES, 'UTF-8') ?>';
      const priceMode = '<?= htmlspecialchars($priceMode, ENT_QUOTES, 'UTF-8') ?>';
      const sortBy = '<?= htmlspecialchars($sortBy, ENT_QUOTES, 'UTF-8') ?>';

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

      function escHtml(value) {
        return String(value)
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#039;');
      }

      function renderMealAiLoading(mealLabel) {
        if (!aiSummary || !aiContent) return;
        aiSummary.textContent = `${mealLabel || 'Meal'} - chargement IA...`;
        aiContent.innerHTML = `
          <div class="meal-ai-block">
            <p class="meal-ai-block-title">Chargement IA...</p>
            <p class="meal-ai-line">Analyse en cours. Merci de patienter quelques secondes.</p>
          </div>
        `;
      }

      function renderMealAiError(message) {
        if (!aiSummary || !aiContent) return;
        aiSummary.textContent = 'Meal AI assistant';
        aiContent.innerHTML = `
          <div class="meal-ai-block">
            <p class="meal-ai-block-title">Analyse indisponible</p>
            <p class="meal-ai-line">${escHtml(message || 'Impossible de charger l analyse pour ce meal.')}</p>
          </div>
        `;
      }

      function renderMealAssistant(payload) {
        if (!aiSummary || !aiContent || !payload) return;
        const originLabelMap = {
          text_direct: 'Correspondance preference',
          vendor_declared: 'Declare par vendeur',
          ai_from_ingredients: 'Inference IA (ingredients)',
          ai_from_basic_recipe: 'Inference IA (recette de base)',
          ai_unknown: 'Meal inconnu pour l IA',
          ai_pending: 'Analyse IA en attente',
          fallback_local: 'Analyse locale',
          ai_precomputed: 'Analyse IA pre-calculee'
        };
        const formatOriginLabel = (origin, originLabelRaw) => {
          const byPayload = String(originLabelRaw || '').trim();
          if (byPayload) return byPayload;
          const key = String(origin || '').trim().toLowerCase();
          return originLabelMap[key] || 'Inference IA';
        };
        const scopeLabelMap = {
          vendor_declared: 'declaration vendeur',
          vendor_ingredients: 'ingredients vendeur',
          basic_missing_ingredients: 'ingredient manquant estime par l IA',
          text_direct: 'Correspondance preference'
        };
        const formatScopeLabels = (scopes) => {
          const labels = (Array.isArray(scopes) ? scopes : [])
            .map((scope) => scopeLabelMap[String(scope || '').trim()] || '')
            .filter(Boolean);
          return labels.length ? labels.join(' + ') : 'source non precisee';
        };
        const allergens = Array.isArray(payload.semantic_allergens)
          ? payload.semantic_allergens
          : (Array.isArray(payload.inferred_allergens) ? payload.inferred_allergens : []);
        const causalDetails = Array.isArray(payload.causal_allergen_details) ? payload.causal_allergen_details : [];
        const confidence = Math.round((Number(payload.confidence || 0) * 100));
        const dataQualityPct = Math.round((Number(payload.data_quality_confidence || 0) * 100));
        const allergenInferencePct = Math.round((Number(payload.allergen_inference_confidence || 0) * 100));
        const completenessScore = Number(payload.ingredient_completeness_score || 0);
        const hardConflicts = Array.isArray(payload.conflict_hard) ? payload.conflict_hard : [];
        const softConflicts = Array.isArray(payload.conflict_soft) ? payload.conflict_soft : [];
        const warningRegime = Boolean(payload.warning_regime);
        const warningUnknown = Boolean(payload.warning_unknown);
        aiSummary.textContent = String(payload.meal_name || 'Meal') + ' - lecture simplifiee IA';

        const missingIngredients = Array.isArray(payload.missing_ingredients) ? payload.missing_ingredients : [];
        const basicRecipeIngredients = Array.isArray(payload.basic_recipe_ingredients) ? payload.basic_recipe_ingredients : [];
        const showBasicRecipe = completenessScore < 60 || Boolean(payload.possible_missing_ingredients);
        const hasMissingSignal = Boolean(payload.possible_missing_ingredients);
        const warningText = payload.legal_warning
          ? payload.legal_warning
          : (String(payload.origin_badge || '').toLowerCase().indexOf('vendor') !== -1
              ? 'Aucune intervention IA necessaire sur les allergenes declares.'
              : 'Allergenes non listes par le vendeur, estimes par l IA selon les informations disponibles.');
        const causalLines = causalDetails.map((row) => {
          if (!row || typeof row !== 'object') return '';
          const allergen = String(row.allergen || '').trim();
          const triggers = Array.isArray(row.trigger_ingredients) ? row.trigger_ingredients.filter(Boolean) : [];
          const scopes = Array.isArray(row.source_scopes) ? row.source_scopes : [];
          const triggerText = triggers.length
            ? triggers.join(', ')
            : (scopes.indexOf('vendor_declared') !== -1 ? 'allergene declare par le vendeur' : 'source non precisee');
          return `<p class="meal-ai-line"><strong>${escHtml(allergen || 'allergene')}</strong> -> ingredients responsables: ${escHtml(triggerText)} | source: ${escHtml(formatScopeLabels(scopes))}</p>`;
        }).filter(Boolean);
        const qualityChip = completenessScore >= 80
          ? '<span class="meal-ai-chip success">donnees vendeur claires</span>'
          : '<span class="meal-ai-chip warn">donnees vendeur partielles</span>';
        const riskChip = payload.potential_conflict
          ? '<span class="meal-ai-chip risk">risque pour votre profil</span>'
          : '<span class="meal-ai-chip success">pas de conflit direct detecte</span>';
        const regimeChip = warningRegime
          ? '<span class="meal-ai-chip warn">regime non compatible</span>'
          : '<span class="meal-ai-chip success">regime compatible</span>';
        const unknownChip = warningUnknown
          ? '<span class="meal-ai-chip risk">meal inconnu (IA non certaine)</span>'
          : '';

        const conflictTokens = Array.isArray(payload.conflict_tokens) ? payload.conflict_tokens : [];
        const userInputMapping = Array.isArray(payload.user_input_mapping) ? payload.user_input_mapping : [];
        const userInputLines = userInputMapping
          .map((entry) => {
            if (!entry || typeof entry !== 'object') return '';
            const raw = String(entry.raw_term || '').trim();
            const mapped = Array.isArray(entry.mapped_allergens) ? entry.mapped_allergens : [];
            if (!raw || !mapped.length) return '';
            return `${raw} -> ${mapped.join(', ')}`;
          })
          .filter(Boolean);

        const conflictLine = (row) => {
          if (!row || typeof row !== 'object') return '';
          const allergen = String(row.allergen || '').trim() || '-';
          const origin = formatOriginLabel(row.origin, row.origin_label);
          const triggers = Array.isArray(row.trigger_ingredients) ? row.trigger_ingredients.filter(Boolean) : [];
          const scopes = Array.isArray(row.source_scopes) ? row.source_scopes : [];
          const scopeLabel = formatScopeLabels(scopes);
          const ingredientText = triggers.length ? triggers.join(', ') : (scopes.indexOf('vendor_declared') !== -1 ? 'allergene declare par le vendeur' : 'source non precisee');
          return `<p class="meal-ai-line"><strong>${escHtml(allergen)}</strong> | source: ${escHtml(origin)} (${escHtml(scopeLabel)}) | ingredient responsable: ${escHtml(ingredientText)}</p>`;
        };
        const hardLines = hardConflicts.map(conflictLine).filter(Boolean);
        const softLines = softConflicts.map(conflictLine).filter(Boolean);
        const aiMode = String(payload.analysis_mode || 'fallback').toLowerCase();
        const aiModeLabel = aiMode === 'ai_live'
          ? 'ai_live'
          : (aiMode === 'pending' ? 'pending' : (aiMode === 'fallback_local' ? 'fallback_local' : 'fallback'));
        const aiModeChip = aiModeLabel === 'ai_live'
          ? '<span class="badge badge-success">ai_live</span>'
          : (aiModeLabel === 'pending'
              ? '<span class="badge badge-warning">pending</span>'
              : '<span class="badge badge-danger">fallback_local</span>');
        const confidenceSummary = `Confiance ${confidence}% | Donnees ${dataQualityPct}% | Allerg. ${allergenInferencePct}%`;
        const recipeIndicator = hasMissingSignal ? '<span style="color:#ffe7a8;">&#9650;</span> ' : '';
        const hardBlock = hardLines.length ? `
          <div class="meal-ai-block">
            <p class="meal-ai-block-title">Conflits bloquants</p>
            ${hardLines.join('')}
          </div>
        ` : '';
        const softBlock = softLines.length ? `
          <div class="meal-ai-block">
            <p class="meal-ai-block-title">Risques IA (non bloquants en strict)</p>
            ${softLines.join('')}
          </div>
        ` : '';
        const warningParts = [];
        if (warningUnknown) {
          warningParts.push(payload.warning_unknown_message || 'Meal inconnu: l IA n a pas pu cerner clairement cet aliment.');
        }
        if (hasMissingSignal) {
          warningParts.push('Des details semblent incomplets; verification manuelle recommandee.');
        }
        if (warningText) {
          warningParts.push(warningText);
        }
        warningParts.push('Aide decisionnelle: verifier les informations sensibles avant validation.');
        const mergedWarning = warningParts.join(' ');

        aiContent.innerHTML = `
          <div class="meal-ai-block">
            <p class="meal-ai-block-title">Resume</p>
            <p class="meal-ai-line"><strong>Source:</strong> ${escHtml(payload.origin_badge || 'Vendor declared')}</p>
            <p class="meal-ai-line"><strong>Confiance:</strong> ${escHtml(confidenceSummary)}</p>
            <p class="meal-ai-line"><strong>Mode analyse:</strong> ${aiModeChip}</p>
            <div class="meal-ai-chip-row">
              ${qualityChip}
              ${riskChip}
              ${regimeChip}
              ${unknownChip}
            </div>
          </div>
          ${hardBlock}
          ${softBlock}
          <div class="meal-ai-block">
            <p class="meal-ai-block-title">Allergenes possibles</p>
            <p class="meal-ai-line">${allergens.length ? escHtml(allergens.join(', ')) : 'Aucun allergene probable detecte.'}</p>
            ${causalLines.length ? causalLines.join('') : '<p class="meal-ai-line">Aucune source fiable (ingredients vendeur ou ingredient manquant estime par l IA) n a ete confirmee.</p>'}
            ${payload.trace_risk ? '<p class="meal-ai-line">Risque de traces detecte (cross-contamination possible).</p>' : ''}
            ${payload.potential_conflict ? `<p class="meal-ai-line"><strong>Conflit avec votre profil:</strong> ${escHtml(conflictTokens.join(', '))}</p>` : ''}
            ${userInputLines.length ? `<p class="meal-ai-line"><strong>Interpretation de votre saisie:</strong> ${escHtml(userInputLines.join(' | '))}</p>` : ''}
          </div>
          <div class="meal-ai-block">
            <p class="meal-ai-block-title">${recipeIndicator}Recette de base probable</p>
            <p class="meal-ai-line"><strong>Indiques par le vendeur:</strong> ${escHtml(payload.ingredients || '-')}</p>
            ${missingIngredients.length ? `<p class="meal-ai-line"><strong>Ingredients possiblement manquants:</strong> ${escHtml(missingIngredients.join(', '))}</p>` : ''}
            ${showBasicRecipe && basicRecipeIngredients.length ? `<p class="meal-ai-line"><strong>Recette de base probable:</strong> ${escHtml(basicRecipeIngredients.join(', '))}</p>` : ''}
            ${Array.isArray(payload.recipe_variants) && payload.recipe_variants.length ? `<p class="meal-ai-line"><strong>Variantes courantes:</strong> ${escHtml(payload.recipe_variants.join(', '))}</p>` : ''}
          </div>
          <p class="meal-ai-warning"><i class="fa-solid fa-triangle-exclamation"></i> ${escHtml(mergedWarning)}</p>
        `;
      }

      async function fetchMealAssistant(mealId, mealLabel) {
        if (!mealId) return;
        if (mealAiCache.has(mealId)) {
          renderMealAssistant(mealAiCache.get(mealId));
          return;
        }
        renderMealAiLoading(mealLabel);
        try {
          const url = new URL('../Controller/MatchingController.php', window.location.href);
          url.searchParams.set('action', 'student_meal_ai_detail');
          url.searchParams.set('id_user', String(idUser));
          url.searchParams.set('id_pref', String(idPref));
          url.searchParams.set('id_restaurant', String(idRestaurant));
          url.searchParams.set('meal_id', String(mealId));
          url.searchParams.set('safety_mode', String(safetyMode));
          url.searchParams.set('price_mode', String(priceMode));
          url.searchParams.set('sort_by', String(sortBy));
          const resp = await fetch(url.toString(), {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
          });
          if (!resp.ok) {
            throw new Error('Reponse serveur invalide');
          }
          const data = await resp.json();
          if (!data || !data.ok || !data.meal) {
            throw new Error((data && data.status) ? data.status : 'analyse indisponible');
          }
          mealAiCache.set(mealId, data.meal);
          renderMealAssistant(data.meal);
        } catch (err) {
          renderMealAiError(err && err.message ? err.message : 'Erreur de chargement IA');
        }
      }

      document.querySelectorAll('.js-meal-row').forEach((row) => {
        row.addEventListener('click', (e) => {
          if (e.target && e.target.closest('.qty-btn')) return;
          const mealId = String(row.getAttribute('data-meal-id') || '');
          const mealLabel = String(row.querySelector('td')?.textContent || 'Meal').trim();
          fetchMealAssistant(mealId, mealLabel);
        });
        row.addEventListener('keydown', (e) => {
          if (e.key !== 'Enter' && e.key !== ' ') return;
          e.preventDefault();
          const mealId = String(row.getAttribute('data-meal-id') || '');
          const mealLabel = String(row.querySelector('td')?.textContent || 'Meal').trim();
          fetchMealAssistant(mealId, mealLabel);
        });
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
          redirectForm.action = 'create_collecte.php';
          redirectForm.style.display = 'none';

          const fields = [
            { name: 'id_user', value: '<?= (int)$idUser ?>' },
            { name: 'id_pref', value: '<?= (int)$idPref ?>' },
            { name: 'id_restaurant', value: '<?= (int)$idRestaurant ?>' },
            { name: 'items_json', value: JSON.stringify(selected) },
            { name: 'montant_total', value: String(totalEl ? totalEl.textContent : '0.00') },
            { name: 'safety_mode', value: '<?= htmlspecialchars($safetyMode, ENT_QUOTES, 'UTF-8') ?>' }
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
