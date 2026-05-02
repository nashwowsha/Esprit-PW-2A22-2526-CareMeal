<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Créez votre compte étudiant CareMeal en quelques étapes et commencez à sauver des repas.">
  <title>Inscription étudiant - CareMeal</title>
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
        <div style="font-size:5rem;margin-bottom:24px;animation:float 3s ease-in-out infinite;">
              <i class="fa-solid fa-graduation-cap"></i></div>
        <h2>étudiants, c'est pour vous !</h2>
        <p>Inscrivez-vous gratuitement et profitez de repas de qualité à prix réduits près de votre campus.</p>
        <div style="display:flex;flex-direction:column;gap:16px;margin-top:48px;text-align:left;">
          <div style="display:flex;align-items:center;gap:12px;">
            <span style="font-size:1.5rem;">
              <span class="input-icon"><i class="fa-solid fa-coins"></i></span>
            <span style="color:var(--color-text);font-size:0.9rem;">économisez jusqu'à 60% sur vos repas</span>
          </div>
          <div style="display:flex;align-items:center;gap:12px;">
            <span style="font-size:1.5rem;">
              <span class="input-icon"><i class="fa-solid fa-seedling"></i></span>
            <span style="color:var(--color-text);font-size:0.9rem;">Agissez contre le gaspillage alimentaire</span>
          </div>
          <div style="display:flex;align-items:center;gap:12px;">
            <span style="font-size:1.5rem;">
              <span class="input-icon"><i class="fa-solid fa-star"></i></span>
            <span style="color:var(--color-text);font-size:0.9rem;">Gagnez des points et des récompenses</span>
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
          <h1>Créer un compte <i class="fa-solid fa-rocket"></i></h1>
          <p>En 3 étapes, vous serez prêt à sauver des repas !</p>
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
          <!-- Step 1: Identité -->
          <div class="form-step active" id="step-1">
            <p class="step-title">étape 1 - Vos informations</p>

            <div class="form-group" style="display:flex; gap:16px;">
              <div style="flex:1;">
                <label for="reg-nom">Nom <span class="required">*</span></label>
                <div class="input-wrapper">
                  <input type="text" id="reg-nom" class="form-input" placeholder="Votre nom">
                  <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                </div>
                <div class="form-error" id="reg-nom-error"></div>
              </div>
              <div style="flex:1;">
                <label for="reg-prenom">Prénom <span class="required">*</span></label>
                <div class="input-wrapper">
                  <input type="text" id="reg-prenom" class="form-input" placeholder="Votre prénom">
                  <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                </div>
                <div class="form-error" id="reg-prenom-error"></div>
              </div>
            </div>

            <div class="form-group">
              <label for="reg-email">Email <span class="required">*</span></label>
              <div class="input-wrapper">
                <input type="text" id="reg-email" class="form-input" placeholder="votre@email.com">
              <span class="input-icon"><i class="fa-solid fa-envelope"></i></span>
              </div>
              <div class="form-error" id="reg-email-error"></div>
            </div>

            <div class="form-group">
              <label for="reg-password">Mot de passe <span class="required">*</span></label>
              <div class="input-wrapper">
                <input type="password" id="reg-password" class="form-input" placeholder="Minimum 6 caractères">
              <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                <button type="button" class="password-toggle">
              <i class="fa-solid fa-eye"></i></button>
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
                <input type="password" id="reg-confirm" class="form-input" placeholder="Retapez votre mot de passe">
              <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
              </div>
              <div class="form-error" id="reg-confirm-error"></div>
            </div>

            <div class="form-group">
              <label for="reg-sponsor">Code Parrain <span style="font-weight:normal; font-size:0.8rem; color:var(--color-text-muted);">(Optionnel)</span></label>
              <div class="input-wrapper">
                <input type="text" id="reg-sponsor" class="form-input" placeholder="Ex: CARE-12-A1B2" style="text-transform: uppercase;">
                <span class="input-icon"><i class="fa-solid fa-gift"></i></span>
              </div>
              <p style="font-size:0.75rem; color:var(--color-primary); margin-top:4px; margin-left:4px;">Gagnez 50 points bonus à l'inscription !</p>
            </div>

            <button type="button" class="btn btn-primary btn-full btn-lg" onclick="Auth.nextStep()">
              Continuer <span><i class="fa-solid fa-arrow-trend-down"></i></span>
            </button>
          </div>

          <!-- Step 2: Localisation -->
          <div class="form-step" id="step-2">
            <p class="step-title">étape 2 - Votre campus</p>

            <div class="form-group">
              <label for="reg-university">Université / école <span class="required">*</span></label>
              <div class="input-wrapper">
                <select id="reg-university" class="form-select">Sélectionnez votre établissement</option>
                  <option value="ESPRIT">ESPRIT</option>
                  <option value="INSAT">INSAT</option>
                  <option value="ENIT">ENIT</option>
                  <option value="FST">FST - Faculté des Sciences de Tunis</option>
                  <option value="IHEC">IHEC Carthage</option>
                  <option value="ISTIC">ISTIC</option>
                  <option value="ULT">Université Libre de Tunis</option>
                  <option value="ENSI">ENSI</option>
                  <option value="ISG">ISG Tunis</option>
                  <option value="ESEN">ESEN Manouba</option>
                  <option value="autre">Autre</option>
                </select>
                <span class="input-icon">
              <span class="input-icon"><i class="fa-solid fa-school"></i></span>
                <span class="select-arrow">--</span>
              </div>
              <div class="form-error" id="reg-university-error"></div>
            </div>

            <div class="form-group" style="display:flex; gap:16px;">
              <div style="flex:1;">
                <label for="reg-telephone">Téléphone <span class="required">*</span></label>
                <div class="input-wrapper">
                  <input type="text" id="reg-telephone" class="form-input" placeholder="Votre numéro">
                  <span class="input-icon"><i class="fa-solid fa-phone"></i></span>
                </div>
                <div class="form-error" id="reg-telephone-error"></div>
              </div>
              <div style="flex:1;">
                <label for="reg-annee">Année d'étude <span class="required">*</span></label>
                <div class="input-wrapper">
                  <select id="reg-annee" class="form-select">
                    <option value="">Sélectionnez</option>
                    <option value="1ere">1ère année</option>
                    <option value="2eme">2ème année</option>
                    <option value="3eme">3ème année</option>
                    <option value="Master 1">Master 1</option>
                    <option value="Master 2">Master 2</option>
                  </select>
                  <span class="select-arrow">▼</span>
                </div>
                <div class="form-error" id="reg-annee-error"></div>
              </div>
            </div>

            <div class="form-group">
              <label for="reg-quartier">Quartier / Zone</label>
              <div class="input-wrapper">
                <select id="reg-quartier" class="form-select">
                  <option value="">Sélectionnez votre zone</option>
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
                <span class="input-icon">
              <span class="input-icon"><i class="fa-solid fa-location-dot"></i></span>
                <span class="select-arrow">▼</span>
              </div>
              <div class="form-error" id="reg-quartier-error"></div>
            </div>

            <div style="display:flex;gap:12px;margin-top:24px;">
              <button type="button" class="btn btn-secondary btn-lg" style="flex:1;" onclick="Auth.prevStep()">
                <span>- -</span> Retour
              </button>
              <button type="button" class="btn btn-primary btn-lg" style="flex:2;" onclick="Auth.nextStep()">
                Continuer <span><i class="fa-solid fa-arrow-trend-down"></i></span>
              </button>
            </div>
          </div>

          <!-- Step 3: Préférences -->
          <div class="form-step" id="step-3">
            <p class="step-title">étape 3 - Vos préférences</p>

            <div class="form-group">
              <label>Préférences alimentaires</label>
              <p style="font-size:0.8rem;color:var(--color-text-muted);margin-bottom:12px;">Sélectionnez tout ce qui vous concerne</p>
              <div class="tags-grid">
                <div class="tag" data-value="halal"><span class="tag-emoji"><i class="fa-solid fa-star-and-crescent"></i></span> Halal</div>
                <div class="tag" data-value="vegetarien"><span class="tag-emoji"><i class="fa-solid fa-leaf"></i></span> Végétarien</div>
                <div class="tag" data-value="vegan"><span class="tag-emoji"><i class="fa-solid fa-seedling"></i></span> Végan</div>
                <div class="tag" data-value="sans-gluten"><span class="tag-emoji"><i class="fa-solid fa-wheat-awn"></i></span> Sans gluten</div>
                <div class="tag" data-value="bio"><span class="tag-emoji"><i class="fa-solid fa-leaf"></i></span> Bio</div>
                <div class="tag" data-value="sans-lactose"><span class="tag-emoji"><i class="fa-solid fa-glass-water"></i></span> Sans lactose</div>
                <div class="tag" data-value="budget"><span class="tag-emoji"><i class="fa-solid fa-coins"></i></span> Petit budget</div>
                <div class="tag" data-value="equilibre"><span class="tag-emoji"><i class="fa-solid fa-scale-balanced"></i></span> équilibré</div>
                
                <!-- Nouvelle option "Autre" -->
                <div class="tag" id="tag-autre" data-value="autre">
                  <span class="tag-emoji"><i class="fa-solid fa-plus"></i></span> Autre
                </div>
              </div>

              <!-- Champ de saisie manuel qui apparaît si "Autre" est sélectionné -->
              <div id="champ-autre-container" style="display: none; margin-top: 16px; animation: slideIn 0.3s ease;">
                <div style="display: flex; gap: 8px;">
                  <div class="input-wrapper" style="flex: 1;">
                    <input type="text" id="autre-preference-text" class="form-input" placeholder="Ajouter une préférence (ex: Sans sel)">
                    <span class="input-icon">
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
                <input type="range" id="reg-budget"   value="8" step="1">
                <div class="range-value" id="budget-value">8 DT</div>
              </div>
            </div>

            <div class="form-group">
              <label for="reg-frequency">Fréquence souhaitée</label>
              <div class="input-wrapper">
                <select id="reg-frequency" class="form-select" style="padding-left:16px;">
                  <option value="quotidien">Tous les jours</option>
                  <option value="hebdomadaire">Quelques fois par semaine</option>
                  <option value="occasionnel">Occasionnellement</option>
                </select>
                <span class="select-arrow">--</span>
              </div>
            </div>

            <div style="display:flex;gap:12px;margin-top:24px;">
              <button type="button" class="btn btn-secondary btn-lg" style="flex:1;" onclick="Auth.prevStep()">
                <span>- -</span> Retour
              </button>
              <button type="submit" class="btn btn-primary btn-lg" style="flex:2;" id="btn-register-student">
                <span>
              <span class="input-icon"><i class="fa-solid fa-party-horn"></i></span> Créer mon compte
              </button>
            </div>
          </div>
        </form>

        <div class="auth-footer">
          <p>Déjà un compte ? <a href="/projet2a22/View/FrontOffice/login.php">Se connecter</a></p>
        </div>
      </div>
    </div>
  </div>

  <script src="/projet2a22/js/app.js"></script>
  <script src="/projet2a22/js/auth.js"></script>
  <script>
    // Logique pour personnaliser la liste des préférences
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

      // 2. Action d'ajout de la nouvelle préférence à la liste en dur
      function ajouterNouveauTag() {
        const valeur = inputAutre.value.trim();
        if (valeur === "") return; // Ne rien faire si c'est vide

        // Créer l'élément HTML (Tag) manuellement
        const newTag = document.createElement("div");
        newTag.className = "tag selected";
        newTag.setAttribute("data-value", valeur.toLowerCase().replace(/\s+/g, '-'));
        
        // Ajouter du texte et une icone générique
        newTag.innerHTML = '<span class="tag-emoji"><i class="fa-solid fa-check"></i></span> ' + valeur;
        
        // Activer l'interaction Clic (comme Auth.js le fait)
        newTag.addEventListener('click', () => {
          newTag.classList.toggle('selected');
        });

        // L'insérer dans la grille JUSTE AVANT le bouton "Autre"
        const grid = document.querySelector(".tags-grid");
        grid.insertBefore(newTag, tagAutre);

        // Nettoyer et fermer
        inputAutre.value = "";
        tagAutre.classList.remove("selected");
        containerAutre.style.display = "none";
      }

      // écouter le clic sur le bouton "+"
      btnAddTag.addEventListener("click", ajouterNouveauTag);

      // écouter la touche "Entrée"
      inputAutre.addEventListener("keypress", (e) => {
        if (e.key === "Enter") {
          e.preventDefault(); // Empêcher la soumission du gros formulaire
          ajouterNouveauTag();
        }
      });
    });
  </script>
</body>
</html>


