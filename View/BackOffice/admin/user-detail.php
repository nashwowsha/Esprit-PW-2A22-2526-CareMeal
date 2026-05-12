<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Détail d'un utilisateur - CareMeal Admin.">
  <title>Fiche Utilisateur - CareMeal Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/css/main.css">
  <link rel="stylesheet" href="/css/components.css">
  <link rel="stylesheet" href="/css/dashboard.css">
  <link rel="stylesheet" href="/css/theme-fix.css">
</head>
<body>
  <div class="dashboard-layout">
    <?php
      $activePage = 'users';
      require dirname(__DIR__, 3) . '/admin/_admin_sidebar.php';
    ?>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle">
              <i class="fa-solid fa-bars"></i></button>
          <div class="page-title">
            <h2>Fiche Utilisateur</h2>
            <p><a href="users.php" style="color:var(--color-primary);">? Retour à la liste</a></p>
          </div>
        </div>
        <div class="header-right">
          <a href="/View/FrontOffice/feed.php" title="Fil d'actualité" style="width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--color-text-muted);font-size:1.1rem;text-decoration:none;transition:background .15s;" onmouseover="this.style.background='rgba(254,85,22,0.1)';this.style.color='#FE5516'" onmouseout="this.style.background='none';this.style.color='var(--color-text-muted)'">
            <i class="fa-solid fa-house"></i>
          </a>
          <button class="header-notification"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button>
          <div class="avatar avatar-sm" id="header-avatar" style="background:linear-gradient(135deg,#EF4444,#F87171);">A</div>
        </div>
      </header>

      <div class="page-content">
        <!-- User Info + Actions -->
        <div class="user-detail-header animate-fade-in-up">
          <div class="user-detail-card">
            <div class="avatar avatar-xl" id="detail-avatar">?</div>
            <div>
              <h2 style="margin-bottom:4px;" id="detail-name">—</h2>
              <p style="color:var(--color-text-muted);margin-bottom:8px;" id="detail-email">—</p>
              <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <span id="detail-role"></span>
                <span id="detail-status"></span>
              </div>
              <p style="font-size:0.8rem;color:var(--color-text-muted);margin-top:8px;">
              <i class="fa-solid fa-calendar"></i> Inscrit le <span id="detail-joined">—</span></p>
            </div>
          </div>
          <div class="user-detail-actions" id="detail-actions">
            <!-- Loaded dynamically -->
          </div>
        </div>

        <!-- Extra Info -->
        <div class="section animate-fade-in-up stagger-1" id="detail-extra">
          <!-- Loaded dynamically -->
        </div>

        <!-- History -->
        <div class="section animate-fade-in-up stagger-2">
          <h3 style="margin-bottom:20px;">
              <i class="fa-solid fa-scroll"></i> Historique</h3>
          <div class="card">
            <div class="timeline" id="detail-history">
              <!-- Loaded dynamically -->
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="/js/app.js"></script>
  <script src="/js/components.js"></script>
  <script src="/js/admin.js"></script>
  <script>document.addEventListener('DOMContentLoaded', () => Admin.initUserDetail());</script>
  <script src="/js/admin-voice-assistant.js?v=20260508"></script>
</body>
</html>
















