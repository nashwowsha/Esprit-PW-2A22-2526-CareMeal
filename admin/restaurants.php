<?php
require_once __DIR__ . '/../Controller/RestaurantController.php';
require_once __DIR__ . '/../Controller/PreferenceController.php';
require_once __DIR__ . '/../Controller/PdfExport.php';

$controller = new RestaurantController();
$preferenceController = new PreferenceController();
$status = $_GET['status'] ?? '';
$search = trim((string)($_GET['q'] ?? ''));
$restaurantSort = strtolower(trim((string)($_GET['sort'] ?? 'id_desc')));
$mealSearch = trim((string)($_GET['meal_q'] ?? ''));
$mealSort = strtolower(trim((string)($_GET['meal_sort'] ?? 'restaurant_asc')));
$allowedRestaurantSort = ['id_desc', 'id_asc', 'nom_asc', 'nom_desc', 'owner_asc', 'owner_desc', 'meals_desc', 'meals_asc'];
$allowedMealSort = ['restaurant_asc', 'restaurant_desc', 'meal_asc', 'meal_desc', 'qty_desc', 'qty_asc', 'price_desc', 'price_asc'];
if (!in_array($restaurantSort, $allowedRestaurantSort, true)) {
    $restaurantSort = 'id_desc';
}
if (!in_array($mealSort, $allowedMealSort, true)) {
    $mealSort = 'restaurant_asc';
}
$selectedId = isset($_GET['selected_id']) ? (int)$_GET['selected_id'] : 0;
$editId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$mealEdit = trim((string)($_GET['meal_edit'] ?? ''));

$restaurants = $controller->getAllRestaurantsForAdmin($search);
usort($restaurants, static function ($a, $b) use ($restaurantSort) {
    $idA = (int)($a['id_restaurant'] ?? 0);
    $idB = (int)($b['id_restaurant'] ?? 0);
    $nameA = strtolower(trim((string)($a['nom'] ?? '')));
    $nameB = strtolower(trim((string)($b['nom'] ?? '')));
    $ownerA = (int)($a['id_owner'] ?? 0);
    $ownerB = (int)($b['id_owner'] ?? 0);
    $mealsA = is_array($a['meals'] ?? null) ? count($a['meals']) : 0;
    $mealsB = is_array($b['meals'] ?? null) ? count($b['meals']) : 0;

    switch ($restaurantSort) {
        case 'id_asc':
            return $idA <=> $idB;
        case 'nom_asc':
            return strcmp($nameA, $nameB);
        case 'nom_desc':
            return strcmp($nameB, $nameA);
        case 'owner_asc':
            return $ownerA <=> $ownerB;
        case 'owner_desc':
            return $ownerB <=> $ownerA;
        case 'meals_desc':
            return $mealsB <=> $mealsA;
        case 'meals_asc':
            return $mealsA <=> $mealsB;
        case 'id_desc':
        default:
            return $idB <=> $idA;
    }
});

$selectedRestaurant = null;
$editRestaurant = null;
foreach ($restaurants as $r) {
    if ($selectedId > 0 && (int)$r['id_restaurant'] === $selectedId) { $selectedRestaurant = $r; }
    if ($editId > 0 && (int)$r['id_restaurant'] === $editId) { $editRestaurant = $r; }
}
if (!$selectedRestaurant && $editRestaurant) {
    $selectedRestaurant = $editRestaurant;
}

// Quantite disponible = quantite base restaurant - reservations actives de collecte.
$reservedByRestaurantMeal = [];
try {
    $db = config::getConnexion();
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
        if (!isset($reservedByRestaurantMeal[$restaurantId])) {
            $reservedByRestaurantMeal[$restaurantId] = [];
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
            if (!isset($reservedByRestaurantMeal[$restaurantId][$mealId])) {
                $reservedByRestaurantMeal[$restaurantId][$mealId] = 0;
            }
            $reservedByRestaurantMeal[$restaurantId][$mealId] += $qty;
        }
    }
} catch (Exception $e) {
    $reservedByRestaurantMeal = [];
}

if (!function_exists('admin_get_available_meal_qty')) {
    function admin_get_available_meal_qty($reservedByRestaurantMeal, $restaurantId, $mealId, $baseQty)
    {
        $restaurantId = (int)$restaurantId;
        $mealId = strtolower(trim((string)$mealId));
        $baseQty = max(0, (int)$baseQty);
        $reservedQty = (int)($reservedByRestaurantMeal[$restaurantId][$mealId] ?? 0);
        return max(0, $baseQty - $reservedQty);
    }
}

$mealEditData = null;
if ($selectedRestaurant && $mealEdit !== '') {
    foreach (($selectedRestaurant['meals'] ?? []) as $meal) {
        if (($meal['meal_id'] ?? '') === $mealEdit) {
            $mealEditData = $meal;
            break;
        }
    }
}

$formAction = $editRestaurant ? 'admin_update_restaurant' : 'admin_create_restaurant';
$formTitle = $editRestaurant ? 'Edit Restaurant #' . (int)$editRestaurant['id_restaurant'] : 'Create Restaurant';
$mealAction = $mealEditData ? 'admin_update_meal' : 'admin_add_meal';
$mealTitle = $mealEditData ? 'Edit Meal' : 'Add Meal';

$regimeOptions = $preferenceController->getRegimeOptions();
if (empty($regimeOptions)) {
    $regimeOptions = [
        ['value' => 'halal', 'label' => 'Halal', 'icon' => 'fa-star-and-crescent', 'icon_type' => 'fa', 'icon_image' => ''],
        ['value' => 'vegetarien', 'label' => 'Vegetarien', 'icon' => 'fa-leaf', 'icon_type' => 'fa', 'icon_image' => ''],
        ['value' => 'vegan', 'label' => 'Vegan', 'icon' => 'fa-seedling', 'icon_type' => 'fa', 'icon_image' => ''],
        ['value' => 'sans-gluten', 'label' => 'Sans gluten', 'icon' => 'fa-wheat-awn', 'icon_type' => 'fa', 'icon_image' => ''],
        ['value' => 'bio', 'label' => 'Bio', 'icon' => 'fa-spa', 'icon_type' => 'fa', 'icon_image' => ''],
        ['value' => 'sans-lactose', 'label' => 'Sans lactose', 'icon' => 'fa-glass-water', 'icon_type' => 'fa', 'icon_image' => ''],
        ['value' => 'budget', 'label' => 'Petit budget', 'icon' => 'fa-coins', 'icon_type' => 'fa', 'icon_image' => ''],
        ['value' => 'equilibre', 'label' => 'Equilibre', 'icon' => 'fa-scale-balanced', 'icon_type' => 'fa', 'icon_image' => ''],
    ];
}

$openTimeValue = '';
$closeTimeValue = '';
if ($editRestaurant && !empty($editRestaurant['horaires']) && preg_match('/^(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})$/', (string)$editRestaurant['horaires'], $matches)) {
    $openTimeValue = $matches[1];
    $closeTimeValue = $matches[2];
}

$selectedMealRegimes = [];
if ($mealEditData && !empty($mealEditData['regime_tags'])) {
    $selectedMealRegimes = array_values(array_filter(array_map('trim', preg_split('/[,;]+/', (string)$mealEditData['regime_tags']))));
}

function isMealRegimeSelected($value, $selectedMealRegimes)
{
    $value = strtolower(trim((string)$value));
    $normalized = array_map(static function ($item) {
        return strtolower(trim((string)$item));
    }, $selectedMealRegimes);

    return in_array($value, $normalized, true);
}

$statusMessages = [
    'success_restaurant_created' => ['class' => 'success', 'text' => 'Restaurant created successfully.'],
    'success_restaurant_updated' => ['class' => 'success', 'text' => 'Restaurant updated successfully.'],
    'success_restaurant_deleted' => ['class' => 'success', 'text' => 'Restaurant deleted successfully.'],
    'success_meal_added' => ['class' => 'success', 'text' => 'Meal added successfully.'],
    'success_meal_updated' => ['class' => 'success', 'text' => 'Meal updated successfully.'],
    'success_meal_deleted' => ['class' => 'success', 'text' => 'Meal deleted successfully.'],
    'error_restaurant_exists' => ['class' => 'error', 'text' => 'This owner already has a restaurant with this name.'],
    'error_restaurant_image_invalid' => ['class' => 'error', 'text' => 'Invalid image file (png/jpg/jpeg/webp/gif, max 3MB).'],
    'error_validation' => ['class' => 'error', 'text' => 'Validation failed.'],
    'error_forbidden' => ['class' => 'error', 'text' => 'Forbidden action.'],
    'error_missing_fields' => ['class' => 'error', 'text' => 'Missing required fields.'],
    'error_not_found' => ['class' => 'error', 'text' => 'Record not found.'],
    'error_db' => ['class' => 'error', 'text' => 'Database error.'],
    'error_unknown_action' => ['class' => 'error', 'text' => 'Unknown action.'],
    'error_invalid_request' => ['class' => 'error', 'text' => 'Invalid request.'],
];

$allMealsRows = [];
foreach ($restaurants as $restaurantRow) {
    foreach (($restaurantRow['meals'] ?? []) as $mealRow) {
        $baseQty = (int)($mealRow['quantity'] ?? 0);
        $availableQty = admin_get_available_meal_qty(
            $reservedByRestaurantMeal,
            (int)$restaurantRow['id_restaurant'],
            (string)($mealRow['meal_id'] ?? ''),
            $baseQty
        );
        $allMealsRows[] = [
            'restaurant_id' => (int)$restaurantRow['id_restaurant'],
            'restaurant_name' => (string)$restaurantRow['nom'],
            'owner_id' => (int)$restaurantRow['id_owner'],
            'owner_name' => (string)($restaurantRow['owner_nom'] ?? 'Unknown'),
            'meal' => $mealRow,
            'available_quantity' => $availableQty,
        ];
    }
}
$allMealsRowsFiltered = $allMealsRows;
if ($mealSearch !== '') {
    $mealNeedle = strtolower($mealSearch);
    $allMealsRowsFiltered = array_values(array_filter($allMealsRowsFiltered, static function ($row) use ($mealNeedle) {
        $meal = $row['meal'] ?? [];
        $haystack = strtolower(
            (string)($row['restaurant_id'] ?? '') . ' ' .
            (string)($row['restaurant_name'] ?? '') . ' ' .
            (string)($row['owner_id'] ?? '') . ' ' .
            (string)($row['owner_name'] ?? '') . ' ' .
            (string)($meal['meal_name'] ?? '') . ' ' .
            (string)($meal['ingredients'] ?? '') . ' ' .
            (string)($meal['allergens'] ?? '') . ' ' .
            (string)($meal['regime_tags'] ?? '')
        );

        return strpos($haystack, $mealNeedle) !== false;
    }));
}
usort($allMealsRowsFiltered, static function ($a, $b) use ($mealSort) {
    $mealA = $a['meal'] ?? [];
    $mealB = $b['meal'] ?? [];
    $restaurantA = strtolower(trim((string)($a['restaurant_name'] ?? '')));
    $restaurantB = strtolower(trim((string)($b['restaurant_name'] ?? '')));
    $mealNameA = strtolower(trim((string)($mealA['meal_name'] ?? '')));
    $mealNameB = strtolower(trim((string)($mealB['meal_name'] ?? '')));
    $qtyA = (int)($a['available_quantity'] ?? 0);
    $qtyB = (int)($b['available_quantity'] ?? 0);
    $priceA = (float)($mealA['price'] ?? 0);
    $priceB = (float)($mealB['price'] ?? 0);

    switch ($mealSort) {
        case 'restaurant_desc':
            return strcmp($restaurantB, $restaurantA);
        case 'meal_asc':
            return strcmp($mealNameA, $mealNameB);
        case 'meal_desc':
            return strcmp($mealNameB, $mealNameA);
        case 'qty_desc':
            return $qtyB <=> $qtyA;
        case 'qty_asc':
            return $qtyA <=> $qtyB;
        case 'price_desc':
            return $priceB <=> $priceA;
        case 'price_asc':
            return $priceA <=> $priceB;
        case 'restaurant_asc':
        default:
            return strcmp($restaurantA, $restaurantB);
    }
});

if (isset($_GET['restaurant_export']) && $_GET['restaurant_export'] === 'pdf') {
    $pdfRows = [];
    foreach ($restaurants as $row) {
        $pdfRows[] = [
            (int)($row['id_restaurant'] ?? 0),
            (string)($row['nom'] ?? ''),
            (int)($row['id_owner'] ?? 0),
            (string)($row['owner_nom'] ?? ''),
            (string)($row['localisation'] ?? ''),
            (string)($row['telephone'] ?? ''),
            (string)($row['horaires'] ?? ''),
            (int)($row['actif'] ?? 0),
            is_array($row['meals'] ?? null) ? count($row['meals']) : 0,
        ];
    }
    caremeal_stream_table_pdf(
        'admin_restaurants_' . date('Ymd_His') . '.pdf',
        'Restaurants (admin)',
        ['ID Restaurant', 'Nom', 'ID Owner', 'Nom owner', 'Localisation', 'Telephone', 'Horaires', 'Actif', 'Nombre meals'],
        $pdfRows,
        'landscape'
    );
}

if (isset($_GET['meal_export']) && $_GET['meal_export'] === 'pdf') {
    $pdfRows = [];
    foreach ($allMealsRowsFiltered as $row) {
        $meal = $row['meal'] ?? [];
        $pdfRows[] = [
            (int)($row['restaurant_id'] ?? 0),
            (string)($row['restaurant_name'] ?? ''),
            (int)($row['owner_id'] ?? 0),
            (string)($row['owner_name'] ?? ''),
            (string)($meal['meal_name'] ?? ''),
            (string)($meal['ingredients'] ?? ''),
            (int)($row['available_quantity'] ?? 0),
            (string)($meal['pricing_mode'] ?? ''),
            number_format((float)($meal['price'] ?? 0), 2, '.', ''),
            (string)($meal['allergens'] ?? ''),
            (string)($meal['regime_tags'] ?? ''),
        ];
    }
    caremeal_stream_table_pdf(
        'admin_all_meals_' . date('Ymd_His') . '.pdf',
        'Tous les meals/aliments (admin)',
        ['ID Restaurant', 'Restaurant', 'ID Owner', 'Owner', 'Meal', 'Ingredients', 'Quantite disponible', 'Mode prix', 'Prix', 'Allergenes', 'Regime tags'],
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
  <meta name="description" content="Backoffice restaurants CRUD.">
  <title>Admin Restaurants - CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="../assets/vendor/leaflet/leaflet.css">
  <style>
    html { scroll-behavior: smooth; }
    .stack { display: grid; grid-template-columns: 1fr; gap: 18px; }
    .alert-box { border-radius: var(--radius-md); padding: 12px 14px; border: 1px solid transparent; font-size: .9rem; }
    .alert-box.success { color: #0f5132; background: rgba(25,135,84,.18); border-color: rgba(25,135,84,.4); }
    .alert-box.error { color: #842029; background: rgba(220,53,69,.16); border-color: rgba(220,53,69,.4); }
    .toolbar { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
    .input, .textarea, .select { width: 100%; background: rgba(255,255,255,.04); border: 1px solid var(--color-dark-border); border-radius: var(--radius-md); color: var(--color-white); padding: 10px 12px; }
    .textarea { min-height: 90px; resize: vertical; }
    .grid2 { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 12px; }
    .full { grid-column: 1 / -1; }
    .field-error { color: #ff9da7; font-size: .8rem; min-height: 16px; display: none; margin-top: 5px; }
    .input-error { border-color: rgba(220,53,69,.7)!important; box-shadow: 0 0 0 1px rgba(220,53,69,.25); }
    .cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:14px; }
    .card-item { border:1px solid var(--color-dark-border); border-radius:var(--radius-lg); overflow:hidden; background:rgba(255,255,255,.03); cursor:pointer; }
    .card-item.selected { border-color: var(--color-primary); box-shadow: 0 0 0 1px rgba(249,115,22,.25); }
    .card-item img { width:100%; height:160px; object-fit:cover; display:block; }
    .card-item .body { padding: 12px; }
    .meta { color: var(--color-text-muted); font-size:.82rem; margin:4px 0; }
    .actions { display:flex; gap:8px; flex-wrap:wrap; margin-top:10px; }
    .table-wrap { overflow:auto; }
    .table { width:100%; border-collapse:collapse; min-width:900px; }
    .table th, .table td { border-bottom:1px solid var(--color-dark-border); padding:10px; text-align:left; vertical-align:top; }
    .table-clickable tbody tr { cursor: pointer; transition: background .15s ease; }
    .table-clickable tbody tr:hover { background: rgba(255,255,255,.04); }
    .table-row-selected { background: rgba(249,115,22,.14); }
    .table-row-selected:hover { background: rgba(249,115,22,.2) !important; }
    .section-note { color: var(--color-text-muted); font-size: .84rem; margin: 0 0 8px; }
    .form-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-top: 12px; }
    .empty-box { border: 1px dashed var(--color-dark-border); border-radius: var(--radius-md); padding: 14px; color: var(--color-text-muted); }
    .tags-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; margin-bottom: 6px; }
    .tag { display: inline-flex; align-items: center; gap: 7px; border: 1px solid var(--color-dark-border); border-radius: 999px; padding: 8px 11px; background: rgba(255,255,255,.03); font-size: .86rem; cursor: pointer; }
    .tag input[type="checkbox"] { display: none; }
    .tag.selected { border-color: var(--color-primary); color: var(--color-primary); background: rgba(249,115,22,.12); }
    .tag-emoji img { width: 18px; height: 18px; border-radius: 4px; object-fit: cover; display: inline-block; }
    .location-picker-tools { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-top: 8px; }
    .location-map { width: 100%; height: 260px; border: 1px solid var(--color-dark-border); border-radius: var(--radius-md); margin-top: 10px; overflow: hidden; }
    .location-coords { color: var(--color-text-muted); font-size: .82rem; margin-top: 8px; }
    .hidden { display: none !important; }
    @media (max-width: 900px) { .grid2 { grid-template-columns:1fr; } }
  </style>
</head>
<body>
  <div class="dashboard-layout">
    <?php
      $activePage = 'restaurants';
      require __DIR__ . '/_admin_sidebar.php';
    ?>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle"><i class="fa-solid fa-bars"></i></button>
          <div class="page-title"><h2>Restaurants Management</h2><p>Admin full CRUD + search by name or owner id</p></div>
        </div>
        <div class="header-right">
          <button class="header-notification"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button>
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

          <section class="card animate-fade-in-up" id="admin-restaurants-section">
            <div class="card-header" style="justify-content:space-between;align-items:center;">
              <h3 class="card-title"><i class="fa-solid fa-shop"></i> All Restaurants (all partners)</h3>
              <span class="badge badge-info"><?= count($restaurants) ?> rows</span>
            </div>
            <div class="toolbar">
              <form method="get" action="restaurants.php" class="toolbar">
                <input class="input" style="width:340px;" type="text" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search by restaurant name or partner id">
                <select class="select" name="sort" style="min-width:220px;">
                  <option value="id_desc"<?= $restaurantSort === 'id_desc' ? ' selected' : '' ?>>Sort: newest IDs</option>
                  <option value="id_asc"<?= $restaurantSort === 'id_asc' ? ' selected' : '' ?>>Sort: oldest IDs</option>
                  <option value="nom_asc"<?= $restaurantSort === 'nom_asc' ? ' selected' : '' ?>>Name A-Z</option>
                  <option value="nom_desc"<?= $restaurantSort === 'nom_desc' ? ' selected' : '' ?>>Name Z-A</option>
                  <option value="owner_asc"<?= $restaurantSort === 'owner_asc' ? ' selected' : '' ?>>Owner ID asc</option>
                  <option value="owner_desc"<?= $restaurantSort === 'owner_desc' ? ' selected' : '' ?>>Owner ID desc</option>
                  <option value="meals_desc"<?= $restaurantSort === 'meals_desc' ? ' selected' : '' ?>>Most meals first</option>
                  <option value="meals_asc"<?= $restaurantSort === 'meals_asc' ? ' selected' : '' ?>>Least meals first</option>
                </select>
                <button class="btn btn-outline btn-sm" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
              </form>
              <a class="btn btn-outline btn-sm" href="restaurants.php">Reset</a>
              <a class="btn btn-outline btn-sm" href="restaurants.php?<?= htmlspecialchars(http_build_query(['q' => $search, 'sort' => $restaurantSort, 'restaurant_export' => 'pdf']), ENT_QUOTES, 'UTF-8') ?>">Export restaurants PDF</a>
              <?php if ($selectedRestaurant): ?>
                <span class="badge badge-info">Selected: #<?= (int)$selectedRestaurant['id_restaurant'] ?> - <?= htmlspecialchars((string)$selectedRestaurant['nom'], ENT_QUOTES, 'UTF-8') ?></span>
              <?php endif; ?>
            </div>

            <p class="section-note">Click a restaurant row to load its meals and manage them.</p>
            <div class="table-wrap">
              <table class="table table-clickable">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Restaurant</th>
                    <th>Partner ID</th>
                    <th>Partner</th>
                    <th>Location</th>
                    <th>Phone</th>
                    <th>Hours</th>
                    <th>Meals</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($restaurants)): ?>
                    <tr><td colspan="9" style="color:var(--color-text-muted);">No restaurants found.</td></tr>
                  <?php else: ?>
                    <?php foreach ($restaurants as $restaurant): ?>
                      <?php $isSel = $selectedRestaurant && (int)$selectedRestaurant['id_restaurant'] === (int)$restaurant['id_restaurant']; ?>
                      <?php
                        $rowQuery = [
                            'q' => $search,
                            'sort' => $restaurantSort,
                            'meal_q' => $mealSearch,
                            'meal_sort' => $mealSort,
                            'selected_id' => (int)$restaurant['id_restaurant'],
                        ];
                        $rowEditQuery = [
                            'q' => $search,
                            'sort' => $restaurantSort,
                            'meal_q' => $mealSearch,
                            'meal_sort' => $mealSort,
                            'selected_id' => (int)$restaurant['id_restaurant'],
                            'edit_id' => (int)$restaurant['id_restaurant'],
                        ];
                      ?>
                      <tr class="<?= $isSel ? 'table-row-selected' : '' ?> js-restaurant-row" data-href="restaurants.php?<?= htmlspecialchars(http_build_query($rowQuery), ENT_QUOTES, 'UTF-8') ?>#admin-meals-section">
                        <td>#<?= (int)$restaurant['id_restaurant'] ?></td>
                        <td><strong><?= htmlspecialchars((string)$restaurant['nom'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td><?= (int)$restaurant['id_owner'] ?></td>
                        <td><?= htmlspecialchars((string)($restaurant['owner_nom'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$restaurant['localisation'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$restaurant['telephone'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$restaurant['horaires'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= count($restaurant['meals'] ?? []) ?></td>
                        <td>
                          <div class="actions">
                            <a class="btn btn-outline btn-sm" href="restaurants.php?<?= htmlspecialchars(http_build_query($rowQuery), ENT_QUOTES, 'UTF-8') ?>#admin-meals-section">Meals</a>
                            <a class="btn btn-outline btn-sm" href="restaurants.php?<?= htmlspecialchars(http_build_query($rowEditQuery), ENT_QUOTES, 'UTF-8') ?>#admin-restaurant-form-card">Edit</a>
                            <form method="post" action="../Controller/restaurant.php?action=admin_delete_restaurant" onsubmit="return confirm('Delete this restaurant?');">
                              <input type="hidden" name="id_owner" value="<?= (int)$restaurant['id_owner'] ?>">
                              <input type="hidden" name="id_restaurant" value="<?= (int)$restaurant['id_restaurant'] ?>">
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

          <section class="card animate-fade-in-up stagger-1" id="admin-restaurant-form-card">
            <div class="card-header" style="justify-content:space-between;align-items:center;">
              <h3 class="card-title"><i class="fa-solid fa-pen-to-square"></i> <?= htmlspecialchars($formTitle, ENT_QUOTES, 'UTF-8') ?></h3>
              <div class="actions">
                <?php if ($editRestaurant): ?>
                  <a class="btn btn-outline btn-sm" href="restaurants.php#admin-restaurant-form-card">New Restaurant</a>
                <?php endif; ?>
                <?php if ($selectedRestaurant): ?>
                  <a class="btn btn-outline btn-sm" href="restaurants.php?selected_id=<?= (int)$selectedRestaurant['id_restaurant'] ?>#admin-meals-section">Manage Meals</a>
                <?php endif; ?>
              </div>
            </div>
            <p class="section-note">Below the list: add a restaurant, edit an existing one, or delete it.</p>

            <form method="post" enctype="multipart/form-data" action="../Controller/restaurant.php?action=<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>" id="admin-restaurant-form" novalidate data-require-image="<?= $editRestaurant ? '0' : '1' ?>">
              <?php if ($editRestaurant): ?>
                <input type="hidden" name="id_restaurant" value="<?= (int)$editRestaurant['id_restaurant'] ?>">
              <?php endif; ?>
              <div class="grid2">
                <div>
                  <label for="owner_id">Partner ID</label>
                  <input class="input" id="owner_id" name="id_owner" type="text" value="<?= htmlspecialchars((string)($editRestaurant['id_owner'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                  <div id="owner_id-error" class="field-error"></div>
                </div>
                <div>
                  <label for="restaurant_name">Restaurant name</label>
                  <input class="input" id="restaurant_name" name="nom" type="text" value="<?= htmlspecialchars((string)($editRestaurant['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                  <div id="restaurant_name-error" class="field-error"></div>
                </div>
                <div class="full">
                  <label for="restaurant_location">Location</label>
                  <input class="input" id="restaurant_location" name="localisation" type="text" value="<?= htmlspecialchars((string)($editRestaurant['localisation'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" id="restaurant_location_lat" name="localisation_lat" value="<?= htmlspecialchars((string)($editRestaurant['localisation_lat'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" id="restaurant_location_lng" name="localisation_lng" value="<?= htmlspecialchars((string)($editRestaurant['localisation_lng'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                  <div class="location-picker-tools">
                    <input class="input" id="admin_restaurant_location_search" type="text" placeholder="Search on map (address or place)" style="max-width: 320px;">
                    <button class="btn btn-outline btn-sm" type="button" id="admin_restaurant_location_search_btn"><i class="fa-solid fa-magnifying-glass"></i> Search map</button>
                    <button class="btn btn-outline btn-sm" type="button" id="admin_restaurant_current_location_btn"><i class="fa-solid fa-location-crosshairs"></i> Ma position actuelle</button>
                  </div>
                  <div id="admin_restaurant_location_map" class="location-map"></div>
                  <div id="admin_restaurant_location_coords" class="location-coords">Coordonnees: non selectionnees</div>
                  <div id="restaurant_location-error" class="field-error"></div>
                </div>
                <div>
                  <label for="restaurant_phone">Telephone</label>
                  <input class="input" id="restaurant_phone" name="telephone" type="text" value="<?= htmlspecialchars((string)($editRestaurant['telephone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                  <div id="restaurant_phone-error" class="field-error"></div>
                </div>
                <div>
                  <label for="restaurant_open_time">Opening (09:00-22:00)</label>
                  <input class="input" id="restaurant_open_time" name="open_time" type="time" min="09:00" max="22:00" step="60" value="<?= htmlspecialchars((string)$openTimeValue, ENT_QUOTES, 'UTF-8') ?>">
                  <div id="restaurant_open_time-error" class="field-error"></div>
                </div>
                <div>
                  <label for="restaurant_close_time">Closing (09:00-22:00)</label>
                  <input class="input" id="restaurant_close_time" name="close_time" type="time" min="09:00" max="22:00" step="60" value="<?= htmlspecialchars((string)$closeTimeValue, ENT_QUOTES, 'UTF-8') ?>">
                  <div id="restaurant_close_time-error" class="field-error"></div>
                </div>
                <div>
                  <label><input type="checkbox" name="actif"<?= ($editRestaurant ? ((int)($editRestaurant['actif'] ?? 1) === 1 ? ' checked' : '') : ' checked') ?>> Active</label>
                </div>
                <div class="full">
                  <label for="restaurant_description">Description</label>
                  <textarea class="textarea" id="restaurant_description" name="description"><?= htmlspecialchars((string)($editRestaurant['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                  <div id="restaurant_description-error" class="field-error"></div>
                </div>
                <div class="full">
                  <label for="restaurant_image">Image</label>
                  <input class="input" id="restaurant_image" name="image_file" type="file">
                  <div id="restaurant_image-error" class="field-error"></div>
                </div>
              </div>
              <div class="form-actions">
                <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-floppy-disk"></i> <?= $editRestaurant ? 'Update Restaurant' : 'Create Restaurant' ?></button>
              </div>
            </form>
            <?php if ($editRestaurant): ?>
              <form method="post" action="../Controller/restaurant.php?action=admin_delete_restaurant" onsubmit="return confirm('Delete this restaurant?');" class="form-actions">
                <input type="hidden" name="id_owner" value="<?= (int)$editRestaurant['id_owner'] ?>">
                <input type="hidden" name="id_restaurant" value="<?= (int)$editRestaurant['id_restaurant'] ?>">
                <button class="btn btn-danger btn-sm" type="submit">Delete Restaurant</button>
              </form>
            <?php endif; ?>
          </section>

          <section class="card animate-fade-in-up stagger-2" id="admin-meals-section">
            <div class="card-header" style="justify-content:space-between;align-items:center;">
              <h3 class="card-title"><i class="fa-solid fa-utensils"></i> Meals by selected restaurant</h3>
              <?php if ($selectedRestaurant): ?>
                <span class="badge badge-info">Restaurant #<?= (int)$selectedRestaurant['id_restaurant'] ?> - <?= htmlspecialchars((string)$selectedRestaurant['nom'], ENT_QUOTES, 'UTF-8') ?></span>
              <?php endif; ?>
            </div>

            <?php if (!$selectedRestaurant): ?>
              <div class="empty-box">Select a restaurant in the table above to load and manage its meals.</div>
            <?php else: ?>
              <p class="section-note">First, see the current meals list. Then add, edit or delete meals for this restaurant.</p>
              <div class="table-wrap">
                <table class="table">
                  <thead><tr><th>Meal</th><th>Ingredients</th><th>Qty dispo</th><th>Regime</th><th>Allergens</th><th>Price</th><th>Actions</th></tr></thead>
                  <tbody>
                    <?php if (empty($selectedRestaurant['meals'])): ?>
                      <tr><td colspan="7" style="color:var(--color-text-muted);">No meals yet.</td></tr>
                    <?php else: ?>
                      <?php foreach ($selectedRestaurant['meals'] as $meal): ?>
                        <?php
                          $mealBaseQty = (int)($meal['quantity'] ?? 0);
                          $mealAvailableQty = admin_get_available_meal_qty(
                              $reservedByRestaurantMeal,
                              (int)$selectedRestaurant['id_restaurant'],
                              (string)($meal['meal_id'] ?? ''),
                              $mealBaseQty
                          );
                        ?>
                        <tr>
                          <td><?= htmlspecialchars((string)($meal['meal_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                          <td><?= htmlspecialchars((string)($meal['ingredients'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                          <td><?= $mealAvailableQty ?></td>
                          <td><?= htmlspecialchars((string)($meal['regime_tags'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                          <td><?= htmlspecialchars((string)($meal['allergens'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                          <td><?= (($meal['pricing_mode'] ?? 'free') === 'free') ? 'Free' : number_format((float)($meal['price'] ?? 0), 2) . ' DT' ?></td>
                          <td>
                            <div class="actions">
                              <a class="btn btn-outline btn-sm" href="restaurants.php?selected_id=<?= (int)$selectedRestaurant['id_restaurant'] ?>&meal_edit=<?= urlencode((string)$meal['meal_id']) ?>#admin-meals-section">Edit</a>
                              <form method="post" action="../Controller/restaurant.php?action=admin_delete_meal" onsubmit="return confirm('Delete this meal?');">
                                <input type="hidden" name="id_owner" value="<?= (int)$selectedRestaurant['id_owner'] ?>">
                                <input type="hidden" name="id_restaurant" value="<?= (int)$selectedRestaurant['id_restaurant'] ?>">
                                <input type="hidden" name="meal_id" value="<?= htmlspecialchars((string)$meal['meal_id'], ENT_QUOTES, 'UTF-8') ?>">
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

              <hr style="margin:16px 0;border:none;border-top:1px solid var(--color-dark-border);">
              <div class="card-header" style="padding:0 0 8px 0;">
                <h3 class="card-title"><i class="fa-solid fa-plus"></i> <?= htmlspecialchars($mealTitle, ENT_QUOTES, 'UTF-8') ?></h3>
                <?php if ($mealEditData): ?>
                  <a class="btn btn-outline btn-sm" href="restaurants.php?selected_id=<?= (int)$selectedRestaurant['id_restaurant'] ?>#admin-meals-section">Cancel Meal Edit</a>
                <?php endif; ?>
              </div>
              <form method="post" action="../Controller/restaurant.php?action=<?= htmlspecialchars($mealAction, ENT_QUOTES, 'UTF-8') ?>" id="admin-meal-form" novalidate>
                <input type="hidden" name="id_owner" value="<?= (int)$selectedRestaurant['id_owner'] ?>">
                <input type="hidden" name="id_restaurant" value="<?= (int)$selectedRestaurant['id_restaurant'] ?>">
                <?php if ($mealEditData): ?>
                  <input type="hidden" name="meal_id" value="<?= htmlspecialchars((string)$mealEditData['meal_id'], ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>
                <div class="grid2">
                  <div><label for="meal_name">Meal name</label><input class="input" id="meal_name" name="meal_name" type="text" value="<?= htmlspecialchars((string)($mealEditData['meal_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><div id="meal_name-error" class="field-error"></div></div>
                  <div><label for="meal_quantity">Quantity</label><input class="input" id="meal_quantity" name="quantity" type="number" step="1" placeholder="ex: 10" value="<?= htmlspecialchars((string)($mealEditData['quantity'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><div id="meal_quantity-error" class="field-error"></div></div>
                  <div class="full"><label for="meal_ingredients">Ingredients</label><textarea class="textarea" id="meal_ingredients" name="ingredients"><?= htmlspecialchars((string)($mealEditData['ingredients'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea><div id="meal_ingredients-error" class="field-error"></div></div>
                  <div>
                    <label>Regime tags</label>
                    <div class="tags-grid">
                      <?php foreach ($regimeOptions as $regime): ?>
                        <?php $checked = isMealRegimeSelected((string)$regime['value'], $selectedMealRegimes); ?>
                        <label class="tag<?= $checked ? ' selected' : '' ?>">
                          <input type="checkbox" name="regime_tags[]" value="<?= htmlspecialchars((string)$regime['value'], ENT_QUOTES, 'UTF-8') ?>"<?= $checked ? ' checked' : '' ?>>
                          <span class="tag-emoji">
                            <?php if (($regime['icon_type'] ?? 'fa') === 'image' && !empty($regime['icon_image'])): ?>
                              <img src="../<?= htmlspecialchars((string)$regime['icon_image'], ENT_QUOTES, 'UTF-8') ?>" alt="icon">
                            <?php else: ?>
                              <i class="fa-solid <?= htmlspecialchars((string)($regime['icon'] ?: 'fa-utensils'), ENT_QUOTES, 'UTF-8') ?>"></i>
                            <?php endif; ?>
                          </span>
                          <?= htmlspecialchars((string)$regime['label'], ENT_QUOTES, 'UTF-8') ?>
                        </label>
                      <?php endforeach; ?>
                    </div>
                    <div id="meal_regime_tags-error" class="field-error"></div>
                  </div>
                  <div><label for="meal_allergens">Allergens</label><input class="input" id="meal_allergens" name="allergens" type="text" value="<?= htmlspecialchars((string)($mealEditData['allergens'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><div id="meal_allergens-error" class="field-error"></div></div>
                  <div><label for="meal_pricing_mode">Pricing mode</label><select class="select" id="meal_pricing_mode" name="pricing_mode"><option value="free"<?= (($mealEditData['pricing_mode'] ?? '') === 'free') ? ' selected' : '' ?>>Free</option><option value="paid"<?= (($mealEditData['pricing_mode'] ?? '') === 'paid') ? ' selected' : '' ?>>Paid</option></select><div id="meal_pricing_mode-error" class="field-error"></div></div>
                  <div id="meal_price_group"><label for="meal_price">Price (if paid)</label><input class="input" id="meal_price" name="price" type="number" step="0.01" placeholder="ex: 7.50" value="<?= htmlspecialchars((string)($mealEditData['price'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"><div id="meal_price-error" class="field-error"></div></div>
                </div>
                <div class="form-actions">
                  <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-floppy-disk"></i> <?= $mealEditData ? 'Update Meal' : 'Add Meal' ?></button>
                </div>
              </form>
            <?php endif; ?>
          </section>

          <section class="card animate-fade-in-up stagger-3" id="admin-all-meals-section">
            <div class="card-header" style="justify-content:space-between;align-items:center;">
              <h3 class="card-title"><i class="fa-solid fa-list"></i> All meals/aliments (all restaurants)</h3>
              <span class="badge badge-info"><?= count($allMealsRowsFiltered) ?> filtered / <?= count($allMealsRows) ?> meals</span>
            </div>
            <div class="toolbar" style="margin-bottom:10px;">
              <form method="get" action="restaurants.php" class="toolbar">
                <input type="hidden" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="sort" value="<?= htmlspecialchars($restaurantSort, ENT_QUOTES, 'UTF-8') ?>">
                <?php if ($selectedRestaurant): ?>
                  <input type="hidden" name="selected_id" value="<?= (int)$selectedRestaurant['id_restaurant'] ?>">
                <?php endif; ?>
                <input class="input" style="width:300px;" type="text" name="meal_q" value="<?= htmlspecialchars($mealSearch, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search meal, allergen, restaurant...">
                <select class="select" name="meal_sort" style="min-width:220px;">
                  <option value="restaurant_asc"<?= $mealSort === 'restaurant_asc' ? ' selected' : '' ?>>Restaurant A-Z</option>
                  <option value="restaurant_desc"<?= $mealSort === 'restaurant_desc' ? ' selected' : '' ?>>Restaurant Z-A</option>
                  <option value="meal_asc"<?= $mealSort === 'meal_asc' ? ' selected' : '' ?>>Meal A-Z</option>
                  <option value="meal_desc"<?= $mealSort === 'meal_desc' ? ' selected' : '' ?>>Meal Z-A</option>
                  <option value="qty_desc"<?= $mealSort === 'qty_desc' ? ' selected' : '' ?>>Quantity desc</option>
                  <option value="qty_asc"<?= $mealSort === 'qty_asc' ? ' selected' : '' ?>>Quantity asc</option>
                  <option value="price_desc"<?= $mealSort === 'price_desc' ? ' selected' : '' ?>>Price desc</option>
                  <option value="price_asc"<?= $mealSort === 'price_asc' ? ' selected' : '' ?>>Price asc</option>
                </select>
                <button class="btn btn-outline btn-sm" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
              </form>
              <a class="btn btn-outline btn-sm" href="restaurants.php?<?= htmlspecialchars(http_build_query(['q' => $search, 'sort' => $restaurantSort, 'meal_export' => 'pdf']), ENT_QUOTES, 'UTF-8') ?>">Export meals PDF</a>
              <a class="btn btn-outline btn-sm" href="restaurants.php?<?= htmlspecialchars(http_build_query(['q' => $search, 'sort' => $restaurantSort]), ENT_QUOTES, 'UTF-8') ?>#admin-all-meals-section">Reset</a>
            </div>
            <div class="table-wrap">
              <table class="table">
                <thead>
                  <tr>
                    <th>Meal</th>
                    <th>Restaurant</th>
                    <th>Partner</th>
                    <th>Qty dispo</th>
                    <th>Price</th>
                    <th>Allergens</th>
                    <th>Regime tags</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($allMealsRows)): ?>
                    <tr><td colspan="7" style="color:var(--color-text-muted);">No meals in database.</td></tr>
                  <?php elseif (empty($allMealsRowsFiltered)): ?>
                    <tr><td colspan="7" style="color:var(--color-text-muted);">No meals match current filters.</td></tr>
                  <?php else: ?>
                    <?php foreach ($allMealsRowsFiltered as $row): ?>
                      <?php $meal = $row['meal']; ?>
                      <tr>
                        <td><?= htmlspecialchars((string)($meal['meal_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>#<?= (int)$row['restaurant_id'] ?> - <?= htmlspecialchars((string)$row['restaurant_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>#<?= (int)$row['owner_id'] ?> - <?= htmlspecialchars((string)$row['owner_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)($row['available_quantity'] ?? 0) ?></td>
                        <td><?= (($meal['pricing_mode'] ?? 'free') === 'free') ? 'Free' : number_format((float)($meal['price'] ?? 0), 2) . ' DT' ?></td>
                        <td><?= htmlspecialchars((string)($meal['allergens'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($meal['regime_tags'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
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
  <script src="../js/app.js?v=20260420c"></script>
  <script src="../js/components.js?v=20260421a"></script>
  <script src="../assets/vendor/leaflet/leaflet.js"></script>
  <script src="../js/collecte-address-picker.js"></script>
  <script src="../js/admin-restaurants-validation.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (window.App && typeof window.App.requireAuth === 'function') {
        App.requireAuth(['admin']);
      }

      if (typeof window.initCollecteAddressPicker === 'function') {
        window.initCollecteAddressPicker({
          mapId: 'admin_restaurant_location_map',
          addressInputId: 'restaurant_location',
          latInputId: 'restaurant_location_lat',
          lngInputId: 'restaurant_location_lng',
          searchInputId: 'admin_restaurant_location_search',
          searchBtnId: 'admin_restaurant_location_search_btn',
          currentLocationBtnId: 'admin_restaurant_current_location_btn',
          coordsLabelId: 'admin_restaurant_location_coords',
          defaultCenter: [36.8065, 10.1815],
          defaultZoom: 12
        });
      }

      document.querySelectorAll('.js-restaurant-row').forEach(function (item) {
        item.addEventListener('click', function (event) {
          if (event.target.closest('a') || event.target.closest('button') || event.target.closest('form')) return;
          var href = item.getAttribute('data-href');
          if (!href) return;
          var targetUrl = new URL(href, window.location.href);
          var currentUrl = new URL(window.location.href);
          if (
            targetUrl.pathname === currentUrl.pathname &&
            targetUrl.search === currentUrl.search &&
            targetUrl.hash === currentUrl.hash
          ) {
            var targetEl = document.querySelector(targetUrl.hash || '#admin-meals-section');
            if (targetEl) {
              targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            return;
          }
          window.location.href = targetUrl.toString();
        });
      });
    });
  </script>
</body>
</html>











