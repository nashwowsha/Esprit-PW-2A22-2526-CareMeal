<?php require_once dirname(__DIR__, 2) . '/session_check.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Votre profil CareMeal — Gérez vos informations personnelles.">
  <title>Mon Profil — CareMeal</title>
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
          <a href="preferences.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-utensils"></i></span> Préférences</a>
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
          <div class="page-title"><h2>Mon Profil</h2><p>Vos informations personnelles</p></div>
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
                <span><i class="fa-solid fa-graduation-cap"></i> </span> <span id="profile-university">—</span>
              </div>
              <div class="profile-meta-item">
                <span><i class="fa-solid fa-location-dot"></i></span> <span id="profile-quartier">—</span>
              </div>
              <div class="profile-meta-item">
                <span><i class="fa-solid fa-calendar"></i> </span> Inscrit le <span id="profile-joined">—</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Preferences -->
        <div class="section animate-fade-in-up stagger-1">
          <div class="section-header">
            <h3><i class="fa-solid fa-utensils"></i> Préférences alimentaires</h3>
            <a href="preferences.php" class="btn btn-sm btn-outline">Modifier</a>
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
            <h3><i class="fa-solid fa-leaf"></i> Votre impact</h3>
          </div>
          <div class="impact-grid">
            <div class="impact-card">
              <div class="impact-icon"><i class="fa-solid fa-utensils"></i></div>
              <div class="impact-value" id="impact-meals">0</div>
              <div class="impact-label">Repas sauvés</div>
            </div>
            <div class="impact-card">
              <div class="impact-icon"><i class="fa-solid fa-earth-europe"></i></div>
              <div class="impact-value" id="impact-co2">0 kg</div>
              <div class="impact-label">CO2 évité</div>
            </div>
            <div class="impact-card">
              <div class="impact-icon"><i class="fa-solid fa-coins"></i></div>
              <div class="impact-value" id="impact-money">0 DT</div>
              <div class="impact-label">Économisés</div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="/projet2a22/js/app.js"></script>
  <script src="/projet2a22/js/components.js"></script>
  <script src="/projet2a22/js/student.js"></script>
  <script>document.addEventListener('DOMContentLoaded', () => Student.initProfile());</script>
</body>
</html>





