<?php
class Database {
    private $host = "localhost";
    private $db_name = "projet2a22";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $exception) {
            echo json_encode(["success" => false, "message" => "Erreur de connexion à la base de données : " . $exception->getMessage()]);
            exit;
        }
        return $this->conn;
    }
}
?>
