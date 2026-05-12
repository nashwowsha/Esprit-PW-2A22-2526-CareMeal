<?php require_once dirname(__DIR__, 2) . '/session_check.php'; ?>
<?php require_once dirname(__DIR__, 3) . '/config/app.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Statistiques de votre &eacute;tablissement sur CareMeal.">
  <title>Statistiques &mdash; CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="<?= htmlspecialchars(caremeal_path('css/main.css'), ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(caremeal_path('css/components.css'), ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(caremeal_path('css/dashboard.css'), ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(caremeal_path('css/theme-fix.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
  <div class="dashboard-layout">
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <div class="sidebar-logo"><img src="<?= htmlspecialchars(caremeal_path('assets/logo.png'), ENT_QUOTES, 'UTF-8') ?>" alt="CareMeal" style="max-width:100%;max-height:100%;object-fit:contain;"></div>
        <div class="sidebar-brand">Care<span>Meal</span></div>
      </div>
      <nav class="sidebar-nav">
        <div class="sidebar-section">
          <div class="sidebar-section-title">Partenaire</div>
          <a href="dashboard.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-store"></i></span> Mon &Eacute;tablissement</a>
          <a href="<?= htmlspecialchars(caremeal_path('partner/offers.php'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-bag-shopping"></i></span> Mes Offres</a>
          <a href="events.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> Mes &Eacute;v&eacute;nements</a>
          <a href="<?= htmlspecialchars(caremeal_path('partner/restaurants.php'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-utensils"></i></span> Mes Restaurants</a>
          <a href="settings.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-gear"></i></span> Param&egrave;tres</a>
        </div>
      </nav>
      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="avatar" id="sidebar-user-avatar" style="background:linear-gradient(135deg,var(--color-primary),#FF7A3D);">P</div>
          <div class="sidebar-user-info">
            <div class="sidebar-user-name" id="sidebar-user-name">Partenaire</div>
            <div class="sidebar-user-role" id="sidebar-user-role">Partenaire</div>
          </div>
          <button class="sidebar-logout" data-action="logout" title="D&eacute;connexion"><i class="fa-solid fa-arrow-right-from-bracket"></i></button>
        </div>
      </div>
    </aside>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle"><i class="fa-solid fa-bars"></i></button>
          <div class="page-title"><h2>Statistiques</h2><p>Performance de votre &eacute;tablissement</p></div>
        </div>
        <div class="header-right">
                    <a href="<?= htmlspecialchars(caremeal_path('View/FrontOffice/feed.php'), ENT_QUOTES, 'UTF-8') ?>" title="Retour au Feed" style="display: flex; align-items: center; color: #FE5516; background: rgba(254,85,22,0.1); border-radius: 20px; padding: 6px 16px; font-size: 0.95rem; font-weight: 600; text-decoration: none; margin-right: 8px; transition: all 0.2s;">
            <i class="fa-solid fa-house" style="margin-right: 8px;"></i> Retour au Feed
          </a>
          <button class="header-notification"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button>
          <div class="avatar avatar-sm" id="header-avatar" style="background:linear-gradient(135deg,var(--color-primary),#FF7A3D);">P</div>
        </div>
      </header>

      <div class="page-content">
        <!-- Main Stats -->
        <div class="grid grid-4 gap-4 mb-8 animate-fade-in-up">
          <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-utensils"></i></div>
            <div class="stat-value" id="stats-meals">0</div>
            <div class="stat-label">Repas sauv&eacute;s</div>
            <div class="stat-change up"><i class="fa-solid fa-arrow-trend-up"></i>  +15%</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon orange"><i class="fa-solid fa-earth-europe"></i></div>
            <div class="stat-value" id="stats-co2">0 kg</div>
            <div class="stat-label">CO2 &eacute;vit&eacute;</div>
            <div class="stat-change up"><i class="fa-solid fa-arrow-trend-up"></i>  +12%</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon yellow"><i class="fa-solid fa-star"></i></div>
            <div class="stat-value" id="stats-rating">&mdash;</div>
            <div class="stat-label">Note moyenne</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon blue"><i class="fa-solid fa-coins"></i></div>
            <div class="stat-value" id="stats-revenue">0 DT</div>
            <div class="stat-label">Revenus g&eacute;n&eacute;r&eacute;s</div>
            <div class="stat-change up"><i class="fa-solid fa-arrow-trend-up"></i>  +20%</div>
          </div>
        </div>

        <!-- Secondary stats -->
        <div class="grid grid-3 gap-4 mb-8 animate-fade-in-up stagger-1">
          <div class="card" style="display:flex;align-items:center;gap:16px;">
            <div class="stat-icon blue" style="width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;"><i class="fa-solid fa-comments"></i></div>
            <div>
              <div style="font-size:1.5rem;font-weight:700;color:var(--color-white);" id="stats-reviews">0</div>
              <div style="font-size:0.8rem;color:var(--color-text-muted);">Avis re&ccedil;us</div>
            </div>
          </div>
          <div class="card" style="display:flex;align-items:center;gap:16px;">
            <div class="stat-icon green" style="width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;"><i class="fa-solid fa-chart-line"></i> </div>
            <div>
              <div style="font-size:1.5rem;font-weight:700;color:var(--color-white);" id="stats-offers-active">0</div>
              <div style="font-size:0.8rem;color:var(--color-text-muted);">Offres actives</div>
            </div>
          </div>
          <div class="card" style="display:flex;align-items:center;gap:16px;">
            <div class="stat-icon orange" style="width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;"><i class="fa-solid fa-bag-shopping"></i></div>
            <div>
              <div style="font-size:1.5rem;font-weight:700;color:var(--color-white);" id="stats-orders">0</div>
              <div style="font-size:0.8rem;color:var(--color-text-muted);">Commandes totales</div>
            </div>
          </div>
        </div>

        <!-- Reviews -->
        <div class="section animate-fade-in-up stagger-2">
          <div class="section-header">
            <h3><i class="fa-solid fa-comments"></i> Derniers avis</h3>
          </div>
          <div class="card">
            <div id="recent-reviews">
              <!-- Loaded dynamically -->
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="<?= htmlspecialchars(caremeal_path('js/app.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
  <script src="<?= htmlspecialchars(caremeal_path('js/components.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
  <script src="<?= htmlspecialchars(caremeal_path('js/partner.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
  <script>document.addEventListener('DOMContentLoaded', () => Partner.initStats());</script>
</body>
</html>















