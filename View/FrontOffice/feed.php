<?php require_once dirname(__DIR__, 1) . '/session_check.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fil d'actualite — CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/projet2a22/css/main.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: system-ui, -apple-system, sans-serif; background: #F0F2F5; color: #1A202C; min-height: 100vh; }

    /* TOP NAVBAR */
    .feed-nav {
      position: fixed; top: 0; left: 0; right: 0; height: 56px; z-index: 100;
      background: #fff; border-bottom: 1px solid #E2E8F0;
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 16px; gap: 12px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    }
    .nav-logo { display: flex; align-items: center; gap: 8px; font-size: 1.2rem; font-weight: 800; color: #1A202C; text-decoration: none; flex-shrink: 0; }
    .nav-logo span { color: #FE5516; }
    .nav-search { flex: 1; max-width: 260px; position: relative; }
    .nav-search input {
      width: 100%; padding: 8px 16px 8px 36px; border-radius: 20px;
      border: none; background: #F0F2F5; font-size: 0.85rem; outline: none; color: #1A202C;
      transition: background .2s;
    }
    .nav-search input:focus { background: #E8EAF0; }
    .nav-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94A3B8; font-size: 0.8rem; }
    .nav-tabs { display: flex; align-items: center; gap: 8px; justify-content: center; height: 100%; }
    .nav-tab {
      width: 50px; height: 48px; border-radius: 8px; display: flex; align-items: center; justify-content: center;
      color: #64748B; font-size: 1.3rem; cursor: pointer; border: none; background: none;
      transition: all .2s; text-decoration: none; position: relative;
    }
    .nav-tab:hover { background: #F0F2F5; color: #1A202C; }
    .nav-tab.active { color: #FE5516; }
    .nav-tab.active::after { content: ""; position: absolute; bottom: -4px; left: 10%; right: 10%; height: 3px; background: #FE5516; border-radius: 3px 3px 0 0; }
    .nav-tab .notif-badge {
      position: absolute; top: 7px; right: 7px; width: 8px; height: 8px;
      background: #FE5516; border-radius: 50%; border: 2px solid #fff;
    }
    .nav-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
    .nav-avatar-wrap { position: relative; }
    .nav-avatar {
      width: 36px; height: 36px; border-radius: 50%;
      background: linear-gradient(135deg, #FE5516, #FF8A50);
      display: flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: 0.85rem; color: #fff; cursor: pointer;
    }
    .nav-avatar-menu {
      position: absolute; top: 44px; right: 0; background: #fff; border-radius: 12px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.15); border: 1px solid #E2E8F0;
      min-width: 220px; padding: 8px; display: none; z-index: 200;
    }
    .nav-avatar-menu.open { display: block; animation: menuPop .15s ease; }
    @keyframes menuPop { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }
    .menu-user { padding: 12px; border-bottom: 1px solid #F0F2F5; margin-bottom: 4px; }
    .menu-user-name { font-weight: 700; font-size: 0.9rem; }
    .menu-user-role { font-size: 0.75rem; color: #64748B; }
    .nav-avatar-menu a, .nav-avatar-menu button {
      display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 8px;
      font-size: 0.85rem; color: #1A202C; text-decoration: none; width: 100%;
      border: none; background: none; cursor: pointer; font-family: inherit; transition: background .15s;
    }
    .nav-avatar-menu a:hover, .nav-avatar-menu button:hover { background: #F0F2F5; }
    .menu-danger { color: #EF4444 !important; }
    .menu-danger:hover { background: rgba(239,68,68,0.08) !important; }
    .menu-divider { border: none; border-top: 1px solid #F0F2F5; margin: 4px 0; }

    /* LAYOUT */
    .feed-layout {
      max-width: 1200px; margin: 0 auto; padding: 80px 20px 40px;
      display: grid; grid-template-columns: minmax(0, 720px) 320px; 
      justify-content: center; gap: 40px;
    }

    /* LEFT SIDEBAR */
    .feed-left { position: sticky; top: 72px; height: fit-content; }
    .feed-left-user {
      display: flex; align-items: center; gap: 12px; padding: 14px;
      border-radius: 14px; background: #fff; border: 1px solid #E2E8F0;
      margin-bottom: 8px; cursor: pointer; text-decoration: none; color: #1A202C;
      transition: box-shadow .2s;
    }
    .feed-left-user:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.1); }
    .left-avatar {
      width: 46px; height: 46px; border-radius: 50%;
      background: linear-gradient(135deg, #FE5516, #FF8A50);
      display: flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: 0.9rem; color: #fff; flex-shrink: 0;
    }
    .left-user-name { font-weight: 700; font-size: 0.9rem; }
    .left-user-role { font-size: 0.72rem; color: #64748B; margin-top: 2px; }
    .left-nav-section { background: #fff; border-radius: 14px; border: 1px solid #E2E8F0; padding: 8px; }
    .left-nav-title { font-size: 0.68rem; font-weight: 700; color: #94A3B8; text-transform: uppercase; letter-spacing: .08em; padding: 6px 10px 4px; }
    .left-nav-link {
      display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 10px;
      font-size: 0.88rem; font-weight: 500; color: #1A202C; text-decoration: none; transition: background .15s;
    }
    .left-nav-link:hover { background: #F0F2F5; }
    .left-nav-link.active { background: rgba(254,85,22,0.1); color: #FE5516; font-weight: 600; }
    .left-nav-link i { width: 18px; text-align: center; font-size: 0.95rem; }

    /* MAIN FEED */
    .feed-main { display: flex; flex-direction: column; gap: 14px; min-width: 0; }

    /* Create post box */
    .post-create {
      background: #fff; border-radius: 14px; border: 1px solid #E2E8F0;
      padding: 14px 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    }
    .post-create-top { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
    .create-avatar {
      width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
      background: linear-gradient(135deg, #FE5516, #FF8A50);
      display: flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: 0.85rem; color: #fff;
    }
    .post-create-input {
      flex: 1; padding: 10px 16px; border-radius: 22px; border: 1px solid #E2E8F0;
      background: #F8FAFC; font-size: 0.88rem; color: #1A202C; cursor: pointer;
      outline: none; font-family: inherit; transition: border-color .2s, background .2s;
    }
    .post-create-input:focus { border-color: #FE5516; background: #fff; }
    .post-create-divider { border: none; border-top: 1px solid #F0F2F5; margin: 0 0 10px; }
    .post-create-actions { display: flex; gap: 2px; }
    .post-action-btn {
      flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px;
      padding: 8px 4px; border-radius: 8px; border: none; background: none; cursor: pointer;
      font-size: 0.8rem; font-weight: 600; color: #64748B; transition: background .15s; font-family: inherit;
    }
    .post-action-btn:hover { background: #F0F2F5; }
    .post-action-btn.clr-blue { color: #3B82F6; }
    .post-action-btn.clr-green { color: #22C55E; }
    .post-action-btn.clr-orange { color: #FE5516; }

    /* Feed card */
    .feed-card {
      background: #fff; border-radius: 14px; border: 1px solid #E2E8F0;
      box-shadow: 0 1px 4px rgba(0,0,0,0.04); overflow: hidden;
      animation: fadeUp .35s ease both;
    }
    @keyframes fadeUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

    /* Card header */
    .card-header { display: flex; align-items: center; gap: 12px; padding: 14px 16px 10px; }
    .card-avatar {
      width: 42px; height: 42px; border-radius: 50%; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: 0.88rem; color: #fff;
    }
    .ca-orange { background: linear-gradient(135deg, #FE5516, #FF8A50); }
    .ca-blue   { background: linear-gradient(135deg, #3B82F6, #60A5FA); }
    .ca-green  { background: linear-gradient(135deg, #22C55E, #4ADE80); }
    .ca-purple { background: linear-gradient(135deg, #A855F7, #C084FC); }
    .ca-teal   { background: linear-gradient(135deg, #14B8A6, #2DD4BF); }
    .card-meta { flex: 1; min-width: 0; }
    .card-author { font-weight: 700; font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .card-time { font-size: 0.73rem; color: #94A3B8; margin-top: 1px; }
    .card-badge {
      font-size: 0.68rem; font-weight: 700; padding: 3px 10px; border-radius: 20px;
      text-transform: uppercase; letter-spacing: .05em; white-space: nowrap; flex-shrink: 0;
    }
    .badge-event        { background: rgba(59,130,246,0.1);  color: #3B82F6; }
    .badge-post         { background: rgba(34,197,94,0.1);   color: #22C55E; }
    .badge-announcement { background: rgba(254,85,22,0.1);   color: #FE5516; }

    /* Card body */
    .card-body { padding: 0 16px 12px; }
    .card-body h3 { font-size: 1rem; font-weight: 700; margin-bottom: 6px; line-height: 1.4; }
    .card-body p  { font-size: 0.875rem; color: #475569; line-height: 1.65; }

    /* Event banner */
    .event-banner {
      margin: 0 16px 12px; border-radius: 12px;
      background: linear-gradient(135deg, #EFF6FF, #DBEAFE);
      padding: 14px 16px; border: 1px solid rgba(59,130,246,0.15);
      display: flex; gap: 14px; align-items: center;
    }
    .event-date-box {
      width: 52px; height: 52px; border-radius: 12px; background: #3B82F6;
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      color: #fff; flex-shrink: 0;
    }
    .event-date-box .day   { font-size: 1.3rem; font-weight: 800; line-height: 1; }
    .event-date-box .month { font-size: 0.58rem; font-weight: 600; text-transform: uppercase; }
    .event-info .ev-title  { font-weight: 700; font-size: 0.88rem; margin-bottom: 5px; }
    .event-meta { font-size: 0.76rem; color: #64748B; display: flex; flex-wrap: wrap; gap: 10px; }
    .event-meta span { display: flex; align-items: center; gap: 4px; }
    .btn-participer {
      margin: 0 16px 14px; display: inline-flex; align-items: center; gap: 6px;
      padding: 8px 18px; border-radius: 8px; background: #3B82F6; color: #fff;
      border: none; font-size: 0.82rem; font-weight: 700; cursor: pointer;
      font-family: inherit; transition: background .2s;
    }
    .btn-participer:hover { background: #2563EB; }

    /* Announcement banner */
    .announcement-banner {
      margin: 0 16px 12px; border-radius: 12px;
      background: linear-gradient(135deg, #FFF7F4, #FFE8DC);
      padding: 14px 16px; border: 1px solid rgba(254,85,22,0.15);
      display: flex; align-items: center; gap: 12px;
    }
    .announcement-icon { font-size: 2rem; flex-shrink: 0; }
    .announcement-text .ann-title { font-weight: 700; font-size: 0.88rem; margin-bottom: 3px; }
    .announcement-text .ann-desc  { font-size: 0.78rem; color: #64748B; }

    /* Card footer */
    .card-footer { border-top: 1px solid #F0F2F5; }
    .card-stats { padding: 6px 16px; font-size: 0.76rem; color: #64748B; }
    .card-actions { display: flex; border-top: 1px solid #F0F2F5; }
    .card-action {
      flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px;
      padding: 10px 4px; border: none; background: none; cursor: pointer;
      font-size: 0.8rem; font-weight: 600; color: #64748B;
      transition: background .15s, color .15s; font-family: inherit;
    }
    .card-action:hover { background: #F8FAFC; color: #FE5516; }
    .card-action.liked { color: #EF4444; }
    .card-action.liked i { font-weight: 900; }

    /* Comments */
    .card-comments { padding: 0 16px 12px; display: none; }
    .card-comments.open { display: block; }
    .comment-input-row { display: flex; gap: 8px; align-items: center; margin: 10px 0 12px; }
    .comment-avatar {
      width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
      background: linear-gradient(135deg, #FE5516, #FF8A50);
      display: flex; align-items: center; justify-content: center;
      font-size: 0.68rem; font-weight: 700; color: #fff;
    }
    .comment-input {
      flex: 1; padding: 8px 14px; border-radius: 20px; border: 1px solid #E2E8F0;
      background: #F8FAFC; font-size: 0.82rem; outline: none; font-family: inherit; color: #1A202C;
      transition: border-color .2s;
    }
    .comment-input:focus { border-color: #FE5516; background: #fff; }
    .comment-send {
      width: 34px; height: 34px; border-radius: 50%; background: #FE5516; border: none;
      color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; transition: background .2s;
    }
    .comment-send:hover { background: #e84a10; }
    .comment-item { display: flex; gap: 8px; margin-bottom: 10px; }
    .comment-bubble { background: #F0F2F5; border-radius: 0 12px 12px 12px; padding: 8px 12px; flex: 1; }
    .comment-bubble .c-author { font-weight: 700; font-size: 0.76rem; margin-bottom: 2px; }
    .comment-bubble .c-text   { font-size: 0.82rem; color: #475569; }
    .comment-bubble .c-time   { font-size: 0.68rem; color: #94A3B8; margin-top: 4px; }

    /* RIGHT SIDEBAR */
    .feed-right { position: sticky; top: 72px; height: fit-content; display: flex; flex-direction: column; gap: 14px; }
    .widget { background: #fff; border-radius: 14px; border: 1px solid #E2E8F0; padding: 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
    .widget-title { font-size: 0.85rem; font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; color: #1A202C; }
    .widget-title i { color: #FE5516; }
    .widget-event-item { display: flex; gap: 10px; align-items: flex-start; padding: 8px 0; border-bottom: 1px solid #F0F2F5; }
    .widget-event-item:last-child { border-bottom: none; padding-bottom: 0; }
    .widget-date-box {
      width: 40px; height: 40px; border-radius: 10px;
      background: linear-gradient(135deg, #3B82F6, #60A5FA);
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      color: #fff; flex-shrink: 0;
    }
    .widget-date-box .wd { font-size: 1rem; font-weight: 800; line-height: 1; }
    .widget-date-box .wm { font-size: 0.52rem; font-weight: 600; text-transform: uppercase; }
    .widget-ev-title { font-size: 0.8rem; font-weight: 600; margin-bottom: 2px; }
    .widget-ev-loc   { font-size: 0.7rem; color: #94A3B8; }
    .widget-see-all  { display: block; text-align: center; font-size: 0.76rem; color: #FE5516; font-weight: 600; margin-top: 10px; text-decoration: none; }
    .widget-see-all:hover { text-decoration: underline; }
    .community-stat { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; }
    .community-stat:not(:last-child) { border-bottom: 1px solid #F0F2F5; }
    .community-stat-label { font-size: 0.78rem; color: #64748B; display: flex; align-items: center; gap: 6px; }
    .community-stat-value { font-weight: 700; font-size: 0.88rem; }
    .partner-item { display: flex; align-items: center; gap: 10px; padding: 7px 0; border-bottom: 1px solid #F0F2F5; }
    .partner-item:last-child { border-bottom: none; }
    .partner-dot { width: 8px; height: 8px; border-radius: 50%; background: #22C55E; flex-shrink: 0; }
    .partner-name { font-size: 0.8rem; font-weight: 600; }
    .partner-type { font-size: 0.7rem; color: #94A3B8; }

    /* Post modal */
    .post-modal-overlay {
      position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 300;
      display: none; align-items: center; justify-content: center; padding: 16px;
    }
    .post-modal-overlay.open { display: flex; }
    .post-modal {
      background: #fff; border-radius: 16px; width: 100%; max-width: 520px;
      box-shadow: 0 16px 48px rgba(0,0,0,0.2); animation: menuPop .2s ease;
    }
    .post-modal-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 16px 20px; border-bottom: 1px solid #E2E8F0;
    }
    .post-modal-header h3 { font-size: 1rem; font-weight: 700; }
    .modal-close {
      width: 32px; height: 32px; border-radius: 50%; background: #F0F2F5;
      border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;
      font-size: 0.9rem; color: #64748B; transition: background .15s;
    }
    .modal-close:hover { background: #E2E8F0; }
    .post-modal-body { padding: 16px 20px; }
    .modal-user-row { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
    .modal-avatar {
      width: 40px; height: 40px; border-radius: 50%;
      background: linear-gradient(135deg, #FE5516, #FF8A50);
      display: flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: 0.85rem; color: #fff; flex-shrink: 0;
    }
    .modal-user-name { font-weight: 700; font-size: 0.9rem; }
    .modal-user-sub  { font-size: 0.72rem; color: #94A3B8; }
    .post-textarea {
      width: 100%; min-height: 120px; border: none; outline: none; resize: none;
      font-size: 0.95rem; font-family: inherit; color: #1A202C; background: transparent;
      line-height: 1.6;
    }
    .post-modal-footer { padding: 12px 20px; border-top: 1px solid #E2E8F0; display: flex; justify-content: flex-end; }
    .btn-publish {
      background: #FE5516; color: #fff; border: none; padding: 10px 24px;
      border-radius: 8px; font-weight: 700; cursor: pointer; font-family: inherit;
      font-size: 0.88rem; transition: background .2s;
    }
    .btn-publish:hover { background: #e84a10; }
    .btn-publish:disabled { background: #CBD5E1; cursor: not-allowed; }

    /* Empty state */
    .feed-empty { text-align: center; padding: 48px 24px; color: #94A3B8; }
    .feed-empty i { font-size: 2.5rem; margin-bottom: 12px; display: block; }
    .feed-empty p { font-size: 0.88rem; }

    /* Responsive */
    @media (max-width: 1100px) {
      .feed-layout { grid-template-columns: minmax(0, 720px); }
      .feed-right { display: none; }
    }
    @media (max-width: 680px) {
      .nav-search { max-width: 160px; }
    }
    @media (max-width: 420px) {
      .nav-search { display: none; }
    }
  </style>
</head>
<body>

  <!-- TOP NAVBAR -->
  <nav class="feed-nav">
    <div style="display: flex; align-items: center; gap: 12px;">
      <a href="/projet2a22/View/FrontOffice/feed.php" class="nav-logo">
        <img src="/projet2a22/assets/logo.png" alt="CareMeal" style="height:30px;width:auto;">
        Care<span>Meal</span>
      </a>

      <div class="nav-search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" placeholder="Rechercher..." id="feed-search" autocomplete="off">
      </div>
    </div>

    <div class="nav-actions">
      <a href="/projet2a22/View/FrontOffice/student/dashboard.php" id="top-dash-btn" class="nav-tab" title="Mon Dashboard" style="color: #FE5516; background: rgba(254,85,22,0.1); border-radius: 20px; padding: 0 16px; width: auto; font-size: 0.95rem; font-weight: 600; margin-right: 8px;">
        <i class="fa-solid fa-chart-line" style="margin-right: 8px;"></i> Espace Étudiant
      </a>
      <button class="nav-tab" id="btn-notif" title="Notifications" style="margin-right: 8px;">
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
            <div class="menu-user-role" id="menu-user-role">Etudiant</div>
          </div>
          <a href="/projet2a22/View/FrontOffice/student/dashboard.php" id="avatar-dash-link">
            <i class="fa-solid fa-chart-line"></i> Mon Dashboard
          </a>
          <hr class="menu-divider">
          <button class="menu-danger" onclick="Feed.logout()">
            <i class="fa-solid fa-arrow-right-from-bracket"></i> Deconnexion
          </button>
        </div>
      </div>
    </div>
  </nav>

  <!-- MAIN LAYOUT -->
  <div class="feed-layout">



    <!-- MAIN FEED -->
    <main class="feed-main" id="feed-main">

      <!-- Create post box -->
      <div class="post-create">
        <div class="post-create-top">
          <div class="create-avatar" id="create-avatar">?</div>
          <input
            type="text"
            class="post-create-input"
            placeholder="Quoi de neuf ? Partagez avec la communaute..."
            readonly
            onclick="Feed.openPostModal()"
          >
        </div>
        <hr class="post-create-divider">
        <div class="post-create-actions">
          <button class="post-action-btn clr-blue" onclick="Feed.openPostModal()">
            <i class="fa-solid fa-pen-to-square"></i> Ecrire un post
          </button>
          <button class="post-action-btn clr-orange" onclick="Feed.scrollToEvents()">
            <i class="fa-solid fa-calendar-days"></i> Evenements
          </button>
          <button class="post-action-btn clr-green" onclick="Feed.openPostModal()">
            <i class="fa-solid fa-image"></i> Photo
          </button>
        </div>
      </div>

      <!-- Feed items -->
      <div id="feed-items">
        <div class="feed-empty">
          <i class="fa-solid fa-spinner fa-spin"></i>
          <p>Chargement du fil...</p>
        </div>
      </div>

    </main>

    <!-- RIGHT SIDEBAR -->
    <aside class="feed-right">

      <!-- Upcoming events widget -->
      <div class="widget">
        <div class="widget-title">
          <i class="fa-solid fa-calendar-days"></i> Evenements a venir
        </div>
        <div id="widget-events-list">
          <div style="font-size:0.78rem;color:#94A3B8;text-align:center;padding:10px;">Chargement...</div>
        </div>
        <a href="#" class="widget-see-all" onclick="Feed.scrollToEvents(); return false;">
          Voir tous les evenements &rarr;
        </a>
      </div>

      <!-- Daily Offers Widget -->
      <div class="widget">
        <div class="widget-title">
          <i class="fa-solid fa-fire" style="color:#FE5516;"></i> Offres du jour
        </div>
        <div id="widget-offers-list">
          <div style="font-size:0.78rem;color:#94A3B8;text-align:center;padding:10px;">Chargement...</div>
        </div>
      </div>

      <!-- Community widget -->
      <div class="widget">
        <div class="widget-title">
          <i class="fa-solid fa-users"></i> Communaute
        </div>
        <div class="community-stat">
          <span class="community-stat-label">
            <i class="fa-solid fa-user-group" style="color:#3B82F6;"></i> Membres actifs
          </span>
          <span class="community-stat-value" id="stat-members">350+</span>
        </div>
        <div class="community-stat">
          <span class="community-stat-label">
            <i class="fa-solid fa-fire" style="color:#FE5516;"></i> Posts ce mois
          </span>
          <span class="community-stat-value" id="stat-posts">0</span>
        </div>
        <div class="community-stat">
          <span class="community-stat-label">
            <i class="fa-solid fa-calendar-check" style="color:#22C55E;"></i> Evenements planifies
          </span>
          <span class="community-stat-value" id="stat-events">0</span>
        </div>
        <div class="community-stat">
          <span class="community-stat-label">
            <i class="fa-solid fa-heart" style="color:#EF4444;"></i> J'aime total
          </span>
          <span class="community-stat-value" id="stat-likes">0</span>
        </div>
      </div>

      <!-- Active partners widget -->
      <div class="widget">
        <div class="widget-title">
          <i class="fa-solid fa-store"></i> Partenaires actifs
        </div>
        <div id="widget-partners-list">
          <div style="font-size:0.78rem;color:#94A3B8;text-align:center;padding:10px;">Chargement...</div>
        </div>
      </div>

    </aside>
  </div>

  <!-- POST MODAL -->
  <div class="post-modal-overlay" id="post-modal-overlay" onclick="Feed.closePostModal(event)">
    <div class="post-modal" onclick="event.stopPropagation()">
      <div class="post-modal-header">
        <h3>Creer un post</h3>
        <button class="modal-close" onclick="Feed.closePostModal()">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>
      <div class="post-modal-body">
        <div class="modal-user-row">
          <div class="modal-avatar" id="modal-avatar">?</div>
          <div>
            <div class="modal-user-name" id="modal-user-name">Utilisateur</div>
            <div class="modal-user-sub"><i class="fa-solid fa-earth-europe" style="margin-right:4px;"></i>Public</div>
          </div>
        </div>
        <textarea
          class="post-textarea"
          id="post-content"
          placeholder="Quoi de neuf ? Partagez une experience, une astuce, une bonne adresse..."
          oninput="Feed.onPostInput()"
        ></textarea>
      </div>
      <div class="post-modal-footer">
        <button class="btn-publish" id="btn-publish" onclick="Feed.submitPost()" disabled>Publier</button>
      </div>
    </div>
  </div>

  <script src="/projet2a22/js/app.js"></script>
  <script src="/projet2a22/js/components.js"></script>
  <script>
  const Feed = {

    // ── Init ──────────────────────────────────────────────────────────────────
    init() {
      if (!App.requireAuth(['student', 'partner', 'admin'])) return;
      const user = App.getCurrentUser();
      if (!user) return;

      const name = user.prenom
        ? (user.prenom + ' ' + (user.nom || '')).trim()
        : (user.name || 'Utilisateur');
      const initials = App.getInitials(name);

      // Avatar initials
      ['nav-avatar-initials', 'left-avatar', 'create-avatar', 'modal-avatar'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.textContent = initials;
      });

      // Names
      ['left-name', 'menu-user-name', 'modal-user-name'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.textContent = name;
      });

      // Role label
      const roleEl = document.getElementById('menu-user-role');
      if (roleEl) {
        const labels = { student: 'Etudiant', partner: 'Partenaire', admin: 'Administrateur' };
        roleEl.textContent = labels[user.role] || 'Utilisateur';
      }

      // Dynamic links based on role
      const dashLink = document.getElementById('avatar-dash-link');
      if (dashLink) {
        dashLink.href = user.role === 'partner' 
          ? '/projet2a22/View/FrontOffice/partner/dashboard.php' 
          : '/projet2a22/View/FrontOffice/student/dashboard.php';
      }

      const topDashBtn = document.getElementById('top-dash-btn');
      if (topDashBtn && user.role === 'partner') {
        topDashBtn.href = '/projet2a22/View/FrontOffice/partner/dashboard.php';
        topDashBtn.innerHTML = '<i class="fa-solid fa-store" style="margin-right: 8px;"></i> Espace Partenaire';
      }

      if (user.role === 'partner') {
        const leftRole = document.querySelector('.feed-left-user .left-user-role');
        if (leftRole) leftRole.textContent = "Partenaire CareMeal";
        
        const leftUserLink = document.querySelector('.feed-left-user');
        if (leftUserLink) leftUserLink.href = "/projet2a22/View/FrontOffice/partner/dashboard.php";
      }

      // Build feed and widgets
      this.buildFeed();
      this.loadWidgets();

      // Close avatar menu on outside click
      document.addEventListener('click', (e) => {
        const wrap = document.getElementById('nav-avatar');
        if (wrap && !wrap.contains(e.target)) {
          document.getElementById('avatar-menu').classList.remove('open');
        }
      });
    },

    // ── Avatar dropdown ───────────────────────────────────────────────────────
    toggleAvatarMenu(e) {
      e.stopPropagation();
      document.getElementById('avatar-menu').classList.toggle('open');
    },

    // ── Logout ────────────────────────────────────────────────────────────────
    logout() {
      if (typeof App.logout === 'function') {
        App.logout();
      } else {
        localStorage.removeItem('caremeal_current_user');
        window.location.href = '/projet2a22/View/FrontOffice/login.php';
      }
    },

    // ── Build feed ────────────────────────────────────────────────────────────
    buildFeed() {
      const events = App.getEvents().filter(e => {
        const s = (e.status || '').toLowerCase();
        return s.includes('planifie') || s.includes('en cours') || s.includes('en attente');
      });

      const posts         = this._getMockPosts();
      const announcements = this._getMockAnnouncements();

      // Merge: events + community posts + partner announcements (NO offers)
      const items = [
        ...events.map(e => ({
          type: 'event',
          data: e,
          ts: new Date(e.date).getTime()
        })),
        ...posts.map(p => ({
          type: 'post',
          data: p,
          ts: p.ts
        })),
        ...announcements.map(a => ({
          type: 'announcement',
          data: a,
          ts: a.ts
        }))
      ].sort((a, b) => b.ts - a.ts);

      const container = document.getElementById('feed-items');

      if (!items.length) {
        container.innerHTML = '<div class="feed-empty"><i class="fa-solid fa-inbox"></i><p>Aucun contenu pour le moment.<br>Soyez le premier a publier !</p></div>';
        return;
      }

      container.innerHTML = items.map((item, idx) => {
        const delay = (idx * 0.04).toFixed(2);
        if (item.type === 'event')        return this._renderEvent(item.data, delay);
        if (item.type === 'announcement') return this._renderAnnouncement(item.data, delay);
        return this._renderPost(item.data, delay);
      }).join('');
    },

    // ── Render: Event card ────────────────────────────────────────────────────
    _renderEvent(e, delay) {
      const d     = new Date(e.date);
      const day   = d.getDate();
      const month = d.toLocaleDateString('fr-FR', { month: 'short' });
      const key   = 'event_' + e.id;
      const likes = this._getLikes(key);
      const liked = this._isLiked(key);
      const comments = this._getComments(key);
      const typeIcon = e.type === 'En ligne'
        ? '<i class="fa-solid fa-video"></i>'
        : '<i class="fa-solid fa-location-dot"></i>';

      return `
        <div class="feed-card" style="animation-delay:${delay}s;" id="card-${key}">
          <div class="card-header">
            <div class="card-avatar ca-blue">${App.getInitials(e.partnerName || 'CareMeal')}</div>
            <div class="card-meta">
              <div class="card-author">${e.partnerName || 'CareMeal'}</div>
              <div class="card-time"><i class="fa-solid fa-clock" style="margin-right:3px;"></i>${App.timeAgo ? App.timeAgo(e.date) : e.date}</div>
            </div>
            <span class="card-badge badge-event"><i class="fa-solid fa-calendar-days"></i> Evenement</span>
          </div>
          <div class="card-body">
            <h3>${e.title}</h3>
            <p>${e.description}</p>
          </div>
          <div class="event-banner">
            <div class="event-date-box">
              <span class="day">${day}</span>
              <span class="month">${month}</span>
            </div>
            <div class="event-info">
              <div class="ev-title">${e.title}</div>
              <div class="event-meta">
                <span><i class="fa-solid fa-clock"></i> ${e.startTime} &ndash; ${e.endTime}</span>
                <span>${typeIcon} ${e.location}</span>
                <span><i class="fa-solid fa-users"></i> ${e.capacity} places</span>
              </div>
            </div>
          </div>
          <button class="btn-participer" onclick="Feed.joinEvent('${e.id}', this)">
            <i class="fa-solid fa-calendar-check"></i> Participer
          </button>
          <div class="card-footer">
            <div class="card-stats" id="stats-${key}">
              ${likes > 0 ? '<i class="fa-solid fa-heart" style="color:#EF4444;margin-right:4px;"></i>' + likes + ' J\'aime' : ''}
            </div>
            <div class="card-actions">
              <button class="card-action${liked ? ' liked' : ''}" onclick="Feed.toggleLike('${key}', this)">
                <i class="fa-${liked ? 'solid' : 'regular'} fa-heart"></i> J'aime
              </button>
              <button class="card-action" onclick="Feed.toggleComments('${key}')">
                <i class="fa-regular fa-comment"></i> Commenter
              </button>
              <button class="card-action" onclick="Feed.shareCard('${e.title}')">
                <i class="fa-solid fa-share-nodes"></i> Partager
              </button>
            </div>
            <div class="card-comments" id="comments-${key}">
              ${this._renderCommentSection(comments, key)}
            </div>
          </div>
        </div>`;
    },

    // ── Render: Community post card ───────────────────────────────────────────
    _renderPost(p, delay) {
      const key      = 'post_' + p.id;
      const likes    = this._getLikes(key);
      const liked    = this._isLiked(key);
      const comments = this._getComments(key);
      const colorMap = { orange: 'ca-orange', blue: 'ca-blue', green: 'ca-green', purple: 'ca-purple', teal: 'ca-teal' };
      const avatarClass = colorMap[p.avatarColor] || 'ca-orange';

      return `
        <div class="feed-card" style="animation-delay:${delay}s;" id="card-${key}">
          <div class="card-header">
            <div class="card-avatar ${avatarClass}">${p.initials || App.getInitials(p.author)}</div>
            <div class="card-meta">
              <div class="card-author">${p.author}</div>
              <div class="card-time">${App.timeAgo ? App.timeAgo(new Date(p.ts).toISOString()) : 'Recemment'}</div>
            </div>
            <span class="card-badge badge-post"><i class="fa-solid fa-pen"></i> Post</span>
          </div>
          <div class="card-body">
            <p style="font-size:0.92rem;">${p.content}</p>
          </div>
          <div class="card-footer">
            <div class="card-stats" id="stats-${key}">
              ${likes > 0 ? '<i class="fa-solid fa-heart" style="color:#EF4444;margin-right:4px;"></i>' + likes + ' J\'aime' : ''}
            </div>
            <div class="card-actions">
              <button class="card-action${liked ? ' liked' : ''}" onclick="Feed.toggleLike('${key}', this)">
                <i class="fa-${liked ? 'solid' : 'regular'} fa-heart"></i> J'aime
              </button>
              <button class="card-action" onclick="Feed.toggleComments('${key}')">
                <i class="fa-regular fa-comment"></i> Commenter
              </button>
              <button class="card-action" onclick="Feed.shareCard('${p.author}')">
                <i class="fa-solid fa-share-nodes"></i> Partager
              </button>
            </div>
            <div class="card-comments" id="comments-${key}">
              ${this._renderCommentSection(comments, key)}
            </div>
          </div>
        </div>`;
    },

    // ── Render: Partner announcement card ─────────────────────────────────────
    _renderAnnouncement(a, delay) {
      const key      = 'ann_' + a.id;
      const likes    = this._getLikes(key);
      const liked    = this._isLiked(key);
      const comments = this._getComments(key);

      return `
        <div class="feed-card" style="animation-delay:${delay}s;" id="card-${key}">
          <div class="card-header">
            <div class="card-avatar ca-orange">${App.getInitials(a.partner)}</div>
            <div class="card-meta">
              <div class="card-author">${a.partner}</div>
              <div class="card-time">${App.timeAgo ? App.timeAgo(new Date(a.ts).toISOString()) : 'Recemment'}</div>
            </div>
            <span class="card-badge badge-announcement"><i class="fa-solid fa-bullhorn"></i> Annonce</span>
          </div>
          <div class="card-body">
            <h3>${a.title}</h3>
            <p>${a.content}</p>
          </div>
          <div class="announcement-banner">
            <div class="announcement-icon">${a.emoji}</div>
            <div class="announcement-text">
              <div class="ann-title">${a.partner}</div>
              <div class="ann-desc">${a.subtitle}</div>
            </div>
          </div>
          <div class="card-footer">
            <div class="card-stats" id="stats-${key}">
              ${likes > 0 ? '<i class="fa-solid fa-heart" style="color:#EF4444;margin-right:4px;"></i>' + likes + ' J\'aime' : ''}
            </div>
            <div class="card-actions">
              <button class="card-action${liked ? ' liked' : ''}" onclick="Feed.toggleLike('${key}', this)">
                <i class="fa-${liked ? 'solid' : 'regular'} fa-heart"></i> J'aime
              </button>
              <button class="card-action" onclick="Feed.toggleComments('${key}')">
                <i class="fa-regular fa-comment"></i> Commenter
              </button>
              <button class="card-action" onclick="Feed.shareCard('${a.title}')">
                <i class="fa-solid fa-share-nodes"></i> Partager
              </button>
            </div>
            <div class="card-comments" id="comments-${key}">
              ${this._renderCommentSection(comments, key)}
            </div>
          </div>
        </div>`;
    },

    // ── Render: Comment section ───────────────────────────────────────────────
    _renderCommentSection(comments, key) {
      const user     = App.getCurrentUser();
      const initials = user ? App.getInitials(user.name || ((user.prenom || '') + ' ' + (user.nom || '')).trim()) : '?';
      const commentsHtml = comments.map(c => `
        <div class="comment-item">
          <div class="comment-avatar">${App.getInitials(c.author)}</div>
          <div class="comment-bubble">
            <div class="c-author">${c.author}</div>
            <div class="c-text">${c.text}</div>
            <div class="c-time">${App.timeAgo ? App.timeAgo(c.ts) : 'Recemment'}</div>
          </div>
        </div>`).join('');

      return `
        <div class="comment-input-row">
          <div class="comment-avatar">${initials}</div>
          <input
            type="text"
            class="comment-input"
            placeholder="Ecrire un commentaire..."
            id="input-${key}"
            onkeydown="if(event.key==='Enter')Feed.addComment('${key}')"
          >
          <button class="comment-send" onclick="Feed.addComment('${key}')">
            <i class="fa-solid fa-paper-plane"></i>
          </button>
        </div>
        <div id="comments-list-${key}">${commentsHtml}</div>`;
    },

    // ── Interactions ──────────────────────────────────────────────────────────
    toggleLike(key, btn) {
      const likes = JSON.parse(localStorage.getItem('cm_likes') || '{}');
      const user  = App.getCurrentUser();
      const uid   = user ? user.id : 'guest';
      const lk    = key + '_' + uid;

      if (likes[lk]) {
        delete likes[lk];
        btn.classList.remove('liked');
        btn.innerHTML = '<i class="fa-regular fa-heart"></i> J\'aime';
      } else {
        likes[lk] = true;
        btn.classList.add('liked');
        btn.innerHTML = '<i class="fa-solid fa-heart"></i> J\'aime';
      }
      localStorage.setItem('cm_likes', JSON.stringify(likes));

      const count  = Object.keys(likes).filter(k => k.startsWith(key + '_')).length;
      const statsEl = document.getElementById('stats-' + key);
      if (statsEl) {
        statsEl.innerHTML = count > 0
          ? '<i class="fa-solid fa-heart" style="color:#EF4444;margin-right:4px;"></i>' + count + ' J\'aime'
          : '';
      }
    },

    _getLikes(key) {
      const likes = JSON.parse(localStorage.getItem('cm_likes') || '{}');
      return Object.keys(likes).filter(k => k.startsWith(key + '_')).length;
    },

    _isLiked(key) {
      const likes = JSON.parse(localStorage.getItem('cm_likes') || '{}');
      const user  = App.getCurrentUser();
      const uid   = user ? user.id : 'guest';
      return !!likes[key + '_' + uid];
    },

    toggleComments(key) {
      const el = document.getElementById('comments-' + key);
      if (el) {
        el.classList.toggle('open');
        if (el.classList.contains('open')) {
          const input = document.getElementById('input-' + key);
          if (input) setTimeout(() => input.focus(), 50);
        }
      }
    },

    addComment(key) {
      const input = document.getElementById('input-' + key);
      if (!input || !input.value.trim()) return;

      const user   = App.getCurrentUser();
      const author = user
        ? (user.prenom ? (user.prenom + ' ' + (user.nom || '')).trim() : user.name)
        : 'Anonyme';
      const comment = { author, text: input.value.trim(), ts: new Date().toISOString() };

      const stored = JSON.parse(localStorage.getItem('cm_comments') || '{}');
      if (!stored[key]) stored[key] = [];
      stored[key].unshift(comment);
      localStorage.setItem('cm_comments', JSON.stringify(stored));

      const list = document.getElementById('comments-list-' + key);
      if (list) {
        const div = document.createElement('div');
        div.className = 'comment-item';
        div.innerHTML = `
          <div class="comment-avatar">${App.getInitials(author)}</div>
          <div class="comment-bubble">
            <div class="c-author">${author}</div>
            <div class="c-text">${comment.text}</div>
            <div class="c-time">A l'instant</div>
          </div>`;
        list.prepend(div);
      }
      input.value = '';

      // Update community stats
      this._updateCommunityStats();
    },

    _getComments(key) {
      const stored = JSON.parse(localStorage.getItem('cm_comments') || '{}');
      return (stored[key] || []).slice(0, 3);
    },

    joinEvent(id, btn) {
      if (btn) {
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Inscrit !';
        btn.style.background = '#22C55E';
        btn.disabled = true;
      }
      if (typeof Components !== 'undefined' && Components.showToast) {
        Components.showToast('Evenement', 'Vous etes inscrit a cet evenement !', 'success');
      }
    },

    shareCard(title) {
      if (navigator.share) {
        navigator.share({ title: 'CareMeal — ' + title, url: window.location.href });
      } else if (typeof Components !== 'undefined' && Components.showToast) {
        Components.showToast('Partage', 'Lien copie dans le presse-papier !', 'info');
      }
    },

    scrollToEvents() {
      const first = document.querySelector('.badge-event');
      if (first) {
        first.closest('.feed-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    },

    // ── Post modal ────────────────────────────────────────────────────────────
    openPostModal() {
      document.getElementById('post-modal-overlay').classList.add('open');
      setTimeout(() => {
        const ta = document.getElementById('post-content');
        if (ta) ta.focus();
      }, 100);
    },

    closePostModal(e) {
      if (!e || e.target === document.getElementById('post-modal-overlay')) {
        document.getElementById('post-modal-overlay').classList.remove('open');
        document.getElementById('post-content').value = '';
        document.getElementById('btn-publish').disabled = true;
      }
    },

    onPostInput() {
      const val = (document.getElementById('post-content').value || '').trim();
      document.getElementById('btn-publish').disabled = val.length === 0;
    },

    submitPost() {
      const content = (document.getElementById('post-content').value || '').trim();
      if (!content) return;

      const user   = App.getCurrentUser();
      const author = user
        ? (user.prenom ? (user.prenom + ' ' + (user.nom || '')).trim() : user.name)
        : 'Anonyme';
      const colors = ['orange', 'blue', 'green', 'purple', 'teal'];
      const newPost = {
        id:          'p_' + Date.now(),
        author,
        initials:    App.getInitials(author),
        avatarColor: colors[Math.floor(Math.random() * colors.length)],
        content,
        ts:          Date.now()
      };

      const stored = JSON.parse(localStorage.getItem('cm_posts') || '[]');
      stored.unshift(newPost);
      localStorage.setItem('cm_posts', JSON.stringify(stored));

      // Prepend to feed
      const container = document.getElementById('feed-items');
      const div = document.createElement('div');
      div.innerHTML = this._renderPost(newPost, 0);
      const card = div.firstElementChild;
      if (card) container.prepend(card);

      document.getElementById('post-content').value = '';
      document.getElementById('btn-publish').disabled = true;
      this.closePostModal();

      this._updateCommunityStats();

      if (typeof Components !== 'undefined' && Components.showToast) {
        Components.showToast('Publie !', 'Votre post est visible par la communaute.', 'success');
      }
    },

    // ── Widgets ───────────────────────────────────────────────────────────────
    loadWidgets() {
      this._loadEventsWidget();
      this._loadPartnersWidget();
      this._loadOffersWidget();
      this._updateCommunityStats();
    },

    _loadOffersWidget() {
      const offers = App.getOffers().filter(o => o.status === 'active').slice(0, 3);
      const el = document.getElementById('widget-offers-list');
      if (!el) return;

      if (!offers.length) {
        el.innerHTML = '<div style="font-size:0.78rem;color:#94A3B8;text-align:center;padding:8px;">Aucune offre du jour</div>';
        return;
      }

      el.innerHTML = offers.map(o => `
        <div style="display:flex; gap:10px; padding:8px 0; border-bottom:1px solid #F0F2F5;">
          <div style="font-size:1.5rem; display:flex; align-items:center; justify-content:center; background:#FFF7F4; border-radius:8px; width:40px; height:40px;">${o.emoji || '🍽️'}</div>
          <div style="flex:1;">
            <div style="font-size:0.85rem; font-weight:700;">${o.title}</div>
            <div style="font-size:0.75rem; color:#64748B;">${o.partnerName}</div>
            <div style="font-size:0.8rem; font-weight:600; color:#FE5516;">${o.price.toFixed(1)} DT <span style="text-decoration:line-through; color:#94A3B8; font-size:0.7rem; margin-left:4px;">${o.originalPrice.toFixed(1)} DT</span></div>
          </div>
        </div>
      `).join('');
    },

    _loadEventsWidget() {
      const events = App.getEvents()
        .filter(e => {
          const s = (e.status || '').toLowerCase();
          return s.includes('planifie') || s.includes('en cours');
        })
        .sort((a, b) => new Date(a.date) - new Date(b.date))
        .slice(0, 4);

      const el = document.getElementById('widget-events-list');
      if (!el) return;

      if (!events.length) {
        el.innerHTML = '<div style="font-size:0.78rem;color:#94A3B8;text-align:center;padding:8px;">Aucun evenement a venir</div>';
        return;
      }

      el.innerHTML = events.map(e => {
        const d = new Date(e.date);
        return `
          <div class="widget-event-item">
            <div class="widget-date-box">
              <span class="wd">${d.getDate()}</span>
              <span class="wm">${d.toLocaleDateString('fr-FR', { month: 'short' })}</span>
            </div>
            <div>
              <div class="widget-ev-title">${e.title}</div>
              <div class="widget-ev-loc"><i class="fa-solid fa-location-dot" style="margin-right:3px;"></i>${e.location}</div>
            </div>
          </div>`;
      }).join('');

      // Update stat
      const statEl = document.getElementById('stat-events');
      if (statEl) statEl.textContent = events.length;
    },

    _loadPartnersWidget() {
      const users = App.getUsers ? App.getUsers() : [];
      const partners = users.filter(u => u.role === 'partner' && u.status === 'active');

      const el = document.getElementById('widget-partners-list');
      if (!el) return;

      if (!partners.length) {
        // Fallback mock partners
        const mock = [
          { name: 'La Baguette Doree', type: 'Boulangerie' },
          { name: 'Chez Sami', type: 'Restaurant' },
          { name: 'Green Bowl Cafe', type: 'Cafeteria' }
        ];
        el.innerHTML = mock.map(p => `
          <div class="partner-item">
            <div class="partner-dot"></div>
            <div>
              <div class="partner-name">${p.name}</div>
              <div class="partner-type">${p.type}</div>
            </div>
          </div>`).join('');
        return;
      }

      el.innerHTML = partners.slice(0, 5).map(p => `
        <div class="partner-item">
          <div class="partner-dot"></div>
          <div>
            <div class="partner-name">${p.name}</div>
            <div class="partner-type">${p.type || 'Partenaire'}</div>
          </div>
        </div>`).join('');
    },

    _updateCommunityStats() {
      // Posts count
      const posts = JSON.parse(localStorage.getItem('cm_posts') || '[]');
      const statPosts = document.getElementById('stat-posts');
      if (statPosts) statPosts.textContent = posts.length + 3; // +3 for defaults

      // Events count
      const events = App.getEvents().filter(e => {
        const s = (e.status || '').toLowerCase();
        return s.includes('planifie') || s.includes('en cours');
      });
      const statEvents = document.getElementById('stat-events');
      if (statEvents) statEvents.textContent = events.length;

      // Likes count
      const likes = JSON.parse(localStorage.getItem('cm_likes') || '{}');
      const statLikes = document.getElementById('stat-likes');
      if (statLikes) statLikes.textContent = Object.keys(likes).length;
    },

    // ── Mock data ─────────────────────────────────────────────────────────────
    _getMockPosts() {
      const stored = JSON.parse(localStorage.getItem('cm_posts') || '[]');
      const defaults = [
        {
          id: 'mp1', author: 'Yasmine T.', initials: 'YT', avatarColor: 'purple',
          content: '🌱 Aujourd\'hui j\'ai sauve mon 10eme repas avec CareMeal ! La boulangerie pres de l\'ESPRIT est incroyable. Je recommande a tous les etudiants !',
          ts: Date.now() - 3600000
        },
        {
          id: 'mp2', author: 'Adem B.', initials: 'AB', avatarColor: 'blue',
          content: '💡 Astuce : commandez tot le matin pour avoir les meilleures offres de viennoiseries. J\'economise 60% sur mon petit-dejeuner chaque jour !',
          ts: Date.now() - 7200000
        },
        {
          id: 'mp3', author: 'Sarra H.', initials: 'SH', avatarColor: 'green',
          content: '🎉 Je viens d\'atteindre le niveau 3 sur CareMeal ! 34 commandes et 12 repas sauves. Merci a toute la communaute 💚',
          ts: Date.now() - 10800000
        },
        {
          id: 'mp4', author: 'Louay C.', initials: 'LC', avatarColor: 'teal',
          content: '🍱 Petit-dejeuner solidaire ce matin au campus ISG — des viennoiseries gratuites offertes par Araek Campus. Merci CareMeal pour l\'info !',
          ts: Date.now() - 18000000
        }
      ];
      return [...stored, ...defaults];
    },

    _getMockAnnouncements() {
      return [
        {
          id: 'ann1',
          partner: 'La Baguette Doree',
          title: 'Nouveau partenariat avec l\'ESPRIT',
          content: 'Nous sommes ravis d\'annoncer notre partenariat officiel avec l\'ESPRIT ! Les etudiants beneficieront de ramassages prioritaires chaque soir a partir de 18h.',
          subtitle: 'Partenaire officiel ESPRIT',
          emoji: '��',
          ts: Date.now() - 5400000
        },
        {
          id: 'ann2',
          partner: 'Chez Sami',
          title: 'Horaires etendus ce Ramadan',
          content: 'Durant le mois de Ramadan, nos horaires de ramassage sont etendus jusqu\'a 22h. Profitez de nos plats traditionnels a prix reduit !',
          subtitle: 'Horaires speciaux Ramadan',
          emoji: '🌙',
          ts: Date.now() - 86400000
        },
        {
          id: 'ann3',
          partner: 'Admin CareMeal',
          title: 'Mise a jour de l\'application',
          content: 'La nouvelle version de CareMeal est disponible ! Nouvelles fonctionnalites : fil d\'actualite communautaire, filtres ameliores et notifications en temps reel.',
          subtitle: 'Version 2.0 disponible',
          emoji: '🚀',
          ts: Date.now() - 172800000
        }
      ];
    }
  };

  document.addEventListener('DOMContentLoaded', () => {
    App.init();
    Feed.init();
  });
  </script>
</body>
</html>






