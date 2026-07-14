<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/helpers.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'volunteer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = $_SESSION['user_id'];
    ensureVolunteerProfile($db, $user_id);

    $stmt = $db->prepare("UPDATE volunteers SET current_location_lat = :lat, current_location_lng = :lng, last_updated = NOW() WHERE user_id = :uid");
    if ($stmt->execute([':lat' => $data['latitude'], ':lng' => $data['longitude'], ':uid' => $user_id])) {
        echo json_encode(['success' => true, 'message' => 'Location updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }
}
