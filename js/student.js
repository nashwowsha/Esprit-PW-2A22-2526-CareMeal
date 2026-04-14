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


    this.loadOffers();

    // Load impact
    this.loadImpact(user);
  },

  //
  // Si le serveur PHP n'est pas disponible, repli sur localStorage
  async loadOffers() {
    const container = document.getElementById('offers-grid');
    if (!container) return;

    container.innerHTML = '<div style="text-align:center;padding:40px;color:var(--color-text-muted);font-size:1.5rem;"><i class="fa-solid fa-spinner fa-spin"></i></div>';

    let offers = [];
    let fromApi = false;

    try {
      const basePath = App.getBasePath();
      const res  = await fetch(basePath + 'api/offers.php?status=active');
      if (res.ok) {
        const json = await res.json();
        if (json.success && Array.isArray(json.data)) {
          offers = json.data;
          fromApi = true;
        }
      }
    } catch (_) {
      // serveur PHP non disponible → fallback localStorage
    }

    // Fallback localStorage (mode démo)
    if (!fromApi) {
      const raw = App.getOffers().filter(o => o.status === 'active');
      offers = raw.map(o => ({
        id_offre:       o.id,
        titre:          o.title,
        description:    o.description,
        prix:           o.price,
        prix_original:  o.originalPrice,
        quantite:       o.quantity,
        quantite_restante: o.remaining,
        heure_debut:    o.pickupStart,
        heure_fin:      o.pickupEnd,
        photo_url:      '',
        statut:         'publiée',
        nom_categorie:  o.category || '',
        discount:       Math.round((1 - o.price / o.originalPrice) * 100),
        _partnerName:   o.partnerName,
      }));
    }

    if (offers.length === 0) {
      container.innerHTML = `
        <div class="empty-state" style="grid-column:1/-1;">
          <div class="empty-icon"><i class="fa-solid fa-utensils"></i></div>
          <h3>Aucune offre disponible</h3>
          <p>Revenez plus tard pour découvrir de nouvelles offres !</p>
        </div>`;
      return;
    }

    container.innerHTML = offers.map(o => {
      const disc    = o.prix_original > 0 ? Math.round((1 - o.prix / o.prix_original) * 100) : 0;
      const resto   = o._partnerName || o.nom_categorie || '';
      const urgence = o.quantite_restante <= 2
        ? `<span class="offer-card-badge"><span class="badge badge-danger badge-dot">Plus que ${o.quantite_restante} !</span></span>`
        : '';
      const img = o.photo_url
        ? `<img src="${o.photo_url}" style="width:100%;height:140px;object-fit:cover;border-radius:12px 12px 0 0;" onerror="this.parentElement.innerHTML='<div style=\'width:100%;height:140px;display:flex;align-items:center;justify-content:center;font-size:2rem;color:var(--color-text-muted);\'>🍽️</div>'">`
        : `<div style="width:100%;height:140px;display:flex;align-items:center;justify-content:center;font-size:2.5rem;background:var(--color-dark-hover);border-radius:12px 12px 0 0;">🍽️</div>`;

      return `
        <div class="offer-card animate-fade-in-up">
          <div class="offer-card-image" style="position:relative;overflow:hidden;border-radius:12px 12px 0 0;">
            ${img}
            <span class="offer-card-discount">-${disc}%</span>
            ${urgence}
          </div>
          <div class="offer-card-body">
            <h4>${o.titre}</h4>
            ${resto ? `<p class="offer-restaurant"><i class="fa-solid fa-location-dot"></i> ${resto}</p>` : ''}
            <p style="font-size:.8rem;color:var(--color-text-muted);margin-bottom:12px;line-height:1.4;">${o.description}</p>
            <div class="offer-card-footer">
              <div class="offer-card-price">
                <span class="original">${Number(o.prix_original).toFixed(1)} DT</span>
                <span class="discounted">${Number(o.prix).toFixed(1)} DT</span>
              </div>
              ${o.heure_debut ? `<span class="offer-card-time"><i class="fa-solid fa-clock"></i> ${o.heure_debut}–${o.heure_fin}</span>` : ''}
            </div>
          </div>
        </div>`;
    }).join('');
  },

  loadImpact(user) {
    const mealsSaved = document.getElementById('impact-meals');
    const co2Saved   = document.getElementById('impact-co2');
    const moneySaved = document.getElementById('impact-money');

    if (mealsSaved) mealsSaved.textContent = user.mealsSaved || 0;
    if (co2Saved)   co2Saved.textContent   = (user.co2Saved || 0).toFixed(1) + ' kg';
    if (moneySaved) {
      const orders = App.getOrders().filter(o => o.userId === user.id && o.status === 'completed');
      const saved  = orders.reduce((sum, o) => sum + (o.originalPrice - o.price), 0);
      moneySaved.textContent = saved.toFixed(1) + ' DT';
    }
  },

  // --- Profile ---
  initProfile() {
    if (!App.requireAuth(['student'])) return;
    const user = App.getCurrentUser();

    document.getElementById('profile-name').textContent       = user.name;
    document.getElementById('profile-email').textContent      = user.email;
    document.getElementById('profile-university').textContent = user.university || '—';
    document.getElementById('profile-quartier').textContent   = user.quartier   || '—';
    document.getElementById('profile-avatar-initials').textContent = App.getInitials(user.name);
    document.getElementById('profile-joined').textContent     = App.formatDate(user.createdAt);

    this.loadImpact(user);

    const tagsContainer = document.getElementById('profile-preferences');
    if (tagsContainer && user.preferences) {
      const tagLabels = {
        'halal': '<i class="fa-solid fa-star-and-crescent"></i> Halal',
        'vegetarien': '<i class="fa-solid fa-leaf"></i> Végétarien',
        'vegan': '<i class="fa-solid fa-seedling"></i> Végan',
        'sans-gluten': '<i class="fa-solid fa-wheat-awn"></i> Sans gluten',
        'bio': '<i class="fa-solid fa-leaf"></i> Bio',
        'sans-lactose': '<i class="fa-solid fa-glass-water"></i> Sans lactose',
        'budget': '<i class="fa-solid fa-coins"></i> Petit budget',
        'equilibre': '<i class="fa-solid fa-scale-balanced"></i> Équilibré'
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

    document.querySelectorAll('.tag').forEach(tag => {
      if (user.preferences && user.preferences.includes(tag.dataset.value)) {
        tag.classList.add('selected');
      }
      tag.addEventListener('click', () => tag.classList.toggle('selected'));
    });

    const slider = document.getElementById('pref-budget');
    const value  = document.getElementById('budget-value');
    if (slider) {
      slider.value = user.budgetMax || 8;
      if (value) value.textContent = slider.value + ' DT';
      slider.addEventListener('input', () => { if (value) value.textContent = slider.value + ' DT'; });
    }

    const freq = document.getElementById('pref-frequency');
    if (freq) freq.value = user.frequency || 'quotidien';
  },

  savePreferences() {
    const selectedTags = document.querySelectorAll('.tag.selected');
    const preferences  = Array.from(selectedTags).map(t => t.dataset.value);
    const budgetSlider = document.getElementById('pref-budget');
    const freq         = document.getElementById('pref-frequency');
    const user         = App.getCurrentUser();
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
    const user   = App.getCurrentUser();
    const orders = App.getOrders().filter(o => o.userId === user.id);
    const container = document.getElementById('orders-table-body');
    if (!container) return;

    if (orders.length === 0) {
      container.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;"><div class="empty-state" style="padding:16px;"><div class="empty-icon"><i class="fa-solid fa-box"></i></div><h3>Aucune commande</h3><p>Votre historique est vide. Commencez à sauver des repas !</p></div></td></tr>';
      return;
    }

    const statusMap = {
      completed: '<span class="badge badge-success badge-dot">Terminée</span>',
      pending:   '<span class="badge badge-warning badge-dot">En cours</span>',
      cancelled: '<span class="badge badge-danger badge-dot">Annulée</span>'
    };

    container.innerHTML = orders.map(order => `
      <tr>
        <td><strong style="color:var(--color-white)">${order.items}</strong></td>
        <td>${order.partnerName}</td>
        <td>
          <span style="text-decoration:line-through;color:var(--color-text-muted);font-size:.8rem;">${order.originalPrice.toFixed(1)} DT</span>
          <strong style="color:var(--color-primary);margin-left:4px;">${order.price.toFixed(1)} DT</strong>
        </td>
        <td>${App.formatDate(order.date)}</td>
        <td>${statusMap[order.status] || order.status}</td>
        <td>${order.rating ? '<i class="fa-solid fa-star"></i>'.repeat(order.rating) : '—'}</td>
      </tr>`).join('');
  },

  // --- Settings ---
  initSettings() {
    if (!App.requireAuth(['student'])) return;
  },

  updatePassword() {
    const current = document.getElementById('current-password')?.value;
    const newPwd  = document.getElementById('new-password')?.value;
    const confirm = document.getElementById('confirm-new-password')?.value;
    const user    = App.getCurrentUser();

    if (current !== user.password) { Components.showToast('Erreur', 'Mot de passe actuel incorrect', 'error'); return; }
    if (newPwd.length < 6)         { Components.showToast('Erreur', 'Minimum 6 caractères', 'error'); return; }
    if (newPwd !== confirm)        { Components.showToast('Erreur', 'Les mots de passe ne correspondent pas', 'error'); return; }

    App.updateUser(user.id, { password: newPwd });
    Components.showToast('Succès', 'Mot de passe modifié avec succès', 'success');
    App.addLog('Mot de passe modifié');
    document.getElementById('current-password').value = '';
    document.getElementById('new-password').value     = '';
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