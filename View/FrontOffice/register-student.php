ÃƒÂ¯Ã‚Â¿Ã‚Â½<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="CrÃƒÆ’Ã‚Â©ez votre compte ÃƒÆ’Ã‚Â©tudiant CareMeal en quelques ÃƒÆ’Ã‚Â©tapes et commencez ÃƒÆ’Ã‚Â  sauver des repas.">
  <title>Inscription ÃƒÂ¯Ã‚Â¿Ã‚Â½0tudiant ÃƒÂ¯Ã‚Â¿Ã‚Â½ CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/main.css">
  <link rel="stylesheet" href="css/auth.css">
</head>
<body>
  <div class="auth-page">
    <!-- Left: Visual -->
    <div class="auth-visual">
      <div class="shape shape-1"></div>
      <div class="shape shape-2"></div>
      <div class="shape shape-3"></div>
      <div class="auth-visual-content">
        <div style="font-size:5rem;margin-bottom:24px;animation:float 3s ease-in-out infinite;"><i class="fa-solid fa-graduation-cap"></i></div>
        <h2>ÃƒÂ¯Ã‚Â¿Ã‚Â½0tudiants, c'est pour vous !</h2>
        <p>Inscrivez-vous gratuitement et profitez de repas de qualitÃƒÆ’Ã‚Â© ÃƒÆ’Ã‚Â  prix rÃƒÆ’Ã‚Â©duits prÃƒÆ’Ã‚Â¨s de votre campus.</p>
        <div style="display:flex;flex-direction:column;gap:16px;margin-top:48px;text-align:left;">
          <div style="display:flex;align-items:center;gap:12px;">
            <span style="font-size:1.5rem;"><i class="fa-solid fa-coins"></i></span>
            <span style="color:var(--color-text);font-size:0.9rem;">ÃƒÂ¯Ã‚Â¿Ã‚Â½0conomisez jusqu'ÃƒÆ’Ã‚Â  60% sur vos repas</span>
          </div>
          <div style="display:flex;align-items:center;gap:12px;">
            <span style="font-size:1.5rem;"><i class="fa-solid fa-seedling"></i></span>
            <span style="color:var(--color-text);font-size:0.9rem;">Agissez contre le gaspillage alimentaire</span>
          </div>
          <div style="display:flex;align-items:center;gap:12px;">
            <span style="font-size:1.5rem;"><i class="fa-solid fa-star"></i></span>
            <span style="color:var(--color-text);font-size:0.9rem;">Gagnez des points et des rÃƒÆ’Ã‚Â©compenses</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Right: Form -->
    <div class="auth-form-side">
      <div class="auth-form-container">
        <div class="auth-form-header">
          <a href="index.html" class="brand-link">
            <div class="brand-icon"><img src="assets/logo.png" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;"></div>
            Care<span style="color:var(--color-primary)">Meal</span>
          </a>
          <h1>CrÃƒÆ’Ã‚Â©er un compte <i class="fa-solid fa-rocket"></i></h1>
          <p>En 3 ÃƒÆ’Ã‚Â©tapes, vous serez prÃƒÆ’Ã‚Âªt ÃƒÆ’Ã‚Â  sauver des repas !</p>
        </div>

        <!-- Step indicators -->
        <div class="step-indicators">
          <div class="step-dot active"></div>
          <div class="step-line"></div>
          <div class="step-dot"></div>
          <div class="step-line"></div>
          <div class="step-dot"></div>
        </div>

        <div id="auth-alert" class="auth-alert" style="display:none;"></div>

        <form id="register-student-form" onsubmit="Auth.handleStudentRegister(event)">
          <!-- Step 1: IdentitÃƒÆ’Ã‚Â© -->
          <div class="form-step active" id="step-1">
            <p class="step-title">ÃƒÂ¯Ã‚Â¿Ã‚Â½0tape 1 ÃƒÂ¯Ã‚Â¿Ã‚Â½ Vos informations</p>

            <div class="form-group">
              <label for="reg-name">Nom complet <span class="required">*</span></label>
              <div class="input-wrapper">
                <input type="text" id="reg-name" class="form-input" placeholder="PrÃƒÆ’Ã‚Â©nom et nom" required>
                <span class="input-icon"><i class="fa-solid fa-user"></i></span>
              </div>
              <div class="form-error" id="reg-name-error"></div>
            </div>

            <div class="form-group">
              <label for="reg-email">Email <span class="required">*</span></label>
              <div class="input-wrapper">
                <input type="email" id="reg-email" class="form-input" placeholder="votre@email.com" required>
                <span class="input-icon"><i class="fa-solid fa-envelope"></i></span>
              </div>
              <div class="form-error" id="reg-email-error"></div>
            </div>

            <div class="form-group">
              <label for="reg-password">Mot de passe <span class="required">*</span></label>
              <div class="input-wrapper">
                <input type="password" id="reg-password" class="form-input" placeholder="Minimum 6 caractÃƒÆ’Ã‚Â¨res" required>
                <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                <button type="button" class="password-toggle"><i class="fa-solid fa-eye"></i></button>
              </div>
              <div class="password-strength">
                <div class="strength-bar"></div>
                <div class="strength-bar"></div>
                <div class="strength-bar"></div>
                <div class="strength-bar"></div>
              </div>
              <div class="strength-text"></div>
              <div class="form-error" id="reg-password-error"></div>
            </div>

            <div class="form-group">
              <label for="reg-confirm">Confirmer le mot de passe <span class="required">*</span></label>
              <div class="input-wrapper">
                <input type="password" id="reg-confirm" class="form-input" placeholder="Retapez votre mot de passe" required>
                <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
              </div>
              <div class="form-error" id="reg-confirm-error"></div>
            </div>

            <button type="button" class="btn btn-primary btn-full btn-lg" onclick="Auth.nextStep()">
              Continuer <span>ÃƒÂ¯Ã‚Â¿Ã‚Â½ </span>
            </button>
          </div>

          <!-- Step 2: Localisation -->
          <div class="form-step" id="step-2">
            <p class="step-title">ÃƒÂ¯Ã‚Â¿Ã‚Â½0tape 2 ÃƒÂ¯Ã‚Â¿Ã‚Â½ Votre campus</p>

            <div class="form-group">
              <label for="reg-university">UniversitÃƒÆ’Ã‚Â© / ÃƒÂ¯Ã‚Â¿Ã‚Â½0cole <span class="required">*</span></label>
              <div class="input-wrapper">
                <select id="reg-university" class="form-select" required>
                  <option value="">SÃƒÆ’Ã‚Â©lectionnez votre ÃƒÆ’Ã‚Â©tablissement</option>
                  <option value="ESPRIT">ESPRIT</option>
                  <option value="INSAT">INSAT</option>
                  <option value="ENIT">ENIT</option>
                  <option value="FST">FST - FacultÃƒÆ’Ã‚Â© des Sciences de Tunis</option>
                  <option value="IHEC">IHEC Carthage</option>
                  <option value="ISTIC">ISTIC</option>
                  <option value="ULT">UniversitÃƒÆ’Ã‚Â© Libre de Tunis</option>
                  <option value="ENSI">ENSI</option>
                  <option value="ISG">ISG Tunis</option>
                  <option value="ESEN">ESEN Manouba</option>
                  <option value="autre">Autre</option>
                </select>
                <span class="input-icon"><i class="fa-solid fa-school"></i></span>
                <span class="select-arrow">ÃƒÂ¯Ã‚Â¿Ã‚Â½ÃƒÂ¯Ã‚Â¿Ã‚Â½</span>
              </div>
              <div class="form-error" id="reg-university-error"></div>
            </div>

            <div class="form-group">
              <label for="reg-quartier">Quartier / Zone</label>
              <div class="input-wrapper">
                <select id="reg-quartier" class="form-select">
                  <option value="">SÃƒÆ’Ã‚Â©lectionnez votre zone</option>
                  <option value="Centre Ville">Centre Ville</option>
                  <option value="Lac 1">Lac 1</option>
                  <option value="Lac 2">Lac 2</option>
                  <option value="Ariana">Ariana</option>
                  <option value="Menzah">Menzah</option>
                  <option value="Manar">El Manar</option>
                  <option value="Marsa">La Marsa</option>
                  <option value="Bardo">Le Bardo</option>
                  <option value="Manouba">Manouba</option>
                  <option value="Ben Arous">Ben Arous</option>
                </select>
                <span class="input-icon"><i class="fa-solid fa-location-dot"></i></span>
                <span class="select-arrow">ÃƒÂ¯Ã‚Â¿Ã‚Â½ÃƒÂ¯Ã‚Â¿Ã‚Â½</span>
              </div>
            </div>

            <div style="display:flex;gap:12px;margin-top:24px;">
              <button type="button" class="btn btn-secondary btn-lg" style="flex:1;" onclick="Auth.prevStep()">
                <span>ÃƒÂ¯Ã‚Â¿Ã‚Â½ ÃƒÂ¯Ã‚Â¿Ã‚Â½</span> Retour
              </button>
              <button type="button" class="btn btn-primary btn-lg" style="flex:2;" onclick="Auth.nextStep()">
                Continuer <span>ÃƒÂ¯Ã‚Â¿Ã‚Â½ </span>
              </button>
            </div>
          </div>

          <!-- Step 3: PrÃƒÆ’Ã‚Â©fÃƒÆ’Ã‚Â©rences -->
          <div class="form-step" id="step-3">
            <p class="step-title">ÃƒÂ¯Ã‚Â¿Ã‚Â½0tape 3 ÃƒÂ¯Ã‚Â¿Ã‚Â½ Vos prÃƒÆ’Ã‚Â©fÃƒÆ’Ã‚Â©rences</p>

            <div class="form-group">
              <label>PrÃƒÆ’Ã‚Â©fÃƒÆ’Ã‚Â©rences alimentaires</label>
              <p style="font-size:0.8rem;color:var(--color-text-muted);margin-bottom:12px;">SÃƒÆ’Ã‚Â©lectionnez tout ce qui vous concerne</p>
              <div class="tags-grid">
                <div class="tag" data-value="halal"><span class="tag-emoji"><i class="fa-solid fa-star-and-crescent"></i></span> Halal</div>
                <div class="tag" data-value="vegetarien"><span class="tag-emoji"><i class="fa-solid fa-leaf"></i></span> VÃƒÆ’Ã‚Â©gÃƒÆ’Ã‚Â©tarien</div>
                <div class="tag" data-value="vegan"><span class="tag-emoji"><i class="fa-solid fa-seedling"></i></span> VÃƒÆ’Ã‚Â©gan</div>
                <div class="tag" data-value="sans-gluten"><span class="tag-emoji"><i class="fa-solid fa-wheat-awn"></i></span> Sans gluten</div>
                <div class="tag" data-value="bio"><span class="tag-emoji"><i class="fa-solid fa-leaf"></i></span> Bio</div>
                <div class="tag" data-value="sans-lactose"><span class="tag-emoji"><i class="fa-solid fa-glass-water"></i></span> Sans lactose</div>
                <div class="tag" data-value="budget"><span class="tag-emoji"><i class="fa-solid fa-coins"></i></span> Petit budget</div>
                <div class="tag" data-value="equilibre"><span class="tag-emoji"><i class="fa-solid fa-scale-balanced"></i></span> ÃƒÂ¯Ã‚Â¿Ã‚Â½0quilibrÃƒÆ’Ã‚Â©</div>
                
                <!-- Nouvelle option "Autre" -->
                <div class="tag" id="tag-autre" data-value="autre">
                  <span class="tag-emoji"><i class="fa-solid fa-plus"></i></span> Autre
                </div>
              </div>

              <!-- Champ de saisie manuel qui apparaÃƒÆ’Ã‚Â®t si "Autre" est sÃƒÆ’Ã‚Â©lectionnÃƒÆ’Ã‚Â© -->
              <div id="champ-autre-container" style="display: none; margin-top: 16px; animation: slideIn 0.3s ease;">
                <div style="display: flex; gap: 8px;">
                  <div class="input-wrapper" style="flex: 1;">
                    <input type="text" id="autre-preference-text" class="form-input" placeholder="Ajouter une prÃƒÆ’Ã‚Â©fÃƒÆ’Ã‚Â©rence (ex: Sans sel)">
                    <span class="input-icon"><i class="fa-solid fa-pen"></i></span>
                  </div>
                  <button type="button" id="btn-add-tag" class="btn btn-primary" style="width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
                    <i class="fa-solid fa-plus"></i>
                  </button>
                </div>
              </div>
            </div>

            <div class="form-group" style="margin-top:24px;">
              <label for="reg-budget">Budget maximum par repas</label>
              <div class="range-slider">
                <input type="range" id="reg-budget" min="2" max="20" value="8" step="1">
                <div class="range-value" id="budget-value">8 DT</div>
              </div>
            </div>

            <div class="form-group">
              <label for="reg-frequency">FrÃƒÆ’Ã‚Â©quence souhaitÃƒÆ’Ã‚Â©e</label>
              <div class="input-wrapper">
                <select id="reg-frequency" class="form-select" style="padding-left:16px;">
                  <option value="quotidien">Tous les jours</option>
                  <option value="hebdomadaire">Quelques fois par semaine</option>
                  <option value="occasionnel">Occasionnellement</option>
                </select>
                <span class="select-arrow">ÃƒÂ¯Ã‚Â¿Ã‚Â½ÃƒÂ¯Ã‚Â¿Ã‚Â½</span>
              </div>
            </div>

            <div style="display:flex;gap:12px;margin-top:24px;">
              <button type="button" class="btn btn-secondary btn-lg" style="flex:1;" onclick="Auth.prevStep()">
                <span>ÃƒÂ¯Ã‚Â¿Ã‚Â½ ÃƒÂ¯Ã‚Â¿Ã‚Â½</span> Retour
              </button>
              <button type="submit" class="btn btn-primary btn-lg" style="flex:2;" id="btn-register-student">
                <span><i class="fa-solid fa-party-horn"></i></span> CrÃƒÆ’Ã‚Â©er mon compte
              </button>
            </div>
          </div>
        </form>

        <div class="auth-footer">
          <p>DÃƒÆ’Ã‚Â©jÃƒÆ’Ã‚Â  un compte ? <a href="login.html">Se connecter</a></p>
        </div>
      </div>
    </div>
  </div>

  <script src="js/app.js"></script>
  <script src="js/auth.js"></script>
  <script>
    // Logique pour personnaliser la liste des prÃƒÆ’Ã‚Â©fÃƒÆ’Ã‚Â©rences
    document.addEventListener("DOMContentLoaded", () => {
      const tagAutre = document.getElementById("tag-autre");
      const containerAutre = document.getElementById("champ-autre-container");
      const inputAutre = document.getElementById("autre-preference-text");
      const btnAddTag = document.getElementById("btn-add-tag");
      
      // 1. Bouton "+" pour afficher le menu d'ajout
      tagAutre.addEventListener("click", () => {
        setTimeout(() => {
          if (tagAutre.classList.contains("selected")) {
            containerAutre.style.display = "block";
            inputAutre.focus();
          } else {
            containerAutre.style.display = "none";
            inputAutre.value = "";
          }
        }, 50);
      });

      // 2. Action d'ajout de la nouvelle prÃƒÆ’Ã‚Â©fÃƒÆ’Ã‚Â©rence ÃƒÆ’Ã‚Â  la liste en dur
      function ajouterNouveauTag() {
        const valeur = inputAutre.value.trim();
        if (valeur === "") return; // Ne rien faire si c'est vide

        // CrÃƒÆ’Ã‚Â©er l'ÃƒÆ’Ã‚Â©lÃƒÆ’Ã‚Â©ment HTML (Tag) manuellement
        const newTag = document.createElement("div");
        newTag.className = "tag selected";
        newTag.setAttribute("data-value", valeur.toLowerCase().replace(/\s+/g, '-'));
        
        // Ajouter du texte et une icone gÃƒÆ’Ã‚Â©nÃƒÆ’Ã‚Â©rique
        newTag.innerHTML = '<span class="tag-emoji"><i class="fa-solid fa-check"></i></span> ' + valeur;
        
        // Activer l'interaction Clic (comme Auth.js le fait)
        newTag.addEventListener('click', () => {
          newTag.classList.toggle('selected');
        });

        // L'insÃƒÆ’Ã‚Â©rer dans la grille JUSTE AVANT le bouton "Autre"
        const grid = document.querySelector(".tags-grid");
        grid.insertBefore(newTag, tagAutre);

        // Nettoyer et fermer
        inputAutre.value = "";
        tagAutre.classList.remove("selected");
        containerAutre.style.display = "none";
      }

      // ÃƒÂ¯Ã‚Â¿Ã‚Â½0couter le clic sur le bouton "+"
      btnAddTag.addEventListener("click", ajouterNouveauTag);

      // ÃƒÂ¯Ã‚Â¿Ã‚Â½0couter la touche "EntrÃƒÆ’Ã‚Â©e"
      inputAutre.addEventListener("keypress", (e) => {
        if (e.key === "Enter") {
          e.preventDefault(); // EmpÃƒÆ’Ã‚Âªcher la soumission du gros formulaire
          ajouterNouveauTag();
        }
      });
    });
  </script>
</body>
</html>

