const AdminVoiceAssistant = {
  recognition: null,
  listening: false,
  elements: {},

  init() {
    this.injectUI();
    this.bindUI();
    this.initSpeechRecognition();
  },

  injectUI() {
    const style = document.createElement('style');
    style.textContent = `
      .voice-assistant-fab {
        position: fixed;
        right: 24px;
        bottom: 24px;
        width: 56px;
        height: 56px;
        border: 0;
        border-radius: 50%;
        background: linear-gradient(135deg, #EF4444, #F97316);
        color: #fff;
        box-shadow: 0 12px 30px rgba(239, 68, 68, 0.45);
        z-index: 1000;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
      }

      .voice-assistant-fab.listening {
        animation: voicePulse 1.2s infinite;
      }

      .voice-assistant-panel {
        position: fixed;
        right: 24px;
        bottom: 92px;
        width: min(360px, calc(100vw - 24px));
        background: #0f1e34;
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 16px;
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
        z-index: 999;
        padding: 14px;
        color: #e7edf8;
      }

      .voice-assistant-panel.hidden {
        display: none;
      }

      .voice-assistant-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
      }

      .voice-assistant-title {
        font-size: 0.95rem;
        font-weight: 700;
      }

      .voice-assistant-close {
        border: 0;
        background: transparent;
        color: #93a4c2;
        font-size: 1rem;
        cursor: pointer;
      }

      .voice-assistant-status {
        font-size: 0.8rem;
        color: #9fb0cf;
        margin-bottom: 10px;
      }

      .voice-assistant-block {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.09);
        border-radius: 10px;
        padding: 10px;
        margin-top: 8px;
      }

      .voice-assistant-label {
        font-size: 0.7rem;
        letter-spacing: 0.06em;
        color: #9fb0cf;
        text-transform: uppercase;
        margin-bottom: 6px;
      }

      .voice-assistant-text {
        font-size: 0.88rem;
        line-height: 1.35;
        color: #e7edf8;
        white-space: pre-wrap;
      }

      @keyframes voicePulse {
        0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.45); }
        70% { box-shadow: 0 0 0 16px rgba(239, 68, 68, 0); }
        100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
      }
    `;
    document.head.appendChild(style);

    const panel = document.createElement('section');
    panel.className = 'voice-assistant-panel hidden';
    panel.innerHTML = `
      <div class="voice-assistant-head">
        <div class="voice-assistant-title">Assistant vocal admin</div>
        <button class="voice-assistant-close" type="button" aria-label="Fermer">✕</button>
      </div>
      <div class="voice-assistant-status">Prêt. Clique sur le micro puis parle.</div>
      <div class="voice-assistant-block">
        <div class="voice-assistant-label">Texte reconnu</div>
        <div class="voice-assistant-text" data-role="heard">...</div>
      </div>
      <div class="voice-assistant-block">
        <div class="voice-assistant-label">Réponse</div>
        <div class="voice-assistant-text" data-role="reply">...</div>
      </div>
    `;

    const fab = document.createElement('button');
    fab.className = 'voice-assistant-fab';
    fab.type = 'button';
    fab.title = 'Parler avec l’assistant';
    fab.innerHTML = '<i class="fa-solid fa-microphone"></i>';

    document.body.appendChild(panel);
    document.body.appendChild(fab);

    this.elements = {
      panel,
      fab,
      closeBtn: panel.querySelector('.voice-assistant-close'),
      status: panel.querySelector('.voice-assistant-status'),
      heard: panel.querySelector('[data-role="heard"]'),
      reply: panel.querySelector('[data-role="reply"]')
    };
  },

  bindUI() {
    this.elements.closeBtn.addEventListener('click', () => {
      this.elements.panel.classList.add('hidden');
    });

    this.elements.fab.addEventListener('click', () => {
      this.elements.panel.classList.remove('hidden');
      if (this.listening) {
        this.stopListening();
      } else {
        this.startListening();
      }
    });
  },

  initSpeechRecognition() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
      this.setStatus('Web Speech API non supportée par ce navigateur.');
      this.elements.fab.disabled = true;
      return;
    }

    this.recognition = new SpeechRecognition();
    this.recognition.lang = 'fr-FR';
    this.recognition.continuous = false;
    this.recognition.interimResults = false;

    this.recognition.onresult = async (event) => {
      const text = event.results?.[0]?.[0]?.transcript?.trim() || '';
      this.elements.heard.textContent = text || '(aucun texte)';
      if (!text) {
        this.setStatus('Aucun texte reconnu.');
        return;
      }

      const localAction = this.executeLocalCommand(text);
      if (localAction) {
        return;
      }

      this.setStatus('Analyse de la demande...');
      await this.askGemini(text);
    };

    this.recognition.onerror = (event) => {
      const err = event.error || 'inconnue';
      if (err === 'network') {
        this.setStatus('Erreur micro réseau. Autorise le micro et vérifie Internet.');
      } else if (err === 'not-allowed') {
        this.setStatus('Micro refusé. Autorise le micro dans le navigateur.');
      } else {
        this.setStatus('Erreur micro: ' + err);
      }
    };

    this.recognition.onend = () => {
      this.listening = false;
      this.elements.fab.classList.remove('listening');
      this.elements.fab.innerHTML = '<i class="fa-solid fa-microphone"></i>';
      if (this.elements.status.textContent === 'J’écoute...') {
        this.setStatus('Écoute terminée.');
      }
    };
  },

  startListening() {
    if (!this.recognition) return;

    this.elements.reply.textContent = '...';
    this.setStatus('J’écoute...');
    this.listening = true;
    this.elements.fab.classList.add('listening');
    this.elements.fab.innerHTML = '<i class="fa-solid fa-stop"></i>';
    this.recognition.start();
  },

  stopListening() {
    if (!this.recognition) return;
    this.recognition.stop();
  },

  executeLocalCommand(text) {
    const normalized = text
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '');

    const navCommands = [
      { words: ['utilisateur', 'utilisateurs', 'user', 'users'], url: 'users.html', message: 'J’ouvre la page Utilisateurs.' },
      { words: ['partenaire', 'partenaires'], url: 'partners.html', message: 'J’ouvre la page Partenaires.' },
      { words: ['categorie', 'categories'], url: 'categorie.php', message: 'J’ouvre la page Catégories.' },
      { words: ['offre', 'offres'], url: 'offers.php', message: 'J’ouvre la page Offres.' },
      { words: ['evenement', 'evenements', 'event', 'events'], url: 'events.html', message: 'J’ouvre la page Événements.' },
      { words: ['log', 'logs', 'activite'], url: 'logs.html', message: 'J’ouvre la page Logs.' },
      { words: ['dashboard', 'vue globale', 'accueil'], url: 'dashboard.html', message: 'Retour au dashboard.' }
    ];

    const wantsOpen = /(ouvre|va|aller|affiche|montre|navigue)/.test(normalized);
    if (wantsOpen) {
      for (const cmd of navCommands) {
        if (cmd.words.some(word => normalized.includes(word))) {
          this.respond(cmd.message, false);
          setTimeout(() => {
            window.location.href = cmd.url;
          }, 450);
          return true;
        }
      }
    }

    if (/(rafraichis|rafraichir|actualise|actualiser|recharge|recharger)/.test(normalized)) {
      this.respond('Je rafraîchis la page.', false);
      setTimeout(() => window.location.reload(), 450);
      return true;
    }

    if (/(deconnexion|deconnecte|logout)/.test(normalized)) {
      this.respond('Je lance la déconnexion.', false);
      setTimeout(() => {
        const btn = document.querySelector('[data-action="logout"]');
        if (btn) btn.click();
      }, 450);
      return true;
    }

    return false;
  },

  async askGemini(text) {
    const apiUrl = this.buildApiUrl();
    const prompt = [
      'Tu es l assistant vocal de l administrateur CareMeal.',
      'Reponds en francais, court et concret.',
      'Si la demande implique une action que tu ne peux pas faire directement depuis cette page, dis exactement l action manuelle a faire.'
    ].join(' ');

    try {
      const response = await fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ text: `${prompt}\n\nDemande admin: ${text}` })
      });
      const data = await response.json();
      if (!response.ok) {
        throw new Error(data.error || 'Erreur serveur');
      }

      const reply = (data.reply || '').trim() || 'Je n ai pas de reponse pour le moment.';
      this.respond(reply, true);
    } catch (error) {
      this.setStatus('Erreur assistant: ' + error.message);
      this.elements.reply.textContent = 'Je n ai pas pu contacter le serveur.';
    }
  },

  buildApiUrl() {
    const pathParts = window.location.pathname.split('/').filter(Boolean);
    const projectBase = pathParts.length > 0 ? '/' + pathParts[0] : '';
    return projectBase + '/api/voice-chat.php';
  },

  respond(message, speak) {
    this.elements.reply.textContent = message;
    this.setStatus('Réponse prête.');
    if (speak) {
      const utterance = new SpeechSynthesisUtterance(message);
      utterance.lang = 'fr-FR';
      utterance.rate = 1;
      utterance.pitch = 1;
      window.speechSynthesis.cancel();
      window.speechSynthesis.speak(utterance);
    }
  },

  setStatus(message) {
    this.elements.status.textContent = message;
  }
};
