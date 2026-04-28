/* ============================================
   CAREMEAL â€” COMPONENTS JS
   components.js â€” Sidebar, Modals, Toasts
   ============================================ */

const Components = {
  // --- Sidebar ---
  initSidebar() {
    const toggle = document.getElementById('menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');

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

    this.ensureSidebarIntegrity();
  },

  ensureSidebarIntegrity() {
    const currentPath = window.location.pathname || '';
    const isStudent = currentPath.includes('/student/');
    const isPartner = currentPath.includes('/partner/');
    const isAdmin = currentPath.includes('/admin/');
    const navRoot = document.querySelector('.sidebar-nav');
    if (!navRoot) return;

    // Safety: force all sidebar links to stay visible
    document.querySelectorAll('.sidebar-link').forEach(link => {
      link.classList.remove('hidden');
      link.style.display = 'flex';
      link.style.visibility = 'visible';
      link.style.opacity = '1';
    });

    // Student/Partner only: guarantee Publications link presence
    if ((isStudent || isPartner) && !navRoot.querySelector('.sidebar-link[href="publications.html"]')) {
      const label = isStudent ? 'Publications' : 'Publications';
      const icon = '<span class="link-icon"><i class="fa-solid fa-pen-nib"></i></span>';
      const fallbackLink = document.createElement('a');
      fallbackLink.href = 'publications.html';
      fallbackLink.className = 'sidebar-link';
      fallbackLink.innerHTML = `${icon} ${label}`;

      const firstSection = navRoot.querySelector('.sidebar-section');
      if (firstSection) {
        const eventsLink = firstSection.querySelector('.sidebar-link[href="events.html"]');
        if (eventsLink) {
          firstSection.insertBefore(fallbackLink, eventsLink);
        } else {
          firstSection.appendChild(fallbackLink);
        }
      }
    }

    if (isAdmin && !navRoot.querySelector('.sidebar-link[href="publications.html"]')) {
      const fallbackLink = document.createElement('a');
      fallbackLink.href = 'publications.html';
      fallbackLink.className = 'sidebar-link';
      fallbackLink.innerHTML = '<span class="link-icon"><i class="fa-solid fa-pen-nib"></i></span> Publications';

      const firstSection = navRoot.querySelector('.sidebar-section');
      if (firstSection) {
        const eventsLink = firstSection.querySelector('.sidebar-link[href="events.html"]');
        if (eventsLink) {
          firstSection.insertBefore(fallbackLink, eventsLink);
        } else {
          firstSection.appendChild(fallbackLink);
        }
      }
    }

    // Stable active state based on current file
    const currentFile = (window.location.pathname.split('/').pop() || '').split('?')[0].split('#')[0];
    const publicationPages = ['publications.html', 'publication-create.html', 'publication-edit.html'];
    const forcePublicationsActive = isAdmin && publicationPages.includes(currentFile);

    document.querySelectorAll('.sidebar-link').forEach(link => {
      link.classList.remove('active');
      const href = (link.getAttribute('href') || '').split('/').pop().split('?')[0].split('#')[0];
      if (forcePublicationsActive && href === 'publications.html') {
        link.classList.add('active');
        return;
      }
      if (href && href === currentFile) {
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
      const roleLabels = { student: 'Étudiant', partner: 'Partenaire', admin: 'Administrateur' };
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
        App.addLog('Déconnexion');
        App.logout();
      });
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
      info: 'â„¹'
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
    html += `<button class="pagination-btn" ${currentPage <= 1 ? 'disabled' : ''} data-page="${currentPage - 1}">â€¹</button>`;

    for (let i = 1; i <= totalPages; i++) {
      html += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
    }

    html += `<button class="pagination-btn" ${currentPage >= totalPages ? 'disabled' : ''} data-page="${currentPage + 1}">â€º</button>`;
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
    this.initModals();
  }
};

// Init on DOM ready
document.addEventListener('DOMContentLoaded', () => Components.init());
