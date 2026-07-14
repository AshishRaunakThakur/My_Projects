<?php
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'unread' => 0, 'notifications' => []]);
    exit();
}

try {
    $user_id = $_SESSION['user_id'];

    $countQuery = "SELECT COUNT(*) as unread FROM notifications WHERE user_id = :user_id AND is_read = 0";
    $countStmt = $db->prepare($countQuery);
    $countStmt->bindParam(':user_id', $user_id);
    $countStmt->execute();
    $unread = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['unread'];

    $query = "SELECT id, title, message, type, is_read, created_at 
              FROM notifications 
              WHERE user_id = :user_id 
              ORDER BY created_at DESC 
              LIMIT 20";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'unread' => $unread,
        'notifications' => $notifications
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'unread' => 0,
        'notifications' => [],
        'message' => $e->getMessage()
    ]);
}
