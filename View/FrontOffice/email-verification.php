ÃƒÂ¯Ã‚Â¿Ã‚Â½<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="VÃƒÆ’Ã‚Â©rifiez votre adresse email pour activer votre compte CareMeal.">
  <title>VÃƒÆ’Ã‚Â©rification Email ÃƒÂ¯Ã‚Â¿Ã‚Â½ CareMeal</title>
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
        <div style="font-size:5rem;margin-bottom:24px;animation:float 3s ease-in-out infinite;"><i class="fa-solid fa-envelope-circle-check"></i></div>
        <h2>Presque terminÃƒÆ’Ã‚Â© !</h2>
        <p>SÃƒÆ’Ã‚Â©curisez votre compte en validant votre adresse email. C'est rapide et ÃƒÆ’Ã‚Â§a nous permet de garder le contact.</p>
      </div>
    </div>

    <div class="auth-form-side">
      <div class="auth-form-container">
        <div class="auth-form-header">
          <a href="index.html" class="brand-link">
            <div class="brand-icon"><img src="assets/logo.png" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;"></div>
            Care<span style="color:var(--color-primary)">Meal</span>
          </a>
          <h1>VÃƒÆ’Ã‚Â©rifiez votre email</h1>
          <p>Nous avons envoyÃƒÆ’Ã‚Â© un lien de confirmation ÃƒÆ’Ã‚Â  votre adresse email.</p>
        </div>

        <div style="text-align: center; margin-bottom: 2rem;">
          <div class="verify-email" id="user-email" style="display: inline-block; padding: 12px 24px; background: rgba(255, 107, 53, 0.1); border: 1px solid rgba(255, 107, 53, 0.2); border-radius: 8px; color: var(--color-primary); font-weight: 600; font-size: 1.1rem; letter-spacing: 0.5px;">votre@email.com</div>
          <p style="font-size: 0.9rem; color: var(--color-text-muted); margin-top: 1rem;">Cliquez sur le lien dans l'email pour activer votre compte.</p>
        </div>

        <button class="btn btn-primary btn-full btn-lg" id="btn-continue" onclick="handleContinue()">
          Continuer <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
        </button>

        <div class="auth-footer" style="margin-top: 2rem; text-align: center;">
          <p>Pas reÃƒÆ’Ã‚Â§u l'email ? <a href="#" onclick="resendEmail(event)" style="color: var(--color-primary); font-weight: 500; transition: opacity 0.2s ease;">Renvoyer</a></p>
        </div>
      </div>
    </div>
  </div>

  <script src="js/app.js"></script>
  <script>
    // Show user email
    const user = App.getCurrentUser();
    if (user) {
      document.getElementById('user-email').textContent = user.email;
    }

    function handleContinue() {
      const u = App.getCurrentUser();
      if (u) {
        if (u.role === 'student') window.location.href = 'student/dashboard.html';
        else if (u.role === 'partner') window.location.href = 'partner/dashboard.html';
        else window.location.href = 'login.html';
      } else {
        window.location.href = 'login.html';
      }
    }

    function resendEmail(e) {
      e.preventDefault();
      const link = e.target;
      link.style.pointerEvents = 'none';
      link.style.opacity = '0.6';
      
      let timeLeft = 30;
      link.innerHTML = `EnvoyÃƒÆ’Ã‚Â© ! (${timeLeft}s)`;
      
      const timer = setInterval(() => {
        timeLeft--;
        if (timeLeft <= 0) {
          clearInterval(timer);
          link.innerHTML = 'Renvoyer';
          link.style.pointerEvents = '';
          link.style.opacity = '1';
        } else {
          link.innerHTML = `EnvoyÃƒÆ’Ã‚Â© ! (${timeLeft}s)`;
        }
      }, 1000);
    }
  </script>
</body>
</html>

