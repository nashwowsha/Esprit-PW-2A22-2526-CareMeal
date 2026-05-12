const Publications = {
  /* -------- État interne pour search / filtre / tri -------- */
  _state: {
    search: '',
    roleFilter: 'all',
    sort: 'recent',
    statsLoaded: false,
    statsVisible: false,
    isPublishing: false,
    isCorrectingText: false,
    isVoiceListening: false,
    isVoiceTranscribing: false,
    manualVoiceStop: false,
    mediaRecorder: null,
    voiceStream: null,
    voiceChunks: [],
    commentSubmittingByPublication: {}
  },

  getCurrentUser() {
    return App.getCurrentUser();
  },

  normalizeToastText(value, fallback) {
    const text = typeof value === 'string' ? value.trim() : '';
    return text || fallback;
  },

  showSafeToast(title, message, type = 'info') {
    const safeTitle = this.normalizeToastText(title, 'Erreur');
    const safeMessage = this.normalizeToastText(message, 'Une erreur est survenue.');
    const normalizedType = type === 'danger' ? 'error' : type;
    Components.showToast(safeTitle, safeMessage, normalizedType);
  },

  getPreciseAIErrorMessage(errorCode, fallbackMessage, provider = 'gemini') {
    const code = String(errorCode || '').trim().toLowerCase();
    const fallback = this.normalizeToastText(fallbackMessage, 'Service IA indisponible.');
    const apiLabel = provider === 'groq' ? 'API Groq' : 'API Gemini';

    if (code === 'quota_exceeded') {
      return 'Quota atteint. Veuillez réessayer plus tard.';
    }
    if (code === 'curl_error') {
      return 'Problème réseau ou DNS. Vérifiez votre connexion puis réessayez.';
    }
    if (code === 'http_error' || code === 'service_error' || code === 'invalid_api_response' || code === 'empty_candidate') {
      return `${apiLabel} indisponible. Veuillez réessayer.`;
    }
    if (code === 'invalid_api_key') {
      return 'Configuration API invalide. Contactez l administrateur.';
    }

    return fallback;
  },

  isAdminArea() {
    return window.location.pathname.includes('/admin/');
  },

  getCurrentPageName() {
    return (window.location.pathname.split('/').pop() || '').split('?')[0].split('#')[0];
  },

  getEditPublicationIdFromUrl() {
    const params = new URLSearchParams(window.location.search || '');
    const idRaw = params.get('id');
    const id = Number(idRaw);
    return Number.isFinite(id) && id > 0 ? id : 0;
  },

  normalizeRole(role) {
    const value = String(role || '').toLowerCase().trim();
    if (value === 'etudiant' || value === 'student') return 'student';
    if (value === 'partenaire' || value === 'partner') return 'partner';
    if (value === 'admin' || value === 'administrateur') return 'admin';
    return 'unknown'; // jamais 'student' par défaut — évite les faux positifs
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
    const roleFallback =
      role === 'student' ? 'Étudiant' :
      role === 'partner' ? 'Partenaire' :
      role === 'admin' ? 'Administrateur' : 'Utilisateur';

    const normalizeDisplayName = (value) => {
      const v = String(value || '').replace(/\s+/g, ' ').trim();
      return v.length >= 2 ? v : '';
    };

    const normalizeId = (value) => {
      const raw = String(value ?? '').trim();
      const num = (raw.match(/\d+/) || [])[0] || '';
      return { raw, num };
    };

    const sameId = (a, b) => {
      const ia = normalizeId(a);
      const ib = normalizeId(b);
      if (!ia.raw || !ib.raw) return false;
      if (ia.raw === ib.raw) return true;
      return ia.num && ib.num && ia.num === ib.num;
    };

    // 1. Chercher dans le vrai registre d'utilisateurs (localStorage)
    if (auteurId !== undefined && auteurId !== null && String(auteurId).trim() !== '' && String(auteurId) !== '0') {
      const users = typeof App.getUsers === 'function' ? App.getUsers() : [];
      const found = users.find((u) =>
        sameId(u?.id, auteurId) ||
        sameId(u?.user_id, auteurId)
      );
      if (found) {
        const fullName = normalizeDisplayName(`${found.prenom || ''} ${found.nom || ''}`);
        const simpleName = normalizeDisplayName(found.name);
        const labelName = normalizeDisplayName(found.etablissement || found.type || found.email);
        return fullName || simpleName || labelName || roleFallback;
      }
    }

    // 2. Fallback : l'utilisateur courant si les IDs correspondent
    const currentUser = this.getCurrentUser();
    if (
      currentUser &&
      sameId(currentUser.id, auteurId) &&
      currentUser.role === role
    ) {
      const fullName = normalizeDisplayName(`${currentUser.prenom || ''} ${currentUser.nom || ''}`);
      const simpleName = normalizeDisplayName(currentUser.name);
      return fullName || simpleName || roleFallback;
    }

    // 3. Fallback propre selon le rôle
    const safeId = String(auteurId ?? '').trim();
    if (safeId && safeId !== '0') {
      return `${roleFallback} #${safeId}`;
    }
    return roleFallback;
  },

  async fetchCommentsByPublicationId(publicationId) {
    try {
      const response = await fetch(`/Controller/CommentController.php?action=list&id_publication=${publicationId}`);
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

  async fetchAllReactions() {
    try {
      const response = await fetch(App.apiUrl('Controller/PublicationController.php?action=list_reactions'));
      if (!response.ok) throw new Error('Erreur récupération réactions');
      const data = await response.json();
      return data;
    } catch (error) {
      console.error('Erreur fetchAllReactions:', error);
      return [];
    }
  },

  async fetchAllCommentReactions() {
    try {
      const response = await fetch(App.apiUrl('Controller/CommentController.php?action=list_reactions'));
      if (!response.ok) throw new Error('Erreur récupération réactions commentaires');
      const data = await response.json();
      return data;
    } catch (error) {
      console.error('Erreur fetchAllCommentReactions:', error);
      return [];
    }
  },


  async getAll() {
    try {
      const response = await fetch(App.apiUrl('Controller/PublicationController.php?action=list'));
      if (!response.ok) throw new Error('Erreur publications');

      const data = await response.json();
      const allReactions = await this.fetchAllReactions();
      const allCommentReactions = await this.fetchAllCommentReactions();

      const publications = await Promise.all(
        data.map(async (pub) => {
          const rawComments = await this.fetchCommentsByPublicationId(pub.id_publication);
          const comments = rawComments.map(c => {
            c.reactions = allCommentReactions.filter(r => String(r.commentaire_id) === String(c.id));
            return c;
          });
          const reactions = allReactions.filter(r => String(r.publication_id) === String(pub.id_publication));

          return {
            id: Number(pub.id_publication),
            authorId: String(pub.auteur_id),
            authorName: this.getAuthorName(pub.auteur_type, pub.auteur_id),
            authorRole: this.normalizeRole(pub.auteur_type),
            content: pub.contenu_publication || '',
            imagePath: pub.image_path || '',
            imageUrl: this.normalizeImageUrl(pub.image_url || pub.image_path || ''),
            moderation: {
              status: String(pub.moderation_status || 'approved'),
              risk: pub.moderation_risk ? String(pub.moderation_risk) : '',
              reason: pub.moderation_reason ? String(pub.moderation_reason) : '',
              provider: String(pub.moderation_provider || 'gemini')
            },
            createdAt: pub.date_publication
              ? pub.date_publication.replace(' ', 'T')
              : new Date().toISOString(),
            comments: comments,
            reactions: reactions
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

  async addPublication(content, imageFile = null) {
    const user = this.getCurrentUser();
    if (!user) return false;

    try {
      const formData = new FormData();
      formData.append('contenu_publication', String(content || '').trim());
      formData.append('auteur_type', this.getBackendAuthorType(user.role));
      formData.append('auteur_id', this.getSafeUserId());
      if (imageFile) {
        formData.append('image_publication', imageFile);
      }

      const response = await fetch(App.apiUrl('Controller/PublicationController.php?action=create'), {
        method: 'POST',
        body: formData
      });

      let result = {};
      try {
        result = await response.json();
      } catch (parseError) {
        result = {};
      }

      if (!response.ok) {
        const backendMessage =
          result && typeof result.message === 'string' && result.message.trim()
            ? result.message.trim()
            : 'La publication n’a pas pu être enregistrée.';
        this.showSafeToast('Erreur', backendMessage, 'danger');
        return false;
      }

      if (result.success) {
        this.showSafeToast('Publication créée', 'Votre publication a été enregistrée avec succès.', 'success');
        return true;
      }

      if (result && (result.code === 'MODERATION_REJECTED')) {
        this.showSafeToast('Publication refusée', 'Publication refusée : contenu inapproprié détecté.', 'danger');
        return false;
      }

      if (result && (result.code === 'MODERATION_UNAVAILABLE' || result.code === 'QUOTA_EXCEEDED')) {
        const unavailableMessage =
          result && typeof result.message === 'string' && result.message.trim()
            ? result.message.trim()
            : 'Modération IA indisponible. Veuillez réessayer.';
        this.showSafeToast('Erreur', unavailableMessage, 'danger');
        return false;
      }

      const safeMessage =
        result && typeof result.message === 'string' && result.message.trim()
          ? result.message.trim()
          : 'La publication n’a pas pu être enregistrée.';
      this.showSafeToast('Erreur', safeMessage, 'danger');
      return false;
    } catch (error) {
      console.error('Erreur addPublication:', error);
      this.showSafeToast('Erreur', 'Impossible d’enregistrer la publication.', 'danger');
      return false;
    }
  },

  async updatePublication(pubId, newContent, imageFile = null, removeImage = false) {
    try {
      const formData = new FormData();
      formData.append('id_publication', pubId);
      formData.append('contenu_publication', String(newContent || '').trim());
      formData.append('remove_image', removeImage ? '1' : '0');
      if (imageFile) {
        formData.append('image_publication', imageFile);
      }

      const response = await fetch(App.apiUrl('Controller/PublicationController.php?action=update'), {
        method: 'POST',
        body: formData
      });

      if (!response.ok) throw new Error('Erreur update publication');

      const result = await response.json();
      if (result.success) {
        Components.showToast('Publication modifiée', 'Votre publication a été mise à jour.', 'success');
        return true;
      }

      Components.showToast('Erreur', result.message || 'La publication n’a pas pu être modifiée.', 'danger');
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

      const response = await fetch(App.apiUrl('Controller/PublicationController.php?action=delete'), {
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

      const response = await fetch(App.apiUrl('Controller/CommentController.php?action=create'), {
        method: 'POST',
        body: formData
      });

      let result = {};
      try {
        result = await response.json();
      } catch (parseError) {
        result = {};
      }

      if (!response.ok) {
        const backendMessage =
          result && typeof result.message === 'string' && result.message.trim()
            ? result.message.trim()
            : 'Le commentaire n’a pas pu être enregistré.';
        this.showSafeToast('Erreur', backendMessage, 'danger');
        return false;
      }

      if (result.success) {
        this.showSafeToast('Commentaire ajouté', 'Votre commentaire a été enregistré avec succès.', 'success');
        return true;
      }

      if (result && result.code === 'MODERATION_REJECTED') {
        this.showSafeToast('Commentaire refusé', 'Commentaire refusé : contenu inapproprié détecté.', 'danger');
        return false;
      }

      if (result && (result.code === 'MODERATION_UNAVAILABLE' || result.code === 'QUOTA_EXCEEDED')) {
        const unavailableMessage =
          result && typeof result.message === 'string' && result.message.trim()
            ? result.message.trim()
            : 'Modération IA indisponible. Veuillez réessayer.';
        this.showSafeToast('Erreur', unavailableMessage, 'danger');
        return false;
      }

      const safeMessage =
        result && typeof result.message === 'string' && result.message.trim()
          ? result.message.trim()
          : 'Le commentaire n’a pas pu être enregistré.';
      this.showSafeToast('Erreur', safeMessage, 'danger');
      return false;
    } catch (error) {
      console.error('Erreur addComment:', error);
      this.showSafeToast('Erreur', 'Impossible d’enregistrer le commentaire.', 'danger');
      return false;
    }
  },

  async updateComment(commentId, newContent) {
    if (!newContent || !newContent.trim()) return false;

    try {
      const formData = new FormData();
      formData.append('id_commentaire', commentId);
      formData.append('contenu_commentaire', newContent.trim());

      const response = await fetch(App.apiUrl('Controller/CommentController.php?action=update'), {
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

      const response = await fetch(App.apiUrl('Controller/CommentController.php?action=delete'), {
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

    if (user.role === 'admin') return true;

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
      String(pub.authorRole).trim().toLowerCase() === String(user.role).trim().toLowerCase();

    const unknownAuthorId = String(pub.authorId) === '0' || String(pub.authorId).trim() === '';

    return sameId || (sameName && sameRole) || (unknownAuthorId && sameRole);
  },

  canEditComment(comment) {
    const user = this.getCurrentUser();
    if (!user) return false;

    if (user.role === 'admin') return true;

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
      String(comment.authorRole).trim().toLowerCase() === String(user.role).trim().toLowerCase();

    const unknownAuthorId = String(comment.authorId) === '0' || String(comment.authorId).trim() === '';

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

  getAllowedImageMimeTypes() {
    return ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
  },

  getMaxImageSizeBytes() {
    return 5 * 1024 * 1024;
  },

  getUploadHelperText() {
    return 'JPG, PNG, WEBP ou GIF • 5 Mo max';
  },

  formatFileSize(bytes) {
    if (!bytes || bytes < 1024) return `${bytes || 0} o`;
    const kb = bytes / 1024;
    if (kb < 1024) return `${kb.toFixed(1)} Ko`;
    return `${(kb / 1024).toFixed(1)} Mo`;
  },

  validatePublicationPayload(content, imageFile) {
    const trimmed = String(content || '').trim();
    if (!trimmed && !imageFile) {
      return { valid: false, message: 'Ajoutez du texte ou une image avant de publier.' };
    }

    if (trimmed.length > 500) {
      return { valid: false, message: 'La publication ne doit pas dépasser 500 caractères.' };
    }

    if (imageFile) {
      const allowed = this.getAllowedImageMimeTypes();
      if (!allowed.includes(imageFile.type)) {
        return { valid: false, message: 'Format d’image invalide (JPG, PNG, WEBP, GIF).' };
      }

      if (imageFile.size > this.getMaxImageSizeBytes()) {
        return { valid: false, message: 'L’image ne doit pas dépasser 5 Mo.' };
      }
    }

    return { valid: true, message: '' };
  },

  normalizeImageUrl(rawPath) {
    const value = String(rawPath || '').trim();
    if (!value) return '';
    if (value.startsWith('http://') || value.startsWith('https://')) return value;
    if (value.startsWith('/')) return value;
    if (value.startsWith('public/')) return `/${value}`;
    return `/${value.replace(/^\/+/, '')}`;
  },

  updateCharCount(textarea, counterId, limit) {
    const el = document.getElementById(counterId);
    if (!el || !textarea) return;
    const current = textarea.value.length;
    el.textContent = `${current} / ${limit}`;
    
    if (current > limit) {
      el.style.color = '#ef4444'; // red
      textarea.style.borderColor = '#ef4444';
    } else {
      el.style.color = '#6b7280'; // gray
      textarea.style.borderColor = '';
    }
  },

  supportsGroqSttRecording() {
    return !!(window.MediaRecorder && navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
  },

  getPreferredRecorderMimeType() {
    if (!window.MediaRecorder || typeof MediaRecorder.isTypeSupported !== 'function') {
      return '';
    }

    const candidates = [
      'audio/webm;codecs=opus',
      'audio/webm',
      'audio/mp4',
      'audio/ogg;codecs=opus'
    ];

    for (const type of candidates) {
      if (MediaRecorder.isTypeSupported(type)) {
        return type;
      }
    }

    return '';
  },
  normalizeDictationText(rawText, ensureFinalPunctuation = false) {
    let text = String(rawText || '')
      .replace(/\s+/g, ' ')
      .replace(/\s+([,.;!?])/g, '$1')
      .trim();

    if (!text) return '';

    text = text.charAt(0).toUpperCase() + text.slice(1);

    if (ensureFinalPunctuation && !/[.!?]$/.test(text)) {
      text += '.';
    }

    return text;
  },

  updateAICorrectButtonState() {
    const button = document.getElementById('pub-ai-correct-btn');
    const textarea = document.getElementById('pub-compose-text');
    if (!button || !textarea) return;
    const hasText = textarea.value.trim().length > 0;
    button.disabled = this._state.isCorrectingText || !hasText;
  },

  applyBasicPublicationTextCleanup() {
    const textarea = document.getElementById('pub-compose-text');
    if (!textarea) return;

    const cleaned = this.normalizeDictationText(textarea.value, false);
    if (!cleaned) {
      textarea.value = '';
      this.updateCharCount(textarea, 'pub-char-count', 500);
      this.updateAICorrectButtonState();
      return;
    }

    textarea.value = cleaned;
    this.updateCharCount(textarea, 'pub-char-count', 500);
    this.updateAICorrectButtonState();
  },

  insertDictationIntoCompose(rawText) {
    const textarea = document.getElementById('pub-compose-text');
    if (!textarea) return;

    const normalized = this.normalizeDictationText(rawText, false);
    if (!normalized) return;

    const hasContent = textarea.value.trim().length > 0;
    const currentValue = textarea.value.replace(/\s+$/g, '');
    const separator = hasContent ? ' ' : '';
    textarea.value = `${currentValue}${separator}${normalized}`.trim();
    this.updateCharCount(textarea, 'pub-char-count', 500);
    this.updateAICorrectButtonState();
    textarea.focus();
  },

  setVoiceListeningState(isListening) {
    this._state.isVoiceListening = !!isListening;
    this.renderVoiceStatusUI();
  },

  setVoiceTranscribingState(isTranscribing) {
    this._state.isVoiceTranscribing = !!isTranscribing;
    this.renderVoiceStatusUI();
  },

  renderVoiceStatusUI() {
    const button = document.getElementById('pub-voice-btn');
    const buttonLabel = document.getElementById('pub-voice-btn-label');
    const status = document.getElementById('pub-voice-status');
    const isListening = !!this._state.isVoiceListening;
    const isTranscribing = !!this._state.isVoiceTranscribing;
    const isBusy = isListening || isTranscribing;

    if (button) {
      button.classList.toggle('is-listening', isListening);
      button.setAttribute('aria-pressed', isListening ? 'true' : 'false');
      button.disabled = isTranscribing;
      button.title = isListening ? 'Arrêter la dictée' : 'Démarrer la dictée';
    }

    if (buttonLabel) {
      if (isListening) {
        buttonLabel.textContent = 'Arrêter la dictée';
      } else if (isTranscribing) {
        buttonLabel.textContent = 'Transcription...';
      } else {
        buttonLabel.textContent = 'Démarrer la dictée';
      }
    }

    if (status) {
      if (isListening) {
        status.textContent = 'Ecoute en cours...';
      } else if (isTranscribing) {
        status.textContent = 'Transcription en cours...';
      } else {
        status.textContent = '';
      }
      status.style.display = isBusy ? 'inline-flex' : 'none';
    }
  },

  cleanupVoiceStream() {
    const stream = this._state.voiceStream;
    if (stream && typeof stream.getTracks === 'function') {
      stream.getTracks().forEach(track => {
        try { track.stop(); } catch (e) {}
      });
    }
    this._state.voiceStream = null;
  },

  stopVoiceRecognition() {
    this._state.manualVoiceStop = true;
    const recorder = this._state.mediaRecorder;
    if (!recorder) {
      this.setVoiceListeningState(false);
      this.setVoiceTranscribingState(false);
      return;
    }

    try {
      if (recorder.state !== 'inactive') {
        recorder.stop();
      }
    } catch (error) {
      this._state.mediaRecorder = null;
      this.cleanupVoiceStream();
      this.setVoiceListeningState(false);
      this.setVoiceTranscribingState(false);
    }
  },

  async transcribeRecordedAudio(blob) {
    const formData = new FormData();
    const fileExt = blob.type && blob.type.includes('ogg')
      ? 'ogg'
      : blob.type && blob.type.includes('mp4')
        ? 'mp4'
        : 'webm';
    formData.append('audio_file', blob, `dictation.${fileExt}`);

    const response = await fetch(App.apiUrl('Controller/PublicationController.php?action=ai_transcribe_audio'), {
      method: 'POST',
      body: formData
    });

    let result = {};
    try {
      result = await response.json();
    } catch (parseError) {
      result = {};
    }

    if (!response.ok || !result.success) {
      const baseMessage = result && typeof result.message === 'string' && result.message.trim()
        ? result.message.trim()
        : 'Transcription vocale indisponible. Veuillez réessayer.';
      const message = this.getPreciseAIErrorMessage(result?.error_code, baseMessage, 'groq');
      this.showSafeToast('Saisie vocale', message, 'danger');
      return;
    }

    const text = String(result.text || '').trim();
    if (!text) {
      this.showSafeToast('Saisie vocale', 'Aucun texte reconnu.', 'warning');
      return;
    }

    this.insertDictationIntoCompose(text);
    this.applyBasicPublicationTextCleanup();
  },

  async startVoiceRecognition() {
    if (!this.supportsGroqSttRecording()) {
      this.showSafeToast('Saisie vocale', "La saisie vocale n'est pas supportee par ce navigateur.", 'info');
      return;
    }

    if (this._state.isVoiceTranscribing) return;

    this._state.manualVoiceStop = false;

    let stream;
    try {
      stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    } catch (error) {
      const errorName = String(error?.name || '').toLowerCase();
      if (errorName === 'notallowederror' || errorName === 'securityerror') {
        this.showSafeToast('Saisie vocale', 'Microphone refusé. Autorisez l accès micro puis réessayez.', 'warning');
      } else if (errorName === 'notfounderror' || errorName === 'devicesnotfounderror') {
        this.showSafeToast('Saisie vocale', 'Aucun microphone detecte.', 'warning');
      } else {
        this.showSafeToast('Saisie vocale', "Impossible d'acceder au microphone.", 'warning');
      }
      return;
    }

    const mimeType = this.getPreferredRecorderMimeType();
    let recorder;
    try {
      recorder = mimeType ? new MediaRecorder(stream, { mimeType }) : new MediaRecorder(stream);
    } catch (error) {
      if (stream && typeof stream.getTracks === 'function') {
        stream.getTracks().forEach(track => {
          try { track.stop(); } catch (e) {}
        });
      }
      this.showSafeToast('Saisie vocale', "Impossible de démarrer l'enregistrement audio.", 'danger');
      return;
    }

    this._state.voiceStream = stream;
    this._state.mediaRecorder = recorder;
    this._state.voiceChunks = [];
    this.setVoiceTranscribingState(false);
    this.setVoiceListeningState(true);

    recorder.ondataavailable = (event) => {
      if (event.data && event.data.size > 0) {
        this._state.voiceChunks.push(event.data);
      }
    };

    recorder.onerror = () => {
      this._state.mediaRecorder = null;
      this.cleanupVoiceStream();
      this.setVoiceListeningState(false);
      this.showSafeToast('Saisie vocale', "Erreur pendant l'enregistrement audio.", 'danger');
    };

    recorder.onstop = async () => {
      const chunks = Array.isArray(this._state.voiceChunks) ? this._state.voiceChunks.slice() : [];
      this._state.voiceChunks = [];
      this._state.mediaRecorder = null;
      this.cleanupVoiceStream();
      this.setVoiceListeningState(false);

      if (chunks.length === 0) return;

      const blobType = recorder.mimeType || mimeType || 'audio/webm';
      const audioBlob = new Blob(chunks, { type: blobType });
      if (!audioBlob.size) return;

      const maxBytes = 25 * 1024 * 1024;
      if (audioBlob.size > maxBytes) {
        this.showSafeToast('Saisie vocale', 'Audio trop volumineux (25 Mo max).', 'danger');
        return;
      }

      this.setVoiceTranscribingState(true);
      try {
        await this.transcribeRecordedAudio(audioBlob);
      } finally {
        this.setVoiceTranscribingState(false);
      }
    };

    try {
      recorder.start(1000);
    } catch (error) {
      this._state.mediaRecorder = null;
      this.cleanupVoiceStream();
      this.setVoiceListeningState(false);
      this.showSafeToast('Saisie vocale', "Impossible de démarrer la dictée vocale.", 'danger');
    }
  },

  toggleVoiceInput() {
    const textarea = document.getElementById('pub-compose-text');
    if (!textarea) return;

    if (this._state.isVoiceListening) {
      this.stopVoiceRecognition();
      return;
    }

    this.startVoiceRecognition();
  },

  setAICorrectButtonLoading(isLoading) {
    const button = document.getElementById('pub-ai-correct-btn');
    if (!button) return;

    this._state.isCorrectingText = !!isLoading;
    if (isLoading) {
      button.dataset.originalHtml = button.innerHTML;
      button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Correction IA...';
      button.disabled = true;
      return;
    }

    if (button.dataset.originalHtml) {
      button.innerHTML = button.dataset.originalHtml;
      delete button.dataset.originalHtml;
    }
    this.updateAICorrectButtonState();
  },

  async correctComposeTextWithAI() {
    const textarea = document.getElementById('pub-compose-text');
    if (!textarea) return;

    const text = String(textarea.value || '').trim();
    if (!text) {
      this.showSafeToast('Correction IA', 'Ajoutez du texte avant de lancer la correction.', 'warning');
      this.updateAICorrectButtonState();
      return;
    }

    this.setAICorrectButtonLoading(true);
    try {
      const response = await fetch(App.apiUrl('Controller/PublicationController.php?action=ai_correct_text'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ text })
      });

      let result = {};
      try {
        result = await response.json();
      } catch (parseError) {
        result = {};
      }

      if (!response.ok || !result.success) {
        const baseMessage = result && typeof result.message === 'string' && result.message.trim()
          ? result.message.trim()
          : 'La correction IA est indisponible pour le moment.';
        const message = this.getPreciseAIErrorMessage(result?.error_code, baseMessage, 'gemini');
        this.showSafeToast('Correction IA', message, 'danger');
        return;
      }

      const corrected = String(result.corrected_text || '').trim();
      if (!corrected) {
        this.showSafeToast('Correction IA', 'Aucun texte corrige recu.', 'warning');
        return;
      }

      textarea.value = corrected;
      this.updateCharCount(textarea, 'pub-char-count', 500);
      this.updateAICorrectButtonState();
      this.showSafeToast('Correction IA', 'Texte corrige avec succes.', 'success');
      textarea.focus();
    } catch (error) {
      this.showSafeToast('Correction IA', "Impossible d'appeler le service de correction IA.", 'danger');
    } finally {
      this.setAICorrectButtonLoading(false);
    }
  },

  renderCompose(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const user = this.getCurrentUser();
    if (!user) return;

    this.stopVoiceRecognition();

    const displayName = user.prenom
      ? `${user.prenom || ''} ${user.nom || ''}`.trim()
      : (user.name || '');
    const initials = App.getInitials(displayName || user.name || 'U');
    const normalizedRole = this.normalizeRole(user.role);

    container.innerHTML = `
      <div class="pub-compose" id="pub-compose-area">
        <div class="pub-compose-header">
          <h3><i class="fa-solid fa-pen-fancy"></i> Ajouter une publication</h3>
        </div>
        <div class="pub-compose-body">
          <div class="pub-author-avatar">
            <div class="avatar" style="width:46px;height:46px;font-size:0.9rem;background:linear-gradient(135deg,#fe5516,#ff8a50);color:#fff;border:2px solid #fff;border-radius:50%;display:flex;align-items:center;justify-content:center;">${initials}</div>
            <span class="pub-role-dot ${normalizedRole}" title="${this.getRoleLabel(normalizedRole)}">
              <i class="fa-solid ${this.getRoleIcon(normalizedRole)}"></i>
            </span>
          </div>
          <div class="pub-compose-input-area" style="width: 100%;">
            <textarea class="pub-compose-textarea" id="pub-compose-text" placeholder="Partagez quelque chose avec la communauté CareMeal..." rows="3" oninput="Publications.updateCharCount(this, 'pub-char-count', 500)"></textarea>
            <div class="pub-upload-block">
              <div class="pub-compose-upload-row">
                <label for="pub-compose-image" class="pub-upload-btn">
                  <i class="fa-regular fa-image"></i> Ajouter une image
                </label>
                <input type="file" id="pub-compose-image" class="pub-compose-file-input" accept="image/jpeg,image/png,image/webp,image/gif">
                <button type="button" class="pub-upload-remove" id="pub-image-remove-btn" style="display:none;" onclick="Publications.clearComposeImage()">
                  <i class="fa-solid fa-xmark"></i> Retirer
                </button>
              </div>
              <div class="pub-upload-meta" id="pub-upload-meta">${this.getUploadHelperText()}</div>
              <div class="pub-compose-preview" id="pub-compose-preview" style="display:none;"></div>
            </div>
            <div class="pub-compose-actions pub-compose-actions--split">
              <div class="pub-compose-meta">
                <span id="pub-char-count" style="font-size:0.85rem; color:#6b7280; font-weight: 500;">0 / 500</span>
                <button type="button" class="pub-voice-btn" id="pub-voice-btn" title="Démarrer la dictée" aria-label="Démarrer ou arrêter la dictée vocale" aria-pressed="false">
                  <i class="fa-solid fa-microphone"></i>
                  <span id="pub-voice-btn-label">Démarrer la dictée</span>
                </button>
                <button type="button" class="pub-ai-correct-btn" id="pub-ai-correct-btn">
                  <i class="fa-solid fa-wand-magic-sparkles"></i>
                  <span>Corriger avec IA</span>
                </button>
                <span class="pub-voice-status" id="pub-voice-status" style="display:none;">Écoute en cours...</span>
              </div>
              <button class="btn btn-primary btn-sm" id="pub-compose-btn" onclick="Publications.handlePublish()">
                <i class="fa-solid fa-paper-plane"></i> Publier
              </button>
            </div>
          </div>
        </div>
      </div>
    `;

    const fileInput = document.getElementById('pub-compose-image');
    if (fileInput) {
      fileInput.addEventListener('change', (e) => this.handleComposeImageChange(e));
    }

    const voiceBtn = document.getElementById('pub-voice-btn');
    if (voiceBtn) {
      voiceBtn.addEventListener('click', () => this.toggleVoiceInput());
    }

    const aiCorrectBtn = document.getElementById('pub-ai-correct-btn');
    if (aiCorrectBtn) {
      aiCorrectBtn.addEventListener('click', () => this.correctComposeTextWithAI());
    }

    const textarea = document.getElementById('pub-compose-text');
    if (textarea) {
      textarea.addEventListener('input', () => this.updateAICorrectButtonState());
    }

    this.setVoiceListeningState(false);
    this.setVoiceTranscribingState(false);
    this.updateAICorrectButtonState();
  },

  handleComposeImageChange(event) {
    const input = event?.target;
    if (!input) return;

    const file = input.files && input.files[0] ? input.files[0] : null;
    const preview = document.getElementById('pub-compose-preview');
    const meta = document.getElementById('pub-upload-meta');
    const removeBtn = document.getElementById('pub-image-remove-btn');

    if (!file) {
      this.clearComposeImage();
      return;
    }

    const validation = this.validatePublicationPayload('ok', file);
    if (!validation.valid) {
      Components.showToast('Erreur', validation.message, 'danger');
      this.clearComposeImage();
      return;
    }

    if (meta) {
      meta.textContent = `${file.name} · ${this.formatFileSize(file.size)}`;
    }

    if (removeBtn) removeBtn.style.display = 'inline-flex';

    if (!preview) return;
    const objectUrl = URL.createObjectURL(file);
    preview.innerHTML = `<img src="${objectUrl}" alt="Aperçu image publication">`;
    preview.style.display = 'block';
  },

  clearComposeImage() {
    const input = document.getElementById('pub-compose-image');
    const preview = document.getElementById('pub-compose-preview');
    const meta = document.getElementById('pub-upload-meta');
    const removeBtn = document.getElementById('pub-image-remove-btn');

    if (input) input.value = '';
    if (preview) {
      preview.style.display = 'none';
      preview.innerHTML = '';
    }
    if (meta) {
      meta.textContent = this.getUploadHelperText();
    }
    if (removeBtn) {
      removeBtn.style.display = 'none';
    }
  },

  setPublishButtonLoading(isLoading) {
    const button = document.getElementById('pub-compose-btn');
    if (!button) return;

    if (isLoading) {
      button.disabled = true;
      button.dataset.originalHtml = button.innerHTML;
      button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Publication...';
      return;
    }

    button.disabled = false;
    if (button.dataset.originalHtml) {
      button.innerHTML = button.dataset.originalHtml;
      delete button.dataset.originalHtml;
    }
  },

  async handlePublish() {
    if (this._state.isPublishing) {
      return;
    }

    const textarea = document.getElementById('pub-compose-text');
    const imageInput = document.getElementById('pub-compose-image');
    if (!textarea) return;

    const content = textarea.value.trim();
    const imageFile = imageInput?.files?.[0] || null;
    const validation = this.validatePublicationPayload(content, imageFile);
    if (!validation.valid) {
      Components.showToast('Erreur', validation.message, 'danger');
      return;
    }

    this._state.isPublishing = true;
    this.setPublishButtonLoading(true);
    try {
      const success = await this.addPublication(content, imageFile);
      if (success) {
        textarea.value = '';
        this.updateCharCount(textarea, 'pub-char-count', 500);
        this.updateAICorrectButtonState();
        this.clearComposeImage();
        await this.renderFeed('pub-feed-container');
      }
    } finally {
      this._state.isPublishing = false;
      this.setPublishButtonLoading(false);
    }
  },

  async renderFeed(containerId, filterUserId = null) {
    const container = document.getElementById(containerId);
    if (!container) return;

    let publications = await this.getAll();

    if (filterUserId) {
      publications = publications.filter(p => String(p.authorId) === String(filterUserId));
    } else {
      // Appliquer recherche, filtre rôle, tri
      publications = this.applyFiltersAndSort(publications);
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
          <h3>${filterUserId ? 'Aucune publication' : 'Aucun résultat'}</h3>
          <p>${filterUserId ? "Vous n'avez pas encore publié. Partagez vos expériences avec la communauté !" : 'Aucune publication ne correspond à votre recherche ou filtre.'}</p>
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

    const reactions = pub.reactions || [];
    const reactionsCount = reactions.length;
    const currentUserReaction = user ? reactions.find(r => 
        String(r.auteur_id) === String(user.id) && 
        this.normalizeRole(r.auteur_type) === this.normalizeRole(user.role)
    ) : null;
    
    const reactionEmojis = { 'Like': '👍', 'Love': '❤️', 'Care': '🥰', 'Haha': '😆', 'Wow': '😲', 'Sad': '😢' };
    const reactionColors = { 'Like': '#3b82f6', 'Love': '#ef4444', 'Care': '#f59e0b', 'Haha': '#f59e0b', 'Wow': '#f59e0b', 'Sad': '#f59e0b' };
    
    const userReactionDisplay = currentUserReaction 
        ? `<span style="color:${reactionColors[currentUserReaction.reaction]}">${reactionEmojis[currentUserReaction.reaction]} ${currentUserReaction.reaction}</span>` 
        : '<i class="fa-regular fa-thumbs-up"></i> J\'aime';
    const showModerationForAdmin = this.isAdminArea() && pub && pub.moderation;
    const moderationStatusRaw = String(pub?.moderation?.status || 'approved').toLowerCase();
    const moderationRiskRaw = String(pub?.moderation?.risk || '').toLowerCase();
    const moderationStatusLabel = moderationStatusRaw === 'rejected' ? 'Refusée' : 'Approuvée';
    const moderationRiskLabel = moderationRiskRaw ? moderationRiskRaw.toUpperCase() : '-';
    const moderationReason = pub?.moderation?.reason ? this.escapeHtml(pub.moderation.reason) : '-';

    return `
      <div class="pub-card" id="pub-${pub.id}">
        <div class="pub-card-header">
          <div class="pub-card-author">
              <div class="pub-author-avatar">
                <div class="avatar" style="width:46px;height:46px;font-size:0.9rem;background:linear-gradient(135deg,#fe5516,#ff8a50);color:#fff;border:2px solid #fff;border-radius:50%;display:flex;align-items:center;justify-content:center;">${initials}</div>
                <span class="pub-role-dot ${pub.authorRole}" title="${this.getRoleLabel(pub.authorRole)}">
                  <i class="fa-solid ${this.getRoleIcon(pub.authorRole)}"></i>
                </span>
            </div>
            <div class="pub-author-info">
              <span class="pub-author-name"><i class="fa-solid fa-user" style="margin-right:6px;color:#64748b;font-size:0.75rem;"></i>${this.escapeHtml(pub.authorName)}</span>
              <div class="pub-author-meta">
                <span class="pub-author-type-icon" title="${this.getRoleLabel(pub.authorRole)}">
                  <i class="fa-solid ${this.getRoleIcon(pub.authorRole)}"></i>
                </span>
                <span class="pub-author-role ${pub.authorRole}">${this.getRoleLabel(pub.authorRole)}</span>
                <span class="pub-author-date"><i class="fa-regular fa-clock"></i> ${this.timeAgo(pub.createdAt)}</span>
              </div>
            </div>
          </div>
          ${canEdit ? `
            <div class="pub-card-actions">
              <button class="pub-action-btn edit" title="Modifier" onclick="Publications.openEditPage(${pub.id})">
                <i class="fa-solid fa-pen"></i>
              </button>
              <button class="pub-action-btn delete" title="Supprimer" onclick="Publications.confirmDelete(${pub.id})">
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
          ` : ''}
        </div>

        <div class="pub-card-body">
          ${pub.content ? `<div class="pub-card-content">${this.escapeHtml(pub.content)}</div>` : ''}
          ${pub.imageUrl ? `
            <div class="pub-card-image-wrap">
              <img class="pub-card-image" src="${this.escapeHtml(pub.imageUrl)}" alt="Image de publication" loading="lazy">
            </div>
          ` : ''}
          ${showModerationForAdmin ? `
            <div class="pub-admin-moderation" style="margin-top:8px; font-size:0.78rem; color:#64748b; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:8px 10px;">
              <strong style="color:#334155;">Modération IA</strong>
              <span style="margin-left:8px;">Statut: ${this.escapeHtml(moderationStatusLabel)}</span>
              <span style="margin-left:8px;">Risque: ${this.escapeHtml(moderationRiskLabel)}</span>
              <span style="margin-left:8px;">Raison: ${moderationReason}</span>
            </div>
          ` : ''}
          ${reactionsCount > 0 ? `
            <div class="pub-reactions-summary">
               <span class="reactions-icons">
                 ${Array.from(new Set(reactions.map(r => r.reaction))).slice(0, 3).map(r => reactionEmojis[r]).join('')}
               </span>
               <span class="reactions-count">${reactionsCount}</span>
            </div>
          ` : ''}
        </div>

        <div class="pub-card-footer" style="padding-top: 8px;">
          <div class="pub-reaction-container">
            <button class="pub-engagement-btn btn-react ${currentUserReaction ? 'reacted' : ''}" onclick="Publications.handleReact(${pub.id}, '${currentUserReaction ? currentUserReaction.reaction : 'Like'}')">
              ${userReactionDisplay}
            </button>
            <div class="pub-reactions-popup">
              <button class="react-emoji" onclick="Publications.handleReact(${pub.id}, 'Like')" title="Like">👍</button>
              <button class="react-emoji" onclick="Publications.handleReact(${pub.id}, 'Love')" title="Love">❤️</button>
              <button class="react-emoji" onclick="Publications.handleReact(${pub.id}, 'Care')" title="Care">🥰</button>
              <button class="react-emoji" onclick="Publications.handleReact(${pub.id}, 'Haha')" title="Haha">😆</button>
              <button class="react-emoji" onclick="Publications.handleReact(${pub.id}, 'Wow')" title="Wow">😲</button>
              <button class="react-emoji" onclick="Publications.handleReact(${pub.id}, 'Sad')" title="Sad">😢</button>
            </div>
          </div>
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
              <input type="text" class="pub-comment-input" placeholder="Écrire un commentaire..." autocomplete="off" maxlength="200" oninput="Publications.updateCharCount(this, 'pub-comment-count-${pub.id}', 200)">
              <button type="submit" class="pub-comment-submit" title="Envoyer">
                <i class="fa-solid fa-paper-plane"></i>
              </button>
            </div>
          </form>
          <div style="text-align: right; margin-top: 4px; padding-right: 40px;">
            <span id="pub-comment-count-${pub.id}" style="font-size:0.75rem; color:#6b7280;">0 / 200</span>
          </div>
        </div>
      </div>
    `;
  },

  renderComment(pubId, comment) {
    const user = this.getCurrentUser();
    const initials = App.getInitials(comment.authorName);
    const canEdit = this.canEditComment(comment);

    const reactions = comment.reactions || [];
    const reactionsCount = reactions.length;
    const currentUserReaction = user ? reactions.find(r => 
        String(r.auteur_id) === String(user.id) && 
        this.normalizeRole(r.auteur_type) === this.normalizeRole(user.role)
    ) : null;

    const reactionEmojis = { 'Like': '👍', 'Love': '❤️', 'Care': '🥰', 'Haha': '😆', 'Wow': '😲', 'Sad': '😢' };
    const reactionColors = { 'Like': '#3b82f6', 'Love': '#ef4444', 'Care': '#f59e0b', 'Haha': '#f59e0b', 'Wow': '#f59e0b', 'Sad': '#f59e0b' };

    const userReactionDisplay = currentUserReaction 
        ? `<span style="color:${reactionColors[currentUserReaction.reaction]}">${reactionEmojis[currentUserReaction.reaction]}</span>` 
        : 'J\'aime';

    return `
      <div class="pub-comment" id="comment-${comment.id}">
        <div class="avatar" style="width:32px;height:32px;font-size:11px;">${initials}</div>
        <div class="pub-comment-bubble" style="width: 100%;">
          <div class="pub-comment-header">
            <span class="pub-comment-author">
              <i class="fa-solid ${this.getRoleIcon(comment.authorRole || 'student')}" style="margin-right:6px;"></i>${this.escapeHtml(comment.authorName)}
            </span>
            <span class="pub-comment-date">${this.timeAgo(comment.createdAt)}</span>
          </div>
          <div class="pub-comment-text">${this.escapeHtml(comment.content)}</div>
          
          <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
            <div class="pub-reaction-container" style="display: inline-block;">
              <button class="pub-comment-action-btn ${currentUserReaction ? 'reacted' : ''}" style="font-weight: 600; font-size: 0.75rem;" onclick="Publications.handleCommentReact(${pubId}, ${comment.id}, '${currentUserReaction ? currentUserReaction.reaction : 'Like'}')">
                ${userReactionDisplay}
              </button>
              <div class="pub-reactions-popup" style="bottom: 100%; margin-bottom: 5px;">
                <button class="react-emoji" onclick="Publications.handleCommentReact(${pubId}, ${comment.id}, 'Like')" title="Like">👍</button>
                <button class="react-emoji" onclick="Publications.handleCommentReact(${pubId}, ${comment.id}, 'Love')" title="Love">❤️</button>
                <button class="react-emoji" onclick="Publications.handleCommentReact(${pubId}, ${comment.id}, 'Care')" title="Care">🥰</button>
                <button class="react-emoji" onclick="Publications.handleCommentReact(${pubId}, ${comment.id}, 'Haha')" title="Haha">😆</button>
                <button class="react-emoji" onclick="Publications.handleCommentReact(${pubId}, ${comment.id}, 'Wow')" title="Wow">😲</button>
                <button class="react-emoji" onclick="Publications.handleCommentReact(${pubId}, ${comment.id}, 'Sad')" title="Sad">😢</button>
              </div>
            </div>
            
            ${reactionsCount > 0 ? `
              <span style="font-size: 0.75rem; color: var(--color-text-light);">
                ${Array.from(new Set(reactions.map(r => r.reaction))).slice(0, 3).map(r => reactionEmojis[r]).join('')} ${reactionsCount}
              </span>
            ` : ''}
          </div>

          ${canEdit ? `
            <div class="pub-comment-actions" style="margin-top: 6px; border-top: 1px solid var(--color-border); padding-top: 4px;">
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

  async handleReact(pubId, type) {
    const user = this.getCurrentUser();
    if (!user) {
      Components.showToast('Erreur', 'Vous devez être connecté pour réagir.', 'warning');
      return;
    }

    try {
      // Find the publication to check current reaction state
      let publications = await this.getAll();
      const pub = publications.find(p => String(p.id) === String(pubId));
      let actionType = type;

      if (pub) {
        const reactions = pub.reactions || [];
        const currentUserReaction = reactions.find(r => 
          String(r.auteur_id) === String(user.id) && 
          this.normalizeRole(r.auteur_type) === this.normalizeRole(user.role)
        );

        // If clicking the same reaction, we remove it. Otherwise we change/add it.
        if (currentUserReaction && currentUserReaction.reaction === type) {
          actionType = ''; // Empty means delete server-side
        }
      }

      const formData = new FormData();
      formData.append('publication_id', pubId);
      formData.append('auteur_type', this.getBackendAuthorType(user.role));
      formData.append('auteur_id', this.getSafeUserId());
      formData.append('reaction', actionType);

      const response = await fetch(App.apiUrl('Controller/PublicationController.php?action=react'), {
        method: 'POST',
        body: formData
      });

      if (!response.ok) throw new Error('Erreur reaction');
      
      const result = await response.json();
      if (result.success) {
        // Optimistically or fully re-render feed
        const sectionMode = document.getElementById(`pub-comments-section-${pubId}`)?.style.display;
        await this.renderFeed('pub-feed-container');
        
        // Restore comment section visibility if it was open
        if (sectionMode === 'block') {
          setTimeout(() => {
            const section = document.getElementById(`pub-comments-section-${pubId}`);
            if (section) section.style.display = 'block';
          }, 50);
        }
      } else {
        throw new Error(result.message || 'Erreur inconnue');
      }
    } catch (error) {
      console.error('Erreur handleReact:', error);
      Components.showToast('Erreur', 'Impossible de réagir.', 'danger');
    }
  },

  async handleCommentReact(pubId, commentId, type) {
    const user = this.getCurrentUser();
    if (!user) {
      Components.showToast('Erreur', 'Vous devez être connecté pour réagir.', 'warning');
      return;
    }

    try {
      let publications = await this.getAll();
      const pub = publications.find(p => String(p.id) === String(pubId));
      let actionType = type;

      if (pub) {
        const comment = pub.comments.find(c => String(c.id) === String(commentId));
        if (comment) {
          const reactions = comment.reactions || [];
          const currentUserReaction = reactions.find(r => 
            String(r.auteur_id) === String(user.id) && 
            this.normalizeRole(r.auteur_type) === this.normalizeRole(user.role)
          );

          if (currentUserReaction && currentUserReaction.reaction === type) {
            actionType = ''; // remove if same
          }
        }
      }

      const formData = new FormData();
      formData.append('commentaire_id', commentId);
      formData.append('auteur_type', this.getBackendAuthorType(user.role));
      formData.append('auteur_id', this.getSafeUserId());
      formData.append('reaction', actionType);

      const response = await fetch(App.apiUrl('Controller/CommentController.php?action=react'), {
        method: 'POST',
        body: formData
      });

      if (!response.ok) throw new Error('Erreur reaction commentaire');
      
      const result = await response.json();
      if (result.success) {
        const sectionMode = document.getElementById(`pub-comments-section-${pubId}`)?.style.display;
        await this.renderFeed('pub-feed-container');
        
        if (sectionMode === 'block') {
          setTimeout(() => {
            const section = document.getElementById(`pub-comments-section-${pubId}`);
            if (section) section.style.display = 'block';
          }, 50);
        }
      } else {
        throw new Error(result.message || 'Erreur inconnue');
      }
    } catch (error) {
      console.error('Erreur handleCommentReact:', error);
      Components.showToast('Erreur', 'Impossible de réagir au commentaire.', 'danger');
    }
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

  setCommentSubmitLoading(form, isLoading) {
    if (!form) return;
    const submitBtn = form.querySelector('.pub-comment-submit');
    if (!submitBtn) return;

    if (isLoading) {
      submitBtn.disabled = true;
      submitBtn.dataset.originalHtml = submitBtn.innerHTML;
      submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
      return;
    }

    submitBtn.disabled = false;
    if (submitBtn.dataset.originalHtml) {
      submitBtn.innerHTML = submitBtn.dataset.originalHtml;
      delete submitBtn.dataset.originalHtml;
    }
  },

  async handleAddComment(e, pubId) {
    e.preventDefault();

    const form = e.target;
    const pubKey = String(pubId);
    if (this._state.commentSubmittingByPublication[pubKey]) {
      return;
    }

    const input = form.querySelector('.pub-comment-input');
    if (!input) return;

    const content = input.value.trim();
    if (!content) {
      Components.showToast('Erreur', 'Le commentaire ne peut pas être vide.', 'danger');
      return;
    }
    
    if (content.length > 200) {
      Components.showToast('Erreur', 'Le commentaire ne doit pas dépasser 200 caractères.', 'danger');
      return;
    }

    this._state.commentSubmittingByPublication[pubKey] = true;
    this.setCommentSubmitLoading(form, true);
    try {
      const success = await this.addComment(pubId, content);

      if (success) {
        input.value = '';
        await this.renderFeed('pub-feed-container');

        setTimeout(() => {
          const section = document.getElementById(`pub-comments-section-${pubId}`);
          if (section) section.style.display = 'block';
        }, 50);
      }
    } finally {
      this._state.commentSubmittingByPublication[pubKey] = false;
      this.setCommentSubmitLoading(form, false);
    }
  },

  async handleDeleteComment(pubId, commentId) {
    Components.confirm(
      'Supprimer le commentaire',
      'Êtes-vous sûr de vouloir supprimer ce commentaire ?',
      async () => {
        const success = await this.deleteComment(commentId);

        if (success) {
          await this.renderFeed('pub-feed-container');

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

    const newContent = prompt('Modifier le commentaire (200 caractères max) :', comment.content);
    if (newContent === null) return;
    
    const trimmed = newContent.trim();
    
    if (!trimmed) {
      Components.showToast('Erreur', 'Le commentaire ne peut pas être vide.', 'danger');
      return;
    }
    
    if (trimmed.length > 200) {
      Components.showToast('Erreur', 'Le commentaire ne doit pas dépasser 200 caractères.', 'danger');
      return;
    }

    const success = await this.updateComment(commentId, trimmed);

    if (success) {
      await this.renderFeed('pub-feed-container');

      setTimeout(() => {
        const section = document.getElementById(`pub-comments-section-${pubId}`);
        if (section) section.style.display = 'block';
      }, 50);
    }
  },

  openEditPage(pubId) {
    if (!pubId) return;

    if (this.isAdminArea()) {
      window.location.href = `/admin/publication-edit.php?id=${encodeURIComponent(pubId)}`;
      return;
    }

    this.openEditModal(pubId);
  },

  openEditModal(pubId) {
    if (this.isAdminArea()) {
      this.openEditPage(pubId);
      return;
    }

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
          <textarea class="pub-edit-textarea" id="pub-edit-content" rows="4" oninput="Publications.updateCharCount(this, 'pub-edit-char-count', 500)"></textarea>
          <div style="text-align: right; margin-top: 4px;">
            <span id="pub-edit-char-count" style="font-size:0.85rem; color:#6b7280; font-weight: 500;">0 / 500</span>
          </div>
          <div class="pub-upload-block pub-upload-block-edit">
            <div class="pub-compose-upload-row" style="margin-top: 12px;">
              <label for="pub-edit-image" class="pub-upload-btn">
                <i class="fa-regular fa-image"></i> Remplacer l'image
              </label>
              <input type="file" id="pub-edit-image" class="pub-compose-file-input" accept="image/jpeg,image/png,image/webp,image/gif">
            </div>
            <div class="pub-upload-meta" id="pub-edit-upload-meta">${this.getUploadHelperText()}</div>
            <div class="pub-compose-preview" id="pub-edit-image-preview" style="display:none;"></div>
          </div>
          <label class="pub-edit-remove-image" id="pub-edit-remove-wrap" style="display:none;">
            <input type="checkbox" id="pub-edit-remove-image">
            Retirer l'image actuelle
          </label>
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
        this.updateCharCount(textarea, 'pub-edit-char-count', 500);

        const preview = document.getElementById('pub-edit-image-preview');
        const removeWrap = document.getElementById('pub-edit-remove-wrap');
        if (pub.imageUrl && preview) {
          preview.innerHTML = `<img src="${this.escapeHtml(pub.imageUrl)}" alt="Image actuelle publication">`;
          preview.style.display = 'block';
          if (removeWrap) removeWrap.style.display = 'inline-flex';
        }
      }
    });

    const editInput = document.getElementById('pub-edit-image');
    if (editInput) {
      editInput.addEventListener('change', (e) => this.handleEditImageChange(e));
    }

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

  handleEditImageChange(event) {
    const input = event?.target;
    const file = input?.files?.[0] || null;
    const preview = document.getElementById('pub-edit-image-preview');
    const meta = document.getElementById('pub-edit-upload-meta');
    const removeCheckbox = document.getElementById('pub-edit-remove-image');
    const removeWrap = document.getElementById('pub-edit-remove-wrap');

    if (!file) {
      if (meta) meta.textContent = this.getUploadHelperText();
      return;
    }

    const validation = this.validatePublicationPayload('ok', file);
    if (!validation.valid) {
      Components.showToast('Erreur', validation.message, 'danger');
      input.value = '';
      if (meta) meta.textContent = this.getUploadHelperText();
      return;
    }

    if (removeCheckbox) removeCheckbox.checked = false;
    if (meta) meta.textContent = `${file.name} · ${this.formatFileSize(file.size)}`;
    if (removeWrap) removeWrap.style.display = 'inline-flex';

    if (preview) {
      preview.innerHTML = `<img src="${URL.createObjectURL(file)}" alt="Nouvelle image publication">`;
      preview.style.display = 'block';
    }
  },

  async saveEdit(pubId) {
    const textarea = document.getElementById('pub-edit-content');
    const imageInput = document.getElementById('pub-edit-image');
    const removeImageCheckbox = document.getElementById('pub-edit-remove-image');
    if (!textarea) return;

    const content = textarea.value.trim();
    const imageFile = imageInput?.files?.[0] || null;
    const removeImage = !!removeImageCheckbox?.checked;

    if (content.length > 500) {
      Components.showToast('Erreur', 'La publication ne doit pas dépasser 500 caractères.', 'danger');
      return;
    }

    if (imageFile) {
      const imageValidation = this.validatePublicationPayload('ok', imageFile);
      if (!imageValidation.valid) {
        Components.showToast('Erreur', imageValidation.message, 'danger');
        return;
      }
    }

    const publications = await this.getAll();
    const currentPublication = publications.find(p => p.id === pubId);
    const currentlyHasImage = !!currentPublication?.imageUrl;
    const willHaveImage = imageFile ? true : (removeImage ? false : currentlyHasImage);

    if (!content && !willHaveImage) {
      Components.showToast('Erreur', 'Ajoutez du texte ou gardez une image pour enregistrer.', 'danger');
      return;
    }

    const success = await this.updatePublication(pubId, content, imageFile, removeImage);

    if (success) {
      const isDedicatedEditPage = !!document.getElementById('pub-edit-page-root');
      if (isDedicatedEditPage) {
        window.location.href = App.apiUrl('admin/publications.php');
        return;
      }

      this.closeEditModal();
      await this.renderFeed('pub-feed-container');
    }
  },

  confirmDelete(pubId) {
    Components.confirm(
      'Supprimer la publication',
      'Êtes-vous sûr de vouloir supprimer cette publication ? Cette action est irréversible.',
      async () => {
        const success = await this.deletePublication(pubId);
        if (success) {
          await this.renderFeed('pub-feed-container');
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
    try { this.renderToolbar('pub-toolbar-container'); } catch(e) { console.warn('toolbar error', e); }

    const hasAdminActions = !!document.getElementById('pub-admin-actions-container');
    if (hasAdminActions) {
      this.renderAdminActions('pub-admin-actions-container');
      const statsContainer = document.getElementById('pub-stats-container');
      if (statsContainer) {
        statsContainer.style.display = 'none';
        statsContainer.innerHTML = '';
      }
      this._state.statsLoaded = false;
      this._state.statsVisible = false;
    } else {
      try { await this.renderStats('pub-stats-container'); } catch(e) { console.warn('stats error', e); }
    }

    this.renderCompose('pub-compose-container');
    await this.renderFeed('pub-feed-container');
  },

  async initEditPage() {
    const pageRoot = document.getElementById('pub-edit-page-root');
    if (!pageRoot) return;

    const publicationId = this.getEditPublicationIdFromUrl();
    const status = document.getElementById('pub-edit-page-status');
    const textarea = document.getElementById('pub-edit-content');
    const saveBtn = document.getElementById('pub-edit-page-save');
    const imageInput = document.getElementById('pub-edit-image');
    const removeCheckbox = document.getElementById('pub-edit-remove-image');

    if (!publicationId || !textarea || !saveBtn) {
      if (status) status.textContent = 'Publication invalide.';
      if (saveBtn) saveBtn.disabled = true;
      return;
    }

    if (status) status.textContent = 'Chargement de la publication...';

    const publications = await this.getAll();
    const publication = publications.find(p => p.id === publicationId);

    if (!publication) {
      if (status) status.textContent = 'Publication introuvable.';
      saveBtn.disabled = true;
      return;
    }

    textarea.value = publication.content || '';
    this.updateCharCount(textarea, 'pub-edit-char-count', 500);

    const preview = document.getElementById('pub-edit-image-preview');
    const removeWrap = document.getElementById('pub-edit-remove-wrap');

    if (publication.imageUrl && preview) {
      preview.innerHTML = `<img src="${this.escapeHtml(publication.imageUrl)}" alt="Image actuelle publication">`;
      preview.style.display = 'block';
      if (removeWrap) removeWrap.style.display = 'inline-flex';
    }

    if (status) status.textContent = '';

    if (imageInput) {
      imageInput.addEventListener('change', (e) => this.handleEditImageChange(e));
    }

    if (removeCheckbox && preview) {
      removeCheckbox.addEventListener('change', () => {
        if (removeCheckbox.checked && !imageInput?.files?.length) {
          preview.style.display = 'none';
        } else if (preview.innerHTML.trim() !== '') {
          preview.style.display = 'block';
        }
      });
    }

    saveBtn.onclick = () => this.saveEdit(publicationId);
  },

  /* ===================================================
     NOUVELLES FONCTIONNALITÉS MÉTIER
     Recherche · Filtre rôle · Tri · Statistiques
     =================================================== */

  /** Applique recherche + filtre par rôle + tri sur un tableau de publications */
  applyFiltersAndSort(publications) {
    let result = [...publications];

    // --- Recherche insensible à la casse sur contenu uniquement ---
    const search = this._state.search.trim().toLowerCase();
    if (search) {
      result = result.filter(p => p.content.toLowerCase().includes(search));
    }

    // --- Filtre par rôle (strict — admin exclu si filtre student/partner) ---
    if (this._state.roleFilter !== 'all') {
      result = result.filter(p => p.authorRole === this._state.roleFilter);
    }

    // --- Tri ---
    if (this._state.sort === 'oldest') {
      result.sort((a, b) => new Date(a.createdAt) - new Date(b.createdAt));
    } else if (this._state.sort === 'reactions') {
      result.sort((a, b) => (b.reactions?.length || 0) - (a.reactions?.length || 0));
    }
    // 'recent' = ordre API déjà DESC par date

    return result;
  },

  /** Met à jour le filtre rôle et re-rend le feed */
  setRoleFilter(role, btn) {
    this._state.roleFilter = role;
    document.querySelectorAll('.pub-filter-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    this.renderFeed('pub-feed-container');
  },

  /** Affiche la barre de recherche / filtre / tri */
  renderToolbar(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = `
      <div class="pub-toolbar">
        <div class="pub-search-wrap">
          <i class="fa-solid fa-magnifying-glass pub-search-icon"></i>
          <input
            type="text"
            class="pub-search-input"
            id="pub-search-input"
            placeholder="Rechercher dans les publications..."
            oninput="Publications._state.search = this.value; Publications.renderFeed('pub-feed-container');"
          >
          <button class="pub-search-clear" id="pub-search-clear" title="Effacer" style="display:none;" onclick="document.getElementById('pub-search-input').value=''; Publications._state.search=''; document.getElementById('pub-search-clear').style.display='none'; Publications.renderFeed('pub-feed-container');">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
        <div class="pub-toolbar-bottom">
          <div class="pub-filter-group">
            <span class="pub-filter-label"><i class="fa-solid fa-filter"></i></span>
            <button class="pub-filter-btn active" onclick="Publications.setRoleFilter('all', this)">Tous</button>
            <button class="pub-filter-btn student" onclick="Publications.setRoleFilter('student', this)"><i class="fa-solid fa-graduation-cap"></i> Étudiants</button>
            <button class="pub-filter-btn partner" onclick="Publications.setRoleFilter('partner', this)"><i class="fa-solid fa-store"></i> Partenaires</button>
          </div>
          <select class="pub-sort-select" onchange="Publications._state.sort = this.value; Publications.renderFeed('pub-feed-container');">
            <option value="recent">Plus récents</option>
            <option value="oldest">Plus anciens</option>
            <option value="reactions">Plus de réactions</option>
          </select>
        </div>
      </div>
    `;

    // Afficher/masquer le bouton effacer lors de la saisie
    const input = document.getElementById('pub-search-input');
    const clearBtn = document.getElementById('pub-search-clear');
    if (input && clearBtn) {
      input.addEventListener('input', () => {
        clearBtn.style.display = input.value ? 'flex' : 'none';
      });
    }
  },

  /** Récupère les statistiques depuis le backend */
  async fetchStats() {
    try {
      const res = await fetch(App.apiUrl('Controller/PublicationController.php?action=stats'));
      if (!res.ok) throw new Error('Erreur stats');
      return await res.json();
    } catch (e) {
      console.error('fetchStats error:', e);
      return null;
    }
  },

  /** Affiche les statistiques de la communauté (admin uniquement) */
  async renderStats(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return; // pas de container = pas d'affichage (student/partner)

    container.innerHTML = `<div class="pub-stats-loading"><i class="fa-solid fa-spinner fa-spin"></i> Chargement des statistiques...</div>`;

    const stats = await this.fetchStats();
    if (!stats || stats.error) {
      container.innerHTML = '';
      return;
    }

    const truncate = (text, max) => {
      if (!text) return '';
      return text.length > max ? text.substring(0, max) + '...' : text;
    };

    // IDs des publications cibles pour le scroll
    const commentedId = stats.most_commented ? stats.most_commented.id_publication : null;
    const reactedId   = stats.most_reacted   ? stats.most_reacted.id_publication   : null;

    const mostCommentedContent = truncate(stats?.most_commented?.contenu_publication || '', 50) || '[Publication avec image]';
    const mostReactedContent = truncate(stats?.most_reacted?.contenu_publication || '', 50) || '[Publication avec image]';

    const mostCommentedLabel = stats.most_commented
      ? `${this.escapeHtml(mostCommentedContent)} <em class="pub-stats-count">(${stats.most_commented.nb_comments} commentaire${stats.most_commented.nb_comments != 1 ? 's' : ''})</em>`
      : '<em>Aucune publication</em>';

    const mostReactedLabel = stats.most_reacted
      ? `${this.escapeHtml(mostReactedContent)} <em class="pub-stats-count">(${stats.most_reacted.nb_reactions} réaction${stats.most_reacted.nb_reactions != 1 ? 's' : ''})</em>`
      : '<em>Aucune publication</em>';

    const commentedClickable = commentedId
      ? `onclick="Publications.scrollToPub(${commentedId})" style="cursor:pointer;" title="Voir cette publication"`
      : '';
    const reactedClickable = reactedId
      ? `onclick="Publications.scrollToPub(${reactedId})" style="cursor:pointer;" title="Voir cette publication"`
      : '';

    container.innerHTML = `
      <div class="pub-stats-section">
        <div class="pub-stats-header">
          <i class="fa-solid fa-chart-bar"></i>
          <h3>Statistiques de la communauté</h3>
          <button class="pub-stats-toggle" onclick="Publications.toggleStats()" title="Masquer/Afficher">
            <i class="fa-solid fa-chevron-up" id="pub-stats-chevron"></i>
          </button>
        </div>
        <div id="pub-stats-body">
          <div class="pub-stats-grid">
            <div class="pub-stat-card">
              <div class="pub-stat-icon pub-stat-icon--orange"><i class="fa-solid fa-newspaper"></i></div>
              <div class="pub-stat-value">${stats.total_publications}</div>
              <div class="pub-stat-label">Publications</div>
            </div>
            <div class="pub-stat-card">
              <div class="pub-stat-icon pub-stat-icon--blue"><i class="fa-solid fa-comments"></i></div>
              <div class="pub-stat-value">${stats.total_commentaires}</div>
              <div class="pub-stat-label">Commentaires totaux</div>
            </div>
            <div class="pub-stat-card">
              <div class="pub-stat-icon pub-stat-icon--green"><i class="fa-solid fa-graduation-cap"></i></div>
              <div class="pub-stat-value">${stats.publications_etudiants}</div>
              <div class="pub-stat-label">Pubs étudiants</div>
            </div>
            <div class="pub-stat-card">
              <div class="pub-stat-icon pub-stat-icon--purple"><i class="fa-solid fa-store"></i></div>
              <div class="pub-stat-value">${stats.publications_partenaires}</div>
              <div class="pub-stat-label">Pubs partenaires</div>
            </div>
            <div class="pub-stat-card">
              <div class="pub-stat-icon pub-stat-icon--green"><i class="fa-solid fa-comment-dots"></i></div>
              <div class="pub-stat-value">${stats.commentaires_etudiants ?? 0}</div>
              <div class="pub-stat-label">Comm. étudiants</div>
            </div>
            <div class="pub-stat-card">
              <div class="pub-stat-icon pub-stat-icon--purple"><i class="fa-solid fa-message"></i></div>
              <div class="pub-stat-value">${stats.commentaires_partenaires ?? 0}</div>
              <div class="pub-stat-label">Comm. partenaires</div>
            </div>
            <div class="pub-stat-card">
              <div class="pub-stat-icon pub-stat-icon--teal"><i class="fa-solid fa-chart-line"></i></div>
              <div class="pub-stat-value">${stats.avg_comments}</div>
              <div class="pub-stat-label">Moy. comm./pub</div>
            </div>
          </div>
          <div class="pub-stats-highlights">
            <div class="pub-stats-highlight-item pub-stats-clickable" ${commentedClickable}>
              <span class="pub-stats-highlight-icon"><i class="fa-solid fa-trophy"></i></span>
              <div class="pub-stats-highlight-text">
                <div class="pub-stats-highlight-label">
                  Publication la plus commentée
                  ${commentedId ? '<i class="fa-solid fa-arrow-right pub-stats-arrow"></i>' : ''}
                </div>
                <div class="pub-stats-highlight-value">${mostCommentedLabel}</div>
              </div>
            </div>
            <div class="pub-stats-highlight-item pub-stats-clickable" ${reactedClickable}>
              <span class="pub-stats-highlight-icon"><i class="fa-solid fa-fire"></i></span>
              <div class="pub-stats-highlight-text">
                <div class="pub-stats-highlight-label">
                  Publication la plus réagie
                  ${reactedId ? '<i class="fa-solid fa-arrow-right pub-stats-arrow"></i>' : ''}
                </div>
                <div class="pub-stats-highlight-value">${mostReactedLabel}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
  },

  /** Scroll fluide vers une publication par son id */
  scrollToPub(pubId) {
    const card = document.getElementById('pub-' + pubId);
    if (!card) return;
    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
    card.classList.add('pub-card-highlight');
    setTimeout(() => card.classList.remove('pub-card-highlight'), 2000);
  },

  /** Toggle affichage stats */
  toggleStats() {
    const body = document.getElementById('pub-stats-body');
    const chevron = document.getElementById('pub-stats-chevron');
    if (!body) return;
    const isHidden = body.style.display === 'none';
    body.style.display = isHidden ? 'block' : 'none';
    if (chevron) {
      chevron.className = isHidden ? 'fa-solid fa-chevron-up' : 'fa-solid fa-chevron-down';
    }
  },

  renderAdminActions(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = `
      <div class="pub-admin-actions">
        <button type="button" class="btn btn-primary btn-sm" onclick="Publications.openAdminCreatePage()">
          <i class="fa-solid fa-plus"></i> Ajouter une publication
        </button>
        <button type="button" class="btn btn-outline btn-sm" id="pub-admin-stats-btn" onclick="Publications.showAdminStats()">
          <i class="fa-solid fa-chart-column"></i> Afficher les statistiques
        </button>
      </div>
    `;
  },

  openAdminCreatePage() {
    window.location.href = App.apiUrl('admin/publication-create.php');
  },

  async showAdminStats() {
    const container = document.getElementById('pub-stats-container');
    const button = document.getElementById('pub-admin-stats-btn');
    if (!container || !button) return;

    if (!this._state.statsLoaded) {
      await this.renderStats('pub-stats-container');
      this._state.statsLoaded = true;
      this._state.statsVisible = true;
      container.style.display = 'block';
      button.innerHTML = '<i class="fa-solid fa-eye-slash"></i> Masquer les statistiques';
      return;
    }

    this._state.statsVisible = !this._state.statsVisible;
    container.style.display = this._state.statsVisible ? 'block' : 'none';
    button.innerHTML = this._state.statsVisible
      ? '<i class="fa-solid fa-eye-slash"></i> Masquer les statistiques'
      : '<i class="fa-solid fa-chart-column"></i> Afficher les statistiques';
  },

  async initProfileSection() {
    const user = this.getCurrentUser();
    if (!user) return;
    await this.renderFeed('profile-pub-feed', user.id);
  }
};







