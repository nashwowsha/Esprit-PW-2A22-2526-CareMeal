<?php
require_once __DIR__ . '/../Controller/PreferenceController.php';
require_once __DIR__ . '/../Controller/PdfExport.php';

$controller = new PreferenceController();
$allRows = $controller->getAllForAdmin();
$status = $_GET['status'] ?? '';

$editId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$selectedId = isset($_GET['selected_id']) ? (int)$_GET['selected_id'] : 0;
$focusUserId = isset($_GET['id_user']) ? (int)$_GET['id_user'] : 0;
$userTableQuery = trim((string)($_GET['user_q'] ?? ''));
$userTableSort = strtolower(trim((string)($_GET['user_sort'] ?? 'date_desc')));
$allTableQuery = trim((string)($_GET['all_q'] ?? ''));
$allTableSort = strtolower(trim((string)($_GET['all_sort'] ?? 'id_desc')));
$allowedPreferenceSort = ['date_desc', 'date_asc', 'id_desc', 'id_asc', 'regime_asc', 'regime_desc', 'localisation_asc', 'localisation_desc', 'user_asc', 'user_desc'];
if (!in_array($userTableSort, $allowedPreferenceSort, true)) {
    $userTableSort = 'date_desc';
}
if (!in_array($allTableSort, $allowedPreferenceSort, true)) {
    $allTableSort = 'id_desc';
}

function adminPreferenceFilterRows($rows, $query) {
    $query = strtolower(trim((string)$query));
    if ($query === '') {
        return $rows;
    }

    return array_values(array_filter($rows, static function ($row) use ($query) {
        $haystack = strtolower(
            (string)($row['id_pref'] ?? '') . ' ' .
            (string)($row['id_user'] ?? '') . ' ' .
            (string)($row['regime_alimentaire'] ?? '') . ' ' .
            (string)($row['allergies'] ?? '') . ' ' .
            (string)($row['localisation'] ?? '') . ' ' .
            (string)($row['date_demande'] ?? '') . ' ' .
            (string)($row['user_nom'] ?? '') . ' ' .
            (string)($row['user_email'] ?? '')
        );

        return strpos($haystack, $query) !== false;
    }));
}

function adminPreferenceSortRows(&$rows, $sort) {
    usort($rows, static function ($a, $b) use ($sort) {
        $dateA = strtotime((string)($a['date_demande'] ?? ''));
        $dateB = strtotime((string)($b['date_demande'] ?? ''));
        $idA = (int)($a['id_pref'] ?? 0);
        $idB = (int)($b['id_pref'] ?? 0);
        $userA = strtolower(trim((string)($a['user_nom'] ?? $a['id_user'] ?? '')));
        $userB = strtolower(trim((string)($b['user_nom'] ?? $b['id_user'] ?? '')));
        $regimeA = strtolower(trim((string)($a['regime_alimentaire'] ?? '')));
        $regimeB = strtolower(trim((string)($b['regime_alimentaire'] ?? '')));
        $locationA = strtolower(trim((string)($a['localisation'] ?? '')));
        $locationB = strtolower(trim((string)($b['localisation'] ?? '')));

        switch ($sort) {
            case 'date_asc':
                return $dateA <=> $dateB;
            case 'id_asc':
                return $idA <=> $idB;
            case 'id_desc':
                return $idB <=> $idA;
            case 'regime_asc':
                return strcmp($regimeA, $regimeB);
            case 'regime_desc':
                return strcmp($regimeB, $regimeA);
            case 'localisation_asc':
                return strcmp($locationA, $locationB);
            case 'localisation_desc':
                return strcmp($locationB, $locationA);
            case 'user_asc':
                return strcmp($userA, $userB);
            case 'user_desc':
                return strcmp($userB, $userA);
            case 'date_desc':
            default:
                return $dateB <=> $dateA;
        }
    });
}

$editRow = $editId > 0 ? $controller->getById($editId) : null;
if ($editRow && $focusUserId <= 0) {
    $focusUserId = (int)$editRow['id_user'];
}

$selectedRow = null;
if ($selectedId > 0) {
    foreach ($allRows as $row) {
        if ((int)$row['id_pref'] === $selectedId) {
            $selectedRow = $row;
            break;
        }
    }
}

if ($selectedRow && $focusUserId <= 0) {
    $focusUserId = (int)$selectedRow['id_user'];
}

if (!$selectedRow && $editRow) {
    foreach ($allRows as $row) {
        if ((int)$row['id_pref'] === (int)$editRow['id_pref']) {
            $selectedRow = $row;
            break;
        }
    }
}

$userRows = [];
if ($focusUserId > 0) {
    foreach ($allRows as $row) {
        if ((int)$row['id_user'] === $focusUserId) {
            $userRows[] = $row;
        }
    }
}

$filteredUserRows = adminPreferenceFilterRows($userRows, $userTableQuery);
adminPreferenceSortRows($filteredUserRows, $userTableSort);

$filteredAllRows = adminPreferenceFilterRows($allRows, $allTableQuery);
adminPreferenceSortRows($filteredAllRows, $allTableSort);

if ($selectedRow && $focusUserId > 0 && (int)$selectedRow['id_user'] !== $focusUserId) {
    $selectedRow = null;
}

if (isset($_GET['user_export']) && $_GET['user_export'] === 'pdf' && $focusUserId > 0) {
    $pdfRows = [];
    foreach ($filteredUserRows as $row) {
        $pdfRows[] = [
            (int)($row['id_pref'] ?? 0),
            (int)($row['id_user'] ?? 0),
            (string)($row['regime_alimentaire'] ?? ''),
            (string)($row['allergies'] ?? ''),
            (string)($row['localisation'] ?? ''),
            (string)($row['date_demande'] ?? ''),
            (string)($row['user_nom'] ?? ''),
            (string)($row['user_email'] ?? ''),
        ];
    }
    caremeal_stream_table_pdf(
        'admin_preferences_user_' . (int)$focusUserId . '_' . date('Ymd_His') . '.pdf',
        'Preferences utilisateur #' . (int)$focusUserId,
        ['ID Pref', 'ID User', 'Regime alimentaire', 'Allergies', 'Localisation', 'Date demande', 'Nom utilisateur', 'Email utilisateur'],
        $pdfRows,
        'landscape'
    );
}

if (isset($_GET['all_export']) && $_GET['all_export'] === 'pdf') {
    $pdfRows = [];
    foreach ($filteredAllRows as $row) {
        $pdfRows[] = [
            (int)($row['id_pref'] ?? 0),
            (int)($row['id_user'] ?? 0),
            (string)($row['regime_alimentaire'] ?? ''),
            (string)($row['allergies'] ?? ''),
            (string)($row['localisation'] ?? ''),
            (string)($row['date_demande'] ?? ''),
            (string)($row['user_nom'] ?? ''),
            (string)($row['user_email'] ?? ''),
        ];
    }
    caremeal_stream_table_pdf(
        'admin_preferences_all_' . date('Ymd_His') . '.pdf',
        'Toutes les preferences',
        ['ID Pref', 'ID User', 'Regime alimentaire', 'Allergies', 'Localisation', 'Date demande', 'Nom utilisateur', 'Email utilisateur'],
        $pdfRows,
        'landscape'
    );
}

$regimeOptions = $controller->getRegimeOptions();
$iconOptions = $controller->getAvailableRegimeIcons();

if (empty($regimeOptions)) {
    $regimeOptions = [
        ['id' => 1, 'value' => 'halal', 'label' => 'Halal', 'icon' => 'fa-star-and-crescent', 'icon_type' => 'fa', 'icon_image' => ''],
        ['id' => 2, 'value' => 'vegetarien', 'label' => 'Vegetarien', 'icon' => 'fa-leaf', 'icon_type' => 'fa', 'icon_image' => ''],
        ['id' => 3, 'value' => 'vegan', 'label' => 'Vegan', 'icon' => 'fa-seedling', 'icon_type' => 'fa', 'icon_image' => ''],
        ['id' => 4, 'value' => 'sans-gluten', 'label' => 'Sans gluten', 'icon' => 'fa-wheat-awn', 'icon_type' => 'fa', 'icon_image' => ''],
        ['id' => 5, 'value' => 'bio', 'label' => 'Bio', 'icon' => 'fa-spa', 'icon_type' => 'fa', 'icon_image' => ''],
        ['id' => 6, 'value' => 'sans-lactose', 'label' => 'Sans lactose', 'icon' => 'fa-glass-water', 'icon_type' => 'fa', 'icon_image' => ''],
        ['id' => 7, 'value' => 'budget', 'label' => 'Petit budget', 'icon' => 'fa-coins', 'icon_type' => 'fa', 'icon_image' => ''],
        ['id' => 8, 'value' => 'equilibre', 'label' => 'Equilibre', 'icon' => 'fa-scale-balanced', 'icon_type' => 'fa', 'icon_image' => ''],
    ];
}

if (empty($iconOptions)) {
    $iconOptions = ['fa-star-and-crescent', 'fa-leaf', 'fa-seedling', 'fa-wheat-awn', 'fa-spa', 'fa-glass-water', 'fa-coins', 'fa-scale-balanced', 'fa-bowl-food', 'fa-carrot', 'fa-utensils', 'fa-apple-whole'];
}

$regimeOptionsJson = json_encode(array_values($regimeOptions), JSON_UNESCAPED_UNICODE);

$selectedRegimes = [];
if ($editRow && !empty($editRow['regime_alimentaire'])) {
    $selectedRegimes = array_values(array_filter(array_map('trim', preg_split('/[,;]+/', (string)$editRow['regime_alimentaire']))));
}

$formAction = $editRow ? 'admin_update' : 'admin_create';
$formTitle = $editRow
    ? 'Edit Preference #' . (int)$editRow['id_pref']
    : ($focusUserId > 0 ? 'Add Preference for User #' . $focusUserId : 'Add Preference');
$formIdUser = $editRow['id_user'] ?? ($focusUserId > 0 ? $focusUserId : '');
$formLocalisation = (string)($editRow['localisation'] ?? '');
$formLocalisationLat = isset($editRow['localisation_lat']) ? (string)$editRow['localisation_lat'] : '';
$formLocalisationLng = isset($editRow['localisation_lng']) ? (string)$editRow['localisation_lng'] : '';

$statusMessages = [
    'success_created' => ['class' => 'success', 'text' => 'Preference saved. User table refreshed.'],
    'success_updated' => ['class' => 'success', 'text' => 'Preference updated successfully.'],
    'success_deleted' => ['class' => 'success', 'text' => 'Preference deleted successfully.'],
    'success_option_added' => ['class' => 'success', 'text' => 'New regime option added successfully.'],
    'success_option_updated' => ['class' => 'success', 'text' => 'Regime option updated successfully.'],
    'success_option_deleted' => ['class' => 'success', 'text' => 'Regime option deleted successfully.'],
    'error_validation' => ['class' => 'error', 'text' => 'Validation failed. Please check your input values.'],
    'error_duplicate_preference' => ['class' => 'error', 'text' => 'This preference already exists for this user.'],
    'error_option_invalid' => ['class' => 'error', 'text' => 'Invalid option label. Use letters and spaces only.'],
    'error_option_icon_invalid' => ['class' => 'error', 'text' => 'Invalid icon. Choose an available icon or upload a valid image.'],
    'error_option_exists' => ['class' => 'error', 'text' => 'This option already exists.'],
    'error_option_not_found' => ['class' => 'error', 'text' => 'Regime option not found.'],
    'error_option_in_use' => ['class' => 'error', 'text' => 'This option is used in preferences and cannot be deleted.'],
    'error_forbidden' => ['class' => 'error', 'text' => 'Forbidden operation for this preference.'],
    'error_missing_fields' => ['class' => 'error', 'text' => 'Missing required fields.'],
    'error_not_found' => ['class' => 'error', 'text' => 'Preference not found.'],
    'error_db' => ['class' => 'error', 'text' => 'Database error.'],
    'error_unknown_action' => ['class' => 'error', 'text' => 'Unknown action.'],
    'error_invalid_request' => ['class' => 'error', 'text' => 'Invalid request.'],
];

function isRegimeChecked($value, $selectedRegimes) {
    return in_array($value, $selectedRegimes, true);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Gestion admin des preferences CareMeal.">
  <title>Admin Preferences - CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="../css/theme-fix.css">
  <link rel="stylesheet" href="../assets/vendor/leaflet/leaflet.css">
  <style>
    .pref-alert {
      border-radius: var(--radius-md);
      padding: 12px 14px;
      margin-bottom: 16px;
      font-size: 0.9rem;
      border: 1px solid transparent;
    }
    .pref-alert.success {
      color: #0f5132;
      background: rgba(25, 135, 84, 0.18);
      border-color: rgba(25, 135, 84, 0.4);
    }
    .pref-alert.error {
      color: #842029;
      background: rgba(220, 53, 69, 0.16);
      border-color: rgba(220, 53, 69, 0.4);
    }
    .pref-toolbar {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      align-items: center;
      margin-bottom: 14px;
    }
    .pref-inline-form {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
    }
    .pref-input,
    .pref-select,
    .pref-textarea {
      width: 100%;
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid var(--color-dark-border);
      border-radius: var(--radius-md);
      color: var(--color-white);
      padding: 11px 12px;
      margin-top: 6px;
      margin-bottom: 10px;
    }
    .pref-select option {
      color: #111;
    }
    .pref-textarea {
      min-height: 90px;
      resize: vertical;
    }
    .pref-actions {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      align-items: center;
    }
    .pref-muted {
      color: var(--color-text-muted);
      font-size: 0.82rem;
    }
    .pref-layout {
      display: grid;
      grid-template-columns: minmax(320px, 430px) minmax(0, 1fr);
      gap: 16px;
    }
    .pref-field-error {
      color: #ff9da7;
      font-size: 0.82rem;
      min-height: 18px;
      margin-top: -4px;
      margin-bottom: 8px;
      display: none;
    }
    .pref-input-error {
      border-color: rgba(220, 53, 69, 0.7) !important;
      box-shadow: 0 0 0 1px rgba(220, 53, 69, 0.25);
    }
    .tags-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 8px;
      margin-bottom: 6px;
    }
    .tag {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      border: 1px solid var(--color-dark-border);
      border-radius: 999px;
      padding: 8px 11px;
      background: rgba(255, 255, 255, 0.03);
      font-size: 0.86rem;
      cursor: pointer;
    }
    .tag input[type="checkbox"] {
      display: none;
    }
    .tag.selected {
      border-color: var(--color-primary);
      color: var(--color-primary);
      background: rgba(249, 115, 22, 0.12);
    }
    .tag-emoji img {
      width: 18px;
      height: 18px;
      border-radius: 4px;
      object-fit: cover;
      display: inline-block;
    }
    .pref-row-click {
      cursor: pointer;
    }
    .pref-row-click:hover {
      background: rgba(255, 255, 255, 0.04);
    }
    .pref-row-selected {
      background: rgba(249, 115, 22, 0.16);
    }
    .pref-icon-mode {
      display: flex;
      gap: 14px;
      align-items: center;
      margin: 4px 0 8px;
      color: var(--color-text-muted);
      font-size: 0.84rem;
    }
    .icon-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 8px;
      margin-bottom: 10px;
    }
    .icon-choice {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 10px 8px;
      border: 1px solid var(--color-dark-border);
      border-radius: 10px;
      cursor: pointer;
      background: rgba(255, 255, 255, 0.03);
      min-height: 40px;
    }
    .icon-choice input[type="radio"] {
      display: none;
    }
    .icon-choice.selected {
      border-color: var(--color-primary);
      background: rgba(249, 115, 22, 0.12);
      color: var(--color-primary);
    }
    .icon-upload-preview {
      margin-top: 4px;
      max-width: 78px;
      max-height: 78px;
      border-radius: 8px;
      border: 1px solid var(--color-dark-border);
      display: none;
      object-fit: cover;
      background: #fff;
    }
    .pref-section-gap {
      margin-top: 16px;
    }
    .pref-card-title-line {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
    }
    .pref-small {
      font-size: 0.8rem;
    }
    .pref-location-tools {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 8px;
      margin-top: 8px;
    }
    .pref-location-map {
      width: 100%;
      height: 260px;
      border: 1px solid var(--color-dark-border);
      border-radius: var(--radius-md);
      margin-top: 10px;
      overflow: hidden;
    }
    .pref-location-coords {
      color: var(--color-text-muted);
      font-size: 0.82rem;
      margin-top: 8px;
    }
    @media (max-width: 1100px) {
      .pref-layout {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="dashboard-layout">
    <?php
      $activePage = 'preferences';
      require __DIR__ . '/_admin_sidebar.php';
    ?>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle"><i class="fa-solid fa-bars"></i></button>
          <div class="page-title">
            <h2>Preferences Management</h2>
            <p>
              <?php if ($focusUserId > 0): ?>
                User #<?= (int)$focusUserId ?> table is active. Submit refreshes this table immediately.
              <?php else: ?>
                Choose a User ID to open and manage that user's preference table.
              <?php endif; ?>
            </p>
          </div>
        </div>
        <div class="header-right">
          <div class="avatar avatar-sm" id="header-avatar" style="background:linear-gradient(135deg,#EF4444,#F87171);">A</div>
        </div>
      </header>

      <div class="page-content">
        <?php if (isset($statusMessages[$status])): ?>
          <div class="pref-alert <?= htmlspecialchars($statusMessages[$status]['class'], ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($statusMessages[$status]['text'], ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>

        <div class="card" style="margin-bottom: 16px;">
          <div class="pref-toolbar">
            <form method="get" action="preferences.php" class="pref-inline-form">
              <label for="focus_user_id" style="margin:0;">User ID:</label>
              <input id="focus_user_id" type="text" name="id_user" class="pref-input" style="width:140px; margin:0;" value="<?= $focusUserId > 0 ? (int)$focusUserId : '' ?>" placeholder="Ex: 1">
              <button type="submit" class="btn btn-outline btn-sm"><i class="fa-solid fa-magnifying-glass"></i> Load user table</button>
            </form>
            <a href="preferences.php" class="btn btn-outline btn-sm">Reset view</a>
            <?php if ($focusUserId > 0): ?>
              <a href="preferences.php?id_user=<?= (int)$focusUserId ?>" class="btn btn-outline btn-sm"><i class="fa-solid fa-plus"></i> Add another pref</a>
            <?php endif; ?>
          </div>
          <p class="pref-muted" style="margin: 0;">Workflow: submit preference -> refreshed user table -> select a row -> edit/delete/start matching.</p>
        </div>

        <div class="pref-layout">
          <section class="card animate-fade-in-up">
            <div class="card-header">
              <h3 class="card-title"><i class="fa-solid fa-pen-to-square"></i> <?= htmlspecialchars($formTitle, ENT_QUOTES, 'UTF-8') ?></h3>
            </div>

            <form method="post" action="../Controller/preference.php?action=<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>" id="admin-pref-form" novalidate>
              <?php if ($editRow): ?>
                <input type="hidden" name="id_pref" value="<?= (int)$editRow['id_pref'] ?>">
              <?php endif; ?>

              <label for="id_user">User ID (required)</label>
              <input id="id_user" name="id_user" class="pref-input" type="text" value="<?= htmlspecialchars((string)$formIdUser, ENT_QUOTES, 'UTF-8') ?>">
              <div id="id_user-error" class="pref-field-error"></div>

              <label style="display:block;margin-top:6px;">Regimes alimentaires</label>
              <div class="tags-grid">
                <?php foreach ($regimeOptions as $regime): ?>
                  <?php $checked = isRegimeChecked($regime['value'], $selectedRegimes); ?>
                  <label class="tag<?= $checked ? ' selected' : '' ?>">
                    <input type="checkbox" name="regimes[]" value="<?= htmlspecialchars((string)$regime['value'], ENT_QUOTES, 'UTF-8') ?>"<?= $checked ? ' checked' : '' ?>>
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
              <div id="regimes-error" class="pref-field-error"></div>

              <label for="allergies">Allergies</label>
              <textarea id="allergies" name="allergies" class="pref-textarea"><?= htmlspecialchars((string)($editRow['allergies'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
              <div id="allergies-error" class="pref-field-error"></div>

              <label for="localisation">Localisation</label>
              <input id="localisation" name="localisation" class="pref-input" type="text" value="<?= htmlspecialchars($formLocalisation, ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" id="localisation-lat" name="localisation_lat" value="<?= htmlspecialchars($formLocalisationLat, ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" id="localisation-lng" name="localisation_lng" value="<?= htmlspecialchars($formLocalisationLng, ENT_QUOTES, 'UTF-8') ?>">
              <div class="pref-location-tools">
                <input id="admin_pref_location_search" class="pref-input" type="text" placeholder="Rechercher sur la carte" style="width: 320px; margin: 0;">
                <button type="button" class="btn btn-outline btn-sm" id="admin_pref_location_search_btn"><i class="fa-solid fa-magnifying-glass"></i> Rechercher</button>
                <button type="button" class="btn btn-outline btn-sm" id="admin_pref_current_location_btn"><i class="fa-solid fa-location-crosshairs"></i> Ma position actuelle</button>
              </div>
              <div id="admin_pref_location_map" class="pref-location-map"></div>
              <div id="admin_pref_location_coords" class="pref-location-coords">Coordonnees: non selectionnees</div>
              <div id="localisation-error" class="pref-field-error"></div>

              <div class="pref-actions">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk"></i> <?= $editRow ? 'Update' : 'Submit Pref' ?></button>
                <?php if ($editRow): ?>
                  <a href="preferences.php?id_user=<?= (int)$focusUserId ?>" class="btn btn-outline btn-sm">Cancel edit</a>
                <?php endif; ?>
              </div>
            </form>

            <?php if ($selectedRow): ?>
              <div class="pref-section-gap">
                <div class="pref-card-title-line" style="margin-bottom:8px;">
                  <strong>Selected Preference #<?= (int)$selectedRow['id_pref'] ?></strong>
                  <span class="pref-muted pref-small">User #<?= (int)$selectedRow['id_user'] ?></span>
                </div>
                <a href="../Controller/MatchingController.php?action=student_matches&id_user=<?= (int)$selectedRow['id_user'] ?>" target="_blank" class="btn btn-outline btn-sm">
                  <i class="fa-solid fa-play"></i> Start Matching
                </a>
                <p class="pref-muted" style="margin-top:8px;">Current endpoint opens JSON (API placeholder).</p>
              </div>
            <?php endif; ?>
          </section>

          <section class="card animate-fade-in-up stagger-1" id="user-preferences-card">
            <div class="card-header" style="justify-content:space-between;align-items:center;">
              <h3 class="card-title"><i class="fa-solid fa-table"></i> User Preferences Table</h3>
              <?php if ($focusUserId > 0): ?>
                <span class="badge badge-info">User #<?= (int)$focusUserId ?> - <?= count($filteredUserRows) ?> filtered / <?= count($userRows) ?> total</span>
              <?php else: ?>
                <span class="badge badge-info">Choose a user</span>
              <?php endif; ?>
            </div>

            <?php if ($focusUserId <= 0): ?>
              <p class="pref-muted">Enter a User ID above to display that user's preferences.</p>
            <?php else: ?>
              <div class="pref-toolbar" style="margin-bottom:10px;">
                <form method="get" action="preferences.php" class="pref-inline-form">
                  <input type="hidden" name="id_user" value="<?= (int)$focusUserId ?>">
                  <input class="pref-input" style="width:260px;margin:0;" type="text" name="user_q" value="<?= htmlspecialchars($userTableQuery, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search regime, allergies, location...">
                  <select class="pref-select" name="user_sort" style="width:210px;margin:0;">
                    <option value="date_desc"<?= $userTableSort === 'date_desc' ? ' selected' : '' ?>>Newest</option>
                    <option value="date_asc"<?= $userTableSort === 'date_asc' ? ' selected' : '' ?>>Oldest</option>
                    <option value="id_desc"<?= $userTableSort === 'id_desc' ? ' selected' : '' ?>>ID desc</option>
                    <option value="id_asc"<?= $userTableSort === 'id_asc' ? ' selected' : '' ?>>ID asc</option>
                    <option value="regime_asc"<?= $userTableSort === 'regime_asc' ? ' selected' : '' ?>>Regime A-Z</option>
                    <option value="regime_desc"<?= $userTableSort === 'regime_desc' ? ' selected' : '' ?>>Regime Z-A</option>
                    <option value="localisation_asc"<?= $userTableSort === 'localisation_asc' ? ' selected' : '' ?>>Location A-Z</option>
                    <option value="localisation_desc"<?= $userTableSort === 'localisation_desc' ? ' selected' : '' ?>>Location Z-A</option>
                  </select>
                  <button type="submit" class="btn btn-outline btn-sm"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
                </form>
                <a class="btn btn-outline btn-sm" href="preferences.php?id_user=<?= (int)$focusUserId ?>#user-preferences-card">Reset</a>
                <a class="btn btn-outline btn-sm" href="preferences.php?<?= htmlspecialchars(http_build_query(['id_user' => (int)$focusUserId, 'user_q' => $userTableQuery, 'user_sort' => $userTableSort, 'user_export' => 'pdf']), ENT_QUOTES, 'UTF-8') ?>">Export PDF</a>
              </div>

              <?php if (empty($userRows)): ?>
                <p class="pref-muted">No preferences found for user #<?= (int)$focusUserId ?>. You can create one from the form.</p>
              <?php elseif (empty($filteredUserRows)): ?>
                <p class="pref-muted">No rows match your user table filters.</p>
              <?php else: ?>
              <div class="table-container">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>ID Pref</th>
                      <th>Regime</th>
                      <th>Allergies</th>
                      <th>Localisation</th>
                      <th>Date</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($filteredUserRows as $row): ?>
                      <?php $isSelected = $selectedRow && (int)$selectedRow['id_pref'] === (int)$row['id_pref']; ?>
                      <?php
                        $rowSelectParams = [
                            'id_user' => (int)$focusUserId,
                            'selected_id' => (int)$row['id_pref'],
                            'user_q' => $userTableQuery,
                            'user_sort' => $userTableSort,
                            'all_q' => $allTableQuery,
                            'all_sort' => $allTableSort,
                        ];
                        $rowEditParams = [
                            'id_user' => (int)$focusUserId,
                            'edit_id' => (int)$row['id_pref'],
                            'selected_id' => (int)$row['id_pref'],
                            'user_q' => $userTableQuery,
                            'user_sort' => $userTableSort,
                            'all_q' => $allTableQuery,
                            'all_sort' => $allTableSort,
                        ];
                      ?>
                      <tr class="pref-row-click<?= $isSelected ? ' pref-row-selected' : '' ?>" data-href="preferences.php?<?= htmlspecialchars(http_build_query($rowSelectParams), ENT_QUOTES, 'UTF-8') ?>#user-preferences-card">
                        <td><?= (int)$row['id_pref'] ?></td>
                        <td><?= htmlspecialchars((string)$row['regime_alimentaire'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$row['allergies'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$row['localisation'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$row['date_demande'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                          <div class="pref-actions">
                            <a class="btn btn-outline btn-sm" href="preferences.php?<?= htmlspecialchars(http_build_query($rowSelectParams), ENT_QUOTES, 'UTF-8') ?>#user-preferences-card">Select</a>
                            <a class="btn btn-outline btn-sm" href="preferences.php?<?= htmlspecialchars(http_build_query($rowEditParams), ENT_QUOTES, 'UTF-8') ?>">Edit</a>
                            <form method="post" action="../Controller/preference.php?action=admin_delete" style="display:inline;" onsubmit="return confirm('Delete preference #<?= (int)$row['id_pref'] ?>?');">
                              <input type="hidden" name="id_pref" value="<?= (int)$row['id_pref'] ?>">
                              <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <?php endif; ?>
            <?php endif; ?>
          </section>
        </div>

        <div class="grid grid-3 gap-4 pref-section-gap">
          <section class="card">
            <div class="card-header">
              <h3 class="card-title"><i class="fa-solid fa-plus"></i> Add Regime Option</h3>
            </div>
            <form method="post" action="../Controller/preference.php?action=admin_add_regime_option" id="admin-regime-option-form" enctype="multipart/form-data" novalidate>
              <label for="option_label">Option label</label>
              <input id="option_label" name="option_label" class="pref-input" type="text" placeholder="Ex: Mediterraneen">
              <div id="option_label-error" class="pref-field-error"></div>

              <div class="pref-icon-mode">
                <label><input type="radio" name="icon_mode" value="fa" checked> Built-in icon</label>
                <label><input type="radio" name="icon_mode" value="upload"> Upload image</label>
              </div>

              <div id="add-fa-picker">
                <div class="icon-grid">
                  <?php foreach ($iconOptions as $index => $iconClass): ?>
                    <label class="icon-choice<?= $index === 0 ? ' selected' : '' ?>">
                      <input type="radio" name="icon_class" value="<?= htmlspecialchars((string)$iconClass, ENT_QUOTES, 'UTF-8') ?>"<?= $index === 0 ? ' checked' : '' ?>>
                      <i class="fa-solid <?= htmlspecialchars((string)$iconClass, ENT_QUOTES, 'UTF-8') ?>"></i>
                    </label>
                  <?php endforeach; ?>
                </div>
              </div>

              <div id="add-upload-picker" style="display:none;">
                <label for="option_icon_file">Upload icon image</label>
                <input id="option_icon_file" name="icon_file" class="pref-input" type="file" accept="image/*">
                <img id="option_icon_preview" class="icon-upload-preview" alt="icon preview">
              </div>
              <div id="option_icon_class-error" class="pref-field-error"></div>

              <button type="submit" class="btn btn-outline btn-sm"><i class="fa-solid fa-plus"></i> Add option</button>
            </form>
          </section>

          <section class="card">
            <div class="card-header">
              <h3 class="card-title"><i class="fa-solid fa-pen"></i> Edit Regime Option</h3>
            </div>
            <form method="post" action="../Controller/preference.php?action=admin_update_regime_option" id="admin-regime-option-edit-form" enctype="multipart/form-data" novalidate>
              <label for="edit_option_id">Choose option</label>
              <select id="edit_option_id" name="option_id" class="pref-select">
                <?php foreach ($regimeOptions as $regime): ?>
                  <option value="<?= (int)($regime['id'] ?? 0) ?>"><?= htmlspecialchars((string)$regime['label'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
              </select>
              <div id="edit_option_id-error" class="pref-field-error"></div>

              <label for="edit_option_label">New label</label>
              <input id="edit_option_label" name="option_label" class="pref-input" type="text" placeholder="Ex: Mediterraneen">
              <div id="edit_option_label-error" class="pref-field-error"></div>

              <div class="pref-icon-mode">
                <label><input type="radio" name="icon_mode" value="fa" checked> Built-in icon</label>
                <label><input type="radio" name="icon_mode" value="upload"> Upload image</label>
              </div>

              <div id="edit-fa-picker">
                <div class="icon-grid">
                  <?php foreach ($iconOptions as $index => $iconClass): ?>
                    <label class="icon-choice<?= $index === 0 ? ' selected' : '' ?>">
                      <input type="radio" name="icon_class" value="<?= htmlspecialchars((string)$iconClass, ENT_QUOTES, 'UTF-8') ?>"<?= $index === 0 ? ' checked' : '' ?>>
                      <i class="fa-solid <?= htmlspecialchars((string)$iconClass, ENT_QUOTES, 'UTF-8') ?>"></i>
                    </label>
                  <?php endforeach; ?>
                </div>
              </div>

              <div id="edit-upload-picker" style="display:none;">
                <label for="edit_option_icon_file">Upload icon image</label>
                <input id="edit_option_icon_file" name="icon_file" class="pref-input" type="file" accept="image/*">
                <img id="edit_option_icon_preview" class="icon-upload-preview" alt="icon preview">
              </div>
              <div id="edit_option_icon_class-error" class="pref-field-error"></div>

              <button type="submit" class="btn btn-outline btn-sm"><i class="fa-solid fa-floppy-disk"></i> Update option</button>
            </form>
          </section>

          <section class="card">
            <div class="card-header">
              <h3 class="card-title"><i class="fa-solid fa-trash"></i> Delete Regime Option</h3>
            </div>
            <form method="post" action="../Controller/preference.php?action=admin_delete_regime_option" id="admin-regime-option-delete-form" onsubmit="return confirm('Delete this regime option?');" novalidate>
              <label for="delete_option_id">Choose option</label>
              <select id="delete_option_id" name="option_id" class="pref-select">
                <?php foreach ($regimeOptions as $regime): ?>
                  <option value="<?= (int)($regime['id'] ?? 0) ?>"><?= htmlspecialchars((string)$regime['label'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
              </select>
              <div id="delete_option_id-error" class="pref-field-error"></div>

              <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i> Delete option</button>
            </form>
          </section>
        </div>

        <section class="card pref-section-gap">
          <div class="card-header" style="justify-content:space-between;align-items:center;">
            <h3 class="card-title"><i class="fa-solid fa-table-list"></i> All Preferences Snapshot</h3>
            <span class="badge badge-info"><?= count($filteredAllRows) ?> filtered / <?= count($allRows) ?> total</span>
          </div>
          <div class="pref-toolbar" style="margin-bottom:10px;">
            <form method="get" action="preferences.php" class="pref-inline-form">
              <?php if ($focusUserId > 0): ?>
                <input type="hidden" name="id_user" value="<?= (int)$focusUserId ?>">
              <?php endif; ?>
              <?php if ($selectedRow): ?>
                <input type="hidden" name="selected_id" value="<?= (int)$selectedRow['id_pref'] ?>">
              <?php endif; ?>
              <input class="pref-input" style="width:260px;margin:0;" type="text" name="all_q" value="<?= htmlspecialchars($allTableQuery, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search all preferences...">
              <select class="pref-select" name="all_sort" style="width:210px;margin:0;">
                <option value="id_desc"<?= $allTableSort === 'id_desc' ? ' selected' : '' ?>>ID desc</option>
                <option value="id_asc"<?= $allTableSort === 'id_asc' ? ' selected' : '' ?>>ID asc</option>
                <option value="date_desc"<?= $allTableSort === 'date_desc' ? ' selected' : '' ?>>Newest</option>
                <option value="date_asc"<?= $allTableSort === 'date_asc' ? ' selected' : '' ?>>Oldest</option>
                <option value="user_asc"<?= $allTableSort === 'user_asc' ? ' selected' : '' ?>>User A-Z</option>
                <option value="user_desc"<?= $allTableSort === 'user_desc' ? ' selected' : '' ?>>User Z-A</option>
                <option value="regime_asc"<?= $allTableSort === 'regime_asc' ? ' selected' : '' ?>>Regime A-Z</option>
                <option value="regime_desc"<?= $allTableSort === 'regime_desc' ? ' selected' : '' ?>>Regime Z-A</option>
              </select>
              <button type="submit" class="btn btn-outline btn-sm"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
            </form>
            <a class="btn btn-outline btn-sm" href="preferences.php<?= $focusUserId > 0 ? '?id_user=' . (int)$focusUserId : '' ?>#user-preferences-card">Reset</a>
            <a class="btn btn-outline btn-sm" href="preferences.php?<?= htmlspecialchars(http_build_query(['id_user' => $focusUserId > 0 ? (int)$focusUserId : null, 'all_q' => $allTableQuery, 'all_sort' => $allTableSort, 'all_export' => 'pdf']), ENT_QUOTES, 'UTF-8') ?>">Export PDF</a>
          </div>
          <div class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th>ID Pref</th>
                  <th>ID User</th>
                  <th>Regime</th>
                  <th>Allergies</th>
                  <th>Localisation</th>
                  <th>User</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($allRows)): ?>
                  <tr><td colspan="6" class="pref-muted">No preference records.</td></tr>
                <?php elseif (empty($filteredAllRows)): ?>
                  <tr><td colspan="6" class="pref-muted">No rows match snapshot filters.</td></tr>
                <?php else: ?>
                  <?php foreach ($filteredAllRows as $row): ?>
                    <tr>
                      <td><?= (int)$row['id_pref'] ?></td>
                      <td><?= (int)$row['id_user'] ?></td>
                      <td><?= htmlspecialchars((string)$row['regime_alimentaire'], ENT_QUOTES, 'UTF-8') ?></td>
                      <td><?= htmlspecialchars((string)$row['allergies'], ENT_QUOTES, 'UTF-8') ?></td>
                      <td><?= htmlspecialchars((string)$row['localisation'], ENT_QUOTES, 'UTF-8') ?></td>
                      <td>
                        <strong><?= htmlspecialchars((string)($row['user_nom'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') ?></strong><br>
                        <span class="pref-muted"><?= htmlspecialchars((string)($row['user_email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </main>
  </div>

  <script>
    window.REGIME_OPTIONS = <?= $regimeOptionsJson ?: '[]' ?>;
  </script>
  <script src="../js/app.js?v=20260420c"></script>
  <script src="../js/components.js?v=20260421a"></script>
  <script src="../assets/vendor/leaflet/leaflet.js"></script>
  <script src="../js/collecte-address-picker.js"></script>
  <script src="../js/admin-preferences-validation.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (window.App && typeof window.App.requireAuth === 'function') {
        App.requireAuth(['admin']);
      }
      if (typeof window.initCollecteAddressPicker === 'function') {
        window.initCollecteAddressPicker({
          mapId: 'admin_pref_location_map',
          addressInputId: 'localisation',
          latInputId: 'localisation-lat',
          lngInputId: 'localisation-lng',
          searchInputId: 'admin_pref_location_search',
          searchBtnId: 'admin_pref_location_search_btn',
          currentLocationBtnId: 'admin_pref_current_location_btn',
          coordsLabelId: 'admin_pref_location_coords',
          defaultCenter: [36.8065, 10.1815],
          defaultZoom: 12
        });
      }

      document.querySelectorAll('.pref-row-click').forEach(function (row) {
        row.addEventListener('click', function (event) {
          if (event.target.closest('a') || event.target.closest('button') || event.target.closest('form')) {
            return;
          }
          var href = row.getAttribute('data-href');
          if (href) {
            window.location.href = href;
          }
        });
      });
    });
  </script>
</body>
</html>











