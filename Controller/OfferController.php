<?php
require_once __DIR__ . '/../Model/Offer.php';

class OfferController
{
    private $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->model = new OfferModel();
    }

    // ── Afficher la liste des offres ─────────────────────────
    public function index()
    {
        $partnerId  = !empty($_GET['partner_id']) ? $_GET['partner_id'] : null;
        $offers     = $this->model->getAll($partnerId);
        $categories = $this->model->getAllCategories();

        $active  = array_values(array_filter($offers, fn($o) => $o['statut'] === 'publiée'));
        $expired = array_values(array_filter($offers, fn($o) => in_array($o['statut'], ['expirée', 'archivée', 'brouillon'])));

        // Compter uniquement les offres du partenaire connecté (pas toutes les offres)
        $counts = [
            'publiée'   => count($active),
            'expirée'   => count(array_filter($offers, fn($o) => $o['statut'] === 'expirée')),
            'archivée'  => count(array_filter($offers, fn($o) => $o['statut'] === 'archivée')),
            'brouillon' => count(array_filter($offers, fn($o) => $o['statut'] === 'brouillon')),
        ];

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        $errors = $_SESSION['errors'] ?? [];
        $old    = $_SESSION['old']    ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);

        require_once __DIR__ . '/../View/FrontOffice/partner/offers.php';
    }

    // ── Créer une offre ──────────────────────────────────────
    public function store()
    {
        $errors = $this->validate($_POST);

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old']    = $_POST;
            $this->redirect();
        }

        $this->model->create($_POST);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Offre créée avec succès !'];
        $this->redirect();
    }

    // ── Modifier une offre ───────────────────────────────────
    public function update()
    {
        $id = (int) ($_POST['id_offre'] ?? 0);

        if (!$id) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Offre introuvable.'];
            $this->redirect();
        }

        $errors = $this->validate($_POST);

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old']    = $_POST;
            $this->redirect();
        }

        $this->model->update($id, $_POST);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Offre modifiée avec succès !'];
        $this->redirect();
    }

    // ── Supprimer une offre ──────────────────────────────────
    public function delete()
    {
        $id = (int) ($_POST['id_offre'] ?? 0);

        if ($id) {
            $this->model->delete($id);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Offre supprimée.'];
        }

        $this->redirect();
    }

    // ── Redirection interne ──────────────────────────────────
    private function redirect()
    {
        $url = strtok($_SERVER['REQUEST_URI'], '?') . '?action=index';
        header('Location: ' . $url);
        exit;
    }

    // ── Validation ───────────────────────────────────────────
    private function validate($data)
    {
        $errors = [];

        $titre = trim($data['titre'] ?? '');

        // Titre obligatoire
        if (empty($titre)) {
            $errors[] = 'Le titre est obligatoire.';
        }
        // Titre : lettres uniquement (avec accents, espaces, tirets, apostrophes)
        elseif (!preg_match('/^[A-Za-zÀ-ÖØ-öø-ÿ\s\-\']+$/u', $titre)) {
            $errors[] = 'Le titre doit contenir uniquement des lettres (pas de chiffres ni symboles).';
        }
        // Titre : longueur minimale
        elseif (mb_strlen($titre) < 3) {
            $errors[] = 'Le titre doit contenir au moins 3 caractères.';
        }

        if (!isset($data['prix']) || !is_numeric($data['prix']) || (float)$data['prix'] <= 0) {
            $errors[] = 'Le prix réduit doit être un nombre positif.';
        }

        if (!isset($data['prix_original']) || !is_numeric($data['prix_original']) || (float)$data['prix_original'] <= 0) {
            $errors[] = 'Le prix original doit être un nombre positif.';
        }

        if (
            isset($data['prix'], $data['prix_original']) &&
            (float)$data['prix'] >= (float)$data['prix_original']
        ) {
            $errors[] = 'Le prix réduit doit être inférieur au prix original.';
        }

        if (!isset($data['quantite']) || trim($data['quantite']) === '' || (int)$data['quantite'] < 1) {
            $errors[] = 'La quantité doit être au moins 1.';
        }

        // Validation format heure (HH:MM)
        $heureDebut = trim($data['heure_debut'] ?? '');
        $heureFin   = trim($data['heure_fin']   ?? '');

        if ($heureDebut !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $heureDebut)) {
            $errors[] = 'L\'heure de début est invalide (format attendu : HH:MM).';
        }
        if ($heureFin !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $heureFin)) {
            $errors[] = 'L\'heure de fin est invalide (format attendu : HH:MM).';
        }
        if ($heureDebut !== '' && $heureFin !== '' && $heureFin <= $heureDebut) {
            $errors[] = 'L\'heure de fin doit être postérieure à l\'heure de début.';
        }

        return $errors;
    }
}