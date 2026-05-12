(function initCareMealPathHelpers() {
  function normalizeBasePath(rawBasePath) {
    const trimmed = String(rawBasePath || '').trim();
    if (trimmed === '' || trimmed === '/') return '';
    return '/' + trimmed.replace(/^\/+|\/+$/g, '');
  }

  function detectBasePathFromLocation() {
    const pathname = String(window.location.pathname || '');
    const markers = [
      '/View/FrontOffice/',
      '/View/BackOffice/',
      '/admin/',
      '/student/',
      '/partner/',
      '/public/',
      '/api/',
      '/Controller/',
    ];

    for (const marker of markers) {
      const idx = pathname.indexOf(marker);
      if (idx >= 0) {
        return normalizeBasePath(pathname.slice(0, idx));
      }
    }

    const lastSlash = pathname.lastIndexOf('/');
    if (lastSlash > 0) {
      return normalizeBasePath(pathname.slice(0, lastSlash));
    }

    return normalizeBasePath('');
  }

  const existingBasePath = normalizeBasePath(window.CAREMEAL_BASE_PATH || '');
  const basePath = existingBasePath !== '' ? existingBasePath : detectBasePathFromLocation();
  const publicBaseUrl = (window.CAREMEAL_PUBLIC_BASE_URL || '').trim() !== ''
    ? String(window.CAREMEAL_PUBLIC_BASE_URL).replace(/\/+$/, '')
    : window.location.origin + basePath;

  window.CAREMEAL_BASE_PATH = basePath;
  window.CAREMEAL_PUBLIC_BASE_URL = publicBaseUrl;

  window.caremealUrl = function caremealUrl(path = '') {
    const clean = String(path || '').replace(/^\/+/, '');
    if (!clean) return window.CAREMEAL_PUBLIC_BASE_URL;
    return `${window.CAREMEAL_PUBLIC_BASE_URL}/${clean}`;
  };

  window.caremealPath = function caremealPath(path = '') {
    const clean = String(path || '').replace(/^\/+/, '');
    if (!clean) return window.CAREMEAL_BASE_PATH || '';
    return `${window.CAREMEAL_BASE_PATH || ''}/${clean}`;
  };
})();
/* ============================================
   CAREMEAL — APP CORE
   app.js — Auth State, RBAC, Helpers
   ============================================ */

const App = {
  // --- Storage Keys ---
  KEYS: {
    CURRENT_USER: 'caremeal_current_user',
    USERS: 'caremeal_users',
    ORDERS: 'caremeal_orders',
    OFFERS: 'caremeal_offers',
    LOGS: 'caremeal_logs',
    EVENTS: 'caremeal_events'
  },

  // --- RBAC Matrix ---
  PERMISSIONS: {
    student: ['view_offers', 'order', 'manage_profile', 'view_points', 'view_orders'],
    partner: ['view_offers', 'publish_offer', 'manage_profile', 'view_own_stats'],
    admin: ['view_offers', 'manage_profile', 'view_all_users', 'ban_user', 'validate_partner', 'view_global_stats', 'view_logs']
  },

  // --- Initialize ---
        init() {
    let shouldSeed = true;
    try {
      const existing = localStorage.getItem(this.KEYS.EVENTS);
      if (existing) {
        const parsed = JSON.parse(existing);
        if (Array.isArray(parsed) && parsed.length > 2) {
          shouldSeed = false;
        }
      }
    } catch(e) {}

    if (shouldSeed) {
      const events = [
        {
          id: 1, title: 'Journée Anti-Gaspi', description: 'Journée de sensibilisation au gaspillage alimentaire sur le campus. Ateliers, expositions et distribution gratuite d\'invendus.',
          date: '2026-04-15', startTime: '09:00', endTime: '17:00', type: 'Présentiel', location: 'Campus El Manar, Tunis',
          capacity: 100, partnerId: 'admin_1', partnerName: 'Admin CareMeal', status: 'Validé / Planifié', createdAt: '10/04/2026',
          participants: [
            {id: 1, name: 'Adem Ben Ali', email: 'adem@etudiant.tn', date: '10/04/2026', status: 'Inscrit'},
            {id: 2, name: 'Yasmine Trabelsi', email: 'yasmine@etudiant.tn', date: '11/04/2026', status: 'Inscrit'},
            {id: 3, name: 'Louay Chaker', email: 'louay@etudiant.tn', date: '12/04/2026', status: 'Inscrit'},
            {id: 4, name: 'Aziz Hamdi', email: 'aziz@etudiant.tn', date: '13/04/2026', status: 'Inscrit'},
            {id: 5, name: 'Hadil Mansour', email: 'hadil@etudiant.tn', date: '13/04/2026', status: 'Inscrit'}
          ]
        },
        {
          id: 2, title: 'Distribution Gratuite', description: 'La Baguette Dorée offre ses invendus du jour aux étudiants. Sandwichs, viennoiseries et boissons.',
          date: '2026-04-20', startTime: '12:00', endTime: '14:00', type: 'Présentiel', location: 'Campus Manouba, Manouba',
          capacity: 50, partnerId: 'partner_1', partnerName: 'La Baguette Dorée', status: 'Validé / Planifié', createdAt: '12/04/2026',
          participants: []
        },
        {
          id: 3, title: 'Conférence Anti-Gaspi', description: 'Conférence en ligne sur l\'impact du gaspillage alimentaire en Tunisie. Intervenants experts en nutrition et environnement.',
          date: '2026-03-10', startTime: '10:00', endTime: '12:00', type: 'En ligne', location: 'zoom.us/j/123456789',
          capacity: 200, partnerId: 'admin_1', partnerName: 'Admin CareMeal', status: 'Terminé', createdAt: '01/03/2026',
          participants: [
            {id: 6, name: 'Adem Ben Ali', email: 'adem@etudiant.tn', date: '01/03/2026', status: 'Présent'},
            {id: 7, name: 'Yasmine Trabelsi', email: 'yasmine@etudiant.tn', date: '02/03/2026', status: 'Présent'},
            {id: 8, name: 'Louay Chaker', email: 'louay@etudiant.tn', date: '03/03/2026', status: 'Absent'},
            {id: 9, name: 'Sarra Belhaj', email: 'sarra@etudiant.tn', date: '04/03/2026', status: 'Présent'},
            {id: 10, name: 'Mohamed Slim', email: 'slim@etudiant.tn', date: '05/03/2026', status: 'Présent'}
          ]
        },
        {
          id: 4, title: 'Atelier Cuisine Anti-Gaspi', description: 'Atelier pratique de cuisine avec les invendus du jour. Apprenez à cuisiner des plats savoureux avec ce qui reste !',
          date: '2026-04-25', startTime: '14:00', endTime: '16:00', type: 'Présentiel', location: 'Campus La Marsa, La Marsa',
          capacity: 30, partnerId: 'partner_2', partnerName: 'Dar Lahdad SARL', status: 'En attente', createdAt: '15/04/2026',
          participants: []
        },
        {
          id: 5, title: 'Petit-Déjeuner Solidaire', description: 'Petit-déjeuner offert aux étudiants avec les invendus de la nuit. Viennoiseries, jus et café.',
          date: '2026-04-09', startTime: '08:00', endTime: '10:00', type: 'Présentiel', location: 'Campus ISG Tunis',
          capacity: 60, partnerId: 'partner_3', partnerName: 'Araek Campus', status: 'En cours', createdAt: '08/04/2026',
          participants: []
        },
        {
          id: 6, title: 'Session de sensibilisation', description: 'Session de sensibilisation',
          date: '2026-05-10', startTime: '10:00', endTime: '12:00', type: 'Présentiel', location: 'Sousse',
          capacity: 100, partnerId: 'partner_1', partnerName: 'La Baguette Dorée', status: 'En attente', createdAt: '15/04/2026',
          participants: []
        },
        {
          id: 7, title: 'Distribution repas chaud', description: 'Distribution repas chaud',
          date: '2026-05-11', startTime: '10:00', endTime: '12:00', type: 'Présentiel', location: 'Sousse',
          capacity: 100, partnerId: 'partner_1', partnerName: 'La Baguette Dorée', status: 'Validé / Planifié', createdAt: '15/04/2026',
          participants: []
        },
        {
          id: 8, title: 'Collecte alimentaire', description: 'Collecte alimentaire',
          date: '2026-05-12', startTime: '10:00', endTime: '12:00', type: 'Présentiel', location: 'Sousse',
          capacity: 100, partnerId: 'partner_1', partnerName: 'La Baguette Dorée', status: 'Terminé', createdAt: '15/04/2026',
          participants: Array(68).fill({id: 0, name: 'Student', email: '', date: '', status: 'Présent'})
        }
      ];
      this.saveEvents(events);
    }
    // Allow re-seed on next load to apply emoji changes
    
    this.seedData();
  },

  // --- Auth State ---

  async refreshCurrentUser() {
    const user = this.getCurrentUser();
    if (!user) return;

    const numericUserId = Number.parseInt(user.id || user.user_id, 10);
    const payloads = [{ action: 'get-me' }];
    if (Number.isInteger(numericUserId) && numericUserId > 0) {
      payloads.push({ action: 'get-me', user_id: numericUserId });
    }

    try {
      let data = null;
      for (const payload of payloads) {
        const res = await fetch(this.apiUrl('Controller/AuthController.php'), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const rawText = await res.text();
        const jsonStart = rawText.indexOf('{');
        const jsonPayload = jsonStart >= 0 ? rawText.slice(jsonStart) : rawText;
        try {
          const parsed = JSON.parse(jsonPayload);
          if (parsed && parsed.success && parsed.user) {
            data = parsed;
            break;
          }
        } catch (_parseError) {
          console.warn('Failed to parse get-me response', rawText);
        }
      }

      if (!data || !data.user) {
        return;
      }

      const normalizedUser = { ...data.user };
      // Keep frontend keys consistent with what partner/student pages expect.
      if (!normalizedUser.phone && normalizedUser.telephone) {
        normalizedUser.phone = normalizedUser.telephone;
      }
      if (!normalizedUser.nomEntreprise && normalizedUser.nom_entreprise) {
        normalizedUser.nomEntreprise = normalizedUser.nom_entreprise;
      }
      if (!normalizedUser.name) {
        const fullName = `${normalizedUser.prenom || ''} ${normalizedUser.nom || ''}`.trim();
        normalizedUser.name = normalizedUser.nomEntreprise || normalizedUser.nom_entreprise || fullName || normalizedUser.email || 'Utilisateur';
      }

      this.setCurrentUser({ ...user, ...normalizedUser });
      // Rerender sidebar
      if (typeof Components !== 'undefined' && typeof Components.initUserInfo === 'function') {
        Components.initUserInfo();
      }
    } catch (e) {
      console.warn('Failed to refresh user from DB', e);
    }
  },

  getCurrentUser() {
    const data = localStorage.getItem(this.KEYS.CURRENT_USER);
    return data ? JSON.parse(data) : null;
  },

  setCurrentUser(user) {
    localStorage.setItem(this.KEYS.CURRENT_USER, JSON.stringify(user));
  },

  logout() {
    localStorage.removeItem(this.KEYS.CURRENT_USER);
    try {
      Object.keys(sessionStorage).forEach((key) => {
        if (key.startsWith('caremeal_student_briefing_once_')) {
          sessionStorage.removeItem(key);
        }
      });
    } catch (_e) {}
    // Vider la session côté backend
    fetch(this.apiUrl('Controller/AuthController.php'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'logout' })
    }).finally(() => {
      // Rediriger de suite et forcer un relancement
      window.location.replace(this.apiUrl('View/FrontOffice/login.php'));
    });
  },

  isLoggedIn() {
    return this.getCurrentUser() !== null;
  },

  // --- RBAC ---
  hasPermission(permission) {
    const user = this.getCurrentUser();
    if (!user) return false;
    return this.PERMISSIONS[user.role]?.includes(permission) || false;
  },

  requireAuth(allowedRoles = []) {
    const user = this.getCurrentUser();
    if (!user) {
      window.location.href = this.apiUrl('View/FrontOffice/login.php');
      return false;
    }
    if (allowedRoles.length > 0 && !allowedRoles.includes(user.role)) {
      window.location.href = this.apiUrl('View/FrontOffice/login.php');
      return false;
    }
    return true;
  },

  // --- Path Helpers ---
  getBasePath() {
    const path = window.location.pathname;
    if (path.includes('/student/') || path.includes('/admin/') || path.includes('/partner/')) {
      return '../';
    }
    return '';
  },

  apiUrl(path = '') {
    const clean = String(path || '').replace(/^\/+/, '');
    if (typeof window.caremealPath === 'function') {
      return window.caremealPath(clean);
    }
    return '/' + clean;
  },

  // --- Users CRUD ---
  getUsers() {
    const data = localStorage.getItem(this.KEYS.USERS);
    return data ? JSON.parse(data) : [];
  },

  saveUsers(users) {
    localStorage.setItem(this.KEYS.USERS, JSON.stringify(users));
  },

  getUserById(id) {
    return this.getUsers().find(u => u.id === id);
  },

  addUser(user) {
    const users = this.getUsers();
    user.id = this.generateId();
    user.createdAt = new Date().toISOString();
    user.status = user.role === 'partner' ? 'pending' : 'active';
    user.points = 0;
    user.level = 1;
    user.ordersCount = 0;
    user.co2Saved = 0;
    user.mealsSaved = 0;
    users.push(user);
    this.saveUsers(users);
    this.addLog(`Nouvel utilisateur inscrit: ${user.name} (${user.role})`);
    return user;
  },

  updateUser(id, updates) {
    const users = this.getUsers();
    const index = users.findIndex(u => u.id === id);
    if (index !== -1) {
      users[index] = { ...users[index], ...updates };
      this.saveUsers(users);
      // Update current user if it's the same
      const current = this.getCurrentUser();
      if (current && current.id === id) {
        this.setCurrentUser(users[index]);
      }
    }
    return users[index];
  },

  deleteUser(id) {
    const users = this.getUsers().filter(u => u.id !== id);
    this.saveUsers(users);
    this.addLog(`Utilisateur supprimé: ID ${id}`);
  },

  findUserByEmail(email) {
    return this.getUsers().find(u => u.email.toLowerCase() === email.toLowerCase());
  },

  // --- Orders ---
  getOrders() {
    const data = localStorage.getItem(this.KEYS.ORDERS);
    return data ? JSON.parse(data) : [];
  },

  saveOrders(orders) {
    localStorage.setItem(this.KEYS.ORDERS, JSON.stringify(orders));
  },

  // --- Events ---
  getEvents() {
    const data = localStorage.getItem(this.KEYS.EVENTS);
    return data ? JSON.parse(data) : [];
  },

  saveEvents(events) {
    localStorage.setItem(this.KEYS.EVENTS, JSON.stringify(events));
  },

  // --- Events ---
  getEvents() {
    const data = localStorage.getItem(this.KEYS.EVENTS);
    return data ? JSON.parse(data) : [];
  },

  saveEvents(events) {
    localStorage.setItem(this.KEYS.EVENTS, JSON.stringify(events));
  },

  // --- Offers ---
  getOffers() {
    const data = localStorage.getItem(this.KEYS.OFFERS);
    return data ? JSON.parse(data) : [];
  },

  saveOffers(offers) {
    localStorage.setItem(this.KEYS.OFFERS, JSON.stringify(offers));
  },

  // --- Logs ---
  getLogs() {
    const data = localStorage.getItem(this.KEYS.LOGS);
    return data ? JSON.parse(data) : [];
  },

  addLog(action, userId = null) {
    const logs = this.getLogs();
    const user = userId ? this.getUserById(userId) : this.getCurrentUser();
    logs.unshift({
      id: this.generateId(),
      action,
      userName: user ? user.name : 'Système',
      userRole: user ? user.role : 'system',
      timestamp: new Date().toISOString()
    });
    // Keep max 100 logs
    if (logs.length > 100) logs.length = 100;
    localStorage.setItem(this.KEYS.LOGS, JSON.stringify(logs));
  },

  // --- Helpers ---
  generateId() {
    return 'id_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
  },

  getInitials(name) {
    if (!name) return '?';
    const parts = name.trim().split(' ');
    if (parts.length >= 2) {
      return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return name.charAt(0).toUpperCase();
  },

  formatDate(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
  },

  formatDateTime(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleDateString('fr-FR', {
      day: '2-digit', month: 'short', year: 'numeric',
      hour: '2-digit', minute: '2-digit'
    });
  },

  timeAgo(dateStr) {
    const now = new Date();
    const d = new Date(dateStr);
    const diff = Math.floor((now - d) / 1000);
    if (diff < 60) return "Ãƒâ‚¬ l'instant";
    if (diff < 3600) return `Il y a ${Math.floor(diff / 60)} min`;
    if (diff < 86400) return `Il y a ${Math.floor(diff / 3600)}h`;
    if (diff < 604800) return `Il y a ${Math.floor(diff / 86400)}j`;
    return this.formatDate(dateStr);
  },

  // --- Seed Mock Data ---
  seedData() {
    if (localStorage.getItem('caremeal_seeded')) return;

    // Seed users
    const users = [
      {
        id: 'user_1', name: 'Ahmed Ben Ali', email: 'ahmed@univ.tn', password: 'student123',
        role: 'student', status: 'active', university: 'ESPRIT', quartier: 'Lac 2',
        preferences: ['halal', 'budget'], budgetMax: 8, frequency: 'quotidien',
        points: 340, level: 3, ordersCount: 12, co2Saved: 4.8, mealsSaved: 12,
        createdAt: '2025-09-15T10:30:00Z', phone: ''
      },
      {
        id: 'user_2', name: 'Fatma Trabelsi', email: 'fatma@univ.tn', password: 'student123',
        role: 'student', status: 'active', university: 'INSAT', quartier: 'Centre Ville',
        preferences: ['vegetarien', 'bio'], budgetMax: 10, frequency: 'hebdomadaire',
        points: 180, level: 2, ordersCount: 7, co2Saved: 2.8, mealsSaved: 7,
        createdAt: '2025-10-01T14:00:00Z', phone: ''
      },
      {
        id: 'user_3', name: 'Youssef Hamdi', email: 'youssef@univ.tn', password: 'student123',
        role: 'student', status: 'active', university: 'ENIT', quartier: 'Ariana',
        preferences: ['sans-gluten'], budgetMax: 6, frequency: 'quotidien',
        points: 520, level: 4, ordersCount: 20, co2Saved: 8.0, mealsSaved: 20,
        createdAt: '2025-08-20T08:00:00Z', phone: ''
      },
      {
        id: 'user_4', name: 'Nour Rjab', email: 'nour@univ.tn', password: 'student123',
        role: 'student', status: 'banned', university: 'ULT', quartier: 'Menzah',
        preferences: [], budgetMax: 5, frequency: 'occasionnel',
        points: 40, level: 1, ordersCount: 2, co2Saved: 0.8, mealsSaved: 2,
        createdAt: '2025-11-10T09:00:00Z', phone: ''
      },
      {
        id: 'partner_1', name: 'La Baguette Dorée', email: 'contact@baguettedoree.tn', password: 'partner123',
        role: 'partner', status: 'active', type: 'boulangerie', address: '45 Rue de la Liberté, Tunis',
        description: 'Boulangerie artisanale depuis 2010, spécialités tunisiennes et françaises.',
        phone: '+216 71 234 567', horaires: { lun: '07:00-19:00', mar: '07:00-19:00', mer: '07:00-19:00', jeu: '07:00-19:00', ven: '07:00-19:00', sam: '08:00-14:00', dim: 'Fermé' },
        mealsSaved: 156, avgRating: 4.6, reviewCount: 45,
        createdAt: '2025-06-01T10:00:00Z', points: 0, level: 1, ordersCount: 0, co2Saved: 62.4
      },
      {
        id: 'partner_2', name: 'Chez Sami - Restaurant', email: 'sami@chezsami.tn', password: 'partner123',
        role: 'partner', status: 'active', type: 'restaurant', address: '12 Avenue Habib Bourguiba, Tunis',
        description: 'Restaurant familial tunisien, couscous et grillades.',
        phone: '+216 71 345 678', horaires: { lun: '11:00-22:00', mar: '11:00-22:00', mer: '11:00-22:00', jeu: '11:00-22:00', ven: '11:00-23:00', sam: '11:00-23:00', dim: '12:00-21:00' },
        mealsSaved: 89, avgRating: 4.3, reviewCount: 28,
        createdAt: '2025-07-15T10:00:00Z', points: 0, level: 1, ordersCount: 0, co2Saved: 35.6
      },
      {
        id: 'partner_3', name: 'Green Bowl Café', email: 'hello@greenbowl.tn', password: 'partner123',
        role: 'partner', status: 'pending', type: 'cafeteria', address: '8 Rue du Parc, Lac 1',
        description: 'Café healthy et bowls frais, options végétariennes.',
        phone: '+216 71 456 789', horaires: { lun: '08:00-18:00', mar: '08:00-18:00', mer: '08:00-18:00', jeu: '08:00-18:00', ven: '08:00-18:00', sam: '09:00-15:00', dim: 'Fermé' },
        mealsSaved: 0, avgRating: 0, reviewCount: 0,
        createdAt: '2026-03-28T10:00:00Z', points: 0, level: 1, ordersCount: 0, co2Saved: 0
      },
      {
        id: 'admin_1', name: 'Admin CareMeal', email: 'admin@caremeal.tn', password: 'admin123',
        role: 'admin', status: 'active',
        createdAt: '2025-01-01T00:00:00Z', points: 0, level: 1, ordersCount: 0, co2Saved: 0, mealsSaved: 0
      }
    ];

    // Seed orders
    const orders = [
      { id: 'ord_1', userId: 'user_1', partnerId: 'partner_1', partnerName: 'La Baguette Dorée', items: 'Panier surprise boulangerie', originalPrice: 12, price: 4.5, status: 'completed', date: '2026-04-05T12:30:00Z', rating: 5 },
      { id: 'ord_2', userId: 'user_1', partnerId: 'partner_2', partnerName: 'Chez Sami', items: 'Couscous du jour', originalPrice: 15, price: 5, status: 'completed', date: '2026-04-04T13:00:00Z', rating: 4 },
      { id: 'ord_3', userId: 'user_1', partnerId: 'partner_1', partnerName: 'La Baguette Dorée', items: 'Viennoiseries assorties', originalPrice: 8, price: 3, status: 'completed', date: '2026-04-02T08:00:00Z', rating: 5 },
      { id: 'ord_4', userId: 'user_2', partnerId: 'partner_2', partnerName: 'Chez Sami', items: 'Salade composée + jus', originalPrice: 10, price: 3.5, status: 'completed', date: '2026-04-03T12:00:00Z', rating: 4 },
      { id: 'ord_5', userId: 'user_3', partnerId: 'partner_1', partnerName: 'La Baguette Dorée', items: 'Pain et pâtisseries', originalPrice: 9, price: 3, status: 'completed', date: '2026-04-05T17:00:00Z', rating: 5 },
      { id: 'ord_6', userId: 'user_1', partnerId: 'partner_2', partnerName: 'Chez Sami', items: 'Plat du jour', originalPrice: 12, price: 4, status: 'pending', date: '2026-04-06T11:30:00Z', rating: null },
    ];

    // Seed offers
    const offers = [
      { id: 'off_1', partnerId: 'partner_1', partnerName: 'La Baguette Dorée', title: 'Panier Surprise Boulangerie', description: 'Pains, viennoiseries et pâtisseries du jour', originalPrice: 12, price: 4.5, quantity: 5, remaining: 3, category: 'boulangerie', status: 'active', pickupStart: '17:00', pickupEnd: '19:00', createdAt: '2026-04-06T06:00:00Z', emoji: '<i class="fa-solid fa-bread-slice"></i>' },
      { id: 'off_2', partnerId: 'partner_2', partnerName: 'Chez Sami', title: 'Box Déjeuner Anti-Gaspi', description: 'Couscous ou plat du jour avec accompagnements', originalPrice: 15, price: 5, quantity: 8, remaining: 5, category: 'restaurant', status: 'active', pickupStart: '14:00', pickupEnd: '15:30', createdAt: '2026-04-06T07:00:00Z', emoji: '<i class="fa-solid fa-bowl-food"></i>' },
      { id: 'off_3', partnerId: 'partner_1', partnerName: 'La Baguette Dorée', title: 'Viennoiseries du Matin', description: 'Croissants, pains au chocolat, brioches', originalPrice: 8, price: 3, quantity: 10, remaining: 6, category: 'boulangerie', status: 'active', pickupStart: '07:30', pickupEnd: '09:00', createdAt: '2026-04-06T05:00:00Z', emoji: '<i class="fa-solid fa-cookie-bite"></i>' },
      { id: 'off_4', partnerId: 'partner_2', partnerName: 'Chez Sami', title: 'Salade Fraîche du Jour', description: 'Salade composée avec protéines au choix', originalPrice: 10, price: 3.5, quantity: 6, remaining: 0, category: 'restaurant', status: 'expired', pickupStart: '12:00', pickupEnd: '13:00', createdAt: '2026-04-05T06:00:00Z', emoji: '<i class="fa-solid fa-bowl-rice"></i>' },
      { id: 'off_5', partnerId: 'partner_1', partnerName: 'La Baguette Dorée', title: 'Sandwichs de la Veille', description: 'Assortiment de sandwichs variés', originalPrice: 7, price: 2.5, quantity: 4, remaining: 0, category: 'boulangerie', status: 'expired', pickupStart: '18:00', pickupEnd: '19:00', createdAt: '2026-04-04T06:00:00Z', emoji: '<i class="fa-solid fa-burger"></i>' },
    ];

    // Seed logs
    const logs = [
      { id: 'log_1', action: 'Connexion administrateur', userName: 'Admin CareMeal', userRole: 'admin', timestamp: '2026-04-06T08:00:00Z' },
      { id: 'log_2', action: 'Nouvelle offre publiée: Panier Surprise Boulangerie', userName: 'La Baguette Dorée', userRole: 'partner', timestamp: '2026-04-06T06:00:00Z' },
      { id: 'log_3', action: 'Commande passée: Box Déjeuner Anti-Gaspi', userName: 'Ahmed Ben Ali', userRole: 'student', timestamp: '2026-04-06T11:30:00Z' },
      { id: 'log_4', action: 'Nouvel utilisateur inscrit: Green Bowl Café (partner)', userName: 'Système', userRole: 'system', timestamp: '2026-03-28T10:00:00Z' },
      { id: 'log_5', action: 'Utilisateur banni: Nour Rjab', userName: 'Admin CareMeal', userRole: 'admin', timestamp: '2026-03-25T14:00:00Z' },
      { id: 'log_6', action: 'Partenaire validé: Chez Sami - Restaurant', userName: 'Admin CareMeal', userRole: 'admin', timestamp: '2025-07-16T09:00:00Z' },
      { id: 'log_7', action: 'Modification profil', userName: 'Fatma Trabelsi', userRole: 'student', timestamp: '2026-04-05T16:00:00Z' },
      { id: 'log_8', action: 'Nouvelle offre publiée: Box Déjeuner Anti-Gaspi', userName: 'Chez Sami', userRole: 'partner', timestamp: '2026-04-06T07:00:00Z' },
    ];

            this.saveUsers(users);
    this.saveOrders(orders);
    this.saveOffers(offers);

    if (!localStorage.getItem(this.KEYS.EVENTS)) {
      const events = [
        {
          id: 1, title: 'Journée Anti-Gaspi', description: 'Journée de sensibilisation au gaspillage alimentaire sur le campus. Ateliers, expositions et distribution gratuite d\'invendus.',
          date: '2026-04-15', startTime: '09:00', endTime: '17:00', type: 'Présentiel', location: 'Campus El Manar, Tunis',
          capacity: 100, partnerId: 'admin_1', partnerName: 'Admin CareMeal', status: 'Validé / Planifié', createdAt: '10/04/2026',
          participants: [
            {id: 1, name: 'Adem Ben Ali', email: 'adem@etudiant.tn', date: '10/04/2026', status: 'Inscrit'},
            {id: 2, name: 'Yasmine Trabelsi', email: 'yasmine@etudiant.tn', date: '11/04/2026', status: 'Inscrit'},
            {id: 3, name: 'Louay Chaker', email: 'louay@etudiant.tn', date: '12/04/2026', status: 'Inscrit'},
            {id: 4, name: 'Aziz Hamdi', email: 'aziz@etudiant.tn', date: '13/04/2026', status: 'Inscrit'},
            {id: 5, name: 'Hadil Mansour', email: 'hadil@etudiant.tn', date: '13/04/2026', status: 'Inscrit'}
          ]
        },
        {
          id: 2, title: 'Distribution Gratuite', description: 'La Baguette Dorée offre ses invendus du jour aux étudiants. Sandwichs, viennoiseries et boissons.',
          date: '2026-04-20', startTime: '12:00', endTime: '14:00', type: 'Présentiel', location: 'Campus Manouba, Manouba',
          capacity: 50, partnerId: 'partner_1', partnerName: 'La Baguette Dorée', status: 'Validé / Planifié', createdAt: '12/04/2026',
          participants: []
        },
        {
          id: 3, title: 'Conférence Anti-Gaspi', description: 'Conférence en ligne sur l\'impact du gaspillage alimentaire en Tunisie. Intervenants experts en nutrition et environnement.',
          date: '2026-03-10', startTime: '10:00', endTime: '12:00', type: 'En ligne', location: 'zoom.us/j/123456789',
          capacity: 200, partnerId: 'admin_1', partnerName: 'Admin CareMeal', status: 'Terminé', createdAt: '01/03/2026',
          participants: [
            {id: 6, name: 'Adem Ben Ali', email: 'adem@etudiant.tn', date: '01/03/2026', status: 'Présent'},
            {id: 7, name: 'Yasmine Trabelsi', email: 'yasmine@etudiant.tn', date: '02/03/2026', status: 'Présent'},
            {id: 8, name: 'Louay Chaker', email: 'louay@etudiant.tn', date: '03/03/2026', status: 'Absent'},
            {id: 9, name: 'Sarra Belhaj', email: 'sarra@etudiant.tn', date: '04/03/2026', status: 'Présent'},
            {id: 10, name: 'Mohamed Slim', email: 'slim@etudiant.tn', date: '05/03/2026', status: 'Présent'}
          ]
        },
        {
          id: 4, title: 'Atelier Cuisine Anti-Gaspi', description: 'Atelier pratique de cuisine avec les invendus du jour. Apprenez à cuisiner des plats savoureux avec ce qui reste !',
          date: '2026-04-25', startTime: '14:00', endTime: '16:00', type: 'Présentiel', location: 'Campus La Marsa, La Marsa',
          capacity: 30, partnerId: 'partner_2', partnerName: 'Dar Lahdad SARL', status: 'En attente', createdAt: '15/04/2026',
          participants: []
        },
        {
          id: 5, title: 'Petit-Déjeuner Solidaire', description: 'Petit-déjeuner offert aux étudiants avec les invendus de la nuit. Viennoiseries, jus et café.',
          date: '2026-04-09', startTime: '08:00', endTime: '10:00', type: 'Présentiel', location: 'Campus ISG Tunis',
          capacity: 60, partnerId: 'partner_3', partnerName: 'Araek Campus', status: 'En cours', createdAt: '08/04/2026',
          participants: []
        },
        {
          id: 6, title: 'Session de sensibilisation', description: 'Session de sensibilisation - Extra 1',
          date: '2026-05-10', startTime: '10:00', endTime: '12:00', type: 'Présentiel', location: 'Sousse',
          capacity: 100, partnerId: 'partner_1', partnerName: 'La Baguette Dorée', status: 'En attente', createdAt: '15/04/2026',
          participants: []
        },
        {
          id: 7, title: 'Distribution repas chaud', description: 'Distribution repas chaud - Extra 2',
          date: '2026-05-11', startTime: '10:00', endTime: '12:00', type: 'Présentiel', location: 'Sousse',
          capacity: 100, partnerId: 'partner_1', partnerName: 'La Baguette Dorée', status: 'Validé / Planifié', createdAt: '15/04/2026',
          participants: []
        },
        {
          id: 8, title: 'Collecte alimentaire', description: 'Collecte alimentaire - Extra 3',
          date: '2026-05-12', startTime: '10:00', endTime: '12:00', type: 'Présentiel', location: 'Sousse',
          capacity: 100, partnerId: 'partner_1', partnerName: 'La Baguette Dorée', status: 'Terminé', createdAt: '15/04/2026',
          participants: Array(68).fill({id: 0, name: 'Student', email: '', date: '', status: 'Présent'})
        }
      ];
      this.saveEvents(events);
    }
    // Allow re-seed on next load to apply emoji changes
    

    if (!localStorage.getItem(this.KEYS.EVENTS)) {
      const events = [
        {
          id: 1, title: 'Journée Anti-Gaspi', description: 'Journée de sensibilisation au gaspillage alimentaire sur le campus. Ateliers, expositions et distribution gratuite d\'invendus.',
          date: '2026-04-15', startTime: '09:00', endTime: '17:00', type: 'Présentiel', location: 'Campus El Manar, Tunis',
          capacity: 100, partnerId: 'admin_1', partnerName: 'Admin CareMeal', status: 'Validé / Planifié', createdAt: '10/04/2026',
          participants: [
            {id: 1, name: 'Adem Ben Ali', email: 'adem@etudiant.tn', date: '10/04/2026', status: 'Inscrit'},
            {id: 2, name: 'Yasmine Trabelsi', email: 'yasmine@etudiant.tn', date: '11/04/2026', status: 'Inscrit'},
            {id: 3, name: 'Louay Chaker', email: 'louay@etudiant.tn', date: '12/04/2026', status: 'Inscrit'},
            {id: 4, name: 'Aziz Hamdi', email: 'aziz@etudiant.tn', date: '13/04/2026', status: 'Inscrit'},
            {id: 5, name: 'Hadil Mansour', email: 'hadil@etudiant.tn', date: '13/04/2026', status: 'Inscrit'}
          ]
        },
        {
          id: 2, title: 'Distribution Gratuite', description: 'La Baguette Dorée offre ses invendus du jour aux étudiants. Sandwichs, viennoiseries et boissons.',
          date: '2026-04-20', startTime: '12:00', endTime: '14:00', type: 'Présentiel', location: 'Campus Manouba, Manouba',
          capacity: 50, partnerId: 'partner_1', partnerName: 'La Baguette Dorée', status: 'Validé / Planifié', createdAt: '12/04/2026',
          participants: []
        },
        {
          id: 3, title: 'Conférence Anti-Gaspi', description: 'Conférence en ligne sur l\'impact du gaspillage alimentaire en Tunisie. Intervenants experts en nutrition et environnement.',
          date: '2026-03-10', startTime: '10:00', endTime: '12:00', type: 'En ligne', location: 'zoom.us/j/123456789',
          capacity: 200, partnerId: 'admin_1', partnerName: 'Admin CareMeal', status: 'Terminé', createdAt: '01/03/2026',
          participants: [
            {id: 6, name: 'Adem Ben Ali', email: 'adem@etudiant.tn', date: '01/03/2026', status: 'Présent'},
            {id: 7, name: 'Yasmine Trabelsi', email: 'yasmine@etudiant.tn', date: '02/03/2026', status: 'Présent'},
            {id: 8, name: 'Louay Chaker', email: 'louay@etudiant.tn', date: '03/03/2026', status: 'Absent'},
            {id: 9, name: 'Sarra Belhaj', email: 'sarra@etudiant.tn', date: '04/03/2026', status: 'Présent'},
            {id: 10, name: 'Mohamed Slim', email: 'slim@etudiant.tn', date: '05/03/2026', status: 'Présent'}
          ]
        },
        {
          id: 4, title: 'Atelier Cuisine Anti-Gaspi', description: 'Atelier pratique de cuisine avec les invendus du jour. Apprenez à cuisiner des plats savoureux avec ce qui reste !',
          date: '2026-04-25', startTime: '14:00', endTime: '16:00', type: 'Présentiel', location: 'Campus La Marsa, La Marsa',
          capacity: 30, partnerId: 'partner_2', partnerName: 'Dar Lahdad SARL', status: 'En attente', createdAt: '15/04/2026',
          participants: []
        },
        {
          id: 5, title: 'Petit-Déjeuner Solidaire', description: 'Petit-déjeuner offert aux étudiants avec les invendus de la nuit. Viennoiseries, jus et café.',
          date: '2026-04-09', startTime: '08:00', endTime: '10:00', type: 'Présentiel', location: 'Campus ISG Tunis',
          capacity: 60, partnerId: 'partner_3', partnerName: 'Araek Campus', status: 'En cours', createdAt: '08/04/2026',
          participants: []
        },
        {
          id: 6, title: 'Session de sensibilisation', description: 'Session de sensibilisation - Extra 1',
          date: '2026-05-10', startTime: '10:00', endTime: '12:00', type: 'Présentiel', location: 'Sousse',
          capacity: 100, partnerId: 'partner_1', partnerName: 'La Baguette Dorée', status: 'En attente', createdAt: '15/04/2026',
          participants: []
        },
        {
          id: 7, title: 'Distribution repas chaud', description: 'Distribution repas chaud - Extra 2',
          date: '2026-05-11', startTime: '10:00', endTime: '12:00', type: 'Présentiel', location: 'Sousse',
          capacity: 100, partnerId: 'partner_1', partnerName: 'La Baguette Dorée', status: 'Validé / Planifié', createdAt: '15/04/2026',
          participants: []
        },
        {
          id: 8, title: 'Collecte alimentaire', description: 'Collecte alimentaire - Extra 3',
          date: '2026-05-12', startTime: '10:00', endTime: '12:00', type: 'Présentiel', location: 'Sousse',
          capacity: 100, partnerId: 'partner_1', partnerName: 'La Baguette Dorée', status: 'Terminé', createdAt: '15/04/2026',
          participants: Array(68).fill({id: 0, name: 'Student', email: '', date: '', status: 'Présent'})
        }
      ];
      this.saveEvents(events);
    }
    // Allow re-seed on next load to apply emoji changes
    
    localStorage.setItem(this.KEYS.LOGS, JSON.stringify(logs));
    localStorage.setItem('caremeal_seeded', 'true');
  }
};

// Initialize on load
App.init();

















document.addEventListener('DOMContentLoaded', () => { App.refreshCurrentUser(); });








