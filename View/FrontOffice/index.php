<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="CareMeal — Sauvez des repas, faites des économies. La plateforme anti-gaspillage alimentaire pour les étudiants tunisiens.">
  <title>CareMeal — Sauvez des repas, faites des économies</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/projet2a22/css/main.css">
  <style>
    /* ============================================
       CAREMEAL - LANDING PAGE — LIGHT MODE
       ============================================ */
    body {
      margin: 0;
      padding: 0;
      height: 100vh;
      overflow: hidden;
      background: url('https://images.unsplash.com/photo-1555939594-58d7cb561ad1?q=80&w=2000&auto=format&fit=crop') center/cover fixed;
      font-family: system-ui, -apple-system, sans-serif;
      color: #1A202C;
    }

    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background: transparent;
      z-index: -1;
    }

    .landing-page {
      height: 100vh;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      position: relative;
    }

    /* Halos décoratifs orange */
    .landing-page::before {
      content: '';
      position: fixed;
      width: 600px;
      height: 600px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(254,85,22,0.12), transparent 70%);
      top: -150px;
      right: -100px;
      pointer-events: none;
      animation: float 8s ease-in-out infinite;
      z-index: -1;
    }
    .landing-page::after {
      content: '';
      position: fixed;
      width: 400px;
      height: 400px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(254,85,22,0.07), transparent 70%);
      bottom: -100px;
      left: -100px;
      pointer-events: none;
      animation: float 6s ease-in-out infinite 2s;
      z-index: -1;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0); }
      50%       { transform: translateY(-20px); }
    }

    /* Nav */
    .landing-nav {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 40px;
      position: relative;
      z-index: 10;
      animation: fadeInDown 0.6s ease forwards;
      background: rgba(30, 20, 10, 0.45);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    @keyframes fadeInDown {
      from { opacity: 0; transform: translateY(-16px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .landing-logo {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 1.5rem;
      font-weight: 800;
      color: #fff;
      text-decoration: none;
    }
    .landing-logo-icon {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .landing-logo span { color: var(--color-primary); }

    .landing-nav-links {
      display: flex;
      align-items: center;
      gap: 24px;
    }
    .landing-nav-links a.nav-link {
      font-size: 0.95rem;
      font-weight: 600;
      color: rgba(255,255,255,0.9);
      text-decoration: none;
      transition: color 0.2s;
    }
    .landing-nav-links a.nav-link:hover { color: #fff; }

    .btn-nav {
      padding: 10px 24px;
      background: linear-gradient(135deg, var(--color-primary), #FF8A50);
      color: white;
      border-radius: 8px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s;
      box-shadow: 0 4px 15px rgba(254,85,22,0.3);
    }
    .btn-nav:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(254,85,22,0.5);
    }

    /* Hero */
    .landing-hero {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      text-align: center;
      position: relative;
      z-index: 5;
    }
    .hero-content {
      max-width: 800px;
      transform: scale(0.95);
      opacity: 0;
      animation: modalPop 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards 0.2s;
    }
    @keyframes modalPop {
      to { transform: scale(1); opacity: 1; }
    }

    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 20px;
      background: rgba(30, 20, 10, 0.55);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1.5px solid rgba(254,85,22,0.7);
      border-radius: 30px;
      font-size: 0.85rem;
      font-weight: 700;
      color: #FE5516;
      margin-bottom: 24px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.3);
    }

    .hero-title {
      font-size: 4rem;
      font-weight: 800;
      line-height: 1.15;
      margin: 0 0 20px;
      color: #fff;
      text-shadow: 0 2px 4px rgba(0,0,0,0.9), 0 4px 24px rgba(0,0,0,0.7), 0 0 60px rgba(0,0,0,0.5);
    }
    .hero-title .highlight {
      color: #FE5516;
      -webkit-text-fill-color: #FE5516;
    }

    .hero-subtitle {
      font-size: 1.15rem;
      color: rgba(255,255,255,0.95);
      max-width: 600px;
      margin: 0 auto 40px;
      line-height: 1.6;
      text-shadow: 0 1px 12px rgba(0,0,0,0.8);
    }

    /* CTA Buttons */
    .hero-cta {
      display: flex;
      gap: 20px;
      justify-content: center;
      flex-wrap: wrap;
    }
    .cta-btn {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      padding: 16px 32px;
      border-radius: 12px;
      font-size: 1.05rem;
      font-weight: 700;
      text-decoration: none;
      transition: all 0.3s;
      min-width: 220px;
      justify-content: center;
    }
    .cta-btn.primary {
      background: linear-gradient(135deg, var(--color-primary), #FF8A50);
      color: white;
      box-shadow: 0 10px 25px rgba(254,85,22,0.35);
    }
    .cta-btn.primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 15px 35px rgba(254,85,22,0.5);
    }
    .cta-btn.secondary {
      background: rgba(30, 20, 10, 0.45);
      color: white;
      border: 1.5px solid rgba(255,255,255,0.6);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      font-weight: 700;
      box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    }
    .cta-btn.secondary:hover {
      background: rgba(30, 20, 10, 0.6);
      border-color: #fff;
      transform: translateY(-3px);
      box-shadow: 0 8px 28px rgba(0,0,0,0.3);
    }

    /* Stats */
    .landing-stats {
      display: flex;
      justify-content: center;
      gap: 64px;
      margin-top: 56px;
    }
    .landing-stat { text-align: center; }
    .landing-stat-value {
      font-size: 3rem;
      font-weight: 800;
      color: #fff;
      text-shadow: 0 2px 16px rgba(0,0,0,0.8);
    }
    .landing-stat-label {
      font-size: 0.85rem;
      color: rgba(255,255,255,0.9);
      margin-top: 4px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      font-weight: 600;
      text-shadow: 0 1px 8px rgba(0,0,0,0.7);
    }

    /* Séparateur entre stats */
    .landing-stat:not(:last-child) {
      padding-right: 64px;
      border-right: 1px solid #E2E8F0;
    }

    /* Admin link */
    .admin-link {
      position: fixed;
      bottom: 24px;
      right: 24px;
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 10px 20px;
      background: rgba(0,0,0,0.35);
      border: 1px solid rgba(255,255,255,0.15);
      border-radius: 30px;
      font-size: 0.85rem;
      color: rgba(255,255,255,0.8);
      text-decoration: none;
      transition: all 0.3s;
      z-index: 20;
      backdrop-filter: blur(10px);
    }
    .admin-link:hover {
      background: var(--color-primary);
      color: white;
      border-color: var(--color-primary);
      box-shadow: 0 4px 15px rgba(254,85,22,0.4);
      transform: translateY(-2px);
    }

    @media (max-width: 768px) {
      .hero-title { font-size: 2.2rem; }
      .landing-stats { flex-direction: column; gap: 24px; }
      .landing-stat:not(:last-child) { padding-right: 0; border-right: none; border-bottom: 1px solid #E2E8F0; padding-bottom: 24px; }
      .landing-nav-links .nav-link { display: none; }
      .cta-btn { width: 100%; }
      .landing-nav { padding: 16px 20px; }
    }
  </style>
</head>
<body>
  <div class="landing-page">
    <!-- Navigation -->
    <nav class="landing-nav">
      <a href="/projet2a22/View/FrontOffice/index.php" class="landing-logo">
        <div class="landing-logo-icon" style="background: none; box-shadow: none;"><img src="/projet2a22/assets/logo.png" alt="Logo" style="max-width: 80px; height: auto;"></div>
        Care<span>Meal</span>
      </a>
      <div class="landing-nav-links">
        <a href="/projet2a22/View/FrontOffice/login.php" class="nav-link">Connexion</a>
        <a href="/projet2a22/View/FrontOffice/register-student.php" class="btn-nav">S'inscrire</a>
      </div>
    </nav>

    <!-- Hero -->
    <section class="landing-hero">
      <div class="hero-content">
        <div class="hero-badge">
              <i class="fa-solid fa-seedling"></i> Anti-gaspillage alimentaire</div>
        <h1 class="hero-title">
          Sauvez des repas,<br>
          <span class="highlight">faites des économies</span>
        </h1>
        <p class="hero-subtitle">
          Récupérez des repas invendus à prix réduits près de chez vous. 
          Bon pour votre portefeuille, bon pour la planète. <i class="fa-solid fa-earth-africa"></i>
        </p>
        <div class="hero-cta">
          <a href="/projet2a22/View/FrontOffice/register-student.php" class="cta-btn primary" id="cta-student">
            <span class="cta-icon">
              <span class="input-icon"><i class="fa-solid fa-graduation-cap"></i></span>
            Je suis étudiant
          </a>
          <a href="/projet2a22/View/FrontOffice/register-partner.php" class="cta-btn secondary" id="cta-partner">
            <span class="cta-icon">
              <span class="input-icon"><i class="fa-solid fa-store"></i></span>
            Je suis partenaire
          </a>
        </div>

        <div class="landing-stats">
          <div class="landing-stat">
            <div class="landing-stat-value">2,450+</div>
            <div class="landing-stat-label">Repas sauvés</div>
          </div>
          <div class="landing-stat">
            <div class="landing-stat-value">980 kg</div>
            <div class="landing-stat-label">CO2 évité</div>
          </div>
          <div class="landing-stat">
            <div class="landing-stat-value">350+</div>
            <div class="landing-stat-label">Étudiants actifs</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Admin access -->
    <a href="/projet2a22/View/FrontOffice/login.php" class="admin-link" id="admin-access">
      <i class="fa-solid fa-lock"></i> Espace Admin
    </a>
  </div>

  <script src="/projet2a22/js/app.js"></script>
</body>
</html>




