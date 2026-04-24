Ã¯»Â¿<?php
// Ce fichier est rendu par OfferController::index()
// Variables disponibles : $offers, $categories, $counts, $active, $expired, $flash
$flash  = $flash  ?? null;
$errors = $_SESSION['errors'] ?? [];
$old    = $_SESSION['old']    ?? [];
unset($_SESSION['errors'], $_SESSION['old']);

// Aide : afficher une valeur en sÃÂ©curitÃÂ©
function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mes Offres - CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/caremeal/css/main.css">
  <link rel="stylesheet" href="/caremeal/css/components.css">
  <link rel="stylesheet" href="/caremeal/css/dashboard.css">
  <style>
    .modal-overlay {
      display: none; position: fixed; inset: 0;
      background: rgba(0,0,0,.75); z-index: 1000;
      align-items: center; justify-content: center;
    }
    .modal-overlay.active { display: flex; }
    .modal {
      background: var(--color-dark-card); border-radius: 16px;
      width: 100%; max-width: 560px; max-height: 90vh; overflow-y: auto;
      border: 1px solid var(--color-dark-border);
    }
    .modal-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 20px 24px; border-bottom: 1px solid var(--color-dark-border);
    }
    .modal-header h3 { margin: 0; font-size: 1.1rem; color: var(--color-white); }
    .modal-body  { padding: 24px; }
    .modal-footer {
      padding: 16px 24px; border-top: 1px solid var(--color-dark-border);
      display: flex; gap: 12px; justify-content: flex-end;
    }
    .modal-close { background: none; border: none; color: var(--color-text-muted); font-size: 1.3rem; cursor: pointer; }
    .modal-close:hover { color: var(--color-white); }
    /* Fix select/option dark theme */
    select.form-input option {
      background-color: var(--color-dark-card, #1e2433);
      color: var(--color-text, #e2e8f0);
    }
    select.form-input {
      background-color: var(--color-dark-input, #252d3d);
      color: var(--color-text, #e2e8f0);
    }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .delete-confirm {
      background: var(--color-dark-card); border-radius: 16px;
      padding: 32px; max-width: 420px; width: 100%; text-align: center;
      border: 1px solid var(--color-dark-border);
    }
    .delete-confirm p { color: var(--color-text-muted); margin: 8px 0 24px; }
    .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
    .empty-state { text-align: center; padding: 40px 20px; color: var(--color-text-muted); grid-column: 1/-1; }
    .empty-state i { font-size: 2.5rem; margin-bottom: 12px; opacity: .4; display: block; }
    .offer-card-actions {
      display: flex; gap: 8px; margin-top: 12px; padding-top: 12px;
      border-top: 1px solid var(--color-dark-border);
    }
    .badge-statut { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: .72rem; font-weight: 600; }
    .s-publiee   { background: rgba(34,197,94,.15);   color: #4ade80; }
    .s-brouillon { background: rgba(251,191,36,.15);  color: #fbbf24; }
    .s-expiree, .s-archivee { background: rgba(156,163,175,.12); color: #9ca3af; }
    .photo-upload-area {
      border: 2px dashed var(--color-dark-border); border-radius: 12px;
      padding: 20px; text-align: center; cursor: pointer;
      transition: border-color .2s; position: relative;
    }
    .photo-upload-area:hover { border-color: var(--color-primary); }
    .photo-upload-area input[type="file"] {
      position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer;
    }
    .photo-preview { width: 100%; height: 140px; object-fit: cover; border-radius: 8px; margin-top: 10px; display: none; }
    .photo-placeholder { color: var(--color-text-muted); font-size: .85rem; pointer-events: none; }
    .photo-placeholder i { font-size: 2rem; display: block; margin-bottom: 6px; opacity: .5; }
    .alert { padding: 12px 18px; border-radius: 10px; margin-bottom: 18px; font-size: .9rem; }
    .alert-success { background: rgba(34,197,94,.12); color: #4ade80; border: 1px solid rgba(34,197,94,.2); }
    .alert-error   { background: rgba(239,68,68,.12);  color: #f87171; border: 1px solid rgba(239,68,68,.2); }

    /* Multi-category checkboxes – screenshot style (square box + uppercase) */
    .cat-checkbox-list { display:flex; flex-wrap:wrap; gap:10px; padding:8px 0; }
    .cat-pill-label {
      display:flex; align-items:center; gap:8px; cursor:pointer;
      background:#1e2433; border:1px solid #2d3748;
      border-radius:8px; padding:8px 16px;
      font-size:.82rem; font-weight:700; color:#e2e8f0;
      letter-spacing:.04em; user-select:none;
      transition:border-color .15s, background .15s;
    }
    .cat-pill-box {
      width:16px; height:16px; border:2px solid #4a5568;
      border-radius:3px; background:#252d3d; flex-shrink:0;
      display:flex; align-items:center; justify-content:center;
      transition:background .15s, border-color .15s;
    }
    .cat-pill-label:has(input:checked) .cat-pill-box {
      background: var(--color-primary, #ef4444);
      border-color: var(--color-primary, #ef4444);
    }
    .cat-pill-label:has(input:checked) .cat-pill-box::after {
      content: '';
      display: block;
      width: 5px; height: 9px;
      border: 2px solid #fff;
      border-top: none; border-left: none;
      transform: rotate(45deg) translate(-1px,-1px);
    }
    .cat-pill-label:has(input:checked) {
      border-color: var(--color-primary, #ef4444);
      background: rgba(239,68,68,.1);
      color: #fff;
    }
    .cat-pill-label:hover { border-color:#4a5568; background:#252d3d; }
    /* keep backward compat for filter pills (rounded) */
    .cat-checkbox-item label:not(.cat-pill-label) {
      display:flex; align-items:center; gap:6px; cursor:pointer;
      background:var(--color-dark-hover); border:1px solid var(--color-dark-border);
      border-radius:20px; padding:6px 14px; font-size:.82rem; color:var(--color-text);
      transition:border-color .15s, background .15s; user-select:none;
    }
    .cat-checkbox-item input[type=checkbox]:not([style]) { accent-color:var(--color-primary); width:14px; height:14px; }
    .cat-checkbox-item label:not(.cat-pill-label):has(input:checked) {
      border-color:var(--color-primary); background:rgba(239,68,68,.12);
      color:var(--color-white); font-weight:600;
    }
    .cat-tags { display:flex; flex-wrap:wrap; gap:4px; }
    .cat-tag { display:inline-block; padding:2px 8px; border-radius:12px; font-size:.7rem; font-weight:600;
               background:rgba(239,68,68,.15); color:#f87171; }

    .category-choice-wrap { margin-top: 12px; }
    .category-choice-select {
      width: 100%;
      max-width: 340px;
      height: 58px;
      border: 1px solid rgba(255,255,255,.28);
      border-radius: 0;
      background: rgba(255,255,255,.03);
      color: var(--color-white);
      font-size: 1.05rem;
      font-weight: 500;
      padding: 0 18px;
      outline: none;
      transition: border-color .2s ease, box-shadow .2s ease;
    }
    .category-choice-select:focus {
      border-color: var(--color-primary);
      box-shadow: 0 0 0 2px rgba(var(--color-primary-rgb), .25);
    }
    .category-choice-select option {
      background: var(--color-dark-card);
      color: var(--color-white);
    }
  </style>
</head>
<body>
<div class="dashboard-layout">

  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo"><img src="/caremeal/assets/logo.png" alt="Logo" style="max-width:100%;max-height:100%;object-fit:contain;"></div>
      <div class="sidebar-brand">Care<span>Meal</span></div>
    </div>
    <nav class="sidebar-nav">
      <div class="sidebar-section">
        <div class="sidebar-section-title">Partenaire</div>
        <a href="/caremeal/partner/dashboard.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-house"></i></span> Mon Etablissement</a>
        <a href="offers.php"                      class="sidebar-link active"><span class="link-icon"><i class="fa-solid fa-box"></i></span> Mes Offres</a>
        <a href="/caremeal/partner/events.html"    class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> Evenements</a>
        <a href="/caremeal/partner/stats.html"     class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-chart-column"></i></span> Statistiques</a>
        <a href="/caremeal/partner/settings.html"  class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-gear"></i></span> ParamÃÂ¨tres</a>
      </div>
    </nav>
    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="avatar" id="sidebar-user-avatar">P</div>
        <div class="sidebar-user-info">
          <div class="sidebar-user-name" id="sidebar-user-name">Partenaire</div>
          <div class="sidebar-user-role" id="sidebar-user-role">Partenaire</div>
        </div>
        <button class="sidebar-logout" data-action="logout" title="Déconnexion"><i class="fa-solid fa-door-open"></i></button>
      </div>
    </div>
  </aside>
  <div class="sidebar-overlay" id="sidebar-overlay"></div>

  <main class="main-content">
    <header class="top-header">
      <div class="header-left">
        <button class="menu-toggle" id="menu-toggle"><i class="fa-solid fa-bars"></i></button>
        <div class="page-title"><h2>Mes Offres</h2><p>GÃÂ©rez vos offres anti-gaspillage</p></div>
      </div>
      <div class="header-right">
        <button class="btn btn-primary" onclick="openModal('modal-create')">
          <i class="fa-solid fa-plus"></i> Nouvelle offre
        </button>
      </div>
    </header>

    <div class="page-content">

      <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
          <i class="fa-solid fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'triangle-exclamation' ?>"></i>
          <?= e($flash['msg']) ?>
        </div>
      <?php endif; ?>

      <?php if ($errors): ?>
        <div class="alert alert-error">
          <?php foreach ($errors as $err): ?>
            <div><i class="fa-solid fa-triangle-exclamation"></i> <?= e($err) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Stats -->
      <div class="grid grid-2 gap-4 mb-8 animate-fade-in-up">
        <div class="card" style="display:flex;align-items:center;gap:16px;">
          <div class="stat-icon green" style="width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;"><i class="fa-solid fa-check"></i></div>
          <div>
            <div style="font-size:1.5rem;font-weight:700;color:var(--color-white);"><?= (int)($counts['publiée'] ?? 0) ?></div>
            <div style="font-size:.8rem;color:var(--color-text-muted);">Offres actives</div>
          </div>
        </div>
        <div class="card" style="display:flex;align-items:center;gap:16px;">
          <div class="stat-icon yellow" style="width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;"><i class="fa-solid fa-clock"></i></div>
          <div>
            <div style="font-size:1.5rem;font-weight:700;color:var(--color-text-muted);"><?= (int)(($counts['expirée'] ?? 0) + ($counts['archivée'] ?? 0)) ?></div>
            <div style="font-size:.8rem;color:var(--color-text-muted);">Expirées / archivées</div>
          </div>
        </div>
      </div>

      <div class="card animate-fade-in-up" style="padding:16px;margin-bottom:24px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:10px;">
          <h3 style="margin:0;font-size:1rem;"><i class="fa-solid fa-tags"></i> Filtrer par catégorie</h3>
          <small style="color:var(--color-text-muted);">Cochez les catégories à afficher. La sélection se reporte dans "Nouvelle offre".</small>
        </div>
        <div class="cat-checkbox-list" id="partner-filter-cat-list">
          <div class="cat-checkbox-item" id="cat-all-item">
            <label>
              <input type="checkbox" id="cat-all-check" checked onchange="onCatAllToggle(this)">
              <span>Toutes</span>
            </label>
          </div>
          <?php foreach (($categories ?? []) as $cat): ?>
            <div class="cat-checkbox-item">
              <label>
                <input type="checkbox" class="cat-filter-check" value="<?= (int)$cat['id_categorie'] ?>" checked
                       onchange="onPartnerCategoryChange()">
                <span><?= e($cat['nom_categorie']) ?></span>
              </label>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Offres actives -->
      <div class="section animate-fade-in-up stagger-1">
        <div class="section-header"><h3><i class="fa-solid fa-check"></i> Offres actives</h3></div>
        <div class="grid grid-3 gap-6">
          <?php if (empty($active)): ?>
            <div class="empty-state"><i class="fa-solid fa-box-open"></i><p>Aucune offre active. Publiez votre premiÃÂ¨re offre !</p></div>
          <?php else: foreach ($active as $o): ?>
            <?php
              $disc = $o['prix_original'] > 0 ? round((1 - $o['prix'] / $o['prix_original']) * 100) : 0;
              $sCls = ['publiée'=>'s-publiee','brouillon'=>'s-brouillon','expirée'=>'s-expiree','archivée'=>'s-archivee'][$o['statut']] ?? '';
            ?>
            <div class="offer-card" data-cat-id="<?= e($o['cat_ids'] ?? (string)($o['id_categorie'] ?? '')) ?>">
              <div style="position:relative;">
                <?php if (!empty($o['photo_url'])): ?>
                  <img src="<?= e($o['photo_url']) ?>" style="width:100%;height:140px;object-fit:cover;border-radius:12px 12px 0 0;" onerror="this.style.display='none'">
                <?php else: ?>
                  <div style="width:100%;height:140px;background:var(--color-dark-hover);border-radius:12px 12px 0 0;display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:var(--color-text-muted);"><i class="fa-solid fa-utensils"></i></div>
                <?php endif; ?>
                <span style="position:absolute;top:10px;left:10px;background:var(--color-primary);color:#fff;padding:4px 10px;border-radius:20px;font-size:.78rem;font-weight:700;">-<?= $disc ?>%</span>
                <span style="position:absolute;top:10px;right:10px;"><span class="badge-statut <?= $sCls ?>"><?= e($o['statut']) ?></span></span>
              </div>
              <div class="offer-card-body">
                <h4 style="margin:0 0 4px;color:var(--color-white);font-size:.95rem;"><?= e($o['titre']) ?></h4>
                <?php if (!empty($o['nom_categorie'])): ?>
                  <p style="font-size:.75rem;color:var(--color-primary);margin:0 0 6px;"><?= e($o['nom_categorie']) ?></p>
                <?php endif; ?>
                <p style="font-size:.8rem;color:var(--color-text-muted);margin-bottom:12px;line-height:1.4;"><?= e($o['description']) ?></p>
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:6px;">
                  <div>
                    <span style="text-decoration:line-through;color:var(--color-text-muted);font-size:.85rem;"><?= number_format($o['prix_original'], 2) ?> DT</span>
                    <span style="color:var(--color-primary);font-size:1.05rem;font-weight:700;margin-left:6px;"><?= number_format($o['prix'], 2) ?> DT</span>
                  </div>
                  <?php if (!empty($o['heure_debut'])): ?>
                    <span style="font-size:.78rem;color:var(--color-text-muted);"><i class="fa-solid fa-clock"></i> <?= e(substr($o['heure_debut'],0,5)) ?> - <?= e(substr($o['heure_fin'],0,5)) ?></span>
                  <?php endif; ?>
                </div>
                <p style="font-size:.75rem;color:var(--color-text-muted);margin-top:6px;"><i class="fa-solid fa-box"></i> <?= (int)($o['quantite_restante'] ?? $o['quantite']) ?>/<?= (int)$o['quantite'] ?> restants</p>
                <div class="offer-card-actions">
                  <button class="btn btn-secondary btn-sm" style="flex:1;" onclick="openEditModal(<?= $o['id_offre'] ?>)">
                    <i class="fa-solid fa-pen"></i> Modifier
                  </button>
                  <button class="btn btn-danger btn-sm" onclick="openDeleteModal(<?= $o['id_offre'] ?>, '<?= e($o['titre']) ?>')">
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </div>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <!-- Offres expirées / archivées -->
      <div class="section animate-fade-in-up stagger-2" style="margin-top:32px;">
        <div class="section-header"><h3><i class="fa-solid fa-clock"></i> Expirées / Archivées</h3></div>
        <div class="grid grid-3 gap-6">
          <?php if (empty($expired)): ?>
            <div class="empty-state"><i class="fa-solid fa-clock"></i><p>Aucune offre expirée.</p></div>
          <?php else: foreach ($expired as $o): ?>
            <?php
              $disc = $o['prix_original'] > 0 ? round((1 - $o['prix'] / $o['prix_original']) * 100) : 0;
              $sCls = ['publiée'=>'s-publiee','brouillon'=>'s-brouillon','expirée'=>'s-expiree','archivée'=>'s-archivee'][$o['statut']] ?? '';
            ?>
            <div class="offer-card" data-cat-id="<?= e($o['cat_ids'] ?? (string)($o['id_categorie'] ?? '')) ?>">
              <div style="position:relative;">
                <?php if (!empty($o['photo_url'])): ?>
                  <img src="<?= e($o['photo_url']) ?>" style="width:100%;height:140px;object-fit:cover;border-radius:12px 12px 0 0;" onerror="this.style.display='none'">
                <?php else: ?>
                  <div style="width:100%;height:140px;background:var(--color-dark-hover);border-radius:12px 12px 0 0;display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:var(--color-text-muted);"><i class="fa-solid fa-utensils"></i></div>
                <?php endif; ?>
                <span style="position:absolute;top:10px;left:10px;background:var(--color-primary);color:#fff;padding:4px 10px;border-radius:20px;font-size:.78rem;font-weight:700;">-<?= $disc ?>%</span>
                <span style="position:absolute;top:10px;right:10px;"><span class="badge-statut <?= $sCls ?>"><?= e($o['statut']) ?></span></span>
              </div>
              <div class="offer-card-body">
                <h4 style="margin:0 0 4px;color:var(--color-white);font-size:.95rem;"><?= e($o['titre']) ?></h4>
                <?php if (!empty($o['nom_categorie'])): ?>
                  <p style="font-size:.75rem;color:var(--color-primary);margin:0 0 6px;"><?= e($o['nom_categorie']) ?></p>
                <?php endif; ?>
                <p style="font-size:.8rem;color:var(--color-text-muted);margin-bottom:12px;line-height:1.4;"><?= e($o['description']) ?></p>
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:6px;">
                  <div>
                    <span style="text-decoration:line-through;color:var(--color-text-muted);font-size:.85rem;"><?= number_format($o['prix_original'], 2) ?> DT</span>
                    <span style="color:var(--color-primary);font-size:1.05rem;font-weight:700;margin-left:6px;"><?= number_format($o['prix'], 2) ?> DT</span>
                  </div>
                </div>
                <p style="font-size:.75rem;color:var(--color-text-muted);margin-top:6px;"><i class="fa-solid fa-box"></i> <?= (int)($o['quantite_restante'] ?? $o['quantite']) ?>/<?= (int)$o['quantite'] ?> restants</p>
                <div class="offer-card-actions">
                  <button class="btn btn-secondary btn-sm" style="flex:1;" onclick="openEditModal(<?= $o['id_offre'] ?>)">
                    <i class="fa-solid fa-pen"></i> Modifier
                  </button>
                  <button class="btn btn-danger btn-sm" onclick="openDeleteModal(<?= $o['id_offre'] ?>, '<?= e($o['titre']) ?>')">
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </div>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂ -->
<!--  MODAL CRÃÂÃÂÃÂ¢Ã¢ÂÂ¬ÃÂ°ER                                           -->
<!-- ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂ -->
<div class="modal-overlay" id="modal-create">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fa-solid fa-plus"></i> Nouvelle offre</h3>
      <button class="modal-close" onclick="closeModal('modal-create')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST" action="offers.php">
      <input type="hidden" name="action"        value="create">
      <input type="hidden" name="id_partenaire" id="create-id_partenaire" value="">
      <div class="modal-body">
        <?php include __DIR__ . '/offerform.php'; // formulaire partagÃÂ© ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-create')">Annuler</button>
        <button type="submit" class="btn btn-primary">
          <i class="fa-solid fa-plus"></i> Créer l'offre
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂ -->
<!--  MODAL MODIFIER  (peuplÃÂ© en JS depuis $offers PHP)     -->
<!-- ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂ -->
<div class="modal-overlay" id="modal-edit">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fa-solid fa-pen"></i> Modifier l'offre</h3>
      <button class="modal-close" onclick="closeModal('modal-edit')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST" action="offers.php" id="form-edit">
      <input type="hidden" name="action"   value="update">
      <input type="hidden" name="id_offre" id="edit-id_offre">
      <div class="modal-body" id="edit-body"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-edit')">Annuler</button>
        <button type="submit" class="btn btn-primary">
          <i class="fa-solid fa-floppy-disk"></i> Enregistrer
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂ -->
<!--  MODAL SUPPRIMER                                       -->
<!-- ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂ¢ÃÂÃÂ -->
<div class="modal-overlay" id="modal-delete">
  <div class="delete-confirm">
    <i class="fa-solid fa-trash" style="font-size:2.5rem;color:#f87171;margin-bottom:12px;display:block;"></i>
    <h3 style="color:var(--color-white);margin:0;">Supprimer l'offre ?</h3>
    <p id="delete-label">Cette action est irreversible.</p>
    <form method="POST" action="offers.php" style="display:flex;gap:12px;justify-content:center;">
      <input type="hidden" name="action"   value="delete">
      <input type="hidden" name="id_offre" id="delete-id_offre">
      <button type="button" class="btn btn-secondary" onclick="closeModal('modal-delete')">Annuler</button>
      <button type="submit" class="btn btn-danger">
        <i class="fa-solid fa-trash"></i> Supprimer
      </button>
    </form>
  </div>
</div>

<!-- DonnÃÂ©es des offres injectÃÂ©es en PHP pour l'ÃÂÃÂÃÂÃÂ©dition JS -->
<script>
const allOffersData = <?= json_encode(array_values($offers ?? []), JSON_UNESCAPED_UNICODE) ?>;
const allCategoriesData = <?= json_encode(array_values($categories ?? []), JSON_UNESCAPED_UNICODE) ?>;
</script>

<script src="/caremeal/js/app.js"></script>
<script src="/caremeal/js/components.js"></script>
<script>
/* ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ Helpers modal ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ */
function openModal(id)  {
  const el=document.getElementById(id);
  el.classList.add('active');
  el.style.display='flex';
  document.body.style.overflow='hidden';
  if (id === 'modal-create') {
    syncCreateCategoryWithSelection();
  }
}
function closeModal(id) {
  const el=document.getElementById(id);
  el.classList.remove('active');
  el.style.display='none';
  document.body.style.overflow='';
  // Vider tous les champs si c'est le modal de crÃÂÃÂÃÂÃÂ©ation
  if (id === 'modal-create') {
    const form = el.querySelector('form');
    if (form) {
      form.reset();
      // RÃÂÃÂÃÂÃÂ©initialiser le champ cachÃÂÃÂÃÂÃÂ© photo_url
      const photoUrl = form.querySelector('#create-photo-url');
      if (photoUrl) photoUrl.value = '';
      // Masquer l'aperÃÂÃÂÃÂÃÂ§u photo et afficher le placeholder
      const preview = form.querySelector('#create-photo-preview');
      if (preview) { preview.src = ''; preview.style.display = 'none'; }
      const placeholder = form.querySelector('#create-photo-placeholder');
      if (placeholder) placeholder.style.display = '';
      // Supprimer les messages d'erreur ÃÂÃÂÃÂÃÂ©ventuels
      form.querySelectorAll('.field-error').forEach(e => e.remove());
      form.querySelectorAll('.form-input.invalid, .form-textarea.invalid').forEach(e => e.classList.remove('invalid'));
    }
  }
}

document.querySelectorAll('.modal-overlay').forEach(el =>
  el.addEventListener('click', e => { if (e.target === el) closeModal(el.id); })
);

/* ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ GÃÂ©nÃÂ©ration du formulaire d'ÃÂÃÂÃÂÃÂ©dition en JS ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ */
function esc(s) { return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function buildEditForm(o) {
  const statuts = ['publiée','brouillon','expirée','archivée'];
  const offerCatIds = (o.categorie_ids && Array.isArray(o.categorie_ids))
    ? o.categorie_ids.map(Number)
    : (o.cat_ids ? o.cat_ids.split(',').map(Number) : (o.id_categorie ? [Number(o.id_categorie)] : []));
  const catCheckboxes = (allCategoriesData || []).map(c =>
    `<div class="cat-checkbox-item">
      <label class="cat-pill-label">
        <span class="cat-pill-box"></span>
        <input type="checkbox" name="id_categories[]" value="${c.id_categorie}" ${offerCatIds.includes(Number(c.id_categorie)) ? 'checked' : ''} style="display:none">
        <span class="cat-pill-name">${esc(c.nom_categorie).toUpperCase()}</span>
      </label>
    </div>`
  ).join('');
  const statutOpts = statuts.map(s =>
    `<option value="${s}" ${(o.statut??'publiée')===s?'selected':''}>${s.charAt(0).toUpperCase()+s.slice(1)}</option>`
  ).join('');
  const existingPhoto = o.photo_url || '';

  return `
    <div class="form-group">
      <label>Titre <span style="color:var(--color-primary)">*</span></label>
      <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-tag"></i></span>
        <input type="text" name="titre" class="form-input" placeholder="Ex: Panier surprise" value="${esc(o.titre??'')}" required>
      </div>
    </div>
    <div class="form-group">
      <label>Description</label>
      <textarea name="description" class="form-textarea" rows="3" placeholder="Décrivez le contenu...">${esc(o.description??'')}</textarea>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Prix original (DT) <span style="color:var(--color-primary)">*</span></label>
        <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-money-bill"></i></span>
          <input type="number" name="prix_original" class="form-input" step="0.01" min="0.01" placeholder="12.00" value="${esc(o.prix_original??'')}" required>
        </div>
      </div>
      <div class="form-group">
        <label>Prix réduit (DT) <span style="color:var(--color-primary)">*</span></label>
        <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-percent"></i></span>
          <input type="number" name="prix" class="form-input" step="0.01" min="0.01" placeholder="4.50" value="${esc(o.prix??'')}" required>
        </div>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Quantité <span style="color:var(--color-primary)">*</span></label>
        <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-boxes-stacked"></i></span>
          <input type="text" name="quantite" class="form-input" placeholder="5" value="${esc(o.quantite??'')}">
        </div>
      </div>
      <div class="form-group" style="grid-column:1/-1">
        <label>Catégories <span style="color:var(--color-primary)">*</span>
          <small style="color:var(--color-text-muted);font-weight:400;">(une ou plusieurs)</small>
        </label>
        <div class="cat-checkbox-list" id="edit-cat-list">${catCheckboxes}</div>
        <div id="edit-cat-error" style="color:#f87171;font-size:.75rem;margin-top:4px;display:none;">Veuillez sélectionner au moins une catégorie.</div>
      </div>
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
          Cliquez ou glissez une image ici<br>
          <small style="opacity:.6;">JPG, PNG, WEBP ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂÃÂ¬ÃÂ¢Ã¢ÂÂ¬ÃÂ max 2 Mo</small>
        </div>
        <img id="edit-photo-preview" class="photo-preview" alt="Aperçu" ${existingPhoto?`src="${esc(existingPhoto)}" style="display:block;"`:''}
      </div>
      ${existingPhoto ? '<p style="font-size:.75rem;color:var(--color-text-muted);margin-top:4px;"><i class="fa-solid fa-image"></i> Photo existante ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂÃÂ¬ÃÂ¢Ã¢ÂÂ¬ÃÂ uploadez-en une nouvelle pour la remplacer</p>' : ''}
    </div>
    <div class="form-group">
      <label>Statut</label>
      <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-toggle-on"></i></span>
        <select name="statut" class="form-input">${statutOpts}</select>
      </div>
    </div>`;
}

/* ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ Validation des formulaires offre ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ */
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
  // Titre
  const titre = form.querySelector('[name="titre"]');
  if (titre) {
    const v = titre.value.trim();
    if (!v) { showFieldError(titre, 'Le titre est obligatoire.'); valid = false; }
    else if (v.length < 3) { showFieldError(titre, 'Minimum 3 caractères.'); valid = false; }    else clearFieldError(titre);
  }
  // Prix original
  const prixOrig = form.querySelector('[name="prix_original"]');
  if (prixOrig) {
    const v = parseFloat(prixOrig.value);
    if (!prixOrig.value || isNaN(v) || v <= 0) { showFieldError(prixOrig, 'Le prix original doit ÃÂÃÂÃÂÃÂªtre un nombre positif.'); valid = false; }
    else clearFieldError(prixOrig);
  }
  // Prix réduit
  const prix = form.querySelector('[name="prix"]');
  if (prix) {
    const v = parseFloat(prix.value);
    if (!prix.value || isNaN(v) || v <= 0) { showFieldError(prix, 'Le prix rÃÂÃÂÃÂÃÂ©duit doit ÃÂÃÂÃÂÃÂªtre un nombre positif.'); valid = false; }
    else if (prixOrig && parseFloat(prixOrig.value) > 0 && v >= parseFloat(prixOrig.value)) {
      showFieldError(prix, 'Le prix rÃÂÃÂÃÂÃÂ©duit doit ÃÂÃÂÃÂÃÂªtre inférieur au prix original.'); valid = false;
    }
    else clearFieldError(prix);
  }
  // Quantité
  const qte = form.querySelector('[name="quantite"]');
  if (qte) {
    const v = parseInt(qte.value);
    if (!qte.value || isNaN(v) || v < 1) { showFieldError(qte, 'La quantité doit ÃÂÃÂÃÂÃÂªtre au moins 1.'); valid = false; }
    else clearFieldError(qte);
  }
  // Au moins une catégorie obligatoire
  const catCbs = form.querySelectorAll('[name="id_categories[]"]');
  const catErrEl = form.querySelector('#partner-create-cat-error, #edit-cat-error');
  if (catCbs.length > 0) {
    const anyCatChecked = Array.from(catCbs).some(cb => cb.checked);
    if (!anyCatChecked) {
      if (catErrEl) catErrEl.style.display = 'block';
      valid = false;
    } else {
      if (catErrEl) catErrEl.style.display = 'none';
    }
  }
  // Heures ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂÃÂ¬ÃÂ¢Ã¢ÂÂ¬ÃÂ format HH:MM et cohÃÂ©rence
  const timeRe = /^([01]\d|2[0-3]):([0-5]\d)$/;
  const hd = form.querySelector('[name="heure_debut"]');
  const hf = form.querySelector('[name="heure_fin"]');
  if (hd && hd.value && !timeRe.test(hd.value.trim())) {
    showFieldError(hd, "Format invalide. Utilisez HH:MM (ex: 08:30)."); valid = false;
  } else if (hd && hd.value) clearFieldError(hd);
  if (hf && hf.value && !timeRe.test(hf.value.trim())) {
    showFieldError(hf, "Format invalide. Utilisez HH:MM (ex: 18:00)."); valid = false;
  } else if (hd && hf && hd.value && hf.value && timeRe.test(hd.value) && timeRe.test(hf.value) && hf.value.trim() <= hd.value.trim()) {
    showFieldError(hf, "L'heure de fin doit ÃÂÃÂÃÂÃÂªtre aprÃÂ¨s l'heure de dÃÂ©but."); valid = false;
  } else if (hf && hf.value) clearFieldError(hf);
  return valid;
}

// Validation titre en temps rÃÂ©el (affiche rouge sans bloquer)
function attachTitreRealtime(form) {
  const titre = form.querySelector('[name="titre"]');
  if (!titre) return;
  titre.addEventListener('input', () => {
    const v = titre.value.trim();
    if (v.length > 0 && v.length < 3) {
      showFieldError(titre, 'Minimum 3 caractères.');
    } else {
      clearFieldError(titre);
    }
  });
}

// Attacher la validation au formulaire de crÃÂÃÂÃÂÃÂ©ation
document.addEventListener('DOMContentLoaded', () => {
  onPartnerCategoryChange();
  syncCreateCategoryWithSelection();

  const createForm = document.querySelector('#modal-create form');
  if (createForm) {
    createForm.addEventListener('submit', e => {
      if (!validateOfferForm(createForm)) e.preventDefault();
    });
    attachTitreRealtime(createForm);
  }
  const editForm = document.getElementById('form-edit');
  if (editForm) {
    editForm.addEventListener('submit', e => {
      if (!validateOfferForm(editForm)) e.preventDefault();
    });
  }
});

/* ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ Ouvrir modal modifier ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ */
function openEditModal(id) {
  const o = allOffersData.find(x => x.id_offre == id);
  if (!o) { alert('Offre introuvable.'); return; }
  document.getElementById('edit-id_offre').value = id;
  document.getElementById('edit-body').innerHTML  = buildEditForm(o);
  // Attacher validation en temps rÃÂ©el sur les champs gÃÂ©nÃÂ©rÃÂ©s dynamiquement
  const editForm = document.getElementById('form-edit');
  editForm.querySelectorAll('input,textarea,select').forEach(el => {
    el.addEventListener('input', () => clearFieldError(el));
  });
  attachTitreRealtime(editForm);
  openModal('modal-edit');
}

/* ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ Ouvrir modal supprimer ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ */
function openDeleteModal(id, titre) {
  document.getElementById('delete-id_offre').value   = id;
  document.getElementById('delete-label').textContent = `ÃÂÃ¢ÂÂÃ« ${titre} ÃÂÃ¢ÂÂÃ» sera dÃÂ©finitivement supprimÃÂÃÂÃÂÃÂ©e.`;
  openModal('modal-delete');
}

/* ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ Upload photo (base64) ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ */
function handlePhotoUpload(input, hiddenId, previewId, placeholderId) {
  const file = input.files[0];
  if (!file) return;
  if (file.size > 2 * 1024 * 1024) { alert('Image trop lourde (max 2 Mo).'); return; }
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById(hiddenId).value   = e.target.result;
    const preview = document.getElementById(previewId);
    const ph      = document.getElementById(placeholderId);
    if (preview) { preview.src = e.target.result; preview.style.display = 'block'; }
    if (ph)      { ph.style.display = 'none'; }
  };
  reader.readAsDataURL(file);
}

/* ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ Injection de l'id du partenaire connectÃÂ© ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬
   L'auth est gÃÂ©rÃÂ©e cÃÂ´tÃÂ© JS (localStorage via app.js).
   On injecte l'ID dans le champ cachÃÂÃÂÃÂÃÂ© du formulaire crÃÂ©er,
   et on recharge la page avec ?partner_id= pour que PHP
   filtre les offres de ce seul partenaire.                 */
(function injectPartnerId() {
  try {
    const stored = localStorage.getItem('caremeal_current_user');
    if (!stored) return;
    const user = JSON.parse(stored);
    if (!user || !user.id) return;

    // Champ cachÃÂÃÂÃÂÃÂ© du formulaire CrÃÂ©er
    const el = document.getElementById('create-id_partenaire');
    if (el) el.value = user.id;

    // Si l'URL n'a pas dÃÂ©jÃÂ  le partner_id, on recharge avec
    const url = new URL(window.location.href);
    if (!url.searchParams.get('partner_id')) {
      url.searchParams.set('partner_id', user.id);
      window.location.replace(url.toString());
    }
  } catch (_) {}
})();

/* ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ Infos partenaire dans la sidebar ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ */
(function initSidebarUser() {
  try {
    const stored = localStorage.getItem('caremeal_current_user');
    if (!stored) return;
    const user = JSON.parse(stored);
    if (!user) return;
    const initials = (user.name || 'P').slice(0, 2).toUpperCase();
    const sAvatar = document.getElementById('sidebar-user-avatar');
    const sName   = document.getElementById('sidebar-user-name');
    const sRole   = document.getElementById('sidebar-user-role');
    const hAvatar = document.getElementById('header-avatar');
    if (sAvatar) sAvatar.textContent = initials;
    if (sName)   sName.textContent   = user.name  || 'Partenaire';
    if (sRole)   sRole.textContent   = user.companyName || 'Partenaire';
    if (hAvatar) hAvatar.textContent = initials;
  } catch (_) {}
})();

/* ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ Déconnexion ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ */
document.querySelectorAll('[data-action="logout"]').forEach(btn => {
  btn.addEventListener('click', () => {
    localStorage.removeItem('caremeal_current_user');
    window.location.href = '/caremeal/login.html';
  });
});

/* ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ Menu mobile ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ */
// ── Filtre catégories partenaire (checkboxes multi) ──
function getCheckedCatIds() {
  return Array.from(document.querySelectorAll('.cat-filter-check:checked'))
              .map(cb => String(cb.value));
}

function onCatAllToggle(allCb) {
  document.querySelectorAll('.cat-filter-check').forEach(cb => { cb.checked = allCb.checked; });
  applyPartnerCategoryFilter();
  syncCreateCategoryWithSelection();
}

function onPartnerCategoryChange() {
  const checks = document.querySelectorAll('.cat-filter-check');
  const allCb  = document.getElementById('cat-all-check');
  if (allCb) allCb.checked = Array.from(checks).every(cb => cb.checked);
  applyPartnerCategoryFilter();
  syncCreateCategoryWithSelection();
}

function applyPartnerCategoryFilter() {
  const selected = getCheckedCatIds();
  const allChecked = document.getElementById('cat-all-check')?.checked ?? true;
  document.querySelectorAll('.offer-card[data-cat-id]').forEach(card => {
    const cardCats = (card.getAttribute('data-cat-id') || '').split(',').map(s => s.trim()).filter(Boolean);
    const show = allChecked || selected.length === 0 || cardCats.some(c => selected.includes(c));
    card.style.display = show ? '' : 'none';
  });
}

function syncCreateCategoryWithSelection() {
  // Pré-cocher dans le formulaire de création les catégories sélectionnées dans le filtre
  const selected = getCheckedCatIds();
  const createList = document.getElementById('partner-create-cat-list');
  if (!createList) return;
  createList.querySelectorAll('input[type=checkbox]').forEach(cb => {
    cb.checked = selected.includes(String(cb.value));
  });
}

const mt = document.getElementById('menu-toggle');
const sb = document.getElementById('sidebar');
const ov = document.getElementById('sidebar-overlay');
if (mt) mt.addEventListener('click', () => sb.classList.toggle('open'));
if (ov) ov.addEventListener('click', () => sb.classList.remove('open'));

/* ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ÃÂÃÂ¢ÃÂ¢Ã¢ÂÂ¬ÃÂÃÂ¢Ã¢ÂÂÃÂ¬ Ouvrir modal si erreurs de validation (retour POST) */
<?php if (!empty($errors) && !empty($old)): ?>
  document.addEventListener('DOMContentLoaded', () => {
    <?php if (isset($old['id_offre'])): ?>
      openEditModal(<?= (int)$old['id_offre'] ?>);
    <?php else: ?>
      openModal('modal-create');
    <?php endif; ?>
  });
<?php endif; ?>
</script>
</body>
</html>