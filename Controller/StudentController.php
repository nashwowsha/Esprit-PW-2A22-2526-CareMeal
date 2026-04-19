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
        // Jointure : offres groupées par catégorie (principe workshop)
        // 1. Toutes les catégories qui ont au moins une offre publiée
        $cats = $this->pdo->query(
            "SELECT DISTINCT c.id_categorie, c.nom_categorie, c.icone
             FROM categorie_offre c
             INNER JOIN offre o ON o.id_categorie = c.id_categorie
             WHERE LOWER(o.statut) IN ('publiée','publiee')
               AND o.id_categorie IS NOT NULL
             ORDER BY c.nom_categorie"
        )->fetchAll();

        // 2. Pour chaque catégorie, ses offres publiées (jointure)
        $stmt = $this->pdo->prepare(
            "SELECT o.*, c.nom_categorie, c.icone
             FROM offre o
             LEFT JOIN categorie_offre c ON o.id_categorie = c.id_categorie
             WHERE o.id_categorie = :id
               AND LOWER(o.statut) IN ('publiée','publiee')
             ORDER BY o.date_creation DESC"
        );
        foreach ($cats as &$cat) {
            $stmt->execute([':id' => $cat['id_categorie']]);
            $cat['offres'] = $stmt->fetchAll();
        }
        unset($cat);
        return $cats;
    }

    public function dashboard()
    {
        $offers             = $this->getPublishedOffers();
        $categoriesWithOffers = $this->getPublishedOffersByCategory();
        require_once __DIR__ . '/../View/FrontOffice/student/dashboard.php';
    }
}