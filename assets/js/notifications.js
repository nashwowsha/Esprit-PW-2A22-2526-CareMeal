/* ============================================================
   CAREMEAL — SYSTÈME DE NOTIFICATIONS EN TEMPS RÉEL
   fetch() toutes les 30 secondes via setInterval()
   ============================================================ */

const CareMealNotifications = {

    interval    : null,
    lastCount   : 0,
    notifications: [],
    isOpen      : false,
    userRole    : null,

    // ── Initialisation ────────────────────────────────────────
    init(role) {
        this.userRole = role || 'student';
        this._upgradebell();
        this._injectStyles();
        this._fetch();                          // Premier appel immédiat
        this.interval = setInterval(() => {
            this._fetch();
        }, 30000);                              // Toutes les 30 secondes
    },

    // ── Remplacer la cloche existante par une cloche interactive ──
    _upgradebell() {
        const bell = document.querySelector('.header-notification');
        if (!bell) return;

        bell.id = 'notif-bell-btn';
        bell.setAttribute('aria-label', 'Notifications');
        bell.style.position = 'relative';

        // Remplacer le contenu
        bell.innerHTML = `
            <i class="fa-solid fa-bell"></i>
            <span id="notif-badge" style="display:none; position:absolute; top:-4px; right:-4px;
                background:#fe5516; color:white; font-size:0.65rem; font-weight:800;
                min-width:18px; height:18px; border-radius:50%; display:none;
                align-items:center; justify-content:center; border:2px solid #0f172a;
                line-height:1; padding:0 3px;">0</span>
        `;

        // Dropdown panel
        const panel = document.createElement('div');
        panel.id = 'notif-panel';
        panel.innerHTML = `
            <div id="notif-panel-header">
                <span><i class="fa-solid fa-bell" style="margin-right:8px;"></i>Notifications</span>
                <button id="notif-mark-all" title="Tout marquer comme lu">
                    <i class="fa-solid fa-check-double"></i>
                </button>
            </div>
            <div id="notif-list">
                <div class="notif-empty">
                    <i class="fa-solid fa-bell-slash"></i>
                    <span>Aucune notification</span>
                </div>
            </div>
        `;
        document.body.appendChild(panel);

        // Événements
        bell.addEventListener('click', (e) => {
            e.stopPropagation();
            this._togglePanel();
        });
        document.addEventListener('click', (e) => {
            const p = document.getElementById('notif-panel');
            if (p && !p.contains(e.target) && e.target.id !== 'notif-bell-btn') {
                this._closePanel();
            }
        });
        document.getElementById('notif-mark-all').addEventListener('click', () => {
            this._markAllRead();
        });
    },

    // ── Fetch les notifications depuis le serveur ─────────────
    async _fetch() {
        try {
            const res  = await fetch('/projet2a22/Controller/NotificationController.php');
            const data = await res.json();

            if (data.success) {
                this.notifications = data.notifications || [];
                this._render();
                this._updateBadge(data.count);

                // Animation cloche si nouvelles notifs
                if (data.count > this.lastCount && this.lastCount !== -1) {
                    this._shakeBell();
                }
                this.lastCount = data.count;
            }
        } catch (e) {
            // Silencieux
        }
    },

    // ── Rendu de la liste de notifications ────────────────────
    _render() {
        const list = document.getElementById('notif-list');
        if (!list) return;

        if (this.notifications.length === 0) {
            list.innerHTML = `
                <div class="notif-empty">
                    <i class="fa-solid fa-bell-slash"></i>
                    <span>Aucune notification</span>
                </div>`;
            return;
        }

        const typeIcons = {
            success : { color: '#10b981', bg: 'rgba(16,185,129,0.12)' },
            warning : { color: '#f59e0b', bg: 'rgba(245,158,11,0.12)' },
            danger  : { color: '#ef4444', bg: 'rgba(239,68,68,0.12)'  },
            info    : { color: '#3b82f6', bg: 'rgba(59,130,246,0.12)' },
        };

        list.innerHTML = this.notifications.map(n => {
            const style = typeIcons[n.type] || typeIcons.info;
            return `
            <a href="${n.link || '#'}" class="notif-item" data-id="${n.id}">
                <div class="notif-icon" style="background:${style.bg}; color:${style.color};">
                    <i class="fa-solid ${n.icon}"></i>
                </div>
                <div class="notif-content">
                    <div class="notif-message">${n.message}</div>
                    <div class="notif-time">${n.time}</div>
                </div>
                <div class="notif-dot-unread" style="background:${style.color};"></div>
            </a>`;
        }).join('');
    },

    // ── Mettre à jour le badge ────────────────────────────────
    _updateBadge(count) {
        const badge = document.getElementById('notif-badge');
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count > 9 ? '9+' : count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    },

    // ── Ouvrir / Fermer le panel ──────────────────────────────
    _togglePanel() {
        this.isOpen ? this._closePanel() : this._openPanel();
    },

    _openPanel() {
        this.isOpen = true;
        const panel = document.getElementById('notif-panel');
        const bell  = document.getElementById('notif-bell-btn');
        if (!panel || !bell) return;

        const rect = bell.getBoundingClientRect();
        panel.style.top   = (rect.bottom + 10) + 'px';
        panel.style.right = (window.innerWidth - rect.right) + 'px';
        panel.style.display = 'block';
        panel.style.animation = 'notif-panel-in 0.2s ease';
    },

    _closePanel() {
        this.isOpen = false;
        const panel = document.getElementById('notif-panel');
        if (panel) panel.style.display = 'none';
    },

    // ── Marquer tout comme lu ─────────────────────────────────
    _markAllRead() {
        this.notifications = [];
        this.lastCount = -1;
        this._render();
        this._updateBadge(0);
        this._closePanel();
    },

    // ── Animation cloche ──────────────────────────────────────
    _shakeBell() {
        const bell = document.getElementById('notif-bell-btn');
        if (!bell) return;
        bell.style.animation = 'notif-bell-shake 0.5s ease';
        setTimeout(() => { bell.style.animation = ''; }, 500);
    },

    // ── Styles CSS ────────────────────────────────────────────
    _injectStyles() {
        const style = document.createElement('style');
        style.textContent = `
        /* Panel notifications */
        #notif-panel {
            display: none;
            position: fixed;
            z-index: 99997;
            width: 340px;
            max-height: 420px;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 14px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            overflow: hidden;
            flex-direction: column;
        }

        #notif-panel-header {
            padding: 14px 16px;
            background: linear-gradient(135deg, #fe5516, #c44010);
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            font-weight: 700;
            font-size: 0.92rem;
        }

        #notif-mark-all {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }
        #notif-mark-all:hover { background: rgba(255,255,255,0.35); }

        #notif-list {
            overflow-y: auto;
            max-height: 360px;
        }
        #notif-list::-webkit-scrollbar { width: 4px; }
        #notif-list::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }

        .notif-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            text-decoration: none;
            transition: background 0.15s;
            cursor: pointer;
        }
        .notif-item:hover { background: rgba(255,255,255,0.04); }
        .notif-item:last-child { border-bottom: none; }

        .notif-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .notif-content { flex: 1; min-width: 0; }
        .notif-message {
            color: #e2e8f0;
            font-size: 0.85rem;
            font-weight: 500;
            line-height: 1.4;
            white-space: normal;
        }
        .notif-time {
            color: #64748b;
            font-size: 0.75rem;
            margin-top: 3px;
        }

        .notif-dot-unread {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .notif-empty {
            padding: 32px 16px;
            text-align: center;
            color: #475569;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            font-size: 0.88rem;
        }
        .notif-empty i { font-size: 1.8rem; }

        /* Animations */
        @keyframes notif-panel-in {
            from { opacity: 0; transform: translateY(-8px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes notif-bell-shake {
            0%,100% { transform: rotate(0deg); }
            20%     { transform: rotate(-15deg); }
            40%     { transform: rotate(15deg); }
            60%     { transform: rotate(-10deg); }
            80%     { transform: rotate(10deg); }
        }
        `;
        document.head.appendChild(style);
    }
};
