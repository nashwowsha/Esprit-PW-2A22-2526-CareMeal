<?php
require_once __DIR__ . '/../Controller/RestaurantController.php';
require_once __DIR__ . '/../Controller/PreferenceController.php';

$controller = new RestaurantController();
$preferenceController = new PreferenceController();
$idOwner = isset($_GET['id_owner']) ? (int)$_GET['id_owner'] : 1;
if ($idOwner <= 0) {
    $idOwner = 1;
}
$status = $_GET['status'] ?? '';
$selectedId = isset($_GET['selected_id']) ? (int)$_GET['selected_id'] : 0;
$editId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$mealEdit = trim((string)($_GET['meal_edit'] ?? ''));

$restaurants = $controller->getPartnerRestaurants($idOwner);
$selectedRestaurant = null;
$editRestaurant = null;

foreach ($restaurants as $r) {
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

$mealEditData = null;
if ($selectedRestaurant && $mealEdit !== '') {
    foreach (($selectedRestaurant['meals'] ?? []) as $meal) {
        if (($meal['meal_id'] ?? '') === $mealEdit) {
            $mealEditData = $meal;
            break;
        }
    }
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
    'error_restaurant_exists' => ['class' => 'error', 'text' => 'A restaurant with the same name already exists for this partner.'],
    'error_restaurant_image_invalid' => ['class' => 'error', 'text' => 'Invalid restaurant image (png/jpg/jpeg/webp/gif, max 3MB).'],
    'error_validation' => ['class' => 'error', 'text' => 'Validation failed. Check your input fields.'],
    'error_forbidden' => ['class' => 'error', 'text' => 'Action forbidden for this restaurant.'],
    'error_missing_fields' => ['class' => 'error', 'text' => 'Missing required fields.'],
    'error_not_found' => ['class' => 'error', 'text' => 'Requested record not found.'],
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
  <meta name="description" content="CRUD Restaurants partenaire CareMeal.">
  <title>Partner Restaurants - CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/dashboard.css">
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
            <h2>Restaurant CRUD</h2>
            <p>Ajout restaurant d'abord, puis ajout meals/ingredients par restaurant</p>
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
                <div>
                  <label for="restaurant_location">Localisation</label>
                  <input class="input" id="restaurant_location" name="localisation" type="text" value="<?= htmlspecialchars((string)($editRestaurant['localisation'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                  <div id="restaurant_location-error" class="field-error"></div>
                </div>
                <div>
                  <label for="restaurant_phone">Telephone</label>
                  <input class="input" id="restaurant_phone" name="telephone" type="text" placeholder="+216 99 123 456" value="<?= htmlspecialchars((string)($editRestaurant['telephone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                  <div id="restaurant_phone-error" class="field-error"></div>
                </div>
                <div>
                  <label for="restaurant_open_time">Ouverture</label>
                  <input class="input" id="restaurant_open_time" name="open_time" type="time" min="09:00" max="22:00" value="<?= htmlspecialchars((string)$openTimeValue, ENT_QUOTES, 'UTF-8') ?>">
                  <div id="restaurant_open_time-error" class="field-error"></div>
                </div>
                <div>
                  <label for="restaurant_close_time">Fermeture</label>
                  <input class="input" id="restaurant_close_time" name="close_time" type="time" min="09:00" max="22:00" value="<?= htmlspecialchars((string)$closeTimeValue, ENT_QUOTES, 'UTF-8') ?>">
                  <div id="restaurant_close_time-error" class="field-error"></div>
                </div>
                <div class="full">
                  <label for="restaurant_description">Description</label>
                  <textarea class="textarea" id="restaurant_description" name="description"><?= htmlspecialchars((string)($editRestaurant['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                  <div id="restaurant_description-error" class="field-error"></div>
                </div>
                <div class="full">
                  <label for="restaurant_image">Image enseigne</label>
                  <input class="input" id="restaurant_image" name="image_file" type="file" accept="image/*">
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
              <span class="badge badge-info"><?= count($restaurants) ?> items</span>
            </div>
            <?php if (empty($restaurants)): ?>
              <p style="color:var(--color-text-muted);">No restaurants yet.</p>
            <?php else: ?>
              <div class="restaurants-grid">
                <?php foreach ($restaurants as $restaurant): ?>
                  <?php $isSelected = $selectedRestaurant && (int)$selectedRestaurant['id_restaurant'] === (int)$restaurant['id_restaurant']; ?>
                  <article class="restaurant-card<?= $isSelected ? ' selected' : '' ?> js-restaurant-card" data-href="restaurants.php?id_owner=<?= (int)$idOwner ?>&selected_id=<?= (int)$restaurant['id_restaurant'] ?>#partner-restaurants-section">
                    <img class="restaurant-img" src="../<?= htmlspecialchars((string)$restaurant['image_path'], ENT_QUOTES, 'UTF-8') ?>" alt="enseigne">
                    <div class="restaurant-body">
                      <h4 class="restaurant-name"><?= htmlspecialchars((string)$restaurant['nom'], ENT_QUOTES, 'UTF-8') ?></h4>
                      <p class="restaurant-meta"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars((string)$restaurant['localisation'], ENT_QUOTES, 'UTF-8') ?></p>
                      <div class="restaurant-actions">
                        <a class="btn btn-outline btn-sm" href="restaurants.php?id_owner=<?= (int)$idOwner ?>&selected_id=<?= (int)$restaurant['id_restaurant'] ?>#partner-restaurants-section">Select</a>
                        <a class="btn btn-outline btn-sm" href="restaurants.php?id_owner=<?= (int)$idOwner ?>&edit_id=<?= (int)$restaurant['id_restaurant'] ?>&selected_id=<?= (int)$restaurant['id_restaurant'] ?>#restaurant-form-card">Edit</a>
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
                    <input class="input" id="meal_quantity" name="quantity" type="number" min="1" max="1000" step="1" value="<?= htmlspecialchars((string)($mealEditData['quantity'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
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
                    <input class="input" id="meal_price" name="price" type="number" min="0" max="500" step="0.01" value="<?= htmlspecialchars((string)($mealEditData['price'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>">
                    <div id="meal_price-error" class="field-error"></div>
                  </div>
                </div>
                <div style="margin-top:12px;">
                  <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-floppy-disk"></i> <?= $mealEditData ? 'Update Meal' : 'Add Meal' ?></button>
                </div>
              </form>

              <hr style="margin:16px 0;border:none;border-top:1px solid var(--color-dark-border);">
              <h4 style="margin:0 0 10px;">Meals list</h4>
              <div class="table-wrap">
                <table class="meal-table">
                  <thead>
                    <tr>
                      <th>Meal</th><th>Ingredients</th><th>Qty</th><th>Regimes</th><th>Allergenes</th><th>Price</th><th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($selectedRestaurant['meals'])): ?>
                      <tr><td colspan="7" style="color:var(--color-text-muted);">No meals yet for this restaurant.</td></tr>
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
          <?php endif; ?>
        </div>
      </div>
    </main>
  </div>

  <script src="../js/app.js"></script>
  <script src="../js/components.js"></script>
  <script src="../js/partner-restaurants-validation.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
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
    });
  </script>
</body>
</html>
