<?php
require_once __DIR__ . '/../Controller/RestaurantController.php';
require_once __DIR__ . '/../Controller/PreferenceController.php';
require_once __DIR__ . '/../Controller/PlanningCollecteController.php';
require_once __DIR__ . '/../Controller/PdfExport.php';

$controller = new RestaurantController();
$preferenceController = new PreferenceController();
$collecteController = new PlanningCollecteController();
$idOwner = isset($_GET['id_owner']) ? (int)$_GET['id_owner'] : 1;
if ($idOwner <= 0) {
    $idOwner = 1;
}
$status = $_GET['status'] ?? '';
$selectedId = isset($_GET['selected_id']) ? (int)$_GET['selected_id'] : 0;
$editId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$mealEdit = trim((string)($_GET['meal_edit'] ?? ''));
$restaurantSearch = trim((string)($_GET['restaurant_q'] ?? ''));
$restaurantSort = strtolower(trim((string)($_GET['restaurant_sort'] ?? 'id_desc')));
$mealSearch = trim((string)($_GET['meal_q'] ?? ''));
$mealSort = strtolower(trim((string)($_GET['meal_sort'] ?? 'name_asc')));
$collecteSearch = trim((string)($_GET['collecte_q'] ?? ''));
$collecteSort = strtolower(trim((string)($_GET['collecte_sort'] ?? 'newest')));
$allowedRestaurantSort = ['id_desc', 'id_asc', 'name_asc', 'name_desc', 'location_asc', 'location_desc'];
$allowedMealSort = ['name_asc', 'name_desc', 'qty_desc', 'qty_asc', 'price_desc', 'price_asc'];
if (!in_array($restaurantSort, $allowedRestaurantSort, true)) {
    $restaurantSort = 'id_desc';
}
if (!in_array($mealSort, $allowedMealSort, true)) {
    $mealSort = 'name_asc';
}
$allowedCollecteSort = ['newest', 'oldest', 'montant_desc', 'montant_asc', 'status_asc', 'status_desc', 'heure_desc', 'heure_asc', 'restaurant_asc', 'restaurant_desc'];
if (!in_array($collecteSort, $allowedCollecteSort, true)) {
    $collecteSort = 'newest';
}

$allRestaurants = $controller->getPartnerRestaurants($idOwner);
$selectedRestaurant = null;
$editRestaurant = null;

foreach ($allRestaurants as $r) {
    if ($selectedId > 0 && (int)$r['id_restaurant'] === $selectedId) {
        $selectedRestaurant = $r;
    }
    if ($editId > 0 && (int)$r['id_restaurant'] === $editId) {
        $editRestaurant = $r;
    }
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

if (!function_exists('partner_get_available_meal_qty')) {
    function partner_get_available_meal_qty($reservedByRestaurantMeal, $restaurantId, $mealId, $baseQty)
    {
        $restaurantId = (int)$restaurantId;
        $mealId = strtolower(trim((string)$mealId));
        $baseQty = max(0, (int)$baseQty);
        $reservedQty = (int)($reservedByRestaurantMeal[$restaurantId][$mealId] ?? 0);
        return max(0, $baseQty - $reservedQty);
    }
}

$restaurants = $allRestaurants;
if ($restaurantSearch !== '') {
    $restaurantNeedle = strtolower($restaurantSearch);
    $restaurants = array_values(array_filter($restaurants, static function ($row) use ($restaurantNeedle) {
        $haystack = strtolower(
            (string)($row['id_restaurant'] ?? '') . ' ' .
            (string)($row['nom'] ?? '') . ' ' .
            (string)($row['localisation'] ?? '') . ' ' .
            (string)($row['telephone'] ?? '') . ' ' .
            (string)($row['horaires'] ?? '')
        );

        return strpos($haystack, $restaurantNeedle) !== false;
    }));
}
usort($restaurants, static function ($a, $b) use ($restaurantSort) {
    $idA = (int)($a['id_restaurant'] ?? 0);
    $idB = (int)($b['id_restaurant'] ?? 0);
    $nameA = strtolower(trim((string)($a['nom'] ?? '')));
    $nameB = strtolower(trim((string)($b['nom'] ?? '')));
    $locA = strtolower(trim((string)($a['localisation'] ?? '')));
    $locB = strtolower(trim((string)($b['localisation'] ?? '')));

    switch ($restaurantSort) {
        case 'id_asc':
            return $idA <=> $idB;
        case 'name_asc':
            return strcmp($nameA, $nameB);
        case 'name_desc':
            return strcmp($nameB, $nameA);
        case 'location_asc':
            return strcmp($locA, $locB);
        case 'location_desc':
            return strcmp($locB, $locA);
        case 'id_desc':
        default:
            return $idB <=> $idA;
    }
});

if (isset($_GET['restaurant_export']) && $_GET['restaurant_export'] === 'pdf') {
    $pdfRows = [];
    foreach ($restaurants as $row) {
        $pdfRows[] = [
            (int)($row['id_restaurant'] ?? 0),
            (int)($row['id_owner'] ?? 0),
            (string)($row['nom'] ?? ''),
            (string)($row['localisation'] ?? ''),
            (string)($row['telephone'] ?? ''),
            (string)($row['horaires'] ?? ''),
            (int)($row['actif'] ?? 0),
            is_array($row['meals'] ?? null) ? count($row['meals']) : 0,
        ];
    }

    caremeal_stream_table_pdf(
        'partner_restaurants_' . (int)$idOwner . '_' . date('Ymd_His') . '.pdf',
        'Restaurants partenaire #' . (int)$idOwner,
        ['ID Restaurant', 'ID Owner', 'Nom', 'Localisation', 'Telephone', 'Horaires', 'Actif', 'Nombre meals'],
        $pdfRows,
        'landscape'
    );
}

$restaurantCollectes = [];
if ($selectedRestaurant) {
    $restaurantCollectes = $collecteController->getPartnerCollectesWithJoin($idOwner, (int)$selectedRestaurant['id_restaurant'], $collecteSearch, $collecteSort);
}
if (isset($_GET['collecte_export']) && $_GET['collecte_export'] === 'pdf' && $selectedRestaurant) {
    $pdfRows = [];
    foreach ($restaurantCollectes as $row) {
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
            (int)($row['id_pref'] ?? 0),
            (int)($row['id_user'] ?? 0),
            (string)($row['user_nom'] ?? ''),
            $itemsCsv,
            (string)($row['mode_collecte'] ?? ''),
            (string)($row['heure_souhaitee'] ?? ''),
            number_format((float)($row['montant_total'] ?? 0), 2, '.', ''),
            (string)($row['statut'] ?? ''),
        ];
    }

    caremeal_stream_table_pdf(
        'partner_collectes_' . (int)$selectedRestaurant['id_restaurant'] . '_' . date('Ymd_His') . '.pdf',
        'Collectes restaurant #' . (int)$selectedRestaurant['id_restaurant'],
        ['ID Collecte', 'ID Restaurant', 'Restaurant', 'ID Pref', 'ID User', 'User', 'Items', 'Mode', 'Heure', 'Montant', 'Statut'],
        $pdfRows,
        'landscape'
    );
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

$selectedRestaurantMealsBase = $selectedRestaurant ? (array)($selectedRestaurant['meals'] ?? []) : [];
$selectedRestaurantMeals = [];
foreach ($selectedRestaurantMealsBase as $meal) {
    $meal['display_quantity'] = partner_get_available_meal_qty(
        $reservedByRestaurantMeal,
        (int)($selectedRestaurant['id_restaurant'] ?? 0),
        (string)($meal['meal_id'] ?? ''),
        (int)($meal['quantity'] ?? 0)
    );
    $selectedRestaurantMeals[] = $meal;
}
$filteredSelectedRestaurantMeals = $selectedRestaurantMeals;
if ($mealSearch !== '') {
    $mealNeedle = strtolower($mealSearch);
    $filteredSelectedRestaurantMeals = array_values(array_filter($filteredSelectedRestaurantMeals, static function ($meal) use ($mealNeedle) {
        $haystack = strtolower(
            (string)($meal['meal_name'] ?? '') . ' ' .
            (string)($meal['ingredients'] ?? '') . ' ' .
            (string)($meal['allergens'] ?? '') . ' ' .
            (string)($meal['regime_tags'] ?? '')
        );

        return strpos($haystack, $mealNeedle) !== false;
    }));
}
usort($filteredSelectedRestaurantMeals, static function ($a, $b) use ($mealSort) {
    $nameA = strtolower(trim((string)($a['meal_name'] ?? '')));
    $nameB = strtolower(trim((string)($b['meal_name'] ?? '')));
    $qtyA = (int)($a['display_quantity'] ?? 0);
    $qtyB = (int)($b['display_quantity'] ?? 0);
    $priceA = (float)($a['price'] ?? 0);
    $priceB = (float)($b['price'] ?? 0);

    switch ($mealSort) {
        case 'name_desc':
            return strcmp($nameB, $nameA);
        case 'qty_desc':
            return $qtyB <=> $qtyA;
        case 'qty_asc':
            return $qtyA <=> $qtyB;
        case 'price_desc':
            return $priceB <=> $priceA;
        case 'price_asc':
            return $priceA <=> $priceB;
        case 'name_asc':
        default:
            return strcmp($nameA, $nameB);
    }
});

if (isset($_GET['meal_export']) && $_GET['meal_export'] === 'pdf' && $selectedRestaurant) {
    $pdfRows = [];
    foreach ($filteredSelectedRestaurantMeals as $meal) {
        $pdfRows[] = [
            (int)$selectedRestaurant['id_restaurant'],
            (string)$selectedRestaurant['nom'],
            (string)($meal['meal_id'] ?? ''),
            (string)($meal['meal_name'] ?? ''),
            (string)($meal['ingredients'] ?? ''),
            (int)($meal['display_quantity'] ?? 0),
            (string)($meal['pricing_mode'] ?? ''),
            number_format((float)($meal['price'] ?? 0), 2, '.', ''),
            (string)($meal['allergens'] ?? ''),
            (string)($meal['regime_tags'] ?? ''),
        ];
    }

    caremeal_stream_table_pdf(
        'partner_meals_restaurant_' . (int)$selectedRestaurant['id_restaurant'] . '_' . date('Ymd_His') . '.pdf',
        'Meals restaurant #' . (int)$selectedRestaurant['id_restaurant'],
        ['ID Restaurant', 'Restaurant', 'Meal ID', 'Meal', 'Ingredients', 'Quantite disponible', 'Mode prix', 'Prix', 'Allergenes', 'Regime tags'],
        $pdfRows,
        'landscape'
    );
}

$formAction = $editRestaurant ? 'partner_update_restaurant' : 'partner_create_restaurant';
$formTitle = $editRestaurant ? 'Edit Restaurant #' . (int)$editRestaurant['id_restaurant'] : 'Add New Restaurant';
$mealAction = $mealEditData ? 'partner_update_meal' : 'partner_add_meal';
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
    'success_collecte_updated' => ['class' => 'success', 'text' => 'Collecte status updated successfully.'],
    'error_restaurant_exists' => ['class' => 'error', 'text' => 'A restaurant with the same name already exists for this partner.'],
    'error_restaurant_image_invalid' => ['class' => 'error', 'text' => 'Invalid restaurant image (png/jpg/jpeg/webp/gif, max 3MB).'],
    'error_validation' => ['class' => 'error', 'text' => 'Validation failed. Check your input fields.'],
    'error_forbidden' => ['class' => 'error', 'text' => 'Action forbidden for this restaurant.'],
    'error_missing_fields' => ['class' => 'error', 'text' => 'Missing required fields.'],
    'error_not_found' => ['class' => 'error', 'text' => 'Requested record not found.'],
    'error_invalid_status' => ['class' => 'error', 'text' => 'Invalid status for this collecte mode.'],
    'error_forbidden_collecte' => ['class' => 'error', 'text' => 'You cannot update this collecte.'],
    'error_cannot_delete_active_collecte' => ['class' => 'error', 'text' => 'Cannot delete this active collecte with current stock state.'],
    'error_items_unavailable' => ['class' => 'error', 'text' => 'Not enough stock left to validate this collecte now.'],
    'error_items_invalid' => ['class' => 'error', 'text' => 'Invalid collecte items for this restaurant.'],
    'error_db' => ['class' => 'error', 'text' => 'Database error.'],
    'error_unknown_action' => ['class' => 'error', 'text' => 'Unknown action.'],
    'error_invalid_request' => ['class' => 'error', 'text' => 'Invalid request.'],
];

if (!function_exists('partner_collecte_status_options')) {
    function partner_collecte_status_options($mode)
    {
        $mode = strtolower(trim((string)$mode));
        if ($mode === 'delivery') {
            return ['en_attente', 'en_cours_livraison', 'livree'];
        }
        return ['en_attente', 'collecte'];
    }
}

if (!function_exists('partner_collecte_status_label')) {
    function partner_collecte_status_label($status)
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

if (!function_exists('partner_collecte_items_display')) {
    function partner_collecte_items_display($items)
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

if (!function_exists('partner_collecte_pref_values')) {
    function partner_collecte_pref_values($row)
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

$partnerRestaurantsMapPoints = [];
foreach ($restaurants as $restaurantForMap) {
    $restaurantId = (int)($restaurantForMap['id_restaurant'] ?? 0);
    $restaurantName = trim((string)($restaurantForMap['nom'] ?? 'Restaurant'));
    $restaurantLocation = trim((string)($restaurantForMap['localisation'] ?? ''));
    $rawRestaurantLat = $restaurantForMap['localisation_lat'] ?? null;
    $rawRestaurantLng = $restaurantForMap['localisation_lng'] ?? null;
    $restaurantLat = is_numeric($rawRestaurantLat) ? (float)$rawRestaurantLat : null;
    $restaurantLng = is_numeric($rawRestaurantLng) ? (float)$rawRestaurantLng : null;

    if ($restaurantLocation === '' && (!is_numeric($restaurantLat) || !is_numeric($restaurantLng))) {
        continue;
    }

    $partnerRestaurantsMapPoints[] = [
        'restaurant_id' => $restaurantId,
        'label' => 'Restaurant #' . $restaurantId . ' - ' . ($restaurantName !== '' ? $restaurantName : 'Restaurant'),
        'location' => $restaurantLocation,
        'status' => '',
        'mode' => '',
        'kind' => 'restaurant',
        'lat' => $restaurantLat,
        'lng' => $restaurantLng,
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="CRUD Restaurants partenaire CareMeal.">
  <title>Partner Restaurants - CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="../assets/vendor/leaflet/leaflet.css">
  <style>
    html { scroll-behavior: smooth; }
    .page-stack { display: grid; grid-template-columns: 1fr; gap: 18px; }
    .alert-box { border-radius: var(--radius-md); padding: 12px 14px; border: 1px solid transparent; font-size: .9rem; }
    .alert-box.success { color: #0f5132; background: rgba(25, 135, 84, .18); border-color: rgba(25, 135, 84, .4); }
    .alert-box.error { color: #842029; background: rgba(220, 53, 69, .16); border-color: rgba(220, 53, 69, .4); }
    .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
    .form-grid .full { grid-column: 1 / -1; }
    .input, .textarea, .select { width: 100%; background: rgba(255,255,255,.04); border: 1px solid var(--color-dark-border); border-radius: var(--radius-md); color: var(--color-white); padding: 10px 12px; }
    .textarea { min-height: 90px; resize: vertical; }
    .field-error { color: #ff9da7; font-size: .8rem; min-height: 16px; display: none; margin-top: 5px; }
    .input-error { border-color: rgba(220,53,69,.7) !important; box-shadow: 0 0 0 1px rgba(220,53,69,.25); }
    .restaurants-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px; }
    .restaurant-card { border: 1px solid var(--color-dark-border); border-radius: var(--radius-lg); background: rgba(255,255,255,.03); overflow: hidden; cursor: pointer; }
    .restaurant-card.selected { border-color: var(--color-primary); box-shadow: 0 0 0 1px rgba(249,115,22,.25); }
    .restaurant-img { width: 100%; height: 160px; object-fit: cover; display: block; background: #0d1b2a; }
    .restaurant-body { padding: 12px; }
    .restaurant-name { margin: 0 0 6px; font-size: 1rem; color: var(--color-white); }
    .restaurant-meta { margin: 0; color: var(--color-text-muted); font-size: .82rem; }
    .restaurant-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
    .table-wrap { overflow: auto; }
    .meal-table { width: 100%; border-collapse: collapse; min-width: 900px; }
    .meal-table th, .meal-table td { border-bottom: 1px solid var(--color-dark-border); padding: 10px; text-align: left; vertical-align: top; }
    .meal-table th { color: var(--color-text-muted); font-size: .85rem; }
    .thumb { width: 90px; height: 64px; border-radius: 8px; object-fit: cover; border: 1px solid var(--color-dark-border); }
    .tags-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; margin-bottom: 6px; }
    .tag { display: inline-flex; align-items: center; gap: 7px; border: 1px solid var(--color-dark-border); border-radius: 999px; padding: 8px 11px; background: rgba(255,255,255,.03); font-size: .86rem; cursor: pointer; }
    .tag input[type="checkbox"] { display: none; }
    .tag.selected { border-color: var(--color-primary); color: var(--color-primary); background: rgba(249,115,22,.12); }
    .tag-emoji img { width: 18px; height: 18px; border-radius: 4px; object-fit: cover; display: inline-block; }
    .location-picker-tools { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-top: 8px; }
    .location-map { width: 100%; height: 260px; border: 1px solid var(--color-dark-border); border-radius: var(--radius-md); margin-top: 10px; overflow: hidden; }
    .location-coords { color: var(--color-text-muted); font-size: .82rem; margin-top: 8px; }
    .hidden { display: none !important; }
    @media (max-width: 900px) { .form-grid { grid-template-columns: 1fr; } }
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
          <a href="stats.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-chart-column"></i></span> Statistiques</a>
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
          <div class="page-title">
            <h2>Restaurants</h2>
            <p>ajout resto et ajout aliments/meals</p>
          </div>
        </div>
        <div class="header-right">
          <button class="header-notification"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button>
          <div class="avatar avatar-sm" id="header-avatar">P</div>
        </div>
      </header>

      <div class="page-content">
        <div class="page-stack">
          <?php if (isset($statusMessages[$status])): ?>
            <div class="alert-box <?= htmlspecialchars($statusMessages[$status]['class'], ENT_QUOTES, 'UTF-8') ?>">
              <?= htmlspecialchars($statusMessages[$status]['text'], ENT_QUOTES, 'UTF-8') ?>
            </div>
          <?php endif; ?>

          <section class="card animate-fade-in-up" id="restaurant-form-card">
            <div class="card-header" style="justify-content:space-between;align-items:center;">
              <h3 class="card-title"><i class="fa-solid fa-pen-to-square"></i> <?= htmlspecialchars($formTitle, ENT_QUOTES, 'UTF-8') ?></h3>
              <?php if ($editRestaurant): ?>
                <a class="btn btn-outline btn-sm" href="restaurants.php?id_owner=<?= (int)$idOwner ?>#restaurant-form-card">New Restaurant</a>
              <?php endif; ?>
            </div>

            <form method="post" enctype="multipart/form-data" action="../Controller/restaurant.php?action=<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>" id="partner-restaurant-form" novalidate data-require-image="<?= $editRestaurant ? '0' : '1' ?>">
              <input type="hidden" name="id_owner" class="js-owner-id" value="<?= (int)$idOwner ?>">
              <?php if ($editRestaurant): ?>
                <input type="hidden" name="id_restaurant" value="<?= (int)$editRestaurant['id_restaurant'] ?>">
              <?php endif; ?>

              <div class="form-grid">
                <div>
                  <label for="restaurant_name">Nom restaurant</label>
                  <input class="input" id="restaurant_name" name="nom" type="text" value="<?= htmlspecialchars((string)($editRestaurant['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                  <div id="restaurant_name-error" class="field-error"></div>
                </div>
                <div class="full">
                  <label for="restaurant_location">Localisation</label>
                  <input class="input" id="restaurant_location" name="localisation" type="text" value="<?= htmlspecialchars((string)($editRestaurant['localisation'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" id="restaurant_location_lat" name="localisation_lat" value="<?= htmlspecialchars((string)($editRestaurant['localisation_lat'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" id="restaurant_location_lng" name="localisation_lng" value="<?= htmlspecialchars((string)($editRestaurant['localisation_lng'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                  <div class="location-picker-tools">
                    <input class="input" id="partner_restaurant_location_search" type="text" placeholder="Rechercher une adresse sur la carte" style="max-width:320px;">
                    <button class="btn btn-outline btn-sm" type="button" id="partner_restaurant_location_search_btn"><i class="fa-solid fa-magnifying-glass"></i> Rechercher</button>
                    <button class="btn btn-outline btn-sm" type="button" id="partner_restaurant_current_location_btn"><i class="fa-solid fa-location-crosshairs"></i> Ma position actuelle</button>
                  </div>
                  <div id="partner_restaurant_location_map" class="location-map"></div>
                  <div id="partner_restaurant_location_coords" class="location-coords">Coordonnees: non selectionnees</div>
                  <div id="restaurant_location-error" class="field-error"></div>
                </div>
                <div>
                  <label for="restaurant_phone">Telephone</label>
                  <input class="input" id="restaurant_phone" name="telephone" type="text" placeholder="+216 99 123 456" value="<?= htmlspecialchars((string)($editRestaurant['telephone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                  <div id="restaurant_phone-error" class="field-error"></div>
                </div>
                <div>
                  <label for="restaurant_open_time">Ouverture</label>
                  <input class="input" id="restaurant_open_time" name="open_time" type="time" min="09:00" max="22:00" step="60" value="<?= htmlspecialchars((string)$openTimeValue, ENT_QUOTES, 'UTF-8') ?>">
                  <div id="restaurant_open_time-error" class="field-error"></div>
                </div>
                <div>
                  <label for="restaurant_close_time">Fermeture</label>
                  <input class="input" id="restaurant_close_time" name="close_time" type="time" min="09:00" max="22:00" step="60" value="<?= htmlspecialchars((string)$closeTimeValue, ENT_QUOTES, 'UTF-8') ?>">
                  <div id="restaurant_close_time-error" class="field-error"></div>
                </div>
                <div class="full">
                  <label for="restaurant_description">Description</label>
                  <textarea class="textarea" id="restaurant_description" name="description"><?= htmlspecialchars((string)($editRestaurant['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                  <div id="restaurant_description-error" class="field-error"></div>
                </div>
                <div class="full">
                  <label for="restaurant_image">Image enseigne</label>
                  <input class="input" id="restaurant_image" name="image_file" type="file">
                  <div id="restaurant_image-error" class="field-error"></div>
                  <?php if ($editRestaurant && !empty($editRestaurant['image_path'])): ?>
                    <img src="../<?= htmlspecialchars((string)$editRestaurant['image_path'], ENT_QUOTES, 'UTF-8') ?>" class="thumb" alt="enseigne actuelle">
                  <?php endif; ?>
                </div>
              </div>

              <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
                <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-floppy-disk"></i> <?= $editRestaurant ? 'Update Restaurant' : 'Add Restaurant' ?></button>
              </div>
            </form>
          </section>

          <section class="card animate-fade-in-up stagger-1" id="partner-restaurants-section">
            <div class="card-header" style="justify-content:space-between;align-items:center;">
              <h3 class="card-title"><i class="fa-solid fa-shop"></i> My Restaurants</h3>
              <span class="badge badge-info"><?= count($restaurants) ?> filtered / <?= count($allRestaurants) ?> items</span>
            </div>
            <div id="partner-restaurants-map" class="collectes-map"></div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:10px;">
              <form method="get" action="restaurants.php" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                <input type="hidden" name="id_owner" value="<?= (int)$idOwner ?>">
                <?php if ($selectedRestaurant): ?>
                  <input type="hidden" name="selected_id" value="<?= (int)$selectedRestaurant['id_restaurant'] ?>">
                <?php endif; ?>
                <input class="input" style="width:260px;" type="text" name="restaurant_q" value="<?= htmlspecialchars($restaurantSearch, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search name, location, phone...">
                <select class="select" name="restaurant_sort" style="min-width:220px;">
                  <option value="id_desc"<?= $restaurantSort === 'id_desc' ? ' selected' : '' ?>>Sort: newest IDs</option>
                  <option value="id_asc"<?= $restaurantSort === 'id_asc' ? ' selected' : '' ?>>Sort: oldest IDs</option>
                  <option value="name_asc"<?= $restaurantSort === 'name_asc' ? ' selected' : '' ?>>Name A-Z</option>
                  <option value="name_desc"<?= $restaurantSort === 'name_desc' ? ' selected' : '' ?>>Name Z-A</option>
                  <option value="location_asc"<?= $restaurantSort === 'location_asc' ? ' selected' : '' ?>>Location A-Z</option>
                  <option value="location_desc"<?= $restaurantSort === 'location_desc' ? ' selected' : '' ?>>Location Z-A</option>
                </select>
                <button class="btn btn-outline btn-sm" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
              </form>
              <a class="btn btn-outline btn-sm" href="restaurants.php?id_owner=<?= (int)$idOwner ?>#partner-restaurants-section">Reset</a>
              <a class="btn btn-outline btn-sm" href="restaurants.php?<?= htmlspecialchars(http_build_query(['id_owner' => (int)$idOwner, 'restaurant_q' => $restaurantSearch, 'restaurant_sort' => $restaurantSort, 'restaurant_export' => 'pdf']), ENT_QUOTES, 'UTF-8') ?>">Export PDF</a>
            </div>
            <?php if (empty($allRestaurants)): ?>
              <p style="color:var(--color-text-muted);">No restaurants yet.</p>
            <?php elseif (empty($restaurants)): ?>
              <p style="color:var(--color-text-muted);">No restaurants match the current filters.</p>
            <?php else: ?>
              <div class="restaurants-grid">
                <?php foreach ($restaurants as $restaurant): ?>
                  <?php $isSelected = $selectedRestaurant && (int)$selectedRestaurant['id_restaurant'] === (int)$restaurant['id_restaurant']; ?>
                  <?php
                    $cardSelectQuery = [
                        'id_owner' => (int)$idOwner,
                        'restaurant_q' => $restaurantSearch,
                        'restaurant_sort' => $restaurantSort,
                        'meal_q' => $mealSearch,
                        'meal_sort' => $mealSort,
                        'selected_id' => (int)$restaurant['id_restaurant'],
                    ];
                    $cardEditQuery = $cardSelectQuery;
                    $cardEditQuery['edit_id'] = (int)$restaurant['id_restaurant'];
                  ?>
                  <article class="restaurant-card<?= $isSelected ? ' selected' : '' ?> js-restaurant-card" data-href="restaurants.php?<?= htmlspecialchars(http_build_query($cardSelectQuery), ENT_QUOTES, 'UTF-8') ?>#partner-restaurants-section">
                    <img class="restaurant-img" src="../<?= htmlspecialchars((string)$restaurant['image_path'], ENT_QUOTES, 'UTF-8') ?>" alt="enseigne">
                    <div class="restaurant-body">
                      <h4 class="restaurant-name"><?= htmlspecialchars((string)$restaurant['nom'], ENT_QUOTES, 'UTF-8') ?></h4>
                      <p class="restaurant-meta"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars((string)$restaurant['localisation'], ENT_QUOTES, 'UTF-8') ?></p>
                      <div class="restaurant-actions">
                        <a class="btn btn-outline btn-sm" href="restaurants.php?<?= htmlspecialchars(http_build_query($cardSelectQuery), ENT_QUOTES, 'UTF-8') ?>#partner-restaurants-section">Select</a>
                        <a class="btn btn-outline btn-sm" href="restaurants.php?<?= htmlspecialchars(http_build_query($cardEditQuery), ENT_QUOTES, 'UTF-8') ?>#restaurant-form-card">Edit</a>
                        <form method="post" action="../Controller/restaurant.php?action=partner_delete_restaurant" onsubmit="return confirm('Delete this restaurant?');">
                          <input type="hidden" name="id_owner" class="js-owner-id" value="<?= (int)$idOwner ?>">
                          <input type="hidden" name="id_restaurant" value="<?= (int)$restaurant['id_restaurant'] ?>">
                          <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                        </form>
                      </div>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </section>

          <?php if ($selectedRestaurant): ?>
            <section class="card animate-fade-in-up stagger-2" id="restaurant-meals-section">
              <div class="card-header" style="justify-content:space-between;align-items:center;">
                <h3 class="card-title"><i class="fa-solid fa-utensils"></i> <?= htmlspecialchars($mealTitle, ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars((string)$selectedRestaurant['nom'], ENT_QUOTES, 'UTF-8') ?></h3>
                <?php if ($mealEditData): ?>
                  <a class="btn btn-outline btn-sm" href="restaurants.php?id_owner=<?= (int)$idOwner ?>&selected_id=<?= (int)$selectedRestaurant['id_restaurant'] ?>#restaurant-meals-section">Cancel Meal Edit</a>
                <?php endif; ?>
              </div>

              <form method="post" action="../Controller/restaurant.php?action=<?= htmlspecialchars($mealAction, ENT_QUOTES, 'UTF-8') ?>" id="partner-meal-form" novalidate>
                <input type="hidden" name="id_owner" class="js-owner-id" value="<?= (int)$idOwner ?>">
                <input type="hidden" name="id_restaurant" value="<?= (int)$selectedRestaurant['id_restaurant'] ?>">
                <?php if ($mealEditData): ?>
                  <input type="hidden" name="meal_id" value="<?= htmlspecialchars((string)$mealEditData['meal_id'], ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>

                <div class="form-grid">
                  <div>
                    <label for="meal_name">Meal name</label>
                    <input class="input" id="meal_name" name="meal_name" type="text" value="<?= htmlspecialchars((string)($mealEditData['meal_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <div id="meal_name-error" class="field-error"></div>
                  </div>
                  <div>
                    <label for="meal_quantity">Quantite</label>
                    <input class="input" id="meal_quantity" name="quantity" type="number" step="1" placeholder="ex: 10" value="<?= htmlspecialchars((string)($mealEditData['quantity'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <div id="meal_quantity-error" class="field-error"></div>
                  </div>
                  <div class="full">
                    <label for="meal_ingredients">Description / Ingredients</label>
                    <textarea class="textarea" id="meal_ingredients" name="ingredients"><?= htmlspecialchars((string)($mealEditData['ingredients'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                    <div id="meal_ingredients-error" class="field-error"></div>
                  </div>
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
                  <div>
                    <label for="meal_allergens">Allergenes declares</label>
                    <input class="input" id="meal_allergens" name="allergens" type="text" placeholder="arachide, gluten ou aucun" value="<?= htmlspecialchars((string)($mealEditData['allergens'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <div id="meal_allergens-error" class="field-error"></div>
                  </div>
                  <div>
                    <label for="meal_pricing_mode">Free or paid</label>
                    <select class="select" id="meal_pricing_mode" name="pricing_mode">
                      <option value="free"<?= (($mealEditData['pricing_mode'] ?? '') === 'free') ? ' selected' : '' ?>>Free</option>
                      <option value="paid"<?= (($mealEditData['pricing_mode'] ?? '') === 'paid') ? ' selected' : '' ?>>Paid</option>
                    </select>
                    <div id="meal_pricing_mode-error" class="field-error"></div>
                  </div>
                  <div id="meal_price_group">
                    <label for="meal_price">Price (if paid)</label>
                    <input class="input" id="meal_price" name="price" type="number" step="0.01" placeholder="ex: 7.50" value="<?= htmlspecialchars((string)($mealEditData['price'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>">
                    <div id="meal_price-error" class="field-error"></div>
                  </div>
                </div>
                <div style="margin-top:12px;">
                  <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-floppy-disk"></i> <?= $mealEditData ? 'Update Meal' : 'Add Meal' ?></button>
                </div>
              </form>

              <hr style="margin:16px 0;border:none;border-top:1px solid var(--color-dark-border);">
              <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px;">
                <h4 style="margin:0;">Meals list</h4>
                <span class="badge badge-info"><?= count($filteredSelectedRestaurantMeals) ?> filtered / <?= count($selectedRestaurantMeals) ?> meals</span>
              </div>
              <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px;">
                <form method="get" action="restaurants.php" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                  <input type="hidden" name="id_owner" value="<?= (int)$idOwner ?>">
                  <input type="hidden" name="selected_id" value="<?= (int)$selectedRestaurant['id_restaurant'] ?>">
                  <input type="hidden" name="restaurant_q" value="<?= htmlspecialchars($restaurantSearch, ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" name="restaurant_sort" value="<?= htmlspecialchars($restaurantSort, ENT_QUOTES, 'UTF-8') ?>">
                  <input class="input" style="width:260px;" type="text" name="meal_q" value="<?= htmlspecialchars($mealSearch, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search meal, ingredients, allergens...">
                  <select class="select" name="meal_sort" style="min-width:210px;">
                    <option value="name_asc"<?= $mealSort === 'name_asc' ? ' selected' : '' ?>>Meal A-Z</option>
                    <option value="name_desc"<?= $mealSort === 'name_desc' ? ' selected' : '' ?>>Meal Z-A</option>
                    <option value="qty_desc"<?= $mealSort === 'qty_desc' ? ' selected' : '' ?>>Quantity desc</option>
                    <option value="qty_asc"<?= $mealSort === 'qty_asc' ? ' selected' : '' ?>>Quantity asc</option>
                    <option value="price_desc"<?= $mealSort === 'price_desc' ? ' selected' : '' ?>>Price desc</option>
                    <option value="price_asc"<?= $mealSort === 'price_asc' ? ' selected' : '' ?>>Price asc</option>
                  </select>
                  <button class="btn btn-outline btn-sm" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
                </form>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                  <a class="btn btn-outline btn-sm" href="restaurants.php?<?= htmlspecialchars(http_build_query(['id_owner' => (int)$idOwner, 'selected_id' => (int)$selectedRestaurant['id_restaurant'], 'restaurant_q' => $restaurantSearch, 'restaurant_sort' => $restaurantSort, 'meal_q' => $mealSearch, 'meal_sort' => $mealSort, 'meal_export' => 'pdf']), ENT_QUOTES, 'UTF-8') ?>">Export meals PDF</a>
                  <a class="btn btn-outline btn-sm" href="restaurants.php?<?= htmlspecialchars(http_build_query(['id_owner' => (int)$idOwner, 'selected_id' => (int)$selectedRestaurant['id_restaurant'], 'restaurant_q' => $restaurantSearch, 'restaurant_sort' => $restaurantSort]), ENT_QUOTES, 'UTF-8') ?>#restaurant-meals-section">Reset</a>
                </div>
              </div>
              <div class="table-wrap">
                <table class="meal-table">
                  <thead>
                    <tr>
                      <th>Meal</th><th>Ingredients</th><th>Qty dispo</th><th>Regimes</th><th>Allergenes</th><th>Price</th><th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($selectedRestaurantMeals)): ?>
                      <tr><td colspan="7" style="color:var(--color-text-muted);">No meals yet for this restaurant.</td></tr>
                    <?php elseif (empty($filteredSelectedRestaurantMeals)): ?>
                      <tr><td colspan="7" style="color:var(--color-text-muted);">No meals match the current filters.</td></tr>
                    <?php else: ?>
                      <?php foreach ($filteredSelectedRestaurantMeals as $meal): ?>
                        <tr>
                          <td><?= htmlspecialchars((string)($meal['meal_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                          <td><?= htmlspecialchars((string)($meal['ingredients'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                          <td><?= (int)($meal['display_quantity'] ?? 0) ?></td>
                          <td><?= htmlspecialchars((string)($meal['regime_tags'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                          <td><?= htmlspecialchars((string)($meal['allergens'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                          <td><?= (($meal['pricing_mode'] ?? 'free') === 'free') ? 'Free' : number_format((float)($meal['price'] ?? 0), 2) . ' DT' ?></td>
                          <td>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                              <a class="btn btn-outline btn-sm" href="restaurants.php?id_owner=<?= (int)$idOwner ?>&selected_id=<?= (int)$selectedRestaurant['id_restaurant'] ?>&meal_edit=<?= urlencode((string)$meal['meal_id']) ?>#restaurant-meals-section">Edit</a>
                              <form method="post" action="../Controller/restaurant.php?action=partner_delete_meal" onsubmit="return confirm('Delete this meal?');">
                                <input type="hidden" name="id_owner" class="js-owner-id" value="<?= (int)$idOwner ?>">
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
            </section>

          <section class="card animate-fade-in-up stagger-2" id="restaurant-collectes-section">
              <div class="card-header" style="justify-content:space-between;align-items:center;">
                <h3 class="card-title"><i class="fa-solid fa-truck"></i> Collectes liees a <?= htmlspecialchars((string)$selectedRestaurant['nom'], ENT_QUOTES, 'UTF-8') ?></h3>
                <span class="badge badge-info"><?= count($restaurantCollectes) ?> items</span>
              </div>
              <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px;">
                <form method="get" action="restaurants.php" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                  <input type="hidden" name="id_owner" value="<?= (int)$idOwner ?>">
                  <input type="hidden" name="selected_id" value="<?= (int)$selectedRestaurant['id_restaurant'] ?>">
                  <input class="input" style="width:280px;" type="text" name="collecte_q" value="<?= htmlspecialchars($collecteSearch, ENT_QUOTES, 'UTF-8') ?>" placeholder="Chercher collecte, user...">
                  <select class="select" name="collecte_sort" style="min-width:220px;">
                    <option value="newest"<?= $collecteSort === 'newest' ? ' selected' : '' ?>>Tri: plus recentes</option>
                    <option value="oldest"<?= $collecteSort === 'oldest' ? ' selected' : '' ?>>Tri: plus anciennes</option>
                    <option value="montant_desc"<?= $collecteSort === 'montant_desc' ? ' selected' : '' ?>>Montant decroissant</option>
                    <option value="montant_asc"<?= $collecteSort === 'montant_asc' ? ' selected' : '' ?>>Montant croissant</option>
                    <option value="status_asc"<?= $collecteSort === 'status_asc' ? ' selected' : '' ?>>Statut A-Z</option>
                    <option value="heure_desc"<?= $collecteSort === 'heure_desc' ? ' selected' : '' ?>>Heure plus tard</option>
                    <option value="heure_asc"<?= $collecteSort === 'heure_asc' ? ' selected' : '' ?>>Heure plus tot</option>
                  </select>
                  <button class="btn btn-outline btn-sm" type="submit">Search</button>
                </form>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                  <a class="btn btn-outline btn-sm" href="restaurants.php?<?= htmlspecialchars(http_build_query(['id_owner' => $idOwner, 'selected_id' => (int)$selectedRestaurant['id_restaurant'], 'collecte_q' => $collecteSearch, 'collecte_sort' => $collecteSort, 'collecte_export' => 'pdf']), ENT_QUOTES, 'UTF-8') ?>">Export PDF</a>
                  <a class="btn btn-outline btn-sm" href="restaurants.php?id_owner=<?= (int)$idOwner ?>&selected_id=<?= (int)$selectedRestaurant['id_restaurant'] ?>#restaurant-collectes-section">Reset</a>
                </div>
              </div>
              <div class="table-wrap">
                <table class="meal-table">
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
                    <?php if (empty($restaurantCollectes)): ?>
                      <tr><td colspan="10" style="color:var(--color-text-muted);">Aucune collecte pour ce restaurant.</td></tr>
                    <?php else: ?>
                      <?php foreach ($restaurantCollectes as $collecte): ?>
                        <?php
                          $prefValues = partner_collecte_pref_values($collecte);
                          $mode = strtolower((string)($collecte['mode_collecte'] ?? 'pickup'));
                          $statusOptions = partner_collecte_status_options($mode);
                          $currentStatus = trim((string)($collecte['statut'] ?? 'en_attente'));
                          if (!in_array($currentStatus, $statusOptions, true) && $currentStatus !== '') {
                              $statusOptions[] = $currentStatus;
                          }
                        ?>
                        <tr>
                          <td>#<?= (int)$collecte['id_collecte'] ?></td>
                          <td>
                            #<?= (int)$collecte['id_restaurant'] ?> - <?= htmlspecialchars((string)($collecte['restaurant_nom'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?>
                          </td>
                          <td>
                            #<?= (int)$collecte['id_pref'] ?><br>
                            <span style="color:var(--color-text-muted);">regime alimentaire : <?= htmlspecialchars((string)$prefValues['regime'], ENT_QUOTES, 'UTF-8') ?></span><br>
                            <span style="color:var(--color-text-muted);">adresse : <?= htmlspecialchars((string)$prefValues['adresse'], ENT_QUOTES, 'UTF-8') ?></span><br>
                            <span style="color:var(--color-text-muted);">allergies : <?= htmlspecialchars((string)$prefValues['allergies'], ENT_QUOTES, 'UTF-8') ?></span>
                          </td>
                          <td>
                            #<?= (int)$collecte['id_user'] ?> - <?= htmlspecialchars((string)($collecte['user_nom'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?>
                          </td>
                          <td><?= htmlspecialchars(partner_collecte_items_display($collecte['items'] ?? []), ENT_QUOTES, 'UTF-8') ?></td>
                          <td><?= htmlspecialchars(partner_collecte_status_label($mode), ENT_QUOTES, 'UTF-8') ?></td>
                          <td><?= htmlspecialchars((string)$collecte['heure_souhaitee'], ENT_QUOTES, 'UTF-8') ?></td>
                          <td><?= number_format((float)$collecte['montant_total'], 2) ?> DT</td>
                          <td><?= htmlspecialchars(partner_collecte_status_label($currentStatus), ENT_QUOTES, 'UTF-8') ?></td>
                          <td>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                              <a class="btn btn-outline btn-sm" href="collecte_detail.php?id_collecte=<?= (int)$collecte['id_collecte'] ?>&id_owner=<?= (int)$idOwner ?>#collecte-detail-card">Voir detail</a>
                              <form method="post" action="../Controller/planning_collecte.php?action=partner_update_collecte_status" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                                <input type="hidden" name="id_owner" class="js-owner-id" value="<?= (int)$idOwner ?>">
                                <input type="hidden" name="id_collecte" value="<?= (int)$collecte['id_collecte'] ?>">
                                <select class="select" name="statut" style="min-width:170px;">
                                  <?php foreach ($statusOptions as $statusOption): ?>
                                    <option value="<?= htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8') ?>"<?= ($currentStatus === $statusOption) ? ' selected' : '' ?>>
                                      <?= htmlspecialchars(partner_collecte_status_label($statusOption), ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                  <?php endforeach; ?>
                                </select>
                                <button class="btn btn-outline btn-sm" type="submit">Maj statut</button>
                              </form>
                              <form method="post" action="../Controller/planning_collecte.php?action=partner_delete_collecte" onsubmit="return confirmPartnerCollecteDelete(this);">
                                <input type="hidden" name="id_owner" class="js-owner-id" value="<?= (int)$idOwner ?>">
                                <input type="hidden" name="id_collecte" value="<?= (int)$collecte['id_collecte'] ?>">
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
          <?php endif; ?>
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
    .collectes-map {
      width: 100%;
      height: 360px;
      border-radius: var(--radius-md);
      border: 1px solid var(--color-dark-border);
      overflow: hidden;
      margin-bottom: 12px;
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

  <script src="../js/app.js"></script>
  <script src="../js/components.js"></script>
  <script src="../assets/vendor/leaflet/leaflet.js"></script>
  <script src="../js/collecte-address-picker.js"></script>
  <script src="../js/collectes-map.js"></script>
  <script src="../js/partner-restaurants-validation.js"></script>
  <script>
    window.PARTNER_RESTAURANTS_MAP_POINTS = <?= json_encode($partnerRestaurantsMapPoints, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  </script>
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

    function confirmPartnerCollecteDelete(form) {
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

      if (!App.requireAuth(['partner'])) return;
      const user = App.getCurrentUser();
      const match = String((user && user.id) ? user.id : '').match(/(\d+)$/);
      if (match) {
        const resolvedId = parseInt(match[1], 10);
        const url = new URL(window.location.href);
        const queryId = parseInt(url.searchParams.get('id_owner') || '0', 10);
        if (!queryId || queryId !== resolvedId) {
          url.searchParams.set('id_owner', String(resolvedId));
          url.searchParams.delete('edit_id');
          url.searchParams.delete('selected_id');
          url.searchParams.delete('meal_edit');
          url.hash = '';
          window.location.replace(url.toString());
          return;
        }
        document.querySelectorAll('.js-owner-id').forEach((el) => { el.value = String(resolvedId); });
      }

      document.querySelectorAll('.js-restaurant-card').forEach((card) => {
        card.addEventListener('click', (event) => {
          if (event.target.closest('a') || event.target.closest('button') || event.target.closest('form')) return;
          const href = card.getAttribute('data-href');
          if (href) window.location.href = href;
        });
      });

      if (typeof window.initCollecteAddressPicker === 'function') {
        window.initCollecteAddressPicker({
          mapId: 'partner_restaurant_location_map',
          addressInputId: 'restaurant_location',
          latInputId: 'restaurant_location_lat',
          lngInputId: 'restaurant_location_lng',
          searchInputId: 'partner_restaurant_location_search',
          searchBtnId: 'partner_restaurant_location_search_btn',
          currentLocationBtnId: 'partner_restaurant_current_location_btn',
          coordsLabelId: 'partner_restaurant_location_coords',
          defaultCenter: [36.8065, 10.1815],
          defaultZoom: 12
        });
      }

      if (typeof window.initCollectesMap === 'function') {
        window.initCollectesMap({
          containerId: 'partner-restaurants-map',
          points: window.PARTNER_RESTAURANTS_MAP_POINTS || []
        });
      }
    });
  </script>
</body>
</html>
