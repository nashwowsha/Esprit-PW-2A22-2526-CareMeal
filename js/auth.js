/* ============================================
   CAREMEAL — AUTH
   auth.js — Login, Register, Validation
   ============================================ */

const Auth = {
  // --- Login ---
  // ================================================================
  // CRUD - READ : Connexion d'un utilisateur + CONTROLE DE SAISIE
  // ================================================================
  handleLogin(e) {
    e.preventDefault();
    const email = document.getElementById('login-email').value.trim();
    const password = document.getElementById('login-password').value;
    const remember = document.getElementById('remember-me')?.checked;

    // Clear errors
    Auth.clearErrors();

    // Validate
    let valid = true;
    if (!email) { Auth.showError('login-email', 'Veuillez entrer votre email'); valid = false; }
    else if (!Auth.isValidEmail(email)) { Auth.showError('login-email', 'Format d\'email invalide'); valid = false; }
    if (!password) { Auth.showError('login-password', 'Veuillez entrer votre mot de passe'); valid = false; }

    if (!valid) return;

    const btn = e.target.querySelector('button[type="submit"]') || e.target.querySelector('.btn-primary');
    const oldHtml = btn ? btn.innerHTML : '';
    if(btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Connexion...'; }

    // Utiliser l'URL absolue si on est sur Live Server (port 5500), sinon chemin relatif correctement résolu
    

    fetch(App.apiUrl('Controller/AuthController.php'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'login', email: email, password: password })
    })
    .then(res => res.text())
    .then(text => {
        let result;
        try {
            let startIndex = text.indexOf('{');
            if (startIndex === -1) throw new Error("RïÃ‚Â¿Ã‚Â½ponse serveur invalide ou vide");
            result = JSON.parse(text.substring(startIndex));
        } catch (e) {
            throw new Error("Erreur de format depuis PHP: " + text);
        }
        
        if(result.success) {
            const dbUser = result.user;
            
            // Format the database user into the format the javascript UI expects
            const user = {
                id: dbUser.id,
                email: dbUser.email,
                role: dbUser.role,
                status: dbUser.status,
                name: (dbUser.prenom + ' ' + dbUser.nom).trim(),
                phone: dbUser.telephone,
                createdAt: dbUser.created_at,
                // specific fields mapped
                university: dbUser.ecole || '',
                ecole: dbUser.ecole || '',
                annee: dbUser.annee_etude || '',
                quartier: dbUser.quartier || '',
                nomEntreprise: dbUser.nom_entreprise || '',
                description: dbUser.description || '',
                linkedin: dbUser.linkedin || '',
                instagram: dbUser.instagram || '',
                facebook: dbUser.facebook || '',
                twitter: dbUser.twitter || '',
                github: dbUser.github || '',
                secteur_activite: dbUser.secteur_activite || '',
                site_web: dbUser.site_web || '',
                prenom: dbUser.prenom || '',
                nom: dbUser.nom || ''
            };

            App.setCurrentUser(user);
            App.addLog('Connexion reussie');
            
            // Redirect based on role
            switch (user.role) {
                case 'student': window.location.href = App.apiUrl('View/FrontOffice/feed.php'); break;
                case 'partner': window.location.href = App.apiUrl('View/FrontOffice/feed.php'); break;
                case 'admin': window.location.href = App.apiUrl('View/BackOffice/admin/dashboard.php'); break;
                default: window.location.href = App.apiUrl('View/FrontOffice/index.php'); break;
            }
        } else {
            // Afficher l'erreur
            if(result.message.includes('Mot de passe')) {
                Auth.showError('login-password', result.message);
            } else {
                Auth.showError('login-email', result.message);
            }
            if(btn) { btn.disabled = false; btn.innerHTML = oldHtml; }
        }
    })
    .catch(err => {
        Auth.showAlert('error', 'Erreur serveur: ' + err.message);
        if(btn) { btn.disabled = false; btn.innerHTML = oldHtml; }
    });
  },

  // --- Register Student ---
  currentStep: 1,
  totalSteps: 2, // Inscription etudiant en 2 etapes

  async nextStep() {
    const valid = await this.validateStep(this.currentStep);
    if (!valid) return;
    if (this.currentStep < this.totalSteps) {
      this.currentStep++;
      this.updateStepUI();
    }
  },

  prevStep() {
    if (this.currentStep > 1) {
      this.currentStep--;
      this.updateStepUI();
    }
  },

  updateStepUI() {
    // Hide all steps
    document.querySelectorAll('.form-step').forEach(s => s.classList.remove('active'));
    // Show current
    const current = document.getElementById('step-' + this.currentStep);
    if (current) current.classList.add('active');
    // Update dots
    document.querySelectorAll('.step-dot').forEach((dot, i) => {
      dot.classList.remove('active', 'completed');
      if (i + 1 === this.currentStep) dot.classList.add('active');
      else if (i + 1 < this.currentStep) dot.classList.add('completed');
    });
    document.querySelectorAll('.step-line').forEach((line, i) => {
      line.classList.toggle('completed', i + 1 < this.currentStep);
    });
  },

  // ================================================================
  // CONTROLE DE SAISIE : Validation des champs inscription étudiant
  // ================================================================
  validateStep(step) {
    return new Promise((resolve) => {
      Auth.clearErrors();
      let valid = true;

      if (step === 1) {
        const nom = document.getElementById('reg-nom')?.value.trim();
        const prenom = document.getElementById('reg-prenom')?.value.trim();
        const email = document.getElementById('reg-email')?.value.trim();
        const password = document.getElementById('reg-password')?.value;
        const confirm = document.getElementById('reg-confirm')?.value;

        if (!nom || nom.length < 2) { Auth.showError('reg-nom', 'Nom requis'); valid = false; }
        if (!prenom || prenom.length < 2) { Auth.showError('reg-prenom', 'PrïÃ‚Â¿Ã‚Â½nom requis'); valid = false; }
        if (!password || password.length < 6) { Auth.showError('reg-password', 'Minimum 6 caract\u00e8res'); valid = false; }
        if (password !== confirm) { Auth.showError('reg-confirm', 'Les mots de passe ne correspondent pas'); valid = false; }

        if (!email) { 
            Auth.showError('reg-email', 'Email requis'); 
            valid = false; 
            resolve(valid);
            return;
        } else if (!Auth.isValidEmail(email)) { 
            Auth.showError('reg-email', 'Format d\'email invalide'); 
            valid = false; 
            resolve(valid);
            return;
        } else {
            // Check email on backend
            // Determiner l'URL de l'API
            

            fetch(App.apiUrl('Controller/AuthController.php'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'check-email', email: email })
            })
            .then(res => res.text())
            .then(text => {
                let result;
                try {
                    let startIndex = text.indexOf('{');
                    if (startIndex === -1) throw new Error("RïÃ‚Â¿Ã‚Â½ponse serveur invalide ou vide");
                    result = JSON.parse(text.substring(startIndex));
                } catch (e) {
                    console.error("Format depuis PHP invalide:", text);
                    resolve(false);
                    return;
                }
                
                if(!result.success) {
                    Auth.showError('reg-email', 'Cet email est d\u00E9j\u00E0 utilis\u00E9');
                    valid = false;
                }
                resolve(valid);
            })
            .catch(err => resolve(false));
            return;
        }
      }

      if (step === 2) {
        const university = document.getElementById('reg-university')?.value;
        const telephone = document.getElementById('reg-telephone')?.value;
        const annee = document.getElementById('reg-annee')?.value;
        if (!university) { Auth.showError('reg-university', 'Veuillez sélectionner votre université'); valid = false; }
        if (!telephone) { Auth.showError('reg-telephone', 'Téléphone requis'); valid = false; }
        else if (!/^[0-9+\s\-]{8,15}$/.test(telephone)) { Auth.showError('reg-telephone', 'Format invalide'); valid = false; }
        if (!annee) { Auth.showError('reg-annee', 'Année d\'étude requise'); valid = false; }
        const quartier = document.getElementById('reg-quartier')?.value;
        if (!quartier) { Auth.showError('reg-quartier', 'Zone requise'); valid = false; }
      }

      if (step === 3) {
        // Validation des options de l'etape 3 (ex. verifier que l'API fonctionnera)
        // Les options sont optionnelles pour l'instant
      }

      resolve(valid);
    });
  },

  // ================================================================
  // CRUD - CREATE : Inscription étudiant (envoi vers AuthController)
  // ================================================================
  async handleStudentRegister(e) {
      e.preventDefault();
      const valid = await this.validateStep(2); // Validation finale sur l'etape campus
      if (!valid) return;

      const userNom = document.getElementById('reg-nom')?.value.trim() || '';
      const userPrenom = document.getElementById('reg-prenom')?.value.trim() || '';
      const emailVal = document.getElementById('reg-email')?.value.trim() || '';
      const passVal = document.getElementById('reg-password')?.value || '';
      const sponsorVal = document.getElementById('reg-sponsor')?.value.trim() || '';
      let univVal = document.getElementById('reg-university')?.value || '';
      if (univVal === 'autre') univVal = document.getElementById('reg-university-other')?.value.trim() || 'Non renseign\u00e9';
      const telephoneVal = document.getElementById('reg-telephone')?.value.trim() || '';
      let anneeVal = document.getElementById('reg-annee')?.value.trim() || '';
      if (anneeVal === 'other') anneeVal = document.getElementById('reg-annee-other')?.value.trim() || 'Non renseign\u00e9';
      let quartierVal = document.getElementById('reg-quartier')?.value.trim() || '';
      if (quartierVal === 'autre') quartierVal = document.getElementById('reg-quartier-other')?.value.trim() || 'Non renseign\u00e9';

      const btn = e.target.tagName === 'FORM' 
        ? e.target.querySelector('button[type="submit"]') 
        : document.getElementById('btn-register-student');
      
      if(btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Inscription...'; }

      

      fetch(App.apiUrl('Controller/AuthController.php'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'register-student',
            nom: userNom,
            prenom: userPrenom,
            email: emailVal,
            password: passVal,
            sponsorCode: sponsorVal,
            ecole: univVal,
            annee: anneeVal,
            telephone: telephoneVal,
            quartier: quartierVal
        })
    })
    .then(res => res.text())
    .then(text => {
        let result;
        try {
            // Nettoyer toute balise HTML ou espace ou BOM avant le '{'
            let startIndex = text.indexOf('{');
            if (startIndex === -1) throw new Error("RïÃ‚Â¿Ã‚Â½ponse serveur invalide ou vide");
            let cleanJson = text.substring(startIndex);
            result = JSON.parse(cleanJson);
        } catch (e) {
            throw new Error("Erreur de format depuis PHP: " + text);
        }
        
        if(result.success) {
            Auth.showAlert('success', result.message);
            setTimeout(() => { window.location.href = App.apiUrl('View/FrontOffice/login.php'); }, 2000);
        } else {
            Auth.showAlert('error', result.message);
            if(btn) { btn.disabled = false; btn.innerHTML = 'CrïÃ‚Â¿Ã‚Â½er mon compte ïÃ‚Â¿Ã‚Â½tudiant <span class="btn-icon"><i class="fa-solid fa-arrow-right"></i></span>'; }
        }
    })
    .catch(err => {
        Auth.showAlert('error', 'Erreur serveur: ' + err.message);
        console.error(err);
        if(btn) { btn.disabled = false; btn.innerHTML = 'CrïÃ‚Â¿Ã‚Â½er mon compte ïÃ‚Â¿Ã‚Â½tudiant <span class="btn-icon"><i class="fa-solid fa-arrow-right"></i></span>'; }
    });

    // Le code en dessous n'est plus utile, on a deja envoye pour public/router.php
    /*
    const preferences = Array.from(selectedTags).map(t => t.dataset.value);
    const budgetSlider = document.getElementById('reg-budget');
    const frequencySelect = document.getElementById('reg-frequency');

    const user = App.addUser({
      name: document.getElementById('reg-name').value.trim(),
      email: document.getElementById('reg-email').value.trim(),
      password: document.getElementById('reg-password').value,
      role: 'student',
      university: document.getElementById('reg-university').value,
      quartier: document.getElementById('reg-quartier')?.value || '',
      preferences: preferences,
      budgetMax: budgetSlider ? parseInt(budgetSlider.value) : 10,
      frequency: frequencySelect ? frequencySelect.value : 'quotidien',
      phone: ''
    });

    // Auto-login
    App.setCurrentUser(user);
    window.location.href = App.apiUrl('View/FrontOffice/email-verification.php');
    */
  },

  // --- Register Partner ---
  currentPartnerStep: 1,
  totalPartnerSteps: 2,

  async nextPartnerStep() {
    const valid = await this.validatePartnerStep(this.currentPartnerStep);
    if (!valid) return;
    if (this.currentPartnerStep < this.totalPartnerSteps) {
      this.currentPartnerStep++;
      this.updatePartnerStepUI();
    }
  },

  prevPartnerStep() {
    if (this.currentPartnerStep > 1) {
      this.currentPartnerStep--;
      this.updatePartnerStepUI();
    }
  },

  updatePartnerStepUI() {
    document.querySelectorAll('.form-step').forEach(s => s.classList.remove('active'));
    const current = document.getElementById('step-partner-' + this.currentPartnerStep);
    if (current) current.classList.add('active');

    document.querySelectorAll('.step-dot').forEach((dot, i) => {
      dot.classList.remove('active', 'completed');
      if (i + 1 === this.currentPartnerStep) dot.classList.add('active');
      else if (i + 1 < this.currentPartnerStep) dot.classList.add('completed');
    });
    document.querySelectorAll('.step-line').forEach((line, i) => {
      line.classList.toggle('completed', i + 1 < this.currentPartnerStep);
    });
  },

  // ================================================================
  // CONTROLE DE SAISIE : Validation des champs inscription partenaire
  // ================================================================
  validatePartnerStep(step) {
    return new Promise((resolve) => {
      Auth.clearErrors();
      let valid = true;

      if (step === 1) {
        const nomEntreprise = document.getElementById('partner-name')?.value.trim();
        const type = document.getElementById('partner-type')?.value;
        const address = document.getElementById('partner-address')?.value.trim();
        const nom = document.getElementById('partner-nom')?.value.trim();
        const prenom = document.getElementById('partner-prenom')?.value.trim();
        const email = document.getElementById('partner-email')?.value.trim();
        const phone = document.getElementById('partner-phone')?.value.trim();

        if (!nomEntreprise || nomEntreprise.length < 2) { Auth.showError('partner-name', 'Nom requis'); valid = false; }
        if (!type || type === '') { Auth.showError('partner-type', 'Sélectionnez un type'); valid = false; }
        if (!address) { Auth.showError('partner-address', 'Adresse requise'); valid = false; }
        
        if (!nom || !/^[a-zA-ZÃƒâ‚¬-ÿ\s\-']+$/.test(nom)) { Auth.showError('partner-nom', 'Nom invalide'); valid = false; }
        if (!prenom || !/^[a-zA-ZÃƒâ‚¬-ÿ\s\-']+$/.test(prenom)) { Auth.showError('partner-prenom', 'Prénom invalide'); valid = false; }

        if (!phone) { 
          Auth.showError('partner-phone', 'Téléphone requis'); 
          valid = false; 
        } else if (!/^[0-9+\s\-]{8,15}$/.test(phone)) { 
          Auth.showError('partner-phone', 'Format invalide'); 
          valid = false; 
        }

        if (!email) {
            Auth.showError('partner-email', 'Email requis');
            valid = false;
            resolve(valid);
            return;
        } else if (!Auth.isValidEmail(email)) {
            Auth.showError('partner-email', 'Format invalide');
            valid = false;
            resolve(valid);
            return;
        } else {
            fetch(App.apiUrl('Controller/AuthController.php'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'check-email', email: email })
            })
            .then(res => res.text())
            .then(text => {
                try {
                    let start = text.indexOf('{');
                    if (start > -1) {
                        let result = JSON.parse(text.substring(start));
                        if (!result.success) {
                            Auth.showError('partner-email', 'Cet email est d\u00E9j\u00E0 utilis\u00E9');
                            valid = false;
                        }
                    }
                } catch(e) {}
                resolve(valid);
            })
            .catch(err => resolve(valid));
            return;
        }
      }

      if (step === 2) {
        const password = document.getElementById('partner-password')?.value;
        if (!password || password.length < 6) { Auth.showError('partner-password', 'Min. 6 caract\u00E8res'); valid = false; }
      }

      resolve(valid);
    });
  },

  // ================================================================
  // CRUD - CREATE : Inscription partenaire (envoi vers AuthController)
  // ================================================================
  async handlePartnerRegister(e) {
    e.preventDefault();
    const valid = await this.validatePartnerStep(2);
    if (!valid) return;

    const nomEntreprise = document.getElementById('partner-name')?.value.trim();
    const type = document.getElementById('partner-type')?.value;
    const address = document.getElementById('partner-address')?.value.trim();
    const nom = document.getElementById('partner-nom')?.value.trim();
    const prenom = document.getElementById('partner-prenom')?.value.trim();
    const email = document.getElementById('partner-email')?.value.trim();
    const phone = document.getElementById('partner-phone')?.value.trim();
    const password = document.getElementById('partner-password')?.value;
    const description = document.getElementById('partner-description')?.value.trim();

    const btn = document.getElementById('btn-register-partner');
    if(btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Inscription...'; }

    fetch(App.apiUrl('Controller/AuthController.php'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'register-partner',
            nom_entreprise: nomEntreprise,
            nom_contact: nom,
            prenom_contact: prenom,
            email: email,
            password: password,
            telephone: phone,
            description: description || ''
        })
    })
    .then(res => res.text())
    .then(text => {
        let result;
        try {
            let startIndex = text.indexOf('{');
            if (startIndex === -1) throw new Error("RïÃ‚Â¿Ã‚Â½ponse serveur invalide ou vide");
            result = JSON.parse(text.substring(startIndex));
        } catch (e) {
            throw new Error("Erreur de format depuis PHP: " + text);
        }
        
        if(result.success) {
            Auth.showAlert('success', result.message);
            setTimeout(() => { window.location.href = App.apiUrl('View/FrontOffice/login.php'); }, 2000);
        } else {
            Auth.showAlert('error', result.message);
            if(btn) { btn.disabled = false; btn.innerHTML = '<span><i class="fa-solid fa-rocket"></i></span> Soumettre candidature'; }
        }
    })
    .catch(err => {
        Auth.showAlert('error', 'Erreur serveur: ' + err.message);
        if(btn) { btn.disabled = false; btn.innerHTML = '<span><i class="fa-solid fa-rocket"></i></span> Soumettre candidature'; }
    });
  },

  // --- Forgot Password ---
  forgotEmailStore: '', // store email for subsequent steps
  forgotCodeStore: '',  // store code for final step

  handleForgotPasswordRequest(e) {
    e.preventDefault();
    Auth.clearErrors();
    const identifier = document.getElementById('forgot-email')?.value.trim();

    if (!identifier) { Auth.showError('forgot-email', 'Veuillez entrer votre email ou téléphone'); return; }
    
    const isEmail = Auth.isValidEmail(identifier);
    const isPhone = /^[0-9+\s\-]{8,15}$/.test(identifier);
    
    if (!isEmail && !isPhone) { 
        Auth.showError('forgot-email', 'Format invalide (Email ou Téléphone)'); 
        return; 
    }

    const btn = document.getElementById('btn-forgot-email');
    if(btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Envoi...'; }

    fetch(App.apiUrl('Controller/AuthController.php'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'forgot-password-request', email: identifier })
    })
    .then(res => res.text())
    .then(text => {
        let result;
        try {
            let start = text.indexOf('{');
            result = JSON.parse(text.substring(start));
        } catch(err) { throw new Error("Erreur serveur"); }

        if(result.success) {
            Auth.showAlert('success', result.message);
            Auth.forgotEmailStore = identifier;
            document.getElementById('forgot-form-email').classList.add('hidden');
            document.getElementById('forgot-form-code').classList.remove('hidden');
            Auth.startResendTimer();
        } else {
            Auth.showAlert('error', result.message);
        }
    })
    .catch(err => Auth.showAlert('error', 'Erreur système'))
    .finally(() => {
        if(btn) { btn.disabled = false; btn.innerHTML = '<span><span class="input-icon"><i class="fa-solid fa-paper-plane"></i></span> Envoyer le code</span>'; }
    });
  },

  resendTimerInterval: null,
  startResendTimer() {
    const btn = document.getElementById('btn-resend-code');
    if (!btn) return;
    
    clearInterval(Auth.resendTimerInterval);
    let timeLeft = 30;
    btn.disabled = true;
    btn.textContent = `Renvoyer le code (${timeLeft}s)`;
    
    Auth.resendTimerInterval = setInterval(() => {
        timeLeft--;
        if (timeLeft <= 0) {
            clearInterval(Auth.resendTimerInterval);
            btn.disabled = false;
            btn.textContent = "Renvoyer le code";
        } else {
            btn.textContent = `Renvoyer le code (${timeLeft}s)`;
        }
    }, 1000);
  },

  handleResendCode() {
    if (!Auth.forgotEmailStore) return;
    
    const btn = document.getElementById('btn-resend-code');
    if(btn) { btn.disabled = true; btn.textContent = 'Envoi...'; }

    fetch(App.apiUrl('Controller/AuthController.php'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'forgot-password-request', email: Auth.forgotEmailStore })
    })
    .then(res => res.text())
    .then(text => {
        let result;
        try {
            let start = text.indexOf('{');
            result = JSON.parse(text.substring(start));
        } catch(err) { throw new Error("Erreur serveur"); }

        if(result.success) {
            Auth.showAlert('success', 'Nouveau code envoyé.');
            Auth.startResendTimer();
        } else {
            Auth.showAlert('error', result.message);
            if(btn) { btn.disabled = false; btn.textContent = 'Renvoyer le code'; }
        }
    })
    .catch(err => {
        Auth.showAlert('error', 'Erreur système');
        if(btn) { btn.disabled = false; btn.textContent = 'Renvoyer le code'; }
    });
  },

  handleVerifyCode(e) {
    e.preventDefault();
    Auth.clearErrors();
    const code = document.getElementById('forgot-code')?.value.trim();

    if (!code || code.length !== 6) { Auth.showError('forgot-code', 'Veuillez entrer le code à 6 chiffres'); return; }

    const btn = document.getElementById('btn-forgot-code');
    if(btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Vérification...'; }

    fetch(App.apiUrl('Controller/AuthController.php'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'verify-reset-code', email: Auth.forgotEmailStore, code: code })
    })
    .then(res => res.text())
    .then(text => {
        let result;
        try {
            let start = text.indexOf('{');
            result = JSON.parse(text.substring(start));
        } catch(err) { throw new Error("Erreur serveur"); }

        if(result.success) {
            Auth.showAlert('success', result.message);
            Auth.forgotCodeStore = code;
            document.getElementById('forgot-form-code').classList.add('hidden');
            document.getElementById('forgot-form-password').classList.remove('hidden');
            // Init password toggle specifically for the new form
            Auth.initPasswordToggles();
        } else {
            Auth.showError('forgot-code', result.message);
        }
    })
    .catch(err => Auth.showAlert('error', 'Erreur système'))
    .finally(() => {
        if(btn) { btn.disabled = false; btn.innerHTML = '<span><span class="input-icon"><i class="fa-solid fa-check"></i></span> Vérifier le code</span>'; }
    });
  },

  handleResetPasswordFinal(e) {
    e.preventDefault();
    Auth.clearErrors();
    const newPassword = document.getElementById('forgot-new-password')?.value;

    if (!newPassword || newPassword.length < 6) { Auth.showError('forgot-new-password', 'Minimum 6 caractères'); return; }

    const btn = document.getElementById('btn-forgot-password');
    if(btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enregistrement...'; }

    fetch(App.apiUrl('Controller/AuthController.php'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            action: 'reset-password-final', 
            email: Auth.forgotEmailStore, 
            code: Auth.forgotCodeStore,
            new_password: newPassword 
        })
    })
    .then(res => res.text())
    .then(text => {
        let result;
        try {
            let start = text.indexOf('{');
            result = JSON.parse(text.substring(start));
        } catch(err) { throw new Error("Erreur serveur"); }

        if(result.success) {
            document.getElementById('forgot-form-password').classList.add('hidden');
            document.getElementById('auth-alert').style.display = 'none';
            document.getElementById('forgot-success').classList.remove('hidden');
        } else {
            Auth.showError('forgot-new-password', result.message);
        }
    })
    .catch(err => Auth.showAlert('error', 'Erreur système'))
    .finally(() => {
        if(btn) { btn.disabled = false; btn.innerHTML = '<span><span class="input-icon"><i class="fa-solid fa-save"></i></span> Réinitialiser</span>'; }
    });
  },

  // --- Validation Helpers ---
  // ================================================================
  // CONTROLE DE SAISIE : Vérification du format email (regex)
  // ================================================================
  isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  },

  clearErrors() {
    document.querySelectorAll('.form-input.error, .form-select.error').forEach(el => el.classList.remove('error'));
    document.querySelectorAll('.form-error').forEach(el => {
        el.classList.remove('visible');
        el.textContent = '';
        el.style.display = 'none'; // Assure qu'il disparait
    });
  },

  showError(inputId, message) {
    const input = document.getElementById(inputId);
    const errorEl = document.getElementById(inputId + '-error');
    if (input) input.classList.add('error');
    if (errorEl) {
      errorEl.textContent = message;
      errorEl.classList.add('visible');
      errorEl.style.display = 'block'; // Montrer l'erreur
      errorEl.style.color = '#e74c3c'; // Style de base rouge vif
      errorEl.style.fontSize = '0.85rem';
      errorEl.style.marginTop = '4px';
    }
  },

  showAlert(type, message) {
    const container = document.getElementById('auth-alert');
    if (container) {
      container.className = 'auth-alert ' + type;
      container.innerHTML = `<span>${type === 'success' ? '<i class="fa-solid fa-check"></i>' : type === 'error' ? '<i class="fa-solid fa-triangle-exclamation"></i>' : '<i class="fa-solid fa-circle-info"></i>'}</span> ${message}`;
      container.style.display = 'flex';
    }
  },

  // --- Password Toggle ---
  togglePassword(btn) {
    if (!btn) return;
    const targetId = btn.getAttribute('data-target');
    let input = null;
    if (targetId) {
      input = document.getElementById(targetId);
    }
    if (!input) {
      const wrapper = btn.closest('.input-wrapper');
      input = wrapper ? wrapper.querySelector('input[type="password"], input[type="text"]') : null;
    }
    if (!input) return;

    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    btn.innerHTML = isPassword ? '<i class="fa-solid fa-eye-slash"></i>' : '<i class="fa-solid fa-eye"></i>';
    btn.setAttribute('aria-label', isPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
    btn.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
  },

  initPasswordToggles() {
    document.querySelectorAll('.password-toggle').forEach(btn => {
      if (btn.dataset.bound === '1') return;
      btn.dataset.bound = '1';
      btn.addEventListener('click', () => {
        this.togglePassword(btn);
      });
    });
  },

  // --- Password Strength ---
  initPasswordStrength() {
    const input = document.getElementById('reg-password') || document.getElementById('partner-password');
    if (!input) return;

    input.addEventListener('input', () => {
      const val = input.value;
      let score = 0;
      if (val.length >= 6) score++;
      if (val.length >= 8) score++;
      if (/[A-Z]/.test(val)) score++;
      if (/[0-9]/.test(val)) score++;
      if (/[^A-Za-z0-9]/.test(val)) score++;

      const bars = document.querySelectorAll('.strength-bar');
      const text = document.querySelector('.strength-text');

      bars.forEach((bar, i) => {
        bar.className = 'strength-bar';
        if (score >= 1 && i === 0) bar.classList.add(score <= 2 ? 'weak' : score <= 3 ? 'medium' : 'strong');
        if (score >= 3 && i === 1) bar.classList.add(score <= 3 ? 'medium' : 'strong');
        if (score >= 4 && i === 2) bar.classList.add('strong');
        if (score >= 5 && i === 3) bar.classList.add('strong');
      });

      if (text) {
        if (score <= 2) text.textContent = 'Faible';
        else if (score <= 3) text.textContent = 'Moyen';
        else text.textContent = 'Fort';
        text.style.color = score <= 2 ? 'var(--color-danger)' : score <= 3 ? 'var(--color-warning)' : 'var(--color-success)';
      }
    });
  },

  // --- Real-time Validation ---
  initRealtimeValidation() {
    document.querySelectorAll('.form-input[required], .form-select[required]').forEach(input => {
      input.addEventListener('blur', () => {
        if (!input.value.trim()) {
          input.classList.add('error');
        } else {
          input.classList.remove('error');
          input.classList.add('success');
        }
      });
      input.addEventListener('input', () => {
        input.classList.remove('error');
      });
    });
  },

  // --- Tag Selection ---
  initTags() {
    document.querySelectorAll('.tag').forEach(tag => {
      tag.addEventListener('click', () => {
        tag.classList.toggle('selected');
      });
    });
  },

  // --- Budget Slider ---
  initBudgetSlider() {
    const slider = document.getElementById('reg-budget');
    const value = document.getElementById('budget-value');
    if (slider && value) {
      slider.addEventListener('input', () => {
        value.textContent = slider.value + ' DT';
      });
    }
  },

  // --- File Upload Preview ---
  initFileUpload() {
    const uploadArea = document.getElementById('logo-upload');
    const fileInput = document.getElementById('logo-file');
    if (!uploadArea || !fileInput) return;

    uploadArea.addEventListener('click', () => fileInput.click());
    uploadArea.addEventListener('dragover', (e) => { e.preventDefault(); uploadArea.style.borderColor = 'var(--color-primary)'; });
    uploadArea.addEventListener('dragleave', () => { uploadArea.style.borderColor = ''; });
    uploadArea.addEventListener('drop', (e) => {
      e.preventDefault();
      uploadArea.style.borderColor = '';
      if (e.dataTransfer.files.length) {
        handleFile(e.dataTransfer.files[0]);
      }
    });

    fileInput.addEventListener('change', () => {
      if (fileInput.files.length) {
        handleFile(fileInput.files[0]);
      }
    });

    function handleFile(file) {
      if (!file.type.startsWith('image/')) return;
      const reader = new FileReader();
      reader.onload = (e) => {
        uploadArea.innerHTML = `<img src="${e.target.result}" class="preview-img" alt="Logo preview"><p class="upload-text mt-2" style="margin-top:8px">Cliquez pour changer</p>`;
      };
      reader.readAsDataURL(file);
    }
  },

  // --- Auto-focus first input ---
  autoFocus() {
    const first = document.querySelector('.form-step.active .form-input, .form-input');
    if (first) first.focus();
  },

  // --- Init All ---
  init() {
    this.initPasswordToggles();
    this.initPasswordStrength();
    this.initRealtimeValidation();
    this.initTags();
    this.initBudgetSlider();
    this.initFileUpload();
    setTimeout(() => this.autoFocus(), 300);
  }
};

// Init on DOM ready
document.addEventListener('DOMContentLoaded', () => Auth.init());








