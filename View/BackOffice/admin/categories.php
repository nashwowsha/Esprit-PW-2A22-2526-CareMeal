<?php require_once dirname(__DIR__, 3) . '/View/session_check.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <script>document.documentElement.className += " page-loading";</script>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Gestion des categories de produits CareMeal.">
  <title>Categories - CareMeal Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/css/main.css">
  <link rel="stylesheet" href="/css/components.css">
  <link rel="stylesheet" href="/css/dashboard.css">
  <link rel="stylesheet" href="/css/theme-fix.css">
</head>
<body>
  <div class="dashboard-layout">
    <?php
      $activePage = 'categories';
      require dirname(__DIR__, 3) . '/admin/_admin_sidebar.php';
    ?>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle">Ã¢ËœÂ°</button>
          <div class="page-title"><h2>Categories</h2><p>Gestion des familles de produits</p></div>
        </div>
        <div class="header-right">
          <button class="btn btn-primary btn-sm" onclick="Admin.openCategoryModal()"><i class="fa-solid fa-plus"></i> Nouvelle categorie</button>
        </div>
      </header>

      <div class="page-content">
        <div class="grid grid-2 gap-4 mb-6 animate-fade-in-up">
          <div class="card" style="display:flex;align-items:center;gap:12px;">
            <div class="stat-icon blue"><i class="fa-solid fa-layer-group"></i></div>
            <div><div class="stat-value" id="categories-total">0</div><div class="stat-label">Categories total</div></div>
          </div>
          <div class="card" style="display:flex;align-items:center;gap:12px;">
            <div class="stat-icon green"><i class="fa-solid fa-check"></i></div>
            <div><div class="stat-value" id="categories-active">0</div><div class="stat-label">Categories actives</div></div>
          </div>
        </div>

        <div class="card animate-fade-in-up">
          <div class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Nom</th>
                  <th>Description</th>
                  <th>Produits</th>
                  <th>Statut</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="categories-table-body"></tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>

  <div class="modal-overlay" id="category-modal">
    <div class="modal">
      <div class="modal-header">
        <h3 id="category-modal-title">Nouvelle categorie</h3>
        <button class="modal-close" data-close-modal="category-modal"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body">
        <form id="category-form" onsubmit="Admin.saveCategory(event)">
          <input type="hidden" id="category-id">
          <div class="form-group">
            <label for="category-name">Nom</label>
            <input id="category-name" class="form-input" placeholder="Nom de la catégorie">
          </div>
          <div class="form-group">
            <label for="category-description">Description</label>
            <textarea id="category-description" class="form-textarea no-icon" rows="3"></textarea>
          </div>
          <label class="form-check" style="margin-bottom:12px;">
            <input type="checkbox" id="category-active" checked>
            <span>Active</span>
          </label>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-close-modal="category-modal">Annuler</button>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="/js/app.js"></script>
  <script src="/js/components.js"></script>
  <script src="/js/admin.js"></script>
  <script src="/js/admin-voice-assistant.js?v=20260508"></script>
  <script>document.addEventListener('DOMContentLoaded', async function() { if (typeof Admin !== 'undefined' && Admin.initCategories) await Admin.initCategories(); });</script>
</body>
</html>

