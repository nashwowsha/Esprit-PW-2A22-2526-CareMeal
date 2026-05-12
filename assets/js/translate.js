/* ============================================================
   CAREMEAL — MODULE TRADUCTION PARTAGÉ
   Utilisé par : admin, partenaire, étudiant
   API : MyMemory (gratuit, sans clé)
   ============================================================ */

const CareMealTranslate = {

    cache: {},

    getBtnsHTML(eventId, hasDesc) {
        if (!hasDesc) return '';
        return `
        <div style="display:flex; gap:6px; margin-top:6px; flex-wrap:wrap;" id="translate-btns-${eventId}">
            <button onclick="CareMealTranslate.translate(${eventId}, 'ar')"
                id="btn-ar-${eventId}"
                style="padding:4px 10px; border-radius:20px; border:1px solid #334155;
                       background:#0f172a; color:#94a3b8; font-size:0.75rem; cursor:pointer;">
                AR عربي
            </button>
            <button onclick="CareMealTranslate.translate(${eventId}, 'en')"
                id="btn-en-${eventId}"
                style="padding:4px 10px; border-radius:20px; border:1px solid #334155;
                       background:#0f172a; color:#94a3b8; font-size:0.75rem; cursor:pointer;">
                EN English
            </button>
            <button onclick="CareMealTranslate.reset(${eventId})"
                id="btn-reset-${eventId}"
                style="display:none; padding:4px 10px; border-radius:20px; border:1px solid #334155;
                       background:#0f172a; color:#64748b; font-size:0.75rem; cursor:pointer;">
                ↩ FR
            </button>
        </div>`;
    },

    async translate(eventId, targetLang) {
        const frEl     = document.getElementById(`desc-fr-${eventId}`);
        const transEl  = document.getElementById(`desc-translated-${eventId}`);
        const resetBtn = document.getElementById(`btn-reset-${eventId}`);
        const btn      = document.getElementById(`btn-${targetLang}-${eventId}`);

        if (!frEl) return;

        const originalText = frEl.dataset.original || frEl.textContent.trim();
        if (!originalText) return;

        const cacheKey = `${eventId}_${targetLang}`;

        if (btn) { btn.textContent = '…'; btn.disabled = true; }

        if (this.cache[cacheKey]) {
            this._show(eventId, this.cache[cacheKey], targetLang);
            if (btn) { btn.textContent = targetLang === 'ar' ? 'AR عربي' : 'EN English'; btn.disabled = false; }
            return;
        }

        try {
            const base = (typeof App !== 'undefined' && App.apiUrl) ? App.apiUrl('Controller/TranslateController.php') : '/Controller/TranslateController.php';
            const res  = await fetch(base, {
                method  : 'POST',
                headers : { 'Content-Type': 'application/json' },
                body    : JSON.stringify({ q: originalText, source: 'fr', target: targetLang })
            });
            const data = await res.json();

            if (data.success && data.translatedText) {
                this.cache[cacheKey] = data.translatedText;
                this._show(eventId, data.translatedText, targetLang);
            } else {
                if (transEl) { transEl.textContent = '⚠ ' + (data.message || 'Traduction indisponible.'); transEl.style.display = 'block'; }
            }
        } catch (err) {
            if (transEl) { transEl.textContent = '⚠ Erreur réseau.'; transEl.style.display = 'block'; }
        } finally {
            if (btn) { btn.textContent = targetLang === 'ar' ? 'AR عربي' : 'EN English'; btn.disabled = false; }
        }
    },

    _show(eventId, text, lang) {
        const frEl    = document.getElementById(`desc-fr-${eventId}`);
        const transEl = document.getElementById(`desc-translated-${eventId}`);
        const resetBtn = document.getElementById(`btn-reset-${eventId}`);

        if (frEl)    frEl.style.display = 'none';
        if (transEl) {
            transEl.textContent   = text;
            transEl.style.display = 'block';
            transEl.dir           = lang === 'ar' ? 'rtl' : 'ltr';
            transEl.style.textAlign = lang === 'ar' ? 'right' : 'left';
        }
        if (resetBtn) resetBtn.style.display = 'inline-block';
    },

    reset(eventId) {
        const frEl    = document.getElementById(`desc-fr-${eventId}`);
        const transEl = document.getElementById(`desc-translated-${eventId}`);
        const resetBtn = document.getElementById(`btn-reset-${eventId}`);

        if (frEl)    frEl.style.display = '';
        if (transEl) { transEl.style.display = 'none'; transEl.textContent = ''; }
        if (resetBtn) resetBtn.style.display = 'none';
    }
};
