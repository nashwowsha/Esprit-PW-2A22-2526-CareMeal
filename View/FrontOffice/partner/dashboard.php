<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Dashboard partenaire CareMeal Ã¢Ã¢â€šÂ¬Ã¢â‚¬Â Profil ÃƒÆ’Ã‚Â©tablissement.">
  <title>Mon ÃƒÆ’Ã¢â‚¬Â°tablissement Ã¢Ã¢â€šÂ¬Ã¢â‚¬Â CareMeal</title>
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
  <div class="dashboard-layout">
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <div class="sidebar-logo">ÃƒÂ°Ã…Â¸Ã‚ÂÃ‚Â½ÃƒÂ¯Ã‚Â¸Ã‚Â</div>
        <div class="sidebar-brand">Care<span>Meal</span></div>
      </div>
      <nav class="sidebar-nav">
        <div class="sidebar-section">
          <div class="sidebar-section-title">Partenaire</div>
          <a href="dashboard.html" class="sidebar-link active"><span class="link-icon">ÃƒÂ°Ã…Â¸Ã‚ÂÃ‚Â </span> Mon ÃƒÆ’Ã¢â‚¬Â°tablissement</a>
          <a href="offers.html" class="sidebar-link"><span class="link-icon">ÃƒÂ°Ã…Â¸Ã¢â‚¬Å“Ã‚Â¦</span> Mes Offres</a>
          <a href="stats.html" class="sidebar-link"><span class="link-icon">ÃƒÂ°Ã…Â¸Ã¢â‚¬Å“Ã…Â </span> Statistiques</a>
          <a href="settings.html" class="sidebar-link"><span class="link-icon">Ã¢Ã…Â¡Ã¢â€žÂ¢ÃƒÂ¯Ã‚Â¸Ã‚Â</span> ParamÃƒÆ’Ã‚Â¨tres</a>
        </div>
      </nav>
      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="avatar" id="sidebar-user-avatar" style="background:linear-gradient(135deg,var(--color-primary),#FF7A3D);">P</div>
          <div class="sidebar-user-info">
            <div class="sidebar-user-name" id="sidebar-user-name">Partenaire</div>
            <div class="sidebar-user-role" id="sidebar-user-role">Partenaire</div>
          </div>
          <button class="sidebar-logout" data-action="logout" title="DÃƒÆ’Ã‚Â©connexion">ÃƒÂ°Ã…Â¸Ã…Â¡Ã‚Âª</button>
        </div>
      </div>
    </aside>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle">Ã¢Ã‹Å“Ã‚Â°</button>
          <div class="page-title"><h2>Mon ÃƒÆ’Ã¢â‚¬Â°tablissement</h2><p>Votre profil public</p></div>
        </div>
        <div class="header-right">
          <button class="header-notification">ÃƒÂ°Ã…Â¸Ã¢â‚¬ÂÃ¢â‚¬Â<span class="notif-dot"></span></button>
          <div class="avatar avatar-sm" id="header-avatar" style="background:linear-gradient(135deg,var(--color-primary),#FF7A3D);">P</div>
        </div>
      </header>

      <div class="page-content">
        <!-- Profile Header -->
        <div class="profile-header animate-fade-in-up">
          <div class="profile-avatar">
            <div class="avatar avatar-2xl" id="partner-avatar" style="font-size:2.5rem;">P</div>
          </div>
          <div class="profile-info">
            <h2 id="partner-name">Mon ÃƒÆ’Ã¢â‚¬Â°tablissement</h2>
            <p class="profile-email" id="partner-email">email@exemple.com</p>
            <div class="profile-meta" style="margin-top:8px;">
              <div class="profile-meta-item"><span>ÃƒÂ°Ã…Â¸Ã¢â‚¬Å“Ã¢â‚¬Â¹</span> <span id="partner-type">Ã¢Ã¢â€šÂ¬Ã¢â‚¬Â</span></div>
              <div class="profile-meta-item"><span>ÃƒÂ°Ã…Â¸Ã¢â‚¬Å“Ã‚Â</span> <span id="partner-address">Ã¢Ã¢â€šÂ¬Ã¢â‚¬Â</span></div>
              <div class="profile-meta-item"><span>ÃƒÂ°Ã…Â¸Ã¢â‚¬Å“Ã‚Â±</span> <span id="partner-phone">Ã¢Ã¢â€šÂ¬Ã¢â‚¬Â</span></div>
            </div>
            <div style="margin-top:12px;" id="partner-status"></div>
          </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-4 gap-4 mb-8 animate-fade-in-up stagger-1">
          <div class="stat-card">
            <div class="stat-icon green">ÃƒÂ°Ã…Â¸Ã‚ÂÃ‚Â½ÃƒÂ¯Ã‚Â¸Ã‚Â</div>
            <div class="stat-value" id="stat-meals">0</div>
            <div class="stat-label">Repas sauvÃƒÆ’Ã‚Â©s</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon yellow">Ã¢Ã‚Â­Ã‚Â</div>
            <div class="stat-value" id="stat-rating">Ã¢Ã¢â€šÂ¬Ã¢â‚¬Â</div>
            <div class="stat-label">Note moyenne</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon blue">ÃƒÂ°Ã…Â¸'Ã‚Â¬</div>
            <div class="stat-value" id="stat-reviews">0</div>
            <div class="stat-label">Avis reÃƒÆ’Ã‚Â§us</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon orange">ÃƒÂ°Ã…Â¸Ã…â€™Ã‚Â¿</div>
            <div class="stat-value" id="stat-co2">0 kg</div>
            <div class="stat-label">COÃ¢Ã¢â‚¬Å¡Ã¢â‚¬Å¡ ÃƒÆ’Ã‚Â©vitÃƒÆ’Ã‚Â©</div>
          </div>
        </div>

        <!-- Description & Hours -->
        <div class="grid grid-2 gap-6 animate-fade-in-up stagger-2">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">ÃƒÂ°Ã…Â¸Ã¢â‚¬Å“Ã‚Â Description</h3>
            </div>
            <p style="color:var(--color-text);line-height:1.7;" id="partner-description">Ã¢Ã¢â€šÂ¬Ã¢â‚¬Â</p>
          </div>

          <div class="card">
            <div class="card-header">
              <h3 class="card-title">ÃƒÂ°Ã…Â¸Ã¢â‚¬Â¢Ã‚Â Horaires d'ouverture</h3>
            </div>
            <div id="partner-hours">
              <!-- Loaded dynamically -->
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="../js/app.js"></script>
  <script src="../js/components.js"></script>
  <script src="../js/partner.js"></script>
  <script>document.addEventListener('DOMContentLoaded', () => Partner.init());</script>
</body>
</html>
