<?php
// config/database.php
class Database {
    private $host = 'zy16r.myd.infomaniak.com';
    private $db_name = 'zy16r_spellbook';
    private $username = 'zy16r_system';
    private $password = '----------';
    private $charset = 'utf8mb4';
    private $conn;

    public function connect() {
        if ($this->conn == null) {
            try {
                $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
                $this->conn = new PDO($dsn, $this->username, $this->password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                error_log("Erreur de connexion : " . $e->getMessage());
                die("Erreur de connexion à la base de données");
            }
        }
        return $this->conn;
    }
}

// Fonction pour obtenir une connexion à la base de données
function getDB() {
    $database = new Database();
    return $database->connect();
}
?>