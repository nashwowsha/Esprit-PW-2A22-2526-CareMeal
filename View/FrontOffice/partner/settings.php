<?php require_once dirname(__DIR__, 2) . '/session_check.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Paramètres de votre établissement CareMeal.">
  <title>Paramètres — CareMeal Partenaire</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/projet2a22/css/main.css">
  <link rel="stylesheet" href="/projet2a22/css/components.css">
  <link rel="stylesheet" href="/projet2a22/css/dashboard.css">
  <link rel="stylesheet" href="/projet2a22/css/face-auth.css">
</head>
<body>
  <div class="dashboard-layout">
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <div class="sidebar-logo"><i class="fa-solid fa-utensils"></i></div>
        <div class="sidebar-brand">Care<span>Meal</span></div>
      </div>
      <nav class="sidebar-nav">
        <div class="sidebar-section">
          <div class="sidebar-section-title">Partenaire</div>
          <a href="dashboard.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-house"></i></span> Mon Établissement</a>
          <a href="offers.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-bag-shopping"></i></span> Mes Offres</a>
          <a href="stats.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-chart-simple"></i></span> Statistiques</a>
          <a href="settings.php" class="sidebar-link active"><span class="link-icon"><i class="fa-solid fa-gear"></i></span> Paramètres</a>
        </div>
      </nav>
      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="avatar" id="sidebar-user-avatar" style="background:linear-gradient(135deg,var(--color-primary),#FF7A3D);">P</div>
          <div class="sidebar-user-info">
            <div class="sidebar-user-name" id="sidebar-user-name">Partenaire</div>
            <div class="sidebar-user-role" id="sidebar-user-role">Partenaire</div>
          </div>
          <button class="sidebar-logout" data-action="logout" title="Déconnexion"><i class="fa-solid fa-arrow-right-from-bracket"></i></button>
        </div>
      </div>
    </aside>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle">☰</button>
          <div class="page-title"><h2>Paramètres</h2><p>Modifiez les informations de votre établissement</p></div>
        </div>
        <div class="header-right">
          <div class="avatar avatar-sm" id="header-avatar" style="background:linear-gradient(135deg,var(--color-primary),#FF7A3D);">P</div>
        </div>
      </header>

      <div class="page-content" style="max-width:700px;">
                <!-- Edit Profile -->
        <div class="section animate-fade-in-up">
          <h3 style="margin-bottom:20px;"><i class="fa-solid fa-shop"></i> Informations de l'établissement</h3>
          <div class="card">
            <div class="form-group">
              <label for="edit-name">Nom de l'établissement</label>
              <div class="input-wrapper">
                <input type="text" id="edit-name" class="form-input" placeholder="Nom de l'établissement">
                <span class="input-icon"><i class="fa-solid fa-shop"></i></span>
              </div>
            </div>


            <hr style="border:none; border-top:1px solid rgba(255,255,255,0.1); margin: 20px 0;">
            <p style="color:var(--color-primary); font-weight:bold; margin-bottom:10px;"><i class="fa-solid fa-user-tie"></i> Contact Gérant</p>

            <div class="form-group" style="display:flex; gap:15px;">
              <div style="flex:1;">
                <label for="edit-nom">Nom</label>
                <div class="input-wrapper">
                  <input type="text" id="edit-nom" class="form-input" placeholder="Nom du gérant">
                  <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                </div>
              </div>
              <div style="flex:1;">
                <label for="edit-prenom">Prénom</label>
                <div class="input-wrapper">
                  <input type="text" id="edit-prenom" class="form-input" placeholder="Prénom du gérant">
                  <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label for="edit-phone">Téléphone</label>
              <div class="input-wrapper">
                <input type="text" id="edit-phone" class="form-input" placeholder="+216 XX XXX XXX">
                <span class="input-icon"><i class="fa-solid fa-mobile"></i></span>
              </div>
            </div>
            
            <div class="form-group">
              <label for="edit-description">Description de l'établissement</label>
              <textarea id="edit-description" class="form-textarea" placeholder="Décrivez votre établissement..." rows="3"></textarea>
            </div>

            <!-- Réseaux Sociaux -->
            <hr style="border:none; border-top:1px solid rgba(255,255,255,0.1); margin: 20px 0;">
            <p style="color:var(--color-primary); font-weight:bold; margin-bottom:10px;"><i class="fa-solid fa-share-nodes"></i> Réseaux Sociaux (Optionnel, mettez les URLs)</p>
            
            <div class="form-group" style="display:flex; gap:15px; flex-wrap:wrap;">
              <div style="flex:1; min-width: 200px;">
                <label for="edit-linkedin">LinkedIn</label>
                <div class="input-wrapper">
                  <input type="text" id="edit-linkedin" class="form-input" placeholder="https://linkedin.com/...">
                  <span class="input-icon"><i class="fa-brands fa-linkedin"></i></span>
                </div>
              </div>
              <div style="flex:1; min-width: 200px;">
                <label for="edit-facebook">Facebook</label>
                <div class="input-wrapper">
                  <input type="text" id="edit-facebook" class="form-input" placeholder="https://facebook.com/...">
                  <span class="input-icon"><i class="fa-brands fa-facebook"></i></span>
                </div>
              </div>
            </div>
            <div class="form-group" style="display:flex; gap:15px; flex-wrap:wrap;">
              <div style="flex:1; min-width: 200px;">
                <label for="edit-instagram">Instagram</label>
                <div class="input-wrapper">
                  <input type="text" id="edit-instagram" class="form-input" placeholder="https://instagram.com/...">
                  <span class="input-icon"><i class="fa-brands fa-instagram"></i></span>
                </div>
              </div>
              <div style="flex:1; min-width: 200px;">
                <label for="edit-twitter">Twitter / X</label>
                <div class="input-wrapper">
                  <input type="text" id="edit-twitter" class="form-input" placeholder="https://twitter.com/...">
                  <span class="input-icon"><i class="fa-brands fa-twitter"></i></span>
                </div>
              </div>
            </div>
            <div class="form-group">
                <label for="edit-github">GitHub</label>
                <div class="input-wrapper">
                  <input type="text" id="edit-github" class="form-input" placeholder="https://github.com/...">
                  <span class="input-icon"><i class="fa-brands fa-github"></i></span>
                </div>
            </div>

            <button class="btn btn-primary" onclick="Partner.saveProfile()" id="btn-save-profile">
              <span><i class="fa-solid fa-check"></i> </span> Sauvegarder
            </button>
          </div>
        </div>

        <!-- Change Password -->
        <div class="section animate-fade-in-up stagger-1">
          <h3 style="margin-bottom:20px;"><i class="fa-solid fa-lock"></i> Modifier le mot de passe</h3>
          <div class="card">
            <div class="form-group">
              <label for="current-password">Mot de passe actuel</label>
              <div class="input-wrapper">
                <input type="password" id="current-password" class="form-input" placeholder="Votre mot de passe actuel">
                <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                <button type="button" class="password-toggle"><i class="fa-solid fa-eye"></i></button>
              </div>
            </div>
            <div class="form-group">
              <label for="new-password">Nouveau mot de passe</label>
              <div class="input-wrapper">
                <input type="password" id="new-password" class="form-input" placeholder="Minimum 6 caractères">
                <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                <button type="button" class="password-toggle"><i class="fa-solid fa-eye"></i></button>
              </div>
            </div>
            <div class="form-group">
              <label for="confirm-new-password">Confirmer le nouveau mot de passe</label>
              <div class="input-wrapper">
                <input type="password" id="confirm-new-password" class="form-input" placeholder="Retapez le nouveau mot de passe">
                <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
              </div>
            </div>
            <button class="btn btn-primary" onclick="Partner.updatePassword()" id="btn-update-password">
              <span><i class="fa-solid fa-check"></i> </span> Mettre à jour
            </button>
          </div>
        </div>

        <!-- Reconnaissance Faciale -->
        <div class="section animate-fade-in-up stagger-2">
          <h3 style="margin-bottom:20px;"><i class="fa-solid fa-face-smile"></i> Reconnaissance faciale</h3>
          <div class="card">
            <div class="face-settings-info">
              <div class="face-settings-icon">
                <i class="fa-solid fa-camera-retro"></i>
              </div>
              <div class="face-settings-text">
                <h4>Connexion par visage</h4>
                <p>Enregistrez votre visage pour vous connecter instantanement sans mot de passe via la camera.</p>
              </div>
            </div>
            <div class="face-settings-status">
              <div class="face-reg-badge inactive" id="face-reg-status">
                <i class="fa-solid fa-circle-xmark"></i> Aucun visage
              </div>
            </div>
            <div class="face-settings-actions">
              <button class="btn btn-primary btn-sm" id="btn-register-face" onclick="FaceAuth.openFaceRegister()">
                <i class="fa-solid fa-camera"></i> Enregistrer mon visage
              </button>
              <button class="btn btn-danger btn-sm" id="btn-remove-face" style="display:none;" onclick="FaceAuth.removeFace()">
                <i class="fa-solid fa-trash"></i> Supprimer
              </button>
            </div>
          </div>
        </div>

        <!-- Notifications -->
        <div class="section animate-fade-in-up stagger-2">
          <h3 style="margin-bottom:20px;"><i class="fa-solid fa-bell"></i> Notifications</h3>
          <div class="settings-group">
            <div class="settings-item">
              <div class="settings-item-left">
                <div class="settings-icon"><i class="fa-solid fa-envelope"></i></div>
                <div class="settings-item-info">
                  <h4>Notifications par email</h4>
                  <p>Recevoir les nouvelles commandes par email</p>
                </div>
              </div>
              <label class="switch">
                <input type="checkbox" checked>
                <span class="switch-slider"></span>
              </label>
            </div>
            <div class="settings-item">
              <div class="settings-item-left">
                <div class="settings-icon"><i class="fa-solid fa-star"></i></div>
                <div class="settings-item-info">
                  <h4>Alertes nouveaux avis</h4>
                  <p>àŠtre notifié quand vous recevez un nouvel avis</p>
                </div>
              </div>
              <label class="switch">
                <input type="checkbox" checked>
                <span class="switch-slider"></span>
              </label>
            </div>
          </div>
        </div>

        <!-- Danger Zone -->
        <div class="section animate-fade-in-up stagger-3">
          <h3 style="margin-bottom:20px;"><i class="fa-solid fa-triangle-exclamation"></i> Zone dangereuse</h3>
          <div class="settings-group">
            <div class="settings-item danger">
              <div class="settings-item-left">
                <div class="settings-icon"><i class="fa-solid fa-trash"></i></div>
                <div class="settings-item-info">
                  <h4>Supprimer mon compte</h4>
                  <p>Action irréversible — toutes vos données seront perdues</p>
                </div>
              </div>
              <button class="btn btn-danger btn-sm" onclick="Partner.deleteAccount()" id="btn-delete-account">Supprimer</button>
            </div>
          </div>
        </div>

      </div>
    </main>
  </div>

  <!-- Face Auth Modal -->
  <div class="face-modal-overlay" id="face-modal">
    <div class="face-modal">
      <button class="face-modal-close" onclick="FaceAuth.closeModal()">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <div class="face-modal-header">
        <div class="face-modal-icon">
          <i class="fa-solid fa-user-shield"></i>
        </div>
        <h2>Enregistrement du visage</h2>
        <p>Regardez la camera et restez immobile pour capturer votre visage</p>
      </div>
      <div class="face-video-container">
        <video id="face-video" autoplay muted playsinline></video>
        <div class="face-scan-frame">
          <div class="face-scan-corner tl"></div>
          <div class="face-scan-corner tr"></div>
          <div class="face-scan-corner bl"></div>
          <div class="face-scan-corner br"></div>
        </div>
        <div class="face-scan-line" id="face-scan-line"></div>
        <div class="face-overlay" id="face-overlay"></div>
      </div>
      <div class="face-status loading" id="face-status">Initialisation...</div>
    </div>
  </div>

  <script src="/projet2a22/js/app.js"></script>
  <script src="/projet2a22/js/components.js"></script>
  <script src="/projet2a22/js/partner.js"></script>
  <script src="/projet2a22/js/auth.js"></script>
  <!-- face-api.js (TensorFlow.js) -->
  <script defer src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.12/dist/face-api.min.js"></script>
  <script defer src="/projet2a22/js/face-auth.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      Partner.initSettings();
      Auth.initPasswordToggles();
      setTimeout(() => FaceAuth.checkFaceStatus(), 500);
    });
  </script>
</body>
</html>