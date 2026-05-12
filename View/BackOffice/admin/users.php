<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Gestion des utilisateurs CareMeal - Liste, filtre, actions.">
  <title>Gestion Utilisateurs - CareMeal Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/css/main.css">
  <link rel="stylesheet" href="/css/components.css">
  <link rel="stylesheet" href="/css/dashboard.css">
  <link rel="stylesheet" href="/css/theme-fix.css">
</head>
<body>
  <div class="dashboard-layout">
    <?php
      $activePage = 'users';
      require dirname(__DIR__, 3) . '/admin/_admin_sidebar.php';
    ?>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle">
              <i class="fa-solid fa-bars"></i></button>
          <div class="page-title"><h2>Gestion Utilisateurs</h2><p id="users-count">0 utilisateurs</p></div>
        </div>
        <div class="header-right">
          <a href="/View/FrontOffice/feed.php" title="Fil d'actualité" style="width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--color-text-muted);font-size:1.1rem;text-decoration:none;transition:background .15s;" onmouseover="this.style.background='rgba(254,85,22,0.1)';this.style.color='#FE5516'" onmouseout="this.style.background='none';this.style.color='var(--color-text-muted)'">
            <i class="fa-solid fa-house"></i>
          </a>
          <button class="header-notification"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button>
          <div class="avatar avatar-sm" id="header-avatar" style="background:linear-gradient(135deg,#EF4444,#F87171);">A</div>
        </div>
      </header>

      <div class="page-content">
        <!-- Toolbar -->
        <div class="toolbar animate-fade-in-up">
          <div class="toolbar-left">
            <div class="search-bar">
              <span class="search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
              <input type="text" id="user-search" placeholder="Rechercher un utilisateur...">
            </div>
            <div class="filter-group">
              <button class="filter-btn active" data-filter="all" onclick="Admin.filterUsers('all')">Tous</button>
              <button class="filter-btn" data-filter="students" onclick="Admin.filterUsers('students')">
              <i class="fa-solid fa-graduation-cap"></i> Étudiants</button>
              <button class="filter-btn" data-filter="partners" onclick="Admin.filterUsers('partners')">
              <i class="fa-solid fa-store"></i> Partenaires</button>
              <button class="filter-btn" data-filter="active" onclick="Admin.filterUsers('active')">
              <i class="fa-solid fa-check"></i> Actifs</button>
              <button class="filter-btn" data-filter="pending" onclick="Admin.filterUsers('pending')">
              <i class="fa-solid fa-hourglass-half"></i> En attente</button>
              <button class="filter-btn" data-filter="banned" onclick="Admin.filterUsers('banned')">
              <i class="fa-solid fa-ban"></i> Bannis</button>
            </div>
          </div>
          <div class="toolbar-right" style="margin-left: auto;">
            <button class="btn btn-primary" onclick="Admin.exportPDF()"><i class="fa-solid fa-file-pdf"></i> Exporter PDF</button>
          </div>
        </div>

        <!-- Users Table -->
        <div class="card animate-fade-in-up stagger-1">
          <div class="table-container">
            <table class="data-table" id="users-table">
              <thead>
                <tr>
                  <th onclick="Admin.sortUsers('name')" style="cursor:pointer;">Utilisateur <i class="fa-solid fa-sort" style="opacity:0.5; font-size:0.8em; margin-left:5px;"></i></th>
                  <th onclick="Admin.sortUsers('role')" style="cursor:pointer;">Rôle <i class="fa-solid fa-sort" style="opacity:0.5; font-size:0.8em; margin-left:5px;"></i></th>
                  <th onclick="Admin.sortUsers('status')" style="cursor:pointer;">Statut <i class="fa-solid fa-sort" style="opacity:0.5; font-size:0.8em; margin-left:5px;"></i></th>
                  <th onclick="Admin.sortUsers('date')" style="cursor:pointer;">Inscrit le <i class="fa-solid fa-sort" style="opacity:0.5; font-size:0.8em; margin-left:5px;"></i></th>
                  <th onclick="Admin.sortUsers('activity')" style="cursor:pointer;">Activité <i class="fa-solid fa-sort" style="opacity:0.5; font-size:0.8em; margin-left:5px;"></i></th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="users-table-body">
                <!-- Loaded dynamically -->
              </tbody>
            </table>
          </div>
        </div>

        <!-- User Statistics Panel -->
        <div class="card animate-fade-in-up stagger-2" style="margin-top:1.5rem; overflow:hidden;">

          <!-- Header -->
          <div style="padding:1.25rem 1.75rem; border-bottom:1px solid var(--color-border); display:flex; align-items:center; justify-content:space-between;">
            <div style="display:flex; align-items:center; gap:10px;">
              <div style="width:32px;height:32px;border-radius:8px;background:rgba(239,68,68,0.12);display:flex;align-items:center;justify-content:center;">
                <i class="fa-solid fa-chart-pie" style="color:#ef4444; font-size:0.9rem;"></i>
              </div>
              <span style="font-size:0.95rem; font-weight:700; color:var(--color-text);">Statistiques des utilisateurs</span>
            </div>
            <span style="font-size:0.75rem; color:var(--color-text-muted); background:var(--color-bg-secondary); padding:4px 10px; border-radius:20px; border:1px solid var(--color-border);">Temps réel</span>
          </div>

          <div style="padding:1.75rem; display:grid; grid-template-columns:auto 1fr 1fr; gap:1.25rem; align-items:stretch;">

            <!-- Total card -->
            <div style="display:flex; flex-direction:column; justify-content:center; align-items:center; min-width:150px; border-radius:16px; padding:1.75rem 1.5rem; gap:10px;
              background: linear-gradient(135deg, rgba(239,68,68,0.15) 0%, rgba(239,68,68,0.05) 100%);
              border:1px solid rgba(239,68,68,0.25);">
              <div style="width:56px; height:56px; border-radius:50%; background:rgba(239,68,68,0.2); display:flex; align-items:center; justify-content:center; box-shadow:0 0 0 8px rgba(239,68,68,0.07);">
                <i class="fa-solid fa-users" style="color:#ef4444; font-size:1.4rem;"></i>
              </div>
              <div style="font-size:2.6rem; font-weight:900; color:var(--color-text); line-height:1; letter-spacing:-1px;" id="ustat-total">—</div>
              <div style="font-size:0.78rem; font-weight:500; color:var(--color-text-muted); text-align:center; text-transform:uppercase; letter-spacing:.06em;">Total utilisateurs</div>
            </div>

            <!-- Donut Rôle -->
            <div style="border-radius:16px; padding:1.5rem; border:1px solid var(--color-border); background:var(--color-bg-secondary); display:flex; flex-direction:column; gap:1rem;">
              <div style="display:flex; align-items:center; gap:8px;">
                <span style="width:6px;height:6px;border-radius:50%;background:#3b82f6;display:inline-block;"></span>
                <span style="font-size:0.72rem; font-weight:700; color:var(--color-text-muted); text-transform:uppercase; letter-spacing:.08em;">Par rôle</span>
              </div>
              <div style="display:flex; align-items:center; gap:1.5rem;">
                <!-- SVG Donut Rôle -->
                <div style="position:relative; flex-shrink:0; width:120px; height:120px;">
                  <svg viewBox="0 0 36 36" style="width:120px;height:120px;transform:rotate(-90deg);filter:drop-shadow(0 2px 8px rgba(0,0,0,0.25));">
                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="3"/>
                    <circle id="donut-role-student" cx="18" cy="18" r="15.9" fill="none" stroke="#3b82f6" stroke-width="3"
                      stroke-dasharray="0 100" stroke-linecap="butt" style="transition:stroke-dasharray .7s cubic-bezier(.4,0,.2,1);"/>
                    <circle id="donut-role-partner" cx="18" cy="18" r="15.9" fill="none" stroke="#f97316" stroke-width="3"
                      stroke-dasharray="0 100" stroke-linecap="butt" style="transition:stroke-dasharray .7s cubic-bezier(.4,0,.2,1);"/>
                    <circle id="donut-role-admin"   cx="18" cy="18" r="15.9" fill="none" stroke="#a855f7" stroke-width="3"
                      stroke-dasharray="0 100" stroke-linecap="butt" style="transition:stroke-dasharray .7s cubic-bezier(.4,0,.2,1);"/>
                  </svg>
                  <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1px;">
                    <span style="font-size:1.25rem;font-weight:900;color:var(--color-text);line-height:1;" id="donut-role-center">—</span>
                    <span style="font-size:0.58rem;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;">total</span>
                  </div>
                </div>
                <!-- Légende rôle -->
                <div style="display:flex; flex-direction:column; gap:10px; flex:1;">
                  <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                    <span style="display:flex;align-items:center;gap:8px;font-size:0.83rem;color:var(--color-text-muted);">
                      <span style="width:8px;height:8px;border-radius:2px;background:#3b82f6;flex-shrink:0;"></span> Étudiants
                    </span>
                    <span style="font-size:0.9rem;font-weight:800;color:var(--color-text);" id="ustat-student-val">—</span>
                  </div>
                  <div style="height:1px;background:var(--color-border);opacity:.5;"></div>
                  <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                    <span style="display:flex;align-items:center;gap:8px;font-size:0.83rem;color:var(--color-text-muted);">
                      <span style="width:8px;height:8px;border-radius:2px;background:#f97316;flex-shrink:0;"></span> Partenaires
                    </span>
                    <span style="font-size:0.9rem;font-weight:800;color:var(--color-text);" id="ustat-partner-val">—</span>
                  </div>
                  <div style="height:1px;background:var(--color-border);opacity:.5;"></div>
                  <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                    <span style="display:flex;align-items:center;gap:8px;font-size:0.83rem;color:var(--color-text-muted);">
                      <span style="width:8px;height:8px;border-radius:2px;background:#a855f7;flex-shrink:0;"></span> Admins
                    </span>
                    <span style="font-size:0.9rem;font-weight:800;color:var(--color-text);" id="ustat-admin-val">—</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Donut Statut -->
            <div style="border-radius:16px; padding:1.5rem; border:1px solid var(--color-border); background:var(--color-bg-secondary); display:flex; flex-direction:column; gap:1rem;">
              <div style="display:flex; align-items:center; gap:8px;">
                <span style="width:6px;height:6px;border-radius:50%;background:#22c55e;display:inline-block;"></span>
                <span style="font-size:0.72rem; font-weight:700; color:var(--color-text-muted); text-transform:uppercase; letter-spacing:.08em;">Par statut</span>
              </div>
              <div style="display:flex; align-items:center; gap:1.5rem;">
                <!-- SVG Donut Statut -->
                <div style="position:relative; flex-shrink:0; width:120px; height:120px;">
                  <svg viewBox="0 0 36 36" style="width:120px;height:120px;transform:rotate(-90deg);filter:drop-shadow(0 2px 8px rgba(0,0,0,0.25));">
                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="3"/>
                    <circle id="donut-status-active"  cx="18" cy="18" r="15.9" fill="none" stroke="#22c55e" stroke-width="3"
                      stroke-dasharray="0 100" stroke-linecap="butt" style="transition:stroke-dasharray .7s cubic-bezier(.4,0,.2,1);"/>
                    <circle id="donut-status-pending" cx="18" cy="18" r="15.9" fill="none" stroke="#eab308" stroke-width="3"
                      stroke-dasharray="0 100" stroke-linecap="butt" style="transition:stroke-dasharray .7s cubic-bezier(.4,0,.2,1);"/>
                    <circle id="donut-status-banned"  cx="18" cy="18" r="15.9" fill="none" stroke="#ef4444" stroke-width="3"
                      stroke-dasharray="0 100" stroke-linecap="butt" style="transition:stroke-dasharray .7s cubic-bezier(.4,0,.2,1);"/>
                  </svg>
                  <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1px;">
                    <span style="font-size:1.25rem;font-weight:900;color:var(--color-text);line-height:1;" id="donut-status-center">—</span>
                    <span style="font-size:0.58rem;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;">total</span>
                  </div>
                </div>
                <!-- Légende statut -->
                <div style="display:flex; flex-direction:column; gap:10px; flex:1;">
                  <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                    <span style="display:flex;align-items:center;gap:8px;font-size:0.83rem;color:var(--color-text-muted);">
                      <span style="width:8px;height:8px;border-radius:2px;background:#22c55e;flex-shrink:0;"></span> Actifs
                    </span>
                    <span style="font-size:0.9rem;font-weight:800;color:var(--color-text);" id="ustat-active-val">—</span>
                  </div>
                  <div style="height:1px;background:var(--color-border);opacity:.5;"></div>
                  <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                    <span style="display:flex;align-items:center;gap:6px;font-size:0.83rem;color:var(--color-text-muted);">
                      <span style="width:8px;height:8px;border-radius:2px;background:#eab308;flex-shrink:0;"></span> En attente
                    </span>
                    <span style="font-size:0.9rem;font-weight:800;color:var(--color-text);" id="ustat-pending-val">—</span>
                  </div>
                  <div style="height:1px;background:var(--color-border);opacity:.5;"></div>
                  <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                    <span style="display:flex;align-items:center;gap:8px;font-size:0.83rem;color:var(--color-text-muted);">
                      <span style="width:8px;height:8px;border-radius:2px;background:#ef4444;flex-shrink:0;"></span> Bannis
                    </span>
                    <span style="font-size:0.9rem;font-weight:800;color:var(--color-text);" id="ustat-banned-val">—</span>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

      </div>
    </main>
  </div>

  <!-- Bibliothèques pour l'export PDF -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.1/jspdf.plugin.autotable.min.js"></script>
  
  <script src="/js/app.js"></script>
  <script src="/js/components.js"></script>
  <script src="/js/admin.js"></script>
  <script>document.addEventListener('DOMContentLoaded', () => Admin.initUsers());</script>
  <script src="/js/admin-voice-assistant.js?v=20260508"></script>
</body>
</html>














