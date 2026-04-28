const EventsPartner = {
    events: [],
    currentEvent: null,

    init() {
        if (!App.getCurrentUser() || App.getCurrentUser().role !== 'partner') {
            window.location.href = '../login.html';
            return;
        }
        this.loadEvents();
        this.renderStats();
        this.renderList();
    },

    loadEvents() {
        this.events = App.getEvents().filter(e => e.partnerId === App.getCurrentUser().id);
    },

    saveAllEvents() {
        // Keep other users' events and update ours
        const allOther = App.getEvents().filter(e => e.partnerId !== App.getCurrentUser().id);
        App.saveEvents(allOther.concat(this.events));
    },

    renderStats() {
        const total = this.events.length;
        const pending = this.events.filter(e => e.status === 'En attente').length;
        const validated = this.events.filter(e => e.status === 'Validé / Planifié').length;
        
        let participants = 0;
        this.events.forEach(e => {
            if (e.participants) participants += e.participants.length;
        });

        document.getElementById('stat-total').textContent = total;
        document.getElementById('stat-pending').textContent = pending;
        document.getElementById('stat-validated').textContent = validated;
        document.getElementById('stat-registered').textContent = participants;
    },

    renderList() {
        const container = document.getElementById('events-container');
        container.innerHTML = '';
        document.getElementById('events-list-view').style.display = 'block';
        document.getElementById('event-detail-view').style.display = 'none';

        if (this.events.length === 0) {
            container.innerHTML = '<p class="text-center text-muted" style="grid-column:1/-1; padding:2rem;">Aucun événement créé.</p>';
            return;
        }

        const statsColors = {
            'En attente': 'var(--color-warning)',
            'Validé / Planifié': 'var(--color-success)',
            'En cours': 'var(--color-primary)',
            'Terminé': 'var(--color-text-muted)'
        };

        this.events.forEach(ev => {
            const regCount = ev.participants ? ev.participants.length : 0;
            const progress = ev.capacity ? ((regCount / ev.capacity) * 100) : 0;
            const isOnline = ev.type === 'En ligne';
            const iconBadge = isOnline ? '<i class="fa-solid fa-video"></i> En ligne' : '<i class="fa-solid fa-location-dot"></i> Présentiel';

            let buttonsHtml = '';
            let bottomWarning = '';

            if (ev.status === 'En attente') {
                buttonsHtml = `
                    <button class="btn btn-secondary btn-sm" onclick="EventsPartner.showDetails(${ev.id})"><i class="fa-solid fa-eye"></i> Voir</button>
                    <button class="btn btn-primary btn-sm" onclick="EventsPartner.editEvent(${ev.id})"><i class="fa-solid fa-pen"></i> Modifier</button>
                    <button class="btn btn-danger btn-sm" onclick="EventsPartner.cancelEvent(${ev.id})"><i class="fa-solid fa-trash"></i> Annuler</button>
                `;
                bottomWarning = '<div style="margin-top:12px; font-size:0.8rem; color:var(--color-warning); text-align:center;"><i class="fa-solid fa-triangle-exclamation"></i> ⚠️ Votre événement n\'est pas encore visible...</div>';
            } else if (ev.status === 'Validé / Planifié') {
                buttonsHtml = `
                    <button class="btn btn-secondary btn-sm" onclick="EventsPartner.showDetails(${ev.id})"><i class="fa-solid fa-eye"></i> Voir</button>
                    <button class="btn btn-primary btn-sm" onclick="EventsPartner.editEvent(${ev.id})"><i class="fa-solid fa-pen"></i> Modifier</button>
                    <button class="btn btn-danger btn-sm" onclick="EventsPartner.cancelEvent(${ev.id})"><i class="fa-solid fa-trash"></i> Annuler</button>
                `;
            } else if (ev.status === 'En cours') {
                buttonsHtml = `
                    <button class="btn btn-secondary btn-sm" onclick="EventsPartner.showDetails(${ev.id})"><i class="fa-solid fa-eye"></i> Voir</button>
                    <button class="btn btn-success btn-sm" onclick="EventsPartner.markPresence(${ev.id})"><i class="fa-solid fa-check-double"></i> Présences</button>
                `;
            } else {
                buttonsHtml = `
                    <button class="btn btn-secondary btn-sm" onclick="EventsPartner.showDetails(${ev.id})"><i class="fa-solid fa-eye"></i> Voir</button>
                `;
            }

            const card = document.createElement('div');
            card.className = 'card';
            card.style.display = 'flex';
            card.style.flexDirection = 'column';
            card.style.gap = '8px';
            
            card.innerHTML = `
                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
                    <h4 style="margin:0;">${ev.title}</h4>
                    <span class="badge" style="background:${statsColors[ev.status]}; color:white; flex-shrink:0;">${ev.status}</span>
                </div>
                <div style="font-size:0.85rem; color:var(--color-text-muted);"><span class="badge badge-primary">${iconBadge}</span></div>
                <div style="font-size:0.9rem; margin-top:8px;"><i class="fa-regular fa-calendar"></i> ${ev.date} de ${ev.startTime} à ${ev.endTime}</div>
                <div style="font-size:0.9rem;"><i class="fa-solid ${isOnline ? 'fa-link' : 'fa-map-pin'}"></i> ${ev.location}</div>
                
                <div style="margin-top:8px;">
                    <div style="display:flex; justify-content:space-between; font-size:0.8rem; margin-bottom:4px;">
                        <span>Inscriptions</span>
                        <span>${regCount} / ${ev.capacity}</span>
                    </div>
                    <div class="progress-bar" style="height:6px; background:var(--color-border); border-radius:4px; overflow:hidden;">
                        <div style="height:100%; width:${progress}%; background:var(--color-primary);"></div>
                    </div>
                </div>

                <div style="display:flex; gap:8px; margin-top:16px;">
                    ${buttonsHtml}
                </div>
                ${bottomWarning}
            `;
            container.appendChild(card);
        });
    },

    showDetails(id) {
        this.currentEvent = this.events.find(e => e.id === id);
        if (!this.currentEvent) return;

        document.getElementById('events-list-view').style.display = 'none';
        document.getElementById('event-detail-view').style.display = 'block';

        const ev = this.currentEvent;
        const regCount = ev.participants ? ev.participants.length : 0;
        
        const container = document.getElementById('event-detail-content');
        
        let participantsHtml = '';
        if (regCount === 0) {
            participantsHtml = '<p class="text-muted" style="padding:16px;">Aucun inscrit pour le moment.</p>';
        } else {
            const trs = ev.participants.map(p => `
                <tr>
                    <td>${p.name}</td>
                    <td>${p.email || 'N/A'}</td>
                    <td>${p.date || 'Aujourd\'hui'}</td>
                    <td>
                        <select class="form-select no-icon" onchange="EventsPartner.updateParticipantStatus(${ev.id}, ${p.id}, this.value)">
                            <option value="Inscrit" ${p.status === 'Inscrit' ? 'selected' : ''}>Inscrit</option>
                            <option value="Présent" ${p.status === 'Présent' ? 'selected' : ''}>Présent</option>
                            <option value="Absent" ${p.status === 'Absent' ? 'selected' : ''}>Absent</option>
                        </select>
                    </td>
                </tr>
            `).join('');

            participantsHtml = `
                <table class="data-table" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Date inscription</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>${trs}</tbody>
                </table>
            `;
        }

        container.innerHTML = `
            <div class="card" style="margin-bottom:24px;">`n<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:24px;">
                    <div>
                        <h2><i class="fa-solid fa-ticket"></i> ${ev.title}</h2>
                        <ul style="list-style:none; padding:0; margin-top:16px; line-height:2;">
                            <li><strong><i class="fa-regular fa-calendar"></i> Date:</strong> ${ev.date}</li>
                            <li><strong><i class="fa-regular fa-clock"></i> Heure:</strong> ${ev.startTime} - ${ev.endTime}</li>
                            <li><strong><i class="fa-solid fa-location-dot"></i> Lieu/Lien:</strong> ${ev.location}</li>
                            <li><strong><i class="fa-solid fa-users"></i> Capacité:</strong> ${regCount} / ${ev.capacity}</li>
                            <li><strong><i class="fa-solid fa-shield-halved"></i> Statut validation:</strong> ${ev.status}</li>
                        </ul>
                    </div>
                    <div>
                        <h3>Description</h3>
                        <p style="color:var(--color-text-muted); font-size:0.95rem;">${ev.description || 'Aucune description fournie.'}</p>
                        <p style="font-size:0.85rem; color:var(--color-text-muted); margin-top:16px;"><strong>Soumis le:</strong> ${ev.createdAt || 'N/A'}</p>
                        ${ev.rejectionReason ? '<div><strong style="color:var(--color-danger);">Motif du refus: </strong>' + ev.rejectionReason + '</div>' : ''}
                    </div>
                </div>
            </div>

            <div class="card">
                <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:16px; margin-bottom:16px;">
                    <h3><i class="fa-solid fa-users-line"></i> Participants inscrits</h3>
                    <div style="display:flex; flex-wrap:wrap; gap:8px;">
                        <button class="btn btn-secondary btn-sm" onclick="EventsPartner.exportCSV()"><i class="fa-solid fa-file-csv"></i> Exporter la liste</button>
                        <button class="btn btn-success btn-sm" onclick="EventsPartner.markAllPresent()"><i class="fa-solid fa-check-double"></i> Marquer tous présents</button>
                    </div>
                </div>
                <div class="table-container" style="overflow-x:auto;">
                    ${participantsHtml}
                </div>
            </div>
        `;
    },

    showList() {
        document.getElementById('events-list-view').style.display = 'block';
        document.getElementById('event-detail-view').style.display = 'none';
        this.currentEvent = null;
        this.renderStats();
        this.renderList();
    },

    openCreateModal() {
        document.getElementById('eventForm').reset();
        document.getElementById('eventId').value = '';
        document.getElementById('eventModalTitle').innerHTML = '<i class="fa-solid fa-plus"></i> Créer un événement';
        Components.openModal('eventModal');
        this.toggleLocationField();
    },

    editEvent(id) {
        const ev = this.events.find(e => e.id === id);
        if(!ev) return;
        document.getElementById('eventId').value = ev.id;
        document.getElementById('evTitle').value = ev.title;
        document.getElementById('evDesc').value = ev.description;
        document.getElementById('evDate').value = ev.date;
        document.getElementById('evStart').value = ev.startTime;
        document.getElementById('evEnd').value = ev.endTime;
        document.getElementById('evType').value = ev.type || 'Présentiel';
        document.getElementById('evCapacity').value = ev.capacity;
        document.getElementById('evLocation').value = ev.location;

        document.getElementById('eventModalTitle').innerHTML = '<i class="fa-solid fa-pen"></i> Modifier événement';
        Components.openModal('eventModal');
        this.toggleLocationField();
    },

        toggleLocationField() {
        const type = document.getElementById('evType').value;
        const label = document.getElementById('evLocLabel');
        const icon = document.getElementById('evLocIcon');
        if (type === 'En ligne') {
            label.textContent = 'Lien Visio (Zoom/Meet)';
            if(icon) icon.className = 'fa-solid fa-link';
        } else {
            label.textContent = 'Emplacement exact';
            if(icon) icon.className = 'fa-solid fa-location-dot';
        }
    },

    saveEvent() {
        const title = document.getElementById('evTitle').value.trim();
        const date = document.getElementById('evDate').value;
        const start = document.getElementById('evStart').value;
        const end = document.getElementById('evEnd').value;
        const capacity = parseInt(document.getElementById('evCapacity').value);
        const location = document.getElementById('evLocation').value.trim();

        if (!title || !date || !start || !end || !capacity || !location) {
            Components.showToast('Veuillez remplir tous les champs obligatoires.', 'error');
            return;
        }

        const id = document.getElementById('eventId').value;
        
        let targetEvent;
        if (id) {
            targetEvent = this.events.find(e => e.id == id);
        } else {
            targetEvent = {
                id: Date.now(),
                partnerId: App.getCurrentUser().id,
                partnerName: App.getCurrentUser().name,
                createdAt: new Date().toLocaleDateString('fr-FR'),
                participants: [],
                status: 'En attente'
            };
            this.events.unshift(targetEvent);
        }

        if(targetEvent) {
            targetEvent.title = title;
            targetEvent.description = document.getElementById('evDesc').value;
            targetEvent.date = date;
            targetEvent.startTime = start;
            targetEvent.endTime = end;
            targetEvent.type = document.getElementById('evType').value;
            targetEvent.capacity = capacity;
            targetEvent.location = location;

            this.saveAllEvents();
            Components.closeModal('eventModal');
            Components.showToast('Soumis avec succès !', 'success');
            this.renderStats();
            this.renderList();
        }
    },

    cancelEvent(id) {
        if (confirm('Voulez-vous vraiment annuler cet événement ?')) {
            const index = this.events.findIndex(e => e.id === id);
            if(index > -1) {
                this.events.splice(index, 1);
                this.saveAllEvents();
                Components.showToast('Événement annulé', 'success');
                this.renderStats();
                this.renderList();
            }
        }
    },

    markPresence(id) {
        this.showDetails(id);
    },

    updateParticipantStatus(eventId, participantId, newStatus) {
        const ev = this.events.find(e => e.id === eventId);
        if(ev && ev.participants) {
             const p = ev.participants.find(p => p.id === participantId);
             if(p) {
                 p.status = newStatus;
                 this.saveAllEvents();
                 Components.showToast('Statut mis à jour', 'success');
             }
        }
    },

    markAllPresent() {
        if(!this.currentEvent || !this.currentEvent.participants) return;
        this.currentEvent.participants.forEach(p => p.status = 'Présent');
        this.saveAllEvents();
        Components.showToast('Tous les participants ont été marqués présents', 'success');
        this.showDetails(this.currentEvent.id);
    },

    exportCSV() {
        if(!this.currentEvent || !this.currentEvent.participants) return;
        
        let csv = 'Nom,Email,Date Inscription,Statut\n';
        this.currentEvent.participants.forEach(p => {
            csv += `${p.name},${p.email || ''},${p.date || ''},${p.status || ''}\n`;
        });
        
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `participants_ev_${this.currentEvent.id}.csv`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    EventsPartner.init();
});





