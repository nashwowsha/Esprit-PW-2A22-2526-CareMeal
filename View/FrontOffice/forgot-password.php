ÃƒÂ¯Ã‚Â¿Ã‚Â½<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="RÃƒÆ’Ã‚Â©initialisez votre mot de passe CareMeal.">
  <title>Mot de passe oubliÃƒÆ’Ã‚Â© ÃƒÂ¯Ã‚Â¿Ã‚Â½ CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/main.css">
  <link rel="stylesheet" href="css/auth.css">
</head>
<body>
  <div class="auth-page">
    <div class="auth-visual">
      <div class="shape shape-1"></div>
      <div class="shape shape-2"></div>
      <div class="auth-visual-content">
        <div style="font-size:5rem;margin-bottom:24px;animation:float 3s ease-in-out infinite;"><i class="fa-solid fa-user-lock"></i></div>
        <h2>Pas de panique !</h2>
        <p>ÃƒÂ¯Ã‚Â¿Ã‚Â½!a arrive ÃƒÆ’Ã‚Â  tout le monde. Nous allons vous aider ÃƒÆ’Ã‚Â  retrouver l'accÃƒÆ’Ã‚Â¨s ÃƒÆ’Ã‚Â  votre compte en quelques secondes.</p>
      </div>
    </div>

    <div class="auth-form-side">
      <div class="auth-form-container">
        <div class="auth-form-header">
          <a href="index.html" class="brand-link">
            <div class="brand-icon"><img src="assets/logo.png" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;"></div>
            Care<span style="color:var(--color-primary)">Meal</span>
          </a>
          <h1>Mot de passe oubliÃƒÆ’Ã‚Â©</h1>
          <p>Entrez votre email pour recevoir un lien de rÃƒÆ’Ã‚Â©initialisation.</p>
        </div>

        <div id="auth-alert" class="auth-alert" style="display:none;"></div>

        <form id="forgot-form" onsubmit="Auth.handleForgotPassword(event)">
          <div class="form-group">
            <label for="forgot-email">Adresse email <span class="required">*</span></label>
            <div class="input-wrapper">
              <input type="email" id="forgot-email" class="form-input" placeholder="votre@email.com" required>
              <span class="input-icon"><i class="fa-solid fa-envelope"></i></span>
            </div>
            <div class="form-error" id="forgot-email-error"></div>
          </div>

          <button type="submit" class="btn btn-primary btn-full btn-lg" id="btn-forgot">
            <span><i class="fa-solid fa-envelope-open"></i></span> Envoyer le lien
          </button>
        </form>

        <!-- Success state -->
        <div id="forgot-success" class="hidden" style="text-align:center;padding:40px 0;">
          <div style="font-size:4rem;margin-bottom:16px;animation:scaleIn 0.4s ease;"><i class="fa-solid fa-check"></i></div>
          <h3 style="margin-bottom:8px;">Email envoyÃƒÆ’Ã‚Â© !</h3>
          <p style="margin-bottom:24px;">VÃƒÆ’Ã‚Â©rifiez votre boÃƒÆ’Ã‚Â®te de rÃƒÆ’Ã‚Â©ception et suivez les instructions.</p>
          <a href="login.html" class="btn btn-primary">Retour ÃƒÆ’Ã‚Â  la connexion</a>
        </div>

        <div class="auth-footer">
          <p>Vous vous souvenez ? <a href="login.html">Retour ÃƒÆ’Ã‚Â  la connexion</a></p>
        </div>
      </div>
    </div>
  </div>

  <script src="js/app.js"></script>
  <script src="js/auth.js"></script>
</body>
</html>

