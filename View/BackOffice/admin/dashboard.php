<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Tableau de bord administrateur CareMeal ÃƒÂ¯Ã‚Â¿Ã‚Â½ Vue globale des statistiques.">
  <title>Admin Dashboard ÃƒÂ¯Ã‚Â¿Ã‚Â½ CareMeal</title>
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
          <a href="dashboard.html" class="sidebar-link active"><span class="link-icon"><i class="fa-solid fa-chart-column"></i></span> Vue globale</a>
          <a href="users.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-users"></i></span> Utilisateurs</a>
          <a href="partners.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-store"></i></span> Partenaires</a>
          <a href="preferences.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-sliders"></i></span> Preferences</a>
                      <a href="events.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> Ãƒâ€°vÃƒÂ©nements</a>
            <a href="logs.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-clipboard-list"></i></span> Logs d'activitÃƒÂ©</a>
        </div>
      </nav>
      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="avatar" id="sidebar-user-avatar" style="background:linear-gradient(135deg,#EF4444,#F87171);">A</div>
          <div class="sidebar-user-info">
            <div class="sidebar-user-name" id="sidebar-user-name">Admin</div>
            <div class="sidebar-user-role" id="sidebar-user-role">Administrateur</div>
          </div>
          <button class="sidebar-logout" data-action="logout" title="DÃƒÂ©connexion"><i class="fa-solid fa-door-open"></i></button>
        </div>
      </div>
    </aside>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle"><i class="fa-solid fa-bars"></i></button>
          <div class="page-title"><h2>Vue globale</h2><p>Statistiques et aperÃƒÂ§u du systÃƒÂ¨me</p></div>
        </div>
        <div class="header-right">
          <button class="header-notification"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button>
          <div class="avatar avatar-sm" id="header-avatar" style="background:linear-gradient(135deg,#EF4444,#F87171);">A</div>
        </div>
      </header>

      <div class="page-content">
        <!-- Welcome -->
        <div class="welcome-banner animate-fade-in-up">
          <div class="welcome-text">
            <h2>Bienvenue, <span>Admin</span> <i class="fa-solid fa-hand-wave"></i></h2>
            <p>Voici un aperÃƒÂ§u de l'activitÃƒÂ© de la plateforme CareMeal.</p>
          </div>
          <div class="welcome-emoji"><i class="fa-solid fa-shield-halved"></i></div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-4 gap-4 mb-8 animate-fade-in-up stagger-1">
          <div class="stat-card">
            <div class="stat-icon orange"><i class="fa-solid fa-users"></i></div>
            <div class="stat-value" id="stat-users">0</div>
            <div class="stat-label">Utilisateurs totaux</div>
            <div class="stat-change up">ÃƒÂ¯Ã‚Â¿Ã‚Â½  +12%</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon blue"><i class="fa-solid fa-graduation-cap"></i></div>
            <div class="stat-value" id="stat-students">0</div>
            <div class="stat-label">ÃƒÂ¯Ã‚Â¿Ã‚Â½0tudiants actifs</div>
            <div class="stat-change up">ÃƒÂ¯Ã‚Â¿Ã‚Â½  +8%</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-utensils"></i></div>
            <div class="stat-value" id="stat-meals">0</div>
            <div class="stat-label">Repas sauvÃƒÂ©s</div>
            <div class="stat-change up">ÃƒÂ¯Ã‚Â¿Ã‚Â½  +23%</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon yellow"><i class="fa-solid fa-leaf"></i></div>
            <div class="stat-value" id="stat-co2">0 kg</div>
            <div class="stat-label">COÃƒÂ¯Ã‚Â¿Ã‚Â½ ÃƒÂ©vitÃƒÂ©</div>
            <div class="stat-change up">ÃƒÂ¯Ã‚Â¿Ã‚Â½  +18%</div>
          </div>
        </div>

        <!-- Secondary stats -->
        <div class="grid grid-3 gap-4 mb-8 animate-fade-in-up stagger-2">
          <div class="card" style="display:flex;align-items:center;gap:16px;">
            <div class="stat-icon orange" style="width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;"><i class="fa-solid fa-store"></i></div>
            <div>
              <div style="font-size:1.5rem;font-weight:700;color:var(--color-white);" id="stat-partners">0</div>
              <div style="font-size:0.8rem;color:var(--color-text-muted);">Partenaires actifs</div>
            </div>
          </div>
          <div class="card" style="display:flex;align-items:center;gap:16px;">
            <div class="stat-icon yellow" style="width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;"><i class="fa-solid fa-hourglass-half"></i></div>
            <div>
              <div style="font-size:1.5rem;font-weight:700;color:var(--color-warning);" id="stat-pending">0</div>
              <div style="font-size:0.8rem;color:var(--color-text-muted);">En attente de validation</div>
            </div>
          </div>
          <div class="card" style="display:flex;align-items:center;gap:16px;">
            <div class="stat-icon blue" style="width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;"><i class="fa-solid fa-box"></i></div>
            <div>
              <div style="font-size:1.5rem;font-weight:700;color:var(--color-white);" id="stat-orders">0</div>
              <div style="font-size:0.8rem;color:var(--color-text-muted);">Commandes totales</div>
            </div>
          </div>
        </div>

        <!-- Recent Users & Logs -->
        <div class="grid grid-2 gap-6 animate-fade-in-up stagger-3">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title"><i class="fa-solid fa-user"></i> Derniers inscrits</h3>
              <a href="users.html" class="btn btn-sm btn-outline">Voir tout</a>
            </div>
            <div class="table-container" style="border:none;">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Utilisateur</th>
                    <th>RÃƒÂ´le</th>
                    <th>Statut</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody id="recent-users"></tbody>
              </table>
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <h3 class="card-title"><i class="fa-solid fa-clipboard-list"></i> ActivitÃƒÂ© rÃƒÂ©cente</h3>
              <a href="events.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> Ãƒâ€°vÃƒÂ©nements</a>
            <a href="logs.html" class="btn btn-sm btn-outline">Voir tout</a>
            </div>
            <div class="timeline" id="recent-logs" style="max-height:320px;overflow-y:auto;">
              <!-- Loaded dynamically -->
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="../js/app.js"></script>
  <script src="../js/components.js"></script>
  <script src="../js/admin.js"></script>
  <script>document.addEventListener('DOMContentLoaded', () => Admin.init());</script>
</body>
</html>




