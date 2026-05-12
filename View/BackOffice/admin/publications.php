<?php require_once dirname(__DIR__, 2) . '/session_check.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Publications CareMeal — Administration des publications de la communauté.">
  <title>Publications — CareMeal Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/css/main.css">
  <link rel="stylesheet" href="/css/components.css">
  <link rel="stylesheet" href="/css/dashboard.css">
  <link rel="stylesheet" href="/css/publications.css?v=4">
</head>
<body>
  <div class="dashboard-layout">
    <!-- Sidebar -->
    <?php
      $activePage = 'publications';
      require dirname(__DIR__, 3) . '/admin/_admin_sidebar.php';
    ?>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <!-- Main Content -->
    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle"><i class="fa-solid fa-bars"></i></button>
          <div class="page-title">
            <h2><i class="fa-solid fa-newspaper" style="color:var(--color-primary);margin-right:8px;"></i>Publications</h2>
            <p>Gestion du fil d'actualité communautaire</p>
          </div>
        </div>
        <div class="header-right">
          <button class="header-notification">
            <i class="fa-solid fa-bell"></i>
            <span class="notif-dot"></span>
          </button>
          <div class="avatar avatar-sm" id="header-avatar">A</div>
        </div>
      </header>

      <div class="page-content" style="max-width:720px;">
        <!-- Toolbar : Recherche / Filtre / Tri -->
        <div id="pub-toolbar-container"></div>

        <!-- Actions Admin -->
        <div id="pub-admin-actions-container"></div>

        <!-- Statistiques (cachées par défaut, affichées sur clic) -->
        <div id="pub-stats-container"></div>

        <!-- Feed -->
        <div id="pub-feed-container"></div>
      </div>
    </main>
  </div>

  <script src="/js/app.js"></script>
  <script src="/js/components.js"></script>
  <script src="/js/publications.js?v=4"></script>
  <script src="/js/admin-voice-assistant.js?v=20260509"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      if (!App.requireAuth(['admin'])) return;
      Publications.initPage();
    });
  </script>
</body>
</html>








