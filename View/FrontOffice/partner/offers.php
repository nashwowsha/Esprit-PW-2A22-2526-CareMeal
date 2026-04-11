<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="GÃƒÆ’Ã‚Â©rez vos offres anti-gaspillage sur CareMeal.">
  <title>Mes Offres Ã¢Ã¢â€šÂ¬Ã¢â‚¬Â CareMeal</title>
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
          <a href="offers.html" class="sidebar-link active"><span class="link-icon">ÃƒÂ°Ã…Â¸Ã¢â‚¬Å“Ã‚Â¦</span> Mes Offres</a>
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
          <div class="page-title"><h2>Mes Offres</h2><p>GÃƒÆ’Ã‚Â©rez vos offres anti-gaspillage</p></div>
        </div>
        <div class="header-right">
          <div class="avatar avatar-sm" id="header-avatar" style="background:linear-gradient(135deg,var(--color-primary),#FF7A3D);">P</div>
        </div>
      </header>

      <div class="page-content">
        <!-- Summary -->
        <div class="grid grid-2 gap-4 mb-8 animate-fade-in-up">
          <div class="card" style="display:flex;align-items:center;gap:16px;">
            <div class="stat-icon green" style="width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;">Ã¢Ã…â€œÃ¢â‚¬Â¦</div>
            <div>
              <div style="font-size:1.5rem;font-weight:700;color:var(--color-white);" id="active-count">0</div>
              <div style="font-size:0.8rem;color:var(--color-text-muted);">Offres actives</div>
            </div>
          </div>
          <div class="card" style="display:flex;align-items:center;gap:16px;">
            <div class="stat-icon yellow" style="width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;">Ã¢Ã‚ÂÃ‚Â°</div>
            <div>
              <div style="font-size:1.5rem;font-weight:700;color:var(--color-text-muted);" id="expired-count">0</div>
              <div style="font-size:0.8rem;color:var(--color-text-muted);">Offres expirÃƒÆ’Ã‚Â©es</div>
            </div>
          </div>
        </div>

        <!-- Active Offers -->
        <div class="section animate-fade-in-up stagger-1">
          <div class="section-header">
            <h3>Ã¢Ã…â€œÃ¢â‚¬Â¦ Offres actives</h3>
          </div>
          <div class="grid grid-3 gap-6" id="active-offers">
            <!-- Loaded dynamically -->
          </div>
        </div>

        <!-- Expired Offers -->
        <div class="section animate-fade-in-up stagger-2">
          <div class="section-header">
            <h3>Ã¢Ã‚ÂÃ‚Â° Offres expirÃƒÆ’Ã‚Â©es</h3>
          </div>
          <div class="grid grid-3 gap-6" id="expired-offers">
            <!-- Loaded dynamically -->
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="../js/app.js"></script>
  <script src="../js/components.js"></script>
  <script src="../js/partner.js"></script>
  <script>document.addEventListener('DOMContentLoaded', () => Partner.initOffers());</script>
</body>
</html>
