<?php
require 'config/database.php';
$db = new Database();
$c = $db->getConnection();
$cols = $c->query("SHOW COLUMNS FROM participation")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($cols, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
