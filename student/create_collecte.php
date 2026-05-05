<?php
require_once __DIR__ . '/../Controller/RestaurantController.php';

$idUser = isset($_POST['id_user']) ? (int)$_POST['id_user'] : (isset($_GET['id_user']) ? (int)$_GET['id_user'] : 1);
if ($idUser <= 0) {
    $idUser = 1;
}
$idPref = isset($_POST['id_pref']) ? (int)$_POST['id_pref'] : (isset($_GET['id_pref']) ? (int)$_GET['id_pref'] : 0);
$idRestaurant = isset($_POST['id_restaurant']) ? (int)$_POST['id_restaurant'] : (isset($_GET['id_restaurant']) ? (int)$_GET['id_restaurant'] : 0);

$status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
$blockedSummary = '';
if (isset($_GET['blocked_summary_b64'])) {
    $decodedBlockedSummary = base64_decode((string)$_GET['blocked_summary_b64'], true);
    if ($decodedBlockedSummary !== false) {
        $blockedSummary = trim((string)$decodedBlockedSummary);
    }
}
$blockedItems = [];
if (isset($_GET['blocked_items_b64'])) {
    $decodedBlockedItems = base64_decode((string)$_GET['blocked_items_b64'], true);
    if ($decodedBlockedItems !== false) {
        $parsedBlockedItems = json_decode((string)$decodedBlockedItems, true);
        if (is_array($parsedBlockedItems)) {
            $blockedItems = $parsedBlockedItems;
        }
    }
}
$addressValue = isset($_POST['adresse_livraison'])
    ? trim((string)$_POST['adresse_livraison'])
    : (isset($_GET['adresse_livraison']) ? trim((string)$_GET['adresse_livraison']) : '');
$modeValue = isset($_POST['mode_collecte'])
    ? trim((string)$_POST['mode_collecte'])
    : (isset($_GET['mode_collecte']) ? trim((string)$_GET['mode_collecte']) : 'pickup');
if (!in_array($modeValue, ['pickup', 'delivery'], true)) {
    $modeValue = 'pickup';
}
$timeValue = isset($_POST['heure_souhaitee'])
    ? trim((string)$_POST['heure_souhaitee'])
    : (isset($_GET['heure_souhaitee']) ? trim((string)$_GET['heure_souhaitee']) : '');
$addressLatValue = isset($_POST['adresse_lat'])
    ? trim((string)$_POST['adresse_lat'])
    : (isset($_GET['adresse_lat']) ? trim((string)$_GET['adresse_lat']) : '');
$addressLngValue = isset($_POST['adresse_lng'])
    ? trim((string)$_POST['adresse_lng'])
    : (isset($_GET['adresse_lng']) ? trim((string)$_GET['adresse_lng']) : '');

$itemsJsonRaw = '';
if (isset($_POST['items_json'])) {
    $itemsJsonRaw = trim((string)$_POST['items_json']);
} elseif (isset($_GET['items_b64'])) {
    $decoded = base64_decode((string)$_GET['items_b64'], true);
    if ($decoded !== false) {
        $itemsJsonRaw = trim((string)$decoded);
    }
}
if ($itemsJsonRaw === '') {
    $itemsJsonRaw = '[]';
}

$itemsDecoded = json_decode($itemsJsonRaw, true);
if (!is_array($itemsDecoded)) {
    $itemsDecoded = [];
}

$itemsForPayload = [];
foreach ($itemsDecoded as $item) {
    if (!is_array($item)) {
        continue;
    }
    $mealId = trim((string)($item['meal_id'] ?? ''));
    $qty = (int)($item['quantity'] ?? 0);
    if ($mealId === '' || $qty <= 0) {
        continue;
    }
    $itemsForPayload[] = [
        'meal_id' => $mealId,
        'quantity' => $qty,
    ];
}

$restaurantController = new RestaurantController();
$restaurant = $idRestaurant > 0 ? $restaurantController->getRestaurantById($idRestaurant) : null;
$restaurantMeals = is_array($restaurant['meals'] ?? null) ? $restaurant['meals'] : [];
$mealIndex = [];
foreach ($restaurantMeals as $meal) {
    $mealId = trim((string)($meal['meal_id'] ?? ''));
    if ($mealId === '') {
        continue;
    }
    $mealIndex[$mealId] = $meal;
}

$displayItems = [];
$computedTotal = 0.0;
$selectedMealsForJs = [];
foreach ($itemsForPayload as $item) {
    $mealId = $item['meal_id'];
    $qty = (int)$item['quantity'];
    $meal = $mealIndex[$mealId] ?? null;
    $mealName = $meal ? trim((string)($meal['meal_name'] ?? $mealId)) : $mealId;
    $pricingMode = $meal && (($meal['pricing_mode'] ?? 'free') === 'paid') ? 'paid' : 'free';
    $unitPrice = $pricingMode === 'paid' ? max(0, (float)($meal['price'] ?? 0)) : 0.0;
    $lineTotal = $unitPrice * $qty;
    $computedTotal += $lineTotal;
    $displayItems[] = [
        'meal_id' => $mealId,
        'meal_name' => $mealName,
        'quantity' => $qty,
        'pricing_mode' => $pricingMode,
        'unit_price' => $unitPrice,
        'line_total' => $lineTotal,
    ];
    if ($meal) {
        $selectedMealsForJs[] = [
            'meal_id' => $mealId,
            'meal_name' => trim((string)($meal['meal_name'] ?? $mealId)),
            'allergens' => trim((string)($meal['allergens'] ?? '')),
            'ingredients' => trim((string)($meal['ingredients'] ?? '')),
            'allergen_disclosure_mode' => trim((string)($meal['allergen_disclosure_mode'] ?? '')),
        ];
    }
}

$totalInput = isset($_POST['montant_total']) ? trim((string)$_POST['montant_total']) : (isset($_GET['montant_total']) ? trim((string)$_GET['montant_total']) : '');
$totalValue = is_numeric($totalInput) ? (float)$totalInput : $computedTotal;
if ($totalValue < 0) {
    $totalValue = 0.0;
}

$itemsJsonPayload = json_encode($itemsForPayload, JSON_UNESCAPED_UNICODE);
if ($itemsJsonPayload === false) {
    $itemsJsonPayload = '[]';
}

$hoursRaw = trim((string)($restaurant['horaires'] ?? ''));
$openTime = '';
$closeTime = '';
if ($hoursRaw !== '' && preg_match('/^(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})$/', $hoursRaw, $m)) {
    $openTime = $m[1];
    $closeTime = $m[2];
}

$statusMessages = [
    'success_collecte_created' => ['class' => 'success', 'text' => 'Collecte creee avec succes.'],
    'error_validation' => ['class' => 'error', 'text' => 'Validation echouee. Verifie les champs.'],
    'error_restaurant_not_found' => ['class' => 'error', 'text' => 'Restaurant introuvable.'],
    'error_pref_not_found' => ['class' => 'error', 'text' => 'Preference introuvable.'],
    'error_user_not_found' => ['class' => 'error', 'text' => 'Utilisateur introuvable.'],
    'error_pref_user_mismatch' => ['class' => 'error', 'text' => 'Cette preference ne correspond pas a cet utilisateur.'],
    'error_items_empty' => ['class' => 'error', 'text' => 'Aucun meal selectionne.'],
    'error_items_invalid' => ['class' => 'error', 'text' => 'Items invalides pour ce restaurant.'],
    'error_items_unavailable' => ['class' => 'error', 'text' => 'Stock insuffisant pour un ou plusieurs meals.'],
    'error_allergen_blocked' => ['class' => 'error', 'text' => 'Collecte bloquee pour securite allergene (mode strict).'],
    'error_db' => ['class' => 'error', 'text' => 'Erreur base de donnees.'],
    'error_unknown_action' => ['class' => 'error', 'text' => 'Action inconnue.'],
    'error_invalid_request' => ['class' => 'error', 'text' => 'Requete invalide.'],
];
$prefAllergiesForJs = '';
if ($idPref > 0) {
    try {
        $prefQ = config::getConnexion()->prepare("SELECT allergies FROM preference WHERE id_pref = :id_pref LIMIT 1");
        $prefQ->execute(['id_pref' => $idPref]);
        $prefRow = $prefQ->fetch();
        if ($prefRow) {
            $prefAllergiesForJs = trim((string)($prefRow['allergies'] ?? ''));
        }
    } catch (Exception $e) {
        $prefAllergiesForJs = '';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Creer une collecte apres choix des meals.">
  <title>Creer Collecte - CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="../assets/vendor/leaflet/leaflet.css">
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
    .blocked-items-list {
      margin: 8px 0 0;
      padding-left: 18px;
    }
    .blocked-items-list li {
      margin: 4px 0;
    }
    .collecte-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
    }
    .collecte-grid .full {
      grid-column: 1 / -1;
    }
    .input, .select {
      width: 100%;
      background: rgba(255,255,255,.04);
      border: 1px solid var(--color-dark-border);
      border-radius: var(--radius-md);
      color: var(--color-white);
      padding: 10px 12px;
    }
    .table-wrap {
      overflow: auto;
    }
    .table {
      width: 100%;
      border-collapse: collapse;
      min-width: 760px;
    }
    .table th, .table td {
      border-bottom: 1px solid var(--color-dark-border);
      padding: 10px;
      text-align: left;
      vertical-align: top;
    }
    .field-error {
      color: #ff9da7;
      font-size: .8rem;
      min-height: 16px;
      display: none;
      margin-top: 5px;
    }
    .input-error {
      border-color: rgba(220,53,69,.7)!important;
      box-shadow: 0 0 0 1px rgba(220,53,69,.25);
    }
    .meta {
      color: var(--color-text-muted);
      font-size: .84rem;
      margin: 0;
    }
    .address-tools {
      margin-top: 10px;
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 8px;
      align-items: center;
    }
    .address-map {
      margin-top: 10px;
      width: 100%;
      height: 260px;
      border-radius: var(--radius-md);
      border: 1px solid var(--color-dark-border);
      overflow: hidden;
    }
    .coords-meta {
      margin-top: 8px;
      color: var(--color-text-muted);
      font-size: .82rem;
    }
    @media (max-width: 900px) {
      .collecte-grid {
        grid-template-columns: 1fr;
      }
      .address-tools {
        grid-template-columns: 1fr;
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
            <h2>Nouvelle collecte</h2>
            <p>Confirme le mode, l'heure et l'adresse si livraison</p>
          </div>
        </div>
        <div class="header-right">
          <button class="header-notification"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button>
          <div class="avatar avatar-sm" id="header-avatar">AA</div>
        </div>
      </header>

      <div class="page-content">
        <?php if (isset($statusMessages[$status])): ?>
          <div class="alert-box <?= htmlspecialchars((string)$statusMessages[$status]['class'], ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars((string)$statusMessages[$status]['text'], ENT_QUOTES, 'UTF-8') ?>
            <?php if ($status === 'error_allergen_blocked' && !empty($blockedItems)): ?>
              <ul class="blocked-items-list">
                <?php foreach ($blockedItems as $blocked): ?>
                  <?php
                    $blockedMeal = trim((string)($blocked['meal_name'] ?? '-'));
                    $blockedAllergen = trim((string)($blocked['allergen'] ?? '-'));
                    $blockedOrigin = trim((string)($blocked['origin'] ?? '-'));
                    $triggerList = [];
                    if (isset($blocked['trigger_ingredients']) && is_array($blocked['trigger_ingredients'])) {
                        foreach ($blocked['trigger_ingredients'] as $tr) {
                            $tv = trim((string)$tr);
                            if ($tv !== '') {
                                $triggerList[] = $tv;
                            }
                        }
                    }
                    $triggerText = empty($triggerList) ? 'source incertaine' : implode(', ', array_unique($triggerList));
                  ?>
                  <li>
                    Meal: <?= htmlspecialchars($blockedMeal, ENT_QUOTES, 'UTF-8') ?> |
                    Allergene: <?= htmlspecialchars($blockedAllergen, ENT_QUOTES, 'UTF-8') ?> |
                    Source: <?= htmlspecialchars($blockedOrigin, ENT_QUOTES, 'UTF-8') ?> |
                    Ingredient responsable: <?= htmlspecialchars($triggerText, ENT_QUOTES, 'UTF-8') ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php elseif ($status === 'error_allergen_blocked' && $blockedSummary !== ''): ?>
              <p class="meta" style="margin-top:8px;color:#ffd5d9;"><?= htmlspecialchars($blockedSummary, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <div class="card" style="margin-bottom:16px;">
          <div class="card-header" style="justify-content:space-between;align-items:center;">
            <h3 class="card-title"><i class="fa-solid fa-store"></i> Restaurant selectionne</h3>
            <a class="btn btn-outline btn-sm" href="matching.php?id_user=<?= (int)$idUser ?>&id_pref=<?= (int)$idPref ?>">Back to matching</a>
          </div>
          <?php if (!$restaurant): ?>
            <p class="meta">Restaurant introuvable.</p>
          <?php else: ?>
            <p class="meta"><strong>#<?= (int)$restaurant['id_restaurant'] ?> - <?= htmlspecialchars((string)($restaurant['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></p>
            <p class="meta">Localisation: <?= htmlspecialchars((string)($restaurant['localisation'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="meta">Horaires: <?= htmlspecialchars($hoursRaw !== '' ? $hoursRaw : '--', ENT_QUOTES, 'UTF-8') ?></p>
          <?php endif; ?>
        </div>

        <div class="card" style="margin-bottom:16px;">
          <div class="card-header" style="justify-content:space-between;align-items:center;">
            <h3 class="card-title"><i class="fa-solid fa-basket-shopping"></i> Items selectionnes</h3>
            <span class="badge badge-info"><?= count($displayItems) ?> items</span>
          </div>
          <div class="table-wrap">
            <table class="table">
              <thead>
                <tr>
                  <th>Meal</th>
                  <th>Quantite</th>
                  <th>Mode prix</th>
                  <th>Prix unitaire</th>
                  <th>Total ligne</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($displayItems)): ?>
                  <tr><td colspan="5" class="meta">Aucun item selectionne.</td></tr>
                <?php else: ?>
                  <?php foreach ($displayItems as $item): ?>
                    <tr>
                      <td><?= htmlspecialchars((string)$item['meal_name'], ENT_QUOTES, 'UTF-8') ?></td>
                      <td><?= (int)$item['quantity'] ?></td>
                      <td><?= $item['pricing_mode'] === 'paid' ? 'Paid' : 'Free' ?></td>
                      <td><?= number_format((float)$item['unit_price'], 2) ?> DT</td>
                      <td><?= number_format((float)$item['line_total'], 2) ?> DT</td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <p class="meta" style="margin-top:10px;">Montant total estime: <strong><?= number_format((float)$totalValue, 2) ?> DT</strong></p>
        </div>

        <div class="card" id="collecte-form-card">
          <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-pen-to-square"></i> Formulaire collecte</h3>
          </div>

          <form method="post" action="../Controller/planning_collecte.php?action=student_create_collecte" id="student-collecte-form" novalidate data-open-time="<?= htmlspecialchars($openTime, ENT_QUOTES, 'UTF-8') ?>" data-close-time="<?= htmlspecialchars($closeTime, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_user" class="js-id-user" value="<?= (int)$idUser ?>">
            <input type="hidden" name="id_pref" value="<?= (int)$idPref ?>">
            <input type="hidden" name="id_restaurant" value="<?= (int)$idRestaurant ?>">
            <input type="hidden" name="items_json" id="student_collecte_items_json" value="<?= htmlspecialchars($itemsJsonPayload, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="montant_total" value="<?= htmlspecialchars(number_format((float)$totalValue, 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="statut" value="en_attente">
            <input type="hidden" name="adresse_lat" id="student_collecte_adresse_lat" value="<?= htmlspecialchars($addressLatValue, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="adresse_lng" id="student_collecte_adresse_lng" value="<?= htmlspecialchars($addressLngValue, ENT_QUOTES, 'UTF-8') ?>">

            <div class="collecte-grid">
              <div>
                <label for="student_collecte_mode">Mode</label>
                <select class="select" id="student_collecte_mode" name="mode_collecte">
                  <option value="pickup"<?= $modeValue === 'pickup' ? ' selected' : '' ?>>pickup</option>
                  <option value="delivery"<?= $modeValue === 'delivery' ? ' selected' : '' ?>>delivery</option>
                </select>
                <div class="field-error" id="student_collecte_mode-error"></div>
              </div>
              <div>
                <label for="student_collecte_heure">Heure souhaitee</label>
                <input class="input" id="student_collecte_heure" name="heure_souhaitee" type="time" value="<?= htmlspecialchars($timeValue, ENT_QUOTES, 'UTF-8') ?>">
                <p class="meta" id="student_collecte_hours_hint">Horaires restaurant: <?= htmlspecialchars($hoursRaw !== '' ? $hoursRaw : '--', ENT_QUOTES, 'UTF-8') ?></p>
                <div class="field-error" id="student_collecte_heure-error"></div>
              </div>
              <div class="full" id="student_collecte_address_group">
                <label for="student_collecte_adresse">Adresse livraison (obligatoire si delivery)</label>
                <input class="input" id="student_collecte_adresse" name="adresse_livraison" type="text" value="<?= htmlspecialchars($addressValue, ENT_QUOTES, 'UTF-8') ?>" placeholder="Adresse complete">
                <div class="address-tools">
                  <input class="input" id="student_collecte_address_search" type="text" placeholder="Rechercher adresse sur la carte...">
                  <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;">
                    <button class="btn btn-outline btn-sm" type="button" id="student_collecte_current_location_btn"><i class="fa-solid fa-location-crosshairs"></i> Ma position</button>
                    <button class="btn btn-outline btn-sm" type="button" id="student_collecte_address_search_btn"><i class="fa-solid fa-magnifying-glass"></i> Chercher</button>
                  </div>
                </div>
                <div id="student_collecte_address_map" class="address-map"></div>
                <div id="student_collecte_coords_hint" class="coords-meta">Coordonnees: non selectionnees</div>
                <div class="field-error" id="student_collecte_adresse-error"></div>
              </div>
            </div>

            <div style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
              <button type="submit" class="btn btn-primary btn-sm"<?= empty($itemsForPayload) || !$restaurant ? ' disabled' : '' ?>>
                <i class="fa-solid fa-floppy-disk"></i> Creer collecte
              </button>
              <?php if ($idRestaurant > 0): ?>
                <a class="btn btn-outline btn-sm" href="matching_restaurant.php?id_user=<?= (int)$idUser ?>&id_pref=<?= (int)$idPref ?>&id_restaurant=<?= (int)$idRestaurant ?>">Modifier les quantites</a>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>

  <script src="../js/app.js"></script>
  <script src="../js/components.js"></script>
  <script src="../assets/vendor/leaflet/leaflet.js"></script>
  <script src="../js/collecte-address-picker.js"></script>
  <script>
    window.STUDENT_COLLECTE_PREF_ALLERGIES = <?= json_encode($prefAllergiesForJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    window.STUDENT_COLLECTE_SELECTED_MEALS = <?= json_encode($selectedMealsForJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  </script>
  <script src="../js/student-collecte-validation.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (!App.requireAuth(['student'])) return;
      const isPostRequest = <?= $_SERVER['REQUEST_METHOD'] === 'POST' ? 'true' : 'false' ?>;
      const user = App.getCurrentUser();
      const match = String((user && user.id) ? user.id : '').match(/(\d+)$/);
      if (match) {
        const resolvedId = parseInt(match[1], 10);
        const url = new URL(window.location.href);
        const queryId = parseInt(url.searchParams.get('id_user') || '0', 10);
        if (!isPostRequest && (!queryId || queryId !== resolvedId)) {
          url.searchParams.set('id_user', String(resolvedId));
          window.history.replaceState({}, '', url.toString());
        }
        document.querySelectorAll('.js-id-user').forEach(function (input) {
          input.value = String(resolvedId);
        });
      }

      if (typeof window.initCollecteAddressPicker === 'function') {
        window.initCollecteAddressPicker({
          mapId: 'student_collecte_address_map',
          addressInputId: 'student_collecte_adresse',
          latInputId: 'student_collecte_adresse_lat',
          lngInputId: 'student_collecte_adresse_lng',
          searchInputId: 'student_collecte_address_search',
          searchBtnId: 'student_collecte_address_search_btn',
          currentLocationBtnId: 'student_collecte_current_location_btn',
          coordsLabelId: 'student_collecte_coords_hint',
          defaultCenter: [36.8065, 10.1815],
          defaultZoom: 11
        });
      }
    });
  </script>
</body>
</html>
