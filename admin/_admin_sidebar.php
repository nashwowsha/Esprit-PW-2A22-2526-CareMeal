<?php
$activePage = $activePage ?? '';
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="sidebar-logo"><img src="/assets/logo.png" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;"></div>
    <div class="sidebar-brand">Care<span>Meal</span></div>
  </div>
  <nav class="sidebar-nav">
    <div class="sidebar-section">
      <div class="sidebar-section-title">ADMINISTRATION</div>
      <a href="/admin/dashboard.php" class="sidebar-link<?= $activePage === 'dashboard' ? ' active' : '' ?>"><span class="link-icon"><i class="fa-solid fa-house"></i></span> Tableau de bord</a>
      <a href="/admin/users.php" class="sidebar-link<?= $activePage === 'users' ? ' active' : '' ?>"><span class="link-icon"><i class="fa-solid fa-users"></i></span> Utilisateurs</a>
      <a href="/admin/partners.php" class="sidebar-link<?= $activePage === 'partners' ? ' active' : '' ?>"><span class="link-icon"><i class="fa-solid fa-store"></i></span> Partenaires</a>
      <a href="/admin/orders.php" class="sidebar-link<?= $activePage === 'orders' ? ' active' : '' ?>"><span class="link-icon"><i class="fa-solid fa-bag-shopping"></i></span> Commandes</a>
      <a href="/admin/restaurants.php" class="sidebar-link<?= $activePage === 'restaurants' ? ' active' : '' ?>"><span class="link-icon"><i class="fa-solid fa-shop"></i></span> Restaurants</a>
      <a href="/admin/planning_collecte.php" class="sidebar-link<?= $activePage === 'planning_collecte' ? ' active' : '' ?>"><span class="link-icon"><i class="fa-solid fa-truck"></i></span> Planning Collecte</a>
      <a href="/admin/preferences.php" class="sidebar-link<?= $activePage === 'preferences' ? ' active' : '' ?>"><span class="link-icon"><i class="fa-solid fa-sliders"></i></span> Pr&eacute;f&eacute;rences</a>
      <a href="/admin/categorie.php" class="sidebar-link<?= $activePage === 'categorie' ? ' active' : '' ?>"><span class="link-icon"><i class="fa-solid fa-tags"></i></span> Cat&eacute;gorie offres</a>
      <a href="/admin/offers.php" class="sidebar-link<?= $activePage === 'offers' ? ' active' : '' ?>"><span class="link-icon"><i class="fa-solid fa-box"></i></span> Offres</a>
      <a href="/admin/categories.php" class="sidebar-link<?= $activePage === 'categories' ? ' active' : '' ?>"><span class="link-icon"><i class="fa-solid fa-layer-group"></i></span> Categories</a>
      <a href="/admin/products.php" class="sidebar-link<?= $activePage === 'products' ? ' active' : '' ?>"><span class="link-icon"><i class="fa-solid fa-bowl-food"></i></span> Produits</a>
      <a href="/admin/publications.php" class="sidebar-link<?= in_array($activePage, ['publications', 'publication_create', 'publication_edit'], true) ? ' active' : '' ?>"><span class="link-icon"><i class="fa-solid fa-pen-nib"></i></span> Publications</a>
      <a href="/admin/events.php" class="sidebar-link<?= $activePage === 'events' ? ' active' : '' ?>"><span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> &Eacute;v&eacute;nements</a>
      <a href="/admin/participations.php" class="sidebar-link<?= $activePage === 'participations' ? ' active' : '' ?>"><span class="link-icon"><i class="fa-solid fa-ticket"></i></span> Participations</a>
      <a href="/admin/logs.php" class="sidebar-link<?= $activePage === 'logs' ? ' active' : '' ?>"><span class="link-icon"><i class="fa-solid fa-list-check"></i></span> Logs d'activit&eacute;</a>
    </div>
  </nav>
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="avatar" id="sidebar-user-avatar" style="background:linear-gradient(135deg,#EF4444,#F87171);">A</div>
      <div class="sidebar-user-info">
        <div class="sidebar-user-name" id="sidebar-user-name">Admin</div>
        <div class="sidebar-user-role" id="sidebar-user-role">Administrateur</div>
      </div>
      <button class="sidebar-logout" data-action="logout" title="Deconnexion"><i class="fa-solid fa-door-open"></i></button>
    </div>
  </div>
</aside>
