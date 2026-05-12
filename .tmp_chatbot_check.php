<?php
require 'config/database.php';
$db = new Database();
$c = $db->getConnection();
try {
  $stmt = $c->prepare("SELECT e.titre, e.date_evenement, (SELECT COUNT(*) FROM participation p WHERE p.evenement_id = e.id_evenement AND p.statut != 'Annulé') as inscrits FROM EVENEMENT e ORDER BY e.date_evenement ASC LIMIT 5");
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode($rows, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
} catch (Throwable $e) {
  echo 'ERR: ' . $e->getMessage();
}
