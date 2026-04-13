/* ============================================
   CAREMEAL — PARTNER MODULE
   partner.js — Partner-specific logic
   ============================================ */

const Partner = {
  init() {
    if (!App.requireAuth(['partner'])) return;
    this.loadDashboard();
  },

  // --- Dashboard / Profile ---
  async loadDashboard() {
    await App.refreshCurrentUser();
    const user = App.getCurrentUser();
    if (!user) return;

    // The display name can be from the database explicit fields, or fallback to name
    const displayName = user.nomEntreprise || user.secteur_activite ? user.nomEntreprise || user.name : user.name || 'Partenaire';

    // Profile info
    this.setField('partner-name', displayName);
    this.setField('partner-type', user.secteur_activite || 'Non renseigné (Secteur)');
    this.setField('partner-address', user.site_web || 'Non renseigné (Site web)');
    this.setField('partner-phone', user.phone || 'Non renseigné');
    this.setField('partner-email', user.email);
    this.setField('partner-description', user.description || 'Aucune description');
    this.setField('partner-avatar', App.getInitials(displayName));

    const statusEl = document.getElementById('partner-status');
    if (statusEl) {
      const badges = {
        active: '<span class="badge badge-active badge-dot">Actif</span>',
        pending: '<span class="badge badge-pending badge-dot">En attente de validation</span>',
        banned: '<span class="badge badge-banned badge-dot">Suspendu</span>'
      };
      statusEl.innerHTML = badges[user.status] || user.status;
    }

    // Horaires
    const hoursContainer = document.getElementById('partner-hours');
    if (hoursContainer && user.horaires) {
      const days = { lun: 'Lundi', mar: 'Mardi', mer: 'Mercredi', jeu: 'Jeudi', ven: 'Vendredi', sam: 'Samedi', dim: 'Dimanche' };
      hoursContainer.innerHTML = Object.entries(days).map(([key, label]) => `
        <div class="hours-row">
          <span class="hours-day">${label}</span>
          <span class="hours-time">${user.horaires[key] || '—'}</span>
        </div>
      `).join('');
    }

    // Quick stats
    this.setField('stat-meals', user.mealsSaved || 0);
    this.setField('stat-rating', user.avgRating ? '<i class="fa-solid fa-star"></i> ' + user.avgRating : '—');
    this.setField('stat-reviews', user.reviewCount || 0);
    this.setField('stat-co2', ((user.co2Saved || 0)).toFixed(1) + ' kg');
  },

  // --- Offers ---
  initOffers() {
    if (!App.requireAuth(['partner'])) return;
    const user = App.getCurrentUser();
    const offers = App.getOffers().filter(o => o.partnerId === user.id);

    const activeContainer = document.getElementById('active-offers');
    const expiredContainer = document.getElementById('expired-offers');

    const active = offers.filter(o => o.status === 'active');
    const expired = offers.filter(o => o.status === 'expired');

    // Active count
    this.setField('active-count', active.length);
    this.setField('expired-count', expired.length);

    if (activeContainer) {
      if (active.length === 0) {
        activeContainer.innerHTML = '<div class="empty-state" style="padding:32px;"><div class="empty-icon"><i class="fa-solid fa-box"></i></div><h3>Aucune offre active</h3><p>Publiez votre première offre anti-gaspi !</p></div>';
      } else {
        activeContainer.innerHTML = active.map(o => this.offerCard(o)).join('');
      }
    }

    if (expiredContainer) {
      if (expired.length === 0) {
        expiredContainer.innerHTML = '<p style="color:var(--color-text-muted);text-align:center;padding:24px;">Aucune offre expirée</p>';
      } else {
        expiredContainer.innerHTML = expired.map(o => this.offerCard(o)).join('');
      }
    }
  },

  offerCard(offer) {
    const discount = Math.round((1 - offer.price / offer.originalPrice) * 100);
    return `
      <div class="offer-card">
        <div class="offer-card-image">
          <span>${offer.emoji || '<i class="fa-solid fa-utensils"></i>'}</span>
          <span class="offer-card-discount">-${discount}%</span>
          <span class="offer-card-badge">
            <span class="badge badge-${offer.status === 'active' ? 'success' : 'warning'} badge-dot">${offer.status === 'active' ? 'Active' : 'Expirée'}</span>
          </span>
        </div>
        <div class="offer-card-body">
          <h4>${offer.title}</h4>
          <p style="font-size:0.8rem;color:var(--color-text-muted);margin-bottom:12px;">${offer.description}</p>
          <div class="offer-card-footer">
            <div class="offer-card-price">
              <span class="original">${offer.originalPrice.toFixed(1)} DT</span>
              <span class="discounted">${offer.price.toFixed(1)} DT</span>
            </div>
            <span class="offer-card-time"><i class="fa-solid fa-clock"></i> ${offer.pickupStart}-${offer.pickupEnd}</span>
          </div>
          ${offer.status === 'active' ? `<p style="font-size:0.75rem;color:var(--color-text-muted);margin-top:8px;"><i class="fa-solid fa-box"></i> ${offer.remaining}/${offer.quantity} restants</p>` : ''}
        </div>
      </div>
    `;
  },

  // --- Stats ---
  initStats() {
    if (!App.requireAuth(['partner'])) return;
    const user = App.getCurrentUser();
    const offers = App.getOffers().filter(o => o.partnerId === user.id);
    const orders = App.getOrders().filter(o => o.partnerId === user.id);

    this.setField('stats-meals', user.mealsSaved || 0);
    this.setField('stats-co2', ((user.co2Saved || 0)).toFixed(1) + ' kg');
    this.setField('stats-rating', user.avgRating || '—');
    this.setField('stats-reviews', user.reviewCount || 0);
    this.setField('stats-offers-active', offers.filter(o => o.status === 'active').length);
    this.setField('stats-offers-total', offers.length);
    this.setField('stats-orders', orders.length);

    // Revenue
    const revenue = orders.reduce((s, o) => s + o.price, 0);
    this.setField('stats-revenue', revenue.toFixed(1) + ' DT');

    // Recent reviews
    const reviewsContainer = document.getElementById('recent-reviews');
    if (reviewsContainer) {
      const ratedOrders = orders.filter(o => o.rating);
      if (ratedOrders.length === 0) {
        reviewsContainer.innerHTML = '<p style="color:var(--color-text-muted);text-align:center;padding:24px;">Aucun avis reçu</p>';
      } else {
        reviewsContainer.innerHTML = ratedOrders.map(o => {
          const student = App.getUserById(o.userId);
          return `
            <div style="display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--color-dark-border);">
              <div class="avatar avatar-sm">${student ? App.getInitials(student.name) : '?'}</div>
              <div style="flex:1;">
                <strong style="color:var(--color-white);font-size:0.9rem;">${student ? student.name : 'Étudiant'}</strong>
                <p style="font-size:0.8rem;color:var(--color-text-muted);">${o.items}</p>
              </div>
              <div style="color:var(--color-warning);">${'<i class="fa-solid fa-star"></i>'.repeat(o.rating)}</div>
            </div>
          `;
        }).join('');
      }
    }
  },

  // --- Settings ---
  async initSettings() {
    await App.refreshCurrentUser();
    if (!App.requireAuth(['partner'])) return;
    const user = App.getCurrentUser();

    // Pre-fill form
    const fields = { 
      'edit-prenom': 'prenom', 
      'edit-nom': 'nom', 
      'edit-name': 'name', 
      'edit-secteur': 'secteur_activite', 
      'edit-website': 'site_web', 
      'edit-phone': 'phone', 
      'edit-description': 'description',
      'edit-linkedin': 'linkedin',
      'edit-facebook': 'facebook',
      'edit-instagram': 'instagram',
      'edit-twitter': 'twitter'
    };
    Object.entries(fields).forEach(([inputId, key]) => {
      const input = document.getElementById(inputId);
      if (input) input.value = user[key] || '';
    });
  },

    async saveProfile() {
    const user = App.getCurrentUser();
    const btn = document.getElementById('btn-save-profile');
    
    const nom_entreprise = document.getElementById('edit-name')?.value.trim();
    const telephone = document.getElementById('edit-phone')?.value.trim();
    const description = document.getElementById('edit-description')?.value.trim();
    const nom = document.getElementById('edit-nom')?.value.trim();
    const prenom = document.getElementById('edit-prenom')?.value.trim();
    const linkedin = document.getElementById('edit-linkedin')?.value.trim();
    const facebook = document.getElementById('edit-facebook')?.value.trim();
    const instagram = document.getElementById('edit-instagram')?.value.trim();
    const twitter = document.getElementById('edit-twitter')?.value.trim();
    const github = document.getElementById('edit-github')?.value.trim();

    if (!nom_entreprise) {
        Components.showToast('Erreur', "Le nom de l'établissement est requis.", 'error');
        return;
    }
    if (!nom || !prenom) {
        Components.showToast('Erreur', "Votre nom et prénom sont obligatoires.", 'error');
        return;
    }
    if (!telephone) {
        Components.showToast('Erreur', 'Le téléphone est requis.', 'error');
        return;
    }
    if (!/^[0-9+\s\-]{8,15}$/.test(telephone)) {
        Components.showToast('Erreur', 'Le format du numéro de téléphone est invalide.', 'error');
        return;
    }

    const urls = { linkedin, github, instagram, facebook, twitter };
    for (let key in urls) {
        if (urls[key] && !/^https?:\/\/.+/.test(urls[key])) {
            Components.showToast('Erreur', `Le format de l'URL pour ${key} est invalide (doit commencer par http:// ou https://)`, 'error');
            return;
        }
    }

    const oldText = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sauvegarde...';
    btn.disabled = true;

    try {
      const response = await fetch('/projet2a22/Controller/AuthController.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'update-partner-profile',
          user_id: user.id || user.user_id,
          nom_entreprise: nom_entreprise,
          nom: nom,
          prenom: prenom,
          telephone: telephone,
          description: description,
          linkedin: linkedin,
          facebook: facebook,
          instagram: instagram,
          twitter: twitter,
          github: github
        })
      });
              const text = await response.text();
        const startIndex = text.indexOf('{');
        const str = startIndex !== -1 ? text.substring(startIndex) : text;
        let data = {success: false, message: 'Erreur PHP'};
        try { data = JSON.parse(str); } catch(e) { console.error('PHP returned:', text); }
      
      btn.innerHTML = oldText;
      btn.disabled = false;

      if (data.success) {
        user.name = nom_entreprise;
        user.nom_entreprise = nom_entreprise;
        user.nom = nom;
        user.prenom = prenom;
        user.phone = telephone;
        user.telephone = telephone;
        user.description = description;
        user.linkedin = linkedin;
        user.facebook = facebook;
        user.instagram = instagram;
        user.twitter = twitter;
        user.github = github;
        App.setCurrentUser(user);
        
        Components.showToast('Succès', 'Profil mis à jour avec succès.', 'success');
        
        const headerNameList = document.querySelectorAll('.sidebar-user-name, #sidebar-user-name');
        headerNameList.forEach(el => el.textContent = nom_entreprise);
      } else {
        Components.showToast('Erreur', data.message || 'Erreur lors de la mise à jour.', 'error');
      }
    } catch (e) {
      btn.innerHTML = oldText;
      btn.disabled = false;
      Components.showToast('Erreur', 'Erreur serveur', 'error');
    }
  },

  updatePassword() {
    const current = document.getElementById('current-password')?.value;
    const newPwd = document.getElementById('new-password')?.value;
    const confirm = document.getElementById('confirm-new-password')?.value;
    const user = App.getCurrentUser();
    const btn = document.getElementById('btn-update-password') || event.target;

    if (!current) {
      Components.showToast('Erreur', 'Veuillez saisir votre mot de passe actuel', 'error');
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

    const oldText = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mise à jour...';
    btn.disabled = true;

    

    fetch('/projet2a22/Controller/AuthController.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'update-password',
        user_id: user.id || user.user_id,
        current_password: current,
        new_password: newPwd
      })
    })
    .then(res => res.json())
    .then(data => {
      btn.innerHTML = oldText;
      btn.disabled = false;
      if (data.success) {
        Components.showToast('Succès', 'Mot de passe modifié avec succès', 'success');
        App.addLog('Mot de passe modifié');
        document.getElementById('current-password').value = '';
        document.getElementById('new-password').value = '';
        document.getElementById('confirm-new-password').value = '';
      } else {
        Components.showToast('Erreur', data.message || 'Le mot de passe actuel est incorrect.', 'error');
      }
    })
    .catch(err => {
      console.error(err);
      btn.innerHTML = oldText;
      btn.disabled = false;
      Components.showToast('Erreur', 'Erreur serveur', 'error');
    });
  },

  deleteAccount() {
    Components.confirm('Supprimer le compte', 'Cette action est irréversible. Toutes vos données seront perdues.', () => {
      const user = App.getCurrentUser();
      
      const btn = document.getElementById('btn-delete-account');
      if(btn) { btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Suppression...'; btn.disabled = true; }

      fetch('/projet2a22/Controller/AuthController.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'delete-account',
          user_id: user.id || user.user_id
        })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          localStorage.removeItem('caremeal_user');
          window.location.href = '/projet2a22/View/FrontOffice/index.php';
        } else {
            if(btn) { btn.innerHTML = 'Supprimer'; btn.disabled = false; }
            Components.showToast('Erreur', data.message || 'Erreur lors de la suppression.', 'error');
        }
      })
      .catch(err => {
         if(btn) { btn.innerHTML = 'Supprimer'; btn.disabled = false; }
         Components.showToast('Erreur', 'Erreur serveur', 'error');
      });
    });
  },

  // --- Helpers ---
  setField(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
  }
};
