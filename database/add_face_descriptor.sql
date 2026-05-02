-- ============================================================
-- Migration : Ajout de la colonne face_descriptor
-- Stocke le descripteur facial (128 floats en JSON)
-- ============================================================

ALTER TABLE `profiles` ADD COLUMN `face_descriptor` TEXT DEFAULT NULL AFTER `secteur_activite`;
