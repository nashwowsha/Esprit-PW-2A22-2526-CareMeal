<?php require_once dirname(__DIR__, 2) . '/config/app.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Inscrivez votre restaurant ou boulangerie sur CareMeal et luttez contre le gaspillage alimentaire.">
  <title>Inscription Partenaire - CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="<?= htmlspecialchars(caremeal_path('css/main.css'), ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(caremeal_path('css/auth.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
  <div class="auth-page">

    <!-- Left: Visual -->
    <div class="auth-visual">
      <div class="auth-visual-pattern"></div>
      <div class="shape shape-1"></div>
      <div class="shape shape-2"></div>
      <div class="shape shape-3"></div>
      <div class="auth-visual-content">
        <div style="width:88px;height:88px;background:rgba(255,255,255,0.18);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 28px;border:2px solid rgba(255,255,255,0.3);">
          <i class="fa-solid fa-store" style="font-size:2.2rem;color:#fff;"></i>
        </div>
        <h2 style="font-size:1.75rem;font-weight:800;color:#fff;margin:0 0 12px;line-height:1.2;">Devenez partenaire CareMeal</h2>
        <p style="font-size:0.9rem;color:rgba(255,255,255,0.8);line-height:1.6;margin:0 0 40px;max-width:260px;margin-left:auto;margin-right:auto;">Réduisez vos pertes, attirez de nouveaux clients et agissez pour la planète.</p>
        <div style="display:flex;flex-direction:column;gap:16px;text-align:left;border-top:1px solid rgba(255,255,255,0.2);padding-top:28px;">
          <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:36px;height:36px;background:rgba(255,255,255,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fa-solid fa-chart-line" style="color:#fff;font-size:0.9rem;"></i>
            </div>
            <span style="color:rgba(255,255,255,0.9);font-size:0.85rem;font-weight:500;">Réduisez vos invendus de 30-50%</span>
          </div>
          <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:36px;height:36px;background:rgba(255,255,255,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fa-solid fa-users" style="color:#fff;font-size:0.9rem;"></i>
            </div>
            <span style="color:rgba(255,255,255,0.9);font-size:0.85rem;font-weight:500;">Touchez des centaines d'étudiants</span>
          </div>
          <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:36px;height:36px;background:rgba(255,255,255,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fa-solid fa-trophy" style="color:#fff;font-size:0.9rem;"></i>
            </div>
            <span style="color:rgba(255,255,255,0.9);font-size:0.85rem;font-weight:500;">Gagnez en visibilité et en réputation</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Right: Form -->
    <div class="auth-form-side">
      <div class="auth-form-container">
        <div class="auth-form-header">
          <a href="<?= htmlspecialchars(caremeal_path('View/FrontOffice/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="brand-link">
            <div class="brand-icon"><img src="<?= htmlspecialchars(caremeal_path('assets/logo.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Logo" style="max-width:100%;max-height:100%;object-fit:contain;"></div>
            Care<span style="color:var(--color-primary)">Meal</span>
          </a>
          <h1>Devenir Partenaire</h1>
          <p>Remplissez les informations de votre établissement.</p>
        </div>

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
              <label for="partner-name">NOM DE L'ETABLISSEMENT <span class="required">*</span></label>
              <div class="input-wrapper">
                <span class="input-icon"><i class="fa-solid fa-house"></i></span>
                <input type="text" id="partner-name" class="form-input" placeholder="Ex: La Baguette Dorée">
              </div>
              <div class="form-error" id="partner-name-error"></div>
            </div>

            <div class="form-group">
              <label for="partner-type">TYPE D'ETABLISSEMENT <span class="required">*</span></label>
              <div class="input-wrapper">
                <span class="input-icon"><i class="fa-solid fa-clipboard-list"></i></span>
                <select id="partner-type" class="form-select">
                  <option value="">Sélectionnez le type</option>
                  <option value="restaurant">Restaurant</option>
                  <option value="boulangerie">Boulangerie</option>
                  <option value="cafeteria">Cafeteria</option>
                  <option value="epicerie">Epicerie</option>
                  <option value="traiteur">Traiteur</option>
                  <option value="autre">Autre</option>
                </select>
                <span class="select-arrow"><i class="fa-solid fa-chevron-down"></i></span>
              </div>
              <div class="form-error" id="partner-type-error"></div>
            </div>

            <div class="form-group">
              <label for="partner-address">ADRESSE COMPLETE <span class="required">*</span></label>
              <div class="input-wrapper">
                <span class="input-icon"><i class="fa-solid fa-location-dot"></i></span>
                <input type="text" id="partner-address" class="form-input" placeholder="Rue, ville, code postal">
              </div>
              <div class="form-error" id="partner-address-error"></div>
            </div>

            <hr style="border:none;border-top:1px solid #E2E8F0;margin:20px 0;">
            <p style="color:var(--color-primary);font-weight:700;margin-bottom:14px;font-size:0.88rem;">
              <i class="fa-solid fa-user-tie" style="margin-right:8px;"></i>Contact Gérant
            </p>

            <div class="form-row">
              <div class="form-group">
                <label for="partner-nom">NOM DU RESPONSABLE <span class="required">*</span></label>
                <div class="input-wrapper">
                  <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                  <input type="text" id="partner-nom" class="form-input" placeholder="Ex: Dupont">
                </div>
                <div class="form-error" id="partner-nom-error"></div>
              </div>
              <div class="form-group">
                <label for="partner-prenom">PRENOM <span class="required">*</span></label>
                <div class="input-wrapper">
                  <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                  <input type="text" id="partner-prenom" class="form-input" placeholder="Ex: Jean">
                </div>
                <div class="form-error" id="partner-prenom-error"></div>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="partner-email">EMAIL PROFESSIONNEL <span class="required">*</span></label>
                <div class="input-wrapper">
                  <span class="input-icon"><i class="fa-solid fa-envelope"></i></span>
                  <input type="text" id="partner-email" class="form-input" placeholder="partner@caremeal.tn">
                </div>
                <div class="form-error" id="partner-email-error"></div>
              </div>
              <div class="form-group">
                <label for="partner-phone">TELEPHONE <span class="required">*</span></label>
                <div class="input-wrapper">
                  <span class="input-icon"><i class="fa-solid fa-mobile-screen"></i></span>
                  <input type="text" id="partner-phone" class="form-input" placeholder="+216 XX XXX XXX">
                </div>
                <div class="form-error" id="partner-phone-error"></div>
              </div>
            </div>

            <button type="button" class="btn btn-primary btn-full btn-lg" style="margin-top:10px;" onclick="Auth.nextPartnerStep()">
              Continuer &rarr;
            </button>
          </div>

          <!-- STEP 2 -->
          <div id="step-partner-2" class="form-step">

            <div class="form-group">
              <label for="partner-password">MOT DE PASSE <span class="required">*</span></label>
              <div class="input-wrapper">
                <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                <input type="password" id="partner-password" class="form-input" placeholder="Minimum 6 caractères">
                <button type="button" class="password-toggle" data-target="partner-password" aria-label="Afficher le mot de passe"><i class="fa-solid fa-eye"></i></button>
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
              <label for="partner-description">DESCRIPTION COURTE</label>
              <textarea id="partner-description" class="form-textarea" placeholder="Decrivez votre établissement en quelques mots..." rows="3"></textarea>
            </div>

            <div class="form-group">
              <label>LOGO DE L'ETABLISSEMENT</label>
              <div class="file-upload" id="logo-upload">
                <span class="upload-icon"><i class="fa-solid fa-arrow-up-from-bracket"></i></span>
                <p class="upload-text"><span>Cliquez pour uploader</span> ou glissez-déposez</p>
                <p style="font-size:0.7rem;color:var(--color-text-muted);margin-top:4px;">PNG, JPG - Max 2 MB</p>
                <input type="file" id="logo-file" accept="image/*">
              </div>
            </div>

            <div style="display:flex;gap:12px;margin-top:20px;">
              <button type="button" class="btn btn-secondary btn-lg" style="flex:1;" onclick="Auth.prevPartnerStep()">
                Retour
              </button>
              <button type="submit" class="btn btn-primary btn-lg" style="flex:2;" id="btn-register-partner">
                Soumettre candidature
              </button>
            </div>

            <p style="text-align:center;font-size:0.75rem;color:var(--color-text-muted);margin-top:12px;">
              Votre compte sera validé par un administrateur sous 24-48h.
            </p>
          </div>

        </form>

        <div class="auth-footer">
          <p>Déjà un compte ? <a href="<?= htmlspecialchars(caremeal_path('View/FrontOffice/login.php'), ENT_QUOTES, 'UTF-8') ?>">Se connecter</a></p>
          <p style="margin-top:8px;">Vous êtes étudiant ? <a href="<?= htmlspecialchars(caremeal_path('View/FrontOffice/register-student.php'), ENT_QUOTES, 'UTF-8') ?>">Créer un compte étudiant</a></p>
        </div>
      </div>
    </div>

  </div>

  <script src="<?= htmlspecialchars(caremeal_path('js/app.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
  <script src="<?= htmlspecialchars(caremeal_path('js/auth.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
  <script src="<?= htmlspecialchars(caremeal_path('js/auth-fallback.js'), ENT_QUOTES, 'UTF-8') ?>?v=20260510-1"></script>
</body>
</html>
