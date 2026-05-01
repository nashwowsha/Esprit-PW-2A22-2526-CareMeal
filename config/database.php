<?php
require_once __DIR__ . '/env.php';

class Config
{
    private static $pdo = null;

    public static function getConnexion()
    {
        Env::load();

        $dbHost = Env::get('DB_HOST', 'localhost');
        $dbName = Env::get('DB_NAME', 'caremeal');
        $dbPort = Env::get('DB_PORT', '3306');
        $dbCharset = Env::get('DB_CHARSET', 'utf8mb4');
        $dbUser = Env::get('DB_USER', 'root');
        $dbPass = Env::get('DB_PASS', '');

        if (!isset(self::$pdo)) {
            try {
                self::$pdo = new PDO(
                    "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset={$dbCharset}",
                    $dbUser,
                    $dbPass,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]
                );
            } catch (Exception $e) {
                die('Erreur de connexion : ' . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}
