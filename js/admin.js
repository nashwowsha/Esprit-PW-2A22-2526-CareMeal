/* ============================================
   CAREMEAL â€” ADMIN MODULE
   admin.js â€” Admin-specific logic
   ============================================ */

const Admin = {
  pageExt() {
    return window.location.pathname.toLowerCase().endsWith('.html') ? '.html' : '.php';
  },

  adminPage(name) {
    return `${name}${this.pageExt()}`;
  },

  init() {
    if (!App.requireAuth(['admin'])) return;
    this.loadDashboard();
  },

  // --- Dashboard ---
  loadDashboard() {
    const users = App.getUsers();
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
          <td><span class="badge badge-${u.role === 'student' ? 'info' : 'primary'}">${u.role === 'student' ? '<i class="fa-solid fa-graduation-cap"></i> Ã‰tudiant' : '<i class="fa-solid fa-store"></i> Partenaire'}</span></td>
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
            <strong>${log.userName}</strong> â€” ${log.action}
          </div>
        </div>
      `).join('');
    }
  },

  // --- Users List ---
  currentFilter: 'all',
  currentSearch: '',

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

  loadUsers() {
    let users = App.getUsers().filter(u => u.role !== 'admin');

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

    const container = document.getElementById('users-table-body');
    if (!container) return;

    document.getElementById('users-count').textContent = users.length + ' utilisateur' + (users.length > 1 ? 's' : '');

    if (users.length === 0) {
      container.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;color:var(--color-text-muted);">Aucun utilisateur trouvÃ©</td></tr>';
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
        <td><span class="badge badge-${u.role === 'student' ? 'info' : 'primary'}">${u.role === 'student' ? '<i class="fa-solid fa-graduation-cap"></i> Ã‰tudiant' : '<i class="fa-solid fa-store"></i> Partenaire'}</span></td>
        <td>${Admin.statusBadge(u.status)}</td>
        <td>${App.formatDate(u.createdAt)}</td>
        <td>${u.ordersCount || u.mealsSaved || 0}</td>
        <td>
          <div class="table-actions">
            <button class="table-action-btn" title="Voir dÃ©tail" onclick="window.location.href='${Admin.adminPage('user-detail')}?id=${u.id}'"><i class="fa-solid fa-eye"></i></button>
            ${u.status === 'active' ? `<button class="table-action-btn danger" title="Bannir" onclick="Admin.toggleBan('${u.id}', true)"><i class="fa-solid fa-ban"></i></button>` : ''}
            ${u.status === 'banned' ? `<button class="table-action-btn" title="RÃ©activer" onclick="Admin.toggleBan('${u.id}', false)"><i class="fa-solid fa-check"></i></button>` : ''}
            ${u.status === 'pending' ? `<button class="table-action-btn" title="Valider" onclick="Admin.validatePartner('${u.id}', true)"><i class="fa-solid fa-check"></i></button>` : ''}
            <button class="table-action-btn danger" title="Supprimer" onclick="Admin.confirmDelete('${u.id}')"><i class="fa-solid fa-trash"></i></button>
          </div>
        </td>
      </tr>
    `).join('');
  },

  toggleBan(userId, ban) {
    const action = ban ? 'bannir' : 'rÃ©activer';
    const user = App.getUserById(userId);
    Components.confirm(
      ban ? 'Bannir l\'utilisateur' : 'RÃ©activer l\'utilisateur',
      `Voulez-vous ${action} <strong>${user.name}</strong> ?`,
      () => {
        App.updateUser(userId, { status: ban ? 'banned' : 'active' });
        App.addLog(`Utilisateur ${ban ? 'banni' : 'rÃ©activÃ©'}: ${user.name}`);
        Components.showToast('SuccÃ¨s', `${user.name} a Ã©tÃ© ${ban ? 'banni' : 'rÃ©activÃ©'}.`, ban ? 'warning' : 'success');
        this.loadUsers();
      }
    );
  },

  confirmDelete(userId) {
    const user = App.getUserById(userId);
    Components.confirm(
      'Supprimer l\'utilisateur',
      `Cette action est irrÃ©versible. Supprimer <strong>${user.name}</strong> ?`,
      () => {
        App.deleteUser(userId);
        Components.showToast('SupprimÃ©', `${user.name} a Ã©tÃ© supprimÃ©.`, 'error');
        this.loadUsers();
      }
    );
  },

  // --- User Detail ---
  initUserDetail() {
    if (!App.requireAuth(['admin'])) return;
    const params = new URLSearchParams(window.location.search);
    const userId = params.get('id');
    if (!userId) { window.location.href = this.adminPage('users'); return; }

    const user = App.getUserById(userId);
    if (!user) { window.location.href = this.adminPage('users'); return; }

    document.getElementById('detail-avatar').textContent = App.getInitials(user.name);
    document.getElementById('detail-name').textContent = user.name;
    document.getElementById('detail-email').textContent = user.email;
    document.getElementById('detail-role').innerHTML = `<span class="badge badge-${user.role === 'student' ? 'info' : 'primary'}">${user.role === 'student' ? '<i class="fa-solid fa-graduation-cap"></i> Ã‰tudiant' : '<i class="fa-solid fa-store"></i> Partenaire'}</span>`;
    document.getElementById('detail-status').innerHTML = this.statusBadge(user.status);
    document.getElementById('detail-joined').textContent = App.formatDate(user.createdAt);

    // Extra info based on role
    const extraContainer = document.getElementById('detail-extra');
    if (user.role === 'student') {
      extraContainer.innerHTML = `
        <div class="impact-grid">
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-school"></i></div><div class="impact-value">${user.university || 'â€”'}</div><div class="impact-label">UniversitÃ©</div></div>
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-location-dot"></i></div><div class="impact-value">${user.quartier || 'â€”'}</div><div class="impact-label">Quartier</div></div>
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-star"></i></div><div class="impact-value">${user.points || 0}</div><div class="impact-label">Points</div></div>
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-utensils"></i></div><div class="impact-value">${user.mealsSaved || 0}</div><div class="impact-label">Repas sauvÃ©s</div></div>
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-leaf"></i></div><div class="impact-value">${(user.co2Saved || 0).toFixed(1)} kg</div><div class="impact-label">COâ‚‚ Ã©vitÃ©</div></div>
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-box"></i></div><div class="impact-value">${user.ordersCount || 0}</div><div class="impact-label">Commandes</div></div>
        </div>
      `;
    } else if (user.role === 'partner') {
      extraContainer.innerHTML = `
        <div class="impact-grid">
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-clipboard"></i></div><div class="impact-value">${user.type || 'â€”'}</div><div class="impact-label">Type</div></div>
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-location-dot"></i></div><div class="impact-value" style="font-size:1rem;">${user.address || 'â€”'}</div><div class="impact-label">Adresse</div></div>
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-mobile-screen"></i></div><div class="impact-value" style="font-size:1rem;">${user.phone || 'â€”'}</div><div class="impact-label">TÃ©lÃ©phone</div></div>
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-utensils"></i></div><div class="impact-value">${user.mealsSaved || 0}</div><div class="impact-label">Repas sauvÃ©s</div></div>
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
      actionsHtml += `<button class="btn btn-success btn-sm btn-full" onclick="Admin.toggleBan('${user.id}', false)"><i class="fa-solid fa-check"></i> RÃ©activer</button>`;
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
            <div class="timeline-content"><strong>${o.items}</strong> â€” ${o.partnerName} â€” ${o.price.toFixed(1)} DT</div>
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
        <td>${p.type || 'â€”'}</td>
        <td>${Admin.statusBadge(p.status)}</td>
        <td>${p.mealsSaved || 0}</td>
        <td>${p.avgRating ? '<i class="fa-solid fa-star"></i> ' + p.avgRating : 'â€”'}</td>
        <td>
          <div class="table-actions">
            <button class="table-action-btn" title="Voir dÃ©tail" onclick="window.location.href='${Admin.adminPage('user-detail')}?id=${p.id}'"><i class="fa-solid fa-eye"></i></button>
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
    const partner = App.getUserById(partnerId);
    const action = approve ? 'valider' : 'refuser';
    Components.confirm(
      approve ? 'Valider le partenaire' : 'Refuser le partenaire',
      `Voulez-vous ${action} <strong>${partner.name}</strong> ?`,
      () => {
        App.updateUser(partnerId, { status: approve ? 'active' : 'banned' });
        App.addLog(`Partenaire ${approve ? 'validÃ©' : 'refusÃ©'}: ${partner.name}`);
        Components.showToast('SuccÃ¨s', `${partner.name} a Ã©tÃ© ${approve ? 'validÃ©' : 'refusÃ©'}.`, approve ? 'success' : 'warning');
        if (typeof this.loadPartners === 'function') this.loadPartners();
        if (typeof this.loadUsers === 'function') this.loadUsers();
      }
    );
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
      container.innerHTML = '<p style="color:var(--color-text-muted);text-align:center;padding:40px;">Aucune activitÃ© enregistrÃ©e</p>';
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
  }
};

