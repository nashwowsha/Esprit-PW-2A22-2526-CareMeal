<?php
require_once __DIR__ . '/../Controller/PlanningCollecteController.php';
require_once __DIR__ . '/../Controller/RestaurantController.php';
require_once __DIR__ . '/../Controller/PdfExport.php';
require_once __DIR__ . '/../config/app.php';

$controller = new PlanningCollecteController();
$restaurantController = new RestaurantController();
$realtimeConfig = caremeal_realtime_public_config();

$status = $_GET['status'] ?? '';
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
$selectedId = isset($_GET['selected_id']) ? (int)$_GET['selected_id'] : 0;
$search = trim((string)($_GET['q'] ?? ''));
$sortBy = strtolower(trim((string)($_GET['sort_by'] ?? 'newest')));
$allowedSorts = ['newest', 'oldest', 'montant_desc', 'montant_asc', 'status_asc', 'status_desc', 'heure_desc', 'heure_asc', 'restaurant_asc', 'restaurant_desc'];
if (!in_array($sortBy, $allowedSorts, true)) {
    $sortBy = 'newest';
}
$rows = $controller->getAllWithJoin($search, $sortBy);
$db = config::getConnexion();
$prefIds = $db->query("SELECT id_pref FROM preference ORDER BY id_pref")->fetchAll(PDO::FETCH_COLUMN);
$prefRowsForJs = $db->query("SELECT id_pref, allergies FROM preference ORDER BY id_pref")->fetchAll();
$userIds = $db->query("SELECT id FROM utilisateur ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
$prefAllergiesMap = [];
foreach ($prefRowsForJs as $prefRowForJs) {
    $prefIdKey = (int)($prefRowForJs['id_pref'] ?? 0);
    if ($prefIdKey <= 0) {
        continue;
    }
    $prefAllergiesMap[$prefIdKey] = trim((string)($prefRowForJs['allergies'] ?? ''));
}

$restaurantsRaw = $restaurantController->getAllRestaurantsForAdmin('');
$restaurantsForPicker = [];

// Stock affiche = stock base restaurant - reservations actives (en_attente / en_cours_livraison).
// Ceci aligne l'affichage admin avec la validation backend (PlanningCollecteController::validateCollecte).
$reservedByRestaurant = [];
$reservedRows = $db->query(
    "SELECT id_restaurant, items_json
     FROM planning_collecte
     WHERE statut IN ('en_attente', 'en_cours_livraison')"
)->fetchAll();
foreach ($reservedRows as $reservedRow) {
    $restaurantId = (int)($reservedRow['id_restaurant'] ?? 0);
    if ($restaurantId <= 0) {
        continue;
    }
    if (!isset($reservedByRestaurant[$restaurantId])) {
        $reservedByRestaurant[$restaurantId] = [];
    }
    $reservedItems = json_decode((string)($reservedRow['items_json'] ?? '[]'), true);
    if (!is_array($reservedItems)) {
        continue;
    }
    foreach ($reservedItems as $reservedItem) {
        if (!is_array($reservedItem)) {
            continue;
        }
        $mealId = strtolower(trim((string)($reservedItem['meal_id'] ?? '')));
        $qty = (int)($reservedItem['quantity'] ?? 0);
        if ($mealId === '' || $qty <= 0) {
            continue;
        }
        if (!isset($reservedByRestaurant[$restaurantId][$mealId])) {
            $reservedByRestaurant[$restaurantId][$mealId] = 0;
        }
        $reservedByRestaurant[$restaurantId][$mealId] += $qty;
    }
}

foreach ($restaurantsRaw as $restaurantRow) {
    $restaurantId = (int)$restaurantRow['id_restaurant'];
    $restaurantReserved = $reservedByRestaurant[$restaurantId] ?? [];
    $mealsSource = is_array($restaurantRow['meals'] ?? null) ? $restaurantRow['meals'] : [];
    $meals = [];
    foreach ($mealsSource as $meal) {
        $mealId = trim((string)($meal['meal_id'] ?? ''));
        if ($mealId === '') {
            continue;
        }
        $reservedQty = (int)($restaurantReserved[strtolower($mealId)] ?? 0);
        $baseQty = (int)($meal['quantity'] ?? 0);
        $availableQty = max(0, $baseQty - $reservedQty);
        $meals[] = [
            'meal_id' => $mealId,
            'meal_name' => trim((string)($meal['meal_name'] ?? 'Meal')),
            'quantity' => $availableQty,
            'price' => (float)($meal['price'] ?? 0),
            'pricing_mode' => (($meal['pricing_mode'] ?? 'free') === 'paid') ? 'paid' : 'free',
            'allergens' => trim((string)($meal['allergens'] ?? '')),
            'ingredients' => trim((string)($meal['ingredients'] ?? '')),
            'allergen_disclosure_mode' => trim((string)($meal['allergen_disclosure_mode'] ?? '')),
        ];
    }
    $restaurantsForPicker[] = [
        'id_restaurant' => $restaurantId,
        'nom' => trim((string)($restaurantRow['nom'] ?? '')),
        'localisation' => trim((string)($restaurantRow['localisation'] ?? '')),
        'horaires' => trim((string)($restaurantRow['horaires'] ?? '')),
        'meals' => $meals,
    ];
}

$statusMessages = [
    'success_collecte_created' => ['class' => 'success', 'text' => 'Collecte creee avec succes.'],
    'success_collecte_updated' => ['class' => 'success', 'text' => 'Statut de collecte mis a jour.'],
    'success_collecte_deleted' => ['class' => 'success', 'text' => 'Collecte supprimee.'],
    'error_validation' => ['class' => 'error', 'text' => 'Validation echouee. Verifiez les champs.'],
    'error_restaurant_not_found' => ['class' => 'error', 'text' => 'ID restaurant inexistant.'],
    'error_pref_not_found' => ['class' => 'error', 'text' => 'ID preference inexistant.'],
    'error_user_not_found' => ['class' => 'error', 'text' => 'ID user inexistant.'],
    'error_pref_user_mismatch' => ['class' => 'error', 'text' => 'La preference ne correspond pas a ce user.'],
    'error_items_empty' => ['class' => 'error', 'text' => 'Aucun meal selectionne.'],
    'error_items_invalid' => ['class' => 'error', 'text' => 'Meals invalides pour ce restaurant.'],
    'error_items_unavailable' => ['class' => 'error', 'text' => 'Quantite demandee depasse le stock disponible.'],
    'error_allergen_blocked' => ['class' => 'error', 'text' => 'Collecte bloquee pour securite allergene (mode strict).'],
    'error_invalid_status' => ['class' => 'error', 'text' => 'Statut invalide pour ce mode de collecte.'],
    'error_not_found' => ['class' => 'error', 'text' => 'Collecte introuvable.'],
    'error_cannot_delete_active_collecte' => ['class' => 'error', 'text' => 'Collecte active: suppression et/ou stock non autorise.'],
    'error_db' => ['class' => 'error', 'text' => 'Erreur base de donnees.'],
    'error_unknown_action' => ['class' => 'error', 'text' => 'Action inconnue.'],
    'error_invalid_request' => ['class' => 'error', 'text' => 'Requete invalide.'],
];

if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    $pdfRows = [];
    foreach ($rows as $row) {
        $itemsCsv = '-';
        $itemsRaw = $row['items'] ?? [];
        if (is_array($itemsRaw) && !empty($itemsRaw)) {
            $parts = [];
            foreach ($itemsRaw as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $qty = (int)($item['quantity'] ?? 0);
                $name = trim((string)($item['meal_name'] ?? ($item['meal_id'] ?? '')));
                if ($qty > 0 && $name !== '') {
                    $parts[] = $qty . ' x ' . $name;
                }
            }
            if (!empty($parts)) {
                $itemsCsv = implode(', ', $parts);
            }
        }
        $pdfRows[] = [
            (int)($row['id_collecte'] ?? 0),
            (int)($row['id_restaurant'] ?? 0),
            (string)($row['restaurant_nom'] ?? ''),
            (int)($row['id_pref'] ?? 0),
            (int)($row['id_user'] ?? 0),
            (string)($row['user_nom'] ?? ''),
            (string)($row['mode_collecte'] ?? ''),
            (string)($row['heure_souhaitee'] ?? ''),
            number_format((float)($row['montant_total'] ?? 0), 2, '.', ''),
            (string)($row['statut'] ?? ''),
            $itemsCsv,
        ];
    }

    caremeal_stream_table_pdf(
        'admin_collectes_export_' . date('Ymd_His') . '.pdf',
        'Collectes admin',
        ['ID Collecte', 'ID Restaurant', 'Restaurant', 'ID Pref', 'ID User', 'User', 'Mode', 'Heure souhaitee', 'Montant total', 'Statut', 'Items'],
        $pdfRows,
        'landscape'
    );
}

if (!function_exists('collecte_status_options')) {
    function collecte_status_options($mode)
    {
        $mode = strtolower(trim((string)$mode));
        if ($mode === 'delivery') {
            return ['en_attente', 'en_cours_livraison', 'livree', 'annulee'];
        }
        return ['en_attente', 'collecte', 'annulee'];
    }
}

if (!function_exists('collecte_status_label')) {
    function collecte_status_label($status)
    {
        $map = [
            'en_attente' => 'En attente',
            'collecte' => 'Collecte',
            'en_cours_livraison' => 'En cours de livraison',
            'livree' => 'Livree',
            'annulee' => 'Annulee',
            'acceptee' => 'Collecte',
            'en_preparation' => 'En attente',
            'en_livraison' => 'En cours de livraison',
            'pickup' => 'pickup',
            'delivery' => 'livraison',
        ];
        $status = trim((string)$status);
        return $map[$status] ?? $status;
    }
}

if (!function_exists('collecte_items_display')) {
    function collecte_items_display($items)
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

if (!function_exists('collecte_pref_values')) {
    function collecte_pref_values($row)
    {
        $regime = trim((string)($row['pref_regime_snapshot'] ?? ''));
        if ($regime === '') {
            $regime = trim((string)($row['regime_alimentaire'] ?? ''));
        }
        $adresse = trim((string)($row['pref_localisation_snapshot'] ?? ''));
        if ($adresse === '') {
            $adresse = trim((string)($row['preference_localisation'] ?? ''));
        }
        $allergies = trim((string)($row['pref_allergies_snapshot'] ?? ''));
        if ($allergies === '') {
            $allergies = trim((string)($row['allergies'] ?? ''));
        }
        return [
            'regime' => $regime === '' ? '-' : $regime,
            'adresse' => $adresse === '' ? '-' : $adresse,
            'allergies' => $allergies === '' ? '-' : $allergies,
        ];
    }
}

$adminMapPoints = [];
foreach ($rows as $row) {
    $collecteId = (int)($row['id_collecte'] ?? 0);
    $restaurantName = trim((string)($row['restaurant_nom'] ?? 'Restaurant'));
    $restaurantLocation = trim((string)($row['restaurant_localisation'] ?? ''));
    $statusLabel = collecte_status_label($row['statut'] ?? '');
    $modeRaw = strtolower(trim((string)($row['mode_collecte'] ?? 'pickup')));
    $modeLabel = collecte_status_label($modeRaw);

    if ($restaurantLocation !== '') {
        $adminMapPoints[] = [
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
        $adminMapPoints[] = [
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Planning collecte restaurant/preference.">
  <title>Planning Collecte - Admin</title>
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
    .blocked-items-list { margin: 8px 0 0; padding-left: 18px; }
    .blocked-items-list li { margin: 4px 0; }
    .toolbar { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
    .grid2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
    .full { grid-column: 1 / -1; }
    .input, .select, .textarea { width: 100%; background: rgba(255,255,255,.04); border: 1px solid var(--color-dark-border); border-radius: var(--radius-md); color: var(--color-white); padding: 10px 12px; }
    .textarea { min-height: 84px; resize: vertical; }
    .field-error { color: #ff9da7; font-size: .8rem; min-height: 16px; display: none; margin-top: 5px; }
    .input-error { border-color: rgba(220,53,69,.7)!important; box-shadow: 0 0 0 1px rgba(220,53,69,.25); }
    .table-wrap { overflow: auto; }
    .table { width: 100%; border-collapse: collapse; min-width: 1200px; }
    .table th, .table td { border-bottom: 1px solid var(--color-dark-border); padding: 10px; text-align: left; vertical-align: top; }
    .row-selected { background: rgba(249,115,22,.14); }
    .json-preview { font-size: .78rem; color: var(--color-text-muted); max-width: 280px; white-space: pre-wrap; word-break: break-word; }
    .hint-text { margin-top: 6px; color: var(--color-text-muted); font-size: .8rem; }
    .hint-inline { color: var(--color-text-muted); font-size: .8rem; margin-top: 5px; }
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
    .meals-box { border: 1px solid var(--color-dark-border); border-radius: var(--radius-md); padding: 12px; background: rgba(255,255,255,.02); display: grid; gap: 8px; }
    .meal-row { display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: center; padding: 10px; border: 1px solid rgba(255,255,255,.06); border-radius: 10px; }
    .meal-name { font-weight: 600; }
    .meal-meta { color: var(--color-text-muted); font-size: .82rem; }
    .qty-wrap { display: inline-flex; align-items: center; gap: 8px; }
    .qty-btn { width: 30px; height: 30px; border-radius: 50%; border: 1px solid var(--color-dark-border); background: rgba(255,255,255,.04); color: var(--color-white); cursor: pointer; }
    .qty-value { min-width: 28px; text-align: center; font-weight: 700; }
    .address-tools { margin-top: 10px; display: grid; grid-template-columns: 1fr auto; gap: 8px; align-items: center; }
    .address-map { margin-top: 10px; width: 100%; height: 260px; border-radius: var(--radius-md); border: 1px solid var(--color-dark-border); overflow: hidden; }
    .coords-meta { margin-top: 8px; color: var(--color-text-muted); font-size: .82rem; }
    .collectes-map {
      width: 100%;
      height: 360px;
      border-radius: var(--radius-md);
      border: 1px solid var(--color-dark-border);
      overflow: hidden;
    }
    @media (max-width: 900px) {
      .grid2 { grid-template-columns: 1fr; }
      .address-tools { grid-template-columns: 1fr; }
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
          <div class="page-title"><h2>Planning Collecte</h2><p>Jointure entre collecte, restaurant et preference</p></div>
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

          <section class="card">
            <div class="card-header">
              <h3 class="card-title"><i class="fa-solid fa-map-location-dot"></i> Carte collectes admin</h3>
            </div>
            <div id="admin-collectes-map" class="collectes-map"></div>
          </section>

          <section class="card">
            <div class="card-header">
              <h3 class="card-title"><i class="fa-solid fa-plus"></i> Nouvelle collecte</h3>
            </div>
            <form method="post" action="../Controller/planning_collecte.php?action=admin_create_collecte" id="admin-collecte-form" novalidate>
              <input type="hidden" name="adresse_lat" id="collecte_adresse_lat" value="">
              <input type="hidden" name="adresse_lng" id="collecte_adresse_lng" value="">
              <div class="grid2">
                <div>
                  <label for="collecte_restaurant_id">ID Restaurant</label>
                  <input class="input" id="collecte_restaurant_id" name="id_restaurant" type="number" placeholder="ex: 3">
                  <div id="collecte_restaurant_hint" class="hint-text">Choisis un ID restaurant pour charger ses meals.</div>
                  <div id="collecte_restaurant_id-error" class="field-error"></div>
                </div>
                <div>
                  <label for="collecte_pref_id">ID Preference</label>
                  <input class="input" id="collecte_pref_id" name="id_pref" type="number" placeholder="ex: 17">
                  <div id="collecte_pref_id-error" class="field-error"></div>
                </div>
                <div>
                  <label for="collecte_user_id">ID User</label>
                  <input class="input" id="collecte_user_id" name="id_user" type="number" placeholder="ex: 1">
                  <div id="collecte_user_id-error" class="field-error"></div>
                </div>
                <div>
                  <label for="collecte_mode_collecte">Mode</label>
                  <select class="select" id="collecte_mode_collecte" name="mode_collecte">
                    <option value="pickup">pickup</option>
                    <option value="delivery">delivery</option>
                  </select>
                  <div id="collecte_mode_collecte-error" class="field-error"></div>
                </div>
                <div class="full" id="collecte_adresse_group">
                  <label for="collecte_adresse_livraison">Adresse livraison (obligatoire si delivery)</label>
                  <input class="input" id="collecte_adresse_livraison" name="adresse_livraison" type="text" placeholder="Adresse complete">
                  <div class="address-tools">
                    <input class="input" id="collecte_address_search" type="text" placeholder="Rechercher adresse sur la carte...">
                    <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;">
                      <button class="btn btn-outline btn-sm" type="button" id="collecte_current_location_btn"><i class="fa-solid fa-location-crosshairs"></i> Ma position</button>
                      <button class="btn btn-outline btn-sm" type="button" id="collecte_address_search_btn"><i class="fa-solid fa-magnifying-glass"></i> Chercher</button>
                    </div>
                  </div>
                  <div id="collecte_address_map" class="address-map"></div>
                  <div id="collecte_coords_hint" class="coords-meta">Coordonnees: non selectionnees</div>
                  <div id="collecte_adresse_livraison-error" class="field-error"></div>
                </div>
                <div class="full">
                  <label>Aliments / meals disponibles</label>
                  <div id="collecte_meals_list" class="meals-box"></div>
                  <div id="collecte_meals_empty" class="hint-text">Saisis un ID restaurant valide pour afficher les meals.</div>
                  <input type="hidden" id="collecte_items_json" name="items_json" value="[]">
                  <div id="collecte_items_json-error" class="field-error"></div>
                </div>
                <div>
                  <label for="collecte_heure_souhaitee">Heure souhaitee</label>
                  <input class="input" id="collecte_heure_souhaitee" name="heure_souhaitee" type="time">
                  <div id="collecte_heure_hint" class="hint-text">Horaires du resto: --</div>
                  <div id="collecte_heure_souhaitee-error" class="field-error"></div>
                </div>
                <div>
                  <label for="collecte_montant_total_display">Montant total (auto)</label>
                  <input class="input" id="collecte_montant_total_display" type="text" value="0.00" readonly>
                  <input id="collecte_montant_total" name="montant_total" type="hidden" value="0.00">
                  <div id="collecte_montant_total-error" class="field-error"></div>
                </div>
              </div>
              <div style="margin-top:12px;">
                <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-floppy-disk"></i> Creer collecte</button>
              </div>
            </form>
          </section>

          <section class="card" id="collecte-list-section">
            <div class="card-header" style="justify-content:space-between;align-items:center;">
              <h3 class="card-title"><i class="fa-solid fa-table-list"></i> Liste collectes</h3>
              <span class="badge badge-info"><?= count($rows) ?> lignes</span>
            </div>
            <div class="toolbar">
              <form method="get" action="planning_collecte.php" class="toolbar">
                <input class="input" style="width:320px;" type="text" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Chercher collecte, restaurant, user...">
                <select class="select" name="sort_by" style="min-width:220px;">
                  <option value="newest"<?= $sortBy === 'newest' ? ' selected' : '' ?>>Tri: plus recentes</option>
                  <option value="oldest"<?= $sortBy === 'oldest' ? ' selected' : '' ?>>Tri: plus anciennes</option>
                  <option value="montant_desc"<?= $sortBy === 'montant_desc' ? ' selected' : '' ?>>Montant decroissant</option>
                  <option value="montant_asc"<?= $sortBy === 'montant_asc' ? ' selected' : '' ?>>Montant croissant</option>
                  <option value="status_asc"<?= $sortBy === 'status_asc' ? ' selected' : '' ?>>Statut A-Z</option>
                  <option value="heure_desc"<?= $sortBy === 'heure_desc' ? ' selected' : '' ?>>Heure plus tard</option>
                  <option value="heure_asc"<?= $sortBy === 'heure_asc' ? ' selected' : '' ?>>Heure plus tot</option>
                </select>
                <button class="btn btn-outline btn-sm" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
              </form>
              <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a class="btn btn-outline btn-sm" href="planning_collecte.php?<?= htmlspecialchars(http_build_query(['q' => $search, 'sort_by' => $sortBy, 'export' => 'pdf']), ENT_QUOTES, 'UTF-8') ?>">Export PDF</a>
                <a class="btn btn-outline btn-sm" href="planning_collecte.php">Reset</a>
              </div>
            </div>
            <div class="table-wrap">
              <table class="table">
                <thead>
                  <tr>
                    <th>ID Collecte</th>
                    <th>Restaurant</th>
                    <th>Preference</th>
                    <th>User</th>
                    <th>Items</th>
                    <th>Mode</th>
                    <th>Heure</th>
                    <th>Montant</th>
                    <th>Statut</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($rows)): ?>
                    <tr><td colspan="10" style="color:var(--color-text-muted);">Aucune collecte.</td></tr>
                  <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                      <?php
                        $prefValues = collecte_pref_values($row);
                        $mode = strtolower((string)($row['mode_collecte'] ?? 'pickup'));
                        $statusOptions = collecte_status_options($mode);
                        $currentStatus = trim((string)($row['statut'] ?? 'en_attente'));
                        $isDelivery = $mode === 'delivery';
                        $driverFirst = trim((string)($row['delivery_driver_first_name'] ?? ''));
                        $driverLast = trim((string)($row['delivery_driver_last_name'] ?? ''));
                        $driverName = trim($driverFirst . ' ' . $driverLast);
                        $driverContact = trim((string)($row['delivery_driver_contact'] ?? ''));
                        $driverUpdatedAt = trim((string)($row['delivery_driver_updated_at'] ?? ''));
                        $driverUpdatedTs = $driverUpdatedAt !== '' ? strtotime($driverUpdatedAt) : false;
                        $secondsSince = ($driverUpdatedTs !== false) ? max(0, time() - (int)$driverUpdatedTs) : null;
                        $isLiveNow = ($secondsSince !== null && $secondsSince <= 20);
                        if (!in_array($currentStatus, $statusOptions, true) && $currentStatus !== '') {
                            $statusOptions[] = $currentStatus;
                        }
                      ?>
                      <tr class="<?= $selectedId > 0 && (int)$row['id_collecte'] === $selectedId ? 'row-selected' : '' ?>">
                        <td>#<?= (int)$row['id_collecte'] ?></td>
                        <td>
                          #<?= (int)$row['id_restaurant'] ?> - <?= htmlspecialchars((string)($row['restaurant_nom'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                          #<?= (int)$row['id_pref'] ?><br>
                          <span style="color:var(--color-text-muted);">regime alimentaire : <?= htmlspecialchars((string)$prefValues['regime'], ENT_QUOTES, 'UTF-8') ?></span><br>
                          <span style="color:var(--color-text-muted);">adresse : <?= htmlspecialchars((string)$prefValues['adresse'], ENT_QUOTES, 'UTF-8') ?></span><br>
                          <span style="color:var(--color-text-muted);">allergies : <?= htmlspecialchars((string)$prefValues['allergies'], ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td>
                          #<?= (int)$row['id_user'] ?> - <?= htmlspecialchars((string)($row['user_nom'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td><?= htmlspecialchars(collecte_items_display($row['items'] ?? []), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(collecte_status_label($mode), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$row['heure_souhaitee'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format((float)$row['montant_total'], 2) ?> DT</td>
                        <td>
                          <?= htmlspecialchars(collecte_status_label($currentStatus), ENT_QUOTES, 'UTF-8') ?>
                          <?php if ($isDelivery && $driverUpdatedAt !== ''): ?>
                            <div class="live-pill <?= $isLiveNow ? 'active' : 'stale' ?>">
                              <?= $isLiveNow ? 'Livreur en direct' : ('Signal ancien (' . (int)$secondsSince . 's)') ?>
                            </div>
                          <?php endif; ?>
                          <?php if ($isDelivery): ?>
                            <div class="hint-inline">Livreur: <?= htmlspecialchars($driverName !== '' ? $driverName : 'non assigne', ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if ($driverContact !== ''): ?>
                              <div class="hint-inline">Contact: <?= htmlspecialchars($driverContact, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                          <?php endif; ?>
                        </td>
                        <td>
                          <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <a class="btn btn-outline btn-sm" href="collecte_detail.php?id_collecte=<?= (int)$row['id_collecte'] ?>#collecte-detail-card">Voir detail</a>
                            <form method="post" action="../Controller/planning_collecte.php?action=admin_update_collecte_status">
                              <input type="hidden" name="id_collecte" value="<?= (int)$row['id_collecte'] ?>">
                              <select class="select" name="statut" style="min-width:130px;">
                                <?php foreach ($statusOptions as $statusOption): ?>
                                  <option value="<?= htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8') ?>"<?= ($currentStatus === $statusOption) ? ' selected' : '' ?>>
                                    <?= htmlspecialchars(collecte_status_label($statusOption), ENT_QUOTES, 'UTF-8') ?>
                                  </option>
                                <?php endforeach; ?>
                              </select>
                              <button class="btn btn-outline btn-sm" type="submit">Maj statut</button>
                            </form>
                            <form method="post" action="../Controller/planning_collecte.php?action=admin_delete_collecte" onsubmit="return confirmCollecteDelete(this);">
                              <input type="hidden" name="id_collecte" value="<?= (int)$row['id_collecte'] ?>">
                              <input type="hidden" name="restock_on_delete" value="1">
                              <input type="hidden" name="current_status" value="<?= htmlspecialchars($currentStatus, ENT_QUOTES, 'UTF-8') ?>">
                              <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                            </form>
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

  <style>
    .collecte-confirm-modal {
      position: fixed;
      inset: 0;
      display: none;
      align-items: center;
      justify-content: center;
      background: rgba(0, 0, 0, 0.5);
      z-index: 2000;
      padding: 16px;
    }
    .collecte-confirm-modal.active { display: flex; }
    .collecte-confirm-card {
      width: min(420px, 100%);
      background: #1a2740;
      border: 1px solid var(--color-dark-border);
      border-radius: 12px;
      padding: 18px;
      color: var(--color-white);
    }
    .collecte-confirm-message {
      margin: 0 0 14px 0;
      font-size: 0.95rem;
    }
    .collecte-confirm-actions {
      display: flex;
      justify-content: flex-end;
      gap: 8px;
    }
  </style>
  <div id="collecte-confirm-modal" class="collecte-confirm-modal" aria-hidden="true">
    <div class="collecte-confirm-card" role="dialog" aria-modal="true" aria-labelledby="collecte-confirm-message">
      <p id="collecte-confirm-message" class="collecte-confirm-message"></p>
      <div class="collecte-confirm-actions">
        <button type="button" class="btn btn-outline btn-sm" id="collecte-confirm-no">Non</button>
        <button type="button" class="btn btn-primary btn-sm" id="collecte-confirm-yes">Oui</button>
      </div>
    </div>
  </div>

  <script src="../js/app.js?v=20260420c"></script>
  <script src="../js/components.js?v=20260421a"></script>
  <script src="../assets/vendor/leaflet/leaflet.js"></script>
  <?php if (!empty($realtimeConfig['enabled'])): ?>
    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
  <?php endif; ?>
  <script src="../js/collecte-address-picker.js"></script>
  <script src="../js/collectes-map.js"></script>
  <script>
    window.ADMIN_COLLECTE_RESTAURANTS = <?= json_encode($restaurantsForPicker, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    window.ADMIN_COLLECTE_PREF_IDS = <?= json_encode(array_map('intval', $prefIds), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    window.ADMIN_COLLECTE_USER_IDS = <?= json_encode(array_map('intval', $userIds), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    window.ADMIN_COLLECTE_PREF_ALLERGIES = <?= json_encode($prefAllergiesMap, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    window.ADMIN_COLLECTES_MAP_POINTS = <?= json_encode($adminMapPoints, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  </script>
  <script src="../js/admin-planning-collecte-validation.js"></script>
  <script>
    let _collecteConfirmCallbacks = { yes: null, no: null };

    function openCollecteConfirm(message, onYes, onNo) {
      const modal = document.getElementById('collecte-confirm-modal');
      const messageEl = document.getElementById('collecte-confirm-message');
      if (!modal || !messageEl) return;
      messageEl.textContent = message;
      _collecteConfirmCallbacks = { yes: onYes || null, no: onNo || null };
      modal.classList.add('active');
      modal.setAttribute('aria-hidden', 'false');
    }

    function closeCollecteConfirm(choice) {
      const modal = document.getElementById('collecte-confirm-modal');
      if (!modal) return;
      modal.classList.remove('active');
      modal.setAttribute('aria-hidden', 'true');
      const callbacks = _collecteConfirmCallbacks;
      _collecteConfirmCallbacks = { yes: null, no: null };
      if (choice && typeof callbacks.yes === 'function') callbacks.yes();
      if (!choice && typeof callbacks.no === 'function') callbacks.no();
    }

    function confirmCollecteDelete(form) {
      const statusInput = form.querySelector('input[name="current_status"]');
      const restockInput = form.querySelector('input[name="restock_on_delete"]');
      const status = statusInput ? String(statusInput.value || '').trim() : '';

      if (status === 'en_attente' || status === 'en_cours_livraison') {
        openCollecteConfirm(
          'Voulez vous remettre cet item dans le stock ?',
          function () {
            if (restockInput) restockInput.value = '1';
            form.submit();
          },
          function () {
            if (restockInput) restockInput.value = '0';
            form.submit();
          }
        );
      } else {
        openCollecteConfirm(
          'Voulez vous supprimer cette collecte ?',
          function () {
            if (restockInput) restockInput.value = '0';
            form.submit();
          },
          function () {}
        );
      }

      return false;
    }

    document.addEventListener('DOMContentLoaded', function () {
      const yesBtn = document.getElementById('collecte-confirm-yes');
      const noBtn = document.getElementById('collecte-confirm-no');
      const modal = document.getElementById('collecte-confirm-modal');
      if (yesBtn) yesBtn.addEventListener('click', function () { closeCollecteConfirm(true); });
      if (noBtn) noBtn.addEventListener('click', function () { closeCollecteConfirm(false); });
      if (modal) {
        modal.addEventListener('click', function (event) {
          if (event.target === modal) closeCollecteConfirm(false);
        });
      }
      if (window.App && typeof window.App.requireAuth === 'function') {
        App.requireAuth(['admin']);
      }
      if (typeof window.initCollecteAddressPicker === 'function') {
        window.initCollecteAddressPicker({
          mapId: 'collecte_address_map',
          addressInputId: 'collecte_adresse_livraison',
          latInputId: 'collecte_adresse_lat',
          lngInputId: 'collecte_adresse_lng',
          searchInputId: 'collecte_address_search',
          searchBtnId: 'collecte_address_search_btn',
          currentLocationBtnId: 'collecte_current_location_btn',
          coordsLabelId: 'collecte_coords_hint',
          defaultCenter: [36.8065, 10.1815],
          defaultZoom: 11
        });
      }
      if (typeof window.initCollectesMap === 'function') {
        window.initCollectesMap({
          containerId: 'admin-collectes-map',
          points: window.ADMIN_COLLECTES_MAP_POINTS || [],
          liveEndpoint: '../Controller/planning_collecte.php?action=live_points&scope=admin',
          pusher: {
            enabled: <?= !empty($realtimeConfig['enabled']) ? 'true' : 'false' ?>,
            key: <?= json_encode((string)($realtimeConfig['key'] ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            cluster: <?= json_encode((string)($realtimeConfig['cluster'] ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            channel: 'caremeal-admin-live',
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
