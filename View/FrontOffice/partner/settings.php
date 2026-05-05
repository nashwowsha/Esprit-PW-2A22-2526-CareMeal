<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="ParamÃƒÆ’Ã‚Â¨tres de votre ÃƒÆ’Ã‚Â©tablissement CareMeal.">
  <title>ParamÃƒÆ’Ã‚Â¨tres Ã¢Ã¢â€šÂ¬Ã¢â‚¬Â CareMeal Partenaire</title>
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
          <div class="sidebar-section-title">Partenaire</div>
          <a href="dashboard.html" class="sidebar-link"><span class="link-icon">ÃƒÂ°Ã…Â¸Ã‚ÂÃ‚Â </span> Mon ÃƒÆ’Ã¢â‚¬Â°tablissement</a>
          <a href="offers.html" class="sidebar-link"><span class="link-icon">ÃƒÂ°Ã…Â¸Ã¢â‚¬Å“Ã‚Â¦</span> Mes Offres</a>
          <a href="stats.html" class="sidebar-link"><span class="link-icon">ÃƒÂ°Ã…Â¸Ã¢â‚¬Å“Ã…Â </span> Statistiques</a>
          <a href="settings.html" class="sidebar-link active"><span class="link-icon">Ã¢Ã…Â¡Ã¢â€žÂ¢ÃƒÂ¯Ã‚Â¸Ã‚Â</span> ParamÃƒÆ’Ã‚Â¨tres</a>
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
          <div class="page-title"><h2>ParamÃƒÆ’Ã‚Â¨tres</h2><p>Modifiez les informations de votre ÃƒÆ’Ã‚Â©tablissement</p></div>
        </div>
        <div class="header-right">
          <div class="avatar avatar-sm" id="header-avatar" style="background:linear-gradient(135deg,var(--color-primary),#FF7A3D);">P</div>
        </div>
      </header>

      <div class="page-content" style="max-width:700px;">
        <!-- Edit Profile -->
        <div class="section animate-fade-in-up">
          <h3 style="margin-bottom:20px;">ÃƒÂ°Ã…Â¸Ã‚ÂÃ‚Âª Informations de l'ÃƒÆ’Ã‚Â©tablissement</h3>
          <div class="card">
            <div class="form-group">
              <label for="edit-name">Nom de l'ÃƒÆ’Ã‚Â©tablissement</label>
              <div class="input-wrapper">
                <input type="text" id="edit-name" class="form-input" placeholder="Nom de l'ÃƒÆ’Ã‚Â©tablissement">
                <span class="input-icon">ÃƒÂ°Ã…Â¸Ã‚ÂÃ‚Â </span>
              </div>
            </div>
            <div class="form-group">
              <label for="edit-address">Adresse complÃƒÆ’Ã‚Â¨te</label>
              <div class="input-wrapper">
                <input type="text" id="edit-address" class="form-input" placeholder="Rue, ville, code postal">
                <span class="input-icon">ÃƒÂ°Ã…Â¸Ã¢â‚¬Å“Ã‚Â</span>
              </div>
            </div>
            <div class="form-group">
              <label for="edit-phone">TÃƒÆ’Ã‚Â©lÃƒÆ’Ã‚Â©phone</label>
              <div class="input-wrapper">
                <input type="tel" id="edit-phone" class="form-input" placeholder="+216 XX XXX XXX">
                <span class="input-icon">ÃƒÂ°Ã…Â¸Ã¢â‚¬Å“Ã‚Â±</span>
              </div>
            </div>
            <div class="form-group">
              <label for="edit-description">Description</label>
              <textarea id="edit-description" class="form-textarea" placeholder="DÃƒÆ’Ã‚Â©crivez votre ÃƒÆ’Ã‚Â©tablissement..." rows="3"></textarea>
            </div>
            <button class="btn btn-primary" onclick="Partner.saveProfile()" id="btn-save-profile">
              <span>ÃƒÂ°Ã…Â¸'Ã‚Â¾</span> Sauvegarder
            </button>
          </div>
        </div>

        <!-- Change Password -->
        <div class="section animate-fade-in-up stagger-1">
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
            <button class="btn btn-primary" onclick="Partner.updatePassword()" id="btn-update-password">
              <span>ÃƒÂ°Ã…Â¸'Ã‚Â¾</span> Mettre ÃƒÆ’Ã‚Â  jour
            </button>
          </div>
        </div>

        <!-- Notifications -->
        <div class="section animate-fade-in-up stagger-2">
          <h3 style="margin-bottom:20px;">ÃƒÂ°Ã…Â¸Ã¢â‚¬ÂÃ¢â‚¬Â Notifications</h3>
          <div class="settings-group">
            <div class="settings-item">
              <div class="settings-item-left">
                <div class="settings-icon">ÃƒÂ°Ã…Â¸Ã¢â‚¬Å“Ã‚Â§</div>
                <div class="settings-item-info">
                  <h4>Notifications par email</h4>
                  <p>Recevoir les nouvelles commandes par email</p>
                </div>
              </div>
              <label class="switch">
                <input type="checkbox" checked>
                <span class="switch-slider"></span>
              </label>
            </div>
            <div class="settings-item">
              <div class="settings-item-left">
                <div class="settings-icon">Ã¢Ã‚Â­Ã‚Â</div>
                <div class="settings-item-info">
                  <h4>Alertes nouveaux avis</h4>
                  <p>ÃƒÆ’Ã…Â tre notifiÃƒÆ’Ã‚Â© quand vous recevez un nouvel avis</p>
                </div>
              </div>
              <label class="switch">
                <input type="checkbox" checked>
                <span class="switch-slider"></span>
              </label>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="../js/app.js"></script>
  <script src="../js/components.js"></script>
  <script src="../js/partner.js"></script>
  <script src="../js/auth.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      Partner.initSettings();
      Auth.initPasswordToggles();
    });
  </script>
</body>
</html>
