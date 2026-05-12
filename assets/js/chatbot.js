/* ============================================================
   CAREMEAL — CHATBOT JS
   Interface flottante, historique multi-turn, Gemini API
   ============================================================ */

const CareMealChatbot = {

    // ── État ──────────────────────────────────────────────────
    isOpen    : false,
    isLoading : false,
    history   : [], // [{role: 'user'|'model', parts: [{text: '...'}]}]
    userRole  : null,

    // ── Suggestions par rôle ──────────────────────────────────
    suggestions: {
        student : [
            "Quels événements sont disponibles ?",
            "Suis-je inscrit à un événement ?",
            "Comment s'inscrire à un événement ?"
        ],
        partner : [
            "Comment créer un événement ?",
            "Voir mes événements en attente",
            "Pourquoi mon événement est rejeté ?"
        ],
        admin : [
            "Combien d'événements en attente ?",
            "Statistiques globales des participations",
            "Liste des inscriptions annulées"
        ]
    },

    // ── Messages d'accueil par rôle ───────────────────────────
    welcomeMessages: {
        student : "👋 Bonjour ! Je suis CareMeal Assistant.\nJe peux vous aider à trouver des événements, vérifier vos inscriptions ou répondre à vos questions.",
        partner : "👋 Bonjour partenaire ! Je suis CareMeal Assistant.\nJe peux vous aider à gérer vos événements, comprendre le processus de validation et analyser vos inscriptions.",
        admin   : "👋 Bonjour Admin ! Je suis CareMeal Assistant.\nJe peux vous fournir des statistiques, vous aider à gérer les événements et les participations."
    },

    // ── Initialisation ────────────────────────────────────────
    init(role) {
        this.userRole = role || 'student';
        this._injectHTML();
        this._bindEvents();
    },

    // ── Injection du HTML dans le DOM ─────────────────────────
    _injectHTML() {
        const html = `
        <!-- Bouton flottant -->
        <button id="chatbot-toggle" aria-label="Ouvrir le chatbot CareMeal" title="Assistant CareMeal">
            <i class="fa-solid fa-robot" id="chatbot-icon-open"></i>
            <i class="fa-solid fa-xmark" id="chatbot-icon-close" style="display:none;"></i>
            <span id="chatbot-notif-dot"></span>
        </button>

        <!-- Fenêtre de chat -->
        <div id="chatbot-window" role="dialog" aria-label="Assistant CareMeal" style="display:none;">

            <!-- Header -->
            <div id="chatbot-header">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div id="chatbot-avatar"><i class="fa-solid fa-robot"></i></div>
                    <div>
                        <div id="chatbot-title">CareMeal Assistant</div>
                        <div id="chatbot-status"><span id="chatbot-status-dot"></span> En ligne</div>
                    </div>
                </div>
                <button id="chatbot-close-btn" aria-label="Fermer le chatbot">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Messages -->
            <div id="chatbot-messages" role="log" aria-live="polite"></div>

            <!-- Suggestions -->
            <div id="chatbot-suggestions"></div>

            <!-- Input -->
            <div id="chatbot-input-area">
                <input type="text" id="chatbot-input"
                    placeholder="Posez votre question..."
                    autocomplete="off"
                    maxlength="500">
                <button id="chatbot-send-btn" aria-label="Envoyer">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </div>

        </div>`;

        const container = document.createElement('div');
        container.id = 'chatbot-container';
        container.innerHTML = html;
        document.body.appendChild(container);

        this._injectStyles();
        this._showWelcome();
        this._renderSuggestions();
    },

    // ── Styles CSS injectés ───────────────────────────────────
    _injectStyles() {
        const style = document.createElement('style');
        style.textContent = `
        #chatbot-container * { box-sizing: border-box; font-family: 'DM Sans', sans-serif; }

        /* Bouton flottant */
        #chatbot-toggle {
            position: fixed; bottom: 28px; right: 28px; z-index: 9998;
            width: 58px; height: 58px; border-radius: 50%;
            background: linear-gradient(135deg, #fe5516, #ff8a50);
            border: none; cursor: pointer; color: white; font-size: 1.3rem;
            box-shadow: 0 6px 20px rgba(254,85,22,0.45);
            display: flex; align-items: center; justify-content: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        #chatbot-toggle:hover { transform: scale(1.08); box-shadow: 0 8px 28px rgba(254,85,22,0.6); }

        #chatbot-notif-dot {
            position: absolute; top: 4px; right: 4px;
            width: 12px; height: 12px; border-radius: 50%;
            background: #10b981; border: 2px solid #0f172a;
            display: block;
        }

        /* Fenêtre */
        #chatbot-window {
            position: fixed; bottom: 100px; right: 28px; z-index: 9999;
            width: 370px; max-width: calc(100vw - 40px);
            height: 520px; max-height: calc(100vh - 130px);
            background: #1e293b;
            border-radius: 16px; border: 1px solid #334155;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            display: flex; flex-direction: column;
            animation: chatbot-slide-in 0.25s ease;
            overflow: hidden;
        }
        @keyframes chatbot-slide-in {
            from { opacity: 0; transform: translateY(20px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Header */
        #chatbot-header {
            background: linear-gradient(135deg, #fe5516, #c44010);
            padding: 14px 16px;
            display: flex; justify-content: space-between; align-items: center;
            flex-shrink: 0;
        }
        #chatbot-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; color: white;
        }
        #chatbot-title { color: white; font-weight: 700; font-size: 0.95rem; }
        #chatbot-status { color: rgba(255,255,255,0.8); font-size: 0.75rem; display: flex; align-items: center; gap: 5px; margin-top: 2px; }
        #chatbot-status-dot { width: 7px; height: 7px; border-radius: 50%; background: #10b981; display: inline-block; }
        #chatbot-close-btn {
            background: rgba(255,255,255,0.2); border: none; color: white;
            width: 28px; height: 28px; border-radius: 50%; cursor: pointer;
            display: flex; align-items: center; justify-content: center; font-size: 0.85rem;
        }
        #chatbot-close-btn:hover { background: rgba(255,255,255,0.35); }

        /* Messages */
        #chatbot-messages {
            flex: 1; overflow-y: auto; padding: 14px;
            display: flex; flex-direction: column; gap: 10px;
            scroll-behavior: smooth;
        }
        #chatbot-messages::-webkit-scrollbar { width: 4px; }
        #chatbot-messages::-webkit-scrollbar-track { background: transparent; }
        #chatbot-messages::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }

        .chatbot-msg { display: flex; gap: 8px; max-width: 88%; animation: msg-in 0.2s ease; }
        @keyframes msg-in { from { opacity:0; transform: translateY(6px); } to { opacity:1; transform: translateY(0); } }

        .chatbot-msg.user { align-self: flex-end; flex-direction: row-reverse; }
        .chatbot-msg.bot  { align-self: flex-start; }

        .chatbot-msg-avatar {
            width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.75rem; font-weight: 700;
        }
        .chatbot-msg.user .chatbot-msg-avatar { background: #fe5516; color: white; }
        .chatbot-msg.bot  .chatbot-msg-avatar { background: #334155; color: #94a3b8; }

        .chatbot-msg-bubble {
            padding: 10px 13px; border-radius: 12px;
            font-size: 0.88rem; line-height: 1.5; white-space: pre-wrap; word-break: break-word;
        }
        .chatbot-msg.user .chatbot-msg-bubble {
            background: linear-gradient(135deg, #fe5516, #c44010);
            color: white; border-bottom-right-radius: 4px;
        }
        .chatbot-msg.bot .chatbot-msg-bubble {
            background: #0f172a; color: #e2e8f0;
            border: 1px solid #334155; border-bottom-left-radius: 4px;
        }

        /* Typing indicator */
        .chatbot-typing .chatbot-msg-bubble {
            display: flex; align-items: center; gap: 5px; padding: 12px 16px;
        }
        .chatbot-typing-dot {
            width: 7px; height: 7px; border-radius: 50%; background: #64748b;
            animation: typing-bounce 1.2s infinite;
        }
        .chatbot-typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .chatbot-typing-dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes typing-bounce {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-6px); }
        }

        /* Suggestions */
        #chatbot-suggestions {
            padding: 0 12px 10px;
            display: flex; flex-wrap: wrap; gap: 6px;
            flex-shrink: 0;
        }
        .chatbot-suggestion {
            padding: 5px 11px; border-radius: 20px;
            border: 1px solid #334155; background: #0f172a;
            color: #94a3b8; font-size: 0.78rem; cursor: pointer;
            transition: all 0.15s; white-space: nowrap;
        }
        .chatbot-suggestion:hover { border-color: #fe5516; color: #fe5516; background: rgba(254,85,22,0.08); }

        /* Input */
        #chatbot-input-area {
            padding: 10px 12px; border-top: 1px solid #334155;
            display: flex; gap: 8px; align-items: center; flex-shrink: 0;
            background: #1e293b;
        }
        #chatbot-input {
            flex: 1; padding: 10px 14px; border-radius: 20px;
            border: 1.5px solid #334155; background: #0f172a;
            color: #f1f5f9; font-size: 0.88rem; outline: none;
            transition: border-color 0.2s;
        }
        #chatbot-input:focus { border-color: #fe5516; }
        #chatbot-input::placeholder { color: #475569; }
        #chatbot-input:disabled { opacity: 0.5; }

        #chatbot-send-btn {
            width: 38px; height: 38px; border-radius: 50%;
            background: linear-gradient(135deg, #fe5516, #c44010);
            border: none; color: white; cursor: pointer; font-size: 0.85rem;
            display: flex; align-items: center; justify-content: center;
            transition: transform 0.15s, opacity 0.15s; flex-shrink: 0;
        }
        #chatbot-send-btn:hover:not(:disabled) { transform: scale(1.1); }
        #chatbot-send-btn:disabled { opacity: 0.4; cursor: not-allowed; }

        /* Erreur */
        .chatbot-msg-bubble.error { border-color: rgba(239,68,68,0.4); color: #fca5a5; }
        `;
        document.head.appendChild(style);
    },

    // ── Afficher le message de bienvenue ──────────────────────
    _showWelcome() {
        const msg = this.welcomeMessages[this.userRole] || this.welcomeMessages.student;
        this._appendMessage('bot', msg);
    },

    // ── Afficher les suggestions ──────────────────────────────
    _renderSuggestions() {
        const container = document.getElementById('chatbot-suggestions');
        const list = this.suggestions[this.userRole] || this.suggestions.student;
        container.innerHTML = list.map(s =>
            `<button class="chatbot-suggestion" data-text="${s}">${s}</button>`
        ).join('');
    },

    // ── Binding des événements ────────────────────────────────
    _bindEvents() {
        // Toggle ouverture/fermeture
        document.getElementById('chatbot-toggle').addEventListener('click', () => this.toggle());
        document.getElementById('chatbot-close-btn').addEventListener('click', () => this.close());

        // Envoi du message
        document.getElementById('chatbot-send-btn').addEventListener('click', () => this._sendMessage());

        // Envoi avec Entrée (sans HTML5)
        document.getElementById('chatbot-input').addEventListener('keydown', (e) => {
            const key = e.key || e.keyCode;
            const isEnter = (key === 'Enter' || key === 13);
            const isShift = e.shiftKey;
            if (isEnter && !isShift) {
                e.preventDefault();
                this._sendMessage();
            }
        });

        // Suggestions cliquables
        document.getElementById('chatbot-suggestions').addEventListener('click', (e) => {
            const btn = e.target.closest('.chatbot-suggestion');
            if (btn) {
                document.getElementById('chatbot-input').value = btn.dataset.text;
                this._sendMessage();
                document.getElementById('chatbot-suggestions').style.display = 'none';
            }
        });
    },

    // ── Ouvrir / Fermer ───────────────────────────────────────
    toggle() {
        this.isOpen ? this.close() : this.open();
    },

    open() {
        this.isOpen = true;
        document.getElementById('chatbot-window').style.display = 'flex';
        document.getElementById('chatbot-icon-open').style.display = 'none';
        document.getElementById('chatbot-icon-close').style.display = 'block';
        document.getElementById('chatbot-notif-dot').style.display = 'none';
        document.getElementById('chatbot-input').focus();
        this._scrollToBottom();
    },

    close() {
        this.isOpen = false;
        document.getElementById('chatbot-window').style.display = 'none';
        document.getElementById('chatbot-icon-open').style.display = 'block';
        document.getElementById('chatbot-icon-close').style.display = 'none';
    },

    // ── Validation du message (sans if/else) ──────────────────
    _validateMessage(text) {
        const errors = {
            empty   : text.length === 0,
            tooLong : text.length > 500,
        };
        return Object.values(errors).every(v => v === false);
    },

    // ── Envoyer un message ────────────────────────────────────
    async _sendMessage() {
        const input   = document.getElementById('chatbot-input');
        const message = input.value.trim();

        const isValid = this._validateMessage(message);
        const isReady = !this.isLoading;

        // Utiliser un tableau de conditions au lieu de if/else
        const canSend = [isValid, isReady].every(Boolean);
        if (!canSend) return;

        // Afficher le message utilisateur
        this._appendMessage('user', message);
        input.value = '';

        // Cacher les suggestions après le premier message
        document.getElementById('chatbot-suggestions').style.display = 'none';

        // Ajouter à l'historique
        this.history.push({ role: 'user', parts: [{ text: message }] });

        // Afficher l'indicateur de chargement
        this._setLoading(true);
        const typingId = this._showTyping();

        try {
            const currentUser = (typeof App !== 'undefined') ? App.getCurrentUser() : null;
            const response = await fetch('/Controller/ChatbotController.php', {
                method  : 'POST',
                headers : { 'Content-Type': 'application/json' },
                body    : JSON.stringify({
                    message  : message,
                    history  : this.history.slice(-10),
                    user_id  : currentUser?.id   || null,
                    user_role: currentUser?.role || this.userRole
                })
            });

            const data = await response.json();
            this._removeTyping(typingId);

            const botText = data.success
                ? data.message
                : `�?� ${data.message || 'Une erreur est survenue.'}`;

            this._appendMessage('bot', botText, !data.success);

            // Ajouter la réponse à l'historique
            if (data.success) {
                this.history.push({ role: 'model', parts: [{ text: data.message }] });
            }

        } catch (err) {
            this._removeTyping(typingId);
            this._appendMessage('bot', '�?� Erreur de connexion. Vérifiez votre réseau.', true);
        }

        this._setLoading(false);
    },

    // ── Ajouter un message dans la fenêtre ────────────────────
    _appendMessage(role, text, isError) {
        const container = document.getElementById('chatbot-messages');
        const div = document.createElement('div');
        div.className = `chatbot-msg ${role}`;

        const avatarIcon = role === 'user' ? 'U' : '<i class="fa-solid fa-robot"></i>';
        const bubbleClass = isError ? 'chatbot-msg-bubble error' : 'chatbot-msg-bubble';

        div.innerHTML = `
            <div class="chatbot-msg-avatar">${avatarIcon}</div>
            <div class="${bubbleClass}">${this._escapeHtml(text)}</div>
        `;
        container.appendChild(div);
        this._scrollToBottom();
    },

    // ── Indicateur "en train de réfléchir" ────────────────────
    _showTyping() {
        const container = document.getElementById('chatbot-messages');
        const id = 'typing-' + Date.now();
        const div = document.createElement('div');
        div.className = 'chatbot-msg bot chatbot-typing';
        div.id = id;
        div.innerHTML = `
            <div class="chatbot-msg-avatar"><i class="fa-solid fa-robot"></i></div>
            <div class="chatbot-msg-bubble">
                <span class="chatbot-typing-dot"></span>
                <span class="chatbot-typing-dot"></span>
                <span class="chatbot-typing-dot"></span>
            </div>`;
        container.appendChild(div);
        this._scrollToBottom();
        return id;
    },

    _removeTyping(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    },

    // ── État de chargement ────────────────────────────────────
    _setLoading(state) {
        this.isLoading = state;
        const input   = document.getElementById('chatbot-input');
        const sendBtn = document.getElementById('chatbot-send-btn');
        const statusEl = document.getElementById('chatbot-status');

        input.disabled   = state;
        sendBtn.disabled = state;
        statusEl.innerHTML = state
            ? '<span style="width:7px;height:7px;border-radius:50%;background:#f59e0b;display:inline-block;"></span> En train de réfléchir...'
            : '<span id="chatbot-status-dot" style="width:7px;height:7px;border-radius:50%;background:#10b981;display:inline-block;"></span> En ligne';
    },

    // ── Scroll vers le bas ────────────────────────────────────
    _scrollToBottom() {
        const container = document.getElementById('chatbot-messages');
        if (container) container.scrollTop = container.scrollHeight;
    },

    // ── Échapper le HTML pour éviter les injections ───────────
    _escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/\n/g, '<br>');
    }
};

