<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="GÃƒÆ’Ã‚Â©rez vos prÃƒÆ’Ã‚Â©fÃƒÆ’Ã‚Â©rences alimentaires sur CareMeal.">
  <title>PrÃƒÆ’Ã‚Â©fÃƒÆ’Ã‚Â©rences Ã¢Ã¢â€šÂ¬Ã¢â‚¬Â CareMeal</title>
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
          <a href="preferences.html" class="sidebar-link active"><span class="link-icon">ÃƒÂ°Ã…Â¸Ã‚ÂÃ‚Â½ÃƒÂ¯Ã‚Â¸Ã‚Â</span> PrÃƒÆ’Ã‚Â©fÃƒÆ’Ã‚Â©rences</a>
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
          <div class="page-title"><h2>PrÃƒÆ’Ã‚Â©fÃƒÆ’Ã‚Â©rences alimentaires</h2><p>Personnalisez vos recommandations</p></div>
        </div>
        <div class="header-right">
          <button class="header-notification">ÃƒÂ°Ã…Â¸Ã¢â‚¬ÂÃ¢â‚¬Â<span class="notif-dot"></span></button>
          <div class="avatar avatar-sm" id="header-avatar">AA</div>
        </div>
      </header>

      <div class="page-content">
        <div class="card animate-fade-in-up" style="max-width:700px;">
          <div class="card-header">
            <h3 class="card-title">ÃƒÂ°Ã…Â¸Ã‚Â¥â€” Vos rÃƒÆ’Ã‚Â©gimes et allergies</h3>
          </div>
          <p style="color:var(--color-text-muted);font-size:0.9rem;margin-bottom:24px;">
            SÃƒÆ’Ã‚Â©lectionnez vos prÃƒÆ’Ã‚Â©fÃƒÆ’Ã‚Â©rences pour recevoir des offres adaptÃƒÆ’Ã‚Â©es.
          </p>

          <div class="tags-grid" style="margin-bottom:32px;">
            <div class="tag" data-value="halal"><span class="tag-emoji">Ã¢Ã‹Å“Ã‚ÂªÃƒÂ¯Ã‚Â¸Ã‚Â</span> Halal</div>
            <div class="tag" data-value="vegetarien"><span class="tag-emoji">ÃƒÂ°Ã…Â¸Ã‚Â¥Ã‚Â¬</span> VÃƒÆ’Ã‚Â©gÃƒÆ’Ã‚Â©tarien</div>
            <div class="tag" data-value="vegan"><span class="tag-emoji">ÃƒÂ°Ã…Â¸Ã…â€™Ã‚Â±</span> VÃƒÆ’Ã‚Â©gan</div>
            <div class="tag" data-value="sans-gluten"><span class="tag-emoji">ÃƒÂ°Ã…Â¸Ã…â€™Ã‚Â¾</span> Sans gluten</div>
            <div class="tag" data-value="bio"><span class="tag-emoji">ÃƒÂ°Ã…Â¸Ã‚ÂÃ†â€™</span> Bio</div>
            <div class="tag" data-value="sans-lactose"><span class="tag-emoji">ÃƒÂ°Ã…Â¸Ã‚Â¥Ã¢â‚¬Âº</span> Sans lactose</div>
            <div class="tag" data-value="budget"><span class="tag-emoji">ÃƒÂ°Ã…Â¸'Ã‚Â°</span> Petit budget</div>
            <div class="tag" data-value="equilibre"><span class="tag-emoji">Ã¢Ã…Â¡Ã¢â‚¬â€œÃƒÂ¯Ã‚Â¸Ã‚Â</span> ÃƒÆ’Ã¢â‚¬Â°quilibrÃƒÆ’Ã‚Â©</div>
            <div class="tag" data-value="sans-noix"><span class="tag-emoji">ÃƒÂ°Ã…Â¸Ã‚Â¥Ã…â€œ</span> Sans fruits ÃƒÆ’Ã‚Â  coque</div>
            <div class="tag" data-value="faible-sucre"><span class="tag-emoji">ÃƒÂ°Ã…Â¸Ã‚ÂÃ‚Â¬</span> Faible en sucre</div>
          </div>

          <div style="margin-bottom:32px;">
            <label style="display:block;font-weight:500;margin-bottom:12px;color:var(--color-white);">ÃƒÂ°Ã…Â¸'Ã‚Â° Budget maximum par repas</label>
            <div class="range-slider">
              <input type="range" id="pref-budget" min="2" max="20" value="8" step="1">
              <div class="range-value" id="budget-value">8 DT</div>
            </div>
          </div>

          <div style="margin-bottom:32px;">
            <label for="pref-frequency" style="display:block;font-weight:500;margin-bottom:12px;color:var(--color-white);">ÃƒÂ°Ã…Â¸Ã¢â‚¬Å“Ã¢â‚¬Â¦ FrÃƒÆ’Ã‚Â©quence de commande souhaitÃƒÆ’Ã‚Â©e</label>
            <select id="pref-frequency" class="form-select no-icon" style="padding-left:16px;background:rgba(255,255,255,0.05);border:1px solid var(--color-dark-border);border-radius:var(--radius-md);color:var(--color-white);height:48px;">
              <option value="quotidien">Tous les jours</option>
              <option value="hebdomadaire">Quelques fois par semaine</option>
              <option value="occasionnel">Occasionnellement</option>
            </select>
          </div>

          <button class="btn btn-primary btn-lg" onclick="Student.savePreferences()" id="btn-save-prefs">
            <span>ÃƒÂ°Ã…Â¸'Ã‚Â¾</span> Sauvegarder mes prÃƒÆ’Ã‚Â©fÃƒÆ’Ã‚Â©rences
          </button>
        </div>
      </div>
    </main>
  </div>

  <script src="../js/app.js"></script>
  <script src="../js/components.js"></script>
  <script src="../js/student.js"></script>
  <script>document.addEventListener('DOMContentLoaded', () => Student.initPreferences());</script>
</body>
</html>
