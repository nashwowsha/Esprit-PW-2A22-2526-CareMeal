<?php require_once dirname(__DIR__, 3) . '/View/session_check.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <script>document.documentElement.className += " page-loading";</script>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Gestion des produits CareMeal.">
  <title>Produits - CareMeal Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/css/main.css">
  <link rel="stylesheet" href="/css/components.css">
  <link rel="stylesheet" href="/css/dashboard.css">
  <link rel="stylesheet" href="/css/theme-fix.css">
</head>
<body>
  <div class="dashboard-layout">
    <?php
      $activePage = 'products';
      require dirname(__DIR__, 3) . '/admin/_admin_sidebar.php';
    ?>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle">Ã¢ËœÂ°</button>
          <div class="page-title"><h2>Produits</h2><p>Gestion du catalogue commande</p></div>
        </div>
        <div class="header-right" style="display:flex;gap:12px;align-items:center;">
          <input id="products-search" class="form-input" placeholder="Rechercher..." style="min-width:220px;" oninput="Admin.setProductsSearch(this.value)">
          <select id="products-filter-category" class="form-select no-icon" style="min-width:220px;" onchange="Admin.setProductsFilter(this.value)"></select>
          <button class="btn btn-primary btn-sm" onclick="Admin.openProductModal()"><i class="fa-solid fa-plus"></i> Nouveau produit</button>
        </div>
      </header>

      <div class="page-content">
        <div class="grid grid-3 gap-4 mb-6 animate-fade-in-up">
          <div class="card" style="display:flex;align-items:center;gap:12px;">
            <div class="stat-icon blue"><i class="fa-solid fa-bowl-food"></i></div>
            <div><div class="stat-value" id="products-total">0</div><div class="stat-label">Produits</div></div>
          </div>
          <div class="card" style="display:flex;align-items:center;gap:12px;">
            <div class="stat-icon green"><i class="fa-solid fa-check"></i></div>
            <div><div class="stat-value" id="products-active">0</div><div class="stat-label">Actifs</div></div>
          </div>
          <div class="card" style="display:flex;align-items:center;gap:12px;">
            <div class="stat-icon yellow"><i class="fa-solid fa-box-open"></i></div>
            <div><div class="stat-value" id="products-low-stock">0</div><div class="stat-label">Stock faible (&lt;=3)</div></div>
          </div>
        </div>

        <div class="card animate-fade-in-up">
          <div class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Produit</th>
                  <th>Categorie</th>
                  <th>Partenaire</th>
                  <th>Prix normal</th>
                  <th>Prix commande</th>
                  <th>Stock</th>
                  <th>Statut</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="products-table-body"></tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>

  <div class="modal-overlay" id="product-modal">
    <div class="modal">
      <div class="modal-header">
        <h3 id="product-modal-title">Nouveau produit</h3>
        <button class="modal-close" data-close-modal="product-modal"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body">
        <form id="product-form" onsubmit="Admin.saveProduct(event)">
          <input type="hidden" id="product-id">
          <div class="grid grid-2 gap-4">
            <div class="form-group">
              <label for="product-name">Nom</label>
              <input id="product-name" class="form-input" placeholder="Nom du produit">
            </div>
            <div class="form-group">
              <label for="product-category">Categorie</label>
              <select id="product-category" class="form-select no-icon"></select>
            </div>
          </div>
          <div class="form-group">
            <label for="product-description">Description</label>
            <textarea id="product-description" class="form-textarea no-icon" rows="3"></textarea>
          </div>
          <div class="grid grid-2 gap-4">
            <div class="form-group">
              <label for="product-partner">Partenaire</label>
              <select id="product-partner" class="form-select no-icon"></select>
            </div>
            <div class="form-group">
              <label for="product-stock">Stock</label>
              <input id="product-stock" type="number" min="0" class="form-input" placeholder="0">
            </div>
          </div>
          <div class="grid grid-2 gap-4">
            <div class="form-group">
              <label for="product-original-price">Prix normal (DT)</label>
              <input id="product-original-price" type="number" min="0.1" step="0.1" class="form-input">
            </div>
            <div class="form-group">
              <label for="product-price">Prix commande (DT)</label>
              <input id="product-price" type="number" min="0.1" step="0.1" class="form-input" required>
            </div>
          </div>
          <label class="form-check" style="margin-bottom:12px;">
            <input type="checkbox" id="product-active" checked>
            <span>Actif</span>
          </label>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-close-modal="product-modal">Annuler</button>
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
  <script>document.addEventListener('DOMContentLoaded', async function() { if (typeof Admin !== 'undefined' && Admin.initProducts) await Admin.initProducts(); });</script>
</body>
</html>

