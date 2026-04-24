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
        $sql = "SELECT o.*, c.nom_categorie, c.icone
                FROM offre o
                LEFT JOIN categorie_offre c ON o.id_categorie = c.id_categorie
                ORDER BY o.date_creation DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function getPublishedOffers(): array
    {
        $sql = "SELECT o.*, c.nom_categorie, c.icone
                FROM offre o
                LEFT JOIN categorie_offre c ON o.id_categorie = c.id_categorie
                WHERE LOWER(o.statut) IN ('publiée', 'publiee', 'publiã©e', 'publiãƒâ©e')
                ORDER BY o.date_creation DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function getPublishedOffersByCategory(): array
    {
        $offers = $this->getPublishedOffers();
        $grouped = [];

        foreach ($offers as $offer) {
            $id = (int)($offer['id_categorie'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            if (!isset($grouped[$id])) {
                $grouped[$id] = [
                    'id_categorie' => $id,
                    'nom_categorie' => (string)($offer['nom_categorie'] ?? 'Sans categorie'),
                    'icone' => (string)($offer['icone'] ?? 'fa-tag'),
                    'offres' => [],
                ];
            }

            $grouped[$id]['offres'][] = $offer;
        }

        return array_values($grouped);
    }

    public function dashboard()
    {
        $offers = $this->getPublishedOffers();
        require_once __DIR__ . '/../View/FrontOffice/student/dashboard.php';
    }
}
