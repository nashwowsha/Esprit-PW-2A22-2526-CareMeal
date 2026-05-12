-- Normalize inconsistent event validation statuses caused by legacy encoding issues.
-- Target canonical labels:
--   Valide, Rejete, En attente

UPDATE EVENEMENT
SET statut_validation = 'Valide'
WHERE LOWER(TRIM(statut_validation)) REGEXP '^valid';

UPDATE EVENEMENT
SET statut_validation = 'Rejete'
WHERE LOWER(TRIM(statut_validation)) REGEXP '^rejet';

UPDATE EVENEMENT
SET statut_validation = 'En attente'
WHERE LOWER(TRIM(statut_validation)) IN ('en attente', 'en_attente', 'attente');
