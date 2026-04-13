const Publications = {
  getCurrentUser() {
    return App.getCurrentUser();
  },

  normalizeRole(role) {
    const value = String(role || '').toLowerCase();
    if (value === 'etudiant') return 'student';
    if (value === 'partenaire') return 'partner';
    if (value === 'admin' || value === 'administrateur') return 'admin';
    return value || 'student';
  },

  getBackendAuthorType(role) {
    const value = String(role || '').toLowerCase();
    if (value === 'student') return 'etudiant';
    if (value === 'partner') return 'partenaire';
    if (value === 'admin') return 'admin';
    return 'etudiant';
  },

  getSafeUserId() {
    const user = this.getCurrentUser();

    if (user && user.id !== undefined && user.id !== null && user.id !== '') {
      return user.id;
    }

    return 1;
  },

  getAuthorName(auteurType, auteurId) {
    const role = this.normalizeRole(auteurType);
    const currentUser = this.getCurrentUser();

    if (currentUser) {
      const currentRole = this.normalizeRole(currentUser.role);
      
      if (String(currentUser.id) === String(auteurId) && currentRole === role) {
        return currentUser.name;
      }

      if (currentRole === role && (String(auteurId) === '0' || String(auteurId) === '1' || String(auteurId).trim() === '')) {
        return currentUser.name;
      }
    }

    if (role === 'student') return `Étudiant #${auteurId}`;
    if (role === 'partner') return `Partenaire #${auteurId}`;
    if (role === 'admin') return `Administrateur #${auteurId}`;
    return `Utilisateur #${auteurId}`;
  },

  async fetchCommentsByPublicationId(publicationId) {
    try {
      const response = await fetch(`/webEsprit/Controller/CommentController.php?action=list&id_publication=${publicationId}`);
      if (!response.ok) throw new Error('Erreur commentaires');

      const data = await response.json();

      return data.map(comment => ({
        id: Number(comment.id_commentaire),
        authorId: String(comment.auteur_id),
        authorName: this.getAuthorName(comment.auteur_type, comment.auteur_id),
        authorRole: this.normalizeRole(comment.auteur_type),
        content: comment.contenu_commentaire,
        createdAt: comment.date_commentaire
          ? comment.date_commentaire.replace(' ', 'T')
          : new Date().toISOString()
      }));
    } catch (error) {
      console.error('Erreur fetchCommentsByPublicationId:', error);
      return [];
    }
  },

  async getAll() {
    try {
      const response = await fetch('/webEsprit/Controller/PublicationController.php?action=list');
      if (!response.ok) throw new Error('Erreur publications');

      const data = await response.json();

      const publications = await Promise.all(
        data.map(async (pub) => {
          const comments = await this.fetchCommentsByPublicationId(pub.id_publication);

          return {
            id: Number(pub.id_publication),
            authorId: String(pub.auteur_id),
            authorName: this.getAuthorName(pub.auteur_type, pub.auteur_id),
            authorRole: this.normalizeRole(pub.auteur_type),
            content: pub.contenu_publication,
            createdAt: pub.date_publication
              ? pub.date_publication.replace(' ', 'T')
              : new Date().toISOString(),
            comments: comments
          };
        })
      );

      return publications;
    } catch (error) {
      console.error('Erreur getAll:', error);
      Components.showToast('Erreur', 'Impossible de charger les publications.', 'danger');
      return [];
    }
  },

  async addPublication(content) {
    const user = this.getCurrentUser();
    if (!user || !content.trim()) return false;

    try {
      const formData = new FormData();
      formData.append('contenu_publication', content.trim());
      formData.append('auteur_type', this.getBackendAuthorType(user.role));
      formData.append('auteur_id', this.getSafeUserId());

      const response = await fetch('/webEsprit/Controller/PublicationController.php?action=create', {
        method: 'POST',
        body: formData
      });

      if (!response.ok) throw new Error('Erreur create publication');

      const result = await response.json();
      if (result.success) {
        Components.showToast('Publication créée', 'Votre publication a été enregistrée avec succès.', 'success');
        return true;
      }

      Components.showToast('Erreur', 'La publication n’a pas pu être enregistrée.', 'danger');
      return false;
    } catch (error) {
      console.error('Erreur addPublication:', error);
      Components.showToast('Erreur', 'Impossible d’enregistrer la publication.', 'danger');
      return false;
    }
  },

  async updatePublication(pubId, newContent) {
    if (!newContent || !newContent.trim()) return false;

    try {
      const formData = new FormData();
      formData.append('id_publication', pubId);
      formData.append('contenu_publication', newContent.trim());

      const response = await fetch('/webEsprit/Controller/PublicationController.php?action=update', {
        method: 'POST',
        body: formData
      });

      if (!response.ok) throw new Error('Erreur update publication');

      const result = await response.json();
      if (result.success) {
        Components.showToast('Publication modifiée', 'Votre publication a été mise à jour.', 'success');
        return true;
      }

      Components.showToast('Erreur', 'La publication n’a pas pu être modifiée.', 'danger');
      return false;
    } catch (error) {
      console.error('Erreur updatePublication:', error);
      Components.showToast('Erreur', 'Impossible de modifier la publication.', 'danger');
      return false;
    }
  },

  async deletePublication(pubId) {
    try {
      const formData = new FormData();
      formData.append('id_publication', pubId);

      const response = await fetch('/webEsprit/Controller/PublicationController.php?action=delete', {
        method: 'POST',
        body: formData
      });

      if (!response.ok) throw new Error('Erreur delete publication');

      const result = await response.json();
      if (result.success) {
        Components.showToast('Publication supprimée', 'La publication a été supprimée avec succès.', 'success');
        return true;
      }

      Components.showToast('Erreur', 'La publication n’a pas pu être supprimée.', 'danger');
      return false;
    } catch (error) {
      console.error('Erreur deletePublication:', error);
      Components.showToast('Erreur', 'Impossible de supprimer la publication.', 'danger');
      return false;
    }
  },

  async addComment(pubId, content) {
    const user = this.getCurrentUser();
    if (!user || !content.trim()) return false;

    try {
      const formData = new FormData();
      formData.append('contenu_commentaire', content.trim());
      formData.append('id_publication', pubId);
      formData.append('auteur_type', this.getBackendAuthorType(user.role));
      formData.append('auteur_id', this.getSafeUserId());

      const response = await fetch('/webEsprit/Controller/CommentController.php?action=create', {
        method: 'POST',
        body: formData
      });

      if (!response.ok) throw new Error('Erreur create commentaire');

      const result = await response.json();
      if (result.success) {
        Components.showToast('Commentaire ajouté', 'Votre commentaire a été enregistré avec succès.', 'success');
        return true;
      }

      Components.showToast('Erreur', 'Le commentaire n’a pas pu être enregistré.', 'danger');
      return false;
    } catch (error) {
      console.error('Erreur addComment:', error);
      Components.showToast('Erreur', 'Impossible d’enregistrer le commentaire.', 'danger');
      return false;
    }
  },

  async updateComment(commentId, newContent) {
    if (!newContent || !newContent.trim()) return false;

    try {
      const formData = new FormData();
      formData.append('id_commentaire', commentId);
      formData.append('contenu_commentaire', newContent.trim());

      const response = await fetch('/webEsprit/Controller/CommentController.php?action=update', {
        method: 'POST',
        body: formData
      });

      if (!response.ok) throw new Error('Erreur update commentaire');

      const result = await response.json();
      if (result.success) {
        Components.showToast('Commentaire modifié', 'Votre commentaire a été mis à jour.', 'success');
        return true;
      }

      Components.showToast('Erreur', 'Le commentaire n’a pas pu être modifié.', 'danger');
      return false;
    } catch (error) {
      console.error('Erreur updateComment:', error);
      Components.showToast('Erreur', 'Impossible de modifier le commentaire.', 'danger');
      return false;
    }
  },

  async deleteComment(commentId) {
    try {
      const formData = new FormData();
      formData.append('id_commentaire', commentId);

      const response = await fetch('/webEsprit/Controller/CommentController.php?action=delete', {
        method: 'POST',
        body: formData
      });

      if (!response.ok) throw new Error('Erreur delete commentaire');

      const result = await response.json();
      if (result.success) {
        Components.showToast('Commentaire supprimé', 'Le commentaire a été supprimé avec succès.', 'success');
        return true;
      }

      Components.showToast('Erreur', 'Le commentaire n’a pas pu être supprimé.', 'danger');
      return false;
    } catch (error) {
      console.error('Erreur deleteComment:', error);
      Components.showToast('Erreur', 'Impossible de supprimer le commentaire.', 'danger');
      return false;
    }
  },

  canEditPublication(pub) {
    const user = this.getCurrentUser();
    if (!user) return false;

    if (this.normalizeRole(user.role) === 'admin') return true;

    const sameId =
      user.id !== undefined &&
      user.id !== null &&
      String(pub.authorId) === String(user.id);

    const sameName =
      user.name &&
      pub.authorName &&
      String(pub.authorName).trim().toLowerCase() === String(user.name).trim().toLowerCase();

    const sameRole =
      user.role &&
      pub.authorRole &&
      this.normalizeRole(pub.authorRole) === this.normalizeRole(user.role);

    const unknownAuthorId = String(pub.authorId) === '0' || String(pub.authorId) === '1' || String(pub.authorId).trim() === '';

    return sameId || (sameName && sameRole) || (unknownAuthorId && sameRole);
  },

  canEditComment(comment) {
    const user = this.getCurrentUser();
    if (!user) return false;

    if (this.normalizeRole(user.role) === 'admin') return true;

    const sameId =
      user.id !== undefined &&
      user.id !== null &&
      String(comment.authorId) === String(user.id);

    const sameName =
      user.name &&
      comment.authorName &&
      String(comment.authorName).trim().toLowerCase() === String(user.name).trim().toLowerCase();

    const sameRole =
      user.role &&
      comment.authorRole &&
      this.normalizeRole(comment.authorRole) === this.normalizeRole(user.role);

    const unknownAuthorId = String(comment.authorId) === '0' || String(comment.authorId) === '1' || String(comment.authorId).trim() === '';

    return sameId || (sameName && sameRole) || (unknownAuthorId && sameRole);
  },

  getRoleLabel(role) {
    const labels = {
      student: 'Étudiant',
      partner: 'Partenaire',
      admin: 'Administrateur'
    };
    return labels[role] || role;
  },

  getRoleIcon(role) {
    const icons = {
      student: 'fa-graduation-cap',
      partner: 'fa-store',
      admin: 'fa-shield-halved'
    };
    return icons[role] || 'fa-user';
  },

  timeAgo(dateStr) {
    return App.timeAgo(dateStr);
  },

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

  async handlePublish() {
    const textarea = document.getElementById('pub-compose-text');
    if (!textarea) return;

    const content = textarea.value.trim();
    if (!content) {
      Components.showToast('Erreur', 'Veuillez écrire quelque chose avant de publier.', 'warning');
      return;
    }

    const success = await this.addPublication(content);
    if (success) {
      textarea.value = '';
      await this.refreshFeed();
    }
  },

  isPublicationOwnedByCurrentUser(pub) {
    const user = this.getCurrentUser();
    if (!user) return false;

    if (user.id !== undefined && user.id !== null && String(pub.authorId) === String(user.id)) {
      return true;
    }

    if (user.name && pub.authorName) {
      const uName = String(user.name).trim().toLowerCase();
      const pName = String(pub.authorName).trim().toLowerCase();
      
      const uRole = this.normalizeRole(user.role);
      const pRole = this.normalizeRole(pub.authorRole);
      
      if (uName === pName && uRole === pRole) {
        return true;
      }
    }
    
    const isMockId = String(pub.authorId) === '0' || String(pub.authorId) === '1' || String(pub.authorId).trim() === '';
    const sameRole = this.normalizeRole(user.role) === this.normalizeRole(pub.authorRole);
    if (isMockId && sameRole) {
      return true;
    }
    
    return false;
  },

  async renderFeed(containerId, filterUserId = null) {
    const container = document.getElementById(containerId);
    if (!container) return;

    let publications = await this.getAll();

    if (filterUserId) {
      publications = publications.filter(p => this.isPublicationOwnedByCurrentUser(p));
    }

    const totalPubs = publications.length;
    const totalComments = publications.reduce((sum, p) => sum + p.comments.length, 0);

    let html = '';

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
          <p>${filterUserId ? "Vous n'avez pas encore publié. Partagez vos expériences avec la communauté !" : 'Soyez le premier à partager quelque chose avec la communauté CareMeal !'}</p>
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

  renderCard(pub) {
    const user = this.getCurrentUser();
    const initials = App.getInitials(pub.authorName);
    const canEdit = this.canEditPublication(pub);
    const commentsCount = pub.comments.length;
    const userInitials = user ? App.getInitials(user.name) : '?';

    return `
      <div class="pub-card" id="pub-${pub.id}">
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

        <div class="pub-card-body">
          <div class="pub-card-content">${this.escapeHtml(pub.content)}</div>
        </div>

        <div class="pub-card-footer">
          <button class="pub-engagement-btn" onclick="Publications.toggleComments(${pub.id})">
            <i class="fa-regular fa-comment"></i> ${commentsCount} commentaire${commentsCount !== 1 ? 's' : ''}
          </button>
        </div>

        <div class="pub-comments-section" id="pub-comments-section-${pub.id}" style="display:none;">
          ${commentsCount > 0 ? `
            <div class="pub-comments-list" id="pub-comments-list-${pub.id}">
              ${pub.comments.map(c => this.renderComment(pub.id, c)).join('')}
            </div>
          ` : ''}
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

  toggleComments(pubId) {
    const section = document.getElementById(`pub-comments-section-${pubId}`);
    if (!section) return;

    if (section.style.display === 'none') {
      section.style.display = 'block';
      const input = section.querySelector('.pub-comment-input');
      if (input) input.focus();
    } else {
      section.style.display = 'none';
    }
  },

  async handleAddComment(e, pubId) {
    e.preventDefault();

    const input = e.target.querySelector('.pub-comment-input');
    if (!input) return;

    const content = input.value.trim();
    if (!content) return;

    const success = await this.addComment(pubId, content);

    if (success) {
      input.value = '';
      await this.refreshFeed();

      setTimeout(() => {
        const section = document.getElementById(`pub-comments-section-${pubId}`);
        if (section) section.style.display = 'block';
      }, 50);
    }
  },

  async handleDeleteComment(pubId, commentId) {
    Components.confirm(
      'Supprimer le commentaire',
      'Êtes-vous sûr de vouloir supprimer ce commentaire ?',
      async () => {
        const success = await this.deleteComment(commentId);

        if (success) {
          await this.refreshFeed();

          setTimeout(() => {
            const section = document.getElementById(`pub-comments-section-${pubId}`);
            if (section) section.style.display = 'block';
          }, 50);
        }
      }
    );
  },

  async editCommentPrompt(pubId, commentId) {
    const publications = await this.getAll();
    const pub = publications.find(p => p.id === pubId);
    if (!pub) return;

    const comment = pub.comments.find(c => c.id === commentId);
    if (!comment) return;

    const newContent = prompt('Modifier le commentaire :', comment.content);
    if (newContent === null) return;
    if (!newContent.trim()) {
      Components.showToast('Erreur', 'Le commentaire ne peut pas être vide.', 'warning');
      return;
    }

    const success = await this.updateComment(commentId, newContent.trim());

    if (success) {
      await this.refreshFeed();

      setTimeout(() => {
        const section = document.getElementById(`pub-comments-section-${pubId}`);
        if (section) section.style.display = 'block';
      }, 50);
    }
  },

  openEditModal(pubId) {
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
          <textarea class="pub-edit-textarea" id="pub-edit-content"></textarea>
        </div>
        <div class="pub-edit-modal-footer">
          <button class="btn btn-secondary btn-sm" onclick="Publications.closeEditModal()">Annuler</button>
          <button class="btn btn-primary btn-sm" onclick="Publications.saveEdit(${pubId})">
            <i class="fa-solid fa-check"></i> Enregistrer
          </button>
        </div>
      </div>
    `;

    document.body.appendChild(overlay);
    document.body.style.overflow = 'hidden';

    this.getAll().then(publications => {
      const pub = publications.find(p => p.id === pubId);
      const textarea = document.getElementById('pub-edit-content');
      if (pub && textarea) {
        textarea.value = pub.content;
      }
    });

    requestAnimationFrame(() => {
      overlay.classList.add('active');
      document.getElementById('pub-edit-content')?.focus();
    });

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

  async saveEdit(pubId) {
    const textarea = document.getElementById('pub-edit-content');
    if (!textarea) return;

    const content = textarea.value.trim();
    if (!content) {
      Components.showToast('Erreur', 'Le contenu ne peut pas être vide.', 'warning');
      return;
    }

    const success = await this.updatePublication(pubId, content);

    if (success) {
      this.closeEditModal();
      await this.refreshFeed();
    }
  },

  confirmDelete(pubId) {
    Components.confirm(
      'Supprimer la publication',
      'Êtes-vous sûr de vouloir supprimer cette publication ? Cette action est irréversible.',
      async () => {
        const success = await this.deletePublication(pubId);
        if (success) {
          await this.refreshFeed();
        }
      }
    );
  },

  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  },

  async initPage() {
    this.renderCompose('pub-compose-container');
    await this.renderFeed('pub-feed-container');
  },

  async initProfileSection() {
    const user = this.getCurrentUser();
    if (!user) return;
    await this.renderFeed('profile-pub-feed', user.id || 'current');
  },

  async refreshFeed() {
    if (document.getElementById('profile-pub-feed')) {
      const user = this.getCurrentUser();
      if (user) {
        await this.renderFeed('profile-pub-feed', user.id || 'current');
      }
    } else if (document.getElementById('pub-feed-container')) {
      await this.renderFeed('pub-feed-container');
    }
  }
};