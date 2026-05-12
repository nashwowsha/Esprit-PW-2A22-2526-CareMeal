-- CareMeal migration: replace legacy `utilisateur` usage with `users` + `profiles`
-- Scope: user schema only. Do not alter business tables except user FK rewiring.
-- Target DB name remains: `caremeal`.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- 1) Create/upgrade user tables (from teammate schema)
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','student','partner') NOT NULL DEFAULT 'student',
  `status` enum('active','inactive','banned') NOT NULL DEFAULT 'active',
  `referral_code` varchar(20) DEFAULT NULL,
  `failed_login_attempts` int(11) DEFAULT 0,
  `lockout_until` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_referral_code` (`referral_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL,
  `github` varchar(255) DEFAULT NULL,
  `ecole` varchar(150) DEFAULT NULL,
  `annee_etude` varchar(50) DEFAULT NULL,
  `quartier` varchar(100) DEFAULT NULL,
  `nom_entreprise` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `site_web` varchar(255) DEFAULT NULL,
  `secteur_activite` varchar(100) DEFAULT NULL,
  `face_descriptor` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `points_accumules` int(11) DEFAULT 0,
  `siret` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_profiles_user_id` (`user_id`),
  CONSTRAINT `fk_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2) Migrate legacy `utilisateur` data while preserving IDs
SET @legacy_exists := (
  SELECT COUNT(*)
  FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'utilisateur'
);

SET @migrate_users_sql := IF(
  @legacy_exists > 0,
  "INSERT INTO users (id, email, password, role, status, created_at, updated_at)
   SELECT
     u.id,
     COALESCE(NULLIF(TRIM(u.email), ''), CONCAT('user_', u.id, '@caremeal.local')),
     '$2y$10$7e3r8vJplw5f4jD3zLyA3eH8kzW3bK3LZLwR7Wv4iOH7dX8Q5QH8C',
     CASE
       WHEN LOWER(TRIM(u.role)) IN ('admin', 'student', 'partner') THEN LOWER(TRIM(u.role))
       WHEN LOWER(TRIM(u.role)) = 'etudiant' THEN 'student'
       WHEN LOWER(TRIM(u.role)) = 'partenaire' THEN 'partner'
       ELSE 'student'
     END,
     'active',
     NOW(),
     NOW()
   FROM utilisateur u
   ON DUPLICATE KEY UPDATE
     email = VALUES(email),
     role = VALUES(role),
     updated_at = CURRENT_TIMESTAMP",
  'SELECT 1'
);
PREPARE stmt_migrate_users FROM @migrate_users_sql;
EXECUTE stmt_migrate_users;
DEALLOCATE PREPARE stmt_migrate_users;

SET @migrate_profiles_sql := IF(
  @legacy_exists > 0,
  "INSERT INTO profiles (user_id, nom, prenom, created_at)
   SELECT
     u.id,
     COALESCE(NULLIF(TRIM(u.nom), ''), COALESCE(NULLIF(TRIM(u.email), ''), CONCAT('User #', u.id))),
     '',
     NOW()
   FROM utilisateur u
   ON DUPLICATE KEY UPDATE
     nom = VALUES(nom)",
  'SELECT 1'
);
PREPARE stmt_migrate_profiles FROM @migrate_profiles_sql;
EXECUTE stmt_migrate_profiles;
DEALLOCATE PREPARE stmt_migrate_profiles;

-- 3) Rewire preference FK from utilisateur(id) to users(id) if needed
SET @pref_fk_exists := (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'preference'
    AND CONSTRAINT_NAME = 'fk_user_preference'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @drop_pref_fk_sql := IF(@pref_fk_exists > 0, 'ALTER TABLE preference DROP FOREIGN KEY fk_user_preference', 'SELECT 1');
PREPARE stmt_drop_pref_fk FROM @drop_pref_fk_sql;
EXECUTE stmt_drop_pref_fk;
DEALLOCATE PREPARE stmt_drop_pref_fk;

SET @pref_orphans := (
  SELECT COUNT(*)
  FROM preference p
  LEFT JOIN users u ON u.id = p.id_user
  WHERE p.id_user IS NOT NULL
    AND u.id IS NULL
);

SET @add_pref_fk_sql := IF(
  @pref_orphans = 0,
  'ALTER TABLE preference ADD CONSTRAINT fk_user_preference FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE stmt_add_pref_fk FROM @add_pref_fk_sql;
EXECUTE stmt_add_pref_fk;
DEALLOCATE PREPARE stmt_add_pref_fk;

-- 4) Rewire planning_collecte FK from utilisateur(id) to users(id) if needed
SET @fk_exists := (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'planning_collecte'
    AND CONSTRAINT_NAME = 'fk_collecte_user'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @drop_fk_sql := IF(@fk_exists > 0, 'ALTER TABLE planning_collecte DROP FOREIGN KEY fk_collecte_user', 'SELECT 1');
PREPARE stmt_drop_fk FROM @drop_fk_sql;
EXECUTE stmt_drop_fk;
DEALLOCATE PREPARE stmt_drop_fk;

SET @orphans := (
  SELECT COUNT(*)
  FROM planning_collecte pc
  LEFT JOIN users u ON u.id = pc.id_user
  WHERE u.id IS NULL
);

SET @add_fk_sql := IF(
  @orphans = 0,
  'ALTER TABLE planning_collecte ADD CONSTRAINT fk_collecte_user FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE stmt_add_fk FROM @add_fk_sql;
EXECUTE stmt_add_fk;
DEALLOCATE PREPARE stmt_add_fk;

-- 5) Drop legacy utilisateur table once no FK references remain
SET @legacy_ref_count := (
  SELECT COUNT(*)
  FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE()
    AND REFERENCED_TABLE_NAME = 'utilisateur'
);

SET @drop_legacy_sql := IF(
  @legacy_ref_count = 0
  AND EXISTS (
    SELECT 1
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'utilisateur'
  ),
  'DROP TABLE utilisateur',
  'SELECT 1'
);
PREPARE stmt_drop_legacy FROM @drop_legacy_sql;
EXECUTE stmt_drop_legacy;
DEALLOCATE PREPARE stmt_drop_legacy;

COMMIT;
