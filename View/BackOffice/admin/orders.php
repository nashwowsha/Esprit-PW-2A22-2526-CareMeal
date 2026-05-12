<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Gestion des commandes CareMeal - Administration">
  <title>Gestion Commandes - CareMeal Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/css/main.css">
  <link rel="stylesheet" href="/css/components.css">
  <link rel="stylesheet" href="/css/dashboard.css">
  <link rel="stylesheet" href="/css/theme-fix.css">
</head>
<body>
  <div class="dashboard-layout">
    <?php
      $activePage = 'orders';
      require dirname(__DIR__, 3) . '/admin/_admin_sidebar.php';
    ?>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <button class="menu-toggle" id="menu-toggle"><i class="fa-solid fa-bars"></i></button>
          <div class="page-title"><h2>Gestion Commandes</h2><p id="orders-count">0 commandes</p></div>
        </div>
        <div class="header-right">
          <a href="/View/FrontOffice/feed.php" title="Fil d'actualit�" style="width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--color-text-muted);font-size:1.1rem;text-decoration:none;transition:background .15s;" onmouseover="this.style.background='rgba(254,85,22,0.1)';this.style.color='#FE5516'" onmouseout="this.style.background='none';this.style.color='var(--color-text-muted)'"><i class="fa-solid fa-house"></i></a>
          <button class="header-notification"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button>
          <div class="avatar avatar-sm" id="header-avatar" style="background:linear-gradient(135deg,#EF4444,#F87171);">A</div>
        </div>
      </header>

      <div class="page-content">
        <!-- Orders Stats -->
        <div class="card animate-fade-in-up">
          <div style="display:flex;gap:12px;align-items:stretch;">
            <div style="flex:1;display:flex;gap:12px;">
              <div style="flex:1;padding:16px;border-radius:12px;border:1px solid var(--color-border);background:var(--color-bg-secondary);">
                <div style="font-size:0.85rem;color:var(--color-text-muted);">Total commandes</div>
                <div style="font-size:1.8rem;font-weight:800;" id="orders-total">0</div>
              </div>
              <div style="flex:1;padding:16px;border-radius:12px;border:1px solid var(--color-border);background:var(--color-bg-secondary);">
                <div style="font-size:0.85rem;color:var(--color-text-muted);">En attente</div>
                <div style="font-size:1.8rem;font-weight:800;" id="orders-pending">0</div>
              </div>
              <div style="flex:1;padding:16px;border-radius:12px;border:1px solid var(--color-border);background:var(--color-bg-secondary);">
                <div style="font-size:0.85rem;color:var(--color-text-muted);">Valid�es</div>
                <div style="font-size:1.8rem;font-weight:800;" id="orders-validated">0</div>
              </div>
              <div style="flex:1;padding:16px;border-radius:12px;border:1px solid var(--color-border);background:var(--color-bg-secondary);">
                <div style="font-size:0.85rem;color:var(--color-text-muted);">Retir�es</div>
                <div style="font-size:1.8rem;font-weight:800;" id="orders-picked">0</div>
              </div>
              <div style="flex:1;padding:16px;border-radius:12px;border:1px solid var(--color-border);background:var(--color-bg-secondary);">
                <div style="font-size:0.85rem;color:var(--color-text-muted);">Annul�es</div>
                <div style="font-size:1.8rem;font-weight:800;" id="orders-cancelled">0</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Orders Table -->
        <div class="card animate-fade-in-up" style="margin-top:1rem;">
          <div class="table-container">
            <table class="data-table" id="orders-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Type</th>
                  <th>Produit</th>
                  <th>Etudiant (ID)</th>
                  <th>Quantite</th>
                  <th>Prix unitaire</th>
                  <th>Total</th>
                  <th>Statut</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="orders-table-body">
                <!-- Loaded dynamically -->
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </main>
  </div>

  <script src="/js/app.js"></script>
  <script src="/js/components.js"></script>
  <script src="/js/admin.js"></script>
  <script>document.addEventListener('DOMContentLoaded', () => Admin.initOrders());</script>
</body>
</html>


