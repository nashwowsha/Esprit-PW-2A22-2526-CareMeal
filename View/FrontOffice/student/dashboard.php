<?php require_once dirname(__DIR__, 2) . '/session_check.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Votre tableau de bord CareMeal — Découvrez les offres anti-gaspillage près de chez vous.">
  <title>Dashboard — CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/projet2a22/css/main.css">
  <link rel="stylesheet" href="/projet2a22/css/components.css">
  <link rel="stylesheet" href="/projet2a22/css/dashboard.css">
</head>
<body>
  <div class="dashboard-layout">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <div class="sidebar-logo"><i class="fa-solid fa-utensils"></i></div>
        <div class="sidebar-brand">Care<span>Meal</span></div>
      </div>

      <nav class="sidebar-nav">
        <div class="sidebar-section">
          <div class="sidebar-section-title">Menu</div>
          <a href="dashboard.php" class="sidebar-link active">
            <span class="link-icon"><i class="fa-solid fa-house"></i></span> Accueil
          </a>
          <a href="profile.php" class="sidebar-link">
            <span class="link-icon">👤</span> Mon Profil
          </a>
          <a href="preferences.php" class="sidebar-link">
            <span class="link-icon"><i class="fa-solid fa-utensils"></i></span> Préférences
          </a>
          <a href="orders.php" class="sidebar-link">
            <span class="link-icon"><i class="fa-solid fa-bag-shopping"></i></span> Mes Commandes
          </a>
          <a href="points.php" class="sidebar-link">
            <span class="link-icon"><i class="fa-solid fa-star"></i></span> Mes Points
          </a>
        </div>
        <div class="sidebar-section">
          <div class="sidebar-section-title">Paramètres</div>
          <a href="settings.php" class="sidebar-link">
            <span class="link-icon"><i class="fa-solid fa-gear"></i></span> Paramètres
          </a>
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

    <!-- Main -->
    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle">☰</button>
          <div class="page-title">
            <h2>Accueil</h2>
            <p>Découvrez les offres du jour</p>
          </div>
        </div>
        <div class="header-right">
          <button class="header-notification">
            <i class="fa-solid fa-bell"></i>
            <span class="notif-dot"></span>
          </button>
          <div class="avatar avatar-sm" id="header-avatar">AA</div>
        </div>
      </header>

      <div class="page-content">
        <!-- Welcome Banner -->
        <div class="welcome-banner animate-fade-in-up">
          <div class="welcome-text">
            <h2>Bonjour, <span id="welcome-name">Étudiant</span> ! 👋</h2>
            <p>Vous avez <strong style="color:var(--color-primary)" id="user-points">0</strong> points. Continuez à sauver des repas !</p>
          </div>
          <div class="welcome-emoji">🥳</div>
        </div>

        <!-- Impact Stats -->
        <div class="impact-grid animate-fade-in-up stagger-1">
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

        <!-- Offers -->
        <div class="section animate-fade-in-up stagger-2">
          <div class="section-header">
            <h3>🔥 Offres du jour</h3>
            <span class="badge badge-primary" id="user-level">Niveau 1</span>
          </div>
          <div class="grid grid-3 gap-6" id="offers-grid">
            <!-- Loaded dynamically -->
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="/projet2a22/js/app.js"></script>
  <script src="/projet2a22/js/components.js"></script>
  <script src="/projet2a22/js/student.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => Student.init());
  </script>
</body>
</html>
