<?php
require 'config/database.php';
$db = new Database();
$c = $db->getConnection();
echo "DB_OK\n";

try {
  $tables = $c->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM);
  echo "TABLES=" . count($tables) . "\n";
} catch (Throwable $e) {
  echo "ERR_TABLES: " . $e->getMessage() . "\n";
}

try {
  $r = $c->query("SELECT COUNT(*) as total, SUM(statut_validation='En attente') as en_attente, SUM(statut_validation='Validé') as valides, SUM(statut_validation='Rejeté') as rejetes FROM EVENEMENT")->fetch(PDO::FETCH_ASSOC);
  echo "STATS_EVENTS=" . json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
} catch (Throwable $e) {
  echo "ERR_STATS_EVENTS: " . $e->getMessage() . "\n";
}

try {
  $r2 = $c->query("SELECT COUNT(*) as total, SUM(statut='Inscrit') as inscrits, SUM(statut='Présent') as presents, SUM(statut='Annulé') as annules FROM participation")->fetch(PDO::FETCH_ASSOC);
  echo "STATS_PART=" . json_encode($r2, JSON_UNESCAPED_UNICODE) . "\n";
} catch (Throwable $e) {
  echo "ERR_STATS_PART: " . $e->getMessage() . "\n";
}

try {
  $stmt = $c->prepare("SELECT e.titre, e.date_evenement, e.heure_debut, e.heure_fin, e.type_evenement, e.lieu, e.lien_online, e.capacite_max, e.statut_validation, (SELECT COUNT(*) FROM participation p WHERE p.nom_evenement = e.titre AND p.statut != 'Annulé') as inscrits FROM EVENEMENT e ORDER BY e.date_evenement ASC LIMIT 5");
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo "EVENT_ROWS=" . count($rows) . "\n";
  echo "EVENT_SAMPLE=" . json_encode($rows, JSON_UNESCAPED_UNICODE) . "\n";
} catch (Throwable $e) {
  echo "ERR_EVENTS_QUERY: " . $e->getMessage() . "\n";
}
