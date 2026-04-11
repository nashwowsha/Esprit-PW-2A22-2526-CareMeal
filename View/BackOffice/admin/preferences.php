<?php
require_once __DIR__ . '/../../../Controller/PreferenceController.php';
require_once __DIR__ . '/../../../Model/Preference.php';

$controller = new PreferenceController();
$rows = $controller->getAllForAdmin();
$status = $_GET['status'] ?? '';

$editId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$editRow = null;
if ($editId > 0) {
    $editRow = Preference::getById($editId);
}

$statusMessages = [
    'success_created' => ['type' => 'success', 'text' => 'Preference created successfully.'],
    'success_updated' => ['type' => 'success', 'text' => 'Preference updated successfully.'],
    'success_deleted' => ['type' => 'success', 'text' => 'Preference deleted successfully.'],
    'error_missing_fields' => ['type' => 'error', 'text' => 'Missing required fields.'],
    'error_not_found' => ['type' => 'error', 'text' => 'Preference not found.'],
    'error_user_has_preference' => ['type' => 'error', 'text' => 'This user already has an active preference. Edit that row instead.'],
    'error_db' => ['type' => 'error', 'text' => 'Database error occurred.'],
    'error_unknown_action' => ['type' => 'error', 'text' => 'Unknown controller action.'],
    'error_invalid_request' => ['type' => 'error', 'text' => 'Invalid request.'],
];

$formAction = $editRow ? 'admin_update' : 'admin_create';
$formTitle = $editRow ? 'Edit Preference #' . (int)$editRow['id_pref'] : 'Create Preference';

$inputIdUser = $editRow['id_user'] ?? '';
$inputRegime = $editRow['regime_alimentaire'] ?? '';
$inputAllergies = $editRow['allergies'] ?? '';
$inputLocation = $editRow['localisation'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Preferences - CareMeal</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 0; background: #f6f7fb; color: #1b1b1b; }
    .container { max-width: 1180px; margin: 30px auto; background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
    h1, h2 { margin-top: 0; }
    .meta { color: #5e6573; font-size: 14px; }
    .status { padding: 10px 12px; border-radius: 8px; margin-bottom: 14px; }
    .status.success { background: #e6f7ea; color: #146c2e; border: 1px solid #b5e2bf; }
    .status.error { background: #ffebee; color: #9c1d1d; border: 1px solid #f2b8bf; }
    .layout { display: grid; grid-template-columns: 360px 1fr; gap: 20px; }
    .card { border: 1px solid #e2e7f0; border-radius: 10px; padding: 16px; background: #fff; }
    label { display: block; font-weight: 600; margin-bottom: 6px; }
    input[type="text"], input[type="number"], textarea { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cdd2da; box-sizing: border-box; margin-bottom: 12px; }
    textarea { min-height: 90px; }
    .actions { display: flex; gap: 8px; flex-wrap: wrap; }
    button, .btn { border: none; border-radius: 8px; padding: 10px 14px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; }
    .btn-primary { background: #0f62fe; color: #fff; }
    .btn-secondary { background: #e5e9f2; color: #222; }
    .btn-danger { background: #d7263d; color: #fff; }
    table { width: 100%; border-collapse: collapse; font-size: 14px; }
    th, td { border-bottom: 1px solid #e5e8ee; padding: 10px; text-align: left; vertical-align: top; }
    th { background: #f8f9fc; }
    .toolbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
    .nowrap { white-space: nowrap; }
    .muted { color: #6a7180; }
    @media (max-width: 960px) {
      .layout { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="toolbar">
      <div>
        <h1>Admin Preference CRUD</h1>
        <p class="meta">Full CRUD with tracking fields <code>id_pref</code> and <code>id_user</code>.</p>
      </div>
      <div class="actions">
        <a class="btn btn-secondary" href="preferences.php">Reset Form</a>
        <a class="btn btn-secondary" href="../../FrontOffice/student/preferences.php">Open Student View</a>
      </div>
    </div>

    <?php if (isset($statusMessages[$status])): ?>
      <div class="status <?= htmlspecialchars($statusMessages[$status]['type'], ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars($statusMessages[$status]['text'], ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <div class="layout">
      <div class="card">
        <h2><?= htmlspecialchars($formTitle, ENT_QUOTES, 'UTF-8') ?></h2>
        <form method="post" action="../../../Controller/preference.php?action=<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>">
          <?php if ($editRow): ?>
            <input type="hidden" name="id_pref" value="<?= (int)$editRow['id_pref'] ?>">
          <?php endif; ?>

          <label for="id_user">User ID (required)</label>
          <input type="number" id="id_user" name="id_user" min="1" required value="<?= htmlspecialchars((string)$inputIdUser, ENT_QUOTES, 'UTF-8') ?>">

          <label for="regime_alimentaire">Regime alimentaire (required)</label>
          <input type="text" id="regime_alimentaire" name="regime_alimentaire" required placeholder="halal, sans-gluten" value="<?= htmlspecialchars((string)$inputRegime, ENT_QUOTES, 'UTF-8') ?>">

          <label for="allergies">Allergies (required)</label>
          <textarea id="allergies" name="allergies" required><?= htmlspecialchars((string)$inputAllergies, ENT_QUOTES, 'UTF-8') ?></textarea>

          <label for="localisation">Localisation (required)</label>
          <input type="text" id="localisation" name="localisation" required value="<?= htmlspecialchars((string)$inputLocation, ENT_QUOTES, 'UTF-8') ?>">

          <div class="actions">
            <button type="submit" class="btn-primary"><?= $editRow ? 'Update Preference' : 'Create Preference' ?></button>
            <?php if ($editRow): ?>
              <a class="btn btn-secondary" href="preferences.php">Cancel Edit</a>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <div class="card">
        <h2>All Preferences</h2>
        <p class="meta">Total records: <?= count($rows) ?></p>
        <table>
          <thead>
            <tr>
              <th>ID Pref</th>
              <th>ID User</th>
              <th>Regime</th>
              <th>Allergies</th>
              <th>Localisation</th>
              <th>Date Demande</th>
              <th>User Info</th>
              <th class="nowrap">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rows)): ?>
              <tr>
                <td colspan="8" class="muted">No preference records yet.</td>
              </tr>
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
                    <div><strong><?= htmlspecialchars((string)($row['user_nom'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                    <div class="muted"><?= htmlspecialchars((string)($row['user_email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                  </td>
                  <td class="nowrap">
                    <a class="btn btn-secondary" href="preferences.php?edit_id=<?= (int)$row['id_pref'] ?>">Edit</a>
                    <form method="post" action="../../../Controller/preference.php?action=admin_delete" style="display:inline;" onsubmit="return confirm('Delete preference #<?= (int)$row['id_pref'] ?>?');">
                      <input type="hidden" name="id_pref" value="<?= (int)$row['id_pref'] ?>">
                      <button type="submit" class="btn-danger">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>

