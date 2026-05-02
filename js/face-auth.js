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

  // Registration state
  _regDescriptors: [],
  _regMode: false,
  _regPoseIdx: 0,
  _regPoseCount: 0,
  _regPoseHoldFrames: 0, // consecutive frames holding correct pose

  // Login multi-scan state
  _loginDescriptors: [],
  _loginTarget: 3,

  // Detection configs — multiple fallbacks for low light / glasses
  _detectConfigs: [
    { inputSize: 512, scoreThreshold: 0.15 },
    { inputSize: 416, scoreThreshold: 0.12 },
    { inputSize: 320, scoreThreshold: 0.08 },
    { inputSize: 224, scoreThreshold: 0.05 }
  ],

  // ============================================================
  //  POSE DEFINITIONS — strict angle thresholds, ZERO timers
  //  Each pose needs 3 consecutive valid frames to confirm
  // ============================================================
  HOLD_FRAMES: 3,

  _regPoses: [
    { name: 'Regardez droit devant',         icon: '😐', check: (a) => Math.abs(a.yaw) < 10  && Math.abs(a.pitch) < 12, count: 3 },
    { name: 'Tournez la tete a gauche',      icon: '👈', check: (a) => a.yaw > 18,  count: 2 },
    { name: 'Tournez la tete a droite',       icon: '👉', check: (a) => a.yaw < -18, count: 2 },
    { name: 'Levez legerement le menton',     icon: '👆', check: (a) => a.pitch < -10, count: 2 },
    { name: 'Baissez legerement le menton',   icon: '👇', check: (a) => a.pitch > 10,  count: 2 },
  ],

  // ============================================================
  //  Load face-api models
  // ============================================================
  async loadModels() {
    if (this.modelsLoaded) return true;
    try {
      const U = '/projet2a22/assets/models';
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
  //  Camera — high resolution for better recognition
  // ============================================================
  async startCamera(videoEl) {
    try {
      const s = await navigator.mediaDevices.getUserMedia({
        video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' }
      });
      videoEl.srcObject = s;
      this.videoStream = s;
      return true;
    } catch (e) { console.error('[FaceAuth]', e); return false; }
  },

  stopCamera() {
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
      } catch (e) {}
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
    const noseTip    = p[30]; // nose tip
    const noseBridge = p[27]; // top of nose bridge
    const lEyeOuter  = p[36]; // left eye outer corner
    const rEyeOuter  = p[45]; // right eye outer corner
    const chin       = p[8];  // chin bottom
    const lBrow      = p[19]; // left eyebrow outer
    const rBrow      = p[24]; // right eyebrow outer

    // === YAW (horizontal rotation) ===
    // Compare nose-to-left-eye distance vs nose-to-right-eye distance
    const dLeft  = Math.sqrt(Math.pow(noseTip.x - lEyeOuter.x, 2) + Math.pow(noseTip.y - lEyeOuter.y, 2));
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
  //  LOGIN — Multi-scan (captures 3 descriptors then sends all)
  // ============================================================
  async openFaceLogin() {
    this._regMode = false;
    this._loginDescriptors = [];
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

    status.textContent = 'Chargement des modeles IA...';
    status.className = 'face-status loading';

    if (!await this.loadModels()) { status.textContent = 'Erreur modeles.'; status.className = 'face-status error'; return; }
    status.textContent = 'Activation camera...';
    if (!await this.startCamera(video)) { status.textContent = 'Camera indisponible.'; status.className = 'face-status error'; return; }

    video.addEventListener('playing', () => {
      this.scanning = true;
      this._autoFlash(video);
      document.getElementById('face-scan-line').style.display = 'block';
      status.textContent = 'Placez votre visage dans le cadre...';
      status.className = 'face-status scanning';
      this._loginScanLoop(video, status);
    }, { once: true });
  },

  async _loginScanLoop(video, statusEl) {
    if (!this.scanning) return;
    const det = await this.detectFace(video);

    if (det) {
      const desc = Array.from(det.descriptor);
      this._loginDescriptors.push(desc);

      if (this._loginDescriptors.length >= this._loginTarget) {
        // Got 3 scans — send for consensus verification
        this.scanning = false;
        document.getElementById('face-scan-line').style.display = 'none';
        statusEl.textContent = 'Visage detecte ! Verification...';
        statusEl.className = 'face-status success';
        document.querySelector('.face-video-container')?.classList.add('face-detected');
        await this._doFaceLogin(this._loginDescriptors, statusEl);
        return;
      } else {
        statusEl.textContent = 'Analyse du visage... (' + this._loginDescriptors.length + '/' + this._loginTarget + ')';
      }
      // Small pause between captures for angle variance
      await new Promise(r => setTimeout(r, 300));
    }

    if (this.scanning) requestAnimationFrame(() => this._loginScanLoop(video, statusEl));
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

    const modal = document.getElementById('face-modal');
    const video = document.getElementById('face-video');
    const status = document.getElementById('face-status');
    if (!modal || !video) return;

    modal.classList.add('active');
    this._setupRegisterUI();

    status.textContent = 'Chargement de l\'IA...';
    status.className = 'face-status loading';

    if (!await this.loadModels()) { status.textContent = 'Erreur modeles.'; status.className = 'face-status error'; return; }
    if (!await this.startCamera(video)) { status.textContent = 'Camera indisponible.'; status.className = 'face-status error'; return; }

    video.addEventListener('playing', () => {
      this.scanning = true;
      this._autoFlash(video);
      status.textContent = this._regPoses[0].icon + ' ' + this._regPoses[0].name;
      status.className = 'face-status scanning';
      this._guidedScanLoop(video, status);
    }, { once: true });
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
        '<div class="face-phase active" id="fphase-1"><span class="phase-dot">1</span> Face de face</div>' +
        '<div class="face-phase-line" id="fphase-line1"></div>' +
        '<div class="face-phase" id="fphase-2"><span class="phase-dot">2</span> Rotation</div>' +
        '<div class="face-phase-line" id="fphase-line2"></div>' +
        '<div class="face-phase" id="fphase-3"><span class="phase-dot"><i class="fa-solid fa-check"></i></span> Termine</div>';
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
      document.getElementById('fphase-3')?.classList.add('active', 'done');
    }
  },

  // ============================================================
  //  GUIDED SCAN LOOP — ZERO TIMER pose validation
  //  Stays stuck until real head angle is confirmed for HOLD_FRAMES
  // ============================================================
  async _guidedScanLoop(video, statusEl) {
    if (!this.scanning) return;

    const totalCaptures = this._regPoses.reduce((s, p) => s + p.count, 0);
    const det = await this.detectFace(video);

    if (!det) {
      this._regPoseHoldFrames = 0; // reset hold counter
      statusEl.textContent = 'Visage non detecte — regardez la camera';
      statusEl.className = 'face-status error';
      await new Promise(r => setTimeout(r, 150));
      if (this.scanning) requestAnimationFrame(() => this._guidedScanLoop(video, statusEl));
      return;
    }

    const currentPose = this._regPoses[this._regPoseIdx];
    if (!currentPose) return;

    const angles = this._computeAngles(det.landmarks);
    const poseOk = currentPose.check(angles);

    // Debug: log angles
    console.log(`[FaceAuth] Yaw: ${angles.yaw}° Pitch: ${angles.pitch}° | Need: ${currentPose.name} | OK: ${poseOk} | Hold: ${this._regPoseHoldFrames}/${this.HOLD_FRAMES}`);

    if (poseOk) {
      this._regPoseHoldFrames++;

      // Show hold progress
      statusEl.innerHTML = currentPose.icon + ' ' + currentPose.name +
        ' — maintenir... (' + this._regPoseHoldFrames + '/' + this.HOLD_FRAMES + ')';
      statusEl.className = 'face-status scanning';

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

          statusEl.innerHTML = '<i class="fa-solid fa-check"></i> ' + currentPose.name +
            ' — ' + this._regPoseCount + '/' + currentPose.count;
          statusEl.className = 'face-status success';

          if (this._regPoseCount >= currentPose.count) {
            // Move to next pose
            this._regPoseIdx++;
            this._regPoseCount = 0;
            this._regPoseHoldFrames = 0;

            if (this._regPoseIdx >= this._regPoses.length) {
              // ALL DONE
              this.scanning = false;
              this._updateRing(1);
              this._setPhase(3);
              statusEl.innerHTML = '<i class="fa-solid fa-circle-check"></i> Visage capture sous tous les angles !';
              statusEl.className = 'face-status success';
              document.querySelector('.face-video-container')?.classList.add('face-detected');
              await new Promise(r => setTimeout(r, 800));
              await this._doFaceRegister(this._regDescriptors, statusEl);
              return;
            }

            if (this._regPoseIdx >= 1) this._setPhase(2);
            const nextPose = this._regPoses[this._regPoseIdx];
            statusEl.textContent = nextPose.icon + ' ' + nextPose.name + '...';
            statusEl.className = 'face-status scanning';
          }
          // Brief pause after successful capture
          await new Promise(r => setTimeout(r, 400));
        } else {
          this._regPoseHoldFrames = 0;
          statusEl.textContent = currentPose.name + '... (bougez un peu plus)';
          statusEl.className = 'face-status scanning';
        }
      }
    } else {
      // Wrong pose — reset hold counter, guide user
      this._regPoseHoldFrames = 0;
      statusEl.textContent = currentPose.icon + ' ' + currentPose.name + '...';
      statusEl.className = 'face-status scanning';
    }

    await new Promise(r => setTimeout(r, 100));
    if (this.scanning) requestAnimationFrame(() => this._guidedScanLoop(video, statusEl));
  },

  // ============================================================
  //  Send LOGIN descriptors (multi-scan consensus)
  // ============================================================
  async _doFaceLogin(descriptors, statusEl) {
    try {
      statusEl.textContent = 'Verification securisee en cours...';
      statusEl.className = 'face-status loading';
      const res = await fetch('/projet2a22/Controller/FaceAuthController.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'face-login', descriptors: descriptors })
      });
      const text = await res.text();
      let result;
      try { result = JSON.parse(text.substring(text.indexOf('{'))); } catch (e) { throw new Error('Reponse invalide'); }

      if (result.success) {
        statusEl.innerHTML = '<i class="fa-solid fa-check-circle"></i> ' + result.message +
          ' (Confiance: ' + result.confidence + '%)';
        statusEl.className = 'face-status success';
        this._hideFlash();
        const dbUser = result.user;
        const user = {
          id: dbUser.id, email: dbUser.email, role: dbUser.role, status: dbUser.status,
          name: ((dbUser.prenom||'') + ' ' + (dbUser.nom||'')).trim(),
          phone: dbUser.telephone, createdAt: dbUser.created_at,
          university: dbUser.ecole||'', ecole: dbUser.ecole||'',
          annee: dbUser.annee_etude||'', quartier: dbUser.quartier||'',
          nomEntreprise: dbUser.nom_entreprise||'', description: dbUser.description||'',
          linkedin: dbUser.linkedin||'', instagram: dbUser.instagram||'',
          facebook: dbUser.facebook||'', twitter: dbUser.twitter||'',
          github: dbUser.github||'', secteur_activite: dbUser.secteur_activite||'',
          site_web: dbUser.site_web||'', prenom: dbUser.prenom||'', nom: dbUser.nom||''
        };
        if (typeof App !== 'undefined') App.setCurrentUser(user);
        setTimeout(() => {
          this.closeModal();
          const paths = { student: '/projet2a22/View/FrontOffice/student/dashboard.php', partner: '/projet2a22/View/FrontOffice/partner/dashboard.php', admin: '/projet2a22/View/BackOffice/admin/dashboard.php' };
          window.location.href = paths[user.role] || '/projet2a22/View/FrontOffice/index.php';
        }, 1500);
      } else {
        statusEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> ' + result.message;
        statusEl.className = 'face-status error';
        setTimeout(() => {
          this._loginDescriptors = [];
          document.querySelector('.face-video-container')?.classList.remove('face-detected');
          this.scanning = true;
          statusEl.textContent = 'Reessayez... Placez votre visage.';
          statusEl.className = 'face-status scanning';
          document.getElementById('face-scan-line').style.display = 'block';
          this._loginScanLoop(document.getElementById('face-video'), statusEl);
        }, 2500);
      }
    } catch (err) { statusEl.textContent = 'Erreur: ' + err.message; statusEl.className = 'face-status error'; }
  },

  // ============================================================
  //  Send REGISTER descriptors (multi-angle array)
  // ============================================================
  async _doFaceRegister(descriptors, statusEl) {
    try {
      statusEl.textContent = 'Enregistrement du visage...';
      statusEl.className = 'face-status loading';
      const currentUser = (typeof App !== 'undefined') ? App.getCurrentUser() : null;
      const userId = currentUser ? currentUser.id : null;

      const res = await fetch('/projet2a22/Controller/FaceAuthController.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'save-face', user_id: userId, descriptors: descriptors })
      });
      const text = await res.text();
      let result;
      try { result = JSON.parse(text.substring(text.indexOf('{'))); } catch (e) { throw new Error('Reponse invalide'); }

      if (result.success) {
        statusEl.innerHTML = '<i class="fa-solid fa-check-circle"></i> Visage enregistre avec succes ! (' + descriptors.length + ' angles)';
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
      const res = await fetch('/projet2a22/Controller/FaceAuthController.php', {
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
      const res = await fetch('/projet2a22/Controller/FaceAuthController.php', {
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
  async _autoFlash(videoEl) {
    this._injectFlashButton();

    // Si l'utilisateur a déjà forcé un état, on le respecte
    if (this._flashForced === true)  { this._showFlash(); return; }
    if (this._flashForced === false) { return; }

    // Attendre 3 frames pour que la caméra s'ajuste à la lumière ambiante
    await new Promise(r => setTimeout(r, 900));
    const b1 = this._measureBrightness(videoEl);
    await new Promise(r => setTimeout(r, 300));
    const b2 = this._measureBrightness(videoEl);
    const brightness = (b1 + b2) / 2;

    console.log(`[FaceAuth] Luminosité ambiante: ${Math.round(brightness * 100)}%`);

    if (brightness < 0.45) {
      // Pièce sombre → flash nécessaire
      console.log('[FaceAuth] Pièce sombre → Studio Light activé');
      this._showFlash();
      this._setFlashBtnState(true);
    } else {
      // Pièce lumineuse → pas de flash
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
      const data = ctx.getImageData(0, 0, 64, 48).data;
      let sum = 0;
      for (let i = 0; i < data.length; i += 4) {
        sum += 0.299 * data[i] + 0.587 * data[i+1] + 0.114 * data[i+2];
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
    this._loginDescriptors = [];
    const modal = document.getElementById('face-modal');
    if (modal) modal.classList.remove('active');
    // Move video back out of ring wrapper into the modal
    const vc = document.querySelector('.face-video-container');
    const ring = document.getElementById('face-progress-ring');
    if (vc && ring && ring.contains(vc)) {
      ring.parentElement.insertBefore(vc, ring.nextSibling);
    }
    if (vc) { vc.classList.remove('face-detected', 'face-circle-mode'); }
    const sf = vc ? vc.querySelector('.face-scan-frame') : null;
    if (sf) sf.style.display = '';
    const sl = document.getElementById('face-scan-line');
    if (sl) sl.style.display = 'none';
  }
};

document.addEventListener('keydown', (e) => { if (e.key === 'Escape') FaceAuth.closeModal(); });
