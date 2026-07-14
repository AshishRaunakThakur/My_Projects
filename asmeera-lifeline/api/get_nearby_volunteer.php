<?php
// Get nearby volunteers for emergency matching
header('Content-Type: application/json');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $lat = $data['latitude'];
    $lng = $data['longitude'];
    $radius = $data['radius'] ?? 10; // km
    
    $query = "SELECT u.id, u.full_name, u.phone, v.availability, v.skills,
        (6371 * acos(cos(radians(:lat)) * cos(radians(ul.latitude)) * 
        cos(radians(ul.longitude) - radians(:lng)) + 
        sin(radians(:lat)) * sin(radians(ul.latitude)))) as distance
        FROM user_locations ul
        JOIN users u ON ul.user_id = u.id
        JOIN volunteers v ON u.id = v.user_id
        WHERE ul.user_type = 'volunteer'
        AND v.availability = 'available'
        AND v.is_online = 1
        AND ul.last_update > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        HAVING distance < :radius
        ORDER BY distance ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':lat' => $lat, ':lng' => $lng, ':radius' => $radius]);
    $volunteers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'volunteers' => $volunteers]);
}
?>