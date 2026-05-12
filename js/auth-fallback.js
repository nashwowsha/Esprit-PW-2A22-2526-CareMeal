/* Fallback auth flow used when main auth.js is broken/cached. */
(function () {
  const API_URL = "/Controller/AuthController.php";

  function parseJsonSafe(text) {
    const body = String(text || "");
    const start = body.indexOf("{");
    if (start < 0) {
      throw new Error("Reponse serveur invalide");
    }
    return JSON.parse(body.slice(start));
  }

  async function postAuth(payload) {
    const res = await fetch(API_URL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });
    const text = await res.text();
    return parseJsonSafe(text);
  }

  function byId(id) {
    return document.getElementById(id);
  }

  function clearErrors() {
    document.querySelectorAll(".form-input.error, .form-select.error").forEach((el) => el.classList.remove("error"));
    document.querySelectorAll(".form-error").forEach((el) => {
      el.textContent = "";
      el.style.display = "none";
    });
  }

  function showError(inputId, message) {
    const input = byId(inputId);
    const error = byId(inputId + "-error");
    if (input) input.classList.add("error");
    if (error) {
      error.textContent = message;
      error.style.display = "block";
    }
  }

  function showAlert(type, message) {
    const box = byId("auth-alert");
    if (!box) return;
    box.className = "auth-alert " + type;
    box.textContent = message;
    box.style.display = "flex";
  }

  function redirectByRole(role) {
    if (role === "admin") {
      window.location.href = App.apiUrl('View/BackOffice/admin/dashboard.php');
      return;
    }
    if (role === "student" || role === "partner") {
      window.location.href = App.apiUrl('View/FrontOffice/feed.php');
      return;
    }
    window.location.href = App.apiUrl('View/FrontOffice/index.php');
  }

  function persistCurrentUser(dbUser) {
    if (!dbUser) return;
    const user = {
      id: dbUser.id,
      email: dbUser.email,
      role: dbUser.role,
      status: dbUser.status,
      name: ((dbUser.prenom || "") + " " + (dbUser.nom || "")).trim() || dbUser.email || "Utilisateur",
      phone: dbUser.telephone || "",
      createdAt: dbUser.created_at || "",
      university: dbUser.ecole || "",
      ecole: dbUser.ecole || "",
      annee: dbUser.annee_etude || "",
      quartier: dbUser.quartier || "",
      nomEntreprise: dbUser.nom_entreprise || "",
      description: dbUser.description || "",
      linkedin: dbUser.linkedin || "",
      instagram: dbUser.instagram || "",
      facebook: dbUser.facebook || "",
      twitter: dbUser.twitter || "",
      github: dbUser.github || "",
      secteur_activite: dbUser.secteur_activite || "",
      site_web: dbUser.site_web || "",
      prenom: dbUser.prenom || "",
      nom: dbUser.nom || ""
    };

    if (window.App && typeof window.App.setCurrentUser === "function") {
      window.App.setCurrentUser(user);
      return;
    }
    try {
      localStorage.setItem("caremeal_current_user", JSON.stringify(user));
    } catch (_e) {}
  }

  function bindPasswordToggles() {
    document.querySelectorAll(".password-toggle").forEach((btn) => {
      if (btn.dataset.bound === "1") return;
      btn.dataset.bound = "1";
      btn.addEventListener("click", () => {
        const targetId = btn.getAttribute("data-target");
        let input = targetId ? document.getElementById(targetId) : null;
        if (!input) {
          const wrapper = btn.closest(".input-wrapper");
          input = wrapper ? wrapper.querySelector('input[type="password"], input[type="text"]') : null;
        }
        if (!input) return;
        const isHidden = input.type === "password";
        input.type = isHidden ? "text" : "password";
        btn.innerHTML = isHidden ? '<i class="fa-solid fa-eye-slash"></i>' : '<i class="fa-solid fa-eye"></i>';
        btn.setAttribute("aria-label", isHidden ? "Masquer le mot de passe" : "Afficher le mot de passe");
        btn.setAttribute("aria-pressed", isHidden ? "true" : "false");
      });
    });
  }

  const Auth = window.Auth || {};
  Auth.clearErrors = clearErrors;
  Auth.showError = showError;
  Auth.showAlert = showAlert;
  Auth.initPasswordToggles = bindPasswordToggles;

  Auth.handleLogin = async function (e) {
    if (e) e.preventDefault();
    clearErrors();

    const email = (byId("login-email")?.value || "").trim();
    const password = byId("login-password")?.value || "";

    let valid = true;
    if (!email) {
      showError("login-email", "Veuillez entrer votre email");
      valid = false;
    }
    if (!password) {
      showError("login-password", "Veuillez entrer votre mot de passe");
      valid = false;
    }
    if (!valid) return false;

    const btn = byId("btn-login");
    const old = btn ? btn.innerHTML : "";
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = "Connexion...";
    }

    try {
      const result = await postAuth({ action: "login", email, password });
      if (result.success) {
        persistCurrentUser(result.user || null);
        redirectByRole(result.user?.role || "");
        return false;
      }
      if ((result.message || "").toLowerCase().includes("mot de passe")) {
        showError("login-password", result.message);
      } else {
        showError("login-email", result.message || "Connexion echouee");
      }
    } catch (err) {
      showAlert("error", "Erreur serveur: " + err.message);
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = old;
      }
    }
    return false;
  };

  Auth.currentStep = Auth.currentStep || 1;
  Auth.totalSteps = 2;
  Auth.updateStepUI = function () {
    document.querySelectorAll(".form-step").forEach((el) => el.classList.remove("active"));
    const current = byId("step-" + Auth.currentStep);
    if (current) current.classList.add("active");
    document.querySelectorAll(".step-dot").forEach((dot, idx) => {
      dot.classList.remove("active", "completed");
      if (idx + 1 === Auth.currentStep) dot.classList.add("active");
      if (idx + 1 < Auth.currentStep) dot.classList.add("completed");
    });
    document.querySelectorAll(".step-line").forEach((line, idx) => {
      line.classList.toggle("completed", idx + 1 < Auth.currentStep);
    });
  };
  Auth.validateStep = async function (step) {
    clearErrors();
    let valid = true;
    if (step === 1) {
      const fields = [
        ["reg-nom", "Nom requis"],
        ["reg-prenom", "Prenom requis"],
        ["reg-email", "Email requis"],
        ["reg-password", "Mot de passe requis"],
        ["reg-confirm", "Confirmation requise"],
      ];
      fields.forEach(([id, msg]) => {
        if (!(byId(id)?.value || "").trim()) {
          showError(id, msg);
          valid = false;
        }
      });
      if ((byId("reg-password")?.value || "").length < 6) {
        showError("reg-password", "Minimum 6 caracteres");
        valid = false;
      }
      if ((byId("reg-password")?.value || "") !== (byId("reg-confirm")?.value || "")) {
        showError("reg-confirm", "Les mots de passe ne correspondent pas");
        valid = false;
      }
    }
    if (step === 2) {
      const required = [
        ["reg-university", "Universite requise"],
        ["reg-telephone", "Telephone requis"],
        ["reg-annee", "Annee requise"],
      ];
      required.forEach(([id, msg]) => {
        if (!(byId(id)?.value || "").trim()) {
          showError(id, msg);
          valid = false;
        }
      });
    }
    return valid;
  };
  Auth.nextStep = async function () {
    const valid = await Auth.validateStep(Auth.currentStep);
    if (!valid) return;
    if (Auth.currentStep < Auth.totalSteps) {
      Auth.currentStep += 1;
      Auth.updateStepUI();
    }
  };
  Auth.prevStep = function () {
    if (Auth.currentStep > 1) {
      Auth.currentStep -= 1;
      Auth.updateStepUI();
    }
  };

  Auth.handleStudentRegister = async function (e) {
    if (e) e.preventDefault();
    const ok1 = await Auth.validateStep(1);
    const ok2 = await Auth.validateStep(2);
    if (!ok1 || !ok2) {
      if (!ok1) {
        Auth.currentStep = 1;
      } else if (!ok2) {
        Auth.currentStep = 2;
      }
      Auth.updateStepUI();
      return false;
    }

    const payload = {
      action: "register-student",
      nom: (byId("reg-nom")?.value || "").trim(),
      prenom: (byId("reg-prenom")?.value || "").trim(),
      email: (byId("reg-email")?.value || "").trim(),
      password: byId("reg-password")?.value || "",
      sponsorCode: (byId("reg-sponsor")?.value || "").trim(),
      ecole: (byId("reg-university")?.value || "").trim(),
      annee: (byId("reg-annee")?.value || "").trim(),
      telephone: (byId("reg-telephone")?.value || "").trim(),
      quartier: (byId("reg-quartier")?.value || "").trim(),
    };

    const btn = byId("btn-register-student");
    const old = btn ? btn.innerHTML : "";
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = "Inscription...";
    }
    try {
      const result = await postAuth(payload);
      if (result.success) {
        showAlert("success", result.message || "Inscription reussie");
        setTimeout(() => {
          window.location.href = App.apiUrl('View/FrontOffice/login.php');
        }, 900);
      } else {
        showAlert("error", result.message || "Inscription echouee");
      }
    } catch (err) {
      showAlert("error", "Erreur serveur: " + err.message);
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = old;
      }
    }
    return false;
  };

  Auth.currentPartnerStep = Auth.currentPartnerStep || 1;
  Auth.totalPartnerSteps = 2;
  Auth.updatePartnerStepUI = function () {
    document.querySelectorAll(".form-step").forEach((el) => el.classList.remove("active"));
    const current = byId("step-partner-" + Auth.currentPartnerStep);
    if (current) current.classList.add("active");
    document.querySelectorAll(".step-dot").forEach((dot, idx) => {
      dot.classList.remove("active", "completed");
      if (idx + 1 === Auth.currentPartnerStep) dot.classList.add("active");
      if (idx + 1 < Auth.currentPartnerStep) dot.classList.add("completed");
    });
    document.querySelectorAll(".step-line").forEach((line, idx) => {
      line.classList.toggle("completed", idx + 1 < Auth.currentPartnerStep);
    });
  };
  Auth.validatePartnerStep = async function (step) {
    clearErrors();
    let valid = true;
    if (step === 1) {
      const required = [
        ["partner-name", "Etablissement requis"],
        ["partner-type", "Type requis"],
        ["partner-address", "Adresse requise"],
        ["partner-nom", "Nom requis"],
        ["partner-prenom", "Prenom requis"],
        ["partner-email", "Email requis"],
        ["partner-phone", "Telephone requis"],
      ];
      required.forEach(([id, msg]) => {
        if (!(byId(id)?.value || "").trim()) {
          showError(id, msg);
          valid = false;
        }
      });
    }
    if (step === 2 && (byId("partner-password")?.value || "").length < 6) {
      showError("partner-password", "Minimum 6 caracteres");
      valid = false;
    }
    return valid;
  };
  Auth.nextPartnerStep = async function () {
    const valid = await Auth.validatePartnerStep(Auth.currentPartnerStep);
    if (!valid) return;
    if (Auth.currentPartnerStep < Auth.totalPartnerSteps) {
      Auth.currentPartnerStep += 1;
      Auth.updatePartnerStepUI();
    }
  };
  Auth.prevPartnerStep = function () {
    if (Auth.currentPartnerStep > 1) {
      Auth.currentPartnerStep -= 1;
      Auth.updatePartnerStepUI();
    }
  };
  Auth.handlePartnerRegister = async function (e) {
    if (e) e.preventDefault();
    const ok1 = await Auth.validatePartnerStep(1);
    const ok2 = await Auth.validatePartnerStep(2);
    if (!ok1 || !ok2) {
      Auth.currentPartnerStep = ok1 ? 2 : 1;
      Auth.updatePartnerStepUI();
      return false;
    }

    const payload = {
      action: "register-partner",
      nom_entreprise: (byId("partner-name")?.value || "").trim(),
      email: (byId("partner-email")?.value || "").trim(),
      password: byId("partner-password")?.value || "",
      nom_contact: (byId("partner-nom")?.value || "").trim(),
      prenom_contact: (byId("partner-prenom")?.value || "").trim(),
      telephone: (byId("partner-phone")?.value || "").trim(),
      description: (byId("partner-description")?.value || "").trim(),
    };

    const btn = byId("btn-register-partner");
    const old = btn ? btn.innerHTML : "";
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = "Inscription...";
    }
    try {
      const result = await postAuth(payload);
      if (result.success) {
        showAlert("success", result.message || "Inscription reussie");
        setTimeout(() => {
          window.location.href = App.apiUrl('View/FrontOffice/login.php');
        }, 900);
      } else {
        showAlert("error", result.message || "Inscription echouee");
      }
    } catch (err) {
      showAlert("error", "Erreur serveur: " + err.message);
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = old;
      }
    }
    return false;
  };

  window.Auth = Auth;

  document.addEventListener("DOMContentLoaded", () => {
    bindPasswordToggles();
  });
})();

