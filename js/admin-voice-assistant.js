const AdminVoiceAssistant = {
  recognition: null,
  listening: false,
  isRequestInFlight: false,
  isInitialized: false,
  elements: {},
  stateKey: "caremeal_admin_assistant_state_v2",
  state: {
    open: false,
    pendingIntent: null,
    lastHeard: "",
    lastReply: "",
  },

  init() {
    if (this.isInitialized) return;
    this.isInitialized = true;
    this.loadState();
    this.injectUI();
    this.bindUI();
    this.initSpeechRecognition();
    this.restoreUIState();
  },

  loadState() {
    try {
      const raw = localStorage.getItem(this.stateKey);
      if (!raw) return;
      const parsed = JSON.parse(raw);
      if (parsed && typeof parsed === "object") {
        this.state = { ...this.state, ...parsed };
      }
    } catch (_e) {}
  },

  saveState() {
    try {
      localStorage.setItem(this.stateKey, JSON.stringify(this.state));
    } catch (_e) {}
  },

  restoreUIState() {
    if (this.state.open) {
      this.elements.panel.classList.remove("hidden");
    }
    if (this.state.lastHeard) {
      this.elements.heard.textContent = this.state.lastHeard;
    }
    if (this.state.lastReply) {
      this.elements.reply.textContent = this.state.lastReply;
    }
    if (this.state.pendingIntent) {
      this.setStatus("En attente de precision...");
    }
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
      .voice-assistant-fab.listening { animation: voicePulse 1.2s infinite; }
      .voice-assistant-panel {
        position: fixed;
        right: 24px;
        bottom: 92px;
        width: min(440px, calc(100vw - 24px));
        background: #0f1e34;
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 16px;
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
        z-index: 999;
        padding: 14px;
        color: #e7edf8;
      }
      .voice-assistant-panel.hidden { display: none; }
      .voice-assistant-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
      .voice-assistant-title { font-size: 0.95rem; font-weight: 700; }
      .voice-assistant-close { border: 0; background: transparent; color: #93a4c2; font-size: 1rem; cursor: pointer; }
      .voice-assistant-status { font-size: 0.8rem; color: #9fb0cf; margin-bottom: 10px; }
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
      .voice-assistant-input-row { display: flex; gap: 8px; margin-top: 10px; }
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
      .voice-assistant-input::placeholder { color: #9fb0cf; }
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
        <input class="voice-assistant-input" type="text" data-role="text-input" placeholder="Dis ou ecris une demande">
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
      this.state.open = false;
      this.saveState();
      this.elements.panel.classList.add("hidden");
    });

    this.elements.fab.addEventListener("click", () => {
      this.state.open = true;
      this.saveState();
      this.elements.panel.classList.remove("hidden");
      if (this.listening) this.stopListening();
      else this.startListening();
    });

    this.elements.sendBtn.addEventListener("click", () => this.handleTypedPrompt());
    this.elements.textInput.addEventListener("keydown", (event) => {
      if (event.key === "Enter") {
        event.preventDefault();
        this.handleTypedPrompt();
      }
    });

    window.addEventListener("beforeunload", () => this.saveState());
  },

  initSpeechRecognition() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
      this.setStatus("Web Speech API non supportee.");
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
      this.state.lastHeard = text;
      this.saveState();
      if (!text) {
        this.setStatus("Aucun texte reconnu.");
        return;
      }
      await this.handlePrompt(text);
    };

    this.recognition.onerror = (event) => {
      this.setStatus("Erreur micro: " + (event.error || "inconnue"));
    };

    this.recognition.onend = () => {
      this.listening = false;
      this.elements.fab.classList.remove("listening");
      this.elements.fab.innerHTML = '<i class="fa-solid fa-microphone"></i>';
    };
  },

  async startListening() {
    if (!this.recognition) return;
    this.setStatus("Verification micro...");
    try {
      if (navigator.mediaDevices?.getUserMedia) {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        stream.getTracks().forEach((t) => t.stop());
      }
    } catch (_e) {
      this.setStatus("Micro bloque. Autorise le micro.");
      return;
    }
    this.listening = true;
    this.elements.fab.classList.add("listening");
    this.elements.fab.innerHTML = '<i class="fa-solid fa-stop"></i>';
    this.setStatus("J ecoute...");
    this.recognition.start();
  },

  stopListening() {
    if (this.recognition) this.recognition.stop();
  },

  async handleTypedPrompt() {
    const text = (this.elements.textInput.value || "").trim();
    if (!text) return;
    this.state.open = true;
    this.elements.panel.classList.remove("hidden");
    this.elements.heard.textContent = text;
    this.state.lastHeard = text;
    this.saveState();
    this.elements.textInput.value = "";
    await this.handlePrompt(text);
  },

  async handlePrompt(text) {
    // Global escape hatch: cancel any pending multi-turn intent.
    if (this.isCancelCommand(text)) {
      this.clearPendingIntent();
      this.respond("D accord, j annule l action en cours. Quelle est ta nouvelle demande ?", true);
      return;
    }

    // 1) If assistant is waiting for details, continue conversation first.
    if (this.state.pendingIntent) {
      try {
        const handledPending = await this.handlePendingIntent(text);
        if (handledPending) return;
      } catch (error) {
        this.setStatus("Erreur action en attente.");
        this.respond("Je n ai pas pu terminer l action: " + (error.message || "erreur inconnue") + ". Tu peux reessayer ou dire annule.", true);
        return;
      }
    }

    // 2) deterministic admin actions
    const dataHandled = await this.handleDataCommand(text);
    if (dataHandled) return;

    // 3) local navigation/actions
    const localHandled = this.executeLocalCommand(text);
    if (localHandled) return;

    // 4) fallback LLM
    this.setStatus("Analyse IA...");
    await this.askGemini(text);
  },

  setPendingIntent(intent, askMessage) {
    this.state.pendingIntent = {
      ...intent,
      createdAt: Date.now(),
    };
    this.saveState();
    this.respond(askMessage, true);
  },

  clearPendingIntent() {
    this.state.pendingIntent = null;
    this.saveState();
  },

  async handlePendingIntent(text) {
    const pending = this.state.pendingIntent;
    if (!pending) return false;

    // Expire old pending intent to avoid sticky lock.
    if (pending.createdAt && Date.now() - pending.createdAt > 5 * 60 * 1000) {
      this.clearPendingIntent();
      this.respond("La demande precedente a expire. Redonne ta demande.", true);
      return true;
    }

    // Ignore greeting/small-talk while pending; keep waiting for real value.
    if (this.isConversationFiller(text)) {
      this.respond("Je suis en attente d une precision pour continuer. Dis annule pour repartir a zero.", true);
      return true;
    }

    const answer = this.cleanEntityName(this.extractQuoted(text) || text);
    if (!answer) return false;

    if (pending.type === "delete_category") {
      await this.callAdminApi({ action: "delete_category", nom_categorie: answer });
      this.clearPendingIntent();
      this.respond(`Categorie supprimee: ${answer}.`, true);
      return true;
    }

    if (pending.type === "delete_offer") {
      await this.callAdminApi({ action: "delete_offer", titre: answer });
      this.clearPendingIntent();
      this.respond(`Offre supprimee: ${answer}.`, true);
      return true;
    }

    if (pending.type === "block_user") {
      const target = this.parseIdentityFromText(text) || { type: "name", value: answer };
      const user = this.findUserLocal(target);
      if (!user) {
        this.respond("Utilisateur introuvable. Redonne nom ou email.", true);
        return true;
      }
      this.updateUserStatusLocal(user.id, "banned", `Utilisateur banni: ${user.name}`);
      this.clearPendingIntent();
      this.respond(`${user.name} est maintenant bloque.`, true);
      return true;
    }

    if (pending.type === "unblock_user") {
      const target = this.parseIdentityFromText(text) || { type: "name", value: answer };
      const user = this.findUserLocal(target);
      if (!user) {
        this.respond("Utilisateur introuvable. Redonne nom ou email.", true);
        return true;
      }
      this.updateUserStatusLocal(user.id, "active", `Utilisateur reactive: ${user.name}`);
      this.clearPendingIntent();
      this.respond(`${user.name} est maintenant actif.`, true);
      return true;
    }

    if (pending.type === "update_category_target_name") {
      await this.callAdminApi({
        action: "update_category",
        nom_categorie_source: pending.sourceName,
        nom_categorie: answer,
      });
      this.clearPendingIntent();
      this.respond(`Categorie modifiee: ${pending.sourceName} -> ${answer}.`, true);
      return true;
    }

    if (pending.type === "update_offer_target_title") {
      await this.callAdminApi({
        action: "update_offer",
        titre_source: pending.sourceTitle,
        titre: answer,
      });
      this.clearPendingIntent();
      this.respond(`Offre modifiee: ${pending.sourceTitle} -> ${answer}.`, true);
      return true;
    }

    return false;
  },

  isCancelCommand(text) {
    const n = this.normalize(text);
    return /(annule|annuler|stop|arrete|arret|reset|reinitialise|oublie|laisse tomber)/.test(n);
  },

  isConversationFiller(text) {
    const n = this.normalize(text);
    return /^(salut|bonjour|bonsoir|hello|hi|ok|d accord|merci|ca va|oui|non)$/.test(n.trim());
  },

  executeLocalCommand(text) {
    const normalized = this.normalize(text);

    if (/(^|\s)(salut|bonjour|bonsoir|hello|hi)(\s|$)/.test(normalized)) {
      this.respond("Bonjour. Je suis pret a t aider sur l espace admin.", true);
      return true;
    }
    if (/(comment tu t[ '’]?appelles|ton nom|qui es tu|tu es qui)/.test(normalized)) {
      this.respond("Je suis l assistant vocal admin de CareMeal.", true);
      return true;
    }
    if (/(que peux tu faire|tu peux faire quoi|aide|help)/.test(normalized)) {
      this.respond("Je peux naviguer, compter, creer, modifier, supprimer, bloquer, debloquer sur l espace admin.", true);
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
    if (wantsOpen || /(page|onglet|section)/.test(normalized) || normalized.split(/\s+/).length <= 2) {
      for (const cmd of navCommands) {
        if (cmd.words.some((word) => normalized.includes(word))) {
          this.state.open = true;
          this.saveState();
          this.respond(cmd.message, false);
          setTimeout(() => {
            window.location.href = cmd.url;
          }, 300);
          return true;
        }
      }
    }

    if (/(rafraichis|rafraichir|actualise|actualiser|recharge|recharger)/.test(normalized)) {
      this.respond("Je rafraichis la page.", false);
      setTimeout(() => window.location.reload(), 300);
      return true;
    }

    if (/(deconnexion|deconnecte|logout)/.test(normalized)) {
      this.respond("Je lance la deconnexion.", false);
      setTimeout(() => {
        const btn = document.querySelector("[data-action='logout']");
        if (btn) btn.click();
      }, 300);
      return true;
    }

    return false;
  },

  async handleDataCommand(text) {
    const normalized = this.normalize(text);
    const actionDelete = /(supprime|supprimer|efface|retire|delete)/.test(normalized);
    const actionUpdate = /(modifie|modifier|update|edite|editer|renomme|renommer)/.test(normalized);
    const actionCreate = /(ajoute|ajouter|cree|creer|nouvelle|nouveau)/.test(normalized);

    const asksCount = /(combien|nombre|total)/.test(normalized);
    const isCategory = /(categorie|categories)/.test(normalized);
    const isOffer = /(offre|offres)/.test(normalized);
    const isUserOrPartner = /(utilisateur|utilisateurs|partenaire|partenaires)/.test(normalized);

    if (asksCount && /utilisateur/.test(normalized)) {
      const s = this.getUserStatsLocal();
      this.respond(`Il y a ${s.totalUsers} utilisateurs hors admin.`, true);
      return true;
    }

    if (asksCount && /partenaire/.test(normalized)) {
      const s = this.getUserStatsLocal();
      this.respond(`Il y a ${s.totalPartners} partenaires. Actifs: ${s.activePartners}, en attente: ${s.pendingPartners}.`, true);
      return true;
    }

    if (asksCount && (isCategory || isOffer)) {
      const db = await this.callAdminApi({ action: "get_counts" });
      if (isCategory && isOffer) this.respond(`Il y a ${db.offers_count} offres et ${db.categories_count} categories.`, true);
      else if (isOffer) this.respond(`Il y a ${db.offers_count} offres.`, true);
      else this.respond(`Il y a ${db.categories_count} categories.`, true);
      return true;
    }

    if (/(bloque|bloquer|ban|bannir)/.test(normalized) && isUserOrPartner) {
      const target = this.parseIdentityFromText(text);
      if (!target) {
        this.setPendingIntent({ type: "block_user" }, "D accord. Quel utilisateur/partenaire veux-tu bloquer ?");
        return true;
      }
      const user = this.findUserLocal(target);
      if (!user) {
        this.respond("Utilisateur introuvable.", true);
        return true;
      }
      this.updateUserStatusLocal(user.id, "banned", `Utilisateur banni: ${user.name}`);
      this.respond(`${user.name} est maintenant bloque.`, true);
      return true;
    }

    if (/(debloque|debloquer|reactive|reactiver|unban)/.test(normalized) && isUserOrPartner) {
      const target = this.parseIdentityFromText(text);
      if (!target) {
        this.setPendingIntent({ type: "unblock_user" }, "D accord. Quel utilisateur/partenaire veux-tu debloquer ?");
        return true;
      }
      const user = this.findUserLocal(target);
      if (!user) {
        this.respond("Utilisateur introuvable.", true);
        return true;
      }
      this.updateUserStatusLocal(user.id, "active", `Utilisateur reactive: ${user.name}`);
      this.respond(`${user.name} est maintenant actif.`, true);
      return true;
    }

    if (actionCreate && isCategory) {
      const name = this.extractCategoryName(text);
      if (!name || name.length < 2) {
        this.respond('Donne juste le nom, exemple: "ajoute categorie desserts".', true);
        return true;
      }
      const created = await this.callAdminApi({
        action: "create_category",
        nom_categorie: name,
        description: this.extractLabeledValue(text, ["description", "desc"]) || "",
        icone: this.extractLabeledValue(text, ["icone", "icon"]) || "",
      });
      this.respond(`Categorie creee: ${created.nom_categorie}.`, true);
      return true;
    }

    if (actionDelete && isCategory) {
      const name = this.extractCategoryName(text);
      if (!name) {
        this.setPendingIntent({ type: "delete_category" }, "D accord. Quelle categorie veux-tu supprimer ?");
        return true;
      }
      await this.callAdminApi({ action: "delete_category", nom_categorie: name });
      this.respond(`Categorie supprimee: ${name}.`, true);
      return true;
    }

    if (actionUpdate && isCategory) {
      const source = this.extractLabeledValue(text, ["source", "ancien", "old"]) || this.extractCategoryName(text);
      const target = this.extractLabeledValue(text, ["nom", "nouveau", "new"]);

      if (!source) {
        this.respond("Quelle categorie veux-tu modifier ?", true);
        return true;
      }
      if (!target) {
        this.setPendingIntent({ type: "update_category_target_name", sourceName: source }, `D accord. Quel nouveau nom pour la categorie ${source} ?`);
        return true;
      }

      await this.callAdminApi({
        action: "update_category",
        nom_categorie_source: source,
        nom_categorie: target,
      });
      this.respond(`Categorie modifiee: ${source} -> ${target}.`, true);
      return true;
    }

    if (actionCreate && isOffer) {
      const payload = this.extractOfferPayload(text);
      if (!payload.titre || !payload.prix || !payload.prix_original || !payload.quantite || !payload.nom_categorie) {
        this.respond("Pour creer une offre: titre=...; prix=...; prix_original=...; quantite=...; categorie=...", true);
        return true;
      }
      const created = await this.callAdminApi({ action: "create_offer", ...payload, statut: "publiee" });
      this.respond(`Offre creee: ${created.titre}.`, true);
      return true;
    }

    if (actionDelete && isOffer) {
      const title = this.extractOfferTitle(text);
      if (!title) {
        this.setPendingIntent({ type: "delete_offer" }, "D accord. Quelle offre veux-tu supprimer ?");
        return true;
      }
      await this.callAdminApi({ action: "delete_offer", titre: title });
      this.respond(`Offre supprimee: ${title}.`, true);
      return true;
    }

    if (actionUpdate && isOffer) {
      const source = this.extractLabeledValue(text, ["source", "ancien", "old"]) || this.extractOfferTitle(text);
      if (!source) {
        this.respond("Quelle offre veux-tu modifier ?", true);
        return true;
      }

      const payload = this.extractOfferPayload(text);
      const hasAnyField =
        payload.titre || payload.description || payload.prix || payload.prix_original ||
        payload.quantite || payload.nom_categorie || payload.heure_debut || payload.heure_fin ||
        payload.photo_url || payload.statut;

      if (!hasAnyField) {
        this.setPendingIntent({ type: "update_offer_target_title", sourceTitle: source }, `D accord. Quel nouveau titre pour l offre ${source} ?`);
        return true;
      }

      await this.callAdminApi({ action: "update_offer", titre_source: source, ...payload });
      this.respond(`Offre modifiee: ${source}.`, true);
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
    try {
      const response = await fetch(this.buildApiUrl(), {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          text: [
            "Tu es l assistant vocal de l administrateur CareMeal.",
            "Reponds en francais et ne devine jamais des chiffres.",
            "Si tu ne sais pas, dis que tu ne sais pas.",
            "",
            "Demande admin: " + text,
          ].join("\n"),
        }),
      });

      const data = await response.json();
      if (!response.ok) {
        const error = new Error(data.error || "Erreur serveur");
        error.code = data.code || null;
        error.retryAfter = data.retry_after_seconds || null;
        error.meta = data.meta || null;
        throw error;
      }
      this.respond((data.reply || "").trim() || "Je n ai pas de reponse.", true);
    } catch (error) {
      if (error.code === "quota_exceeded") {
        const waitPart = error.retryAfter ? ` Reessaie dans ${error.retryAfter} secondes.` : "";
        const attemptsPart = error.meta?.attempts ? ` Tentatives: ${error.meta.attempts}.` : "";
        this.setStatus("Quota Gemini depasse.");
        this.respond("Le quota Gemini est depasse." + waitPart + attemptsPart, false);
      } else {
        this.setStatus("Erreur assistant.");
        this.respond("Erreur: " + (error.message || "inconnue"), false);
      }
    } finally {
      this.isRequestInFlight = false;
    }
  },

  parseIdentityFromText(text) {
    const email = this.extractLabeledValue(text, ["email", "mail"]);
    if (email) return { type: "email", value: email.toLowerCase() };
    const name = this.extractLabeledValue(text, ["nom", "name", "utilisateur", "partenaire"]);
    if (name) return { type: "name", value: name.toLowerCase() };

    const q = this.extractQuoted(text);
    if (q) return { type: "name", value: q.toLowerCase() };
    return null;
  },

  extractQuoted(text) {
    const m = text.match(/["“](.+?)["”]/);
    return m ? m[1].trim() : "";
  },

  extractCategoryName(text) {
    const quoted = this.extractQuoted(text);
    if (quoted) return this.cleanEntityName(quoted);
    const labeled = this.extractLabeledValue(text, ["nom", "categorie", "category"]);
    if (labeled) return this.cleanEntityName(labeled);

    const m = text.match(/cat[eé]gorie\s+(.+)$/i);
    if (m && m[1]) return this.cleanEntityName(m[1]);
    return "";
  },

  extractOfferTitle(text) {
    const quoted = this.extractQuoted(text);
    if (quoted) return this.cleanEntityName(quoted);
    const labeled = this.extractLabeledValue(text, ["titre", "offre", "nom"]);
    if (labeled) return this.cleanEntityName(labeled);

    const m = text.match(/offre\s+(.+)$/i);
    if (m && m[1]) return this.cleanEntityName(m[1]);
    return "";
  },

  cleanEntityName(value) {
    return (value || "")
      .trim()
      .replace(/^[\s,:;=-]+/, "")
      .replace(/[\s,:;=-]+$/, "")
      .replace(/^(la|le|les|une|un|du|de|des)\s+/i, "")
      .trim();
  },

  extractOfferPayload(text) {
    return {
      titre: this.extractLabeledValue(text, ["titre"]),
      description: this.extractLabeledValue(text, ["description", "desc"]),
      prix: this.extractLabeledValue(text, ["prix"]),
      prix_original: this.extractLabeledValue(text, ["prix_original", "prix original", "original"]),
      quantite: this.extractLabeledValue(text, ["quantite", "qte"]),
      nom_categorie: this.extractLabeledValue(text, ["categorie", "category"]),
      heure_debut: this.extractLabeledValue(text, ["heure_debut", "debut"]),
      heure_fin: this.extractLabeledValue(text, ["heure_fin", "fin"]),
      photo_url: this.extractLabeledValue(text, ["photo", "image", "photo_url"]),
      statut: this.extractLabeledValue(text, ["statut"]),
    };
  },

  extractLabeledValue(text, labels) {
    for (const label of labels) {
      const escaped = label.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
      const regex = new RegExp(`${escaped}\\s*[:=]\\s*([^;,\\n]+)`, "i");
      const match = text.match(regex);
      if (match && match[1]) return match[1].trim();
    }
    return "";
  },

  normalize(text) {
    return (text || "").toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
  },

  safeGetUsers() {
    try {
      if (typeof App !== "undefined" && typeof App.getUsers === "function") return App.getUsers();
    } catch (_e) {}
    return [];
  },

  getUserStatsLocal() {
    const users = this.safeGetUsers();
    const nonAdmin = users.filter((u) => u.role !== "admin");
    const partners = users.filter((u) => u.role === "partner");
    return {
      totalUsers: nonAdmin.length,
      totalPartners: partners.length,
      activePartners: partners.filter((u) => u.status === "active").length,
      pendingPartners: partners.filter((u) => u.status === "pending").length,
    };
  },

  findUserLocal(target) {
    const users = this.safeGetUsers();
    if (target.type === "email") return users.find((u) => (u.email || "").toLowerCase() === target.value) || null;
    return users.find((u) => (u.name || "").toLowerCase() === target.value) || null;
  },

  updateUserStatusLocal(id, status, logMessage) {
    if (typeof App === "undefined") return;
    if (typeof App.updateUser === "function") App.updateUser(id, { status });
    if (typeof App.addLog === "function") App.addLog(logMessage);
  },

  buildApiUrl() {
    const pathParts = window.location.pathname.split("/").filter(Boolean);
    const base = pathParts.length > 0 ? "/" + pathParts[0] : "";
    return base + "/api/voice-chat.php";
  },

  buildAdminApiUrl() {
    const pathParts = window.location.pathname.split("/").filter(Boolean);
    const base = pathParts.length > 0 ? "/" + pathParts[0] : "";
    return base + "/api/admin-assistant.php";
  },

  async callAdminApi(payload) {
    const response = await fetch(this.buildAdminApiUrl(), {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });
    const data = await response.json();
    if (!response.ok) throw new Error(data.error || "Erreur API admin");
    return data;
  },

  respond(message, speak) {
    this.elements.reply.textContent = message;
    this.state.lastReply = message;
    this.saveState();
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

(function bootstrapAdminVoice() {
  if (!/\/admin\//.test(window.location.pathname)) return;
  const start = () => {
    if (typeof AdminVoiceAssistant !== "undefined") AdminVoiceAssistant.init();
  };
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", start);
  else start();
})();
