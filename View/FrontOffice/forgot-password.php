<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Réinitialisez votre mot de passe CareMeal.">
  <title>Mot de passe oublié - CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/projet2a22/css/main.css">
  <link rel="stylesheet" href="/projet2a22/css/auth.css">
</head>
<body>
  <div class="auth-page">
    <div class="auth-visual">
      <div class="shape shape-1"></div>
      <div class="shape shape-2"></div>
      <div class="auth-visual-content">
        <div style="font-size:5rem;margin-bottom:24px;animation:float 3s ease-in-out infinite;">
              <i class="fa-solid fa-user-lock"></i></div>
        <h2>Pas de panique !</h2>
        <p>-!a arrive à tout le monde. Nous allons vous aider à retrouver l'accès à votre compte en quelques secondes.</p>
      </div>
    </div>

    <div class="auth-form-side">
      <div class="auth-form-container">
        <div class="auth-form-header">
          <a href="/projet2a22/View/FrontOffice/index.php" class="brand-link">
            <div class="brand-icon"><img src="/projet2a22/assets/logo.png" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;"></div>
            Care<span style="color:var(--color-primary)">Meal</span>
          </a>
          <h1>Mot de passe oublié</h1>
          <p>Entrez votre email pour recevoir un lien de réinitialisation.</p>
        </div>

        <div id="auth-alert" class="auth-alert" style="display:none;"></div>

        <!-- Step 1: Request Code -->
        <form id="forgot-form-email" onsubmit="Auth.handleForgotPasswordRequest(event)">
          <div class="form-group">
            <label for="forgot-email">Adresse email ou Numéro de téléphone <span class="required">*</span></label>
            <div class="input-wrapper">
              <input type="text" id="forgot-email" class="form-input" placeholder="votre@email.com ou 12345678">
              <span class="input-icon"><i class="fa-solid fa-envelope"></i></span>
            </div>
            <div class="form-error" id="forgot-email-error"></div>
          </div>
          <button type="submit" class="btn btn-primary btn-full btn-lg" id="btn-forgot-email">
            <span><span class="input-icon"><i class="fa-solid fa-paper-plane"></i></span> Envoyer le code</span>
          </button>
        </form>

        <!-- Step 2: Enter Code -->
        <form id="forgot-form-code" class="hidden" onsubmit="Auth.handleVerifyCode(event)">
          <p style="margin-bottom: 20px;">Veuillez entrer le code à 6 chiffres reçu par email.</p>
          <div class="form-group">
            <label for="forgot-code">Code de vérification <span class="required">*</span></label>
            <div class="input-wrapper">
              <input type="text" id="forgot-code" class="form-input" placeholder="123456">
              <span class="input-icon"><i class="fa-solid fa-key"></i></span>
            </div>
            <div class="form-error" id="forgot-code-error"></div>
          </div>
          <button type="submit" class="btn btn-primary btn-full btn-lg" id="btn-forgot-code">
            <span><span class="input-icon"><i class="fa-solid fa-check"></i></span> Vérifier le code</span>
          </button>
          
          <div style="text-align:center; margin-top: 15px;">
             <button type="button" id="btn-resend-code" onclick="Auth.handleResendCode()" class="btn btn-secondary" style="background:transparent; border:none; color:var(--color-primary); cursor:pointer;" disabled>
                Renvoyer le code (30s)
             </button>
          </div>
        </form>

        <!-- Step 3: New Password -->
        <form id="forgot-form-password" class="hidden" onsubmit="Auth.handleResetPasswordFinal(event)">
          <div class="form-group">
            <label for="forgot-new-password">Nouveau mot de passe <span class="required">*</span></label>
            <div class="input-wrapper">
              <input type="password" id="forgot-new-password" class="form-input" placeholder="Min. 6 caractères">
              <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
              <button type="button" class="password-toggle" tabindex="-1"><i class="fa-solid fa-eye"></i></button>
            </div>
            <div class="form-error" id="forgot-new-password-error"></div>
          </div>
          <button type="submit" class="btn btn-primary btn-full btn-lg" id="btn-forgot-password">
            <span><span class="input-icon"><i class="fa-solid fa-save"></i></span> Réinitialiser</span>
          </button>
        </form>

        <!-- Success state -->
        <div id="forgot-success" class="hidden" style="text-align:center;padding:40px 0;">
          <div style="font-size:4rem;margin-bottom:16px;animation:scaleIn 0.4s ease; color: var(--color-success);">
              <i class="fa-solid fa-check-circle"></i></div>
          <h3 style="margin-bottom:8px;">Mot de passe modifié !</h3>
          <p style="margin-bottom:24px;">Votre mot de passe a été réinitialisé avec succès.</p>
          <a href="/projet2a22/View/FrontOffice/login.php" class="btn btn-primary">Retour à la connexion</a>
        </div>

        <div class="auth-footer">
          <p>Vous vous souvenez ? <a href="/projet2a22/View/FrontOffice/login.php">Retour à la connexion</a></p>
        </div>
      </div>
    </div>
  </div>

  <script src="/projet2a22/js/app.js"></script>
  <script src="/projet2a22/js/auth.js"></script>
</body>
</html>


