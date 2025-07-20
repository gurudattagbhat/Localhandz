<?php
class Database {
    // Database credentials - update these with your actual database info
    private $host = "localhost";
    private $db_name = "local_handz"; // Updated database name
    private $username = "root";        // Default WAMP username
    private $password = "";            // Default WAMP password is empty
    public $conn;

    // Get the database connection
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            // Instead of echo, throw the exception so it can be caught properly
            throw new Exception("Connection error: " . $exception->getMessage());
        }

        return $this->conn;
    }
}
?> 