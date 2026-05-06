<?php require_once dirname(__DIR__, 2) . '/session_check.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Gérez vos préférences alimentaires sur CareMeal.">
  <title>Préférences — CareMeal</title>
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
          <div class="sidebar-section-title">Menu</div>
                    <a href="dashboard.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-chart-line"></i></span> Mon Dashboard</a>
          <a href="preferences.php" class="sidebar-link active"><span class="link-icon"><i class="fa-solid fa-utensils"></i></span> Préférences</a>
          <a href="orders.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-bag-shopping"></i></span> Mes Commandes</a>
        </div>
        <div class="sidebar-section">
          <div class="sidebar-section-title">Paramètres</div>
          <a href="settings.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-gear"></i></span> Paramètres</a>
        </div>
      </nav>
      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="avatar" id="sidebar-user-avatar">AA</div>
          <div class="sidebar-user-info">
            <div class="sidebar-user-name" id="sidebar-user-name">Utilisateur</div>
            <div class="sidebar-user-role" id="sidebar-user-role">Étudiant</div>
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
          <div class="page-title"><h2>Préférences alimentaires</h2><p>Personnalisez vos recommandations</p></div>
        </div>
        <div class="header-right">
                    <a href="/projet2a22/View/FrontOffice/feed.php" title="Retour au Feed" style="display: flex; align-items: center; color: #FE5516; background: rgba(254,85,22,0.1); border-radius: 20px; padding: 6px 16px; font-size: 0.95rem; font-weight: 600; text-decoration: none; margin-right: 8px; transition: all 0.2s;">
            <i class="fa-solid fa-house" style="margin-right: 8px;"></i> Retour au Feed
          </a>
          <button class="header-notification"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button>
          <div class="avatar avatar-sm" id="header-avatar">AA</div>
        </div>
      </header>

      <div class="page-content">
        <div class="card animate-fade-in-up" style="max-width:700px;">
          <div class="card-header">
            <h3 class="card-title">🥳 Vos régimes et allergies</h3>
          </div>
          <p style="color:var(--color-text-muted);font-size:0.9rem;margin-bottom:24px;">
            Sélectionnez vos préférences pour recevoir des offres adaptées.
          </p>

          <div class="tags-grid" style="margin-bottom:32px;">
            <div class="tag" data-value="halal"><span class="tag-emoji"><i class="fa-solid fa-star-and-crescent"></i> </span> Halal</div>
            <div class="tag" data-value="vegetarien"><span class="tag-emoji"><i class="fa-solid fa-leaf"></i> </span> Végétarien</div>
            <div class="tag" data-value="vegan"><span class="tag-emoji"><i class="fa-solid fa-seedling"></i> </span> Végan</div>
            <div class="tag" data-value="sans-gluten"><span class="tag-emoji"><i class="fa-solid fa-wheat-awn"></i> </span> Sans gluten</div>
            <div class="tag" data-value="bio"><span class="tag-emoji"><i class="fa-solid fa-leaf"></i> </span> Bio</div>
            <div class="tag" data-value="sans-lactose"><span class="tag-emoji"><i class="fa-solid fa-glass-water"></i> </span> Sans lactose</div>
            <div class="tag" data-value="budget"><span class="tag-emoji"><i class="fa-solid fa-coins"></i></span> Petit budget</div>
            <div class="tag" data-value="equilibre"><span class="tag-emoji"><i class="fa-solid fa-scale-balanced"></i> </span> Équilibré</div>
            <div class="tag" data-value="sans-noix"><span class="tag-emoji"><i class="fa-solid fa-seedling"></i> </span> Sans fruits à coque</div>
            <div class="tag" data-value="faible-sucre"><span class="tag-emoji"><i class="fa-solid fa-candy-cane"></i> </span> Faible en sucre</div>
          </div>

          <div style="margin-bottom:32px;">
            <label style="display:block;font-weight:500;margin-bottom:12px;color:var(--color-white);"><i class="fa-solid fa-coins"></i> Budget maximum par repas</label>
            <div class="range-slider">
              <input type="range" id="pref-budget"   value="8" step="1">
              <div class="range-value" id="budget-value">8 DT</div>
            </div>
          </div>

          <div style="margin-bottom:32px;">
            <label for="pref-frequency" style="display:block;font-weight:500;margin-bottom:12px;color:var(--color-white);"><i class="fa-solid fa-clock"></i> Fréquence de commande souhaitée</label>
            <select id="pref-frequency" class="form-select no-icon" style="padding-left:16px;background:rgba(255,255,255,0.05);border:1px solid var(--color-dark-border);border-radius:var(--radius-md);color:var(--color-white);height:48px;">
              <option value="quotidien">Tous les jours</option>
              <option value="hebdomadaire">Quelques fois par semaine</option>
              <option value="occasionnel">Occasionnellement</option>
            </select>
          </div>

          <button class="btn btn-primary btn-lg" onclick="Student.savePreferences()" id="btn-save-prefs">
            <span><i class="fa-solid fa-check"></i> </span> Sauvegarder mes préférences
          </button>
        </div>
      </div>
    </main>
  </div>

  <script src="/projet2a22/js/app.js"></script>
  <script src="/projet2a22/js/components.js"></script>
  <script src="/projet2a22/js/student.js"></script>
  <script>document.addEventListener('DOMContentLoaded', () => Student.initPreferences());</script>
</body>
</html>







