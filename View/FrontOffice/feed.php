<?php require_once dirname(__DIR__, 1) . '/session_check.php'; ?>
<?php require_once dirname(__DIR__, 2) . '/config/app.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fil d'actualit&eacute; - CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="<?= htmlspecialchars(caremeal_path('css/main.css'), ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(caremeal_path('css/components.css'), ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(caremeal_path('css/publications.css'), ENT_QUOTES, 'UTF-8') ?>?v=4">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: system-ui, -apple-system, sans-serif; background: #f0f2f5; color: #1a202c; min-height: 100vh; }

    .feed-nav {
      position: fixed; top: 0; left: 0; right: 0; height: 68px; z-index: 100;
      background: #fff; border-bottom: 1px solid #e2e8f0;
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 16px; gap: 12px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    }
    .nav-logo {
      display: flex; align-items: center; gap: 8px; font-size: 1.2rem;
      font-weight: 800; color: #1a202c; text-decoration: none;
    }
    .nav-logo span { color: #fe5516; }
    .nav-search { flex: 1; max-width: 380px; position: relative; }
    .nav-search i {
      position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
      color: #94a3b8; font-size: 0.8rem;
    }
    .nav-search input {
      width: 100%; padding: 10px 16px 10px 36px; border-radius: 20px; border: none;
      background: #f0f2f5; font-size: 0.9rem; outline: none; color: #1a202c;
    }
    .nav-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
    .nav-tab {
      width: 42px; height: 42px; border-radius: 10px; display: flex;
      align-items: center; justify-content: center; color: #64748b; font-size: 1.15rem;
      cursor: pointer; border: none; background: none; transition: all .2s;
      text-decoration: none; position: relative;
    }
    .nav-tab:hover { background: #f0f2f5; color: #1a202c; }
    .nav-tab .notif-badge {
      position: absolute; top: 8px; right: 8px; width: 8px; height: 8px;
      background: #fe5516; border-radius: 50%; border: 2px solid #fff;
    }
    .nav-space-btn {
      color: #fe5516; background: rgba(254,85,22,0.1); border-radius: 20px;
      padding: 8px 14px; font-size: 0.92rem; font-weight: 600; text-decoration: none;
      display: inline-flex; align-items: center; gap: 8px;
    }
    .nav-space-btn:hover { background: rgba(254,85,22,0.16); }

    .nav-avatar-wrap { position: relative; }
    .nav-avatar {
      width: 42px; height: 42px; border-radius: 50%;
      background: linear-gradient(135deg, #fe5516, #ff8a50);
      display: flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: 0.9rem; color: #fff; cursor: pointer;
    }
    .nav-avatar-menu {
      position: absolute; top: 52px; right: 0; background: #fff; border-radius: 12px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.15); border: 1px solid #e2e8f0;
      min-width: 220px; padding: 8px; display: none; z-index: 200;
    }
    .nav-avatar-menu.open { display: block; }
    .menu-user { padding: 12px; border-bottom: 1px solid #f0f2f5; margin-bottom: 4px; }
    .menu-user-name { font-weight: 700; font-size: 0.9rem; }
    .menu-user-role { font-size: 0.75rem; color: #64748b; }
    .nav-avatar-menu a, .nav-avatar-menu button {
      display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 8px;
      font-size: 0.85rem; color: #1a202c; text-decoration: none; width: 100%;
      border: none; background: none; cursor: pointer; font-family: inherit;
    }
    .nav-avatar-menu a:hover, .nav-avatar-menu button:hover { background: #f0f2f5; }
    .menu-danger { color: #ef4444 !important; }
    .menu-danger:hover { background: rgba(239,68,68,0.08) !important; }
    .menu-divider { border: none; border-top: 1px solid #f0f2f5; margin: 4px 0; }

    .feed-layout {
      max-width: 1280px; margin: 0 auto; padding: 84px 20px 30px;
      display: grid; grid-template-columns: minmax(0, 760px) 320px;
      justify-content: center; gap: 30px;
    }
    .feed-main { min-width: 0; display: flex; flex-direction: column; gap: 14px; }
    .feed-right { position: sticky; top: 84px; height: fit-content; display: flex; flex-direction: column; gap: 14px; }
    .widget { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; padding: 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
    .widget-title { font-size: 0.85rem; font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; color: #1a202c; }
    .widget-title i { color: #fe5516; }
    .widget-event-item { display: flex; gap: 10px; align-items: flex-start; padding: 8px 0; border-bottom: 1px solid #f0f2f5; }
    .widget-event-item:last-child { border-bottom: none; padding-bottom: 0; }
    .widget-date-box {
      width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #3b82f6, #60a5fa);
      display: flex; flex-direction: column; align-items: center; justify-content: center; color: #fff; flex-shrink: 0;
    }
    .widget-date-box .wd { font-size: 1rem; font-weight: 800; line-height: 1; }
    .widget-date-box .wm { font-size: 0.52rem; font-weight: 600; text-transform: uppercase; }
    .widget-ev-title { font-size: 0.8rem; font-weight: 600; margin-bottom: 2px; }
    .widget-ev-loc { font-size: 0.7rem; color: #94a3b8; }
    .widget-see-all { display: block; text-align: center; font-size: 0.76rem; color: #fe5516; font-weight: 600; margin-top: 10px; text-decoration: none; }

    .community-stat { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; }
    .community-stat:not(:last-child) { border-bottom: 1px solid #f0f2f5; }
    .community-stat-label { font-size: 0.78rem; color: #64748b; display: flex; align-items: center; gap: 6px; }
    .community-stat-value { font-weight: 700; font-size: 0.88rem; }
    .partner-item { display: flex; align-items: center; gap: 10px; padding: 7px 0; border-bottom: 1px solid #f0f2f5; }
    .partner-item:last-child { border-bottom: none; }
    .partner-dot { width: 8px; height: 8px; border-radius: 50%; background: #22c55e; flex-shrink: 0; }
    .partner-name { font-size: 0.8rem; font-weight: 600; }
    .partner-type { font-size: 0.7rem; color: #94a3b8; }

    .pub-toolbar,
    .pub-compose,
    .pub-card,
    .pub-feed-stats,
    .pub-stats-section {
      background: #fff !important;
      border-color: #e2e8f0 !important;
      box-shadow: 0 1px 4px rgba(0,0,0,0.04) !important;
    }
    .pub-card,
    .pub-compose,
    .pub-toolbar {
      color: #1a202c !important;
    }

    @media (max-width: 1100px) {
      .feed-layout { grid-template-columns: minmax(0, 760px); }
      .feed-right { display: none; }
    }
    @media (max-width: 700px) {
      .nav-search { max-width: 180px; }
      .nav-space-btn { padding: 8px 10px; font-size: 0.82rem; }
    }
    @media (max-width: 520px) {
      .nav-search { display: none; }
    }
  </style>
</head>
<body>
  <nav class="feed-nav">
    <div style="display:flex;align-items:center;gap:12px;">
      <a href="<?= htmlspecialchars(caremeal_path('View/FrontOffice/feed.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-logo">
        <img src="<?= htmlspecialchars(caremeal_path('assets/logo.png'), ENT_QUOTES, 'UTF-8') ?>" alt="CareMeal" style="height:30px;width:auto;">
        Care<span>Meal</span>
      </a>
      <div class="nav-search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" placeholder="Rechercher dans les publications..." id="feed-search" autocomplete="off">
      </div>
    </div>

    <div class="nav-actions">
      <a href="<?= htmlspecialchars(caremeal_path('View/FrontOffice/student/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>" id="top-dash-btn" class="nav-space-btn" title="Mon espace">
        <i class="fa-solid fa-chart-line"></i> Espace &Eacute;tudiant
      </a>
      <button class="nav-tab" id="btn-notif" title="Notifications">
        <i class="fa-solid fa-bell"></i>
        <span class="notif-badge"></span>
      </button>
      <div class="nav-avatar-wrap">
        <div class="nav-avatar" id="nav-avatar" onclick="Feed.toggleAvatarMenu(event)" title="Mon compte">
          <span id="nav-avatar-initials">?</span>
        </div>
        <div class="nav-avatar-menu" id="avatar-menu">
          <div class="menu-user">
            <div class="menu-user-name" id="menu-user-name">Chargement...</div>
            <div class="menu-user-role" id="menu-user-role">&Eacute;tudiant</div>
          </div>
          <a href="<?= htmlspecialchars(caremeal_path('View/FrontOffice/student/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>" id="avatar-dash-link">
            <i class="fa-solid fa-chart-line"></i> Mon espace
          </a>
          <hr class="menu-divider">
          <button class="menu-danger" onclick="Feed.logout()">
            <i class="fa-solid fa-arrow-right-from-bracket"></i> D&eacute;connexion
          </button>
        </div>
      </div>
    </div>
  </nav>

  <div class="feed-layout">
    <main class="feed-main">
      <div id="pub-toolbar-container"></div>
      <div id="pub-compose-container"></div>
      <div id="pub-feed-container">
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;text-align:center;color:#64748b;">
          <i class="fa-solid fa-spinner fa-spin" style="margin-right:8px;"></i> Chargement des publications...
        </div>
      </div>
    </main>

    <aside class="feed-right">
      <div class="widget">
        <div class="widget-title"><i class="fa-solid fa-calendar-days"></i> &Eacute;v&eacute;nements &agrave; venir</div>
        <div id="widget-events-list">
          <div style="font-size:0.78rem;color:#94a3b8;text-align:center;padding:10px;">Chargement...</div>
        </div>
        <a href="#" class="widget-see-all" onclick="Feed.goToEvents(); return false;">Voir tous les &eacute;v&eacute;nements -&gt;</a>
      </div>

      <div class="widget">
        <div class="widget-title"><i class="fa-solid fa-fire"></i> <span id="widget-offers-title">Offres du jour</span></div>
        <div id="widget-offers-list">
          <div style="font-size:0.78rem;color:#94a3b8;text-align:center;padding:10px;">Chargement...</div>
        </div>
      </div>

      <div class="widget">
        <div class="widget-title"><i class="fa-solid fa-users"></i> Communaut&eacute;</div>
        <div class="community-stat">
          <span class="community-stat-label"><i class="fa-solid fa-user-group" style="color:#3b82f6;"></i> Membres actifs</span>
          <span class="community-stat-value" id="stat-members">0</span>
        </div>
        <div class="community-stat">
          <span class="community-stat-label"><i class="fa-solid fa-fire" style="color:#fe5516;"></i> Publications</span>
          <span class="community-stat-value" id="stat-posts">0</span>
        </div>
        <div class="community-stat">
          <span class="community-stat-label"><i class="fa-solid fa-calendar-check" style="color:#22c55e;"></i> &Eacute;v&eacute;nements planifi&eacute;s</span>
          <span class="community-stat-value" id="stat-events">0</span>
        </div>
        <div class="community-stat">
          <span class="community-stat-label"><i class="fa-solid fa-heart" style="color:#ef4444;"></i> R&eacute;actions</span>
          <span class="community-stat-value" id="stat-likes">0</span>
        </div>
      </div>

      <div class="widget">
        <div class="widget-title"><i class="fa-solid fa-store"></i> Partenaires actifs</div>
        <div id="widget-partners-list">
          <div style="font-size:0.78rem;color:#94a3b8;text-align:center;padding:10px;">Chargement...</div>
        </div>
      </div>
    </aside>
  </div>

  <script src="<?= htmlspecialchars(caremeal_path('js/app.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
  <script src="<?= htmlspecialchars(caremeal_path('js/components.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
  <script src="<?= htmlspecialchars(caremeal_path('js/publications.js'), ENT_QUOTES, 'UTF-8') ?>?v=4"></script>
  <script>
    // Fix ciblé feed: corriger les variantes mojibake de "à l'instant" uniquement sur cette page.
    function normalizeInstantLabel(value) {
      const text = String(value || '');
      if (/instant/i.test(text) && /Ã|Â|�/.test(text)) return "à l'instant";
      if (/^\s*a\s+l['’]instant\s*$/i.test(text)) return "à l'instant";
      return text;
    }

    if (typeof App !== 'undefined' && typeof App.timeAgo === 'function') {
      const originalAppTimeAgo = App.timeAgo.bind(App);
      App.timeAgo = function patchedTimeAgo(dateStr) {
        return normalizeInstantLabel(originalAppTimeAgo(dateStr));
      };
    }

    if (typeof Publications !== 'undefined' && typeof Publications.timeAgo === 'function') {
      const originalPublicationsTimeAgo = Publications.timeAgo.bind(Publications);
      Publications.timeAgo = function patchedPublicationsTimeAgo(dateStr) {
        return normalizeInstantLabel(originalPublicationsTimeAgo(dateStr));
      };
    }

    const Feed = {
      async init() {
        if (!App.requireAuth(['student', 'partner'])) return;
        await App.refreshCurrentUser();
        const user = App.getCurrentUser();
        if (!user) return;

        this.initUserArea(user);
        this.bindMenuClose();
        await this.initPublications();
        this.bindTopSearchToPublications();
        await this.loadWidgets();
      },

      initUserArea(user) {
        const fullName = user.prenom
          ? (user.prenom + ' ' + (user.nom || '')).trim()
          : (user.name || 'Utilisateur');
        const initials = App.getInitials(fullName);
        const roleLabels = { student: 'Étudiant', partner: 'Partenaire' };
        const roleLabel = roleLabels[user.role] || 'Utilisateur';

        const avatar = document.getElementById('nav-avatar-initials');
        if (avatar) avatar.textContent = initials;
        const menuName = document.getElementById('menu-user-name');
        if (menuName) menuName.textContent = fullName;
        const menuRole = document.getElementById('menu-user-role');
        if (menuRole) menuRole.textContent = roleLabel;

        const topDashBtn = document.getElementById('top-dash-btn');
        const avatarDashLink = document.getElementById('avatar-dash-link');
        if (user.role === 'partner') {
          if (topDashBtn) {
            topDashBtn.href = App.apiUrl('View/FrontOffice/partner/dashboard.php');
            topDashBtn.innerHTML = '<i class="fa-solid fa-store"></i> Espace Partenaire';
          }
          if (avatarDashLink) avatarDashLink.href = App.apiUrl('View/FrontOffice/partner/dashboard.php');
        } else {
          if (topDashBtn) {
            topDashBtn.href = App.apiUrl('View/FrontOffice/student/dashboard.php');
            topDashBtn.innerHTML = '<i class="fa-solid fa-chart-line"></i> Espace Étudiant';
          }
          if (avatarDashLink) avatarDashLink.href = App.apiUrl('View/FrontOffice/student/dashboard.php');
        }
      },

      bindMenuClose() {
        document.addEventListener('click', (e) => {
          const wrap = document.querySelector('.nav-avatar-wrap');
          if (!wrap) return;
          if (!wrap.contains(e.target)) {
            document.getElementById('avatar-menu')?.classList.remove('open');
          }
        });
      },

      async initPublications() {
        if (typeof Publications === 'undefined') {
          const root = document.getElementById('pub-feed-container');
          if (root) {
            root.innerHTML = '<div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;color:#ef4444;">Module publications introuvable.</div>';
          }
          return;
        }
        await Publications.initPage();
      },

      bindTopSearchToPublications() {
        const topInput = document.getElementById('feed-search');
        if (!topInput) return;
        topInput.addEventListener('input', () => {
          const pubSearch = document.getElementById('pub-search-input');
          if (!pubSearch) return;
          pubSearch.value = topInput.value;
          pubSearch.dispatchEvent(new Event('input', { bubbles: true }));
        });
      },

      toggleAvatarMenu(e) {
        e.stopPropagation();
        document.getElementById('avatar-menu')?.classList.toggle('open');
      },

      logout() {
        if (typeof App.logout === 'function') App.logout();
      },

      goToEvents() {
        const user = App.getCurrentUser();
        if (!user) return;
        if (user.role === 'partner') {
          window.location.href = App.apiUrl('View/FrontOffice/partner/events.php');
        } else {
          window.location.href = App.apiUrl('View/FrontOffice/student/events.php');
        }
      },

      async loadWidgets() {
        await this.loadEventsWidget();
        await this.loadOffersWidget();
        await this.loadPartnersWidget();
        await this.updateCommunityStats();
      },

      async loadOffersWidget() {
        const el = document.getElementById('widget-offers-list');
        const titleEl = document.getElementById('widget-offers-title');
        if (!el) return;

        let offers = [];
        let scope = 'student';
        try {
          const res = await fetch(App.apiUrl('api/feed-offers.php'), {
            method: 'GET',
            credentials: 'same-origin'
          });
          const data = await res.json();
          if (data && data.success && Array.isArray(data.offers)) {
            offers = data.offers;
            scope = data.scope || scope;
          }
        } catch (_err) {}

        if (titleEl) titleEl.textContent = scope === 'partner' ? 'Mes offres' : 'Offres du jour';
        if (!offers.length) {
          el.innerHTML = '<div style="font-size:0.78rem;color:#94a3b8;text-align:center;padding:8px;">Aucune offre disponible</div>';
          return;
        }

        el.innerHTML = offers.slice(0, 3).map((o) => `
          <div style="display:flex;gap:10px;padding:8px 0;border-bottom:1px solid #f0f2f5;">
            <div style="font-size:1.2rem;display:flex;align-items:center;justify-content:center;background:#fff7f4;border-radius:8px;width:40px;height:40px;"><i class="fa-solid fa-utensils" style="color:#fe5516;"></i></div>
            <div style="flex:1;">
              <div style="font-size:0.85rem;font-weight:700;">${o.title || ''}</div>
              <div style="font-size:0.75rem;color:#64748b;">${scope === 'partner' ? (o.category || 'Mes offres') : (o.partnerName || 'Partenaire')}</div>
              <div style="font-size:0.8rem;font-weight:600;color:#fe5516;">
                ${Number(o.price || 0).toFixed(1)} DT
                <span style="text-decoration:line-through;color:#94a3b8;font-size:0.7rem;margin-left:4px;">
                  ${Number(o.originalPrice || 0).toFixed(1)} DT
                </span>
              </div>
            </div>
          </div>
        `).join('');
      },

      async loadEventsWidget() {
        const el = document.getElementById('widget-events-list');
        if (!el) return;

        let events = [];
        try {
          const user = App.getCurrentUser();
          const res = await fetch(App.apiUrl('Controller/EventParticipationController.php'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'get_events', student_id: user ? (user.id || user.user_id) : 0 })
          });
          const data = await res.json();
          if (data.success) {
            events = (data.events || []).map((e) => ({
              title: e.titre,
              date: e.date_evenement,
              location: e.lieu
            }));
          }
        } catch (_err) {}

        events = events.sort((a, b) => new Date(a.date) - new Date(b.date)).slice(0, 4);
        if (!events.length) {
          el.innerHTML = '<div style="font-size:0.78rem;color:#94a3b8;text-align:center;padding:8px;">Aucun événement à venir</div>';
          return;
        }

        el.innerHTML = events.map((e) => {
          const d = new Date(e.date);
          return `
            <div class="widget-event-item">
              <div class="widget-date-box">
                <span class="wd">${d.getDate()}</span>
                <span class="wm">${d.toLocaleDateString('fr-FR', { month: 'short' })}</span>
              </div>
              <div>
                <div class="widget-ev-title">${e.title || ''}</div>
                <div class="widget-ev-loc"><i class="fa-solid fa-location-dot" style="margin-right:3px;"></i>${e.location || ''}</div>
              </div>
            </div>
          `;
        }).join('');
      },

      async loadPartnersWidget() {
        const el = document.getElementById('widget-partners-list');
        if (!el) return;

        let partners = [];
        try {
          const res = await fetch(App.apiUrl('Controller/UserController.php'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'get_users' })
          });
          const rawText = await res.text();
          const jsonStart = rawText.indexOf('{');
          const jsonPayload = jsonStart >= 0 ? rawText.slice(jsonStart) : rawText;
          const data = JSON.parse(jsonPayload);
          if (data && data.success && Array.isArray(data.users)) {
            partners = data.users.filter((u) => u.role === 'partner' && u.status === 'active');
          }
        } catch (_err) {}

        if (!partners.length) {
          const users = typeof App.getUsers === 'function' ? App.getUsers() : [];
          partners = users.filter((u) => u.role === 'partner' && u.status === 'active');
        }

        if (!partners.length) {
          el.innerHTML = `
            <div class="partner-item"><div class="partner-dot"></div><div><div class="partner-name">La Baguette Doree</div><div class="partner-type">Boulangerie</div></div></div>
            <div class="partner-item"><div class="partner-dot"></div><div><div class="partner-name">Chez Sami</div><div class="partner-type">Restaurant</div></div></div>
            <div class="partner-item"><div class="partner-dot"></div><div><div class="partner-name">Green Bowl Cafe</div><div class="partner-type">Cafeteria</div></div></div>
          `;
          return;
        }

        el.innerHTML = partners.slice(0, 5).map((p) => {
          const fullName = [p.lastName, p.firstName].filter(Boolean).join(' ').trim();
          const displayName = (fullName || p.establishmentName || p.nom_entreprise || p.name || '').trim();
          return `
          <div class="partner-item">
            <div class="partner-dot"></div>
            <div>
              <div class="partner-name">${displayName}</div>
              <div class="partner-type">${p.type || 'Partenaire'}</div>
            </div>
          </div>
        `;
        }).join('');
      },

      async updateCommunityStats() {
        const setText = (id, value) => {
          const el = document.getElementById(id);
          if (el) el.textContent = value;
        };

        const users = typeof App.getUsers === 'function' ? App.getUsers() : [];
        const activeMembers = users.filter((u) => (u.role === 'student' || u.role === 'partner') && u.status !== 'banned').length;
        setText('stat-members', activeMembers > 0 ? activeMembers : '350+');

        let plannedEvents = 0;
        if (typeof App.getEvents === 'function') {
          plannedEvents = (App.getEvents() || []).filter((e) => {
            const s = String(e.status || '').toLowerCase();
            return s.includes('planifie') || s.includes('en cours') || s.includes('valid');
          }).length;
        }
        setText('stat-events', plannedEvents);

        if (typeof Publications === 'undefined' || typeof Publications.getAll !== 'function') {
          return;
        }

        try {
          const publications = await Publications.getAll();
          const totalReactions = publications.reduce((sum, p) => sum + ((p.reactions || []).length), 0);
          setText('stat-posts', publications.length);
          setText('stat-likes', totalReactions);
        } catch (_err) {
          setText('stat-posts', 0);
          setText('stat-likes', 0);
        }
      }
    };

    document.addEventListener('DOMContentLoaded', async () => {
      App.init();
      await Feed.init();
    });
  </script>
</body>
</html>







