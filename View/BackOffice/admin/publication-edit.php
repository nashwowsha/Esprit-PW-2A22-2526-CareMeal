<?php require_once dirname(__DIR__, 2) . '/session_check.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Modifier une publication CareMeal en tant qu'administrateur.">
  <title>Modifier une publication â€” CareMeal Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/css/main.css">
  <link rel="stylesheet" href="/css/components.css">
  <link rel="stylesheet" href="/css/dashboard.css">
  <link rel="stylesheet" href="/css/publications.css?v=4">
</head>
<body>
  <div class="dashboard-layout">
    <?php
      $activePage = 'publication_edit';
      require dirname(__DIR__, 3) . '/admin/_admin_sidebar.php';
    ?>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle"><i class="fa-solid fa-bars"></i></button>
          <div class="page-title">
            <h2><i class="fa-solid fa-pen-to-square" style="color:var(--color-primary);margin-right:8px;"></i>Modifier une publication</h2>
            <p>Ã‰ditez le texte et l'image puis revenez au fil</p>
          </div>
        </div>
        <div class="header-right">
          <button class="header-notification">
            <i class="fa-solid fa-bell"></i>
            <span class="notif-dot"></span>
          </button>
          <div class="avatar avatar-sm" id="header-avatar">A</div>
        </div>
      </header>

      <div class="page-content" style="max-width:720px;">
        <div class="pub-admin-back-wrap">
          <a href="/admin/publications.php" class="btn btn-outline btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Retour aux publications
          </a>
        </div>

        <div class="pub-edit-page" id="pub-edit-page-root">
          <div class="pub-edit-modal-header">
            <h3><i class="fa-solid fa-pen-to-square"></i> Modifier la publication</h3>
          </div>
          <div class="pub-edit-modal-body">
            <textarea class="pub-edit-textarea" id="pub-edit-content" rows="5" oninput="Publications.updateCharCount(this, 'pub-edit-char-count', 500)"></textarea>
            <div style="text-align: right; margin-top: 4px;">
              <span id="pub-edit-char-count" style="font-size:0.85rem; color:#6b7280; font-weight: 500;">0 / 500</span>
            </div>

            <div class="pub-upload-block pub-upload-block-edit">
              <div class="pub-compose-upload-row" style="margin-top: 12px;">
                <label for="pub-edit-image" class="pub-upload-btn">
                  <i class="fa-regular fa-image"></i> Remplacer l'image
                </label>
                <input type="file" id="pub-edit-image" class="pub-compose-file-input" accept="image/jpeg,image/png,image/webp,image/gif">
              </div>
              <div class="pub-upload-meta" id="pub-edit-upload-meta">JPG, PNG, WEBP ou GIF â€¢ 5 Mo max</div>
              <div class="pub-compose-preview" id="pub-edit-image-preview" style="display:none;"></div>
            </div>

            <label class="pub-edit-remove-image" id="pub-edit-remove-wrap" style="display:none;">
              <input type="checkbox" id="pub-edit-remove-image">
              Retirer l'image actuelle
            </label>

            <p class="pub-edit-status" id="pub-edit-page-status"></p>
          </div>
          <div class="pub-edit-modal-footer">
            <a href="/admin/publications.php" class="btn btn-secondary btn-sm">Annuler</a>
            <button type="button" class="btn btn-primary btn-sm" id="pub-edit-page-save">
              <i class="fa-solid fa-check"></i> Enregistrer
            </button>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="/js/app.js"></script>
  <script src="/js/components.js"></script>
  <script src="/js/publications.js?v=4"></script>
  <script src="/js/admin-voice-assistant.js?v=20260509"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      if (!App.requireAuth(['admin'])) return;
      Publications.initEditPage();
    });
  </script>
</body>
</html>








