<?php
session_start();
require_once __DIR__ . '/../../../Controller/EventController.php';
require_once __DIR__ . '/../../../Model/Event.php';

// Si pas connecté, redirigez normalement (ici on simule avec user_id = 3 par défaut si non défini)
$user_id = $_SESSION['user_id'] ?? 3; 
$eventController = new EventController();
$message = '';
$eventToEdit = null;

// Gérer la suppression
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = intval($_GET['id']);
    if ($eventController->deleteEvent($id_to_delete, $user_id)) {
        $message = "<div class='alert alert-success' style='background: #4caf50; color: white; padding: 10px; margin-bottom: 15px; border-radius: 5px;'><i class='fa-solid fa-check'></i> Événement supprimé avec succès.</div>";
    }
}

// Récupérer un événement pour la modification
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $eventToEdit = $eventController->getEventById(intval($_GET['id']), $user_id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sauvegarder_evenement'])) {
    
    // Récupération des données du formulaire
    $titre = $_POST['titre'] ?? '';
    $description = $_POST['description'] ?? '';
    $date_evenement = $_POST['date_evenement'] ?? '';
    $heure_debut = $_POST['heure_debut'] ?? '';
    $heure_fin = $_POST['heure_fin'] ?? '';
    $type_evenement = $_POST['type_evenement'] ?? 'Présentiel';
    $lieu = $_POST['lieu'] ?? '';
    $lien_online = $_POST['lien_online'] ?? '';
    $capacite_max = intval($_POST['capacite_max'] ?? 0);
    
    $eventObj = new Event(
        $titre, $description, $date_evenement, $heure_debut, $heure_fin,
        $type_evenement, $lieu, $lien_online, $capacite_max,
        null, 'Partenaire', $user_id
    );

    if (!empty($_POST['id_evenement'])) {
        // Mode Edition
        $eventId = intval($_POST['id_evenement']);
        if ($eventController->updateEvent($eventId, $user_id, $eventObj)) {
            $message = "<div class='alert alert-success' style='background: #4caf50; color: white; padding: 10px; margin-bottom: 15px; border-radius: 5px;'><i class='fa-solid fa-check'></i> Événement mis à jour avec succès. Il repasse en attente de validation.</div>";
            $eventToEdit = null;
        }
    } else {
        // Mode Création
        $eventId = $eventController->create($eventObj);
        if ($eventId) {
            $message = "<div class='alert alert-success' style='background: #4caf50; color: white; padding: 10px; margin-bottom: 15px; border-radius: 5px;'><i class='fa-solid fa-check'></i> Événement soumis avec succès ! Il est en attente de validation.</div>";
        } else {
            $message = "<div class='alert alert-danger' style='background: #f44336; color: white; padding: 10px; margin-bottom: 15px; border-radius: 5px;'>Erreur lors de la création de l'événement.</div>";
        }
    }

    // Gestion de l'upload de l'image sans changer l'entité
    if (isset($eventId) && $eventId && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../../assets/images/events/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (in_array($ext, $allowed)) {
            // Nettoyer les anciennes extensions
            array_map('unlink', array_filter((array)glob($uploadDir . 'event_' . $eventId . '.*')));
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . 'event_' . $eventId . '.' . $ext);
        }
    }
}

// Récupérer la liste des événements
$events = $eventController->getPartnerEvents($user_id);

$totalEvents = count($events);
$pendingEvents = 0;
$validatedEvents = 0;
foreach($events as $e) {
    if ($e["statut_validation"] === "En attente") $pendingEvents++;
    if ($e["statut_validation"] === "Validé") $validatedEvents++;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Mes Événements — CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- FullCalendar v6 CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">
  <link rel="stylesheet" href="/projet2a22/css/main.css">
  <link rel="stylesheet" href="/projet2a22/css/components.css">
  <link rel="stylesheet" href="/projet2a22/css/dashboard.css">
  <style>
    .form-container {
        background: var(--color-surface, #1e293b);
        padding: 24px;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        margin-bottom: 30px;
        color: var(--color-text, #E8D9BB);
        border: 1px solid var(--color-border, rgba(232, 217, 187, 0.1));
    }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: var(--color-text, #E8D9BB); }
    .form-group input, .form-group textarea, .form-group select {
        width: 100%; padding: 12px; border: 1px solid var(--color-border, rgba(232, 217, 187, 0.2));
        border-radius: 8px; background-color: rgba(0,0,0,0.2); color: #fff; font-family: inherit; box-sizing: border-box; color-scheme: dark;
    }
    .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: var(--color-primary, #FE5516); }
    .btn-submit { background: var(--color-primary, #FE5516); color: #fff; padding: 12px 20px; border: none; border-radius: 50px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block;}
    .btn-submit:hover { opacity: 0.9; }

    /* Cards Stats Grid */
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
    .stat-box { background: linear-gradient(135deg, rgba(30,41,59,1), rgba(15,23,42,1)); padding: 25px 20px; border-radius: 12px; text-align: center; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 4px 6px rgba(0,0,0,0.2); }
    .stat-box h3 { font-size: 2.2rem; margin: 0 0 5px 0; color: #fff; font-weight: 800; }
    .stat-box.green h3 { color: #4caf50; }
    .stat-box.orange h3 { color: #f59e0b; }
    .stat-box p { margin: 0; font-size: 0.9rem; color: #94a3b8; font-weight: 500; }

    /* Events Grid */
    .events-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; }
    .event-card {
        background: #1e293b; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);
        display: flex; flex-direction: column; position: relative; overflow: hidden;
    }
    
    .card-img-wrapper {
        height: 180px; width: 100%; position: relative;
        background-color: #2a3b52; background-size: cover; background-position: center;
    }

    .event-card-header { padding: 12px; display: flex; justify-content: space-between; align-items: flex-start; z-index: 2; position: absolute; top:0; left:0; right:0; }
    .badges { display: flex; gap: 8px; flex-wrap: wrap; }
    .badge { padding: 5px 12px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; color: white; display: inline-block; }
    .badge-presentiel { background: #0284c7; }
    .badge-online { background: #7e22ce; }
    .badge-attente { background: #f59e0b; color: white; }
    .badge-valide { background: #10b981; color: white;}
    .badge-refuse { background: #ef4444; color: white;}

    .actions { display: flex; gap: 8px; }
    .btn-icon { background: rgba(30, 41, 59, 0.8); color: #cbd5e1; border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; padding: 8px 10px; text-decoration: none; display: flex; align-items:center; justify-content:center; }
    .btn-icon:hover { background: rgba(0,0,0,0.8); color: white; }
    
    .card-body { padding: 15px; display: flex; flex-direction: column; gap: 10px; flex: 1; }
    .event-title { font-size: 1.35rem; font-weight: bold; margin: 0; color: #f8fafc; }
    .event-description { color: #cbd5e1; font-size: 0.95em; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 5px; }

    .event-detail { font-size: 0.9rem; color: #94a3b8; display: flex; align-items: flex-start; gap: 8px; margin: 3px 0; }
    .event-detail i { width: 16px; margin-top: 3px; color: #64748b; text-align: center; }
  </style>
</head>
<body>
  <div class="dashboard-layout">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <div class="sidebar-logo"><i class="fa-solid fa-utensils"></i></div>
        <div class="sidebar-brand">Care<span>Meal</span></div>
      </div>
      <nav class="sidebar-nav">
        <div class="sidebar-section">
          <div class="sidebar-section-title">Partenaire</div>
          <a href="dashboard.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-house"></i></span> Mon Établissement</a>
          <a href="offers.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-bag-shopping"></i></span> Mes Offres</a>
          <a href="events.php" class="sidebar-link active"><span class="link-icon"><i class="fa-solid fa-calendar-alt"></i></span> Événements</a>
          <a href="stats.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-chart-simple"></i></span> Statistiques</a>
          <a href="settings.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-gear"></i></span> Paramètres</a>
        </div>
      </nav>
    </aside>

    <main class="main-content">
      <header class="top-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div class="header-left">
          <div class="page-title">
            <h2 style="margin-bottom: 5px;">Mes Événements</h2>
            <p style="margin: 0; color: #aaa;">Gérez vos ateliers et distributions</p>
          </div>
        </div>
        <div class="header-right" style="display:flex; align-items:center; gap:12px;">
            <button class="header-notification"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button>
            <button type="button" class="btn-submit" onclick="document.getElementById('form-section').style.display='block'; window.scrollTo(0,0);">
                <i class="fa-solid fa-plus"></i> Créer un événement
            </button>
        </div>
      </header>

      <div class="page-content">
        <?= $message ?>

        <!-- Formulaire caché sauf en édition -->
        <div class="form-container" id="form-section" style="display: <?= $eventToEdit ? 'block' : 'none' ?>;">
            <h3 style="margin-top: 0;"><i class="fa-solid <?= $eventToEdit ? 'fa-pen' : 'fa-plus' ?>"></i> <?= $eventToEdit ? 'Modifier l\'événement' : 'Nouvel Événement' ?></h3>
            
            <form id="eventForm" novalidate method="POST" action="events.php" enctype="multipart/form-data">
                <input type="hidden" name="id_evenement" value="<?= $eventToEdit ? htmlspecialchars($eventToEdit['id_evenement']) : '' ?>">
                
                <div class="form-group" style="padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); margin-bottom: 20px;">
                    <label><i class="fa-solid fa-image"></i> Image de couverture (optionnel)</label>
                    <input type="file" id="image" name="image" accept="image/*" style="padding: 10px; background: rgba(0,0,0,0.3); border: 1px dashed rgba(255,255,255,0.2); border-radius: 8px;">
                    <small style="color: #aaa; margin-top: 5px; display: block;">Sera utilisé comme image de couverture de votre événement</small>
                </div>

                <div class="form-group">
                    <label>Titre de l'événement *</label>
                    <input type="text" id="titre" name="titre" value="<?= $eventToEdit ? htmlspecialchars($eventToEdit['titre']) : '' ?>">
                    <span class="error-msg" id="err-titre" style="color: #f44336; font-size: 0.85em; display: none;">Ce champ est requis.</span>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3"><?= $eventToEdit ? htmlspecialchars($eventToEdit['description']) : '' ?></textarea>
                </div>
                <div style="display:flex; gap: 15px; flex-wrap: wrap;">
                    <div class="form-group" style="flex: 1; min-width: 150px;">
                        <label>Date *</label>
                        <input type="date" id="date_evenement" name="date_evenement" value="<?= $eventToEdit ? htmlspecialchars($eventToEdit['date_evenement']) : '' ?>">
                        <span class="error-msg" id="err-date" style="color: #f44336; font-size: 0.85em; display: none;">Une date valide est requise.</span>
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 120px;">
                        <label>Heure de début *</label>
                        <input type="time" id="heure_debut" name="heure_debut" value="<?= $eventToEdit ? htmlspecialchars($eventToEdit['heure_debut']) : '' ?>">
                        <span class="error-msg" id="err-heure-debut" style="color: #f44336; font-size: 0.85em; display: none;">Heure de début invalide.</span>
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 120px;">
                        <label>Heure de fin *</label>
                        <input type="time" id="heure_fin" name="heure_fin" value="<?= $eventToEdit ? htmlspecialchars($eventToEdit['heure_fin']) : '' ?>">
                        <span class="error-msg" id="err-heure-fin" style="color: #f44336; font-size: 0.85em; display: none;">Heure de fin invalide.</span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Type d'événement *</label>
                    <select id="type_evenement" name="type_evenement" onchange="toggleLocationFields()">
                        <option value="Présentiel" <?= ($eventToEdit && $eventToEdit['type_evenement'] == 'Présentiel') ? 'selected' : '' ?>>Présentiel</option>
                        <option value="En ligne" <?= ($eventToEdit && $eventToEdit['type_evenement'] == 'En ligne') ? 'selected' : '' ?>>En ligne</option>
                    </select>
                </div>
                <div class="form-group" id="group-lieu">
                    <label>Lieu (si présentiel)</label>
                    <input type="text" id="lieu" name="lieu" placeholder="Ex: Campus El Manar, Tunis" value="<?= $eventToEdit ? htmlspecialchars($eventToEdit['lieu']) : '' ?>">
                    <span class="error-msg" id="err-lieu" style="color: #f44336; font-size: 0.85em; display: none;">Le lieu est requis pour un événement en présentiel.</span>
                </div>
                <div class="form-group" id="group-lien">
                    <label>Lien online (Zoom / Meet)</label>
                    <input type="text" id="lien_online" name="lien_online" placeholder="Ex: https://meet.google.com/xxx" value="<?= $eventToEdit ? htmlspecialchars($eventToEdit['lien_online']) : '' ?>">
                    <span class="error-msg" id="err-lien" style="color: #f44336; font-size: 0.85em; display: none;">Un lien valide est requis.</span>
                </div>
                <div class="form-group">
                    <label>Capacité maximale *</label>
                    <input type="text" id="capacite_max" name="capacite_max" value="<?= $eventToEdit ? htmlspecialchars($eventToEdit['capacite_max']) : '' ?>">
                    <span class="error-msg" id="err-capacite" style="color: #f44336; font-size: 0.85em; display: none;">Doit être un nombre valide supérieur à 0.</span>
                </div>
                <div style="margin-top: 20px;">
                    <button type="submit" name="sauvegarder_evenement" class="btn-submit" style="border-radius: 8px;">
                        <?= $eventToEdit ? 'Mettre à jour' : 'Soumettre l\'événement' ?>
                    </button>
                    <button type="button" class="btn-submit" style="background:#555; border-radius: 8px; margin-left:10px;" onclick="<?= $eventToEdit ? "window.location.href='events.php';" : "document.getElementById('form-section').style.display='none';" ?>">Annuler</button>
                </div>
            </form>
        </div>

        <!-- 4 Stats Boxes -->
        <div class="stats-grid">
            <div class="stat-box">
                <h3><?= $totalEvents ?></h3>
                <p>Total événements créés</p>
            </div>
            <div class="stat-box">
                <h3 style="color: #ff9800;"><?= $pendingEvents ?></h3>
                <p>En attente de validation</p>
            </div>
            <div class="stat-box green">
                <h3><?= $validatedEvents ?></h3>
                <p>Événements validés</p>
            </div>
            <div class="stat-box white">
                <h3><?= array_sum(array_column($events, 'inscrits')) ?></h3>
                <p>Total participants inscrits</p>
            </div>
        </div>

        <!-- Search and Sort -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
            <div style="position:relative; width:300px;">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:var(--color-text-muted);"></i>
                <input type="text" id="partnerSearchInput" placeholder="Rechercher un événement..." style="width:100%; padding:12px 16px 12px 40px; background:var(--color-surface); border:1px solid var(--color-border); border-radius:24px; color:white; font-size:0.95rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);" oninput="PartnerEventsFilter.filterAndSort()">
            </div>
            <select id="partnerSortSelect" class="sort-select" onchange="PartnerEventsFilter.filterAndSort()">
                <option value="date_asc">Trier par : Date (Croissante)</option>
                <option value="date_desc">Trier par : Date (Décroissante)</option>
                <option value="title_asc">Trier par : Titre (A-Z)</option>
            </select>
        </div>

        <!-- Boutons bascule Liste / Calendrier -->
        <div style="display:flex; gap:10px; margin-bottom:20px;">
            <button id="btn-view-list" class="view-toggle-btn active-view">
                <i class="fa-solid fa-list"></i> Vue Liste
            </button>
            <button id="btn-view-calendar" class="view-toggle-btn inactive-view">
                <i class="fa-solid fa-calendar-days"></i> Vue Calendrier
            </button>
        </div>

        <!-- Vue Calendrier -->
        <div id="calendar-view" style="display:none;">
            <div id="calendar-loader" style="display:none; justify-content:center; align-items:center; padding:60px; color:#64748b; gap:12px;">
                <i class="fa-solid fa-circle-notch fa-spin"></i> Chargement du calendrier...
            </div>
            <div id="fullcalendar"></div>
        </div>

        <!-- Vue Liste (existante) -->
        <div id="calendar-list-view">
        <!-- Events Grid -->
        <div class="events-grid" id="partner-events-grid">
            <?php if(empty($events)): ?>
                <p style="grid-column: 1 / -1; color: #aaa;">Vous n'avez créé aucun événement pour le moment.</p>
            <?php else: ?>
                <?php foreach($events as $event): ?>
                    <?php
                        $isEnAttente = $event['statut_validation'] === 'En attente';
                        $isValide = $event['statut_validation'] === 'Validé';
                        $isRefuse = $event['statut_validation'] === 'Rejeté';
                        
                        $badgeTypeClass = $event['type_evenement'] === 'Présentiel' ? 'badge-presentiel' : 'badge-online';
                        
                        // Image de fond (Image uploadée ou Fallback aléatoire propre)
                        $displayImg = "https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&q=80&w=800";
                        $seeds = ["vegetables", "cooking", "gardening", "food", "market", "farm"];
                        $seed = $seeds[$event['id_evenement'] % count($seeds)];
                        if($seed == "gardening") $displayImg = "https://images.unsplash.com/photo-1416879598555-220b8fa017ae?auto=format&fit=crop&q=80&w=800";
                        if($seed == "cooking") $displayImg = "https://images.unsplash.com/photo-1556910103-1c02745a872e?auto=format&fit=crop&q=80&w=800";
                        if($seed == "food") $displayImg = "https://images.unsplash.com/photo-1498837167922-ddd27525d352?auto=format&fit=crop&q=80&w=800";
                        
                        // Verification de l'image uploadée localement
                        $uploadDir = __DIR__ . '/../../../assets/images/events/';
                        $possibleFiles = glob($uploadDir . 'event_' . $event['id_evenement'] . '.*');
                        if (!empty($possibleFiles)) {
                            $displayImg = "../../../assets/images/events/" . basename($possibleFiles[0]) . "?v=" . filemtime($possibleFiles[0]);
                        }
                    ?>
                    <div class="event-card">
                        <div class="card-img-wrapper" style="background-image: url('<?= htmlspecialchars($displayImg) ?>');">
                            <div style="position: absolute; inset: 0; background: linear-gradient(to bottom, rgba(0,0,0,0.4) 0%, transparent 40%, #1e293b 100%);"></div>
                            
                            <div class="event-card-header">
                                <div class="badges">
                                    <span class="badge <?= $badgeTypeClass ?>"><?= htmlspecialchars($event['type_evenement']) ?></span>
                                    <?php if($isEnAttente): ?><span class="badge badge-attente">En attente</span><?php endif; ?>
                                    <?php if($isValide): ?><span class="badge badge-valide">Validé</span><?php endif; ?>
                                    <?php if($isRefuse): ?><span class="badge badge-refuse">Rejeté</span><?php endif; ?>
                                </div>
                                <div class="actions">
                                    <a href="?action=edit&id=<?= $event['id_evenement'] ?>" class="btn-icon" title="Modifier"><i class="fa-solid fa-pen"></i></a>
                                    <a href="?action=delete&id=<?= $event['id_evenement'] ?>" class="btn-icon delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet événement ?');" title="Supprimer"><i class="fa-solid fa-trash"></i></a>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <h4 class="event-title"><?= htmlspecialchars($event['titre']) ?></h4>
                            <?php if(!empty($event['description'])): ?>
                            <div class="event-description">
                                <?= nl2br(htmlspecialchars($event['description'])) ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($isRefuse && !empty($event['motif_refus'])): ?>
                            <div style="background: rgba(239, 68, 68, 0.1); border-left: 3px solid #ef4444; padding: 10px; margin-bottom: 10px; font-size: 0.85rem; color: #fca5a5; border-radius: 4px;">
                                <strong>Motif du refus :</strong> <?= htmlspecialchars($event['motif_refus']) ?>
                            </div>
                            <?php endif; ?>

                            <div style="margin-top: auto;">
                                <div class="event-detail">
                                    <i class="fa-regular fa-calendar"></i>
                                    <span><?= htmlspecialchars($event['date_evenement']) ?> | <?= htmlspecialchars(substr($event['heure_debut'], 0, 5)) ?> → <?= htmlspecialchars(substr($event['heure_fin'], 0, 5)) ?></span>
                                </div>
                                <div class="event-detail">
                                    <i class="fa-solid fa-location-dot"></i>
                                    <span><?= htmlspecialchars($event['lieu'] ?: $event['lien_online'] ?: 'Non renseigné') ?></span>
                                </div>
                                <div class="event-detail" style="margin-top: 8px;">
                                    <i class="fa-solid fa-users" style="color: #64748b;"></i>
                                    <span><?= htmlspecialchars($event['inscrits'] ?? 0) ?> / <?= htmlspecialchars($event['capacite_max']) ?> inscrits</span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        </div><!-- fin #calendar-list-view -->

      </div>
    </main>
  </div>

  <script>
    document.getElementById('eventForm').addEventListener('submit', function(e) {
        let hasErrors = false;
        
        // Hide all general errors
        document.querySelectorAll('.error-msg').forEach(el => el.style.display = 'none');
        
        let titre = document.getElementById('titre').value.trim();
        if(!titre) {
            document.getElementById('err-titre').style.display = 'block';
            hasErrors = true;
        }

        let dateEvt = document.getElementById('date_evenement').value.trim();
        if(!dateEvt) {
            document.getElementById('err-date').style.display = 'block';
            hasErrors = true;
        }

        let heureDebut = document.getElementById('heure_debut').value.trim();
        if(!heureDebut) {
            document.getElementById('err-heure-debut').style.display = 'block';
            hasErrors = true;
        }

        let heureFin = document.getElementById('heure_fin').value.trim();
        if(!heureFin) {
            document.getElementById('err-heure-fin').style.display = 'block';
            hasErrors = true;
        }

        if(heureDebut && heureFin && heureDebut >= heureFin) {
            document.getElementById('err-heure-fin').innerText = "L'heure de fin doit être après le début.";
            document.getElementById('err-heure-fin').style.display = 'block';
            hasErrors = true;
        }

        let typeEvt = document.getElementById('type_evenement').value;
        let lieu = document.getElementById('lieu').value.trim();
        let lien = document.getElementById('lien_online').value.trim();
        
        if(typeEvt === 'Présentiel' && !lieu) {
            document.getElementById('err-lieu').style.display = 'block';
            hasErrors = true;
        }

        if(typeEvt === 'En ligne' && !lien) {
            document.getElementById('err-lien').style.display = 'block';
            hasErrors = true;
        }

                let capacite = document.getElementById('capacite_max').value.trim();
        if(!capacite || isNaN(capacite) || parseInt(capacite) < 1) {
            document.getElementById('err-capacite').style.display = 'block';
            hasErrors = true;
        }

        if(hasErrors) {
            e.preventDefault();
        }
    });

    // �viter la saisie de texte dans 'capacite_max'
    document.getElementById('capacite_max').addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
    // Empêcher la soumission multiple du formulaire si l'utilisateur rafraîchit la page (F5)
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    
    const PartnerEventsFilter = {
        filterAndSort: function() {
            const searchVal = document.getElementById('partnerSearchInput').value.toLowerCase();
            const sortVal = document.getElementById('partnerSortSelect').value;
            const grid = document.getElementById('partner-events-grid');
            let cards = Array.from(grid.querySelectorAll('.event-card'));

            cards.forEach(card => {
                const title = card.querySelector('.event-title').innerText.toLowerCase();
                const locationNodes = card.querySelectorAll('.event-detail');
                const location = locationNodes.length > 1 ? locationNodes[1].innerText.toLowerCase() : '';
                
                if (title.includes(searchVal) || location.includes(searchVal)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });

            cards.sort((a, b) => {
                if (sortVal === 'title_asc') {
                    const titleA = a.querySelector('.event-title').innerText;
                    const titleB = b.querySelector('.event-title').innerText;
                    return titleA.localeCompare(titleB);
                }
                if (sortVal === 'date_asc' || sortVal === 'date_desc') {
                    const dateA = a.querySelectorAll('.event-detail')[0].innerText.split('|')[0].trim();
                    const dateB = b.querySelectorAll('.event-detail')[0].innerText.split('|')[0].trim();
                    const timeA = new Date(dateA).getTime();
                    const timeB = new Date(dateB).getTime();
                    return sortVal === 'date_asc' ? timeA - timeB : timeB - timeA;
                }
                return 0;
            });

            // Re-append in order
            cards.forEach(card => grid.appendChild(card));
        }
    };

    // ── Afficher/cacher lieu ou lien selon le type d'événement ──
    function toggleLocationFields() {
        var type = document.getElementById('type_evenement').value;
        var groupLieu = document.getElementById('group-lieu');
        var groupLien = document.getElementById('group-lien');
        var inputLieu = document.getElementById('lieu');
        var inputLien = document.getElementById('lien_online');

        if (type === 'En ligne') {
            groupLieu.style.display = 'none';
            groupLien.style.display = 'block';
            inputLieu.value = '';
        } else {
            groupLieu.style.display = 'block';
            groupLien.style.display = 'none';
            inputLien.value = '';
        }
    }

    // Appliquer au chargement de la page
    toggleLocationFields();
  </script>
  <script src="/projet2a22/assets/js/chatbot.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
  <script src="/projet2a22/assets/js/calendar.js"></script>
  <script src="/projet2a22/assets/js/notifications.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      CareMealChatbot.init('partner');
      CareMealCalendar.init('partner');
      CareMealNotifications.init('partner');
    });
  </script>
</body>
</html>


