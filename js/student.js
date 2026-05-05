/* ============================================
   CAREMEAL — STUDENT MODULE
   student.js — Student-specific logic
   ============================================ */

const Student = {
  init() {
    if (!App.requireAuth(['student'])) return;
    this.loadDashboard();
  },

  loadDashboard() {
    const user = App.getCurrentUser();
    if (!user) return;

    // Welcome name
    const welcomeName = document.getElementById('welcome-name');
    if (welcomeName) welcomeName.textContent = user.name.split(' ')[0];

    // Level
    const levelDisplay = document.getElementById('user-level');
    if (levelDisplay) levelDisplay.textContent = 'Niveau ' + (user.level || 1);

    // Load offers
    this.loadOffers();

    // Load impact
    this.loadImpact(user);
  },

  loadOffers() {
    const container = document.getElementById('offers-grid');
    if (!container) return;

    const offers = App.getOffers().filter(o => o.status === 'active');

    if (offers.length === 0) {
      container.innerHTML = '<div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-utensils"></i></div><h3>Aucune offre disponible</h3><p>Revenez plus tard pour découvrir de nouvelles offres !</p></div>';
      return;
    }

    container.innerHTML = offers.map(offer => `
      <div class="offer-card animate-fade-in-up">
        <div class="offer-card-image">
          <span>${offer.emoji || '<i class="fa-solid fa-utensils"></i>'}</span>
          <span class="offer-card-discount">-${Math.round((1 - offer.price / offer.originalPrice) * 100)}%</span>
          ${offer.remaining <= 2 ? '<span class="offer-card-badge"><span class="badge badge-danger badge-dot">Plus que ' + offer.remaining + '</span></span>' : ''}
        </div>
        <div class="offer-card-body">
          <h4>${offer.title}</h4>
          <p class="offer-restaurant"><i class="fa-solid fa-location-dot"></i> ${offer.partnerName}</p>
          <p style="font-size:0.8rem;color:var(--color-text-muted);margin-bottom:12px;">${offer.description}</p>
          <div class="offer-card-footer">
            <div class="offer-card-price">
              <span class="original">${offer.originalPrice.toFixed(1)} DT</span>
              <span class="discounted">${offer.price.toFixed(1)} DT</span>
            </div>
            <span class="offer-card-time"><i class="fa-solid fa-clock"></i> ${offer.pickupStart}-${offer.pickupEnd}</span>
          </div>
        </div>
      </div>
    `).join('');
  },

  loadImpact(user) {
    const mealsSaved = document.getElementById('impact-meals');
    const co2Saved = document.getElementById('impact-co2');
    const moneySaved = document.getElementById('impact-money');

    if (mealsSaved) mealsSaved.textContent = user.mealsSaved || 0;
    if (co2Saved) co2Saved.textContent = (user.co2Saved || 0).toFixed(1) + ' kg';
    if (moneySaved) {
      const orders = App.getOrders().filter(o => o.userId === user.id && o.status === 'completed');
      const saved = orders.reduce((sum, o) => sum + (o.originalPrice - o.price), 0);
      moneySaved.textContent = saved.toFixed(1) + ' DT';
    }
  },

  // --- Profile ---
  initProfile() {
    if (!App.requireAuth(['student'])) return;
    const user = App.getCurrentUser();

    document.getElementById('profile-name').textContent = user.name;
    document.getElementById('profile-email').textContent = user.email;
    document.getElementById('profile-university').textContent = user.university || '—';
    document.getElementById('profile-quartier').textContent = user.quartier || '—';
    document.getElementById('profile-avatar-initials').textContent = App.getInitials(user.name);
    document.getElementById('profile-joined').textContent = App.formatDate(user.createdAt);

    // Load impact
    this.loadImpact(user);

    // Load preferences tags
    const tagsContainer = document.getElementById('profile-preferences');
    if (tagsContainer && user.preferences) {
      const tagLabels = {
        'halal': '<i class="fa-solid fa-star-and-crescent"></i> Halal', 'vegetarien': '<i class="fa-solid fa-leaf"></i> Végétarien', 'vegan': '<i class="fa-solid fa-seedling"></i> Végan',
        'sans-gluten': '<i class="fa-solid fa-wheat-awn"></i> Sans gluten', 'bio': '<i class="fa-solid fa-leaf"></i> Bio', 'sans-lactose': '<i class="fa-solid fa-glass-water"></i> Sans lactose',
        'budget': '<i class="fa-solid fa-coins"></i> Petit budget', 'equilibre': '<i class="fa-solid fa-scale-balanced"></i> Équilibré'
      };
      tagsContainer.innerHTML = user.preferences.map(p =>
        `<span class="badge badge-primary">${tagLabels[p] || p}</span>`
      ).join('') || '<span class="text-muted text-sm">Aucune préférence définie</span>';
    }
  },

  // --- Preferences ---
  initPreferences() {
    if (!App.requireAuth(['student'])) return;
    const user = App.getCurrentUser();

    // Pre-select tags
    document.querySelectorAll('.tag').forEach(tag => {
      if (user.preferences && user.preferences.includes(tag.dataset.value)) {
        tag.classList.add('selected');
      }
      tag.addEventListener('click', () => tag.classList.toggle('selected'));
    });

    // Budget slider
    const slider = document.getElementById('pref-budget');
    const value = document.getElementById('budget-value');
    if (slider) {
      slider.value = user.budgetMax || 8;
      if (value) value.textContent = slider.value + ' DT';
      slider.addEventListener('input', () => {
        if (value) value.textContent = slider.value + ' DT';
      });
    }

    // Frequency
    const freq = document.getElementById('pref-frequency');
    if (freq) freq.value = user.frequency || 'quotidien';
  },

  savePreferences() {
    const selectedTags = document.querySelectorAll('.tag.selected');
    const preferences = Array.from(selectedTags).map(t => t.dataset.value);
    const budgetSlider = document.getElementById('pref-budget');
    const freq = document.getElementById('pref-frequency');

    const user = App.getCurrentUser();
    App.updateUser(user.id, {
      preferences,
      budgetMax: budgetSlider ? parseInt(budgetSlider.value) : 8,
      frequency: freq ? freq.value : 'quotidien'
    });

    Components.showToast('Préférences sauvegardées', 'Vos préférences ont été mises à jour.', 'success');
    App.addLog('Préférences alimentaires modifiées');
  },

  // --- Orders ---
  initOrders() {
    if (!App.requireAuth(['student'])) return;
    this.loadOrderProducts();
    this.loadOrdersTable();
    // Attach search handler if present
    const search = document.getElementById('orders-search');
    if (search) {
      search.addEventListener('input', () => this.loadOrdersTable(search.value.trim()));
    }
  },

  _normalizeProduct(p) {
    return {
      id: p.id_produit != null ? p.id_produit : p.id,
      name: p.nom || p.name || '',
      description: p.description || '',
      categoryId: p.id_categorie != null ? p.id_categorie : p.categoryId,
      categoryName: p.category_name || p.categoryName || '',
      originalPrice: parseFloat(p.prix_normal != null ? p.prix_normal : (p.originalPrice || 0)),
      price: parseFloat(p.prix_commande != null ? p.prix_commande : (p.price || 0)),
      stock: Number(p.stock != null ? p.stock : 0),
      active: Number(p.actif != null ? p.actif : (p.active != null ? p.active : 1)) ? true : false,
      partnerName: p.partnerName || 'CareMeal',
      partnerId: p.partnerId || null,
      createdAt: p.created_at || p.createdAt || null
    };
  },

  _normalizeOrder(o, userId) {
    const statMap = { en_attente: 'pending', validee: 'completed', retiree: 'completed', annulee: 'cancelled' };
    const stat = statMap[o.statut] || o.status || 'pending';
    const productName = o.product_name || o.nom_produit || o.items || 'Produit';
    const qty = Number(o.quantite != null ? o.quantite : (o.quantity || 1));
    return {
      id: o.id_commande != null ? o.id_commande : o.id,
      userId: o.id_user || o.userId || userId,
      productId: o.id_produit != null ? o.id_produit : o.productId,
      items: productName.includes(' x') ? productName : `${productName} x${qty}`,
      partnerName: o.partnerName || 'CareMeal',
      originalPrice: parseFloat(o.prix_normal_snapshot != null ? o.prix_normal_snapshot : (o.originalPrice || 0)),
      price: parseFloat(o.total != null ? o.total : (o.price || 0)),
      quantity: qty,
      status: stat,
      date: o.date_commande || o.date || new Date().toISOString(),
      rating: o.rating || null
    };
  },

  loadOrderProducts() {
    const container = document.getElementById('products-grid');
    if (!container) return;

    container.innerHTML = '<div class="card" style="grid-column:1/-1;text-align:center;padding:24px;color:var(--color-text-muted);">Chargement…</div>';

    (async () => {
      let products = [];
      try {
        const resp = await fetch(App.apiUrl('api/products.php'));
        if (resp.ok) {
          const raw = await resp.json();
          const normalized = raw.map(p => this._normalizeProduct(p));
          App.saveProducts(normalized);
          products = normalized.filter(p => p.active && p.stock > 0);
        } else {
          products = App.getProducts().filter(p => p.active && p.stock > 0);
        }
      } catch (e) {
        products = App.getProducts().filter(p => p.active && p.stock > 0);
      }

      if (products.length === 0) {
        container.innerHTML = '<div class="card" style="grid-column:1/-1;text-align:center;padding:24px;color:var(--color-text-muted);">Aucun produit disponible pour le moment.</div>';
        return;
      }

      container.innerHTML = products.map(product => `
        <div class="card">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:8px;">
            <h4 style="margin:0;">${product.name}</h4>
            <span class="badge badge-primary">${product.categoryName || 'Catégorie'}</span>
          </div>
          <p style="font-size:0.85rem;color:var(--color-text-muted);margin-bottom:10px;">${product.description || 'Produit alimentaire anti-gaspi'}</p>
          <p style="font-size:0.8rem;color:var(--color-text-muted);margin-bottom:10px;"><i class="fa-solid fa-store"></i> ${product.partnerName || 'CareMeal'}</p>
          <div style="display:flex;align-items:baseline;gap:8px;margin-bottom:12px;">
            <span style="text-decoration:line-through;color:var(--color-text-muted);font-size:0.8rem;">${product.originalPrice.toFixed(1)} DT</span>
            <strong style="color:var(--color-primary);font-size:1.1rem;">${product.price.toFixed(1)} DT</strong>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
            <span class="badge badge-info badge-dot">Stock: ${product.stock}</span>
            <div style="display:flex;gap:8px;align-items:center;">
              <input type="number" min="1" max="${product.stock}" value="1" id="qty-${product.id}" class="form-input" style="width:76px;height:36px;padding:0 8px;">
              <button class="btn btn-primary btn-sm" onclick="Student.placeOrder('${product.id}')"><i class="fa-solid fa-cart-plus"></i> Commander</button>
            </div>
          </div>
        </div>
      `).join('');
    })();
  },

  async placeOrder(productId) {
    const qtyInput = document.getElementById('qty-' + productId);
    const quantity = qtyInput ? parseInt(qtyInput.value, 10) : 1;

    const result = await App.createOrderFromProduct(productId, quantity);
    if (!result.ok) {
      Components.showToast('Commande refusée', result.message, 'error');
      return;
    }

    Components.showToast('Succès', 'Votre commande a été enregistrée.', 'success');
    this.loadOrderProducts();
    this.loadOrdersTable();
  },

  loadOrdersTable(search = '') {
    const user = App.getCurrentUser();
    const container = document.getElementById('orders-table-body');
    if (!container) return;

    const renderOrders = (orders) => {
      let filtered = orders;
      if (search && search.length > 0) {
        const q = search.toLowerCase();
        filtered = orders.filter(o => (
          (o.items && o.items.toLowerCase().includes(q)) ||
          (o.partnerName && o.partnerName.toLowerCase().includes(q)) ||
          (o.date && o.date.toLowerCase().includes(q))
        ));
      }
      if (filtered.length === 0) {
        container.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;"><div class="empty-state" style="padding:16px;"><div class="empty-icon"><i class="fa-solid fa-box"></i></div><h3>Aucune commande</h3><p>Votre historique est vide. Commencez \u00e0 sauver des repas !</p></div></td></tr>';
        return;
      }
      const statusMap = {
        completed: '<span class="badge badge-success badge-dot">Termin\u00e9e</span>',
        pending:   '<span class="badge badge-warning badge-dot">En cours</span>',
        cancelled: '<span class="badge badge-danger badge-dot">Annul\u00e9e</span>'
      };
      container.innerHTML = filtered.map(order => `
        <tr>
          <td><strong style="color:var(--color-white)">${order.items}</strong></td>
          <td>${order.partnerName}</td>
          <td>
            <span style="text-decoration:line-through;color:var(--color-text-muted);font-size:0.8rem;">${Number(order.originalPrice || 0).toFixed(1)} DT</span>
            <strong style="color:var(--color-primary);margin-left:4px;">${Number(order.price || 0).toFixed(1)} DT</strong>
          </td>
          <td>${App.formatDate(order.date)}</td>
          <td>${statusMap[order.status] || order.status}</td>
          <td>${order.rating ? '<i class="fa-solid fa-star"></i>'.repeat(order.rating) : '\u2014'}</td>
          <td>
            <div class="table-actions">
              ${order.status === 'pending' ? ('<button class="table-action-btn" title="Annuler" onclick="Student.confirmCancelOrder(\'' + order.id + '\')"><i class="fa-solid fa-times-circle"></i></button>') : ''}
              <button class="table-action-btn" title="Supprimer" onclick="Student.confirmDeleteOrder('${order.id}')"><i class="fa-solid fa-trash"></i></button>
            </div>
          </td>
        </tr>
      `).join('');
    };

    // Show local data immediately while API loads
    const localOrders = App.getOrders().filter(o => String(o.userId) === String(user.id));
    renderOrders(localOrders);

    // Always fetch fresh from API (commande JOIN produit gives us product_name)
    (async () => {
      try {
        const resp = await fetch(App.apiUrl('api/commandes.php?user_id=' + encodeURIComponent(user.id)));
        if (!resp.ok) return;
        const raw = await resp.json();
        const apiOrders = raw
          .map(o => this._normalizeOrder(o, user.id))
          .filter(o => String(o.userId) === String(user.id));
        const dbIds = new Set(apiOrders.map(o => String(o.id)));
        const localOnly = App.getOrders().filter(o => !dbIds.has(String(o.id)) && String(o.userId) === String(user.id));
        const merged = [...apiOrders, ...localOnly];
        const otherOrders = App.getOrders().filter(o => String(o.userId) !== String(user.id));
        App.saveOrders([...otherOrders, ...merged]);
        renderOrders(merged);
      } catch (e) { /* already showing local */ }
    })();
  },

  confirmDeleteOrder(orderId) {
    const order = App.getOrders().find(o => String(o.id) === String(orderId));
    if (!order) return;
    Components.confirm('Supprimer la commande', `Supprimer définitivement la commande <strong>${order.items}</strong> ?`, () => this.deleteOrder(orderId));
  },

  async deleteOrder(orderId) {
    // Attempt server delete first
    try {
      const resp = await fetch(App.apiUrl(`api/commandes.php?id=${encodeURIComponent(orderId)}`), { method: 'DELETE' });
      if (resp.ok) {
        // remove from local cache
        const orders = App.getOrders().filter(o => String(o.id) !== String(orderId));
        App.saveOrders(orders);
        Components.showToast('Supprimé', 'Commande supprimée.', 'success');
        this.loadOrderProducts();
        this.loadOrdersTable();
        return;
      }
    } catch (e) {
      // ignore and fallback to local
    }

    // Fallback: remove locally
    const orders = App.getOrders().filter(o => String(o.id) !== String(orderId));
    App.saveOrders(orders);
    Components.showToast('Supprimé (local)', 'Commande supprimée localement.', 'success');
    this.loadOrderProducts();
    this.loadOrdersTable();
  },
  confirmCancelOrder(orderId) {
  const order = App.getOrders().find(o => String(o.id) === String(orderId));
  if (!order) return;
  Components.confirm('Annuler la commande', `Êtes-vous sûr ? La commande <strong>${order.items}</strong> sera annulée et le stock restauré.`, () => this.cancelOrder(orderId));
},

async cancelOrder(orderId) {
  const localOrder = App.getOrders().find(o => String(o.id) === String(orderId));

  // If id looks numeric, try server cancel first
  const looksRemote = /^\d+$/.test(String(orderId));

  if (looksRemote) {
    try {
      const resp = await fetch(App.apiUrl(`api/commandes.php?id=${encodeURIComponent(orderId)}`), {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'cancel' })
      });

      const payload = await resp.json().catch(() => null);
      if (resp.ok && payload) {
        // mark local order cancelled
        const orders = App.getOrders();
        const idx = orders.findIndex(o => String(o.id) === String(orderId));
        if (idx !== -1) {
          orders[idx].status = 'cancelled';
          App.saveOrders(orders);
        }

        // restore stock if product/quantity info returned
        const prodId = payload.id_produit ?? payload.product_id ?? (localOrder ? localOrder.productId : null);
        const qty = Number(payload.quantite ?? payload.quantity ?? (localOrder ? localOrder.quantity : 0));
        if (prodId && qty) {
          const prod = App.getProductById(prodId);
          if (prod) App.updateProduct(prodId, { stock: prod.stock + qty });
        }

        Components.showToast('Annulée', 'Commande annulée et stock restauré.', 'success');
        App.addLog('Commande annulée');
        this.loadOrderProducts();
        this.loadOrdersTable();
        return;
      }

      const err = payload && payload.error ? payload.error : 'Impossible d\'annuler la commande.';
      Components.showToast('Erreur', err, 'error');
      return;
    } catch (e) {
      // fall through to local fallback
    }
  }

  // Local fallback: mark cancelled and restore local stock
  if (localOrder) {
    const orders = App.getOrders();
    const idx = orders.findIndex(o => String(o.id) === String(orderId));
    if (idx !== -1) {
      orders[idx].status = 'cancelled';
      App.saveOrders(orders);
      const prod = App.getProductById(localOrder.productId);
      if (prod) App.updateProduct(localOrder.productId, { stock: prod.stock + (localOrder.quantity || 0) });
    }
    Components.showToast('Annulée (local)', 'Commande annulée localement et stock restauré.', 'success');
    this.loadOrderProducts();
    this.loadOrdersTable();
    return;
  }

  Components.showToast('Erreur', 'Impossible d\'annuler la commande.', 'error');
},


  // --- Points (Removed) ---

  // --- Settings ---
  initSettings() {
    if (!App.requireAuth(['student'])) return;
  },

  updatePassword() {
    const current = document.getElementById('current-password')?.value;
    const newPwd = document.getElementById('new-password')?.value;
    const confirm = document.getElementById('confirm-new-password')?.value;
    const user = App.getCurrentUser();

    if (current !== user.password) {
      Components.showToast('Erreur', 'Mot de passe actuel incorrect', 'error');
      return;
    }
    if (newPwd.length < 6) {
      Components.showToast('Erreur', 'Minimum 6 caractères', 'error');
      return;
    }
    if (newPwd !== confirm) {
      Components.showToast('Erreur', 'Les mots de passe ne correspondent pas', 'error');
      return;
    }

    App.updateUser(user.id, { password: newPwd });
    Components.showToast('Succès', 'Mot de passe modifié avec succès', 'success');
    App.addLog('Mot de passe modifié');

    // Clear fields
    document.getElementById('current-password').value = '';
    document.getElementById('new-password').value = '';
    document.getElementById('confirm-new-password').value = '';
  },

  deleteAccount() {
    Components.confirm('Supprimer le compte', 'Cette action est irréversible. Toutes vos données seront perdues.', () => {
      const user = App.getCurrentUser();
      App.deleteUser(user.id);
      App.logout();
    });
  }
};
