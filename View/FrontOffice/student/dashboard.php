<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Votre tableau de bord CareMeal Ã¢Ã¢â€šÂ¬Ã¢â‚¬Â DÃƒÆ’Ã‚Â©couvrez les offres anti-gaspillage prÃƒÆ’Ã‚Â¨s de chez vous.">
  <title>Dashboard Ã¢Ã¢â€šÂ¬Ã¢â‚¬Â CareMeal</title>
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
  <div class="dashboard-layout">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <div class="sidebar-logo">ÃƒÂ°Ã…Â¸Ã‚ÂÃ‚Â½ÃƒÂ¯Ã‚Â¸Ã‚Â</div>
        <div class="sidebar-brand">Care<span>Meal</span></div>
      </div>

      <nav class="sidebar-nav">
        <div class="sidebar-section">
          <div class="sidebar-section-title">Menu</div>
          <a href="dashboard.html" class="sidebar-link active">
            <span class="link-icon">ÃƒÂ°Ã…Â¸Ã‚ÂÃ‚Â </span> Accueil
          </a>
          <a href="profile.html" class="sidebar-link">
            <span class="link-icon">ÃƒÂ°Ã…Â¸Ã¢â‚¬ËœÃ‚Â¤</span> Mon Profil
          </a>
          <a href="preferences.html" class="sidebar-link">
            <span class="link-icon">ÃƒÂ°Ã…Â¸Ã‚ÂÃ‚Â½ÃƒÂ¯Ã‚Â¸Ã‚Â</span> Préferences 
          </a>
          <a href="orders.html" class="sidebar-link">
            <span class="link-icon">ÃƒÂ°Ã…Â¸Ã¢â‚¬Å“Ã‚Â¦</span> Mes Commandes
          </a>
          <a href="points.html" class="sidebar-link">
            <span class="link-icon">Ã¢Ã‚Â­Ã‚Â</span> Mes Points
          </a>
        </div>
        <div class="sidebar-section">
          <div class="sidebar-section-title">Paramètres</div>
          <a href="settings.html" class="sidebar-link">
            <span class="link-icon">Ã¢Ã…Â¡Ã¢â€žÂ¢ÃƒÂ¯Ã‚Â¸Ã‚Â</span> Paramètres
          </a>
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

    <!-- Main -->
    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle">Ã¢Ã‹Å“Ã‚Â°</button>
          <div class="page-title">
            <h2>Accueil</h2>
            <p>Decouvrer les offres du jour</p>
          </div>
        </div>
        <div class="header-right">
          <button class="header-notification">
            ÃƒÂ°Ã…Â¸Ã¢â‚¬ÂÃ¢â‚¬Â
            <span class="notif-dot"></span>
          </button>
          <div class="avatar avatar-sm" id="header-avatar">AA</div>
        </div>
      </header>

      <div class="page-content">
        <!-- Welcome Banner -->
        <div class="welcome-banner animate-fade-in-up">
          <div class="welcome-text">
            <h2>Bonjour, <span id="welcome-name">ÃƒÆ’Ã¢â‚¬Â°tudiant</span> ! ÃƒÂ°Ã…Â¸Ã¢â‚¬ËœÃ¢â‚¬Â¹</h2>
            <p>Vous avez <strong style="color:var(--color-primary)" id="user-points">0</strong> points. Continuez ÃƒÆ’Ã‚Â  sauver des repas !</p>
          </div>
          <div class="welcome-emoji">ÃƒÂ°Ã…Â¸Ã‚Â¥â€”</div>
        </div>

        <!-- Impact Stats -->
        <div class="impact-grid animate-fade-in-up stagger-1">
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

        <!-- Offers -->
        <div class="section animate-fade-in-up stagger-2">
          <div class="section-header">
            <h3>ÃƒÂ°Ã…Â¸Ã¢â‚¬ÂÃ‚Â¥ Offres du jour</h3>
            <span class="badge badge-primary" id="user-level">Niveau 1</span>
          </div>
          <div class="grid grid-3 gap-6" id="offers-grid">
            <!-- Loaded dynamically -->
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="../js/app.js"></script>
  <script src="../js/components.js"></script>
  <script src="../js/student.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => Student.init());
  </script>
</body>
</html>
