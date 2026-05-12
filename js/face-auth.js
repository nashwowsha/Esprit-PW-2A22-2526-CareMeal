/* ============================================
   CAREMEAL — FACE AUTH (Secure — Real Pose Validation)
   face-auth.js — Reconnaissance faciale iPhone-style
   ============================================ */

const FaceAuth = {
  modelsLoaded: false,
  videoStream: null,
  scanning: false,
  _flashDimTimer: null,
  _exposureInterval: null,
  _flashForced: null, // null = auto, true = forcé ON, false = forcé OFF
  _flashPhaseActive: false,
  _flashCaptures: 0,
  _flashPoseIdx: 0,
  _flashPoseCount: 0,
  _roomBright: false,
  _noFaceFrames: 0,
  _lastBrightness: 0.5,

  // Registration state
  _regDescriptors: [],
  _regMode: false,
  _regPoseIdx: 0,
  _regPoseCount: 0,
  _regPoseHoldFrames: 0, // consecutive frames holding correct pose

  // Login multi-scan state
  _loginDescriptors: [],
  _loginPoses: [],
  _loginPoseIdx: 0,
  _loginPoseHoldFrames: 0,
  _loginStartTime: 0,
  _loginTimeoutId: null,

  // Liveness (anti-photo) — blink detection
  _blinkDetected: false,
  _eyeWasOpen: true,

  // Detection configs — multiple fallbacks for low light / glasses
  // inputSize plus grand = meilleure précision sur 720p
  _detectConfigs: [
    { inputSize: 608, scoreThreshold: 0.10 },
    { inputSize: 512, scoreThreshold: 0.08 },
    { inputSize: 416, scoreThreshold: 0.05 },
    { inputSize: 320, scoreThreshold: 0.03 }
  ],

  // Fast detection for login — priorité vitesse mais profite du 720p
  _loginDetectConfigs: [
    { inputSize: 608, scoreThreshold: 0.10 },
    { inputSize: 416, scoreThreshold: 0.05 },
    { inputSize: 320, scoreThreshold: 0.03 }
  ],

  // ============================================================
  //  POSE DEFINITIONS — strict angle thresholds, ZERO timers
  //  Each pose needs multiple consecutive valid frames to build a dense vector
  // ============================================================
  HOLD_FRAMES: 5, // 5 frames pleines pour solidifier les points (Deep Scan)

  _regPoses: [
    { name: 'Fixez l\'écran', icon: '🎯', check: (a) => Math.abs(a.yaw) < 10 && Math.abs(a.pitch) < 12, count: 5 },
    { name: 'Tournez lentement à gauche', icon: '⬅�?', check: (a) => a.yaw > 22, count: 3 },
    { name: 'Tournez lentement à droite', icon: '➡�?', check: (a) => a.yaw < -22, count: 3 },
    { name: 'Levez la tête', icon: '⬆�?', check: (a) => a.pitch < -15, count: 2 },
    { name: 'Baissez la tête', icon: '⬇�?', check: (a) => a.pitch > 15, count: 2 },
  ],

  // Phase 3 — Flash poses (subset with rotations)
  _flashPoses: [
    { name: 'Fixez l\'écran', icon: '🎯', check: (a) => Math.abs(a.yaw) < 12 && Math.abs(a.pitch) < 14, count: 2 },
    { name: 'Tournez vers la gauche', icon: '⬅�?', check: (a) => a.yaw > 18, count: 1 },
    { name: 'Tournez vers la droite', icon: '➡�?', check: (a) => a.yaw < -18, count: 1 },
  ],

  // ============================================================
  //  Load face-api models
  // ============================================================
  async loadModels() {
    if (this.modelsLoaded) return true;
    try {
      if (faceapi.tf && faceapi.tf.setBackend) {
        await faceapi.tf.setBackend('webgl');
        await faceapi.tf.ready();
      }
      
      const U = (typeof window.caremealPath === 'function')
        ? window.caremealPath('assets/models')
        : '/assets/models';
      await Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri(U),
        faceapi.nets.faceLandmark68Net.loadFromUri(U),
        faceapi.nets.faceRecognitionNet.loadFromUri(U)
      ]);
      this.modelsLoaded = true;
      return true;
    } catch (e) { console.error('[FaceAuth]', e); return false; }
  },

  // ============================================================
  //  Camera — high resolution + intelligent adaptive settings
  // ============================================================
  //  Camera — Full auto adaptive (lumière normale / sombre / forte)
  // ============================================================
  async startCamera(videoEl) {
    try {
      const s = await navigator.mediaDevices.getUserMedia({
        video: {
          width:      { ideal: 1280, min: 640 },
          height:     { ideal: 720,  min: 480 },
          frameRate:  { ideal: 30,   min: 15  },
          facingMode: 'user'
        }
      });

      videoEl.srcObject = s;
      await videoEl.play();
      this.videoStream = s;

      // Attendre que la caméra stabilise son auto-exposition
      await new Promise(r => setTimeout(r, 900));
      await this._applyAdvancedConstraints(s, videoEl);

      return true;
    } catch (e) { console.error('[FaceAuth]', e); return false; }
  },

  // ============================================================
  //  Applique les meilleurs paramètres selon la lumière détectée
  //  3 profils : sombre / normale / très lumineuse
  // ============================================================
  async _applyAdvancedConstraints(stream, videoEl) {
    const track = stream.getVideoTracks()[0];
    if (!track) return;

    const caps = track.getCapabilities();
    if (!caps) return;

    // Mesure la lumière ambiante réelle (zone centrale = visage)
    const brightness = videoEl ? this._measureBrightness(videoEl) : 0.5;
    console.log(`[FaceAuth] Lumière ambiante détectée: ${Math.round(brightness * 100)}%`);

    // Choisir le profil selon la lumière
    let profile;
    if (brightness < 0.30) {
      profile = 'dark';        // pièce sombre
    } else if (brightness > 0.72) {
      profile = 'bright';      // très lumineuse / contre-jour
    } else {
      profile = 'normal';      // lumière normale
    }
    console.log(`[FaceAuth] Profil caméra: ${profile}`);

    // Applique une contrainte sans planter si non supportée
    const tryApply = async (name, value) => {
      try {
        await track.applyConstraints({ [name]: value });
      } catch (e) { /* ignoré silencieusement */ }
    };

    // ── FOCUS : toujours continu, distance ~50cm (visage) ──
    if (caps.focusMode) {
      const mode = caps.focusMode.includes('continuous') ? 'continuous'
                 : caps.focusMode.includes('auto')       ? 'auto'
                 : null;
      if (mode) await tryApply('focusMode', mode);
    }
    if (caps.focusDistance) {
      const target = Math.min(caps.focusDistance.max,
                     Math.max(caps.focusDistance.min, 0.5));
      await tryApply('focusDistance', target);
    }

    // ── BALANCE DES BLANCS : toujours auto ──
    if (caps.whiteBalanceMode) {
      const mode = caps.whiteBalanceMode.includes('continuous') ? 'continuous'
                 : caps.whiteBalanceMode[0];
      await tryApply('whiteBalanceMode', mode);
    }

    // ── NETTETÉ : toujours max ──
    if (caps.sharpness) {
      await tryApply('sharpness', caps.sharpness.max);
    }

    // ── EXPOSITION : adaptée au profil ──
    if (caps.exposureMode) {
      await tryApply('exposureMode', 'continuous');
    }
    if (caps.exposureCompensation) {
      const { min, max } = caps.exposureCompensation;
      // Sombre → surexposer / Normal → léger boost / Très lumineux → sous-exposer
      const ev = profile === 'dark'   ? Math.min(max, 1.5)
               : profile === 'bright' ? Math.max(min, -1.0)
               :                        Math.min(max, Math.max(min, 0.5));
      await tryApply('exposureCompensation', ev);
    }
    if (caps.exposureTime) {
      // Sombre → temps long (plus de lumière) / Lumineux → temps court (évite surexpo)
      const target = profile === 'dark'   ? Math.min(caps.exposureTime.max, 33333)  // ~1/30s
                   : profile === 'bright' ? Math.max(caps.exposureTime.min, 8333)   // ~1/120s
                   :                        Math.min(caps.exposureTime.max,
                                            Math.max(caps.exposureTime.min, 16666)); // ~1/60s
      await tryApply('exposureTime', target);
    }

    // ── LUMINOSITÉ : adaptée au profil ──
    if (caps.brightness) {
      const { min, max } = caps.brightness;
      const range = max - min;
      const val = profile === 'dark'   ? Math.round(min + range * 0.75)
                : profile === 'bright' ? Math.round(min + range * 0.40)
                :                        Math.round(min + range * 0.58);
      await tryApply('brightness', val);
    }

    // ── CONTRASTE : légèrement renforcé dans tous les cas ──
    if (caps.contrast) {
      const { min, max } = caps.contrast;
      const val = profile === 'dark'   ? Math.round(min + (max - min) * 0.70)
                : profile === 'bright' ? Math.round(min + (max - min) * 0.55)
                :                        Math.round(min + (max - min) * 0.65);
      await tryApply('contrast', val);
    }

    // ── SATURATION : neutre (ne pas fausser la reconnaissance) ──
    if (caps.saturation) {
      const { min, max } = caps.saturation;
      await tryApply('saturation', Math.round(min + (max - min) * 0.5));
    }

    // ── Pas d'interpolation logicielle ──
    if (caps.resizeMode && caps.resizeMode.includes('none')) {
      await tryApply('resizeMode', 'none');
    }

    // Démarrer l'adaptation dynamique continue
    this._startAdaptiveLoop(track, caps, videoEl);
  },

  // ============================================================
  //  Boucle adaptative — réévalue la lumière toutes les 3s
  //  et ajuste l'exposition si la scène change
  // ============================================================
  _startAdaptiveLoop(track, caps, videoEl) {
    if (this._adaptiveInterval) clearInterval(this._adaptiveInterval);
    if (!caps.exposureCompensation && !caps.brightness) return;

    let lastProfile = null;

    this._adaptiveInterval = setInterval(async () => {
      if (!this.scanning || !videoEl) return;

      const brightness = this._measureBrightness(videoEl);
      const profile = brightness < 0.30 ? 'dark'
                    : brightness > 0.72  ? 'bright'
                    :                      'normal';

      // Ne ré-appliquer que si le profil a changé
      if (profile === lastProfile) return;
      lastProfile = profile;
      console.log(`[FaceAuth] Profil changé → ${profile} (${Math.round(brightness * 100)}%)`);

      const tryApply = async (name, value) => {
        try { await track.applyConstraints({ [name]: value }); } catch (e) {}
      };

      if (caps.exposureCompensation) {
        const { min, max } = caps.exposureCompensation;
        const ev = profile === 'dark'   ? Math.min(max, 1.5)
                 : profile === 'bright' ? Math.max(min, -1.0)
                 :                        Math.min(max, Math.max(min, 0.5));
        await tryApply('exposureCompensation', ev);
      }
      if (caps.brightness) {
        const { min, max } = caps.brightness;
        const range = max - min;
        const val = profile === 'dark'   ? Math.round(min + range * 0.75)
                  : profile === 'bright' ? Math.round(min + range * 0.40)
                  :                        Math.round(min + range * 0.58);
        await tryApply('brightness', val);
      }
    }, 3000);
  },

  stopCamera() {
    if (this._adaptiveInterval) {
      clearInterval(this._adaptiveInterval);
      this._adaptiveInterval = null;
    }
    if (this._focusWatcherInterval) {
      clearInterval(this._focusWatcherInterval);
      this._focusWatcherInterval = null;
    }
    if (this.videoStream) { this.videoStream.getTracks().forEach(t => t.stop()); this.videoStream = null; }
  },

  // ============================================================
  //  Detect face — tries multiple configs for low-light tolerance
  // ============================================================
  async detectFace(videoEl) {
    for (const c of this._detectConfigs) {
      try {
        const r = await faceapi.detectSingleFace(videoEl, new faceapi.TinyFaceDetectorOptions(c))
          .withFaceLandmarks().withFaceDescriptor();
        if (r) return r;
      } catch (e) { }
    }
    return null;
  },

  // Fast detection for login — fewer configs = faster response
  async detectFaceFast(videoEl) {
    for (const c of this._loginDetectConfigs) {
      try {
        const r = await faceapi.detectSingleFace(videoEl, new faceapi.TinyFaceDetectorOptions(c))
          .withFaceLandmarks().withFaceDescriptor();
        if (r) return r;
      } catch (e) { }
    }
    return null;
  },

  // ============================================================
  //  REAL YAW/PITCH calculation from 68 landmarks
  //  Uses nose tip, nose bridge, eye corners, chin geometry
  // ============================================================
  _computeAngles(landmarks) {
    const p = landmarks.positions;

    // Key points
    const noseTip = p[30]; // nose tip
    const noseBridge = p[27]; // top of nose bridge
    const lEyeOuter = p[36]; // left eye outer corner
    const rEyeOuter = p[45]; // right eye outer corner
    const chin = p[8];  // chin bottom
    const lBrow = p[19]; // left eyebrow outer
    const rBrow = p[24]; // right eyebrow outer

    // === YAW (horizontal rotation) ===
    // Compare nose-to-left-eye distance vs nose-to-right-eye distance
    const dLeft = Math.sqrt(Math.pow(noseTip.x - lEyeOuter.x, 2) + Math.pow(noseTip.y - lEyeOuter.y, 2));
    const dRight = Math.sqrt(Math.pow(noseTip.x - rEyeOuter.x, 2) + Math.pow(noseTip.y - rEyeOuter.y, 2));
    const eyeWidth = Math.sqrt(Math.pow(rEyeOuter.x - lEyeOuter.x, 2) + Math.pow(rEyeOuter.y - lEyeOuter.y, 2)) || 1;

    // Normalized asymmetry: positive = turned left (nose closer to right eye), negative = turned right
    const yawRatio = (dLeft - dRight) / eyeWidth;
    // Convert to approximate degrees (empirically ~45° maps to ratio ~0.6)
    const yaw = yawRatio * 75;

    // === PITCH (vertical rotation) ===
    // Compare nose-to-brow distance vs nose-to-chin distance
    const browMidY = (lBrow.y + rBrow.y) / 2;
    const noseToBrow = Math.abs(noseTip.y - browMidY);
    const noseToChin = Math.abs(chin.y - noseTip.y);
    const totalFaceH = noseToBrow + noseToChin || 1;

    // Neutral: nose divides face roughly 40% brow / 60% chin
    // vRatio < 0.35 = looking up, vRatio > 0.45 = looking down
    const vRatio = noseToBrow / totalFaceH;
    const pitch = (vRatio - 0.40) * 100; // positive = looking down, negative = looking up

    return { yaw: Math.round(yaw * 10) / 10, pitch: Math.round(pitch * 10) / 10 };
  },

  // ============================================================
  //  Euclidean distance
  // ============================================================
  _eucDist(a, b) {
    let s = 0;
    for (let i = 0; i < 128; i++) { const d = a[i] - b[i]; s += d * d; }
    return Math.sqrt(s);
  },

  // ============================================================
  //  EAR — Eye Aspect Ratio for blink/liveness detection
  //  Uses 6 eye landmarks per eye from the 68-point model
  //  EAR < 0.21 = eyes closed, EAR > 0.25 = eyes open
  // ============================================================
  _computeEAR(landmarks) {
    const p = landmarks.positions;
    const dist = (a, b) => Math.sqrt(Math.pow(a.x - b.x, 2) + Math.pow(a.y - b.y, 2));
    // Left eye (36-41)
    const earL = (dist(p[37], p[41]) + dist(p[38], p[40])) / (2 * dist(p[36], p[39]));
    // Right eye (42-47)
    const earR = (dist(p[43], p[47]) + dist(p[44], p[46])) / (2 * dist(p[42], p[45]));
    return (earL + earR) / 2;
  },

  // ============================================================
  //  Face position analysis — checks size + centering
  //  Returns guidance about what's wrong with face position
  // ============================================================
  _analyzeFacePosition(detection, videoEl) {
    const box = detection.detection.box;
    const videoW = videoEl.videoWidth || videoEl.width || 640;
    const videoH = videoEl.videoHeight || videoEl.height || 480;
    const faceArea = (box.width * box.height) / (videoW * videoH);
    const centerX = (box.x + box.width / 2) / videoW;
    const centerY = (box.y + box.height / 2) / videoH;
    return {
      tooFar: faceArea < 0.035,
      tooClose: faceArea > 0.55,
      offCenter: Math.abs(centerX - 0.5) > 0.28 || Math.abs(centerY - 0.5) > 0.28,
      faceArea, centerX, centerY
    };
  },

  // ============================================================
  //  Success sound — Web Audio API (no external file)
  // ============================================================
  _playSuccessSound() {
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.frequency.setValueAtTime(880, ctx.currentTime);
      osc.frequency.setValueAtTime(1320, ctx.currentTime + 0.08);
      gain.gain.setValueAtTime(0.12, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.3);
    } catch (e) { /* silent fail */ }
  },

  // ============================================================
  //  LOGIN — Multi-angle with random pose challenge
  // ============================================================
  async openFaceLogin() {
    this._regMode = false;
    this._loginDescriptors = [];
    this._loginPoseIdx = 0;
    this._loginPoseHoldFrames = 0;
    this._blinkDetected = false;
    this._eyeWasOpen = true;
    this._loginStartTime = Date.now();
    this._earBaseline = 0;
    this._earSamples = 0;
    this._noFaceFrames = 0;
    this._lastBrightness = 0.5;

    // 3 quick front-facing captures (like iPhone Face ID — fast & secure)
    this._loginPoses = [
      { name: 'Regardez la camera', icon: '\uD83D\uDE10', check: (a) => Math.abs(a.yaw) < 14 && Math.abs(a.pitch) < 15 },
      { name: 'Regardez la camera', icon: '\uD83D\uDE10', check: (a) => Math.abs(a.yaw) < 14 && Math.abs(a.pitch) < 15 },
      { name: 'Regardez la camera', icon: '\uD83D\uDE10', check: (a) => Math.abs(a.yaw) < 14 && Math.abs(a.pitch) < 15 },
    ];
    this._loginLandmarks = []; // Store landmark positions for liveness

    const modal = document.getElementById('face-modal');
    const video = document.getElementById('face-video');
    const status = document.getElementById('face-status');
    if (!modal || !video) return;

    modal.classList.add('active');
    const vc = document.querySelector('.face-video-container');
    if (vc) vc.classList.remove('face-circle-mode');
    const pr = document.getElementById('face-progress-ring');
    if (pr) pr.style.display = 'none';
    const ph = document.getElementById('face-reg-phases');
    if (ph) ph.style.display = 'none';
    const sf = vc ? vc.querySelector('.face-scan-frame') : null;
    if (sf) sf.style.display = '';

    // Inject premium login UI
    this._injectLoginUI(vc);
    this._injectCanvas(vc, video);

    status.textContent = 'Configuration de Face ID...';
    status.className = 'face-status loading';

    if (!await this.loadModels()) { status.textContent = 'Erreur de configuration.'; status.className = 'face-status error'; return; }
    status.textContent = 'Démarrage de la caméra...';
    
    video.addEventListener('playing', () => {
      this.scanning = true;
      this._autoFlash(video);
      this._updateLoginDots(0);
      status.textContent = 'Positionnez votre visage dans le cadre';
      status.className = 'face-status scanning';
      // Timeout tips
      this._loginTimeoutId = setTimeout(() => {
        if (this.scanning && this._loginPoseIdx < this._loginPoses.length) {
          const statusEl = document.getElementById('face-status');
          if (statusEl) {
            statusEl.textContent = 'Améliorez l\'éclairage ambiant';
            statusEl.className = 'face-status error';
          }
        }
      }, 8000);
      this._loginScanLoop(video, status);
    }, { once: true });

    if (!await this.startCamera(video)) { status.textContent = 'Appareil photo indisponible.'; status.className = 'face-status error'; return; }
  },

  _injectCanvas(vc, video) {
    if (!vc) return;
    let canvas = document.getElementById('face-overlay-canvas');
    if (!canvas) {
      canvas = document.createElement('canvas');
      canvas.id = 'face-overlay-canvas';
      canvas.style.position = 'absolute';
      canvas.style.top = '0';
      canvas.style.left = '0';
      canvas.style.width = '100%';
      canvas.style.height = '100%';
      // Le miroir inversé a été supprimé ici
      canvas.style.objectFit = 'cover';
      canvas.style.zIndex = '10';
      canvas.style.pointerEvents = 'none';
      vc.appendChild(canvas);
    }
    // Set internal size to match video
    if (video) {
       video.addEventListener('loadedmetadata', () => {
         canvas.width = video.videoWidth;
         canvas.height = video.videoHeight;
       });
    }
  },

  _drawLandmarks(videoEl, detection) {
    // Les points visuels ont été retirés à la demande de l'utilisateur.
    // L'enregistrement vectoriel profond reste actif en arrière-plan sans UI superposée.
    return;
  },

  _injectLoginUI(vc) {
    if (!vc) return;
    // Lock icon
    if (!document.getElementById('face-lock')) {
      const lock = document.createElement('div');
      lock.id = 'face-lock';
      lock.className = 'face-lock-icon';
      lock.innerHTML = '<i class="fa-solid fa-lock"></i>';
      vc.appendChild(lock);
    }
    // Progress dots
    const statusEl = document.getElementById('face-status');
    if (statusEl && !document.getElementById('face-login-dots')) {
      const dots = document.createElement('div');
      dots.id = 'face-login-dots';
      dots.className = 'face-login-dots';
      dots.innerHTML = '<div class="face-login-dot" data-idx="0"></div>' +
        '<div class="face-login-dot" data-idx="1"></div>' +
        '<div class="face-login-dot" data-idx="2"></div>';
      statusEl.parentElement.insertBefore(dots, statusEl);
    }
    // Pose arrow (hidden initially)
    if (!document.getElementById('face-pose-arrow')) {
      const arrow = document.createElement('div');
      arrow.id = 'face-pose-arrow';
      arrow.className = 'face-pose-arrow';
      arrow.style.display = 'none';
      vc.appendChild(arrow);
    }
  },

  _updateLoginDots(activeIdx) {
    const dots = document.querySelectorAll('.face-login-dot');
    dots.forEach((d, i) => {
      d.classList.remove('active', 'done');
      if (i < activeIdx) d.classList.add('done');
      else if (i === activeIdx) d.classList.add('active');
    });
  },

  _showPoseArrow(pose) {
    const arrow = document.getElementById('face-pose-arrow');
    if (!arrow) return;
    if (pose && pose.name.includes('gauche')) {
      arrow.className = 'face-pose-arrow arrow-left';
      arrow.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
      arrow.style.display = '';
    } else if (pose && pose.name.includes('droite')) {
      arrow.className = 'face-pose-arrow arrow-right';
      arrow.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
      arrow.style.display = '';
    } else {
      arrow.style.display = 'none';
    }
  },

  async _loginScanLoop(video, statusEl) {
    if (!this.scanning) return;
    const det = await this.detectFaceFast(video);
    const vc = document.querySelector('.face-video-container');

    if (!det) {
      this._drawLandmarks(video, null);
      this._loginPoseHoldFrames = 0;
      this._noFaceFrames++;
      vc?.classList.remove('face-scanning-active');

      // Smart progressive messages based on context
      const elapsed = (Date.now() - this._loginStartTime) / 1000;

      if (this._noFaceFrames > 30 || elapsed > 12) {
        const brightness = this._measureBrightness(video);
        this._lastBrightness = brightness;
        if (brightness < 0.25) {
          this._setStatus(statusEl, '<i class="fa-solid fa-lightbulb"></i> Luminosité faible', 'face-status error', 800);
        } else if (brightness < 0.38) {
          this._setStatus(statusEl, '<i class="fa-solid fa-sun"></i> Améliorez l\'éclairage', 'face-status error', 800);
        } else {
          this._setStatus(statusEl, '<i class="fa-solid fa-user"></i> Positionnez votre visage', 'face-status error', 800);
        }
      } else if (this._noFaceFrames > 5) {
        this._setStatus(statusEl, 'Recherche du visage...', 'face-status error', 500);
      } else {
        this._setStatus(statusEl, 'Positionnez votre visage dans le cadre', 'face-status scanning', 200);
      }

      await new Promise(r => setTimeout(r, 60));
      if (this.scanning) requestAnimationFrame(() => this._loginScanLoop(video, statusEl));
      return;
    }

    // Face detected — reset no-face counter + visual feedback
    this._noFaceFrames = 0;
    vc?.classList.add('face-scanning-active');
    this._drawLandmarks(video, det);

    // Check face position (size + centering)
    const pos = this._analyzeFacePosition(det, video);

    // Track EAR baseline (eyes open average) for blink calibration
    const ear = this._computeEAR(det.landmarks);
    if (ear > 0.15) {
      this._earBaseline = ((this._earBaseline * this._earSamples) + ear) / (this._earSamples + 1);
      this._earSamples++;
    }

    const currentPose = this._loginPoses[this._loginPoseIdx];
    if (!currentPose) return;

    const angles = this._computeAngles(det.landmarks);
    const poseOk = currentPose.check(angles);

    if (poseOk) {
      this._loginPoseHoldFrames++;

      if (this._loginPoseHoldFrames >= 1) {
        // Clear lock since we captured
        this._statusLockUntil = 0;

        // Capture descriptor
        const desc = Array.from(det.descriptor);
        this._loginDescriptors.push(desc);
        this._loginPoseHoldFrames = 0;
        this._loginPoseIdx++;
        this._updateLoginDots(this._loginPoseIdx);

        if (this._loginPoseIdx >= this._loginPoses.length) {
          // All captures done — send to server
          this.scanning = false;
          if (this._loginTimeoutId) { clearTimeout(this._loginTimeoutId); this._loginTimeoutId = null; }
          vc?.classList.remove('face-scanning-active');

          // Proceed to verification
          this._finishLoginScan(statusEl);
          return;
        }

        this._setStatus(statusEl, 'Analyse en cours...', 'face-status scanning');
        await new Promise(r => setTimeout(r, 80));
      }
    } else {
      this._loginPoseHoldFrames = 0;
      // Specific guidance when face is detected but wrong angle
      if (pos.offCenter) {
        this._setStatus(statusEl, '<i class="fa-solid fa-crosshairs"></i> Placez votre visage dans le cadre', 'face-status scanning', 500);
      } else {
        this._setStatus(statusEl, 'Positionnez votre visage dans le cadre', 'face-status scanning', 300);
      }
    }

    await new Promise(r => setTimeout(r, 40));
    if (this.scanning) requestAnimationFrame(() => this._loginScanLoop(video, statusEl));
  },

  // Finalize login scan — all captures verified
  _finishLoginScan(statusEl) {
    document.querySelectorAll('.face-login-dot').forEach(d => { d.classList.remove('active'); d.classList.add('done'); });
    statusEl.textContent = 'Vérification...';
    statusEl.className = 'face-status success';
    document.querySelector('.face-video-container')?.classList.add('face-detected');
    this._doFaceLogin(this._loginDescriptors, statusEl);
  },

  // ============================================================
  //  REGISTER — iPhone-style guided multi-angle (ZERO timers)
  // ============================================================
  async openFaceRegister() {
    this._regMode = true;
    this._regDescriptors = [];
    this._regPoseIdx = 0;
    this._regPoseCount = 0;
    this._regPoseHoldFrames = 0;
    this._flashPhaseActive = false;
    this._flashCaptures = 0;
    this._flashPoseIdx = 0;
    this._flashPoseCount = 0;

    const modal = document.getElementById('face-modal');
    const video = document.getElementById('face-video');
    const status = document.getElementById('face-status');
    if (!modal || !video) return;

    modal.classList.add('active');
    this._setupRegisterUI();
    const vc = document.querySelector('.face-video-container');
    this._injectCanvas(vc, video);

    status.textContent = 'Configuration de Face ID...';
    status.className = 'face-status loading';

    if (!await this.loadModels()) { status.textContent = 'Erreur de configuration.'; status.className = 'face-status error'; return; }

    video.addEventListener('playing', () => {
      this.scanning = true;
      this._autoFlash(video);
      status.textContent = this._regPoses[0].icon + ' ' + this._regPoses[0].name;
      status.className = 'face-status scanning';
      this._guidedScanLoop(video, status);
    }, { once: true });

    if (!await this.startCamera(video)) { status.textContent = 'Appareil photo indisponible.'; status.className = 'face-status error'; return; }
  },

  _setupRegisterUI() {
    const vc = document.querySelector('.face-video-container');
    vc.classList.add('face-circle-mode');
    const sf = vc.querySelector('.face-scan-frame');
    if (sf) sf.style.display = 'none';
    const sl = vc.querySelector('#face-scan-line');
    if (sl) sl.style.display = 'none';

    let ring = document.getElementById('face-progress-ring');
    if (!ring) {
      ring = document.createElement('div');
      ring.className = 'face-progress-wrapper';
      ring.id = 'face-progress-ring';
      ring.innerHTML = '<svg viewBox="0 0 240 240" class="face-ring-svg">' +
        '<circle cx="120" cy="120" r="108" class="face-ring-bg"/>' +
        '<circle cx="120" cy="120" r="108" class="face-ring-fill" id="face-ring-fill"/>' +
        '</svg>';
      vc.parentElement.insertBefore(ring, vc);
    }
    // Move video INSIDE ring wrapper for perfect centering
    ring.appendChild(vc);
    ring.style.display = '';

    let phases = document.getElementById('face-reg-phases');
    if (!phases) {
      phases = document.createElement('div');
      phases.className = 'face-reg-phases';
      phases.id = 'face-reg-phases';
      phases.innerHTML =
        '<div class="face-phase active" id="fphase-1"><span class="phase-dot">1</span> Visage</div>' +
        '<div class="face-phase-line" id="fphase-line1"></div>' +
        '<div class="face-phase" id="fphase-2"><span class="phase-dot">2</span> Rotations</div>' +
        '<div class="face-phase-line" id="fphase-line2"></div>' +
        '<div class="face-phase" id="fphase-3"><span class="phase-dot">3</span> Eclairage</div>' +
        '<div class="face-phase-line" id="fphase-line3"></div>' +
        '<div class="face-phase" id="fphase-4"><span class="phase-dot"><i class="fa-solid fa-check"></i></span> Termine</div>';
      const statusEl = document.getElementById('face-status');
      statusEl.parentElement.insertBefore(phases, statusEl);
    }
    phases.style.display = '';
    document.querySelectorAll('.face-phase').forEach(p => p.classList.remove('active', 'done'));
    document.querySelectorAll('.face-phase-line').forEach(l => l.classList.remove('done'));
    document.getElementById('fphase-1')?.classList.add('active');

    this._updateRing(0);
  },

  _updateRing(pct) {
    const fill = document.getElementById('face-ring-fill');
    if (!fill) return;
    const circ = 2 * Math.PI * 108;
    fill.style.strokeDasharray = circ;
    fill.style.strokeDashoffset = circ * (1 - pct);
  },

  _setPhase(n) {
    if (n >= 2) {
      document.getElementById('fphase-1')?.classList.remove('active');
      document.getElementById('fphase-1')?.classList.add('done');
      document.getElementById('fphase-line1')?.classList.add('done');
      document.getElementById('fphase-2')?.classList.add('active');
    }
    if (n >= 3) {
      document.getElementById('fphase-2')?.classList.remove('active');
      document.getElementById('fphase-2')?.classList.add('done');
      document.getElementById('fphase-line2')?.classList.add('done');
      document.getElementById('fphase-3')?.classList.add('active');
    }
    if (n >= 4) {
      document.getElementById('fphase-3')?.classList.remove('active');
      document.getElementById('fphase-3')?.classList.add('done');
      document.getElementById('fphase-line3')?.classList.add('done');
      document.getElementById('fphase-4')?.classList.add('active', 'done');
    }
  },

  // ============================================================
  //  UI Helper: Update status with optional lock duration
  //  Prevents message flickering by locking the text for X ms
  // ============================================================
  _setStatus(el, html, className, lockMs = 0) {
    const now = Date.now();
    if (this._statusLockUntil && now < this._statusLockUntil) return;
    el.innerHTML = html;
    el.className = className;
    if (lockMs > 0) this._statusLockUntil = now + lockMs;
  },

  // ============================================================
  //  GUIDED SCAN LOOP — ZERO TIMER pose validation
  //  Stays stuck until real head angle is confirmed for HOLD_FRAMES
  // ============================================================
  async _guidedScanLoop(video, statusEl) {
    if (!this.scanning) return;

    const normalCaptures = this._regPoses.reduce((s, p) => s + p.count, 0);
    const flashTotal = this._roomBright ? this._flashPoses.reduce((s, p) => s + p.count, 0) : 0;
    const totalCaptures = normalCaptures + flashTotal;
    const det = await this.detectFace(video);
    const vc = document.querySelector('.face-video-container');

    if (!det) {
      this._drawLandmarks(video, null);
      this._regPoseHoldFrames = 0; // reset hold counter
      vc?.classList.remove('face-scanning-active');
      this._setStatus(statusEl, 'Visage non détecté', 'face-status error', 500);
      await new Promise(r => setTimeout(r, 150));
      if (this.scanning) requestAnimationFrame(() => this._guidedScanLoop(video, statusEl));
      return;
    }

    vc?.classList.add('face-scanning-active');
    this._drawLandmarks(video, det);

    // Distances
    const pos = this._analyzeFacePosition(det, video);

    const currentPose = this._regPoses[this._regPoseIdx];
    if (!currentPose) return;

    const angles = this._computeAngles(det.landmarks);
    const poseOk = currentPose.check(angles);

    // Debug: log angles
    console.log(`[FaceAuth] Yaw: ${angles.yaw}° Pitch: ${angles.pitch}° | Need: ${currentPose.name} | OK: ${poseOk} | Hold: ${this._regPoseHoldFrames}/${this.HOLD_FRAMES}`);

    if (poseOk) {
      this._regPoseHoldFrames++;
      this._statusLockUntil = 0; // Clear lock

      // Clean hold indicator
      this._setStatus(statusEl, currentPose.icon + ' ' + currentPose.name, 'face-status scanning');

      if (this._regPoseHoldFrames >= this.HOLD_FRAMES) {
        // Pose confirmed! Capture descriptor
        const desc = Array.from(det.descriptor);

        // Diversity check for non-center poses
        const minDist = this._regDescriptors.length > 0
          ? Math.min(...this._regDescriptors.map(d => this._eucDist(desc, d)))
          : 999;
        const diversityOk = this._regPoseIdx === 0 ? true : (minDist > 0.1);

        if (diversityOk) {
          this._regDescriptors.push(desc);
          this._regPoseCount++;
          this._regPoseHoldFrames = 0;

          const captured = this._regDescriptors.length;
          this._updateRing(captured / totalCaptures);

          this._setStatus(statusEl, '<i class="fa-solid fa-check"></i> ' + currentPose.name + ' ✓', 'face-status success');

          if (this._regPoseCount >= currentPose.count) {
            // Move to next pose
            this._regPoseIdx++;
            this._regPoseCount = 0;
            this._regPoseHoldFrames = 0;

            if (this._regPoseIdx >= this._regPoses.length) {
              // Phase 3: flash captures if room is bright
              if (this._roomBright && !this._flashPhaseActive) {
                this._startFlashPhase(video, statusEl);
                return;
              }
              // ALL DONE
              this.scanning = false;
              this._updateRing(1);
              this._setPhase(4);
              this._setStatus(statusEl, '<i class="fa-solid fa-circle-check"></i> Face ID est configuré.', 'face-status success');
              document.querySelector('.face-video-container')?.classList.add('face-detected');
              await new Promise(r => setTimeout(r, 800));
              await this._doFaceRegister(this._regDescriptors, statusEl);
              return;
            }

            if (this._regPoseIdx >= 1) this._setPhase(2);
            const nextPose = this._regPoses[this._regPoseIdx];
            this._setStatus(statusEl, nextPose.icon + ' ' + nextPose.name, 'face-status scanning');
          }
          // Brief pause after successful capture
          await new Promise(r => setTimeout(r, 400));
        } else {
          this._regPoseHoldFrames = 0;
          this._setStatus(statusEl, currentPose.icon + ' Positionnez votre visage', 'face-status scanning');
        }
      }
    } else {
      // Wrong pose — reset hold counter, guide user
      this._regPoseHoldFrames = 0;
      this._setStatus(statusEl, currentPose.icon + ' ' + currentPose.name, 'face-status scanning');
    }

    await new Promise(r => setTimeout(r, 100));
    if (this.scanning) requestAnimationFrame(() => this._guidedScanLoop(video, statusEl));
  },

  // ============================================================
  //  PHASE 3 — Flash capture for lighting robustness
  //  Only runs when room is bright (adds flash-lit descriptors)
  // ============================================================
  _startFlashPhase(video, statusEl) {
    this._flashPhaseActive = true;
    this._flashCaptures = 0;
    this._flashPoseIdx = 0;
    this._flashPoseCount = 0;
    this._regPoseHoldFrames = 0;
    this._setPhase(3);

    // Force flash ON
    this._flashForced = true;
    this._showFlash();
    this._setFlashBtnState(true);

    statusEl.innerHTML = '<i class="fa-solid fa-lightbulb"></i> Activation du flash studio...';
    statusEl.className = 'face-status scanning';

    // Wait for flash + camera auto-exposure to stabilize
    setTimeout(() => {
      const pose = this._flashPoses[0];
      statusEl.innerHTML = pose.icon + ' ' + pose.name;
      this._flashScanLoop(video, statusEl);
    }, 1500);
  },

  async _flashScanLoop(video, statusEl) {
    if (!this.scanning) return;

    const normalCaptures = this._regPoses.reduce((s, p) => s + p.count, 0);
    const flashTotal = this._flashPoses.reduce((s, p) => s + p.count, 0);
    const totalCaptures = normalCaptures + flashTotal;
    const det = await this.detectFace(video);
    const vc = document.querySelector('.face-video-container');

    if (!det) {
      this._drawLandmarks(video, null);
      this._regPoseHoldFrames = 0;
      vc?.classList.remove('face-scanning-active');
      this._setStatus(statusEl, 'Visage non détecté', 'face-status error', 500);
      await new Promise(r => setTimeout(r, 150));
      if (this.scanning) requestAnimationFrame(() => this._flashScanLoop(video, statusEl));
      return;
    }

    vc?.classList.add('face-scanning-active');
    this._drawLandmarks(video, det);

    // Distances
    const pos = this._analyzeFacePosition(det, video);
    if (pos.tooFar) {
      this._regPoseHoldFrames = 0;
      await new Promise(r => setTimeout(r, 60));
      if (this.scanning) requestAnimationFrame(() => this._flashScanLoop(video, statusEl));
      return;
    }
    if (pos.tooClose) {
      this._regPoseHoldFrames = 0;
      await new Promise(r => setTimeout(r, 60));
      if (this.scanning) requestAnimationFrame(() => this._flashScanLoop(video, statusEl));
      return;
    }

    const currentPose = this._flashPoses[this._flashPoseIdx];
    if (!currentPose) return;

    const angles = this._computeAngles(det.landmarks);
    const poseOk = currentPose.check(angles);

    if (poseOk) {
      this._regPoseHoldFrames++;
      this._statusLockUntil = 0;

      this._setStatus(statusEl, currentPose.icon + ' ' + currentPose.name, 'face-status scanning');

      if (this._regPoseHoldFrames >= this.HOLD_FRAMES) {
        const desc = Array.from(det.descriptor);
        this._regDescriptors.push(desc);
        this._flashCaptures++;
        this._flashPoseCount++;
        this._regPoseHoldFrames = 0;

        const captured = normalCaptures + this._flashCaptures;
        this._updateRing(captured / totalCaptures);

        this._setStatus(statusEl, '<i class="fa-solid fa-check"></i> ' + currentPose.name + ' ✓', 'face-status success', 400);

        if (this._flashPoseCount >= currentPose.count) {
          // Move to next flash pose
          this._flashPoseIdx++;
          this._flashPoseCount = 0;
          this._regPoseHoldFrames = 0;

          if (this._flashPoseIdx >= this._flashPoses.length) {
            // Phase 3 complete!
            this.scanning = false;
            this._updateRing(1);
            this._setPhase(4);
            this._setStatus(statusEl, '<i class="fa-solid fa-circle-check"></i> Face ID est configuré.', 'face-status success');
            document.querySelector('.face-video-container')?.classList.add('face-detected');
            await new Promise(r => setTimeout(r, 800));
            await this._doFaceRegister(this._regDescriptors, statusEl);
            return;
          }

          const nextPose = this._flashPoses[this._flashPoseIdx];
          this._setStatus(statusEl, nextPose.icon + ' ' + nextPose.name, 'face-status scanning');
        }
        await new Promise(r => setTimeout(r, 400));
      }
    } else {
      this._regPoseHoldFrames = 0;
      this._setStatus(statusEl, currentPose.icon + ' ' + currentPose.name, 'face-status scanning');
    }

    await new Promise(r => setTimeout(r, 100));
    if (this.scanning) requestAnimationFrame(() => this._flashScanLoop(video, statusEl));
  },

  // ============================================================
  //  Send LOGIN descriptors (multi-scan consensus)
  // ============================================================
  async _doFaceLogin(descriptors, statusEl) {
    try {
      statusEl.textContent = 'Déverrouillage...';
      statusEl.className = 'face-status loading';
      const res = await fetch(App.apiUrl('Controller/FaceAuthController.php'), {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'face-login', descriptors: descriptors })
      });
      const text = await res.text();
      let result;
      try { result = JSON.parse(text.substring(text.indexOf('{'))); } catch (e) { throw new Error('Reponse invalide'); }

      if (result.success) {
        // Unlock animation + sound
        this._playSuccessSound();
        const lock = document.getElementById('face-lock');
        if (lock) { lock.classList.add('unlocked'); lock.innerHTML = '<i class="fa-solid fa-lock-open"></i>'; }
        statusEl.innerHTML = '<i class="fa-solid fa-check-circle"></i> Déverrouillé';
        statusEl.className = 'face-status success';
        this._hideFlash();
        const dbUser = result.user;
        const user = {
          id: dbUser.id, email: dbUser.email, role: dbUser.role, status: dbUser.status,
          name: ((dbUser.prenom || '') + ' ' + (dbUser.nom || '')).trim(),
          phone: dbUser.telephone, createdAt: dbUser.created_at,
          university: dbUser.ecole || '', ecole: dbUser.ecole || '',
          annee: dbUser.annee_etude || '', quartier: dbUser.quartier || '',
          nomEntreprise: dbUser.nom_entreprise || '', description: dbUser.description || '',
          linkedin: dbUser.linkedin || '', instagram: dbUser.instagram || '',
          facebook: dbUser.facebook || '', twitter: dbUser.twitter || '',
          github: dbUser.github || '', secteur_activite: dbUser.secteur_activite || '',
          site_web: dbUser.site_web || '', prenom: dbUser.prenom || '', nom: dbUser.nom || ''
        };
        if (typeof App !== 'undefined') App.setCurrentUser(user);
        setTimeout(() => {
          this.closeModal();
          const buildPath = (path) => (
            typeof window.caremealPath === 'function'
              ? window.caremealPath(path)
              : `/${String(path || '').replace(/^\/+/, '')}`
          );
          const paths = {
            student: buildPath('View/FrontOffice/feed.php'),
            partner: buildPath('View/FrontOffice/feed.php'),
            admin: buildPath('View/BackOffice/admin/dashboard.php'),
          };
          window.location.href = paths[user.role] || buildPath('View/FrontOffice/index.php');
        }, 800);
      } else {
        // Smart error messages based on server response
        const msg = result.message || 'Visage non reconnu';
        if (msg.includes('Trop de tentatives')) {
          statusEl.innerHTML = '<i class="fa-solid fa-clock"></i> ' + msg;
        } else if (msg.includes('Confiance')) {
          statusEl.innerHTML = '<i class="fa-solid fa-shield-halved"></i> Échec — Améliorez l\'éclairage';
        } else if (msg.includes('ambigue')) {
          statusEl.innerHTML = '<i class="fa-solid fa-users"></i> ' + msg;
        } else {
          statusEl.innerHTML = '<i class="fa-solid fa-face-frown"></i> Visage non reconnu';
        }
        statusEl.className = 'face-status error';
        setTimeout(() => {
          this._loginDescriptors = [];
          this._loginPoseIdx = 0;
          this._loginPoseHoldFrames = 0;
          this._blinkDetected = false;
          this._eyeWasOpen = true;
          this._loginStartTime = Date.now();
          this._noFaceFrames = 0;
          document.querySelector('.face-video-container')?.classList.remove('face-detected');
          // Reset premium UI
          const lock = document.getElementById('face-lock');
          if (lock) { lock.classList.remove('unlocked'); lock.innerHTML = '<i class="fa-solid fa-lock"></i>'; }
          this._updateLoginDots(0);
          this.scanning = true;
          statusEl.textContent = 'Positionnez votre visage dans le cadre';
          statusEl.className = 'face-status scanning';
          this._loginScanLoop(document.getElementById('face-video'), statusEl);
        }, 1200);
      }
    } catch (err) { statusEl.textContent = 'Erreur: ' + err.message; statusEl.className = 'face-status error'; }
  },

  // ============================================================
  //  Send REGISTER descriptors (multi-angle array)
  // ============================================================
  async _doFaceRegister(descriptors, statusEl) {
    try {
      statusEl.textContent = 'Enregistrement en cours...';
      statusEl.className = 'face-status loading';
      const currentUser = (typeof App !== 'undefined') ? App.getCurrentUser() : null;
      const userId = currentUser ? currentUser.id : null;

      const res = await fetch(App.apiUrl('Controller/FaceAuthController.php'), {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'save-face', user_id: userId, descriptors: descriptors })
      });
      const text = await res.text();
      let result;
      try { result = JSON.parse(text.substring(text.indexOf('{'))); } catch (e) { throw new Error('Reponse invalide'); }

      if (result.success) {
        statusEl.innerHTML = '<i class="fa-solid fa-check-circle"></i> Configuration terminée';
        statusEl.className = 'face-status success';
        this._hideFlash();
        const badge = document.getElementById('face-reg-status');
        if (badge) { badge.innerHTML = '<i class="fa-solid fa-check-circle"></i> Visage enregistre'; badge.className = 'face-reg-badge active'; }
        const regBtn = document.getElementById('btn-register-face');
        if (regBtn) regBtn.innerHTML = '<i class="fa-solid fa-rotate"></i> Re-enregistrer';
        const delBtn = document.getElementById('btn-remove-face');
        if (delBtn) delBtn.style.display = 'inline-flex';
        setTimeout(() => this.closeModal(), 2000);
      } else { statusEl.textContent = result.message; statusEl.className = 'face-status error'; }
    } catch (err) { statusEl.textContent = 'Erreur: ' + err.message; statusEl.className = 'face-status error'; }
  },

  // ============================================================
  //  Remove face
  // ============================================================
  async removeFace() {
    const currentUser = (typeof App !== 'undefined') ? App.getCurrentUser() : null;
    try {
      const res = await fetch(App.apiUrl('Controller/FaceAuthController.php'), {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'remove-face', user_id: currentUser?.id })
      });
      const text = await res.text();
      let result;
      try { result = JSON.parse(text.substring(text.indexOf('{'))); } catch (e) { return; }
      if (result.success) {
        const badge = document.getElementById('face-reg-status');
        if (badge) { badge.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Aucun visage'; badge.className = 'face-reg-badge inactive'; }
        const regBtn = document.getElementById('btn-register-face');
        if (regBtn) regBtn.innerHTML = '<i class="fa-solid fa-camera"></i> Enregistrer mon visage';
        const delBtn = document.getElementById('btn-remove-face');
        if (delBtn) delBtn.style.display = 'none';
      }
    } catch (e) { console.error(e); }
  },

  async checkFaceStatus() {
    const currentUser = (typeof App !== 'undefined') ? App.getCurrentUser() : null;
    if (!currentUser?.id) return;
    try {
      const res = await fetch(App.apiUrl('Controller/FaceAuthController.php'), {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'check-face', user_id: currentUser.id })
      });
      const text = await res.text();
      let result;
      try { result = JSON.parse(text.substring(text.indexOf('{'))); } catch (e) { return; }
      if (result.success && result.has_face) {
        const badge = document.getElementById('face-reg-status');
        if (badge) { badge.innerHTML = '<i class="fa-solid fa-check-circle"></i> Visage enregistre'; badge.className = 'face-reg-badge active'; }
        const regBtn = document.getElementById('btn-register-face');
        if (regBtn) regBtn.innerHTML = '<i class="fa-solid fa-rotate"></i> Re-enregistrer';
        const delBtn = document.getElementById('btn-remove-face');
        if (delBtn) delBtn.style.display = 'inline-flex';
      }
    } catch (e) { console.error(e); }
  },

  // ============================================================
  //  STUDIO LIGHT — Détection automatique de luminosité
  //  Seuil : luminosité < 0.45 → pièce sombre → flash ON
  //           luminosité ≥ 0.45 → pièce lumineuse → flash OFF
  //  _flashForced : null=auto | true=forcé ON | false=forcé OFF
  // ============================================================

  // Analyse ~1s de vidéo (3 frames) pour une mesure stable
  // Détermine aussi _roomBright pour décider si Phase 3 flash est nécessaire
  async _autoFlash(videoEl) {
    this._injectFlashButton();

    // Attendre que la caméra finisse son auto-exposition (important sur laptop)
    await new Promise(r => setTimeout(r, 800));

    // Prendre 4 mesures espacées pour une moyenne stable
    const samples = [];
    for (let i = 0; i < 4; i++) {
      samples.push(this._measureBrightness(videoEl));
      await new Promise(r => setTimeout(r, 150));
    }
    // Ignorer la valeur min et max, moyenner les 2 du milieu (robuste aux pics)
    samples.sort((a, b) => a - b);
    const brightness = (samples[1] + samples[2]) / 2;

    // Stocker pour décision Phase 3
    this._roomBright = brightness >= 0.42;

    console.log(`[FaceAuth] Luminosité ambiante: ${Math.round(brightness * 100)}% | Pièce ${this._roomBright ? 'lumineuse (Phase 3 prévue)' : 'sombre (Phase 3 skip)'}`);

    // Si l'utilisateur a déjà forcé un état, on le respecte
    if (this._flashForced === true) { this._showFlash(); this._setFlashBtnState(true); return; }
    if (this._flashForced === false) { this._setFlashBtnState(false); return; }

    if (brightness < 0.42) {
      console.log('[FaceAuth] Pièce sombre → Studio Light activé');
      this._showFlash();
      this._setFlashBtnState(true);
    } else {
      console.log('[FaceAuth] Pièce lumineuse → pas de flash');
      this._setFlashBtnState(false);
    }
  },

  // Bascule manuelle du flash (bouton utilisateur)
  _toggleFlash() {
    const overlay = document.getElementById('face-modal');
    const isActive = overlay && overlay.classList.contains('flash-active');
    if (isActive) {
      this._flashForced = false;
      this._hideFlash();
      this._setFlashBtnState(false);
    } else {
      this._flashForced = true;
      this._showFlash();
      this._setFlashBtnState(true);
    }
  },

  // Injecte le bouton Flash dans la modale (une seule fois)
  _injectFlashButton() {
    if (document.getElementById('flash-toggle-btn')) return;
    const modalCard = document.querySelector('#face-modal .face-modal');
    if (!modalCard) return;
    const btn = document.createElement('button');
    btn.id = 'flash-toggle-btn';
    btn.className = 'flash-toggle-btn';
    btn.title = 'Activer / désactiver le flash studio';
    btn.innerHTML = '<i class="fa-solid fa-lightbulb"></i>';
    btn.onclick = () => this._toggleFlash();
    modalCard.appendChild(btn);
  },

  _setFlashBtnState(on) {
    const btn = document.getElementById('flash-toggle-btn');
    if (!btn) return;
    btn.classList.toggle('flash-btn-on', on);
    btn.title = on ? 'Désactiver le flash' : 'Activer le flash';
  },

  // ============================================================
  //  STUDIO LIGHT — Softbox ambré, vignettage inverse
  // ============================================================
  _showFlash() {
    const overlay = document.getElementById('face-modal');
    if (!overlay) return;

    overlay.classList.remove('flash-dimmed', 'flash-expo-low');

    setTimeout(() => {
      overlay.classList.add('flash-active');
      this._startExposureControl();
    }, 420);

    this._flashDimTimer = setTimeout(() => {
      overlay.classList.add('flash-dimmed');
      this._updateIntensityBar(0.65);
    }, 3500 + 420);
  },

  _measureBrightness(videoEl) {
    try {
      const canvas = document.createElement('canvas');
      canvas.width = 64; canvas.height = 48;
      const ctx = canvas.getContext('2d');
      ctx.drawImage(videoEl, 0, 0, 64, 48);
      // Analyser uniquement la zone centrale (40x30) pour ignorer le fond sombre
      const cx = 12, cy = 9, cw = 40, ch = 30;
      const data = ctx.getImageData(cx, cy, cw, ch).data;
      let sum = 0;
      for (let i = 0; i < data.length; i += 4) {
        // Formule luminance perceptuelle (ITU-R BT.601)
        sum += 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
      }
      return sum / (data.length / 4) / 255;
    } catch (e) { return 0.5; }
  },

  _startExposureControl() {
    const overlay = document.getElementById('face-modal');
    const video = document.getElementById('face-video');
    if (!overlay || !video) return;

    let bar = document.getElementById('flash-intensity-bar');
    if (!bar) {
      bar = document.createElement('div');
      bar.id = 'flash-intensity-bar';
      bar.className = 'flash-intensity-bar';
      bar.innerHTML =
        '<span>Studio</span>' +
        '<div class="fib-track"><div class="fib-fill" id="fib-fill"></div></div>' +
        '<span id="fib-pct">90%</span>';
      const modal = overlay.querySelector('.face-modal');
      if (modal) modal.appendChild(bar);
    }
    bar.style.display = 'flex';
    this._updateIntensityBar(0.9);

    this._exposureInterval = setInterval(() => {
      if (!this.scanning) return;
      const brightness = this._measureBrightness(video);
      if (brightness > 0.78) {
        overlay.classList.add('flash-expo-low');
        overlay.classList.remove('flash-dimmed');
        this._updateIntensityBar(0.45);
      } else if (brightness > 0.65) {
        overlay.classList.add('flash-dimmed');
        overlay.classList.remove('flash-expo-low');
        this._updateIntensityBar(0.65);
      } else {
        overlay.classList.remove('flash-dimmed', 'flash-expo-low');
        this._updateIntensityBar(0.9);
      }
    }, 800);
  },

  _updateIntensityBar(pct) {
    const fill = document.getElementById('fib-fill');
    const label = document.getElementById('fib-pct');
    if (fill) fill.style.width = Math.round(pct * 100) + '%';
    if (label) label.textContent = Math.round(pct * 100) + '%';
  },

  _hideFlash() {
    const overlay = document.getElementById('face-modal');
    if (overlay) overlay.classList.remove('flash-active', 'flash-dimmed', 'flash-expo-low');
    if (this._flashDimTimer) { clearTimeout(this._flashDimTimer); this._flashDimTimer = null; }
    if (this._exposureInterval) { clearInterval(this._exposureInterval); this._exposureInterval = null; }
    const bar = document.getElementById('flash-intensity-bar');
    if (bar) bar.style.display = 'none';
  },

  closeModal() {
    this.scanning = false;
    this._hideFlash();
    this._flashForced = null;
    // Retire le bouton flash pour qu'il soit recréé proprement à la prochaine ouverture
    const fb = document.getElementById('flash-toggle-btn');
    if (fb) fb.remove();
    this.stopCamera();
    this._regDescriptors = [];
    this._regPoseIdx = 0;
    this._regPoseCount = 0;
    this._regPoseHoldFrames = 0;
    this._regMode = false;
    this._flashPhaseActive = false;
    this._flashCaptures = 0;
    this._flashPoseIdx = 0;
    this._flashPoseCount = 0;
    this._roomBright = false;
    this._noFaceFrames = 0;
    this._lastBrightness = 0.5;
    this._loginDescriptors = [];
    this._loginPoses = [];
    this._loginPoseIdx = 0;
    this._loginPoseHoldFrames = 0;
    this._blinkDetected = false;
    this._eyeWasOpen = true;
    this._earBaseline = 0;
    this._earSamples = 0;
    if (this._loginTimeoutId) { clearTimeout(this._loginTimeoutId); this._loginTimeoutId = null; }
    const modal = document.getElementById('face-modal');
    if (modal) modal.classList.remove('active');
    // Clean up premium login UI
    const lockEl = document.getElementById('face-lock');
    if (lockEl) lockEl.remove();
    const dotsEl = document.getElementById('face-login-dots');
    if (dotsEl) dotsEl.remove();
    const arrowEl = document.getElementById('face-pose-arrow');
    if (arrowEl) arrowEl.remove();
    // Move video back out of ring wrapper into the modal
    const vc = document.querySelector('.face-video-container');
    const ring = document.getElementById('face-progress-ring');
    if (vc && ring && ring.contains(vc)) {
      ring.parentElement.insertBefore(vc, ring.nextSibling);
    }
    if (vc) { vc.classList.remove('face-detected', 'face-circle-mode', 'face-scanning-active'); }
    const sf = vc ? vc.querySelector('.face-scan-frame') : null;
    if (sf) sf.style.display = '';
    const sl = document.getElementById('face-scan-line');
    if (sl) sl.style.display = 'none';
  }
};

document.addEventListener('keydown', (e) => { if (e.key === 'Escape') FaceAuth.closeModal(); });



