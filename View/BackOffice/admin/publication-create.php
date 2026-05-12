<?php require_once dirname(__DIR__, 2) . '/session_check.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Créer une publication CareMeal en tant qu'administrateur.">
  <title>Ajouter une publication — CareMeal Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/css/main.css">
  <link rel="stylesheet" href="/css/components.css">
  <link rel="stylesheet" href="/css/dashboard.css">
  <link rel="stylesheet" href="/css/publications.css?v=4">
</head>
<body>
  <div class="dashboard-layout">
    <?php
      $activePage = 'publication_create';
      require dirname(__DIR__, 3) . '/admin/_admin_sidebar.php';
    ?>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle"><i class="fa-solid fa-bars"></i></button>
          <div class="page-title">
            <h2><i class="fa-solid fa-square-plus" style="color:var(--color-primary);margin-right:8px;"></i>Ajouter une publication</h2>
            <p>Publiez du texte, une image, ou les deux</p>
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
        <div class="pub-admin-back-wrap">
          <a href="/admin/publications.php" class="btn btn-outline btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Retour aux publications
          </a>
        </div>

        <div id="pub-compose-container"></div>
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








