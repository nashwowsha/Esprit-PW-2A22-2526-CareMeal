<?php
require_once __DIR__ . '/../Controller/StudentController.php';
$studentController        = new StudentController();
$offers                   = $studentController->getPublishedOffers();
$categoriesWithOffers     = $studentController->getPublishedOffersByCategory();
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

        <!-- ── OFFRES DU JOUR ── -->
        <div class="section animate-fade-in-up stagger-2">
          <div class="section-header" style="margin-bottom:16px;">
            <h3><i class="fa-solid fa-fire"></i> Offres du jour</h3>
            <span class="badge badge-primary" id="user-level">Niveau 1</span>
          </div>

          <!-- ── Barre de recherche ── -->
          <div style="position:relative;margin-bottom:20px;max-width:400px;">
            <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--color-text-muted);font-size:.85rem;pointer-events:none;z-index:1;"></i>
            <input type="text" id="offer-search"
                   style="width:100%;padding:10px 40px 10px 40px;border-radius:50px;background:var(--color-dark-card);border:1.5px solid var(--color-dark-border);color:var(--color-white);font-size:.875rem;outline:none;box-sizing:border-box;transition:border-color .2s;"
                   placeholder="Rechercher une offre..."
                   oninput="applyFilters()"
                   onfocus="this.style.borderColor='var(--color-primary)'"
                   onblur="this.style.borderColor='var(--color-dark-border)'">
            <button onclick="clearSearch()"
                    style="display:none;position:absolute;right:13px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--color-text-muted);font-size:.85rem;padding:0;line-height:1;"
                    id="search-clear-btn">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <!-- ── Filtre catégories (redesigné) ── -->
          <style>
            .cat-pill {
              display: inline-flex; align-items: center; gap: 7px;
              padding: 8px 16px; border-radius: 50px;
              border: 1.5px solid var(--color-dark-border);
              background: var(--color-dark-card);
              color: var(--color-text-muted);
              font-size: .82rem; font-weight: 600;
              cursor: pointer; white-space: nowrap;
              transition: all .2s ease;
            }
            .cat-pill:hover {
              border-color: var(--color-primary);
              color: var(--color-white);
              background: rgba(239,68,68,.08);
              transform: translateY(-1px);
              box-shadow: 0 4px 12px rgba(239,68,68,.15);
            }
            .cat-pill.active {
              background: var(--color-primary);
              border-color: var(--color-primary);
              color: #fff;
              box-shadow: 0 4px 16px rgba(239,68,68,.3);
              transform: translateY(-1px);
            }
            .cat-pill-ico {
              width: 20px; height: 20px; border-radius: 50%;
              background: rgba(255,255,255,.12);
              display: flex; align-items: center; justify-content: center;
              font-size: .65rem; flex-shrink: 0;
            }
            .cat-pill:not(.active) .cat-pill-ico { background: rgba(255,255,255,.06); }
            .cat-pill-cnt {
              background: rgba(0,0,0,.22); border-radius: 50px;
              padding: 1px 8px; font-size: .68rem; font-weight: 700;
            }
            .search-hl { background: rgba(239,68,68,.3); color:#fff; border-radius:3px; padding:0 2px; font-weight:700; }
            @keyframes pulse-w { 0%,100%{opacity:1} 50%{opacity:.3} }
          </style>

          <div id="category-filters" style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:24px;">
            <button class="cat-pill active" data-cat="all" onclick="filterByCategory('all',this)">
              <span class="cat-pill-ico"><i class="fa-solid fa-border-all"></i></span>
              <span>Toutes</span>
              <span class="cat-pill-cnt"><?= count($offers) ?></span>
            </button>
            <?php foreach ($categoriesWithOffers as $cat): ?>
              <button class="cat-pill" data-cat="<?= (int)$cat['id_categorie'] ?>"
                      onclick="filterByCategory('<?= (int)$cat['id_categorie'] ?>',this)">
                <?php if (!empty($cat['icone'])): ?>
                  <span class="cat-pill-ico"><i class="fa-solid <?= htmlspecialchars($cat['icone']) ?>"></i></span>
                <?php endif; ?>
                <span><?= htmlspecialchars($cat['nom_categorie']) ?></span>
                <span class="cat-pill-cnt"><?= count($cat['offres']) ?></span>
              </button>
            <?php endforeach; ?>
          </div>

          <!-- Grille d'offres -->
          <?php if (empty($offers)): ?>
            <div class="empty-state">
              <div class="empty-icon"><i class="fa-solid fa-utensils"></i></div>
              <h3>Aucune offre disponible</h3>
              <p>Revenez plus tard pour découvrir de nouvelles offres !</p>
            </div>
          <?php else: ?>
            <div class="grid grid-3 gap-6" id="offers-grid">
              <?php foreach ($offers as $o):
                $disc    = ($o['prix_original'] > 0) ? round((1 - $o['prix'] / $o['prix_original']) * 100) : 0;
                $titre   = htmlspecialchars($o['titre'] ?? '');
                $desc    = htmlspecialchars($o['description'] ?? '');
                $restant = (int)($o['quantite_restante'] ?? $o['quantite'] ?? 0);
                $catId   = (int)$o['id_categorie'];
                $catNom  = htmlspecialchars($o['nom_categorie'] ?? '');
                $catIcon = htmlspecialchars($o['icone'] ?? 'fa-tag');
              ?>
              <div class="offer-card animate-fade-in-up student-offer-card" data-cat-id="<?= $catId ?>">
                <div class="offer-card-image" style="position:relative;overflow:hidden;border-radius:12px 12px 0 0;">
                  <?php if (!empty($o['photo_url'])): ?>
                    <img src="<?= htmlspecialchars($o['photo_url']) ?>"
                         style="width:100%;height:140px;object-fit:cover;border-radius:12px 12px 0 0;"
                         onerror="this.style.display='none'">
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
                  <!-- Badge catégorie orangé (jointure simple) -->
                  <div style="margin:0 0 8px;">
                    <span style="display:inline-block;background:var(--color-primary);color:#fff;border-radius:20px;padding:3px 12px;font-size:.72rem;font-weight:700;">
                      <i class="fa-solid <?= $catIcon ?>" style="margin-right:4px;font-size:.65rem;"></i><?= $catNom ?>
                    </span>
                  </div>
                  <p style="font-size:.8rem;color:var(--color-text-muted);margin-bottom:12px;line-height:1.4;"><?= $desc ?></p>
                  <!-- Quantité / Stock -->
                  <div style="display:flex;align-items:center;gap:6px;margin-bottom:12px;">
                    <?php if ($restant <= 0): ?>
                      <span style="display:inline-flex;align-items:center;gap:5px;background:rgba(156,163,175,.12);color:#9ca3af;border-radius:20px;padding:4px 12px;font-size:.75rem;font-weight:700;">
                        <i class="fa-solid fa-ban"></i> Rupture de stock
                      </span>
                    <?php elseif ($restant <= 3): ?>
                      <span style="display:inline-flex;align-items:center;gap:5px;background:rgba(239,68,68,.12);color:#f87171;border-radius:20px;padding:4px 12px;font-size:.75rem;font-weight:700;border:1px solid rgba(239,68,68,.25);">
                        <i class="fa-solid fa-triangle-exclamation" style="animation:pulse-w 1.2s infinite;"></i>
                        Plus que <?= $restant ?> disponible<?= $restant > 1 ? 's' : '' ?>
                      </span>
                    <?php else: ?>
                      <span style="display:inline-flex;align-items:center;gap:5px;background:rgba(34,197,94,.1);color:#4ade80;border-radius:20px;padding:4px 12px;font-size:.75rem;font-weight:700;">
                        <i class="fa-solid fa-box"></i> <?= $restant ?> en stock
                      </span>
                    <?php endif; ?>
                  </div>
                  <div class="offer-card-footer">
                    <div class="offer-card-price">
                      <span class="original"><?= number_format($o['prix_original'], 1) ?> DT</span>
                      <span class="discounted"><?= number_format($o['prix'], 1) ?> DT</span>
                    </div>
                    <?php if (!empty($o['heure_debut'])): ?>
                      <span class="offer-card-time">
                        <i class="fa-solid fa-clock"></i>
                        <?= htmlspecialchars(substr($o['heure_debut'],0,5)) ?> - <?= htmlspecialchars(substr($o['heure_fin'] ?? '',0,5)) ?>
                      </span>
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
        </div>
      </div>
    </main>
  </div>

  <script src="../js/app.js"></script>
  <script src="../js/components.js"></script>
  <script src="../js/student.js"></script>
  <script src="../js/student-voice-assistant.js"></script>
  <script>
    let activeCat = 'all';

    function filterByCategory(catId, btn) {
      activeCat = catId;
      document.querySelectorAll('.cat-pill').forEach(b => b.classList.remove('active'));
      if (btn) btn.classList.add('active');
      applyFilters();
    }

    function applyFilters() {
      const q = (document.getElementById('offer-search').value || '').trim().toLowerCase();
      const clearBtn = document.getElementById('search-clear-btn');
      if (clearBtn) clearBtn.style.display = q ? 'block' : 'none';

      let visible = 0;
      document.querySelectorAll('.student-offer-card').forEach(card => {
        const matchCat = activeCat === 'all' || card.dataset.catId === String(activeCat);
        const titleEl  = card.querySelector('h4');
        const title    = (titleEl ? titleEl.textContent : '').toLowerCase();
        const matchQ   = !q || title.includes(q);

        if (matchCat && matchQ) {
          card.style.display = '';
          visible++;
          // Surligner le terme recherché dans le titre
          if (titleEl) {
            if (q) {
              const esc = q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
              titleEl.innerHTML = titleEl.textContent.replace(
                new RegExp(esc, 'gi'),
                m => `<mark class="search-hl">${m}</mark>`
              );
            } else {
              titleEl.textContent = titleEl.textContent;
            }
          }
        } else {
          card.style.display = 'none';
          if (titleEl) titleEl.textContent = titleEl.textContent; // reset highlight
        }
      });

      // Message vide
      const grid = document.getElementById('offers-grid');
      if (grid) {
        let emptyEl = document.getElementById('student-empty-filter');
        if (visible === 0) {
          if (!emptyEl) {
            emptyEl = document.createElement('div');
            emptyEl.id = 'student-empty-filter';
            emptyEl.style.cssText = 'text-align:center;padding:40px 20px;color:var(--color-text-muted);grid-column:1/-1;';
            emptyEl.innerHTML = `<i class="fa-solid fa-magnifying-glass" style="font-size:2rem;opacity:.3;display:block;margin-bottom:10px;"></i><p style="margin:0;">Aucune offre trouvée.</p>`;
            grid.appendChild(emptyEl);
          }
        } else if (emptyEl) {
          emptyEl.remove();
        }
      }
    }

    function clearSearch() {
      const input = document.getElementById('offer-search');
      if (input) { input.value = ''; input.focus(); }
      applyFilters();
    }
  </script>
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
