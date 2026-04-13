/* ============================================
   CAREMEAL — AUTH
   auth.js — Login, Register, Validation
   ============================================ */

const Auth = {
  // --- Login ---
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

    // Find user
    const user = App.findUserByEmail(email);
    if (!user) {
      Auth.showError('login-email', 'Aucun compte trouvé avec cet email');
      return;
    }

    if (user.password !== password) {
      Auth.showError('login-password', 'Mot de passe incorrect');
      return;
    }

    if (user.status === 'banned') {
      Auth.showAlert('error', 'Votre compte a été suspendu. Contactez le support.');
      return;
    }

    if (user.status === 'pending') {
      Auth.showAlert('info', 'Votre compte est en attente de validation par un administrateur.');
      return;
    }

    // Success - login
    App.setCurrentUser(user);
    App.addLog('Connexion réussie');

    // Redirect based on role
    switch (user.role) {
      case 'student': window.location.href = 'student/dashboard.html'; break;
      case 'partner': window.location.href = 'partner/dashboard.html'; break;
      case 'admin': window.location.href = 'admin/dashboard.html'; break;
    }
  },

  // --- Register Student ---
  currentStep: 1,
  totalSteps: 3,

  nextStep() {
    if (!this.validateStep(this.currentStep)) return;
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

  validateStep(step) {
    Auth.clearErrors();
    let valid = true;

    if (step === 1) {
      const name = document.getElementById('reg-name')?.value.trim();
      const email = document.getElementById('reg-email')?.value.trim();
      const password = document.getElementById('reg-password')?.value;
      const confirm = document.getElementById('reg-confirm')?.value;

      if (!name || name.length < 3) { Auth.showError('reg-name', 'Nom complet requis (min. 3 caractères)'); valid = false; }
      if (!email) { Auth.showError('reg-email', 'Email requis'); valid = false; }
      else if (!Auth.isValidEmail(email)) { Auth.showError('reg-email', 'Format d\'email invalide'); valid = false; }
      else if (App.findUserByEmail(email)) { Auth.showError('reg-email', 'Cet email est déjà utilisé'); valid = false; }
      if (!password || password.length < 6) { Auth.showError('reg-password', 'Minimum 6 caractères'); valid = false; }
      if (password !== confirm) { Auth.showError('reg-confirm', 'Les mots de passe ne correspondent pas'); valid = false; }
    }

    if (step === 2) {
      const university = document.getElementById('reg-university')?.value;
      if (!university) { Auth.showError('reg-university', 'Veuillez sélectionner votre université'); valid = false; }
    }

    return valid;
  },

  handleStudentRegister(e) {
    e.preventDefault();
    if (!this.validateStep(this.currentStep)) return;

    const selectedTags = document.querySelectorAll('.tag.selected');
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
    window.location.href = 'email-verification.html';
  },

  // --- Register Partner ---
  handlePartnerRegister(e) {
    e.preventDefault();
    Auth.clearErrors();
    let valid = true;

    const name = document.getElementById('partner-name')?.value.trim();
    const type = document.getElementById('partner-type')?.value;
    const address = document.getElementById('partner-address')?.value.trim();
    const email = document.getElementById('partner-email')?.value.trim();
    const phone = document.getElementById('partner-phone')?.value.trim();
    const password = document.getElementById('partner-password')?.value;
    const description = document.getElementById('partner-description')?.value.trim();

    if (!name || name.length < 2) { Auth.showError('partner-name', 'Nom requis'); valid = false; }
    if (!type) { Auth.showError('partner-type', 'Sélectionnez un type'); valid = false; }
    if (!address) { Auth.showError('partner-address', 'Adresse requise'); valid = false; }
    if (!email) { Auth.showError('partner-email', 'Email requis'); valid = false; }
    else if (!Auth.isValidEmail(email)) { Auth.showError('partner-email', 'Format invalide'); valid = false; }
    else if (App.findUserByEmail(email)) { Auth.showError('partner-email', 'Email déjà utilisé'); valid = false; }
    if (!phone) { Auth.showError('partner-phone', 'Téléphone requis'); valid = false; }
    if (!password || password.length < 6) { Auth.showError('partner-password', 'Min. 6 caractères'); valid = false; }

    if (!valid) return;

    const user = App.addUser({
      name, email, password,
      role: 'partner',
      type, address, phone,
      description: description || '',
      horaires: { lun: '08:00-18:00', mar: '08:00-18:00', mer: '08:00-18:00', jeu: '08:00-18:00', ven: '08:00-18:00', sam: '09:00-14:00', dim: 'Fermé' },
      mealsSaved: 0, avgRating: 0, reviewCount: 0
    });

    App.setCurrentUser(user);
    window.location.href = 'email-verification.html';
  },

  // --- Forgot Password ---
  handleForgotPassword(e) {
    e.preventDefault();
    Auth.clearErrors();
    const email = document.getElementById('forgot-email')?.value.trim();

    if (!email) { Auth.showError('forgot-email', 'Veuillez entrer votre email'); return; }
    if (!Auth.isValidEmail(email)) { Auth.showError('forgot-email', 'Format invalide'); return; }

    // Show success regardless (security)
    Auth.showAlert('success', 'Si un compte existe avec cet email, un lien de réinitialisation a été envoyé.');
    document.getElementById('forgot-form')?.classList.add('hidden');
    document.getElementById('forgot-success')?.classList.remove('hidden');
  },

  // --- Validation Helpers ---
  isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  },

  showError(inputId, message) {
    const input = document.getElementById(inputId);
    const errorEl = document.getElementById(inputId + '-error');
    if (input) input.classList.add('error');
    if (errorEl) {
      errorEl.textContent = message;
      errorEl.classList.add('visible');
    }
  },

  clearErrors() {
    document.querySelectorAll('.form-input.error, .form-select.error').forEach(el => el.classList.remove('error'));
    document.querySelectorAll('.form-error').forEach(el => el.classList.remove('visible'));
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
  initPasswordToggles() {
    document.querySelectorAll('.password-toggle').forEach(btn => {
      btn.addEventListener('click', () => {
        const input = btn.parentElement.querySelector('input');
        if (input) {
          const isPassword = input.type === 'password';
          input.type = isPassword ? 'text' : 'password';
          btn.innerHTML = isPassword ? '<i class="fa-solid fa-eye-slash"></i>' : '<i class="fa-solid fa-eye"></i>';
        }
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
