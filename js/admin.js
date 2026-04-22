/* ============================================
   CAREMEAL — ADMIN MODULE
   admin.js — Admin-specific logic
   ============================================ */

const Admin = {
  categoriesFilter: 'all',
  productsFilter: 'all',

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
            <strong>${log.userName}</strong> — ${log.action}
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
            <button class="table-action-btn" title="Voir détail" onclick="window.location.href='user-detail.html?id=${u.id}'"><i class="fa-solid fa-eye"></i></button>
            ${u.status === 'active' ? `<button class="table-action-btn danger" title="Bannir" onclick="Admin.toggleBan('${u.id}', true)"><i class="fa-solid fa-ban"></i></button>` : ''}
            ${u.status === 'banned' ? `<button class="table-action-btn" title="Réactiver" onclick="Admin.toggleBan('${u.id}', false)"><i class="fa-solid fa-check"></i></button>` : ''}
            ${u.status === 'pending' ? `<button class="table-action-btn" title="Valider" onclick="Admin.validatePartner('${u.id}', true)"><i class="fa-solid fa-check"></i></button>` : ''}
            <button class="table-action-btn danger" title="Supprimer" onclick="Admin.confirmDelete('${u.id}')"><i class="fa-solid fa-trash"></i></button>
          </div>
        </td>
      </tr>
    `).join('');
  },

  toggleBan(userId, ban) {
    const action = ban ? 'bannir' : 'réactiver';
    const user = App.getUserById(userId);
    Components.confirm(
      ban ? 'Bannir l\'utilisateur' : 'Réactiver l\'utilisateur',
      `Voulez-vous ${action} <strong>${user.name}</strong> ?`,
      () => {
        App.updateUser(userId, { status: ban ? 'banned' : 'active' });
        App.addLog(`Utilisateur ${ban ? 'banni' : 'réactivé'}: ${user.name}`);
        Components.showToast('Succès', `${user.name} a été ${ban ? 'banni' : 'réactivé'}.`, ban ? 'warning' : 'success');
        this.loadUsers();
      }
    );
  },

  confirmDelete(userId) {
    const user = App.getUserById(userId);
    Components.confirm(
      'Supprimer l\'utilisateur',
      `Cette action est irréversible. Supprimer <strong>${user.name}</strong> ?`,
      () => {
        App.deleteUser(userId);
        Components.showToast('Supprimé', `${user.name} a été supprimé.`, 'error');
        this.loadUsers();
      }
    );
  },

  // --- User Detail ---
  initUserDetail() {
    if (!App.requireAuth(['admin'])) return;
    const params = new URLSearchParams(window.location.search);
    const userId = params.get('id');
    if (!userId) { window.location.href = 'users.html'; return; }

    const user = App.getUserById(userId);
    if (!user) { window.location.href = 'users.html'; return; }

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
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-school"></i></div><div class="impact-value">${user.university || '—'}</div><div class="impact-label">Université</div></div>
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-location-dot"></i></div><div class="impact-value">${user.quartier || '—'}</div><div class="impact-label">Quartier</div></div>
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-star"></i></div><div class="impact-value">${user.points || 0}</div><div class="impact-label">Points</div></div>
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-utensils"></i></div><div class="impact-value">${user.mealsSaved || 0}</div><div class="impact-label">Repas sauvés</div></div>
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-leaf"></i></div><div class="impact-value">${(user.co2Saved || 0).toFixed(1)} kg</div><div class="impact-label">CO₂ évité</div></div>
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-box"></i></div><div class="impact-value">${user.ordersCount || 0}</div><div class="impact-label">Commandes</div></div>
        </div>
      `;
    } else if (user.role === 'partner') {
      extraContainer.innerHTML = `
        <div class="impact-grid">
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-clipboard"></i></div><div class="impact-value">${user.type || '—'}</div><div class="impact-label">Type</div></div>
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-location-dot"></i></div><div class="impact-value" style="font-size:1rem;">${user.address || '—'}</div><div class="impact-label">Adresse</div></div>
          <div class="impact-card"><div class="impact-icon"><i class="fa-solid fa-mobile-screen"></i></div><div class="impact-value" style="font-size:1rem;">${user.phone || '—'}</div><div class="impact-label">Téléphone</div></div>
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
            <div class="timeline-content"><strong>${o.items}</strong> — ${o.partnerName} — ${o.price.toFixed(1)} DT</div>
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
        <td>${p.type || '—'}</td>
        <td>${Admin.statusBadge(p.status)}</td>
        <td>${p.mealsSaved || 0}</td>
        <td>${p.avgRating ? '<i class="fa-solid fa-star"></i> ' + p.avgRating : '—'}</td>
        <td>
          <div class="table-actions">
            <button class="table-action-btn" title="Voir détail" onclick="window.location.href='user-detail.html?id=${p.id}'"><i class="fa-solid fa-eye"></i></button>
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

  // --- Categories ---
  initCategories() {
    if (!App.requireAuth(['admin'])) return;
    this.loadCategories();
  },

  loadCategories() {
    const container = document.getElementById('categories-table-body');
    if (!container) return;

    const categories = App.getCategories();
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
        <td>${c.description || '—'}</td>
        <td>${App.getProducts().filter(p => p.categoryId === c.id).length}</td>
        <td>${c.active ? '<span class="badge badge-success badge-dot">Active</span>' : '<span class="badge badge-danger badge-dot">Inactive</span>'}</td>
        <td>
          <div class="table-actions">
            <button class="table-action-btn" title="Modifier" onclick="Admin.openCategoryModal('${c.id}')"><i class="fa-solid fa-pen"></i></button>
            <button class="table-action-btn ${c.active ? 'danger' : ''}" title="Activer/Désactiver" onclick="Admin.toggleCategory('${c.id}')"><i class="fa-solid ${c.active ? 'fa-ban' : 'fa-check'}"></i></button>
          </div>
        </td>
      </tr>
    `).join('');
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

    const category = App.getCategoryById(categoryId);
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
    const id = document.getElementById('category-id').value;
    const name = document.getElementById('category-name').value.trim();
    const description = document.getElementById('category-description').value.trim();
    const active = document.getElementById('category-active').checked;

    if (!name) {
      Components.showToast('Erreur', 'Le nom de catégorie est requis.', 'error');
      return;
    }

    if (!id) {
      App.addCategory({ name, description, active });
      App.addLog(`Catégorie créée: ${name}`);
      Components.showToast('Succès', 'Catégorie créée.', 'success');
    } else {
      App.updateCategory(id, { name, description, active });
      App.addLog(`Catégorie modifiée: ${name}`);
      Components.showToast('Succès', 'Catégorie mise à jour.', 'success');
    }

    Components.closeModal('category-modal');
    this.loadCategories();
  },

  toggleCategory(categoryId) {
    const category = App.getCategoryById(categoryId);
    if (!category) return;
    App.updateCategory(categoryId, { active: !category.active });
    App.addLog(`Catégorie ${!category.active ? 'activée' : 'désactivée'}: ${category.name}`);
    this.loadCategories();
  },

  // --- Products ---
  initProducts() {
    if (!App.requireAuth(['admin'])) return;
    this.initProductFilters();
    this.loadProducts();
  },

  initProductFilters() {
    const select = document.getElementById('products-filter-category');
    if (!select) return;

    const categories = App.getCategories();
    select.innerHTML = '<option value="all">Toutes les catégories</option>' +
      categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('');

    select.value = this.productsFilter;
  },

  loadProducts() {
    const container = document.getElementById('products-table-body');
    if (!container) return;

    let products = App.getProducts();
    if (this.productsFilter !== 'all') {
      products = products.filter(p => p.categoryId === this.productsFilter);
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
        <td>${p.categoryName || '—'}</td>
        <td>${p.partnerName || 'CareMeal'}</td>
        <td>${p.originalPrice.toFixed(1)} DT</td>
        <td><strong style="color:var(--color-primary)">${p.price.toFixed(1)} DT</strong></td>
        <td>${p.stock}</td>
        <td>${p.active ? '<span class="badge badge-success badge-dot">Actif</span>' : '<span class="badge badge-danger badge-dot">Inactif</span>'}</td>
        <td>
          <div class="table-actions">
            <button class="table-action-btn" title="Modifier" onclick="Admin.openProductModal('${p.id}')"><i class="fa-solid fa-pen"></i></button>
            <button class="table-action-btn ${p.active ? 'danger' : ''}" title="Activer/Désactiver" onclick="Admin.toggleProduct('${p.id}')"><i class="fa-solid ${p.active ? 'fa-ban' : 'fa-check'}"></i></button>
          </div>
        </td>
      </tr>
    `).join('');
  },

  setProductsFilter(categoryId) {
    this.productsFilter = categoryId;
    this.loadProducts();
  },

  openProductModal(productId = '') {
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

    const categories = App.getCategories().filter(c => c.active);
    categoryInput.innerHTML = categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('');

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

    const product = App.getProductById(productId);
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
    const id = document.getElementById('product-id').value;
    const name = document.getElementById('product-name').value.trim();
    const description = document.getElementById('product-description').value.trim();
    const categoryId = document.getElementById('product-category').value;
    const partnerId = document.getElementById('product-partner').value;
    const price = Number(document.getElementById('product-price').value);
    const originalPrice = Number(document.getElementById('product-original-price').value);
    const stock = Number(document.getElementById('product-stock').value);
    const active = document.getElementById('product-active').checked;

    if (!name || !categoryId || !Number.isFinite(price) || price <= 0 || !Number.isFinite(stock) || stock < 0) {
      Components.showToast('Erreur', 'Vérifiez les champs requis du produit.', 'error');
      return;
    }

    const category = App.getCategoryById(categoryId);
    if (!category) {
      Components.showToast('Erreur', 'Catégorie invalide.', 'error');
      return;
    }

    const partner = partnerId ? App.getUserById(partnerId) : null;
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

    if (!id) {
      App.addProduct(payload);
      App.addLog(`Produit créé: ${name}`);
      Components.showToast('Succès', 'Produit créé.', 'success');
    } else {
      App.updateProduct(id, payload);
      App.addLog(`Produit modifié: ${name}`);
      Components.showToast('Succès', 'Produit mis à jour.', 'success');
    }

    Components.closeModal('product-modal');
    this.loadProducts();
  },

  toggleProduct(productId) {
    const product = App.getProductById(productId);
    if (!product) return;
    App.updateProduct(productId, { active: !product.active });
    App.addLog(`Produit ${!product.active ? 'activé' : 'désactivé'}: ${product.name}`);
    this.loadProducts();
  },

  validatePartner(partnerId, approve) {
    const partner = App.getUserById(partnerId);
    const action = approve ? 'valider' : 'refuser';
    Components.confirm(
      approve ? 'Valider le partenaire' : 'Refuser le partenaire',
      `Voulez-vous ${action} <strong>${partner.name}</strong> ?`,
      () => {
        App.updateUser(partnerId, { status: approve ? 'active' : 'banned' });
        App.addLog(`Partenaire ${approve ? 'validé' : 'refusé'}: ${partner.name}`);
        Components.showToast('Succès', `${partner.name} a été ${approve ? 'validé' : 'refusé'}.`, approve ? 'success' : 'warning');
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
  }
};
