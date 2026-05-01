const AdminVoiceAssistant = {
  recognition: null,
  listening: false,
  isRequestInFlight: false,
  isInitialized: false,
  elements: {},

  init() {
    if (this.isInitialized) return;
    this.isInitialized = true;
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
      .voice-assistant-fab.listening { animation: voicePulse 1.2s infinite; }
      .voice-assistant-panel {
        position: fixed;
        right: 24px;
        bottom: 92px;
        width: min(420px, calc(100vw - 24px));
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
        <input class="voice-assistant-input" type="text" data-role="text-input" placeholder="Ecris une demande admin">
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
    this.elements.closeBtn.addEventListener("click", () => this.elements.panel.classList.add("hidden"));
    this.elements.fab.addEventListener("click", () => {
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
      if (!text) {
        this.setStatus("Aucun texte reconnu.");
        return;
      }
      await this.handlePrompt(text);
    };

    this.recognition.onerror = (event) => {
      const err = event.error || "inconnue";
      this.setStatus("Erreur micro: " + err);
    };

    this.recognition.onend = () => {
      this.listening = false;
      this.elements.fab.classList.remove("listening");
      this.elements.fab.innerHTML = '<i class="fa-solid fa-microphone"></i>';
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
    this.elements.panel.classList.remove("hidden");
    this.elements.heard.textContent = text;
    this.elements.textInput.value = "";
    await this.handlePrompt(text);
  },

  async handlePrompt(text) {
    const dataAction = await this.handleDataCommand(text);
    if (dataAction) return;

    const localAction = this.executeLocalCommand(text);
    if (localAction) return;

    this.setStatus("Analyse IA...");
    await this.askGemini(text);
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
      this.respond("Je peux naviguer, compter, creer, modifier, supprimer offres/categories, et gerer utilisateurs/partenaires.", true);
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
          this.respond(cmd.message, false);
          setTimeout(() => {
            window.location.href = cmd.url;
          }, 350);
          return true;
        }
      }
    }

    if (/(rafraichis|rafraichir|actualise|actualiser|recharge|recharger)/.test(normalized)) {
      this.respond("Je rafraichis la page.", false);
      setTimeout(() => window.location.reload(), 350);
      return true;
    }

    if (/(deconnexion|deconnecte|logout)/.test(normalized)) {
      this.respond("Je lance la deconnexion.", false);
      setTimeout(() => {
        const btn = document.querySelector("[data-action='logout']");
        if (btn) btn.click();
      }, 350);
      return true;
    }

    return false;
  },

  async handleDataCommand(text) {
    const normalized = this.normalize(text);

    // Deterministic local stats for users/partners from App local storage.
    if (/(combien|nombre|total)/.test(normalized) && /(utilisateur|utilisateurs)/.test(normalized)) {
      const stats = this.getUserStatsLocal();
      this.respond(`Il y a ${stats.totalUsers} utilisateurs hors admin.`, true);
      return true;
    }

    if (/(combien|nombre|total)/.test(normalized) && /(partenaire|partenaires)/.test(normalized)) {
      const stats = this.getUserStatsLocal();
      this.respond(`Il y a ${stats.totalPartners} partenaires. Actifs: ${stats.activePartners}, en attente: ${stats.pendingPartners}.`, true);
      return true;
    }

    if (/(liste|voir|montre)/.test(normalized) && /(partenaire|partenaires)/.test(normalized)) {
      const users = this.safeGetUsers().filter((u) => u.role === "partner");
      const names = users.slice(0, 5).map((u) => u.name).join(", ");
      this.respond(users.length > 0 ? `Partenaires (${users.length}): ${names}` : "Aucun partenaire.", true);
      return true;
    }

    if (/(liste|voir|montre)/.test(normalized) && /(utilisateur|utilisateurs)/.test(normalized)) {
      const users = this.safeGetUsers().filter((u) => u.role !== "admin");
      const names = users.slice(0, 5).map((u) => u.name).join(", ");
      this.respond(users.length > 0 ? `Utilisateurs (${users.length}): ${names}` : "Aucun utilisateur.", true);
      return true;
    }

    if (/(bloque|bloquer|ban|bannir)/.test(normalized) && /(utilisateur|partenaire)/.test(normalized)) {
      const target = this.extractTargetIdentity(text);
      if (!target) {
        this.respond("Utilise: bloque utilisateur email=nom@mail.com ou bloque partenaire nom=Nom Etablissement.", true);
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

    if (/(debloque|debloquer|reactive|reactiver|unban)/.test(normalized) && /(utilisateur|partenaire)/.test(normalized)) {
      const target = this.extractTargetIdentity(text);
      if (!target) {
        this.respond("Utilise: debloque utilisateur email=nom@mail.com ou debloque partenaire nom=Nom Etablissement.", true);
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

    const asksCount = /(combien|nombre|total)/.test(normalized);
    const mentionsOffers = /(offre|offres)/.test(normalized);
    const mentionsCategories = /(categorie|categories)/.test(normalized);

    if (asksCount && (mentionsOffers || mentionsCategories)) {
      this.setStatus("Lecture DB reelle...");
      const stats = await this.callAdminApi({ action: "get_counts" });
      if (mentionsOffers && mentionsCategories) {
        this.respond(`Il y a ${stats.offers_count} offres et ${stats.categories_count} categories en base.`, true);
      } else if (mentionsOffers) {
        this.respond(`Il y a ${stats.offers_count} offres en base.`, true);
      } else {
        this.respond(`Il y a ${stats.categories_count} categories en base.`, true);
      }
      return true;
    }

    if (/(ajoute|ajouter|cree|creer|nouvelle|nouveau)/.test(normalized) && mentionsCategories) {
      const name = this.extractCategoryName(text);
      if (!name || name.length < 2) {
        this.respond('Format: ajoute categorie "Desserts" ou ajoute categorie nom=Desserts; description=...; icone=fa-tag', true);
        return true;
      }
      const description = this.extractLabeledValue(text, ["description", "desc"]);
      const icon = this.extractLabeledValue(text, ["icone", "icon"]);
      const created = await this.callAdminApi({
        action: "create_category",
        nom_categorie: name,
        description: description || "",
        icone: icon || "",
      });
      this.respond(`Categorie creee: ${created.nom_categorie}.`, true);
      return true;
    }

    if (/(modifie|modifier|update|renomme|renommer)/.test(normalized) && mentionsCategories) {
      const source = this.extractLabeledValue(text, ["source", "ancien", "old", "nom_source"]) || this.extractCategoryName(text);
      const target = this.extractLabeledValue(text, ["nom", "nouveau", "new"]);
      if (!source || !target) {
        this.respond("Format: modifie categorie source=Snacks; nom=Snacking; description=...; icone=fa-tag", true);
        return true;
      }
      await this.callAdminApi({
        action: "update_category",
        nom_categorie_source: source,
        nom_categorie: target,
        description: this.extractLabeledValue(text, ["description", "desc"]) || "",
        icone: this.extractLabeledValue(text, ["icone", "icon"]) || "",
      });
      this.respond(`Categorie modifiee: ${source} -> ${target}.`, true);
      return true;
    }

    if (/(supprime|supprimer|delete|efface|retire)/.test(normalized) && mentionsCategories) {
      const name = this.extractLabeledValue(text, ["nom", "categorie"]) || this.extractCategoryName(text);
      if (!name) {
        this.respond("Format: supprime categorie nom=Desserts", true);
        return true;
      }
      await this.callAdminApi({
        action: "delete_category",
        nom_categorie: name,
      });
      this.respond(`Categorie supprimee: ${name}.`, true);
      return true;
    }

    if (/(ajoute|ajouter|cree|creer|nouvelle|nouveau)/.test(normalized) && mentionsOffers) {
      const payload = this.extractOfferPayload(text);
      if (!payload.titre || !payload.prix || !payload.prix_original || !payload.quantite || !payload.nom_categorie) {
        this.respond("Format: ajoute offre titre=Salade; prix=8; prix_original=12; quantite=20; categorie=Desserts; description=Fraiche.", true);
        return true;
      }
      const created = await this.callAdminApi({ action: "create_offer", ...payload, statut: "publiee" });
      this.respond(`Offre creee: ${created.titre}.`, true);
      return true;
    }

    if (/(modifie|modifier|update|edite|editer)/.test(normalized) && mentionsOffers) {
      const source = this.extractLabeledValue(text, ["source", "ancien", "old", "titre_source"]);
      if (!source) {
        this.respond("Format: modifie offre source=Salade; titre=Salade XL; prix=9; prix_original=13; quantite=25; categorie=Desserts", true);
        return true;
      }
      const payload = this.extractOfferPayload(text);
      await this.callAdminApi({
        action: "update_offer",
        titre_source: source,
        ...payload,
      });
      this.respond(`Offre modifiee: ${source}.`, true);
      return true;
    }

    if (/(supprime|supprimer|delete|efface|retire)/.test(normalized) && mentionsOffers) {
      const title = this.extractLabeledValue(text, ["titre", "offre", "nom"]);
      if (!title) {
        this.respond("Format: supprime offre titre=Salade", true);
        return true;
      }
      await this.callAdminApi({ action: "delete_offer", titre: title });
      this.respond(`Offre supprimee: ${title}.`, true);
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
            "Reponds en francais, court et concret.",
            "Ne devine jamais des chiffres.",
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

      const reply = (data.reply || "").trim() || "Je n ai pas de reponse.";
      this.respond(reply, true);
    } catch (error) {
      if (error.code === "quota_exceeded") {
        const waitPart = error.retryAfter ? ` Reessaie dans ${error.retryAfter} secondes.` : "";
        const attemptsPart = error.meta?.attempts ? ` Tentatives: ${error.meta.attempts}.` : "";
        this.setStatus("Quota Gemini depasse.");
        this.elements.reply.textContent = "Le quota Gemini est depasse." + waitPart + attemptsPart;
      } else {
        this.setStatus("Erreur assistant.");
        this.elements.reply.textContent = "Erreur: " + (error.message || "inconnue");
      }
    } finally {
      this.isRequestInFlight = false;
    }
  },

  normalize(text) {
    return text.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
  },

  extractCategoryName(text) {
    const quoted = text.match(/["“](.+?)["”]/);
    if (quoted) return quoted[1].trim();
    const named = this.extractLabeledValue(text, ["nom", "categorie", "category"]);
    return named || "";
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

  extractTargetIdentity(text) {
    const email = this.extractLabeledValue(text, ["email", "mail"]);
    if (email) return { type: "email", value: email.toLowerCase() };
    const name = this.extractLabeledValue(text, ["nom", "name", "utilisateur", "partenaire"]);
    if (name) return { type: "name", value: name.toLowerCase() };
    return null;
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
    if (!response.ok) throw new Error(data.error || "Erreur API admin");
    return data;
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

// Auto-init when script is loaded on admin pages.
(function bootstrapAdminVoice() {
  const isAdminPage = /\/admin\//.test(window.location.pathname);
  if (!isAdminPage) return;

  const start = () => {
    if (typeof AdminVoiceAssistant !== "undefined") AdminVoiceAssistant.init();
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", start);
  } else {
    start();
  }
})();
