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
        <p>-!a arrive à  tout le monde. Nous allons vous aider à  retrouver l'accès à  votre compte en quelques secondes.</p>
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

        <form id="forgot-form" novalidate onsubmit="Auth.handleForgotPassword(event)">
          <div class="form-group">
            <label for="forgot-email">Adresse email <span class="required">*</span></label>
            <div class="input-wrapper">
              <input type="text" id="forgot-email" class="form-input" placeholder="votre@email.com">
              <span class="input-icon"><i class="fa-solid fa-envelope"></i></span>
            </div>
            <div class="form-error" id="forgot-email-error"></div>
          </div>

          <button type="submit" class="btn btn-primary btn-full btn-lg" id="btn-forgot">
            <span>
              <span class="input-icon"><i class="fa-solid fa-envelope-open"></i></span> Envoyer le lien
          </button>
        </form>

        <!-- Success state -->
        <div id="forgot-success" class="hidden" style="text-align:center;padding:40px 0;">
          <div style="font-size:4rem;margin-bottom:16px;animation:scaleIn 0.4s ease;">
              <i class="fa-solid fa-check"></i></div>
          <h3 style="margin-bottom:8px;">Email envoyé !</h3>
          <p style="margin-bottom:24px;">Vérifiez votre boîte de réception et suivez les instructions.</p>
          <a href="/projet2a22/View/FrontOffice/login.php" class="btn btn-primary">Retour à  la connexion</a>
        </div>

        <div class="auth-footer">
          <p>Vous vous souvenez ? <a href="/projet2a22/View/FrontOffice/login.php">Retour à  la connexion</a></p>
        </div>
      </div>
    </div>
  </div>

  <script src="/projet2a22/js/app.js"></script>
  <script src="/projet2a22/js/auth.js"></script>
</body>
</html>


