<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Historique de vos commandes CareMeal.">
  <title>Mes Commandes Ã¢Ã¢â€šÂ¬Ã¢â‚¬Â CareMeal</title>
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
          <a href="profile.html" class="sidebar-link"><span class="link-icon">ÃƒÂ°Ã…Â¸Ã¢â‚¬ËœÃ‚Â¤</span> Mon Profil</a>
          <a href="preferences.html" class="sidebar-link"><span class="link-icon">ÃƒÂ°Ã…Â¸Ã‚ÂÃ‚Â½ÃƒÂ¯Ã‚Â¸Ã‚Â</span> Préferences</a>
          <a href="orders.html" class="sidebar-link active"><span class="link-icon">ÃƒÂ°Ã…Â¸Ã¢â‚¬Å“Ã‚Â¦</span> Mes Commandes</a>
          <a href="points.html" class="sidebar-link"><span class="link-icon">Ã¢Ã‚Â­Ã‚Â</span> Mes Points</a>
        </div>
        <div class="sidebar-section">
          <div class="sidebar-section-title">Paramètres</div>
          <a href="settings.html" class="sidebar-link"><span class="link-icon">Ã¢Ã…Â¡Ã¢â€žÂ¢ÃƒÂ¯Ã‚Â¸Ã‚Â</span> Paramètres</a>
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
          <div class="page-title"><h2>Mes Commandes</h2><p>Historique de vos achats</p></div>
        </div>
        <div class="header-right">
          <button class="header-notification">ÃƒÂ°Ã…Â¸Ã¢â‚¬ÂÃ¢â‚¬Â<span class="notif-dot"></span></button>
          <div class="avatar avatar-sm" id="header-avatar">AA</div>
        </div>
      </header>

      <div class="page-content">
        <div class="card animate-fade-in-up">
          <div class="table-container">
            <table class="data-table" id="orders-table">
              <thead>
                <tr>
                  <th>Article</th>
                  <th>Restaurant</th>
                  <th>Prix</th>
                  <th>Date</th>
                  <th>Statut</th>
                  <th>Note</th>
                </tr>
              </thead>
              <tbody id="orders-table-body">
                <!-- Loaded dynamically -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="../js/app.js"></script>
  <script src="../js/components.js"></script>
  <script src="../js/student.js"></script>
  <script>document.addEventListener('DOMContentLoaded', () => Student.initOrders());</script>
</body>
</html>
