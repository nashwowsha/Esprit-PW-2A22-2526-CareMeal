const EventsAdmin = {
    events: [],
    currentFilter: 'Tous',
    searchQuery: '',

    async init() {
        const user = App.getCurrentUser();
        if (!user || user.role !== 'admin') {
            window.location.href = "../login.php";
            return;
        }
        await this.loadEvents();
    },

    async loadEvents() {
        try {
            const response = await fetch('/projet2a22/Controller/EventController.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_all' })
            });
            const result = await response.json();
            if(result.success && result.events) {
                this.events = result.events;
            } else {
                this.events = [];
            }
        } catch(e) {
            console.error('Erreur chargement des événements', e);
            this.events = [];
        }
        this.renderStats();
        this.renderList();
    },

    handleSearch() {
        this.searchQuery = document.getElementById('searchInput').value.toLowerCase();
        this.renderList();
    },

    setFilter(filter, btnElement) {
        this.currentFilter = filter;
        
        const btns = document.getElementById('filterBtns').querySelectorAll('button');
        btns.forEach(b => {
             b.className = 'btn btn-outline';
             b.style.background = 'transparent';
             b.style.border = '1px solid rgba(255,255,255,0.2)';
             b.style.color = 'white';
        });
        if(btnElement) {
            btnElement.className = 'btn btn-primary';
            btnElement.style.background = 'var(--color-primary)';
            btnElement.style.border = '1px solid var(--color-primary)';
            btnElement.style.color = 'white';
        }
        this.renderList();
    },

        renderStats() {
        const total = this.events.length;
        const pending = this.events.filter(e => this.getEventValidationStatus(e) === 'En attente').length;
        
        let participants = 0;
        let presents = 0;
        
        this.events.forEach(e => {
            if (true) {
                participants += parseInt(e.inscrits || 0);
                presents += 0;
            }
        });

        const tx = participants > 0 ? Math.round((presents / participants) * 100) : 0;

        const elTotal = document.getElementById('stat-total');
        const elPending = document.getElementById('stat-pending');
        const elParts = document.getElementById('stat-participants');
        const elPres = document.getElementById('stat-presence');

        if(elTotal) elTotal.textContent = total;
        if(elPending) elPending.textContent = pending;
        if(elParts) elParts.textContent = participants;
        if(elPres) elPres.textContent = tx + '%';
    },

    getEventValidationStatus(e) {
        if (e.statut_validation === 'En attente' || e.status === 'En attente') return 'En attente';
        if (e.statut_validation === 'Validé' || e.status === 'Validé / Planifié' || e.status === 'Validé') return 'Validé';
        if (e.statut_validation === 'Rejeté' || e.status === 'Rejeté') return 'Rejeté';
        if (e.status === 'Terminé' || e.statut === 'Terminé') return 'Terminé';
        if (e.status === 'En cours' || e.statut === 'En cours') return 'En cours';
        return e.statut_validation || e.status || e.statut || 'N/A';
    },

    createCardHTML(e) {
        const t = e.title || e.titre || 'Sans titre';
        const p = e.partnerName || e.partnerId || 'Inconnu';
        const d = e.date || e.date_evenement || '-';
        const st = e.startTime || e.heure_debut || '';
        const en = e.endTime || e.heure_fin || '';
        const timeStr = en ? `${st} → ${en}` : st;
        const capacity = e.capacite_max || e.capacity || e.capacite || 0;
        const participantsCount = e.inscrits || 0;
        const status = this.getEventValidationStatus(e);
        const desc = e.description || '';
        const img = e.displayImg || "https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&q=80&w=800";
        const motif = e.motif_refus || e.rejectionReason || '';
        const typeEv = e.type || e.type_evenement || 'Présentiel';
        const badgeTypeClass = typeEv === 'Présentiel' ? 'badge-presentiel' : 'badge-online';
        const loc = e.location || e.lieu || 'Non spécifié';

        let badgeStatusHtml = '';
        if (status === 'En attente') badgeStatusHtml = '<span class="badge badge-attente">En attente</span>';
        if (status === 'Validé' || status === 'Validé / Planifié') badgeStatusHtml = '<span class="badge badge-valide">Validé</span>';
        if (status === 'Rejeté') badgeStatusHtml = '<span class="badge badge-refuse">Rejeté</span>';

        let actionsHtml = '';
        let badgeDeTraitement = '';
        if (status === 'En attente') {
            actionsHtml = `
                <div style="display:flex; gap:10px; margin-top: 16px;">
                    <button class="btn" style="flex:1; background:rgba(16, 185, 129, 0.15); color:#10b981; border:1px solid rgba(16,185,129,0.3); border-radius:6px; padding:10px; font-weight:600; cursor:pointer;" onclick="EventsAdmin.validateEvent(${e.id || e.id_evenement})">
                        <i class="fa-solid fa-check"></i> Valider
                    </button>
                    <button class="btn" style="flex:1; background:rgba(239, 68, 68, 0.15); color:#ef4444; border:1px solid rgba(239,68,68,0.3); border-radius:6px; padding:10px; font-weight:600; cursor:pointer;" onclick="EventsAdmin.rejectEventModal(${e.id || e.id_evenement})">
                        <i class="fa-solid fa-xmark"></i> Rejeter
                    </button>
                </div>
            `;
        } else {
             badgeDeTraitement = `<span style="font-size:0.75rem; color: var(--color-text-muted); background:rgba(0,0,0,0.5); padding:4px 10px; border-radius:4px; position:absolute; top:12px; right:12px; z-index:5;">Traité</span>`;
        }

        let motifHtml = '';
        if (status === 'Rejeté' && motif) {
            motifHtml = `
            <div style="background: rgba(239, 68, 68, 0.1); border-left: 3px solid #ef4444; padding: 10px; margin-bottom: 10px; font-size: 0.85rem; color: #fca5a5; border-radius: 4px;">
                <strong>Motif du refus :</strong> ${motif}
            </div>`;
        }
        
        let descHtml = desc ? `<div class="event-description">${desc}</div>` : '';

        return `
        <div class="event-card">
            <div class="card-img-wrapper" style="background-image: url('${img}');">
                <div style="position: absolute; inset: 0; background: linear-gradient(to bottom, rgba(0,0,0,0.4) 0%, transparent 40%, #1e293b 100%);"></div>

                <div class="event-card-header" style="z-index: 10;">
                    <div class="badges">
                        <span class="badge ${badgeTypeClass}">${typeEv}</span>
                         ${badgeStatusHtml}
                    </div>
                </div>
                ${badgeDeTraitement}
            </div>

            <div class="card-body">
                <h4 class="event-title">${t}</h4>
                <div style="font-size:0.85rem; color:var(--color-text-muted); margin-bottom:8px;">ID Partenaire: ${p}</div>
                ${descHtml}
                ${motifHtml}

                <div style="margin-top: auto;">
                    <div class="event-detail">
                        <i class="fa-regular fa-calendar"></i>
                        <span>${d} | ${timeStr}</span>
                    </div>
                    <div class="event-detail">
                        <i class="fa-solid fa-location-dot"></i>
                        <span>${loc}</span>
                    </div>
                    <div class="event-detail">
                        <i class="fa-solid fa-users"></i>
                        <span>${participantsCount} / ${capacity} inscrits</span>
                    </div>
                </div>
                ${actionsHtml}
            </div>
        </div>`;
    },

    renderList() {
        document.getElementById('events-main-view').style.display = 'block';
        document.getElementById('event-examine-view').style.display = 'none';

        const pendingContainer = document.getElementById('pending-container');
        const allTable = document.getElementById('all-events-table');

        let filteredEvents = this.events.filter(e => {
            const term = this.searchQuery;
            const title = e.title || e.titre || '';
            const partner = e.partnerName || e.partnerId || '';
            return title.toLowerCase().includes(term) || partner.toLowerCase().includes(term);
        });

        if (this.currentFilter === 'En attente') {
            filteredEvents = filteredEvents.filter(e => this.getEventValidationStatus(e) === 'En attente');
        } else if (this.currentFilter === 'En ligne') {
            filteredEvents = filteredEvents.filter(e => (e.type || e.type_evenement || '') === 'En ligne');
        } else if (this.currentFilter === 'Terminés') {
            filteredEvents = filteredEvents.filter(e => this.getEventValidationStatus(e) === 'Terminé');
        } else if (this.currentFilter === 'En attente de validation') {
            filteredEvents = filteredEvents.filter(e => this.getEventValidationStatus(e) === 'En attente');
        }

        // Apply Sorting
        const sortSel = document.getElementById('sortSelect');
        if (sortSel) {
            const sortVal = sortSel.value;
            filteredEvents.sort((a, b) => {
                if (sortVal === 'title_asc') {
                    const titleA = a.title || a.titre || '';
                    const titleB = b.title || b.titre || '';
                    return titleA.localeCompare(titleB);
                } else if (sortVal === 'date_asc' || sortVal === 'date_desc') {
                    const dateA = new Date(a.date || a.date_evenement || 0).getTime();
                    const dateB = new Date(b.date || b.date_evenement || 0).getTime();
                    return sortVal === 'date_asc' ? dateA - dateB : dateB - dateA;
                }
                return 0;
            });
        }

        const pendingEvents = filteredEvents.filter(e => this.getEventValidationStatus(e) === 'En attente');
        
                if (pendingEvents.length === 0) {
            pendingContainer.innerHTML = '<p class="text-muted" style="grid-column:1/-1; padding:2rem; background:rgba(0,0,0,0.2); border-radius:6px; text-align:center;">Aucun événement en attente de validation.</p>';
        } else {
            pendingContainer.innerHTML = pendingEvents.map(e => this.createCardHTML(e)).join('');
        }

        const otherEvents = filteredEvents;
        if (otherEvents.length === 0) {
            allTable.innerHTML = '<p class="text-muted" style="grid-column:1/-1; padding:2rem; background:rgba(0,0,0,0.2); border-radius:6px; text-align:center;">Aucun événement à afficher.</p>';
        } else {
            allTable.innerHTML = otherEvents.map(e => this.createCardHTML(e)).join('');
        }
      },

    showList() {
        this.renderList();
    },

    examineEvent(id) {
        const ev = this.events.find(e => e.id === id);
        if(!ev) return;
        
        document.getElementById('events-main-view').style.display = 'none';
        document.getElementById('event-examine-view').style.display = 'block';

        let actionButtons = '';
        if (ev.status === 'En attente') {
            actionButtons = `
                <div style="margin-top:24px; padding-top:24px; border-top:1px solid var(--color-border); display:flex; gap:16px; justify-content:center;">
                    <button class="btn btn-danger" style="padding:12px 32px;" onclick="EventsAdmin.rejectEventModal(${ev.id})"><i class="fa-solid fa-xmark"></i> Refuser l'événement</button>
                    <button class="btn btn-success" style="padding:12px 32px;" onclick="EventsAdmin.validateEvent(${ev.id}, true)"><i class="fa-solid fa-check"></i> Valider l'événement</button>
                </div>
            `;
        }

        document.getElementById('examine-content').innerHTML = `
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-bottom:24px;">
                    <h2 style="margin:0; word-break:break-word;"><i class="fa-solid fa-magnifying-glass"></i> Examen: ${ev.title}</h2>
                    <span class="badge" style="font-size:1rem; padding:8px 16px; flex-shrink:0;">${ev.status}</span>
                </div>
                
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:32px;">
                    <div>
                        <h4 style="margin-bottom:16px; color:var(--color-primary);">Informations sur l'événement</h4>
                        <ul style="list-style:none; padding:0; margin:0; line-height:2;">
                            <li><strong>Type:</strong> <span class="badge badge-primary">${ev.type}</span></li>
                            <li><strong>Date:</strong> ${ev.date}</li>
                            <li><strong>Horaires:</strong> ${ev.startTime} à ${ev.endTime}</li>
                            <li><strong>Lieu/Lien:</strong> ${ev.location}</li>
                            <li><strong>Capacité:</strong> ${ev.capacite_max || ev.capacity} participants</li>
                            <li><strong>Inscrits:</strong> ${ev.inscrits || 0}</li>
                        </ul>
                    </div>
                    <div>
                        <h4 style="margin-bottom:16px; color:var(--color-primary);">Description & Partenaire</h4>
                        <p style="background:var(--color-background); padding:16px; border-radius:8px; font-size:0.95rem; min-height:100px;">
                            ${ev.description || 'Aucune description fournie.'}
                        </p>
                        <ul style="list-style:none; padding:0; margin-top:16px; line-height:2;">
                            <li><strong>Partenaire organisateur:</strong> ${ev.partnerName}</li>
                            <li><strong>Date de création:</strong> ${ev.createdAt || 'N/A'}</li>
                            ${ev.rejectionReason ? `<li><strong style="color:var(--color-danger);">Motif du refus:</strong> ${ev.rejectionReason}</li>` : ''}
                        </ul>
                    </div>
                </div>
                ${actionButtons}
            </div>
        `;
    },

    async validateEvent(id, fromExamine = false) {
        if(confirm('Confirmez-vous la validation de cet événement ? Il sera visible par les étudiants.')) {
            try {
                const response = await fetch('/projet2a22/Controller/EventController.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'validate', id_evenement: id })
                });
                const result = await response.json();
                if(result.success) {
                    Components.showToast('Événement validé avec succès', 'success');
                    await this.loadEvents();
                    if(fromExamine) this.examineEvent(id);
                } else {
                    Components.showToast("Erreur lors de la validation", "error");
                }
            } catch(e) {
                console.error(e);
            }
        }
    },

    rejectEventModal(id) {
        const form = document.getElementById('rejectForm');
        if(form) form.reset();
        
        const rejectIdField = document.getElementById('rejectEventId');
        if(rejectIdField) rejectIdField.value = id;
        
        if(typeof Components !== 'undefined' && Components.openModal) {
            Components.openModal('rejectModal');
        }
    },

    async confirmReject() {
        const reasonField = document.getElementById('rejectReason');
        const reason = reasonField ? reasonField.value.trim() : '';
        
        if(!reason) {
            Components.showToast('Veuillez spécifier un motif de refus.', 'error');
            return;
        }
        const idField = document.getElementById('rejectEventId');
        const id = idField ? idField.value : null;

        try {
            const response = await fetch('/projet2a22/Controller/EventController.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'reject', id_evenement: id, raison: reason })
            });
            const result = await response.json();
            
            if(result.success) {
                if(typeof Components !== 'undefined' && Components.closeModal) {
                    Components.closeModal('rejectModal');
                }
                Components.showToast("L'événement a été rejeté.", 'success');
                
                await this.loadEvents();
                const detailView = document.getElementById('event-examine-view');
                if(detailView && detailView.style.display === 'block') {
                    this.examineEvent(id);
                }
            } else {
                 Components.showToast("Erreur lors du rejet de l'événement.", 'error');
            }
        } catch(e) {
             console.error(e);
        }
    },

    deleteEvent(id) {
        if(confirm('Êtes-vous sûr de vouloir supprimer définitivement cet événement ? Cette action est irréversible.')) {
            this.events = this.events.filter(e => e.id !== id);
            App.saveEvents(this.events);
            Components.showToast('Événement supprimé', 'success');
            this.renderList();
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    EventsAdmin.init();
});






