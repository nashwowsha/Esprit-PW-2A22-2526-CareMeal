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
    pendingOfferSearchQuery: "",
  },

  init() {
    if (this.isInitialized) return;
    this.isInitialized = true;
    this.loadState();
    this.injectUI();
    this.bindUI();
    this.initSpeechRecognition();
    this.restoreUIState();
    this.resumePendingOfferSearch();
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
    if (!SpeechRecognition) return;

    this.recognition = new SpeechRecognition();
    this.recognition.lang = "fr-FR";
    this.recognition.continuous = false;
    this.recognition.interimResults = false;

    this.recognition.onresult = async (event) => {
      const text = event.results?.[0]?.[0]?.transcript?.trim() || "";
      await this.onSpeechText(text);
    };

    this.recognition.onerror = (event) => {
      const errorCode = event?.error || "inconnue";
      if (errorCode === "no-speech") {
        this.setStatus("Aucun son detecte. Re-clique le micro et parle juste apres.");
        return;
      }
      if (errorCode === "aborted") {
        this.setStatus("Ecoute arretee.");
        return;
      }
      if (errorCode === "audio-capture") {
        this.setStatus("Micro non detecte. Verifie le peripherique micro.");
        return;
      }
      if (errorCode === "not-allowed" || errorCode === "service-not-allowed") {
        this.setStatus("Micro bloque. Autorise le micro dans le navigateur.");
        return;
      }
      this.setStatus("Erreur micro: " + errorCode);
    };

    this.recognition.onend = () => {
      this.resetListeningUi();
    };
  },

  async startListening() {
    if (this.listening) return;

    if (this.supportsServerStt()) {
      await this.startServerSttRecording();
      return;
    }

    if (!this.recognition) {
      this.setStatus("Aucun moteur STT disponible.");
      return;
    }

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
    this.setListeningUi();
    this.setStatus("J ecoute...");
    try {
      this.recognition.start();
    } catch (_e) {
      this.resetListeningUi();
      this.setStatus("Impossible de demarrer la reconnaissance.");
    }
  },

  stopListening() {
    if (this.mediaRecorder && this.mediaRecorder.state === "recording") {
      try {
        this.mediaRecorder.stop();
      } catch (_e) {}
      return;
    }
    if (this.recognition) this.recognition.stop();
  },

  supportsServerStt() {
    return Boolean(window.MediaRecorder && navigator.mediaDevices?.getUserMedia && window.FormData && window.fetch);
  },

  getPreferredAudioMimeType() {
    const types = [
      "audio/webm;codecs=opus",
      "audio/webm",
      "audio/ogg;codecs=opus",
      "audio/mp4",
    ];
    for (const type of types) {
      try {
        if (window.MediaRecorder && MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported(type)) {
          return type;
        }
      } catch (_e) {}
    }
    return "";
  },

  setListeningUi() {
    this.listening = true;
    this.elements.fab.classList.add("listening");
    this.elements.fab.innerHTML = '<i class="fa-solid fa-stop"></i>';
  },

  resetListeningUi() {
    this.listening = false;
    this.elements.fab.classList.remove("listening");
    this.elements.fab.innerHTML = '<i class="fa-solid fa-microphone"></i>';
    if (this.recordingTimeout) {
      clearTimeout(this.recordingTimeout);
      this.recordingTimeout = null;
    }
  },

  releaseMediaStream() {
    if (this.mediaStream) {
      this.mediaStream.getTracks().forEach((t) => t.stop());
    }
    this.mediaStream = null;
  },

  async startServerSttRecording() {
    this.setStatus("Verification micro...");
    try {
      this.mediaStream = await navigator.mediaDevices.getUserMedia({ audio: true });
    } catch (_e) {
      this.setStatus("Micro bloque. Autorise le micro.");
      return;
    }

    const mimeType = this.getPreferredAudioMimeType();
    const options = mimeType ? { mimeType } : undefined;

    try {
      this.mediaRecorder = new MediaRecorder(this.mediaStream, options);
    } catch (_e) {
      this.releaseMediaStream();
      if (this.recognition) {
        this.setStatus("STT serveur indisponible. Fallback navigateur.");
        await this.startListeningFallbackRecognition();
        return;
      }
      this.setStatus("Impossible de demarrer l enregistrement.");
      return;
    }

    this.audioChunks = [];
    this.mediaRecorder.ondataavailable = (event) => {
      if (event.data && event.data.size > 0) this.audioChunks.push(event.data);
    };

    this.mediaRecorder.onerror = () => {
      this.resetListeningUi();
      this.releaseMediaStream();
      this.setStatus("Erreur enregistrement micro.");
    };

    this.mediaRecorder.onstop = async () => {
      const blob = new Blob(this.audioChunks, { type: this.mediaRecorder?.mimeType || "audio/webm" });
      this.mediaRecorder = null;
      this.audioChunks = [];
      this.resetListeningUi();
      this.releaseMediaStream();

      if (!blob || blob.size === 0) {
        this.setStatus("Aucun son detecte.");
        return;
      }

      await this.transcribeWithServerStt(blob);
    };

    this.setListeningUi();
    this.setStatus("J ecoute... clique encore pour arreter.");
    this.mediaRecorder.start();
    this.recordingTimeout = setTimeout(() => {
      if (this.mediaRecorder && this.mediaRecorder.state === "recording") {
        try {
          this.mediaRecorder.stop();
        } catch (_e) {}
      }
    }, 12000);
  },

  async startListeningFallbackRecognition() {
    if (!this.recognition) return;
    this.setListeningUi();
    this.setStatus("J ecoute...");
    try {
      this.recognition.start();
    } catch (_e) {
      this.resetListeningUi();
      this.setStatus("Impossible de demarrer la reconnaissance.");
    }
  },

  async transcribeWithServerStt(blob) {
    this.setStatus("Transcription IA...");
    const form = new FormData();
    form.append("audio", blob, "speech.webm");
    form.append("language", "fr");
    form.append("prompt", "Transcription en francais claire.");

    try {
      const response = await fetch(this.buildSttApiUrl(), {
        method: "POST",
        body: form,
      });
      const data = await response.json().catch(() => ({}));
      if (!response.ok) {
        throw new Error(data.error || "Erreur STT");
      }
      const text = String(data.text || "").trim();
      await this.onSpeechText(text);
    } catch (error) {
      if (this.recognition) {
        this.setStatus("STT serveur indisponible. Fallback navigateur.");
        await this.startListeningFallbackRecognition();
        return;
      }
      this.setStatus("Erreur STT: " + (error.message || "inconnue"));
    }
  },

  async onSpeechText(text) {
    const cleanedRaw = this.cleanRecognizedText(text || "");
    const wakeParsed = this.extractWakeCommand(cleanedRaw);
    const heardText = wakeParsed.command || text || "";
    this.elements.heard.textContent = heardText || "(aucun texte)";
    this.state.lastHeard = heardText;
    this.saveState();
    if (!heardText) {
      this.setStatus("Aucun texte reconnu.");
      return;
    }
    if (wakeParsed.onlyWakeWord) {
      this.respond("Oui, je t ecoute.", true);
      return;
    }
    try {
      await this.handlePrompt(heardText);
    } catch (_e) {
      this.setStatus("Erreur traitement.");
      this.respond("Je n ai pas pu traiter cette demande. Reessaie avec une phrase plus precise.", true);
    }
  },

  async handleTypedPrompt() {
    const text = this.cleanRecognizedText((this.elements.textInput.value || "").trim());
    if (!text) return;
    const wakeParsed = this.extractWakeCommand(text);
    const prompt = wakeParsed.command || text;
    this.state.open = true;
    this.elements.panel.classList.remove("hidden");
    this.elements.heard.textContent = prompt;
    this.state.lastHeard = prompt;
    this.saveState();
    this.elements.textInput.value = "";
    if (wakeParsed.onlyWakeWord) {
      this.respond("Oui, je t ecoute.", true);
      return;
    }
    await this.handlePrompt(prompt);
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
      // During a multi-turn CRUD flow, only explicit page navigation can interrupt.
      if (this.isExplicitPageNavigation(text)) {
        this.clearPendingIntent();
        const navHandled = this.executeNavigationOnly(text);
        if (navHandled) return;
      }

      try {
        const handledPending = await this.handlePendingIntent(text);
        if (handledPending) return;
      } catch (error) {
        this.setStatus("Erreur action en attente.");
        this.respond("Je n ai pas pu terminer l action: " + (error.message || "erreur inconnue") + ". Tu peux reessayer ou dire annule.", true);
        return;
      }
    }

    // Direct profile navigation should never fall back to generic Gemini chat.
    if (this.tryHandleDirectProfileNavigation(text)) {
      return;
    }

    // Outside pending flow, navigation can be handled directly.
    if (this.executeNavigationOnly(text)) {
      return;
    }

    if (await this.handleCreateOfferBootstrap(text)) {
      return;
    }

    if (await this.handleAdminOfferSearchIntent(text)) {
      return;
    }

    const isAdminRequest = this.looksLikeAdminIntent(text);

    // 2) Gemini-driven admin intent resolution (priority)
    if (isAdminRequest) {
      const aiHandled = await this.askGeminiAction(text);
      if (aiHandled) return;
    }

    if (isAdminRequest) {
      this.respond("Je n ai pas pu interpreter la commande via Gemini. Reformule avec plus de precision.", true);
      return;
    }

    // 3) Gemini direct for non-admin requests
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

    if (this.tryHandleDirectProfileNavigation(text)) {
      this.clearPendingIntent();
      return true;
    }

    // Highest priority: explicit page navigation must always interrupt pending flow.
    if (this.isExplicitPageNavigation(text)) {
      this.clearPendingIntent();
      const navHandled = this.executeNavigationOnly(text);
      if (navHandled) return true;
    }

    // Allow immediate pivot to navigation without being blocked in a multi-turn form.
    if (this.isNavigationCommand(text)) {
      this.clearPendingIntent();
      const navHandled = this.executeNavigationOnly(text);
      if (navHandled) return true;
    }

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

    if (pending.type === "create_offer_collect" || pending.type === "create_offer_title") {
      const basePayload = pending.payload && typeof pending.payload === "object" ? pending.payload : {};
      const merged = this.mergeOfferPayload(basePayload, text);
      const step = String(pending.step || "").trim() || (this.isMeaningfulOfferTitle(merged.titre || "") ? "price" : "title");

      if (step === "price") {
        // In price step, never overwrite title from spoken number like "5 dinars".
        const rawPrice = this.extractNumberByKeyword(text, ["prix", "dt", "dinar", "dinars"]) || text;
        const parsedPrice = String(rawPrice).replace(",", ".").match(/[0-9]+(?:\.[0-9]+)?/)?.[0] || "";
        if (this.hasValidPositiveNumber(parsedPrice)) {
          merged.prix = parsedPrice;
        }

        if (this.looksLikePriceOnly(merged.titre || "")) {
          merged.titre = "";
        }

        if (!this.isMeaningfulOfferTitle(merged.titre || "")) {
          this.setPendingIntent(
            { type: "create_offer_collect", payload: merged, step: "title" },
            "Donne le titre de l offre."
          );
          return true;
        }

        const missingFields = this.getMissingCreateOfferFields(merged);
        if (missingFields.length > 0) {
          this.setPendingIntent(
            { type: "create_offer_collect", payload: merged, step: "required_fields" },
            this.buildCreateOfferFieldsQuestion(merged.titre, missingFields)
          );
          return true;
        }

        const created = await this.callAdminApi(this.buildCreateOfferPayload(merged));
        this.clearPendingIntent();
        this.respond(`Offre creee: ${created.titre}.`, true);
        return true;
      }

      if (this.looksLikePriceOnly(merged.titre || "")) {
        merged.titre = "";
      }

      if (step === "title" && this.isMeaningfulOfferTitle(basePayload.titre || "") && this.looksLikePriceOnly(answer)) {
        const inferredPrice = String(answer).replace(",", ".").match(/[0-9]+(?:\.[0-9]+)?/)?.[0] || "";
        merged.titre = this.sanitizeOfferTitle(basePayload.titre || "");
        if (this.hasValidPositiveNumber(inferredPrice)) {
          merged.prix = inferredPrice;
        }
      }

      const titleCandidate = this.sanitizeOfferTitle(merged.titre || answer);
      if (this.isMeaningfulOfferTitle(titleCandidate) && !this.looksLikePriceOnly(titleCandidate)) {
        merged.titre = titleCandidate;
      }

      if (!this.isMeaningfulOfferTitle(merged.titre || "")) {
        this.setPendingIntent(
          { type: "create_offer_collect", payload: merged, step: "title" },
          "Donne le titre de l offre."
        );
        return true;
      }

      if (step === "required_fields") {
        const missingFields = this.getMissingCreateOfferFields(merged);
        if (missingFields.length > 0) {
          this.setPendingIntent(
            { type: "create_offer_collect", payload: merged, step: "required_fields" },
            this.buildCreateOfferFieldsQuestion(merged.titre, missingFields)
          );
          return true;
        }
      }

      const created = await this.callAdminApi(this.buildCreateOfferPayload(merged));
      this.clearPendingIntent();
      this.respond(`Offre creee: ${created.titre}.`, true);
      return true;
    }

    if (pending.type === "create_category_name") {
      const name = this.cleanEntityName(answer);
      if (!name || name.length < 2) {
        this.respond("Je n ai pas compris le nom. Redonne le nom de la categorie.", true);
        return true;
      }
      const basePayload = pending.payload && typeof pending.payload === "object" ? pending.payload : {};
      const created = await this.callAdminApi({
        action: "create_category",
        ...basePayload,
        nom_categorie: name,
      });
      this.clearPendingIntent();
      this.respond(`Categorie creee: ${created.nom_categorie}.`, true);
      return true;
    }

    if (pending.type === "delete_category") {
      const categoryName =
        this.cleanEntityName(this.extractCategoryName(text) || this.extractDeleteCategoryTarget(text) || answer);
      await this.callAdminApi({ action: "delete_category", nom_categorie: categoryName });
      this.clearPendingIntent();
      this.respond(`Categorie supprimee: ${categoryName}.`, true);
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

    if (pending.type === "update_category_source") {
      const sourceName = this.cleanEntityName(this.extractCategoryName(text) || answer);
      if (!sourceName) {
        this.respond("Je n ai pas compris le nom de categorie. Redonne le nom exact.", true);
        return true;
      }
      this.setPendingIntent(
        { type: "update_category_fields", sourceName },
        `D accord. Pour la categorie ${sourceName}, tu veux modifier quoi ?`
      );
      return true;
    }

    if (pending.type === "update_category_fields") {
      const sourceName = this.cleanEntityName(pending.sourceName || "");
      if (!sourceName) {
        this.clearPendingIntent();
        this.respond("La demande a expire. Redonne la commande de modification de categorie.", true);
        return true;
      }

      const textRaw = String(text || "").trim();
      const newTitle =
        this.cleanEntityName(this.extractLabeledValue(textRaw, ["titre", "nom", "nouveau", "new"])) ||
        "";
      const description = this.extractCategoryDescription(textRaw);
      const icone = this.cleanFieldText(this.extractLabeledValue(textRaw, ["icone", "icon"]));

      const payload = {
        action: "update_category",
        nom_categorie_source: sourceName,
      };

      let hasField = false;
      if (newTitle) {
        payload.nom_categorie = newTitle;
        hasField = true;
      }
      if (description) {
        payload.description = description;
        hasField = true;
      }
      if (icone) {
        payload.icone = icone;
        hasField = true;
      }

      if (!hasField) {
        const intendedField = this.detectCategoryFieldIntent(textRaw);
        if (intendedField) {
          this.setPendingIntent(
            { type: "update_category_single_field", sourceName, field: intendedField },
            this.buildCategoryFieldQuestion(sourceName, intendedField)
          );
          return true;
        }
        this.respond("Je n ai pas compris la nouvelle valeur. Redonne la modification a faire.", true);
        return true;
      }

      await this.callAdminApi(payload);
      this.clearPendingIntent();
      this.respond(`Categorie modifiee: ${sourceName}.`, true);
      return true;
    }

    if (pending.type === "update_category_single_field") {
      const sourceName = this.cleanEntityName(pending.sourceName || "");
      const field = String(pending.field || "").trim();
      if (!sourceName || !field) {
        this.clearPendingIntent();
        this.respond("La demande a expire. Redonne la modification de categorie.", true);
        return true;
      }

      const value = String(text || "").trim();
      const payload = {
        action: "update_category",
        nom_categorie_source: sourceName,
      };

      if (field === "description") {
        const v = this.cleanFieldText(value);
        if (!v) {
          this.respond("Quelle est la nouvelle description ?", true);
          return true;
        }
        payload.description = v;
      } else if (field === "icone") {
        const v = this.cleanFieldText(value);
        if (!v) {
          this.respond("Quelle est la nouvelle icone ?", true);
          return true;
        }
        payload.icone = v;
      } else {
        const v = this.cleanEntityName(value);
        if (!v) {
          this.respond("Quel est le nouveau titre de la categorie ?", true);
          return true;
        }
        payload.nom_categorie = v;
      }

      await this.callAdminApi(payload);
      this.clearPendingIntent();
      this.respond(`Categorie modifiee: ${sourceName}.`, true);
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
        const intendedField = this.detectOfferFieldIntent(text);
        if (intendedField) {
          this.setPendingIntent(
            { type: "update_offer_single_field", sourceTitle: pending.sourceTitle, field: intendedField },
            this.buildOfferFieldQuestion(pending.sourceTitle, intendedField)
          );
          return true;
        }
        this.respond("Je n ai pas compris la nouvelle valeur. Redonne la modification a faire.", true);
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

    if (pending.type === "update_offer_single_field") {
      const sourceTitle = this.cleanEntityName(pending.sourceTitle || "");
      const field = String(pending.field || "").trim();
      if (!sourceTitle || !field) {
        this.clearPendingIntent();
        this.respond("La demande a expire. Redonne la modification de l offre.", true);
        return true;
      }

      const raw = String(text || "").trim();
      const payload = {
        action: "update_offer",
        titre_source: sourceTitle,
      };

      if (field === "prix" || field === "prix_original") {
        const n = this.extractNumberByKeyword(raw, ["prix", "valeur", "montant"]) || raw;
        const v = String(n).replace(",", ".").match(/[0-9]+(?:\.[0-9]+)?/)?.[0] || "";
        if (!v) {
          this.respond(field === "prix" ? "Quel est le nouveau prix ?" : "Quel est le nouveau prix original ?", true);
          return true;
        }
        payload[field] = v;
      } else if (field === "quantite") {
        const v = this.extractIntegerByKeyword(raw, ["quantite", "qte", "stock"]) || raw.match(/[0-9]+/)?.[0] || "";
        if (!v) {
          this.respond("Quelle est la nouvelle quantite ?", true);
          return true;
        }
        payload.quantite = v;
      } else if (field === "nom_categorie") {
        const v = this.cleanEntityName(raw);
        if (!v) {
          this.respond("Quelle est la nouvelle categorie ?", true);
          return true;
        }
        payload.nom_categorie = v;
      } else if (field === "statut") {
        const v = this.extractOfferStatus(raw);
        if (!v) {
          this.respond("Quel est le nouveau statut ?", true);
          return true;
        }
        payload.statut = v;
      } else if (field === "description") {
        const v = this.cleanFieldText(raw);
        if (!v) {
          this.respond("Quelle est la nouvelle description ?", true);
          return true;
        }
        payload.description = v;
      } else {
        const v = this.sanitizeOfferTitle(raw);
        if (!this.isMeaningfulOfferTitle(v)) {
          this.respond("Quel est le nouveau titre de l offre ?", true);
          return true;
        }
        payload.titre = v;
      }

      await this.callAdminApi(payload);
      this.clearPendingIntent();
      this.respond(`Offre modifiee: ${sourceTitle}.`, true);
      return true;
    }

    if (pending.type === "update_offer_source") {
      const sourceTitle = this.cleanEntityName(this.extractOfferTitle(text) || answer);
      if (!sourceTitle) {
        this.respond("Je n ai pas compris le nom de l offre. Redonne le titre exact.", true);
        return true;
      }
      this.setPendingIntent(
        { type: "update_offer_fields", sourceTitle },
        `D accord. Pour l offre ${sourceTitle}, tu veux modifier quoi ?`
      );
      return true;
    }

    if (pending.type === "open_user_profile_target") {
      const target = this.parseIdentityFromText(text) || { type: "name", value: answer.toLowerCase() };
      const user = this.findUserLocal(target);
      if (!user || !user.id) {
        this.respond("Utilisateur introuvable. Redonne nom ou email exact.", true);
        return true;
      }
      this.clearPendingIntent();
      this.respond(`J ouvre la fiche de ${user.name}.`, false);
      setTimeout(() => {
        window.location.href = `user-detail.html?id=${encodeURIComponent(user.id)}`;
      }, 250);
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
    return /(comment tu t|qui es tu|aide|help|ouvre|ouvrir|va |aller|affiche|montre|voir|page|consulter|profil|profile|fiche|combien|supprime|modifier|modifie|ajoute|cree|bloque|debloque|annule|reset|logout|deconnexion|rafraich)/.test(n);
  },

  isNavigationCommand(text) {
    const n = this.normalize(text);
    // Do not treat CRUD intents as navigation.
    if (/(modifie|modifier|update|ajoute|ajouter|cree|creer|supprime|supprimer|efface|retire|delete|bloque|debloque|reactive|reactiver|prix|description|statut|quantite)/.test(n)) {
      return false;
    }
    const hasNavVerb = /(ouvre|ouvrir|va|vas|allez|aller|go|navigue|naviguer|affiche|afficher|montre|montrer|consulte|consulter|voir|retourne|retour)/.test(n);
    const hasPageTarget = /(page|dashboard|accueil|utilisateur|utilisateurs|user|users|partenaire|partenaires|categorie|categories|offre|offres|evenement|evenements|log|logs|activite|profil|profile|fiche)/.test(n);
    return hasNavVerb && hasPageTarget;
  },

  isExplicitPageNavigation(text) {
    const n = this.normalize(text);
    return /(aller|allez|va|vas|ouvre|ouvrir|affiche|afficher|montre|montrer|navigue|naviguer)/.test(n) &&
      /(page|dashboard|accueil|utilisateur|utilisateurs|user|users|partenaire|partenaires|categorie|categories|offre|offres|evenement|evenements|log|logs|activite)/.test(n);
  },

  isProfileNavigationCommand(text) {
    const n = this.normalize(text);
    const hasProfileWord = /(profil|profile|fiche)/.test(n);
    const hasVerb = /(voir|consulter|consulte|ouvre|ouvrir|affiche|afficher|montre|montrer|va|aller)/.test(n);
    return hasProfileWord && hasVerb;
  },

  extractProfileTarget(text) {
    const direct = this.parseIdentityFromText(text);
    if (direct) return direct;

    const raw = String(text || "");
    const m = raw.match(/(?:profil|profile|fiche)\s+(?:de|du|d['’])?\s*(.+)$/i);
    if (!m || !m[1]) return null;
    const value = this.cleanEntityName(m[1]);
    if (!value) return null;
    if (value.includes("@")) return { type: "email", value: value.toLowerCase() };
    return { type: "name", value: value.toLowerCase() };
  },

  tryHandleDirectProfileNavigation(text) {
    if (!this.isProfileNavigationCommand(text)) return false;

    const target = this.extractProfileTarget(text);
    if (!target || (target.type === "name" && this.isWeakIdentityTarget(target.value))) {
      this.setPendingIntent({ type: "open_user_profile_target" }, "Quel utilisateur veux-tu consulter ?");
      return true;
    }

    const user = this.findUserLocal(target);
    if (!user || !user.id) {
      this.respond("Utilisateur introuvable. Redonne nom ou email exact.", true);
      return true;
    }

    this.state.open = true;
    this.saveState();
    this.respond(`J ouvre la fiche de ${user.name}.`, false);
    setTimeout(() => {
      window.location.href = `user-detail.html?id=${encodeURIComponent(user.id)}`;
    }, 250);
    return true;
  },

  executeNavigationOnly(text) {
    const normalized = this.normalize(text);
    // Never hijack CRUD sentences.
    if (/(modifie|modifier|update|ajoute|ajouter|cree|creer|supprime|supprimer|efface|retire|delete|bloque|debloque|reactive|reactiver|prix|description|statut|quantite)/.test(normalized)) {
      return false;
    }
    const hasNavVerb = /(ouvre|ouvrir|va|vas|allez|aller|go|navigue|naviguer|affiche|afficher|montre|montrer|consulte|consulter|retourne|retour)/.test(normalized);
    const hasPageHint = /(page|section|onglet|dashboard|accueil|utilisateur|utilisateurs|user|users|partenaire|partenaires|categorie|categories|offre|offres|evenement|evenements|log|logs|activite|profil)/.test(normalized);
    if (!hasNavVerb || !hasPageHint) return false;

    const navCommands = [
      { words: ["utilisateur", "utilisateurs", "user", "users"], url: "users.html", message: "J ouvre la page Utilisateurs." },
      { words: ["partenaire", "partenaires"], url: "partners.html", message: "J ouvre la page Partenaires." },
      { words: ["categorie", "categories"], url: "categorie.php", message: "J ouvre la page Categories." },
      { words: ["offre", "offres"], url: "offers.php", message: "J ouvre la page Offres." },
      { words: ["evenement", "evenements", "event", "events"], url: "events.html", message: "J ouvre la page Evenements." },
      { words: ["log", "logs", "activite"], url: "logs.html", message: "J ouvre la page Logs." },
      { words: ["dashboard", "vue globale", "accueil"], url: "dashboard.html", message: "Retour au dashboard." },
    ];

    for (const cmd of navCommands) {
      if (cmd.words.some((word) => normalized.includes(word))) {
        this.state.open = true;
        this.saveState();
        this.respond(cmd.message, false);
        setTimeout(() => {
          window.location.href = cmd.url;
        }, 250);
        return true;
      }
    }

    return false;
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

    const wantsOpen = /(ouvre|ouvrir|va|vas|allez|aller|affiche|afficher|montre|montrer|navigue|naviguer)/.test(normalized);
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
        this.setPendingIntent({ type: "create_category_name", payload: {} }, 'Quel nom pour la nouvelle categorie ?');
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
      const hasTitle = this.isMeaningfulOfferTitle(payload.titre || "");
      const missingFields = this.getMissingCreateOfferFields(payload);
      if (!hasTitle || missingFields.length > 0) {
        this.setPendingIntent(
          { type: "create_offer_collect", payload },
          !hasTitle
            ? "D accord. Quel titre pour la nouvelle offre ?"
            : this.buildCreateOfferFieldsQuestion(this.sanitizeOfferTitle(payload.titre), missingFields)
        );
        return true;
      }
      const created = await this.callAdminApi(this.buildCreateOfferPayload(payload));
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

  async handleCreateOfferBootstrap(text) {
    const normalized = this.normalize(text);
    const wantsCreateOffer = /(ajoute|ajouter|cree|creer|cr[eé]e|nouvelle|nouveau)/.test(normalized) && /\boffre(s)?\b/.test(normalized);
    if (!wantsCreateOffer) return false;

    const payload = this.extractOfferPayload(text);
    const hasTitle = this.isMeaningfulOfferTitle(payload.titre || "");

    if (!hasTitle) {
      this.setPendingIntent(
        { type: "create_offer_collect", payload: {}, step: "required_fields" },
        "D accord. Donne les informations necessaires pour creer l offre."
      );
      return true;
    }

    const missingFields = this.getMissingCreateOfferFields(payload);
    if (missingFields.length > 0) {
      this.setPendingIntent(
        { type: "create_offer_collect", payload, step: "required_fields" },
        this.buildCreateOfferFieldsQuestion(this.sanitizeOfferTitle(payload.titre), missingFields)
      );
      return true;
    }

    const created = await this.callAdminApi(this.buildCreateOfferPayload(payload));
    this.respond(`Offre creee: ${created.titre}.`, true);
    return true;
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
            "Tu es l assistant vocal admin CareMeal.",
            "Reponds en francais, court et clair.",
            "Si on te pose une question personnelle (ex: age, identite, ce que tu fais), reponds factuellement sans inventer.",
            "Ne fabrique jamais d informations sur des donnees que tu n as pas.",
            "Si la demande n est pas assez precise, demande UNE precision manquante, en une phrase courte.",
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
        throw error;
      }

      const parsed = this.parseGeminiActionReply((data.reply || "").trim());
      if (!parsed || !parsed.action) {
        return false;
      }

      return await this.executeGeminiAction(parsed, text);
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
      "delete_user, block_user, unblock_user, open_user_profile, get_counts, create_category, update_category, delete_category, create_offer, update_offer, delete_offer, unknown",
      "Regles:",
      "- Si info manquante, mets des champs vides et choisis quand meme la meilleure action.",
      "- Ne fabrique jamais d ids.",
      "- 'etablissement' est un synonyme de partenaire.",
      "- 'consulter/voir/ouvrir profil utilisateur X' => action='open_user_profile' avec target_name ou target_email.",
      "- Si la demande contient seulement un nom (ex: 'glucides'), renvoie action='unknown' et mets ce nom dans target_name.",
      "- Pour update_offer: source_title = offre existante a modifier. new_name = nouveau titre seulement si renommage explicite.",
      "- Pour update_category: si l utilisateur dit juste 'modifier categorie', mets action='update_category' et laisse source_name/new_name/description/icone vides.",
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

  async executeGeminiAction(cmd, originalText = "") {
    const action = String(cmd.action || "").trim().toLowerCase();
    if (!action || action === "unknown") return false;

    const targetName = this.cleanEntityName(cmd.target_name || "");
    const targetEmail = String(cmd.target_email || "").trim().toLowerCase();

    if (action === "open_user_profile") {
      const target =
        targetEmail ? { type: "email", value: targetEmail } :
          (targetName ? { type: "name", value: targetName.toLowerCase() } : null);

      if (!target) {
        this.setPendingIntent({ type: "open_user_profile_target" }, "Quel utilisateur veux-tu consulter ?");
        return true;
      }

      const user = this.findUserLocal(target);
      if (!user || !user.id) {
        this.respond("Utilisateur introuvable.", true);
        return true;
      }

      this.respond(`J ouvre la fiche de ${user.name}.`, false);
      setTimeout(() => {
        window.location.href = `user-detail.html?id=${encodeURIComponent(user.id)}`;
      }, 250);
      return true;
    }

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
      const name = this.cleanEntityName(
        cmd.new_name ||
        cmd.source_name ||
        cmd.title ||
        cmd.target_name ||
        cmd.categorie ||
        this.extractCategoryName(originalText) ||
        ""
      );
      if (!name) {
        this.setPendingIntent(
          {
            type: "create_category_name",
            payload: {
              description: String(cmd.description || "").trim(),
              icone: this.cleanFieldText(cmd.icone || ""),
            },
          },
          "Quel nom pour la categorie ?"
        );
        return true;
      }
      const created = await this.callAdminApi({
        action: "create_category",
        nom_categorie: name,
        description: this.cleanFieldText(cmd.description || ""),
        icone: this.cleanFieldText(cmd.icone || ""),
      });
      this.respond(`Categorie creee: ${created.nom_categorie}.`, true);
      return true;
    }

    if (action === "update_category") {
      const source = this.cleanEntityName(
        cmd.source_name ||
        cmd.target_name ||
        cmd.title ||
        cmd.categorie ||
        this.extractCategorySourceFromUpdate(originalText) ||
        this.extractCategoryName(originalText) ||
        ""
      );
      const target = this.cleanEntityName(cmd.new_name || cmd.title || "");
      const description = this.cleanFieldText(cmd.description || "");
      const icone = this.cleanFieldText(cmd.icone || "");
      if (!source) {
        this.setPendingIntent({ type: "update_category_source" }, "Quelle categorie veux-tu modifier ?");
        return true;
      }
      if (!target && !description && !icone) {
        const intendedField = this.detectCategoryFieldIntent(originalText);
        if (intendedField) {
          this.setPendingIntent(
            { type: "update_category_single_field", sourceName: source, field: intendedField },
            this.buildCategoryFieldQuestion(source, intendedField)
          );
          return true;
        }
        this.setPendingIntent({ type: "update_category_fields", sourceName: source }, `Que veux-tu modifier pour la categorie ${source} ?`);
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
      const name = this.cleanEntityName(
        cmd.source_name ||
        cmd.target_name ||
        cmd.new_name ||
        this.extractCategoryName(originalText) ||
        this.extractDeleteCategoryTarget(originalText) ||
        ""
      );
      if (!name) {
        this.setPendingIntent({ type: "delete_category" }, "Quelle categorie veux-tu supprimer ?");
        return true;
      }
      await this.callAdminApi({ action: "delete_category", nom_categorie: name });
      this.respond(`Categorie supprimee: ${name}.`, true);
      return true;
    }

    if (action === "create_offer") {
      const rawTitle = this.sanitizeOfferTitle(
        cmd.title ||
        cmd.new_name ||
        cmd.source_title ||
        cmd.source_name ||
        this.inferOfferTitle(originalText) ||
        this.extractOfferTitle(originalText) ||
        ""
      );
      const title = this.isMeaningfulOfferTitle(rawTitle) ? rawTitle : "";
      const payload = {
        titre: title,
        description: String(cmd.description || "").trim(),
        prix: String(cmd.prix || "").trim(),
        prix_original: String(cmd.prix_original || "").trim(),
        quantite: String(cmd.quantite || "").trim(),
        nom_categorie: this.cleanEntityName(cmd.categorie || ""),
        statut: String(cmd.statut || "").trim(),
      };
      if (!this.isMeaningfulOfferTitle(payload.titre)) {
        const pendingPayload = { ...payload };
        delete pendingPayload.titre;
        this.setPendingIntent({ type: "create_offer_collect", payload: pendingPayload, step: "title" }, "Quel titre pour la nouvelle offre ?");
        return true;
      }
      const missingFields = this.getMissingCreateOfferFields(payload);
      if (missingFields.length > 0) {
        this.setPendingIntent(
          { type: "create_offer_collect", payload, step: "required_fields" },
          this.buildCreateOfferFieldsQuestion(this.sanitizeOfferTitle(payload.titre), missingFields)
        );
        return true;
      }
      const created = await this.callAdminApi(this.buildCreateOfferPayload(payload));
      this.respond(`Offre creee: ${created.titre}.`, true);
      return true;
    }

    if (action === "update_offer") {
      const source = this.cleanEntityName(
        cmd.source_title ||
        cmd.source_name ||
        cmd.target_name ||
        cmd.title ||
        this.extractOfferSourceFromUpdate(originalText) ||
        this.extractOfferTitle(originalText) ||
        ""
      );
      if (!source) {
        this.setPendingIntent({ type: "update_offer_source" }, "Quelle offre veux-tu modifier ?");
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
        const intendedField = this.detectOfferFieldIntent(originalText);
        if (intendedField) {
          this.setPendingIntent(
            { type: "update_offer_single_field", sourceTitle: source, field: intendedField },
            this.buildOfferFieldQuestion(source, intendedField)
          );
          return true;
        }
        this.setPendingIntent({ type: "update_offer_fields", sourceTitle: source }, `Que veux-tu modifier pour l offre ${source} ?`);
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

  extractWakeCommand(text) {
    const raw = String(text || "").trim();
    if (!raw) return { command: "", onlyWakeWord: false };
    const normalized = this.normalize(raw);
    const wakePatterns = ["hey caremeal", "hey car meal", "salut caremeal", "ok caremeal", "ok car meal"];
    const hasWake = wakePatterns.some((p) => normalized.includes(p));
    if (!hasWake) return { command: raw, onlyWakeWord: false };

    const stripped = raw
      .replace(/\b(hey|ok|salut)\s+care\s*meal\b/gi, " ")
      .replace(/\bcare\s*meal\b/gi, " ")
      .replace(/^[\s,;:.!?-]+/, "")
      .trim();

    return {
      command: stripped,
      onlyWakeWord: stripped === "",
    };
  },

  isAdminOffersPage() {
    return /\/admin\/offers\.php$/i.test(window.location.pathname);
  },

  resumePendingOfferSearch() {
    if (!this.isAdminOffersPage()) return;
    const pending = String(this.state.pendingOfferSearchQuery || "").trim();
    if (!pending) return;
    this.state.pendingOfferSearchQuery = "";
    this.saveState();
    setTimeout(() => {
      this.handleAdminOfferSearchIntent(pending);
    }, 350);
  },

  isOfferSearchFilterIntent(text) {
    const n = this.normalize(text);
    const hasCrudVerb = /(ajoute|ajouter|cree|creer|cr[eé]e|supprime|supprimer|modifie|modifier|delete|update)/.test(n);
    if (hasCrudVerb) return false;

    const hasVerb = /(cherche|chercher|recherche|filtre|filtrer|affiche|afficher|montre|montrer|liste|lister|voir)/.test(n);
    const hasTarget = /\boffre(s)?\b/.test(n);
    const hasCriteria = /(categorie|category|prix|moins de|plus de|inferieur|superieur|statut|brouillon|publie|expire|archive|stock)/.test(n);
    if ((hasVerb && hasTarget) || (hasTarget && hasCriteria)) return true;

    if (this.isAdminOffersPage()) {
      const words = n.trim().split(/\s+/).filter(Boolean);
      const hasCrudVerb = /(supprime|supprimer|modifie|modifier|ajoute|ajouter|cree|creer|delete|update)/.test(n);
      if (!hasCrudVerb && words.length >= 1 && words.length <= 3) {
        return true;
      }
    }
    return false;
  },

  parseAdminOfferSearchRequest(text) {
    const raw = String(text || "").trim();
    const normalized = this.normalize(raw);
    const extractedTitle = this.sanitizeOfferTitle(this.extractOfferTitle(raw));

    const categoryMatch = normalized.match(/(?:categorie|category)\s+([a-z0-9À-ÿ_\-\s]+)/i);
    const category = categoryMatch ? this.cleanEntityName(categoryMatch[1]) : "";

    const maxMatch = normalized.match(/(?:moins de|inferieur a|max(?:imum)?|<=?)\s*([0-9]+(?:[.,][0-9]+)?)/i);
    const minMatch = normalized.match(/(?:plus de|superieur a|min(?:imum)?|>=?)\s*([0-9]+(?:[.,][0-9]+)?)/i);
    const maxPrice = maxMatch ? String(maxMatch[1]).replace(",", ".") : "";
    const minPrice = minMatch ? String(minMatch[1]).replace(",", ".") : "";

    const status = this.extractOfferStatus(raw);

    let keyword = raw
      .replace(/trouve[- ]?moi|trouve moi|trouver|trouve|cherche[- ]?moi|cherche moi|cherche|chercher|recherche|filtre|filtrer/gi, " ")
      .replace(/affiche[- ]?moi|affiche moi|afficher|affiche|montre[- ]?moi|montre moi|montrer|montre|liste|lister|voir/gi, " ")
      .replace(/\boffres?\b/gi, " ")
      .replace(/\b(nomme|nomme[eé]?|nomm[eé]e?)\b/gi, " ")
      .replace(/(?:moins de|plus de|inferieur a|superieur a|max(?:imum)?|min(?:imum)?|<=?|>=?)\s*[0-9]+(?:[.,][0-9]+)?\s*(dt|dinar|dinars|tnd)?/gi, " ")
      .replace(/(?:categorie|category)\s+[a-z0-9À-ÿ_\-\s]+/gi, " ")
      .replace(/\b(publiee?|brouillon|expiree?|archivee?|stock)\b/gi, " ")
      .replace(/[,:;!?()[\]{}]/g, " ")
      .replace(/\s+/g, " ")
      .trim();

    if (this.isMeaningfulOfferTitle(extractedTitle)) {
      keyword = extractedTitle;
    }

    return {
      keyword: this.cleanFieldText(keyword),
      nom_categorie: category,
      statut: status,
      min_prix: minPrice,
      max_prix: maxPrice,
    };
  },

  async handleAdminOfferSearchIntent(text) {
    if (!this.isOfferSearchFilterIntent(text)) return false;

    if (!this.isAdminOffersPage()) {
      this.state.pendingOfferSearchQuery = String(text || "").trim();
      this.saveState();
      this.respond("J ouvre la page Offres et j applique le filtre.", false);
      setTimeout(() => {
        window.location.href = "offers.php";
      }, 250);
      return true;
    }

    const criteria = this.parseAdminOfferSearchRequest(text);
    this.applyAdminOfferFiltersUi(criteria);

    const payload = {
      action: "search_offers",
      keyword: criteria.keyword || "",
      nom_categorie: criteria.nom_categorie || "",
      statut: criteria.statut || "",
      min_prix: criteria.min_prix || "",
      max_prix: criteria.max_prix || "",
      limit: 20,
    };
    const result = await this.callAdminApi(payload);
    const offers = Array.isArray(result.offers) ? result.offers : [];
    if (offers.length === 0) {
      this.respond("Aucune offre ne correspond a ce filtre.", true);
      return true;
    }

    const top = offers.slice(0, 3).map((o) => {
      const title = String(o.titre || "Offre");
      const price = Number.parseFloat(String(o.prix || "0"));
      if (Number.isFinite(price) && price > 0) {
        return `${title} a ${price.toFixed(2)} DT`;
      }
      return title;
    });

    this.respond(`J ai trouve ${offers.length} offre${offers.length > 1 ? "s" : ""}. ${top.join(", ")}.`, true);
    return true;
  },

  applyAdminOfferFiltersUi(criteria) {
    const searchInput = document.querySelector("#offer-search, input[placeholder*='Rechercher par titre']");
    if (searchInput) {
      searchInput.value = criteria.keyword || "";
      searchInput.dispatchEvent(new Event("input", { bubbles: true }));
      searchInput.dispatchEvent(new Event("change", { bubbles: true }));
    }

    const statusText = this.normalize(criteria.statut || "");
    if (statusText) {
      const statusMap = {
        publiee: ["publie", "publiees", "publi"],
        brouillon: ["brouillon", "brouillons"],
        expiree: ["expire", "expirees", "expir"],
        archivee: ["archive", "archivees", "archiv"],
      };
      const wanted = statusMap[statusText] || [];
      const buttons = Array.from(document.querySelectorAll("button, .filter-btn, .status-btn, .tab-btn"));
      for (const btn of buttons) {
        const label = this.normalize(btn.textContent || "");
        if (wanted.some((w) => label.includes(w))) {
          btn.click();
          break;
        }
      }
    }

    if (typeof window.applyFilters === "function") {
      try {
        window.applyFilters();
      } catch (_e) {}
    }

    this.filterAdminOfferRowsLocally(criteria);
  },

  filterAdminOfferRowsLocally(criteria) {
    const rows = Array.from(document.querySelectorAll("tbody tr"));
    if (rows.length === 0) return;

    const keyword = this.normalize(criteria.keyword || "");
    const cat = this.normalize(criteria.nom_categorie || "");
    const minPrice = criteria.min_prix ? Number.parseFloat(String(criteria.min_prix).replace(",", ".")) : null;
    const maxPrice = criteria.max_prix ? Number.parseFloat(String(criteria.max_prix).replace(",", ".")) : null;
    const status = this.normalize(criteria.statut || "");

    rows.forEach((row) => {
      const txt = this.normalize(row.textContent || "");
      const title = this.normalize(row.querySelector("h4, .offer-title, td:first-child")?.textContent || "");
      const priceMatch = (row.textContent || "").replace(",", ".").match(/([0-9]+(?:\.[0-9]+)?)\s*dt/i);
      const price = priceMatch ? Number.parseFloat(priceMatch[1]) : null;

      let visible = true;
      if (keyword && !(txt.includes(keyword) || title.includes(keyword))) visible = false;
      if (cat && !txt.includes(cat)) visible = false;
      if (status && !txt.includes(status.replace("e", ""))) visible = false;
      if (minPrice !== null && Number.isFinite(minPrice) && price !== null && price < minPrice) visible = false;
      if (maxPrice !== null && Number.isFinite(maxPrice) && price !== null && price > maxPrice) visible = false;
      row.style.display = visible ? "" : "none";
    });
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

  extractDeleteCategoryTarget(text) {
    const m = String(text || "").match(/\b(?:supprime|supprimer|efface|retire|delete)\b\s+(?:la|le|les|l['’])?\s*(?:cat[eé]gorie)?\s*(.+)$/i);
    if (!m || !m[1]) return "";
    return this.cleanEntityName(m[1]);
  },

  looksLikePriceOnly(value) {
    const raw = String(value || "").trim();
    if (!raw) return false;
    const n = this.normalize(raw);
    if (/^[0-9]+(?:[.,][0-9]+)?$/.test(n)) return true;
    if (/^[0-9]+(?:[.,][0-9]+)?\s*(dt|dinar|dinars|tnd)$/.test(n)) return true;
    if (/^(dt|dinar|dinars|tnd)\s*[0-9]+(?:[.,][0-9]+)?$/.test(n)) return true;
    return false;
  },

  extractOfferTitle(text) {
    const quoted = this.extractQuoted(text);
    if (quoted) return this.cleanEntityName(quoted);
    const labeled = this.extractLabeledValue(text, ["titre", "offre", "nom"]);
    if (labeled) return this.cleanEntityName(labeled);

    const m = text.match(/offre\s+(.+)$/i);
    if (m && m[1]) return this.sanitizeOfferTitle(this.cleanEntityName(m[1]));
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
    const normalized = this.normalizeSpokenSymbols(value || "");
    return normalized
      .trim()
      .replace(/^[\s,:;=.!?'"`«»()\-]+/, "")
      .replace(/[\s,:;=.!?'"`«»()\-]+$/, "")
      .replace(/^(cat[eé]gorie|categorie|category|offre|offer)\s+/i, "")
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
      quantite: this.extractLabeledValue(text, ["quantite", "quantité", "qte", "stock"]) || this.extractIntegerByKeyword(text, ["quantite", "quantité", "qte", "stock"]),
      nom_categorie:
        this.extractLabeledValue(text, ["categorie", "category"]) ||
        this.extractCategoryFromNaturalText(text),
      heure_debut: this.extractLabeledValue(text, ["heure_debut", "debut"]),
      heure_fin: this.extractLabeledValue(text, ["heure_fin", "fin"]),
      photo_url: this.extractLabeledValue(text, ["photo", "image", "photo_url"]),
      statut: this.extractLabeledValue(text, ["statut"]),
    };
  },

  buildCreateOfferPayload(rawPayload = {}) {
    const payload = {
      action: "create_offer",
      titre: this.sanitizeOfferTitle(String(rawPayload.titre || "")),
      description: String(rawPayload.description || "").trim(),
      prix: String(rawPayload.prix || "").trim(),
      prix_original: String(rawPayload.prix_original || "").trim(),
      quantite: String(rawPayload.quantite || "").trim(),
      nom_categorie: this.cleanEntityName(String(rawPayload.nom_categorie || "")),
      statut: String(rawPayload.statut || "").trim() || "publiee",
      heure_debut: String(rawPayload.heure_debut || "").trim(),
      heure_fin: String(rawPayload.heure_fin || "").trim(),
      photo_url: String(rawPayload.photo_url || "").trim(),
    };

    // Defaults: title-only creation must always work.
    const priceNum = parseFloat((payload.prix || "").replace(",", "."));
    const safePrice = Number.isFinite(priceNum) && priceNum > 0 ? priceNum : 1;
    if (!payload.prix) {
      payload.prix = safePrice.toFixed(2);
    }

    const originalNum = parseFloat((payload.prix_original || "").replace(",", "."));
    const computedOriginal = Math.max(safePrice + 1, safePrice * 1.2);
    if (!payload.prix_original || !Number.isFinite(originalNum) || originalNum <= safePrice) {
      payload.prix_original = computedOriginal.toFixed(2);
    }

    const qtyNum = parseInt(payload.quantite, 10);
    if (!Number.isFinite(qtyNum) || qtyNum < 1) {
      payload.quantite = "1";
    }

    if (!payload.heure_debut) delete payload.heure_debut;
    if (!payload.heure_fin) delete payload.heure_fin;
    if (!payload.photo_url) delete payload.photo_url;

    return payload;
  },

  getMissingCreateOfferFields(payload = {}) {
    // Back-end can safely fill defaults (prix/quantite/categorie).
    // Keep collection flexible: user can provide any subset of fields.
    return [];
  },

  buildCreateOfferFieldsQuestion(title, missingFields = []) {
    const safeTitle = this.sanitizeOfferTitle(title || "");
    if (!safeTitle) return "Donne les informations necessaires pour creer l offre.";
    return `Donne les informations necessaires pour creer l offre ${safeTitle}.`;
  },

  mergeOfferPayload(basePayload = {}, text = "") {
    const merged = {
      ...(basePayload && typeof basePayload === "object" ? basePayload : {}),
    };
    const extracted = this.extractOfferPayload(String(text || ""));
    const rawPriceFallback = this.extractNumberByKeyword(String(text || ""), ["prix", "dt", "dinar"]) || String(text || "");
    const fallbackNumber = String(rawPriceFallback).replace(",", ".").match(/[0-9]+(?:\.[0-9]+)?/)?.[0] || "";

    for (const [k, v] of Object.entries(extracted)) {
      if (typeof v === "string" && v.trim() !== "") {
        if (k === "titre" && this.looksLikePriceOnly(v)) {
          continue;
        }
        merged[k] = v.trim();
      }
    }

    if (!this.hasValidPositiveNumber(merged.prix || "") && fallbackNumber) {
      merged.prix = fallbackNumber;
    }

    return merged;
  },

  hasValidPositiveNumber(value) {
    const n = parseFloat(String(value || "").replace(",", "."));
    return Number.isFinite(n) && n > 0;
  },

  inferOfferTitle(text) {
    const quoted = this.extractQuoted(text);
    if (quoted) {
      const directTitle = this.sanitizeOfferTitle(quoted);
      return this.isMeaningfulOfferTitle(directTitle) ? directTitle : "";
    }

    const cleaned = (text || "")
      .replace(/\b(je veux|j veux|je voudrais|j voudrais|je souhaite|j souhaite)\b/gi, " ")
      .replace(/\b(ajoute|ajouter|cree|creer|cr[eé]e|cr[eé]er|nouvelle|nouveau)\b/gi, " ")
      .replace(/\b(une|un|la|le|les|des|du|de)\b/gi, " ")
      .replace(/\boffre(s)?\b/gi, " ")
      .replace(/\b(nomme|nomme[e]?|nomm[eé]e?|appele|appel[eé]e|intitule|intitul[eé]e)\b/gi, " ")
      .replace(/\b(avec|prix|prix_original|prix original|quantite|categorie|category|description|desc)\b[\s:=].*$/i, " ")
      .replace(/[;,]/g, " ")
      .trim();

    const title = this.sanitizeOfferTitle(cleaned);
    return this.isMeaningfulOfferTitle(title) ? title : "";
  },

  sanitizeOfferTitle(value) {
    const normalized = this.normalizeSpokenSymbols(value || "");
    return normalized
      .trim()
      .replace(/^(nomme|nomme[eé]?|nomm[eé]e?)\s+/i, "")
      .replace(/^(offre)\s+/i, "")
      .replace(/^[\s,;:.!?=+\-_/\\]+/, "")
      .replace(/[\s,;:.!?=+\-_/\\]+$/, "")
      .replace(/\s+/g, " ")
      .trim();
  },

  isMeaningfulOfferTitle(value) {
    const title = this.sanitizeOfferTitle(value);
    if (!title || title.length < 3) return false;
    if (this.looksLikePriceOnly(title)) return false;

    const normalized = this.normalize(title);
    if (/^(je|j)$/.test(normalized)) return false;
    if (/^(je|j)\s+(veux|voudrais|souhaite)$/.test(normalized)) return false;
    if (/^(tu|vous|on)\s+(veux|veut|voulez|voudrais|voudriez|souhaite|souhaites|pouvez|peux)$/.test(normalized)) return false;
    if (/^(ajoute|ajouter|cree|creer|offre|nouveau|nouvelle)$/.test(normalized)) return false;
    if (/^(une|un)\s+offre$/.test(normalized)) return false;
    if (/^(titre|nom|nomme|nommee)$/.test(normalized)) return false;
    if (/^(quel|quelle|quels|quelles)\s+(prix|nom|titre)$/.test(normalized)) return false;

    const weakTokens = new Set([
      "je", "j", "tu", "vous", "on", "veux", "veut", "voulez", "voudrais", "voudriez", "souhaite", "souhaites",
      "peux", "pouvez", "ajoute", "ajouter", "cree", "creer", "offre", "un", "une", "le", "la", "les",
      "de", "des", "du", "stp", "svp", "merci", "quel", "quelle", "quels", "quelles", "prix", "dt", "dinar", "dinars", "tnd"
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

  detectOfferFieldIntent(text) {
    const n = this.normalize(text || "");
    if (!n) return "";
    if (/\bprix original\b|\bprix_original\b|\boriginal\b/.test(n)) return "prix_original";
    if (/\bprix\b/.test(n)) return "prix";
    if (/\bquantite\b|\bqte\b|\bstock\b/.test(n)) return "quantite";
    if (/\bcategorie\b|\bcategory\b/.test(n)) return "nom_categorie";
    if (/\bstatut\b|\bstatus\b/.test(n)) return "statut";
    if (/\bdescription\b|\bdesc\b/.test(n)) return "description";
    if (/\btitre\b|\bnom\b|\brenomme\b|\brenommer\b/.test(n)) return "titre";
    return "";
  },

  detectCategoryFieldIntent(text) {
    const n = this.normalize(text || "");
    if (!n) return "";
    if (/\bdescription\b|\bdesc\b/.test(n)) return "description";
    if (/\bicone\b|\bicon\b/.test(n)) return "icone";
    if (/\btitre\b|\bnom\b|\brenomme\b|\brenommer\b/.test(n)) return "nom_categorie";
    return "";
  },

  buildOfferFieldQuestion(sourceTitle, field) {
    if (field === "prix") return `Quel est le nouveau prix de l offre ${sourceTitle} ?`;
    if (field === "prix_original") return `Quel est le nouveau prix original de l offre ${sourceTitle} ?`;
    if (field === "quantite") return `Quelle est la nouvelle quantite de l offre ${sourceTitle} ?`;
    if (field === "nom_categorie") return `Quelle est la nouvelle categorie de l offre ${sourceTitle} ?`;
    if (field === "statut") return `Quel est le nouveau statut de l offre ${sourceTitle} ?`;
    if (field === "description") return `Quelle est la nouvelle description de l offre ${sourceTitle} ?`;
    return `Quel est le nouveau titre de l offre ${sourceTitle} ?`;
  },

  buildCategoryFieldQuestion(sourceName, field) {
    if (field === "description") return `Quelle est la nouvelle description de la categorie ${sourceName} ?`;
    if (field === "icone") return `Quelle est la nouvelle icone de la categorie ${sourceName} ?`;
    return `Quel est le nouveau titre de la categorie ${sourceName} ?`;
  },

  cleanFieldText(value) {
    const normalized = this.normalizeSpokenSymbols(value || "");
    return normalized
      .replace(/^[\s:=,;.-]+/, "")
      .replace(/[\s,;.-]+$/, "")
      .trim();
  },

  normalizeSpokenSymbols(value) {
    return String(value || "")
      // Voice STT often outputs "tiret" when user says "-"
      .replace(/\s*\b(tiret|dash|hyphen)\b\s*/gi, "-");
  },

  cleanRecognizedText(value) {
    return String(value || "")
      .replace(/[!¡]/g, "")
      .replace(/\s+/g, " ")
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
    return /(utilisateur|partenaire|etablissement|commerce|offre|categorie|evenement|profil|profile|fiche|consulter|voir|supprime|modifier|modifie|ajoute|cree|bloque|debloque|reactive|reactiver|ban|unban|statut|quantite|prix|description)/.test(n);
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

  buildSttApiUrl() {
    const pathParts = window.location.pathname.split("/").filter(Boolean);
    const base = pathParts.length > 0 ? "/" + pathParts[0] : "";
    return base + "/api/stt.php";
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
