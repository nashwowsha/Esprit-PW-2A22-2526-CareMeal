<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Votre profil CareMeal Ã¢Ã¢â€šÂ¬Ã¢â‚¬Â GÃƒÆ’Ã‚Â©rez vos informations personnelles.">
  <title>Mon Profil Ã¢Ã¢â€šÂ¬Ã¢â‚¬Â CareMeal</title>
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
          <div class="sidebar-section-title">Menu</div>
          <a href="dashboard.html" class="sidebar-link"><span class="link-icon">ÃƒÂ°Ã…Â¸Ã‚ÂÃ‚Â </span> Accueil</a>
          <a href="profile.html" class="sidebar-link active"><span class="link-icon">ÃƒÂ°Ã…Â¸Ã¢â‚¬ËœÃ‚Â¤</span> Mon Profil</a>
          <a href="preferences.html" class="sidebar-link"><span class="link-icon">ÃƒÂ°Ã…Â¸Ã‚ÂÃ‚Â½ÃƒÂ¯Ã‚Â¸Ã‚Â</span> PrÃƒÆ’Ã‚Â©fÃƒÆ’Ã‚Â©rences</a>
          <a href="orders.html" class="sidebar-link"><span class="link-icon">ÃƒÂ°Ã…Â¸Ã¢â‚¬Å“Ã‚Â¦</span> Mes Commandes</a>
          <a href="points.html" class="sidebar-link"><span class="link-icon">Ã¢Ã‚Â­Ã‚Â</span> Mes Points</a>
        </div>
        <div class="sidebar-section">
          <div class="sidebar-section-title">ParamÃƒÆ’Ã‚Â¨tres</div>
          <a href="settings.html" class="sidebar-link"><span class="link-icon">Ã¢Ã…Â¡Ã¢â€žÂ¢ÃƒÂ¯Ã‚Â¸Ã‚Â</span> ParamÃƒÆ’Ã‚Â¨tres</a>
        </div>
      </nav>
      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="avatar" id="sidebar-user-avatar">AA</div>
          <div class="sidebar-user-info">
            <div class="sidebar-user-name" id="sidebar-user-name">Utilisateur</div>
            <div class="sidebar-user-role" id="sidebar-user-role">ÃƒÆ’Ã¢â‚¬Â°tudiant</div>
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
          <div class="page-title"><h2>Mon Profil</h2><p>Vos informations personnelles</p></div>
        </div>
        <div class="header-right">
          <button class="header-notification">ÃƒÂ°Ã…Â¸Ã¢â‚¬ÂÃ¢â‚¬Â<span class="notif-dot"></span></button>
          <div class="avatar avatar-sm" id="header-avatar">AA</div>
        </div>
      </header>

      <div class="page-content">
        <!-- Profile Header -->
        <div class="profile-header animate-fade-in-up">
          <div class="profile-avatar">
            <div class="avatar avatar-2xl" id="profile-avatar-initials">AA</div>
          </div>
          <div class="profile-info">
            <h2 id="profile-name">Nom Complet</h2>
            <p class="profile-email" id="profile-email">email@example.com</p>
            <div class="profile-meta">
              <div class="profile-meta-item">
                <span>ÃƒÂ°Ã…Â¸Ã‚ÂÃ‚Â«</span> <span id="profile-university">Ã¢Ã¢â€šÂ¬Ã¢â‚¬Â</span>
              </div>
              <div class="profile-meta-item">
                <span>ÃƒÂ°Ã…Â¸Ã¢â‚¬Å“Ã‚Â</span> <span id="profile-quartier">Ã¢Ã¢â€šÂ¬Ã¢â‚¬Â</span>
              </div>
              <div class="profile-meta-item">
                <span>ÃƒÂ°Ã…Â¸Ã¢â‚¬Å“Ã¢â‚¬Â¦</span> Inscrit le <span id="profile-joined">Ã¢Ã¢â€šÂ¬Ã¢â‚¬Â</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Preferences -->
        <div class="section animate-fade-in-up stagger-1">
          <div class="section-header">
            <h3>ÃƒÂ°Ã…Â¸Ã‚ÂÃ‚Â½ÃƒÂ¯Ã‚Â¸Ã‚Â PrÃƒÆ’Ã‚Â©fÃƒÆ’Ã‚Â©rences alimentaires</h3>
            <a href="preferences.html" class="btn btn-sm btn-outline">Modifier</a>
          </div>
          <div class="card">
            <div class="tags-grid" id="profile-preferences">
              <!-- Loaded dynamically -->
            </div>
          </div>
        </div>

        <!-- Impact -->
        <div class="section animate-fade-in-up stagger-2">
          <div class="section-header">
            <h3>ÃƒÂ°Ã…Â¸Ã…â€™Ã‚Â Votre impact</h3>
          </div>
          <div class="impact-grid">
            <div class="impact-card">
              <div class="impact-icon">ÃƒÂ°Ã…Â¸Ã‚ÂÃ‚Â½ÃƒÂ¯Ã‚Â¸Ã‚Â</div>
              <div class="impact-value" id="impact-meals">0</div>
              <div class="impact-label">Repas sauvÃƒÆ’Ã‚Â©s</div>
            </div>
            <div class="impact-card">
              <div class="impact-icon">ÃƒÂ°Ã…Â¸Ã…â€™Ã‚Â¿</div>
              <div class="impact-value" id="impact-co2">0 kg</div>
              <div class="impact-label">COÃ¢Ã¢â‚¬Å¡Ã¢â‚¬Å¡ ÃƒÆ’Ã‚Â©vitÃƒÆ’Ã‚Â©</div>
            </div>
            <div class="impact-card">
              <div class="impact-icon">ÃƒÂ°Ã…Â¸'Ã‚Â°</div>
              <div class="impact-value" id="impact-money">0 DT</div>
              <div class="impact-label">ÃƒÆ’Ã¢â‚¬Â°conomisÃƒÆ’Ã‚Â©s</div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="../js/app.js"></script>
  <script src="../js/components.js"></script>
  <script src="../js/student.js"></script>
  <script>document.addEventListener('DOMContentLoaded', () => Student.initProfile());</script>
</body>
</html>
