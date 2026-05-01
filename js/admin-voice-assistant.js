const AdminVoiceAssistant = {
  recognition: null,
  listening: false,
  isRequestInFlight: false,
  quotaBlockedUntilMs: 0,
  elements: {},

  init() {
    this.injectUI();
    this.bindUI();
    this.initSpeechRecognition();
  },

  injectUI() {
    const style = document.createElement("style");
    style.textContent = `
      .voice-assistant-fab {
        position: fixed;
        right: 24px;
        bottom: 24px;
        width: 56px;
        height: 56px;
        border: 0;
        border-radius: 50%;
        background: linear-gradient(135deg, #ef4444, #f97316);
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

      .voice-assistant-input-row {
        display: flex;
        gap: 8px;
        margin-top: 10px;
      }

      .voice-assistant-input {
        flex: 1;
        min-width: 0;
        border: 1px solid rgba(255, 255, 255, 0.18);
        background: rgba(255, 255, 255, 0.06);
        color: #e7edf8;
        border-radius: 10px;
        padding: 10px 12px;
        outline: none;
      }

      .voice-assistant-input::placeholder {
        color: #9fb0cf;
      }

      .voice-assistant-send {
        border: 0;
        border-radius: 10px;
        padding: 10px 12px;
        background: linear-gradient(135deg, #ef4444, #f97316);
        color: #fff;
        font-weight: 600;
        cursor: pointer;
      }

      @keyframes voicePulse {
        0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.45); }
        70% { box-shadow: 0 0 0 16px rgba(239, 68, 68, 0); }
        100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
      }
    `;
    document.head.appendChild(style);

    const panel = document.createElement("section");
    panel.className = "voice-assistant-panel hidden";
    panel.innerHTML = `
      <div class="voice-assistant-head">
        <div class="voice-assistant-title">Assistant vocal admin</div>
        <button class="voice-assistant-close" type="button" aria-label="Fermer">x</button>
      </div>
      <div class="voice-assistant-status">Pret. Clique sur le micro puis parle.</div>
      <div class="voice-assistant-block">
        <div class="voice-assistant-label">Texte reconnu</div>
        <div class="voice-assistant-text" data-role="heard">...</div>
      </div>
      <div class="voice-assistant-block">
        <div class="voice-assistant-label">Reponse</div>
        <div class="voice-assistant-text" data-role="reply">...</div>
      </div>
      <div class="voice-assistant-input-row">
        <input class="voice-assistant-input" type="text" data-role="text-input" placeholder="Ecris une demande si le micro ne marche pas">
        <button class="voice-assistant-send" type="button" data-role="send-btn">Envoyer</button>
      </div>
    `;

    const fab = document.createElement("button");
    fab.className = "voice-assistant-fab";
    fab.type = "button";
    fab.title = "Parler avec l assistant";
    fab.innerHTML = '<i class="fa-solid fa-microphone"></i>';

    document.body.appendChild(panel);
    document.body.appendChild(fab);

    this.elements = {
      panel,
      fab,
      closeBtn: panel.querySelector(".voice-assistant-close"),
      status: panel.querySelector(".voice-assistant-status"),
      heard: panel.querySelector('[data-role="heard"]'),
      reply: panel.querySelector('[data-role="reply"]'),
      textInput: panel.querySelector('[data-role="text-input"]'),
      sendBtn: panel.querySelector('[data-role="send-btn"]'),
    };
  },

  bindUI() {
    this.elements.closeBtn.addEventListener("click", () => {
      this.elements.panel.classList.add("hidden");
    });

    this.elements.fab.addEventListener("click", () => {
      this.elements.panel.classList.remove("hidden");
      if (this.listening) {
        this.stopListening();
      } else {
        this.startListening();
      }
    });

    this.elements.sendBtn.addEventListener("click", () => this.handleTypedPrompt());
    this.elements.textInput.addEventListener("keydown", (event) => {
      if (event.key === "Enter") {
        event.preventDefault();
        this.handleTypedPrompt();
      }
    });
  },

  initSpeechRecognition() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
      this.setStatus("Web Speech API non supportee par ce navigateur.");
      this.elements.fab.disabled = true;
      return;
    }

    this.recognition = new SpeechRecognition();
    this.recognition.lang = "fr-FR";
    this.recognition.continuous = false;
    this.recognition.interimResults = false;

    this.recognition.onresult = async (event) => {
      const text = event.results?.[0]?.[0]?.transcript?.trim() || "";
      this.elements.heard.textContent = text || "(aucun texte)";
      if (!text) {
        this.setStatus("Aucun texte reconnu.");
        return;
      }

      const dataAction = await this.handleDataCommand(text);
      if (dataAction) return;

      const localAction = this.executeLocalCommand(text);
      if (localAction) return;

      this.setStatus("Analyse de la demande...");
      await this.askGemini(text);
    };

    this.recognition.onerror = (event) => {
      const err = event.error || "inconnue";
      if (err === "network") {
        const isEdge = /Edg\//.test(navigator.userAgent);
        this.setStatus(
          isEdge
            ? "Erreur STT reseau sur Edge. Essaie Chrome ou utilise la zone texte."
            : "Erreur STT reseau. Verifie Internet ou utilise la zone texte."
        );
      } else if (err === "not-allowed") {
        this.setStatus("Micro refuse. Autorise le micro pour ce site.");
      } else if (err === "audio-capture") {
        this.setStatus("Aucun micro detecte.");
      } else {
        this.setStatus("Erreur micro: " + err);
      }
    };

    this.recognition.onend = () => {
      this.listening = false;
      this.elements.fab.classList.remove("listening");
      this.elements.fab.innerHTML = '<i class="fa-solid fa-microphone"></i>';
      if (this.elements.status.textContent === "J ecoute...") {
        this.setStatus("Ecoute terminee.");
      }
    };
  },

  async startListening() {
    if (!this.recognition) return;

    this.elements.reply.textContent = "...";
    this.setStatus("Verification micro...");

    try {
      if (navigator.mediaDevices?.getUserMedia) {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        stream.getTracks().forEach((track) => track.stop());
      }
    } catch (_error) {
      this.setStatus("Micro bloque. Autorise le micro pour ce site.");
      return;
    }

    this.setStatus("J ecoute...");
    this.listening = true;
    this.elements.fab.classList.add("listening");
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
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "");

    if (/(^|\s)(salut|bonjour|bonsoir|hello|hi)(\s|$)/.test(normalized)) {
      this.respond("Bonjour. Je suis pret a t aider sur l espace admin.", true);
      return true;
    }

    if (/(comment tu t[ '’]?appelles|ton nom|qui es tu|tu es qui)/.test(normalized)) {
      this.respond("Je suis l assistant vocal admin de CareMeal.", true);
      return true;
    }

    if (/(que peux tu faire|tu peux faire quoi|aide|help)/.test(normalized)) {
      this.respond(
        "Je peux ouvrir les pages admin, donner le nombre d offres/categories, ajouter une categorie et ajouter une offre avec le format guide.",
        true
      );
      return true;
    }

    const navCommands = [
      { words: ["utilisateur", "utilisateurs", "user", "users"], url: "users.html", message: "J ouvre la page Utilisateurs." },
      { words: ["partenaire", "partenaires"], url: "partners.html", message: "J ouvre la page Partenaires." },
      { words: ["categorie", "categories"], url: "categorie.php", message: "J ouvre la page Categories." },
      { words: ["offre", "offres"], url: "offers.php", message: "J ouvre la page Offres." },
      { words: ["evenement", "evenements", "event", "events"], url: "events.html", message: "J ouvre la page Evenements." },
      { words: ["log", "logs", "activite"], url: "logs.html", message: "J ouvre la page Logs." },
      { words: ["dashboard", "vue globale", "accueil"], url: "dashboard.html", message: "Retour au dashboard." },
    ];

    const wantsOpen = /(ouvre|ouvrir|va|aller|affiche|afficher|montre|montrer|navigue|naviguer)/.test(normalized);
    if (wantsOpen) {
      for (const cmd of navCommands) {
        if (cmd.words.some((word) => normalized.includes(word))) {
          this.respond(cmd.message, false);
          setTimeout(() => {
            window.location.href = cmd.url;
          }, 450);
          return true;
        }
      }
    }

    // Fallback: if user says only the page target (e.g. "page offres"), navigate directly.
    if (/(page|onglet|section)/.test(normalized) || normalized.split(/\s+/).length <= 2) {
      for (const cmd of navCommands) {
        if (cmd.words.some((word) => normalized.includes(word))) {
          this.respond(cmd.message, false);
          setTimeout(() => {
            window.location.href = cmd.url;
          }, 450);
          return true;
        }
      }
    }

    if (/(rafraichis|rafraichir|actualise|actualiser|recharge|recharger)/.test(normalized)) {
      this.respond("Je rafraichis la page.", false);
      setTimeout(() => window.location.reload(), 450);
      return true;
    }

    if (/(deconnexion|deconnecte|logout)/.test(normalized)) {
      this.respond("Je lance la deconnexion.", false);
      setTimeout(() => {
        const btn = document.querySelector("[data-action='logout']");
        if (btn) btn.click();
      }, 450);
      return true;
    }

    return false;
  },

  async handleDataCommand(text) {
    const normalized = text
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "");

    const asksCount = /(combien|nombre|total)/.test(normalized);
    const mentionsOffers = /(offre|offres)/.test(normalized);
    const mentionsCategories = /(categorie|categories)/.test(normalized);

    if (asksCount && (mentionsOffers || mentionsCategories)) {
      this.setStatus("Lecture des donnees reelles...");
      try {
        const stats = await this.callAdminApi({ action: "get_counts" });
        if (mentionsOffers && mentionsCategories) {
          this.respond(
            `Il y a ${stats.offers_count} offres et ${stats.categories_count} categories en base.`,
            true
          );
        } else if (mentionsOffers) {
          this.respond(`Il y a ${stats.offers_count} offres en base.`, true);
        } else {
          this.respond(`Il y a ${stats.categories_count} categories en base.`, true);
        }
      } catch (error) {
        this.setStatus("Erreur donnees: " + error.message);
      }
      return true;
    }

    if (/(ajoute|ajouter|cree|creer|nouvelle|nouveau)/.test(normalized) && mentionsCategories) {
      let name = "";
      const quoted = text.match(/["“](.+?)["”]/);
      if (quoted) {
        name = quoted[1].trim();
      } else {
        const m = text.match(/cat[eé]gorie\s*(?:nomm[ée]e?|appel[ée]e?|:|=)?\s*(.+)$/i);
        if (m) name = m[1].trim();
      }

      name = name.replace(/^(de|la|le|une|un)\s+/i, "").trim();

      if (name.length < 2) {
        this.respond('Donne le nom de categorie comme: ajoute categorie "Desserts".', true);
        return true;
      }

      this.setStatus("Creation de categorie...");
      try {
        const created = await this.callAdminApi({
          action: "create_category",
          nom_categorie: name,
        });
        this.respond(`Categorie creee: ${created.nom_categorie}.`, true);
      } catch (error) {
        this.setStatus("Erreur creation categorie: " + error.message);
      }
      return true;
    }

    if (/(ajoute|ajouter|cree|creer|nouvelle|nouveau)/.test(normalized) && mentionsOffers) {
      const titre = this.extractLabeledValue(text, ["titre"]);
      const prixOriginal = this.extractLabeledValue(text, ["prix_original", "prix original", "original"]);
      const prix = this.extractLabeledValue(text, ["prix"]);
      const quantite = this.extractLabeledValue(text, ["quantite", "qte"]);
      const categorie = this.extractLabeledValue(text, ["categorie", "category"]);
      const description = this.extractLabeledValue(text, ["description", "desc"]);

      if (!titre || !prix || !prixOriginal || !quantite || !categorie) {
        this.respond(
          "Pour ajouter une offre, utilise ce format: ajoute offre titre=Salade; prix=8; prix_original=12; quantite=20; categorie=Desserts; description=Fraiche.",
          true
        );
        return true;
      }

      this.setStatus("Creation de l offre...");
      try {
        const created = await this.callAdminApi({
          action: "create_offer",
          titre,
          prix,
          prix_original: prixOriginal,
          quantite,
          nom_categorie: categorie,
          description: description || "",
          statut: "publiée",
        });
        this.respond(`Offre creee: ${created.titre}.`, true);
      } catch (error) {
        this.setStatus("Erreur creation offre: " + error.message);
      }
      return true;
    }

    return false;
  },

  async askGemini(text) {
    if (this.isRequestInFlight) {
      this.setStatus("Une requete est deja en cours...");
      return;
    }

    this.isRequestInFlight = true;
    const apiUrl = this.buildApiUrl();
    const prompt = [
      "Tu es l assistant vocal de l administrateur CareMeal.",
      "Reponds en francais, court et concret.",
      "Si la demande implique une action que tu ne peux pas faire directement depuis cette page, dis exactement l action manuelle a faire.",
    ].join(" ");

    try {
      const response = await fetch(apiUrl, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ text: `${prompt}\n\nDemande admin: ${text}` }),
      });

      const data = await response.json();
      if (!response.ok) {
        const error = new Error(data.error || "Erreur serveur");
        error.code = data.code || null;
        error.retryAfter = data.retry_after_seconds || null;
        error.meta = data.meta || null;
        throw error;
      }

      const reply = (data.reply || "").trim() || "Je n ai pas de reponse pour le moment.";
      this.respond(reply, true);
    } catch (error) {
      if (error.code === "quota_exceeded") {
        const waitPart = error.retryAfter ? ` Reessaie dans ${error.retryAfter} secondes.` : "";
        const attemptsPart = error.meta?.attempts ? ` Tentatives: ${error.meta.attempts}.` : "";
        const xaiPart = error.meta?.xai_tried
          ? (error.meta?.xai_http_code ? ` Fallback xAI HTTP ${error.meta.xai_http_code}.` : " Fallback xAI essaye.")
          : " Fallback xAI non configure.";
        const xaiErrPart = error.meta?.xai_error ? ` Detail xAI: ${error.meta.xai_error}` : "";
        if (error.retryAfter) {
          this.quotaBlockedUntilMs = Date.now() + (error.retryAfter * 1000);
        } else {
          this.quotaBlockedUntilMs = Date.now() + 30000;
        }
        this.setStatus("Quota Gemini temporairement depasse.");
        this.elements.reply.textContent = "Le quota Gemini est depasse pour le moment." + waitPart + attemptsPart + xaiPart + xaiErrPart;
      } else {
        this.setStatus("Erreur assistant: " + error.message);
        this.elements.reply.textContent = "Je n ai pas pu contacter le serveur.";
      }
    } finally {
      this.isRequestInFlight = false;
    }
  },

  async handleTypedPrompt() {
    const text = (this.elements.textInput.value || "").trim();
    if (!text) return;

    this.elements.panel.classList.remove("hidden");
    this.elements.heard.textContent = text;
    this.elements.textInput.value = "";

    const dataAction = await this.handleDataCommand(text);
    if (dataAction) return;

    const localAction = this.executeLocalCommand(text);
    if (localAction) return;

    this.setStatus("Analyse de la demande...");
    await this.askGemini(text);
  },

  buildApiUrl() {
    const pathParts = window.location.pathname.split("/").filter(Boolean);
    const projectBase = pathParts.length > 0 ? "/" + pathParts[0] : "";
    return projectBase + "/api/voice-chat.php";
  },

  buildAdminApiUrl() {
    const pathParts = window.location.pathname.split("/").filter(Boolean);
    const projectBase = pathParts.length > 0 ? "/" + pathParts[0] : "";
    return projectBase + "/api/admin-assistant.php";
  },

  async callAdminApi(payload) {
    const response = await fetch(this.buildAdminApiUrl(), {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });
    const data = await response.json();
    if (!response.ok) {
      throw new Error(data.error || "Erreur API admin");
    }
    return data;
  },

  extractLabeledValue(text, labels) {
    for (const label of labels) {
      const escaped = label.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
      const regex = new RegExp(`${escaped}\\s*[:=]\\s*([^;,\\n]+)`, "i");
      const match = text.match(regex);
      if (match && match[1]) {
        return match[1].trim();
      }
    }
    return "";
  },

  respond(message, speak) {
    this.elements.reply.textContent = message;
    this.setStatus("Reponse prete.");
    if (speak) {
      const utterance = new SpeechSynthesisUtterance(message);
      utterance.lang = "fr-FR";
      utterance.rate = 1;
      utterance.pitch = 1;
      window.speechSynthesis.cancel();
      window.speechSynthesis.speak(utterance);
    }
  },

  setStatus(message) {
    this.elements.status.textContent = message;
  },
};
