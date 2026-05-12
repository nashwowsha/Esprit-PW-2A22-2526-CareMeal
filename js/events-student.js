/* ============================================
   CAREMEAL — STUDENT EVENTS
   js/events-student.js
   ============================================ */

const EventsStudent = {
  filter: 'all', // 'all' or 'my'
  events: [],

  endpoint(path) {
    const clean = String(path || '').replace(/^\/+/, '');
    if (typeof window.caremealPath === 'function') {
      return window.caremealPath(clean);
    }
    return '/' + clean;
  },

  async init() {
    await this.fetchEvents();
  },

  setFilter(f) {
    this.filter = f;
    document.getElementById('filter-all').className = f === 'all' ? 'btn btn-primary' : 'btn btn-outline';
    document.getElementById('filter-my').className = f === 'my' ? 'btn btn-primary' : 'btn btn-outline';
    this.render();
  },

  formatDate(d) {
    if(!d) return ''; 
    const parts = d.split('-'); 
    if(parts.length !== 3) return d;
    return `${parts[2]}/${parts[1]}/${parts[0]}`; 
  },

  async fetchEvents() {
    try {
        const studentId = App.getCurrentUser()?.id || null;
        console.log("Fetching events for studentId:", studentId);
        const response = await fetch(this.endpoint('Controller/EventParticipationController.php'), {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'get_events', student_id: studentId })
        });
        const data = await response.json();
        console.log("Response data:", data);
        
        if (data.success && Array.isArray(data.events)) {
            this.events = data.events.map(e => ({
                id: parseInt(e.id_evenement),
                title: e.titre || '',
                type: e.type_evenement || '',
                date: e.date_evenement || '',
                start: e.heure_debut || '00:00',
                end: e.heure_fin || '00:00',
                location: e.lieu || '',
                capacity: parseInt(e.capacite_max) || 0,
                registered: parseInt(e.inscrits) || 0,
                status: 'validated',
                desc: e.description || '',
                userSubscribed: parseInt(e.est_inscrit) > 0,
                displayImg: e.image || "https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&q=80&w=800"
            }));
            this.render();
        } else {
            console.error("Fetch returned false success or non-array events:", data);
            document.getElementById('events-container').innerHTML = `<div style="grid-column: 1/-1; padding:20px; text-align:center;">Erreur: impossible de charger les événements. (${data.message || ""})</div>`;
        }
    } catch (e) {
        console.error('Erreur de récupération des événements:', e);
        document.getElementById('events-container').innerHTML = `<div style="grid-column: 1/-1; padding:20px; text-align:center;">Erreur de parsing JSON. Voir console.</div>`;
    }
  },

    openSubscribeModal(id) {
        const e = this.events.find(x => x.id === id);
        if (!e || e.registered >= e.capacity || e.userSubscribed) return;

        const regModal = document.getElementById('registerModal');
        if (!regModal) return;

        document.getElementById('regModalEventId').value = id;
        document.getElementById('regModalEventTitle').innerText = e.title;
        this.clearRegisterModal();

        const user = App.getCurrentUser();
        const pwdRow = document.getElementById('regmod-password-row');
        const submitBtn = document.getElementById('regmod-submit');
        const modalTitle = document.getElementById('regModalTitle');

        if (user) {
            // Connecté : afficher tous les champs sauf mot de passe, pré-remplir
            if (pwdRow) pwdRow.style.display = 'none';
            if (submitBtn) submitBtn.innerHTML = '<i class="fa-solid fa-check"></i> Confirmer mon inscription';
            if (modalTitle) modalTitle.textContent = "Confirmer l'inscription";

            const set = (elId, val) => { const el = document.getElementById(elId); if (el) el.value = val || ''; };
            set('regmod-nom',        user.nom || '');
            set('regmod-prenom',     user.prenom || '');
            set('regmod-email',      user.email || '');
            set('regmod-telephone',  user.phone || user.telephone || '');
            set('regmod-university', user.ecole || user.university || '');
            set('regmod-annee',      user.annee || user.annee_etude || '');

            // Email en lecture seule
            const emailEl = document.getElementById('regmod-email');
            if (emailEl) { emailEl.readOnly = true; emailEl.style.opacity = '0.65'; }
        } else {
            // Visiteur : tous les champs visibles
            if (pwdRow) pwdRow.style.display = '';
            if (submitBtn) submitBtn.innerHTML = '<i class="fa-solid fa-user-plus"></i> Créer mon compte et m\'inscrire';
            if (modalTitle) modalTitle.textContent = "Formulaire d'inscription";
            const emailEl = document.getElementById('regmod-email');
            if (emailEl) { emailEl.readOnly = false; emailEl.style.opacity = '1'; }
        }

        regModal.style.display = 'flex';
    },

    clearRegisterModal() {
        const fields = ['nom','prenom','email','password','confirm','telephone','university','annee'];
        fields.forEach(f => {
            const el = document.getElementById('regmod-' + f);
            if (el) el.value = '';
            const err = document.getElementById('regmod-' + f + '-error');
            if (err) err.innerText = '';
        });
    },

    showRegisterError(field, msg) {
        const err = document.getElementById('regmod-' + field + '-error');
        if (err) err.innerText = msg;
    },

    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    },

    async submitRegisterAndSubscribe(e) {
        e.preventDefault();
        ['nom','prenom','email','password','confirm','telephone','university','annee'].forEach(f => {
            const err = document.getElementById('regmod-' + f + '-error'); if (err) err.innerText = '';
        });

        const nom        = document.getElementById('regmod-nom')?.value.trim() || '';
        const prenom     = document.getElementById('regmod-prenom')?.value.trim() || '';
        const email      = document.getElementById('regmod-email')?.value.trim() || '';
        const password   = document.getElementById('regmod-password')?.value || '';
        const confirm    = document.getElementById('regmod-confirm')?.value || '';
        const telephone  = document.getElementById('regmod-telephone')?.value.trim() || '';
        const university = document.getElementById('regmod-university')?.value || '';
        const annee      = document.getElementById('regmod-annee')?.value || '';
        const eventId    = parseInt(document.getElementById('regModalEventId')?.value || 0, 10);

        // ── Validations ──────────────────────────────────────────────
        let valid = true;

        // Nom : obligatoire, min 2 lettres, pas de chiffres
        if (!nom || nom.length < 2) {
            this.showRegisterError('nom', 'Nom requis (min. 2 caractères)'); valid = false;
        } else if (/\d/.test(nom)) {
            this.showRegisterError('nom', 'Le nom ne doit pas contenir de chiffres'); valid = false;
        } else if (!/^[a-zA-ZÀ-ÿ\s\-']+$/.test(nom)) {
            this.showRegisterError('nom', 'Le nom ne doit contenir que des lettres'); valid = false;
        }

        // Prénom : obligatoire, min 2 lettres, pas de chiffres
        if (!prenom || prenom.length < 2) {
            this.showRegisterError('prenom', 'Prénom requis (min. 2 caractères)'); valid = false;
        } else if (/\d/.test(prenom)) {
            this.showRegisterError('prenom', 'Le prénom ne doit pas contenir de chiffres'); valid = false;
        } else if (!/^[a-zA-ZÀ-ÿ\s\-']+$/.test(prenom)) {
            this.showRegisterError('prenom', 'Le prénom ne doit contenir que des lettres'); valid = false;
        }

        // Email
        if (!email) {
            this.showRegisterError('email', 'Email requis'); valid = false;
        } else if (!this.isValidEmail(email)) {
            this.showRegisterError('email', 'Format d\'email invalide (ex: nom@domaine.com)'); valid = false;
        }

        // Téléphone : si renseigné, exactement 8 chiffres
        if (telephone) {
            if (/[a-zA-Z]/.test(telephone)) {
                this.showRegisterError('telephone', 'Le numéro ne doit pas contenir de lettres'); valid = false;
            } else if (!/^\d{8}$/.test(telephone.replace(/[\s\-().+]/g, ''))) {
                this.showRegisterError('telephone', 'Le numéro doit contenir exactement 8 chiffres'); valid = false;
            }
        }

        // Université + Année
        if (!university) { this.showRegisterError('university', 'Sélectionnez votre université'); valid = false; }
        if (!annee)      { this.showRegisterError('annee', 'Sélectionnez votre année d\'étude'); valid = false; }

        if (!valid) return;

        const btn = document.getElementById('regmod-submit');
        const oldHtml = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> En cours...'; }

        // === CAS 1 : Utilisateur déjà connecté → inscription directe ===
        if (App.isLoggedIn()) {
            document.getElementById('registerModal').style.display = 'none';
            await this.confirmSubscribe(eventId, '', {
                nom, prenom, email, telephone, universite: university, annee
            });
            if (btn) { btn.disabled = false; btn.innerHTML = oldHtml; }
            return;
        }

        // === CAS 2 : Visiteur → validation mot de passe + création de compte ===
        if (!password || password.length < 6) { this.showRegisterError('password', 'Minimum 6 caractères'); if (btn) { btn.disabled = false; btn.innerHTML = oldHtml; } return; }
        if (password !== confirm)             { this.showRegisterError('confirm', 'Les mots de passe ne correspondent pas'); if (btn) { btn.disabled = false; btn.innerHTML = oldHtml; } return; }

        try {
            // Vérifier disponibilité email
            const checkRaw = await fetch(this.endpoint('Controller/AuthController.php'), {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ action: 'check-email', email })
            });
            const checkText = await checkRaw.text();
            let checkJson; try { checkJson = JSON.parse(checkText.substring(checkText.indexOf('{'))); } catch { checkJson = { success: false }; }
            if (!checkJson.success) {
                this.showRegisterError('email', 'Cet email est déjà utilisé');
                if (btn) { btn.disabled = false; btn.innerHTML = oldHtml; }
                return;
            }

            // Créer le compte
            const regRaw = await fetch(this.endpoint('Controller/AuthController.php'), {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ action: 'register-student', nom, prenom, email, password, ecole: university, annee, telephone, quartier: '' })
            });
            const regText = await regRaw.text();
            let regJson; try { regJson = JSON.parse(regText.substring(regText.indexOf('{'))); } catch { regJson = { success: false, message: 'Réponse invalide' }; }
            if (!regJson.success) {
                alert('Erreur: ' + (regJson.message || 'Inscription échouée'));
                if (btn) { btn.disabled = false; btn.innerHTML = oldHtml; }
                return;
            }

            // Auto-login
            const loginRaw = await fetch(this.endpoint('Controller/AuthController.php'), {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ action: 'login', email, password })
            });
            const loginText = await loginRaw.text();
            let loginJson; try { loginJson = JSON.parse(loginText.substring(loginText.indexOf('{'))); } catch { loginJson = { success: false }; }

            if (loginJson.success && loginJson.user) {
                const dbUser = loginJson.user;
                App.setCurrentUser({
                    id: dbUser.id, email: dbUser.email, role: dbUser.role, status: dbUser.status,
                    name: ((dbUser.prenom || '') + ' ' + (dbUser.nom || '')).trim() || dbUser.email,
                    phone: dbUser.telephone || '', ecole: dbUser.ecole || '',
                    university: dbUser.ecole || '', annee: dbUser.annee_etude || '',
                    prenom: dbUser.prenom || '', nom: dbUser.nom || ''
                });
            }

            document.getElementById('registerModal').style.display = 'none';
            await this.confirmSubscribe(eventId, '', { nom, prenom, email, telephone, universite: university, annee });
            alert('Compte créé et inscription réussie !');

        } catch(err) {
            console.error(err);
            alert('Erreur réseau. Réessayez plus tard.');
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = oldHtml; }
        }
    },

  async confirmSubscribe(directId = null, directRemarque = null, extraData = {}) {
    const isModal = directId === null;
    const id = isModal ? parseInt(document.getElementById('subEventId').value) : directId;
    const remarque = isModal ? document.getElementById('subRemarque').value.trim() : directRemarque;

    const e = this.events.find(x => x.id === id);
    if (!e) return;

    if(isModal) {
        document.getElementById('subscribeModal').style.display = 'none';
    }

    try {
        const studentId = App.getCurrentUser()?.id || null;
        const user = App.getCurrentUser();

        // Récupérer les infos depuis le formulaire ou l'utilisateur connecté
        const nom        = extraData.nom        || user?.nom    || '';
        const prenom     = extraData.prenom     || user?.prenom || '';
        const email      = extraData.email      || user?.email  || '';
        const telephone  = extraData.telephone  || user?.phone  || '';
        const universite = extraData.universite || user?.ecole  || user?.university || '';
        const annee      = extraData.annee      || user?.annee  || '';

        const response = await fetch(this.endpoint('Controller/EventParticipationController.php'), {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ 
                action: 'participate', 
                evenement_id: id, 
                student_id: studentId,
                remarque: remarque,
                nom: nom,
                prenom: prenom,
                email: email,
                telephone: telephone,
                universite: universite,
                annee: annee
            })
        });
        const data = await response.json();
        if(data.success) {
            e.registered++;
            e.userSubscribed = true;
            this.render();
            alert('Inscription réussie !');
        } else {
            alert('Erreur: ' + data.message);
        }
    } catch(err) {
        console.error(err);
        alert('Erreur de connexion au serveur.');
    }
  },

  // ── Traduction automatique de la description ─────────────
  // Utilise LibreTranslate (API gratuite et open source)
  translateCache: {}, // cache pour éviter les appels répétés

  async translateDesc(eventId, targetLang) {
      const event = this.events.find(x => x.id === eventId);
      if (!event || !event.desc) return;

      const cacheKey = `${eventId}_${targetLang}`;
      const descEl   = document.getElementById(`desc-translated-${eventId}`);
      const frEl     = document.getElementById(`desc-fr-${eventId}`);
      const resetBtn = document.getElementById(`btn-reset-${eventId}`);
      const btn      = document.getElementById(`btn-${targetLang}-${eventId}`);

      // Afficher le loader
      if (btn) { btn.textContent = '�?�'; btn.disabled = true; }

      // Utiliser le cache si disponible
      if (this.translateCache[cacheKey]) {
          this._showTranslation(eventId, this.translateCache[cacheKey], targetLang);
          if (btn) { btn.textContent = targetLang === 'ar' ? '�? عربي' : '�? English'; btn.disabled = false; }
          return;
      }

      try {
          // Appel via le proxy PHP (évite CORS + gère les erreurs serveur)
          const response = await fetch(this.endpoint('Controller/TranslateController.php'), {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({
                  q      : event.desc,
                  source : 'fr',
                  target : targetLang,
                  format : 'text'
              })
          });

          const data = await response.json();

          if (data.success && data.translatedText) {
              // Mettre en cache
              this.translateCache[cacheKey] = data.translatedText;
              this._showTranslation(eventId, data.translatedText, targetLang);
          } else {
              if (descEl) { descEl.textContent = '⚠�? ' + (data.message || 'Traduction indisponible.'); descEl.style.display = 'block'; }
          }
      } catch (err) {
          console.error('Erreur traduction:', err);
          if (descEl) { descEl.textContent = '⚠�? Service de traduction indisponible.'; descEl.style.display = 'block'; }
      } finally {
          if (btn) {
              btn.textContent = targetLang === 'ar' ? '�? عربي' : '�? English';
              btn.disabled = false;
          }
      }
  },

  _showTranslation(eventId, text, lang) {
      const descEl   = document.getElementById(`desc-translated-${eventId}`);
      const frEl     = document.getElementById(`desc-fr-${eventId}`);
      const resetBtn = document.getElementById(`btn-reset-${eventId}`);

      if (descEl) {
          descEl.textContent  = text;
          descEl.style.display = 'block';
          descEl.dir = (lang === 'ar') ? 'rtl' : 'ltr';
          descEl.style.textAlign = (lang === 'ar') ? 'right' : 'left';
      }
      if (frEl)     frEl.style.display = 'none';
      if (resetBtn) resetBtn.style.display = 'inline-block';
  },

  resetDesc(eventId) {
      const descEl   = document.getElementById(`desc-translated-${eventId}`);
      const frEl     = document.getElementById(`desc-fr-${eventId}`);
      const resetBtn = document.getElementById(`btn-reset-${eventId}`);

      if (descEl)   { descEl.style.display = 'none'; descEl.textContent = ''; }
      if (frEl)     frEl.style.display = 'block';
      if (resetBtn) resetBtn.style.display = 'none';
  },

  async unsubscribeEvent(id) {
    const e = this.events.find(x => x.id === id);
    if (!e || !e.userSubscribed) return;
    
    if (!confirm("Êtes-vous sûr de vouloir vous désinscrire de cet événement ?\nVotre inscription sera marquée comme annulée.")) return;

    try {
        const user = App.getCurrentUser();
        const studentId = user?.id || null;
        const email     = user?.email || '';

        const response = await fetch(this.endpoint('Controller/EventParticipationController.php'), {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'cancel_participation',
                evenement_id: id,
                student_id: studentId,
                email: email
            })
        });
        const data = await response.json();
        if (data.success) {
            e.registered--;
            e.userSubscribed = false;
            this.render();
            alert('Désinscription effectuée. Votre inscription est marquée comme annulée.');
        } else {
            alert('Erreur: ' + data.message);
        }
    } catch(err) {
        console.error(err);
        alert('Erreur de connexion au serveur.');
    }
  },

  render() {
    const validatedEvents = this.events;
    
    const totalAvail = validatedEvents.filter(e => e.registered < e.capacity).length;
    const totalSubs = validatedEvents.filter(e => e.userSubscribed).length;
    const totalFull = validatedEvents.filter(e => e.registered >= e.capacity).length;
    
    const statAvail = document.getElementById('stat-available');
    const statSubs = document.getElementById('stat-subscribed');
    const statFull = document.getElementById('stat-full');
    
    if (statAvail) statAvail.innerText = totalAvail;
    if (statSubs) statSubs.innerText = totalSubs;
    if (statFull) statFull.innerText = totalFull;

    let displayList = [...validatedEvents];
    if (this.filter === 'my') {
      displayList = displayList.filter(e => e.userSubscribed);
    }

    // 1. RECHERCHE
    const searchInput = document.getElementById('searchInput');
    if (searchInput && searchInput.value.trim() !== '') {
        const term = searchInput.value.trim().toLowerCase();
        displayList = displayList.filter(e => 
            (e.title && e.title.toLowerCase().includes(term)) || 
            (e.location && e.location.toLowerCase().includes(term))
        );
    }

    // 2. TRI
    const sortSelect = document.getElementById('sortSelect');
    if (sortSelect) {
        const sortVal = sortSelect.value;
        displayList.sort((a, b) => {
            if (sortVal === 'date_asc') {
                return new Date(a.date) - new Date(b.date);
            } else if (sortVal === 'date_desc') {
                return new Date(b.date) - new Date(a.date);
            } else if (sortVal === 'title_asc') {
                return a.title.localeCompare(b.title);
            }
            return 0;
        });
    }

    const container = document.getElementById('events-container');
    if (!container) return;
    
    if (displayList.length === 0) {
      container.innerHTML = `<div style="grid-column: 1 / -1; padding: 40px; text-align: center; color: var(--color-text-muted); background: var(--color-panel-bg); border-radius: 8px;">Aucun événement.</div>`;
      return;
    }

    container.innerHTML = displayList.map(e => {
      const typeEv = e.type || 'Présentiel';
      const badgeTypeClass = typeEv === 'Présentiel' ? 'badge-presentiel' : 'badge-online';
      
      const isFull = e.registered >= e.capacity;
      const progressPercent = e.capacity > 0 ? (e.registered / e.capacity) * 100 : 100;
      let barClass = 'progress-bar-success';
      if (progressPercent > 70) barClass = 'progress-bar-warning';
      if (progressPercent >= 100) barClass = 'progress-bar-danger';
      
      let btnHtml = '';
      if(e.userSubscribed) {
        btnHtml = `<button class="btn btn-outline" style="width:100%; border-color:var(--color-danger); color:var(--color-danger);" onclick="EventsStudent.unsubscribeEvent(${e.id})"><i class="fa-solid fa-xmark"></i> Se désinscrire</button>`;
      } else if(isFull) {
        btnHtml = `<button class="btn btn-secondary" style="width:100%; opacity:0.6; cursor:not-allowed;" disabled><i class="fa-solid fa-lock"></i> Complet</button>`;
      } else {
        btnHtml = `<button class="btn btn-primary" style="width:100%;" onclick="EventsStudent.openSubscribeModal(${e.id})"><i class="fa-solid fa-plus"></i> S'inscrire</button>`;
      }

      return `<div class="event-card">
          <div class="card-img-wrapper" style="background-image: url('${e.displayImg}');">
              <div style="position: absolute; inset: 0; background: linear-gradient(to bottom, rgba(0,0,0,0.4) 0%, transparent 40%, #1e293b 100%);"></div>
              
              <div class="event-card-header" style="z-index: 10;">
                  <div class="badges">
                      <span class="badge ${badgeTypeClass}">${typeEv}</span>
                      ${e.userSubscribed ? '<span class="badge badge-valide"><i class="fa-solid fa-check"></i> Inscrit</span>' : ''}
                      ${isFull && !e.userSubscribed ? '<span class="badge badge-complet">Complet</span>' : ''}
                  </div>
              </div>
          </div>

          <div class="card-body">
              <h4 class="event-title">${e.title}</h4>

              <!-- Météo (seulement présentiel + date future) -->
              <div id="weather-${e.id}" style="margin-bottom:6px;"></div>

              <!-- Description + boutons traduction -->
              <div id="desc-fr-${e.id}" class="event-description" data-original="${e.desc.replace(/"/g, '&quot;')}">${e.desc}</div>
              <div id="desc-translated-${e.id}" class="event-description" style="display:none; direction:auto; border-left:3px solid #fe5516; padding-left:8px; margin-top:4px; color:#94a3b8; font-style:italic;"></div>
              ${e.desc && typeof CareMealTranslate !== 'undefined' && typeof CareMealTranslate.getBtnsHTML === 'function' ? CareMealTranslate.getBtnsHTML(e.id, true) : ''}
              
              <div style="margin-top: auto; padding-top: 10px;">
                  <div class="event-detail">
                      <i class="fa-regular fa-calendar"></i>
                      <span>${this.formatDate(e.date)} | ${e.start.substring(0,5)} → ${e.end.substring(0,5)}</span>
                  </div>
                  <div class="event-detail">
                      <i class="fa-solid fa-location-dot"></i>
                      <span>${e.location}</span>
                  </div>
                  <div class="event-detail">
                      <i class="fa-solid fa-users"></i>
                      <div style="flex:1;">
                          <div style="display:flex; justify-content:space-between; font-size:0.85rem;">
                              <span>${e.registered} / ${e.capacity} inscrits</span>
                              <span>${Math.round(progressPercent)}%</span>
                          </div>
                          <div class="progress-container">
                              <div class="progress-bar ${barClass}" style="width: ${progressPercent}%"></div>
                          </div>
                      </div>
                  </div>
              </div>
              <div style="margin-top:15px;">
                ${btnHtml}
              </div>
          </div>
      </div>`;
    }).join('');

    // Charger la météo pour les événements présentiel
    if (typeof CareMealWeather !== 'undefined') {
        CareMealWeather.loadAll(displayList);
    }
  }
};

document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('events-container')) {
      EventsStudent.init().then(() => {
        const params = new URLSearchParams(window.location.search);
        const view = (params.get('view') || "").toLowerCase();
        const hash = (window.location.hash || "").toLowerCase();
        const wantsMy = view === "my" || hash === "#my" || hash === "#inscriptions" || hash === "#participations";
        if (wantsMy) {
          EventsStudent.setFilter('my');
        }
      });
      
      const btnAll = document.getElementById('filter-all');
      const btnMy = document.getElementById('filter-my');
      
      if(btnAll) btnAll.addEventListener('click', () => EventsStudent.setFilter('all'));
      if(btnMy) btnMy.addEventListener('click', () => EventsStudent.setFilter('my'));

      const searchInput = document.getElementById('searchInput');
      const sortSelect = document.getElementById('sortSelect');
      
      if(searchInput) searchInput.addEventListener('input', () => EventsStudent.render());
      if(sortSelect) sortSelect.addEventListener('change', () => EventsStudent.render());
  }
});

