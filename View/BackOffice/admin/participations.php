<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Participations</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/css/main.css">
  <link rel="stylesheet" href="/css/components.css">
  <link rel="stylesheet" href="/css/dashboard.css">
  <link rel="stylesheet" href="/css/theme-fix.css">
  <style>
    .table-container { background: var(--color-surface); border-radius: 12px; overflow: hidden; }
    .table { width: 100%; border-collapse: collapse; text-align: left; }
    .table th, .table td { padding: 16px; border-bottom: 1px solid var(--color-border, rgba(0,0,0,0.05)); }
    .table th { background: var(--color-bg, #f8fafc); font-weight: 600; color: var(--color-text-muted, #64748b); }
    .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: bold; }
    .status-inscrit { background: rgba(16, 185, 129, 0.2); color: #10b981; }
    .status-annule { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

    /* Selects thème sombre — même style que sort-select */
    .dark-select {
        padding: 11px 40px 11px 18px;
        border-radius: 24px;
        border: none;
        background: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 4 5"><path fill="%23ffffff" d="M2 0L0 2h4zm0 5L0 3h4z"/></svg>') no-repeat right 15px center, linear-gradient(135deg, #fe5516 0%, #FF8A50 100%);
        background-size: 10px 12px, auto;
        color: white;
        font-weight: 600;
        font-size: 0.92rem;
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(254, 85, 22, 0.3);
        outline: none;
    }
    .dark-select:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(254, 85, 22, 0.45);
    }
    .dark-select:focus {
        box-shadow: 0 0 0 3px rgba(254, 85, 22, 0.5);
    }
    .dark-select option {
        background-color: #0f172a;
        color: #f8fafc;
        padding: 12px;
        font-weight: 500;
    }

    /* Barre de recherche */
    .search-input {
        width: 100%;
        padding: 11px 16px 11px 42px;
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: 24px;
        color: white;
        font-size: 0.92rem;
        outline: none;
        transition: border-color 0.2s;
    }
    .search-input:focus { border-color: var(--color-primary); }
    .search-input::placeholder { color: var(--color-text-muted); }

    /* Deux assistants cote a cote : chatbot a droite, vocal decale */
    #chatbot-toggle { right: 24px !important; bottom: 24px !important; }
    #chatbot-window { right: 24px !important; }
    .voice-assistant-fab { right: 96px !important; bottom: 24px !important; }
  </style>
</head>
<body>
  <div class="dashboard-layout">
    <?php
      $activePage = 'participations';
      require dirname(__DIR__, 3) . '/admin/_admin_sidebar.php';
    ?>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <div class="page-title"><h2>Gestion des Participations</h2><p>CRUD de la 2ème entité avec Jointure M/M</p></div>
        </div>
        <div class="header-right">
          <button class="header-notification"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button>
        </div>
      </header>

      <div class="page-content">

        <!-- Stats rapides -->
        <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:28px;" id="part-stats"></div>

        <!-- Search and Sort -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
            <div style="position:relative; width:320px;">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:var(--color-text-muted); font-size:0.85rem;"></i>
                <input type="text" id="partSearchInput" placeholder="Rechercher par nom, email ou événement..."
                    class="search-input" onkeyup="filterAndSortParticipations()">
            </div>
            <div style="display:flex; gap:12px; align-items:center;">
                <select id="partStatusSelect" class="dark-select" onchange="filterAndSortParticipations()">
                    <option value="Tous">Tous les statuts</option>
                    <option value="Inscrit">Inscrit</option>
                    <option value="Présent">Présent</option>
                    <option value="Absent">Absent</option>
                    <option value="Annulé">Annulé</option>
                </select>
                <select id="partSortSelect" class="dark-select" onchange="filterAndSortParticipations()">
                    <option value="date_desc">Date ? (récents)</option>
                    <option value="date_asc">Date ? (anciens)</option>
                    <option value="nom_asc">Nom A-Z</option>
                </select>
            </div>
        </div>

        <div class="table-container" style="overflow-x:auto;">
          <table class="table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Étudiant</th>
                <th>Université / Année</th>
                <th>Événement</th>
                <th>Date inscription</th>
                <th>Statut</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="participations-body">
                <tr><td colspan="7" style="text-align:center; padding:30px;"><i class="fa-solid fa-circle-notch fa-spin"></i> Chargement...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <script src="/js/app.js"></script>
  <script src="/js/participations-admin.js"></script>
  <script src="/assets/js/chatbot.js"></script>
  <script src="/assets/js/notifications.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      CareMealChatbot.init('admin');
      CareMealNotifications.init('admin');
    });
  </script>
  <script src="/js/admin-voice-assistant.js?v=20260508"></script>
</body>
</html>













