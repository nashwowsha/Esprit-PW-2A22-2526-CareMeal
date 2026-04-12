<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Gestion des partenaires CareMeal � Validation et suivi.">
  <title>Gestion Partenaires � CareMeal Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
  <div class="dashboard-layout">
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <div class="sidebar-logo"><img src="../assets/logo.png" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;"></div>
        <div class="sidebar-brand">Care<span>Meal</span></div>
      </div>
      <nav class="sidebar-nav">
                <div class="sidebar-section">
          <div class="sidebar-section-title">Administration</div>
          <a href="dashboard.html" class="sidebar-link "><span class="link-icon"><i class="fa-solid fa-chart-column"></i></span> Vue globale</a>
          <a href="users.html" class="sidebar-link "><span class="link-icon"><i class="fa-solid fa-users"></i></span> Utilisateurs</a>
          <a href="partners.html" class="sidebar-link active"><span class="link-icon"><i class="fa-solid fa-store"></i></span> Partenaires</a>
          <a href="offers.php" class="sidebar-link "><span class="link-icon"><i class="fa-solid fa-box"></i></span> Offres</a>
          <a href="events.html" class="sidebar-link "><span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> Événements</a>
          <a href="logs.html" class="sidebar-link "><span class="link-icon"><i class="fa-solid fa-clipboard-list"></i></span> Logs d'activité</a>
        </div>
      </nav>
      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="avatar" id="sidebar-user-avatar" style="background:linear-gradient(135deg,#EF4444,#F87171);">A</div>
          <div class="sidebar-user-info">
            <div class="sidebar-user-name" id="sidebar-user-name">Admin</div>
            <div class="sidebar-user-role" id="sidebar-user-role">Administrateur</div>
          </div>
          <button class="sidebar-logout" data-action="logout" title="Déconnexion"><i class="fa-solid fa-door-open"></i></button>
        </div>
      </div>
    </aside>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle"><i class="fa-solid fa-bars"></i></button>
          <div class="page-title"><h2>Gestion Partenaires</h2><p>Validation et suivi des établissements</p></div>
        </div>
        <div class="header-right">
          <div class="avatar avatar-sm" id="header-avatar" style="background:linear-gradient(135deg,#EF4444,#F87171);">A</div>
        </div>
      </header>

      <div class="page-content">
        <!-- Pending Alert -->
        <div id="pending-alert" class="animate-fade-in-up" style="display:none;">
          <div class="auth-alert info" style="margin-bottom:24px;">
            <span><i class="fa-solid fa-hourglass-half"></i></span> <span id="pending-count">0</span> partenaire(s) en attente de validation.
          </div>
        </div>

        <!-- Partners Table -->
        <div class="card animate-fade-in-up stagger-1">
          <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-store"></i> Tous les partenaires</h3>
          </div>
          <div class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th>�Établissement</th>
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

  <script src="../js/app.js"></script>
  <script src="../js/components.js"></script>
  <script src="../js/admin.js"></script>
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
</body>
</html>