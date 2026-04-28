<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Statistiques de votre ÃƒÆ’Ã‚Â©tablissement sur CareMeal.">
  <title>Statistiques Ã¢Ã¢â€šÂ¬Ã¢â‚¬Â CareMeal</title>
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
          <a href="dashboard.html" class="sidebar-link"><span class="link-icon">ÃƒÂ°Ã…Â¸Ã‚ÂÃ‚Â </span> Mon ÃƒÆ’Ã¢â‚¬Â°tablissement</a>
          <a href="offers.html" class="sidebar-link"><span class="link-icon">ÃƒÂ°Ã…Â¸Ã¢â‚¬Å“Ã‚Â¦</span> Mes Offres</a>
          <a href="stats.html" class="sidebar-link active"><span class="link-icon">ÃƒÂ°Ã…Â¸Ã¢â‚¬Å“Ã…Â </span> Statistiques</a>
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
          <div class="page-title"><h2>Statistiques</h2><p>Performance de votre ÃƒÆ’Ã‚Â©tablissement</p></div>
        </div>
        <div class="header-right">
          <div class="avatar avatar-sm" id="header-avatar" style="background:linear-gradient(135deg,var(--color-primary),#FF7A3D);">P</div>
        </div>
      </header>

      <div class="page-content">
        <!-- Main Stats -->
        <div class="grid grid-4 gap-4 mb-8 animate-fade-in-up">
          <div class="stat-card">
            <div class="stat-icon green">ÃƒÂ°Ã…Â¸Ã‚ÂÃ‚Â½ÃƒÂ¯Ã‚Â¸Ã‚Â</div>
            <div class="stat-value" id="stats-meals">0</div>
            <div class="stat-label">Repas sauvÃƒÆ’Ã‚Â©s</div>
            <div class="stat-change up">Ã¢Ã¢â‚¬Â Ã¢â‚¬Ëœ +15%</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon orange">ÃƒÂ°Ã…Â¸Ã…â€™Ã‚Â¿</div>
            <div class="stat-value" id="stats-co2">0 kg</div>
            <div class="stat-label">COÃ¢Ã¢â‚¬Å¡Ã¢â‚¬Å¡ ÃƒÆ’Ã‚Â©vitÃƒÆ’Ã‚Â©</div>
            <div class="stat-change up">Ã¢Ã¢â‚¬Â Ã¢â‚¬Ëœ +12%</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon yellow">Ã¢Ã‚Â­Ã‚Â</div>
            <div class="stat-value" id="stats-rating">Ã¢Ã¢â€šÂ¬Ã¢â‚¬Â</div>
            <div class="stat-label">Note moyenne</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon blue">ÃƒÂ°Ã…Â¸'Ã‚Â°</div>
            <div class="stat-value" id="stats-revenue">0 DT</div>
            <div class="stat-label">Revenus gÃƒÆ’Ã‚Â©nÃƒÆ’Ã‚Â©rÃƒÆ’Ã‚Â©s</div>
            <div class="stat-change up">Ã¢Ã¢â‚¬Â Ã¢â‚¬Ëœ +20%</div>
          </div>
        </div>

        <!-- Secondary stats -->
        <div class="grid grid-3 gap-4 mb-8 animate-fade-in-up stagger-1">
          <div class="card" style="display:flex;align-items:center;gap:16px;">
            <div class="stat-icon blue" style="width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;">ÃƒÂ°Ã…Â¸'Ã‚Â¬</div>
            <div>
              <div style="font-size:1.5rem;font-weight:700;color:var(--color-white);" id="stats-reviews">0</div>
              <div style="font-size:0.8rem;color:var(--color-text-muted);">Avis reÃƒÆ’Ã‚Â§us</div>
            </div>
          </div>
          <div class="card" style="display:flex;align-items:center;gap:16px;">
            <div class="stat-icon green" style="width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;">Ã¢Ã…â€œÃ¢â‚¬Â¦</div>
            <div>
              <div style="font-size:1.5rem;font-weight:700;color:var(--color-white);" id="stats-offers-active">0</div>
              <div style="font-size:0.8rem;color:var(--color-text-muted);">Offres actives</div>
            </div>
          </div>
          <div class="card" style="display:flex;align-items:center;gap:16px;">
            <div class="stat-icon orange" style="width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;">ÃƒÂ°Ã…Â¸Ã¢â‚¬Å“Ã‚Â¦</div>
            <div>
              <div style="font-size:1.5rem;font-weight:700;color:var(--color-white);" id="stats-orders">0</div>
              <div style="font-size:0.8rem;color:var(--color-text-muted);">Commandes totales</div>
            </div>
          </div>
        </div>

        <!-- Reviews -->
        <div class="section animate-fade-in-up stagger-2">
          <div class="section-header">
            <h3>ÃƒÂ°Ã…Â¸'Ã‚Â¬ Derniers avis</h3>
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

  <script src="../js/app.js"></script>
  <script src="../js/components.js"></script>
  <script src="../js/partner.js"></script>
  <script>document.addEventListener('DOMContentLoaded', () => Partner.initStats());</script>
</body>
</html>
