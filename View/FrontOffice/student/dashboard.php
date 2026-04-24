<?php
require_once __DIR__ . '/../Controller/StudentController.php';
$studentController     = new StudentController();
$offers                = $studentController->getPublishedOffers();
$categoriesWithOffers  = $studentController->getPublishedOffersByCategory();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/dashboard.css">
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
          <div class="sidebar-section-title">Menu</div>
          <a href="dashboard.php" class="sidebar-link active"><span class="link-icon"><i class="fa-solid fa-house"></i></span> Accueil</a>
          <a href="profile.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-user"></i></span> Mon Profil</a>
          <a href="preferences.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-utensils"></i></span> Préférences</a>
          <a href="events.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> Événements</a>
          <a href="orders.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-box"></i></span> Mes Commandes</a>
        </div>
        <div class="sidebar-section">
          <div class="sidebar-section-title">Paramètres</div>
          <a href="settings.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-gear"></i></span> Paramètres</a>
        </div>
      </nav>

      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="avatar" id="sidebar-user-avatar">AA</div>
          <div class="sidebar-user-info">
            <div class="sidebar-user-name" id="sidebar-user-name">Utilisateur</div>
            <div class="sidebar-user-role" id="sidebar-user-role">Étudiant</div>
          </div>
          <button class="sidebar-logout" data-action="logout" title="Déconnexion"><i class="fa-solid fa-door-open"></i></button>
        </div>
      </div>
    </aside>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle"><i class="fa-solid fa-bars"></i></button>
          <div class="page-title">
            <h2>Accueil</h2>
            <p>Découvrez les offres du jour</p>
          </div>
        </div>
        <div class="header-right">
          <button class="header-notification"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button>
          <div class="avatar avatar-sm" id="header-avatar">AA</div>
        </div>
      </header>

      <div class="page-content">
        <div class="welcome-banner animate-fade-in-up">
          <div class="welcome-text">
            <h2>Bonjour, <span id="welcome-name">Étudiant</span> !</h2>
            <p>Heureux de vous revoir. Continuez à sauver des repas !</p>
          </div>
          <div class="welcome-emoji"><i class="fa-solid fa-bowl-food"></i></div>
        </div>

        <div class="grid grid-3 gap-6 mb-8 animate-fade-in-up stagger-1">
          <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-utensils"></i></div>
            <div class="stat-value" id="impact-meals">0</div>
            <div class="stat-label">Repas sauvés</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon orange"><i class="fa-solid fa-leaf"></i></div>
            <div class="stat-value" id="impact-co2">0 kg</div>
            <div class="stat-label">CO2 évité</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon yellow"><i class="fa-solid fa-coins"></i></div>
            <div class="stat-value" id="impact-money">0 DT</div>
            <div class="stat-label">Économisés</div>
          </div>
        </div>

        <!-- ── FILTRES CATÉGORIES ── -->
        <div class="section animate-fade-in-up stagger-2">
          <div class="section-header" style="margin-bottom:16px;">
            <h3><i class="fa-solid fa-fire"></i> Offres du jour</h3>
            <span class="badge badge-primary" id="user-level">Niveau 1</span>
          </div>

          <!-- Boutons filtre par catégorie -->
          <div id="category-filters" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:24px;">
            <button class="btn btn-primary cat-filter-btn active" data-cat="all" onclick="filterByCategory('all', this)">
              <i class="fa-solid fa-border-all"></i> Toutes
              <span style="background:rgba(255,255,255,.15);border-radius:20px;padding:1px 7px;font-size:.72rem;margin-left:4px;">
                <?= count($offers) ?>
              </span>
            </button>
            <?php
            // Build unique category list from all offers
            $allCats = [];
            foreach ($offers as $o) {
              $ids  = $o['categorie_ids'] ?? ($o['id_categorie'] ? [(int)$o['id_categorie']] : []);
              $noms = $o['cat_noms'] ? array_map('trim', explode(',', $o['cat_noms'])) : [$o['nom_categorie'] ?? ''];
              foreach ($ids as $ci => $cid) {
                if ($cid && !isset($allCats[$cid])) {
                  $allCats[$cid] = ['id' => $cid, 'nom' => $noms[$ci] ?? $noms[0] ?? '', 'icone' => $o['icone'] ?? ''];
                }
              }
            }
            foreach ($allCats as $cat):
              // Count offers that belong to this category
              $count = count(array_filter($offers, fn($o) => in_array($cat['id'], $o['categorie_ids'] ?? ($o['id_categorie'] ? [(int)$o['id_categorie']] : []))));
            ?>
              <button class="btn btn-secondary cat-filter-btn"
                      data-cat="<?= (int)$cat['id'] ?>"
                      onclick="filterByCategory('<?= (int)$cat['id'] ?>', this)">
                <?php if (!empty($cat['icone'])): ?>
                  <i class="fa-solid <?= htmlspecialchars($cat['icone']) ?>"></i>
                <?php endif; ?>
                <?= htmlspecialchars($cat['nom']) ?>
                <span style="background:rgba(255,255,255,.15);border-radius:20px;padding:1px 7px;font-size:.72rem;margin-left:4px;">
                  <?= $count ?>
                </span>
              </button>
            <?php endforeach; ?>
          </div>

          <!-- Grille d'offres (filtre multi-catégorie par data-cat-ids) -->
          <?php if (empty($offers)): ?>
            <div class="empty-state">
              <div class="empty-icon"><i class="fa-solid fa-utensils"></i></div>
              <h3>Aucune offre disponible</h3>
              <p>Revenez plus tard pour découvrir de nouvelles offres !</p>
            </div>
          <?php else: ?>
            <div class="grid grid-3 gap-6" id="student-offers-grid">
              <?php foreach ($offers as $o):
                $disc    = ($o['prix_original'] > 0) ? round((1 - $o['prix'] / $o['prix_original']) * 100) : 0;
                $titre   = htmlspecialchars($o['titre'] ?? '');
                $desc    = htmlspecialchars($o['description'] ?? '');
                $restant = (int)($o['quantite'] ?? 0);
                // data-cat-ids = "1,2,3" for JS filtering
                $catIds  = implode(',', $o['categorie_ids'] ?? ($o['id_categorie'] ? [(int)$o['id_categorie']] : []));
                $displayCats = !empty($o['cat_noms']) ? $o['cat_noms'] : ($o['nom_categorie'] ?? '');
                $catList = $displayCats ? array_filter(array_map('trim', explode(',', $displayCats))) : [];
              ?>
              <div class="offer-card animate-fade-in-up" data-cat-ids="<?= htmlspecialchars($catIds) ?>">
                <div class="offer-card-image" style="position:relative;overflow:hidden;border-radius:12px 12px 0 0;">
                  <?php if (!empty($o['photo_url'])): ?>
                    <img src="<?= htmlspecialchars($o['photo_url']) ?>"
                         style="width:100%;height:140px;object-fit:cover;border-radius:12px 12px 0 0;"
                         onerror="this.parentElement.innerHTML='<div style=\'width:100%;height:140px;display:flex;align-items:center;justify-content:center;font-size:2.5rem;background:var(--color-dark-hover);border-radius:12px 12px 0 0;\'><i class=\'fa-solid fa-utensils\'></i></div>'">
                  <?php else: ?>
                    <div style="width:100%;height:140px;display:flex;align-items:center;justify-content:center;font-size:2.5rem;background:var(--color-dark-hover);border-radius:12px 12px 0 0;">
                      <i class="fa-solid fa-utensils"></i>
                    </div>
                  <?php endif; ?>
                  <span class="offer-card-discount">-<?= $disc ?>%</span>
                  <?php if ($restant > 0 && $restant <= 2): ?>
                    <span class="offer-card-badge"><span class="badge badge-danger badge-dot">Plus que <?= $restant ?> !</span></span>
                  <?php endif; ?>
                </div>
                <div class="offer-card-body">
                  <h4><?= $titre ?></h4>
                  <?php if (!empty($catList)): ?>
                  <p style="font-size:.72rem;color:var(--color-primary);margin:0 0 6px;font-weight:600;">
                    <?php foreach ($catList as $ci => $cn): ?>
                      <?php if ($ci > 0): ?><span style="color:var(--color-text-muted);margin:0 2px;">·</span><?php endif; ?>
                      <span><?= htmlspecialchars($cn) ?></span>
                    <?php endforeach; ?>
                  </p>
                  <?php endif; ?>
                  <p style="font-size:.8rem;color:var(--color-text-muted);margin-bottom:12px;line-height:1.4;"><?= $desc ?></p>
                  <div class="offer-card-footer">
                    <div class="offer-card-price">
                      <span class="original"><?= number_format($o['prix_original'], 1) ?> DT</span>
                      <span class="discounted"><?= number_format($o['prix'], 1) ?> DT</span>
                    </div>
                    <?php if (!empty($o['heure_debut'])): ?>
                      <span class="offer-card-time"><i class="fa-solid fa-clock"></i> <?= htmlspecialchars(substr($o['heure_debut'],0,5)) ?> - <?= htmlspecialchars(substr($o['heure_fin'] ?? '',0,5)) ?></span>
                    <?php endif; ?>
                  </div>
                  <button class="btn btn-primary" style="width:100%;margin-top:12px;">
                    <i class="fa-solid fa-basket-shopping"></i> Commander
                  </button>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
                <style>
          /* Checkbox pill filter */
          .cat-checkbox-list { display:flex; flex-wrap:wrap; gap:8px; padding:4px 0; }
          .cat-checkbox-item label {
            display:flex; align-items:center; gap:6px; cursor:pointer;
            background:var(--color-dark-card); border:1px solid var(--color-dark-border);
            border-radius:20px; padding:7px 16px; font-size:.85rem; color:var(--color-text);
            transition:border-color .15s, background .15s; user-select:none;
          }
          .cat-checkbox-item input[type=checkbox] { accent-color:var(--color-primary); width:14px; height:14px; }
          .cat-checkbox-item label:has(input:checked) {
            border-color:var(--color-primary);
            background:rgba(239,68,68,.12);
            color:var(--color-white);
            font-weight:600;
          }
        </style>
        <script>
        function filterByCategory(catId, btn) {
          // Update active button style
          document.querySelectorAll('.cat-filter-btn').forEach(b => {
            b.classList.remove('btn-primary');
            b.classList.add('btn-secondary');
          });
          btn.classList.remove('btn-secondary');
          btn.classList.add('btn-primary');

          // Filter cards by data-cat-ids
          document.querySelectorAll('#student-offers-grid .offer-card').forEach(card => {
            if (catId === 'all') {
              card.style.display = '';
              return;
            }
            const ids = (card.dataset.catIds || '').split(',').map(s => s.trim()).filter(Boolean);
            card.style.display = ids.includes(String(catId)) ? '' : 'none';
          });

          // Show empty state if no cards visible
          const grid = document.getElementById('student-offers-grid');
          if (grid) {
            const visible = grid.querySelectorAll('.offer-card:not([style*="display: none"])').length;
            let emptyEl = document.getElementById('student-empty-filter');
            if (visible === 0) {
              if (!emptyEl) {
                emptyEl = document.createElement('p');
                emptyEl.id = 'student-empty-filter';
                emptyEl.style.cssText = 'color:var(--color-text-muted);text-align:center;padding:32px;grid-column:1/-1;';
                emptyEl.textContent = 'Aucune offre dans cette catégorie.';
                grid.appendChild(emptyEl);
              }
            } else if (emptyEl) {
              emptyEl.remove();
            }
          }
        }
        </script>
        </div>
      </div>
    </main>
  </div>

  <script src="../js/app.js"></script>
  <script src="../js/components.js"></script>
  <script src="../js/student.js"></script>
  <script>
    Student.loadOffers = function() {};
    document.addEventListener('DOMContentLoaded', () => {
      if (!App.requireAuth(['student'])) return;
      const user = App.getCurrentUser();
      if (!user) return;

      const wn = document.getElementById('welcome-name');
      if (wn) wn.textContent = user.name?.split(' ')[0] || 'Étudiant';

      const initials = (user.name || 'AA').slice(0,2).toUpperCase();
      ['sidebar-user-avatar','header-avatar'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.textContent = initials;
      });

      const sn = document.getElementById('sidebar-user-name');
      if (sn) sn.textContent = user.name || 'Utilisateur';

      const ul = document.getElementById('user-level');
      if (ul) ul.textContent = 'Niveau ' + (user.level || 1);

      const im = document.getElementById('impact-meals');
      if (im) im.textContent = user.mealsSaved || 0;
      const ic = document.getElementById('impact-co2');
      if (ic) ic.textContent = (user.co2Saved || 0).toFixed(1) + ' kg';
      const imo = document.getElementById('impact-money');
      if (imo) {
        const orders = App.getOrders().filter(o => o.userId === user.id && o.status === 'completed');
        const saved = orders.reduce((sum, o) => sum + (o.originalPrice - o.price), 0);
        imo.textContent = saved.toFixed(1) + ' DT';
      }
    });
  </script>
</body>
</html>