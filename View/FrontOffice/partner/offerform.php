<?php
// Formulaire de CRÉATION d'offre (inclus dans modal-create de offers.php)
// Variables disponibles : $categories, $old (valeurs précédentes si erreur)
$old = $old ?? [];

function ec($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>

<style>
/* ── Category pill style (screenshot match) ── */
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
  transform: rotate(45deg) translate(-1px, -1px);
}
.cat-pill-label:has(input:checked) {
  border-color: var(--color-primary, #ef4444);
  background: rgba(239,68,68,.1);
  color: #fff;
}
.cat-pill-label:hover { border-color: #4a5568; background:#252d3d; }
</style>

<div class="form-group">
  <label>Titre <span style="color:var(--color-primary)">*</span></label>
  <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-tag"></i></span>
    <input type="text" name="titre" class="form-input"
           placeholder="Ex: Panier surprise"
           value="<?= ec($old['titre'] ?? '') ?>"
           id="create-titre">
  </div>
</div>

<div class="form-group">
  <label>Description</label>
  <textarea name="description" class="form-textarea" rows="3" placeholder="Décrivez le contenu..."><?= ec($old['description'] ?? '') ?></textarea>
</div>

<div class="form-row">
  <div class="form-group">
    <label>Prix original (DT) <span style="color:var(--color-primary)">*</span></label>
    <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-money-bill"></i></span>
      <input type="text" name="prix_original" class="form-input" placeholder="12.00" value="<?= ec($old['prix_original'] ?? '') ?>">
    </div>
  </div>
  <div class="form-group">
    <label>Prix réduit (DT) <span style="color:var(--color-primary)">*</span></label>
    <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-percent"></i></span>
      <input type="text" name="prix" class="form-input" placeholder="4.50" value="<?= ec($old['prix'] ?? '') ?>">
    </div>
  </div>
</div>

<div class="form-group">
  <label>Quantité <span style="color:var(--color-primary)">*</span></label>
  <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-boxes-stacked"></i></span>
    <input type="text" name="quantite" class="form-input" placeholder="5" value="<?= ec($old['quantite'] ?? '') ?>">
  </div>
</div>

<!-- ── Catégories multi-sélection (checkboxes pill) ── -->
<div class="form-group">
  <label>Catégories <span style="color:var(--color-primary)">*</span>
    <small style="color:var(--color-text-muted);font-weight:400;">(une ou plusieurs)</small>
  </label>
  <div class="cat-checkbox-list" id="partner-create-cat-list">
    <?php
    $oldCats = isset($old['id_categories']) && is_array($old['id_categories'])
        ? array_map('intval', $old['id_categories'])
        : (isset($old['id_categorie']) ? [(int)$old['id_categorie']] : []);
    foreach (($categories ?? []) as $c):
      $checked = in_array((int)$c['id_categorie'], $oldCats) ? 'checked' : '';
    ?>
      <div class="cat-checkbox-item">
        <label class="cat-pill-label">
          <span class="cat-pill-box"></span>
          <input type="checkbox" name="id_categories[]" value="<?= (int)$c['id_categorie'] ?>" <?= $checked ?> style="display:none">
          <span class="cat-pill-name"><?= strtoupper(ec($c['nom_categorie'])) ?></span>
        </label>
      </div>
    <?php endforeach; ?>
  </div>
  <div id="partner-create-cat-error" style="color:#f87171;font-size:.75rem;margin-top:4px;display:none;">
    Veuillez sélectionner au moins une catégorie.
  </div>
</div>

<div class="form-row">
  <div class="form-group">
    <label>Heure début</label>
    <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-clock"></i></span>
      <input type="text" name="heure_debut" class="form-input" value="<?= ec($old['heure_debut'] ?? '') ?>">
    </div>
  </div>
  <div class="form-group">
    <label>Heure fin</label>
    <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-clock"></i></span>
      <input type="text" name="heure_fin" class="form-input" value="<?= ec($old['heure_fin'] ?? '') ?>">
    </div>
  </div>
</div>

<div class="form-group">
  <label>Photo du produit</label>
  <input type="hidden" name="photo_url" id="create-photo-url" value="">
  <div class="photo-upload-area">
    <input type="file" onchange="handlePhotoUpload(this,'create-photo-url','create-photo-preview','create-photo-placeholder')">
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