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
    LOGS: 'caremeal_logs'
  },

  // --- RBAC Matrix ---
  PERMISSIONS: {
    student: ['view_offers', 'order', 'manage_profile', 'view_points', 'view_orders'],
    partner: ['view_offers', 'publish_offer', 'manage_profile', 'view_own_stats'],
    admin: ['view_offers', 'manage_profile', 'view_all_users', 'ban_user', 'validate_partner', 'view_global_stats', 'view_logs']
  },

  // --- Initialize ---
  init() {
    this.seedData();
  },

  // --- Auth State ---
  getCurrentUser() {
    const data = localStorage.getItem(this.KEYS.CURRENT_USER);
    return data ? JSON.parse(data) : null;
  },

  setCurrentUser(user) {
    localStorage.setItem(this.KEYS.CURRENT_USER, JSON.stringify(user));
  },

  logout() {
    localStorage.removeItem(this.KEYS.CURRENT_USER);
    window.location.href = this.getBasePath() + 'login.html';
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
      window.location.href = this.getBasePath() + 'login.html';
      return false;
    }
    if (allowedRoles.length > 0 && !allowedRoles.includes(user.role)) {
      window.location.href = this.getBasePath() + 'login.html';
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
    if (diff < 60) return "À l'instant";
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
    localStorage.setItem(this.KEYS.LOGS, JSON.stringify(logs));
    localStorage.setItem('caremeal_seeded', 'true');
  }
};

// Initialize on load
App.init();
