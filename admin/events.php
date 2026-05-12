<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Administration des événements.">
  <title>Gestion des Événements — Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="../css/theme-fix.css">
  <style>
    #pending-container,
    #all-events-table {
      align-items: stretch;
      justify-items: stretch;
    }

    #pending-container .event-card,
    #all-events-table .event-card {
      width: 100% !important;
      max-width: none !important;
      min-width: 0;
      display: flex !important;
      flex-direction: column !important;
      overflow: hidden;
    }

    .event-card .card-img-wrapper {
      position: relative;
      height: 170px !important;
      min-height: 170px !important;
      background-size: cover;
      background-position: center;
      overflow: hidden;
      flex-shrink: 0;
    }

    .event-card .event-card-header {
      position: absolute;
      top: 12px;
      left: 12px;
      right: 12px;
      z-index: 2;
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      pointer-events: none;
    }

    .event-card .badges {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .event-card .card-body {
      display: flex !important;
      flex-direction: column !important;
      gap: 8px;
      padding: 14px;
      min-height: 260px;
    }

    .event-card .event-title {
      margin: 0;
      line-height: 1.25;
      color: #0F172A !important;
      overflow-wrap: anywhere;
      word-break: break-word;
    }

    .event-card .event-description {
      margin: 0 0 6px;
      line-height: 1.45;
      color: #475569;
      overflow-wrap: anywhere;
      word-break: break-word;
    }

    .event-card .event-detail {
      display: flex;
      align-items: flex-start;
      gap: 8px;
      margin: 3px 0;
      color: #64748B;
      overflow-wrap: anywhere;
      word-break: break-word;
    }

    .event-card .event-detail i {
      width: 16px;
      margin-top: 2px;
      text-align: center;
      flex-shrink: 0;
    }
  </style>
</head>
<body>
  <div class="dashboard-layout">
    <?php
      $activePage = 'events';
      require __DIR__ . '/_admin_sidebar.php';
    ?>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle"><i class="fa-solid fa-bars"></i></button>
          <div class="page-title"><h2>Gestion des Événements</h2><p>Validation et modération des événements partenaires</p></div>
        </div>
      </header>

      <div class="page-content" id="events-main-view">
                <div class="stats-grid" style="display:flex; gap:16px; margin-bottom:24px; flex-wrap:wrap;">
          <div class="card" style="flex:1; min-width:200px; padding:16px; text-align:center;">
            <h3 id="stat-total" style="font-size:1.8rem; margin-bottom:4px; color:var(--color-primary);">0</h3>
            <span style="font-size:0.85rem; color:var(--color-text-muted);">Total événements</span>
          </div>
          <div class="card" style="flex:1; min-width:200px; padding:16px; text-align:center;">
            <h3 id="stat-pending" style="color:var(--color-warning); font-size:1.8rem; margin-bottom:4px;">0</h3>
            <span style="font-size:0.85rem; color:var(--color-text-muted);">En attente</span>
          </div>
          <div class="card" style="flex:1; min-width:200px; padding:16px; text-align:center;">
            <h3 id="stat-participants" style="color:var(--color-success); font-size:1.8rem; margin-bottom:4px;">0</h3>
            <span style="font-size:0.85rem; color:var(--color-text-muted);">Total participants</span>
          </div>
          <div class="card" style="flex:1; min-width:200px; padding:16px; text-align:center;">
            <h3 id="stat-presence" style="font-size:1.8rem; margin-bottom:4px; color:var(--color-primary-light);">0%</h3>
            <span style="font-size:0.85rem; color:var(--color-text-muted);">Taux de présence</span>
          </div>
        </div>
        <div class="filter-bar" style="display:flex; flex-wrap:wrap; gap:16px; margin-bottom:24px; align-items:center; justify-content:space-between;">
          <div class="search-box" style="flex:1; min-width:250px; position:relative;">
            <i class="fa-solid fa-magnifying-glass search-icon" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--color-text-muted);"></i>
            <input type="text" id="searchInput" class="form-input" style="padding-left:36px; width:100%;" placeholder="Recherche globale..." onkeyup="EventsAdmin.handleSearch()">
          </div>
          <div class="filter-buttons" style="display:flex; flex-wrap:wrap; gap:8px;" id="filterBtns">
            <button class="btn btn-primary" onclick="EventsAdmin.setFilter('Tous', this)">Tous</button>
            <button class="btn btn-secondary" onclick="EventsAdmin.setFilter('En attente', this)">En attente de validation</button>
            <button class="btn btn-secondary" onclick="EventsAdmin.setFilter('En ligne', this)">En ligne</button>
            <button class="btn btn-secondary" onclick="EventsAdmin.setFilter('Terminés', this)">Terminés</button>
          </div>
          <div>
            <button type="button" class="btn btn-primary" onclick="EventsAdmin.openEventFormModal()">
              <i class="fa-solid fa-plus"></i> Ajouter un événement
            </button>
          </div>
        </div>

        <div id="pending-section" style="margin-bottom:32px; width:100%;">
          <h3 style="margin-bottom:16px; border-bottom:2px solid var(--color-warning); padding-bottom:8px; display:inline-block; color:#0F172A; font-weight:700;"><i class="fa-solid fa-hourglass-half"></i> EN ATTENTE DE VALIDATION</h3>
          <div class="grid gap-6 animate-fade-in-up" id="pending-container" style="display:grid; width:100%; grid-template-columns:repeat(auto-fit, minmax(min(100%, 320px), 1fr)); gap:20px;">
             <!-- JS Populated -->
          </div>
        </div>

        <div id="all-section" style="width:100%;">
          <h3 style="margin-bottom:16px; border-bottom:2px solid #E2E8F0; padding-bottom:8px; display:inline-block; color:#0F172A; font-weight:700;"><i class="fa-solid fa-list"></i> TOUS LES ÉVÉNEMENTS</h3>
          <div class="card" style="padding:16px; width:100%; box-sizing:border-box;">
            <p style="margin:0 0 12px; font-size:0.85rem; color:#64748b;">Vue cartes (validés, rejetés, autres statuts).</p>
            <div id="all-events-table" class="grid gap-6" style="display:grid; width:100%; grid-template-columns:repeat(auto-fit, minmax(min(100%, 320px), 1fr)); gap:20px;">
              <!-- cartes injectées par events-admin.js -->
            </div>
          </div>
        </div>
      </div>

      <div class="page-content" id="event-examine-view" style="display:none;">
        <button type="button" class="btn btn-secondary btn-sm" style="margin-bottom:24px;" onclick="EventsAdmin.showList()"><i class="fa-solid fa-arrow-left"></i> Retour</button>
        <div id="examine-content"></div>

        <div id="event-participants-section" style="display:none; margin-top:24px;">
          <h3 style="font-size:1.15rem; font-weight:700; margin-bottom:16px; color:#0F172A; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <i class="fa-solid fa-users" style="color:var(--color-primary, #FE5516);"></i> Participants inscrits
            <span id="participants-count-badge" style="background:var(--color-primary, #FE5516); color:#fff; font-size:0.8rem; padding:4px 12px; border-radius:20px; font-weight:600;">0</span>
          </h3>
          <div style="position:relative; max-width:360px; margin-bottom:16px;">
            <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#64748b; font-size:0.85rem;"></i>
            <input type="text" id="part-search" placeholder="Rechercher un participant..." onkeyup="EventsAdmin.filterParticipants()" style="width:100%; padding:10px 14px 10px 36px; background:#FFFFFF; border:1px solid #E2E8F0; border-radius:20px; color:#0F172A; font-size:0.9rem; box-sizing:border-box;">
          </div>
          <div style="overflow-x:auto; background:#FFFFFF; border-radius:12px; border:1px solid #E2E8F0;">
            <table style="width:100%; border-collapse:collapse;">
              <thead>
                <tr style="background:#F8FAFC;">
                  <th style="padding:12px 14px; text-align:left; color:#64748B; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; font-weight:600;">#</th>
                  <th style="padding:12px 14px; text-align:left; color:#64748B; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; font-weight:600;">Nom</th>
                  <th style="padding:12px 14px; text-align:left; color:#64748B; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; font-weight:600;">Email</th>
                  <th style="padding:12px 14px; text-align:left; color:#64748B; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; font-weight:600;">Tél.</th>
                  <th style="padding:12px 14px; text-align:left; color:#64748B; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; font-weight:600;">Université</th>
                  <th style="padding:12px 14px; text-align:left; color:#64748B; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; font-weight:600;">Année</th>
                  <th style="padding:12px 14px; text-align:left; color:#64748B; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; font-weight:600;">Inscription</th>
                  <th style="padding:12px 14px; text-align:left; color:#64748B; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; font-weight:600;">Statut</th>
                  <th style="padding:12px 14px; text-align:left; color:#64748B; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; font-weight:600;">Actions</th>
                </tr>
              </thead>
              <tbody id="participants-table-body">
                <tr><td colspan="9" style="text-align:center; padding:24px; color:#64748b;">Aucune donnée.</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>

  <div class="modal-overlay" id="eventFormModal">
    <div class="modal">
      <div class="modal-header">
        <h3 id="eventFormTitle"><i class="fa-solid fa-calendar-plus"></i> Ajouter un événement</h3>
        <button class="modal-close" onclick="Components.closeModal('eventFormModal')"><i class="fa-solid fa-times"></i></button>
      </div>
      <div class="modal-body">
        <form id="eventCrudForm">
          <input type="hidden" id="eventFormId">
          <div class="form-group">
            <label class="form-label">Titre <span style="color:var(--color-danger);">*</span></label>
            <input type="text" id="eventFormTitre" class="form-input" required placeholder="Titre de l'événement">
          </div>
          <div class="form-group">
            <label class="form-label">Description</label>
            <textarea id="eventFormDescription" class="form-textarea no-icon" rows="3" placeholder="Description de l'événement"></textarea>
          </div>
          <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:12px;">
            <div class="form-group">
              <label class="form-label">Date <span style="color:var(--color-danger);">*</span></label>
              <input type="date" id="eventFormDate" class="form-input" required>
            </div>
            <div class="form-group">
              <label class="form-label">Début <span style="color:var(--color-danger);">*</span></label>
              <input type="time" id="eventFormStart" class="form-input" required>
            </div>
            <div class="form-group">
              <label class="form-label">Fin <span style="color:var(--color-danger);">*</span></label>
              <input type="time" id="eventFormEnd" class="form-input" required>
            </div>
          </div>
          <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:12px;">
            <div class="form-group">
              <label class="form-label">Type</label>
              <select id="eventFormType" class="form-input">
                <option value="Présentiel">Présentiel</option>
                <option value="En ligne">En ligne</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Capacité <span style="color:var(--color-danger);">*</span></label>
              <input type="number" id="eventFormCapacite" class="form-input" min="1" step="1" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">ID partenaire (créateur) <span style="color:var(--color-danger);">*</span></label>
            <input type="number" id="eventFormPartnerInput" class="form-input" min="1" step="1" required placeholder="Ex: 3">
          </div>
          <div class="form-group" id="eventFormLieuGroup">
            <label class="form-label">Lieu</label>
            <input type="text" id="eventFormLieu" class="form-input" placeholder="Lieu de l'événement">
          </div>
          <div class="form-group" id="eventFormLienGroup" style="display:none;">
            <label class="form-label">Lien en ligne</label>
            <input type="url" id="eventFormLien" class="form-input" placeholder="https://...">
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="Components.closeModal('eventFormModal')">Annuler</button>
        <button type="button" class="btn btn-primary" onclick="EventsAdmin.submitEventForm()">Enregistrer</button>
      </div>
    </div>
  </div>

  <div class="modal-overlay" id="rejectModal">
    <div class="modal">
      <div class="modal-header">
        <h3><i class="fa-solid fa-ban"></i> Rejeter l'événement</h3>
        <button class="modal-close" onclick="Components.closeModal('rejectModal')"><i class="fa-solid fa-times"></i></button>
      </div>
      <div class="modal-body">
        <form id="rejectForm">
          <input type="hidden" id="rejectEventId">
          <p style="margin-bottom:16px; font-size:0.95rem;">Veuillez indiquer le motif du refus. Ce motif sera communiqué au partenaire.</p>
          <div class="form-group">
            <label class="form-label">Motif du refus <span style="color:var(--color-danger);">*</span></label>
            <textarea id="rejectReason" class="form-textarea no-icon" rows="4" required placeholder="Ex: Informations incomplètes, créneau non conforme..."></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" onclick="Components.closeModal('rejectModal')">Annuler</button>
        <button class="btn btn-danger" onclick="EventsAdmin.confirmReject()">Confirmer le rejet</button>
      </div>
    </div>
  </div>

  <script src="../js/app.js?v=20260420c"></script>
  <script src="../js/components.js?v=20260421a"></script>
  <script src="../assets/js/translate.js?v=20260511"></script>
  <script src="../js/events-admin.js?v=20260511e"></script>
</body>
</html>




















