<?php require_once dirname(__DIR__, 2) . '/session_check.php'; ?>
<?php
session_start();
require_once __DIR__ . '/../../../Model/Event.php';

// Si pas connect?, redirigez normalement (ici on simule avec user_id = 3 par d?faut si non d?fini)
$user_id = $_SESSION['user_id'] ?? 3; 
$eventModel = new Event();
$message = '';
$eventToEdit = null;

// G?rer la suppression
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = intval($_GET['id']);
    if ($eventModel->deleteEvent($id_to_delete, $user_id)) {
        $message = "<div class='alert alert-success' style='background: #4caf50; color: white; padding: 10px; margin-bottom: 15px; border-radius: 5px;'><i class='fa-solid fa-check'></i> ?v?nement supprim? avec succ?s.</div>";
    }
}

// R?cup?rer un ?v?nement pour la modification
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $eventToEdit = $eventModel->getEventById(intval($_GET['id']), $user_id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sauvegarder_evenement'])) {
    
    // R?cup?ration des donn?es du formulaire
    $data = [
        'titre' => $_POST['titre'] ?? '',
        'description' => $_POST['description'] ?? '',
        'date_evenement' => $_POST['date_evenement'] ?? '',
        'heure_debut' => $_POST['heure_debut'] ?? '',
        'heure_fin' => $_POST['heure_fin'] ?? '',
        'type_evenement' => $_POST['type_evenement'] ?? 'Pr?sentiel',
        'lieu' => $_POST['lieu'] ?? '',
        'lien_online' => $_POST['lien_online'] ?? '',
        'capacite_max' => intval($_POST['capacite_max'] ?? 0),
        'createur_type' => 'Partenaire',
        'createur_id' => $user_id
    ];

    // Validation PHP backend (remplace required HTML5)
    if (empty(trim($data['titre'])) || empty($data['date_evenement']) || empty($data['heure_debut']) || empty($data['heure_fin']) || empty($data['capacite_max'])) {
        $message = "<div class='alert alert-danger' style='background: #f44336; color: white; padding: 10px; margin-bottom: 15px; border-radius: 5px;'><i class='fa-solid fa-triangle-exclamation'></i> Erreur : Veuillez remplir tous les champs obligatoires (Titre, Date, Horaires, Capacit?).</div>";
    } elseif (!empty($_POST['id_evenement'])) {
        // Mode Edition
        if ($eventModel->updateEvent(intval($_POST['id_evenement']), $user_id, $data)) {
            $message = "<div class='alert alert-success' style='background: #4caf50; color: white; padding: 10px; margin-bottom: 15px; border-radius: 5px;'><i class='fa-solid fa-check'></i> ?v?nement mis ? jour avec succ?s. Il repasse en attente de validation.</div>";
            $eventToEdit = null;
        }
    } else {
        // Mode Cr?ation
        if ($eventModel->create($data)) {
            $message = "<div class='alert alert-success' style='background: #4caf50; color: white; padding: 10px; margin-bottom: 15px; border-radius: 5px;'><i class='fa-solid fa-check'></i> ?v?nement soumis avec succ?s ! Il est en attente de validation.</div>";
        } else {
            $message = "<div class='alert alert-danger' style='background: #f44336; color: white; padding: 10px; margin-bottom: 15px; border-radius: 5px;'>Erreur lors de la cr?ation de l'?v?nement.</div>";
        }
    }
}

// R?cup?rer la liste des ?v?nements
$events = $eventModel->getPartnerEvents($user_id);

$totalEvents = count($events);
$pendingEvents = 0;
$validatedEvents = 0;
foreach($events as $e) {
    if ($e["statut_validation"] === "En attente") $pendingEvents++;
    if ($e["statut_validation"] === "Valid?") $validatedEvents++;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Mes ?v?nements ? CareMeal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/projet2a22/css/main.css">
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
    .stat-box { background: #1e293b; padding: 20px; border-radius: 12px; text-align: center; border: 1px solid rgba(255,255,255,0.05); }
    .stat-box h3 { font-size: 2rem; margin: 0; color: var(--color-primary, #FE5516); }
    .stat-box.green h3 { color: #4caf50; }
    .stat-box.white h3 { color: #fff; }
    .stat-box p { margin: 5px 0 0 0; font-size: 0.9rem; color: #aaa; }

    /* Events Grid */
    .events-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
    .event-card {
        background: #1e293b; border-radius: 12px; padding: 20px; border: 1px solid rgba(255,255,255,0.05);
        display: flex; flex-direction: column; gap: 10px; position: relative;
    }
    .event-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .badges { display: flex; gap: 8px; }
    .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; background: rgba(255,255,255,0.1); color: #ccc; }
    .badge-presentiel { background: rgba(33, 150, 243, 0.2); color: #4fc3f7; }
    .badge-online { background: rgba(156, 39, 176, 0.2); color: #e040fb; }
    .badge-attente { background: rgba(255, 152, 0, 0.2); color: #ffb74d; }
    .badge-valide { background: rgba(76, 175, 80, 0.2); color: #81c784; }
    .badge-refuse { background: rgba(244, 67, 54, 0.2); color: #e57373; }

    .actions { display: flex; gap: 8px; }
    .btn-icon { background: rgba(255,255,255,0.1); color: white; border: none; border-radius: 6px; padding: 6px 10px; cursor: pointer; text-decoration: none; }
    .btn-icon:hover { background: rgba(255,255,255,0.2); }
    .btn-icon.delete { background: rgba(244, 67, 54, 0.2); color: #e57373; }
    .btn-icon.delete:hover { background: rgba(244, 67, 54, 0.4); }

    .event-title { font-size: 1.4rem; font-weight: bold; margin: 0; color: #fff; }
    .event-detail { font-size: 0.9rem; color: #bbb; display: flex; align-items: center; gap: 8px; margin: 2px 0; }
    .event-detail i { width: 16px; color: #888; text-align: center; }
    
    .event-footer { margin-top: auto; padding-top: 15px; }
    .msg-attente { background: rgba(255, 152, 0, 0.1); border-left: 3px solid #ff9800; padding: 8px 12px; font-size: 0.85rem; color: #ffb74d; border-radius: 4px; }
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
                    <a href="dashboard.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-store"></i></span> Mon ?tablissement</a>
          <a href="offers.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-bag-shopping"></i></span> Mes Offres</a>
          <a href="events.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-calendar-alt"></i></span> Mes ?v?nements</a>
          <a href="stats.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-chart-simple"></i></span> Statistiques</a>
          <a href="settings.php" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-gear"></i></span> Param?tres</a>
        </div>
      </nav>
    </aside>

    <main class="main-content">
      <header class="top-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div class="header-left">
          <div class="page-title">
            <h2 style="margin-bottom: 5px;">Mes ?v?nements</h2>
            <p style="margin: 0; color: #aaa;">G?rez vos ateliers et distributions</p>
          </div>
        </div>
        <div class="header-right">
            <button type="button" class="btn-submit" onclick="document.getElementById('form-section').style.display='block'; window.scrollTo(0,0);">
                <i class="fa-solid fa-plus"></i> Cr?er un ?v?nement
            </button>
        </div>
      </header>

      <div class="page-content">
        <?= $message ?>

        <!-- Formulaire cach? sauf en ?dition -->
        <div class="form-container" id="form-section" style="display: <?= $eventToEdit ? 'block' : 'none' ?>;">
            <h3 style="margin-top: 0;"><i class="fa-solid <?= $eventToEdit ? 'fa-pen' : 'fa-plus' ?>"></i> <?= $eventToEdit ? 'Modifier l\'?v?nement' : 'Nouvel ?v?nement' ?></h3>
            
            <form method="POST" action="events.php" novalidate>
                <input type="hidden" name="id_evenement" value="<?= $eventToEdit ? htmlspecialchars($eventToEdit['id_evenement']) : '' ?>">
                
                <div class="form-group">
                    <label>Titre de l'?v?nement *</label>
                    <input type="text" name="titre" value="<?= $eventToEdit ? htmlspecialchars($eventToEdit['titre']) : '' ?>">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3"><?= $eventToEdit ? htmlspecialchars($eventToEdit['description']) : '' ?></textarea>
                </div>
                <div style="display:flex; gap: 15px; flex-wrap: wrap;">
                    <div class="form-group" style="flex: 1; min-width: 150px;">
                        <label>Date *</label>
                        <input type="date" name="date_evenement" value="<?= $eventToEdit ? htmlspecialchars($eventToEdit['date_evenement']) : '' ?>">
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 120px;">
                        <label>Heure de d?but *</label>
                        <input type="time" name="heure_debut" value="<?= $eventToEdit ? htmlspecialchars($eventToEdit['heure_debut']) : '' ?>">
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 120px;">
                        <label>Heure de fin *</label>
                        <input type="time" name="heure_fin" value="<?= $eventToEdit ? htmlspecialchars($eventToEdit['heure_fin']) : '' ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Type d'?v?nement *</label>
                    <select name="type_evenement">
                        <option value="Pr?sentiel" <?= ($eventToEdit && $eventToEdit['type_evenement'] == 'Pr?sentiel') ? 'selected' : '' ?>>Pr?sentiel</option>
                        <option value="En ligne" <?= ($eventToEdit && $eventToEdit['type_evenement'] == 'En ligne') ? 'selected' : '' ?>>En ligne</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Lieu (si pr?sentiel)</label>
                    <input type="text" name="lieu" value="<?= $eventToEdit ? htmlspecialchars($eventToEdit['lieu']) : '' ?>">
                </div>
                <div class="form-group">
                    <label>Lien online (si en ligne)</label>
                    <input type="text" name="lien_online" value="<?= $eventToEdit ? htmlspecialchars($eventToEdit['lien_online']) : '' ?>">
                </div>
                <div class="form-group">
                    <label>Capacit? maximale *</label>
                    <input type="text" name="capacite_max" value="<?= $eventToEdit ? htmlspecialchars($eventToEdit['capacite_max']) : '' ?>">
                </div>
                <div style="margin-top: 20px;">
                    <button type="submit" name="sauvegarder_evenement" class="btn-submit" style="border-radius: 8px;">
                        <?= $eventToEdit ? 'Mettre ? jour' : 'Soumettre l\'?v?nement' ?>
                    </button>
                    <button type="button" class="btn-submit" style="background:#555; border-radius: 8px; margin-left:10px;" onclick="<?= $eventToEdit ? "window.location.href='events.php';" : "document.getElementById('form-section').style.display='none';" ?>">Annuler</button>
                </div>
            </form>
        </div>

        <!-- 4 Stats Boxes -->
        <div class="stats-grid">
            <div class="stat-box">
                <h3><?= $totalEvents ?></h3>
                <p>Total ?v?nements cr??s</p>
            </div>
            <div class="stat-box">
                <h3 style="color: #ff9800;"><?= $pendingEvents ?></h3>
                <p>En attente de validation</p>
            </div>
            <div class="stat-box green">
                <h3><?= $validatedEvents ?></h3>
                <p>?v?nements valid?s</p>
            </div>
            <div class="stat-box white">
                <h3>0</h3> <!-- Mettre ? jour avec une requ?te plus tard -->
                <p>Total participants inscrits</p>
            </div>
        </div>

        <!-- Events Grid -->
        <div class="events-grid">
            <?php if(empty($events)): ?>
                <p style="grid-column: 1 / -1; color: #aaa;">Vous n'avez cr?? aucun ?v?nement pour le moment.</p>
            <?php else: ?>
                <?php foreach($events as $event): ?>
                    <?php
                        $isEnAttente = $event['statut_validation'] === 'En attente';
                        $isValide = $event['statut_validation'] === 'Valid?';
                        $isRefuse = $event['statut_validation'] === 'Rejet?';
                        
                        $badgeTypeClass = $event['type_evenement'] === 'Pr?sentiel' ? 'badge-presentiel' : 'badge-online';
                        
                        $badgeStatusClass = 'badge-attente';
                        if ($isValide) $badgeStatusClass = 'badge-valide';
                        if ($isRefuse) $badgeStatusClass = 'badge-refuse';
                    ?>
                    <div class="event-card">
                        <div class="event-card-header">
                            <div class="badges">
                                <span class="badge <?= $badgeTypeClass ?>"><?= htmlspecialchars($event['type_evenement']) ?></span>
                                <span class="badge <?= $badgeStatusClass ?>"><?= htmlspecialchars($event['statut_validation']) ?></span>
                            </div>
                            <div class="actions">
                                <a href="?action=edit&id=<?= $event['id_evenement'] ?>" class="btn-icon" title="Modifier"><i class="fa-solid fa-pen"></i></a>
                                <a href="?action=delete&id=<?= $event['id_evenement'] ?>" class="btn-icon delete" onclick="return confirm('?tes-vous s?r de vouloir supprimer cet ?v?nement -');" title="Supprimer"><i class="fa-solid fa-trash"></i></a>
                            </div>
                        </div>
                        
                        <h4 class="event-title" style="margin-bottom: 4px;"><?= htmlspecialchars($event['titre']) ?></h4>
                        <?php if(!empty($event['description'])): ?>
                        <div style="color:#bbb; font-size:0.97em; margin-bottom: 6px; white-space: pre-line;">
                            <?= nl2br(htmlspecialchars($event['description'])) ?>
                        </div>
                        <?php endif; ?>
                        <div class="event-detail">
                            <i class="fa-regular fa-calendar"></i>
                            <?= htmlspecialchars($event['date_evenement']) ?> ? <?= htmlspecialchars(substr($event['heure_debut'], 0, 5)) ?> ? <?= htmlspecialchars(substr($event['heure_fin'], 0, 5)) ?>
                        </div>
                        <div class="event-detail">
                            <i class="fa-solid fa-location-dot"></i>
                            <?= htmlspecialchars($event['lieu'] ?: $event['lien_online'] ?: 'Non renseign?') ?>
                        </div>
                        <div class="event-detail" style="margin-top: 8px;">
                            <i class="fa-solid fa-users"></i>
                            0 / <?= htmlspecialchars($event['capacite_max']) ?> inscrits
                        </div>

                        <?php if($isEnAttente): ?>
                            <div class="event-footer">
                                <div class="msg-attente">En attente de validation</div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

      </div>
    </main>
  </div>
</body>
</html>











