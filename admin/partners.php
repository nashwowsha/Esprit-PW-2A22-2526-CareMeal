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
    <?php
      $activePage = 'partners';
      require __DIR__ . '/_admin_sidebar.php';
    ?>
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

  <script src="../js/app.js?v=20260420c"></script>
  <script src="../js/components.js?v=20260421a"></script>
  <script src="../js/admin.js?v=20260420c"></script>
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


















