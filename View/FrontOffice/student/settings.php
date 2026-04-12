<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="ParamÃƒÆ’Ã‚Â¨tres de votre compte CareMeal.">
  <title>ParamÃƒÆ’Ã‚Â¨tres Ã¢Ã¢â€šÂ¬Ã¢â‚¬Â CareMeal</title>
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="../css/auth.css">
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
          <a href="preferences.html" class="sidebar-link"><span class="link-icon">ÃƒÂ°Ã…Â¸Ã‚ÂÃ‚Â½ÃƒÂ¯Ã‚Â¸Ã‚Â</span> PrÃƒÆ’Ã‚Â©fÃƒÆ’Ã‚Â©rences</a>
          <a href="orders.html" class="sidebar-link"><span class="link-icon">ÃƒÂ°Ã…Â¸Ã¢â‚¬Å“Ã‚Â¦</span> Mes Commandes</a>
          <a href="points.html" class="sidebar-link"><span class="link-icon">Ã¢Ã‚Â­Ã‚Â</span> Mes Points</a>
        </div>
        <div class="sidebar-section">
          <div class="sidebar-section-title">ParamÃƒÆ’Ã‚Â¨tres</div>
          <a href="settings.html" class="sidebar-link active"><span class="link-icon">Ã¢Ã…Â¡Ã¢â€žÂ¢ÃƒÂ¯Ã‚Â¸Ã‚Â</span> ParamÃƒÆ’Ã‚Â¨tres</a>
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
          <div class="page-title"><h2>ParamÃƒÆ’Ã‚Â¨tres</h2><p>GÃƒÆ’Ã‚Â©rez votre compte</p></div>
        </div>
        <div class="header-right">
          <button class="header-notification">ÃƒÂ°Ã…Â¸Ã¢â‚¬ÂÃ¢â‚¬Â<span class="notif-dot"></span></button>
          <div class="avatar avatar-sm" id="header-avatar">AA</div>
        </div>
      </header>

      <div class="page-content" style="max-width:700px;">
        <!-- Change Password -->
        <div class="section animate-fade-in-up">
          <h3 style="margin-bottom:20px;">ÃƒÂ°Ã…Â¸Ã¢â‚¬Â' Modifier le mot de passe</h3>
          <div class="card">
            <div class="form-group">
              <label for="current-password">Mot de passe actuel</label>
              <div class="input-wrapper">
                <input type="password" id="current-password" class="form-input" placeholder="Votre mot de passe actuel">
                <span class="input-icon">ÃƒÂ°Ã…Â¸Ã¢â‚¬ÂÃ¢â‚¬Ëœ</span>
                <button type="button" class="password-toggle">ÃƒÂ°Ã…Â¸Ã¢â‚¬ËœÃ‚ÂÃƒÂ¯Ã‚Â¸Ã‚Â</button>
              </div>
            </div>
            <div class="form-group">
              <label for="new-password">Nouveau mot de passe</label>
              <div class="input-wrapper">
                <input type="password" id="new-password" class="form-input" placeholder="Minimum 6 caractÃƒÆ’Ã‚Â¨res">
                <span class="input-icon">ÃƒÂ°Ã…Â¸Ã¢â‚¬Â'</span>
                <button type="button" class="password-toggle">ÃƒÂ°Ã…Â¸Ã¢â‚¬ËœÃ‚ÂÃƒÂ¯Ã‚Â¸Ã‚Â</button>
              </div>
            </div>
            <div class="form-group">
              <label for="confirm-new-password">Confirmer le nouveau mot de passe</label>
              <div class="input-wrapper">
                <input type="password" id="confirm-new-password" class="form-input" placeholder="Retapez le nouveau mot de passe">
                <span class="input-icon">ÃƒÂ°Ã…Â¸Ã¢â‚¬Â'</span>
              </div>
            </div>
            <button class="btn btn-primary" onclick="Student.updatePassword()" id="btn-update-password">
              <span>ÃƒÂ°Ã…Â¸'Ã‚Â¾</span> Mettre ÃƒÆ’Ã‚Â  jour
            </button>
          </div>
        </div>

        <!-- Notifications -->
        <div class="section animate-fade-in-up stagger-1">
          <h3 style="margin-bottom:20px;">ÃƒÂ°Ã…Â¸Ã¢â‚¬ÂÃ¢â‚¬Â Notifications</h3>
          <div class="settings-group">
            <div class="settings-item">
              <div class="settings-item-left">
                <div class="settings-icon">ÃƒÂ°Ã…Â¸Ã¢â‚¬Å“Ã‚Â§</div>
                <div class="settings-item-info">
                  <h4>Notifications par email</h4>
                  <p>Recevoir les offres et nouveautÃƒÆ’Ã‚Â©s par email</p>
                </div>
              </div>
              <label class="switch">
                <input type="checkbox" checked>
                <span class="switch-slider"></span>
              </label>
            </div>
            <div class="settings-item">
              <div class="settings-item-left">
                <div class="settings-icon">ÃƒÂ°Ã…Â¸Ã¢â‚¬ÂÃ¢â‚¬Â</div>
                <div class="settings-item-info">
                  <h4>Alertes nouvelles offres</h4>
                  <p>ÃƒÆ’Ã…Â tre notifiÃƒÆ’Ã‚Â© quand une offre correspond ÃƒÆ’Ã‚Â  vos prÃƒÆ’Ã‚Â©fÃƒÆ’Ã‚Â©rences</p>
                </div>
              </div>
              <label class="switch">
                <input type="checkbox" checked>
                <span class="switch-slider"></span>
              </label>
            </div>
            <div class="settings-item">
              <div class="settings-item-left">
                <div class="settings-icon">ÃƒÂ°Ã…Â¸Ã¢â‚¬Å“Ã…Â </div>
                <div class="settings-item-info">
                  <h4>RÃƒÆ’Ã‚Â©sumÃƒÆ’Ã‚Â© hebdomadaire</h4>
                  <p>Recevoir un rÃƒÆ’Ã‚Â©capitulatif de votre impact chaque semaine</p>
                </div>
              </div>
              <label class="switch">
                <input type="checkbox">
                <span class="switch-slider"></span>
              </label>
            </div>
          </div>
        </div>

        <!-- Danger Zone -->
        <div class="section animate-fade-in-up stagger-2">
          <h3 style="margin-bottom:20px;">Ã¢Ã…Â¡Ã‚Â ÃƒÂ¯Ã‚Â¸Ã‚Â Zone dangereuse</h3>
          <div class="settings-group">
            <div class="settings-item danger">
              <div class="settings-item-left">
                <div class="settings-icon">ÃƒÂ°Ã…Â¸â€”Ã¢â‚¬ËœÃƒÂ¯Ã‚Â¸Ã‚Â</div>
                <div class="settings-item-info">
                  <h4>Supprimer mon compte</h4>
                  <p>Action irrÃƒÆ’Ã‚Â©versible Ã¢Ã¢â€šÂ¬Ã¢â‚¬Â toutes vos donnÃƒÆ’Ã‚Â©es seront perdues</p>
                </div>
              </div>
              <button class="btn btn-danger btn-sm" onclick="Student.deleteAccount()" id="btn-delete-account">Supprimer</button>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="../js/app.js"></script>
  <script src="../js/components.js"></script>
  <script src="../js/student.js"></script>
  <script src="../js/auth.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      Student.initSettings();
      Auth.initPasswordToggles();
    });
  </script>
</body>
</html>
