<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['user_role'];
    $latitude = $data['latitude'] ?? null;
    $longitude = $data['longitude'] ?? null;
    $location_name = $data['location_name'] ?? '';
    
    if (!$latitude || !$longitude) {
        echo json_encode(['success' => false, 'message' => 'Location required']);
        exit();
    }
    
    try {
        // Insert/Update in user_locations table
        $check = $db->prepare("SELECT id FROM user_locations WHERE user_id = :uid");
        $check->execute([':uid' => $user_id]);
        
        if ($check->rowCount() > 0) {
            $stmt = $db->prepare("UPDATE user_locations SET latitude = :lat, longitude = :lng, location_name = :name, last_update = NOW() WHERE user_id = :uid");
        } else {
            $stmt = $db->prepare("INSERT INTO user_locations (user_id, user_type, latitude, longitude, location_name) VALUES (:uid, :type, :lat, :lng, :name)");
            $stmt->bindParam(':type', $role);
        }
        
        $stmt->bindParam(':uid', $user_id);
        $stmt->bindParam(':lat', $latitude);
        $stmt->bindParam(':lng', $longitude);
        $stmt->bindParam(':name', $location_name);
        $stmt->execute();
        
        // Update volunteer online status
        if ($role == 'volunteer') {
            $db->prepare("UPDATE volunteers SET is_online = 1, last_heartbeat = NOW() WHERE user_id = :uid")
               ->execute([':uid' => $user_id]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Location updated']);
        
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>