<?php
require_once __DIR__ . '/../Controller/RestaurantController.php';
require_once __DIR__ . '/../Controller/PreferenceController.php';

$controller = new RestaurantController();
$preferenceController = new PreferenceController();
$status = $_GET['status'] ?? '';
$search = trim((string)($_GET['q'] ?? ''));
$selectedId = isset($_GET['selected_id']) ? (int)$_GET['selected_id'] : 0;
$editId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$mealEdit = trim((string)($_GET['meal_edit'] ?? ''));
$ownerFilter = isset($_GET['owner_filter']) ? (int)$_GET['owner_filter'] : 0;

$restaurants = $controller->getAllRestaurantsForAdmin($search);
if ($ownerFilter > 0) {
    $restaurants = array_values(array_filter($restaurants, static function ($r) use ($ownerFilter) {
        return (int)$r['id_owner'] === $ownerFilter;
    }));
}

$selectedRestaurant = null;
$editRestaurant = null;
foreach ($restaurants as $r) {
    if ($selectedId > 0 && (int)$r['id_restaurant'] === $selectedId) { $selectedRestaurant = $r; }
    if ($editId > 0 && (int)$r['id_restaurant'] === $editId) { $editRestaurant = $r; }
}
if (!$selectedRestaurant && $editRestaurant) {
    $selectedRestaurant = $editRestaurant;
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
    .tags-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; margin-bottom: 6px; }
    .tag { display: inline-flex; align-items: center; gap: 7px; border: 1px solid var(--color-dark-border); border-radius: 999px; padding: 8px 11px; background: rgba(255,255,255,.03); font-size: .86rem; cursor: pointer; }
    .tag input[type="checkbox"] { display: none; }
    .tag.selected { border-color: var(--color-primary); color: var(--color-primary); background: rgba(249,115,22,.12); }
    .tag-emoji img { width: 18px; height: 18px; border-radius: 4px; object-fit: cover; display: inline-block; }
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

          <section class="card animate-fade-in-up">
            <div class="toolbar">
              <form method="get" action="restaurants.php" class="toolbar">
                <input class="input" style="width:320px;" type="text" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search by restaurant name or partner id">
                <button class="btn btn-outline btn-sm" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
              </form>
              <a class="btn btn-outline btn-sm" href="restaurants.php">Reset</a>
            </div>
          </section>

          <section class="card animate-fade-in-up stagger-1" id="admin-restaurant-form-card">
            <div class="card-header" style="justify-content:space-between;align-items:center;">
              <h3 class="card-title"><i class="fa-solid fa-pen-to-square"></i> <?= htmlspecialchars($formTitle, ENT_QUOTES, 'UTF-8') ?></h3>
              <?php if ($editRestaurant): ?>
                <a class="btn btn-outline btn-sm" href="restaurants.php#admin-restaurant-form-card">New Restaurant</a>
              <?php endif; ?>
            </div>

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
                <div>
                  <label for="restaurant_location">Location</label>
                  <input class="input" id="restaurant_location" name="localisation" type="text" value="<?= htmlspecialchars((string)($editRestaurant['localisation'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                  <div id="restaurant_location-error" class="field-error"></div>
                </div>
                <div>
                  <label for="restaurant_phone">Telephone</label>
                  <input class="input" id="restaurant_phone" name="telephone" type="text" value="<?= htmlspecialchars((string)($editRestaurant['telephone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                  <div id="restaurant_phone-error" class="field-error"></div>
                </div>
                <div>
                  <label for="restaurant_open_time">Opening (09:00-22:00)</label>
                  <input class="input" id="restaurant_open_time" name="open_time" type="time" min="09:00" max="22:00" value="<?= htmlspecialchars((string)$openTimeValue, ENT_QUOTES, 'UTF-8') ?>">
                  <div id="restaurant_open_time-error" class="field-error"></div>
                </div>
                <div>
                  <label for="restaurant_close_time">Closing (09:00-22:00)</label>
                  <input class="input" id="restaurant_close_time" name="close_time" type="time" min="09:00" max="22:00" value="<?= htmlspecialchars((string)$closeTimeValue, ENT_QUOTES, 'UTF-8') ?>">
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
                  <input class="input" id="restaurant_image" name="image_file" type="file" accept="image/*">
                  <div id="restaurant_image-error" class="field-error"></div>
                </div>
              </div>
              <button class="btn btn-primary btn-sm" type="submit" style="margin-top:12px;"><i class="fa-solid fa-floppy-disk"></i> <?= $editRestaurant ? 'Update Restaurant' : 'Create Restaurant' ?></button>
            </form>
          </section>

          <section class="card animate-fade-in-up stagger-2" id="admin-restaurants-section">
            <div class="card-header" style="justify-content:space-between;align-items:center;">
              <h3 class="card-title"><i class="fa-solid fa-shop"></i> All Restaurants</h3>
              <span class="badge badge-info"><?= count($restaurants) ?> rows</span>
            </div>
            <?php if (empty($restaurants)): ?>
              <p style="color:var(--color-text-muted);">No restaurants found.</p>
            <?php else: ?>
              <div class="cards">
                <?php foreach ($restaurants as $restaurant): ?>
                  <?php $isSel = $selectedRestaurant && (int)$selectedRestaurant['id_restaurant'] === (int)$restaurant['id_restaurant']; ?>
                  <article class="card-item<?= $isSel ? ' selected' : '' ?> js-item" data-href="restaurants.php?selected_id=<?= (int)$restaurant['id_restaurant'] ?>#admin-restaurants-section">
                    <img src="../<?= htmlspecialchars((string)$restaurant['image_path'], ENT_QUOTES, 'UTF-8') ?>" alt="restaurant">
                    <div class="body">
                      <h4 style="margin:0;"><?= htmlspecialchars((string)$restaurant['nom'], ENT_QUOTES, 'UTF-8') ?></h4>
                      <p class="meta">Owner #<?= (int)$restaurant['id_owner'] ?> - <?= htmlspecialchars((string)($restaurant['owner_nom'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') ?></p>
                      <p class="meta"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars((string)$restaurant['localisation'], ENT_QUOTES, 'UTF-8') ?></p>
                      <div class="actions">
                        <a class="btn btn-outline btn-sm" href="restaurants.php?selected_id=<?= (int)$restaurant['id_restaurant'] ?>#admin-restaurants-section">Select</a>
                        <a class="btn btn-outline btn-sm" href="restaurants.php?edit_id=<?= (int)$restaurant['id_restaurant'] ?>&selected_id=<?= (int)$restaurant['id_restaurant'] ?>#admin-restaurant-form-card">Edit</a>
                        <form method="post" action="../Controller/restaurant.php?action=admin_delete_restaurant" onsubmit="return confirm('Delete this restaurant?');">
                          <input type="hidden" name="id_owner" value="<?= (int)$restaurant['id_owner'] ?>">
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
            <section class="card animate-fade-in-up stagger-3" id="admin-meals-section">
              <div class="card-header" style="justify-content:space-between;align-items:center;">
                <h3 class="card-title"><i class="fa-solid fa-utensils"></i> <?= htmlspecialchars($mealTitle, ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars((string)$selectedRestaurant['nom'], ENT_QUOTES, 'UTF-8') ?></h3>
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
                  <div><label for="meal_quantity">Quantity</label><input class="input" id="meal_quantity" name="quantity" type="number" min="1" max="1000" step="1" value="<?= htmlspecialchars((string)($mealEditData['quantity'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><div id="meal_quantity-error" class="field-error"></div></div>
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
                  <div id="meal_price_group"><label for="meal_price">Price (if paid)</label><input class="input" id="meal_price" name="price" type="number" min="0" max="500" step="0.01" value="<?= htmlspecialchars((string)($mealEditData['price'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"><div id="meal_price-error" class="field-error"></div></div>
                </div>
                <button class="btn btn-primary btn-sm" type="submit" style="margin-top:12px;"><i class="fa-solid fa-floppy-disk"></i> <?= $mealEditData ? 'Update Meal' : 'Add Meal' ?></button>
              </form>

              <hr style="margin:16px 0;border:none;border-top:1px solid var(--color-dark-border);">
              <div class="table-wrap">
                <table class="table">
                  <thead><tr><th>Meal</th><th>Ingredients</th><th>Qty</th><th>Regime</th><th>Allergens</th><th>Price</th><th>Actions</th></tr></thead>
                  <tbody>
                    <?php if (empty($selectedRestaurant['meals'])): ?>
                      <tr><td colspan="7" style="color:var(--color-text-muted);">No meals yet.</td></tr>
                    <?php else: ?>
                      <?php foreach ($selectedRestaurant['meals'] as $meal): ?>
                        <tr>
                          <td><?= htmlspecialchars((string)($meal['meal_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                          <td><?= htmlspecialchars((string)($meal['ingredients'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                          <td><?= (int)($meal['quantity'] ?? 0) ?></td>
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
            </section>
          <?php endif; ?>
        </div>
      </div>
    </main>
  </div>
  <script src="../js/app.js?v=20260420c"></script>
  <script src="../js/components.js?v=20260420j"></script>
  <script src="../js/admin-restaurants-validation.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (window.App && typeof window.App.requireAuth === 'function') {
        App.requireAuth(['admin']);
      }
      document.querySelectorAll('.js-item').forEach(function (item) {
        item.addEventListener('click', function (event) {
          if (event.target.closest('a') || event.target.closest('button') || event.target.closest('form')) return;
          var href = item.getAttribute('data-href');
          if (href) window.location.href = href;
        });
      });
    });
  </script>
</body>
</html>










