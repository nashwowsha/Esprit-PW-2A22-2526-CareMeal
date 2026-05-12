const StudentLayout = {
  pages: [
    { key: "dashboard", href: "View/FrontOffice/student/dashboard.php", label: "Accueil", icon: "fa-house" },
    { key: "profile", href: "View/FrontOffice/student/profile.php", label: "Mon Profil", icon: "fa-user" },
    { key: "preferences", href: "student/preferences.php", label: "Pr\u00e9f\u00e9rences", icon: "fa-utensils" },
    { key: "events", href: "View/FrontOffice/student/events.php", label: "\u00c9v\u00e9nements", icon: "fa-calendar-day" },
    { key: "orders", href: "View/FrontOffice/student/orders.php", label: "Mes Commandes", icon: "fa-box" },
    { key: "mes_collectes", href: "View/FrontOffice/student/mes_collectes.php", label: "Mes Collectes", icon: "fa-box-archive" },
  ],

  init() {
    const nav = document.querySelector(".sidebar-nav");
    const headerRight = document.querySelector(".top-header .header-right");
    if (!nav && !headerRight) return;
    this.renderSidebar();
    this.ensureTopHeaderActions();
  },

  currentPageKey() {
    const file = (window.location.pathname.split("/").pop() || "").toLowerCase();
    if (file.startsWith("profile")) return "profile";
    if (file.startsWith("preferences")) return "preferences";
    if (file.startsWith("matching")) return "preferences";
    if (file.startsWith("create_collecte")) return "preferences";
    if (file.startsWith("events")) return "events";
    if (file.startsWith("orders")) return "orders";
    if (file.startsWith("mes_collectes")) return "mes_collectes";
    if (file.startsWith("collecte_detail")) return "mes_collectes";
    if (file.startsWith("settings")) return "settings";
    return "dashboard";
  },

  path(relativePath) {
    if (typeof window.caremealPath === "function") return window.caremealPath(relativePath);
    return "/" + String(relativePath || "").replace(/^\/+/, "");
  },

  renderSidebar() {
    const nav = document.querySelector(".sidebar-nav");
    if (!nav) return;

    const active = this.currentPageKey();
    const menuLinks = this.pages
      .map((item) => {
        const isActive = item.key === active ? " active" : "";
        return `<a href="${this.path(item.href)}" class="sidebar-link${isActive}"><span class="link-icon"><i class="fa-solid ${item.icon}"></i></span> ${item.label}</a>`;
      })
      .join("");

    nav.innerHTML = `
      <div class="sidebar-section">
        <div class="sidebar-section-title">Menu</div>
        ${menuLinks}
      </div>
      <div class="sidebar-section">
        <div class="sidebar-section-title">Param\u00e8tres</div>
        <a href="${this.path("View/FrontOffice/student/settings.php")}" class="sidebar-link${active === "settings" ? " active" : ""}"><span class="link-icon"><i class="fa-solid fa-gear"></i></span> Param\u00e8tres</a>
      </div>
    `;

    const logo = document.querySelector(".sidebar-logo img");
    if (logo) logo.src = this.path("assets/logo.png");

    const logoutBtn = document.querySelector(".sidebar-logout");
    if (logoutBtn) logoutBtn.innerHTML = '<i class="fa-solid fa-arrow-right-from-bracket"></i>';
  },

  ensureTopHeaderActions() {
    const right = document.querySelector(".top-header .header-right");
    if (!right) return;

    const feedCandidates = Array.from(right.querySelectorAll("a,button")).filter((el) => {
      const txt = (el.textContent || "").toLowerCase();
      const title = (el.getAttribute("title") || "").toLowerCase();
      const href = (el.getAttribute("href") || "").toLowerCase();
      const role = (el.getAttribute("data-role") || "").toLowerCase();
      return (
        txt.includes("retour au feed") ||
        title.includes("retour au feed") ||
        href.includes("feed.php") ||
        role === "student-feed-link"
      );
    });
    feedCandidates.forEach((el) => el.remove());

    const feedBtn = document.createElement("a");
    feedBtn.setAttribute("data-role", "student-feed-link");
    feedBtn.href = this.path("View/FrontOffice/feed.php");
    feedBtn.title = "Retour au Feed";
    feedBtn.style.cssText =
      "display:flex;align-items:center;color:#FE5516;background:rgba(254,85,22,0.1);border-radius:20px;padding:6px 16px;font-size:0.95rem;font-weight:600;text-decoration:none;margin-right:8px;transition:all 0.2s;";
    feedBtn.innerHTML = '<i class="fa-solid fa-house" style="margin-right:8px;"></i> Retour au Feed';
    right.prepend(feedBtn);

    let notif = right.querySelector(".header-notification");
    if (!notif) {
      notif = document.createElement("button");
      notif.className = "header-notification";
      notif.type = "button";
      notif.innerHTML = '<i class="fa-solid fa-bell"></i><span class="notif-dot"></span>';
      right.appendChild(notif);
    }

    let avatar = right.querySelector("#header-avatar");
    if (!avatar) {
      avatar = document.createElement("div");
      avatar.className = "avatar avatar-sm";
      avatar.id = "header-avatar";
      avatar.textContent = "AA";
      right.appendChild(avatar);
    }
  },
};

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => StudentLayout.init());
} else {
  StudentLayout.init();
}
