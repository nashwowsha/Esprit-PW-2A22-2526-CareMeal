<?php
require_once __DIR__ . '/../Controller/PreferenceController.php';
require_once __DIR__ . '/../Controller/MatchingController.php';

$idUser = isset($_GET['id_user']) ? (int)$_GET['id_user'] : 1;
if ($idUser <= 0) {
    $idUser = 1;
}

$status = $_GET['status'] ?? '';
$editId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$selectedId = isset($_GET['selected_id']) ? (int)$_GET['selected_id'] : 0;

$preferenceController = new PreferenceController();
$preferenceRows = $preferenceController->getListByUserId($idUser);
$editingPreference = null;
$selectedPreference = null;

if ($editId > 0) {
    $candidate = $preferenceController->getById($editId);
    if ($candidate && (int)$candidate['id_user'] === $idUser) {
        $editingPreference = $candidate;
    }
}

if ($selectedId > 0) {
    foreach ($preferenceRows as $row) {
        if ((int)$row['id_pref'] === $selectedId) {
            $selectedPreference = $row;
            break;
        }
    }
}

if (!$selectedPreference && $editingPreference) {
    $selectedPreference = $editingPreference;
}

$selectedRegimes = [];
$allergiesValue = '';
$localisationValue = '';

if ($editingPreference) {
    $selectedRegimes = array_values(array_filter(array_map('trim', preg_split('/[,;]+/', (string)$editingPreference['regime_alimentaire']))));
    $allergiesValue = (string)$editingPreference['allergies'];
    $localisationValue = (string)$editingPreference['localisation'];
}

$matchingController = new MatchingController();
$matchingResult = $matchingController->getRankedOffersForUser($idUser);

$statusMessages = [
    'success_created' => ['class' => 'success', 'text' => 'Preference added successfully.'],
    'success_updated' => ['class' => 'success', 'text' => 'Selected preference updated successfully.'],
    'success_deleted' => ['class' => 'success', 'text' => 'Preference deleted successfully.'],
    'error_validation' => ['class' => 'error', 'text' => 'Validation failed. Please check your input values.'],
    'error_duplicate_preference' => ['class' => 'error', 'text' => 'This preference already exists for this user.'],
    'error_missing_fields' => ['class' => 'error', 'text' => 'Please fill all required fields.'],
    'error_db' => ['class' => 'error', 'text' => 'Database error. Please try again.'],
    'error_forbidden' => ['class' => 'error', 'text' => 'You cannot modify another student preference.'],
    'error_not_found' => ['class' => 'error', 'text' => 'Selected preference was not found.'],
];

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

function isRegimeChecked($value, $selectedRegimes) {
    return in_array($value, $selectedRegimes, true);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Gerez vos preferences alimentaires sur CareMeal.">
  <title>Preferences - CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <style>
    html { scroll-behavior: smooth; }
    .pref-alert {
      border-radius: var(--radius-md);
      padding: 12px 14px;
      margin-bottom: 18px;
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
    .pref-form-input {
      width: 100%;
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid var(--color-dark-border);
      border-radius: var(--radius-md);
      color: var(--color-white);
      padding: 12px 14px;
    }
    .pref-form-input::placeholder { color: var(--color-text-muted); }
    .pref-form-textarea {
      min-height: 110px;
      resize: vertical;
    }
    .tag input[type="checkbox"] { display: none; }
    .tag-emoji img {
      width: 18px;
      height: 18px;
      border-radius: 4px;
      object-fit: cover;
      display: inline-block;
    }
    .pref-meta {
      color: var(--color-text-muted);
      font-size: 0.82rem;
    }
    .pref-page-stack {
      display: grid;
      grid-template-columns: 1fr;
      gap: 18px;
    }
    .pref-form-card {
      min-height: calc(100vh - 175px);
    }
    .pref-row-click { cursor: pointer; }
    .pref-row-click:hover { background: rgba(255, 255, 255, 0.03); }
    .pref-row-selected { background: rgba(249, 115, 22, 0.14); }
    .pref-actions-inline {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      align-items: center;
    }
    .pref-field-error {
      color: #ff9da7;
      font-size: 0.82rem;
      min-height: 18px;
      margin-top: 6px;
      margin-bottom: 8px;
      display: none;
    }
    .pref-input-error {
      border-color: rgba(220, 53, 69, 0.7) !important;
      box-shadow: 0 0 0 1px rgba(220, 53, 69, 0.25);
    }
    @media (max-width: 900px) {
      .pref-form-card {
        min-height: auto;
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
          <a href="preferences.php" class="sidebar-link active"><span class="link-icon"><i class="fa-solid fa-utensils"></i></span> Preferences</a>
          <a href="events.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> Evenements</a>
          <a href="orders.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-box"></i></span> Mes Commandes</a>
        </div>
        <div class="sidebar-section">
          <div class="sidebar-section-title">Parametres</div>
          <a href="settings.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-gear"></i></span> Parametres</a>
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
          <div class="page-title"><h2>Preferences alimentaires</h2><p>Choisissez une ligne pour modifier, ou ajoutez une nouvelle preference</p></div>
        </div>
        <div class="header-right">
          <button class="header-notification"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button>
          <div class="avatar avatar-sm" id="header-avatar">AA</div>
        </div>
      </header>

      <div class="page-content">
        <?php if (isset($statusMessages[$status])): ?>
          <div class="pref-alert <?= htmlspecialchars($statusMessages[$status]['class'], ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($statusMessages[$status]['text'], ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>

        <div class="pref-page-stack animate-fade-in-up">
          <div class="card pref-form-card" id="pref-form-card">
            <div class="card-header" style="justify-content:space-between;align-items:center;">
              <h3 class="card-title"><i class="fa-solid fa-sliders"></i> <?= $editingPreference ? 'Edit Selected Preference' : 'Add New Preference' ?></h3>
              <?php if ($editingPreference): ?>
                <a class="btn btn-outline btn-sm" href="preferences.php?id_user=<?= (int)$idUser ?>#pref-form-card">New Preference</a>
              <?php endif; ?>
            </div>

            <form method="post" action="../Controller/preference.php?action=student_upsert" id="student-pref-form" novalidate>
              <input type="hidden" name="id_user" class="js-id-user" value="<?= (int)$idUser ?>">
              <?php if ($editingPreference): ?>
                <input type="hidden" name="id_pref" value="<?= (int)$editingPreference['id_pref'] ?>">
              <?php endif; ?>

              <label style="display:block;font-weight:500;margin-bottom:12px;color:var(--color-white);">Regimes alimentaires (required)</label>
              <div class="tags-grid" style="margin-bottom:20px;">
                <?php foreach ($regimeOptions as $regime): ?>
                  <?php $checked = isRegimeChecked($regime['value'], $selectedRegimes); ?>
                  <label class="tag<?= $checked ? ' selected' : '' ?>" style="cursor:pointer;">
                    <input type="checkbox" name="regimes[]" value="<?= htmlspecialchars($regime['value'], ENT_QUOTES, 'UTF-8') ?>"<?= $checked ? ' checked' : '' ?>>
                    <span class="tag-emoji">
                      <?php if (($regime['icon_type'] ?? 'fa') === 'image' && !empty($regime['icon_image'])): ?>
                        <img src="../<?= htmlspecialchars((string)$regime['icon_image'], ENT_QUOTES, 'UTF-8') ?>" alt="icon">
                      <?php else: ?>
                        <i class="fa-solid <?= htmlspecialchars((string)$regime['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                      <?php endif; ?>
                    </span>
                    <?= htmlspecialchars($regime['label'], ENT_QUOTES, 'UTF-8') ?>
                  </label>
                <?php endforeach; ?>
              </div>
              <div id="regimes-error" class="pref-field-error"></div>

              <div style="margin-bottom:18px;">
                <label for="pref-allergies" style="display:block;font-weight:500;margin-bottom:8px;color:var(--color-white);">Allergies (required)</label>
                <textarea id="pref-allergies" name="allergies" class="pref-form-input pref-form-textarea" placeholder="Ex: soja, arachide"><?= htmlspecialchars($allergiesValue, ENT_QUOTES, 'UTF-8') ?></textarea>
                <div id="allergies-error" class="pref-field-error"></div>
              </div>

              <div style="margin-bottom:20px;">
                <label for="pref-localisation" style="display:block;font-weight:500;margin-bottom:8px;color:var(--color-white);">Localisation (required, exact match)</label>
                <input type="text" id="pref-localisation" name="localisation" class="pref-form-input" value="<?= htmlspecialchars($localisationValue, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: Ariana">
                <div id="localisation-error" class="pref-field-error"></div>
              </div>

              <div class="pref-actions-inline">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk"></i> <?= $editingPreference ? 'Save Changes' : 'Add Preference' ?></button>
              </div>
            </form>

            <?php if ($editingPreference): ?>
              <form method="post" action="../Controller/preference.php?action=student_delete" onsubmit="return confirm('Delete selected preference?');" style="margin-top:10px;">
                <input type="hidden" name="id_pref" value="<?= (int)$editingPreference['id_pref'] ?>">
                <input type="hidden" name="id_user" class="js-id-user" value="<?= (int)$idUser ?>">
                <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i> Delete Selected</button>
              </form>
            <?php endif; ?>
          </div>

          <div class="card" id="saved-preferences-card">
            <div class="card-header" style="justify-content:space-between;align-items:center;">
              <h3 class="card-title"><i class="fa-solid fa-list"></i> Your Saved Preferences</h3>
              <span class="badge badge-info"><?= count($preferenceRows) ?> items</span>
            </div>

            <?php if (empty($preferenceRows)): ?>
              <p class="pref-meta">No preferences saved yet.</p>
            <?php else: ?>
              <div class="table-container" style="max-height:260px;overflow:auto;">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Regime</th>
                      <th>Localisation</th>
                      <th>Date</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($preferenceRows as $row): ?>
                      <?php $isActiveRow = $selectedPreference && (int)$selectedPreference['id_pref'] === (int)$row['id_pref']; ?>
                      <tr class="pref-row-click<?= $isActiveRow ? ' pref-row-selected' : '' ?>" data-href="preferences.php?id_user=<?= (int)$idUser ?>&selected_id=<?= (int)$row['id_pref'] ?>#saved-preferences-card">
                        <td><?= (int)$row['id_pref'] ?></td>
                        <td><?= htmlspecialchars((string)$row['regime_alimentaire'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$row['localisation'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$row['date_demande'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><a class="btn btn-outline btn-sm" href="preferences.php?id_user=<?= (int)$idUser ?>&edit_id=<?= (int)$row['id_pref'] ?>&selected_id=<?= (int)$row['id_pref'] ?>#pref-form-card">Edit</a></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>

            <hr style="margin:18px 0;border:none;border-top:1px solid rgba(255,255,255,0.08);">

            <h3 class="card-title" style="margin-bottom:12px;"><i class="fa-solid fa-ranking-star"></i> Matching IA v1</h3>
            <?php if (!$matchingResult['ok']): ?>
              <p class="pref-meta">Matching unavailable right now.</p>
            <?php elseif ($matchingResult['status'] === 'no_preference'): ?>
              <p class="pref-meta">No saved preference yet. Add one to get ranked offers.</p>
            <?php elseif (empty($matchingResult['matches'])): ?>
              <p class="pref-meta">No offers match your current restrictions.</p>
            <?php else: ?>
              <div class="table-container" style="max-height:230px;overflow:auto;">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Offer</th>
                      <th>Location</th>
                      <th>Pickup</th>
                      <th>Score</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($matchingResult['matches'] as $offer): ?>
                      <tr>
                        <td><?= htmlspecialchars((string)$offer['title'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$offer['localisation'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$offer['pickup_start'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars((string)$offer['pickup_end'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="badge badge-primary"><?= (int)$offer['score'] ?></span></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>

            <p class="pref-meta" style="margin-top:14px;">
              <a href="../Controller/MatchingController.php?action=student_matches&id_user=<?= (int)$idUser ?>" target="_blank">View matching JSON</a>
            </p>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="../js/app.js"></script>
  <script src="../js/components.js"></script>
  <script src="../js/student-preferences-validation.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      if (!App.requireAuth(['student'])) return;

      const user = App.getCurrentUser();
      const match = String((user && user.id) ? user.id : '').match(/(\d+)$/);
      if (match) {
        const resolvedId = parseInt(match[1], 10);
        const url = new URL(window.location.href);
        const queryId = parseInt(url.searchParams.get('id_user') || '0', 10);

        if (!queryId || queryId !== resolvedId) {
          url.searchParams.set('id_user', String(resolvedId));
          url.searchParams.delete('edit_id');
          url.searchParams.delete('selected_id');
          url.hash = '';
          window.location.replace(url.toString());
          return;
        }

        document.querySelectorAll('.js-id-user').forEach((input) => {
          input.value = String(resolvedId);
        });
      }

      document.querySelectorAll('.tag input[type="checkbox"]').forEach((checkbox) => {
        const tag = checkbox.closest('.tag');
        tag.classList.toggle('selected', checkbox.checked);
        checkbox.addEventListener('change', () => {
          tag.classList.toggle('selected', checkbox.checked);
        });
      });

      document.querySelectorAll('.pref-row-click').forEach((row) => {
        row.addEventListener('click', (event) => {
          if (event.target.closest('a') || event.target.closest('button') || event.target.closest('form')) {
            return;
          }
          const href = row.getAttribute('data-href');
          if (href) {
            window.location.href = href;
          }
        });
      });

    });
  </script>
</body>
</html>
