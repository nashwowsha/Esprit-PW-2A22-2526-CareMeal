<?php
require_once __DIR__ . '/../View/session_check.php';
require_once __DIR__ . '/../Controller/MatchingController.php';

$idUser = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($idUser <= 0) {
    header('Location: ../View/FrontOffice/login.php');
    exit;
}

$idPref = isset($_GET['id_pref']) ? (int)$_GET['id_pref'] : 0;
$priceMode = isset($_GET['price_mode']) ? strtolower(trim((string)$_GET['price_mode'])) : 'all';
if ($priceMode === 'both') {
    $priceMode = 'all';
}
if (!in_array($priceMode, ['all', 'free', 'paid'], true)) {
    $priceMode = 'all';
}
$sortBy = isset($_GET['sort_by']) ? strtolower(trim((string)$_GET['sort_by'])) : 'score';
if (!in_array($sortBy, ['score', 'location', 'name'], true)) {
    $sortBy = 'score';
}
$safetyMode = isset($_GET['safety_mode']) ? strtolower(trim((string)$_GET['safety_mode'])) : 'strict';
if (!in_array($safetyMode, ['strict', 'souple'], true)) {
    $safetyMode = 'strict';
}

$matchingController = new MatchingController();
$matchingResult = [
    'ok' => false,
    'status' => 'error_invalid_preference',
    'restaurants' => [],
    'preference' => null,
];
if ($idPref > 0) {
    $matchingResult = $matchingController->getMatchedRestaurantsForPreference($idUser, $idPref, [
        'price_mode' => $priceMode,
        'sort_by' => $sortBy,
        'safety_mode' => $safetyMode,
    ]);
}

$statusMessages = [
    'success' => ['class' => 'success', 'text' => 'Matching completed successfully.'],
    'no_match' => ['class' => 'error', 'text' => 'No restaurant matches this preference right now.'],
    'analysis_required' => ['class' => 'error', 'text' => 'Matching indisponible: des meals sont en attente d analyse IA.'],
    'error_invalid_user' => ['class' => 'error', 'text' => 'Invalid user.'],
    'error_invalid_preference' => ['class' => 'error', 'text' => 'Select a preference first before launching matching.'],
    'error_preference_not_found' => ['class' => 'error', 'text' => 'Selected preference was not found.'],
    'error_forbidden_preference' => ['class' => 'error', 'text' => 'This preference does not belong to the current student.'],
];

$selectedPreference = $matchingResult['preference'] ?? null;
$matchedRestaurants = $matchingResult['restaurants'] ?? [];
$analysisSummary = is_array($matchingResult['analysis_summary'] ?? null) ? $matchingResult['analysis_summary'] : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Resultats du matching restaurants selon votre preference.">
  <title>Matching Restaurants - CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="../css/theme-fix.css">
  <style>
    .alert-box {
      border-radius: var(--radius-md);
      padding: 12px 14px;
      margin-bottom: 16px;
      border: 1px solid transparent;
      font-size: 0.9rem;
    }
    .alert-box.success {
      color: #0f5132;
      background: rgba(25, 135, 84, 0.18);
      border-color: rgba(25, 135, 84, 0.4);
    }
    .alert-box.error {
      color: #842029;
      background: rgba(220, 53, 69, 0.16);
      border-color: rgba(220, 53, 69, 0.4);
    }
    .matching-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 16px;
    }
    .match-card {
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid var(--color-dark-border);
      border-radius: var(--radius-lg);
      overflow: hidden;
      display: flex;
      flex-direction: column;
    }
    .match-img {
      width: 100%;
      height: 160px;
      object-fit: cover;
      background: #0d1b2a;
      border-bottom: 1px solid var(--color-dark-border);
    }
    .match-body {
      padding: 12px;
      display: grid;
      gap: 8px;
    }
    .match-title {
      margin: 0;
      font-size: 1rem;
      color: var(--color-white);
    }
    .match-meta {
      color: var(--color-text-muted);
      font-size: 0.83rem;
      margin: 0;
    }
    .match-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }
    .filter-bar {
      display: flex;
      gap: 10px;
      align-items: end;
      flex-wrap: wrap;
      margin-bottom: 14px;
    }
    .filter-group {
      display: grid;
      gap: 6px;
      min-width: 180px;
    }
    .filter-select {
      width: 100%;
      background: rgba(255,255,255,.04);
      border: 1px solid var(--color-dark-border);
      border-radius: var(--radius-md);
      color: var(--color-white);
      padding: 10px 12px;
    }
    .filter-select option {
      color: #0f172a;
      background: #ffffff;
    }
    .filter-label {
      color: var(--color-text-muted);
      font-size: .8rem;
    }
    .matching-layout {
      display: grid;
      grid-template-columns: minmax(0, 1fr) 300px;
      gap: 14px;
      align-items: start;
    }
    .ai-assistant-card {
      position: sticky;
      top: 18px;
      border: 1px solid var(--color-dark-border);
      background: rgba(255,255,255,.03);
      border-radius: var(--radius-md);
      padding: 12px;
      display: grid;
      gap: 8px;
    }
    .ai-assistant-title {
      margin: 0;
      font-size: .95rem;
      color: var(--color-white);
    }
    .ai-assistant-meta {
      margin: 0;
      font-size: .83rem;
      color: var(--color-text-muted);
      line-height: 1.5;
    }
    .ai-assistant-list {
      margin: 0;
      padding-left: 18px;
      color: var(--color-text-muted);
      font-size: .82rem;
      line-height: 1.45;
    }
    @media (max-width: 1080px) {
      .matching-layout {
        grid-template-columns: 1fr;
      }
      .ai-assistant-card {
        position: static;
      }
    }

    /* Light-mode readability overrides */
    .match-card {
      background: #FFFFFF !important;
      border-color: #E2E8F0 !important;
    }
    .match-title {
      color: #0F172A !important;
    }
    .match-meta {
      color: #475569 !important;
    }
    .filter-select {
      background: #FFFFFF !important;
      color: #0F172A !important;
      border-color: #CBD5E1 !important;
    }
    .filter-label {
      color: #475569 !important;
    }
    .ai-assistant-card {
      background: #FFFFFF !important;
      border-color: #E2E8F0 !important;
      box-shadow: 0 4px 16px rgba(15, 23, 42, 0.08);
    }
    .ai-assistant-title,
    .ai-assistant-meta,
    .ai-assistant-list {
      color: #0F172A !important;
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
          <a href="preferences.php?id_user=<?= (int)$idUser ?>" class="sidebar-link active"><span class="link-icon"><i class="fa-solid fa-utensils"></i></span> Preferences</a>
          <a href="events.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> Evenements</a>
          <a href="mes_collectes.php?id_user=<?= (int)$idUser ?>" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-box"></i></span> Mes Collectes</a>
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
          <div class="page-title">
            <h2>Restaurants matches</h2>
            <p>Resultats classes selon la preference selectionnee</p>
          </div>
        </div>
        <div class="header-right">
          <button class="header-notification"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button>
          <div class="avatar avatar-sm" id="header-avatar">AA</div>
        </div>
      </header>

      <div class="page-content">
        <?php $status = (string)($matchingResult['status'] ?? ''); ?>
        <?php if (isset($statusMessages[$status])): ?>
          <div class="alert-box <?= htmlspecialchars((string)$statusMessages[$status]['class'], ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars((string)$statusMessages[$status]['text'], ENT_QUOTES, 'UTF-8') ?>
            <?php if ($status === 'analysis_required' && is_array($analysisSummary)): ?>
              <br>
              <span style="display:inline-block;margin-top:6px;">
                Pending: <?= (int)($analysisSummary['pending_meals_count'] ?? 0) ?> |
                Unknown: <?= (int)($analysisSummary['unknown_meals_count'] ?? 0) ?> |
                Total meals: <?= (int)($analysisSummary['total_meals_count'] ?? 0) ?>
              </span>
              <?php $pendingSamples = is_array($analysisSummary['pending_samples'] ?? null) ? $analysisSummary['pending_samples'] : []; ?>
              <?php if (!empty($pendingSamples)): ?>
                <br>
                <span style="display:inline-block;margin-top:4px;">
                  Exemples en attente:
                  <?php
                    $sampleTexts = [];
                    foreach ($pendingSamples as $sample) {
                        if (!is_array($sample)) continue;
                        $sampleTexts[] = trim((string)($sample['restaurant'] ?? '')) . ' / ' . trim((string)($sample['meal_name'] ?? ''));
                    }
                    echo htmlspecialchars(implode(' | ', array_slice($sampleTexts, 0, 4)), ENT_QUOTES, 'UTF-8');
                  ?>
                </span>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <div class="card" style="margin-bottom:16px;">
          <div class="card-header" style="justify-content:space-between;align-items:center;">
            <h3 class="card-title"><i class="fa-solid fa-filter"></i> Preference selectionnee</h3>
            <a class="btn btn-outline btn-sm" href="preferences.php?id_user=<?= (int)$idUser ?>&selected_id=<?= (int)$idPref ?>#saved-preferences-card">Back to preferences</a>
          </div>
          <?php if ($selectedPreference): ?>
            <p class="match-meta"><strong>ID Preference:</strong> #<?= (int)$selectedPreference['id_pref'] ?></p>
            <p class="match-meta"><strong>Regime:</strong> <?= htmlspecialchars((string)$selectedPreference['regime_alimentaire'], ENT_QUOTES, 'UTF-8') ?></p>
            <p class="match-meta"><strong>Allergies:</strong> <?= htmlspecialchars((string)$selectedPreference['allergies'], ENT_QUOTES, 'UTF-8') ?></p>
            <p class="match-meta"><strong>Localisation:</strong> <?= htmlspecialchars((string)$selectedPreference['localisation'], ENT_QUOTES, 'UTF-8') ?></p>
            <p class="match-meta"><strong>Mode securite allergenes:</strong> <?= $safetyMode === 'souple' ? 'Souple' : 'Strict' ?></p>
          <?php else: ?>
            <p class="match-meta">Aucune preference selectionnee.</p>
          <?php endif; ?>
        </div>

        <div class="card">
          <div class="card-header" style="justify-content:space-between;align-items:center;">
            <h3 class="card-title"><i class="fa-solid fa-store"></i> Restaurants compatibles</h3>
            <span class="badge badge-info"><?= count($matchedRestaurants) ?> results</span>
          </div>

          <form method="get" action="matching.php" class="filter-bar">
            <input type="hidden" name="id_user" value="<?= (int)$idUser ?>">
            <input type="hidden" name="id_pref" value="<?= (int)$idPref ?>">
            <div class="filter-group">
              <label for="price_mode" class="filter-label">Filter prix</label>
              <select id="price_mode" name="price_mode" class="filter-select">
                <option value="all"<?= $priceMode === 'all' ? ' selected' : '' ?>>Free + Paid</option>
                <option value="free"<?= $priceMode === 'free' ? ' selected' : '' ?>>Free only</option>
                <option value="paid"<?= $priceMode === 'paid' ? ' selected' : '' ?>>Paid only</option>
              </select>
            </div>
            <div class="filter-group">
              <label for="sort_by" class="filter-label">Tri</label>
              <select id="sort_by" name="sort_by" class="filter-select">
                <option value="score"<?= $sortBy === 'score' ? ' selected' : '' ?>>Compatibilite globale</option>
                <option value="location"<?= $sortBy === 'location' ? ' selected' : '' ?>>Proximite la plus proche</option>
                <option value="name"<?= $sortBy === 'name' ? ' selected' : '' ?>>Name A-Z</option>
              </select>
            </div>
            <div class="filter-group">
              <label for="safety_mode" class="filter-label">Mode securite allergenes</label>
              <select id="safety_mode" name="safety_mode" class="filter-select">
                <option value="strict"<?= $safetyMode === 'strict' ? ' selected' : '' ?>>Strict (defaut)</option>
                <option value="souple"<?= $safetyMode === 'souple' ? ' selected' : '' ?>>Souple</option>
              </select>
            </div>
            <button type="submit" class="btn btn-outline btn-sm">Apply filters</button>
          </form>

          <?php if (!$matchingResult['ok'] || empty($matchedRestaurants)): ?>
            <?php if ($status === 'analysis_required'): ?>
              <p class="match-meta">Le matching est bloque tant que les meals en attente ne sont pas analyses.</p>
            <?php else: ?>
              <p class="match-meta">Aucun restaurant compatible pour le moment.</p>
            <?php endif; ?>
          <?php else: ?>
            <div class="matching-layout">
              <div class="matching-grid">
                <?php foreach ($matchedRestaurants as $restaurant): ?>
                  <?php
                    $imagePath = trim((string)($restaurant['image_path'] ?? ''));
                    if ($imagePath === '') {
                        $imagePath = 'assets/logo.png';
                    }
                    $assistantPayload = [
                        'nom' => (string)($restaurant['nom'] ?? ''),
                        'score' => (int)($restaurant['score'] ?? 0),
                        'distance_km' => isset($restaurant['distance_km']) ? $restaurant['distance_km'] : null,
                        'free' => !empty($restaurant['has_free_options']),
                        'paid' => !empty($restaurant['has_paid_options']),
                        'safe_meals' => (int)($restaurant['safe_meals_count'] ?? 0),
                        'ai_potential_conflict_meals' => (int)($restaurant['ai_potential_conflict_meals'] ?? 0),
                        'reasons' => (array)($restaurant['matching_brief']['score_reasons'] ?? []),
                        'engine' => (string)($restaurant['matching_brief']['engine'] ?? ''),
                        'safety_mode' => (string)($restaurant['matching_brief']['safety_mode'] ?? 'strict'),
                    ];
                  ?>
                  <article
                    class="match-card js-match-card"
                    data-ai='<?= htmlspecialchars(json_encode($assistantPayload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE), ENT_QUOTES, 'UTF-8') ?>'
                  >
                    <img class="match-img" src="../<?= htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') ?>" alt="restaurant image">
                    <div class="match-body">
                      <h4 class="match-title"><?= htmlspecialchars((string)$restaurant['nom'], ENT_QUOTES, 'UTF-8') ?></h4>
                      <p class="match-meta"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars((string)$restaurant['localisation'], ENT_QUOTES, 'UTF-8') ?></p>
                      <p class="match-meta"><i class="fa-solid fa-clock"></i> <?= htmlspecialchars((string)$restaurant['horaires'], ENT_QUOTES, 'UTF-8') ?></p>
                      <div class="match-tags">
                        <span class="badge badge-primary" title="Score personnalise: regime > allergies > proximite">Score <?= (int)$restaurant['score'] ?></span>
                        <span class="badge badge-info"><?= (int)$restaurant['matched_meals_count'] ?> meals</span>
                        <?php if (!empty($restaurant['matching_brief']['engine'])): ?>
                          <span class="badge badge-secondary"><?= htmlspecialchars((string)$restaurant['matching_brief']['engine'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                        <?php if (isset($restaurant['distance_km']) && $restaurant['distance_km'] !== null): ?>
                          <span class="badge badge-secondary"><?= number_format((float)$restaurant['distance_km'], 2) ?> km</span>
                        <?php endif; ?>
                        <?php if (!empty($restaurant['location_match'])): ?>
                          <span class="badge badge-success">Same location</span>
                        <?php endif; ?>
                        <?php if (!empty($restaurant['has_free_options'])): ?>
                          <span class="badge badge-warning">Free</span>
                        <?php endif; ?>
                        <?php if (!empty($restaurant['has_paid_options'])): ?>
                          <span class="badge badge-danger">Paid</span>
                        <?php endif; ?>
                        <?php if (!empty($restaurant['ai_potential_conflict_meals'])): ?>
                          <span class="badge badge-warning"><i class="fa-solid fa-triangle-exclamation"></i> IA risk <?= (int)$restaurant['ai_potential_conflict_meals'] ?></span>
                        <?php endif; ?>
                        <?php if (!empty($restaurant['regime_warning_meals'])): ?>
                          <span class="badge badge-info"><i class="fa-solid fa-circle-exclamation"></i> Regime warning <?= (int)$restaurant['regime_warning_meals'] ?></span>
                        <?php endif; ?>
                        <?php if (!empty($restaurant['unknown_meals_count'])): ?>
                          <span class="badge badge-danger"><i class="fa-solid fa-triangle-exclamation"></i> Unknown meals <?= (int)$restaurant['unknown_meals_count'] ?></span>
                        <?php endif; ?>
                      </div>
                      <?php if (!empty($restaurant['matching_brief']['score_reasons']) && is_array($restaurant['matching_brief']['score_reasons'])): ?>
                        <p class="match-meta"><strong>IA insight:</strong> <?= htmlspecialchars(implode(' | ', $restaurant['matching_brief']['score_reasons']), ENT_QUOTES, 'UTF-8') ?></p>
                      <?php endif; ?>
                      <a class="btn btn-primary btn-sm" href="matching_restaurant.php?id_user=<?= (int)$idUser ?>&id_pref=<?= (int)$idPref ?>&id_restaurant=<?= (int)$restaurant['id_restaurant'] ?>&safety_mode=<?= urlencode($safetyMode) ?>&price_mode=<?= urlencode($priceMode) ?>&sort_by=<?= urlencode($sortBy) ?>">
                        Voir meals
                      </a>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>

              <aside class="ai-assistant-card" id="matching-ai-assistant">
                <h4 class="ai-assistant-title"><i class="fa-solid fa-robot"></i> Assistant IA</h4>
                <p class="ai-assistant-meta" id="ai-restaurant-title">Survole un restaurant pour voir pourquoi il est recommande.</p>
                <p class="ai-assistant-meta" id="ai-restaurant-summary">Le scoring prend en compte le regime, la securite allergenes, et la proximite.</p>
                <ul class="ai-assistant-list" id="ai-reasons-list">
                  <li>Regime prioritaire</li>
                  <?php if ($safetyMode === 'strict'): ?>
                    <li>Conflits allergenes exclus</li>
                  <?php else: ?>
                    <li>Conflits allergenes visibles en warning</li>
                  <?php endif; ?>
                  <li>Distance utilisee pour le tri proximite</li>
                </ul>
              </aside>
            </div>
          <?php endif; ?>

          <?php if ($idPref > 0): ?>
            <p class="match-meta" style="margin-top:14px;">
              <a href="../Controller/MatchingController.php?action=student_restaurants&id_user=<?= (int)$idUser ?>&id_pref=<?= (int)$idPref ?>&price_mode=<?= urlencode($priceMode) ?>&sort_by=<?= urlencode($sortBy) ?>&safety_mode=<?= urlencode($safetyMode) ?>" target="_blank">
                View matching JSON
              </a>
            </p>
          <?php endif; ?>
        </div>
      </div>
    </main>
  </div>

  <script src="../js/app.js"></script>
  <script src="../js/components.js"></script>
  <script src="../js/student-layout.js"></script>
  <script src="../js/student-voice-assistant.js?v=20260508"></script>
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
          window.history.replaceState({}, '', url.toString());
        }
      }

      const assistantTitle = document.getElementById('ai-restaurant-title');
      const assistantSummary = document.getElementById('ai-restaurant-summary');
      const reasonsList = document.getElementById('ai-reasons-list');
      const cards = document.querySelectorAll('.js-match-card');

      function renderAssistant(payload) {
        if (!assistantTitle || !assistantSummary || !reasonsList || !payload) return;
        const freePaid = payload.free && payload.paid ? 'Free + Paid' : (payload.free ? 'Free only' : (payload.paid ? 'Paid only' : 'N/A'));
        assistantTitle.textContent = payload.nom + ' (score ' + String(payload.score || 0) + ')';
        assistantSummary.textContent =
          'Mode prix: ' + freePaid
          + ' | meals safe: ' + String(payload.safe_meals || 0)
          + ' | securite: ' + String(payload.safety_mode || 'strict')
          + ' | IA risques: ' + String(payload.ai_potential_conflict_meals || 0)
          + (payload.distance_km !== null && payload.distance_km !== undefined ? (' | distance: ' + String(payload.distance_km) + ' km') : '');
        reasonsList.innerHTML = '';
        const reasons = Array.isArray(payload.reasons) ? payload.reasons : [];
        if (!reasons.length) {
          const li = document.createElement('li');
          li.textContent = 'Aucune raison detaillee disponible.';
          reasonsList.appendChild(li);
          return;
        }
        reasons.forEach((r) => {
          const li = document.createElement('li');
          li.textContent = String(r);
          reasonsList.appendChild(li);
        });
      }

      cards.forEach((card) => {
        card.addEventListener('mouseenter', () => {
          try {
            const payload = JSON.parse(card.getAttribute('data-ai') || '{}');
            renderAssistant(payload);
          } catch (e) {}
        });
        card.addEventListener('focusin', () => {
          try {
            const payload = JSON.parse(card.getAttribute('data-ai') || '{}');
            renderAssistant(payload);
          } catch (e) {}
        });
      });
    });
  </script>
</body>
</html>
