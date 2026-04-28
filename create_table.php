<?php
require 'c:\xampp\htdocs\webEsprit\config\database.php';
$pdo->query('CREATE TABLE IF NOT EXISTS publication_reaction (
    id INT AUTO_INCREMENT PRIMARY KEY,
    publication_id INT NOT NULL,
    auteur_type VARCHAR(50) NOT NULL,
    auteur_id VARCHAR(50) NOT NULL,
    reaction VARCHAR(20) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reaction (publication_id, auteur_type, auteur_id)
);');
$pdo->query('CREATE TABLE IF NOT EXISTS commentaire_reaction (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commentaire_id INT NOT NULL,
    auteur_type VARCHAR(50) NOT NULL,
    auteur_id VARCHAR(50) NOT NULL,
    reaction VARCHAR(20) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_comment_reaction (commentaire_id, auteur_type, auteur_id)
);');
echo 'Tables created successfully';
?>
