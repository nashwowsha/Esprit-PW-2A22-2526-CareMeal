<?php
require_once __DIR__ . '/../Controller/MatchingController.php';

$idUser = isset($_GET['id_user']) ? (int)$_GET['id_user'] : 1;
if ($idUser <= 0) {
    $idUser = 1;
}
$idPref = isset($_GET['id_pref']) ? (int)$_GET['id_pref'] : 0;
$idRestaurant = isset($_GET['id_restaurant']) ? (int)$_GET['id_restaurant'] : 0;
$safetyMode = isset($_GET['safety_mode']) ? strtolower(trim((string)$_GET['safety_mode'])) : 'strict';
if (!in_array($safetyMode, ['strict', 'souple'], true)) {
    $safetyMode = 'strict';
}

$matchingController = new MatchingController();
$detailResult = $matchingController->getMatchedRestaurantForPreference($idUser, $idPref, $idRestaurant, [
    'safety_mode' => $safetyMode,
]);

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
            <a class="btn btn-outline btn-sm" href="matching.php?id_user=<?= (int)$idUser ?>&id_pref=<?= (int)$idPref ?>&safety_mode=<?= urlencode($safetyMode) ?>">Back to matching list</a>
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
                        $iaTooltipParts = [];
                        $iaTooltipParts[] = 'Source: ' . (string)($meal['ai_origin_badge'] ?? 'Vendor declared');
                        if (!empty($meal['missing_ingredients']) && is_array($meal['missing_ingredients'])) {
                            $iaTooltipParts[] = 'Ingredients possiblement manquants: ' . implode(', ', array_slice((array)$meal['missing_ingredients'], 0, 8));
                        }
                        if (!empty($meal['inferred_allergens_semantic']) && is_array($meal['inferred_allergens_semantic'])) {
                            $iaTooltipParts[] = 'Allergenes possibles: ' . implode(', ', (array)$meal['inferred_allergens_semantic']);
                        }
                        if (!empty($meal['ai_allergen_sources']) && is_array($meal['ai_allergen_sources'])) {
                            foreach ($meal['ai_allergen_sources'] as $allergenName => $srcTokens) {
                                if (!is_array($srcTokens) || empty($srcTokens)) {
                                    continue;
                                }
                                $iaTooltipParts[] = (string)$allergenName . ' possible via: ' . implode(', ', array_values($srcTokens));
                            }
                        }
                        if (isset($meal['ai_confidence'])) {
                            $iaTooltipParts[] = 'Confiance IA: ' . number_format((float)$meal['ai_confidence'] * 100, 0) . '%';
                        }
                        if (!empty($meal['ai_explanation'])) {
                            $iaTooltipParts[] = 'Explication IA: ' . (string)$meal['ai_explanation'];
                        }
                        if (!empty($meal['ai_legal_warning'])) {
                            $iaTooltipParts[] = (string)$meal['ai_legal_warning'];
                        }
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
                        $iaTooltipParts[0] = 'Source: ' . $originLabel;
                        $iaTooltip = implode(' | ', $iaTooltipParts);
                        $mealAiPayload = [
                          'meal_name' => (string)($meal['meal_name'] ?? ''),
                          'semantic_allergens' => array_values((array)($meal['semantic_allergens'] ?? [])),
                          'trace_risk' => !empty($meal['trace_risk']),
                          'ingredients' => (string)($meal['ingredients'] ?? ''),
                          'origin_badge' => $originLabel,
                          'source' => (string)($meal['ai_source'] ?? ''),
                          'confidence' => (float)($meal['ai_confidence'] ?? 0),
                          'data_quality_confidence' => (float)($meal['ai_data_quality_confidence'] ?? 0),
                          'allergen_inference_confidence' => (float)($meal['ai_allergen_inference_confidence'] ?? 0),
                          'ingredient_completeness_score' => (int)($meal['ai_ingredient_completeness_score'] ?? 0),
                          'explanation' => (string)($meal['ai_explanation'] ?? ''),
                          'inferred_ingredients' => array_values((array)($meal['inferred_ingredients'] ?? [])),
                          'missing_ingredients' => array_values((array)($meal['missing_ingredients'] ?? [])),
                          'inferred_allergens' => array_values((array)($meal['inferred_allergens_semantic'] ?? [])),
                          'legal_warning' => (string)($meal['ai_legal_warning'] ?? ''),
                          'allergen_sources' => (array)($meal['ai_allergen_sources'] ?? []),
                          'causal_allergen_details' => array_values((array)($meal['ai_causal_allergen_details'] ?? [])),
                          'possible_missing_ingredients' => !empty($meal['ai_possible_missing_ingredients']),
                          'basic_recipe_ingredients' => array_values((array)($meal['ai_basic_recipe_ingredients'] ?? [])),
                          'basic_recipe_allergens' => array_values((array)($meal['ai_basic_recipe_allergens'] ?? [])),
                          'ai_check_passed' => !empty($meal['ai_check_passed']),
                          'potential_conflict' => !empty($meal['ai_potential_conflict']),
                          'conflict_tokens' => array_values((array)($meal['ai_conflict_tokens'] ?? [])),
                          'user_allergies_raw' => $userAllergiesRaw,
                          'user_allergy_input_map' => $userAllergyInputMap,
                        ];
                      ?>
                      <tr
                        class="js-meal-row"
                        data-meal-id="<?= htmlspecialchars((string)$meal['meal_id'], ENT_QUOTES, 'UTF-8') ?>"
                        data-stock="<?= (int)$meal['quantity'] ?>"
                        data-price="<?= number_format((float)$meal['price'], 2, '.', '') ?>"
                        data-mode="<?= htmlspecialchars((string)$meal['pricing_mode'], ENT_QUOTES, 'UTF-8') ?>"
                        data-ai='<?= htmlspecialchars(json_encode($mealAiPayload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE), ENT_QUOTES, 'UTF-8') ?>'
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
                          <?php if (!empty($meal['ai_potential_conflict'])): ?>
                            <span class="badge badge-danger">
                              <?= $safetyMode === 'strict' ? 'Bloque en mode strict' : 'Risque (mode souple)' ?>
                            </span>
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
                <p class="meal-ai-text" id="meal-ai-summary">Survole un meal pour voir l'analyse IA.</p>
                <div class="meal-ai-content" id="meal-ai-content">
                  <div class="meal-ai-block">
                    <p class="meal-ai-block-title">Comment lire ce panneau</p>
                    <p class="meal-ai-line">1) Le vendeur reste la source principale.</p>
                    <p class="meal-ai-line">2) L IA complete seulement les informations manquantes.</p>
                    <p class="meal-ai-line">3) Chaque allergene possible est lie a son ingredient responsable.</p>
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
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      if (!App.requireAuth(['student'])) return;

      const rows = document.querySelectorAll('#matching-meals-table tbody tr');
      const totalEl = document.getElementById('matching-total');
      const validateBtn = document.getElementById('matching-validate-btn');
      const aiSummary = document.getElementById('meal-ai-summary');
      const aiContent = document.getElementById('meal-ai-content');

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

      function renderMealAssistant(payload) {
        if (!aiSummary || !aiContent || !payload) return;
        const allergens = Array.isArray(payload.semantic_allergens)
          ? payload.semantic_allergens
          : (Array.isArray(payload.inferred_allergens) ? payload.inferred_allergens : []);
        const causalDetails = Array.isArray(payload.causal_allergen_details) ? payload.causal_allergen_details : [];
        const confidence = Math.round((Number(payload.confidence || 0) * 100));
        const dataQualityPct = Math.round((Number(payload.data_quality_confidence || 0) * 100));
        const allergenInferencePct = Math.round((Number(payload.allergen_inference_confidence || 0) * 100));
        const completenessScore = Number(payload.ingredient_completeness_score || 0);
        aiSummary.textContent = String(payload.meal_name || 'Meal') + ' - lecture simplifiee IA';

        const vendorPart = String(payload.origin_badge || '').toLowerCase().indexOf('vendor') !== -1
          ? 'Le vendeur a deja fourni les allergenes. L IA a fait une verification de coherence.'
          : 'Le vendeur n a pas liste clairement les allergenes. L assistant IA a complete l analyse.';

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
          const triggerText = triggers.length ? triggers.join(', ') : 'source incertaine';
          return `<p class="meal-ai-line"><strong>${escHtml(allergen || 'allergene')}</strong> -> ingredients responsables: ${escHtml(triggerText)}</p>`;
        }).filter(Boolean);
        const qualityChip = completenessScore >= 80
          ? '<span class="meal-ai-chip success">donnees vendeur claires</span>'
          : '<span class="meal-ai-chip warn">donnees vendeur partielles</span>';
        const riskChip = payload.potential_conflict
          ? '<span class="meal-ai-chip risk">risque pour votre profil</span>'
          : '<span class="meal-ai-chip success">pas de conflit direct detecte</span>';

        const conflictTokens = Array.isArray(payload.conflict_tokens) ? payload.conflict_tokens : [];
        const userAllergyMap = (payload.user_allergy_input_map && typeof payload.user_allergy_input_map === 'object')
          ? payload.user_allergy_input_map
          : {};
        const conflictMapLines = [];
        Object.keys(userAllergyMap).forEach((rawKey) => {
          const mapped = Array.isArray(userAllergyMap[rawKey]) ? userAllergyMap[rawKey] : [];
          if (!mapped.length || !conflictTokens.length) return;
          const hasOverlap = mapped.some((t) => conflictTokens.indexOf(String(t)) !== -1);
          if (hasOverlap) {
            conflictMapLines.push(`${rawKey} -> ${mapped.join(', ')}`);
          }
        });

        aiContent.innerHTML = `
          <div class="meal-ai-block">
            <p class="meal-ai-block-title">Resume</p>
            <p class="meal-ai-line">${escHtml(vendorPart)}</p>
            <p class="meal-ai-line"><strong>Source:</strong> ${escHtml(payload.origin_badge || 'Vendor declared')}</p>
            <p class="meal-ai-line"><strong>Confiance globale:</strong> ${confidence}%</p>
            <p class="meal-ai-line"><strong>Qualite des donnees vendeur:</strong> ${dataQualityPct}% (score completude ${Math.max(0, Math.min(100, completenessScore))}/100)</p>
            <p class="meal-ai-line"><strong>Confiance inference allergenes:</strong> ${allergenInferencePct}%</p>
            ${payload.ai_check_passed ? '<p class="meal-ai-line"><strong>AI check passed:</strong> donnees vendeur claires, pas de manque detecte.</p>' : ''}
            <div class="meal-ai-chip-row">
              ${qualityChip}
              ${riskChip}
            </div>
          </div>
          <div class="meal-ai-block">
            <p class="meal-ai-block-title">Allergenes possibles</p>
            <p class="meal-ai-line">${allergens.length ? escHtml(allergens.join(', ')) : 'Aucun allergene probable detecte.'}</p>
            ${causalLines.length ? causalLines.join('') : '<p class="meal-ai-line">Source ingredient non determinee avec certitude.</p>'}
            ${payload.trace_risk ? '<p class="meal-ai-line">Risque de traces detecte (cross-contamination possible).</p>' : ''}
            ${payload.potential_conflict ? `<p class="meal-ai-line"><strong>Conflit avec votre profil:</strong> ${escHtml(conflictTokens.join(', '))}</p>` : ''}
            ${payload.potential_conflict && conflictMapLines.length ? `<p class="meal-ai-line"><strong>Interpretation de votre saisie:</strong> ${escHtml(conflictMapLines.join(' | '))}</p>` : ''}
          </div>
          <div class="meal-ai-block">
            <p class="meal-ai-block-title">Ingredients responsables</p>
            <p class="meal-ai-line"><strong>Indiques par le vendeur:</strong> ${escHtml(payload.ingredients || '-')}</p>
            ${missingIngredients.length ? `<p class="meal-ai-line"><strong>Ingredients possiblement manquants:</strong> ${escHtml(missingIngredients.join(', '))}</p>` : ''}
            ${hasMissingSignal ? `<p class="meal-ai-line"><strong>Description possiblement incomplete:</strong> le descriptif vendeur semble partiel.</p>` : ''}
            ${showBasicRecipe && basicRecipeIngredients.length ? `<p class="meal-ai-line"><strong>Recette de base probable:</strong> ${escHtml(basicRecipeIngredients.join(', '))}</p>` : ''}
          </div>
          <div class="meal-ai-block">
            <p class="meal-ai-block-title">Avertissement</p>
            <p class="meal-ai-line">${hasMissingSignal ? 'Le vendeur n a pas donne assez de details. L IA a complete avec prudence.' : 'Donnees vendeur plutot claires, verification IA de securite appliquee.'}</p>
            <p class="meal-ai-line">Ce resultat est une aide decisionnelle et non une verite medicale.</p>
          </div>
          <p class="meal-ai-warning"><i class="fa-solid fa-triangle-exclamation"></i> ${escHtml(warningText)}</p>
        `;
      }

      document.querySelectorAll('.js-meal-row').forEach((row) => {
        row.addEventListener('mouseenter', () => {
          try {
            renderMealAssistant(JSON.parse(row.getAttribute('data-ai') || '{}'));
          } catch (e) {}
        });
        row.addEventListener('focusin', () => {
          try {
            renderMealAssistant(JSON.parse(row.getAttribute('data-ai') || '{}'));
          } catch (e) {}
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
          redirectForm.action = '/caremeal/student/create_collecte.php';
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
