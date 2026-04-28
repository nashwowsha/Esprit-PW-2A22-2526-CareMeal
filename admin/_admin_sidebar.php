<?php
$activePage = $activePage ?? '';

if (!function_exists('admin_route_href')) {
    function admin_route_href($baseName)
    {
        return $baseName . '.php';
    }
}
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="sidebar-logo"><img src="../assets/logo.png" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;"></div>
    <div class="sidebar-brand">Care<span>Meal</span></div>
  </div>
  <nav class="sidebar-nav">
    <div class="sidebar-section">
      <div class="sidebar-section-title">Administration</div>
      <a href="<?= htmlspecialchars(admin_route_href('dashboard'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-link<?= $activePage === 'dashboard' ? ' active' : '' ?>"><span class="link-icon"><i class="fa-solid fa-chart-column"></i></span> Vue globale</a>
      <a href="<?= htmlspecialchars(admin_route_href('users'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-link<?= $activePage === 'users' ? ' active' : '' ?>"><span class="link-icon"><i class="fa-solid fa-users"></i></span> Utilisateurs</a>
      <a href="partners.php" class="sidebar-link<?= $activePage === 'partners' ? ' active' : '' ?>"><span class="link-icon"><i class="fa-solid fa-store"></i></span> Partenaires</a>
      <a href="restaurants.php" class="sidebar-link<?= $activePage === 'restaurants' ? ' active' : '' ?>"><span class="link-icon"><i class="fa-solid fa-shop"></i></span> Restaurants</a>
      <a href="planning_collecte.php" class="sidebar-link<?= $activePage === 'planning_collecte' ? ' active' : '' ?>"><span class="link-icon"><i class="fa-solid fa-truck"></i></span> Planning Collecte</a>
      <a href="preferences.php" class="sidebar-link<?= $activePage === 'preferences' ? ' active' : '' ?>"><span class="link-icon"><i class="fa-solid fa-sliders"></i></span> Pr&eacute;f&eacute;rences</a>
      <a href="events.php" class="sidebar-link<?= $activePage === 'events' ? ' active' : '' ?>"><span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> &Eacute;v&eacute;nements</a>
      <a href="<?= htmlspecialchars(admin_route_href('logs'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-link<?= $activePage === 'logs' ? ' active' : '' ?>"><span class="link-icon"><i class="fa-solid fa-clipboard-list"></i></span> Logs d'activit&eacute;</a>
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
