<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Participations</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/projet2a22/css/main.css">
  <link rel="stylesheet" href="/projet2a22/css/components.css">
  <link rel="stylesheet" href="/projet2a22/css/dashboard.css">
  <style>
    .table-container { background: var(--color-surface); border-radius: 12px; overflow: hidden; }
    .table { width: 100%; border-collapse: collapse; text-align: left; }
    .table th, .table td { padding: 16px; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .table th { background: rgba(0,0,0,0.2); font-weight: 600; color: var(--color-text-muted); }
    .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: bold; }
    .status-inscrit { background: rgba(16, 185, 129, 0.2); color: #10b981; }
    .status-annule { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
  </style>
</head>
<body>
  <div class="dashboard-layout">
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-header"><div class="sidebar-brand">Care<span>Meal</span></div></div>
      <nav class="sidebar-nav">
        <div class="sidebar-section">
          <div class="sidebar-section-title">Administration</div>
          <a href="dashboard.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-chart-column"></i></span> Vue globale</a>
          <a href="users.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-users"></i></span> Utilisateurs</a>
          <a href="partners.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-store"></i></span> Partenaires</a>
          <a href="events.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> Evenements</a>
          <a href="participations.php" class="sidebar-link active"><span class="link-icon"><i class="fa-solid fa-ticket"></i></span> Participations</a>
          <a href="logs.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-clipboard-list"></i></span> Logs d'activite</a>
        </div>
      </nav>
    </aside>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <div class="page-title"><h2>Gestion des Participations</h2><p>CRUD de la 2ème entité avec Jointure M/M</p></div>
        </div>
      </header>

      <div class="page-content">

        <!-- Search and Sort -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
            <div style="position:relative; width:300px;">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:var(--color-text-muted);"></i>
                <input type="text" id="partSearchInput" placeholder="Rechercher par étudiant ou événement..." style="width:100%; padding:12px 16px 12px 40px; background:var(--color-surface); border:1px solid var(--color-border); border-radius:24px; color:white; font-size:0.95rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);" onkeyup="filterAndSortParticipations()">
            </div>
            <div style="display:flex; gap:12px;">
                <select id="partStatusSelect" style="padding:12px 20px; border-radius:24px; border:1px solid var(--color-border); background:var(--color-surface); color:white; cursor:pointer;" onchange="filterAndSortParticipations()">
                    <option style="background:#1e293b; color:white;" value="Tous">Tous les statuts</option>
                    <option style="background:#1e293b; color:white;" value="Inscrit">Inscrit</option>
                    <option style="background:#1e293b; color:white;" value="Validé">Validé</option>
                    <option style="background:#1e293b; color:white;" value="Annulé">Annulé</option>
                </select>
                <select id="partSortSelect" style="padding:12px 20px; border-radius:24px; border:1px solid var(--color-border); background:var(--color-surface); color:white; cursor:pointer;" onchange="filterAndSortParticipations()">
                    <option style="background:#1e293b; color:white;" value="date_desc">⬇️ Plus récents d'abord</option>
                    <option style="background:#1e293b; color:white;" value="date_asc">⬆️ Plus anciens d'abord</option>
                </select>
            </div>
        </div>

        <div class="table-container">
          <table class="table">
            <thead>
              <tr>
                <th>Date Inscription</th>
                <th>Étudiant (Email)</th>
                <th>Événement</th>
                <th>Statut</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="participations-body">
                <tr><td colspan="5" style="text-align:center;">Chargement...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <script src="/projet2a22/js/app.js"></script>
  <script src="/projet2a22/js/participations-admin.js"></script>
</body>
</html>
