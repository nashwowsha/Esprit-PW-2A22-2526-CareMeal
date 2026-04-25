<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Les événements CareMeal — Découvrez et participez.">
  <title>Événements — CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/projet2a22/css/main.css">
  <link rel="stylesheet" href="/projet2a22/css/components.css">
  <link rel="stylesheet" href="/projet2a22/css/dashboard.css">
  <style>
      .filters { display:flex; gap:12px; margin-bottom:24px; }
      .grid-3 { display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:24px; }
      .stats-panel { display:grid; grid-template-columns:repeat(3, 1fr); gap:16px; margin-bottom:32px; }
      .stat-card { background:var(--color-panel-bg); padding:20px; border-radius:8px; border:1px solid var(--color-border); text-align:center; }
      .stat-value { font-size:2rem; font-weight:800; color:var(--color-white); margin-bottom:4px; font-family:'Archivo Black', sans-serif; }
      .stat-label { font-size:0.85rem; color:var(--color-text-muted); text-transform:uppercase; font-weight:600; letter-spacing:1px; }

      /* Styles pour l'affichage en cartes identiques a Admin/Partner */
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
      .badge { padding: 5px 12px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; color: white; display: inline-block; text-transform:uppercase; letter-spacing:0.5px;}
      .badge-presentiel { background: #0284c7; }
      .badge-online { background: #7e22ce; }
      .badge-valide { background: #10b981; color: white;}
      .badge-complet { background: #ef4444; color: white;}

      .card-body { padding: 15px; display: flex; flex-direction: column; gap: 10px; flex: 1; }
      .event-title { font-size: 1.35rem; font-weight: bold; margin: 0; color: #f8fafc; }
      .event-description { color: #cbd5e1; font-size: 0.95em; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 5px; }

      .event-detail { font-size: 0.9rem; color: #94a3b8; display: flex; align-items: flex-start; gap: 8px; margin: 3px 0; }
      .event-detail i { width: 16px; margin-top: 3px; color: #64748b; text-align: center; }

      .progress-container { width: 100%; background-color: var(--color-background); border-radius: 4px; overflow: hidden; margin-top: 5px; height: 6px; }
      .progress-bar { height: 100%; border-radius: 4px; transition: width 0.3s ease; }
      .progress-bar-success { background-color: #10b981; }
      .progress-bar-warning { background-color: #f59e0b; }
      .progress-bar-danger { background-color: #ef4444; }
  </style>
</head>
<body>
  <div class="dashboard-layout">
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <div class="sidebar-logo"><i class="fa-solid fa-utensils"></i></div>
        <div class="sidebar-brand">Care<span>Meal</span></div>
      </div>
      <nav class="sidebar-nav">
        <div class="sidebar-section">
          <div class="sidebar-section-title">Menu</div>
          <a href="dashboard.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-house"></i></span> Accueil</a>
          <a href="profile.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-user"></i></span> Mon Profil</a>
          <a href="preferences.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-utensils"></i></span> Préférences</a>
          <a href="orders.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-bag-shopping"></i></span> Mes Commandes</a>
          <a href="events.php" class="sidebar-link active">
            <span class="link-icon"><i class="fa-solid fa-calendar-days"></i></span> Événements
          </a>
          <a href="points.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-star"></i></span> Mes Points</a>
        </div>
        <div class="sidebar-section">
          <div class="sidebar-section-title">Paramètres</div>
          <a href="settings.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-gear"></i></span> Paramètres</a>
        </div>
      </nav>
      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="avatar" id="sidebar-user-avatar">AA</div>
          <div class="sidebar-user-info">
            <div class="sidebar-user-name" id="sidebar-user-name">Utilisateur</div>
            <div class="sidebar-user-role" id="sidebar-user-role">Étudiant</div>
          </div>
          <button class="sidebar-logout" data-action="logout" title="Déconnexion"><i class="fa-solid fa-arrow-right-from-bracket"></i></button>
        </div>
      </div>
    </aside>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle">☰</button>
          <div class="page-title"><h2>Événements</h2><p>Découvrez et participez</p></div>
        </div>
        <div class="header-right">
          <button class="header-notification"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button>
          <div class="avatar avatar-sm" id="header-avatar">AA</div>
        </div>
      </header>

      <div class="page-content">

        <div class="stats-panel">
            <div class="stat-card">
                <div class="stat-value" id="stat-available">-</div>
                <div class="stat-label" style="color:var(--color-success)">Disponibles</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="stat-subscribed">-</div>
                <div class="stat-label" style="color:var(--color-primary)">Mes Inscriptions</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="stat-full">-</div>
                <div class="stat-label" style="color:var(--color-text-muted)">Complets</div>
            </div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
            <div class="filters" style="margin-bottom:0;">
                <button id="filter-all" class="btn btn-primary"><i class="fa-solid fa-list"></i> Tous les événements</button>
                <button id="filter-my" class="btn btn-outline"><i class="fa-solid fa-check"></i> Mes inscriptions</button>
            </div>
            
            <div style="display:flex; gap:12px; align-items:center;">
                <div style="position:relative;">
                    <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--color-text-muted);"></i>
                    <input type="text" id="searchInput" placeholder="Rechercher par titre ou lieu..." style="padding:10px 10px 10px 35px; border-radius:20px; border:1px solid var(--color-border); background:var(--color-surface); color:white; width: 250px;">
                </div>
                <select id="sortSelect" style="padding:10px; border-radius:20px; border:1px solid var(--color-border); background:var(--color-surface); color:white; cursor:pointer;">
                    <option style="background:#1e293b; color:white;" value="date_asc">🗓 Date (Croissante)</option>
                    <option style="background:#1e293b; color:white;" value="date_desc">🗓 Date (Décroissante)</option>
                    <option style="background:#1e293b; color:white;" value="title_asc">🔤 Titre (A-Z)</option>
                </select>
            </div>
        </div>

        <div id="events-container" class="grid-3 gap-6">
            <div style="grid-column: 1/-1; text-align:center; padding: 40px; color: var(--color-text-muted);">
                <i class="fa-solid fa-circle-notch fa-spin"></i> Chargement des événements...
            </div>
        </div>

      </div>
    </main>
  </div>

  <script src="/projet2a22/js/app.js"></script>
  <script src="/projet2a22/js/components.js"></script>
  <script src="/projet2a22/js/student.js"></script>
  <script src="/projet2a22/js/events-student.js?v=5"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => Student.init());
  </script>
</body>
</html>