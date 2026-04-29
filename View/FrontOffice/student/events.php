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
                <select id="sortSelect" class="sort-select">
                    <option value="date_asc">Trier par : Date (Croissante)</option>
                    <option value="date_desc">Trier par : Date (Décroissante)</option>
                    <option value="title_asc">Trier par : Titre (A-Z)</option>
                </select>
            </div>
        </div>

        <div id="events-container" class="grid-3 gap-6">
            <div style="grid-column: 1/-1; text-align:center; padding: 40px; color: var(--color-text-muted);">
                <i class="fa-solid fa-circle-notch fa-spin"></i> Chargement des événements...
            </div>
        </div>

        <!-- === MODAL D'INSCRIPTION ÉTUDIANT === -->
        <div id="subscribeModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:9999; align-items:center; justify-content:center;">
            <div style="background:var(--color-surface); padding:24px; border-radius:12px; width:450px; max-width:90%; position:relative; border: 1px solid var(--color-border); box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
                <button type="button" onclick="document.getElementById('subscribeModal').style.display='none'" style="position:absolute; top:15px; right:15px; background:none; border:none; color:#cbd5e1; cursor:pointer; font-size:1.2rem;"><i class="fa-solid fa-xmark"></i></button>
                <h3 style="color:white; margin-bottom: 5px; font-size:1.5rem;">Inscription</h3>
                <p id="subModalEventTitle" style="color:var(--color-primary); margin-bottom:20px; font-weight:bold; font-size:1.1rem;"></p>

                <form id="subscribeForm" onsubmit="event.preventDefault(); EventsStudent.confirmSubscribe();">
                    <input type="hidden" id="subEventId">
                    
                    <div class="form-group" style="margin-bottom:15px;">
                        <label for="subRemarque" style="color:white; display:block; margin-bottom:8px; font-weight:500;">Motivation / Remarque :</label>
                        <textarea id="subRemarque" rows="4" placeholder="Pourquoi souhaitez-vous participer à cet événement ?..." style="width:100%; padding:12px; border-radius:8px; border:1px solid var(--color-border); background:var(--color-background); color:white; resize:vertical;"></textarea>
                    </div>

                    <div style="display:flex; gap:12px; margin-top:24px;">
                        <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('subscribeModal').style.display='none'">Annuler</button>
                        <button type="submit" class="btn btn-primary" style="flex:1;">Valider mon inscription</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- === MODALE : Formulaire d'inscription étudiant === -->
        <div id="registerModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:10000; align-items:center; justify-content:center; overflow-y:auto; padding:20px; box-sizing:border-box;">
          <div style="background:#1e293b; border-radius:16px; width:100%; max-width:540px; margin:auto; position:relative; border:1px solid #334155; box-shadow:0 25px 60px rgba(0,0,0,0.7);">
            
            <!-- Header orange -->
            <div style="background:linear-gradient(135deg, #fe5516 0%, #c44010 100%); padding:22px 28px; border-radius:16px 16px 0 0;">
              <button type="button" onclick="document.getElementById('registerModal').style.display='none'" style="position:absolute; top:14px; right:14px; background:rgba(255,255,255,0.25); border:none; color:white; cursor:pointer; font-size:0.9rem; width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-xmark"></i></button>
              <div style="display:flex; align-items:center; gap:14px;">
                <div style="background:rgba(255,255,255,0.2); width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center;">
                  <i class="fa-solid fa-user-pen" style="color:white; font-size:1.2rem;"></i>
                </div>
                <div>
                  <h3 id="regModalTitle" style="color:white; margin:0; font-size:1.15rem; font-weight:700;">Formulaire d'inscription</h3>
                  <p id="regModalEventTitle" style="color:rgba(255,255,255,0.8); margin:3px 0 0; font-size:0.88rem;"></p>
                </div>
              </div>
            </div>

            <!-- Corps -->
            <div style="padding:26px 28px; background:#1e293b; border-radius:0 0 16px 16px;">
              <form id="registerModalForm" onsubmit="event.preventDefault(); EventsStudent.submitRegisterAndSubscribe(event)">
                <input type="hidden" id="regModalEventId">

                <!-- Nom + Prénom -->
                <div id="regmod-identity-section">
                  <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                    <div>
                      <label style="color:#94a3b8; display:block; margin-bottom:6px; font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.6px;">Nom *</label>
                      <input type="text" id="regmod-nom" placeholder="Votre nom" style="width:100%; padding:11px 13px; border-radius:8px; border:1.5px solid #334155; background:#0f172a; color:#f1f5f9; font-size:0.93rem; box-sizing:border-box; outline:none;" onfocus="this.style.borderColor='#fe5516'" onblur="this.style.borderColor='#334155'">
                      <div id="regmod-nom-error" style="color:#f87171; font-size:0.78rem; margin-top:4px;"></div>
                    </div>
                    <div>
                      <label style="color:#94a3b8; display:block; margin-bottom:6px; font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.6px;">Prénom *</label>
                      <input type="text" id="regmod-prenom" placeholder="Votre prénom" style="width:100%; padding:11px 13px; border-radius:8px; border:1.5px solid #334155; background:#0f172a; color:#f1f5f9; font-size:0.93rem; box-sizing:border-box; outline:none;" onfocus="this.style.borderColor='#fe5516'" onblur="this.style.borderColor='#334155'">
                      <div id="regmod-prenom-error" style="color:#f87171; font-size:0.78rem; margin-top:4px;"></div>
                    </div>
                  </div>

                  <!-- Email -->
                  <div style="margin-bottom:14px;">
                    <label style="color:#94a3b8; display:block; margin-bottom:6px; font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.6px;">Email *</label>
                    <div style="position:relative;">
                      <i class="fa-solid fa-envelope" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#64748b; font-size:0.82rem;"></i>
                      <input type="text" id="regmod-email" placeholder="votre@email.com" style="width:100%; padding:11px 13px 11px 34px; border-radius:8px; border:1.5px solid #334155; background:#0f172a; color:#f1f5f9; font-size:0.93rem; box-sizing:border-box; outline:none;" onfocus="this.style.borderColor='#fe5516'" onblur="this.style.borderColor='#334155'">
                    </div>
                    <div id="regmod-email-error" style="color:#f87171; font-size:0.78rem; margin-top:4px;"></div>
                  </div>

                  <!-- Mot de passe (caché si connecté) -->
                  <div id="regmod-password-row" style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                    <div>
                      <label style="color:#94a3b8; display:block; margin-bottom:6px; font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.6px;">Mot de passe *</label>
                      <input type="password" id="regmod-password" placeholder="Min. 6 caractères" style="width:100%; padding:11px 13px; border-radius:8px; border:1.5px solid #334155; background:#0f172a; color:#f1f5f9; font-size:0.93rem; box-sizing:border-box; outline:none;" onfocus="this.style.borderColor='#fe5516'" onblur="this.style.borderColor='#334155'">
                      <div id="regmod-password-error" style="color:#f87171; font-size:0.78rem; margin-top:4px;"></div>
                    </div>
                    <div>
                      <label style="color:#94a3b8; display:block; margin-bottom:6px; font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.6px;">Confirmer *</label>
                      <input type="password" id="regmod-confirm" placeholder="Retapez le mot de passe" style="width:100%; padding:11px 13px; border-radius:8px; border:1.5px solid #334155; background:#0f172a; color:#f1f5f9; font-size:0.93rem; box-sizing:border-box; outline:none;" onfocus="this.style.borderColor='#fe5516'" onblur="this.style.borderColor='#334155'">
                      <div id="regmod-confirm-error" style="color:#f87171; font-size:0.78rem; margin-top:4px;"></div>
                    </div>
                  </div>

                  <!-- Téléphone -->
                  <div style="margin-bottom:14px;">
                    <label style="color:#94a3b8; display:block; margin-bottom:6px; font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.6px;">Téléphone</label>
                    <div style="position:relative;">
                      <i class="fa-solid fa-phone" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#64748b; font-size:0.82rem;"></i>
                      <input type="text" id="regmod-telephone" placeholder="+216 9X XXX XXX" style="width:100%; padding:11px 13px 11px 34px; border-radius:8px; border:1.5px solid #334155; background:#0f172a; color:#f1f5f9; font-size:0.93rem; box-sizing:border-box; outline:none;" onfocus="this.style.borderColor='#fe5516'" onblur="this.style.borderColor='#334155'">
                    </div>
                    <div id="regmod-telephone-error" style="color:#f87171; font-size:0.78rem; margin-top:4px;"></div>
                  </div>
                </div>

                <!-- Séparateur -->
                <div id="regmod-separator" style="border-top:1px solid #334155; margin:4px 0 16px;"></div>

                <!-- Université + Année -->
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:22px;">
                  <div>
                    <label style="color:#94a3b8; display:block; margin-bottom:6px; font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.6px;">Université *</label>
                    <select id="regmod-university" style="width:100%; padding:11px 13px; border-radius:8px; border:1.5px solid #334155; background:#0f172a; color:#f1f5f9; font-size:0.93rem; box-sizing:border-box; cursor:pointer; outline:none;" onfocus="this.style.borderColor='#fe5516'" onblur="this.style.borderColor='#334155'">
                      <option value="">Sélectionnez</option>
                      <option value="ESPRIT">ESPRIT</option>
                      <option value="INSAT">INSAT</option>
                      <option value="ENIT">ENIT</option>
                      <option value="FST">FST</option>
                      <option value="IHEC">IHEC</option>
                      <option value="ENSI">ENSI</option>
                      <option value="autre">Autre</option>
                    </select>
                    <div id="regmod-university-error" style="color:#f87171; font-size:0.78rem; margin-top:4px;"></div>
                  </div>
                  <div>
                    <label style="color:#94a3b8; display:block; margin-bottom:6px; font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.6px;">Année d'étude *</label>
                    <select id="regmod-annee" style="width:100%; padding:11px 13px; border-radius:8px; border:1.5px solid #334155; background:#0f172a; color:#f1f5f9; font-size:0.93rem; box-sizing:border-box; cursor:pointer; outline:none;" onfocus="this.style.borderColor='#fe5516'" onblur="this.style.borderColor='#334155'">
                      <option value="">Sélectionnez</option>
                      <option value="1ere">1ère année</option>
                      <option value="2eme">2ème année</option>
                      <option value="3eme">3ème année</option>
                      <option value="4eme">4ème année</option>
                      <option value="5eme">5ème année</option>
                      <option value="Master 1">Master 1</option>
                      <option value="Master 2">Master 2</option>
                    </select>
                    <div id="regmod-annee-error" style="color:#f87171; font-size:0.78rem; margin-top:4px;"></div>
                  </div>
                </div>

                <!-- Boutons -->
                <div style="display:flex; gap:12px;">
                  <button type="button" onclick="document.getElementById('registerModal').style.display='none'" style="flex:1; padding:12px; border-radius:8px; border:1.5px solid #475569; background:transparent; color:#94a3b8; font-size:0.95rem; cursor:pointer; font-weight:600;">
                    <i class="fa-solid fa-xmark"></i> Annuler
                  </button>
                  <button type="submit" id="regmod-submit" style="flex:2; padding:12px; border-radius:8px; border:none; background:linear-gradient(135deg, #fe5516, #c44010); color:white; font-size:0.95rem; cursor:pointer; font-weight:700;">
                    <i class="fa-solid fa-check"></i> S'inscrire
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

      </div>
    </main>
  </div>

  <script src="/projet2a22/js/app.js"></script>
  <script src="/projet2a22/js/components.js"></script>
  <script src="/projet2a22/js/student.js"></script>
  <script src="/projet2a22/js/events-student.js?v=14"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      Student.init();
    });
  </script>
  </script>
</body>
</html>