const EventsAdmin = {
    events: [],
    currentFilter: 'Tous',
    searchQuery: '',
    _loadSeq: 0,
    allParticipantsForEvent: [],
    activeParticipantsEventId: null,

    fixMojibake(value) {
        const text = String(value ?? '');
        if (!text) return '';
        return text
            .replace(/Ã©/g, 'é')
            .replace(/Ã¨/g, 'è')
            .replace(/Ãª/g, 'ê')
            .replace(/Ã«/g, 'ë')
            .replace(/Ã /g, 'à')
            .replace(/Ã¢/g, 'â')
            .replace(/Ã®/g, 'î')
            .replace(/Ã¯/g, 'ï')
            .replace(/Ã´/g, 'ô')
            .replace(/Ã»/g, 'û')
            .replace(/Ã¹/g, 'ù')
            .replace(/Ã§/g, 'ç')
            .replace(/Ã‰/g, 'É')
            .replace(/Ã€/g, 'À')
            .replace(/Ã‡/g, 'Ç')
            .replace(/â†’/g, '→')
            .replace(/â€”/g, '—')
            .replace(/â€“/g, '–')
            .replace(/â€˜/g, '‘')
            .replace(/â€™/g, '’')
            .replace(/â€œ/g, '“')
            .replace(/â€/g, '”')
            .replace(/Â/g, '');
    },

    escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    },

    getEventId(eventObj) {
        return Number(eventObj?.id_evenement ?? eventObj?.id ?? 0);
    },

    getPartnerId(eventObj) {
        const raw = eventObj?.createur_id ?? eventObj?.partner_id ?? eventObj?.partnerId ?? null;
        const n = Number(raw);
        return Number.isFinite(n) && n > 0 ? n : null;
    },

    normalizeType(rawType) {
        const fixed = this.fixMojibake(rawType).toLowerCase().trim();
        if (fixed.includes('ligne')) return 'En ligne';
        return 'Présentiel';
    },

    getEventValidationStatus(e) {
        const sv = this.fixMojibake(e.statut_validation ?? '').trim().toLowerCase();
        const st = this.fixMojibake(e.status ?? e.statut ?? '').trim().toLowerCase();
        const combined = `${sv} ${st}`;

        if (combined.includes('en attente')) return 'En attente';
        if (combined.includes('rejet')) return 'Rejeté';
        if (combined.includes('valid')) return 'Validé';
        if (combined.includes('termin')) return 'Terminé';
        if (combined.includes('en cours')) return 'En cours';
        return this.fixMojibake(e.statut_validation ?? e.status ?? e.statut ?? 'N/A');
    },

    async init() {
        const user = App.getCurrentUser();
        if (!user || user.role !== 'admin') {
            window.location.href = '../login.php';
            return;
        }

        const typeSelect = document.getElementById('eventFormType');
        if (typeSelect) {
            typeSelect.addEventListener('change', () => this.toggleEventFormTypeFields());
        }

        const seq = ++this._loadSeq;
        await this.loadEvents();
        if (seq !== this._loadSeq) return;
    },

    async loadEvents() {
        try {
            const response = await fetch(App.apiUrl('Controller/EventController.php'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_all' })
            });
            const result = await response.json();
            this.events = result.success && Array.isArray(result.events) ? result.events : [];
        } catch (error) {
            console.error('Erreur chargement événements', error);
            this.events = [];
        }

        this.renderStats();
        this.renderList();
    },

    renderStats() {
        const total = this.events.length;
        const pending = this.events.filter((e) => this.getEventValidationStatus(e) === 'En attente').length;
        let participants = 0;
        this.events.forEach((e) => { participants += Number(e.inscrits || 0); });
        const tx = participants > 0 ? 0 : 0;

        const elTotal = document.getElementById('stat-total');
        const elPending = document.getElementById('stat-pending');
        const elParts = document.getElementById('stat-participants');
        const elPres = document.getElementById('stat-presence');

        if (elTotal) elTotal.textContent = total;
        if (elPending) elPending.textContent = pending;
        if (elParts) elParts.textContent = participants;
        if (elPres) elPres.textContent = `${tx}%`;
    },

    handleSearch() {
        const input = document.getElementById('searchInput');
        this.searchQuery = input ? input.value.trim().toLowerCase() : '';
        this.renderList();
    },

    setFilter(filter, btnElement) {
        this.currentFilter = filter === 'Termines' ? 'Terminés' : filter;

        const root = document.getElementById('filterBtns');
        if (root) {
            root.querySelectorAll('button').forEach((btn) => {
                btn.className = 'btn btn-secondary';
            });
        }
        if (btnElement) btnElement.className = 'btn btn-primary';

        this.renderList();
    },

    _eventTitleSearchText(e) {
        return this.fixMojibake(e.title ?? e.titre ?? '');
    },

    _eventPartnerSearchText(e) {
        return this.fixMojibake(e.partner_name ?? e.partnerName ?? e.createur_id ?? e.partnerId ?? '');
    },

    createCardHTML(e) {
        const eventId = this.getEventId(e);
        const partnerId = this.getPartnerId(e);
        const title = this.escapeHtml(this.fixMojibake(e.title || e.titre || 'Sans titre'));
        const partnerIdRaw = e.createur_id ?? e.partner_id ?? e.partnerId ?? '';
        const partnerIdNum = Number(partnerIdRaw);
        const partnerIdDisplay = Number.isFinite(partnerIdNum) && partnerIdNum > 0 ? String(partnerIdNum) : 'N/A';
        const date = this.escapeHtml(this.fixMojibake(e.date || e.date_evenement || '-'));
        const start = this.escapeHtml(this.fixMojibake(e.startTime || e.heure_debut || ''));
        const end = this.escapeHtml(this.fixMojibake(e.endTime || e.heure_fin || ''));
        const timeStr = end ? `${start} → ${end}` : start;
        const status = this.getEventValidationStatus(e);
        const description = this.fixMojibake(e.description || '');
        const image = e.displayImg || 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&q=80&w=800';
        const motif = this.escapeHtml(this.fixMojibake(e.motif_refus || e.rejectionReason || ''));
        const eventType = this.normalizeType(e.type || e.type_evenement);
        const location = this.escapeHtml(this.fixMojibake(e.location || e.lieu || 'Non spécifié'));
        const capacity = Number(e.capacite_max || e.capacity || e.capacite || 0);
        const participantsCount = Number(e.inscrits || 0);
        const badgeTypeClass = eventType === 'Présentiel' ? 'badge-presentiel' : 'badge-online';

        let statusBadgeHtml = '';
        if (status === 'En attente') statusBadgeHtml = '<span class="badge badge-attente">En attente</span>';
        if (status === 'Validé') statusBadgeHtml = '<span class="badge badge-valide">Validé</span>';
        if (status === 'Rejeté') statusBadgeHtml = '<span class="badge badge-refuse">Rejeté</span>';
        if (status === 'Terminé') statusBadgeHtml = '<span class="badge" style="background:#475569;">Terminé</span>';

        const participantsBtn = `
            <button type="button" onclick="EventsAdmin.showParticipants(${eventId})"
                style="width:100%; margin-top:10px; padding:10px; border-radius:8px; border:1px solid #CBD5E1; background:#F1F5F9; color:#0F172A; font-size:0.88rem; cursor:pointer; font-weight:600; display:flex; align-items:center; justify-content:center; gap:8px;">
                <i class="fa-solid fa-users" style="color:#64748B;"></i> Voir les participants (${participantsCount})
            </button>`;

        const editDeleteBtns = `
            <div style="display:flex; gap:8px; margin-top:10px;">
                <button type="button" class="btn btn-secondary" style="flex:1;" onclick="EventsAdmin.openEventFormModal(${eventId})">
                    <i class="fa-solid fa-pen"></i> Modifier
                </button>
                <button type="button" class="btn btn-danger" style="flex:1;" onclick="EventsAdmin.deleteEvent(${eventId})" ${partnerId ? '' : 'disabled'}>
                    <i class="fa-solid fa-trash"></i> Supprimer
                </button>
            </div>`;

        let moderationBtns = '';
        if (status === 'En attente') {
            moderationBtns = `
                <div style="display:flex; gap:10px; margin-top:10px;">
                    <button type="button" class="btn" style="flex:1; background:#059669; color:#fff; border:1px solid #047857; border-radius:8px; padding:10px; font-weight:600; cursor:pointer;" onclick="EventsAdmin.validateEvent(${eventId})">
                        <i class="fa-solid fa-check"></i> Valider
                    </button>
                    <button type="button" class="btn" style="flex:1; background:#DC2626; color:#fff; border:1px solid #B91C1C; border-radius:8px; padding:10px; font-weight:600; cursor:pointer;" onclick="EventsAdmin.rejectEventModal(${eventId})">
                        <i class="fa-solid fa-xmark"></i> Rejeter
                    </button>
                </div>`;
        }

        const translateBtns = typeof CareMealTranslate !== 'undefined' && typeof CareMealTranslate.getBtnsHTML === 'function'
            ? CareMealTranslate.getBtnsHTML(eventId, true)
            : '';
        const descEscaped = this.escapeHtml(description);
        const descHtml = description
            ? `
                <div id="desc-fr-${eventId}" class="event-description" data-original="${descEscaped}">${descEscaped}</div>
                <div id="desc-translated-${eventId}" class="event-description" style="display:none; direction:auto; border-left:3px solid #fe5516; padding-left:8px; color:#64748b; font-style:italic;"></div>
                ${translateBtns}
            `
            : '';

        const rejectionHtml = status === 'Rejeté' && motif
            ? `
                <div style="background:rgba(239,68,68,0.1); border-left:3px solid #ef4444; padding:10px; margin-bottom:10px; font-size:0.85rem; color:#b91c1c; border-radius:4px;">
                    <strong>Motif du refus :</strong> ${motif}
                </div>
            `
            : '';

        return `
            <div class="event-card" data-event-id="${eventId}">
                <div class="card-img-wrapper" style="background-image:url('${image}');">
                    <div style="position:absolute; inset:0; pointer-events:none; background:linear-gradient(to bottom, rgba(0,0,0,0.35) 0%, transparent 45%, rgba(255,255,255,0.95) 100%);"></div>
                    <div class="event-card-header" style="z-index:10; pointer-events:none;">
                        <div class="badges">
                            <span class="badge ${badgeTypeClass}">${this.escapeHtml(eventType)}</span>
                            ${statusBadgeHtml}
                        </div>
                    </div>
                </div>
                <div class="card-body" style="position:relative; z-index:2;">
                    <h4 class="event-title">${title}</h4>
                    <div style="font-size:0.85rem; color:var(--color-text-muted); margin-bottom:8px;">ID partenaire : ${this.escapeHtml(partnerIdDisplay)}</div>
                    <div id="weather-${eventId}" style="margin-bottom:6px;"></div>
                    ${descHtml}
                    ${rejectionHtml}

                    <div style="margin-top:auto;">
                        <div class="event-detail"><i class="fa-regular fa-calendar"></i><span>${date} | ${timeStr}</span></div>
                        <div class="event-detail"><i class="fa-solid fa-location-dot"></i><span>${location}</span></div>
                        <div class="event-detail"><i class="fa-solid fa-users"></i><span>${participantsCount} / ${capacity} inscrits</span></div>
                    </div>

                    ${moderationBtns}
                    ${editDeleteBtns}
                    ${participantsBtn}
                </div>
            </div>
        `;
    },

    renderList() {
        const mainView = document.getElementById('events-main-view');
        const examineView = document.getElementById('event-examine-view');
        if (mainView) mainView.style.display = 'block';
        if (examineView) examineView.style.display = 'none';

        const pendingContainer = document.getElementById('pending-container');
        const allTable = document.getElementById('all-events-table');
        if (!pendingContainer || !allTable) return;

        const term = (this.searchQuery || '').trim().toLowerCase();
        let filteredEvents = this.events.filter((e) => {
            if (!term) return true;
            const title = this._eventTitleSearchText(e).toLowerCase();
            const partner = this._eventPartnerSearchText(e).toLowerCase();
            return title.includes(term) || partner.includes(term);
        });

        const filter = this.currentFilter === 'Termines' ? 'Terminés' : this.currentFilter;
        if (filter === 'En attente' || filter === 'En attente de validation') {
            filteredEvents = filteredEvents.filter((e) => this.getEventValidationStatus(e) === 'En attente');
        } else if (filter === 'En ligne') {
            filteredEvents = filteredEvents.filter((e) => this.normalizeType(e.type || e.type_evenement) === 'En ligne');
        } else if (filter === 'Terminés') {
            filteredEvents = filteredEvents.filter((e) => this.getEventValidationStatus(e) === 'Terminé');
        }

        const sortSel = document.getElementById('sortSelect');
        if (sortSel) {
            const sortVal = sortSel.value;
            filteredEvents.sort((a, b) => {
                if (sortVal === 'title_asc') {
                    return this._eventTitleSearchText(a).localeCompare(this._eventTitleSearchText(b), 'fr');
                }
                if (sortVal === 'date_asc' || sortVal === 'date_desc') {
                    const dateA = new Date(a.date || a.date_evenement || 0).getTime();
                    const dateB = new Date(b.date || b.date_evenement || 0).getTime();
                    return sortVal === 'date_asc' ? dateA - dateB : dateB - dateA;
                }
                return 0;
            });
        }

        const pendingEvents = filteredEvents.filter((e) => this.getEventValidationStatus(e) === 'En attente');
        const otherEvents = filteredEvents.filter((e) => this.getEventValidationStatus(e) !== 'En attente');

        if (pendingEvents.length === 0) {
            pendingContainer.innerHTML = '<p class="text-muted" style="grid-column:1/-1; padding:2rem; background:var(--color-surface); border-radius:6px; text-align:center; box-shadow:0 4px 6px rgba(0,0,0,0.05); color:#64748b;">Aucun événement en attente de validation.</p>';
        } else {
            pendingContainer.innerHTML = pendingEvents.map((e) => this.createCardHTML(e)).join('');
        }

        if (otherEvents.length === 0) {
            allTable.innerHTML = '<p class="text-muted" style="grid-column:1/-1; padding:2rem; background:var(--color-surface); border-radius:6px; text-align:center; box-shadow:0 4px 6px rgba(0,0,0,0.05); color:#64748b;">Aucun événement à afficher ici.</p>';
        } else {
            allTable.innerHTML = otherEvents.map((e) => this.createCardHTML(e)).join('');
        }

        if (typeof CareMealWeather !== 'undefined' && typeof CareMealWeather.loadAll === 'function') {
            CareMealWeather.loadAll(filteredEvents);
        }
    },

    showList() {
        this.renderList();
    },

    examineEvent(id) {
        const ev = this.events.find((e) => this.getEventId(e) === Number(id));
        if (!ev) return;

        const mainView = document.getElementById('events-main-view');
        const examineView = document.getElementById('event-examine-view');
        if (mainView) mainView.style.display = 'none';
        if (examineView) examineView.style.display = 'block';

        const mount = document.getElementById('examine-content') || document.getElementById('admin-detail-content');
        if (!mount) return;

        const title = this.escapeHtml(this.fixMojibake(ev.title || ev.titre || ''));
        const type = this.escapeHtml(this.normalizeType(ev.type || ev.type_evenement));
        const date = this.escapeHtml(this.fixMojibake(ev.date || ev.date_evenement || ''));
        const h1 = this.escapeHtml(this.fixMojibake(ev.startTime || ev.heure_debut || ''));
        const h2 = this.escapeHtml(this.fixMojibake(ev.endTime || ev.heure_fin || ''));
        const lieu = this.escapeHtml(this.fixMojibake(ev.location || ev.lieu || ev.lien_online || ''));
        const partner = this.escapeHtml(this.fixMojibake(ev.partner_name || ev.partnerName || ev.createur_id || '—'));
        const status = this.escapeHtml(this.getEventValidationStatus(ev));

        mount.innerHTML = `
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-bottom:24px;">
                    <h2 style="margin:0; word-break:break-word; color:var(--tf-text, #1A202C);"><i class="fa-solid fa-magnifying-glass"></i> Examen : ${title}</h2>
                    <span class="badge" style="font-size:1rem; padding:8px 16px; flex-shrink:0;">${status}</span>
                </div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:24px;">
                    <div>
                        <ul style="list-style:none; padding:0; margin:0; line-height:2; color:var(--tf-text, #1A202C);">
                            <li><strong>Type :</strong> ${type}</li>
                            <li><strong>Date :</strong> ${date}</li>
                            <li><strong>Horaires :</strong> ${h1} à ${h2}</li>
                            <li><strong>Lieu/Lien :</strong> ${lieu}</li>
                            <li><strong>Capacité :</strong> ${Number(ev.capacite_max || ev.capacity || 0)}</li>
                            <li><strong>Inscrits :</strong> ${Number(ev.inscrits || 0)}</li>
                            <li><strong>Partenaire :</strong> ${partner}</li>
                        </ul>
                    </div>
                </div>
            </div>
        `;
    },

    async validateEvent(id, fromExamine = false) {
        if (!confirm('Confirmez-vous la validation de cet événement ?')) return;
        try {
            const response = await fetch(App.apiUrl('Controller/EventController.php'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'validate', id_evenement: id })
            });
            const result = await response.json();
            if (!result.success) {
                Components.showToast('Erreur lors de la validation.', 'error');
                return;
            }
            Components.showToast('Événement validé avec succès.', 'success');
            await this.loadEvents();
            if (fromExamine) this.examineEvent(id);
        } catch (error) {
            console.error(error);
            Components.showToast('Erreur réseau pendant la validation.', 'error');
        }
    },

    rejectEventModal(id) {
        const form = document.getElementById('rejectForm');
        if (form) form.reset();
        const field = document.getElementById('rejectEventId');
        if (field) field.value = id;
        if (typeof Components !== 'undefined' && Components.openModal) {
            Components.openModal('rejectModal');
        }
    },

    async confirmReject() {
        const reasonField = document.getElementById('rejectReason');
        const idField = document.getElementById('rejectEventId');
        const reason = reasonField ? reasonField.value.trim() : '';
        const id = idField ? Number(idField.value) : 0;

        if (!reason) {
            Components.showToast('Veuillez saisir un motif de refus.', 'error');
            return;
        }
        if (!id) {
            Components.showToast('ID événement invalide.', 'error');
            return;
        }

        try {
            const response = await fetch(App.apiUrl('Controller/EventController.php'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'reject', id_evenement: id, raison: reason })
            });
            const result = await response.json();
            if (!result.success) {
                Components.showToast('Erreur lors du rejet.', 'error');
                return;
            }

            if (typeof Components !== 'undefined' && Components.closeModal) {
                Components.closeModal('rejectModal');
            }
            Components.showToast("L'événement a été rejeté.", 'success');
            await this.loadEvents();
        } catch (error) {
            console.error(error);
            Components.showToast('Erreur réseau pendant le rejet.', 'error');
        }
    },

    toggleEventFormTypeFields() {
        const typeEl = document.getElementById('eventFormType');
        const lieuGroup = document.getElementById('eventFormLieuGroup');
        const lienGroup = document.getElementById('eventFormLienGroup');
        if (!typeEl || !lieuGroup || !lienGroup) return;
        if (this.normalizeType(typeEl.value) === 'En ligne') {
            lieuGroup.style.display = 'none';
            lienGroup.style.display = 'block';
        } else {
            lieuGroup.style.display = 'block';
            lienGroup.style.display = 'none';
        }
    },

    openEventFormModal(eventId = null) {
        const form = document.getElementById('eventCrudForm');
        if (!form) return;
        form.reset();

        const titleEl = document.getElementById('eventFormTitle');
        const idEl = document.getElementById('eventFormId');
        const partnerInput = document.getElementById('eventFormPartnerInput');
        if (idEl) idEl.value = '';
        if (titleEl) titleEl.innerHTML = '<i class="fa-solid fa-calendar-plus"></i> Ajouter un événement';

        if (eventId) {
            const ev = this.events.find((e) => this.getEventId(e) === Number(eventId));
            if (!ev) {
                Components.showToast('Événement introuvable.', 'error');
                return;
            }

            if (idEl) idEl.value = this.getEventId(ev);
            if (titleEl) titleEl.innerHTML = '<i class="fa-solid fa-pen"></i> Modifier un événement';
            const setValue = (id, value) => {
                const el = document.getElementById(id);
                if (el) el.value = value ?? '';
            };

            setValue('eventFormTitre', this.fixMojibake(ev.titre || ev.title || ''));
            setValue('eventFormDescription', this.fixMojibake(ev.description || ''));
            setValue('eventFormDate', ev.date_evenement || ev.date || '');
            setValue('eventFormStart', ev.heure_debut || ev.startTime || '');
            setValue('eventFormEnd', ev.heure_fin || ev.endTime || '');
            setValue('eventFormType', this.normalizeType(ev.type_evenement || ev.type || 'Présentiel'));
            setValue('eventFormCapacite', ev.capacite_max || ev.capacity || '');
            setValue('eventFormLieu', this.fixMojibake(ev.lieu || ev.location || ''));
            setValue('eventFormLien', this.fixMojibake(ev.lien_online || ''));
            if (partnerInput) partnerInput.value = this.getPartnerId(ev) ?? '';
        }

        this.toggleEventFormTypeFields();
        if (typeof Components !== 'undefined' && Components.openModal) {
            Components.openModal('eventFormModal');
        }
    },

    async submitEventForm() {
        const read = (id) => {
            const el = document.getElementById(id);
            return el ? el.value.trim() : '';
        };

        const eventId = read('eventFormId');
        const titre = read('eventFormTitre');
        const description = read('eventFormDescription');
        const dateEvenement = read('eventFormDate');
        const heureDebut = read('eventFormStart');
        const heureFin = read('eventFormEnd');
        const typeEvenement = this.normalizeType(read('eventFormType'));
        const capaciteMax = Number(read('eventFormCapacite'));
        const lieu = read('eventFormLieu');
        const lienOnline = read('eventFormLien');
        const partnerId = Number(read('eventFormPartnerInput'));

        if (!titre || !dateEvenement || !heureDebut || !heureFin || !Number.isFinite(capaciteMax) || capaciteMax <= 0) {
            Components.showToast('Veuillez remplir correctement les champs obligatoires.', 'error');
            return;
        }
        if (!Number.isFinite(partnerId) || partnerId <= 0) {
            Components.showToast("L'ID partenaire est obligatoire.", 'error');
            return;
        }
        if (typeEvenement === 'Présentiel' && !lieu) {
            Components.showToast('Le lieu est obligatoire pour un événement présentiel.', 'error');
            return;
        }
        if (typeEvenement === 'En ligne' && !lienOnline) {
            Components.showToast('Le lien est obligatoire pour un événement en ligne.', 'error');
            return;
        }

        const payload = {
            action: eventId ? 'update' : 'add',
            id_evenement: eventId || undefined,
            titre,
            description,
            date_evenement: dateEvenement,
            heure_debut: heureDebut,
            heure_fin: heureFin,
            type_evenement: typeEvenement,
            lieu: typeEvenement === 'Présentiel' ? lieu : '',
            lien_online: typeEvenement === 'En ligne' ? lienOnline : '',
            capacite_max: capaciteMax,
            createur_type: 'Partenaire',
            createur_id: partnerId
        };

        try {
            const response = await fetch(App.apiUrl('Controller/EventController.php'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await response.json();
            if (!result.success) {
                Components.showToast(this.fixMojibake(result.message || "Erreur lors de l'enregistrement."), 'error');
                return;
            }

            if (typeof Components !== 'undefined' && Components.closeModal) {
                Components.closeModal('eventFormModal');
            }
            Components.showToast(eventId ? 'Événement modifié.' : 'Événement ajouté.', 'success');
            await this.loadEvents();
        } catch (error) {
            console.error(error);
            Components.showToast("Erreur réseau pendant l'enregistrement.", 'error');
        }
    },

    async deleteEvent(id) {
        const ev = this.events.find((e) => this.getEventId(e) === Number(id));
        if (!ev) {
            Components.showToast('Événement introuvable.', 'error');
            return;
        }

        const partnerId = this.getPartnerId(ev);
        if (!partnerId) {
            Components.showToast('Suppression impossible: ID partenaire introuvable.', 'error');
            return;
        }

        if (!confirm('Supprimer définitivement cet événement ?')) return;

        try {
            const response = await fetch(App.apiUrl('Controller/EventController.php'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'delete',
                    id_evenement: this.getEventId(ev),
                    partner_id: partnerId
                })
            });
            const result = await response.json();
            if (!result.success) {
                Components.showToast(this.fixMojibake(result.message || 'Erreur lors de la suppression.'), 'error');
                return;
            }
            Components.showToast('Événement supprimé.', 'success');
            await this.loadEvents();
        } catch (error) {
            console.error(error);
            Components.showToast('Erreur réseau pendant la suppression.', 'error');
        }
    },

    async showParticipants(eventId) {
        this.activeParticipantsEventId = Number(eventId);

        const mainView = document.getElementById('events-main-view');
        const examineView = document.getElementById('event-examine-view');
        if (mainView) mainView.style.display = 'none';
        if (examineView) examineView.style.display = 'block';

        const detailA = document.getElementById('admin-detail-content');
        const detailB = document.getElementById('examine-content');
        if (detailA) detailA.innerHTML = '';
        if (detailB) detailB.innerHTML = '';

        const section = document.getElementById('event-participants-section');
        const tbody = document.getElementById('participants-table-body');
        const badge = document.getElementById('participants-count-badge');
        const searchEl = document.getElementById('part-search');
        if (section) section.style.display = 'block';
        if (searchEl) searchEl.value = '';
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:24px; color:#64748b;"><i class="fa-solid fa-circle-notch fa-spin"></i> Chargement...</td></tr>';
        }

        try {
            const user = typeof App !== 'undefined' && App.getCurrentUser ? App.getCurrentUser() : null;
            const payload = {
                action: 'get_participants_by_event',
                evenement_id: this.activeParticipantsEventId,
                ...(user && user.id ? { user_id: user.id, user_role: user.role || '' } : {})
            };
            const res = await fetch(App.apiUrl('Controller/EventParticipationController.php'), {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (!data.success) {
                this.allParticipantsForEvent = [];
                Components.showToast(this.fixMojibake(data.message || 'Impossible de charger les participants.'), 'error');
            } else {
                this.allParticipantsForEvent = Array.isArray(data.participants) ? data.participants : [];
            }
        } catch (error) {
            console.error('showParticipants', error);
            this.allParticipantsForEvent = [];
            Components.showToast('Erreur réseau lors du chargement des participants.', 'error');
        }

        if (badge) badge.textContent = this.allParticipantsForEvent.length;
        this.renderParticipantsTable(this.allParticipantsForEvent);
    },

    filterParticipants() {
        const term = (document.getElementById('part-search')?.value || '').toLowerCase();
        const filtered = this.allParticipantsForEvent.filter((p) => {
            const nom = this.fixMojibake(p.nom || '').toLowerCase();
            const prenom = this.fixMojibake(p.prenom || '').toLowerCase();
            const email = this.fixMojibake(p.email || '').toLowerCase();
            const univ = this.fixMojibake(p.universite || '').toLowerCase();
            return nom.includes(term) || prenom.includes(term) || email.includes(term) || univ.includes(term);
        });
        this.renderParticipantsTable(filtered);
    },

    renderParticipantsTable(list) {
        const tbody = document.getElementById('participants-table-body');
        if (!tbody) return;
        if (!Array.isArray(list) || list.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:24px; color:#64748b;">Aucun participant inscrit.</td></tr>';
            return;
        }

        const statusColors = {
            Inscrit: { bg: 'rgba(16,185,129,0.15)', color: '#10b981' },
            Présent: { bg: 'rgba(59,130,246,0.15)', color: '#3b82f6' },
            Absent: { bg: 'rgba(245,158,11,0.15)', color: '#f59e0b' },
            Annulé: { bg: 'rgba(239,68,68,0.15)', color: '#ef4444' }
        };

        tbody.innerHTML = list.map((p, i) => {
            const statut = this.fixMojibake(p.statut || '');
            const color = statusColors[statut] || { bg: 'rgba(100,116,139,0.15)', color: '#64748b' };
            const idPart = Number(p.id || p.id_participation || 0);
            return `
                <tr style="border-bottom:1px solid #E2E8F0;">
                    <td style="padding:12px 16px; color:#64748b; font-size:0.85rem;">${i + 1}</td>
                    <td style="padding:12px 16px; font-weight:600; color:#0F172A;">${this.escapeHtml(this.fixMojibake(p.nom || ''))} ${this.escapeHtml(this.fixMojibake(p.prenom || ''))}</td>
                    <td style="padding:12px 16px; color:#334155; font-size:0.88rem;">${this.escapeHtml(this.fixMojibake(p.email || '—'))}</td>
                    <td style="padding:12px 16px; color:#334155; font-size:0.88rem;">${this.escapeHtml(this.fixMojibake(p.telephone || '—'))}</td>
                    <td style="padding:12px 16px; color:#334155; font-size:0.88rem;">${this.escapeHtml(this.fixMojibake(p.universite || '—'))}</td>
                    <td style="padding:12px 16px; color:#334155; font-size:0.88rem;">${this.escapeHtml(this.fixMojibake(p.annee_etude || '—'))}</td>
                    <td style="padding:12px 16px; color:#334155; font-size:0.88rem; white-space:nowrap;">${this.escapeHtml(this.fixMojibake(p.date_inscription || '—'))}</td>
                    <td style="padding:12px 16px;">
                        <span style="padding:4px 12px; border-radius:20px; font-size:0.8rem; font-weight:700; background:${color.bg}; color:${color.color};">${this.escapeHtml(statut)}</span>
                    </td>
                    <td style="padding:12px 16px; white-space:nowrap;">
                        <button type="button" title="Présent" onclick="EventsAdmin.updateParticipantStatus(${idPart}, 'Présent')" style="background:#3b82f6; color:#fff; border:none; padding:6px 10px; border-radius:6px; cursor:pointer; margin-right:4px;">
                            <i class="fa-solid fa-user-check"></i>
                        </button>
                        <button type="button" title="Absent" onclick="EventsAdmin.updateParticipantStatus(${idPart}, 'Absent')" style="background:#f59e0b; color:#fff; border:none; padding:6px 10px; border-radius:6px; cursor:pointer; margin-right:4px;">
                            <i class="fa-solid fa-user-xmark"></i>
                        </button>
                        <button type="button" title="Annuler" onclick="EventsAdmin.updateParticipantStatus(${idPart}, 'Annulé')" style="background:#6b7280; color:#fff; border:none; padding:6px 10px; border-radius:6px; cursor:pointer; margin-right:4px;">
                            <i class="fa-solid fa-ban"></i>
                        </button>
                        <button type="button" title="Supprimer" onclick="EventsAdmin.deleteParticipation(${idPart})" style="background:#ef4444; color:#fff; border:none; padding:6px 10px; border-radius:6px; cursor:pointer;">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    },

    async updateParticipantStatus(participationId, status) {
        if (!participationId) return;
        if (!confirm(`Confirmer le passage du statut à "${status}" ?`)) return;

        try {
            const res = await fetch(App.apiUrl('Controller/EventParticipationController.php'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'update_status', id: participationId, statut: status })
            });
            const data = await res.json();
            if (!data.success) {
                Components.showToast(this.fixMojibake(data.message || 'Mise à jour impossible.'), 'error');
                return;
            }
            Components.showToast('Statut participant mis à jour.', 'success');
            if (this.activeParticipantsEventId) {
                await this.showParticipants(this.activeParticipantsEventId);
            }
        } catch (error) {
            console.error(error);
            Components.showToast('Erreur réseau lors de la mise à jour du statut.', 'error');
        }
    },

    async deleteParticipation(participationId) {
        if (!participationId) return;
        if (!confirm('Supprimer définitivement cette participation ?')) return;

        try {
            const res = await fetch(App.apiUrl('Controller/EventParticipationController.php'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete_participation', id: participationId })
            });
            const data = await res.json();
            if (!data.success) {
                Components.showToast(this.fixMojibake(data.message || 'Suppression impossible.'), 'error');
                return;
            }
            Components.showToast('Participation supprimée.', 'success');
            if (this.activeParticipantsEventId) {
                await this.showParticipants(this.activeParticipantsEventId);
            }
        } catch (error) {
            console.error(error);
            Components.showToast('Erreur réseau lors de la suppression.', 'error');
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    EventsAdmin.init();
});
