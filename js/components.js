/* ============================================
   CAREMEAL Ã¢â‚¬â€ COMPONENTS JS
   components.js Ã¢â‚¬â€ Sidebar, Modals, Toasts
   ============================================ */

const Components = {
  adminSidebarObserver: null,
  adminSidebarRepairing: false,

  getAdminLinkExtension() {
    return '.php';
  },

  normalizeAdminSidebarLabels() {
    const path = window.location.pathname.toLowerCase();
    if (!path.includes('/admin/')) return;

    const labelByRoute = {
      'preferences.php': 'Pr&eacute;f&eacute;rences',
      'preferences.html': 'Pr&eacute;f&eacute;rences',
      'events.php': '&Eacute;v&eacute;nements',
      'events.html': '&Eacute;v&eacute;nements',
      'logs.php': "Logs d'activit&eacute;",
      'logs.html': "Logs d'activit&eacute;"
    };

    document.querySelectorAll('.sidebar-nav .sidebar-link').forEach((link) => {
      const href = (link.getAttribute('href') || '').toLowerCase();
      const label = labelByRoute[href];
      if (!label) return;

      const icon = link.querySelector('.link-icon');
      if (!icon) return;

      link.innerHTML = `${icon.outerHTML} ${label}`;
    });
  },

  ensureAdminSidebarCompleteness() {
    const path = window.location.pathname.toLowerCase();
    if (!path.includes('/admin/')) return;

    const sidebarNav = document.querySelector('.sidebar-nav');
    if (!sidebarNav) return;

    const ext = this.getAdminLinkExtension();
    const section = sidebarNav.querySelector('.sidebar-section');
    if (!section) return;

    const currentPage = path.split('/').pop();
    const currentBase = (currentPage || '').replace(/\.php$|\.html$/i, '');
    const routes = [
      { base: 'dashboard', label: 'Vue globale', icon: 'fa-chart-column' },
      { base: 'users', label: 'Utilisateurs', icon: 'fa-users' },
      { base: 'partners', label: 'Partenaires', icon: 'fa-store' },
      { base: 'restaurants', label: 'Restaurants', icon: 'fa-shop', forcePhp: true },
      { base: 'planning_collecte', label: 'Planning Collecte', icon: 'fa-truck', forcePhp: true },
      { base: 'preferences', label: 'Pr&eacute;f&eacute;rences', icon: 'fa-sliders', forcePhp: true },
      { base: 'events', label: '&Eacute;v&eacute;nements', icon: 'fa-calendar-day', forcePhp: true },
      { base: 'logs', label: "Logs d'activit&eacute;", icon: 'fa-clipboard-list' }
    ];

    const restaurantsLink = section.querySelector('.sidebar-link[href="restaurants.php"], .sidebar-link[href$="/restaurants.php"], .sidebar-link[href$="restaurants.php"]');
    if (restaurantsLink) {
      restaurantsLink.classList.remove('hidden');
      restaurantsLink.removeAttribute('hidden');
      restaurantsLink.style.display = '';
    }

    const hasRestaurants = !!restaurantsLink;
    const linksCount = section.querySelectorAll('.sidebar-link').length;
    if (hasRestaurants && linksCount >= 8) return;

    let html = '<div class="sidebar-section-title">Administration</div>';
    routes.forEach((route) => {
      const href = route.forcePhp ? `${route.base}.php` : `${route.base}${ext}`;
      const isActive = currentBase === route.base ? ' active' : '';
      html += `<a href="${href}" class="sidebar-link${isActive}"><span class="link-icon"><i class="fa-solid ${route.icon}"></i></span> ${route.label}</a>`;
    });
    section.innerHTML = html;
  },

  setupAdminSidebarWatcher() {
    const path = window.location.pathname.toLowerCase();
    if (!path.includes('/admin/')) return;

    const section = document.querySelector('.sidebar-nav .sidebar-section');
    if (!section) return;

    if (this.adminSidebarObserver) {
      this.adminSidebarObserver.disconnect();
      this.adminSidebarObserver = null;
    }

    this.adminSidebarObserver = new MutationObserver(() => {
      if (this.adminSidebarRepairing) return;
      this.adminSidebarRepairing = true;

      setTimeout(() => {
        this.ensureAdminSidebarCompleteness();
        this.normalizeAdminSidebarLabels();
        this.adminSidebarRepairing = false;
      }, 0);
    });

    this.adminSidebarObserver.observe(section, { childList: true, subtree: true, attributes: true, attributeFilter: ['class', 'style', 'hidden'] });
  },

  ensureStudentSidebarCompleteness() {
    const path = window.location.pathname.toLowerCase();
    if (!path.includes('/student/')) return;

    const sidebarNav = document.querySelector('.sidebar-nav');
    if (!sidebarNav) return;

    const url = new URL(window.location.href);
    const queryUserId = parseInt(url.searchParams.get('id_user') || '0', 10);
    const appUser = (typeof App !== 'undefined' && App && typeof App.getCurrentUser === 'function')
      ? App.getCurrentUser()
      : null;
    const appUserId = appUser ? parseInt(appUser.id || '0', 10) : 0;
    const resolvedId = queryUserId > 0 ? queryUserId : (appUserId > 0 ? appUserId : 1);

    const sections = Array.from(sidebarNav.querySelectorAll('.sidebar-section'));
    let menuSection = sections.find((section) => {
      const title = section.querySelector('.sidebar-section-title');
      if (!title) return false;
      const txt = (title.textContent || '').toLowerCase();
      return txt.indexOf('menu') !== -1;
    });
    let settingsSection = sections.find((section) => {
      const title = section.querySelector('.sidebar-section-title');
      if (!title) return false;
      const txt = (title.textContent || '').toLowerCase();
      return txt.indexOf('param') !== -1 || txt.indexOf('setting') !== -1;
    });

    if (!menuSection) {
      menuSection = document.createElement('div');
      menuSection.className = 'sidebar-section';
      sidebarNav.prepend(menuSection);
    }

    if (!settingsSection) {
      settingsSection = document.createElement('div');
      settingsSection.className = 'sidebar-section';
      sidebarNav.appendChild(settingsSection);
    }

    const menuRoutes = [
      { href: 'dashboard.html', icon: 'fa-house', label: 'Accueil' },
      { href: 'profile.html', icon: 'fa-user', label: 'Mon Profil' },
      { href: `preferences.php?id_user=${resolvedId}`, icon: 'fa-utensils', label: 'Préférences', base: 'preferences.php' },
      { href: 'events.html', icon: 'fa-calendar-day', label: 'Événements' },
      { href: 'orders.html', icon: 'fa-box', label: 'Mes Commandes' },
      { href: `mes_collectes.php?id_user=${resolvedId}`, icon: 'fa-box-archive', label: 'Mes Collectes', base: 'mes_collectes.php' }
    ];

    const ensureTitle = (section, text) => {
      let title = section.querySelector('.sidebar-section-title');
      if (!title) {
        title = document.createElement('div');
        title.className = 'sidebar-section-title';
        section.prepend(title);
      }
      title.textContent = text;
    };

    const getRouteBase = (route) => (route.base || route.href).split('?')[0].toLowerCase();
    const getLinkBase = (link) => ((link.getAttribute('href') || '').replace('./', '').toLowerCase().split('?')[0]);
    const allMenuLinks = () => Array.from(menuSection.querySelectorAll('.sidebar-link'));
    const allSettingsLinks = () => Array.from(settingsSection.querySelectorAll('.sidebar-link'));

    ensureTitle(menuSection, 'Menu');
    const orderedMenuLinks = menuRoutes.map((route) => {
      const routeBase = getRouteBase(route);
      let link = allMenuLinks().find((candidate) => getLinkBase(candidate) === routeBase);
      if (!link) {
        link = document.createElement('a');
        link.className = 'sidebar-link';
      }
      link.setAttribute('href', route.href);
      link.innerHTML = `<span class="link-icon"><i class="fa-solid ${route.icon}"></i></span> ${route.label}`;
      return link;
    });

    allMenuLinks().forEach((link) => {
      if (!orderedMenuLinks.includes(link)) {
        link.remove();
      }
    });
    orderedMenuLinks.forEach((link) => menuSection.appendChild(link));

    ensureTitle(settingsSection, 'Paramètres');
    const settingsRoute = { href: 'settings.html', icon: 'fa-gear', label: 'Paramètres', base: 'settings.html' };
    const settingsBase = getRouteBase(settingsRoute);
    let settingsLink = allSettingsLinks().find((candidate) => getLinkBase(candidate) === settingsBase);
    if (!settingsLink) {
      settingsLink = document.createElement('a');
      settingsLink.className = 'sidebar-link';
    }
    settingsLink.setAttribute('href', settingsRoute.href);
    settingsLink.innerHTML = `<span class="link-icon"><i class="fa-solid ${settingsRoute.icon}"></i></span> ${settingsRoute.label}`;
    allSettingsLinks().forEach((link) => {
      if (link !== settingsLink) {
        link.remove();
      }
    });
    settingsSection.appendChild(settingsLink);
  },

  // --- Sidebar ---
  initSidebar() {
    const toggle = document.getElementById('menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    this.ensureAdminSidebarCompleteness();
    setTimeout(() => this.ensureAdminSidebarCompleteness(), 0);
    setTimeout(() => this.ensureAdminSidebarCompleteness(), 250);
    setTimeout(() => this.ensureAdminSidebarCompleteness(), 1000);
    this.normalizeAdminSidebarLabels();
    this.setupAdminSidebarWatcher();
    this.ensureStudentSidebarCompleteness();

    if (toggle && sidebar) {
      toggle.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        if (overlay) overlay.classList.toggle('active');
      });
    }

    if (overlay) {
      overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
      });
    }

    // Set active link
    const currentPath = window.location.pathname;
    const currentPage = (currentPath.split('/').pop() || '').toLowerCase();
    const activeAlias = {
      'create_collecte.php': 'preferences.php',
      'matching.php': 'preferences.php',
      'matching_restaurant.php': 'preferences.php',
      'collecte_detail.php': 'mes_collectes.php'
    };
    const targetActivePage = activeAlias[currentPage] || currentPage;

    document.querySelectorAll('.sidebar-link').forEach(link => {
      link.classList.remove('active');
      const href = (link.getAttribute('href') || '').replace('./', '').toLowerCase();
      if (!href) return;
      const hrefPath = href.split('?')[0];
      if (currentPath.toLowerCase().endsWith(hrefPath) || hrefPath === targetActivePage) {
        link.classList.add('active');
      }
    });
  },

  // --- Populate User Info ---
  initUserInfo() {
    const user = App.getCurrentUser();
    if (!user) return;

    // Sidebar user
    const userName = document.getElementById('sidebar-user-name');
    const userRole = document.getElementById('sidebar-user-role');
    const userAvatar = document.getElementById('sidebar-user-avatar');

    if (userName) userName.textContent = user.name;
    if (userRole) {
      const roleLabels = { student: 'Etudiant', partner: 'Partenaire', admin: 'Administrateur' };
      userRole.textContent = roleLabels[user.role] || user.role;
    }
    if (userAvatar) userAvatar.textContent = App.getInitials(user.name);

    // Header avatar
    const headerAvatar = document.getElementById('header-avatar');
    if (headerAvatar) headerAvatar.textContent = App.getInitials(user.name);
  },

  // --- Logout ---
  initLogout() {
    document.querySelectorAll('[data-action="logout"]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        App.addLog('Deconnexion');
        App.logout();
      });
    });
  },

  // --- Custom Select (cross-browser combobox style) ---
  initCustomSelects() {
    if (!document.body) return;

    if (!this._customSelectGlobalBound) {
      document.addEventListener('click', (event) => {
        document.querySelectorAll('.cm-select-wrap.open').forEach((wrap) => {
          if (!wrap.contains(event.target)) {
            wrap.classList.remove('open');
          }
        });
      });
      this._customSelectGlobalBound = true;
    }

    const selects = document.querySelectorAll(
      'select:not([multiple]):not([data-native-select="1"])'
    );

    selects.forEach((select) => {
      if (select.dataset.cmSelectReady === '1') return;
      select.dataset.cmSelectReady = '1';

      const wrap = document.createElement('div');
      wrap.className = 'cm-select-wrap';
      if (select.className) wrap.classList.add(...String(select.className).split(/\s+/).filter(Boolean));

      const trigger = document.createElement('button');
      trigger.type = 'button';
      trigger.className = 'cm-select-trigger';

      const label = document.createElement('span');
      label.className = 'cm-select-label';

      const chevron = document.createElement('span');
      chevron.className = 'cm-select-chevron';
      chevron.innerHTML = '<i class="fa-solid fa-chevron-down"></i>';

      trigger.appendChild(label);
      trigger.appendChild(chevron);

      const list = document.createElement('div');
      list.className = 'cm-select-list';

      const updateLabel = () => {
        const selectedOption = select.options[select.selectedIndex];
        label.textContent = selectedOption ? selectedOption.textContent : '';
      };

      const rebuildList = () => {
        list.innerHTML = '';

        Array.from(select.options).forEach((option) => {
          const item = document.createElement('button');
          item.type = 'button';
          item.className = 'cm-select-item';
          item.textContent = option.textContent;
          item.disabled = option.disabled;
          if (option.value === select.value) item.classList.add('active');

          item.addEventListener('click', () => {
            if (option.disabled) return;
            if (select.value !== option.value) {
              select.value = option.value;
              select.dispatchEvent(new Event('change', { bubbles: true }));
            }
            wrap.classList.remove('open');
            updateLabel();
            rebuildList();
          });

          list.appendChild(item);
        });
      };

      trigger.addEventListener('click', () => {
        const isOpen = wrap.classList.contains('open');
        document.querySelectorAll('.cm-select-wrap.open').forEach((openWrap) => {
          if (openWrap !== wrap) openWrap.classList.remove('open');
        });
        wrap.classList.toggle('open', !isOpen);
      });

      select.addEventListener('change', () => {
        updateLabel();
        rebuildList();
      });

      const observer = new MutationObserver(() => {
        updateLabel();
        rebuildList();
      });
      observer.observe(select, { childList: true, subtree: true, attributes: true });

      select.classList.add('cm-select-native');
      select.parentNode.insertBefore(wrap, select);
      wrap.appendChild(select);
      wrap.appendChild(trigger);
      wrap.appendChild(list);

      updateLabel();
      rebuildList();
    });
  },

  // --- Modal ---
  openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
  },

  closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.remove('active');
      document.body.style.overflow = '';
    }
  },

  initModals() {
    // Close buttons
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
      btn.addEventListener('click', () => {
        const modalId = btn.getAttribute('data-close-modal');
        this.closeModal(modalId);
      });
    });

    // Click outside
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
      overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
          overlay.classList.remove('active');
          document.body.style.overflow = '';
        }
      });
    });
  },

  // --- Toast ---
  showToast(title, message, type = 'info') {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      container.className = 'toast-container';
      document.body.appendChild(container);
    }

    const icons = {
      success: '<i class="fa-solid fa-check"></i>',
      error: '<i class="fa-solid fa-xmark"></i>',
      warning: '<i class="fa-solid fa-triangle-exclamation"></i>',
      info: '<i class="fa-solid fa-circle-info"></i>'
    };

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
      <span class="toast-icon">${icons[type]}</span>
      <div class="toast-content">
        <div class="toast-title">${title}</div>
        <div class="toast-message">${message}</div>
      </div>
    `;

    container.appendChild(toast);

    // Auto-remove
    setTimeout(() => {
      toast.remove();
      if (container.children.length === 0) container.remove();
    }, 4000);
  },

  // --- Search/Filter Table ---
  initTableSearch(searchInputId, tableId) {
    const input = document.getElementById(searchInputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;

    input.addEventListener('input', () => {
      const query = input.value.toLowerCase();
      const rows = table.querySelectorAll('tbody tr');
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
      });
    });
  },

  // --- Filter Buttons ---
  initFilterButtons(containerSelector, callback) {
    const container = document.querySelector(containerSelector);
    if (!container) return;

    container.querySelectorAll('.filter-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        container.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        if (callback) callback(btn.dataset.filter);
      });
    });
  },

  // --- Pagination ---
  paginate(items, page, perPage = 10) {
    const start = (page - 1) * perPage;
    return {
      items: items.slice(start, start + perPage),
      total: items.length,
      pages: Math.ceil(items.length / perPage),
      page
    };
  },

  renderPagination(containerId, currentPage, totalPages, onPageChange) {
    const container = document.getElementById(containerId);
    if (!container) return;

    let html = `<span class="pagination-info">Page ${currentPage} sur ${totalPages}</span>`;
    html += '<div class="pagination-controls">';
    html += `<button class="pagination-btn" ${currentPage <= 1 ? 'disabled' : ''} data-page="${currentPage - 1}">Ã¢â‚¬Â¹</button>`;

    for (let i = 1; i <= totalPages; i++) {
      html += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
    }

    html += `<button class="pagination-btn" ${currentPage >= totalPages ? 'disabled' : ''} data-page="${currentPage + 1}">Ã¢â‚¬Âº</button>`;
    html += '</div>';

    container.innerHTML = html;

    container.querySelectorAll('.pagination-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const page = parseInt(btn.dataset.page);
        if (page >= 1 && page <= totalPages) {
          onPageChange(page);
        }
      });
    });
  },

  // --- Confirmation Dialog ---
  confirm(title, message, onConfirm) {
    // Create a temporary modal
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay active';
    overlay.innerHTML = `
      <div class="modal" style="max-width:420px">
        <div class="modal-header">
          <h3>${title}</h3>
          <button class="modal-close" id="confirm-cancel-x"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
          <p style="color:var(--color-text)">${message}</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary btn-sm" id="confirm-cancel">Annuler</button>
          <button class="btn btn-danger btn-sm" id="confirm-ok">Confirmer</button>
        </div>
      </div>
    `;
    document.body.appendChild(overlay);
    document.body.style.overflow = 'hidden';

    const close = () => {
      overlay.remove();
      document.body.style.overflow = '';
    };

    overlay.querySelector('#confirm-cancel').addEventListener('click', close);
    overlay.querySelector('#confirm-cancel-x').addEventListener('click', close);
    overlay.querySelector('#confirm-ok').addEventListener('click', () => { close(); onConfirm(); });
    overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });
  },

  // --- Init All ---
  init() {
    this.initSidebar();
    this.initUserInfo();
    this.initLogout();
    this.initCustomSelects();
    this.initModals();
  }
};

// Init on DOM ready
document.addEventListener('DOMContentLoaded', () => Components.init());

