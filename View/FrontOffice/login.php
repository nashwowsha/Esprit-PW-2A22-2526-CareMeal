<?php require_once dirname(__DIR__, 2) . '/config/app.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Connectez-vous à CareMeal pour accéder à vos offres anti-gaspillage préférées.">
  <title>Connexion - CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="<?= htmlspecialchars(caremeal_path('css/main.css'), ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(caremeal_path('css/auth.css'), ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(caremeal_path('css/face-auth.css'), ENT_QUOTES, 'UTF-8') ?>">
  <?php require_once dirname(__DIR__, 2) . '/config/oauth.php'; ?>
  <meta name="google-client-id" content="<?= htmlspecialchars(GOOGLE_CLIENT_ID) ?>">
  <meta name="facebook-app-id" content="<?= htmlspecialchars(FACEBOOK_APP_ID) ?>">
  <!-- Google Identity Services -->
  <script src="https://accounts.google.com/gsi/client" async defer></script>
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

        <!-- Logo -->
        <div style="width:88px;height:88px;background:rgba(255,255,255,0.18);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 32px;border:2px solid rgba(255,255,255,0.3);">
          <img src="<?= htmlspecialchars(caremeal_path('assets/logo.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Logo" style="width:60px;height:60px;object-fit:contain;">
        </div>

        <h2 style="font-size:1.75rem;font-weight:800;color:#fff;margin:0 0 12px;line-height:1.2;">Bienvenue sur<br>CareMeal</h2>
        <p style="font-size:0.9rem;color:rgba(255,255,255,0.8);line-height:1.6;margin:0 0 48px;max-width:260px;margin-left:auto;margin-right:auto;">La plateforme anti-gaspillage alimentaire pour les étudiants.</p>

        <!-- 3 stats épurées -->
        <div style="display:flex;gap:0;justify-content:center;border-top:1px solid rgba(255,255,255,0.2);padding-top:32px;">
          <div style="flex:1;text-align:center;padding:0 12px;border-right:1px solid rgba(255,255,255,0.2);">
            <div style="font-size:1.4rem;margin-bottom:8px;"><i class="fa-solid fa-tag" style="color:#fff;"></i></div>
            <div style="font-size:0.72rem;font-weight:600;color:rgba(255,255,255,0.9);text-transform:uppercase;letter-spacing:.06em;">Prix réduits</div>
          </div>
          <div style="flex:1;text-align:center;padding:0 12px;border-right:1px solid rgba(255,255,255,0.2);">
            <div style="font-size:1.4rem;margin-bottom:8px;"><i class="fa-solid fa-seedling" style="color:#fff;"></i></div>
            <div style="font-size:0.72rem;font-weight:600;color:rgba(255,255,255,0.9);text-transform:uppercase;letter-spacing:.06em;">Éco-responsable</div>
          </div>
          <div style="flex:1;text-align:center;padding:0 12px;">
            <div style="font-size:1.4rem;margin-bottom:8px;"><i class="fa-solid fa-bolt" style="color:#fff;"></i></div>
            <div style="font-size:0.72rem;font-weight:600;color:rgba(255,255,255,0.9);text-transform:uppercase;letter-spacing:.06em;">Rapide</div>
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
          <h1>Bon retour !</h1>
          <p>Connectez-vous pour accéder à votre espace.</p>
        </div>

        <!-- Alert container -->
        <div id="auth-alert" class="auth-alert" style="display:none;"></div>

        <!-- Login Form -->
        <form id="login-form" onsubmit="Auth.handleLogin(event)">
          <div class="form-group">
            <label for="login-email">EMAIL <span class="required">*</span></label>
            <div class="input-wrapper">
              <span class="input-icon"><i class="fa-solid fa-envelope"></i></span>
              <input type="text" id="login-email" class="form-input" placeholder="votre@email.com" autocomplete="email">
            </div>
            <div class="form-error" id="login-email-error"></div>
          </div>

          <div class="form-group">
            <label for="login-password">MOT DE PASSE <span class="required">*</span></label>
            <div class="input-wrapper">
              <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
              <input type="password" id="login-password" class="form-input" placeholder="••••••••" autocomplete="current-password">
              <button type="button" class="password-toggle" data-target="login-password" aria-label="Afficher le mot de passe"><i class="fa-solid fa-eye"></i></button>
            </div>
            <div class="form-error" id="login-password-error"></div>
          </div>

          <div class="form-actions-row">
            <label class="form-check">
              <input type="checkbox" id="remember-me">
              <span>Se souvenir de moi</span>
            </label>
            <a href="<?= htmlspecialchars(caremeal_path('View/FrontOffice/forgot-password.php'), ENT_QUOTES, 'UTF-8') ?>">Mot de passe oublié ?</a>
          </div>

          <button type="submit" class="btn btn-primary btn-full btn-lg" id="btn-login">
            Se connecter
          </button>
        </form>

        <div class="divider"><span>ou continuer avec</span></div>

        <div class="social-buttons">
          <div id="google-btn-container" style="display:none;position:absolute;"></div>
          <button class="btn btn-social btn-google" id="btn-google" onclick="Auth.handleGoogleLogin()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
              <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
              <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
              <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
              <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            </svg>
            <span>Google</span>
          </button>
          <button class="btn btn-social btn-facebook" id="btn-facebook" onclick="Auth.handleFacebookLogin()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
              <path d="M24 12c0-6.627-5.373-12-12-12S0 5.373 0 12c0 5.99 4.388 10.954 10.125 11.854V15.47H7.078V12h3.047V9.356c0-3.007 1.792-4.668 4.533-4.668 1.312 0 2.686.234 2.686.234v2.953H15.83c-1.491 0-1.956.925-1.956 1.875V12h3.328l-.532 3.47h-2.796v8.385C19.612 22.954 24 17.99 24 12z" fill="#1877F2"/>
            </svg>
            <span>Facebook</span>
          </button>
          <button class="btn btn-social btn-face" id="btn-face-login" onclick="FaceAuth.openFaceLogin()">
            <i class="fa-solid fa-camera" style="font-size:1rem;"></i>
            <span>Visage</span>
          </button>
        </div>

        <div class="auth-footer">
          <p>Pas encore de compte ? <a href="<?= htmlspecialchars(caremeal_path('View/FrontOffice/register-student.php'), ENT_QUOTES, 'UTF-8') ?>">Créer un compte étudiant</a></p>
          <p style="margin-top:8px;">Vous êtes un restaurant ? <a href="<?= htmlspecialchars(caremeal_path('View/FrontOffice/register-partner.php'), ENT_QUOTES, 'UTF-8') ?>">Devenir partenaire</a></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Face Auth Modal -->
  <div class="face-modal-overlay" id="face-modal">
    <div class="face-modal">
      <button class="face-modal-close" onclick="FaceAuth.closeModal()">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <div class="face-modal-header">
        <div class="face-modal-icon">
          <i class="fa-solid fa-user-shield"></i>
        </div>
        <h2>Face ID</h2>
        <p>Regardez l'écran pour vous connecter de manière sécurisée.</p>
      </div>
      <div class="face-video-container">
        <video id="face-video" autoplay muted playsinline></video>
        <div class="face-scan-frame">
          <div class="face-scan-corner tl"></div>
          <div class="face-scan-corner tr"></div>
          <div class="face-scan-corner bl"></div>
          <div class="face-scan-corner br"></div>
        </div>
        <div class="face-scan-line" id="face-scan-line"></div>
        <div class="face-overlay" id="face-overlay"></div>
      </div>
      <div class="face-status loading" id="face-status">Initialisation...</div>
    </div>
  </div>

  <script src="<?= htmlspecialchars(caremeal_path('js/app.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
  <script src="<?= htmlspecialchars(caremeal_path('js/auth.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
  <script src="<?= htmlspecialchars(caremeal_path('js/auth-fallback.js'), ENT_QUOTES, 'UTF-8') ?>?v=20260510-1"></script>
  <!-- face-api.js (TensorFlow.js) -->
  <script defer src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.12/dist/face-api.min.js"></script>
  <script defer src="<?= htmlspecialchars(caremeal_path('js/face-auth.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
  <!-- Facebook SDK -->
  <script>
    window.fbAsyncInit = function() {
      FB.init({
        appId   : document.querySelector('meta[name="facebook-app-id"]').content,
        cookie  : true,
        xfbml   : true,
        version : 'v19.0'
      });
    };
    (function(d,s,id){
      var js,fjs=d.getElementsByTagName(s)[0];
      if(d.getElementById(id))return;
      js=d.createElement(s);js.id=id;
      js.src="https://connect.facebook.net/fr_FR/sdk.js";
      fjs.parentNode.insertBefore(js,fjs);
    }(document,'script','facebook-jssdk'));
  </script>
  <script src="<?= htmlspecialchars(caremeal_path('js/social-auth.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>




