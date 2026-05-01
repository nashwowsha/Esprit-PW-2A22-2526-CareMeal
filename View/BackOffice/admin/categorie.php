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

    /* ── INLINE FORM (same pattern as offers.php) ── */
    #view-form { display: none; flex-direction: column; gap: 20px; }

    .form-page-header {
      display: flex; align-items: center; gap: 16px;
      margin-bottom: 4px;
    }
    .btn-back {
      display: inline-flex; align-items: center; gap: 8px;
      background: rgba(255,255,255,.07);
      border: 1px solid var(--color-dark-border);
      color: var(--color-white);
      border-radius: 10px; padding: 9px 16px;
      font-size: .875rem; font-weight: 600;
      cursor: pointer; transition: background .2s, border-color .2s;
      white-space: nowrap;
    }
    .btn-back:hover { background: rgba(255,255,255,.12); border-color: rgba(255,255,255,.2); }

    .form-card {
      background: var(--color-dark-card);
      border: 1px solid var(--color-dark-border);
      border-radius: 16px;
      padding: 24px;
    }
    .form-card .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
    .form-card .form-group:last-child { margin-bottom: 0; }
    .form-card label {
      font-size: .78rem; font-weight: 600;
      color: var(--color-text-muted);
      text-transform: uppercase; letter-spacing: .06em;
    }
    .form-card .form-input,
    .form-card .form-textarea {
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
    .form-card .form-input:focus,
    .form-card .form-textarea:focus {
      border-color: var(--color-primary);
      box-shadow: 0 0 0 3px rgba(254,85,22,.18);
      background: rgba(255,255,255,.08);
    }
    .form-card .form-input.input-error {
      border-color: #f87171;
      box-shadow: 0 0 0 3px rgba(248,113,113,.2);
    }
    .form-card .form-input::placeholder,
    .form-card .form-textarea::placeholder { color: rgba(255,255,255,.35); }
    .field-error { font-size: .78rem; color: #f87171; display: none; }
    .field-error.visible { display: block; }

    .form-actions {
      display: flex; justify-content: flex-end; gap: 10px;
      border-top: 1px solid var(--color-dark-border);
      margin-top: 20px; padding-top: 20px;
    }

    /* Delete card */
    .delete-inline-card {
      text-align: center; padding: 40px 24px;
      background: var(--color-dark-card);
      border: 1px solid var(--color-dark-border);
      border-radius: 16px;
    }
    .delete-inline-card i.big-icon { font-size: 3rem; color: #f87171; display: block; margin-bottom: 16px; }
    .delete-inline-card h3 { margin: 0 0 8px; color: var(--color-white); }
    .delete-inline-card p  { color: var(--color-text-muted); margin: 0 0 24px; }

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
    .offers-table tbody tr { transition: background .15s; cursor: default; }
    .offers-table tbody tr:hover td { background: rgba(255,255,255,.04); }
    .offers-table td {
      padding: 13px 18px;
      border-bottom: 1px solid rgba(255,255,255,.05);
    }
    .offers-table tbody tr:last-child td { border-bottom: none; }

    /* Price */
    .price-cell     { font-weight: 700; color: var(--color-white); }
    .price-original { color: var(--color-text-muted); text-decoration: line-through; font-size: .82rem; }

    /* Qty badge */
    .qty-badge {
      display: inline-flex; align-items: center; justify-content: center;
      min-width: 28px; padding: 2px 8px;
      border-radius: 20px; font-size: .78rem; font-weight: 600;
      background: rgba(255,255,255,.07); color: var(--color-white);
    }

    /* Status badge */
    .status-badge {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: .72rem; font-weight: 600;
      padding: 4px 10px; border-radius: 20px;
      text-transform: capitalize;
      background: rgba(255,255,255,.06);
      color: var(--color-text-muted);
    }
    .status-badge::before {
      content: ''; width: 6px; height: 6px;
      border-radius: 50%; background: currentColor;
    }
    .status-badge.publiée  { background: rgba(34,197,94,.12); color: #4ade80; }
    .status-badge.brouillon { background: rgba(250,204,21,.12); color: #facc15; }
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
          <h2 id="page-heading">Catégorie Offres</h2>
          <p id="page-sub">CRUD des catégories et affichage des offres liées</p>
        </div>
      </div>
      <div class="header-right">
        <button class="btn btn-primary" id="btn-add-cat" onclick="showCreateForm()"
                style="display:flex;align-items:center;gap:8px;">
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

      <!-- ══════════════════════════════════════
           VUE 1 : LISTE DES CATÉGORIES
      ══════════════════════════════════════ -->
      <div id="view-list">

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
                              onclick="showEditForm('<?= $catId ?>', '<?= e($category['nom_categorie']) ?>', '<?= e($category['description']) ?>', '<?= e($category['icone']) ?>')">
                        <i class="fa-solid fa-pen"></i>
                      </button>
                      <button class="btn btn-danger btn-sm"
                              onclick="showDeleteForm('<?= $catId ?>', '<?= e($category['nom_categorie']) ?>')">
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

      </div><!-- /view-list -->

      <!-- ══════════════════════════════════════
           VUE 2 : FORMULAIRE INLINE
      ══════════════════════════════════════ -->
      <div id="view-form">

        <!-- En-tête formulaire -->
        <div class="form-page-header">
          <button class="btn-back" onclick="showList()">
            <i class="fa-solid fa-arrow-left"></i> Retour
          </button>
          <div>
            <h2 id="form-heading">Ajouter une catégorie</h2>
            <p id="form-sub">Remplissez les informations ci-dessous</p>
          </div>
        </div>

        <!-- ── Formulaire CRÉER ── -->
        <div id="form-create-wrap" style="display:none;">
          <div class="form-card">
            <form method="POST" action="/caremeal/admin/categorie.php" id="form-create" novalidate>
              <input type="hidden" name="action" value="create_category">
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
              <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="showList()">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="validateAndSubmit('form-create')">
                  <i class="fa-solid fa-floppy-disk"></i> Enregistrer
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- ── Formulaire MODIFIER ── -->
        <div id="form-edit-wrap" style="display:none;">
          <div class="form-card">
            <form method="POST" action="/caremeal/admin/categorie.php" id="form-edit" novalidate>
              <input type="hidden" name="action" value="update_category">
              <input type="hidden" name="id_categorie" id="edit-id">
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
              <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="showList()">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="validateAndSubmit('form-edit')">
                  <i class="fa-solid fa-floppy-disk"></i> Mettre à jour
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- ── Formulaire SUPPRIMER ── -->
        <div id="form-delete-wrap" style="display:none;">
          <div class="delete-inline-card">
            <i class="fa-solid fa-trash big-icon"></i>
            <h3>Supprimer la catégorie</h3>
            <p id="delete-label"></p>
            <form method="POST" action="/caremeal/admin/categorie.php">
              <input type="hidden" name="action" value="delete_category">
              <input type="hidden" name="id_categorie" id="delete-id">
              <div class="form-actions" style="justify-content:center;border:none;margin-top:0;padding-top:0;">
                <button type="button" class="btn btn-secondary" onclick="showList()">Annuler</button>
                <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Supprimer</button>
              </div>
            </form>
          </div>
        </div>

      </div><!-- /view-form -->

    </div><!-- /page-content -->
  </main>
</div>

<script src="/caremeal/js/app.js"></script>
<script src="/caremeal/js/components.js"></script>
<script>
/* ══════════════════════════════════════════
   NAVIGATION ENTRE VUES (sans modal)
══════════════════════════════════════════ */
function showList() {
  document.getElementById('view-list').style.display = 'block';
  document.getElementById('view-form').style.display = 'none';
  document.getElementById('btn-add-cat').style.display = 'flex';
  document.getElementById('page-heading').textContent = 'Catégorie Offres';
  document.getElementById('page-sub').textContent     = 'CRUD des catégories et affichage des offres liées';
}

function showFormView(heading, sub) {
  document.getElementById('view-list').style.display = 'none';
  document.getElementById('view-form').style.display = 'flex';
  document.getElementById('btn-add-cat').style.display = 'none';
  document.getElementById('form-heading').textContent = heading;
  document.getElementById('form-sub').textContent     = sub;
  // Cacher les 3 sous-vues
  document.getElementById('form-create-wrap').style.display = 'none';
  document.getElementById('form-edit-wrap').style.display   = 'none';
  document.getElementById('form-delete-wrap').style.display = 'none';
}

function showCreateForm() {
  showFormView('Ajouter une catégorie', 'Remplissez les informations ci-dessous');
  document.getElementById('form-create-wrap').style.display = 'block';
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function showEditForm(id, name, description, icon) {
  showFormView('Modifier la catégorie', name);
  document.getElementById('edit-id').value          = id;
  document.getElementById('edit-name').value        = name;
  document.getElementById('edit-description').value = description;
  document.getElementById('edit-icon').value        = icon;
  clearErrors('form-edit');
  document.getElementById('form-edit-wrap').style.display = 'block';
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function showDeleteForm(id, name) {
  showFormView('Supprimer la catégorie', '');
  document.getElementById('delete-id').value       = id;
  document.getElementById('delete-label').textContent =
    'Voulez-vous vraiment supprimer la catégorie "' + name + '" ? Cette action est irréversible.';
  document.getElementById('form-delete-wrap').style.display = 'block';
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ══════════════════════════════════════════
   Validation JS (sans HTML5)
══════════════════════════════════════════ */
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
      clearError(rule.field, rule.errId);
      for (var i = 0; i < rule.checks.length; i++) {
        if (rule.checks[i].test(val)) {
          showError(rule.field, rule.errId, rule.checks[i].msg);
          valid = false;
          break;
        }
      }
    });
  }

  if (valid && form) form.submit();
}

// Effacer les erreurs en temps réel
['create-nom','create-icone','edit-name','edit-icon'].forEach(function(id) {
  var el = document.getElementById(id);
  if (!el) return;
  el.addEventListener('input', function() {
    el.classList.remove('input-error');
    var errSpans = document.querySelectorAll('.field-error.visible');
    errSpans.forEach(function(s) {
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
  <script src="/caremeal/js/admin-voice-assistant.js"></script>
</body>

