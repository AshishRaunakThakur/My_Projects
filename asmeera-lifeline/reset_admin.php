<?php
// reset_admin.php - Run once then DELETE this file
require_once 'config/database.php';

$email = 'ashishraunak5@gmail.com';
$password = '386000';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Generated Hash: " . $hash . "<br><br>";

// Check/Insert role
$stmt = $db->prepare("SELECT id FROM roles WHERE role_name = 'admin'");
$stmt->execute();
$role_id = $stmt->fetchColumn();

if (!$role_id) {
    $db->exec("INSERT INTO roles (role_name) VALUES ('admin')");
    $role_id = $db->lastInsertId();
    echo "Admin role created with ID: " . $role_id . "<br>";
}

// Check if user exists
$stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
$stmt->execute([':email' => $email]);
$exists = $stmt->fetchColumn();

if ($exists) {
    // Update existing user
    $stmt = $db->prepare("UPDATE users SET password = :pass, role_id = :role, is_active = 1 WHERE email = :email");
    $stmt->execute([':pass' => $hash, ':role' => $role_id, ':email' => $email]);
    echo "Admin user UPDATED!<br>";
} else {
    // Insert new user
    $stmt = $db->prepare("INSERT INTO users (full_name, email, password, phone, role_id, is_active) 
        VALUES ('Admin User', :email, :pass, '1234567890', :role, 1)");
    $stmt->execute([':email' => $email, ':pass' => $hash, ':role' => $role_id]);
    echo "Admin user CREATED!<br>";
}

echo "<hr>";
echo "<strong>Login Credentials:</strong><br>";
echo "Email: " . $email . "<br>";
echo "Password: " . $password . "<br>";
echo "<br><a href='login.php' class='btn btn-danger'>Go to Login →</a>";
?>