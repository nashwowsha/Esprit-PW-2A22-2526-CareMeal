<?php require_once dirname(__DIR__, 2) . '/config/app.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Créez votre compte étudiant CareMeal en quelques étapes et commencez à sauver des repas.">
  <title>Inscription étudiant - CareMeal</title>
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
          <i class="fa-solid fa-graduation-cap" style="font-size:2.2rem;color:#fff;"></i>
        </div>

        <h2 style="font-size:1.75rem;font-weight:800;color:#fff;margin:0 0 12px;line-height:1.2;">Pour les étudiants</h2>
        <p style="font-size:0.9rem;color:rgba(255,255,255,0.8);line-height:1.6;margin:0 0 40px;max-width:260px;margin-left:auto;margin-right:auto;">Repas de qualité à prix réduits, près de votre campus.</p>

        <div style="display:flex;flex-direction:column;gap:16px;text-align:left;border-top:1px solid rgba(255,255,255,0.2);padding-top:28px;">
          <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:36px;height:36px;background:rgba(255,255,255,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fa-solid fa-coins" style="color:#fff;font-size:0.9rem;"></i>
            </div>
            <span style="color:rgba(255,255,255,0.9);font-size:0.85rem;font-weight:500;">Économisez jusqu'à 60% sur vos repas</span>
          </div>
          <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:36px;height:36px;background:rgba(255,255,255,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fa-solid fa-seedling" style="color:#fff;font-size:0.9rem;"></i>
            </div>
            <span style="color:rgba(255,255,255,0.9);font-size:0.85rem;font-weight:500;">Agissez contre le gaspillage alimentaire</span>
          </div>
          <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:36px;height:36px;background:rgba(255,255,255,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fa-solid fa-star" style="color:#fff;font-size:0.9rem;"></i>
            </div>
            <span style="color:rgba(255,255,255,0.9);font-size:0.85rem;font-weight:500;">Gagnez des récompenses</span>
          </div>
        </div>

      </div>
    </div>

    <!-- Right: Form -->
    <div class="auth-form-side">
      <div class="auth-form-container">
        <div class="auth-form-header">
          <a href="<?= htmlspecialchars(caremeal_path('View/FrontOffice/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="brand-link">
            <div class="brand-icon"><img src="<?= htmlspecialchars(caremeal_path('assets/logo.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;"></div>
            Care<span style="color:var(--color-primary)">Meal</span>
          </a>
          <h1>Créer un compte</h1>
          <p>En 2 étapes, vous serez prêt à sauver des repas.</p>
        </div>

        <!-- Step indicators -->
        <div class="step-indicators">
          <div class="step-dot active"></div>
          <div class="step-line"></div>
          <div class="step-dot"></div>
        </div>

        <div id="auth-alert" class="auth-alert" style="display:none;"></div>

        <form id="register-student-form" onsubmit="Auth.handleStudentRegister(event)">
          <!-- Step 1: Identité -->
          <div class="form-step active" id="step-1">
            <p class="step-title">ÉTAPE 1 — Vos informations</p>

            <div class="form-group" style="display:flex; gap:16px;">
              <div style="flex:1;">
                <label for="reg-nom">NOM <span class="required">*</span></label>
                <div class="input-wrapper">
                  <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                  <input type="text" id="reg-nom" class="form-input" placeholder="Votre nom">
                </div>
                <div class="form-error" id="reg-nom-error"></div>
              </div>
              <div style="flex:1;">
                <label for="reg-prenom">PRÉNOM <span class="required">*</span></label>
                <div class="input-wrapper">
                  <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                  <input type="text" id="reg-prenom" class="form-input" placeholder="Votre prénom">
                </div>
                <div class="form-error" id="reg-prenom-error"></div>
              </div>
            </div>

            <div class="form-group">
              <label for="reg-email">EMAIL <span class="required">*</span></label>
              <div class="input-wrapper">
                <span class="input-icon"><i class="fa-solid fa-envelope"></i></span>
                <input type="text" id="reg-email" class="form-input" placeholder="votre@email.com">
              </div>
              <div class="form-error" id="reg-email-error"></div>
            </div>

            <div class="form-group">
              <label for="reg-password">MOT DE PASSE <span class="required">*</span></label>
              <div class="input-wrapper">
                <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                <input type="password" id="reg-password" class="form-input" placeholder="Minimum 6 caractères">
                <button type="button" class="password-toggle" data-target="reg-password" aria-label="Afficher le mot de passe"><i class="fa-solid fa-eye"></i></button>
              </div>
              <div class="password-strength">
                <div class="strength-bar"></div>
                <div class="strength-bar"></div>
                <div class="strength-bar"></div>
                <div class="strength-bar"></div>
              </div>
              <div class="strength-text"></div>
              <div class="form-error" id="reg-password-error"></div>
            </div>

            <div class="form-group">
              <label for="reg-confirm">CONFIRMER LE MOT DE PASSE <span class="required">*</span></label>
              <div class="input-wrapper">
                <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                <input type="password" id="reg-confirm" class="form-input" placeholder="Retapez votre mot de passe">
              </div>
              <div class="form-error" id="reg-confirm-error"></div>
            </div>

            <button type="button" class="btn btn-primary btn-full btn-lg" onclick="Auth.nextStep()">
              Continuer
            </button>
          </div>

          <!-- Step 2: Localisation -->
          <div class="form-step" id="step-2">
            <p class="step-title">ÉTAPE 2 — Votre campus</p>

            <div class="form-group">
              <label for="reg-university">UNIVERSITÉ / ÉCOLE <span class="required">*</span></label>
              <div class="input-wrapper">
                <span class="input-icon"><i class="fa-solid fa-school"></i></span>
                <select id="reg-university" class="form-select">
                  <option value="">Sélectionnez votre établissement</option>
                  <option value="ESPRIT">ESPRIT</option>
                  <option value="INSAT">INSAT</option>
                  <option value="ENIT">ENIT</option>
                  <option value="FST">FST - Faculté des Sciences de Tunis</option>
                  <option value="IHEC">IHEC Carthage</option>
                  <option value="ISTIC">ISTIC</option>
                  <option value="ULT">Université Libre de Tunis</option>
                  <option value="ENSI">ENSI</option>
                  <option value="ISG">ISG Tunis</option>
                  <option value="ESEN">ESEN Manouba</option>
                  <option value="autre">Autre</option>
                </select>
                <span class="select-arrow"><i class="fa-solid fa-chevron-down"></i></span>
              </div>
              <div class="form-error" id="reg-university-error"></div>
            </div>

            <div class="form-group" style="display:flex; gap:16px;">
              <div style="flex:1;">
                <label for="reg-telephone">TÉLÉPHONE <span class="required">*</span></label>
                <div class="input-wrapper">
                  <span class="input-icon"><i class="fa-solid fa-phone"></i></span>
                  <input type="text" id="reg-telephone" class="form-input" placeholder="Votre numéro">
                </div>
                <div class="form-error" id="reg-telephone-error"></div>
              </div>
              <div style="flex:1;">
                <label for="reg-annee">ANNÉE D'ÉTUDE <span class="required">*</span></label>
                <div class="input-wrapper">
                  <span class="input-icon"><i class="fa-solid fa-calendar"></i></span>
                  <select id="reg-annee" class="form-select">
                    <option value="">Sélectionnez</option>
                    <option value="1ere">1ère année</option>
                    <option value="2eme">2ème année</option>
                    <option value="3eme">3ème année</option>
                    <option value="Master 1">Master 1</option>
                    <option value="Master 2">Master 2</option>
                  </select>
                  <span class="select-arrow"><i class="fa-solid fa-chevron-down"></i></span>
                </div>
                <div class="form-error" id="reg-annee-error"></div>
              </div>
            </div>

            <div class="form-group">
              <label for="reg-quartier">QUARTIER / ZONE</label>
              <div class="input-wrapper">
                <span class="input-icon"><i class="fa-solid fa-location-dot"></i></span>
                <select id="reg-quartier" class="form-select">
                  <option value="">Sélectionnez votre zone</option>
                  <option value="Centre Ville">Centre Ville</option>
                  <option value="Lac 1">Lac 1</option>
                  <option value="Lac 2">Lac 2</option>
                  <option value="Ariana">Ariana</option>
                  <option value="Menzah">Menzah</option>
                  <option value="Manar">El Manar</option>
                  <option value="Marsa">La Marsa</option>
                  <option value="Bardo">Le Bardo</option>
                  <option value="Manouba">Manouba</option>
                  <option value="Ben Arous">Ben Arous</option>
                </select>
                <span class="select-arrow"><i class="fa-solid fa-chevron-down"></i></span>
              </div>
              <div class="form-error" id="reg-quartier-error"></div>
            </div>

            <div style="display:flex;gap:12px;margin-top:24px;">
              <button type="button" class="btn btn-secondary btn-lg" style="flex:1;" onclick="Auth.prevStep()">
                Retour
              </button>
              <button type="submit" class="btn btn-primary btn-lg" style="flex:2;" id="btn-register-student">
                Cr&eacute;er mon compte
              </button>
            </div>
          </div>
        </form>

        <div class="auth-footer">
          <p>Déjà un compte ? <a href="<?= htmlspecialchars(caremeal_path('View/FrontOffice/login.php'), ENT_QUOTES, 'UTF-8') ?>">Se connecter</a></p>
        </div>
      </div>
    </div>
  </div>

  <script src="<?= htmlspecialchars(caremeal_path('js/app.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
  <script src="<?= htmlspecialchars(caremeal_path('js/auth.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
  <script src="<?= htmlspecialchars(caremeal_path('js/auth-fallback.js'), ENT_QUOTES, 'UTF-8') ?>?v=20260510-1"></script>
</body>
</html>





