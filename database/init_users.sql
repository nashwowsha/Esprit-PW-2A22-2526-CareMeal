-- Nettoyage de la base de donnees (supprime les anciennes tables si elles existent)
DROP TABLE IF EXISTS `profiles`;
DROP TABLE IF EXISTS `users`;

-- Creation de la base de donnees
CREATE DATABASE IF NOT EXISTS `caremeal` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `caremeal`;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','student','partner') NOT NULL DEFAULT 'student',
  `status` enum('active','inactive','banned') NOT NULL DEFAULT 'active',
  `referral_code` varchar(20) DEFAULT NULL UNIQUE,
  `failed_login_attempts` int(11) DEFAULT 0,
  `lockout_until` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `profiles`
--
CREATE TABLE `profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  
  -- Informations communes
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  
  -- Reseaux Sociaux
  `linkedin` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL,
  `github` varchar(255) DEFAULT NULL,
  
  -- Informations Etudiants
  `ecole` varchar(150) DEFAULT NULL,
  `annee_etude` varchar(50) DEFAULT NULL,
  `quartier` varchar(100) DEFAULT NULL,
  
  -- Informations Partenaires
  `nom_entreprise` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `site_web` varchar(255) DEFAULT NULL,
  `secteur_activite` varchar(100) DEFAULT NULL,
  
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `fk_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

INSERT INTO `users` (`id`, `email`, `password`, `role`, `status`) VALUES
(1, 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

INSERT INTO `profiles` (`user_id`, `nom`, `prenom`) VALUES
(1, 'Super', 'Admin');

-- ==========================================
-- CREATION DES TABLES EVENEMENTS
-- ==========================================

CREATE TABLE EVENEMENT (
    id_evenement INT PRIMARY KEY AUTO_INCREMENT,
    titre VARCHAR(100) NOT NULL,
    description TEXT,
    date_evenement DATE NOT NULL,
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    type_evenement VARCHAR(20) NOT NULL DEFAULT 'Présentiel',
    lieu VARCHAR(150),
    lien_online VARCHAR(255),
    capacite_max INT NOT NULL,
    statut VARCHAR(20) NOT NULL DEFAULT 'Planifié',
    createur_type VARCHAR(20) NOT NULL,
    createur_id INT NOT NULL,
    statut_validation VARCHAR(20) NOT NULL DEFAULT 'En attente'
);

CREATE TABLE PARTICIPATION (
    id_participation INT PRIMARY KEY AUTO_INCREMENT,
    evenement_id INT NOT NULL,
    etudiant_id INT NOT NULL,
    date_inscription DATE NOT NULL,
    statut VARCHAR(20) NOT NULL DEFAULT 'Inscrit',
    FOREIGN KEY (evenement_id) REFERENCES EVENEMENT(id_evenement)
);

-- ==========================================
-- INSERTION DES DONNEES (TEST)
-- ==========================================

INSERT INTO EVENEMENT (titre, date_evenement, heure_debut, heure_fin, type_evenement, lieu, lien_online, capacite_max, statut, createur_type, createur_id, statut_validation) VALUES
('Journée Anti-Gaspi 🌱', '2026-05-10', '09:00:00', '12:00:00', 'Présentiel', 'Campus El Manar', NULL, 100, 'Planifié', 'Admin', 1, 'Validé'),
('Distribution Gratuite 🍱', '2026-05-12', '12:00:00', '14:00:00', 'Présentiel', 'Campus Manouba', NULL, 50, 'Planifié', 'Partenaire', 3, 'Validé');
