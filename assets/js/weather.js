/* ============================================================
   CAREMEAL — MODULE MÉTÉO
   API : Open-Meteo (100% gratuit, sans clé API)
   Affiche la météo prévue pour les événements présentiel
   ============================================================ */

const CareMealWeather = {

    // Cache : { "lat_lon_date": { icon, temp, label } }
    cache: {},

    // ── Coordonnées des villes tunisiennes ────────────────────
    cities: {
        'tunis'       : { lat: 36.8065, lon: 10.1815 },
        'sfax'        : { lat: 34.7406, lon: 10.7603 },
        'sousse'      : { lat: 35.8245, lon: 10.6346 },
        'bizerte'     : { lat: 37.2744, lon: 9.8739  },
        'nabeul'      : { lat: 36.4561, lon: 10.7376 },
        'monastir'    : { lat: 35.7643, lon: 10.8113 },
        'gabes'       : { lat: 33.8881, lon: 10.0975 },
        'ariana'      : { lat: 36.8625, lon: 10.1956 },
        'manouba'     : { lat: 36.8100, lon: 10.0972 },
        'hawariya'    : { lat: 37.0500, lon: 10.6167 },
        'default'     : { lat: 36.8065, lon: 10.1815 }, // Tunis par défaut
    },

    // ── Conversion weather code → icône + label ───────────────
    // Source : https://open-meteo.com/en/docs#weathervariables
    getWeatherInfo(code) {
        const map = {
            0  : { icon: '☀️',  label: 'Ciel dégagé'     },
            1  : { icon: '🌤️', label: 'Peu nuageux'      },
            2  : { icon: '⛅',  label: 'Partiellement nuageux' },
            3  : { icon: '☁️',  label: 'Couvert'          },
            45 : { icon: '🌫️', label: 'Brouillard'       },
            48 : { icon: '🌫️', label: 'Brouillard givrant'},
            51 : { icon: '🌦️', label: 'Bruine légère'    },
            53 : { icon: '🌦️', label: 'Bruine modérée'   },
            55 : { icon: '🌧️', label: 'Bruine dense'     },
            61 : { icon: '🌧️', label: 'Pluie légère'     },
            63 : { icon: '🌧️', label: 'Pluie modérée'    },
            65 : { icon: '🌧️', label: 'Pluie forte'      },
            71 : { icon: '🌨️', label: 'Neige légère'     },
            73 : { icon: '🌨️', label: 'Neige modérée'    },
            75 : { icon: '❄️',  label: 'Neige forte'      },
            80 : { icon: '🌦️', label: 'Averses légères'  },
            81 : { icon: '🌧️', label: 'Averses modérées' },
            82 : { icon: '⛈️', label: 'Averses violentes'},
            95 : { icon: '⛈️', label: 'Orage'            },
            96 : { icon: '⛈️', label: 'Orage avec grêle' },
            99 : { icon: '⛈️', label: 'Orage violent'    },
        };
        return map[code] || { icon: '🌡️', label: 'Météo inconnue' };
    },

    // ── Détecter les coordonnées depuis le nom de la ville ────
    getCoordsFromLocation(location) {
        if (!location) return this.cities['default'];
        const loc = location.toLowerCase();
        for (const city in this.cities) {
            if (loc.includes(city)) return this.cities[city];
        }
        return this.cities['default'];
    },

    // ── Vérifier si la date est dans les 16 prochains jours ──
    // Open-Meteo gratuit supporte jusqu'à 16 jours
    // Pour les dates plus lointaines, on affiche quand même une estimation climatique
    isDateInForecastRange(dateStr) {
        const today     = new Date();
        today.setHours(0, 0, 0, 0);
        const eventDate = new Date(dateStr);
        const diffDays  = Math.floor((eventDate - today) / (1000 * 60 * 60 * 24));
        return diffDays >= 0; // Toutes les dates futures (pas de limite)
    },

    // ── Récupérer la météo depuis Open-Meteo ──────────────────
    async fetchWeather(lat, lon, date) {
        const cacheKey = `${lat}_${lon}_${date}`;
        if (this.cache[cacheKey]) return this.cache[cacheKey];

        const today    = new Date();
        today.setHours(0, 0, 0, 0);
        const eventDate = new Date(date);
        const diffDays  = Math.floor((eventDate - today) / (1000 * 60 * 60 * 24));

        // Choisir l'endpoint selon la distance dans le temps
        // ≤ 16 jours → prévision précise
        // > 16 jours → données climatiques historiques (moyenne du mois)
        let url;
        if (diffDays <= 15) {
            url = `https://api.open-meteo.com/v1/forecast`
                + `?latitude=${lat}&longitude=${lon}`
                + `&daily=temperature_2m_max,weathercode`
                + `&timezone=Africa%2FTunis`
                + `&start_date=${date}&end_date=${date}`;
        } else {
            // Données climatiques : moyenne sur les 30 dernières années pour ce mois
            const month = date.substring(5, 7); // "05" pour mai
            const day   = date.substring(8, 10);
            // Utiliser l'année courante pour les données climatiques
            const year  = new Date().getFullYear() - 1; // année passée pour avoir des données
            const histDate = `${year}-${month}-${day}`;
            url = `https://archive-api.open-meteo.com/v1/archive`
                + `?latitude=${lat}&longitude=${lon}`
                + `&daily=temperature_2m_max,weathercode`
                + `&timezone=Africa%2FTunis`
                + `&start_date=${histDate}&end_date=${histDate}`;
        }

        try {
            const res  = await fetch(url);
            const data = await res.json();

            if (data.daily && data.daily.temperature_2m_max && data.daily.temperature_2m_max.length > 0) {
                const temp = Math.round(data.daily.temperature_2m_max[0]);
                const code = data.daily.weathercode[0];
                const info = this.getWeatherInfo(code);
                const isEstimate = diffDays > 15;

                const result = {
                    temp,
                    icon    : info.icon,
                    label   : info.label,
                    estimate: isEstimate, // true = données historiques, pas une prévision
                    success : true
                };
                this.cache[cacheKey] = result;
                return result;
            }
        } catch (err) {
            console.warn('[CareMealWeather] Erreur API:', err);
        }
        return { success: false };
    },

    // ── Afficher la météo sur une carte événement ─────────────
    // Appelé après le rendu des cartes
    async loadForCard(eventId, location, date, type) {
        // Seulement pour les événements présentiel avec date future
        if (type !== 'Présentiel' && type !== 'presentiel') return;
        if (!this.isDateInForecastRange(date)) return;

        const el = document.getElementById(`weather-${eventId}`);
        if (!el) return;

        const coords  = this.getCoordsFromLocation(location);
        const weather = await this.fetchWeather(coords.lat, coords.lon, date);

        if (weather.success) {
            const estimateNote = weather.estimate
                ? `<span style="font-size:0.7rem; opacity:0.7;"> (estimation)</span>`
                : '';
            el.innerHTML = `
                <span style="display:inline-flex; align-items:center; gap:5px;
                    background:rgba(59,130,246,0.12); border:1px solid rgba(59,130,246,0.25);
                    border-radius:20px; padding:3px 10px; font-size:0.78rem; color:#93c5fd;">
                    ${weather.icon} ${weather.temp}°C · ${weather.label}${estimateNote}
                </span>`;
        }
    },

    // ── Charger la météo pour tous les événements visibles ────
    async loadAll(events) {
        events.forEach(ev => {
            const type     = ev.type || ev.type_evenement || '';
            const location = ev.location || ev.lieu || '';
            const date     = ev.date || ev.date_evenement || '';
            const id       = ev.id || ev.id_evenement;
            this.loadForCard(id, location, date, type);
        });
    }
};
