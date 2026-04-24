<?php
require_once __DIR__ . '/../config/database.php';

class StudentController
{
    private PDO $pdo;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->pdo = Config::getConnexion();
    }

    public function getAllOffers(): array
    {
        $sql = "
            SELECT
                o.*,
                GROUP_CONCAT(DISTINCT c.id_categorie ORDER BY c.nom_categorie SEPARATOR ',') AS cat_ids,
                GROUP_CONCAT(DISTINCT c.nom_categorie ORDER BY c.nom_categorie SEPARATOR ', ') AS cat_noms,
                (
                    SELECT c2.nom_categorie
                    FROM offre_categorie oc2
                    JOIN categorie_offre c2 ON oc2.id_categorie = c2.id_categorie
                    WHERE oc2.id_offre = o.id_offre
                    ORDER BY c2.nom_categorie LIMIT 1
                ) AS nom_categorie,
                (
                    SELECT c2.icone
                    FROM offre_categorie oc2
                    JOIN categorie_offre c2 ON oc2.id_categorie = c2.id_categorie
                    WHERE oc2.id_offre = o.id_offre
                    ORDER BY c2.nom_categorie LIMIT 1
                ) AS icone
            FROM offre o
            LEFT JOIN offre_categorie oc ON o.id_offre = oc.id_offre
            LEFT JOIN categorie_offre  c  ON oc.id_categorie = c.id_categorie
            GROUP BY o.id_offre
            ORDER BY o.date_creation DESC
        ";

        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['categorie_ids'] = $row['cat_ids']
                ? array_map('intval', explode(',', $row['cat_ids']))
                : ($row['id_categorie'] ? [(int)$row['id_categorie']] : []);
        }
        unset($row);
        return $rows;
    }

    public function getPublishedOffers(): array
    {
        $sql = "
            SELECT
                o.*,
                GROUP_CONCAT(DISTINCT c.id_categorie ORDER BY c.nom_categorie SEPARATOR ',') AS cat_ids,
                GROUP_CONCAT(DISTINCT c.nom_categorie ORDER BY c.nom_categorie SEPARATOR ', ') AS cat_noms,
                (
                    SELECT c2.nom_categorie
                    FROM offre_categorie oc2
                    JOIN categorie_offre c2 ON oc2.id_categorie = c2.id_categorie
                    WHERE oc2.id_offre = o.id_offre
                    ORDER BY c2.nom_categorie LIMIT 1
                ) AS nom_categorie,
                (
                    SELECT c2.icone
                    FROM offre_categorie oc2
                    JOIN categorie_offre c2 ON oc2.id_categorie = c2.id_categorie
                    WHERE oc2.id_offre = o.id_offre
                    ORDER BY c2.nom_categorie LIMIT 1
                ) AS icone
            FROM offre o
            LEFT JOIN offre_categorie oc ON o.id_offre = oc.id_offre
            LEFT JOIN categorie_offre  c  ON oc.id_categorie = c.id_categorie
            WHERE LOWER(o.statut) IN ('publiée', 'publiee', 'publiã©e', 'publiãƒâ©e')
            GROUP BY o.id_offre
            ORDER BY o.date_creation DESC
        ";

        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            // categorie_ids array — used for JS filtering on the student side
            $row['categorie_ids'] = $row['cat_ids']
                ? array_map('intval', explode(',', $row['cat_ids']))
                : ($row['id_categorie'] ? [(int)$row['id_categorie']] : []);
        }
        unset($row);
        return $rows;
    }

    /**
     * Group published offers by category.
     * An offer belonging to multiple categories appears in EACH category group.
     */
    public function getPublishedOffersByCategory(): array
    {
        $offers  = $this->getPublishedOffers();
        $grouped = [];

        foreach ($offers as $offer) {
            // Use all category IDs from pivot, not just the primary one
            $catIds   = $offer['categorie_ids'] ?? [];
            $catNoms  = $offer['cat_noms'] ? explode(', ', $offer['cat_noms']) : [];

            if (empty($catIds)) {
                continue;
            }

            foreach ($catIds as $i => $catId) {
                if ($catId <= 0) continue;

                if (!isset($grouped[$catId])) {
                    $grouped[$catId] = [
                        'id_categorie'  => $catId,
                        'nom_categorie' => $catNoms[$i] ?? ($offer['nom_categorie'] ?? 'Sans catégorie'),
                        'icone'         => $offer['icone'] ?? 'fa-tag',
                        'offres'        => [],
                    ];
                }

                $grouped[$catId]['offres'][] = $offer;
            }
        }

        return array_values($grouped);
    }

    public function dashboard()
    {
        $offers = $this->getPublishedOffers();
        require_once __DIR__ . '/../View/FrontOffice/student/dashboard.php';
    }
}