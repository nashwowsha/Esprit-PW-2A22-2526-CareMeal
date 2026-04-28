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

    const offers = App.getOffers()
      .filter(o => o.status === 'active')
      .filter((offer) => {
        const rawRemaining = offer && offer.remaining !== undefined ? offer.remaining : null;
        const rawQuantity = offer && offer.quantity !== undefined ? offer.quantity : null;

        const remaining = rawRemaining === null ? NaN : Number(rawRemaining);
        const quantity = rawQuantity === null ? NaN : Number(rawQuantity);

        if (Number.isFinite(remaining)) {
          return remaining > 0;
        }
        if (Number.isFinite(quantity)) {
          return quantity > 0;
        }

        return true;
      });

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
    const user = App.getCurrentUser();
    const orders = App.getOrders().filter(o => o.userId === user.id);

    const container = document.getElementById('orders-table-body');
    if (!container) return;

    if (orders.length === 0) {
      container.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;"><div class="empty-state" style="padding:16px;"><div class="empty-icon"><i class="fa-solid fa-box"></i></div><h3>Aucune commande</h3><p>Votre historique est vide. Commencez à sauver des repas !</p></div></td></tr>';
      return;
    }

    container.innerHTML = orders.map(order => {
      const statusMap = {
        completed: '<span class="badge badge-success badge-dot">Terminée</span>',
        pending: '<span class="badge badge-warning badge-dot">En cours</span>',
        cancelled: '<span class="badge badge-danger badge-dot">Annulée</span>'
      };
      return `
        <tr>
          <td><strong style="color:var(--color-white)">${order.items}</strong></td>
          <td>${order.partnerName}</td>
          <td>
            <span style="text-decoration:line-through;color:var(--color-text-muted);font-size:0.8rem;">${order.originalPrice.toFixed(1)} DT</span>
            <strong style="color:var(--color-primary);margin-left:4px;">${order.price.toFixed(1)} DT</strong>
          </td>
          <td>${App.formatDate(order.date)}</td>
          <td>${statusMap[order.status] || order.status}</td>
          <td>${order.rating ? '<i class="fa-solid fa-star"></i>'.repeat(order.rating) : '—'}</td>
        </tr>
      `;
    }).join('');
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
