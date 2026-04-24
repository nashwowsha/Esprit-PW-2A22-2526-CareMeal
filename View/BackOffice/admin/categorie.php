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
    .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 14px; }
    .alert-success { background: rgba(34,197,94,.14); border: 1px solid rgba(34,197,94,.3); color: #4ade80; }
    .alert-error { background: rgba(239,68,68,.14); border: 1px solid rgba(239,68,68,.3); color: #f87171; }
    .grid-cats { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 14px; }
    .cat-card { border: 1px solid var(--color-dark-border); border-radius: 14px; background: var(--color-dark-card); }
    .cat-card-header { display: flex; justify-content: space-between; gap: 8px; padding: 14px; }
    .cat-title { font-size: .95rem; font-weight: 700; color: var(--color-white); display: flex; gap: 8px; align-items: center; }
    .cat-meta { font-size: .8rem; color: var(--color-text-muted); margin-top: 4px; }
    .cat-actions { display: flex; gap: 6px; align-items: center; }
    .cat-footer { border-top: 1px solid var(--color-dark-border); padding: 10px 14px; display: flex; justify-content: space-between; align-items: center; }
    .count-badge { font-size: .75rem; background: rgba(var(--color-primary-rgb), .16); color: var(--color-primary); padding: 4px 9px; border-radius: 16px; }
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, .72); z-index: 999; align-items: center; justify-content: center; }
    .modal-overlay.active { display: flex; }
    .modal-box { width: 100%; max-width: 560px; background: var(--color-dark-card); border: 1px solid var(--color-dark-border); border-radius: 14px; overflow: hidden; }
    .modal-head { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--color-dark-border); padding: 16px 18px; }
    .modal-body { padding: 16px 18px; }
    .modal-foot { border-top: 1px solid var(--color-dark-border); padding: 14px 18px; display: flex; justify-content: flex-end; gap: 8px; }
    .table-wrap { overflow-x: auto; border: 1px solid var(--color-dark-border); border-radius: 12px; }
    .offers-table { width: 100%; border-collapse: collapse; }
    .offers-table th, .offers-table td { padding: 12px 14px; border-bottom: 1px solid var(--color-dark-border); font-size: .86rem; }
    .offers-table th { text-align: left; font-size: .75rem; text-transform: uppercase; color: var(--color-text-muted); background: rgba(255,255,255,.03); letter-spacing: .06em; }
    .offers-table tr:last-child td { border-bottom: none; }
    .offers-table tr:hover td { background: var(--color-dark-hover); }
    .modal-body .form-input,
    .modal-body .form-textarea {
      width: 100%;
      border: 1px solid var(--color-dark-border);
      border-radius: 12px;
      background: rgba(255,255,255,.05);
      color: var(--color-white);
      font-size: .92rem;
      padding: 12px 14px;
      outline: none;
    }
    .modal-body .form-input:focus,
    .modal-body .form-textarea:focus {
      border-color: var(--color-primary);
      box-shadow: 0 0 0 2px rgba(var(--color-primary-rgb), .22);
      background: rgba(255,255,255,.07);
    }
    .modal-body .form-input::placeholder,
    .modal-body .form-textarea::placeholder {
      color: rgba(255,255,255,.45);
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
        <div class="sidebar-section-title">Administration</div>
        <a href="/caremeal/admin/dashboard.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-chart-column"></i></span> Vue globale</a>
        <a href="/caremeal/admin/users.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-users"></i></span> Utilisateurs</a>
        <a href="/caremeal/admin/partners.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-store"></i></span> Partenaires</a>
        <a href="/caremeal/admin/categorie.php" class="sidebar-link active"><span class="link-icon"><i class="fa-solid fa-tags"></i></span> Categorie offres</a>
        <a href="/caremeal/admin/offers.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-box"></i></span> Offres</a>
        <a href="/caremeal/admin/events.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> Evenements</a>
        <a href="/caremeal/admin/logs.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-clipboard-list"></i></span> Logs d'activites</a>
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
          <h2>Categorie Offres</h2>
          <p>CRUD des categories et affichage des offres liees</p>
        </div>
      </div>
      <div class="header-right">
        <button class="btn btn-primary" onclick="openModal('modal-create')">
          <i class="fa-solid fa-plus"></i> Ajouter categorie
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

      <div class="card" style="padding:16px; margin-bottom:18px;">
        <h3 style="margin:0 0 12px;">Categories</h3>
        <?php if (empty($categories)): ?>
          <p style="margin:0;color:var(--color-text-muted);">Aucune categorie disponible.</p>
        <?php else: ?>
          <div class="grid-cats">
            <?php foreach ($categories as $category): ?>
              <?php $catId = (int)$category['id_categorie']; ?>
              <div class="cat-card">
                <div class="cat-card-header">
                  <div>
                    <div class="cat-title">
                      <i class="fa-solid <?= e($category['icone'] ?: 'fa-tag') ?>"></i>
                      <?= e($category['nom_categorie']) ?>
                    </div>
                    <div class="cat-meta"><?= e($category['description'] ?: 'Pas de description') ?></div>
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
                  <span class="count-badge"><?= (int)$category['total_offres'] ?> offre(s)</span>
                  <a class="btn btn-primary btn-sm" href="?id_categorie=<?= $catId ?>">Voir offres</a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="card" style="padding:16px;">
        <h3 style="margin:0 0 12px;">Offres de la categorie selectionnee</h3>
        <?php if ($selectedCategoryId <= 0): ?>
          <p style="margin:0;color:var(--color-text-muted);">Choisissez une categorie.</p>
        <?php elseif (empty($offers)): ?>
          <p style="margin:0;color:var(--color-text-muted);">Aucune offre liee a cette categorie.</p>
        <?php else: ?>
          <div class="table-wrap">
            <table class="offers-table">
              <thead>
                <tr>
                  <th>Titre</th>
                  <th>Prix</th>
                  <th>Prix original</th>
                  <th>Quantite</th>
                  <th>Statut</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($offers as $offer): ?>
                <tr>
                  <td><?= e($offer['titre']) ?></td>
                  <td><?= number_format((float)$offer['prix'], 2) ?> DT</td>
                  <td><?= number_format((float)$offer['prix_original'], 2) ?> DT</td>
                  <td><?= (int)$offer['quantite'] ?></td>
                  <td><?= e($offer['statut']) ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<div class="modal-overlay" id="modal-create">
  <div class="modal-box">
    <div class="modal-head">
      <h3 style="margin:0;">Ajouter categorie</h3>
      <button class="btn btn-secondary btn-sm" onclick="closeModal('modal-create')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST" action="/caremeal/admin/categorie.php">
      <input type="hidden" name="action" value="create_category">
      <div class="modal-body">
        <div class="form-group">
          <label>Nom categorie</label>
          <input type="text" name="nom_categorie" class="form-input" value="<?= e($old['nom_categorie'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" class="form-textarea" rows="3"><?= e($old['description'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
          <label>Icone Font Awesome (ex: `fa-burger`)</label>
          <input type="text" name="icone" class="form-input" value="<?= e($old['icone'] ?? '') ?>">
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-create')">Annuler</button>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="modal-edit">
  <div class="modal-box">
    <div class="modal-head">
      <h3 style="margin:0;">Modifier categorie</h3>
      <button class="btn btn-secondary btn-sm" onclick="closeModal('modal-edit')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST" action="/caremeal/admin/categorie.php">
      <input type="hidden" name="action" value="update_category">
      <input type="hidden" name="id_categorie" id="edit-id">
      <div class="modal-body">
        <div class="form-group">
          <label>Nom categorie</label>
          <input type="text" name="nom_categorie" id="edit-name" class="form-input" required>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" id="edit-description" class="form-textarea" rows="3"></textarea>
        </div>
        <div class="form-group">
          <label>Icone Font Awesome</label>
          <input type="text" name="icone" id="edit-icon" class="form-input">
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-edit')">Annuler</button>
        <button type="submit" class="btn btn-primary">Mettre a jour</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="modal-delete">
  <div class="modal-box" style="max-width:430px;">
    <div class="modal-head">
      <h3 style="margin:0;">Supprimer categorie</h3>
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
        <button type="submit" class="btn btn-danger">Supprimer</button>
      </div>
    </form>
  </div>
</div>

<script src="/caremeal/js/app.js"></script>
<script src="/caremeal/js/components.js"></script>
<script>
function openModal(id) {
  const modal = document.getElementById(id);
  if (!modal) return;
  modal.classList.add('active');
}
function closeModal(id) {
  const modal = document.getElementById(id);
  if (!modal) return;
  modal.classList.remove('active');
}
function openEditModal(id, name, description, icon) {
  document.getElementById('edit-id').value = id;
  document.getElementById('edit-name').value = name;
  document.getElementById('edit-description').value = description;
  document.getElementById('edit-icon').value = icon;
  openModal('modal-edit');
}
function openDeleteModal(id, name) {
  document.getElementById('delete-id').value = id;
  document.getElementById('delete-label').textContent = 'La categorie "' + name + '" sera supprimee.';
  openModal('modal-delete');
}
document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
  overlay.addEventListener('click', function (event) {
    if (event.target === overlay) {
      overlay.classList.remove('active');
    }
  });
});

const mt = document.getElementById('menu-toggle');
const sb = document.getElementById('sidebar');
const ov = document.getElementById('sidebar-overlay');
if (mt && sb) mt.addEventListener('click', () => sb.classList.toggle('open'));
if (ov && sb) ov.addEventListener('click', () => sb.classList.remove('open'));

document.querySelectorAll('[data-action="logout"]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    if (typeof App !== 'undefined' && typeof App.logout === 'function') {
      App.logout();
      return;
    }
    localStorage.removeItem('caremeal_current_user');
    window.location.href = '/caremeal/login.html';
  });
});
</script>
</body>
</html>
