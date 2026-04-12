<?php
// Variables disponibles : $offers, $categories, $counts, $flash, $errors, $old
function ea($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Offres — Admin CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/caremeal/css/main.css">
  <link rel="stylesheet" href="/caremeal/css/components.css">
  <link rel="stylesheet" href="/caremeal/css/dashboard.css">
  <style>
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.75); z-index:1000; align-items:center; justify-content:center; }
    .modal-overlay.active { display:flex; }
    .modal { background:var(--color-dark-card); border-radius:16px; width:100%; max-width:560px; max-height:90vh; overflow-y:auto; border:1px solid var(--color-dark-border); }
    .modal-header { display:flex; align-items:center; justify-content:space-between; padding:20px 24px; border-bottom:1px solid var(--color-dark-border); }
    .modal-header h3 { margin:0; font-size:1.1rem; color:var(--color-white); }
    .modal-body { padding:24px; }
    .modal-footer { padding:16px 24px; border-top:1px solid var(--color-dark-border); display:flex; gap:12px; justify-content:flex-end; }
    .modal-close { background:none; border:none; color:var(--color-text-muted); font-size:1.3rem; cursor:pointer; }
    .modal-close:hover { color:var(--color-white); }
    .form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    .delete-confirm { background:var(--color-dark-card); border-radius:16px; padding:32px; max-width:420px; width:100%; text-align:center; border:1px solid var(--color-dark-border); }
    .delete-confirm p { color:var(--color-text-muted); margin:8px 0 24px; }
    .badge-statut { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.72rem; font-weight:600; }
    .s-publiee   { background:rgba(34,197,94,.15);  color:#4ade80; }
    .s-brouillon { background:rgba(251,191,36,.15); color:#fbbf24; }
    .s-expiree, .s-archivee { background:rgba(156,163,175,.12); color:#9ca3af; }
    .data-table { width:100%; border-collapse:collapse; }
    .data-table th { text-align:left; padding:12px 16px; font-size:.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--color-text-muted); border-bottom:1px solid var(--color-dark-border); }
    .data-table td { padding:13px 16px; border-bottom:1px solid var(--color-dark-border); font-size:.875rem; vertical-align:middle; }
    .data-table tr:last-child td { border-bottom:none; }
    .data-table tr:hover td { background:var(--color-dark-hover); }
    .photo-upload-area { border:2px dashed var(--color-dark-border); border-radius:12px; padding:20px; text-align:center; cursor:pointer; transition:border-color .2s; position:relative; }
    .photo-upload-area:hover { border-color:var(--color-primary); }
    .photo-upload-area input[type="file"] { position:absolute; inset:0; width:100%; height:100%; opacity:0; cursor:pointer; }
    .photo-preview { width:100%; height:120px; object-fit:cover; border-radius:8px; margin-top:10px; display:none; }
    .photo-placeholder { color:var(--color-text-muted); font-size:.85rem; pointer-events:none; }
    .photo-placeholder i { font-size:2rem; display:block; margin-bottom:6px; opacity:.5; }
    .empty-state { text-align:center; padding:60px 20px; color:var(--color-text-muted); }
    .empty-state i { font-size:3rem; display:block; margin-bottom:12px; opacity:.3; }
    .alert { padding:12px 18px; border-radius:10px; margin-bottom:18px; font-size:.9rem; }
    .alert-success { background:rgba(34,197,94,.12); color:#4ade80; border:1px solid rgba(34,197,94,.2); }
    .alert-error   { background:rgba(239,68,68,.12);  color:#f87171; border:1px solid rgba(239,68,68,.2); }
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
        <a href="/caremeal/admin/users.html"     class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-users"></i></span> Utilisateurs</a>
        <a href="/caremeal/admin/partners.html"  class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-store"></i></span> Partenaires</a>
        <a href="offers.php"                    class="sidebar-link active"><span class="link-icon"><i class="fa-solid fa-box"></i></span> Offres</a>
        <a href="/caremeal/admin/events.html"    class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> Événements</a>
        <a href="/caremeal/admin/logs.html"      class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-clipboard-list"></i></span> Logs d'activité</a>
      </div>
    </nav>
    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="avatar" id="sidebar-user-avatar" style="background:linear-gradient(135deg,#EF4444,#F87171);">A</div>
        <div class="sidebar-user-info">
          <div class="sidebar-user-name" id="sidebar-user-name">Admin</div>
          <div class="sidebar-user-role">Administrateur</div>
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
        <div class="page-title"><h2>Offres partenaires</h2><p>Liste et gestion de toutes les offres</p></div>
      </div>
      <div class="header-right">
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

      <!-- Filtres -->
      <div class="card" style="padding:16px;margin-bottom:20px;display:flex;flex-wrap:wrap;gap:12px;align-items:center;">
        <div style="flex:1;min-width:220px;position:relative;">
          <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--color-text-muted);"></i>
          <input type="text" id="search-input" class="form-input" style="padding-left:36px;" placeholder="Rechercher par titre, partenaire…" oninput="applyFilters()">
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;" id="filter-btns">
          <button class="btn btn-primary"   onclick="setFilter('tous',this)">Tous</button>
          <button class="btn btn-secondary" onclick="setFilter('publiée',this)">Publiées</button>
          <button class="btn btn-secondary" onclick="setFilter('brouillon',this)">Brouillons</button>
          <button class="btn btn-secondary" onclick="setFilter('expirée',this)">Expirées</button>
          <button class="btn btn-secondary" onclick="setFilter('archivée',this)">Archivées</button>
        </div>
      </div>

      <!-- Tableau PHP -->
      <div class="card" style="padding:0;overflow:hidden;">
        <div style="overflow-x:auto;">
          <table class="data-table" id="offers-table">
            <thead>
              <tr>
                <th>Offre</th>
                <th>Partenaire</th>
                <th>Prix</th>
                <th>Stock</th>
                <th>Statut</th>
                <th style="text-align:center;">Actions</th>
              </tr>
            </thead>
            <tbody id="offers-tbody">
              <?php if (empty($offers)): ?>
                <tr><td colspan="6">
                  <div class="empty-state"><i class="fa-solid fa-box-open"></i><p>Aucune offre trouvée.</p></div>
                </td></tr>
              <?php else: foreach ($offers as $o):
                $disc = $o['prix_original'] > 0 ? round((1 - $o['prix'] / $o['prix_original']) * 100) : 0;
                $sCls = ['publiée'=>'s-publiee','brouillon'=>'s-brouillon','expirée'=>'s-expiree','archivée'=>'s-archivee'][$o['statut']] ?? '';
                $stock = ($o['quantite'] ?? '?');
              ?>
                <tr data-statut="<?= ea($o['statut']) ?>" data-titre="<?= ea(strtolower($o['titre'])) ?>" data-partenaire="<?= ea(strtolower($o['id_partenaire'] ?? '')) ?>">
                  <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                      <?php if (!empty($o['photo_url'])): ?>
                        <img src="<?= ea($o['photo_url']) ?>" style="width:40px;height:40px;object-fit:cover;border-radius:8px;" onerror="this.style.display='none'">
                      <?php else: ?>
                        <div style="width:40px;height:40px;border-radius:8px;background:var(--color-dark-hover);display:flex;align-items:center;justify-content:center;color:var(--color-text-muted);"><i class="fa-solid fa-utensils"></i></div>
                      <?php endif; ?>
                      <div>
                        <div style="font-weight:600;color:var(--color-white);font-size:.88rem;"><?= ea($o['titre']) ?></div>
                        <div style="font-size:.75rem;color:var(--color-text-muted);"><?= ea($o['nom_categorie'] ?? '') ?></div>
                      </div>
                    </div>
                  </td>
                  <td style="font-size:.85rem;"><?= ea($o['id_partenaire'] ?? '—') ?></td>
                  <td>
                    <span style="text-decoration:line-through;color:var(--color-text-muted);font-size:.8rem;"><?= number_format($o['prix_original'] ?? 0, 2) ?> DT</span><br>
                    <span style="color:var(--color-primary);font-weight:700;"><?= number_format($o['prix'] ?? 0, 2) ?> DT</span>
                    <span style="color:#4ade80;font-size:.72rem;margin-left:2px;">−<?= $disc ?>%</span>
                  </td>
                  <td style="font-size:.85rem;"><?= ea($stock) ?></td>
                  <td><span class="badge-statut <?= $sCls ?>"><?= ea($o['statut']) ?></span></td>
                  <td style="text-align:center;">
                    <div style="display:flex;gap:6px;justify-content:center;">
                      <button class="btn btn-secondary btn-sm" onclick="openEditModal(<?= (int)$o['id_offre'] ?>)" title="Modifier"><i class="fa-solid fa-pen"></i></button>
                      <button class="btn btn-danger btn-sm"    onclick="openDeleteModal(<?= (int)$o['id_offre'] ?>, '<?= ea($o['titre']) ?>')" title="Supprimer"><i class="fa-solid fa-trash"></i></button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!--  MODAL MODIFIER                                           -->
<!-- ══════════════════════════════════════════════════════════ -->
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

<!-- ══════════════════════════════════════════════════════════ -->
<!--  MODAL SUPPRIMER                                          -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modal-delete">
  <div class="delete-confirm">
    <i class="fa-solid fa-trash" style="font-size:2.5rem;color:#f87171;margin-bottom:12px;display:block;"></i>
    <h3 style="color:var(--color-white);margin:0;">Supprimer cette offre ?</h3>
    <p id="delete-label"></p>
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

<!-- Données PHP injectées pour le JS d'édition -->
<script>
const allOffersData     = <?= json_encode(array_values($offers ?? []), JSON_UNESCAPED_UNICODE) ?>;
const allCategoriesData = <?= json_encode($categories ?? [], JSON_UNESCAPED_UNICODE) ?>;
</script>

<script src="/caremeal/js/app.js"></script>
<script src="/caremeal/js/components.js"></script>
<script>
/* ── Helpers modal ──────────────────────────────────────── */
function openModal(id)  { const el=document.getElementById(id); el.classList.add('active'); el.style.display='flex'; document.body.style.overflow='hidden'; }
function closeModal(id) { const el=document.getElementById(id); el.classList.remove('active'); el.style.display='none'; document.body.style.overflow=''; }

document.querySelectorAll('.modal-overlay').forEach(el =>
  el.addEventListener('click', e => { if (e.target === el) closeModal(el.id); })
);

/* ── Escape HTML ────────────────────────────────────────── */
function esc(s) { return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

/* ── Génération du formulaire d'édition ─────────────────── */
function buildEditForm(o) {
  const catOpts = allCategoriesData.map(c =>
    `<option value="${c.id_categorie}" ${o.id_categorie == c.id_categorie ? 'selected' : ''}>${esc(c.nom_categorie)}</option>`
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
          <input type="number" name="prix_original" class="form-input" step="0.01" min="0.01" value="${esc(o.prix_original??'')}" required>
        </div>
      </div>
      <div class="form-group">
        <label>Prix réduit (DT) <span style="color:var(--color-primary)">*</span></label>
        <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-percent"></i></span>
          <input type="number" name="prix" class="form-input" step="0.01" min="0.01" value="${esc(o.prix??'')}" required>
        </div>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Quantité <span style="color:var(--color-primary)">*</span></label>
        <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-boxes-stacked"></i></span>
          <input type="number" name="quantite" class="form-input" min="1" value="${esc(o.quantite??'')}" required>
        </div>
      </div>
      <div class="form-group">
        <label>Catégorie</label>
        <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-list"></i></span>
          <select name="id_categorie" class="form-input"><option value="">-- Choisir --</option>${catOpts}</select>
        </div>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Heure début</label>
        <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-clock"></i></span>
          <input type="time" name="heure_debut" class="form-input" value="${esc(o.heure_debut??'')}">
        </div>
      </div>
      <div class="form-group">
        <label>Heure fin</label>
        <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-clock"></i></span>
          <input type="time" name="heure_fin" class="form-input" value="${esc(o.heure_fin??'')}">
        </div>
      </div>
    </div>
    <div class="form-group">
      <label>Photo</label>
      <input type="hidden" name="photo_url" id="edit-photo-url" value="${esc(existingPhoto)}">
      <div class="photo-upload-area">
        <input type="file" accept="image/*" onchange="handlePhotoUpload(this)">
        <div class="photo-placeholder" id="edit-photo-placeholder" ${existingPhoto?'style="display:none"':''}>
          <i class="fa-solid fa-cloud-arrow-up"></i>
          Cliquez ou glissez une image<br><small style="opacity:.6;">JPG, PNG, WEBP — max 2 Mo</small>
        </div>
        <img id="edit-photo-preview" class="photo-preview" alt="Aperçu" ${existingPhoto?`src="${esc(existingPhoto)}" style="display:block;"`:''}
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

/* ── Ouvrir modal modifier ──────────────────────────────── */
function openEditModal(id) {
  const o = allOffersData.find(x => x.id_offre == id);
  if (!o) { alert('Offre introuvable.'); return; }
  document.getElementById('edit-id_offre').value = id;
  document.getElementById('edit-body').innerHTML  = buildEditForm(o);
  // Attacher validation en temps réel sur les champs générés dynamiquement
  const editForm = document.getElementById('form-edit');
  editForm.querySelectorAll('input,textarea,select').forEach(el => {
    el.addEventListener('input', () => clearFieldError(el));
  });
  openModal('modal-edit');
}

/* ── Ouvrir modal supprimer ─────────────────────────────── */
function openDeleteModal(id, titre) {
  document.getElementById('delete-id_offre').value   = id;
  document.getElementById('delete-label').textContent = `« ${titre} » sera définitivement supprimée.`;
  openModal('modal-delete');
}

/* ── Validation des formulaires offre ─────────────────── */
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
    else if (!/^[A-Za-zÀ-ÖØ-öø-ÿ\s\-']+$/.test(v)) { showFieldError(titre, 'Lettres uniquement, pas de chiffres ni symboles.'); valid = false; }
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
  const hd = form.querySelector('[name="heure_debut"]');
  const hf = form.querySelector('[name="heure_fin"]');
  if (hd && hf && hd.value && hf.value && hf.value <= hd.value) {
    showFieldError(hf, "L'heure de fin doit être après l'heure de début."); valid = false;
  } else if (hf) clearFieldError(hf);
  return valid;
}

// Attacher validation au formulaire d'édition
document.addEventListener('DOMContentLoaded', () => {
  const editForm = document.getElementById('form-edit');
  if (editForm) {
    editForm.addEventListener('submit', e => {
      if (!validateOfferForm(editForm)) e.preventDefault();
    });
  }
});

/* ── Upload photo (base64 → champ caché) ────────────────── */
function handlePhotoUpload(input) {
  const file = input.files[0];
  if (!file) return;
  if (file.size > 2 * 1024 * 1024) { alert('Image trop lourde (max 2 Mo).'); return; }
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('edit-photo-url').value = e.target.result;
    const preview = document.getElementById('edit-photo-preview');
    const ph      = document.getElementById('edit-photo-placeholder');
    if (preview) { preview.src = e.target.result; preview.style.display = 'block'; }
    if (ph)      { ph.style.display = 'none'; }
  };
  reader.readAsDataURL(file);
}

/* ── Filtre côté client (tableau PHP statique) ──────────── */
let currentFilter = 'tous';

function setFilter(f, btn) {
  currentFilter = f;
  document.getElementById('filter-btns').querySelectorAll('button').forEach(b => {
    b.classList.remove('btn-primary'); b.classList.add('btn-secondary');
  });
  if (btn) { btn.classList.remove('btn-secondary'); btn.classList.add('btn-primary'); }
  applyFilters();
}

function applyFilters() {
  const q = (document.getElementById('search-input').value || '').toLowerCase();
  document.querySelectorAll('#offers-tbody tr[data-statut]').forEach(row => {
    const statut     = row.dataset.statut     || '';
    const titre      = row.dataset.titre      || '';
    const partenaire = row.dataset.partenaire || '';
    const matchFilter = currentFilter === 'tous' || statut === currentFilter;
    const matchSearch = !q || titre.includes(q) || partenaire.includes(q);
    row.style.display = (matchFilter && matchSearch) ? '' : 'none';
  });
}

/* ── Menu mobile ────────────────────────────────────────── */
const mt = document.getElementById('menu-toggle');
const sb = document.getElementById('sidebar');
const ov = document.getElementById('sidebar-overlay');
if (mt) mt.addEventListener('click', () => sb.classList.toggle('open'));
if (ov) ov.addEventListener('click', () => sb.classList.remove('open'));

const logoutBtn = document.querySelector('[data-action="logout"]');
if (logoutBtn) logoutBtn.addEventListener('click', () => { if (typeof App !== 'undefined') App.logout(); });

/* ── Ré-ouvrir modal si erreurs de validation ───────────── */
<?php if (!empty($errors) && !empty($old) && isset($old['id_offre'])): ?>
  document.addEventListener('DOMContentLoaded', () => openEditModal(<?= (int)$old['id_offre'] ?>));
<?php endif; ?>
</script>
</body>
</html>