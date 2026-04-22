const fs = require('fs');
const path = require('path');

const studentHtml = fs.readFileSync(path.join(__dirname, 'student', 'dashboard.html'), 'utf-8').replace(
    'href="dashboard.html" class="sidebar-link active"', 'href="dashboard.html" class="sidebar-link"'
).replace(
    'href="events.html" class="sidebar-link"', 'href="events.html" class="sidebar-link active"'
).replace(/<main class="main-content">[\s\S]*<\/main>/, `<main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle"><i class="fa-solid fa-bars"></i></button>
          <div class="page-title"><h2>Événements disponibles</h2><p>Participez aux actions anti-gaspi</p></div>
        </div>
        <div class="header-right">
          <div class="avatar avatar-sm" id="header-avatar" style="background:#3B82F6;">E</div>
        </div>
      </header>

      <div class="page-content">
        <div class="grid grid-3 gap-6 animate-fade-in-up">
          <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
              <div>
                <h3 class="card-title" style="margin-bottom:4px; font-size:1.1rem;">Atelier Cuisine 🍳</h3>
                <span class="badge badge-info">Présentiel</span>
              </div>
            </div>
            <p style="color:var(--color-text); margin-bottom:16px; font-size:0.9rem;">Apprenez à cuisiner vos restes avec le chef du Campus.</p>
            <div style="color:var(--color-text-muted); font-size:0.85rem; margin-bottom:16px;">
              <span style="display:inline-block; margin-right:12px;"><i class="fa-solid fa-calendar" style="color:var(--color-primary);"></i> 14 Mai 2026</span>
              <span><i class="fa-solid fa-location-dot" style="color:var(--color-primary);"></i> RU El Manar</span>
            </div>
            <div style="border-top:1px solid var(--color-border); padding-top:16px; display:flex; justify-content:space-between; align-items:center;">
              <span style="font-size:0.85rem; color:var(--color-text-muted);">Places : <strong>12/20</strong></span>
              <button class="btn btn-primary btn-sm" onclick="this.innerHTML='<i class=\\'fa-solid fa-check\\'></i> Inscrit'; this.classList.remove('btn-primary'); this.classList.add('btn-success'); this.disabled=true;">S'inscrire</button>
            </div>
          </div>
        </div>
      </div>
    </main>`);

const adminHtml = fs.readFileSync(path.join(__dirname, 'admin', 'dashboard.html'), 'utf-8').replace(
    'href="dashboard.html" class="sidebar-link active"', 'href="dashboard.html" class="sidebar-link"'
).replace(
    'href="events.html" class="sidebar-link"', 'href="events.html" class="sidebar-link active"'
).replace(/<main class="main-content">[\s\S]*<\/main>/, `<main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle"><i class="fa-solid fa-bars"></i></button>
          <div class="page-title"><h2>Gestion des Événements</h2><p>Validation et participants</p></div>
        </div>
      </header>

      <div class="page-content">
        <div class="card animate-fade-in-up">
          <div class="card-header">
            <h3 class="card-title">Événements en attente</h3>
          </div>
          <div class="table-container" style="overflow-x:auto; width:100%;">
            <table style="width:100%; border-collapse:collapse; text-align:left;">
              <thead style="background:var(--color-surface); border-bottom:1px solid var(--color-border);">
                <tr>
                  <th style="padding:12px;">Titre</th>
                  <th style="padding:12px;">Organisateur</th>
                  <th style="padding:12px;">Statut</th>
                  <th style="padding:12px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr style="border-bottom:1px solid var(--color-border);" id="row-1">
                  <td style="padding:12px; font-weight:600; color:white;">Atelier Cuisine 🍳</td>
                  <td style="padding:12px; color:var(--color-text);">La Baguette Dorée</td>
                  <td style="padding:12px;"><span class="badge badge-warning">En attente</span></td>
                  <td style="padding:12px;">
                    <button class="btn btn-success btn-sm" onclick="document.querySelector('#row-1 .badge').className='badge badge-success'; document.querySelector('#row-1 .badge').innerText='Validé';"><i class="fa-solid fa-check"></i> Valider</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>`);

const partnerHtml = fs.readFileSync(path.join(__dirname, 'partner', 'dashboard.html'), 'utf-8').replace(
    'href="dashboard.html" class="sidebar-link active"', 'href="dashboard.html" class="sidebar-link"'
).replace(
    'href="events.html" class="sidebar-link"', 'href="events.html" class="sidebar-link active"'
).replace(/<main class="main-content">[\s\S]*<\/main>/, `<main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle"><i class="fa-solid fa-bars"></i></button>
          <div class="page-title"><h2>Mes Événements</h2><p>Gérez vos ateliers</p></div>
        </div>
        <div class="header-right">
          <button class="btn btn-primary" onclick="openModal('createEventModal')"><i class="fa-solid fa-plus"></i> Créer et animer</button>
        </div>
      </header>

      <div class="page-content">
        <div class="grid grid-3 gap-6 animate-fade-in-up">
          <!-- Event Card 1 -->
          <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
              <div>
                <h3 class="card-title" style="margin-bottom:4px; font-size:1.1rem;">Distribution 🍱</h3>
                <span class="badge badge-info">Présentiel</span>
              </div>
              <span class="badge badge-success"><i class="fa-solid fa-check"></i> Validé</span>
            </div>
            <p style="color:var(--color-text); margin-bottom:16px; font-size:0.9rem;">Distribution de paniers invendus.</p>
            <div style="border-top:1px solid var(--color-border); padding-top:16px; display:flex; justify-content:space-between; align-items:center;">
              <span style="font-size:0.85rem; color:var(--color-text-muted);"><strong>24/50</strong> inscrits</span>
              <button class="btn btn-secondary btn-sm" onclick="openModal('participantsModal')"><i class="fa-solid fa-users"></i> Voir inscrits</button>
            </div>
          </div>
        </div>
      </div>
    </main>

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
        <button class="btn btn-primary" onclick="closeModal('createEventModal'); alert('Événement créé !')">Soumettre</button>
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
            <div class="avatar avatar-sm" style="background:var(--color-primary); color:white;">A</div>
            <div>
              <div style="font-weight:600; color:white;">Aziz (Néoclix)</div>
              <div style="font-size:0.8rem; color:var(--color-text-muted);">Inscrit le 08/04/2026</div>
            </div>
          </div>
          <select class="form-control" style="width: auto; padding: 5px; font-size:0.85rem;">
            <option value="présent">Présent ✅</option>
            <option value="inscrit" selected>En attente ⏳</option>
            <option value="absent">Absent ❌</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <script>
    function openModal(id) { document.getElementById(id).classList.add('active'); }
    function closeModal(id) { document.getElementById(id).classList.remove('active'); }
  </script>`);

fs.writeFileSync(path.join(__dirname, 'student', 'events.html'), studentHtml);
fs.writeFileSync(path.join(__dirname, 'admin', 'events.html'), adminHtml);
fs.writeFileSync(path.join(__dirname, 'partner', 'events.html'), partnerHtml);

console.log("Success.");
