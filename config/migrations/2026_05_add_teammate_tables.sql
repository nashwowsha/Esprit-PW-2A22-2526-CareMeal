-- CareMeal migration: add teammate tables into `caremeal` without altering existing business tables
-- Sources: caremeal_db (1).sql, caremeal (2).sql, offre.sql, bd caremeal sql.txt

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- Social/publication module
CREATE TABLE IF NOT EXISTS `publication` (
  `id_publication` int(11) NOT NULL AUTO_INCREMENT,
  `contenu_publication` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `date_publication` datetime DEFAULT current_timestamp(),
  `auteur_type` enum('etudiant','partenaire','admin') NOT NULL,
  `auteur_id` varchar(50) DEFAULT NULL,
  `moderation_status` varchar(20) NOT NULL DEFAULT 'approved',
  `moderation_risk` varchar(20) DEFAULT NULL,
  `moderation_reason` varchar(255) DEFAULT NULL,
  `moderation_provider` varchar(50) NOT NULL DEFAULT 'gemini',
  PRIMARY KEY (`id_publication`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `commentaire` (
  `id_commentaire` int(11) NOT NULL AUTO_INCREMENT,
  `contenu_commentaire` text NOT NULL,
  `date_commentaire` datetime DEFAULT current_timestamp(),
  `id_publication` int(11) NOT NULL,
  `auteur_type` enum('etudiant','partenaire') NOT NULL,
  `auteur_id` varchar(50) DEFAULT NULL,
  `moderation_status` varchar(20) NOT NULL DEFAULT 'approved',
  `moderation_risk` varchar(20) DEFAULT NULL,
  `moderation_reason` varchar(255) DEFAULT NULL,
  `moderation_provider` varchar(50) NOT NULL DEFAULT 'gemini',
  PRIMARY KEY (`id_commentaire`),
  KEY `idx_commentaire_publication` (`id_publication`),
  CONSTRAINT `commentaire_ibfk_1` FOREIGN KEY (`id_publication`) REFERENCES `publication` (`id_publication`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `publication_reaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publication_id` int(11) NOT NULL,
  `auteur_type` varchar(50) NOT NULL,
  `auteur_id` varchar(50) NOT NULL,
  `reaction` varchar(20) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_reaction` (`publication_id`, `auteur_type`, `auteur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `commentaire_reaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `commentaire_id` int(11) NOT NULL,
  `auteur_type` varchar(50) NOT NULL,
  `auteur_id` varchar(50) NOT NULL,
  `reaction` varchar(20) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_comment_reaction` (`commentaire_id`, `auteur_type`, `auteur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Events module
CREATE TABLE IF NOT EXISTS `evenement` (
  `id_evenement` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `date_evenement` date NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `type_evenement` varchar(20) NOT NULL DEFAULT 'Presentiel',
  `lieu` varchar(150) DEFAULT NULL,
  `lien_online` varchar(255) DEFAULT NULL,
  `capacite_max` int(11) NOT NULL,
  `statut` varchar(20) NOT NULL DEFAULT 'Planifie',
  `createur_type` varchar(20) NOT NULL,
  `createur_id` int(11) NOT NULL,
  `statut_validation` varchar(20) NOT NULL DEFAULT 'En attente',
  `motif_refus` text DEFAULT NULL,
  PRIMARY KEY (`id_evenement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `participation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom_evenement` varchar(150) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `universite` varchar(150) DEFAULT NULL,
  `annee_etude` varchar(50) DEFAULT NULL,
  `date_inscription` date NOT NULL,
  `statut` varchar(20) NOT NULL DEFAULT 'Inscrit',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Offers module
CREATE TABLE IF NOT EXISTS `offre` (
  `id_offre` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `prix` decimal(10,2) NOT NULL,
  `prix_original` decimal(10,2) NOT NULL,
  `photo_url` longtext DEFAULT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1,
  `heure_debut` time DEFAULT NULL,
  `heure_fin` time DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_modification` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `statut` enum('publiee','brouillon','archivee','expiree') DEFAULT 'publiee',
  `id_categorie` int(11) DEFAULT NULL,
  `id_partenaire` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_offre`),
  KEY `idx_offre_categorie` (`id_categorie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conditionally add offre -> categorie_offre FK when categorie_offre exists
SET @has_categorie_offre := (
  SELECT COUNT(*)
  FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'categorie_offre'
);
SET @has_offre_fk := (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'offre'
    AND CONSTRAINT_NAME = 'fk_offre_categorie'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);
SET @add_offre_fk_sql := IF(
  @has_categorie_offre > 0 AND @has_offre_fk = 0,
  'ALTER TABLE offre ADD CONSTRAINT fk_offre_categorie FOREIGN KEY (id_categorie) REFERENCES categorie_offre(id_categorie) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE stmt_add_offre_fk FROM @add_offre_fk_sql;
EXECUTE stmt_add_offre_fk;
DEALLOCATE PREPARE stmt_add_offre_fk;

-- Product/order module
CREATE TABLE IF NOT EXISTS `categorie` (
  `id_categorie` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_categorie`),
  UNIQUE KEY `uq_categorie_nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `produit` (
  `id_produit` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_categorie` int(10) unsigned NOT NULL,
  `nom` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `prix_normal` decimal(10,2) NOT NULL,
  `prix_commande` decimal(10,2) NOT NULL,
  `stock` int(10) unsigned NOT NULL DEFAULT 0,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_produit`),
  KEY `idx_produit_categorie` (`id_categorie`),
  KEY `idx_produit_actif` (`actif`),
  CONSTRAINT `fk_produit_categorie` FOREIGN KEY (`id_categorie`) REFERENCES `categorie` (`id_categorie`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `commande` (
  `id_commande` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_produit` int(10) unsigned NOT NULL,
  `id_user` varchar(100) NOT NULL,
  `quantite` int(10) unsigned NOT NULL,
  `prix_unitaire_snapshot` decimal(10,2) NOT NULL,
  `prix_normal_snapshot` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `statut` enum('en_attente','validee','retiree','annulee') NOT NULL DEFAULT 'en_attente',
  `date_commande` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_commande`),
  KEY `idx_commande_user` (`id_user`),
  KEY `idx_commande_statut` (`statut`),
  KEY `idx_commande_date` (`date_commande`),
  CONSTRAINT `fk_commande_produit` FOREIGN KEY (`id_produit`) REFERENCES `produit` (`id_produit`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
