-- ==========================================
-- RESTRUCTURATION TABLE PARTICIPATION
-- Supprimer les colonnes techniques peu lisibles
-- Remplacer par des colonnes claires
-- A executer dans phpMyAdmin > caremeal > SQL
-- ==========================================

-- Etape 1 : Supprimer l'ancienne table
DROP TABLE IF EXISTS `participation`;

-- Etape 2 : Recreer avec une structure claire
CREATE TABLE `participation` (
    `id`              INT(11) NOT NULL AUTO_INCREMENT,
    `nom_evenement`   VARCHAR(150) NOT NULL,
    `nom`             VARCHAR(100) NOT NULL,
    `prenom`          VARCHAR(100) NOT NULL,
    `email`           VARCHAR(150) NOT NULL,
    `telephone`       VARCHAR(20)  DEFAULT NULL,
    `universite`      VARCHAR(150) DEFAULT NULL,
    `annee_etude`     VARCHAR(50)  DEFAULT NULL,
    `date_inscription` DATE        NOT NULL,
    `statut`          VARCHAR(20)  NOT NULL DEFAULT 'Inscrit',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
