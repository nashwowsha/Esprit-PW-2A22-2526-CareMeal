-- ==========================================
-- MISE A JOUR TABLE PARTICIPATION
-- Ajouter les colonnes nom, prenom, email, telephone, universite, annee_etude
-- A executer dans phpMyAdmin > caremeal > SQL
-- ==========================================

ALTER TABLE `participation`
  ADD COLUMN `nom` VARCHAR(100) NOT NULL DEFAULT '' AFTER `etudiant_id`,
  ADD COLUMN `prenom` VARCHAR(100) NOT NULL DEFAULT '' AFTER `nom`,
  ADD COLUMN `email` VARCHAR(150) NOT NULL DEFAULT '' AFTER `prenom`,
  ADD COLUMN `telephone` VARCHAR(20) DEFAULT NULL AFTER `email`,
  ADD COLUMN `universite` VARCHAR(150) DEFAULT NULL AFTER `telephone`,
  ADD COLUMN `annee_etude` VARCHAR(50) DEFAULT NULL AFTER `universite`;
