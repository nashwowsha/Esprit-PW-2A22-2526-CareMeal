<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Gestion des partenaires CareMeal - Validation et suivi.">
  <title>Gestion Partenaires - CareMeal Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/css/main.css">
  <link rel="stylesheet" href="/css/components.css">
  <link rel="stylesheet" href="/css/dashboard.css">
  <link rel="stylesheet" href="/css/theme-fix.css">
</head>
<body>
  <div class="dashboard-layout">
    <?php
      $activePage = 'partners';
      require dirname(__DIR__, 3) . '/admin/_admin_sidebar.php';
    ?>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle">
              <i class="fa-solid fa-bars"></i></button>
          <div class="page-title"><h2>Gestion Partenaires</h2><p>Validation et suivi des établissements</p></div>
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
        <!-- Pending Alert -->
        <div id="pending-alert" class="animate-fade-in-up" style="display:none;">
          <div class="auth-alert info" style="margin-bottom:24px;">
            <span>
              <span class="input-icon"><i class="fa-solid fa-hourglass-half"></i></span> <span id="pending-count">0</span> partenaire(s) en attente de validation.</span>
          </div>
        </div>

        <!-- Partners Table -->
        <div class="card animate-fade-in-up stagger-1">
          <div class="card-header">
            <h3 class="card-title">
              <i class="fa-solid fa-store"></i> Tous les partenaires</h3>
          </div>
          <div class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Etablissement</th>
                  <th>Type</th>
                  <th>Statut</th>
                  <th>Repas sauvés</th>
                  <th>Note</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="partners-table-body">
                <!-- Loaded dynamically -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="/js/app.js"></script>
  <script src="/js/components.js"></script>
  <script src="/js/admin.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      Admin.initPartners();
      // Show pending alert
      const pending = App.getUsers().filter(u => u.role === 'partner' && u.status === 'pending');
      if (pending.length > 0) {
        document.getElementById('pending-alert').style.display = 'block';
        document.getElementById('pending-count').textContent = pending.length;
      }
    });
  </script>
  <script src="/js/admin-voice-assistant.js?v=20260508"></script>
</body>
</html>














