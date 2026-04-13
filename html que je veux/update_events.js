const fs = require('fs');
const path = require('path');

const studentHtml = `<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Découvrez et inscrivez-vous aux événements CareMeal">
  <title>Événements - Étudiant | CareMeal</title>
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
          <div class="sidebar-section-title">Menu</div>
          <a href="dashboard.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-house"></i></span> Accueil</a>
          <a href="profile.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-user"></i></span> Mon Profil</a>
          <a href="preferences.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-utensils"></i></span> Préférences</a>
          <a href="events.html" class="sidebar-link active"><span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> Événements</a>
          <a href="orders.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-box"></i></span> Mes Commandes</a>
        </div>
      </nav>
      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="avatar" id="sidebar-user-avatar" style="background:linear-gradient(135deg,#3B82F6,#60A5FA);">E</div>
          <div class="sidebar-user-info">
            <div class="sidebar-user-name" id="sidebar-user-name">Étudiant</div>
            <div class="sidebar-user-role" id="sidebar-user-role">Étudiant</div>
          </div>
        </div>
      </div>
    </aside>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle"><i class="fa-solid fa-bars"></i></button>
          <div class="page-title"><h2>Événements</h2><p>Participez aux distributions et ateliers anti-gaspi</p></div>
        </div>
        <div class="header-right">
          <div class="avatar avatar-sm" id="header-avatar" style="background:#3B82F6;">E</div>
        </div>
      </header>

      <div class="page-content">
        <div style="display: flex; gap: 20px; border-bottom: 1px solid var(--color-border); margin-bottom: 24px;">
          <div class="tab active" style="padding: 10px 15px; cursor: pointer; border-bottom: 2px solid var(--color-primary); color: var(--color-primary);">Événements disponibles</div>
        </div>

        <div class="grid grid-3 gap-6 animate-fade-in-up">
          <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
              <div>
                <h3 class="card-title" style="margin-bottom: 4px; font-size: 1.1rem;">Atelier Cuisine 🍳</h3>
                <span class="badge badge-info">Présentiel</span>
              </div>
            </div>
            <p style="color: var(--color-text); margin-bottom: 16px; font-size: 0.9rem;">Apprenez à cuisiner vos restes avec le chef du Campus.</p>
            <div style="color: var(--color-text-muted); font-size: 0.85rem; margin-bottom: 8px;">
              <span style="display:inline-block; margin-right:12px;"><i class="fa-solid fa-calendar" style="color:var(--color-primary);"></i> 14 Mai 2026</span>
              <span><i class="fa-solid fa-clock" style="color:var(--color-primary);"></i> 15h00</span>
            </div>
            <div style="color: var(--color-text-muted); font-size: 0.85rem; margin-bottom: 16px;">
              <span><i class="fa-solid fa-location-dot" style="color:var(--color-primary);"></i> RU El Manar</span>
            </div>
            <div style="border-top: 1px solid var(--color-border); padding-top: 16px; display: flex; justify-content: space-between; align-items: center;">
              <span style="font-size: 0.85rem; color: var(--color-text-muted);">Places : <strong>12/20</strong></span>
              <button class="btn btn-primary btn-sm" onclick="this.innerHTML='<i class=\\'fa-solid fa-check\\'></i> Inscrit'; this.classList.remove('btn-primary'); this.classList.add('btn-success'); this.disabled=true;">S'inscrire</button>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
  <script src="../js/app.js"></script>
  <script src="../js/components.js"></script>
</body>
</html>`;

const partnerHtml = `<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Événements - Partenaire | CareMeal</title>
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
          <div class="sidebar-section-title">Partenaire</div>
          <a href="dashboard.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-house"></i></span> Mon Établissement</a>
          <a href="offers.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-box"></i></span> Mes Offres</a>
          <a href="events.html" class="sidebar-link active"><span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> Événements</a>
          <a href="stats.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-chart-column"></i></span> Statistiques</a>
          <a href="settings.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-gear"></i></span> Paramètres</a>
        </div>
      </nav>
    </aside>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <div class="page-title"><h2>Mes Événements</h2><p>Gérez vos ateliers</p></div>
        </div>
        <div class="header-right">
          <button class="btn btn-primary" onclick="openModal('createEventModal')"><i class="fa-solid fa-plus"></i> Créer un événement</button>
        </div>
      </header>

      <div class="page-content">
        <div class="grid grid-3 gap-6 animate-fade-in-up">
          <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
              <div>
                <h3 class="card-title" style="margin-bottom: 4px; font-size: 1.1rem;">Distribution 🍱</h3>
                <span class="badge badge-info">Présentiel</span>
              </div>
              <span class="badge badge-success"><i class="fa-solid fa-check"></i> Validé</span>
            </div>
            <p style="color: var(--color-text); margin-bottom: 16px; font-size: 0.9rem;">Distribution gratuite aux étudiants.</p>
            <div style="border-top: 1px solid var(--color-border); padding-top: 16px; display: flex; justify-content: space-between; align-items: center;">
              <span style="font-size: 0.85rem; color: var(--color-text-muted);"><strong>24/50</strong> inscrits</span>
              <button class="btn btn-secondary btn-sm" onclick="openModal('participantsModal')"><i class="fa-solid fa-users"></i> Gérer</button>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <div class="modal-overlay" id="createEventModal">
    <div class="modal" style="width:100%; max-width:600px;">
      <div class="modal-header">
        <h3>Créer un événement</h3>
        <button class="modal-close" onclick="closeModal('createEventModal')"><i class="fa-solid fa-times"></i></button>
      </div>
      <div class="modal-body">
        <form>
          <div class="form-group">
            <label class="form-label">Titre</label>
            <input type="text" class="form-control" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" onclick="closeModal('createEventModal')">Annuler</button>
        <button class="btn btn-primary">Soumettre</button>
      </div>
    </div>
  </div>

  <div class="modal-overlay" id="participantsModal">
    <div class="modal" style="width:100%; max-width:600px;">
      <div class="modal-header">
        <h3>Inscrits - Distribution 🍱</h3>
        <button class="modal-close" onclick="closeModal('participantsModal')"><i class="fa-solid fa-times"></i></button>
      </div>
      <div class="modal-body">
        <div class="card" style="padding: 12px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;">
          <div style="display: flex; align-items: center; gap: 12px;">
            <div class="avatar avatar-sm">A</div>
            <div>
              <div style="font-weight:600; color:white;">Aziz (Néoclix)</div>
            </div>
          </div>
          <select class="form-control" style="width: auto; padding: 5px; font-size:0.85rem;">
            <option value="présent">Présent ✅</option>
            <option value="inscrit" selected>En attente ⏳</option>
            <option value="absent">Absent ❌</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" onclick="closeModal('participantsModal')">Fermer</button>
      </div>
    </div>
  </div>

  <script src="../js/app.js"></script>
  <script src="../js/components.js"></script>
  <script>
    function openModal(id) { document.getElementById(id).classList.add('active'); }
    function closeModal(id) { document.getElementById(id).classList.remove('active'); }
  </script>
</body>
</html>`;

const adminHtml = \`<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Événements - Admin | CareMeal</title>
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
          <a href="dashboard.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-chart-column"></i></span> Vue globale</a>
          <a href="events.html" class="sidebar-link active"><span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> Événements</a>
        </div>
      </nav>
    </aside>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <div class="page-title"><h2>Gestion des Événements</h2></div>
        </div>
      </header>

      <div class="page-content">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Événements en attente</h3>
          </div>
          <div class="table-container">
            <table class="table w-full">
              <thead>
                <tr>
                  <th>Titre</th>
                  <th>Organisateur</th>
                  <th>Statut</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr id="row-1">
                  <td>Atelier Cuisine 🍳</td>
                  <td>La Baguette Dorée</td>
                  <td><span class="badge badge-warning">En attente</span></td>
                  <td>
                    <button class="btn btn-success btn-sm" onclick="document.querySelector('#row-1 .badge').className='badge badge-success'; document.querySelector('#row-1 .badge').innerText='Validé';"><i class="fa-solid fa-check"></i> Valider</button>
                    <button class="btn btn-danger btn-sm" onclick="openModal('participantsModal')"><i class="fa-solid fa-users"></i></button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>

  <div class="modal-overlay" id="participantsModal">
    <div class="modal" style="width:100%; max-width:600px;">
      <div class="modal-header">
        <h3>Inscrits</h3>
        <button class="modal-close" onclick="closeModal('participantsModal')"><i class="fa-solid fa-times"></i></button>
      </div>
      <div class="modal-body">
        <div class="card" style="padding: 12px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;">
          <div style="display: flex; align-items: center; gap: 12px;">
            <div class="avatar avatar-sm">A</div>
            <div>
              <div style="font-weight:600; color:white;">Aziz (Néoclix)</div>
            </div>
          </div>
          <select class="form-control" style="width: auto; padding: 5px; font-size:0.85rem;">
            <option value="présent">Présent ✅</option>
            <option value="inscrit" selected>En attente ⏳</option>
            <option value="absent">Absent ❌</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" onclick="closeModal('participantsModal')">Fermer</button>
      </div>
    </div>
  </div>

  <script src="../js/app.js"></script>
  <script src="../js/components.js"></script>
  <script>
    function openModal(id) { document.getElementById(id).classList.add('active'); }
    function closeModal(id) { document.getElementById(id).classList.remove('active'); }
  </script>
</body>
</html>\`;

fs.writeFileSync(path.join(__dirname, 'student', 'events.html'), studentHtml);
fs.writeFileSync(path.join(__dirname, 'partner', 'events.html'), partnerHtml);
fs.writeFileSync(path.join(__dirname, 'admin', 'events.html'), adminHtml);

console.log("Done");
