const StudentVoiceAssistant = {
  recognition: null,
  listening: false,
  isBusy: false,
  stateKey: "caremeal_student_assistant_state_v2",
  state: {
    open: false,
    lastHeard: "",
    lastReply: "",
    lastBriefingDate: "",
    pendingOfferQuery: "",
  },
  elements: {},

  init() {
    if (!/\/student\//.test(window.location.pathname)) return;
    this.loadState();
    this.injectUI();
    this.bindUI();
    this.initSpeechRecognition();
    this.restoreUI();
    this.autoBriefing();
    this.resumePendingOfferQuery();
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

  injectUI() {
    const style = document.createElement("style");
    style.textContent = `
      .student-va-fab{
        position:fixed;right:24px;bottom:24px;width:56px;height:56px;border:0;border-radius:50%;
        background:linear-gradient(135deg,#10b981,#0ea5e9);color:#fff;
        box-shadow:0 12px 30px rgba(14,165,233,.42);z-index:1000;cursor:pointer;font-size:1.2rem
      }
      .student-va-fab.listening{animation:studentVaPulse 1.2s infinite}
      .student-va-panel{
        position:fixed;right:24px;bottom:92px;width:min(430px,calc(100vw - 24px));
        background:#0f1e34;border:1px solid rgba(255,255,255,.14);border-radius:16px;
        box-shadow:0 16px 40px rgba(0,0,0,.35);z-index:999;padding:14px;color:#e7edf8
      }
      .student-va-panel.hidden{display:none}
      .student-va-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
      .student-va-close{border:0;background:transparent;color:#93a4c2;font-size:1rem;cursor:pointer}
      .student-va-status{font-size:.8rem;color:#9fb0cf;margin-bottom:10px}
      .student-va-block{
        background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.09);
        border-radius:10px;padding:10px;margin-top:8px
      }
      .student-va-label{
        font-size:.7rem;letter-spacing:.06em;color:#9fb0cf;text-transform:uppercase;margin-bottom:6px
      }
      .student-va-text{font-size:.9rem;line-height:1.35;white-space:pre-wrap}
      .student-va-row{display:flex;gap:8px;margin-top:10px}
      .student-va-input{
        flex:1;min-width:0;border:1px solid rgba(255,255,255,.18);
        background:rgba(255,255,255,.06);color:#e7edf8;border-radius:10px;padding:10px 12px;outline:none
      }
      .student-va-send{
        border:0;border-radius:10px;padding:10px 12px;background:linear-gradient(135deg,#10b981,#0ea5e9);
        color:#fff;font-weight:600;cursor:pointer
      }
      @keyframes studentVaPulse{
        0%{box-shadow:0 0 0 0 rgba(14,165,233,.45)}
        70%{box-shadow:0 0 0 16px rgba(14,165,233,0)}
        100%{box-shadow:0 0 0 0 rgba(14,165,233,0)}
      }
    `;
    document.head.appendChild(style);

    const panel = document.createElement("section");
    panel.className = "student-va-panel hidden";
    panel.innerHTML = `
      <div class="student-va-head">
        <strong>Assistant etudiant</strong>
        <button type="button" class="student-va-close" aria-label="Fermer">x</button>
      </div>
      <div class="student-va-status">Pret. Clique sur le micro puis parle.</div>
      <div class="student-va-block">
        <div class="student-va-label">Texte reconnu</div>
        <div class="student-va-text" data-role="heard">...</div>
      </div>
      <div class="student-va-block">
        <div class="student-va-label">Reponse</div>
        <div class="student-va-text" data-role="reply">...</div>
      </div>
      <div class="student-va-row">
        <input type="text" class="student-va-input" data-role="input" placeholder="Dis ou ecris une demande">
        <button type="button" class="student-va-send" data-role="send">Envoyer</button>
      </div>
    `;

    const fab = document.createElement("button");
    fab.className = "student-va-fab";
    fab.type = "button";
    fab.title = "Assistant etudiant";
    fab.innerHTML = '<i class="fa-solid fa-microphone"></i>';

    document.body.appendChild(panel);
    document.body.appendChild(fab);

    this.elements = {
      panel,
      fab,
      closeBtn: panel.querySelector(".student-va-close"),
      status: panel.querySelector(".student-va-status"),
      heard: panel.querySelector('[data-role="heard"]'),
      reply: panel.querySelector('[data-role="reply"]'),
      input: panel.querySelector('[data-role="input"]'),
      send: panel.querySelector('[data-role="send"]'),
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

    this.elements.send.addEventListener("click", () => this.handleTyped());
    this.elements.input.addEventListener("keydown", (event) => {
      if (event.key !== "Enter") return;
      event.preventDefault();
      this.handleTyped();
    });
  },

  restoreUI() {
    if (this.state.open) this.elements.panel.classList.remove("hidden");
    if (this.state.lastHeard) this.elements.heard.textContent = this.state.lastHeard;
    if (this.state.lastReply) this.elements.reply.textContent = this.state.lastReply;
  },

  initSpeechRecognition() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
      this.setStatus("Reconnaissance vocale non supportee.");
      this.elements.fab.disabled = true;
      return;
    }

    this.recognition = new SpeechRecognition();
    this.recognition.lang = "fr-FR";
    this.recognition.interimResults = false;
    this.recognition.continuous = false;

    this.recognition.onresult = async (event) => {
      const text = (event.results?.[0]?.[0]?.transcript || "").trim();
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
      const code = event?.error || "unknown";
      if (code === "no-speech") this.setStatus("Aucun son detecte. Re-clique et parle.");
      else if (code === "not-allowed" || code === "service-not-allowed") this.setStatus("Micro bloque. Autorise le micro.");
      else if (code === "network") this.setStatus("Erreur micro reseau. Verifie internet et permissions.");
      else this.setStatus("Erreur micro: " + code);
    };

    this.recognition.onend = () => {
      this.listening = false;
      this.elements.fab.classList.remove("listening");
      this.elements.fab.innerHTML = '<i class="fa-solid fa-microphone"></i>';
    };
  },

  async startListening() {
    if (!this.recognition) return;
    if (this.listening) return;

    this.setStatus("Verification micro...");
    try {
      if (navigator.mediaDevices?.getUserMedia) {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        stream.getTracks().forEach((track) => track.stop());
      }
    } catch (_e) {
      this.setStatus("Micro bloque. Autorise le micro puis reessaie.");
      return;
    }

    this.listening = true;
    this.elements.fab.classList.add("listening");
    this.elements.fab.innerHTML = '<i class="fa-solid fa-stop"></i>';
    this.setStatus("J'ecoute...");

    try {
      this.recognition.start();
    } catch (_e) {
      this.listening = false;
      this.elements.fab.classList.remove("listening");
      this.elements.fab.innerHTML = '<i class="fa-solid fa-microphone"></i>';
    }
  },

  stopListening() {
    if (this.recognition) this.recognition.stop();
  },

  async handleTyped() {
    const text = (this.elements.input.value || "").trim();
    if (!text) return;
    this.elements.input.value = "";
    this.elements.heard.textContent = text;
    this.state.lastHeard = text;
    this.saveState();
    await this.handlePrompt(text);
  },

  async handlePrompt(text) {
    if (this.isBusy) return;
    this.isBusy = true;
    this.setStatus("Analyse...");

    try {
      const normalized = this.normalize(text);

      if (this.handleStudentNavigation(normalized)) return;

      if (this.isLogoutIntent(normalized)) {
        this.respond("Je lance la deconnexion.", false);
        const logoutBtn = document.querySelector("[data-action='logout']");
        if (logoutBtn) setTimeout(() => logoutBtn.click(), 200);
        return;
      }

      if (this.isOfferSearchIntent(normalized)) {
        if (!this.isDashboardPage()) {
          this.state.pendingOfferQuery = text;
          this.saveState();
          this.respond("Je vais sur Accueil pour rechercher les offres.", false);
          setTimeout(() => {
            window.location.href = "dashboard.php";
          }, 240);
          return;
        }

        const result = this.applyVoiceOfferSearch(text);
        this.respond(result.message, true);
        return;
      }

      if (this.isOfferCountIntent(normalized)) {
        if (!this.isDashboardPage()) {
          this.respond("Va sur Accueil pour voir toutes les offres en direct.", true);
          return;
        }
        const visible = this.getVisibleOfferCards().length;
        const total = this.getAllOfferCards().length;
        this.respond(`Il y a ${visible} offre${visible > 1 ? "s" : ""} visible${visible > 1 ? "s" : ""}, sur ${total} au total.`, true);
        return;
      }

      if (/(aide|help|tu peux faire quoi|que peux tu faire)/.test(normalized)) {
        this.respond("Je peux naviguer dans l'espace etudiant, te deconnecter et chercher des offres, par exemple: trouve-moi une pizza moins de 5 DT.", true);
        return;
      }

      if (/(supprimer|supprime|modifier|modifie|ajouter|ajoute|bloquer|debloquer|valider)/.test(normalized)) {
        this.respond("Je suis l'assistant etudiant. Je ne peux pas executer d'actions admin.", true);
        return;
      }

      const aiReply = await this.askStudentAssistant(text);
      this.respond(aiReply, true);
    } finally {
      this.isBusy = false;
    }
  },

  isDashboardPage() {
    return /\/student\/dashboard\.php$/i.test(window.location.pathname);
  },

  isLogoutIntent(normalizedText) {
    return /(deconnexion|deconnecte|logout|se deconnecter|se deconnecte)/.test(normalizedText);
  },

  isOfferSearchIntent(normalizedText) {
    const hasVerb = /(trouve|trouver|cherche|chercher|recherche|filtre|filtrer|affiche|afficher|montre|montrer|voir)/.test(normalizedText);
    const hasOfferTarget = /(offre|offres|pizza|sandwich|dessert|dt|dinar|moins de|plus de)/.test(normalizedText);
    const hasPriceClause = /(moins de|plus de)\s*[0-9]+(?:[.,][0-9]+)?/.test(normalizedText) && /(offre|offres|dt|dinar)/.test(normalizedText);
    return (hasVerb && hasOfferTarget) || hasPriceClause;
  },

  isOfferCountIntent(normalizedText) {
    return /(combien|nombre).*(offre|offres)/.test(normalizedText) || /(offre|offres).*(disponible|disponibles)/.test(normalizedText);
  },

  handleStudentNavigation(normalizedText) {
    const routes = [
      { words: ["accueil", "dashboard", "home"], url: "dashboard.php", msg: "J'ouvre l'accueil." },
      { words: ["profil", "profile"], url: "profile.html", msg: "J'ouvre ton profil." },
      { words: ["preference", "preferences"], url: "preferences.html", msg: "J'ouvre les preferences." },
      { words: ["evenement", "evenements", "event"], url: "events.html", msg: "J'ouvre les evenements." },
      { words: ["commande", "commandes", "orders"], url: "orders.html", msg: "J'ouvre les commandes." },
      { words: ["parametre", "parametres", "settings"], url: "settings.html", msg: "J'ouvre les parametres." },
      { words: ["point", "points"], url: "points.html", msg: "J'ouvre les points." },
    ];

    const hasNavVerb = /(aller|va|ouvre|ouvrir|affiche|afficher|montre|montrer|navigue|naviguer|page)/.test(normalizedText);
    if (!hasNavVerb) return false;

    for (const route of routes) {
      if (!route.words.some((word) => normalizedText.includes(word))) continue;
      this.respond(route.msg, false);
      setTimeout(() => {
        window.location.href = route.url;
      }, 240);
      return true;
    }
    return false;
  },

  autoBriefing() {
    if (!this.isDashboardPage()) return;
    const today = new Date().toISOString().slice(0, 10);
    if (this.state.lastBriefingDate === today) return;
    const total = this.getAllOfferCards().length;
    const msg = `Bonjour ! Tu as ${total} nouvelle${total > 1 ? "s" : ""} offre${total > 1 ? "s" : ""} pres de toi aujourd'hui.`;
    this.state.lastBriefingDate = today;
    this.saveState();
    this.respond(msg, true);
  },

  resumePendingOfferQuery() {
    if (!this.isDashboardPage()) return;
    const query = (this.state.pendingOfferQuery || "").trim();
    if (!query) return;
    this.state.pendingOfferQuery = "";
    this.saveState();
    setTimeout(() => {
      const result = this.applyVoiceOfferSearch(query);
      this.respond(result.message, true);
    }, 350);
  },

  applyVoiceOfferSearch(rawText) {
    const normalized = this.normalize(rawText);
    const keyword = this.extractKeyword(normalized);
    const maxPrice = this.extractMaxPrice(normalized);
    const wantsAllOffers = /\b(toutes?|tous)\b/.test(normalized) && /\boffres?\b/.test(normalized);

    this.resetCategoryFilterToAll();

    const searchInput = document.getElementById("offer-search");
    if (searchInput) searchInput.value = wantsAllOffers ? "" : keyword;
    if (typeof window.applyFilters === "function") {
      window.applyFilters();
    } else {
      this.filterCardsByKeyword(wantsAllOffers ? "" : keyword);
    }

    if (maxPrice !== null) {
      this.applyMaxPriceFilter(maxPrice);
    }

    const visibleCards = this.getVisibleOfferCards();
    if (visibleCards.length === 0) {
      return { message: "Aucune offre correspondante trouvee." };
    }

    const top = visibleCards.slice(0, 3).map((card) => {
      const title = card.querySelector("h4")?.textContent?.trim() || "Offre";
      const price = this.extractCardPrice(card);
      return price === null ? title : `${title} a ${price.toFixed(2)} DT`;
    });

    return {
      message: `J'ai trouve ${visibleCards.length} offre${visibleCards.length > 1 ? "s" : ""}. ${top.join(", ")}.`,
    };
  },

  extractKeyword(normalizedText) {
    const cleaned = normalizedText
      .replace(/trouve[- ]?moi|trouve moi|trouver|trouve|cherche[- ]?moi|cherche moi|cherche|chercher|recherche|filtre|filtrer/g, " ")
      .replace(/affiche[- ]?moi|affiche moi|afficher|affiche|montre[- ]?moi|montre moi|montrer|montre|voir/g, " ")
      .replace(/moins de\s*[0-9]+(?:[.,][0-9]+)?\s*(dt|dinar|dinars)?/g, " ")
      .replace(/plus de\s*[0-9]+(?:[.,][0-9]+)?\s*(dt|dinar|dinars)?/g, " ")
      .replace(/\b(offres|offre|disponibles|disponible|s il te plait|svp|stp)\b/g, " ")
      .replace(/\b(toutes|tous|toute|tout|moi|me|mon|ma|mes)\b/g, " ")
      .replace(/\b(le|la|les|du|des|de|d|l)\b/g, " ")
      .replace(/['’`"]/g, " ")
      .replace(/[.,;:!?()[\]{}]/g, " ")
      .replace(/-/g, " ")
      .replace(/\s+/g, " ")
      .trim();

    // Evite les residus de 1 caractere (ex: "s" apres nettoyage de "offres")
    return cleaned
      .split(" ")
      .filter((token) => token.length > 1)
      .join(" ");
  },

  resetCategoryFilterToAll() {
    const allButton = document.querySelector(".cat-pill[data-cat='all']");
    if (typeof window.filterByCategory === "function" && allButton) {
      window.filterByCategory("all", allButton);
      return;
    }
    if (allButton) {
      allButton.click();
    }
  },

  extractMaxPrice(normalizedText) {
    const match = normalizedText.match(/moins de\s*([0-9]+(?:[.,][0-9]+)?)/);
    if (!match) return null;
    const value = parseFloat(match[1].replace(",", "."));
    return Number.isFinite(value) ? value : null;
  },

  filterCardsByKeyword(keyword) {
    const needle = (keyword || "").trim();
    for (const card of this.getAllOfferCards()) {
      if (!needle) {
        card.style.display = "";
        continue;
      }
      const title = (card.querySelector("h4")?.textContent || "").toLowerCase();
      card.style.display = title.includes(needle) ? "" : "none";
    }
  },

  applyMaxPriceFilter(maxPrice) {
    for (const card of this.getAllOfferCards()) {
      if (card.style.display === "none") continue;
      const price = this.extractCardPrice(card);
      if (price === null) continue;
      if (price > maxPrice) card.style.display = "none";
    }
  },

  getAllOfferCards() {
    return Array.from(document.querySelectorAll(".student-offer-card"));
  },

  getVisibleOfferCards() {
    return this.getAllOfferCards().filter((card) => card.style.display !== "none");
  },

  extractCardPrice(card) {
    const text = card.querySelector(".offer-card-price .discounted")?.textContent || "";
    const match = text.replace(",", ".").match(/([0-9]+(?:\.[0-9]+)?)/);
    if (!match) return null;
    const price = parseFloat(match[1]);
    return Number.isFinite(price) ? price : null;
  },

  getApiUrl(path) {
    const pathname = window.location.pathname;
    const marker = "/student/";
    const idx = pathname.toLowerCase().indexOf(marker);
    const base = idx >= 0 ? pathname.slice(0, idx) : "";
    return `${base}${path}`;
  },

  async askStudentAssistant(text) {
    try {
      const response = await fetch(this.getApiUrl("/api/student-assistant.php"), {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          text,
          context: {
            page: window.location.pathname,
            visible_offers: this.getVisibleOfferCards().length,
            total_offers: this.getAllOfferCards().length,
          },
        }),
      });

      const payload = await response.json().catch(() => ({}));
      if (!response.ok) {
        const message = (payload.error || "Erreur IA").toString();
        if (response.status === 429) {
          const retry = Number(payload.retry_after_seconds || 0);
          if (retry > 0) return `Le service IA est temporairement limite. Reessaie dans ${retry} secondes.`;
          return "Le service IA est temporairement limite. Reessaie dans quelques secondes.";
        }
        return `Je n'ai pas pu contacter l'assistant IA (${message}).`;
      }

      const reply = (payload.reply || "").toString().trim();
      if (!reply) return "Je n'ai pas de reponse utile pour le moment.";
      return reply;
    } catch (_e) {
      return "Connexion IA indisponible pour le moment.";
    }
  },

  normalize(text) {
    return (text || "")
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "");
  },

  cleanForSpeech(text) {
    return (text || "")
      .replace(/[*_`#>/\\]/g, " ")
      .replace(/\s+/g, " ")
      .trim();
  },

  respond(message, speak) {
    const safeMessage = (message || "").trim() || "Je n'ai pas compris.";
    this.elements.reply.textContent = safeMessage;
    this.state.lastReply = safeMessage;
    this.saveState();
    this.setStatus("Reponse prete.");

    if (!speak) return;
    const utterance = new SpeechSynthesisUtterance(this.cleanForSpeech(safeMessage));
    utterance.lang = "fr-FR";
    utterance.rate = 1;
    window.speechSynthesis.cancel();
    window.speechSynthesis.speak(utterance);
  },

  setStatus(message) {
    this.elements.status.textContent = message;
  },
};

(function bootstrapStudentVoiceAssistant() {
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => StudentVoiceAssistant.init());
    return;
  }
  StudentVoiceAssistant.init();
})();
