<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Inscrivez votre restaurant ou boulangerie sur CareMeal et luttez contre le gaspillage alimentaire.">
  <title>Inscription Partenaire - CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/projet2a22/css/main.css">
  <link rel="stylesheet" href="/projet2a22/css/auth.css">
</head>
<body>
  <div class="auth-page">
    <!-- Left: Visual -->
    <div class="auth-visual">
      <div class="shape shape-1"></div>
      <div class="shape shape-2"></div>
      <div class="shape shape-3"></div>
      <div class="auth-visual-content">
        <div style="font-size:5rem;margin-bottom:24px;animation:float 3s ease-in-out infinite;">
              <i class="fa-solid fa-store"></i></div>
        <h2>Devenez partenaire CareMeal</h2>
        <p>Réduisez vos pertes, attirez de nouveaux clients et agissez pour la planète.</p>
        <div style="display:flex;flex-direction:column;gap:16px;margin-top:48px;text-align:left;">
          <div style="display:flex;align-items:center;gap:12px;">
            <span style="font-size:1.5rem;">
              <span class="input-icon"><i class="fa-solid fa-chart-line"></i></span>
            <span style="color:var(--color-text);font-size:0.9rem;">Réduisez vos invendus de 30-50%</span>
          </div>
          <div style="display:flex;align-items:center;gap:12px;">
            <span style="font-size:1.5rem;">
              <span class="input-icon"><i class="fa-solid fa-users"></i></span>
            <span style="color:var(--color-text);font-size:0.9rem;">Touchez des centaines d'étudiants</span>
          </div>
          <div style="display:flex;align-items:center;gap:12px;">
            <span style="font-size:1.5rem;">
              <span class="input-icon"><i class="fa-solid fa-trophy"></i></span>
            <span style="color:var(--color-text);font-size:0.9rem;">Gagnez en visibilité et en réputation</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Right: Form -->
    <div class="auth-form-side">
      <div class="auth-form-container">
        <div class="auth-form-header">
          <a href="/projet2a22/View/FrontOffice/index.php" class="brand-link">
            <div class="brand-icon"><img src="/projet2a22/assets/logo.png" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;"></div>
            Care<span style="color:var(--color-primary)">Meal</span>
          </a>
          <h1>Devenir Partenaire <i class="fa-solid fa-handshake"></i></h1>
          <p>Remplissez les informations de votre établissement.</p>
        </div>

        <!-- Step indicators added for consistency -->
        <div class="step-indicators">
          <div class="step-dot active"></div>
          <div class="step-line"></div>
          <div class="step-dot"></div>
        </div>

        <div id="auth-alert" class="auth-alert" style="display:none;"></div>

        <form id="register-partner-form" onsubmit="Auth.handlePartnerRegister(event)">
          <!-- STEP 1 -->
          <div id="step-partner-1" class="form-step active">
            <div class="form-group">
              <label for="partner-name">Nom de l'établissement <span class="required">*</span></label>
              <div class="input-wrapper">
                <input type="text" id="partner-name" class="form-input" placeholder="Ex: La Baguette Dorée">
              <span class="input-icon"><i class="fa-solid fa-house"></i></span>
              </div>
              <div class="form-error" id="partner-name-error"></div>
            </div>

            <div class="form-group">
              <label for="partner-type">Type d'établissement <span class="required">*</span></label>
              <div class="input-wrapper">
                <select id="partner-type" class="form-select">Sélectionnez le type</option>
                  <option value="restaurant">
              <i class="fa-solid fa-utensils"></i> Restaurant</option>
                  <option value="boulangerie">
              <i class="fa-solid fa-bread-slice"></i> Boulangerie</option>
                  <option value="cafeteria">
              <i class="fa-solid fa-mug-hot"></i> Cafétéria</option>
                  <option value="epicerie">
              <i class="fa-solid fa-cart-shopping"></i> épicerie</option>
                  <option value="traiteur">
              <i class="fa-solid fa-user-chef"></i> Traiteur</option>
                  <option value="autre">
              <i class="fa-solid fa-box"></i> Autre</option>
                </select>
                <span class="input-icon">
              <span class="input-icon"><i class="fa-solid fa-clipboard-list"></i></span>
                <span class="select-arrow">--</span>
              </div>
              <div class="form-error" id="partner-type-error"></div>
            </div>

            <div class="form-group">
              <label for="partner-address">Adresse complète <span class="required">*</span></label>
              <div class="input-wrapper">
                <input type="text" id="partner-address" class="form-input" placeholder="Rue, ville, code postal">
              <span class="input-icon"><i class="fa-solid fa-location-dot"></i></span>
              </div>
              <div class="form-error" id="partner-address-error"></div>
            </div>

            <hr style="border:none; border-top:1px solid rgba(255,255,255,0.1); margin: 20px 0;">
            <p style="color:var(--color-primary); font-weight:bold; margin-bottom:15px; font-size:0.9rem;"><i class="fa-solid fa-user-tie" style="margin-right:8px;"></i>Contact Gérant</p>

            <div class="form-row">
              <div class="form-group">
                <label for="partner-nom">Nom du responsable <span class="required">*</span></label>
                <div class="input-wrapper">
                  <input type="text" id="partner-nom" class="form-input" placeholder="Ex: Dupont">
                  <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                </div>
                <div class="form-error" id="partner-nom-error"></div>
              </div>

              <div class="form-group">
                <label for="partner-prenom">Prénom <span class="required">*</span></label>
                <div class="input-wrapper">
                  <input type="text" id="partner-prenom" class="form-input" placeholder="Ex: Jean">
                  <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                </div>
                <div class="form-error" id="partner-prenom-error"></div>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="partner-email">Email professionnel <span class="required">*</span></label>
                <div class="input-wrapper">
                  <input type="text" id="partner-email" class="form-input" placeholder="contact@...">
              <span class="input-icon"><i class="fa-solid fa-envelope"></i></span>
                </div>
                <div class="form-error" id="partner-email-error"></div>
              </div>

              <div class="form-group">
                <label for="partner-phone">Téléphone <span class="required">*</span></label>
                <div class="input-wrapper">
                  <input type="text" id="partner-phone" class="form-input" placeholder="+216 XX XXX XXX">
              <span class="input-icon"><i class="fa-solid fa-mobile-screen"></i></span>
                </div>
                <div class="form-error" id="partner-phone-error"></div>
              </div>
            </div>

            <button type="button" class="btn btn-primary btn-full btn-lg" style="margin-top:10px" onclick="Auth.nextPartnerStep()">
              Continuer <i class="fa-solid fa-arrow-right" style="margin-left: 6px;"></i>
            </button>
          </div>

          <!-- STEP 2 -->
          <div id="step-partner-2" class="form-step">
            <div class="form-group">
              <label for="partner-password">Mot de passe <span class="required">*</span></label>
              <div class="input-wrapper">
                <input type="password" id="partner-password" class="form-input" placeholder="Minimum 6 caractères">
              <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                <button type="button" class="password-toggle">
              <i class="fa-solid fa-eye"></i></button>
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
              <textarea id="partner-description" class="form-textarea" placeholder="Décrivez votre établissement en quelques mots..." rows="3"></textarea>
            </div>

            <div class="form-group">
              <label>Logo de l'établissement</label>
              <div class="file-upload" id="logo-upload">
                <span class="upload-icon">
              <span class="input-icon"><i class="fa-solid fa-arrow-up-from-bracket"></i></span>
                <p class="upload-text"><span>Cliquez pour uploader</span> ou glissez-déposez</p>
                <p style="font-size:0.7rem;color:var(--color-text-muted);margin-top:4px;">PNG, JPG • Max 2 MB</p>
                <input type="file" id="logo-file" accept="image/*">
              </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 20px;">
              <button type="button" class="btn btn-secondary btn-lg" style="flex: 1;" onclick="Auth.prevPartnerStep()">
                <i class="fa-solid fa-arrow-left"></i> Retour
              </button>
              <button type="submit" class="btn btn-primary btn-lg" style="flex: 2;" id="btn-register-partner">
                <span>
              <span class="input-icon"><i class="fa-solid fa-rocket"></i></span> Soumettre candidature
              </button>
            </div>

            <p style="text-align:center;font-size:0.75rem;color:var(--color-text-muted);margin-top:12px;">
              Votre compte sera validé par un administrateur sous 24-48h.
            </p>
          </div>
        </form>

        <div class="auth-footer">
          <p>Déjà  un compte ? <a href="/projet2a22/View/FrontOffice/login.php">Se connecter</a></p>
          <p style="margin-top:8px;">Vous êtes étudiant ? <a href="/projet2a22/View/FrontOffice/register-student.php">Créer un compte étudiant</a></p>
        </div>
      </div>
    </div>
  </div>

  <script src="/projet2a22/js/app.js"></script>
  <script src="/projet2a22/js/auth.js"></script>
  </body>
</html>



