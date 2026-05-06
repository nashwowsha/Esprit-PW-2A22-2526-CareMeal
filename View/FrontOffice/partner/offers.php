<?php require_once dirname(__DIR__, 2) . '/session_check.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Gérez vos offres anti-gaspillage sur CareMeal.">
  <title>Mes Offres — CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/projet2a22/css/main.css">
  <link rel="stylesheet" href="/projet2a22/css/components.css">
  <link rel="stylesheet" href="/projet2a22/css/dashboard.css">
</head>
<body>
  <div class="dashboard-layout">
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <div class="sidebar-logo"><i class="fa-solid fa-utensils"></i></div>
        <div class="sidebar-brand">Care<span>Meal</span></div>
      </div>
      <nav class="sidebar-nav">
        <div class="sidebar-section">
          <div class="sidebar-section-title">Partenaire</div>
                    <a href="dashboard.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-store"></i></span> Mon Établissement</a>
          <a href="offers.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-bag-shopping"></i></span> Mes Offres</a>
          <a href="events.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-calendar-alt"></i></span> Mes Événements</a>
          <a href="stats.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-chart-simple"></i></span> Statistiques</a>
          <a href="settings.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-gear"></i></span> Paramètres</a>
        </div>
      </nav>
      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="avatar" id="sidebar-user-avatar" style="background:linear-gradient(135deg,var(--color-primary),#FF7A3D);">P</div>
          <div class="sidebar-user-info">
            <div class="sidebar-user-name" id="sidebar-user-name">Partenaire</div>
            <div class="sidebar-user-role" id="sidebar-user-role">Partenaire</div>
          </div>
          <button class="sidebar-logout" data-action="logout" title="Déconnexion"><i class="fa-solid fa-arrow-right-from-bracket"></i></button>
        </div>
      </div>
    </aside>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle">☰</button>
          <div class="page-title"><h2>Mes Offres</h2><p>Gérez vos offres anti-gaspillage</p></div>
        </div>
        <div class="header-right">
                    <a href="/projet2a22/View/FrontOffice/feed.php" title="Retour au Feed" style="display: flex; align-items: center; color: #FE5516; background: rgba(254,85,22,0.1); border-radius: 20px; padding: 6px 16px; font-size: 0.95rem; font-weight: 600; text-decoration: none; margin-right: 8px; transition: all 0.2s;">
            <i class="fa-solid fa-house" style="margin-right: 8px;"></i> Retour au Feed
          </a>
          <button class="header-notification"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button>
          <div class="avatar avatar-sm" id="header-avatar" style="background:linear-gradient(135deg,var(--color-primary),#FF7A3D);">P</div>
        </div>
      </header>

      <div class="page-content">
        <!-- Summary -->
        <div class="grid grid-2 gap-4 mb-8 animate-fade-in-up">
          <div class="card" style="display:flex;align-items:center;gap:16px;">
            <div class="stat-icon green" style="width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;"><i class="fa-solid fa-chart-line"></i> </div>
            <div>
              <div style="font-size:1.5rem;font-weight:700;color:var(--color-white);" id="active-count">0</div>
              <div style="font-size:0.8rem;color:var(--color-text-muted);">Offres actives</div>
            </div>
          </div>
          <div class="card" style="display:flex;align-items:center;gap:16px;">
            <div class="stat-icon yellow" style="width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;">â Â°</div>
            <div>
              <div style="font-size:1.5rem;font-weight:700;color:var(--color-text-muted);" id="expired-count">0</div>
              <div style="font-size:0.8rem;color:var(--color-text-muted);">Offres expirées</div>
            </div>
          </div>
        </div>

        <!-- Active Offers -->
        <div class="section animate-fade-in-up stagger-1">
          <div class="section-header">
            <h3><i class="fa-solid fa-chart-line"></i>  Offres actives</h3>
          </div>
          <div class="grid grid-3 gap-6" id="active-offers">
            <!-- Loaded dynamically -->
          </div>
        </div>

        <!-- Expired Offers -->
        <div class="section animate-fade-in-up stagger-2">
          <div class="section-header">
            <h3>â Â° Offres expirées</h3>
          </div>
          <div class="grid grid-3 gap-6" id="expired-offers">
            <!-- Loaded dynamically -->
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="/projet2a22/js/app.js"></script>
  <script src="/projet2a22/js/components.js"></script>
  <script src="/projet2a22/js/partner.js"></script>
  <script>document.addEventListener('DOMContentLoaded', () => Partner.initOffers());</script>
</body>
</html>







