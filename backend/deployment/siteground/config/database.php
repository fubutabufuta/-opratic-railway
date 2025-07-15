<?php
class Database
{
    private $host = 'localhost';
    private $dbname = 'otoasist';
    private $username = 'root';
    private $password = '';
    private $conn = null;

    public function getConnection()
    {
        if ($this->conn === null) {
            try {
                $this->conn = new PDO(
                    "mysql:host=" . $this->host . ";dbname=" . $this->dbname . ";charset=utf8",
                    $this->username,
                    $this->password
                );
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                return null;
            }
        }
        return $this->conn;
    }
}
