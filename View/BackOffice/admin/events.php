<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard — CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- FullCalendar v6 CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">
  <link rel="stylesheet" href="/css/main.css">
  <link rel="stylesheet" href="/css/components.css">
  <link rel="stylesheet" href="/css/dashboard.css">
  <link rel="stylesheet" href="/css/theme-fix.css">
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
    .event-title { font-size: 1.35rem; font-weight: bold; margin: 0; color: #1A202C; }
    .event-description { color: #475569; font-size: 0.95em; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 5px; }

    .event-detail { font-size: 0.9rem; color: #94a3b8; display: flex; align-items: flex-start; gap: 8px; margin: 3px 0; }
    .event-detail i { width: 16px; margin-top: 3px; color: #64748b; text-align: center; }

    /* Deux assistants cote a cote : chatbot a droite, vocal decale */
    #chatbot-toggle { right: 24px !important; bottom: 24px !important; }
    #chatbot-window { right: 24px !important; }
    .voice-assistant-fab { right: 96px !important; bottom: 24px !important; }
  </style>
</head>
<body>
  <div class="dashboard-layout">
    <?php
      $activePage = 'events';
      require dirname(__DIR__, 3) . '/admin/_admin_sidebar.php';
    ?>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle">
              <i class="fa-solid fa-bars"></i></button>
          <div class="page-title"><h2>Gestion des Événements</h2><p>Validation et participants</p></div>
        </div>
        <div class="header-right">
          <button class="header-notification"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button>
        </div>
      </header>

      <div class="page-content" id="events-main-view">
        <!-- Boutons bascule Liste / Calendrier -->
        <div style="display:flex; gap:10px; margin-bottom:24px;">
            <button id="btn-view-list" class="view-toggle-btn active-view">
                <i class="fa-solid fa-list"></i> Vue Liste
            </button>
            <button id="btn-view-calendar" class="view-toggle-btn inactive-view">
                <i class="fa-solid fa-calendar-days"></i> Vue Calendrier
            </button>
        </div>

        <!-- Vue Calendrier (cachée par défaut) -->
        <div id="calendar-view" style="display:none;">
            <div id="calendar-loader"><i class="fa-solid fa-circle-notch fa-spin"></i> Chargement du calendrier...</div>
            <div id="fullcalendar"></div>
        </div>

        <!-- Vue Liste (existante) -->
        <div id="calendar-list-view">
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
                    <input type="text" id="searchInput" class="form-control" placeholder="Recherche globale..." style="width:100%; padding:12px 16px 12px 40px; background:#FFFFFF; border:1px solid var(--color-border); border-radius:24px; color:#1A202C; font-size:0.95rem; box-shadow: 0 4px 6px rgba(0,0,0,0.06);" onkeyup="EventsAdmin.handleSearch()">
                </div>
                <select id="sortSelect" class="sort-select" onchange="EventsAdmin.handleSearch()">
                    <option value="date_asc">Trier par : Date (Croissante)</option>
                    <option value="date_desc">Trier par : Date (Décroissante)</option>
                    <option value="title_asc">Trier par : Titre (A-Z)</option>
                </select>
            </div>
            <div class="filters" id="filterBtns" style="display:flex; gap:12px;">
                <button class="btn btn-primary" style="border-radius:24px; padding:10px 24px;" onclick="EventsAdmin.setFilter('Tous', this)">Tous</button>
                <button class="btn btn-outline" style="border-radius:24px; padding:10px 24px; border:1px solid #E2E8F0; color:#334155; background:transparent;" onclick="EventsAdmin.setFilter('En attente', this)">En attente de validation</button>
                <button class="btn btn-outline" style="border-radius:24px; padding:10px 24px; border:1px solid #E2E8F0; color:#334155; background:transparent;" onclick="EventsAdmin.setFilter('En ligne', this)">En ligne</button>
                <button class="btn btn-outline" style="border-radius:24px; padding:10px 24px; border:1px solid #E2E8F0; color:#334155; background:transparent;" onclick="EventsAdmin.setFilter('Termines', this)">Terminés</button>
            </div>
        </div>

        <h2 style="margin-bottom:20px; display:flex; align-items:center; gap:12px; font-size:1.6rem; text-transform:uppercase; font-weight:800;">
            <i class="fa-solid fa-hourglass-half"></i> EN ATTENTE DE VALIDATION
        </h2>
        <div id="pending-container" style="display:grid; width:100%; grid-template-columns:repeat(auto-fit, minmax(min(100%, 300px), 1fr)); gap:20px; margin-bottom:40px;">
            <!-- Pending events populated here -->
        </div>

        <h2 style="margin-bottom:20px; display:flex; align-items:center; gap:12px; font-size:1.6rem; text-transform:uppercase; font-weight:800;">
            <i class="fa-solid fa-list-ul"></i> AUTRES ÉVÉNEMENTS (Validés / Rejetés)
        </h2>
        <div id="all-events-table" class="events-grid" style="margin-bottom:40px; width:100%; display:grid; grid-template-columns:repeat(auto-fit, minmax(min(100%, 300px), 1fr)); gap:20px;">
            <!-- All events populated here -->
        </div>
        </div><!-- fin #calendar-list-view -->
      </div>

      <div class="page-content" id="event-examine-view" style="display:none;">
        <button class="btn btn-secondary btn-sm" style="margin-bottom:24px;" onclick="EventsAdmin.showList()">
              <i class="fa-solid fa-arrow-left"></i> Retour</button>
        <div id="admin-detail-content"></div>

        <!-- Jointure : Participants de l'événement -->
        <div id="event-participants-section" style="margin-top:32px; display:none;">
          <h3 style="font-size:1.2rem; font-weight:700; margin-bottom:16px; display:flex; align-items:center; gap:10px;">
            <i class="fa-solid fa-users" style="color:var(--color-primary);"></i> Participants inscrits
            <span id="participants-count-badge" style="background:var(--color-primary); color:white; font-size:0.8rem; padding:3px 10px; border-radius:20px; font-weight:600;"></span>
          </h3>

          <!-- Recherche participants -->
          <div style="position:relative; width:300px; margin-bottom:16px;">
            <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#64748b; font-size:0.85rem;"></i>
            <input type="text" id="part-search" placeholder="Rechercher un participant..." onkeyup="EventsAdmin.filterParticipants()" style="width:100%; padding:10px 14px 10px 36px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:20px; color:#1A202C; font-size:0.9rem; box-sizing:border-box; outline:none;">
          </div>

          <div style="overflow-x:auto; background:#FFFFFF; border-radius:12px; border:1px solid #E2E8F0;">
            <table style="width:100%; border-collapse:collapse;">
              <thead>
                <tr style="background:rgba(0,0,0,0.03);">
                  <th style="padding:14px 16px; text-align:left; color:#64748B; font-size:0.8rem; text-transform:uppercase; letter-spacing:0.6px; font-weight:600;">#</th>
                  <th style="padding:14px 16px; text-align:left; color:#64748B; font-size:0.8rem; text-transform:uppercase; letter-spacing:0.6px; font-weight:600;">Nom & Prénom</th>
                  <th style="padding:14px 16px; text-align:left; color:#64748B; font-size:0.8rem; text-transform:uppercase; letter-spacing:0.6px; font-weight:600;">Email</th>
                  <th style="padding:14px 16px; text-align:left; color:#64748B; font-size:0.8rem; text-transform:uppercase; letter-spacing:0.6px; font-weight:600;">Téléphone</th>
                  <th style="padding:14px 16px; text-align:left; color:#64748B; font-size:0.8rem; text-transform:uppercase; letter-spacing:0.6px; font-weight:600;">Université</th>
                  <th style="padding:14px 16px; text-align:left; color:#64748B; font-size:0.8rem; text-transform:uppercase; letter-spacing:0.6px; font-weight:600;">Année</th>
                  <th style="padding:14px 16px; text-align:left; color:#64748B; font-size:0.8rem; text-transform:uppercase; letter-spacing:0.6px; font-weight:600;">Date inscription</th>
                  <th style="padding:14px 16px; text-align:left; color:#64748B; font-size:0.8rem; text-transform:uppercase; letter-spacing:0.6px; font-weight:600;">Statut</th>
                </tr>
              </thead>
              <tbody id="participants-table-body">
                <tr><td colspan="8" style="text-align:center; padding:24px; color:#64748b;"><i class="fa-solid fa-circle-notch fa-spin"></i> Chargement...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
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
            <textarea id="rejectReason" class="form-control" rows="4" style="width:100%; background:#F8FAFC; border:1px solid #E2E8F0; color:#1A202C; border-radius:12px; padding:16px; font-family:inherit; resize:vertical;" placeholder="Saisissez le motif ici..." required></textarea>
          </div>
          <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:32px;">
            <button type="button" class="btn btn-outline" style="border:1px solid rgba(255,255,255,0.2); padding:10px 24px; border-radius:12px;" onclick="Components.closeModal('rejectModal')">Annuler</button>
            <button type="button" class="btn" style="background:#ff4444; color:white; border:none; padding:10px 24px; border-radius:12px; box-shadow:0 4px 12px rgba(255,68,68,0.3);" onclick="EventsAdmin.confirmReject()">Confirmer le refus</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <script src="/js/app.js"></script>
  <script src="/js/components.js"></script>
  <script src="/js/events-admin.js?v=20260511"></script>
  <script src="/assets/js/chatbot.js"></script>
  <script src="/assets/js/translate.js"></script>
  <script src="/assets/js/weather.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
  <script src="/assets/js/calendar.js"></script>
  <script src="/assets/js/notifications.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => { 
        if(typeof App !== 'undefined') App.init();
        CareMealChatbot.init('admin');
        CareMealCalendar.init('admin');
        CareMealNotifications.init('admin');
    });
  </script>
  <script src="/js/admin-voice-assistant.js?v=20260508"></script>
</body>
</html>









