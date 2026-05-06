<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Logs d'activité — CareMeal Admin.">
  <title>Logs d'activité — CareMeal Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/projet2a22/css/main.css">
  <link rel="stylesheet" href="/projet2a22/css/components.css">
  <link rel="stylesheet" href="/projet2a22/css/dashboard.css">
</head>
<body>
  <div class="dashboard-layout">
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <div class="sidebar-logo"><img src="/projet2a22/assets/logo.png" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;"></div>
        <div class="sidebar-brand">Care<span>Meal</span></div>
      </div>
      <nav class="sidebar-nav">
          <div class="sidebar-section">
            <div class="sidebar-section-title">Administration</div>
            <a href="dashboard.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-chart-column"></i></span> Vue globale</a>
            <a href="users.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-users"></i></span> Utilisateurs</a>
            <a href="partners.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-store"></i></span> Partenaires</a>
            <a href="events.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> �v�nements</a>
            <a href="logs.php" class="sidebar-link active"><span class="link-icon"><i class="fa-solid fa-clipboard-list"></i></span> Logs d'activit�</a>
          </div>
        </nav>
      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="avatar" id="sidebar-user-avatar" style="background:linear-gradient(135deg,#EF4444,#F87171);">A</div>
          <div class="sidebar-user-info">
            <div class="sidebar-user-name" id="sidebar-user-name">Admin</div>
            <div class="sidebar-user-role" id="sidebar-user-role">Administrateur</div>
          </div>
          <button class="sidebar-logout" data-action="logout" title="Déconnexion">
              <i class="fa-solid fa-door-open"></i></button>
        </div>
      </div>
    </aside>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle">
              <i class="fa-solid fa-bars"></i></button>
          <div class="page-title"><h2>Logs d'activité</h2><p>Qui a fait quoi, quand</p></div>
        </div>
        <div class="header-right">
          <a href="/projet2a22/View/FrontOffice/feed.php" title="Fil d'actualit�" style="width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--color-text-muted);font-size:1.1rem;text-decoration:none;transition:background .15s;" onmouseover="this.style.background='rgba(254,85,22,0.1)';this.style.color='#FE5516'" onmouseout="this.style.background='none';this.style.color='var(--color-text-muted)'">
            <i class="fa-solid fa-house"></i>
          </a>
          <button class="header-notification"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button>
          <div class="avatar avatar-sm" id="header-avatar" style="background:linear-gradient(135deg,#EF4444,#F87171);">A</div>
        </div>
      </header>

      <div class="page-content">
        <div class="card animate-fade-in-up">
          <div class="card-header">
            <h3 class="card-title">
              <i class="fa-solid fa-clipboard-list"></i> Toute l'activité</h3>
            <span class="badge badge-info" id="logs-count">0 entrées</span>
          </div>
          <div class="timeline" id="logs-timeline" style="padding:16px 16px 16px 40px;">
            <!-- Loaded dynamically -->
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="/projet2a22/js/app.js"></script>
  <script src="/projet2a22/js/components.js"></script>
  <script src="/projet2a22/js/admin.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      Admin.initLogs();
      const logs = App.getLogs();
      document.getElementById('logs-count').textContent = logs.length + ' entrée' + (logs.length > 1 ? 's' : '');
    });
  </script>
</body>
</html>








