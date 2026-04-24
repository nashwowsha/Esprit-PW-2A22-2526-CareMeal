<?php
function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$selectedCategoryId = (int)($selectedCategoryId ?? 0);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Categorie Offres - Admin CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/caremeal/css/main.css">
  <link rel="stylesheet" href="/caremeal/css/components.css">
  <link rel="stylesheet" href="/caremeal/css/dashboard.css">
  <style>
    /* ── Alerts ── */
    .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 14px; }
    .alert-success { background: rgba(34,197,94,.14); border: 1px solid rgba(34,197,94,.3); color: #4ade80; }
    .alert-error   { background: rgba(239,68,68,.14);  border: 1px solid rgba(239,68,68,.3);  color: #f87171; }

    /* ── Category Cards Grid ── */
    .grid-cats { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 14px; }
    .cat-card {
      border: 1px solid var(--color-dark-border);
      border-radius: 16px;
      background: var(--color-dark-card);
      transition: transform .2s, box-shadow .2s, border-color .2s;
    }
    .cat-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 28px rgba(0,0,0,.35);
      border-color: rgba(var(--color-primary-rgb, 254,85,22), .35);
    }
    .cat-card-header { display: flex; justify-content: space-between; gap: 8px; padding: 16px; }
    .cat-icon-wrap {
      width: 40px; height: 40px; border-radius: 10px;
      background: rgba(254,85,22,.15);
      display: flex; align-items: center; justify-content: center;
      color: var(--color-primary); font-size: 1.1rem; flex-shrink: 0;
    }
    .cat-title { font-size: .95rem; font-weight: 700; color: var(--color-white); }
    .cat-meta  { font-size: .78rem; color: var(--color-text-muted); margin-top: 3px; line-height: 1.4; }
    .cat-actions { display: flex; gap: 6px; align-items: flex-start; }
    .cat-footer {
      border-top: 1px solid var(--color-dark-border);
      padding: 10px 16px;
      display: flex; justify-content: space-between; align-items: center;
    }
    .count-badge {
      font-size: .72rem; font-weight: 600;
      background: rgba(254,85,22,.16); color: var(--color-primary);
      padding: 4px 10px; border-radius: 20px;
    }

    /* ── Modals ── */
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.75); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(3px); }
    .modal-overlay.active { display: flex; animation: fadeInBg .2s ease; }
    @keyframes fadeInBg { from { opacity: 0; } to { opacity: 1; } }
    .modal-box {
      width: 100%; max-width: 560px;
      background: var(--color-dark-card);
      border: 1px solid rgba(255,255,255,.1);
      border-radius: 18px; overflow: hidden;
      animation: slideUp .25s ease;
    }
    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    .modal-head { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--color-dark-border); padding: 18px 20px; }
    .modal-body { padding: 20px; display: flex; flex-direction: column; gap: 14px; }
    .modal-foot { border-top: 1px solid var(--color-dark-border); padding: 14px 20px; display: flex; justify-content: flex-end; gap: 8px; }

    /* ── Form fields ── */
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group label { font-size: .8rem; font-weight: 600; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: .05em; }
    .modal-body .form-input,
    .modal-body .form-textarea {
      width: 100%; box-sizing: border-box;
      border: 1.5px solid var(--color-dark-border);
      border-radius: 10px;
      background: rgba(255,255,255,.05);
      color: var(--color-white);
      font-size: .92rem;
      padding: 11px 14px;
      outline: none;
      transition: border-color .2s, box-shadow .2s, background .2s;
    }
    .modal-body .form-input:focus,
    .modal-body .form-textarea:focus {
      border-color: var(--color-primary);
      box-shadow: 0 0 0 3px rgba(254,85,22,.18);
      background: rgba(255,255,255,.08);
    }
    .modal-body .form-input.input-error {
      border-color: #f87171;
      box-shadow: 0 0 0 3px rgba(248,113,113,.2);
    }
    .modal-body .form-input::placeholder,
    .modal-body .form-textarea::placeholder { color: rgba(255,255,255,.35); }
    .field-error { font-size: .78rem; color: #f87171; display: none; }
    .field-error.visible { display: block; }

    /* ══════════════════════════════════════════════
       BEAUTIFUL OFFERS TABLE
    ══════════════════════════════════════════════ */
    .table-wrap {
      overflow-x: auto;
      border-radius: 16px;
      border: 1px solid var(--color-dark-border);
      background: var(--color-dark-card);
    }

    .offers-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      font-size: .875rem;
    }

    /* Header */
    .offers-table thead tr {
      background: linear-gradient(90deg, rgba(254,85,22,.08) 0%, rgba(254,85,22,.04) 100%);
    }
    .offers-table th {
      padding: 14px 18px;
      text-align: left;
      font-size: .68rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .1em;
      color: var(--color-primary);
      border-bottom: 1px solid rgba(254,85,22,.2);
      white-space: nowrap;
    }
    .offers-table th:first-child { border-radius: 0; padding-left: 20px; }
    .offers-table th:last-child  { padding-right: 20px; }

    /* Body rows */
    .offers-table tbody tr {
      transition: background .15s;
      cursor: default;
    }
    .offers-table tbody tr:hover td {
      background: rgba(255,255,255,.04);
    }
    .offers-table td {
      padding: 13px 18px;
      border-bottom: 1px solid rgba(255,255,255,.05);
      color: var(--color-white);
      vertical-align: middle;
    }
    .offers-table tbody tr:last-child td { border-bottom: none; }
    .offers-table td:first-child { padding-left: 20px; font-weight: 600; }
    .offers-table td:last-child  { padding-right: 20px; }

    /* Price cell */
    .price-cell { font-weight: 700; color: #4ade80; }
    .price-original { font-size: .8rem; color: var(--color-text-muted); text-decoration: line-through; }

    /* Quantity cell */
    .qty-badge {
      display: inline-flex; align-items: center; justify-content: center;
      min-width: 32px; height: 26px; border-radius: 8px;
      background: rgba(255,255,255,.08);
      font-weight: 600; font-size: .8rem;
      color: var(--color-white); padding: 0 8px;
    }

    /* Status badge */
    .status-badge {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 4px 12px; border-radius: 20px;
      font-size: .75rem; font-weight: 700; text-transform: capitalize;
    }
    .status-badge::before {
      content: '';
      display: inline-block;
      width: 6px; height: 6px;
      border-radius: 50%;
    }
    .status-badge.publiee {
      background: rgba(74,222,128,.14);
      color: #4ade80;
      border: 1px solid rgba(74,222,128,.25);
    }
    .status-badge.publiee::before  { background: #4ade80; box-shadow: 0 0 6px #4ade80; }
    .status-badge.brouillon {
      background: rgba(251,191,36,.14);
      color: #fbbf24;
      border: 1px solid rgba(251,191,36,.25);
    }
    .status-badge.brouillon::before { background: #fbbf24; }
    .status-badge.archivee,
    .status-badge.expiree {
      background: rgba(248,113,113,.1);
      color: #f87171;
      border: 1px solid rgba(248,113,113,.2);
    }
    .status-badge.archivee::before,
    .status-badge.expiree::before  { background: #f87171; }

    /* Discount chip */
    .discount-chip {
      display: inline-block;
      background: rgba(254,85,22,.18);
      color: var(--color-primary);
      font-size: .7rem; font-weight: 700;
      padding: 2px 7px; border-radius: 6px;
      margin-left: 6px; vertical-align: middle;
    }

    /* Empty state */
    .table-empty {
      padding: 48px 20px; text-align: center;
      color: var(--color-text-muted);
    }
    .table-empty i { font-size: 2rem; margin-bottom: 10px; opacity: .4; display: block; }

    /* ── Section card ── */
    .section-card {
      background: var(--color-dark-card);
      border: 1px solid var(--color-dark-border);
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 20px;
    }
    .section-card h3 {
      margin: 0 0 16px;
      font-size: 1rem; font-weight: 700;
      display: flex; align-items: center; gap: 8px;
    }
    .section-card h3 i { color: var(--color-primary); }
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
        <div class="sidebar-section-title">Administration</div>
        <a href="/caremeal/admin/dashboard.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-chart-column"></i></span> Vue globale</a>
        <a href="/caremeal/admin/users.html"      class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-users"></i></span> Utilisateurs</a>
        <a href="/caremeal/admin/partners.html"   class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-store"></i></span> Partenaires</a>
        <a href="/caremeal/admin/categorie.php"   class="sidebar-link active"><span class="link-icon"><i class="fa-solid fa-tags"></i></span> Categorie offres</a>
        <a href="/caremeal/admin/offers.php"      class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-box"></i></span> Offres</a>
        <a href="/caremeal/admin/events.html"     class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> Evenements</a>
        <a href="/caremeal/admin/logs.html"       class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-clipboard-list"></i></span> Logs d'activites</a>
      </div>
    </nav>
    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="avatar" id="sidebar-user-avatar" style="background:linear-gradient(135deg,#EF4444,#F87171);">A</div>
        <div class="sidebar-user-info">
          <div class="sidebar-user-name" id="sidebar-user-name">Admin</div>
          <div class="sidebar-user-role" id="sidebar-user-role">Administrateur</div>
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
          <h2>Catégorie Offres</h2>
          <p>CRUD des catégories et affichage des offres liées</p>
        </div>
      </div>
      <div class="header-right">
        <button class="btn btn-primary" onclick="openModal('modal-create')">
          <i class="fa-solid fa-plus"></i> Ajouter catégorie
        </button>
      </div>
    </header>

    <div class="page-content">
      <?php if (!empty($flash)): ?>
        <div class="alert <?= ($flash['type'] ?? '') === 'success' ? 'alert-success' : 'alert-error' ?>"><?= e($flash['msg'] ?? '') ?></div>
      <?php endif; ?>
      <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
          <?php foreach ($errors as $error): ?>
            <div><?= e($error) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- ── Categories grid ── -->
      <div class="section-card">
        <h3><i class="fa-solid fa-tags"></i> Catégories</h3>
        <?php if (empty($categories)): ?>
          <p style="margin:0;color:var(--color-text-muted);">Aucune catégorie disponible.</p>
        <?php else: ?>
          <div class="grid-cats">
            <?php foreach ($categories as $category): ?>
              <?php $catId = (int)$category['id_categorie']; ?>
              <div class="cat-card">
                <div class="cat-card-header">
                  <div style="display:flex;gap:12px;align-items:flex-start;">
                    <div class="cat-icon-wrap">
                      <i class="fa-solid <?= e($category['icone'] ?: 'fa-tag') ?>"></i>
                    </div>
                    <div>
                      <div class="cat-title"><?= e($category['nom_categorie']) ?></div>
                      <div class="cat-meta"><?= e($category['description'] ?: 'Pas de description') ?></div>
                    </div>
                  </div>
                  <div class="cat-actions">
                    <button class="btn btn-secondary btn-sm"
                            onclick="openEditModal('<?= $catId ?>', '<?= e($category['nom_categorie']) ?>', '<?= e($category['description']) ?>', '<?= e($category['icone']) ?>')">
                      <i class="fa-solid fa-pen"></i>
                    </button>
                    <button class="btn btn-danger btn-sm"
                            onclick="openDeleteModal('<?= $catId ?>', '<?= e($category['nom_categorie']) ?>')">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </div>
                </div>
                <div class="cat-footer">
                  <span class="count-badge"><i class="fa-solid fa-box" style="margin-right:4px;font-size:.65rem;"></i><?= (int)$category['total_offres'] ?> offre(s)</span>
                  <a class="btn btn-primary btn-sm" href="?id_categorie=<?= $catId ?>">Voir offres</a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- ── Offers table ── -->
      <div class="section-card">
        <h3><i class="fa-solid fa-box-open"></i> Offres de la catégorie sélectionnée</h3>
        <?php if ($selectedCategoryId <= 0): ?>
          <div class="table-empty">
            <i class="fa-solid fa-hand-pointer"></i>
            Choisissez une catégorie pour afficher ses offres.
          </div>
        <?php elseif (empty($offers)): ?>
          <div class="table-empty">
            <i class="fa-solid fa-box-open"></i>
            Aucune offre liée à cette catégorie.
          </div>
        <?php else: ?>
          <div class="table-wrap">
            <table class="offers-table">
              <thead>
                <tr>
                  <th><i class="fa-solid fa-utensils" style="margin-right:6px;"></i>Titre</th>
                  <th><i class="fa-solid fa-tag" style="margin-right:6px;"></i>Prix</th>
                  <th><i class="fa-solid fa-receipt" style="margin-right:6px;"></i>Prix original</th>
                  <th><i class="fa-solid fa-cubes" style="margin-right:6px;"></i>Stock</th>
                  <th><i class="fa-solid fa-circle-dot" style="margin-right:6px;"></i>Statut</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($offers as $offer): ?>
                <?php
                  $prix     = (float)$offer['prix'];
                  $original = (float)$offer['prix_original'];
                  $discount = ($original > 0 && $original > $prix)
                    ? round((1 - $prix / $original) * 100) : 0;
                  $statut   = strtolower(trim($offer['statut'] ?? ''));
                ?>
                <tr>
                  <td><?= e($offer['titre']) ?></td>
                  <td>
                    <span class="price-cell"><?= number_format($prix, 2) ?> DT</span>
                    <?php if ($discount > 0): ?>
                      <span class="discount-chip">-<?= $discount ?>%</span>
                    <?php endif; ?>
                  </td>
                  <td><span class="price-original"><?= number_format($original, 2) ?> DT</span></td>
                  <td><span class="qty-badge"><?= (int)$offer['quantite'] ?></span></td>
                  <td>
                    <span class="status-badge <?= e($statut) ?>"><?= e($offer['statut']) ?></span>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div><!-- /page-content -->
  </main>
</div>

<!-- ══ Modal: Créer catégorie ══ -->
<div class="modal-overlay" id="modal-create">
  <div class="modal-box">
    <div class="modal-head">
      <h3 style="margin:0;display:flex;align-items:center;gap:8px;"><i class="fa-solid fa-plus" style="color:var(--color-primary);"></i> Ajouter catégorie</h3>
      <button class="btn btn-secondary btn-sm" onclick="closeModal('modal-create')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <!-- NOTE: no HTML5 validation (novalidate), JS handles it -->
    <form method="POST" action="/caremeal/admin/categorie.php" id="form-create" novalidate>
      <input type="hidden" name="action" value="create_category">
      <div class="modal-body">
        <div class="form-group">
          <label>Nom catégorie <span style="color:#f87171;">*</span></label>
          <input type="text" name="nom_categorie" id="create-nom" class="form-input"
                 placeholder="Ex : Sandwichs, Salades…"
                 value="<?= e($old['nom_categorie'] ?? '') ?>">
          <span class="field-error" id="err-create-nom"></span>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" class="form-textarea" rows="3"
                    placeholder="Courte description de la catégorie…"><?= e($old['description'] ?? '') ?></textarea>
          <span class="field-error" id="err-create-desc"></span>
        </div>
        <div class="form-group">
          <label>Icône Font Awesome <span style="color:var(--color-text-muted);font-weight:400;">(ex&nbsp;: fa-burger)</span></label>
          <input type="text" name="icone" id="create-icone" class="form-input"
                 placeholder="fa-tag"
                 value="<?= e($old['icone'] ?? '') ?>">
          <span class="field-error" id="err-create-icone"></span>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-create')">Annuler</button>
        <button type="button" class="btn btn-primary" onclick="validateAndSubmit('form-create')">
          <i class="fa-solid fa-floppy-disk"></i> Enregistrer
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ══ Modal: Modifier catégorie ══ -->
<div class="modal-overlay" id="modal-edit">
  <div class="modal-box">
    <div class="modal-head">
      <h3 style="margin:0;display:flex;align-items:center;gap:8px;"><i class="fa-solid fa-pen" style="color:var(--color-primary);"></i> Modifier catégorie</h3>
      <button class="btn btn-secondary btn-sm" onclick="closeModal('modal-edit')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST" action="/caremeal/admin/categorie.php" id="form-edit" novalidate>
      <input type="hidden" name="action" value="update_category">
      <input type="hidden" name="id_categorie" id="edit-id">
      <div class="modal-body">
        <div class="form-group">
          <label>Nom catégorie <span style="color:#f87171;">*</span></label>
          <input type="text" name="nom_categorie" id="edit-name" class="form-input" placeholder="Nom de la catégorie">
          <span class="field-error" id="err-edit-nom"></span>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" id="edit-description" class="form-textarea" rows="3" placeholder="Description…"></textarea>
          <span class="field-error" id="err-edit-desc"></span>
        </div>
        <div class="form-group">
          <label>Icône Font Awesome</label>
          <input type="text" name="icone" id="edit-icon" class="form-input" placeholder="fa-tag">
          <span class="field-error" id="err-edit-icone"></span>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-edit')">Annuler</button>
        <button type="button" class="btn btn-primary" onclick="validateAndSubmit('form-edit')">
          <i class="fa-solid fa-floppy-disk"></i> Mettre à jour
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ══ Modal: Supprimer ══ -->
<div class="modal-overlay" id="modal-delete">
  <div class="modal-box" style="max-width:430px;">
    <div class="modal-head">
      <h3 style="margin:0;display:flex;align-items:center;gap:8px;"><i class="fa-solid fa-trash" style="color:#f87171;"></i> Supprimer catégorie</h3>
      <button class="btn btn-secondary btn-sm" onclick="closeModal('modal-delete')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST" action="/caremeal/admin/categorie.php">
      <input type="hidden" name="action" value="delete_category">
      <input type="hidden" name="id_categorie" id="delete-id">
      <div class="modal-body">
        <p id="delete-label" style="margin:0; color:var(--color-text-muted);"></p>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-delete')">Annuler</button>
        <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Supprimer</button>
      </div>
    </form>
  </div>
</div>

<script src="/caremeal/js/app.js"></script>
<script src="/caremeal/js/components.js"></script>
<script>
/* ══════════════════════════════════════════
   Modals
══════════════════════════════════════════ */
function openModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.add('active');
}
function closeModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.remove('active');
}
function openEditModal(id, name, description, icon) {
  document.getElementById('edit-id').value          = id;
  document.getElementById('edit-name').value        = name;
  document.getElementById('edit-description').value = description;
  document.getElementById('edit-icon').value        = icon;
  clearErrors('form-edit');
  openModal('modal-edit');
}
function openDeleteModal(id, name) {
  document.getElementById('delete-id').value    = id;
  document.getElementById('delete-label').textContent =
    'Voulez-vous vraiment supprimer la catégorie "' + name + '" ? Cette action est irréversible.';
  openModal('modal-delete');
}

// Close on backdrop click
document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
  overlay.addEventListener('click', function(e) {
    if (e.target === overlay) overlay.classList.remove('active');
  });
});

/* ══════════════════════════════════════════
   Validation JS (sans HTML5)
══════════════════════════════════════════ */

// Règles de validation par champ (formId → liste de règles)
var RULES = {
  'form-create': [
    {
      field  : 'create-nom',
      errId  : 'err-create-nom',
      checks : [
        { test: function(v){ return v.trim().length === 0; },        msg: 'Le nom de la catégorie est obligatoire.' },
        { test: function(v){ return v.trim().length < 2; },           msg: 'Le nom doit contenir au moins 2 caractères.' },
        { test: function(v){ return v.trim().length > 80; },          msg: 'Le nom ne peut pas dépasser 80 caractères.' },
        { test: function(v){ return !/^[\p{L}0-9 \-'éèêëàâùûüîïôœçÉÈÊËÀÂÙÛÜÎÏÔŒÇ]+$/u.test(v.trim()); },
          msg: 'Le nom contient des caractères non autorisés.' }
      ]
    },
    {
      field  : 'create-icone',
      errId  : 'err-create-icone',
      checks : [
        { test: function(v){ return v.trim().length > 0 && !/^fa-[a-z0-9\-]+$/.test(v.trim()); },
          msg: 'Format invalide. Exemple valide : fa-burger' }
      ]
    }
  ],
  'form-edit': [
    {
      field  : 'edit-name',
      errId  : 'err-edit-nom',
      checks : [
        { test: function(v){ return v.trim().length === 0; },        msg: 'Le nom de la catégorie est obligatoire.' },
        { test: function(v){ return v.trim().length < 2; },           msg: 'Le nom doit contenir au moins 2 caractères.' },
        { test: function(v){ return v.trim().length > 80; },          msg: 'Le nom ne peut pas dépasser 80 caractères.' }
      ]
    },
    {
      field  : 'edit-icon',
      errId  : 'err-edit-icone',
      checks : [
        { test: function(v){ return v.trim().length > 0 && !/^fa-[a-z0-9\-]+$/.test(v.trim()); },
          msg: 'Format invalide. Exemple valide : fa-tag' }
      ]
    }
  ]
};

function showError(fieldId, errId, msg) {
  var input = document.getElementById(fieldId);
  var span  = document.getElementById(errId);
  if (input) input.classList.add('input-error');
  if (span)  { span.textContent = msg; span.classList.add('visible'); }
}

function clearError(fieldId, errId) {
  var input = document.getElementById(fieldId);
  var span  = document.getElementById(errId);
  if (input) input.classList.remove('input-error');
  if (span)  { span.textContent = ''; span.classList.remove('visible'); }
}

function clearErrors(formId) {
  var rules = RULES[formId];
  if (!rules) return;
  rules.forEach(function(rule) { clearError(rule.field, rule.errId); });
}

function validateAndSubmit(formId) {
  var rules  = RULES[formId];
  var form   = document.getElementById(formId);
  var valid  = true;

  if (rules) {
    rules.forEach(function(rule) {
      var input = document.getElementById(rule.field);
      var val   = input ? input.value : '';
      var failed = false;

      clearError(rule.field, rule.errId);

      for (var i = 0; i < rule.checks.length; i++) {
        if (rule.checks[i].test(val)) {
          showError(rule.field, rule.errId, rule.checks[i].msg);
          failed = true;
          valid  = false;
          break; // une seule erreur affichée par champ
        }
      }
    });
  }

  if (valid && form) form.submit();
}

// Effacer les erreurs en temps réel quand l'utilisateur tape
['create-nom','create-icone','edit-name','edit-icon'].forEach(function(id) {
  var el = document.getElementById(id);
  if (!el) return;
  el.addEventListener('input', function() {
    el.classList.remove('input-error');
    // trouver et vider le span d'erreur associé
    var errSpans = document.querySelectorAll('.field-error.visible');
    errSpans.forEach(function(s) {
      var fieldId = id;
      // vérification simple : si le span suit directement l'input (même form-group)
      if (el.parentNode && el.parentNode.contains(s)) {
        s.textContent = '';
        s.classList.remove('visible');
      }
    });
  });
});

/* ══ Sidebar ══ */
var mt = document.getElementById('menu-toggle');
var sb = document.getElementById('sidebar');
var ov = document.getElementById('sidebar-overlay');
if (mt && sb) mt.addEventListener('click', function(){ sb.classList.toggle('open'); });
if (ov && sb) ov.addEventListener('click', function(){ sb.classList.remove('open'); });

/* ══ Logout ══ */
document.querySelectorAll('[data-action="logout"]').forEach(function(btn) {
  btn.addEventListener('click', function() {
    if (typeof App !== 'undefined' && typeof App.logout === 'function') {
      App.logout(); return;
    }
    localStorage.removeItem('caremeal_current_user');
    window.location.href = '/caremeal/login.html';
  });
});
</script>
</body>
</html>