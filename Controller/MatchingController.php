<?php
require_once __DIR__ . '/../Model/Preference.php';
require_once __DIR__ . '/../Model/Matching.php';

class MatchingController {
    public function getRankedOffersForUser($idUser) {
        $idUser = (int)$idUser;
        if ($idUser <= 0) {
            return [
                'ok' => false,
                'status' => 'error_invalid_user',
                'matches' => [],
            ];
        }

        $preference = Preference::getByUserId($idUser);
        if (!$preference) {
            return [
                'ok' => true,
                'status' => 'no_preference',
                'preference' => null,
                'matches' => [],
            ];
        }

        $offers = Matching::getMockOffers();
        $matches = Matching::rankOffers($preference, $offers);

        return [
            'ok' => true,
            'status' => empty($matches) ? 'no_match' : 'success',
            'preference' => $preference,
            'matches' => $matches,
        ];
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $action = $_GET['action'] ?? '';

    if ($action === 'student_matches') {
        $idUser = isset($_GET['id_user']) ? (int)$_GET['id_user'] : 0;
        $controller = new MatchingController();
        $result = $controller->getRankedOffersForUser($idUser);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => false,
        'status' => 'error_unknown_action',
        'matches' => [],
    ]);
    exit;
}

