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
  async initOrders() {
    await App.refreshCurrentUser();
    if (!App.requireAuth(['student'])) return;

    // Load products grid
    this.loadOrderProducts();

    // Load order history
    const user = App.getCurrentUser();
    let orders = [];
    try {
      const resp = await fetch(App.apiUrl(`api/commandes.php?user_id=${encodeURIComponent(user.id)}`));
      orders = await resp.json();
    } catch(e) {
      orders = [];
    }

    const container = document.getElementById('orders-table-body');
    if (!container) return;

    // Store all orders for search functionality
    this._currentOrders = orders;

    // Render function with optional search filter
    const renderOrders = (searchText = '') => {
      let filtered = orders;
      
      if (searchText.trim().length > 0) {
        const q = searchText.toLowerCase();
        filtered = orders.filter(o => (
          (o.product_name && o.product_name.toLowerCase().includes(q)) ||
          (o.partnerName && o.partnerName.toLowerCase().includes(q)) ||
          (o.date_commande && o.date_commande.toLowerCase().includes(q))
        ));
      }

      if (filtered.length === 0) {
        container.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;"><div class="empty-state" style="padding:16px;"><div class="empty-icon"><i class="fa-solid fa-box"></i></div><h3>Aucune commande</h3><p>Votre historique est vide. Commencez à sauver des repas !</p></div></td></tr>';
        return;
      }

      const statusMap = {
        en_attente: '<span class="badge badge-warning badge-dot">En attente</span>',
        validee: '<span class="badge badge-success badge-dot">Validée</span>',
        retiree: '<span class="badge badge-success badge-dot">Retirée</span>',
        annulee: '<span class="badge badge-danger badge-dot">Annulée</span>'
      };

      // Render orders with AI scores (throttled to avoid rate limit)
      (async () => {
        const rows = [];
        
        for (const order of filtered) {
          // Calculate waste reduction score from discount
          const normal = Number(order.prix_normal_snapshot || 0);
          const paid = Number(order.total || 0);
          const score = normal > 0 ? Math.round(((normal - paid) / normal) * 100) : 50;

          // Get AI explanation (sequential to avoid rate limit)
          let aiNote = '—';
          try {
            const aiResp = await fetch(App.apiUrl('api/ai_score.php'), {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({
                score: score,
                product: [order.product_name || 'produit']
              })
            });
            if (aiResp.ok) {
              const aiData = await aiResp.json();
              aiNote = `<span title="${aiData.explanation}" style="cursor:help;">
                <strong style="color:var(--color-primary)">${aiData.score}/100</strong>
                <i class="fa-solid fa-circle-info" style="margin-left:4px;color:var(--color-text-muted);"></i>
              </span>`;
            } else if (aiResp.status === 429) {
              aiNote = `<span title="API rate limited, try again in a moment" style="cursor:help;color:var(--color-text-muted);">
                ${score}/100 <i class="fa-solid fa-hourglass-end"></i>
              </span>`;
            }
          } catch(e) {
            // keep aiNote as '—'
          }

          // Small delay between requests to avoid rate limit
          await new Promise(r => setTimeout(r, 300));

          rows.push(`
            <tr>
              <td><strong>${order.product_name || '—'}</strong></td>
              <td>${order.partnerName || '—'}</td>
              <td>
                <span style="text-decoration:line-through;color:var(--color-text-muted);font-size:0.8rem;">${normal.toFixed(1)} DT</span>
                <strong style="color:var(--color-primary);margin-left:4px;">${paid.toFixed(1)} DT</strong>
              </td>
              <td>${order.date_commande ? order.date_commande.substring(0, 10) : '—'}</td>
              <td>${statusMap[order.statut] || order.statut}</td>
              <td>${aiNote}</td>
              <td>
                <div class="table-actions">
                  ${order.statut === 'en_attente' ? `<button class="table-action-btn danger" title="Annuler" onclick="Student.cancelOrder(${order.id_commande})"><i class="fa-solid fa-ban"></i></button>` : ''}
                  <button class="table-action-btn danger" title="Supprimer" onclick="Student.deleteOrder(${order.id_commande})"><i class="fa-solid fa-trash"></i></button>
                </div>
              </td>
            </tr>
          `);
        }

        container.innerHTML = rows.join('');
      })();
    };

    // Initial render
    renderOrders();

    // Attach search listener
    const searchInput = document.getElementById('orders-search');
    if (searchInput) {
      searchInput.addEventListener('input', (e) => {
        renderOrders(e.target.value);
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

    fetch(App.apiUrl('Controller/AuthController.php'), {
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

    

    fetch(App.apiUrl('Controller/AuthController.php'), {
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

  // --- Points ---
  async initPoints() {
    await App.refreshCurrentUser();
    if (!App.requireAuth(['student'])) return;
    const user = App.getCurrentUser() || {};

    const points = Number(user.points ?? user.totalPoints ?? 0);
    const levels = [
      { name: 'Débutant', min: 0, next: 100 },
      { name: 'Bronze', min: 100, next: 250 },
      { name: 'Argent', min: 250, next: 500 },
      { name: 'Or', min: 500, next: 1000 },
      { name: 'Platine', min: 1000, next: null }
    ];

    let current = levels[0];
    for (const level of levels) {
      if (points >= level.min) current = level;
    }

    const totalPointsEl = document.getElementById('total-points');
    const levelNameEl = document.getElementById('level-name');
    const currentLevelEl = document.getElementById('current-level');
    const pointsNextEl = document.getElementById('points-next');
    const progressEl = document.getElementById('level-progress');

    if (totalPointsEl) totalPointsEl.textContent = points;
    if (levelNameEl) levelNameEl.innerHTML = `${current.name} <i class="fa-solid fa-star"></i>`;
    if (currentLevelEl) currentLevelEl.textContent = String(levels.indexOf(current) + 1);

    let progress = 100;
    if (current.next !== null) {
      const pointsInLevel = points - current.min;
      const span = current.next - current.min;
      progress = Math.max(0, Math.min(100, Math.round((pointsInLevel / span) * 100)));
      if (pointsNextEl) pointsNextEl.textContent = `${Math.max(0, current.next - points)} points pour le niveau suivant`;
    } else if (pointsNextEl) {
      pointsNextEl.textContent = 'Niveau maximum atteint';
    }

    if (progressEl) progressEl.style.width = `${progress}%`;
  },

  copyReferralCode() {
    const codeEl = document.getElementById('referral-code');
    if (!codeEl) return;

    const code = codeEl.textContent.trim();
    if (!code) return;

    navigator.clipboard.writeText(code)
      .then(() => Components.showToast('Succès', 'Code copié dans le presse-papiers.', 'success'))
      .catch(() => Components.showToast('Erreur', 'Impossible de copier le code.', 'error'));
  },

  // ================================================================
  // CRUD - DELETE : Supprimer son propre compte
  // ================================================================
  deleteAccount() {
    Components.confirm('Supprimer le compte', 'Cette action est irréversible. Toutes vos données seront perdues.', () => {
      const user = App.getCurrentUser();
      
      const btn = document.getElementById('btn-delete-account');
      if(btn) { btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Suppression...'; btn.disabled = true; }

      fetch(App.apiUrl('Controller/AuthController.php'), {
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
          window.location.href = App.apiUrl('View/FrontOffice/index.php');
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
    (async () => {
      try {
        const resp = await fetch(App.apiUrl('api/products.php'));
        const raw = await resp.json();
        const products = raw.map(p => ({
          id: String(p.id_produit || p.id),
          name: p.nom || p.name,
          description: p.description || '',
          categoryName: p.category_name || p.categoryName || '',
          originalPrice: parseFloat(p.prix_normal || p.originalPrice || 0),
          price: parseFloat(p.prix_commande || p.price || 0),
          stock: Number(p.stock || 0),
          active: Number(p.actif ?? p.active ?? 0) === 1
        })).filter(p => p.active && p.stock > 0);

        const grid = document.getElementById('products-grid');
        if (!grid) return;

        if (products.length === 0) {
          grid.innerHTML = '<p style="color:var(--color-text-muted);padding:16px;">Aucun produit disponible.</p>';
          return;
        }

        grid.innerHTML = products.map(p => `
          <div class="card" style="padding:16px;display:flex;flex-direction:column;gap:8px;">
            <h4 style="margin:0;color:var(--color-text)">${p.name}</h4>
            <p style="margin:0;font-size:0.85rem;color:var(--color-text-muted)">${p.categoryName}</p>
            <p style="margin:0;font-size:0.82rem;color:var(--color-text-muted)">${p.description}</p>
            <div style="display:flex;align-items:center;gap:8px;">
              <span style="text-decoration:line-through;color:var(--color-text-muted);font-size:0.8rem;">${p.originalPrice.toFixed(1)} DT</span>
              <strong style="color:var(--color-primary)">${p.price.toFixed(1)} DT</strong>
            </div>
            <p style="margin:0;font-size:0.8rem;color:var(--color-text-muted);">Stock: ${p.stock}</p>
            <button class="btn btn-primary" onclick="Student.placeOrder('${p.id}')">
              <i class="fa-solid fa-cart-shopping"></i> Commander
            </button>
          </div>
        `).join('');
      } catch(e) {
        console.error('Erreur chargement produits:', e);
      }
    })();
  },

  placeOrder(productId) {
    (async () => {
      try {
        const user = App.getCurrentUser();
        const payload = { product_id: productId, user_id: user.id, quantity: 1 };
        const resp = await fetch(App.apiUrl('api/commandes.php'), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await resp.json();
        if (resp.ok) {
          Components.showToast('Succès', 'Commande passée avec succès!', 'success');
          Student.initOrders();
        } else {
          Components.showToast('Erreur', data.error || 'Erreur lors de la commande.', 'error');
        }
      } catch(e) {
        console.error('placeOrder error:', e);
        Components.showToast('Erreur', 'Impossible de passer la commande.', 'error');
      }
    })();
  },

  placeOfferOrder(offerId) {
    (async () => {
      try {
        await App.refreshCurrentUser();
        const user = App.getCurrentUser();
        if (!user) {
          Components.showToast('Erreur', 'Session utilisateur introuvable.', 'error');
          return;
        }

        const fullName = String(user.name || '').trim();
        const nameParts = fullName.split(/\s+/).filter(Boolean);
        const prenom = String(user.prenom || nameParts[0] || '').trim();
        const nom = String(user.nom || nameParts.slice(1).join(' ') || prenom || 'Etudiant').trim();
        const email = String(user.email || '').trim();
        const telephone = String(user.phone || user.telephone || '').trim();

        if (!email || !telephone) {
          Components.showToast('Erreur', 'Votre email et votre téléphone sont requis pour commander.', 'error');
          return;
        }

        const resp = await fetch(App.apiUrl('api/commander.php'), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            id_offre: offerId,
            nom,
            prenom,
            email,
            telephone,
            quantite: 1,
            statut: 'en_attente'
          })
        });
        const data = await resp.json();

        if (resp.ok && data.success) {
          Components.showToast('Succès', 'Commande envoyée avec succès.', 'success');
        } else {
          Components.showToast('Erreur', data.error || 'Erreur lors de la commande.', 'error');
        }
      } catch (e) {
        console.error('placeOfferOrder error:', e);
        Components.showToast('Erreur', 'Impossible de passer la commande.', 'error');
      }
    })();
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
        en_attente: '<span class="badge badge-warning badge-dot">En attente</span>',
        validee: '<span class="badge badge-success badge-dot">Validée</span>',
        retiree: '<span class="badge badge-success badge-dot">Retirée</span>',
        annulee: '<span class="badge badge-danger badge-dot">Annulée</span>'
      };

      // Render orders with AI scores
      (async () => {
        const rows = await Promise.all(filtered.map(async (order) => {
          // Calculate waste reduction score from discount
          const normal = Number(order.prix_normal_snapshot || 0);
          const paid = Number(order.total || 0);
          const score = normal > 0 ? Math.round(((normal - paid) / normal) * 100) : 50;

          // Get AI explanation
          let aiNote = '—';
          try {
            const aiResp = await fetch(App.apiUrl('api/ai_score.php'), {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({
                score: score,
                product: [order.product_name || 'produit']
              })
            });
            if (aiResp.ok) {
              const aiData = await aiResp.json();
              aiNote = `<span title="${aiData.explanation}" style="cursor:help;">
                <strong style="color:var(--color-primary)">${aiData.score}/100</strong>
                <i class="fa-solid fa-circle-info" style="margin-left:4px;color:var(--color-text-muted);"></i>
              </span>`;
            }
          } catch(e) {
            // keep aiNote as '—'
          }

          return `
            <tr>
              <td><strong>${order.product_name || '—'}</strong></td>
              <td>${order.partnerName || '—'}</td>
              <td>
                <span style="text-decoration:line-through;color:var(--color-text-muted);font-size:0.8rem;">${normal.toFixed(1)} DT</span>
                <strong style="color:var(--color-primary);margin-left:4px;">${paid.toFixed(1)} DT</strong>
              </td>
              <td>${order.date_commande ? order.date_commande.substring(0, 10) : '—'}</td>
              <td>${statusMap[order.statut] || order.statut}</td>
              <td>${aiNote}</td>
              <td>
                <div class="table-actions">
                  ${order.statut === 'en_attente' ? `<button class="table-action-btn danger" title="Annuler" onclick="Student.cancelOrder(${order.id_commande})"><i class="fa-solid fa-ban"></i></button>` : ''}
                </div>
              </td>
            </tr>
          `;
        }));

        container.innerHTML = rows.join('');
      })();
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
        const localOrders = App.getOrders().filter(o => String(o.userId) === String(user.id));
        const localById = new Map(localOrders.map(o => [String(o.id), o]));
        const apiOrders = raw
          .map(o => this._normalizeOrder(o, user.id))
          .filter(o => String(o.userId) === String(user.id))
          .map(order => {
            const localOrder = localById.get(String(order.id));
            return localOrder && localOrder.note ? { ...order, note: localOrder.note } : order;
          });
        const dbIds = new Set(apiOrders.map(o => String(o.id)));
        const localOnly = localOrders.filter(o => !dbIds.has(String(o.id)) && String(o.userId) === String(user.id));
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
    Components.confirm('Supprimer la commande', `Supprimer définitivement la commande <strong>${order.product_name || order.items || 'cette commande'}</strong> ?`, () => this.deleteOrder(orderId));
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
        Student.initOrders();
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
    Student.initOrders();
  },

  cancelOrder(orderId) {
    (async () => {
      try {
        const resp = await fetch(App.apiUrl(`api/commandes.php?id=${orderId}`), {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'cancel' })
        });
        if (resp.ok) {
          Components.showToast('Succès', 'Commande annulée.', 'success');
          Student.initOrders();
        } else {
          const data = await resp.json();
          Components.showToast('Erreur', data.error || 'Impossible d\'annuler.', 'error');
        }
      } catch(e) {
        Components.showToast('Erreur', 'Impossible d\'annuler la commande.', 'error');
      }
    })();
  }

};


