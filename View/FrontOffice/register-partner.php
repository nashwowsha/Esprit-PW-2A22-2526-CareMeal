ÃƒÂ¯Ã‚Â¿Ã‚Â½<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Inscrivez votre restaurant ou boulangerie sur CareMeal et luttez contre le gaspillage alimentaire.">
  <title>Inscription Partenaire ÃƒÂ¯Ã‚Â¿Ã‚Â½ CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/main.css">
  <link rel="stylesheet" href="css/auth.css">
</head>
<body>
  <div class="auth-page">
    <!-- Left: Visual -->
    <div class="auth-visual">
      <div class="shape shape-1"></div>
      <div class="shape shape-2"></div>
      <div class="shape shape-3"></div>
      <div class="auth-visual-content">
        <div style="font-size:5rem;margin-bottom:24px;animation:float 3s ease-in-out infinite;"><i class="fa-solid fa-store"></i></div>
        <h2>Devenez partenaire CareMeal</h2>
        <p>RÃƒÆ’Ã‚Â©duisez vos pertes, attirez de nouveaux clients et agissez pour la planÃƒÆ’Ã‚Â¨te.</p>
        <div style="display:flex;flex-direction:column;gap:16px;margin-top:48px;text-align:left;">
          <div style="display:flex;align-items:center;gap:12px;">
            <span style="font-size:1.5rem;"><i class="fa-solid fa-chart-line"></i></span>
            <span style="color:var(--color-text);font-size:0.9rem;">RÃƒÆ’Ã‚Â©duisez vos invendus de 30-50%</span>
          </div>
          <div style="display:flex;align-items:center;gap:12px;">
            <span style="font-size:1.5rem;"><i class="fa-solid fa-users"></i></span>
            <span style="color:var(--color-text);font-size:0.9rem;">Touchez des centaines d'ÃƒÆ’Ã‚Â©tudiants</span>
          </div>
          <div style="display:flex;align-items:center;gap:12px;">
            <span style="font-size:1.5rem;"><i class="fa-solid fa-trophy"></i></span>
            <span style="color:var(--color-text);font-size:0.9rem;">Gagnez en visibilitÃƒÆ’Ã‚Â© et en rÃƒÆ’Ã‚Â©putation</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Right: Form -->
    <div class="auth-form-side">
      <div class="auth-form-container">
        <div class="auth-form-header">
          <a href="index.html" class="brand-link">
            <div class="brand-icon"><img src="assets/logo.png" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;"></div>
            Care<span style="color:var(--color-primary)">Meal</span>
          </a>
          <h1>Devenir Partenaire <i class="fa-solid fa-handshake"></i></h1>
          <p>Remplissez les informations de votre ÃƒÆ’Ã‚Â©tablissement.</p>
        </div>

        <div id="auth-alert" class="auth-alert" style="display:none;"></div>

        <form id="register-partner-form" onsubmit="Auth.handlePartnerRegister(event)">
          <!-- STEP 1 -->
          <div id="step-1" class="form-step active">
            <div class="form-group">
              <label for="partner-name">Nom de l'ÃƒÆ’Ã‚Â©tablissement <span class="required">*</span></label>
              <div class="input-wrapper">
                <input type="text" id="partner-name" class="form-input" placeholder="Ex: La Baguette DorÃƒÆ’Ã‚Â©e" required>
                <span class="input-icon"><i class="fa-solid fa-house"></i></span>
              </div>
              <div class="form-error" id="partner-name-error"></div>
            </div>

            <div class="form-group">
              <label for="partner-type">Type d'ÃƒÆ’Ã‚Â©tablissement <span class="required">*</span></label>
              <div class="input-wrapper">
                <select id="partner-type" class="form-select" required>
                  <option value="">SÃƒÆ’Ã‚Â©lectionnez le type</option>
                  <option value="restaurant"><i class="fa-solid fa-utensils"></i> Restaurant</option>
                  <option value="boulangerie"><i class="fa-solid fa-bread-slice"></i> Boulangerie</option>
                  <option value="cafeteria"><i class="fa-solid fa-mug-hot"></i> CafÃƒÆ’Ã‚Â©tÃƒÆ’Ã‚Â©ria</option>
                  <option value="epicerie"><i class="fa-solid fa-cart-shopping"></i> ÃƒÂ¯Ã‚Â¿Ã‚Â½0picerie</option>
                  <option value="traiteur"><i class="fa-solid fa-user-chef"></i> Traiteur</option>
                  <option value="autre"><i class="fa-solid fa-box"></i> Autre</option>
                </select>
                <span class="input-icon"><i class="fa-solid fa-clipboard-list"></i></span>
                <span class="select-arrow">ÃƒÂ¯Ã‚Â¿Ã‚Â½ÃƒÂ¯Ã‚Â¿Ã‚Â½</span>
              </div>
              <div class="form-error" id="partner-type-error"></div>
            </div>

            <div class="form-group">
              <label for="partner-address">Adresse complÃƒÆ’Ã‚Â¨te <span class="required">*</span></label>
              <div class="input-wrapper">
                <input type="text" id="partner-address" class="form-input" placeholder="Rue, ville, code postal" required>
                <span class="input-icon"><i class="fa-solid fa-location-dot"></i></span>
              </div>
              <div class="form-error" id="partner-address-error"></div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="partner-email">Email professionnel <span class="required">*</span></label>
                <div class="input-wrapper">
                  <input type="email" id="partner-email" class="form-input" placeholder="contact@..." required>
                  <span class="input-icon"><i class="fa-solid fa-envelope"></i></span>
                </div>
                <div class="form-error" id="partner-email-error"></div>
              </div>

              <div class="form-group">
                <label for="partner-phone">TÃƒÆ’Ã‚Â©lÃƒÆ’Ã‚Â©phone <span class="required">*</span></label>
                <div class="input-wrapper">
                  <input type="tel" id="partner-phone" class="form-input" placeholder="+216 XX XXX XXX" required>
                  <span class="input-icon"><i class="fa-solid fa-mobile-screen"></i></span>
                </div>
                <div class="form-error" id="partner-phone-error"></div>
              </div>
            </div>

            <button type="button" class="btn btn-primary btn-full btn-lg" style="margin-top:10px" onclick="nextStep(1)">
              Continuer <i class="fa-solid fa-arrow-right" style="margin-left: 6px;"></i>
            </button>
          </div>

          <!-- STEP 2 -->
          <div id="step-2" class="form-step">
            <div class="form-group">
              <label for="partner-password">Mot de passe <span class="required">*</span></label>
              <div class="input-wrapper">
                <input type="password" id="partner-password" class="form-input" placeholder="Minimum 6 caractÃƒÆ’Ã‚Â¨res" required>
                <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                <button type="button" class="password-toggle"><i class="fa-solid fa-eye"></i></button>
              </div>
              <div class="password-strength">
                <div class="strength-bar"></div>
                <div class="strength-bar"></div>
                <div class="strength-bar"></div>
                <div class="strength-bar"></div>
              </div>
              <div class="strength-text"></div>
              <div class="form-error" id="partner-password-error"></div>
            </div>

            <div class="form-group">
              <label for="partner-description">Description courte</label>
              <textarea id="partner-description" class="form-textarea" placeholder="DÃƒÆ’Ã‚Â©crivez votre ÃƒÆ’Ã‚Â©tablissement en quelques mots..." rows="3"></textarea>
            </div>

            <div class="form-group">
              <label>Logo de l'ÃƒÆ’Ã‚Â©tablissement</label>
              <div class="file-upload" id="logo-upload">
                <span class="upload-icon"><i class="fa-solid fa-arrow-up-from-bracket"></i></span>
                <p class="upload-text"><span>Cliquez pour uploader</span> ou glissez-dÃƒÆ’Ã‚Â©posez</p>
                <p style="font-size:0.7rem;color:var(--color-text-muted);margin-top:4px;">PNG, JPG Ã¢Ã‚Â¬Ã‚Â¢ Max 2 MB</p>
                <input type="file" id="logo-file" accept="image/*">
              </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 20px;">
              <button type="button" class="btn btn-secondary btn-lg" style="flex: 1;" onclick="prevStep(2)">
                <i class="fa-solid fa-arrow-left"></i> Retour
              </button>
              <button type="submit" class="btn btn-primary btn-lg" style="flex: 2;" id="btn-register-partner">
                <span><i class="fa-solid fa-rocket"></i></span> Soumettre candidature
              </button>
            </div>

            <p style="text-align:center;font-size:0.75rem;color:var(--color-text-muted);margin-top:12px;">
              Votre compte sera validÃƒÆ’Ã‚Â© par un administrateur sous 24-48h.
            </p>
          </div>
        </form>

        <div class="auth-footer">
          <p>DÃƒÆ’Ã‚Â©jÃƒÆ’Ã‚Â  un compte ? <a href="login.html">Se connecter</a></p>
          <p style="margin-top:8px;">Vous ÃƒÆ’Ã‚Âªtes ÃƒÆ’Ã‚Â©tudiant ? <a href="register-student.html">CrÃƒÆ’Ã‚Â©er un compte ÃƒÆ’Ã‚Â©tudiant</a></p>
        </div>
      </div>
    </div>
  </div>

  <script src="js/app.js"></script>
  <script src="js/auth.js"></script>
  <script>
    // Logique du formulaire multi-ÃƒÆ’Ã‚Â©tapes
    function nextStep(currentStep) {
      const stepContainer = document.getElementById('step-' + currentStep);
      const inputs = stepContainer.querySelectorAll('input[required], select[required]');
      let valid = true;
      
      // VÃƒÆ’Ã‚Â©rifier si les champs requis de l'ÃƒÆ’Ã‚Â©tape sont remplis
      inputs.forEach(input => {
        if (!input.checkValidity()) {
          input.reportValidity();
          valid = false;
        }
      });
      
      if (!valid) return;

      // Passage ÃƒÆ’Ã‚Â  l'ÃƒÆ’Ã‚Â©tape suivante
      document.getElementById('step-' + currentStep).classList.remove('active');
      document.getElementById('step-' + (currentStep + 1)).classList.add('active');
    }

    function prevStep(currentStep) {
      // Retour ÃƒÆ’Ã‚Â  l'ÃƒÆ’Ã‚Â©tape prÃƒÆ’Ã‚Â©cÃƒÆ’Ã‚Â©dente
      document.getElementById('step-' + currentStep).classList.remove('active');
      document.getElementById('step-' + (currentStep - 1)).classList.add('active');
    }
  </script>
</body>
</html>

