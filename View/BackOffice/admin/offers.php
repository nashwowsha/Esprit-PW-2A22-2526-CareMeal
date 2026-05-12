<?php
// Variables disponibles : $offers, $categories, $counts, $flash, $errors, $old
function ea($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Offres - Admin CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/css/main.css">
  <link rel="stylesheet" href="/css/components.css">
  <link rel="stylesheet" href="/css/dashboard.css">
  <style>
    /* -- Table -- */
    .form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    .badge-statut { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.72rem; font-weight:600; }
    .s-publiee   { background:rgba(34,197,94,.15);  color:#4ade80; }
    .s-brouillon { background:rgba(251,191,36,.15); color:#fbbf24; }
    .s-expiree, .s-archivee { background:rgba(156,163,175,.12); color:#9ca3af; }
    .data-table { width:100%; border-collapse:collapse; }
    .data-table th { text-align:left; padding:12px 16px; font-size:.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--color-text-muted); border-bottom:1px solid var(--color-dark-border); }
    .data-table td { padding:13px 16px; border-bottom:1px solid var(--color-dark-border); font-size:.875rem; vertical-align:middle; }
    .data-table tr:last-child td { border-bottom:none; }
    .data-table tr:hover td { background:var(--color-dark-hover); }
    .cat-tags { display:flex; flex-wrap:wrap; gap:4px; }
    .cat-tag { display:inline-block; padding:2px 8px; border-radius:12px; font-size:.7rem; font-weight:600; background:rgba(239,68,68,.15); color:#f87171; }
    .empty-state { text-align:center; padding:60px 20px; color:var(--color-text-muted); }
    .empty-state i { font-size:3rem; display:block; margin-bottom:12px; opacity:.3; }
    .alert { padding:12px 18px; border-radius:10px; margin-bottom:18px; font-size:.9rem; }
    .alert-success { background:rgba(34,197,94,.12); color:#4ade80; border:1px solid rgba(34,197,94,.2); }
    .alert-error   { background:rgba(239,68,68,.12);  color:#f87171; border:1px solid rgba(239,68,68,.2); }

    /* -- Photo upload -- */
    .photo-upload-area { border:2px dashed var(--color-dark-border); border-radius:12px; padding:20px; text-align:center; cursor:pointer; transition:border-color .2s; position:relative; }
    .photo-upload-area:hover { border-color:var(--color-primary); }
    .photo-upload-area input[type="file"] { position:absolute; inset:0; width:100%; height:100%; opacity:0; cursor:pointer; }
    .photo-preview { width:100%; height:120px; object-fit:cover; border-radius:8px; margin-top:10px; display:none; }
    .photo-placeholder { color:var(--color-text-muted); font-size:.85rem; pointer-events:none; }
    .photo-placeholder i { font-size:2rem; display:block; margin-bottom:6px; opacity:.5; }

    /* -- Category pills -- */
    .cat-checkbox-list { display:flex; flex-wrap:wrap; gap:10px; padding:10px 0; }
    .cat-pill-label {
      display:flex; align-items:center; gap:8px; cursor:pointer;
      background:#1e2433; border:1px solid #2d3748;
      border-radius:50px; padding:8px 18px;
      font-size:.82rem; font-weight:700; color:#e2e8f0;
      letter-spacing:.04em; user-select:none;
      transition:border-color .15s, background .15s;
    }
    .cat-pill-box { width:14px; height:14px; border:2px solid #4a5568; border-radius:50%; background:#252d3d; flex-shrink:0; display:flex; align-items:center; justify-content:center; transition:background .15s, border-color .15s; }
    .cat-pill-label:has(input:checked) .cat-pill-box { background:var(--color-primary,#ef4444); border-color:var(--color-primary,#ef4444); }
    .cat-pill-label:has(input:checked) .cat-pill-box::after { content:''; display:block; width:5px; height:5px; border-radius:50%; background:#fff; }
    .cat-pill-label:has(input:checked) { border-color:var(--color-primary,#ef4444); background:rgba(239,68,68,.12); color:#fff; }
    .cat-pill-label:hover { border-color:#4a5568; background:#252d3d; }

    /* Fix select dark theme */
    select.form-input option { background-color:var(--color-dark-card,#1e2433); color:var(--color-text,#e2e8f0); }
    select.form-input { background-color:var(--color-dark-input,#252d3d); color:var(--color-text,#e2e8f0); }

    /* -- Custom Category Dropdown -- */
    .cat-dropdown-wrapper {
      position: relative;
      min-width: 230px;
      user-select: none;
    }
    .cat-dropdown-trigger {
      display: flex;
      align-items: center;
      gap: 10px;
      background: #252d3d;
      border: 1.5px solid #2d3748;
      border-radius: 10px;
      padding: 10px 16px;
      cursor: pointer;
      transition: border-color .2s, box-shadow .2s;
      min-height: 44px;
    }
    .cat-dropdown-trigger:hover,
    .cat-dropdown-wrapper.open .cat-dropdown-trigger {
      border-color: var(--color-primary, #ef4444);
      box-shadow: 0 0 0 3px rgba(239,68,68,.12);
    }
    .cat-dropdown-trigger .cat-trigger-icon {
      color: var(--color-primary, #ef4444);
      font-size: .95rem;
      flex-shrink: 0;
    }
    .cat-dropdown-trigger .cat-trigger-label {
      flex: 1;
      font-size: .875rem;
      font-weight: 600;
      color: #e2e8f0;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .cat-dropdown-trigger .cat-trigger-badge {
      background: var(--color-primary, #ef4444);
      color: #fff;
      font-size: .7rem;
      font-weight: 800;
      border-radius: 50px;
      padding: 2px 8px;
      display: none;
    }
    .cat-dropdown-trigger .cat-trigger-badge.visible { display: inline-block; }
    .cat-dropdown-trigger .cat-trigger-arrow {
      color: #718096;
      font-size: .8rem;
      transition: transform .2s;
      flex-shrink: 0;
    }
    .cat-dropdown-wrapper.open .cat-trigger-arrow { transform: rotate(180deg); }

    .cat-dropdown-menu {
      display: none;
      position: absolute;
      top: calc(100% + 8px);
      left: 0;
      right: 0;
      background: #1a2035;
      border: 1.5px solid #2d3748;
      border-radius: 12px;
      box-shadow: 0 12px 40px rgba(0,0,0,.45);
      z-index: 999;
      overflow: hidden;
      animation: catDropDown .18s ease;
    }
    @keyframes catDropDown {
      from { opacity:0; transform:translateY(-6px); }
      to   { opacity:1; transform:translateY(0); }
    }
    .cat-dropdown-wrapper.open .cat-dropdown-menu { display: block; }

    .cat-dropdown-search {
      padding: 10px 12px;
      border-bottom: 1px solid #2d3748;
      position: relative;
    }
    .cat-dropdown-search i {
      position: absolute;
      left: 22px;
      top: 50%;
      transform: translateY(-50%);
      color: #718096;
      font-size: .8rem;
    }
    .cat-dropdown-search input {
      width: 100%;
      background: #252d3d;
      border: 1px solid #2d3748;
      border-radius: 7px;
      padding: 7px 10px 7px 30px;
      color: #e2e8f0;
      font-size: .82rem;
      outline: none;
      box-sizing: border-box;
      transition: border-color .2s;
    }
    .cat-dropdown-search input:focus { border-color: var(--color-primary,#ef4444); }
    .cat-dropdown-search input::placeholder { color: #4a5568; }

    .cat-dropdown-list {
      max-height: 220px;
      overflow-y: auto;
      padding: 6px;
      scrollbar-width: thin;
      scrollbar-color: #2d3748 transparent;
    }
    .cat-dropdown-list::-webkit-scrollbar { width: 4px; }
    .cat-dropdown-list::-webkit-scrollbar-thumb { background: #2d3748; border-radius: 4px; }

    .cat-dropdown-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 9px 12px;
      border-radius: 8px;
      cursor: pointer;
      transition: background .15s;
      font-size: .85rem;
      font-weight: 500;
      color: #a0aec0;
    }
    .cat-dropdown-item:hover { background: #252d3d; color: #e2e8f0; }
    .cat-dropdown-item.active {
      background: rgba(239,68,68,.12);
      color: #fff;
      font-weight: 700;
    }
    .cat-dropdown-item .cat-item-dot {
      width: 8px; height: 8px;
      border-radius: 50%;
      background: #4a5568;
      flex-shrink: 0;
      transition: background .15s;
    }
    .cat-dropdown-item.active .cat-item-dot { background: var(--color-primary,#ef4444); }
    .cat-dropdown-item .cat-item-check {
      margin-left: auto;
      color: var(--color-primary,#ef4444);
      font-size: .75rem;
      opacity: 0;
      transition: opacity .15s;
    }
    .cat-dropdown-item.active .cat-item-check { opacity: 1; }
    .cat-dropdown-no-result {
      text-align: center;
      padding: 18px;
      color: #4a5568;
      font-size: .82rem;
      display: none;
    }

    /* --------------------------------------
       VUE INLINE — Formulaire dans la page
    -------------------------------------- */
    #view-list   { display: block; }
    #view-form   { display: none; }

    .form-page-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 24px;
      padding-bottom: 18px;
      border-bottom: 1px solid var(--color-dark-border);
    }
    .form-page-header h2 {
      margin: 0;
      font-size: 1.15rem;
      font-weight: 700;
      color: var(--color-white);
    }
    .form-page-header p {
      margin: 2px 0 0;
      font-size: .8rem;
      color: var(--color-text-muted);
    }
    .btn-back {
      display: inline-flex; align-items: center; gap: 6px;
      background: rgba(255,255,255,.06);
      border: 1px solid var(--color-dark-border);
      color: var(--color-text-muted);
      border-radius: 10px; padding: 8px 14px;
      font-size: .85rem; font-weight: 600;
      cursor: pointer; transition: background .15s, color .15s;
      text-decoration: none;
    }
    .btn-back:hover { background: rgba(255,255,255,.1); color: var(--color-white); }

    .form-card {
      background: var(--color-dark-card);
      border: 1px solid var(--color-dark-border);
      border-radius: 16px;
      padding: 28px;
      max-width: 680px;
    }
    .form-card .form-group { margin-bottom: 18px; }
    .form-card .form-group label {
      display: block;
      font-size: .78rem; font-weight: 700;
      text-transform: uppercase; letter-spacing: .05em;
      color: var(--color-text-muted);
      margin-bottom: 7px;
    }
    .form-actions {
      display: flex; gap: 10px; justify-content: flex-end;
      margin-top: 24px; padding-top: 18px;
      border-top: 1px solid var(--color-dark-border);
    }

    /* Delete confirm inline */
    .delete-inline-card {
      background: var(--color-dark-card);
      border: 1px solid rgba(248,113,113,.25);
      border-radius: 16px;
      padding: 40px 28px;
      max-width: 480px;
      margin: 0 auto;
      text-align: center;
    }
    .delete-inline-card i.big-icon { font-size: 3rem; color: #f87171; display: block; margin-bottom: 16px; }
    .delete-inline-card h3 { margin: 0 0 8px; color: var(--color-white); }
    .delete-inline-card p  { color: var(--color-text-muted); margin: 0 0 24px; }
    .delete-inline-card .form-actions { justify-content: center; border: none; margin-top: 0; padding-top: 0; }

    /* -- Alerte stock faible -- */
    .stock-alert-banner {
      display: none;
      align-items: center;
      gap: 12px;
      background: rgba(239,68,68,.1);
      border: 1px solid rgba(239,68,68,.3);
      border-radius: 12px;
      padding: 12px 18px;
      margin-bottom: 16px;
      font-size: .875rem;
      color: #f87171;
      font-weight: 600;
    }
    .stock-alert-banner i { font-size: 1.1rem; flex-shrink: 0; }
    .stock-alert-banner .alert-count {
      background: #ef4444;
      color: #fff;
      border-radius: 50px;
      padding: 2px 10px;
      font-size: .75rem;
      font-weight: 700;
      margin-left: 4px;
    }
    .stock-alert-banner .btn-filter-low {
      margin-left: auto;
      background: rgba(239,68,68,.15);
      border: 1px solid rgba(239,68,68,.4);
      color: #f87171;
      border-radius: 8px;
      padding: 5px 14px;
      font-size: .78rem;
      font-weight: 700;
      cursor: pointer;
      transition: background .15s;
    }
    .stock-alert-banner .btn-filter-low:hover { background: rgba(239,68,68,.28); }

    /* Stock faible dans le tableau */
    .stock-low { color: #f87171 !important; font-weight: 700; }
    .stock-low-icon { font-size: .75rem; margin-left: 4px; animation: pulse-warn 1.4s infinite; }
    @keyframes pulse-warn { 0%,100%{opacity:1} 50%{opacity:.4} }

    /* -- Bouton tri stock -- */
    .btn-sort-stock {
      background: none;
      border: none;
      cursor: pointer;
      color: var(--color-text-muted);
      font-size: .75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .05em;
      display: flex;
      align-items: center;
      gap: 5px;
      padding: 0;
      transition: color .15s;
    }
    .btn-sort-stock:hover { color: #0f172a; }
    .btn-sort-stock.active { color: var(--color-primary); }
    .sort-icon { font-style: normal; font-size: .85rem; }

    /* Visibilite textes/champs/categories */
    #search-input,
    #cat-search-input,
    .form-card .form-input,
    .form-card .form-textarea,
    .form-card select.form-input {
      background: #ffffff !important;
      border: 1px solid #d1d5db !important;
      color: #0f172a !important;
    }
    #search-input::placeholder,
    #cat-search-input::placeholder,
    .form-card .form-input::placeholder,
    .form-card .form-textarea::placeholder {
      color: #64748b !important;
    }
    .cat-tag {
      background: rgba(254,85,22,.22);
      color: #ffffff;
    }
    .form-card .input-icon { color: #64748b; }

    .cat-dropdown-trigger,
    .cat-dropdown-menu,
    .cat-dropdown-search input,
    .cat-dropdown-item {
      background: #ffffff !important;
      color: #0f172a !important;
      border-color: #d1d5db !important;
    }
    .cat-dropdown-trigger .cat-trigger-icon,
    .cat-dropdown-trigger .cat-trigger-label {
      color: #0f172a !important;
    }
    .cat-dropdown-trigger .cat-trigger-badge {
      background: #e2e8f0 !important;
      color: #334155 !important;
    }
    .cat-dropdown-search i,
    .cat-dropdown-trigger .cat-trigger-arrow {
      color: #64748b !important;
    }
    .cat-dropdown-item:hover {
      background: #fff7ed !important;
      color: #9a3412 !important;
    }
    .cat-dropdown-item.active {
      background: #f97316 !important;
      color: #ffffff !important;
    }
    .data-table th { color: #475569 !important; }
    .data-table td { color: #0f172a !important; }

    /* Catégorie formulaire: fond blanc + texte lisible */
    .cat-pill-label {
      background: #ffffff !important;
      border-color: #d1d5db !important;
      color: #0f172a !important;
    }
    .cat-pill-label span:not(.cat-pill-box) {
      color: #0f172a !important;
    }
    .cat-pill-label:has(input:checked) {
      background: #fff7ed !important;
      border-color: #f97316 !important;
      color: #9a3412 !important;
    }
    .cat-pill-label:has(input:checked) span:not(.cat-pill-box) {
      color: #9a3412 !important;
    }

    /* Sidebar blanche sur page offres admin */
    .sidebar,
    .sidebar-header,
    .sidebar-nav,
    .sidebar-footer {
      background: #ffffff !important;
    }
  </style>
</head>
<body>
<div class="dashboard-layout">

  <?php
      $activePage = 'offers';
      require dirname(__DIR__, 3) . '/admin/_admin_sidebar.php';
    ?>
  <div class="sidebar-overlay" id="sidebar-overlay"></div>

  <main class="main-content">
    <header class="top-header">
      <div class="header-left">
        <button class="menu-toggle" id="menu-toggle"><i class="fa-solid fa-bars"></i></button>
        <div class="page-title">
          <h2 id="page-heading">Offres partenaires</h2>
          <p id="page-sub">Liste et gestion de toutes les offres</p>
        </div>
      </div>
      <div class="header-right" style="display:flex;align-items:center;gap:12px;">
        <!-- Bouton visible sur la vue liste -->
        <button class="btn btn-primary" id="btn-add-offer" onclick="showCreateForm()"
                style="display:flex;align-items:center;gap:8px;">
          <i class="fa-solid fa-plus"></i> Ajouter une offre
        </button>
        <div class="avatar avatar-sm" id="header-avatar" style="background:linear-gradient(135deg,#EF4444,#F87171);">A</div>
      </div>
    </header>

    <div class="page-content">

      <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
          <i class="fa-solid fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'triangle-exclamation' ?>"></i>
          <?= ea($flash['msg']) ?>
        </div>
      <?php endif; ?>

      <?php if ($errors): ?>
        <div class="alert alert-error">
          <?php foreach ($errors as $err): ?>
            <div><i class="fa-solid fa-triangle-exclamation"></i> <?= ea($err) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- --------------------------------------
           VUE 1 : LISTE DES OFFRES
      -------------------------------------- -->
      <div id="view-list">

        <!-- Filtres -->
        <div class="card" style="padding:16px;margin-bottom:20px;display:flex;flex-wrap:wrap;gap:12px;align-items:center;">
          <div style="flex:1;min-width:220px;position:relative;">
            <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--color-text-muted);"></i>
            <input type="text" id="search-input" class="form-input" style="padding-left:36px;" placeholder="Rechercher par titre, partenaire..." oninput="applyFilters()">
          </div>
          <!-- Custom Styled Category Dropdown -->
          <div class="cat-dropdown-wrapper" id="cat-dropdown-wrapper">
            <div class="cat-dropdown-trigger" id="cat-dropdown-trigger" onclick="toggleCatDropdown()">
              <i class="fa-solid fa-layer-group cat-trigger-icon"></i>
              <span class="cat-trigger-label" id="cat-trigger-label">Toutes les catégories</span>
              <span class="cat-trigger-badge" id="cat-trigger-badge"></span>
              <i class="fa-solid fa-chevron-down cat-trigger-arrow"></i>
            </div>
            <div class="cat-dropdown-menu" id="cat-dropdown-menu">
              <div class="cat-dropdown-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="cat-search-input" placeholder="Rechercher une catégorie..." oninput="filterCatItems(this.value)">
              </div>
              <div class="cat-dropdown-list" id="cat-dropdown-list">
                <div class="cat-dropdown-item active" data-value="" onclick="selectCategory('', 'Toutes les catégories', this)">
                  <span class="cat-item-dot"></span>
                  Toutes les catégories
                  <i class="fa-solid fa-check cat-item-check"></i>
                </div>
                <?php foreach ($categories as $c): ?>
                <div class="cat-dropdown-item" data-value="<?= (int)$c['id_categorie'] ?>" onclick="selectCategory('<?= (int)$c['id_categorie'] ?>', '<?= ea($c['nom_categorie']) ?>', this)">
                  <span class="cat-item-dot"></span>
                  <?= ea($c['nom_categorie']) ?>
                  <i class="fa-solid fa-check cat-item-check"></i>
                </div>
                <?php endforeach; ?>
                <div class="cat-dropdown-no-result" id="cat-no-result">Aucune catégorie trouvée</div>
              </div>
            </div>
          </div>
          <!-- Hidden select for compatibility -->
          <select id="category-filter" style="display:none;" onchange="onCategoryFilterChange()">
            <option value="">Toutes les catégories</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= (int)$c['id_categorie'] ?>"><?= ea($c['nom_categorie']) ?></option>
            <?php endforeach; ?>
          </select>
          <div style="display:flex;gap:8px;flex-wrap:wrap;" id="filter-btns">
            <button class="btn btn-primary"   onclick="setFilter('tous',this)">Tous</button>
            <button class="btn btn-secondary" onclick="setFilter('publiée',this)">Publiées</button>
            <button class="btn btn-secondary" onclick="setFilter('brouillon',this)">Brouillons</button>
            <button class="btn btn-secondary" onclick="setFilter('expirée',this)">Expirées</button>
            <button class="btn btn-secondary" onclick="setFilter('archivée',this)">Archivées</button>
          </div>
        </div>

        <!-- Bannière stock faible -->
        <div class="stock-alert-banner" id="stock-alert-banner">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <span>Stock faible détecté sur <span class="alert-count" id="stock-alert-count">0</span> offre(s) — quantité = 3</span>
          <button class="btn-filter-low" onclick="filterLowStock()">
            <i class="fa-solid fa-filter"></i> Voir uniquement
          </button>
        </div>

        <!-- Tableau -->
        <div class="card" style="padding:0;overflow:hidden;">
          <div style="overflow-x:auto;">
            <table class="data-table" id="offers-table">
              <thead>
                <tr>
                  <th>Offre</th>
                  <th>Partenaire</th>
                  <th>Prix</th>
                  <th>
                    <button id="btn-sort-stock" class="btn-sort-stock" onclick="sortByStock()" title="Trier par stock">
                      Stock <i class="sort-icon" id="sort-stock-icon">?</i>
                    </button>
                  </th>
                  <th>Statut</th>
                  <th style="text-align:center;">Actions</th>
                </tr>
              </thead>
              <tbody id="offers-tbody">
                <?php if (empty($offers)): ?>
                  <tr><td colspan="6">
                    <div class="empty-state"><i class="fa-solid fa-box-open"></i><p>Aucune offre trouvée.</p></div>
                  </td></tr>
                <?php else: $i = 0; foreach ($offers as $o): $i++;
                  $disc = $o['prix_original'] > 0 ? round((1 - $o['prix'] / $o['prix_original']) * 100) : 0;
                  $sCls = ['publiée'=>'s-publiee','brouillon'=>'s-brouillon','expirée'=>'s-expiree','archivée'=>'s-archivee'][$o['statut']] ?? '';
                  $stock = ($o['quantite'] ?? '?');
                ?>
                  <tr data-original-index="<?= $i ?>"
                      data-statut="<?= ea($o['statut']) ?>"
                      data-titre="<?= ea(strtolower($o['titre'])) ?>"
                      data-partenaire="<?= ea(strtolower($o['id_partenaire'] ?? '')) ?>"
                      data-categorie="<?= ea($o['cat_ids'] ?? '') ?>"
                      data-stock="<?= is_numeric($o['quantite'] ?? '') ? (int)$o['quantite'] : -1 ?>">
                    <td>
                      <div style="display:flex;align-items:center;gap:10px;">
                        <?php if (!empty($o['photo_url'])): ?>
                          <img src="<?= ea($o['photo_url']) ?>" style="width:40px;height:40px;object-fit:cover;border-radius:8px;" onerror="this.style.display='none'">
                        <?php else: ?>
                          <div style="width:40px;height:40px;border-radius:8px;background:var(--color-dark-hover);display:flex;align-items:center;justify-content:center;color:var(--color-text-muted);"><i class="fa-solid fa-utensils"></i></div>
                        <?php endif; ?>
                        <div>
                          <div style="font-weight:600;color:var(--color-text);font-size:.88rem;"><?= ea($o['titre']) ?></div>
                          <div class="cat-tags" style="margin-top:3px;">
                            <?php foreach (array_filter(explode(', ', $o['cat_noms'] ?? '')) as $cn): ?>
                              <span class="cat-tag"><?= ea($cn) ?></span>
                            <?php endforeach; ?>
                          </div>
                        </div>
                      </div>
                    </td>
                    <td style="font-size:.85rem;"><?= ea($o['id_partenaire'] ?? '—') ?></td>
                    <td>
                      <span style="text-decoration:line-through;color:var(--color-text-muted);font-size:.8rem;"><?= number_format($o['prix_original'] ?? 0, 2) ?> DT</span><br>
                      <span style="color:var(--color-primary);font-weight:700;"><?= number_format($o['prix'] ?? 0, 2) ?> DT</span>
                      <span style="color:#4ade80;font-size:.72rem;margin-left:2px;">?<?= $disc ?>%</span>
                    </td>
                    <td style="font-size:.85rem;">
                      <?php
                        $stockVal = is_numeric($stock) ? (int)$stock : null;
                        $isLow    = $stockVal !== null && $stockVal <= 3;
                      ?>
                      <span class="<?= $isLow ? 'stock-low' : '' ?>">
                        <?= ea($stock) ?>
                        <?php if ($isLow): ?>
                          <i class="fa-solid fa-triangle-exclamation stock-low-icon" title="Stock faible !"></i>
                        <?php endif; ?>
                      </span>
                    </td>
                    <td><span class="badge-statut <?= $sCls ?>"><?= ea($o['statut']) ?></span></td>
                    <td style="text-align:center;">
                      <div style="display:flex;gap:6px;justify-content:center;">
                        <button class="btn btn-secondary btn-sm" onclick="showEditForm(<?= (int)$o['id_offre'] ?>)" title="Modifier"><i class="fa-solid fa-pen"></i></button>
                        <button class="btn btn-danger btn-sm"    onclick="showDeleteForm(<?= (int)$o['id_offre'] ?>, '<?= ea($o['titre']) ?>')" title="Supprimer"><i class="fa-solid fa-trash"></i></button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div><!-- /view-list -->

      <!-- --------------------------------------
           VUE 2 : FORMULAIRE (Créer / Modifier / Supprimer)
      -------------------------------------- -->
      <div id="view-form">

        <!-- En-tête de la vue formulaire -->
        <div class="form-page-header">
          <button class="btn-back" onclick="showList()">
            <i class="fa-solid fa-arrow-left"></i> Retour
          </button>
          <div>
            <h2 id="form-heading">Ajouter une offre</h2>
            <p id="form-sub">Remplissez les informations ci-dessous</p>
          </div>
        </div>

        <!-- -- Formulaire CRÉER -- -->
        <div id="form-create-wrap">
          <div class="form-card">
            <form method="POST" action="/admin/offers.php" id="form-create" novalidate>
              <input type="hidden" name="action" value="create">

              <div class="form-group">
                <label>Titre <span style="color:var(--color-primary)">*</span></label>
                <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-tag"></i></span>
                  <input type="text" name="titre" id="create-titre" class="form-input" placeholder="Ex: Panier surprise"
                         value="<?= ea($old['titre'] ?? '') ?>">
                </div>
              </div>

              <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-textarea" rows="3" placeholder="Décrivez le contenu..."><?= ea($old['description'] ?? '') ?></textarea>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>Prix original (DT) <span style="color:var(--color-primary)">*</span></label>
                  <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-money-bill"></i></span>
                    <input type="number" name="prix_original" class="form-input" step="0.01" min="0.01" placeholder="12.00"
                           value="<?= ea($old['prix_original'] ?? '') ?>">
                  </div>
                </div>
                <div class="form-group">
                  <label>Prix réduit (DT) <span style="color:var(--color-primary)">*</span></label>
                  <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-percent"></i></span>
                    <input type="number" name="prix" class="form-input" step="0.01" min="0.01" placeholder="4.50"
                           value="<?= ea($old['prix'] ?? '') ?>">
                  </div>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>Quantité <span style="color:var(--color-primary)">*</span></label>
                  <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-boxes-stacked"></i></span>
                    <input type="number" name="quantite" class="form-input" min="1" placeholder="5"
                           value="<?= ea($old['quantite'] ?? '') ?>">
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label>Catégorie <span style="color:var(--color-primary)">*</span></label>
                <div class="cat-checkbox-list" id="create-cat-list">
                  <?php
                  $oldCatId = !empty($old['id_categorie']) ? (int)$old['id_categorie'] : 0;
                  foreach ($categories as $c):
                    $checked = ($oldCatId === (int)$c['id_categorie']) ? 'checked' : '';
                  ?>
                    <div class="cat-checkbox-item">
                      <label class="cat-pill-label">
                        <span class="cat-pill-box"></span>
                        <input type="radio" name="id_categorie" value="<?= (int)$c['id_categorie'] ?>" <?= $checked ?> style="display:none">
                        <span><?= strtoupper(ea($c['nom_categorie'])) ?></span>
                      </label>
                    </div>
                  <?php endforeach; ?>
                </div>
                <div id="create-cat-error" style="color:#f87171;font-size:.75rem;margin-top:4px;display:none;">Veuillez sélectionner une catégorie.</div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>Heure début</label>
                  <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-clock"></i></span>
                    <input type="text" name="heure_debut" class="form-input" placeholder="HH:MM"
                           value="<?= ea($old['heure_debut'] ?? '') ?>">
                  </div>
                </div>
                <div class="form-group">
                  <label>Heure fin</label>
                  <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-clock"></i></span>
                    <input type="text" name="heure_fin" class="form-input" placeholder="HH:MM"
                           value="<?= ea($old['heure_fin'] ?? '') ?>">
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label>Photo du produit</label>
                <input type="hidden" name="photo_url" id="create-photo-url" value="">
                <div class="photo-upload-area">
                  <input type="file" accept="image/*"
                         onchange="handlePhotoUpload(this,'create-photo-url','create-photo-preview','create-photo-placeholder')">
                  <div class="photo-placeholder" id="create-photo-placeholder">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    Cliquez ou glissez une image ici<br>
                    <small style="opacity:.6;">JPG, PNG, WEBP — max 2 Mo</small>
                  </div>
                  <img id="create-photo-preview" class="photo-preview" alt="Aperçu">
                </div>
              </div>

              <div class="form-group">
                <label>Statut</label>
                <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-toggle-on"></i></span>
                  <select name="statut" class="form-input">
                    <?php foreach (['publiée','brouillon','expirée','archivée'] as $s): ?>
                      <option value="<?= $s ?>" <?= (($old['statut'] ?? 'publiée') === $s) ? 'selected' : '' ?>>
                        <?= ucfirst($s) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="showList()">Annuler</button>
                <button type="submit" class="btn btn-primary">
                  <i class="fa-solid fa-plus"></i> Créer l'offre
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- -- Formulaire MODIFIER -- -->
        <div id="form-edit-wrap" style="display:none;">
          <div class="form-card">
            <form method="POST" action="/admin/offers.php" id="form-edit" novalidate>
              <input type="hidden" name="action"   value="update">
              <input type="hidden" name="id_offre" id="edit-id_offre">
              <div id="edit-body"></div>
              <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="showList()">Annuler</button>
                <button type="submit" class="btn btn-primary">
                  <i class="fa-solid fa-floppy-disk"></i> Enregistrer
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- -- Confirmer SUPPRESSION -- -->
        <div id="form-delete-wrap" style="display:none;">
          <div class="delete-inline-card">
            <i class="fa-solid fa-trash big-icon"></i>
            <h3>Supprimer cette offre ?</h3>
            <p id="delete-label"></p>
            <form method="POST" action="/admin/offers.php">
              <input type="hidden" name="action"   value="delete">
              <input type="hidden" name="id_offre" id="delete-id_offre">
              <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="showList()">Annuler</button>
                <button type="submit" class="btn btn-danger">
                  <i class="fa-solid fa-trash"></i> Supprimer
                </button>
              </div>
            </form>
          </div>
        </div>

      </div><!-- /view-form -->

    </div><!-- /page-content -->
  </main>
</div>

<!-- Données PHP pour le JS -->
<script>
const allOffersData     = <?= json_encode(array_values($offers ?? []), JSON_UNESCAPED_UNICODE) ?>;
const allCategoriesData = <?= json_encode($categories ?? [], JSON_UNESCAPED_UNICODE) ?>;
</script>

<script src="/js/app.js"></script>
<script src="/js/components.js"></script>
<script>
/* ------------------------------------------
   NAVIGATION ENTRE VUES (sans modal)
------------------------------------------ */
function showList() {
  document.getElementById('view-list').style.display = 'block';
  document.getElementById('view-form').style.display = 'none';
  document.getElementById('btn-add-offer').style.display = 'flex';
  document.getElementById('page-heading').textContent = 'Offres partenaires';
  document.getElementById('page-sub').textContent     = 'Liste et gestion de toutes les offres';
}

function showFormView(heading, sub) {
  document.getElementById('view-list').style.display = 'none';
  document.getElementById('view-form').style.display = 'block';
  document.getElementById('btn-add-offer').style.display = 'none';
  document.getElementById('form-heading').textContent = heading;
  document.getElementById('form-sub').textContent     = sub;
  // Cacher les 3 sous-vues
  document.getElementById('form-create-wrap').style.display = 'none';
  document.getElementById('form-edit-wrap').style.display   = 'none';
  document.getElementById('form-delete-wrap').style.display = 'none';
}

function showCreateForm() {
  showFormView('Ajouter une offre', 'Remplissez les informations ci-dessous');
  document.getElementById('form-create-wrap').style.display = 'block';
  syncCreateCategoryWithFilter();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function showEditForm(id) {
  const o = allOffersData.find(x => x.id_offre == id);
  if (!o) { alert('Offre introuvable.'); return; }
  showFormView('Modifier l\'offre', o.titre || '');
  document.getElementById('edit-id_offre').value = id;
  document.getElementById('edit-body').innerHTML  = buildEditForm(o);
  document.getElementById('form-edit-wrap').style.display = 'block';
  // Attacher validation temps réel
  document.getElementById('form-edit').querySelectorAll('input,textarea,select').forEach(el => {
    el.addEventListener('input', () => clearFieldError(el));
  });
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function showDeleteForm(id, titre) {
  showFormView('Supprimer une offre', '');
  document.getElementById('delete-id_offre').value   = id;
  document.getElementById('delete-label').textContent = '« ' + titre + ' » sera définitivement supprimée.';
  document.getElementById('form-delete-wrap').style.display = 'block';
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ------------------------------------------
   GÉNÉRATION DU FORMULAIRE MODIFIER
------------------------------------------ */
function esc(s) { return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function buildEditForm(o) {
  const offerCatId = o.id_categorie ? Number(o.id_categorie) : (o.cat_ids ? Number(o.cat_ids.split(',')[0]) : 0);
  const catCheckboxes = allCategoriesData.map(c =>
    `<div class="cat-checkbox-item">
      <label class="cat-pill-label">
        <span class="cat-pill-box"></span>
        <input type="radio" name="id_categorie" value="${c.id_categorie}" ${offerCatId === Number(c.id_categorie) ? 'checked' : ''} style="display:none">
        <span>${esc(c.nom_categorie).toUpperCase()}</span>
      </label>
    </div>`
  ).join('');
  const statuts = ['publiée','brouillon','expirée','archivée'];
  const statutOpts = statuts.map(s =>
    `<option value="${s}" ${(o.statut??'publiée')===s?'selected':''}>${s.charAt(0).toUpperCase()+s.slice(1)}</option>`
  ).join('');
  const existingPhoto = o.photo_url || '';

  return `
    <div class="form-group">
      <label>Titre <span style="color:var(--color-primary)">*</span></label>
      <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-tag"></i></span>
        <input type="text" name="titre" class="form-input" value="${esc(o.titre??'')}" required>
      </div>
    </div>
    <div class="form-group">
      <label>Description</label>
      <textarea name="description" class="form-textarea" rows="3">${esc(o.description??'')}</textarea>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Prix original (DT) <span style="color:var(--color-primary)">*</span></label>
        <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-money-bill"></i></span>
          <input type="number" name="prix_original" class="form-input" step="0.01" min="0.01" value="${esc(o.prix_original??'')}">
        </div>
      </div>
      <div class="form-group">
        <label>Prix réduit (DT) <span style="color:var(--color-primary)">*</span></label>
        <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-percent"></i></span>
          <input type="number" name="prix" class="form-input" step="0.01" min="0.01" value="${esc(o.prix??'')}">
        </div>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Quantité <span style="color:var(--color-primary)">*</span></label>
        <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-boxes-stacked"></i></span>
          <input type="text" name="quantite" class="form-input" value="${esc(o.quantite??'')}">
        </div>
      </div>
    </div>
    <div class="form-group">
      <label>Catégorie <span style="color:var(--color-primary)">*</span></label>
      <div class="cat-checkbox-list" id="edit-cat-list">${catCheckboxes}</div>
      <div id="edit-cat-error" style="color:#f87171;font-size:.75rem;margin-top:4px;display:none;">Veuillez sélectionner une catégorie.</div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Heure début</label>
        <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-clock"></i></span>
          <input type="text" name="heure_debut" class="form-input" placeholder="HH:MM" value="${(o.heure_debut??'').substring(0,5)}">
        </div>
      </div>
      <div class="form-group">
        <label>Heure fin</label>
        <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-clock"></i></span>
          <input type="text" name="heure_fin" class="form-input" placeholder="HH:MM" value="${(o.heure_fin??'').substring(0,5)}">
        </div>
      </div>
    </div>
    <div class="form-group">
      <label>Photo</label>
      <input type="hidden" name="photo_url" id="edit-photo-url" value="${esc(existingPhoto)}">
      <div class="photo-upload-area">
        <input type="file" accept="image/*" onchange="handlePhotoUpload(this,'edit-photo-url','edit-photo-preview','edit-photo-placeholder')">
        <div class="photo-placeholder" id="edit-photo-placeholder" ${existingPhoto?'style="display:none"':''}>
          <i class="fa-solid fa-cloud-arrow-up"></i>
          Cliquez ou glissez une image<br><small style="opacity:.6;">JPG, PNG, WEBP — max 2 Mo</small>
        </div>
        <img id="edit-photo-preview" class="photo-preview" alt="Aperçu" ${existingPhoto?`src="${esc(existingPhoto)}" style="display:block;"`:''}>
      </div>
    </div>
    <div class="form-group">
      <label>Statut</label>
      <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-toggle-on"></i></span>
        <select name="statut" class="form-input">${statutOpts}</select>
      </div>
    </div>`;
}

/* ------------------------------------------
   VALIDATION
------------------------------------------ */
function showFieldError(input, msg) {
  input.style.borderColor = '#f87171';
  let err = input.parentElement.parentElement.querySelector('.field-error');
  if (!err) {
    err = document.createElement('small');
    err.className = 'field-error';
    err.style.cssText = 'color:#f87171;font-size:.75rem;margin-top:4px;display:block;';
    input.parentElement.parentElement.appendChild(err);
  }
  err.textContent = msg;
}
function clearFieldError(input) {
  input.style.borderColor = '';
  const err = input.parentElement.parentElement.querySelector('.field-error');
  if (err) err.remove();
}
function validateOfferForm(form) {
  let valid = true;
  const titre = form.querySelector('[name="titre"]');
  if (titre) {
    const v = titre.value.trim();
    if (!v) { showFieldError(titre, 'Le titre est obligatoire.'); valid = false; }
    else if (v.length < 3) { showFieldError(titre, 'Minimum 3 caractères.'); valid = false; }
    else clearFieldError(titre);
  }
  const prixOrig = form.querySelector('[name="prix_original"]');
  if (prixOrig) {
    const v = parseFloat(prixOrig.value);
    if (!prixOrig.value || isNaN(v) || v <= 0) { showFieldError(prixOrig, 'Le prix original doit être un nombre positif.'); valid = false; }
    else clearFieldError(prixOrig);
  }
  const prix = form.querySelector('[name="prix"]');
  if (prix) {
    const v = parseFloat(prix.value);
    if (!prix.value || isNaN(v) || v <= 0) { showFieldError(prix, 'Le prix réduit doit être un nombre positif.'); valid = false; }
    else if (prixOrig && parseFloat(prixOrig.value) > 0 && v >= parseFloat(prixOrig.value)) {
      showFieldError(prix, 'Le prix réduit doit être inférieur au prix original.'); valid = false;
    }
    else clearFieldError(prix);
  }
  const qte = form.querySelector('[name="quantite"]');
  if (qte) {
    const v = parseInt(qte.value);
    if (!qte.value || isNaN(v) || v < 1) { showFieldError(qte, 'La quantité doit être au moins 1.'); valid = false; }
    else clearFieldError(qte);
  }
  const catRadios = form.querySelectorAll('[name="id_categorie"]');
  const catErrorEl = form.querySelector('#create-cat-error, #edit-cat-error');
  if (catRadios.length > 0) {
    const anyChecked = Array.from(catRadios).some(r => r.checked);
    if (!anyChecked) { if (catErrorEl) catErrorEl.style.display = 'block'; valid = false; }
    else             { if (catErrorEl) catErrorEl.style.display = 'none'; }
  }
  const timeRe = /^([01]\d|2[0-3]):([0-5]\d)$/;
  const hd = form.querySelector('[name="heure_debut"]');
  const hf = form.querySelector('[name="heure_fin"]');
  if (hd && hd.value && !timeRe.test(hd.value.trim())) { showFieldError(hd, 'Format invalide. Ex: 08:30'); valid = false; }
  else if (hd && hd.value) clearFieldError(hd);
  if (hf && hf.value && !timeRe.test(hf.value.trim())) { showFieldError(hf, 'Format invalide. Ex: 18:00'); valid = false; }
  else if (hd && hf && hd.value && hf.value && timeRe.test(hd.value) && timeRe.test(hf.value) && hf.value <= hd.value) {
    showFieldError(hf, 'Heure de fin doit être après l\'heure de début.'); valid = false;
  }
  else if (hf && hf.value) clearFieldError(hf);
  return valid;
}

/* ------------------------------------------
   UPLOAD PHOTO
------------------------------------------ */
function handlePhotoUpload(input, urlId, previewId, placeholderId) {
  const file = input.files[0];
  if (!file) return;
  if (file.size > 2 * 1024 * 1024) { alert('Image trop lourde (max 2 Mo).'); return; }
  const reader = new FileReader();
  reader.onload = e => {
    const urlEl   = document.getElementById(urlId);
    const preview = document.getElementById(previewId);
    const ph      = document.getElementById(placeholderId);
    if (urlEl)   urlEl.value = e.target.result;
    if (preview) { preview.src = e.target.result; preview.style.display = 'block'; }
    if (ph)      ph.style.display = 'none';
  };
  reader.readAsDataURL(file);
}

/* ------------------------------------------
   FILTRES
------------------------------------------ */
let currentFilter = 'tous';
let currentCategoryFilter = '';

function setFilter(f, btn) {
  currentFilter = f;
  document.getElementById('filter-btns').querySelectorAll('button').forEach(b => {
    b.classList.remove('btn-primary'); b.classList.add('btn-secondary');
  });
  if (btn) { btn.classList.remove('btn-secondary'); btn.classList.add('btn-primary'); }
  applyFilters();
}
/* Custom Category Dropdown JS */
function toggleCatDropdown() {
  const w = document.getElementById('cat-dropdown-wrapper');
  const isOpen = w.classList.toggle('open');
  if (isOpen) {
    setTimeout(() => document.getElementById('cat-search-input').focus(), 50);
  }
}
function selectCategory(value, label, el) {
  document.querySelectorAll('.cat-dropdown-item').forEach(i => i.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('cat-trigger-label').textContent = label;
  const badge = document.getElementById('cat-trigger-badge');
  if (value) { badge.textContent = '1'; badge.classList.add('visible'); }
  else { badge.classList.remove('visible'); }
  const sel = document.getElementById('category-filter');
  sel.value = value;
  document.getElementById('cat-dropdown-wrapper').classList.remove('open');
  onCategoryFilterChange();
}
function filterCatItems(query) {
  const q = query.trim().toLowerCase();
  const items = document.querySelectorAll('#cat-dropdown-list .cat-dropdown-item');
  let found = 0;
  items.forEach(item => {
    const txt = item.textContent.trim().toLowerCase();
    const match = txt.includes(q);
    item.style.display = match ? '' : 'none';
    if (match) found++;
  });
  document.getElementById('cat-no-result').style.display = found === 0 ? 'block' : 'none';
}
document.addEventListener('click', function(e) {
  const w = document.getElementById('cat-dropdown-wrapper');
  if (w && !w.contains(e.target)) w.classList.remove('open');
});

function onCategoryFilterChange() {
  const select = document.getElementById('category-filter');
  currentCategoryFilter = select ? (select.value || '') : '';
  applyFilters();
  syncCreateCategoryWithFilter();
}
function syncCreateCategoryWithFilter() {
  const createForm = document.getElementById('form-create');
  if (!createForm) return;
  const radios = createForm.querySelectorAll('[name="id_categorie"]');
  if (!radios.length || !currentCategoryFilter) return;
  radios.forEach(r => { r.checked = (r.value === String(currentCategoryFilter)); });
}
function applyFilters() {
  showingLowStockOnly = false;
  const btn = document.querySelector('.btn-filter-low');
  if (btn) { btn.innerHTML = '<i class="fa-solid fa-filter"></i> Voir uniquement'; btn.style.background = ''; }
  const q = (document.getElementById('search-input').value || '').toLowerCase();
  document.querySelectorAll('#offers-tbody tr[data-statut]').forEach(row => {
    const statut     = row.dataset.statut     || '';
    const titre      = row.dataset.titre      || '';
    const partenaire = row.dataset.partenaire || '';
    const categorie  = row.dataset.categorie  || '';
    const matchFilter   = currentFilter === 'tous' || statut === currentFilter;
    const matchCategory = !currentCategoryFilter || categorie.split(',').includes(currentCategoryFilter);
    const matchSearch   = !q || titre.includes(q) || partenaire.includes(q);
    row.style.display = (matchFilter && matchCategory && matchSearch) ? '' : 'none';
  });
  checkLowStock();
}

/* ------------------------------------------
   ALERTE STOCK FAIBLE
------------------------------------------ */
const STOCK_LOW_THRESHOLD = 3;
let showingLowStockOnly = false;

function checkLowStock() {
  const rows = document.querySelectorAll('#offers-tbody tr[data-statut]');
  let count = 0;
  rows.forEach(row => {
    const stock = parseInt(row.dataset.stock);
    if (stock >= 0 && stock <= STOCK_LOW_THRESHOLD) count++;
  });

  const banner = document.getElementById('stock-alert-banner');
  const countEl = document.getElementById('stock-alert-count');

  if (count > 0) {
    banner.style.display = 'flex';
    countEl.textContent  = count;
  } else {
    banner.style.display = 'none';
    showingLowStockOnly  = false;
  }
}

function filterLowStock() {
  showingLowStockOnly = !showingLowStockOnly;
  const btn = document.querySelector('.btn-filter-low');

  if (showingLowStockOnly) {
    // Masquer toutes les lignes sauf stock faible
    document.querySelectorAll('#offers-tbody tr[data-statut]').forEach(row => {
      const stock = parseInt(row.dataset.stock);
      const isLow = stock >= 0 && stock <= STOCK_LOW_THRESHOLD;
      row.style.display = isLow ? '' : 'none';
    });
    btn.innerHTML = '<i class="fa-solid fa-xmark"></i> Voir tout';
    btn.style.background = 'rgba(239,68,68,.3)';
  } else {
    applyFilters(); // Remettre les filtres normaux
    btn.innerHTML = '<i class="fa-solid fa-filter"></i> Voir uniquement';
    btn.style.background = '';
  }
}

/* ------------------------------------------
   TRI PAR STOCK (croissant / décroissant / original)
------------------------------------------ */
let stockSortDir = null; // null | 'asc' | 'desc'

function sortByStock() {
  // Cycle : null ? asc ? desc ? null
  if (stockSortDir === null)        stockSortDir = 'asc';
  else if (stockSortDir === 'asc')  stockSortDir = 'desc';
  else                              stockSortDir = null;

  // Mettre à jour l'icône et la couleur du bouton
  const icon = document.getElementById('sort-stock-icon');
  const btn  = document.getElementById('btn-sort-stock');

  if (stockSortDir === 'asc') {
    icon.textContent = '?';
    btn.classList.add('active');
    btn.title = 'Stock croissant — cliquer pour décroissant';
  } else if (stockSortDir === 'desc') {
    icon.textContent = '?';
    btn.classList.add('active');
    btn.title = 'Stock décroissant — cliquer pour annuler';
  } else {
    icon.textContent = '?';
    btn.classList.remove('active');
    btn.title = 'Trier par stock';
  }

  const tbody = document.getElementById('offers-tbody');
  const rows  = Array.from(tbody.querySelectorAll('tr[data-statut]'));

  if (stockSortDir === null) {
    // Restaurer l'ordre original PHP via data-original-index
    rows.sort((a, b) =>
      parseInt(a.dataset.originalIndex || 0) - parseInt(b.dataset.originalIndex || 0)
    );
  } else {
    rows.sort((a, b) => {
      // Lire la valeur de la cellule Stock (4e colonne, index 3)
      const getStock = row => {
        const td = row.querySelectorAll('td')[3];
        if (!td) return -1;
        const val = parseInt(td.textContent.trim());
        // Les stocks indéterminés '?' sont placés en dernier
        return isNaN(val) ? (stockSortDir === 'asc' ? Infinity : -Infinity) : val;
      };
      const diff = getStock(a) - getStock(b);
      return stockSortDir === 'asc' ? diff : -diff;
    });
  }

  // Ré-insérer les lignes dans le nouvel ordre
  rows.forEach(row => tbody.appendChild(row));
}

/* ------------------------------------------
   FORMULAIRES — Attacher validation
------------------------------------------ */
document.addEventListener('DOMContentLoaded', () => {
  const createForm = document.getElementById('form-create');
  if (createForm) {
    createForm.addEventListener('submit', e => { if (!validateOfferForm(createForm)) e.preventDefault(); });
    createForm.querySelectorAll('input,textarea,select').forEach(el => {
      el.addEventListener('input', () => clearFieldError(el));
    });
  }
  const editForm = document.getElementById('form-edit');
  if (editForm) {
    editForm.addEventListener('submit', e => { if (!validateOfferForm(editForm)) e.preventDefault(); });
  }

  // Vérifier les stocks faibles au chargement
  checkLowStock();

  // Ré-ouvrir la vue formulaire si erreurs serveur
  <?php if (!empty($errors) && !empty($old) && isset($old['id_offre'])): ?>
    showEditForm(<?= (int)$old['id_offre'] ?>);
  <?php elseif (!empty($errors) && !empty($old)): ?>
    showCreateForm();
  <?php endif; ?>
});

/* ------------------------------------------
   SIDEBAR / LOGOUT
------------------------------------------ */
const mt = document.getElementById('menu-toggle');
const sb = document.getElementById('sidebar');
const ov = document.getElementById('sidebar-overlay');
if (mt) mt.addEventListener('click', () => sb.classList.toggle('open'));
if (ov) ov.addEventListener('click', () => sb.classList.remove('open'));
const logoutBtn = document.querySelector('[data-action="logout"]');
if (logoutBtn) logoutBtn.addEventListener('click', () => { if (typeof App !== 'undefined') App.logout(); });
</script>
  <script src="/js/admin-voice-assistant.js?v=20260508"></script>
</body>
</html>















