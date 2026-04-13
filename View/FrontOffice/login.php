<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Connectez-vous à  CareMeal pour accéder à  vos offres anti-gaspillage préférées.">
  <title>Connexion - CareMeal</title>
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
        <div class="logo" style="width:120px;height:120px;display:flex;align-items:center;justify-content:center;margin:0 auto 32px;"><img src="/projet2a22/assets/logo.png" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;"></div>
        <h2>Bienvenue sur CareMeal</h2>
        <p>Rejoignez la communauté qui lutte contre le gaspillage alimentaire tout en faisant des économies.</p>
        <div style="display:flex;gap:24px;justify-content:center;margin-top:48px;">
          <div style="text-align:center;">
            <div style="font-size:1.5rem;font-weight:700;color:var(--color-white);">-60%</div>
            <div style="font-size:0.8rem;color:var(--color-text-muted);">Prix réduits</div>
          </div>
          <div style="text-align:center;">
            <div style="font-size:1.5rem;font-weight:700;color:var(--color-white);">
              <i class="fa-solid fa-seedling"></i></div>
            <div style="font-size:0.8rem;color:var(--color-text-muted);">éco-responsable</div>
          </div>
          <div style="text-align:center;">
            <div style="font-size:1.5rem;font-weight:700;color:var(--color-white);">
              <i class="fa-solid fa-bolt"></i></div>
            <div style="font-size:0.8rem;color:var(--color-text-muted);">Rapide & Simple</div>
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
          <h1>Bon retour ! <i class="fa-solid fa-hand-wave"></i></h1>
          <p>Connectez-vous pour accéder à  votre espace.</p>
        </div>

        <!-- Alert container -->
        <div id="auth-alert" class="auth-alert" style="display:none;"></div>

        <!-- Login Form -->
        <form id="login-form" onsubmit="Auth.handleLogin(event)">
          <div class="form-group">
            <label for="login-email">Email <span class="required">*</span></label>
            <div class="input-wrapper">
              <input type="text" id="login-email" class="form-input" placeholder="votre@email.com" autocomplete="email">
              <span class="input-icon"><i class="fa-solid fa-envelope"></i></span>
            </div>
            <div class="form-error" id="login-email-error"></div>
          </div>

          <div class="form-group">
            <label for="login-password">Mot de passe <span class="required">*</span></label>
            <div class="input-wrapper">
              <input type="password" id="login-password" class="form-input" placeholder="Votre mot de passe" autocomplete="current-password">
              <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
              <button type="button" class="password-toggle">
              <i class="fa-solid fa-eye"></i></button>
            </div>
            <div class="form-error" id="login-password-error"></div>
          </div>

          <div class="form-actions-row">
            <label class="form-check">
              <input type="checkbox" id="remember-me">
              <span>Se souvenir de moi</span>
            </label>
            <a href="/projet2a22/View/FrontOffice/forgot-password.php">Mot de passe oublié ?</a>
          </div>

          <button type="submit" class="btn btn-primary btn-full btn-lg" id="btn-login">
            <span>
              <span class="input-icon"><i class="fa-solid fa-rocket"></i></span> Se connecter
          </button>
        </form>

        <div class="divider">
          <span>ou continuer avec</span>
        </div>

        <button class="btn btn-google btn-full" id="btn-google" onclick="Auth.showAlert('info','Connexion Google non disponible en mode démo')">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
          </svg>
          Continuer avec Google
        </button>

        <div class="auth-footer">
          <p>Pas encore de compte ? <a href="/projet2a22/View/FrontOffice/register-student.php">Créer un compte étudiant</a></p>
          <p style="margin-top:8px;">Vous êtes un restaurant ? <a href="/projet2a22/View/FrontOffice/register-partner.php">Devenir partenaire</a></p>
        </div>

        <!-- Demo credentials info -->
        <div style="margin-top:16px;padding:12px;background:rgba(255,255,255,0.03);border-radius:12px;border:1px solid rgba(255,255,255,0.1);">
          <p style="font-size:0.75rem;color:var(--color-text-muted);margin-bottom:6px;">
              <i class="fa-solid fa-key"></i> <strong style="color:var(--color-white);">Comptes démo :</strong></p>
          <p style="font-size:0.7rem;color:var(--color-text-muted);line-height:1.6;">
            étudiant : <strong style="color:var(--color-beige);">ahmed@univ.tn</strong> / student123<br>
            Partenaire : <strong style="color:var(--color-beige);">contact@baguettedoree.tn</strong> / partner123<br>
            Admin : <strong style="color:var(--color-beige);">admin@caremeal.tn</strong> / admin123
          </p>
        </div>
      </div>
    </div>
  </div>

  <script src="/projet2a22/js/app.js"></script>
  <script src="/projet2a22/js/auth.js"></script>
</body>
</html>



