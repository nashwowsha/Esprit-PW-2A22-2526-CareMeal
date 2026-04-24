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

    /**
     * Jointure simple : offre JOIN categorie_offre
     * Comme dans le workshop Album JOIN Genre
     */
    public function getPublishedOffers(): array
    {
        $sql = "
            SELECT
                o.*,
                c.id_categorie,
                c.nom_categorie,
                c.icone
            FROM offre o
            JOIN categorie_offre c ON o.id_categorie = c.id_categorie
            WHERE LOWER(o.statut) IN ('publiée', 'publiee', 'publiée')
            ORDER BY o.date_creation DESC
        ";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Toutes les offres (admin/partenaire)
     */
    public function getAllOffers(): array
    {
        $sql = "
            SELECT
                o.*,
                c.id_categorie,
                c.nom_categorie,
                c.icone
            FROM offre o
            JOIN categorie_offre c ON o.id_categorie = c.id_categorie
            ORDER BY o.date_creation DESC
        ";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Grouper les offres publiées par catégorie
     */
    public function getPublishedOffersByCategory(): array
    {
        $offers  = $this->getPublishedOffers();
        $grouped = [];

        foreach ($offers as $offer) {
            $catId = (int)$offer['id_categorie'];
            if ($catId <= 0) continue;

            if (!isset($grouped[$catId])) {
                $grouped[$catId] = [
                    'id_categorie'  => $catId,
                    'nom_categorie' => $offer['nom_categorie'],
                    'icone'         => $offer['icone'] ?? 'fa-tag',
                    'offres'        => [],
                ];
            }
            $grouped[$catId]['offres'][] = $offer;
        }

        return array_values($grouped);
    }

    public function dashboard()
    {
        $offers = $this->getPublishedOffers();
        require_once __DIR__ . '/../View/FrontOffice/student/dashboard.php';
    }
}