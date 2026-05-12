<?php
require_once __DIR__ . '/env.php';

class Config
{
    private static ?PDO $pdo = null;

    public static function getConnexion(): PDO
    {
        Env::load();

        $dbHost = Env::get('DB_HOST', 'localhost');
        $dbName = Env::get('DB_NAME', 'caremeal');
        $dbPort = Env::get('DB_PORT', '3306');
        $dbCharset = Env::get('DB_CHARSET', 'utf8mb4');
        $dbUser = Env::get('DB_USER', 'root');
        $dbPass = Env::get('DB_PASS', '');

        if (self::$pdo === null) {
            self::$pdo = new PDO(
                "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset={$dbCharset}",
                $dbUser,
                $dbPass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
                ]
            );
        }

        return self::$pdo;
    }
}

class Database
{
    public ?PDO $conn = null;

    public function getConnection(): ?PDO
    {
        try {
            $this->conn = Config::getConnexion();
        } catch (Throwable $exception) {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur de connexion a la base de donnees : ' . $exception->getMessage(),
            ]);
            exit;
        }

        return $this->conn;
    }
}
