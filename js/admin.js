/* ============================================
   CAREMEAL - ADMIN MODULE
   admin.js - Admin-specific logic
   ============================================ */

window.Admin = {
  init() {
    if (!App.requireAuth(['admin'])) return;
    this.loadDashboard();
  },

  // --- Dashboard ---
  async loadDashboard() {
    let users = [];
    

    try {
      const res = await fetch(App.apiUrl('Controller/UserController.php'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get_users' })
      });
      const data = await res.json();
      if(data.success && data.users) {
        users = data.users;
        App.saveUsers(users); // Sync cache
      } else {
        users = App.getUsers();
      }
    } catch(e) {
      console.error('API Error:', e);
      users = App.getUsers();
    }

    const orders = App.getOrders();
    const students = users.filter(u => u.role === 'student');
    const partners = users.filter(u => u.role === 'partner');

    // Stats
    this.setStat('stat-users', users.length - 1); // minus admin
    this.setStat('stat-students', students.length);
    this.setStat('stat-partners', partners.filter(p => p.status === 'active').length);
    this.setStat('stat-pending', partners.filter(p => p.status === 'pending').length);
    this.setStat('stat-orders', orders.length);
    this.setStat('stat-meals', students.reduce((s, u) => s + (u.mealsSaved || 0), 0) + partners.reduce((s, u) => s + (u.mealsSaved || 0), 0));
    this.setStat('stat-co2', (students.reduce((s, u) => s + (u.co2Saved || 0), 0) + partners.reduce((s, u) => s + (u.co2Saved || 0), 0)).toFixed(1) + ' kg');

    // Recent users
    const recentContainer = document.getElementById('recent-users');
    if (recentContainer) {
      const recent = users.filter(u => u.role !== 'admin').sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt)).slice(0, 5);
      recentContainer.innerHTML = recent.map(u => `
        <tr>
          <td>
            <div class="table-user">
              <div class="avatar avatar-sm">${App.getInitials(u.name)}</div>
              <div class="table-user-info">
                <span class="table-user-name">${u.name}</span>
                <span class="table-user-email">${u.email}</span>
              </div>
            </div>
          </td>
          <td><span class="badge badge-${u.role === 'student' ? 'info' : 'primary'}">${u.role === 'student' ? '<i class="fa-solid fa-graduation-cap"></i> Étudiant' : '<i class="fa-solid fa-store"></i> Partenaire'}</span></td>
          <td>${Admin.statusBadge(u.status)}</td>
          <td>${App.formatDate(u.createdAt)}</td>
        </tr>
      `).join('');
    }

    // Recent logs
    const logsContainer = document.getElementById('recent-logs');
    if (logsContainer) {
      const logs = App.getLogs().slice(0, 5);
      logsContainer.innerHTML = logs.map(log => `
        <div class="timeline-item">
          <div class="timeline-time">${App.timeAgo(log.timestamp)}</div>
          <div class="timeline-content">
            <strong>${log.userName}</strong> - ${log.action}
          </div>
        </div>
      `).join('');
    }
  },

  // --- Users List ---
  currentFilter: 'all',
  currentSearch: '',
  currentSortCol: 'date',
  currentSortDir: 'desc',
  productsFilter: 'all',

  sortUsers(col) {
    if (this.currentSortCol === col) {
      this.currentSortDir = this.currentSortDir === 'asc' ? 'desc' : 'asc';
    } else {
      this.currentSortCol = col;
      this.currentSortDir = 'asc';
    }
    this.loadUsers();
  },

  exportPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    // Titre du document
    doc.setFontSize(18);
    doc.text("Liste des Utilisateurs CareMeal", 14, 22);
    doc.setFontSize(11);
    doc.setTextColor(100);
    doc.text("Généré le : " + new Date().toLocaleDateString('fr-FR'), 14, 30);

    // Préparation des données pour le tableau PDF
    const tableData = [];
    // Récupérer les utilisateurs actuels affichés
    const users = this._lastRenderedUsers || []; 
    
    users.forEach(u => {
      tableData.push([
        u.name,
        u.email,
        u.role === 'student' ? 'Étudiant' : 'Partenaire',
        u.status === 'active' ? 'Actif' : (u.status === 'banned' ? 'Banni' : 'En attente'),
        App.formatDate(u.createdAt),
        (u.ordersCount || u.mealsSaved || 0).toString()
      ]);
    });

    // Génération du tableau
    doc.autoTable({
      startY: 35,
      head: [['Nom', 'Email', 'Rôle', 'Statut', 'Date d\'inscription', 'Activité']],
      body: tableData,
      theme: 'grid',
      headStyles: { fillColor: [239, 68, 68] }, // Couleur primaire CareMeal
      styles: { fontSize: 9 }
    });

    // Sauvegarde
    doc.save("utilisateurs_caremeal.pdf");
  },

  initUsers() {
    if (!App.requireAuth(['admin'])) return;
    this.loadUsers();

    // Search
    const searchInput = document.getElementById('user-search');
    if (searchInput) {
      searchInput.addEventListener('input', () => {
        this.currentSearch = searchInput.value.toLowerCase();
        this.loadUsers();
      });
    }
  },

  filterUsers(filter) {
    this.currentFilter = filter;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    document.querySelector(`.filter-btn[data-filter="${filter}"]`)?.classList.add('active');
    this.loadUsers();
  },

  // ================================================================
  // CRUD - READ : Charger la liste des utilisateurs depuis la BDD
  // ================================================================
  async loadUsers() {
    const container = document.getElementById('users-table-body');
    if (!container) return;

    // Optional spinner while loading:
    container.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;"><i class="fa-solid fa-spinner fa-spin"></i> Chargement...</td></tr>';

    

    let users = [];
    try {
      const res = await fetch(App.apiUrl('Controller/UserController.php'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get_users' })
      });
      const data = await res.json();
      
      if(data.success && data.users) {
        this._allUsersRaw = data.users; // keep full list for type stats
        users = data.users.filter(u => u.role !== 'admin');
        App.saveUsers(users); // Sync local storage for other scripts
      } else {
        this._allUsersRaw = null;
        users = App.getUsers().filter(u => u.role !== 'admin'); // Fallback local storage
      }
    } catch (e) {
      console.error('API Error:', e);
      users = App.getUsers().filter(u => u.role !== 'admin'); // Fallback local storage
    }

    // Filter
    if (this.currentFilter === 'students') users = users.filter(u => u.role === 'student');
    else if (this.currentFilter === 'partners') users = users.filter(u => u.role === 'partner');
    else if (this.currentFilter === 'active') users = users.filter(u => u.status === 'active');
    else if (this.currentFilter === 'banned') users = users.filter(u => u.status === 'banned');
    else if (this.currentFilter === 'pending') users = users.filter(u => u.status === 'pending');

    // Search
    if (this.currentSearch) {
      users = users.filter(u =>
        u.name.toLowerCase().includes(this.currentSearch) ||
        u.email.toLowerCase().includes(this.currentSearch)
      );
    }

    // Sort
    users.sort((a, b) => {
      let valA, valB;
      switch (this.currentSortCol) {
        case 'name': valA = a.name.toLowerCase(); valB = b.name.toLowerCase(); break;
        case 'role': valA = a.role; valB = b.role; break;
        case 'status': valA = a.status; valB = b.status; break;
        case 'date': valA = new Date(a.createdAt); valB = new Date(b.createdAt); break;
        case 'activity': valA = (a.ordersCount || a.mealsSaved || 0); valB = (b.ordersCount || b.mealsSaved || 0); break;
        default: valA = new Date(a.createdAt); valB = new Date(b.createdAt);
      }
      if (valA < valB) return this.currentSortDir === 'asc' ? -1 : 1;
      if (valA > valB) return this.currentSortDir === 'asc' ? 1 : -1;
      return 0;
    });

    // Save for PDF export
    this._lastRenderedUsers = users;

    document.getElementById('users-count').textContent = users.length + ' utilisateur' + (users.length > 1 ? 's' : '');

    if (users.length === 0) {
      container.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;color:var(--color-text-muted);">Aucun utilisateur trouvé</td></tr>';
      return;
    }

    container.innerHTML = users.map(u => `
      <tr>
        <td>
          <div class="table-user">
            <div class="avatar avatar-sm">${App.getInitials(u.name)}</div>
            <div class="table-user-info">
              <span class="table-user-name">${u.name}</span>
              <span class="table-user-email">${u.email}</span>
            </div>
          </div>
        </td>
        <td><span class="badge badge-${u.role === 'student' ? 'info' : 'primary'}">${u.role === 'student' ? '<i class="fa-solid fa-graduation-cap"></i> Étudiant' : '<i class="fa-solid fa-store"></i> Partenaire'}</span></td>
        <td>${Admin.statusBadge(u.status)}</td>
        <td>${App.formatDate(u.createdAt)}</td>
        <td>${u.ordersCount || u.mealsSaved || 0}</td>
        <td>
          <div class="table-actions">
            <!-- Ensure window is targeted correctly -->
            <button class="table-action-btn" title="Voir détail" onclick="window.location.assign('user-detail.php?id=${u.id}')"><i class="fa-solid fa-eye"></i></button>
            ${u.status === 'active' ? `<button class="table-action-btn danger" title="Bannir" onclick="Admin.toggleBan('${u.id}', true)"><i class="fa-solid fa-ban"></i></button>` : ''}
            ${u.status === 'banned' ? `<button class="table-action-btn" title="Réactiver" onclick="Admin.toggleBan('${u.id}', false)"><i class="fa-solid fa-check"></i></button>` : ''}
            ${u.status === 'pending' ? `<button class="table-action-btn" title="Valider" onclick="Admin.validatePartner('${u.id}', true)"><i class="fa-solid fa-check"></i></button>` : ''}
            <button class="table-action-btn danger" title="Supprimer" onclick="Admin.confirmDelete('${u.id}')"><i class="fa-solid fa-trash"></i></button>
          </div>
        </td>
      </tr>
    `).join('');

    // Update type stats below the table
    this.updateUserTypeStats();
  },

  updateUserTypeStats() {
    const all = this._allUsersRaw || App.getUsers();
    const total = all.length;

    // Totals
    const nStudents = all.filter(u => u.role === 'student').length;
    const nPartners = all.filter(u => u.role === 'partner').length;
    const nAdmins   = all.filter(u => u.role === 'admin').length;
    const nActive   = all.filter(u => u.status === 'active').length;
    const nPending  = all.filter(u => u.status === 'pending').length;
    const nBanned   = all.filter(u => u.status === 'banned').length;

    // Helper: set text
    const setText = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };

    // Total card
    setText('ustat-total', total);

    // Legend values
    setText('ustat-student-val', nStudents);
    setText('ustat-partner-val', nPartners);
    setText('ustat-admin-val',   nAdmins);
    setText('ustat-active-val',  nActive);
    setText('ustat-pending-val', nPending);
    setText('ustat-banned-val',  nBanned);

    // Donut centers
    setText('donut-role-center',   total);
    setText('donut-status-center', total);

    if (!total) return;

    // SVG donut helper - each segment offset by sum of previous
    // circumference of r=15.9 â‰ˆ 99.9 â‰ˆ 100 (we use 100 as base)
    const setDonut = (segments) => {
      // segments: [{id, count}], total already known
      let offset = 0;
      segments.forEach(({ id, count }) => {
        const pct = (count / total) * 100;
        const el = document.getElementById(id);
        if (!el) return;
        el.style.strokeDasharray  = `${pct} ${100 - pct}`;
        el.style.strokeDashoffset = -offset;
        offset += pct;
      });
    };

    setDonut([
      { id: 'donut-role-student', count: nStudents },
      { id: 'donut-role-partner', count: nPartners },
      { id: 'donut-role-admin',   count: nAdmins   },
    ]);

    setDonut([
      { id: 'donut-status-active',  count: nActive  },
      { id: 'donut-status-pending', count: nPending },
      { id: 'donut-status-banned',  count: nBanned  },
    ]);
  },

    // ================================================================
    // CRUD - UPDATE : Bannir ou réactiver un utilisateur
    // ================================================================
    toggleBan(userId, ban) {
    const action = ban ? "bannir" : "réactiver";
    const user = App.getUserById(userId) || App.getUsers().find(u => u.id == userId);
    if(!user) return;
    Components.confirm(ban ? "Bannir l utilisateur" : "Réactiver l utilisateur", "Voulez-vous " + action + " <strong>" + user.name + "</strong> ?", async () => {
      
      try {
        await fetch(App.apiUrl('Controller/UserController.php'), { method: "POST", headers: {"Content-Type":"application/json"}, body: window.JSON.stringify({action: "update_user_status", user_id: userId, status: ban ? "banned" : "active"}) });
        App.updateUser(userId, { status: ban ? "banned" : "active" });
        Components.showToast("Succ�s", user.name + " a été " + (ban ? "banni" : "réactivé"), ban ? "warning" : "success");
        if(typeof Admin !=="undefined" && Admin.loadUsers) Admin.loadUsers(); else this.loadUsers();
      } catch(e) { console.error(e); }
    });
  },

    // ================================================================
    // CRUD - DELETE : Supprimer un utilisateur (par l'admin)
    // ================================================================
    confirmDelete(userId) {
    const user = App.getUserById(userId) || App.getUsers().find(u => u.id == userId);
    if(!user) return;
    Components.confirm("Supprimer l utilisateur", "Cette action est irréversible. Supprimer <strong>" + user.name + "</strong> ?", async () => {
      
      try {
        await fetch(App.apiUrl('Controller/UserController.php'), { method: "POST", headers: {"Content-Type":"application/json"}, body: window.JSON.stringify({action: "delete_user", user_id: userId}) });
        App.deleteUser(userId);
        Components.showToast("Supprimé", user.name + " a été supprimé.", "error");
        if(typeof Admin !=="undefined" && Admin.loadUsers) Admin.loadUsers(); else this.loadUsers();
      } catch(e) { console.error(e); }
    });
  },

  // --- User Detail ---
  async initUserDetail() {
    if (!App.requireAuth(['admin'])) return;
    const params = new URLSearchParams(window.location.search);
    const userId = params.get('id');
    if (!userId) { window.location.href = "users.php"; return; }

    let user = App.getUserById(userId) || App.getUsers().find(u => u.id == userId);

    // If not in cache, let's fetch it from API
    if (!user) {
        try {
            

            const res = await fetch(App.apiUrl('Controller/UserController.php'), {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ action: 'get_users' })
            });
            const data = await res.json();
            if(data.success && data.users) {
              App.saveUsers(data.users); // Update cache
              user = App.getUserById(userId) || App.getUsers().find(u => u.id == userId);
            }
        } catch(e) {
            console.error('API Error:', e);
        }
    }

    if (!user) { window.location.href = "users.php"; return; }

    document.getElementById('detail-avatar').textContent = App.getInitials(user.name);
    document.getElementById('detail-name').textContent = user.name;
    document.getElementById('detail-email').textContent = user.email;
    document.getElementById('detail-role').innerHTML = `<span class="badge badge-${user.role === 'student' ? 'info' : 'primary'}">${user.role === 'student' ? '<i class="fa-solid fa-graduation-cap"></i> Étudiant' : '<i class="fa-solid fa-store"></i> Partenaire'}</span>`;
    document.getElementById('detail-status').innerHTML = this.statusBadge(user.status);
    document.getElementById('detail-joined').textContent = App.formatDate(user.createdAt);

    // Extra info based on role
    const extraContainer = document.getElementById('detail-extra');
    if (user.role === 'student') {
      extraContainer.innerHTML = `
        <div class="impact-grid">
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-school"></i></div><div class="impact-value">${user.university || '-'}</div><div class="impact-label">Université</div></div>
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-location-dot"></i></div><div class="impact-value">${user.quartier || '-'}</div><div class="impact-label">Quartier</div></div>
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-star"></i></div><div class="impact-value">${user.points || 0}</div><div class="impact-label">Points</div></div>
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-utensils"></i></div><div class="impact-value">${user.mealsSaved || 0}</div><div class="impact-label">Repas sauvés</div></div>
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-leaf"></i></div><div class="impact-value">${(user.co2Saved || 0).toFixed(1)} kg</div><div class="impact-label">CO₂ évité</div></div>
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-box"></i></div><div class="impact-value">${user.ordersCount || 0}</div><div class="impact-label">Commandes</div></div>
        </div>
      `;
    } else if (user.role === 'partner') {
      extraContainer.innerHTML = `
        <div class="impact-grid">
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-briefcase"></i></div><div class="impact-value">${user.type || '-'}</div><div class="impact-label">Secteur</div></div>
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-globe"></i></div><div class="impact-value" style="font-size:1rem;word-break:break-all;">${user.address || '-'}</div><div class="impact-label">Site Web</div></div>
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-mobile-screen"></i></div><div class="impact-value" style="font-size:1rem;">${user.phone || '-'}</div><div class="impact-label">Téléphone</div></div>
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-utensils"></i></div><div class="impact-value">${user.mealsSaved || 0}</div><div class="impact-label">Repas sauvés</div></div>
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-star"></i></div><div class="impact-value">${user.avgRating || 0}</div><div class="impact-label">Note moyenne</div></div>
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-message"></i></div><div class="impact-value">${user.reviewCount || 0}</div><div class="impact-label">Avis</div></div>
        </div>
      `;
    }

    // Actions
    const actionsContainer = document.getElementById('detail-actions');
    let actionsHtml = '';
    if (user.status === 'active') {
      actionsHtml += `<button class="btn btn-danger btn-sm btn-full" onclick="Admin.toggleBan('${user.id}', true)"><i class="fa-solid fa-ban"></i> Bannir</button>`;
    } else if (user.status === 'banned') {
      actionsHtml += `<button class="btn btn-success btn-sm btn-full" onclick="Admin.toggleBan('${user.id}', false)"><i class="fa-solid fa-check"></i> Réactiver</button>`;
    } else if (user.status === 'pending') {
      actionsHtml += `<button class="btn btn-success btn-sm btn-full" onclick="Admin.validatePartner('${user.id}', true)"><i class="fa-solid fa-check"></i> Valider</button>`;
      actionsHtml += `<button class="btn btn-danger btn-sm btn-full" onclick="Admin.validatePartner('${user.id}', false)"><i class="fa-solid fa-xmark"></i> Refuser</button>`;
    }
    actionsHtml += `<button class="btn btn-secondary btn-sm btn-full" onclick="Admin.confirmDelete('${user.id}')"><i class="fa-solid fa-trash"></i> Supprimer</button>`;
    actionsContainer.innerHTML = actionsHtml;

    // User orders/history
    const historyContainer = document.getElementById('detail-history');
    if (historyContainer) {
      const orders = App.getOrders().filter(o => o.userId === user.id || o.partnerId === user.id);
      if (orders.length === 0) {
        historyContainer.innerHTML = '<p style="color:var(--color-text-muted);text-align:center;padding:24px;">Aucun historique</p>';
      } else {
        historyContainer.innerHTML = orders.map(o => `
          <div class="timeline-item">
            <div class="timeline-time">${App.formatDate(o.date)}</div>
            <div class="timeline-content"><strong>${o.items}</strong> - ${o.partnerName} - ${o.price.toFixed(1)} DT</div>
          </div>
        `).join('');
      }
    }
  },

  // --- Partners ---
  initPartners() {
    if (!App.requireAuth(['admin'])) return;
    this.loadPartners();
  },

  loadPartners() {
    const partners = App.getUsers().filter(u => u.role === 'partner');
    const container = document.getElementById('partners-table-body');
    if (!container) return;

    container.innerHTML = partners.map(p => `
      <tr>
        <td>
          <div class="table-user">
            <div class="avatar avatar-sm">${App.getInitials(p.name)}</div>
            <div class="table-user-info">
              <span class="table-user-name">${p.name}</span>
              <span class="table-user-email">${p.email}</span>
            </div>
          </div>
        </td>
        <td>${p.type || '-'}</td>
        <td>${Admin.statusBadge(p.status)}</td>
        <td>${p.mealsSaved || 0}</td>
        <td>${p.avgRating ? '<i class="fa-solid fa-star"></i> ' + p.avgRating : '-'}</td>
        <td>
          <div class="table-actions">
            <!-- Ensure window is targeted correctly -->
            <button class="table-action-btn" title="Voir détail" onclick="window.location.assign('user-detail.php?id=${p.id}')"><i class="fa-solid fa-eye"></i></button>
            ${p.status === 'pending' ? `
              <button class="table-action-btn" title="Valider" onclick="Admin.validatePartner('${p.id}', true)" style="color:var(--color-success);"><i class="fa-solid fa-check"></i></button>
              <button class="table-action-btn danger" title="Refuser" onclick="Admin.validatePartner('${p.id}', false)"><i class="fa-solid fa-xmark"></i></button>
            ` : ''}
            ${p.status === 'active' ? `<button class="table-action-btn danger" title="Bannir" onclick="Admin.toggleBan('${p.id}', true)"><i class="fa-solid fa-ban"></i></button>` : ''}
          </div>
        </td>
      </tr>
    `).join('');
  },

    validatePartner(partnerId, approve) {
      const partner = App.getUserById(partnerId) || App.getUsers().find(u => u.id == partnerId);
      if(!partner) return;
      const action = approve ? "valider" : "refuser";
      Components.confirm(approve ? "Valider le partenaire" : "Refuser le partenaire", "Voulez-vous " + action + " <strong>" + partner.name + "</strong> ?", async () => {
        
        try {
          await fetch(App.apiUrl('Controller/UserController.php'), { method: "POST", headers: {"Content-Type":"application/json"}, body: window.JSON.stringify({ action: "update_user_status", user_id: partnerId, status: approve ? "active" : "banned" }) });
          App.updateUser(partnerId, { status: approve ? "active" : "banned" });
          Components.showToast("Succ�s", partner.name + " a été " + (approve ? "validé" : "refusé"), approve ? "success" : "warning");
          if (typeof this.loadPartners === "function") this.loadPartners();
          if (typeof this.loadUsers === "function") this.loadUsers();
        } catch(e) {}
      });
  },

  // --- Logs ---
  initLogs() {
    if (!App.requireAuth(['admin'])) return;
    this.loadLogs();
  },

  loadLogs() {
    const logs = App.getLogs();
    const container = document.getElementById('logs-timeline');
    if (!container) return;

    if (logs.length === 0) {
      container.innerHTML = '<p style="color:var(--color-text-muted);text-align:center;padding:40px;">Aucune activité enregistrée</p>';
      return;
    }

    container.innerHTML = logs.map(log => {
      const roleColors = { admin: 'var(--color-danger)', partner: 'var(--color-primary)', student: 'var(--color-info)', system: 'var(--color-text-muted)' };
      return `
        <div class="timeline-item">
          <div class="timeline-time">${App.formatDateTime(log.timestamp)}</div>
          <div class="timeline-content">
            <strong style="color:${roleColors[log.userRole] || 'var(--color-white)'}">${log.userName}</strong>
            <span class="badge badge-${log.userRole === 'admin' ? 'danger' : log.userRole === 'partner' ? 'primary' : log.userRole === 'student' ? 'info' : 'warning'}" style="margin-left:8px;font-size:0.65rem;">${log.userRole}</span>
            <br>${log.action}
          </div>
        </div>
      `;
    }).join('');
  },

  // --- Helpers ---
  statusBadge(status) {
    const map = {
      active: '<span class="badge badge-active badge-dot">Actif</span>',
      pending: '<span class="badge badge-pending badge-dot">En attente</span>',
      banned: '<span class="badge badge-banned badge-dot">Banni</span>'
    };
    return map[status] || status;
  },

  setStat(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
  },
  _categories: [],
  _products: [],
  apiBase: (typeof window.caremealPath === 'function') ? window.caremealPath('api') : '/api',
  async fetchCategories() {
    try {
      const resp = await fetch(this.apiBase + '/categories.php');
      if (!resp.ok) return this._categories || [];
      let categories = await resp.json();
      categories = categories.map(c => ({ id: c.id_categorie || c.id, name: c.nom || c.name, description: c.description || '', active: Number(c.actif || c.active || 0) ? true : false }));
      this._categories = categories;
      return this._categories;
    } catch (e) {
      return this._categories || [];
    }
  },
  async fetchProducts() {
    try {
      const resp = await fetch(this.apiBase + '/products.php');
      if (!resp.ok) return this._products || [];
      let products = await resp.json();
      products = products.map(p => ({
        id: p.id_produit || p.id,
        name: p.nom || p.name,
        description: p.description || '',
        categoryId: p.id_categorie || p.categoryId,
        categoryName: p.category_name || p.categoryName,
        originalPrice: parseFloat(p.prix_normal || p.originalPrice || 0),
        price: parseFloat(p.prix_commande || p.price || 0),
        stock: Number(p.stock || 0),
        active: Number(p.actif || p.active || 0) ? true : false,
        createdAt: p.created_at || p.createdAt
      }));
      this._products = products;
      return this._products;
    } catch (e) {
      return this._products || [];
    }
  },
  //PARTIE COMMANDES HADIL
  // --- Categories ---

  initCategories() {
    if (!App.requireAuth(['admin'])) return;
    this.loadCategories();
  },

  loadCategories() {
    const container = document.getElementById('categories-table-body');
    if (!container) return;

    (async () => {
      let categories = await this.fetchCategories();
      const products = (this._products && this._products.length) ? this._products : await this.fetchProducts();

      const active = categories.filter(c => c.active).length;
      const totalEl = document.getElementById('categories-total');
      const activeEl = document.getElementById('categories-active');
      if (totalEl) totalEl.textContent = categories.length;
      if (activeEl) activeEl.textContent = active;

      if (categories.length === 0) {
        container.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:32px;color:var(--color-text-muted);">Aucune catégorie disponible</td></tr>';
        return;
      }

      container.innerHTML = categories.map(c => `
      <tr>
        <td><strong>${c.name}</strong></td>
        <td>${c.description || '-'}</td>
        <td>${(products || []).filter(p => String(p.categoryId) === String(c.id)).length}</td>
        <td>${c.active ? '<span class="badge badge-success badge-dot">Active</span>' : '<span class="badge badge-danger badge-dot">Inactive</span>'}</td>
        <td>
          <div class="table-actions">
            <button class="table-action-btn" title="Modifier" onclick="Admin.openCategoryModal('${c.id}')"><i class="fa-solid fa-pen"></i></button>
            <button class="table-action-btn ${c.active ? 'danger' : ''}" title="Activer/Désactiver" onclick="Admin.toggleCategory('${c.id}')"><i class="fa-solid ${c.active ? 'fa-ban' : 'fa-check'}"></i></button>
            <button class="table-action-btn danger" title="Supprimer" onclick="Admin.confirmDeleteCategory('${c.id}')"><i class="fa-solid fa-trash"></i></button>
          </div>
        </td>
      </tr>
    `).join('');
    })();
  },

  openCategoryModal(categoryId = '') {
    const idInput = document.getElementById('category-id');
    const nameInput = document.getElementById('category-name');
    const descInput = document.getElementById('category-description');
    const activeInput = document.getElementById('category-active');
    const title = document.getElementById('category-modal-title');

    if (!idInput || !nameInput || !descInput || !activeInput || !title) return;

    if (!categoryId) {
      idInput.value = '';
      nameInput.value = '';
      descInput.value = '';
      activeInput.checked = true;
      title.textContent = 'Nouvelle catégorie';
      Components.openModal('category-modal');
      return;
    }

    const category = (this._categories || []).find(c => String(c.id) === String(categoryId));
    if (!category) return;

    idInput.value = category.id;
    nameInput.value = category.name;
    descInput.value = category.description || '';
    activeInput.checked = !!category.active;
    title.textContent = 'Modifier catégorie';
    Components.openModal('category-modal');
  },

  saveCategory(event) {
    event.preventDefault();
    // Basic validation
    const nameEl = document.getElementById('category-name');
    const descEl = document.getElementById('category-description');
    const showNameError = (message) => {
      if (!nameEl) return;
      let errorEl = document.getElementById('category-name-error');
      if (!errorEl) {
        errorEl = document.createElement('div');
        errorEl.id = 'category-name-error';
        errorEl.style.cssText = 'color:#ef4444;font-size:0.78rem;margin-top:6px;';
        nameEl.insertAdjacentElement('afterend', errorEl);
      }
      errorEl.textContent = message;
      if (!nameEl.dataset.inlineValidationBound) {
        nameEl.addEventListener('input', () => {
          const el = document.getElementById('category-name-error');
          if (el) el.textContent = '';
        });
        nameEl.dataset.inlineValidationBound = '1';
      }
    };
    const clearNameError = () => {
      const errorEl = document.getElementById('category-name-error');
      if (errorEl) errorEl.textContent = '';
    };
    const categoryName = String((nameEl || {}).value || '').trim();
    if (!nameEl || categoryName === '') {
      showNameError('Le nom de la catégorie ne peut pas être vide.');
      return;
    }
    if (/^\d+$/.test(categoryName)) {
      showNameError('Le nom de la catégorie ne peut pas être uniquement numérique.');
      return;
    }
    clearNameError();
    if (descEl && String(descEl.value || '').length > 255) {
      alert('Description trop longue (255 caractères max).');
      return;
    }

    const id = document.getElementById('category-id').value;
    const name = document.getElementById('category-name').value.trim();
    const description = document.getElementById('category-description').value.trim();
    const active = document.getElementById('category-active').checked;
    (async () => {
      try {
        const method = id ? 'PUT' : 'POST';
        const url = id ? (this.apiBase + `/categories.php?id=${encodeURIComponent(id)}`) : (this.apiBase + '/categories.php');
        const body = { name, description, active };
        const resp = await fetch(url, { method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
        if (resp.ok) {
          const data = await resp.json();
          Components.showToast('Succ�s', id ? 'Catégorie mise � jour.' : 'Catégorie créée.', 'success');
          // update local cache
          if (id) {
            this._categories = (this._categories || []).map(c => String(c.id) === String(id) ? { ...c, name, description, active } : c);
          } else {
            const dbId = String(data.id_categorie || data.id || '');
            this._categories = this._categories || [];
            this._categories.push({ id: dbId, name, description: description || '', active, createdAt: new Date().toISOString() });
          }
        } else {
          try { const err = await resp.json(); if (err.errors) Components.showToast('Erreur', err.errors.join('; '), 'error'); else Components.showToast('Erreur', err.error || 'Erreur serveur', 'error'); } catch(e) { Components.showToast('Erreur', 'Impossible de contacter l\'API', 'error'); }
        }
      } catch (e) {
        Components.showToast('Erreur', 'Impossible de joindre l\'API catégories.', 'error');
      } finally {
        Components.closeModal('category-modal');
        this.loadCategories();
      }
    })();
  },

  toggleCategory(categoryId) {
    const category = (this._categories || []).find(c => String(c.id) === String(categoryId));
    if (!category) return;
    const newActive = !category.active;
    (async () => {
      try {
        const resp = await fetch(this.apiBase + `/categories.php?id=${encodeURIComponent(categoryId)}` , { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ active: newActive }) });
        if (resp.ok) {
          this._categories = (this._categories || []).map(c => String(c.id) === String(categoryId) ? { ...c, active: newActive } : c);
          App.addLog(`Catégorie ${newActive ? 'activée' : 'désactivée'}: ${category.name}`);
          Components.showToast('Succ�s', `Catégorie ${newActive ? 'activée' : 'désactivée'}.`, 'success');
        } else {
          Components.showToast('Erreur', 'Impossible de mettre � jour la catégorie.', 'error');
        }
      } catch (e) {
        this._categories = (this._categories || []).map(c => String(c.id) === String(categoryId) ? { ...c, active: newActive } : c);
        App.addLog(`Catégorie ${newActive ? 'activée' : 'désactivée'} (local): ${category.name}`);
        Components.showToast('Succ�s (local)', `Catégorie ${newActive ? 'activée' : 'désactivée'} localement.`, 'success');
      } finally {
        this.loadCategories();
      }
    })();
  },

  async confirmDeleteCategory(categoryId) {
    const category = (this._categories || []).find(c => String(c.id) === String(categoryId));
    if (!category) return;
    const products = (this._products && this._products.length) ? this._products : await this.fetchProducts();
    const productCount = (products || []).filter(p => String(p.categoryId) === String(categoryId)).length;
    const msg = productCount > 0 ? `Cette catégorie contient ${productCount} produit(s). Supprimer ? (Les produits seront aussi supprimés.)` : `Supprimer définitivement la catégorie <strong>${category.name}</strong> ?`;
    Components.confirm('Supprimer la catégorie', msg, () => this.deleteCategory(categoryId));
  },

  async deleteCategory(categoryId) {
    try {
      const resp = await fetch(this.apiBase + `/categories.php?id=${encodeURIComponent(categoryId)}` , { method: 'DELETE' });
      if (resp.ok) {
        this._categories = (this._categories || []).filter(c => String(c.id) !== String(categoryId));
        Components.showToast('Supprimée', 'Catégorie supprimée.', 'success');
        App.addLog('Catégorie supprimée');
        this.loadCategories();
        return;
      }
    } catch (e) {
      // fallback to local
    }
    this._categories = (this._categories || []).filter(c => String(c.id) !== String(categoryId));
    Components.showToast('Supprimée (local)', 'Catégorie supprimée localement.', 'success');
    this.loadCategories();
  },

  // --- Products ---
  async initProducts() {
    if (!App.requireAuth(['admin'])) return;
    this.productsFilter = 'all';
    await this.initProductFilters();
    this.loadProducts();
  },

  async initProductFilters() {
    const select = document.getElementById('products-filter-category');
    if (!select) return;

    const categories = (this._categories && this._categories.length) ? this._categories : await this.fetchCategories();
    select.innerHTML = '<option value="all">Toutes les catégories</option>' +
      (categories || []).map(c => `<option value="${c.id}">${c.name}</option>`).join('');

    select.value = this.productsFilter || 'all';
  },

  loadProducts() {
    const container = document.getElementById('products-table-body');
    if (!container) return;

    // Load products from API, fallback to localStorage
    (async () => {
      let products = [];
      try {
        const resp = await fetch(this.apiBase + '/products.php');
        if (resp.ok) {
          products = await resp.json();
          products = products.map(p => ({
              id: String(p.id_produit || p.id),
              name: p.nom || p.name,
              description: p.description || '',
              categoryId: String(p.id_categorie || p.categoryId),
              categoryName: p.category_name || p.categoryName || '',
              originalPrice: parseFloat(p.prix_normal || p.originalPrice || 0),
              price: parseFloat(p.prix_commande || p.price || 0),
              stock: Number(p.stock || 0),
              active: Number(p.actif ?? p.active ?? 0) === 1,
              createdAt: p.created_at || p.createdAt
          }));
          this._products = products;
        } else {
          products = this._products || [];
        }
      } catch (e) {
        products = this._products || [];
      }

          if (this.productsFilter !== 'all') {
            products = products.filter(p => String(p.categoryId) === String(this.productsFilter));
          }

          // Apply admin-side search filter (by name, category or partner)
          if (this.currentSearch && this.currentSearch.length > 0) {
            const q = this.currentSearch.toLowerCase();
            products = products.filter(p => (
              (p.name && String(p.name).toLowerCase().includes(q)) ||
              (p.categoryName && String(p.categoryName).toLowerCase().includes(q)) ||
              (p.partnerName && String(p.partnerName).toLowerCase().includes(q))
            ));
          }

    const totalEl = document.getElementById('products-total');
    const activeEl = document.getElementById('products-active');
    const lowStockEl = document.getElementById('products-low-stock');
    if (totalEl) totalEl.textContent = products.length;
    if (activeEl) activeEl.textContent = products.filter(p => p.active).length;
    if (lowStockEl) lowStockEl.textContent = products.filter(p => p.stock <= 3).length;

      if (products.length === 0) {
      container.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:32px;color:var(--color-text-muted);">Aucun produit trouvé</td></tr>';
        return;
      }

      container.innerHTML = products.map(p => `
      <tr>
        <td><strong>${p.name}</strong></td>
        <td>${p.categoryName || '-'}</td>
        <td>${p.partnerName || 'CareMeal'}</td>
        <td>${p.originalPrice.toFixed(1)} DT</td>
        <td><strong style="color:var(--color-primary)">${p.price.toFixed(1)} DT</strong></td>
        <td>${p.stock}</td>
        <td>${p.active ? '<span class="badge badge-success badge-dot">Actif</span>' : '<span class="badge badge-danger badge-dot">Inactif</span>'}</td>
        <td>
          <div class="table-actions">
            <button class="table-action-btn" title="Modifier" onclick="Admin.openProductModal('${p.id}')"><i class="fa-solid fa-pen"></i></button>
            <button class="table-action-btn ${p.active ? 'danger' : ''}" title="Activer/Désactiver" onclick="Admin.toggleProduct('${p.id}')"><i class="fa-solid ${p.active ? 'fa-ban' : 'fa-check'}"></i></button>
            <button class="table-action-btn danger" title="Supprimer" onclick="Admin.confirmDeleteProduct('${p.id}')"><i class="fa-solid fa-trash"></i></button>
          </div>
        </td>
      </tr>
    `).join('');
    })();
  },

  setProductsFilter(categoryId) {
    this.productsFilter = categoryId;
    this.loadProducts();
  },

  setProductsSearch(query) {
    this.currentSearch = String(query || '').trim();
    this.loadProducts();
  },

  async openProductModal(productId = '') {
    const idInput = document.getElementById('product-id');
    const nameInput = document.getElementById('product-name');
    const descInput = document.getElementById('product-description');
    const categoryInput = document.getElementById('product-category');
    const partnerInput = document.getElementById('product-partner');
    const priceInput = document.getElementById('product-price');
    const originalInput = document.getElementById('product-original-price');
    const stockInput = document.getElementById('product-stock');
    const activeInput = document.getElementById('product-active');
    const title = document.getElementById('product-modal-title');

    if (!idInput || !nameInput || !descInput || !categoryInput || !partnerInput || !priceInput || !originalInput || !stockInput || !activeInput || !title) {
      return;
    }

    const categories = (this._categories && this._categories.length) ? this._categories : await this.fetchCategories();
    categoryInput.innerHTML = (categories || []).filter(c => c.active).map(c => `<option value="${c.id}">${c.name}</option>`).join('');

    const partners = App.getUsers().filter(u => u.role === 'partner');
    partnerInput.innerHTML = '<option value="">CareMeal</option>' +
      partners.map(p => `<option value="${p.id}">${p.name}</option>`).join('');

    if (!productId) {
      idInput.value = '';
      nameInput.value = '';
      descInput.value = '';
      priceInput.value = '';
      originalInput.value = '';
      stockInput.value = '1';
      activeInput.checked = true;
      title.textContent = 'Nouveau produit';
      Components.openModal('product-modal');
      return;
    }

    const product = (this._products || []).find(p => String(p.id) === String(productId));
    if (!product) return;

    idInput.value = product.id;
    nameInput.value = product.name;
    descInput.value = product.description || '';
    categoryInput.value = product.categoryId;
    partnerInput.value = product.partnerId || '';
    priceInput.value = product.price;
    originalInput.value = product.originalPrice;
    stockInput.value = product.stock;
    activeInput.checked = !!product.active;
    title.textContent = 'Modifier produit';
    Components.openModal('product-modal');
  },

  saveProduct(event) {
    event.preventDefault();
    // Basic validation
    const productNameEl = document.getElementById('product-name');
    const showNameError = (message) => {
      if (!productNameEl) return;
      let errorEl = document.getElementById('product-name-error');
      if (!errorEl) {
        errorEl = document.createElement('div');
        errorEl.id = 'product-name-error';
        errorEl.style.cssText = 'color:#ef4444;font-size:0.78rem;margin-top:6px;';
        productNameEl.insertAdjacentElement('afterend', errorEl);
      }
      errorEl.textContent = message;
      if (!productNameEl.dataset.inlineValidationBound) {
        productNameEl.addEventListener('input', () => {
          const el = document.getElementById('product-name-error');
          if (el) el.textContent = '';
        });
        productNameEl.dataset.inlineValidationBound = '1';
      }
    };
    const clearNameError = () => {
      const errorEl = document.getElementById('product-name-error');
      if (errorEl) errorEl.textContent = '';
    };
    const nameVal = String((productNameEl || {}).value || '').trim();
    if (!productNameEl || nameVal === '') {
      showNameError('Le nom du produit ne peut pas être vide.');
      return;
    }
    if (/^\d+$/.test(nameVal)) {
      showNameError('Le nom du produit ne peut pas être uniquement numérique.');
      return;
    }
    clearNameError();
    const catVal = String((document.getElementById('product-category') || {}).value || '');
    if (!catVal) { alert('Sélectionnez une catégorie.'); return; }
    const priceVal = Number((document.getElementById('product-price') || {}).value || 0);
    if (!Number.isFinite(priceVal) || priceVal <= 0) { alert('Prix commande invalide.'); return; }
    const stockVal = Number((document.getElementById('product-stock') || {}).value || 0);
    if (!Number.isFinite(stockVal) || stockVal < 0) { alert('Stock invalide.'); return; }

    const id = document.getElementById('product-id').value;
    const name = document.getElementById('product-name').value.trim();
    const description = document.getElementById('product-description').value.trim();
    const categoryId = document.getElementById('product-category').value;
    const partnerId = document.getElementById('product-partner').value;
    const price = Number(document.getElementById('product-price').value);
    const originalPrice = Number(document.getElementById('product-original-price').value);
    const stock = Number(document.getElementById('product-stock').value);
    const active = document.getElementById('product-active').checked;

    const category = (this._categories || []).find(c => String(c.id) === String(categoryId));
    if (!category) {
      Components.showToast('Erreur', 'Catégorie invalide.', 'error');
      return;
    }

    const partner = (this._partners || []).find(u => String(u.id) === String(partnerId)) || null;
    const payload = {
      name,
      description,
      categoryId,
      categoryName: category.name,
      partnerId: partner ? partner.id : null,
      partnerName: partner ? partner.name : 'CareMeal',
      price,
      originalPrice: Number.isFinite(originalPrice) && originalPrice > 0 ? originalPrice : price,
      stock,
      active
    };

    // Send to API (fallback to local persistence on error)
    (async () => {
      try {
        const method = id ? 'PUT' : 'POST';
        const url = id ? (this.apiBase + `/products.php?id=${encodeURIComponent(id)}`) : (this.apiBase + '/products.php');
        const body = {
          name,
          description,
          category_id: categoryId,
          price,
          original_price: originalPrice || price,
          stock,
          active
        };
        const resp = await fetch(url, {
          method,
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(body)
        });
        if (resp.ok) {
          const data = await resp.json();
          Components.showToast('Succ�s', id ? 'Produit mis � jour.' : 'Produit créé.', 'success');
          // Update internal cache with server-returned data (preserves real DB id)
          if (id) {
            this._products = (this._products || []).map(p => String(p.id) === String(id) ? { ...p, ...payload } : p);
          } else {
            const serverProduct = { ...payload, id: String(data.id_produit || data.id || '') };
            this._products = this._products || [];
            this._products.push(serverProduct);
          }
        } else {
          try { const err = await resp.json(); if (err.errors) Components.showToast('Erreur', err.errors.join('; '), 'error'); else Components.showToast('Erreur', 'Serveur: ' + (err.error || 'Erreur'), 'error'); } catch(e) { Components.showToast('Erreur', 'Impossible de contacter l\'API', 'error'); }
        }
      } catch (e) {
        Components.showToast('Erreur', 'Impossible de joindre l\'API produits.', 'error');
      } finally {
        Components.closeModal('product-modal');
        this.loadProducts();
      }
    })();
  },

  toggleProduct(productId) {
    const product = (this._products || []).find(p => String(p.id) === String(productId));
    if (!product) return;
    const newActive = !product.active;
    (async () => {
      try {
        const resp = await fetch(this.apiBase + `/products.php?id=${encodeURIComponent(productId)}` , {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ active: newActive })
        });
        if (resp.ok) {
          this._products = (this._products || []).map(p => String(p.id) === String(productId) ? { ...p, active: newActive } : p);
          App.addLog(`Produit ${newActive ? 'activé' : 'désactivé'}: ${product.name}`);
          Components.showToast('Succ�s', `Produit ${newActive ? 'activé' : 'désactivé'}.`, 'success');
          this.loadProducts();
          return;
        } else {
          Components.showToast('Erreur', 'Impossible de mettre � jour le produit.', 'error');
        }
      } catch (e) {
        // fallback to local
        this._products = (this._products || []).map(p => String(p.id) === String(productId) ? { ...p, active: newActive } : p);
        App.addLog(`Produit ${newActive ? 'activé' : 'désactivé'} (local): ${product.name}`);
        Components.showToast('Succ�s (local)', `Produit ${newActive ? 'activé' : 'désactivé'} localement.`, 'success');
      } finally {
        this.loadProducts();
      }
    })();
  },

  confirmDeleteProduct(productId) {
    const product = (this._products || []).find(p => String(p.id) === String(productId));
    if (!product) return;
    Components.confirm('Supprimer le produit', `Supprimer définitivement <strong>${product.name}</strong> ?`, () => this.deleteProduct(productId));
  },

  async deleteProduct(productId) {
    try {
      const resp = await fetch(this.apiBase + `/products.php?id=${encodeURIComponent(productId)}` , { method: 'DELETE' });
      if (resp.ok) {
        this._products = (this._products || []).filter(p => String(p.id) !== String(productId));
        Components.showToast('Supprimé', 'Produit supprimé.', 'success');
        App.addLog('Produit supprimé');
        this.loadProducts();
        return;
      }
    } catch (e) {
      // fallback to local
    }
    this._products = (this._products || []).filter(p => String(p.id) !== String(productId));
    Components.showToast('Supprimé (local)', 'Produit supprimé localement.', 'success');
    this.loadProducts();
  },

  // --- Orders Management ---
  async initOrders() {
    if (!App.requireAuth(['admin'])) return;
    const container = document.getElementById('orders-table-body');
    if (!container) return;
    container.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:40px;"><i class="fa-solid fa-spinner fa-spin"></i> Chargement...</td></tr>';
    try {
      const [commandesRes, commanderRes] = await Promise.all([
        fetch(App.apiUrl('api/commandes.php'), { method: 'GET' }),
        fetch(App.apiUrl('api/commander.php'), { method: 'GET' })
      ]);
      const commandesData = await commandesRes.json();
      const commanderData = await commanderRes.json();
      const productOrders = (Array.isArray(commandesData) ? commandesData : []).map(o => ({ ...o, _orderType: 'Produit' }));
      const offerOrders = (Array.isArray(commanderData) ? commanderData : []).map(o => ({ ...o, _orderType: 'Offre' }));
      const orders = [...productOrders, ...offerOrders].sort((a, b) => {
        const dateA = new Date(a.date_commande || a.created_at || a.date || 0).getTime() || 0;
        const dateB = new Date(b.date_commande || b.created_at || b.date || 0).getTime() || 0;
        return dateB - dateA;
      });
      App.saveOrders(orders);
      const cntEl = document.getElementById('orders-count'); if (cntEl) cntEl.textContent = orders.length + ' commandes';
      const totalEl = document.getElementById('orders-total'); if (totalEl) totalEl.textContent = orders.length;
      const pending = orders.filter(o => (o.statut || o.status || '').toString().toLowerCase().includes('attente') || (o.statut==='en_attente')).length;
      const pendingEl = document.getElementById('orders-pending'); if (pendingEl) pendingEl.textContent = pending;
      const validatedEl = document.getElementById('orders-validated'); if (validatedEl) validatedEl.textContent = orders.filter(o=> ['validee', 'confirmee'].includes(o.statut || o.status || '')).length;
      const pickedEl = document.getElementById('orders-picked'); if (pickedEl) pickedEl.textContent = orders.filter(o=> ['retiree', 'recuperee'].includes(o.statut || o.status || '')).length;
      const cancelledEl = document.getElementById('orders-cancelled'); if (cancelledEl) cancelledEl.textContent = orders.filter(o=> (o.statut||o.status||'').toString().toLowerCase().includes('annul')).length;

      container.innerHTML = orders.map(o => {
        const type = o._orderType || 'Produit';
        const isOffer = type === 'Offre';
        const statusLabel = o.statut || o.status || '';
        const statusBadge = `<span class="badge">${statusLabel}</span>`;
        const student = isOffer ? `${o.nom || ''} ${o.prenom || ''}`.trim() || '-' : (o.student_name || o.user_name || o.student || o.user || '-');
        const studentSuffix = isOffer ? '' : ` (${o.id_user || ''})`;
        const product = isOffer ? `Offre #${o.id_offre || ''}` : (o.product_name || o.nom_produit || (o.products && o.products[0] && o.products[0].name) || '-');
        const qty = o.quantity || o.quantite || (o.products && o.products[0] && o.products[0].quantity) || 1;
        const unit = o.prix_unitaire || o.unit_price || o.prix_normal_snapshot || o.prix || '-';
        const total = o.total || o.total_price || o.prix_total || (Number.isFinite(parseFloat(unit)) ? (parseFloat(unit) * qty).toFixed(2) : '-');
        const date = o.date_commande || o.created_at || o.date || '';
        const id = o.id_commande || o.id || o.id_order || '';
        const actions = isOffer ? `
              <button class="table-action-btn" title="Confirmer" onclick="Admin.updateOrderStatus('${id}','confirmee','Offre')"><i class="fa-solid fa-check"></i></button>
              <button class="table-action-btn warning" title="Marquer r�cup�r�e" onclick="Admin.updateOrderStatus('${id}','recuperee','Offre')"><i class="fa-solid fa-box-open"></i></button>
              <button class="table-action-btn danger" title="Annuler" onclick="Admin.updateOrderStatus('${id}','annulee','Offre')"><i class="fa-solid fa-xmark"></i></button>
        ` : `
              <button class="table-action-btn" title="Valider" onclick="Admin.updateOrderStatus('${id}','validee','Produit')"><i class="fa-solid fa-check"></i></button>
              <button class="table-action-btn warning" title="Marquer retir�e" onclick="Admin.updateOrderStatus('${id}','retiree','Produit')"><i class="fa-solid fa-box-open"></i></button>
              <button class="table-action-btn danger" title="Annuler" onclick="Admin.updateOrderStatus('${id}','annulee','Produit')"><i class="fa-solid fa-xmark"></i></button>
        `;
        return `<tr>
          <td>${id}</td>
          <td><span class="badge">${type}</span></td>
          <td>${product}</td>
          <td>${student}${studentSuffix}</td>
          <td>${qty}</td>
          <td>${unit} FCFA</td>
          <td>${total} FCFA</td>
          <td>${statusBadge}</td>
          <td>${date}</td>
          <td>
            <div class="table-actions">
              ${actions}
            </div>
          </td>
        </tr>`;
      }).join('');
    } catch (e) {
      console.error(e);
      container.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:40px;color:var(--color-text-muted);">Impossible de charger les commandes</td></tr>';
    }
  },

  async updateOrderStatus(orderId, newStatus, orderType = 'Produit') {
    if (!orderId) return;
    Components.confirm('Modifier le statut de la commande', `Mettre la commande ${orderId} � <strong>${newStatus}</strong> ?`, async () => {
      try {
        const endpoint = orderType === 'Offre' ? 'commander.php' : 'commandes.php';
        const resp = await fetch(`/api/${endpoint}?id=${orderId}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ statut: newStatus })
});
       if (resp.ok) {
        Components.showToast('Succ�s', 'Statut mis � jour.', 'success');
        Admin.initOrders();
}      else {
  const data = await resp.json();
  Components.showToast('Erreur', data.error || 'Impossible de mettre � jour le statut', 'error');
}
      } catch (e) {
        console.error(e);
        Components.showToast('Erreur', 'Erreur r�seau lors de la mise � jour du statut', 'error');
      }
    });
  }

};






