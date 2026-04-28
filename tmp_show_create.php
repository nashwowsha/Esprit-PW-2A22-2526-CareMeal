<?php
require __DIR__ . '/config/database.php';
$db = config::getConnexion();
$tables = ['planning_collecte','restaurant','preference','utilisateur'];
foreach ($tables as $t) {
    echo "===== $t =====\n";
    $stmt = $db->query("SHOW CREATE TABLE `$t`");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) { echo "(not found)\n"; continue; }
    echo $row['Create Table'] . "\n\n";
}
?>
