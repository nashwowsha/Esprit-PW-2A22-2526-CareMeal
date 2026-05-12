<?php require_once dirname(__DIR__, 2) . '/session_check.php'; ?>
<?php require_once dirname(__DIR__, 3) . '/config/app.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Vos points de fidélité et récompenses CareMeal.">
  <title>Mes Points �?? CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="<?= htmlspecialchars(caremeal_path('css/main.css'), ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(caremeal_path('css/components.css'), ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(caremeal_path('css/dashboard.css'), ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(caremeal_path('css/theme-fix.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
  <div class="dashboard-layout">
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <div class="sidebar-logo"><img src="<?= htmlspecialchars(caremeal_path('assets/logo.png'), ENT_QUOTES, 'UTF-8') ?>" alt="CareMeal" style="max-width:100%;max-height:100%;object-fit:contain;"></div>
        <div class="sidebar-brand">Care<span>Meal</span></div>
      </div>
      <nav class="sidebar-nav">
        <div class="sidebar-section">
          <div class="sidebar-section-title">Menu</div>
          <a href="dashboard.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-house"></i></span> Accueil</a>
          <a href="profile.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-user"></i></span> Mon Profil</a>
          <a href="preferences.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-utensils"></i></span> Pr&eacute;f&eacute;rences</a>
          <a href="orders.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-box"></i></span> Mes Commandes</a>
          <a href="mes_collectes.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-box-archive"></i></span> Mes Collectes</a>
</div>
        <div class="sidebar-section">
          <div class="sidebar-section-title">Paramètres</div>
          <a href="settings.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-gear"></i></span> Paramètres</a>
        </div>
      </nav>
      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="avatar" id="sidebar-user-avatar">AA</div>
          <div class="sidebar-user-info">
            <div class="sidebar-user-name" id="sidebar-user-name">Utilisateur</div>
            <div class="sidebar-user-role" id="sidebar-user-role">Étudiant</div>
          </div>
          <button class="sidebar-logout" data-action="logout" title="Déconnexion"><i class="fa-solid fa-arrow-right-from-bracket"></i></button>
        </div>
      </div>
    </aside>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle"><i class="fa-solid fa-bars"></i></button>
          <div class="page-title"><h2>Mes Points</h2><p>Programme de fidélité</p></div>
        </div>
        <div class="header-right">
          <a href="<?= htmlspecialchars(caremeal_path('View/FrontOffice/feed.php'), ENT_QUOTES, 'UTF-8') ?>" title="Retour au Feed" style="display:flex; align-items:center; color:#FE5516; background:rgba(254,85,22,0.1); border-radius:20px; padding:6px 16px; font-size:0.95rem; font-weight:600; text-decoration:none; margin-right:8px; transition:all 0.2s;">
            <i class="fa-solid fa-house" style="margin-right:8px;"></i> Retour au Feed
          </a>
          <button class="header-notification"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button>
          <div class="avatar avatar-sm" id="header-avatar">AA</div>
        </div>
      </header>

      <div class="page-content">
        <!-- Points overview -->
        <div class="card animate-fade-in-up" style="text-align:center;padding:48px;margin-bottom:32px;position:relative;overflow:hidden;">
          <div style="position:absolute;top:-50px;right:-50px;width:200px;height:200px;border-radius:50%;background:radial-gradient(circle,var(--color-primary-glow),transparent);pointer-events:none;"></div>
          <div style="position:relative;z-index:2;">
            <div class="level-badge" style="margin-bottom:16px;font-size:1rem;padding:8px 20px;" id="level-name">Débutant <i class="fa-solid fa-star"></i></div>
            <div style="font-size:4rem;font-weight:800;color:var(--color-white);margin-bottom:4px;" id="total-points">0</div>
            <div style="color:var(--color-text-muted);font-size:0.9rem;margin-bottom:24px;">points accumulés</div>

            <div style="max-width:400px;margin:0 auto;">
              <div style="display:flex;justify-content:space-between;font-size:0.8rem;color:var(--color-text-muted);margin-bottom:8px;">
                <span>Niveau <span id="current-level">1</span></span>
                <span id="points-next">100 points pour le niveau suivant</span>
              </div>
              <div class="progress-bar">
                <div class="progress-fill" id="level-progress" style="width:0%"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- How to earn -->
        <div class="section animate-fade-in-up stagger-1">
          <h3 style="margin-bottom:20px;"><i class="fa-solid fa-star"></i> Comment gagner des points</h3>
          <div class="grid grid-3 gap-4">
            <div class="card" style="text-align:center;">
              <div style="font-size:2.5rem;margin-bottom:12px;"><i class="fa-solid fa-cart-shopping"></i></div>
              <h4 style="font-size:0.95rem;margin-bottom:4px;">Commander</h4>
              <p style="font-size:0.8rem;">+20 pts par commande</p>
            </div>
            <div class="card" style="text-align:center;">
              <div style="font-size:2.5rem;margin-bottom:12px;"><i class="fa-solid fa-star"></i></div>
              <h4 style="font-size:0.95rem;margin-bottom:4px;">Laisser un avis</h4>
              <p style="font-size:0.8rem;">+10 pts par avis</p>
            </div>
            <div class="card" style="text-align:center;">
              <div style="font-size:2.5rem;margin-bottom:12px;"><i class="fa-solid fa-user-plus"></i></div>
              <h4 style="font-size:0.95rem;margin-bottom:4px;">Parrainer un ami</h4>
              <p style="font-size:0.8rem;">+50 pts par parrainage</p>
            </div>
          </div>
        </div>

        <!-- Available rewards -->
        <div class="section animate-fade-in-up stagger-2">
          <h3 style="margin-bottom:20px;"><i class="fa-solid fa-gift"></i> Récompenses disponibles</h3>
          <div style="display:flex;flex-direction:column;gap:12px;">
            <div class="reward-card">
              <div class="reward-icon"><i class="fa-solid fa-gift"></i></div>
              <div class="reward-info">
                <h4>Café offert</h4>
                <p>Un café gratuit chez nos partenaires</p>
              </div>
              <div class="reward-cost">100 pts</div>
            </div>
            <div class="reward-card">
              <div class="reward-icon"><i class="fa-solid fa-house"></i> </div>
              <div class="reward-info">
                <h4>Viennoiserie gratuite</h4>
                <p>Une viennoiserie offerte à votre prochaine commande</p>
              </div>
              <div class="reward-cost">200 pts</div>
            </div>
            <div class="reward-card">
              <div class="reward-icon"><i class="fa-solid fa-utensils"></i></div>
              <div class="reward-info">
                <h4>Panier surprise XL</h4>
                <p>Un panier surprise double portion gratuit</p>
              </div>
              <div class="reward-cost">400 pts</div>
            </div>
            <div class="reward-card">
              <div class="reward-icon"><i class="fa-solid fa-utensils"></i></div>
              <div class="reward-info">
                <h4>Repas complet offert</h4>
                <p>Un repas complet gratuit chez un partenaire au choix</p>
              </div>
              <div class="reward-cost">800 pts</div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="<?= htmlspecialchars(caremeal_path('js/app.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
  <script src="<?= htmlspecialchars(caremeal_path('js/components.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
  <script src="<?= htmlspecialchars(caremeal_path('js/student.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
  <script src="<?= htmlspecialchars(caremeal_path('js/student-layout.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
  <script src="<?= htmlspecialchars(caremeal_path('js/student-voice-assistant.js'), ENT_QUOTES, 'UTF-8') ?>?v=20260508"></script>
  <script>document.addEventListener('DOMContentLoaded', () => Student.initPoints());</script>
</body>
</html>















