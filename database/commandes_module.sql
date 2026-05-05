-- =====================================================
-- CareMeal - Module Commandes
-- Tables required by project brief: categorie, produit, commande
-- =====================================================

CREATE TABLE IF NOT EXISTS categorie (
  id_categorie INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(120) NOT NULL,
  description VARCHAR(255) NULL,
  actif TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_categorie_nom (nom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS produit (
  id_produit INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_categorie INT UNSIGNED NOT NULL,
  nom VARCHAR(150) NOT NULL,
  description TEXT NULL,
  prix_normal DECIMAL(10,2) NOT NULL,
  prix_commande DECIMAL(10,2) NOT NULL,
  stock INT UNSIGNED NOT NULL DEFAULT 0,
  actif TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_produit_categorie
    FOREIGN KEY (id_categorie) REFERENCES categorie(id_categorie)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_produit_categorie (id_categorie),
  INDEX idx_produit_actif (actif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS commande (
  id_commande INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_produit INT UNSIGNED NOT NULL,
  id_user VARCHAR(100) NOT NULL,
  quantite INT UNSIGNED NOT NULL,
  prix_unitaire_snapshot DECIMAL(10,2) NOT NULL,
  prix_normal_snapshot DECIMAL(10,2) NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  statut ENUM('en_attente','validee','retiree','annulee') NOT NULL DEFAULT 'en_attente',
  date_commande TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_commande_produit
    FOREIGN KEY (id_produit) REFERENCES produit(id_produit)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_commande_user (id_user),
  INDEX idx_commande_statut (statut),
  INDEX idx_commande_date (date_commande)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed examples aligned with CareMeal
INSERT INTO categorie (nom, description) VALUES
('Boulangerie', 'Pains, viennoiseries et patisseries anti-gaspi'),
('Repas chauds', 'Plats cuisinés du jour'),
('Salades et bowls', 'Options fraiches et legeres'),
('Desserts', 'Desserts invendus')
ON DUPLICATE KEY UPDATE description = VALUES(description);
