/* ============================================
   CAREMEAL — STUDENT MODULE
   student.js — Student-specific logic
   ============================================ */

const Student = {
  init() {
    if (!App.requireAuth(['student'])) return;
    this.loadDashboard();
  },

  async loadDashboard() {
    await App.refreshCurrentUser();
    let user = App.getCurrentUser();
    if (!user) return;

    // Welcome name
    const welcomeName = document.getElementById('welcome-name');
    if (welcomeName) {
        const displayName = user.prenom ? (user.prenom + ' ' + user.nom).trim() : user.name;
        welcomeName.textContent = displayName.split(' ')[0] || 'Étudiant';
    }

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
  async initProfile() {
    await App.refreshCurrentUser();
    if (!App.requireAuth(['student'])) return;
    const user = App.getCurrentUser();

    document.getElementById('profile-name').textContent = user.name;
    document.getElementById('profile-email').textContent = user.email;
      document.getElementById('profile-university').textContent = user.university || user.ecole || '—';
      document.getElementById('profile-quartier').textContent = user.quartier || '—';
      document.getElementById('profile-telephone').textContent = user.phone || user.telephone || 'Non renseigné';
      document.getElementById('profile-avatar-initials').textContent = App.getInitials(user.name);
      document.getElementById('profile-joined').textContent = App.formatDate(user.createdAt || user.created_at);
    const telNode = document.getElementById('profile-telephone');
    if(telNode) telNode.textContent = user.phone || user.telephone || 'Non renseigné';

      // Update social links
      const socials = ['linkedin', 'github', 'instagram', 'facebook', 'twitter'];
      socials.forEach(sns => {
        const linkEl = document.getElementById('profile-' + sns);
        if (linkEl) {
          if (user[sns]) {
            let url = user[sns];
            // If the user typed just the handle for instagram, we might need a fallback, but let's just make it a link.
            if(sns === 'instagram' && !url.startsWith('http') && !url.startsWith('@')) {
               url = 'https://instagram.com/' + url;
            } else if (sns === 'instagram' && url.startsWith('@')) {
               url = 'https://instagram.com/' + url.substring(1);
            } else if (!url.startsWith('http')) {
               url = 'https://' + url;
            }
            linkEl.href = url;
            linkEl.style.opacity = '1';
              linkEl.title = user[sns];
              linkEl.style.display = 'inline-block';
          } else {
              linkEl.href = '#';
              linkEl.style.opacity = '0.3';
                linkEl.title = 'Non renseigné';
            }
          }
        });
        
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

  // --- Points ---
  async initPoints() {
    await App.refreshCurrentUser();
    if (!App.requireAuth(['student'])) return;
    const user = App.getCurrentUser();
    
    // Total points and level
    const totalPointsNode = document.getElementById('total-points');
    if (totalPointsNode) totalPointsNode.textContent = user.points_accumules || 0;
    
    const levelNameNode = document.getElementById('level-name');
    const currentLevelNode = document.getElementById('current-level');
    const pointsNextNode = document.getElementById('points-next');
    const levelProgressNode = document.getElementById('level-progress');
    
    const pts = user.points_accumules || 0;
    let level = 1, levelName = "Débutant 🌱", maxPts = 100;
    
    if (pts >= 1000) { level = 4; levelName = "Légende Anti-Gaspi 👑"; maxPts = 1000; }
    else if (pts >= 500) { level = 3; levelName = "Héros 🦸"; maxPts = 1000; }
    else if (pts >= 100) { level = 2; levelName = "Initié 🌟"; maxPts = 500; }

    if (levelNameNode) levelNameNode.textContent = levelName;
    if (currentLevelNode) currentLevelNode.textContent = level;
    
    if (level < 4) {
      if (pointsNextNode) pointsNextNode.textContent = (maxPts - pts) + ' points pour le niveau suivant';
      if (levelProgressNode) levelProgressNode.style.width = Math.min(100, (pts / maxPts) * 100) + '%';
    } else {
      if (pointsNextNode) pointsNextNode.textContent = 'Niveau Maximum Atteint !';
      if (levelProgressNode) levelProgressNode.style.width = '100%';
    }

    // Referral Code
    const refCodeNode = document.getElementById('user-referral-code');
    if (refCodeNode) {
      refCodeNode.textContent = user.referral_code || 'NON-DISPONIBLE';
    }
  },

  copyReferralCode() {
    const code = document.getElementById('user-referral-code')?.textContent.trim();
    if (code && code !== 'NON-DISPONIBLE' && code !== 'CHARGEMENT...') {
      navigator.clipboard.writeText(code).then(() => {
        const btn = document.getElementById('btn-copy-code');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Copié !';
        btn.classList.add('btn-primary');
        btn.classList.remove('btn-outline');
        setTimeout(() => {
          btn.innerHTML = originalHtml;
          btn.classList.remove('btn-primary');
          btn.classList.add('btn-outline');
        }, 2000);
        Components.showToast('Succès', 'Code copié dans le presse-papiers !', 'success');
      }).catch(() => {
        Components.showToast('Erreur', 'Impossible de copier le code', 'error');
      });
    }
  },

  // --- Settings ---
  async initSettings() {
    await App.refreshCurrentUser();
    if (!App.requireAuth(['student'])) return;
    const user = App.getCurrentUser();
    
    // Remplir les champs
      document.getElementById('edit-nom').value = user.nom || user.name.split(' ').slice(1).join(' ') || '';
      document.getElementById('edit-prenom').value = user.prenom || user.name.split(' ')[0] || '';
      document.getElementById('edit-telephone').value = user.phone || user.telephone || '';
    const unVal = user.university || user.ecole || '';
      const unSel = document.getElementById('edit-university');
      let foundUn = false;
      if (unSel) {
          for (let i = 0; i < unSel.options.length; i++) {
              if (unSel.options[i].value === unVal) { foundUn = true; break; }
          }
          if (!foundUn && unVal !== '') {
              unSel.value = 'autre';
              document.getElementById('edit-university-other-container').style.display = 'block';
              document.getElementById('edit-university-other').value = unVal;
          } else {
              unSel.value = unVal;
          }
      }
    const qtVal = user.quartier || '';
      const qtSel = document.getElementById('edit-quartier');
      let foundQt = false;
      if (qtSel && qtSel.options) {
          for (let i = 0; i < qtSel.options.length; i++) {
              if (qtSel.options[i].value === qtVal) { foundQt = true; break; }
          }
          if (!foundQt && qtVal !== '') {
              qtSel.value = 'autre';
              document.getElementById('edit-quartier-other-container').style.display = 'block';
              document.getElementById('edit-quartier-other').value = qtVal;
          } else {
              qtSel.value = qtVal;
          }
      }
    document.getElementById('edit-linkedin').value = user.linkedin || '';
    document.getElementById('edit-github').value = user.github || '';
    document.getElementById('edit-instagram').value = user.instagram || '';
    document.getElementById('edit-facebook').value = user.facebook || '';
    document.getElementById('edit-twitter').value = user.twitter || '';
  },

    // ================================================================
    // CRUD - UPDATE : Modifier le profil étudiant + CONTROLE DE SAISIE
    // ================================================================
    updateProfile() {
    const user = App.getCurrentUser();
    const btn = document.getElementById('btn-update-profile') || event.target;
    
    const nom = document.getElementById('edit-nom').value.trim();
    const prenom = document.getElementById('edit-prenom').value.trim();

    const tel = document.getElementById('edit-telephone').value.trim();

    if (!nom || !prenom) {
        Components.showToast('Attention', 'Votre nom et prénom sont obligatoires.', 'error');
        return;
    }
    
    if (tel && !/^[0-9+\s\-]{8,15}$/.test(tel)) {
        Components.showToast('Erreur', 'Le format du numéro de téléphone est invalide.', 'error');
        return;
    }

    const unSel = document.getElementById('edit-university');
      const unSelVal = unSel ? unSel.value : '';
      const ecole = (unSelVal === 'autre') ? document.getElementById('edit-university-other').value.trim() : unSelVal;
    const qtSel = document.getElementById('edit-quartier');
      const qtSelVal = qtSel ? qtSel.value : '';
      const quartier = (qtSelVal === 'autre') ? document.getElementById('edit-quartier-other').value.trim() : qtSelVal;
    const linkedin = document.getElementById('edit-linkedin').value.trim();
    const github = document.getElementById('edit-github').value.trim();
    const instagram = document.getElementById('edit-instagram').value.trim();
    const facebook = document.getElementById('edit-facebook').value.trim();
    const twitter = document.getElementById('edit-twitter').value.trim();

    const urls = { linkedin, github, instagram, facebook, twitter };
    for (let key in urls) {
        if (urls[key] && !/^https?:\/\/.+/.test(urls[key])) {
            Components.showToast('Erreur', `Le format de l'URL pour ${key} est invalide (doit commencer par http:// ou https://)`, 'error');
            return;
        }
    }

    const oldText = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enregistrement...';
    btn.disabled = true;

    fetch('/projet2a22/Controller/AuthController.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'update-profile',
            user_id: user.id,
            nom: nom,
            prenom: prenom,
            telephone: tel,
            linkedin: linkedin,
            github: github,
            instagram: instagram, facebook: facebook, twitter: twitter,
            ecole: ecole,
            quartier: quartier
        })
    })
    .then(res => res.text())
    .then(text => {
        let result;
        try {
            let startIndex = text.indexOf('{');
            if (startIndex === -1) throw new Error("Réponse serveur invalide ou vide");
            result = JSON.parse(text.substring(startIndex));
        } catch (e) {
            throw new Error("Erreur de format depuis PHP: " + text);
        }
        if(result.success) {
            // Mettre à jour l'utilisateur localement
            user.name = prenom + ' ' + nom;
            user.phone = tel;
            user.university = ecole;
            user.ecole = ecole;
            user.quartier = quartier;
            user.linkedin = linkedin;
            user.github = github;
            user.instagram = instagram;
            user.facebook = facebook;
            user.twitter = twitter;
            App.setCurrentUser(user);
            
            Components.showToast('Succ\u00E8s', 'Profil mis \u00E0 jour avec succ\u00E8s', 'success');
        } else {
            Components.showToast('Erreur', result.message, 'error');
        }
        btn.innerHTML = oldText;
        btn.disabled = false;
    })
    .catch(err => {
        Components.showToast('Erreur', 'Erreur serveur', 'error');
        btn.innerHTML = oldText;
        btn.disabled = false;
    });
  },

  // ================================================================
  // CRUD - UPDATE : Modifier le mot de passe + CONTROLE DE SAISIE
  // ================================================================
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

  // ================================================================
  // CRUD - DELETE : Supprimer son propre compte
  // ================================================================
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
  }
};
