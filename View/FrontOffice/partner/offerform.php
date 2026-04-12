<?php
// Formulaire de CRÉATION d'offre (inclus dans modal-create de offers.php)
// Variables disponibles : $categories, $old (valeurs précédentes si erreur)
$old = $old ?? [];

function ec($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>

<div class="form-group">
  <label>Titre <span style="color:var(--color-primary)">*</span></label>
  <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-tag"></i></span>
    <input type="text" name="titre" class="form-input" 
           placeholder="Ex: Panier surprise" 
           value="<?= ec($old['titre'] ?? '') ?>"
           id="create-titre"
           required>
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
      <input type="number" name="prix_original" class="form-input" step="0.01" min="0.01" placeholder="12.00" value="<?= ec($old['prix_original'] ?? '') ?>" required>
    </div>
  </div>
  <div class="form-group">
    <label>Prix réduit (DT) <span style="color:var(--color-primary)">*</span></label>
    <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-percent"></i></span>
      <input type="number" name="prix" class="form-input" step="0.01" min="0.01" placeholder="4.50" value="<?= ec($old['prix'] ?? '') ?>" required>
    </div>
  </div>
</div>

<div class="form-row">
  <div class="form-group">
    <label>Quantité <span style="color:var(--color-primary)">*</span></label>
    <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-boxes-stacked"></i></span>
      <input type="number" name="quantite" class="form-input" min="1" placeholder="5" value="<?= ec($old['quantite'] ?? '') ?>" required>
    </div>
  </div>
  <div class="form-group">
    <label>Catégorie</label>
    <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-list"></i></span>
      <select name="id_categorie" class="form-input">
        <option value="">-- Choisir --</option>
        <?php foreach ($categories ?? [] as $c): ?>
          <option value="<?= (int)$c['id_categorie'] ?>" <?= (($old['id_categorie'] ?? '') == $c['id_categorie']) ? 'selected' : '' ?>>
            <?= ec($c['nom_categorie']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</div>

<div class="form-row">
  <div class="form-group">
    <label>Heure début</label>
    <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-clock"></i></span>
      <input type="time" name="heure_debut" class="form-input" value="<?= ec($old['heure_debut'] ?? '') ?>">
    </div>
  </div>
  <div class="form-group">
    <label>Heure fin</label>
    <div class="input-wrapper"><span class="input-icon"><i class="fa-solid fa-clock"></i></span>
      <input type="time" name="heure_fin" class="form-input" value="<?= ec($old['heure_fin'] ?? '') ?>">
    </div>
  </div>
</div>

<div class="form-group">
  <label>Photo du produit</label>
  <input type="hidden" name="photo_url" id="create-photo-url" value="">
  <div class="photo-upload-area">
    <input type="file" accept="image/*" onchange="handlePhotoUpload(this,'create-photo-url','create-photo-preview','create-photo-placeholder')">
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