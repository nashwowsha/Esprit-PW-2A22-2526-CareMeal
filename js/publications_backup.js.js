/* ============================================
   CAREMEAL — PUBLICATIONS MODULE
   publications.js — Social Feed Logic (Facebook-style)
   ============================================ */

const Publications = {
  STORAGE_KEY: 'caremeal_publications',

  // --- Storage ---
  getAll() {
    const data = localStorage.getItem(this.STORAGE_KEY);
    return data ? JSON.parse(data) : [];
  },

  save(publications) {
    localStorage.setItem(this.STORAGE_KEY, JSON.stringify(publications));
  },

  // --- Current User Helper ---
  getCurrentUser() {
    return App.getCurrentUser();
  },

  // --- Seed Demo Data ---
  seedIfEmpty() {
    if (this.getAll().length > 0) return;

    const now = Date.now();
    const demo = [
      {
        id: now - 500000,
        authorId: 'user_1',
        authorName: 'Ahmed Ben Ali',
        authorRole: 'student',
        content: 'J\'ai récupéré un super panier chez La Baguette Dorée ! 🥖 Des croissants, du pain frais et même des éclairs au chocolat. Tout ça pour seulement 4.5 DT au lieu de 12 DT. Merci CareMeal pour cette initiative géniale contre le gaspillage alimentaire ! 🌍💚',
        createdAt: new Date(now - 7200000).toISOString(),
        comments: [
          { id: now - 400000, authorId: 'user_2', authorName: 'Fatma Trabelsi', authorRole: 'student', content: 'Trop bien ! Je vais essayer demain 😍', createdAt: new Date(now - 3600000).toISOString() },
          { id: now - 300000, authorId: 'partner_1', authorName: 'La Baguette Dorée', authorRole: 'partner', content: 'Merci Ahmed ! On est ravis que ça vous plaise. À bientôt ! 🥐', createdAt: new Date(now - 1800000).toISOString() }
        ]
      },
      {
        id: now - 400000,
        authorId: 'partner_2',
        authorName: 'Chez Sami - Restaurant',
        authorRole: 'partner',
        content: '🍲 Aujourd\'hui on a un surplus de couscous agneau et de brick au thon ! Venez récupérer vos portions à prix réduit entre 14h et 15h30. Ensemble contre le gaspillage !\n\n📍 12 Avenue Habib Bourguiba, Tunis\n⏰ 14:00 - 15:30',
        createdAt: new Date(now - 14400000).toISOString(),
        comments: [
          { id: now - 200000, authorId: 'user_3', authorName: 'Youssef Hamdi', authorRole: 'student', content: 'J\'arrive ! Le couscous de Chez Sami est le meilleur 😋', createdAt: new Date(now - 10800000).toISOString() }
        ]
      },
      {
        id: now - 300000,
        authorId: 'user_2',
        authorName: 'Fatma Trabelsi',
        authorRole: 'student',
        content: 'Astuce anti-gaspi du jour : Saviez-vous que les bananes trop mûres font les meilleurs banana breads ? 🍌 Ne les jetez plus ! Voici ma recette préférée :\n\n1. 3 bananes bien mûres écrasées\n2. 80g de beurre fondu\n3. 150g de farine\n4. 100g de sucre\n5. 1 œuf\n6. 1 cc de vanille\n\nAu four 45 min à 180°C. Régalez-vous ! 🎂',
        createdAt: new Date(now - 86400000).toISOString(),
        comments: []
      },
      {
        id: now - 200000,
        authorId: 'partner_1',
        authorName: 'La Baguette Dorée',
        authorRole: 'partner',
        content: '📢 Bonne nouvelle ! À partir de cette semaine, nous proposons chaque jour un "Panier Surprise" à 4.5 DT (valeur 12 DT). Pain du jour, viennoiseries et parfois même des pâtisseries 🎉\n\nRendez-vous sur CareMeal pour réserver le vôtre !',
        createdAt: new Date(now - 172800000).toISOString(),
        comments: [
          { id: now - 100000, authorId: 'user_1', authorName: 'Ahmed Ben Ali', authorRole: 'student', content: 'Excellente initiative ! 👏', createdAt: new Date(now - 160000000).toISOString() },
          { id: now - 90000, authorId: 'user_2', authorName: 'Fatma Trabelsi', authorRole: 'student', content: 'J\'adore ce concept. Bravo !', createdAt: new Date(now - 150000000).toISOString() },
          { id: now - 80000, authorId: 'user_3', authorName: 'Youssef Hamdi', authorRole: 'student', content: 'Partagé avec tous mes amis de l\'ENIT ! 🙌', createdAt: new Date(now - 140000000).toISOString() }
        ]
      }
    ];

    this.save(demo);
  },

  // --- CRUD: Publications ---
  addPublication(content) {
    const user = this.getCurrentUser();
    if (!user || !content.trim()) return;

    const publications = this.getAll();
    publications.unshift({
      id: Date.now(),
      authorId: user.id,
      authorName: user.name,
      authorRole: user.role,
      content: content.trim(),
      createdAt: new Date().toISOString(),
      comments: []
    });

    this.save(publications);
    App.addLog('Nouvelle publication créée');
    Components.showToast('Publication créée', 'Votre publication a été partagée avec succès.', 'success');
  },

  updatePublication(pubId, newContent) {
    const publications = this.getAll();
    const pub = publications.find(p => p.id === pubId);
    if (pub && newContent.trim()) {
      pub.content = newContent.trim();
      this.save(publications);
      App.addLog('Publication modifiée');
      Components.showToast('Publication modifiée', 'Votre publication a été mise à jour.', 'success');
    }
  },

  deletePublication(pubId) {
    let publications = this.getAll();
    publications = publications.filter(p => p.id !== pubId);
    this.save(publications);
    App.addLog('Publication supprimée');
    Components.showToast('Publication supprimée', 'La publication a été retirée.', 'info');
  },

  // --- CRUD: Comments ---
  addComment(pubId, content) {
    const user = this.getCurrentUser();
    if (!user || !content.trim()) return;

    const publications = this.getAll();
    const pub = publications.find(p => p.id === pubId);
    if (pub) {
      pub.comments.push({
        id: Date.now(),
        authorId: user.id,
        authorName: user.name,
        authorRole: user.role,
        content: content.trim(),
        createdAt: new Date().toISOString()
      });
      this.save(publications);
    }
  },

  updateComment(pubId, commentId, newContent) {
    const publications = this.getAll();
    const pub = publications.find(p => p.id === pubId);
    if (pub) {
      const comment = pub.comments.find(c => c.id === commentId);
      if (comment && newContent.trim()) {
        comment.content = newContent.trim();
        this.save(publications);
        Components.showToast('Commentaire modifié', 'Votre commentaire a été mis à jour.', 'success');
      }
    }
  },

  deleteComment(pubId, commentId) {
    const publications = this.getAll();
    const pub = publications.find(p => p.id === pubId);
    if (pub) {
      pub.comments = pub.comments.filter(c => c.id !== commentId);
      this.save(publications);
    }
  },

  // --- Permissions ---
  canEditPublication(pub) {
    const user = this.getCurrentUser();
    if (!user) return false;
    return user.role === 'admin' || pub.authorId === user.id;
  },

  canEditComment(comment) {
    const user = this.getCurrentUser();
    if (!user) return false;
    return user.role === 'admin' || comment.authorId === user.id;
  },

  // --- Role Labels ---
  getRoleLabel(role) {
    const labels = { student: 'Étudiant', partner: 'Partenaire', admin: 'Administrateur' };
    return labels[role] || role;
  },

  getRoleIcon(role) {
    const icons = { student: 'fa-graduation-cap', partner: 'fa-store', admin: 'fa-shield-halved' };
    return icons[role] || 'fa-user';
  },

  // --- Time Ago ---
  timeAgo(dateStr) {
    return App.timeAgo(dateStr);
  },

  // ============================================
  // RENDERING
  // ============================================

  // --- Render Compose Area ---
  renderCompose(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const user = this.getCurrentUser();
    if (!user) return;

    const initials = App.getInitials(user.name);

    container.innerHTML = `
      <div class="pub-compose" id="pub-compose-area">
        <div class="pub-compose-header">
          <h3><i class="fa-solid fa-pen-fancy"></i> Ajouter une publication</h3>
        </div>
        <div class="pub-compose-body">
          <div class="avatar" style="width:46px;height:46px;font-size:0.9rem;">${initials}</div>
          <div class="pub-compose-input-area">
            <textarea class="pub-compose-textarea" id="pub-compose-text" placeholder="Partagez quelque chose avec la communauté CareMeal... 💬" rows="3"></textarea>
            <div class="pub-compose-actions">
              <button class="btn btn-primary btn-sm" id="pub-compose-btn" onclick="Publications.handlePublish()">
                <i class="fa-solid fa-paper-plane"></i> Publier
              </button>
            </div>
          </div>
        </div>
      </div>
    `;
  },

  handlePublish() {
    const textarea = document.getElementById('pub-compose-text');
    if (!textarea) return;

    const content = textarea.value.trim();
    if (!content) {
      Components.showToast('Erreur', 'Veuillez écrire quelque chose avant de publier.', 'warning');
      return;
    }

    this.addPublication(content);
    textarea.value = '';
    this.renderFeed('pub-feed-container');
  },

  // --- Render Feed ---
  renderFeed(containerId, filterUserId = null) {
    const container = document.getElementById(containerId);
    if (!container) return;

    let publications = this.getAll();

    // Filter by user if specified (profile page)
    if (filterUserId) {
      publications = publications.filter(p => p.authorId === filterUserId);
    }

    // Stats
    const totalPubs = publications.length;
    const totalComments = publications.reduce((sum, p) => sum + p.comments.length, 0);

    let html = '';

    // Stats bar (only on main feed)
    if (!filterUserId) {
      html += `
        <div class="pub-feed-stats">
          <div class="pub-feed-stats-left">
            <i class="fa-solid fa-newspaper"></i>
            <span><strong>${totalPubs}</strong> publication${totalPubs !== 1 ? 's' : ''} · <strong>${totalComments}</strong> commentaire${totalComments !== 1 ? 's' : ''}</span>
          </div>
        </div>
      `;
    }

    if (publications.length === 0) {
      html += `
        <div class="pub-empty-state">
          <div class="pub-empty-icon"><i class="fa-solid fa-comments"></i></div>
          <h3>${filterUserId ? 'Aucune publication' : 'Le fil est vide'}</h3>
          <p>${filterUserId ? 'Vous n\'avez pas encore publié. Partagez vos expériences avec la communauté !' : 'Soyez le premier à partager quelque chose avec la communauté CareMeal !'}</p>
        </div>
      `;
    } else {
      html += '<div class="pub-feed">';
      publications.forEach(pub => {
        html += this.renderCard(pub);
      });
      html += '</div>';
    }

    container.innerHTML = html;
  },

  // --- Render Single Card ---
  renderCard(pub) {
    const user = this.getCurrentUser();
    const initials = App.getInitials(pub.authorName);
    const canEdit = this.canEditPublication(pub);
    const commentsCount = pub.comments.length;
    const userInitials = user ? App.getInitials(user.name) : '?';

    return `
      <div class="pub-card" id="pub-${pub.id}">
        <!-- Card Header -->
        <div class="pub-card-header">
          <div class="pub-card-author">
            <div class="pub-author-avatar">
              <div class="avatar" style="width:46px;height:46px;font-size:0.9rem;">${initials}</div>
              <span class="pub-role-dot ${pub.authorRole}" title="${this.getRoleLabel(pub.authorRole)}">
                <i class="fa-solid ${this.getRoleIcon(pub.authorRole)}"></i>
              </span>
            </div>
            <div class="pub-author-info">
              <span class="pub-author-name">${this.escapeHtml(pub.authorName)}</span>
              <div class="pub-author-meta">
                <span class="pub-author-role ${pub.authorRole}">${this.getRoleLabel(pub.authorRole)}</span>
                <span class="pub-author-date"><i class="fa-regular fa-clock"></i> ${this.timeAgo(pub.createdAt)}</span>
              </div>
            </div>
          </div>
          ${canEdit ? `
            <div class="pub-card-actions">
              <button class="pub-action-btn edit" title="Modifier" onclick="Publications.openEditModal(${pub.id})">
                <i class="fa-solid fa-pen"></i>
              </button>
              <button class="pub-action-btn delete" title="Supprimer" onclick="Publications.confirmDelete(${pub.id})">
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
          ` : ''}
        </div>

        <!-- Card Body -->
        <div class="pub-card-body">
          <div class="pub-card-content">${this.escapeHtml(pub.content)}</div>
        </div>

        <!-- Card Footer -->
        <div class="pub-card-footer">
          <button class="pub-engagement-btn" onclick="Publications.toggleComments(${pub.id})">
            <i class="fa-regular fa-comment"></i> ${commentsCount} commentaire${commentsCount !== 1 ? 's' : ''}
          </button>
        </div>

        <!-- Comments Section -->
        <div class="pub-comments-section" id="pub-comments-section-${pub.id}" style="display:none;">
          ${commentsCount > 0 ? `
            <div class="pub-comments-list" id="pub-comments-list-${pub.id}">
              ${pub.comments.map(c => this.renderComment(pub.id, c)).join('')}
            </div>
          ` : ''}
          <!-- Comment Form -->
          <form class="pub-comment-form" onsubmit="Publications.handleAddComment(event, ${pub.id})">
            <div class="avatar" style="width:32px;height:32px;font-size:11px;">${userInitials}</div>
            <div class="pub-comment-input-wrap">
              <input type="text" class="pub-comment-input" placeholder="Écrire un commentaire..." required autocomplete="off">
              <button type="submit" class="pub-comment-submit" title="Envoyer">
                <i class="fa-solid fa-paper-plane"></i>
              </button>
            </div>
          </form>
        </div>
      </div>
    `;
  },

  // --- Render Comment ---
  renderComment(pubId, comment) {
    const initials = App.getInitials(comment.authorName);
    const canEdit = this.canEditComment(comment);

    return `
      <div class="pub-comment" id="comment-${comment.id}">
        <div class="avatar" style="width:32px;height:32px;font-size:11px;">${initials}</div>
        <div class="pub-comment-bubble">
          <div class="pub-comment-header">
            <span class="pub-comment-author">${this.escapeHtml(comment.authorName)}</span>
            <span class="pub-comment-date">${this.timeAgo(comment.createdAt)}</span>
          </div>
          <div class="pub-comment-text">${this.escapeHtml(comment.content)}</div>
          ${canEdit ? `
            <div class="pub-comment-actions">
              <button class="pub-comment-action-btn" onclick="Publications.editCommentPrompt(${pubId}, ${comment.id})">
                <i class="fa-solid fa-pen"></i> Modifier
              </button>
              <button class="pub-comment-action-btn delete" onclick="Publications.handleDeleteComment(${pubId}, ${comment.id})">
                <i class="fa-solid fa-trash"></i> Supprimer
              </button>
            </div>
          ` : ''}
        </div>
      </div>
    `;
  },

  // ============================================
  // INTERACTIONS
  // ============================================

  toggleComments(pubId) {
    const section = document.getElementById(`pub-comments-section-${pubId}`);
    if (!section) return;

    if (section.style.display === 'none') {
      section.style.display = 'block';
      // Focus the comment input
      const input = section.querySelector('.pub-comment-input');
      if (input) input.focus();
    } else {
      section.style.display = 'none';
    }
  },

  handleAddComment(e, pubId) {
    e.preventDefault();
    const input = e.target.querySelector('.pub-comment-input');
    if (!input) return;

    const content = input.value.trim();
    if (!content) return;

    this.addComment(pubId, content);
    input.value = '';

    // Re-render the feed to show the new comment
    const feedContainer = document.getElementById('pub-feed-container');
    const profileContainer = document.getElementById('profile-pub-feed');

    if (feedContainer) this.renderFeed('pub-feed-container');
    if (profileContainer) {
      const user = this.getCurrentUser();
      if (user) this.renderFeed('profile-pub-feed', user.id);
    }

    // Re-open the comments for this publication
    setTimeout(() => {
      const section = document.getElementById(`pub-comments-section-${pubId}`);
      if (section) section.style.display = 'block';
    }, 50);
  },

  handleDeleteComment(pubId, commentId) {
    this.deleteComment(pubId, commentId);

    const feedContainer = document.getElementById('pub-feed-container');
    const profileContainer = document.getElementById('profile-pub-feed');

    if (feedContainer) this.renderFeed('pub-feed-container');
    if (profileContainer) {
      const user = this.getCurrentUser();
      if (user) this.renderFeed('profile-pub-feed', user.id);
    }

    // Re-open comments section
    setTimeout(() => {
      const section = document.getElementById(`pub-comments-section-${pubId}`);
      if (section) section.style.display = 'block';
    }, 50);
  },

  editCommentPrompt(pubId, commentId) {
    const publications = this.getAll();
    const pub = publications.find(p => p.id === pubId);
    if (!pub) return;

    const comment = pub.comments.find(c => c.id === commentId);
    if (!comment) return;

    const newContent = prompt('Modifier le commentaire :', comment.content);
    if (newContent !== null && newContent.trim()) {
      this.updateComment(pubId, commentId, newContent.trim());

      const feedContainer = document.getElementById('pub-feed-container');
      const profileContainer = document.getElementById('profile-pub-feed');

      if (feedContainer) this.renderFeed('pub-feed-container');
      if (profileContainer) {
        const user = this.getCurrentUser();
        if (user) this.renderFeed('profile-pub-feed', user.id);
      }

      setTimeout(() => {
        const section = document.getElementById(`pub-comments-section-${pubId}`);
        if (section) section.style.display = 'block';
      }, 50);
    }
  },

  // --- Edit Publication Modal ---
  openEditModal(pubId) {
    const publications = this.getAll();
    const pub = publications.find(p => p.id === pubId);
    if (!pub) return;

    // Remove old overlay if exists
    const old = document.getElementById('pub-edit-overlay');
    if (old) old.remove();

    const overlay = document.createElement('div');
    overlay.id = 'pub-edit-overlay';
    overlay.className = 'pub-edit-overlay';
    overlay.innerHTML = `
      <div class="pub-edit-modal">
        <div class="pub-edit-modal-header">
          <h3><i class="fa-solid fa-pen-to-square"></i> Modifier la publication</h3>
          <button class="pub-edit-close" onclick="Publications.closeEditModal()">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
        <div class="pub-edit-modal-body">
          <textarea class="pub-edit-textarea" id="pub-edit-content">${this.escapeHtml(pub.content)}</textarea>
        </div>
        <div class="pub-edit-modal-footer">
          <button class="btn btn-secondary btn-sm" onclick="Publications.closeEditModal()">Annuler</button>
          <button class="btn btn-primary btn-sm" onclick="Publications.saveEdit(${pub.id})">
            <i class="fa-solid fa-check"></i> Enregistrer
          </button>
        </div>
      </div>
    `;

    document.body.appendChild(overlay);
    document.body.style.overflow = 'hidden';

    // Animate in
    requestAnimationFrame(() => {
      overlay.classList.add('active');
      document.getElementById('pub-edit-content')?.focus();
    });

    // Close on click outside
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) this.closeEditModal();
    });
  },

  closeEditModal() {
    const overlay = document.getElementById('pub-edit-overlay');
    if (overlay) {
      overlay.classList.remove('active');
      document.body.style.overflow = '';
      setTimeout(() => overlay.remove(), 300);
    }
  },

  saveEdit(pubId) {
    const textarea = document.getElementById('pub-edit-content');
    if (!textarea) return;

    const content = textarea.value.trim();
    if (!content) {
      Components.showToast('Erreur', 'Le contenu ne peut pas être vide.', 'warning');
      return;
    }

    this.updatePublication(pubId, content);
    this.closeEditModal();

    const feedContainer = document.getElementById('pub-feed-container');
    const profileContainer = document.getElementById('profile-pub-feed');

    if (feedContainer) this.renderFeed('pub-feed-container');
    if (profileContainer) {
      const user = this.getCurrentUser();
      if (user) this.renderFeed('profile-pub-feed', user.id);
    }
  },

  // --- Delete Confirmation ---
  confirmDelete(pubId) {
    Components.confirm(
      'Supprimer la publication',
      'Êtes-vous sûr de vouloir supprimer cette publication ? Cette action est irréversible.',
      () => {
        this.deletePublication(pubId);

        const feedContainer = document.getElementById('pub-feed-container');
        const profileContainer = document.getElementById('profile-pub-feed');

        if (feedContainer) this.renderFeed('pub-feed-container');
        if (profileContainer) {
          const user = this.getCurrentUser();
          if (user) this.renderFeed('profile-pub-feed', user.id);
        }
      }
    );
  },

  // --- Helper ---
  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  },

  // ============================================
  // INITIALIZATION
  // ============================================

  // Init full page (Publications page)
  initPage() {
    this.seedIfEmpty();
    this.renderCompose('pub-compose-container');
    this.renderFeed('pub-feed-container');
  },

  // Init profile section (only user's publications)
  initProfileSection() {
    this.seedIfEmpty();
    const user = this.getCurrentUser();
    if (!user) return;
    this.renderFeed('profile-pub-feed', user.id);
  }
};
