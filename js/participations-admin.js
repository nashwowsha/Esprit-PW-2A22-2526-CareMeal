/* ============================================
   CAREMEAL — ADMIN PARTICIPATIONS
   Jointure PARTICIPATION ⟶ EVENEMENT
   ============================================ */

let globalParticipationsList = [];

document.addEventListener('DOMContentLoaded', () => {
    if (typeof App !== 'undefined') App.init();
    loadParticipations();
});

function loadParticipations() {
    fetch('/projet2a22/Controller/EventParticipationController.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get_all_participations' })
    })
    .then(res => res.json())
    .then(data => {
        globalParticipationsList = (data.success && data.participations) ? data.participations : [];
        renderStats();
        renderParticipations();
    })
    .catch(err => {
        console.error('Erreur:', err);
        document.getElementById('participations-body').innerHTML =
            '<tr><td colspan="8" style="text-align:center; color:#f87171; padding:30px;">Erreur de chargement.</td></tr>';
    });
}

function renderStats() {
    const total    = globalParticipationsList.length;
    const inscrits = globalParticipationsList.filter(p => p.statut === 'Inscrit').length;
    const presents = globalParticipationsList.filter(p => p.statut === 'Présent').length;
    const annules  = globalParticipationsList.filter(p => p.statut === 'Annulé').length;
    const statsEl  = document.getElementById('part-stats');
    if (!statsEl) return;
    statsEl.innerHTML = `
        <div style="background:var(--color-surface); border-radius:10px; padding:18px; border:1px solid var(--color-border); text-align:center;">
            <div style="font-size:2rem; font-weight:800; color:#f1f5f9;">${total}</div>
            <div style="font-size:0.8rem; color:var(--color-text-muted); text-transform:uppercase; letter-spacing:1px; margin-top:4px;">Total inscriptions</div>
        </div>
        <div style="background:var(--color-surface); border-radius:10px; padding:18px; border:1px solid var(--color-border); text-align:center;">
            <div style="font-size:2rem; font-weight:800; color:#10b981;">${inscrits}</div>
            <div style="font-size:0.8rem; color:var(--color-text-muted); text-transform:uppercase; letter-spacing:1px; margin-top:4px;">Inscrits</div>
        </div>
        <div style="background:var(--color-surface); border-radius:10px; padding:18px; border:1px solid var(--color-border); text-align:center;">
            <div style="font-size:2rem; font-weight:800; color:#fe5516;">${presents}</div>
            <div style="font-size:0.8rem; color:var(--color-text-muted); text-transform:uppercase; letter-spacing:1px; margin-top:4px;">Présents</div>
        </div>
        <div style="background:var(--color-surface); border-radius:10px; padding:18px; border:1px solid rgba(239,68,68,0.4); text-align:center; position:relative;">
            <div style="font-size:2rem; font-weight:800; color:#ef4444;">${annules}</div>
            <div style="font-size:0.8rem; color:var(--color-text-muted); text-transform:uppercase; letter-spacing:1px; margin-top:4px;">Annulés</div>
            ${annules > 0 ? `<span style="position:absolute; top:-6px; right:-6px; background:#ef4444; color:white; font-size:0.7rem; font-weight:700; width:20px; height:20px; border-radius:50%; display:flex; align-items:center; justify-content:center;">${annules}</span>` : ''}
        </div>`;
}

function filterAndSortParticipations() { renderParticipations(); }

function renderParticipations() {
    const tbody = document.getElementById('participations-body');
    let list = [...globalParticipationsList];

    const search = document.getElementById('partSearchInput')?.value.toLowerCase() || '';
    if (search) {
        list = list.filter(p =>
            (p.nom           || '').toLowerCase().includes(search) ||
            (p.prenom        || '').toLowerCase().includes(search) ||
            (p.email         || '').toLowerCase().includes(search) ||
            (p.nom_evenement || '').toLowerCase().includes(search) ||
            (p.universite    || '').toLowerCase().includes(search)
        );
    }

    const statut = document.getElementById('partStatusSelect')?.value || 'Tous';
    if (statut !== 'Tous') list = list.filter(p => p.statut === statut);

    const sort = document.getElementById('partSortSelect')?.value || 'date_desc';
    list.sort((a, b) => {
        if (sort === 'date_asc')  return new Date(a.date_inscription) - new Date(b.date_inscription);
        if (sort === 'date_desc') return new Date(b.date_inscription) - new Date(a.date_inscription);
        if (sort === 'nom_asc')   return (a.nom || '').localeCompare(b.nom || '');
        return 0;
    });

    if (list.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:30px; color:var(--color-text-muted);">Aucune participation trouvée.</td></tr>';
        return;
    }

    const sc = {
        'Inscrit': { bg: 'rgba(16,185,129,0.15)',  color: '#10b981' },
        'Présent': { bg: 'rgba(59,130,246,0.15)',  color: '#3b82f6' },
        'Absent':  { bg: 'rgba(245,158,11,0.15)',  color: '#f59e0b' },
        'Annulé':  { bg: 'rgba(239,68,68,0.15)',   color: '#ef4444' },
    };

    tbody.innerHTML = list.map((p, i) => {
        const s = sc[p.statut] || { bg: 'rgba(100,116,139,0.15)', color: '#94a3b8' };
        const typeBadge = (p.event_type || '') === 'En ligne'
            ? `<span style="font-size:0.72rem; background:rgba(126,34,206,0.2); color:#a855f7; padding:2px 7px; border-radius:4px; font-weight:600;">EN LIGNE</span>`
            : `<span style="font-size:0.72rem; background:rgba(2,132,199,0.2); color:#38bdf8; padding:2px 7px; border-radius:4px; font-weight:600;">PRÉSENTIEL</span>`;

        return `<tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
            <td style="padding:14px 16px; color:#64748b; font-size:0.85rem; font-weight:600;">${p.id}</td>
            <td style="padding:14px 16px;">
                <div style="font-weight:700; color:#f1f5f9;">${p.nom || ''} ${p.prenom || ''}</div>
                <div style="font-size:0.82rem; color:#64748b; margin-top:3px;"><i class="fa-solid fa-envelope" style="margin-right:5px;"></i>${p.email || '—'}</div>
                ${p.telephone ? `<div style="font-size:0.82rem; color:#64748b; margin-top:2px;"><i class="fa-solid fa-phone" style="margin-right:5px;"></i>${p.telephone}</div>` : ''}
            </td>
            <td style="padding:14px 16px;">
                <div style="font-weight:600; color:#f1f5f9;">${p.universite || '—'}</div>
                <div style="font-size:0.82rem; color:#64748b; margin-top:2px;">${p.annee_etude || '—'}</div>
            </td>
            <td style="padding:14px 16px;">
                <div style="font-weight:600; color:#f1f5f9;">${p.nom_evenement || '—'}</div>
                <div style="margin-top:5px;">${typeBadge}</div>
                ${p.event_date ? `<div style="font-size:0.82rem; color:#64748b; margin-top:3px;"><i class="fa-regular fa-calendar" style="margin-right:4px;"></i>${p.event_date}</div>` : ''}
            </td>
            <td style="padding:14px 16px; color:#94a3b8; font-size:0.88rem; white-space:nowrap;">${p.date_inscription || '—'}</td>
            <td style="padding:14px 16px;">
                <span style="padding:4px 12px; border-radius:20px; font-size:0.82rem; font-weight:700; background:${s.bg}; color:${s.color};">${p.statut}</span>
            </td>
            <td style="padding:14px 16px; white-space:nowrap;">
                ${p.statut === 'Annulé' ? '' : `
                <button onclick="updateStatus(${p.id}, 'Présent')" title="Présent" style="background:#3b82f6; color:white; border:none; padding:6px 10px; border-radius:6px; cursor:pointer; margin-right:4px;"><i class="fa-solid fa-user-check"></i></button>
                <button onclick="updateStatus(${p.id}, 'Absent')"  title="Absent"  style="background:#f59e0b; color:white; border:none; padding:6px 10px; border-radius:6px; cursor:pointer; margin-right:4px;"><i class="fa-solid fa-user-xmark"></i></button>
                `}
                <button onclick="deleteParticipation(${p.id})" title="Supprimer" style="background:#ef4444; color:white; border:none; padding:6px 10px; border-radius:6px; cursor:pointer;"><i class="fa-solid fa-trash"></i></button>
            </td>
        </tr>`;
    }).join('');
}

function updateStatus(id, newStatus) {
    if (!confirm(`Marquer cette participation comme "${newStatus}" ?`)) return;
    fetch('/projet2a22/Controller/EventParticipationController.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'update_status', id: id, statut: newStatus })
    })
    .then(res => res.json())
    .then(data => { if (data.success) loadParticipations(); else alert('Erreur: ' + data.message); });
}

function deleteParticipation(id) {
    if (!confirm('Supprimer définitivement cette participation ?')) return;
    fetch('/projet2a22/Controller/EventParticipationController.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete_participation', id: id })
    })
    .then(res => res.json())
    .then(data => { if (data.success) loadParticipations(); else alert('Erreur: ' + data.message); });
}
