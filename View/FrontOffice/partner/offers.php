<?php
// Ce fichier est rendu par OfferController::index()
// Variables disponibles : $offers, $categories, $counts, $active, $expired, $flash
$flash  = $flash  ?? null;
$errors = $_SESSION['errors'] ?? [];
$old    = $_SESSION['old']    ?? [];
unset($_SESSION['errors'], $_SESSION['old']);

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
    /* Fix select/option dark theme */
    select.form-input option { background-color: var(--color-dark-card, #1e2433); color: var(--color-text, #e2e8f0); }
    select.form-input { background-color: var(--color-dark-input, #252d3d); color: var(--color-text, #e2e8f0); }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

    /* Delete confirm */
    .delete-confirm {
      background: var(--color-dark-card); border-radius: 16px;
      padding: 32px; text-align: center;
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

    /* ── Filter bar chips ── */
    .filter-bar {
      background: var(--color-dark-card);
      border: 1px solid rgba(255,255,255,.07);
      border-radius: 16px; padding: 16px 20px;
      margin-bottom: 24px;
      display: flex; align-items: center; gap: 18px; flex-wrap: wrap;
    }
    .filter-bar-label {
      font-size: .75rem; font-weight: 700; color: var(--color-text-muted);
      text-transform: uppercase; letter-spacing: .8px; white-space: nowrap;
      display: flex; align-items: center; gap: 7px;
    }
    .filter-bar-label i { color: var(--color-primary); }
    .filter-chips { display: flex; gap: 8px; flex-wrap: wrap; flex: 1; }
    .filter-chip {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 6px 14px; border-radius: 100px;
      font-size: .82rem; font-weight: 600; cursor: pointer;
      border: 1.5px solid rgba(255,255,255,.1);
      background: rgba(255,255,255,.04); color: var(--color-text-muted);
      transition: all .16s ease; user-select: none;
    }
    .filter-chip:hover { border-color: rgba(255,255,255,.22); color: var(--color-white); background: rgba(255,255,255,.08); }
    .filter-chip.active { background: var(--color-primary-light); border-color: var(--color-primary); color: var(--color-primary); }
    .filter-chip .chip-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .filter-hint { font-size: .73rem; color: var(--color-text-muted); margin-left: auto; white-space: nowrap; display: flex; align-items: center; gap: 5px; }

    /* Category radio/checkbox pill style */
    .cat-checkbox-list { display: flex; flex-wrap: wrap; gap: 10px; padding: 8px 0; }
    .cat-pill-label {
      display: flex; align-items: center; gap: 8px; cursor: pointer;
      background: #1e2433; border: 1px solid #2d3748;
      border-radius: 50px; padding: 8px 18px;
      font-size: .82rem; font-weight: 700; color: #e2e8f0;
      letter-spacing: .04em; user-select: none;
      transition: border-color .15s, background .15s;
    }
    .cat-pill-box {
      width: 14px; height: 14px; border: 2px solid #4a5568;
      border-radius: 50%; background: #252d3d; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      transition: background .15s, border-color .15s;
    }
    .cat-pill-label:has(input:checked) .cat-pill-box { background: var(--color-primary, #ef4444); border-color: var(--color-primary, #ef4444); }
    .cat-pill-label:has(input:checked) .cat-pill-box::after { content: ''; display: block; width: 5px; height: 5px; border-radius: 50%; background: #fff; }
    .cat-pill-label:has(input:checked) { border-color: var(--color-primary, #ef4444); background: rgba(239,68,68,.12); color: #fff; }
    .cat-pill-label:hover { border-color: #4a5568; background: #252d3d; }
    .cat-tags { display: flex; flex-wrap: wrap; gap: 4px; }
    .cat-tag { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: .7rem; font-weight: 600; background: rgba(239,68,68,.15); color: #f87171; }

    /* ══ INLINE FORM (pattern offers admin) ══ */
    #view-form { display: none; flex-direction: column; gap: 20px; }

    .form-page-header {
      display: flex; align-items: center; gap: 16px; margin-bottom: 4px;
    }
    .btn-back {
      display: inline-flex; align-items: center; gap: 8px;
      background: rgba(255,255,255,.07);
      border: 1px solid var(--color-dark-border);
      color: var(--color-white); border-radius: 10px; padding: 9px 16px;
      font-size: .875rem; font-weight: 600; cursor: pointer;
      transition: background .2s, border-color .2s; white-space: nowrap;
    }
    .btn-back:hover { background: rgba(255,255,255,.12); border-color: rgba(255,255,255,.2); }

    .form-card {
      background: var(--color-dark-card);
      border: 1px solid var(--color-dark-border);
      border-radius: 16px; padding: 24px;
    }
    .form-card .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
    .form-card .form-group:last-child { margin-bottom: 0; }
    .form-card label { font-size: .78rem; font-weight: 600; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: .06em; }

    .form-actions {
      display: flex; justify-content: flex-end; gap: 10px;
      border-top: 1px solid var(--color-dark-border);
      margin-top: 20px; padding-top: 20px;
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
        <a href="offers.php"                       class="sidebar-link active"><span class="link-icon"><i class="fa-solid fa-box"></i></span> Mes Offres</a>
        <a href="/caremeal/partner/events.html"    class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> Evenements</a>
        <a href="/caremeal/partner/stats.html"     class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-chart-column"></i></span> Statistiques</a>
        <a href="/caremeal/partner/settings.html"  class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-gear"></i></span> Paramètres</a>
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
        <div class="page-title">
          <h2 id="page-heading">Mes Offres</h2>
          <p id="page-sub">Gérez vos offres anti-gaspillage</p>
        </div>
      </div>
      <div class="header-right">
        <button class="btn btn-primary" id="btn-add-offer" onclick="showCreateForm()"
                style="display:flex;align-items:center;gap:8px;">
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

      <!-- ══════════════════════════════════════
           VUE 1 : LISTE DES OFFRES
      ══════════════════════════════════════ -->
      <div id="view-list">

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

        <!-- Filter bar -->
        <div class="filter-bar animate-fade-in-up">
          <span class="filter-bar-label"><i class="fa-solid fa-sliders"></i> Filtrer par catégorie</span>
          <div class="filter-chips" id="partner-filter-cat-list">
            <button type="button" class="filter-chip active" id="cat-chip-all" onclick="onCatChipAllToggle(this)">
              <span class="chip-dot"></span> Toutes
              <input type="checkbox" id="cat-all-check" checked style="display:none" onchange="onCatAllToggle(this)">
            </button>
            <?php foreach (($categories ?? []) as $cat): ?>
              <button type="button" class="filter-chip active" data-cat-id="<?= (int)$cat['id_categorie'] ?>" onclick="onCatChipToggle(this)">
                <span class="chip-dot"></span>
                <?= e($cat['nom_categorie']) ?>
                <input type="checkbox" class="cat-filter-check" value="<?= (int)$cat['id_categorie'] ?>" checked style="display:none">
              </button>
            <?php endforeach; ?>
          </div>
          <span class="filter-hint">
            <i class="fa-solid fa-circle-info" style="opacity:.5;"></i>
            La sélection se reporte dans "Nouvelle offre"
          </span>
        </div>

        <!-- Offres actives -->
        <div class="section animate-fade-in-up stagger-1">
          <div class="section-header"><h3><i class="fa-solid fa-check"></i> Offres actives</h3></div>
          <div class="grid grid-3 gap-6">
            <?php if (empty($active)): ?>
              <div class="empty-state"><i class="fa-solid fa-box-open"></i><p>Aucune offre active. Publiez votre première offre !</p></div>
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
                  <?php
                    $displayCats = !empty($o['cat_noms']) ? $o['cat_noms'] : ($o['nom_categorie'] ?? '');
                    if (!empty($displayCats)):
                      $catList = array_values(array_filter(array_map('trim', explode(',', $displayCats))));
                      $isMulti = count($catList) > 1;
                  ?>
                    <p style="font-size:.75rem;margin:0 0 6px;font-weight:600;">
                      <?php foreach ($catList as $ci => $cn): ?>
                        <?php if ($ci > 0): ?><span style="color:var(--color-text-muted);margin:0 2px;">·</span><?php endif; ?>
                        <span style="color:<?= $isMulti ? '#f97316' : 'var(--color-primary)' ?>;"><?= e($cn) ?></span>
                      <?php endforeach; ?>
                    </p>
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
                    <button class="btn btn-secondary btn-sm" style="flex:1;" onclick="showEditForm(<?= $o['id_offre'] ?>)">
                      <i class="fa-solid fa-pen"></i> Modifier
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="showDeleteForm(<?= $o['id_offre'] ?>, '<?= e($o['titre']) ?>')">
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
                  <?php
                    $displayCats = !empty($o['cat_noms']) ? $o['cat_noms'] : ($o['nom_categorie'] ?? '');
                    if (!empty($displayCats)):
                      $catList = array_values(array_filter(array_map('trim', explode(',', $displayCats))));
                      $isMulti = count($catList) > 1;
                  ?>
                    <p style="font-size:.75rem;margin:0 0 6px;font-weight:600;">
                      <?php foreach ($catList as $ci => $cn): ?>
                        <?php if ($ci > 0): ?><span style="color:var(--color-text-muted);margin:0 2px;">·</span><?php endif; ?>
                        <span style="color:<?= $isMulti ? '#f97316' : 'var(--color-primary)' ?>;"><?= e($cn) ?></span>
                      <?php endforeach; ?>
                    </p>
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
                    <button class="btn btn-secondary btn-sm" style="flex:1;" onclick="showEditForm(<?= $o['id_offre'] ?>)">
                      <i class="fa-solid fa-pen"></i> Modifier
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="showDeleteForm(<?= $o['id_offre'] ?>, '<?= e($o['titre']) ?>')">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </div>
                </div>
              </div>
            <?php endforeach; endif; ?>
          </div>
        </div>

      </div><!-- /view-list -->

      <!-- ══════════════════════════════════════
           VUE 2 : FORMULAIRE INLINE
      ══════════════════════════════════════ -->
      <div id="view-form">

        <!-- En-tête -->
        <div class="form-page-header">
          <button class="btn-back" onclick="showList()">
            <i class="fa-solid fa-arrow-left"></i> Retour
          </button>
          <div>
            <h2 id="form-heading">Nouvelle offre</h2>
            <p id="form-sub">Remplissez les informations ci-dessous</p>
          </div>
        </div>

        <!-- ── CRÉER ── -->
        <div id="form-create-wrap" style="display:none;">
          <div class="form-card">
            <form method="POST" action="offers.php" id="form-create">
              <input type="hidden" name="action" value="create">
              <input type="hidden" name="id_partenaire" id="create-id_partenaire" value="">

              <?php include __DIR__ . '/offerform.php'; ?>

              <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="showList()">Annuler</button>
                <button type="submit" class="btn btn-primary">
                  <i class="fa-solid fa-plus"></i> Créer l'offre
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- ── MODIFIER ── -->
        <div id="form-edit-wrap" style="display:none;">
          <div class="form-card">
            <form method="POST" action="offers.php" id="form-edit">
              <input type="hidden" name="action" value="update">
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

        <!-- ── SUPPRIMER ── -->
        <div id="form-delete-wrap" style="display:none;">
          <div class="delete-confirm">
            <i class="fa-solid fa-trash" style="font-size:2.5rem;color:#f87171;margin-bottom:12px;display:block;"></i>
            <h3 style="color:var(--color-white);margin:0;">Supprimer l'offre ?</h3>
            <p id="delete-label">Cette action est irréversible.</p>
            <form method="POST" action="offers.php" style="display:flex;gap:12px;justify-content:center;">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id_offre" id="delete-id_offre">
              <button type="button" class="btn btn-secondary" onclick="showList()">Annuler</button>
              <button type="submit" class="btn btn-danger">
                <i class="fa-solid fa-trash"></i> Supprimer
              </button>
            </form>
          </div>
        </div>

      </div><!-- /view-form -->

    </div>
  </main>
</div>

<!-- Données PHP injectées pour le JS -->
<script>
const allOffersData    = <?= json_encode(array_values($offers     ?? []), JSON_UNESCAPED_UNICODE) ?>;
const allCategoriesData = <?= json_encode(array_values($categories ?? []), JSON_UNESCAPED_UNICODE) ?>;
</script>

<script src="/caremeal/js/app.js"></script>
<script src="/caremeal/js/components.js"></script>
<script>
/* ══════════════════════════════════════════
   NAVIGATION ENTRE VUES (sans modal)
══════════════════════════════════════════ */
function showList() {
  document.getElementById('view-list').style.display  = 'block';
  document.getElementById('view-form').style.display  = 'none';
  document.getElementById('btn-add-offer').style.display = 'flex';
  document.getElementById('page-heading').textContent = 'Mes Offres';
  document.getElementById('page-sub').textContent     = 'Gérez vos offres anti-gaspillage';
}

function showFormView(heading, sub) {
  document.getElementById('view-list').style.display  = 'none';
  document.getElementById('view-form').style.display  = 'flex';
  document.getElementById('btn-add-offer').style.display = 'none';
  document.getElementById('form-heading').textContent = heading;
  document.getElementById('form-sub').textContent     = sub;
  document.getElementById('form-create-wrap').style.display = 'none';
  document.getElementById('form-edit-wrap').style.display   = 'none';
  document.getElementById('form-delete-wrap').style.display = 'none';
}

function showCreateForm() {
  showFormView('Nouvelle offre', 'Remplissez les informations ci-dessous');
  document.getElementById('form-create-wrap').style.display = 'block';
  syncCreateCategoryWithSelection();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function showEditForm(id) {
  const o = allOffersData.find(x => x.id_offre == id);
  if (!o) { alert('Offre introuvable.'); return; }
  showFormView('Modifier l\'offre', o.titre || '');
  document.getElementById('edit-id_offre').value = id;
  document.getElementById('edit-body').innerHTML  = buildEditForm(o);
  // Attacher validation temps réel
  const editForm = document.getElementById('form-edit');
  editForm.querySelectorAll('input,textarea,select').forEach(el => {
    el.addEventListener('input', () => clearFieldError(el));
  });
  attachTitreRealtime(editForm);
  document.getElementById('form-edit-wrap').style.display = 'block';
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function showDeleteForm(id, titre) {
  showFormView('Supprimer l\'offre', '');
  document.getElementById('delete-id_offre').value   = id;
  document.getElementById('delete-label').textContent = '« ' + titre + ' » sera définitivement supprimée.';
  document.getElementById('form-delete-wrap').style.display = 'block';
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ══════════════════════════════════════════
   Génération du formulaire modifier en JS
══════════════════════════════════════════ */
function esc(s) { return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function buildEditForm(o) {
  const statuts = ['publiée','brouillon','expirée','archivée'];
  const offerCatId = o.id_categorie ? Number(o.id_categorie) : (o.cat_ids ? Number(o.cat_ids.split(',')[0]) : 0);
  const catCheckboxes = (allCategoriesData || []).map(c =>
    `<div class="cat-checkbox-item">
      <label class="cat-pill-label">
        <span class="cat-pill-box"></span>
        <input type="radio" name="id_categorie" value="${c.id_categorie}" ${offerCatId === Number(c.id_categorie) ? 'checked' : ''} style="display:none">
        <span>${esc(c.nom_categorie).toUpperCase()}</span>
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
        <label>Catégorie <span style="color:var(--color-primary)">*</span></label>
        <div class="cat-checkbox-list" id="edit-cat-list">${catCheckboxes}</div>
        <div id="edit-cat-error" style="color:#f87171;font-size:.75rem;margin-top:4px;display:none;">Veuillez sélectionner une catégorie.</div>
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
          <small style="opacity:.6;">JPG, PNG, WEBP — max 2 Mo</small>
        </div>
        <img id="edit-photo-preview" class="photo-preview" alt="Aperçu" ${existingPhoto?`src="${esc(existingPhoto)}" style="display:block;"`:''}>
      </div>
      ${existingPhoto ? '<p style="font-size:.75rem;color:var(--color-text-muted);margin-top:4px;"><i class="fa-solid fa-image"></i> Photo existante — uploadez-en une nouvelle pour la remplacer</p>' : ''}
    </div>
    <div class="form-group">
      <label>Statut</label>
      <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-toggle-on"></i></span>
        <select name="statut" class="form-input">${statutOpts}</select>
      </div>
    </div>`;
}

/* ══════════════════════════════════════════
   Validation formulaires offre
══════════════════════════════════════════ */
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
    } else clearFieldError(prix);
  }
  const qte = form.querySelector('[name="quantite"]');
  if (qte) {
    const v = parseInt(qte.value);
    if (!qte.value || isNaN(v) || v < 1) { showFieldError(qte, 'La quantité doit être au moins 1.'); valid = false; }
    else clearFieldError(qte);
  }
  const catCbs = form.querySelectorAll('[name="id_categorie"]');
  const catErrEl = form.querySelector('#partner-create-cat-error, #edit-cat-error');
  if (catCbs.length > 0) {
    const anyCatChecked = Array.from(catCbs).some(cb => cb.checked);
    if (!anyCatChecked) { if (catErrEl) catErrEl.style.display = 'block'; valid = false; }
    else { if (catErrEl) catErrEl.style.display = 'none'; }
  }
  const timeRe = /^([01]\d|2[0-3]):([0-5]\d)$/;
  const hd = form.querySelector('[name="heure_debut"]');
  const hf = form.querySelector('[name="heure_fin"]');
  if (hd && hd.value && !timeRe.test(hd.value.trim())) { showFieldError(hd, 'Format invalide. Utilisez HH:MM (ex: 08:30).'); valid = false; }
  else if (hd && hd.value) clearFieldError(hd);
  if (hf && hf.value && !timeRe.test(hf.value.trim())) { showFieldError(hf, 'Format invalide. Utilisez HH:MM (ex: 18:00).'); valid = false; }
  else if (hd && hf && hd.value && hf.value && timeRe.test(hd.value) && timeRe.test(hf.value) && hf.value.trim() <= hd.value.trim()) {
    showFieldError(hf, "L'heure de fin doit être après l'heure de début."); valid = false;
  } else if (hf && hf.value) clearFieldError(hf);
  return valid;
}

function attachTitreRealtime(form) {
  const titre = form.querySelector('[name="titre"]');
  if (!titre) return;
  titre.addEventListener('input', () => {
    const v = titre.value.trim();
    if (v.length > 0 && v.length < 3) showFieldError(titre, 'Minimum 3 caractères.');
    else clearFieldError(titre);
  });
}

document.addEventListener('DOMContentLoaded', () => {
  onPartnerCategoryChange();
  syncCreateCategoryWithSelection();

  const createForm = document.getElementById('form-create');
  if (createForm) {
    createForm.addEventListener('submit', e => { if (!validateOfferForm(createForm)) e.preventDefault(); });
    attachTitreRealtime(createForm);
  }
  const editForm = document.getElementById('form-edit');
  if (editForm) {
    editForm.addEventListener('submit', e => { if (!validateOfferForm(editForm)) e.preventDefault(); });
  }

  // Ouvrir directement le formulaire si retour POST avec erreurs
  <?php if (!empty($errors) && !empty($old)): ?>
    <?php if (isset($old['id_offre'])): ?>
      showEditForm(<?= (int)$old['id_offre'] ?>);
    <?php else: ?>
      showCreateForm();
    <?php endif; ?>
  <?php endif; ?>
});

/* ══ Upload photo (base64) ══ */
function handlePhotoUpload(input, hiddenId, previewId, placeholderId) {
  const file = input.files[0];
  if (!file) return;
  if (file.size > 2 * 1024 * 1024) { alert('Image trop lourde (max 2 Mo).'); return; }
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById(hiddenId).value = e.target.result;
    const preview = document.getElementById(previewId);
    const ph      = document.getElementById(placeholderId);
    if (preview) { preview.src = e.target.result; preview.style.display = 'block'; }
    if (ph)      { ph.style.display = 'none'; }
  };
  reader.readAsDataURL(file);
}

/* ══ Injection id partenaire depuis localStorage ══ */
(function injectPartnerId() {
  try {
    const stored = localStorage.getItem('caremeal_current_user');
    if (!stored) return;
    const user = JSON.parse(stored);
    if (!user || !user.id) return;
    const el = document.getElementById('create-id_partenaire');
    if (el) el.value = user.id;
    const url = new URL(window.location.href);
    if (!url.searchParams.get('partner_id')) {
      url.searchParams.set('partner_id', user.id);
      window.location.replace(url.toString());
    }
  } catch (_) {}
})();

/* ══ Infos partenaire sidebar ══ */
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
    if (sAvatar) sAvatar.textContent = initials;
    if (sName)   sName.textContent   = user.name || 'Partenaire';
    if (sRole)   sRole.textContent   = user.companyName || 'Partenaire';
  } catch (_) {}
})();

/* ══ Déconnexion ══ */
document.querySelectorAll('[data-action="logout"]').forEach(btn => {
  btn.addEventListener('click', () => {
    localStorage.removeItem('caremeal_current_user');
    window.location.href = '/caremeal/login.html';
  });
});

/* ══ Menu mobile ══ */
const mt = document.getElementById('menu-toggle');
const sb = document.getElementById('sidebar');
const ov = document.getElementById('sidebar-overlay');
if (mt) mt.addEventListener('click', () => sb.classList.toggle('open'));
if (ov) ov.addEventListener('click', () => sb.classList.remove('open'));

/* ══ Filter chips catégories ══ */
function onCatChipAllToggle(chip) {
  const allActive = chip.classList.contains('active');
  document.querySelectorAll('.filter-chip').forEach(c => { if (allActive) c.classList.remove('active'); else c.classList.add('active'); });
  document.querySelectorAll('.cat-filter-check').forEach(cb => { cb.checked = !allActive; });
  const allCb = document.getElementById('cat-all-check');
  if (allCb) allCb.checked = !allActive;
  applyPartnerCategoryFilter();
  syncCreateCategoryWithSelection();
}
function onCatChipToggle(chip) {
  chip.classList.toggle('active');
  const cb = chip.querySelector('.cat-filter-check');
  if (cb) cb.checked = chip.classList.contains('active');
  const allChip = document.getElementById('cat-chip-all');
  const allCb   = document.getElementById('cat-all-check');
  const allChecked = Array.from(document.querySelectorAll('.cat-filter-check')).every(c => c.checked);
  if (allChip) allChip.classList.toggle('active', allChecked);
  if (allCb)   allCb.checked = allChecked;
  applyPartnerCategoryFilter();
  syncCreateCategoryWithSelection();
}
function getCheckedCatIds() {
  return Array.from(document.querySelectorAll('.cat-filter-check:checked')).map(cb => String(cb.value));
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
  const selected = getCheckedCatIds();
  const createList = document.getElementById('partner-create-cat-list');
  if (!createList) return;
  createList.querySelectorAll('input[type=radio]').forEach(rb => {
    rb.checked = selected.length > 0 && rb.value === selected[0];
  });
}
</script>
</body>
</html>