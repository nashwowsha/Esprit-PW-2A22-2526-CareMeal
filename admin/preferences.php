<?php
require_once __DIR__ . '/../Controller/PreferenceController.php';

$controller = new PreferenceController();
$rows = $controller->getAllForAdmin();
$status = $_GET['status'] ?? '';

$editId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$editRow = $editId > 0 ? $controller->getById($editId) : null;

$formAction = $editRow ? 'admin_update' : 'admin_create';
$formTitle = $editRow ? 'Edit Preference #' . (int)$editRow['id_pref'] : 'Create Preference';

$statusMessages = [
    'success_created' => ['class' => 'success', 'text' => 'Preference created successfully.'],
    'success_updated' => ['class' => 'success', 'text' => 'Preference updated successfully.'],
    'success_deleted' => ['class' => 'success', 'text' => 'Preference deleted successfully.'],
    'error_validation' => ['class' => 'error', 'text' => 'Validation failed. Please check your input values.'],
    'error_duplicate_preference' => ['class' => 'error', 'text' => 'This preference already exists for this user.'],
    'error_missing_fields' => ['class' => 'error', 'text' => 'Missing required fields.'],
    'error_not_found' => ['class' => 'error', 'text' => 'Preference not found.'],
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
  <meta name="description" content="Gestion admin des preferences CareMeal.">
  <title>Admin Preferences - CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <style>
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
    .pref-input {
      width: 100%;
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid var(--color-dark-border);
      border-radius: var(--radius-md);
      color: var(--color-white);
      padding: 11px 12px;
      margin-top: 6px;
      margin-bottom: 14px;
    }
    .pref-input::placeholder { color: var(--color-text-muted); }
    .pref-textarea { min-height: 90px; resize: vertical; }
    .pref-actions { display:flex; gap:8px; flex-wrap:wrap; }
    .pref-muted { color: var(--color-text-muted); font-size: 0.82rem; }
    .pref-layout {
      display: grid;
      grid-template-columns: minmax(320px, 380px) minmax(0, 1fr);
      gap: 18px;
    }
    .pref-client-error {
      color: #842029;
      background: rgba(220, 53, 69, 0.16);
      border: 1px solid rgba(220, 53, 69, 0.4);
      border-radius: var(--radius-md);
      padding: 10px 12px;
      margin-bottom: 14px;
      font-size: 0.88rem;
      display: none;
    }
    .pref-input-error {
      border-color: rgba(220, 53, 69, 0.7) !important;
      box-shadow: 0 0 0 1px rgba(220, 53, 69, 0.25);
    }
    @media (max-width: 1100px) {
      .pref-layout { grid-template-columns: 1fr; }
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
          <div class="sidebar-section-title">Administration</div>
          <a href="dashboard.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-chart-column"></i></span> Vue globale</a>
          <a href="users.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-users"></i></span> Utilisateurs</a>
          <a href="partners.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-store"></i></span> Partenaires</a>
          <a href="preferences.php" class="sidebar-link active"><span class="link-icon"><i class="fa-solid fa-sliders"></i></span> Preferences</a>
          <a href="events.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> Evenements</a>
          <a href="logs.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-clipboard-list"></i></span> Logs d'activite</a>
        </div>
      </nav>
      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="avatar" id="sidebar-user-avatar" style="background:linear-gradient(135deg,#EF4444,#F87171);">A</div>
          <div class="sidebar-user-info">
            <div class="sidebar-user-name" id="sidebar-user-name">Admin</div>
            <div class="sidebar-user-role" id="sidebar-user-role">Administrateur</div>
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
          <div class="page-title"><h2>Preferences Management</h2><p>Full CRUD with id_pref and id_user tracking</p></div>
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

        <div class="pref-layout">
          <div class="card animate-fade-in-up">
            <div class="card-header">
              <h3 class="card-title"><i class="fa-solid fa-pen-to-square"></i> <?= htmlspecialchars($formTitle, ENT_QUOTES, 'UTF-8') ?></h3>
            </div>

            <form method="post" action="../Controller/preference.php?action=<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>" id="admin-pref-form" novalidate>
              <?php if ($editRow): ?>
                <input type="hidden" name="id_pref" value="<?= (int)$editRow['id_pref'] ?>">
              <?php endif; ?>

              <div id="admin-pref-error" class="pref-client-error"></div>

              <label for="id_user">User ID (required)</label>
              <input id="id_user" name="id_user" class="pref-input" type="text" value="<?= htmlspecialchars((string)($editRow['id_user'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

              <label for="regime_alimentaire">Regime alimentaire (required)</label>
              <input id="regime_alimentaire" name="regime_alimentaire" class="pref-input" type="text" placeholder="halal, sans-gluten" value="<?= htmlspecialchars((string)($editRow['regime_alimentaire'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

              <label for="allergies">Allergies (required)</label>
              <textarea id="allergies" name="allergies" class="pref-input pref-textarea"><?= htmlspecialchars((string)($editRow['allergies'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>

              <label for="localisation">Localisation (required)</label>
              <input id="localisation" name="localisation" class="pref-input" type="text" value="<?= htmlspecialchars((string)($editRow['localisation'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

              <div class="pref-actions">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk"></i> <?= $editRow ? 'Update' : 'Create' ?></button>
                <?php if ($editRow): ?>
                  <a href="preferences.php" class="btn btn-outline btn-sm">Cancel</a>
                <?php endif; ?>
              </div>
            </form>
          </div>

          <div class="card animate-fade-in-up stagger-1">
            <div class="card-header" style="justify-content:space-between;align-items:center;">
              <h3 class="card-title"><i class="fa-solid fa-table"></i> All Preferences</h3>
              <span class="badge badge-info"><?= count($rows) ?> rows</span>
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
                    <th>Date Demande</th>
                    <th>User</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($rows)): ?>
                    <tr><td colspan="8" class="pref-muted">No preference records.</td></tr>
                  <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                      <tr>
                        <td><?= (int)$row['id_pref'] ?></td>
                        <td><?= (int)$row['id_user'] ?></td>
                        <td><?= htmlspecialchars((string)$row['regime_alimentaire'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$row['allergies'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$row['localisation'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$row['date_demande'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                          <strong><?= htmlspecialchars((string)($row['user_nom'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') ?></strong><br>
                          <span class="pref-muted"><?= htmlspecialchars((string)($row['user_email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td>
                          <div class="pref-actions">
                            <a class="btn btn-outline btn-sm" href="preferences.php?edit_id=<?= (int)$row['id_pref'] ?>">Edit</a>
                            <form method="post" action="../Controller/preference.php?action=admin_delete" style="display:inline;" onsubmit="return confirm('Delete preference #<?= (int)$row['id_pref'] ?>?');">
                              <input type="hidden" name="id_pref" value="<?= (int)$row['id_pref'] ?>">
                              <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="../js/app.js"></script>
  <script src="../js/components.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      App.requireAuth(['admin']);

      const form = document.getElementById('admin-pref-form');
      if (!form) return;

      const errorBox = document.getElementById('admin-pref-error');
      const idUserField = document.getElementById('id_user');
      const regimeField = document.getElementById('regime_alimentaire');
      const allergiesField = document.getElementById('allergies');
      const localisationField = document.getElementById('localisation');
      const allergyTokenPattern = /^[A-Za-z\u00C0-\u024F\s'-]+$/u;

      function parseCommaList(value) {
        return String(value || '')
          .split(/[;,]+/)
          .map((part) => part.trim())
          .filter((part) => part.length > 0);
      }

      function clearErrors() {
        [idUserField, regimeField, allergiesField, localisationField].forEach((field) => {
          if (field) field.classList.remove('pref-input-error');
        });
      }

      form.addEventListener('submit', (event) => {
        clearErrors();
        const errors = [];

        const idUser = (idUserField ? idUserField.value : '').trim();
        const regime = (regimeField ? regimeField.value : '').trim();
        const allergies = (allergiesField ? allergiesField.value : '').trim();
        const localisation = (localisationField ? localisationField.value : '').trim();
        const regimeItems = parseCommaList(regime);
        const allergyItems = parseCommaList(allergies);

        if (!/^\d+$/.test(idUser) || parseInt(idUser, 10) <= 0) {
          errors.push('User ID must be a positive integer.');
          if (idUserField) idUserField.classList.add('pref-input-error');
        }

        if (regime.length === 0) {
          errors.push('Regime alimentaire is required.');
          if (regimeField) regimeField.classList.add('pref-input-error');
        }

        if (allergies.length === 0) {
          errors.push('Allergies field is required.');
          if (allergiesField) allergiesField.classList.add('pref-input-error');
        }

        if (/\d/.test(allergies)) {
          errors.push('Allergies cannot contain numbers.');
          if (allergiesField) allergiesField.classList.add('pref-input-error');
        }

        if (regimeItems.length > 4) {
          errors.push('At most 4 regime items are allowed.');
          if (regimeField) regimeField.classList.add('pref-input-error');
        }

        if (allergies.length > 0) {
          if (allergyItems.length === 0) {
            errors.push('Add at least one valid allergy item.');
            if (allergiesField) allergiesField.classList.add('pref-input-error');
          }

          const seenAllergies = new Set();
          for (const token of allergyItems) {
            const normalized = token.toLocaleLowerCase();
            if (seenAllergies.has(normalized)) {
              errors.push('Duplicate allergy items are not allowed.');
              if (allergiesField) allergiesField.classList.add('pref-input-error');
              break;
            }
            seenAllergies.add(normalized);

            if (!allergyTokenPattern.test(token)) {
              errors.push('Each allergy must contain only letters, spaces, apostrophe or hyphen.');
              if (allergiesField) allergiesField.classList.add('pref-input-error');
              break;
            }
          }
        }

        if (localisation.length === 0) {
          errors.push('Localisation field is required.');
          if (localisationField) localisationField.classList.add('pref-input-error');
        }

        if (regime.length > 1000) {
          errors.push('Regime alimentaire must be 1000 characters or less.');
          if (regimeField) regimeField.classList.add('pref-input-error');
        }

        if (allergies.length > 1000) {
          errors.push('Allergies must be 1000 characters or less.');
          if (allergiesField) allergiesField.classList.add('pref-input-error');
        }

        if (localisation.length > 1000) {
          errors.push('Localisation must be 1000 characters or less.');
          if (localisationField) localisationField.classList.add('pref-input-error');
        }

        if (errors.length > 0) {
          event.preventDefault();
          if (errorBox) {
            errorBox.innerHTML = errors.join('<br>');
            errorBox.style.display = 'block';
          }
          return;
        }

        if (errorBox) {
          errorBox.innerHTML = '';
          errorBox.style.display = 'none';
        }
      });
    });
  </script>
</body>
</html>
