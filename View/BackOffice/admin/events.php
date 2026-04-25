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
  <style>
    /* Styles pour l'affichage en cartes identiques a Mon Etablissement */
    .events-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; }
    .event-card {
        background: var(--color-surface); border-radius: 12px; border: 1px solid var(--color-border);
        display: flex; flex-direction: column; position: relative; overflow: hidden;
    }
    
    .card-img-wrapper {
        height: 180px; width: 100%; position: relative;
        background-color: var(--color-background); background-size: cover; background-position: center;
    }

    .event-card-header { padding: 12px; display: flex; justify-content: space-between; align-items: flex-start; z-index: 2; position: absolute; top:0; left:0; right:0; }
    .badges { display: flex; gap: 8px; flex-wrap: wrap; }
    .badge { padding: 5px 12px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; color: white; display: inline-block; }
    .badge-presentiel { background: #0284c7; }
    .badge-online { background: #7e22ce; }
    .badge-attente { background: #f59e0b; color: white; }
    .badge-valide { background: #10b981; color: white;}
    .badge-refuse { background: #ef4444; color: white;}

    .actions { display: flex; gap: 8px; }
    .btn-icon { background: rgba(30, 41, 59, 0.8); color: #cbd5e1; border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; padding: 8px 10px; text-decoration: none; display: flex; align-items:center; justify-content:center; }
    .btn-icon:hover { background: rgba(0,0,0,0.8); color: white; }
    
    .card-body { padding: 15px; display: flex; flex-direction: column; gap: 10px; flex: 1; }
    .event-title { font-size: 1.35rem; font-weight: bold; margin: 0; color: #f8fafc; }
    .event-description { color: #cbd5e1; font-size: 0.95em; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 5px; }

    .event-detail { font-size: 0.9rem; color: #94a3b8; display: flex; align-items: flex-start; gap: 8px; margin: 3px 0; }
    .event-detail i { width: 16px; margin-top: 3px; color: #64748b; text-align: center; }
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
          <a href="events.php" class="sidebar-link active"><span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> Evenements</a>
          <a href="logs.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-clipboard-list"></i></span> Logs d'activite</a>
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
          <div class="page-title"><h2>Gestion des Evenements</h2><p>Validation et participants</p></div>
        </div>
        <div class="header-right"></div>
      </header>

      <div class="page-content" id="events-main-view">
        <div class="stats-grid" style="display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:24px;">
            <div class="card" style="padding:24px; text-align:center; border-radius:16px;">
                <h3 id="stat-total" style="font-size:2.5rem; margin-bottom:8px; color:var(--color-primary);">0</h3>
                <span style="font-size:0.9rem; color:var(--color-text-muted);">Total evenements</span>
            </div>
            <div class="card" style="padding:24px; text-align:center; border-radius:16px;">
                <h3 id="stat-pending" style="font-size:2.5rem; margin-bottom:8px; color:var(--color-warning);">0</h3>
                <span style="font-size:0.9rem; color:var(--color-text-muted);">En attente</span>
            </div>
            <div class="card" style="padding:24px; text-align:center; border-radius:16px;">
                <h3 id="stat-participants" style="font-size:2.5rem; margin-bottom:8px; color:var(--color-success);">0</h3>
                <span style="font-size:0.9rem; color:var(--color-text-muted);">Total participants</span>
            </div>
            <div class="card" style="padding:24px; text-align:center; border-radius:16px;">
                <h3 id="stat-presence" style="font-size:2.5rem; margin-bottom:8px; color:var(--color-text-muted);">0%</h3>
                <span style="font-size:0.9rem; color:var(--color-text-muted);">Taux de presence</span>
            </div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:32px; flex-wrap:wrap; gap:16px;">
            <div style="display:flex; gap:12px; align-items:center;">
                <div style="position:relative; width:300px;">
                    <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:var(--color-text-muted);"></i>
                    <input type="text" id="searchInput" class="form-control" placeholder="Recherche globale..." style="width:100%; padding:12px 16px 12px 40px; background:var(--color-surface); border:1px solid var(--color-border); border-radius:24px; color:white; font-size:0.95rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);" onkeyup="EventsAdmin.handleSearch()">
                </div>
                <select id="sortSelect" style="padding:12px 20px; border-radius:24px; border:1px solid var(--color-border); background:var(--color-surface); color:white; cursor:pointer;" onchange="EventsAdmin.handleSearch()">
                    <option style="background:#1e293b; color:white;" value="date_asc">🗓 Date (Croissante)</option>
                    <option style="background:#1e293b; color:white;" value="date_desc">🗓 Date (Décroissante)</option>
                    <option style="background:#1e293b; color:white;" value="title_asc">🔤 Titre (A-Z)</option>
                </select>
            </div>
            <div class="filters" id="filterBtns" style="display:flex; gap:12px;">
                <button class="btn btn-primary" style="border-radius:24px; padding:10px 24px;" onclick="EventsAdmin.setFilter('Tous', this)">Tous</button>
                <button class="btn btn-outline" style="border-radius:24px; padding:10px 24px; border:1px solid rgba(255,255,255,0.2); color:white; background:transparent;" onclick="EventsAdmin.setFilter('En attente', this)">En attente de validation</button>
                <button class="btn btn-outline" style="border-radius:24px; padding:10px 24px; border:1px solid rgba(255,255,255,0.2); color:white; background:transparent;" onclick="EventsAdmin.setFilter('En ligne', this)">En ligne</button>
                <button class="btn btn-outline" style="border-radius:24px; padding:10px 24px; border:1px solid rgba(255,255,255,0.2); color:white; background:transparent;" onclick="EventsAdmin.setFilter('Termines', this)">Termines</button>
            </div>
        </div>

        <h2 style="margin-bottom:20px; display:flex; align-items:center; gap:12px; font-size:1.6rem; text-transform:uppercase; font-weight:800;">
            <i class="fa-solid fa-hourglass-half"></i> EN ATTENTE DE VALIDATION
        </h2>
        <div id="pending-container" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:20px; margin-bottom:40px;">
            <!-- Pending events populated here -->
        </div>

        <h2 style="margin-bottom:20px; display:flex; align-items:center; gap:12px; font-size:1.6rem; text-transform:uppercase; font-weight:800;">
            <i class="fa-solid fa-list-ul"></i> TOUS LES EVENEMENTS
        </h2>
        <div id="all-events-table" class="events-grid" style="margin-bottom:40px;">
            <!-- All events populated here -->
        </div>
      </div>

      <div class="page-content" id="event-examine-view" style="display:none;">
        <button class="btn btn-secondary btn-sm" style="margin-bottom:24px;" onclick="EventsAdmin.showList()">
              <i class="fa-solid fa-arrow-left"></i> Retour</button>
        <div id="admin-detail-content"></div>
      </div>
    </main>
  </div>

  <!-- Rejection Modal -->
  <div id="rejectModal" class="modal-overlay">
    <div class="modal">
      <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.08);">
        <h3 style="margin:0; font-size:1.5rem; color:#ff4444;"><i class="fa-solid fa-triangle-exclamation"></i> Motif du refus</h3>
        <button class="modal-close" onclick="Components.closeModal('rejectModal')"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body">
        <form id="rejectForm" novalidate>
          <input type="hidden" id="rejectEventId" value="">
          <div style="margin-bottom:16px;">
            <label style="display:block; margin-bottom:12px; color:var(--color-text-muted); font-size:0.95rem;">Veuillez expliquer a l'organisateur pourquoi cet evenement est rejete :</label>
            <textarea id="rejectReason" class="form-control" rows="4" style="width:100%; background:rgba(0,0,0,0.2); border:1px solid rgba(255,255,255,0.1); color:white; border-radius:12px; padding:16px; font-family:inherit; resize:vertical;" placeholder="Saisissez le motif ici..." required></textarea>
          </div>
          <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:32px;">
            <button type="button" class="btn btn-outline" style="border:1px solid rgba(255,255,255,0.2); padding:10px 24px; border-radius:12px;" onclick="Components.closeModal('rejectModal')">Annuler</button>
            <button type="button" class="btn" style="background:#ff4444; color:white; border:none; padding:10px 24px; border-radius:12px; box-shadow:0 4px 12px rgba(255,68,68,0.3);" onclick="EventsAdmin.confirmReject()">Confirmer le refus</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <script src="/projet2a22/js/app.js"></script>
  <script src="/projet2a22/js/components.js"></script>
  <script src="/projet2a22/js/events-admin.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => { 
        if(typeof App !== 'undefined') App.init();
        if(typeof EventsAdmin !== 'undefined') EventsAdmin.init(); 
    });
  </script>
</body>
</html>
