<?php
// Chargement direct sans passer par le contrôleur (accès direct au fichier)
if (!isset($offers)) {
    require_once __DIR__ . '/../../../config/database.php';
    require_once __DIR__ . '/../../../Model/Offer.php';
    $offerModel = new OfferModel();
    $allOffers  = $offerModel->getAll();
    $offers     = array_values(array_filter($allOffers, fn($o) => $o['statut'] === 'publiée'));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Votre tableau de bord CareMeal — Découvrez les offres anti-gaspillage près de chez vous.">
  <title>Dashboard — CareMeal</title>
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
          <a href="profile.php" class="sidebar-link">
            <span class="link-icon"><i class="fa-solid fa-user"></i></span> Mon Profil
          </a>
          <a href="preferences.php" class="sidebar-link">
            <span class="link-icon"><i class="fa-solid fa-utensils"></i></span> Préférences
          </a>
          <a href="events.php" class="sidebar-link">
            <span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> Événements
          </a>
          <a href="orders.php" class="sidebar-link">
            <span class="link-icon"><i class="fa-solid fa-box"></i></span> Mes Commandes
          </a>
        </div>
        <div class="sidebar-section">
          <div class="sidebar-section-title">Paramètres</div>
          <a href="settings.php" class="sidebar-link">
            <span class="link-icon"><i class="fa-solid fa-gear"></i></span> Paramètres
          </a>
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

    <!-- Main -->
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
            <h2>Bonjour, <span id="welcome-name">Étudiant</span> ! <i class="fa-solid fa-hand-wave"></i></h2>
            <p>Heureux de vous revoir. Continuez à sauver des repas !</p>
          </div>
          <div class="welcome-emoji"><i class="fa-solid fa-bowl-food"></i></div>
        </div>

        <!-- Impact Stats -->
        <div class="grid grid-3 gap-6 mb-8 animate-fade-in-up stagger-1">
          <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-utensils"></i></div>
            <div class="stat-value" id="impact-meals">0</div>
            <div class="stat-label">Repas sauvés</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon orange"><i class="fa-solid fa-leaf"></i></div>
            <div class="stat-value" id="impact-co2">0 kg</div>
            <div class="stat-label">CO₂ évité</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon yellow"><i class="fa-solid fa-coins"></i></div>
            <div class="stat-value" id="impact-money">0 DT</div>
            <div class="stat-label">Économisés</div>
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
                <p>Revenez plus tard pour découvrir de nouvelles offres !</p>
              </div>
            <?php else: ?>
              <?php foreach ($offers as $o):
                $disc   = ($o['prix_original'] > 0) ? round((1 - $o['prix'] / $o['prix_original']) * 100) : 0;
                $resto  = htmlspecialchars($o['nom_categorie'] ?? '');
                $titre  = htmlspecialchars($o['titre'] ?? '');
                $desc   = htmlspecialchars($o['description'] ?? '');
                $restant = (int)($o['quantite_restante'] ?? $o['quantite'] ?? 0);
              ?>
              <div class="offer-card animate-fade-in-up">
                <div class="offer-card-image" style="position:relative;overflow:hidden;border-radius:12px 12px 0 0;">
                  <?php if (!empty($o['photo_url'])): ?>
                    <img src="<?= htmlspecialchars($o['photo_url']) ?>"
                         style="width:100%;height:140px;object-fit:cover;border-radius:12px 12px 0 0;"
                         onerror="this.parentElement.innerHTML='<div style=\'width:100%;height:140px;display:flex;align-items:center;justify-content:center;font-size:2.5rem;background:var(--color-dark-hover);border-radius:12px 12px 0 0;\'>🍽️</div>'">
                  <?php else: ?>
                    <div style="width:100%;height:140px;display:flex;align-items:center;justify-content:center;font-size:2.5rem;background:var(--color-dark-hover);border-radius:12px 12px 0 0;">🍽️</div>
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
                        <?= htmlspecialchars($o['heure_debut']) ?>–<?= htmlspecialchars($o['heure_fin'] ?? '') ?>
                      </span>
                    <?php endif; ?>
                  </div>
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
    // Initialise sidebar, avatar, niveau — sans recharger les offres (déjà en PHP)
    document.addEventListener('DOMContentLoaded', () => {
      // Sidebar toggle
      const toggle  = document.getElementById('menu-toggle');
      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('sidebar-overlay');
      if (toggle && sidebar) {
        toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
        overlay?.addEventListener('click', () => sidebar.classList.remove('open'));
      }

      // Infos utilisateur depuis localStorage (session JS)
      if (typeof App !== 'undefined') {
        const user = App.getCurrentUser?.();
        if (user) {
          const wn = document.getElementById('welcome-name');
          if (wn) wn.textContent = user.name?.split(' ')[0] || 'Étudiant';

          const av = document.getElementById('sidebar-user-avatar');
          if (av) av.textContent = (user.name || 'AA').slice(0,2).toUpperCase();

          const ha = document.getElementById('header-avatar');
          if (ha) ha.textContent = (user.name || 'AA').slice(0,2).toUpperCase();

          const sn = document.getElementById('sidebar-user-name');
          if (sn) sn.textContent = user.name || 'Utilisateur';

          const ul = document.getElementById('user-level');
          if (ul) ul.textContent = 'Niveau ' + (user.level || 1);

          // Impact
          const im = document.getElementById('impact-meals');
          if (im) im.textContent = user.mealsSaved || 0;
          const ic = document.getElementById('impact-co2');
          if (ic) ic.textContent = (user.co2Saved || 0).toFixed(1) + ' kg';
        }
      }

      // Logout
      document.querySelectorAll('[data-action="logout"]').forEach(btn => {
        btn.addEventListener('click', () => {
          if (typeof App !== 'undefined') App.logout?.();
          else window.location.href = '../login.php';
        });
      });
    });
  </script>
</body>
</html>