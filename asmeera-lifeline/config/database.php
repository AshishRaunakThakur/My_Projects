<?php
// config/database.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../includes/helpers.php';

class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->dbname,
                $this->user,
                $this->pass
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
        
        return $this->conn;
    }
}

// Create database connection instance
$database = new Database();
$db = $database->getConnection();

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check user role
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == $role;
}

// Function to redirect if not logged in
function requireLogin() {
    if(!isLoggedIn()) {
        header("Location: " . SITE_URL . "login.php");
        exit();
    }
}

// Function to redirect if not proper role
function requireRole($role) {
    requireLogin();
    if(!hasRole($role)) {
        header("Location: " . SITE_URL . "dashboard.php");
        exit();
    }
}
?>