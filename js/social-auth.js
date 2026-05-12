/* ============================================
   CAREMEAL — SOCIAL AUTH
   social-auth.js — Google, Facebook, GitHub
   ============================================ */

// Ãƒâ€°tend l'objet Auth existant avec les méthodes sociales
Object.assign(Auth, {

  // ================================================================
  // Connexion Google via Google Identity Services (popup)
  // ================================================================
  handleGoogleLogin() {
    const clientId = document.querySelector('meta[name="google-client-id"]')?.content;
    if (!clientId || clientId.includes('VOTRE_')) {
      Auth.showAlert('error', 'Google OAuth non configuré.');
      return;
    }

    const btn = document.getElementById('btn-google');
    if (btn) { btn.disabled = true; btn.querySelector('span').textContent = 'Connexion...'; }

    // Initialiser GSI
    google.accounts.id.initialize({
      client_id: clientId,
      callback: (response) => Auth._handleGoogleCredential(response, btn),
      auto_select: false,
      cancel_on_tap_outside: true,
      ux_mode: 'popup',
    });

    // Créer un bouton invisible et le cliquer — contourne les restrictions One Tap
    const container = document.getElementById('google-btn-container');
    container.innerHTML = '';
    google.accounts.id.renderButton(container, {
      type: 'standard',
      theme: 'outline',
      size: 'large',
      text: 'signin_with',
    });
    // Cliquer automatiquement sur le bouton rendu
    setTimeout(() => {
      const gBtn = container.querySelector('div[role="button"]') || container.querySelector('button');
      if (gBtn) {
        gBtn.click();
      } else {
        // Fallback One Tap
        google.accounts.id.prompt((n) => {
          if (n.isNotDisplayed() || n.isSkippedMoment()) {
            if (btn) { btn.disabled = false; btn.querySelector('span').textContent = 'Google'; }
            Auth.showAlert('error', 'Popup Google bloqué par le navigateur. Autorisez les popups.');
          }
        });
      }
    }, 300);
  },

  _handleGoogleCredential(response, btn) {
    if (!response.credential) {
      Auth.showAlert('error', 'Connexion Google annulée.');
      if (btn) { btn.disabled = false; btn.querySelector('span').textContent = 'Google'; }
      return;
    }
    Auth._sendSocialToken('google', response.credential, btn);
  },

  // ================================================================
  // Connexion Facebook via SDK JS
  // ================================================================
  handleFacebookLogin() {
    const appId = document.querySelector('meta[name="facebook-app-id"]')?.content;
    if (!appId || appId.includes('VOTRE_')) {
      Auth.showAlert('error', 'Facebook OAuth non configuré. Ajoutez votre App ID dans config/oauth.php');
      return;
    }

    if (typeof FB === 'undefined') {
      Auth.showAlert('error', 'SDK Facebook non chargé. Vérifiez votre connexion internet.');
      return;
    }

    const btn = document.getElementById('btn-facebook');
    if (btn) { btn.disabled = true; btn.querySelector('span').textContent = 'Connexion...'; }

    FB.login((response) => {
      if (response.authResponse) {
        const accessToken = response.authResponse.accessToken;
        Auth._sendSocialToken('facebook', accessToken, btn);
      } else {
        Auth.showAlert('info', 'Connexion Facebook annulée.');
        if (btn) { btn.disabled = false; btn.querySelector('span').textContent = 'Facebook'; }
      }
    }, { scope: 'public_profile,email' });
  },

  // ================================================================
  // Connexion GitHub — redirection directe
  // ================================================================
  handleGithubLogin() {
    window.location.href = App.apiUrl('Controller/SocialAuthController.php?action=github-init');
  },

  // ================================================================
  // Envoi du token social au backend PHP pour vérification
  // ================================================================
  _sendSocialToken(provider, token, btn) {
    fetch(App.apiUrl('Controller/SocialAuthController.php?action=social-token'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ provider, token }),
    })
    .then(res => res.text())
    .then(text => {
      let result;
      try {
        const start = text.indexOf('{');
        result = JSON.parse(text.substring(start));
      } catch (e) {
        throw new Error('Réponse serveur invalide');
      }

      if (result.success) {
        const u = result.user;
        App.setCurrentUser({
          id:            u.id,
          email:         u.email,
          role:          u.role,
          status:        u.status,
          name:          u.name || u.email,
          prenom:        u.prenom || '',
          nom:           u.nom || '',
          phone:         u.phone || '',
          avatar:        u.avatar || '',
          ecole:         u.ecole || '',
          annee:         u.annee || '',
          quartier:      u.quartier || '',
          nomEntreprise: u.nomEntreprise || '',
          description:   u.description || '',
        });
        App.addLog('Connexion ' + provider + ' réussie');

        // Redirection selon le rôle
        switch (u.role) {
          case 'student': window.location.href = App.apiUrl('View/FrontOffice/student/dashboard.php'); break;
          case 'partner': window.location.href = App.apiUrl('View/FrontOffice/partner/dashboard.php'); break;
          case 'admin':   window.location.href = App.apiUrl('View/BackOffice/admin/dashboard.php'); break;
          default:        window.location.href = App.apiUrl('View/FrontOffice/index.php'); break;
        }
      } else {
        Auth.showAlert('error', result.message || 'Erreur de connexion sociale.');
        if (btn) {
          btn.disabled = false;
          const label = provider.charAt(0).toUpperCase() + provider.slice(1);
          btn.querySelector('span').textContent = label;
        }
      }
    })
    .catch(err => {
      Auth.showAlert('error', 'Erreur serveur: ' + err.message);
      if (btn) {
        btn.disabled = false;
        const label = provider.charAt(0).toUpperCase() + provider.slice(1);
        btn.querySelector('span').textContent = label;
      }
    });
  },
});

// Afficher l'erreur OAuth si redirigé depuis GitHub avec une erreur
document.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);
  const socialError = params.get('social_error');
  if (socialError) {
    Auth.showAlert('error', decodeURIComponent(socialError));
    // Nettoyer l'URL
    window.history.replaceState({}, '', window.location.pathname);
  }
});



