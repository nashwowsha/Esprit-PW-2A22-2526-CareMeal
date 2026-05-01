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
      try {
        await this.handlePrompt(text);
      } catch (_e) {
        this.setStatus("Erreur traitement.");
        this.respond("Je n ai pas pu traiter cette demande. Reessaie avec une phrase plus precise.", true);
      }
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

    // If user starts a clearly new request, do not stay trapped in previous pending flow.
    if (this.state.pendingIntent && this.isNewIntentCommand(text)) {
      this.clearPendingIntent();
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

    const isAdminRequest = this.looksLikeAdminIntent(text);

    // 2) Gemini-driven admin intent resolution (priority)
    if (isAdminRequest) {
      const aiHandled = await this.askGeminiAction(text);
      if (aiHandled) return;
    }

    // 3) deterministic admin actions (fallback if Gemini parse failed)
    const dataHandled = await this.handleDataCommand(text);
    if (dataHandled) return;

    // 4) local navigation/actions
    const localHandled = this.executeLocalCommand(text);
    if (localHandled) return;

    if (isAdminRequest) {
      this.respond("Commande admin incomplete. Dis par exemple: modifier offre pizza prix 5.5, ou supprimer utilisateur Fatma Trabelsi.", true);
      return;
    }

    // 5) fallback LLM
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

    if (pending.type === "create_offer_title") {
      const title = this.sanitizeOfferTitle(answer);
      if (!this.isMeaningfulOfferTitle(title)) {
        this.respond("Je n ai pas compris le titre. Dis juste le nom de l offre, par exemple: pizza margherita.", true);
        return true;
      }

      const basePayload = pending.payload && typeof pending.payload === "object" ? pending.payload : {};
      const created = await this.callAdminApi({
        action: "create_offer",
        ...basePayload,
        titre: title,
        statut: "publiee",
      });
      this.clearPendingIntent();
      this.respond(`Offre creee: ${created.titre}.`, true);
      return true;
    }

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

    if (pending.type === "delete_user") {
      const target = this.parseIdentityFromText(text) || { type: "name", value: answer };
      const user = this.findUserLocal(target);
      if (!user) {
        this.clearPendingIntent();
        this.respond("Utilisateur introuvable. J annule cette action. Redonne une nouvelle demande complete.", true);
        return true;
      }
      this.deleteUserLocal(user.id, user.name);
      this.clearPendingIntent();
      this.respond(`${user.name} a ete supprime.`, true);
      return true;
    }

    if (pending.type === "block_user") {
      const target = this.parseIdentityFromText(text) || { type: "name", value: answer };
      const user = this.findUserLocal(target);
      if (!user) {
        this.clearPendingIntent();
        this.respond("Utilisateur introuvable. J annule cette action. Redonne une nouvelle demande complete.", true);
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
        this.clearPendingIntent();
        this.respond("Utilisateur introuvable. J annule cette action. Redonne une nouvelle demande complete.", true);
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

    if (pending.type === "update_offer_fields") {
      const payload = this.extractOfferPayload(text);
      const explicitStatus = this.extractOfferStatus(text);
      if (explicitStatus) {
        payload.statut = explicitStatus;
      }
      const explicitTitleTarget = this.extractLabeledValue(text, ["titre", "nom", "nouveau", "new"]);
      if (explicitTitleTarget) {
        payload.titre = this.sanitizeOfferTitle(explicitTitleTarget);
      } else {
        delete payload.titre;
      }

      if (payload.titre && this.normalize(payload.titre) === this.normalize(pending.sourceTitle || "")) {
        delete payload.titre;
      }
      const cleanedPayload = this.cleanOfferPayload(payload);
      const hasAnyField = Object.keys(cleanedPayload).length > 0;

      if (!hasAnyField) {
        this.respond("Dis exactement le champ a modifier, par exemple: prix 5.5, categorie glucides, statut brouillon, description sandwich chaud.", true);
        return true;
      }

      await this.callAdminApi({
        action: "update_offer",
        titre_source: pending.sourceTitle,
        ...cleanedPayload,
      });
      this.clearPendingIntent();
      this.respond(`Offre modifiee: ${pending.sourceTitle}.`, true);
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

  isNewIntentCommand(text) {
    const n = this.normalize(text);
    return /(comment tu t|qui es tu|aide|help|ouvre|ouvrir|va |affiche|montre|combien|supprime|modifier|modifie|ajoute|cree|bloque|debloque|annule|reset|logout|deconnexion|rafraich)/.test(n);
  },

  executeLocalCommand(text) {
    const normalized = this.normalize(text);

    // Never hijack CRUD admin commands with navigation shortcuts.
    if (/(supprime|supprimer|efface|retire|delete|modifie|modifier|update|ajoute|ajouter|cree|creer|prix|statut|description|categorie|reactive|reactiver|debloque|debloquer|bloque|bloquer|ban|unban)/.test(normalized)) {
      return false;
    }

    if (/(^|\s)(salut|bonjour|bonsoir|hello|hi)(\s|$)/.test(normalized)) {
      this.respond("Bonjour. Je suis pret a t aider sur l espace admin.", true);
      return true;
    }
    if (/(comment tu t[ 'â€™]?appelles|ton nom|qui es tu|tu es qui)/.test(normalized)) {
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
    const isUserOrPartner = /(utilisateur|utilisateurs|partenaire|partenaires|etablissement|etablissements|commerce|commerces)/.test(normalized);

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

    if (actionDelete && isUserOrPartner) {
      const target = this.parseIdentityFromText(text) || this.extractNaturalUserTarget(text);
      if (!target) {
        this.setPendingIntent({ type: "delete_user" }, "D accord. Quel utilisateur, partenaire ou etablissement veux-tu supprimer ?");
        return true;
      }
      const user = this.findUserLocal(target);
      if (!user) {
        this.respond("Utilisateur introuvable.", true);
        return true;
      }
      this.deleteUserLocal(user.id, user.name);
      this.respond(`${user.name} a ete supprime.`, true);
      return true;
    }

    // Allow natural commands like "supprimer Fatma Trabelsi" without saying "utilisateur".
    if (actionDelete && !isOffer && !isCategory) {
      const target = this.parseIdentityFromText(text) || this.extractNaturalUserTarget(text);
      if (target) {
        const user = this.findUserLocal(target);
        if (user) {
          this.deleteUserLocal(user.id, user.name);
          this.respond(`${user.name} a ete supprime.`, true);
          return true;
        }
      }
    }

    if (/(bloque|bloquer|ban|bannir)/.test(normalized) && isUserOrPartner) {
      const target = this.parseIdentityFromText(text) || this.extractNaturalUserTarget(text);
      if (!target) {
        this.setPendingIntent({ type: "block_user" }, "D accord. Quel utilisateur, partenaire ou etablissement veux-tu bloquer ?");
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
      const target = this.parseIdentityFromText(text) || this.extractNaturalUserTarget(text);
      if (!target) {
        this.setPendingIntent({ type: "unblock_user" }, "D accord. Quel utilisateur, partenaire ou etablissement veux-tu debloquer ?");
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

    if (actionUpdate && !isOffer && /(prix|statut|description|quantite|categorie|category)/.test(normalized)) {
      this.respond("Tu veux modifier quel offre ? Exemple: modifier offre pizza prix 5.5.", true);
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
      const renamePair = this.extractRenamePair(text, "categorie");
      const source =
        this.extractLabeledValue(text, ["source", "ancien", "old"]) ||
        renamePair.source ||
        this.extractCategorySourceFromUpdate(text) ||
        this.extractCategoryName(text);
      const target = this.extractLabeledValue(text, ["nom", "nouveau", "new"]) || renamePair.target;
      const description = this.extractCategoryDescription(text);
      const icone = this.extractLabeledValue(text, ["icone", "icon"]);

      if (!source) {
        this.respond("Quelle categorie veux-tu modifier ?", true);
        return true;
      }
      if (!target && !description && !icone) {
        this.setPendingIntent({ type: "update_category_target_name", sourceName: source }, `D accord. Quel nouveau nom pour la categorie ${source} ?`);
        return true;
      }

      await this.callAdminApi({
        action: "update_category",
        nom_categorie_source: source,
        nom_categorie: target || source,
        description: description || "",
        icone: icone || "",
      });
      this.respond(`Categorie modifiee: ${source}.`, true);
      return true;
    }

    if (actionCreate && isOffer) {
      const payload = this.extractOfferPayload(text);
      if (!this.isMeaningfulOfferTitle(payload.titre)) {
        const pendingPayload = { ...payload };
        delete pendingPayload.titre;
        this.setPendingIntent(
          { type: "create_offer_title", payload: pendingPayload },
          "D accord. Quel titre pour la nouvelle offre ?"
        );
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
      const renamePair = this.extractRenamePair(text, "offre");
      const source =
        this.extractLabeledValue(text, ["source", "ancien", "old"]) ||
        renamePair.source ||
        this.extractOfferSourceFromUpdate(text) ||
        this.extractOfferTitle(text);
      if (!source) {
        this.respond("Quelle offre veux-tu modifier ?", true);
        return true;
      }

      const payload = this.extractOfferPayload(text);
      const explicitTitleTarget =
        renamePair.target ||
        this.extractLabeledValue(text, ["titre", "nom", "nouveau", "new"]);
      if (explicitTitleTarget) {
        payload.titre = this.sanitizeOfferTitle(explicitTitleTarget);
      } else {
        delete payload.titre;
      }

      if (payload.titre && this.normalize(payload.titre) === this.normalize(source)) {
        delete payload.titre;
      }

      const explicitStatus = this.extractOfferStatus(text);
      if (explicitStatus) {
        payload.statut = explicitStatus;
      }

      const cleanedPayload = this.cleanOfferPayload(payload);
      const hasAnyField = Object.keys(cleanedPayload).length > 0;

      if (!hasAnyField) {
        this.setPendingIntent(
          { type: "update_offer_fields", sourceTitle: source },
          `D accord. Que veux-tu modifier pour l offre ${source} (prix, categorie, statut, description) ?`
        );
        return true;
      }

      await this.callAdminApi({ action: "update_offer", titre_source: source, ...cleanedPayload });
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
            "Tu es l assistant vocal admin CareMeal (CRUD admin uniquement).",
            "N ecris JAMAIS de reponse generaliste hors CareMeal.",
            "Si la demande n est pas assez precise, demande uniquement la precision manquante en une phrase courte.",
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
        error.details = data.details || null;
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
        const details = error.details ? ` (${error.details})` : "";
        this.setStatus("Erreur Gemini.");
        this.respond("Erreur Gemini: " + (error.message || "inconnue") + details, false);
      }
    } finally {
      this.isRequestInFlight = false;
    }
  },

  async askGeminiAction(text) {
    if (this.isRequestInFlight) {
      this.setStatus("Une requete est deja en cours...");
      return false;
    }
    this.isRequestInFlight = true;
    this.setStatus("Analyse action admin via Gemini...");

    try {
      const response = await fetch(this.buildApiUrl(), {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          text: this.buildGeminiActionPrompt(text),
          json_mode: true,
        }),
      });

      const data = await response.json();
      if (!response.ok) {
        const error = new Error(data.error || "Erreur serveur");
        error.code = data.code || null;
        error.retryAfter = data.retry_after_seconds || null;
        error.meta = data.meta || null;
        error.details = data.details || null;
        throw error;
      }

      const parsed = this.parseGeminiActionReply((data.reply || "").trim());
      if (!parsed || !parsed.action) {
        return false;
      }

      return await this.executeGeminiAction(parsed);
    } catch (error) {
      if (error.code === "quota_exceeded") {
        const waitPart = error.retryAfter ? ` Reessaie dans ${error.retryAfter} secondes.` : "";
        this.setStatus("Quota Gemini depasse.");
        this.respond("Le quota Gemini est depasse." + waitPart, false);
        return true;
      }
      this.setStatus("Erreur interpretation action.");
      return false;
    } finally {
      this.isRequestInFlight = false;
    }
  },

  buildGeminiActionPrompt(userText) {
    return [
      "Tu es un parseur de commandes admin CareMeal.",
      "Tu n es PAS un chatbot general.",
      "Convertis la demande en JSON strict SANS markdown et SANS texte autour.",
      "Schema exact:",
      '{"action":"","target_name":"","target_email":"","source_name":"","new_name":"","title":"","source_title":"","description":"","categorie":"","prix":"","prix_original":"","quantite":"","statut":"","icone":""}',
      "Actions autorisees:",
      "delete_user, block_user, unblock_user, get_counts, create_category, update_category, delete_category, create_offer, update_offer, delete_offer, unknown",
      "Regles:",
      "- Si info manquante, mets des champs vides et choisis quand meme la meilleure action.",
      "- Ne fabrique jamais d ids.",
      "- 'etablissement' est un synonyme de partenaire.",
      "- Si la demande contient seulement un nom (ex: 'glucides'), renvoie action='unknown' et mets ce nom dans target_name.",
      "- Pour update_offer: source_title = offre existante a modifier. new_name = nouveau titre seulement si renommage explicite.",
      "- Reponds uniquement le JSON.",
      "",
      "Demande admin:",
      userText,
    ].join("\n");
  },

  parseGeminiActionReply(reply) {
    if (!reply) return null;
    const cleaned = reply.replace(/^```(?:json)?/i, "").replace(/```$/i, "").trim();
    try {
      const obj = JSON.parse(cleaned);
      if (obj && typeof obj === "object") return obj;
    } catch (_e) {}

    const start = cleaned.indexOf("{");
    const end = cleaned.lastIndexOf("}");
    if (start >= 0 && end > start) {
      const sub = cleaned.slice(start, end + 1);
      try {
        const obj = JSON.parse(sub);
        if (obj && typeof obj === "object") return obj;
      } catch (_e) {}
    }
    return null;
  },

  async executeGeminiAction(cmd) {
    const action = String(cmd.action || "").trim().toLowerCase();
    if (!action || action === "unknown") return false;

    const targetName = this.cleanEntityName(cmd.target_name || "");
    const targetEmail = String(cmd.target_email || "").trim().toLowerCase();

    if (action === "delete_user") {
      if (!targetEmail && this.isWeakIdentityTarget(targetName)) {
        this.setPendingIntent({ type: "delete_user" }, "Quel utilisateur, partenaire ou etablissement veux-tu supprimer ?");
        return true;
      }
      const target =
        targetEmail ? { type: "email", value: targetEmail } :
          (targetName ? { type: "name", value: targetName.toLowerCase() } : null);
      if (!target) {
        this.setPendingIntent({ type: "delete_user" }, "Quel utilisateur, partenaire ou etablissement veux-tu supprimer ?");
        return true;
      }
      const user = this.findUserLocal(target);
      if (!user) {
        this.respond("Utilisateur introuvable.", true);
        return true;
      }
      this.deleteUserLocal(user.id, user.name);
      this.respond(`${user.name} a ete supprime.`, true);
      return true;
    }

    if (action === "block_user" || action === "unblock_user") {
      if (!targetEmail && this.isWeakIdentityTarget(targetName)) {
        this.setPendingIntent(
          { type: action === "block_user" ? "block_user" : "unblock_user" },
          action === "block_user"
            ? "Quel utilisateur, partenaire ou etablissement veux-tu bloquer ?"
            : "Quel utilisateur, partenaire ou etablissement veux-tu debloquer ?"
        );
        return true;
      }
      const target =
        targetEmail ? { type: "email", value: targetEmail } :
          (targetName ? { type: "name", value: targetName.toLowerCase() } : null);
      if (!target) {
        this.setPendingIntent(
          { type: action === "block_user" ? "block_user" : "unblock_user" },
          action === "block_user"
            ? "Quel utilisateur, partenaire ou etablissement veux-tu bloquer ?"
            : "Quel utilisateur, partenaire ou etablissement veux-tu debloquer ?"
        );
        return true;
      }
      const user = this.findUserLocal(target);
      if (!user) {
        this.respond("Utilisateur introuvable.", true);
        return true;
      }
      const nextStatus = action === "block_user" ? "banned" : "active";
      this.updateUserStatusLocal(user.id, nextStatus, `Utilisateur ${nextStatus === "banned" ? "banni" : "reactive"}: ${user.name}`);
      this.respond(
        nextStatus === "banned"
          ? `${user.name} est maintenant bloque.`
          : `${user.name} est maintenant actif.`,
        true
      );
      return true;
    }

    if (action === "get_counts") {
      const db = await this.callAdminApi({ action: "get_counts" });
      this.respond(`Il y a ${db.offers_count} offres et ${db.categories_count} categories.`, true);
      return true;
    }

    if (action === "create_category") {
      const name = this.cleanEntityName(cmd.new_name || cmd.source_name || cmd.title || "");
      if (!name) {
        this.respond("Quel nom pour la categorie ?", true);
        return true;
      }
      const created = await this.callAdminApi({
        action: "create_category",
        nom_categorie: name,
        description: String(cmd.description || "").trim(),
      });
      this.respond(`Categorie creee: ${created.nom_categorie}.`, true);
      return true;
    }

    if (action === "update_category") {
      const source = this.cleanEntityName(cmd.source_name || cmd.target_name || "");
      const target = this.cleanEntityName(cmd.new_name || cmd.title || "");
      const description = this.cleanFieldText(cmd.description || "");
      const icone = this.cleanFieldText(cmd.icone || "");
      if (!source) {
        this.respond("Quelle categorie veux-tu modifier ?", true);
        return true;
      }
      if (!target && !description && !icone) {
        this.setPendingIntent({ type: "update_category_target_name", sourceName: source }, `Quel nouveau nom pour la categorie ${source} ?`);
        return true;
      }
      await this.callAdminApi({
        action: "update_category",
        nom_categorie_source: source,
        nom_categorie: target || source,
        description: description || "",
        icone: icone || "",
      });
      this.respond(`Categorie modifiee: ${source}.`, true);
      return true;
    }

    if (action === "delete_category") {
      const name = this.cleanEntityName(cmd.source_name || cmd.target_name || cmd.new_name || "");
      if (!name) {
        this.setPendingIntent({ type: "delete_category" }, "Quelle categorie veux-tu supprimer ?");
        return true;
      }
      await this.callAdminApi({ action: "delete_category", nom_categorie: name });
      this.respond(`Categorie supprimee: ${name}.`, true);
      return true;
    }

    if (action === "create_offer") {
      const title = this.sanitizeOfferTitle(cmd.title || cmd.new_name || "");
      const payload = {
        action: "create_offer",
        titre: title,
        description: String(cmd.description || "").trim(),
        prix: String(cmd.prix || "").trim(),
        prix_original: String(cmd.prix_original || "").trim(),
        quantite: String(cmd.quantite || "").trim(),
        nom_categorie: this.cleanEntityName(cmd.categorie || ""),
        statut: String(cmd.statut || "publiee").trim() || "publiee",
      };
      if (!this.isMeaningfulOfferTitle(payload.titre)) {
        const pendingPayload = { ...payload };
        delete pendingPayload.titre;
        this.setPendingIntent({ type: "create_offer_title", payload: pendingPayload }, "Quel titre pour la nouvelle offre ?");
        return true;
      }
      const created = await this.callAdminApi(payload);
      this.respond(`Offre creee: ${created.titre}.`, true);
      return true;
    }

    if (action === "update_offer") {
      const source = this.cleanEntityName(cmd.source_title || cmd.source_name || cmd.target_name || "");
      if (!source) {
        this.respond("Quelle offre veux-tu modifier ?", true);
        return true;
      }
      const payload = {
        action: "update_offer",
        titre_source: source,
        titre: this.sanitizeOfferTitle(cmd.new_name || ""),
        description: String(cmd.description || "").trim(),
        prix: String(cmd.prix || "").trim(),
        prix_original: String(cmd.prix_original || "").trim(),
        quantite: String(cmd.quantite || "").trim(),
        nom_categorie: this.cleanEntityName(cmd.categorie || ""),
        statut: this.extractOfferStatus(String(cmd.statut || "").trim()),
      };
      Object.keys(payload).forEach((k) => {
        if (payload[k] === "") delete payload[k];
      });
      if (payload.titre && this.normalize(payload.titre) === this.normalize(source)) {
        delete payload.titre;
      }
      if (Object.keys(payload).length <= 2) {
        this.setPendingIntent(
          { type: "update_offer_fields", sourceTitle: source },
          `Que veux-tu modifier pour l offre ${source} (prix, categorie, statut, description) ?`
        );
        return true;
      }
      await this.callAdminApi(payload);
      this.respond(`Offre modifiee: ${source}.`, true);
      return true;
    }

    if (action === "delete_offer") {
      const title = this.cleanEntityName(cmd.source_title || cmd.title || cmd.target_name || "");
      if (!title) {
        this.setPendingIntent({ type: "delete_offer" }, "Quelle offre veux-tu supprimer ?");
        return true;
      }
      await this.callAdminApi({ action: "delete_offer", titre: title });
      this.respond(`Offre supprimee: ${title}.`, true);
      return true;
    }

    return false;
  },

  parseIdentityFromText(text) {
    const email = this.extractLabeledValue(text, ["email", "mail"]);
    if (email) return { type: "email", value: email.toLowerCase() };
    const name = this.extractLabeledValue(text, ["nom", "name", "utilisateur", "partenaire", "etablissement", "commerce"]);
    if (name) return { type: "name", value: name.toLowerCase() };

    const q = this.extractQuoted(text);
    if (q) return { type: "name", value: q.toLowerCase() };
    return null;
  },

  extractNaturalUserTarget(text) {
    const raw = (text || "")
      .replace(/\b(je veux|j veux|je voudrais|j voudrais|je souhaite|j souhaite|peux tu|s il te plait)\b/gi, " ")
      .replace(/\b(supprime|supprimer|efface|retire|delete|bloque|bloquer|bannir|ban|debloque|debloquer|reactive|reactiver|unban)\b/gi, " ")
      .replace(/\b(utilisateur|utilisateurs|user|users|partenaire|partenaires|etablissement|etablissements|commerce|commerces)\b/gi, " ")
      .replace(/\b(stp|svp|merci)\b/gi, " ")
      .replace(/[,:;!?]/g, " ")
      .replace(/\s+/g, " ")
      .trim();

    if (!raw || raw.length < 3) return null;
    const normalized = this.normalize(raw);
    const weakTokens = new Set([
      "je", "j", "veux", "voudrais", "souhaite", "un", "une", "le", "la", "les", "de", "des", "du", "stp", "svp", "merci"
    ]);
    const strongTokens = normalized.split(/\s+/).filter((t) => t && !weakTokens.has(t));
    if (strongTokens.length === 0) return null;
    return { type: "name", value: raw.toLowerCase() };
  },

  extractQuoted(text) {
    const m = text.match(/["'“”«»](.+?)["'“”«»]/);
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

  extractOfferSourceFromUpdate(text) {
    const m = (text || "").match(/\boffre\s+(.+?)\s+(?:prix|prix original|prix_original|quantite|categorie|category|description|desc|statut|heure|photo|image|vers|en)\b/i);
    if (!m || !m[1]) return "";
    return this.cleanEntityName(m[1]);
  },

  extractCategorySourceFromUpdate(text) {
    const m = (text || "").match(/\bcategorie\s+(.+?)\s+(?:description|desc|icone|icon|en|vers|to)\b/i);
    if (!m || !m[1]) return "";
    return this.cleanEntityName(m[1]);
  },

  extractRenamePair(text, entityKeyword) {
    const escaped = entityKeyword.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    const regex = new RegExp(`\\b${escaped}\\s+(.+?)\\s+(?:en|vers|to)\\s+(.+)$`, "i");
    const m = (text || "").match(regex);
    if (!m) return { source: "", target: "" };
    return {
      source: this.cleanEntityName(m[1]),
      target: this.cleanEntityName(m[2]),
    };
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
    const inferredTitle = this.inferOfferTitle(text);
    const naturalDescription = this.extractOfferDescription(text);
    return {
      titre: this.sanitizeOfferTitle(this.extractLabeledValue(text, ["titre"]) || inferredTitle),
      description: this.extractLabeledValue(text, ["description", "desc"]) || naturalDescription,
      prix: this.extractLabeledValue(text, ["prix"]) || this.extractNumberByKeyword(text, ["prix"]),
      prix_original:
        this.extractLabeledValue(text, ["prix_original", "prix original", "original"]) ||
        this.extractNumberByKeyword(text, ["prix original", "prix_original", "original"]),
      quantite: this.extractLabeledValue(text, ["quantite", "qte"]) || this.extractIntegerByKeyword(text, ["quantite", "qte", "stock"]),
      nom_categorie:
        this.extractLabeledValue(text, ["categorie", "category"]) ||
        this.extractCategoryFromNaturalText(text),
      heure_debut: this.extractLabeledValue(text, ["heure_debut", "debut"]),
      heure_fin: this.extractLabeledValue(text, ["heure_fin", "fin"]),
      photo_url: this.extractLabeledValue(text, ["photo", "image", "photo_url"]),
      statut: this.extractLabeledValue(text, ["statut"]),
    };
  },

  inferOfferTitle(text) {
    const quoted = this.extractQuoted(text);
    if (quoted) {
      const directTitle = this.sanitizeOfferTitle(quoted);
      return this.isMeaningfulOfferTitle(directTitle) ? directTitle : "";
    }

    const cleaned = (text || "")
      .replace(/\b(je veux|j veux|je voudrais|j voudrais|je souhaite|j souhaite)\b/gi, " ")
      .replace(/\b(ajoute|ajouter|cree|creer|nouvelle|nouveau)\b/gi, " ")
      .replace(/\b(une|un|la|le|les|des|du|de)\b/gi, " ")
      .replace(/\boffre(s)?\b/gi, " ")
      .replace(/\b(nomme|nomme[e]?|appele|appel[eé]e|intitule|intitul[eé]e)\b/gi, " ")
      .replace(/\b(avec|prix|prix_original|prix original|quantite|categorie|category|description|desc)\b[\s:=].*$/i, " ")
      .replace(/[;,]/g, " ")
      .trim();

    const title = this.sanitizeOfferTitle(cleaned);
    return this.isMeaningfulOfferTitle(title) ? title : "";
  },

  sanitizeOfferTitle(value) {
    return (value || "")
      .trim()
      .replace(/^[\s,;:.!?=+\-_/\\]+/, "")
      .replace(/[\s,;:.!?=+\-_/\\]+$/, "")
      .replace(/\s+/g, " ")
      .trim();
  },

  isMeaningfulOfferTitle(value) {
    const title = this.sanitizeOfferTitle(value);
    if (!title || title.length < 3) return false;

    const normalized = this.normalize(title);
    if (/^(je|j)$/.test(normalized)) return false;
    if (/^(je|j)\s+(veux|voudrais|souhaite)$/.test(normalized)) return false;
    if (/^(ajoute|ajouter|cree|creer|offre|nouveau|nouvelle)$/.test(normalized)) return false;
    if (/^(une|un)\s+offre$/.test(normalized)) return false;
    if (/^(titre|nom|nomme|nommee)$/.test(normalized)) return false;

    const weakTokens = new Set([
      "je", "j", "veux", "voudrais", "souhaite", "ajoute", "ajouter", "cree", "creer",
      "offre", "un", "une", "le", "la", "les", "de", "des", "du", "stp", "svp", "merci"
    ]);
    const tokens = normalized.split(/\s+/).filter(Boolean);
    const strongTokens = tokens.filter((t) => !weakTokens.has(t));
    return strongTokens.length > 0;
  },

  extractCategoryFromNaturalText(text) {
    const match = (text || "").match(/\b(?:dans|de|categorie|category)\s+([a-zA-Z0-9À-ÿ_\-\s]{2,})$/i);
    if (!match || !match[1]) return "";
    return this.cleanEntityName(match[1]);
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

  extractNumberByKeyword(text, keywords) {
    for (const keyword of keywords) {
      const escaped = keyword.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
      const re = new RegExp(`\\b${escaped}\\b\\s*(?:[:=]|a|à)?\\s*([0-9]+(?:[.,][0-9]+)?)`, "i");
      const m = (text || "").match(re);
      if (m && m[1]) return m[1].trim();
    }
    return "";
  },

  extractIntegerByKeyword(text, keywords) {
    const value = this.extractNumberByKeyword(text, keywords);
    if (!value) return "";
    const n = parseInt(String(value).replace(",", "."), 10);
    if (Number.isNaN(n)) return "";
    return String(n);
  },

  extractCategoryDescription(text) {
    const labeled = this.extractLabeledValue(text, ["description", "desc"]);
    if (labeled) return this.cleanFieldText(labeled);

    const m = (text || "").match(/\bdescription\b\s+(?:de|du|pour)?\s*(?:la\s+)?(?:categorie\s+)?(.+)$/i);
    if (!m || !m[1]) return "";
    return this.cleanFieldText(m[1]);
  },

  extractOfferDescription(text) {
    const m = (text || "").match(/\bdescription\b\s+(?:de|du|pour)?\s*(?:l['’]?\s*)?(?:offre\s+)?(.+)$/i);
    if (!m || !m[1]) return "";
    return this.cleanFieldText(m[1]);
  },

  extractOfferStatus(text) {
    const n = this.normalize(text || "");
    if (!n) return "";
    if (/\b(publie|publiee|publiees|active)\b/.test(n)) return "publiee";
    if (/\b(brouillon|draft)\b/.test(n)) return "brouillon";
    if (/\b(expire|expiree|expirees)\b/.test(n)) return "expiree";
    if (/\b(archive|archivee|archivees)\b/.test(n)) return "archivee";

    const labeled = this.extractLabeledValue(text, ["statut", "status"]);
    if (!labeled) return "";
    const ln = this.normalize(labeled);
    if (/\b(publie|publiee|active)\b/.test(ln)) return "publiee";
    if (/\b(brouillon|draft)\b/.test(ln)) return "brouillon";
    if (/\b(expire|expiree)\b/.test(ln)) return "expiree";
    if (/\b(archive|archivee)\b/.test(ln)) return "archivee";
    return "";
  },

  cleanFieldText(value) {
    return (value || "")
      .replace(/^[\s:=,;.-]+/, "")
      .replace(/[\s,;.-]+$/, "")
      .trim();
  },

  cleanOfferPayload(payload) {
    const allowedKeys = [
      "titre",
      "description",
      "prix",
      "prix_original",
      "quantite",
      "nom_categorie",
      "heure_debut",
      "heure_fin",
      "photo_url",
      "statut",
    ];
    const out = {};
    for (const key of allowedKeys) {
      if (!Object.prototype.hasOwnProperty.call(payload, key)) continue;
      const value = payload[key];
      if (value === null || value === undefined) continue;
      if (typeof value === "string" && value.trim() === "") continue;
      out[key] = value;
    }
    return out;
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
    if (target.type === "email") {
      return users.find((u) => (u.email || "").toLowerCase() === target.value) || null;
    }

    const wanted = this.normalize((target.value || "").trim());
    if (!wanted) return null;

    const exact = users.find((u) => this.normalize(u.name || "") === wanted);
    if (exact) return exact;

    const contains = users.find((u) => this.normalize(u.name || "").includes(wanted) || wanted.includes(this.normalize(u.name || "")));
    return contains || null;
  },

  updateUserStatusLocal(id, status, logMessage) {
    if (typeof App === "undefined") return;
    if (typeof App.updateUser === "function") App.updateUser(id, { status });
    if (typeof App.addLog === "function") App.addLog(logMessage);
    if (typeof Admin !== "undefined" && typeof Admin.loadUsers === "function") {
      Admin.loadUsers();
    }
    if (typeof Admin !== "undefined" && typeof Admin.loadPartners === "function") {
      Admin.loadPartners();
    }
  },

  deleteUserLocal(id, name) {
    if (typeof App === "undefined") return;
    if (typeof App.deleteUser === "function") App.deleteUser(id);
    if (typeof App.addLog === "function") App.addLog(`Utilisateur supprime: ${name}`);
    if (typeof Admin !== "undefined" && typeof Admin.loadUsers === "function") {
      Admin.loadUsers();
    }
    if (typeof Admin !== "undefined" && typeof Admin.loadPartners === "function") {
      Admin.loadPartners();
    }
  },

  looksLikeAdminIntent(text) {
    const n = this.normalize(text);
    return /(utilisateur|partenaire|etablissement|commerce|offre|categorie|evenement|supprime|modifier|modifie|ajoute|cree|bloque|debloque|reactive|reactiver|ban|unban|statut|quantite|prix|description)/.test(n);
  },

  isLikelyAdminEntityOnly(text) {
    const n = this.normalize(text).trim();
    if (!n) return false;
    if (this.isConversationFiller(n)) return false;
    if (/(bonjour|salut|aide|help|qui es tu|comment tu t)/.test(n)) return false;
    const words = n.split(/\s+/).filter(Boolean);
    if (words.length > 3) return false;
    return /^[a-z0-9\s'’\-]+$/i.test(n);
  },

  isWeakIdentityTarget(value) {
    const n = this.normalize(value || "").trim();
    if (!n || n.length < 3) return true;
    if (/^(un|une|le|la|les|utilisateur|partenaire|etablissement|commerce)s?$/.test(n)) return true;
    const weakTokens = new Set(["un", "une", "le", "la", "les", "utilisateur", "partenaire", "etablissement", "commerce", "je", "veux"]);
    const tokens = n.split(/\s+/).filter(Boolean);
    const strong = tokens.filter((t) => !weakTokens.has(t));
    return strong.length === 0;
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
      const spokenMessage = this.toSpeechText(message);
      const utterance = new SpeechSynthesisUtterance(spokenMessage);
      utterance.lang = "fr-FR";
      utterance.rate = 1;
      utterance.pitch = 1;
      window.speechSynthesis.cancel();
      window.speechSynthesis.speak(utterance);
    }
  },

  toSpeechText(message) {
    return (message || "")
      .replace(/[`*_#~]/g, " ")
      .replace(/[\/\\]+/g, " ")
      .replace(/\s*[:=]+\s*/g, " ")
      .replace(/[|<>\[\]{}]/g, " ")
      .replace(/[^\p{L}\p{N}\s.,!?;:'"()%+-]/gu, " ")
      .replace(/\s+/g, " ")
      .trim();
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

