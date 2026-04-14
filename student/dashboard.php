<?php
require_once __DIR__ . '/../Controller/StudentController.php';
$studentController = new StudentController();
$offers = $studentController->getAllOffers();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard â€” CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
  <div class="dashboard-layout">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <div class="sidebar-logo"><img src="../assets/logo.png" alt="Logo" style="max-width:100%;max-height:100%;object-fit:contain;"></div>
        <div class="sidebar-brand">Care<span>Meal</span></div>
      </div>

      <nav class="sidebar-nav">
        <div class="sidebar-section">
          <div class="sidebar-section-title">Menu</div>
          <a href="dashboard.php" class="sidebar-link active">
            <span class="link-icon"><i class="fa-solid fa-house"></i></span> Accueil
          </a>
          <a href="profile.html" class="sidebar-link">
            <span class="link-icon"><i class="fa-solid fa-user"></i></span> Mon Profil
          </a>
          <a href="preferences.html" class="sidebar-link">
            <span class="link-icon"><i class="fa-solid fa-utensils"></i></span> PrÃ©fÃ©rences
          </a>
          <a href="events.html" class="sidebar-link">
            <span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> Ã‰vÃ©nements
          </a>
          <a href="orders.html" class="sidebar-link">
            <span class="link-icon"><i class="fa-solid fa-box"></i></span> Mes Commandes
          </a>
        </div>
        <div class="sidebar-section">
          <div class="sidebar-section-title">ParamÃ¨tres</div>
          <a href="settings.html" class="sidebar-link">
            <span class="link-icon"><i class="fa-solid fa-gear"></i></span> ParamÃ¨tres
          </a>
        </div>
      </nav>

      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="avatar" id="sidebar-user-avatar">AA</div>
          <div class="sidebar-user-info">
            <div class="sidebar-user-name" id="sidebar-user-name">Utilisateur</div>
            <div class="sidebar-user-role" id="sidebar-user-role">Ã‰tudiant</div>
          </div>
          <button class="sidebar-logout" data-action="logout" title="DÃ©connexion"><i class="fa-solid fa-door-open"></i></button>
        </div>
      </div>
    </aside>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <!-- Main -->
    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle"><i class="fa-solid fa-bars"></i></button>
          <div class="page-title">
            <h2>Accueil</h2>
            <p>DÃ©couvrez les offres du jour</p>
          </div>
        </div>
        <div class="header-right">
          <button class="header-notification">
            <i class="fa-solid fa-bell"></i>
            <span class="notif-dot"></span>
          </button>
          <div class="avatar avatar-sm" id="header-avatar">AA</div>
        </div>
      </header>

      <div class="page-content">
        <!-- Welcome Banner -->
        <div class="welcome-banner animate-fade-in-up">
          <div class="welcome-text">
            <h2>Bonjour, <span id="welcome-name">Ã‰tudiant</span> ! <i class="fa-solid fa-hand-wave"></i></h2>
            <p>Heureux de vous revoir. Continuez Ã  sauver des repas !</p>
          </div>
          <div class="welcome-emoji"><i class="fa-solid fa-bowl-food"></i></div>
        </div>

        <!-- Impact Stats -->
        <div class="grid grid-3 gap-6 mb-8 animate-fade-in-up stagger-1">
          <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-utensils"></i></div>
            <div class="stat-value" id="impact-meals">0</div>
            <div class="stat-label">Repas sauvÃ©s</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon orange"><i class="fa-solid fa-leaf"></i></div>
            <div class="stat-value" id="impact-co2">0 kg</div>
            <div class="stat-label">COâ‚‚ Ã©vitÃ©</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon yellow"><i class="fa-solid fa-coins"></i></div>
            <div class="stat-value" id="impact-money">0 DT</div>
            <div class="stat-label">Ã‰conomisÃ©s</div>
          </div>
        </div>

        <!-- Offers -->
        <div class="section animate-fade-in-up stagger-2">
          <div class="section-header">
            <h3><i class="fa-solid fa-fire"></i> Offres du jour</h3>
            <span class="badge badge-primary" id="user-level">Niveau 1</span>
          </div>

          <div class="grid grid-3 gap-6" id="offers-grid">
            <?php if (empty($offers)): ?>
              <div class="empty-state" style="grid-column:1/-1;">
                <div class="empty-icon"><i class="fa-solid fa-utensils"></i></div>
                <h3>Aucune offre disponible</h3>
                <p>Revenez plus tard pour dÃ©couvrir de nouvelles offres !</p>
              </div>
            <?php else: ?>
              <?php foreach ($offers as $o):
                $disc    = ($o['prix_original'] > 0) ? round((1 - $o['prix'] / $o['prix_original']) * 100) : 0;
                $resto   = htmlspecialchars($o['nom_categorie'] ?? '');
                $titre   = htmlspecialchars($o['titre'] ?? '');
                $desc    = htmlspecialchars($o['description'] ?? '');
                $restant = (int)($o['quantite_restante'] ?? $o['quantite'] ?? 0);
              ?>
              <div class="offer-card animate-fade-in-up">
                <div class="offer-card-image" style="position:relative;overflow:hidden;border-radius:12px 12px 0 0;">
                  <?php if (!empty($o['photo_url'])): ?>
                    <img src="<?= htmlspecialchars($o['photo_url']) ?>"
                         style="width:100%;height:140px;object-fit:cover;border-radius:12px 12px 0 0;"
                         onerror="this.parentElement.innerHTML='<div style=\'width:100%;height:140px;display:flex;align-items:center;justify-content:center;font-size:2.5rem;background:var(--color-dark-hover);border-radius:12px 12px 0 0;\'>ðŸ½ï¸</div>'">
                  <?php else: ?>
                    <div style="width:100%;height:140px;display:flex;align-items:center;justify-content:center;font-size:2.5rem;background:var(--color-dark-hover);border-radius:12px 12px 0 0;">ðŸ½ï¸</div>
                  <?php endif; ?>
                  <span class="offer-card-discount">-<?= $disc ?>%</span>
                  <?php if ($restant > 0 && $restant <= 2): ?>
                    <span class="offer-card-badge">
                      <span class="badge badge-danger badge-dot">Plus que <?= $restant ?> !</span>
                    </span>
                  <?php endif; ?>
                </div>
                <div class="offer-card-body">
                  <h4><?= $titre ?></h4>
                  <?php if ($resto): ?>
                    <p class="offer-restaurant"><i class="fa-solid fa-location-dot"></i> <?= $resto ?></p>
                  <?php endif; ?>
                  <p style="font-size:.8rem;color:var(--color-text-muted);margin-bottom:12px;line-height:1.4;"><?= $desc ?></p>
                  <div class="offer-card-footer">
                    <div class="offer-card-price">
                      <span class="original"><?= number_format($o['prix_original'], 1) ?> DT</span>
                      <span class="discounted"><?= number_format($o['prix'], 1) ?> DT</span>
                    </div>
                    <?php if (!empty($o['heure_debut'])): ?>
                      <span class="offer-card-time">
                        <i class="fa-solid fa-clock"></i>
                        <?= htmlspecialchars($o['heure_debut']) ?>â€“<?= htmlspecialchars($o['heure_fin'] ?? '') ?>
                      </span>
                    <?php endif; ?>
                  </div>
                  <button class="btn btn-primary" style="width:100%;margin-top:12px;">
                    <i class="fa-solid fa-basket-shopping"></i> Commander
                  </button>
                </div>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="../js/app.js"></script>
  <script src="../js/components.js"></script>
  <script src="../js/student.js"></script>
  <script>
    // On empÃªche student.js de recharger les offres (dÃ©jÃ  rendues par PHP)
    // On surcharge loadOffers pour qu'il ne fasse rien
    const _origStudentInit = Student.init.bind(Student);
    Student.loadOffers = function() {};  // dÃ©sactivÃ© â€” offres dÃ©jÃ  en PHP
    
    document.addEventListener('DOMContentLoaded', () => {
      // Auth & infos utilisateur via app.js
      if (!App.requireAuth(['student'])) return;
      const user = App.getCurrentUser();
      if (!user) return;

      // Nom
      const wn = document.getElementById('welcome-name');
      if (wn) wn.textContent = user.name?.split(' ')[0] || 'Ã‰tudiant';

      // Avatars
      const initials = (user.name || 'AA').slice(0,2).toUpperCase();
      ['sidebar-user-avatar','header-avatar'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.textContent = initials;
      });

      // Nom sidebar
      const sn = document.getElementById('sidebar-user-name');
      if (sn) sn.textContent = user.name || 'Utilisateur';

      // Niveau
      const ul = document.getElementById('user-level');
      if (ul) ul.textContent = 'Niveau ' + (user.level || 1);

      // Impact
      const im = document.getElementById('impact-meals');
      if (im) im.textContent = user.mealsSaved || 0;
      const ic = document.getElementById('impact-co2');
      if (ic) ic.textContent = parseFloat(user.co2Saved || 0).toFixed(1) + ' kg';
      const imn = document.getElementById('impact-money');
      if (imn) {
        const orders = App.getOrders().filter(o => o.userId === user.id && o.status === 'completed');
        const saved  = orders.reduce((sum, o) => sum + ((o.originalPrice || 0) - (o.price || 0)), 0);
        imn.textContent = saved.toFixed(1) + ' DT';
      }

      // Sidebar toggle
      const toggle  = document.getElementById('menu-toggle');
      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('sidebar-overlay');
      if (toggle && sidebar) {
        toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
        overlay?.addEventListener('click', () => sidebar.classList.remove('open'));
      }
    });
  </script>
</body>
</html>

