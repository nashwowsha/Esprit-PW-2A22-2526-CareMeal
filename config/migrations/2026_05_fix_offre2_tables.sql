-- CareMeal migration: fix teammate offre dump (offre (2).sql)
-- Goals:
-- 1) Add missing tables: categorie_offre, commander
-- 2) Keep offre FK clean (single FK to categorie_offre)
-- 3) Normalize mojibake/accents in enum values to ASCII-safe values
-- 4) Seed teammate data idempotently

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

CREATE TABLE IF NOT EXISTS `categorie_offre` (
  `id_categorie` int(11) NOT NULL AUTO_INCREMENT,
  `nom_categorie` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `icone` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_categorie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `commander` (
  `id_commande` int(11) NOT NULL AUTO_INCREMENT,
  `id_offre` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1,
  `statut` enum('en_attente','confirmee','annulee','recuperee') DEFAULT 'en_attente',
  `date_commande` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_commande`),
  KEY `idx_commander_id_offre` (`id_offre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure offer category index exists
SET @has_offre_idx := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'offre'
    AND INDEX_NAME = 'idx_offre_categorie'
);
SET @add_offre_idx_sql := IF(
  @has_offre_idx = 0,
  'ALTER TABLE offre ADD KEY idx_offre_categorie (id_categorie)',
  'SELECT 1'
);
PREPARE stmt_add_offre_idx FROM @add_offre_idx_sql;
EXECUTE stmt_add_offre_idx;
DEALLOCATE PREPARE stmt_add_offre_idx;

-- Drop duplicate/legacy FKs on offre.id_categorie if present
SET @drop_fk_offre_categorie_sql := IF(
  EXISTS (
    SELECT 1
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'offre'
      AND CONSTRAINT_NAME = 'fk_offre_categorie'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
  ),
  'ALTER TABLE offre DROP FOREIGN KEY fk_offre_categorie',
  'SELECT 1'
);
PREPARE stmt_drop_fk_offre_categorie FROM @drop_fk_offre_categorie_sql;
EXECUTE stmt_drop_fk_offre_categorie;
DEALLOCATE PREPARE stmt_drop_fk_offre_categorie;

SET @drop_fk_offre_ibfk_1_sql := IF(
  EXISTS (
    SELECT 1
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'offre'
      AND CONSTRAINT_NAME = 'offre_ibfk_1'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
  ),
  'ALTER TABLE offre DROP FOREIGN KEY offre_ibfk_1',
  'SELECT 1'
);
PREPARE stmt_drop_fk_offre_ibfk_1 FROM @drop_fk_offre_ibfk_1_sql;
EXECUTE stmt_drop_fk_offre_ibfk_1;
DEALLOCATE PREPARE stmt_drop_fk_offre_ibfk_1;

-- Re-add single clean FK on offre.id_categorie
SET @add_fk_offre_sql := IF(
  EXISTS (
    SELECT 1
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'offre'
      AND COLUMN_NAME = 'id_categorie'
  ),
  'ALTER TABLE offre ADD CONSTRAINT fk_offre_categorie FOREIGN KEY (id_categorie) REFERENCES categorie_offre(id_categorie) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE stmt_add_fk_offre FROM @add_fk_offre_sql;
EXECUTE stmt_add_fk_offre;
DEALLOCATE PREPARE stmt_add_fk_offre;

-- Ensure commander -> offre FK exists (single)
SET @drop_fk_commander_sql := IF(
  EXISTS (
    SELECT 1
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'commander'
      AND CONSTRAINT_NAME = 'commander_ibfk_1'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
  ),
  'ALTER TABLE commander DROP FOREIGN KEY commander_ibfk_1',
  'SELECT 1'
);
PREPARE stmt_drop_fk_commander FROM @drop_fk_commander_sql;
EXECUTE stmt_drop_fk_commander;
DEALLOCATE PREPARE stmt_drop_fk_commander;

ALTER TABLE `commander`
  ADD CONSTRAINT `commander_ibfk_1` FOREIGN KEY (`id_offre`) REFERENCES `offre` (`id_offre`) ON DELETE CASCADE;

-- Seed categories from teammate dump (idempotent)
INSERT INTO `categorie_offre` (`id_categorie`, `nom_categorie`, `description`, `icone`) VALUES
  (47, 'dessert', 'pizza-pizza', NULL),
  (55, 'Pizza.', NULL, NULL),
  (61, 'Pizza', 'Pizza', NULL),
  (62, 'Fruits', NULL, NULL),
  (64, 'Pizza', NULL, NULL),
  (66, 'Sandwich', NULL, NULL)
ON DUPLICATE KEY UPDATE
  `nom_categorie` = VALUES(`nom_categorie`),
  `description` = VALUES(`description`),
  `icone` = VALUES(`icone`);

-- Seed offers from teammate dump (normalized status)
INSERT INTO `offre` (`id_offre`, `titre`, `description`, `prix`, `prix_original`, `photo_url`, `quantite`, `heure_debut`, `heure_fin`, `date_creation`, `date_modification`, `statut`, `id_categorie`, `id_partenaire`) VALUES
  (120, 'banane', 'une grande banane', 5.00, 6.00, '', 8, NULL, NULL, '2026-05-03 15:49:52', '2026-05-03 15:50:17', 'publiee', 47, NULL),
  (121, 'Titres banane', 'banane chere', 50.00, 60.00, NULL, 11, NULL, NULL, '2026-05-03 15:55:33', '2026-05-03 15:55:33', 'publiee', 47, NULL),
  (122, 'Pizza', 'PizzaShot', 50.00, 60.00, NULL, 8, NULL, NULL, '2026-05-03 16:07:22', '2026-05-03 16:07:22', 'publiee', 47, NULL),
  (126, 'Sandwich', NULL, 40.00, 48.00, NULL, 7, NULL, NULL, '2026-05-05 13:11:17', '2026-05-05 13:11:17', 'publiee', 47, NULL),
  (127, 'Sandwich', 'sandwich delicieux', 50.00, 60.00, NULL, 1, NULL, NULL, '2026-05-05 15:10:53', '2026-05-05 15:10:53', 'publiee', 47, NULL)
ON DUPLICATE KEY UPDATE
  `titre` = VALUES(`titre`),
  `description` = VALUES(`description`),
  `prix` = VALUES(`prix`),
  `prix_original` = VALUES(`prix_original`),
  `photo_url` = VALUES(`photo_url`),
  `quantite` = VALUES(`quantite`),
  `heure_debut` = VALUES(`heure_debut`),
  `heure_fin` = VALUES(`heure_fin`),
  `date_modification` = VALUES(`date_modification`),
  `statut` = VALUES(`statut`),
  `id_categorie` = VALUES(`id_categorie`),
  `id_partenaire` = VALUES(`id_partenaire`);

COMMIT;
