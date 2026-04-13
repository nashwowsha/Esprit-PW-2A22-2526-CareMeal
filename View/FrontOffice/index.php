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
       CAREMEAL - PREMIUM LANDING (Full Screen Food Theme)
       ============================================ */
    body {
      margin: 0;
      padding: 0;
      height: 100vh; overflow: hidden;
      background: url('https://images.unsplash.com/photo-1555939594-58d7cb561ad1?q=80&w=2000&auto=format&fit=crop') center/cover fixed;
      font-family: system-ui, -apple-system, sans-serif;
      color: white;
    }

    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background: linear-gradient(135deg, rgba(15, 23, 37, 0.85) 0%, rgba(10, 15, 26, 0.95) 100%);
      z-index: -1;
    }

    .landing-page {
      height: 100vh; overflow: hidden;
      display: flex;
      flex-direction: column;
      position: relative;
    }

    /* Ambient glowing decorations */
    .landing-page::before {
      content: '';
      position: fixed;
      width: 600px;
      height: 600px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(254,85,22,0.15), transparent 70%);
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
      background: radial-gradient(circle, rgba(254,85,22,0.08), transparent 70%);
      bottom: -100px;
      left: -100px;
      pointer-events: none;
      animation: float 6s ease-in-out infinite 2s;
      z-index: -1;
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
    }
    .landing-logo {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 1.5rem;
      font-weight: 800;
      color: white;
      text-decoration: none;
    }
    .landing-logo-icon {
      width: 44px;
      height: 44px;
      background: linear-gradient(135deg, var(--color-primary), #FF8A50);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
      box-shadow: 0 8px 20px rgba(254,85,22,0.3);
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
      color: #cbd5e1;
      text-decoration: none;
      transition: color 0.3s;
    }
    .landing-nav-links a.nav-link:hover { color: white; }

    .btn-nav {
      padding: 10px 24px;
      background: linear-gradient(135deg, var(--color-primary), #FF8A50);
      color: white;
      border-radius: 8px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s;
      box-shadow: 0 4px 15px rgba(254,85,22,0.4);
    }
    .btn-nav:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(254,85,22,0.6);
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
      padding: 6px 16px;
      background: rgba(254,85,22,0.15);
      border: 1px solid rgba(254,85,22,0.3);
      border-radius: 30px;
      font-size: 0.85rem;
      font-weight: 600;
      color: #FF8A50;
      margin-bottom: 24px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .hero-title {
      font-size: 4rem;
      font-weight: 800;
      line-height: 1.15;
      margin: 0 0 20px;
      text-shadow: 0 4px 20px rgba(0,0,0,0.4);
    }
    .hero-title .highlight {
      background: linear-gradient(135deg, var(--color-primary), #FF8A50);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .hero-subtitle {
      font-size: 1.15rem;
      color: #cbd5e1;
      max-width: 600px;
      margin: 0 auto 40px;
      line-height: 1.6;
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
      box-shadow: 0 10px 25px rgba(254,85,22,0.4);
    }
    .cta-btn.primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 15px 35px rgba(254,85,22,0.6);
    }
    .cta-btn.secondary {
      background: rgba(255,255,255,0.05);
      color: white;
      border: 1px solid rgba(255,255,255,0.2);
      backdrop-filter: blur(10px);
    }
    .cta-btn.secondary:hover {
      background: rgba(255,255,255,0.15);
      transform: translateY(-3px);
    }
    .cta-icon {
      font-size: 1.2rem;
    }

    /* Stats row */
    .landing-stats {
      display: flex;
      justify-content: center;
      gap: 64px;
      margin-top: 64px;
      padding-top: 0;
      border-top: none;
    }
    .landing-stat {
      text-align: center;
    }
    .landing-stat-value {
      font-size: 3rem;
      font-weight: 800;
      color: #fff;
    }
    .landing-stat-label {
      font-size: 1rem;
      color: #94a3b8;
      margin-top: 4px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      font-weight: 600;
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
      background: rgba(0,0,0,0.4);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 30px;
      font-size: 0.85rem;
      color: #94a3b8;
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
      .landing-stats { flex-direction: column; gap: 24px; padding-top: 24px; }
      .landing-nav-links .nav-link { display: none; }
      .cta-btn { width: 100%; }
      
      .landing-nav { padding: 20px; }
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
          Récupérez des repas invendus à  prix réduits près de chez vous. 
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




