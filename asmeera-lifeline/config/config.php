<?php
// config/config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'asmeera_lifeline');

define('SITE_NAME', 'Asmeera Lifeline');
define('SITE_URL', 'http://localhost/asmeera-lifeline/');
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/asmeera-lifeline/assets/uploads/');

error_reporting(E_ALL);
ini_set('display_errors', 1);
