/* ============================================================
   CAREMEAL — VUE CALENDRIER
   FullCalendar.js v6 — Lazy loading — Thème sombre
   ============================================================ */

const CareMealCalendar = {

    calendar     : null,   // instance FullCalendar
    initialized  : false,  // lazy init flag
    events       : [],     // cache des événements
    currentRole  : 'admin',

    // ── Couleurs selon statut_validation ─────────────────────
    statusColors : {
        'Validé'     : '#10b981',
        'En attente' : '#f59e0b',
        'Rejeté'     : '#ef4444',
        'default'    : '#64748b'
    },

    // ── Initialisation (appelée au premier clic sur "Calendrier") ──
    init(role) {
        this.currentRole = role || 'admin';
        this._injectStyles();
        this._bindToggleButtons();
    },

    // ── Basculer vers la vue Calendrier ───────────────────────
    async showCalendar() {
        const listView = document.getElementById('calendar-list-view');
        const calView  = document.getElementById('calendar-view');
        const btnList  = document.getElementById('btn-view-list');
        const btnCal   = document.getElementById('btn-view-calendar');

        if (listView) listView.style.display = 'none';
        if (calView)  calView.style.display  = 'block';
        if (btnList)  { btnList.classList.remove('active-view'); btnList.classList.add('inactive-view'); }
        if (btnCal)   { btnCal.classList.add('active-view');     btnCal.classList.remove('inactive-view'); }

        // Lazy init : créer le calendrier seulement la première fois
        const wasInit = this.initialized;
        if (!wasInit) {
            await this._loadEvents();
            this._createCalendar();
            this.initialized = true;
        } else {
            this.calendar && this.calendar.render();
        }
    },

    // ── Basculer vers la vue Liste ────────────────────────────
    showList() {
        const listView = document.getElementById('calendar-list-view');
        const calView  = document.getElementById('calendar-view');
        const btnList  = document.getElementById('btn-view-list');
        const btnCal   = document.getElementById('btn-view-calendar');

        if (listView) listView.style.display = '';
        if (calView)  calView.style.display  = 'none';
        if (btnList)  { btnList.classList.add('active-view');     btnList.classList.remove('inactive-view'); }
        if (btnCal)   { btnCal.classList.remove('active-view');   btnCal.classList.add('inactive-view'); }
    },

    // ── Binding des boutons de bascule ────────────────────────
    _bindToggleButtons() {
        const btnList = document.getElementById('btn-view-list');
        const btnCal  = document.getElementById('btn-view-calendar');
        if (btnList) btnList.addEventListener('click', () => this.showList());
        if (btnCal)  btnCal.addEventListener('click',  () => this.showCalendar());
    },

    // ── Récupérer les événements depuis le Controller ─────────
    async _loadEvents() {
        const loader = document.getElementById('calendar-loader');
        if (loader) loader.style.display = 'flex';

        try {
            const res  = await fetch('/projet2a22/Controller/EventController.php', {
                method  : 'POST',
                headers : { 'Content-Type': 'application/json' },
                body    : JSON.stringify({ action: 'get_all' })
            });
            const data = await res.json();
            this.events = (data.success && data.events) ? data.events : [];
        } catch (e) {
            console.error('CareMealCalendar: erreur chargement événements', e);
            this.events = [];
        }

        if (loader) loader.style.display = 'none';
    },

    // ── Formater les événements pour FullCalendar ─────────────
    _formatEvents() {
        return this.events.map(ev => {
            const id     = ev.id_evenement || ev.id || '';
            const title  = ev.titre        || ev.title || 'Sans titre';
            const date   = ev.date_evenement || ev.date || '';
            const start  = ev.heure_debut  || ev.startTime || '00:00';
            const end    = ev.heure_fin    || ev.endTime   || '00:00';
            const statut = ev.statut_validation || ev.status || '';
            const color  = this.statusColors[statut] || this.statusColors['default'];

            return {
                id         : String(id),
                title      : title,
                start      : date + 'T' + start.substring(0, 5),
                end        : date + 'T' + end.substring(0, 5),
                color      : color,
                borderColor: color,
                textColor  : '#ffffff',
                extendedProps: {
                    description : ev.description || '',
                    type        : ev.type_evenement || ev.type || 'Présentiel',
                    lieu        : ev.lieu || ev.lien_online || ev.location || 'N/A',
                    capacite    : ev.capacite_max || ev.capacity || 0,
                    inscrits    : ev.inscrits || 0,
                    statut      : statut,
                    lien_online : ev.lien_online || ''
                }
            };
        });
    },

    // ── Créer l'instance FullCalendar ─────────────────────────
    _createCalendar() {
        const el = document.getElementById('fullcalendar');
        if (!el || typeof FullCalendar === 'undefined') {
            console.error('CareMealCalendar: FullCalendar non chargé ou #fullcalendar introuvable');
            return;
        }

        this.calendar = new FullCalendar.Calendar(el, {
            locale         : 'fr',
            initialView    : 'dayGridMonth',
            height         : 'auto',
            firstDay       : 1, // Lundi en premier

            // Barre d'outils
            headerToolbar  : {
                left   : 'prev,next today',
                center : 'title',
                right  : 'dayGridMonth,timeGridWeek,listWeek'
            },

            buttonText: {
                today    : "Aujourd'hui",
                month    : 'Mois',
                week     : 'Semaine',
                list     : 'Liste'
            },

            // Événements
            events: this._formatEvents(),

            // Clic sur un événement → modal détail
            eventClick: (info) => {
                info.jsEvent.preventDefault();
                this._showEventModal(info.event);
            },

            // Style au survol
            eventMouseEnter: (info) => {
                info.el.style.transform  = 'scale(1.02)';
                info.el.style.transition = 'transform 0.15s';
                info.el.style.cursor     = 'pointer';
                info.el.style.zIndex     = '10';
            },
            eventMouseLeave: (info) => {
                info.el.style.transform = 'scale(1)';
                info.el.style.zIndex    = '';
            },

            // Personnalisation du rendu des événements
            eventContent: (arg) => {
                const typeIcon = arg.event.extendedProps.type === 'En ligne'
                    ? '🎥' : '📍';
                return {
                    html: `<div style="padding:2px 5px; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; font-size:0.82rem; font-weight:600;">
                        ${typeIcon} ${arg.event.title}
                    </div>`
                };
            }
        });

        this.calendar.render();
    },

    // ── Modal détail d'un événement ───────────────────────────
    _showEventModal(event) {
        const existing = document.getElementById('cal-event-modal');
        if (existing) existing.remove();

        const props  = event.extendedProps;
        const statut = props.statut || '';
        const color  = this.statusColors[statut] || this.statusColors['default'];

        const startStr = event.start
            ? event.start.toLocaleDateString('fr-FR', { weekday:'long', day:'2-digit', month:'long', year:'numeric' })
            : '—';
        const startTime = event.start
            ? event.start.toLocaleTimeString('fr-FR', { hour:'2-digit', minute:'2-digit' })
            : '';
        const endTime = event.end
            ? event.end.toLocaleTimeString('fr-FR', { hour:'2-digit', minute:'2-digit' })
            : '';

        const typeIcon = props.type === 'En ligne' ? '🎥' : '📍';
        const lieuLabel = props.type === 'En ligne' ? 'Lien' : 'Lieu';

        const modal = document.createElement('div');
        modal.id = 'cal-event-modal';
        modal.innerHTML = `
        <div id="cal-modal-backdrop" style="position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:99998;"></div>
        <div style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:99999;
                    background:#1e293b;border-radius:16px;width:480px;max-width:95vw;
                    border:1px solid #334155;box-shadow:0 25px 60px rgba(0,0,0,0.6);overflow:hidden;
                    animation:cal-modal-in 0.2s ease;">

            <!-- Header coloré selon statut -->
            <div style="background:${color};padding:20px 24px;display:flex;justify-content:space-between;align-items:flex-start;">
                <div>
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.8);font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">
                        ${typeIcon} ${props.type || 'Présentiel'}
                    </div>
                    <h3 style="color:white;margin:0;font-size:1.15rem;font-weight:700;line-height:1.3;">${event.title}</h3>
                </div>
                <button id="cal-modal-close" style="background:rgba(255,255,255,0.2);border:none;color:white;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-left:12px;">✕</button>
            </div>

            <!-- Corps -->
            <div style="padding:22px 24px;display:flex;flex-direction:column;gap:14px;">

                <!-- Statut badge -->
                <div>
                    <span style="padding:4px 14px;border-radius:20px;font-size:0.82rem;font-weight:700;background:${color}22;color:${color};border:1px solid ${color}44;">
                        ${statut || 'N/A'}
                    </span>
                </div>

                <!-- Infos -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="background:#0f172a;border-radius:8px;padding:12px;border:1px solid #334155;">
                        <div style="font-size:0.75rem;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">📅 Date</div>
                        <div style="color:#f1f5f9;font-size:0.88rem;font-weight:600;">${startStr}</div>
                    </div>
                    <div style="background:#0f172a;border-radius:8px;padding:12px;border:1px solid #334155;">
                        <div style="font-size:0.75rem;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">🕐 Horaire</div>
                        <div style="color:#f1f5f9;font-size:0.88rem;font-weight:600;">${startTime} → ${endTime}</div>
                    </div>
                    <div style="background:#0f172a;border-radius:8px;padding:12px;border:1px solid #334155;">
                        <div style="font-size:0.75rem;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">${lieuLabel === 'Lien' ? '🔗' : '📍'} ${lieuLabel}</div>
                        <div style="color:#f1f5f9;font-size:0.88rem;font-weight:600;word-break:break-all;">${props.lieu || '—'}</div>
                    </div>
                    <div style="background:#0f172a;border-radius:8px;padding:12px;border:1px solid #334155;">
                        <div style="font-size:0.75rem;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">👥 Inscrits</div>
                        <div style="color:#f1f5f9;font-size:0.88rem;font-weight:600;">${props.inscrits} / ${props.capacite}</div>
                    </div>
                </div>

                <!-- Description -->
                ${props.description ? `
                <div style="background:#0f172a;border-radius:8px;padding:12px;border:1px solid #334155;">
                    <div style="font-size:0.75rem;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">📝 Description</div>
                    <div style="color:#94a3b8;font-size:0.88rem;line-height:1.5;">${props.description}</div>
                </div>` : ''}

                <!-- Bouton fermer -->
                <button id="cal-modal-close-btn" style="width:100%;padding:11px;border-radius:8px;border:1.5px solid #334155;background:transparent;color:#94a3b8;font-size:0.92rem;cursor:pointer;font-weight:600;margin-top:4px;">
                    Fermer
                </button>
            </div>
        </div>`;

        document.body.appendChild(modal);

        // Fermeture
        const close = () => { const m = document.getElementById('cal-event-modal'); if (m) m.remove(); };
        document.getElementById('cal-modal-close').addEventListener('click', close);
        document.getElementById('cal-modal-close-btn').addEventListener('click', close);
        document.getElementById('cal-modal-backdrop').addEventListener('click', close);
    },

    // ── Styles CSS injectés ───────────────────────────────────
    _injectStyles() {
        const style = document.createElement('style');
        style.textContent = `
        /* Boutons bascule Liste / Calendrier */
        .view-toggle-btn {
            padding: 10px 20px; border-radius: 24px; font-size: 0.9rem;
            font-weight: 600; cursor: pointer; border: none;
            display: flex; align-items: center; gap: 8px;
            transition: all 0.2s;
        }
        .active-view {
            background: linear-gradient(135deg, #fe5516, #c44010);
            color: white;
            box-shadow: 0 4px 12px rgba(254,85,22,0.35);
        }
        .inactive-view {
            background: var(--color-surface, #1e293b);
            color: #94a3b8;
            border: 1px solid #334155 !important;
        }
        .inactive-view:hover { color: white; border-color: #fe5516 !important; }

        /* Conteneur calendrier */
        #calendar-view {
            background: #1e293b;
            border-radius: 12px;
            border: 1px solid #334155;
            padding: 20px;
            margin-top: 8px;
        }

        /* Loader */
        #calendar-loader {
            display: none; justify-content: center; align-items: center;
            padding: 60px; color: #64748b; gap: 12px; font-size: 0.95rem;
        }

        /* FullCalendar thème sombre */
        #fullcalendar .fc { color: #e2e8f0; }
        #fullcalendar .fc-toolbar-title { color: #f1f5f9; font-size: 1.1rem; font-weight: 700; }
        #fullcalendar .fc-button {
            background: #334155 !important; border-color: #475569 !important;
            color: #e2e8f0 !important; font-size: 0.82rem !important;
            padding: 6px 12px !important; border-radius: 6px !important;
        }
        #fullcalendar .fc-button:hover { background: #475569 !important; }
        #fullcalendar .fc-button-active,
        #fullcalendar .fc-button-primary:not(:disabled).fc-button-active {
            background: #fe5516 !important; border-color: #fe5516 !important; color: white !important;
        }
        #fullcalendar .fc-col-header-cell { background: #0f172a; }
        #fullcalendar .fc-col-header-cell-cushion { color: #94a3b8; font-size: 0.82rem; font-weight: 600; text-decoration: none; }
        #fullcalendar .fc-daygrid-day { background: #1e293b; }
        #fullcalendar .fc-daygrid-day:hover { background: #263548; }
        #fullcalendar .fc-daygrid-day-number { color: #94a3b8; font-size: 0.85rem; text-decoration: none; }
        #fullcalendar .fc-day-today { background: rgba(254,85,22,0.08) !important; }
        #fullcalendar .fc-day-today .fc-daygrid-day-number { color: #fe5516 !important; font-weight: 700; }
        #fullcalendar .fc-scrollgrid { border-color: #334155 !important; }
        #fullcalendar .fc-scrollgrid td, #fullcalendar .fc-scrollgrid th { border-color: #334155 !important; }
        #fullcalendar .fc-list-day-cushion { background: #0f172a !important; }
        #fullcalendar .fc-list-event:hover td { background: #263548 !important; }
        #fullcalendar .fc-list-event-title a { color: #e2e8f0 !important; text-decoration: none; }
        #fullcalendar .fc-list-event-time { color: #64748b !important; }
        #fullcalendar .fc-timegrid-slot { border-color: #334155 !important; }
        #fullcalendar .fc-timegrid-axis { color: #64748b; font-size: 0.78rem; }
        #fullcalendar .fc-event { border-radius: 5px !important; border: none !important; }
        #fullcalendar .fc-more-link { color: #fe5516 !important; font-size: 0.78rem; }

        /* Animation modal */
        @keyframes cal-modal-in {
            from { opacity: 0; transform: translate(-50%, -48%) scale(0.97); }
            to   { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        }
        `;
        document.head.appendChild(style);
    }
};
