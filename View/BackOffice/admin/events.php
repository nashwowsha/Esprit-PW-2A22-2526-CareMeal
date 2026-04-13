<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard — CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/projet2a22/css/main.css">
  <link rel="stylesheet" href="/projet2a22/css/components.css">
  <link rel="stylesheet" href="/projet2a22/css/dashboard.css">
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
          <a href="events.php" class="sidebar-link active"><span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> Événements</a>
          <a href="logs.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-clipboard-list"></i></span> Logs d'activité</a>
        </div>
      </nav>
      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="avatar" style="background:linear-gradient(135deg,#EF4444,#F87171);">A</div>
          <div class="sidebar-user-info"><div class="sidebar-user-name">Admin</div><div class="sidebar-user-role">Administrateur</div></div>
          <button class="sidebar-logout" data-action="logout">
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
          <div class="page-title"><h2>Gestion des Événements</h2><p>Validation et participants</p></div>
        </div>
        <div class="header-right"></div>
      </header>

      <div class="page-content" id="events-list-view">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
            <div class="filters" style="display:flex; gap:8px;">
                <button class="btn btn-primary btn-sm" onclick="EventsAdmin.setFilter('all')">Tous (<span id="st-all">0</span>)</button>
                <button class="btn btn-secondary btn-sm" onclick="EventsAdmin.setFilter('pending')">
              <i class="fa-solid fa-hourglass-half"></i> En attente (<span id="st-pend">0</span>)</button>
                <button class="btn btn-secondary btn-sm" onclick="EventsAdmin.setFilter('validated')">
              <i class="fa-solid fa-check"></i> Validés (<span id="st-val">0</span>)</button>
                <button class="btn btn-secondary btn-sm" onclick="EventsAdmin.setFilter('completed')">
              <span class="input-icon"><i class="fa-solid fa-circle-xmark" style="color:var(--color-text-muted)"></i> Terminés (<span id="st-comp">0</span>)</button>
            </div>
            <div style="display:flex; gap:16px;">
                <input type="text" class="form-control" placeholder="&#xf002; Rechercher..." style="width:250px; font-family: var(--font-family), 'Font Awesome 6 Free'; font-weight: 900; background:var(--color-surface); border:1px solid var(--color-border); color:white; padding:8px 12px; border-radius:8px;" onkeyup="EventsAdmin.search(this.value)">
            </div>
        </div>

        <div class="stats-grid" style="display:flex; gap:16px; margin-bottom:24px;">
          <div class="card" style="flex:1; padding:16px; text-align:center;"><h3 id="stat-total" style="font-size:1.8rem; margin-bottom:4px;">0</h3><span style="font-size:0.85rem; color:var(--color-text-muted);">Total événements</span></div>
          <div class="card" style="flex:1; padding:16px; text-align:center;"><h3 id="stat-pending" style="color:var(--color-warning); font-size:1.8rem; margin-bottom:4px;">0</h3><span style="font-size:0.85rem; color:var(--color-text-muted);">En attente</span></div>
          <div class="card" style="flex:1; padding:16px; text-align:center;"><h3 id="stat-registered" style="font-size:1.8rem; margin-bottom:4px;">0</h3><span style="font-size:0.85rem; color:var(--color-text-muted);">Participants inscrits</span></div>
          <div class="card" style="flex:1; padding:16px; text-align:center;"><h3 id="stat-rate" style="color:var(--color-success); font-size:1.8rem; margin-bottom:4px;">0%</h3><span style="font-size:0.85rem; color:var(--color-text-muted);">Taux de présence</span></div>
        </div>
        
        <div class="card">
            <div class="table-container">
                <table style="width:100%; border-collapse:collapse; text-align:left;">
                    <thead style="border-bottom:1px solid var(--color-border); background:rgba(0,0,0,0.2);">
                        <tr>
                            <th style="padding:16px;">Événement</th>
                            <th style="padding:16px;">Créateur</th>
                            <th style="padding:16px;">Date</th>
                            <th style="padding:16px;">Inscrits</th>
                            <th style="padding:16px;">Statut</th>
                            <th style="padding:16px; text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="events-table-body"></tbody>
                </table>
            </div>
        </div>
      </div>

      <div class="page-content" id="event-detail-view" style="display:none;">
        <button class="btn btn-secondary btn-sm" style="margin-bottom:24px;" onclick="EventsAdmin.showList()">
              <i class="fa-solid fa-arrow-left"></i> Retour</button>
        <div id="admin-detail-content"></div>
      </div>
    </main>
  </div>
  <script src="/projet2a22/js/app.js"></script>
  <script src="/projet2a22/js/components.js"></script>
  <script src="/projet2a22/js/admin.js"></script>
  <script src="/projet2a22/js/events-admin.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => { 
        if(typeof Admin !== 'undefined') Admin.init(); 
        if(typeof EventsAdmin !== 'undefined') EventsAdmin.init(); 
    });
  </script>
</body>
</html>





