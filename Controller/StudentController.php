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

    // Compatibilite avec les anciens appels
    public function getPublishedOffers(): array
    {
        return $this->getAllOffers();
    }

    public function dashboard()
    {
        $offers = $this->getAllOffers();
        require_once __DIR__ . '/../View/FrontOffice/student/dashboard.php';
    }
}
