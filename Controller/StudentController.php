<?php
require_once __DIR__ . '/../Model/Offer.php';

class StudentController
{
    private $offerModel;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->offerModel = new OfferModel();
    }

    // ── Dashboard étudiant ────────────────────────────────────
    public function dashboard()
    {
        // Récupérer uniquement les offres publiées
        $allOffers = $this->offerModel->getAll();
        $offers    = array_values(array_filter($allOffers, fn($o) => $o['statut'] === 'publiée'));

        require_once __DIR__ . '/../View/FrontOffice/student/dashboard.php';
    }
}