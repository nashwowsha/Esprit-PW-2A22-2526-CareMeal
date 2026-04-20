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
          <a href="dashboard.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-chart-column"></i></span> Vue globale</a>
          <a href="users.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-users"></i></span> Utilisateurs</a>
          <a href="partners.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-store"></i></span> Partenaires</a>
          <a href="restaurants.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-shop"></i></span> Restaurants</a>
          <a href="preferences.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-sliders"></i></span> Preferences</a>
          <a href="events.php" class="sidebar-link active"><span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> Événements</a>
          <a href="logs.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-clipboard-list"></i></span> Logs d'activité</a>
        </div>
      </nav>
      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="avatar" id="sidebar-user-avatar">A</div>
          <div class="sidebar-user-info">
            <div class="sidebar-user-name" id="sidebar-user-name">Admin</div>
            <div class="sidebar-user-role" id="sidebar-user-role">Super Admin</div>
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
        </div>

        <div id="pending-section" style="margin-bottom:32px;">
          <h3 style="margin-bottom:16px; border-bottom:2px solid var(--color-warning); padding-bottom:8px; display:inline-block;"><i class="fa-solid fa-hourglass-half"></i> EN ATTENTE DE VALIDATION</h3>
          <div class="grid gap-6 animate-fade-in-up" id="pending-container" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(350px, 1fr));">
             <!-- JS Populated -->
          </div>
        </div>

        <div id="all-section">
          <h3 style="margin-bottom:16px; border-bottom:2px solid var(--color-border); padding-bottom:8px; display:inline-block;"><i class="fa-solid fa-list"></i> TOUS LES ÉVÉNEMENTS</h3>
          <div class="card">
            <div class="table-container" style="overflow-x:auto;">
              <table class="data-table" style="width:100%;">
                <thead>
                  <tr>
                    <th>Titre</th>
                    <th>Partenaire</th>
                    <th>Date & Heure</th>
                    <th>Statut</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="all-events-table">
                  <!-- JS Populated -->
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="page-content" id="event-examine-view" style="display:none;">
        <button class="btn btn-secondary btn-sm" style="margin-bottom:24px;" onclick="EventsAdmin.showList()"><i class="fa-solid fa-arrow-left"></i> Retour</button>
        <div id="examine-content"></div>
      </div>
    </main>
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
  <script src="../js/components.js?v=20260420j"></script>
  <script src="../js/events-admin.js?v=20260420z"></script>
</body>
</html>



















