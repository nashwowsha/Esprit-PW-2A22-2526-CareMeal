let globalParticipationsList = [];

document.addEventListener('DOMContentLoaded', () => {
    if(typeof App !== 'undefined') App.init();
    loadParticipations();
});

function filterAndSortParticipations() {
    renderParticipations();
}

function loadParticipations() {
    fetch('/projet2a22/Controller/EventParticipationController.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get_all_participations' })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success && data.participations) {
            globalParticipationsList = data.participations;
        } else {
            globalParticipationsList = [];
        }
        renderParticipations();
    })
    .catch(err => console.error("Erreur:", err));
}

function renderParticipations() {
    const tbody = document.getElementById('participations-body');
    tbody.innerHTML = '';
    
    let list = [...globalParticipationsList];
    
    // Search Filter
    const searchInput = document.getElementById('partSearchInput');
    if (searchInput) {
        const val = searchInput.value.toLowerCase();
        if (val) {
            list = list.filter(p => {
                const nom = (p.etudiant_nom + ' ' + p.etudiant_prenom).toLowerCase();
                const event = (p.event_title).toLowerCase();
                return nom.includes(val) || event.includes(val);
            });
        }
    }
    
    // Status Filter
    const statusSelect = document.getElementById('partStatusSelect');
    if (statusSelect && statusSelect.value !== 'Tous') {
        list = list.filter(p => p.statut === statusSelect.value);
    }
    
    // Sort
    const sortSelect = document.getElementById('partSortSelect');
    if (sortSelect) {
        list.sort((a,b) => {
            const dA = new Date(a.date_inscription).getTime();
            const dB = new Date(b.date_inscription).getTime();
            return sortSelect.value === 'date_asc' ? dA - dB : dB - dA;
        });
    }

    if(list.length > 0) {
        list.forEach(p => {
            const tr = document.createElement('tr');
            
            // Mettre en forme le badge
            let statusClass = p.statut === 'Annulé' ? 'status-annule' : 'status-inscrit';
            
            tr.innerHTML = `
                <td>${p.date_inscription}</td>
                <td>
                    <strong>${p.etudiant_nom || ''} ${p.etudiant_prenom || ''}</strong><br>
                    <span style="font-size:0.85rem; color:var(--color-text-muted)">${p.etudiant_email}</span>
                </td>
                <td>
                    <strong>${p.event_title}</strong><br>
                    <span style="font-size:0.85rem; color:var(--color-text-muted)">${p.event_date}</span>
                </td>
                <td><span class="status-badge ${statusClass}">${p.statut}</span></td>
                <td>
                    <button onclick="updateStatus(${p.evenement_id}, ${p.etudiant_id}, 'Validé')" class="btn btn-sm btn-primary" title="Valider"><i class="fa-solid fa-check"></i></button>
                    <button onclick="updateStatus(${p.evenement_id}, ${p.etudiant_id}, 'Annulé')" class="btn btn-sm btn-warning" title="Annuler" style="background:#f59e0b; color:white; border:none;"><i class="fa-solid fa-ban"></i></button>
                    <button onclick="deleteParticipation(${p.evenement_id}, ${p.etudiant_id})" class="btn btn-sm" title="Supprimer" style="background:#ef4444; color:white; border:none;"><i class="fa-solid fa-trash"></i></button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    } else {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Aucune participation trouvée</td></tr>';
    }
}

function updateStatus(eventId, studentId, newStatus) {
    if(!confirm(`Voulez-vous marquer cette participation comme ${newStatus} ?`)) return;

    fetch('/projet2a22/Controller/EventParticipationController.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            action: 'update_status', 
            evenement_id: eventId, 
            etudiant_id: studentId,
            statut: newStatus 
        })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            alert(data.message);
            loadParticipations(); // refresh list
        } else {
            alert("Erreur: " + data.message);
        }
    });
}

function deleteParticipation(eventId, studentId) {
    if(!confirm("Êtes-vous sûr de vouloir supprimer définitivement cette participation ?")) return;

    fetch('/projet2a22/Controller/EventParticipationController.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            action: 'delete_participation', 
            evenement_id: eventId, 
            etudiant_id: studentId
        })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            alert(data.message);
            loadParticipations(); // refresh list
        } else {
            alert("Erreur: " + data.message);
        }
    });
}
