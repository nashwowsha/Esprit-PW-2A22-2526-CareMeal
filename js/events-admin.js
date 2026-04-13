const EventsAdmin = {
    events: [],
    currentFilter: 'Tous',
    searchQuery: '',

    init() {
        const user = App.getCurrentUser();
        if (!user || user.role !== 'admin') {
            window.location.href = "../login.php";
            return;
        }
        this.loadEvents();
        this.renderStats();
        this.renderList();
    },

    loadEvents() {
        this.events = App.getEvents() || [];
    },

    handleSearch() {
        this.searchQuery = document.getElementById('searchInput').value.toLowerCase();
        this.renderList();
    },

    setFilter(filter, btnElement) {
        this.currentFilter = filter;
        
        const btns = document.getElementById('filterBtns').querySelectorAll('button');
        btns.forEach(b => {
             b.classList.remove('btn-primary');
             b.classList.add('btn-secondary');
        });
        if(btnElement) {
            btnElement.classList.remove('btn-secondary');
            btnElement.classList.add('btn-primary');
        }
        this.renderList();
    },

        renderStats() {
        const total = this.events.length;
        const pending = this.events.filter(e => e.status === 'En attente').length;
        
        let participants = 0;
        let presents = 0;
        
        this.events.forEach(e => {
            if (e.participants) {
                participants += e.participants.length;
                presents += e.participants.filter(p => p.status === 'Présent').length;
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

    renderList() {
        document.getElementById('events-main-view').style.display = 'block';
        document.getElementById('event-examine-view').style.display = 'none';

        const pendingContainer = document.getElementById('pending-container');
        const allTable = document.getElementById('all-events-table');

        let filteredEvents = this.events.filter(e => {
            const term = this.searchQuery;
            return e.title.toLowerCase().includes(term) || (e.partnerName && e.partnerName.toLowerCase().includes(term));
        });

        if (this.currentFilter === 'En attente') {
            filteredEvents = filteredEvents.filter(e => e.status === 'En attente');
        } else if (this.currentFilter === 'En ligne') {
            filteredEvents = filteredEvents.filter(e => e.type === 'En ligne');
        } else if (this.currentFilter === 'Terminés') {
            filteredEvents = filteredEvents.filter(e => e.status === 'Terminé');
        }

        const pendingEvents = filteredEvents.filter(e => e.status === 'En attente');
        
        if (pendingEvents.length === 0) {
            pendingContainer.innerHTML = '<p class="text-muted" style="grid-column:1/-1; padding:2rem; background:white; border-radius:8px; text-align:center;">Aucun événement en attente de validation.</p>';
        } else {
            pendingContainer.innerHTML = pendingEvents.map(e => `
                <div class="card" style="border-left:4px solid var(--color-warning);">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
                        <h4 style="margin:0;">${e.title}</h4>
                        <span class="badge" style="background:var(--color-warning); color:white; flex-shrink:0;">En attente</span>
                    </div>
                    <div style="font-size:0.85rem; color:var(--color-primary); font-weight:bold; margin-top:4px;">${e.partnerName}</div>
                    <div style="margin-top:8px; font-size:0.9rem;">
                        <div style="margin-bottom:4px;"><i class="fa-regular fa-calendar"></i> ${e.date} (${e.startTime}-${e.endTime})</div>
                        <div style="margin-bottom:4px;"><i class="fa-solid ${e.type === 'En ligne' ? 'fa-video' : 'fa-location-dot'}"></i> ${e.type}</div>
                        <div><i class="fa-solid fa-users"></i> Capacité: ${e.capacity}</div>
                    </div>
                    <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:16px;">
                        <button class="btn btn-success btn-sm" style="flex:1;" onclick="EventsAdmin.validateEvent(${e.id})"><i class="fa-solid fa-check"></i> Valider</button>
                        <button class="btn btn-danger btn-sm" style="flex:1;" onclick="EventsAdmin.rejectEventModal(${e.id})"><i class="fa-solid fa-xmark"></i> Rejeter</button>
                        <button class="btn btn-secondary btn-sm" style="flex:1;" onclick="EventsAdmin.examineEvent(${e.id})"><i class="fa-solid fa-eye"></i> Examiner</button>
                    </div>
                </div>
            `).join('');
        }

        const otherEvents = filteredEvents.filter(e => e.status !== 'En attente');
        if (otherEvents.length === 0) {
            allTable.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Aucun événement à afficher.</td></tr>';
        } else {
            const statsColors = {
                'En attente': 'var(--color-warning)',
                'Validé / Planifié': 'var(--color-success)',
                'En cours': 'var(--color-primary)',
                'Terminé': 'var(--color-text-muted)',
                'Rejeté': 'var(--color-danger)'
            };
            allTable.innerHTML = otherEvents.map(e => `
                <tr>
                    <td><strong>${e.title}</strong></td>
                    <td>${e.partnerName}</td>
                    <td>${e.date} <br><small class="text-muted">${e.startTime} - ${e.endTime}</small></td>
                    <td><span class="badge" style="background:${statsColors[e.status] || 'gray'}; color:white;">${e.status}</span></td>
                    <td>
                        <button class="btn btn-secondary btn-sm" onclick="EventsAdmin.examineEvent(${e.id})"><i class="fa-solid fa-eye"></i> Détails</button>
                        <button class="btn btn-danger btn-sm" onclick="EventsAdmin.deleteEvent(${e.id})"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>
            `).join('');
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
                            <li><strong>Capacité:</strong> ${ev.capacity} participants</li>
                            <li><strong>Inscrits:</strong> ${ev.participants ? ev.participants.length : 0}</li>
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

    validateEvent(id, fromExamine = false) {
        if(confirm('Confirmez-vous la validation de cet événement ? Il sera visible par les étudiants.')) {
            const ev = this.events.find(e => e.id === id);
            if(ev) {
                ev.status = 'Validé / Planifié';
                ev.rejectionReason = null;
                App.saveEvents(this.events);
                Components.showToast('Événement validé avec succès', 'success');
                if(fromExamine) this.examineEvent(id);
                else this.renderList();
            }
        }
    },

    rejectEventModal(id) {
        document.getElementById('rejectForm').reset();
        document.getElementById('rejectEventId').value = id;
        Components.openModal('rejectModal');
    },

    confirmReject() {
        const reason = document.getElementById('rejectReason').value.trim();
        if(!reason) {
            Components.showToast('Veuillez spécifier un motif de refus.', 'error');
            return;
        }
        const id = document.getElementById('rejectEventId').value;
        const ev = this.events.find(e => e.id == id);
        
        if (ev) {
            ev.status = 'Rejeté';
            ev.rejectionReason = reason;
            App.saveEvents(this.events);
            Components.closeModal('rejectModal');
            Components.showToast("L'événement a été rejeté.", 'success');
            
            if(document.getElementById('event-examine-view').style.display === 'block') {
                this.examineEvent(id);
            } else {
                this.renderList();
            }
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




