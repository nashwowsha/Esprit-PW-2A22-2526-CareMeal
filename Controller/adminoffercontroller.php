<?php
require_once __DIR__ . '/../Model/Offer.php';

class AdminOfferController
{
    private $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->model = new OfferModel();
    }

    // ── Liste toutes les offres ──────────────────────────────
    public function index()
    {
        $offers     = $this->model->getAll();
        $categories = $this->model->getAllCategories();
        $counts     = $this->model->countByStatut();

        $flash  = $_SESSION['flash']  ?? null;
        $errors = $_SESSION['errors'] ?? [];
        $old    = $_SESSION['old']    ?? [];
        unset($_SESSION['flash'], $_SESSION['errors'], $_SESSION['old']);

        require_once __DIR__ . '/../View/BackOffice/admin/offers.php';
    }

    // ── Modifier une offre (admin peut modifier toutes) ──────
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

    // ── Redirection vers la liste ────────────────────────────
    private function redirect()
    {
        $url = strtok($_SERVER['REQUEST_URI'], '?');
        header('Location: ' . $url);
        exit;
    }

    // ── Validation ───────────────────────────────────────────
    private function validate($data)
    {
        $errors = [];

        if (empty(trim($data['titre'] ?? ''))) {
            $errors[] = 'Le titre est obligatoire.';
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

        if (!isset($data['quantite']) || (int)$data['quantite'] < 1) {
            $errors[] = 'La quantité doit être au moins 1.';
        }

        return $errors;
    }
}