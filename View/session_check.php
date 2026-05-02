<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: /projet2a22/View/FrontOffice/login.php");
    exit;
}

// Empêcher le cache du navigateur pour éviter le problème du bouton "Retour" (Back/Forward Cache)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
