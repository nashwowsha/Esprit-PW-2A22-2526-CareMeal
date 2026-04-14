<?php
require_once __DIR__ . '/../../../Controller/PreferenceController.php';
require_once __DIR__ . '/../../../Controller/MatchingController.php';

$idUser = isset($_GET['id_user']) ? (int)$_GET['id_user'] : 1;
if ($idUser <= 0) {
    $idUser = 1;
}

$status = $_GET['status'] ?? '';
$preferenceController = new PreferenceController();
$preference = $preferenceController->getByUserId($idUser);
$selectedRegimes = [];

if ($preference && !empty($preference['regime_alimentaire'])) {
    $selectedRegimes = array_values(array_filter(array_map('trim', explode(',', $preference['regime_alimentaire']))));
}

$allergiesValue = $preference['allergies'] ?? '';
$localisationValue = $preference['localisation'] ?? '';

$matchingController = new MatchingController();
$matchingResult = $matchingController->getRankedOffersForUser($idUser);

$statusMessages = [
    'success_saved' => ['type' => 'success', 'text' => 'Preference saved successfully.'],
    'success_deleted' => ['type' => 'success', 'text' => 'Preference deleted successfully.'],
    'error_missing_fields' => ['type' => 'error', 'text' => 'Please fill all required fields.'],
    'error_db' => ['type' => 'error', 'text' => 'Database error occurred. Please retry.'],
    'error_forbidden' => ['type' => 'error', 'text' => 'You cannot delete another student preference.'],
    'error_not_found' => ['type' => 'error', 'text' => 'Preference record not found.'],
];

$regimeOptions = ['halal', 'vegetarien', 'vegan', 'sans-gluten', 'bio', 'sans-lactose', 'budget', 'equilibre'];

function isRegimeChecked($regime, $selectedRegimes) {
    return in_array($regime, $selectedRegimes, true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Preferences - CareMeal</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 0; background: #f6f7fb; color: #1b1b1b; }
    .container { max-width: 980px; margin: 30px auto; background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
    h1, h2 { margin-top: 0; }
    .row { display: flex; gap: 16px; flex-wrap: wrap; }
    .field { margin-bottom: 14px; flex: 1; min-width: 220px; }
    label { display: block; font-weight: 600; margin-bottom: 6px; }
    input[type="text"], input[type="number"], textarea { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cdd2da; box-sizing: border-box; }
    textarea { min-height: 90px; }
    .regimes { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 8px; margin: 10px 0 16px; }
    .regime-item { background: #f2f4f8; border: 1px solid #dde3ed; border-radius: 8px; padding: 8px 10px; }
    .actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
    button { border: none; border-radius: 8px; padding: 10px 14px; cursor: pointer; font-weight: 600; }
    .btn-primary { background: #0f62fe; color: #fff; }
    .btn-danger { background: #d7263d; color: #fff; }
    .btn-secondary { background: #e5e9f2; color: #222; }
    .status { padding: 10px 12px; border-radius: 8px; margin-bottom: 14px; }
    .status.success { background: #e6f7ea; color: #146c2e; border: 1px solid #b5e2bf; }
    .status.error { background: #ffebee; color: #9c1d1d; border: 1px solid #f2b8bf; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th, td { border-bottom: 1px solid #e5e8ee; padding: 10px; text-align: left; }
    th { background: #f8f9fc; }
    .meta { color: #5e6573; font-size: 14px; }
    .top-links { display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
    a { color: #0f62fe; text-decoration: none; }
  </style>
</head>
<body>
  <div class="container">
    <div class="top-links">
      <div>
        <h1>Student Preference CRUD</h1>
        <p class="meta">One active preference per student (upsert by <code>id_user</code>).</p>
      </div>
      <div>
        <a href="../../../BackOffice/admin/preferences.php">Go to Admin Preferences</a>
      </div>
    </div>

    <?php if (isset($statusMessages[$status])): ?>
      <div class="status <?= htmlspecialchars($statusMessages[$status]['type'], ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars($statusMessages[$status]['text'], ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <form method="get" action="">
      <div class="row">
        <div class="field" style="max-width: 220px;">
          <label for="id_user_lookup">Student ID (manual test mode)</label>
          <input type="number" id="id_user_lookup" name="id_user" min="1" value="<?= (int)$idUser ?>">
        </div>
        <div class="field" style="align-self: end; max-width: 220px;">
          <button type="submit" class="btn-secondary">Load Student</button>
        </div>
      </div>
    </form>

    <form method="post" action="../../../Controller/preference.php?action=student_upsert">
      <input type="hidden" name="id_user" value="<?= (int)$idUser ?>">

      <label>Dietary regimes (required)</label>
      <div class="regimes">
        <?php foreach ($regimeOptions as $regime): ?>
          <label class="regime-item">
            <input type="checkbox" name="regimes[]" value="<?= htmlspecialchars($regime, ENT_QUOTES, 'UTF-8') ?>"
              <?= isRegimeChecked($regime, $selectedRegimes) ? 'checked' : '' ?>>
            <?= htmlspecialchars(ucfirst($regime), ENT_QUOTES, 'UTF-8') ?>
          </label>
        <?php endforeach; ?>
      </div>

      <div class="row">
        <div class="field">
          <label for="allergies">Allergies (required, comma-separated)</label>
          <textarea id="allergies" name="allergies" required><?= htmlspecialchars($allergiesValue, ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <div class="field">
          <label for="localisation">Location (required, exact matching)</label>
          <input type="text" id="localisation" name="localisation" required value="<?= htmlspecialchars($localisationValue, ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>

      <div class="actions">
        <button type="submit" class="btn-primary">Save Preference</button>
      </div>
    </form>

    <?php if ($preference): ?>
      <hr style="margin: 22px 0; border: none; border-top: 1px solid #e8ebf1;">
      <h2>Current Preference Record</h2>
      <p class="meta">
        <strong>id_pref:</strong> <?= (int)$preference['id_pref'] ?> |
        <strong>id_user:</strong> <?= (int)$preference['id_user'] ?> |
        <strong>date_demande:</strong> <?= htmlspecialchars((string)$preference['date_demande'], ENT_QUOTES, 'UTF-8') ?>
      </p>

      <form method="post" action="../../../Controller/preference.php?action=student_delete" onsubmit="return confirm('Delete this preference?');">
        <input type="hidden" name="id_pref" value="<?= (int)$preference['id_pref'] ?>">
        <input type="hidden" name="id_user" value="<?= (int)$idUser ?>">
        <button type="submit" class="btn-danger">Delete My Preference</button>
      </form>
    <?php endif; ?>

    <hr style="margin: 26px 0; border: none; border-top: 1px solid #e8ebf1;">
    <h2>Matching IA v1 - Ranked Offers</h2>

    <?php if (!$matchingResult['ok']): ?>
      <div class="status error">Unable to compute matches (invalid user or system error).</div>
    <?php elseif ($matchingResult['status'] === 'no_preference'): ?>
      <div class="status error">No preference found for this student yet. Save one to see matches.</div>
    <?php elseif (empty($matchingResult['matches'])): ?>
      <div class="status error">No matching offers found for current preference constraints.</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Offer</th>
            <th>Location</th>
            <th>Pickup Window</th>
            <th>Price</th>
            <th>Score</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($matchingResult['matches'] as $offer): ?>
            <tr>
              <td><?= htmlspecialchars((string)$offer['title'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string)$offer['localisation'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string)$offer['pickup_start'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars((string)$offer['pickup_end'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= number_format((float)$offer['price'], 2) ?> DT</td>
              <td><?= (int)$offer['score'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <p class="meta" style="margin-top: 14px;">
      JSON endpoint: <a href="../../../Controller/MatchingController.php?action=student_matches&id_user=<?= (int)$idUser ?>" target="_blank">View raw matching payload</a>
    </p>
  </div>
</body>
</html>


