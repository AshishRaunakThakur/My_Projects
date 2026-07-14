<?php
// api/get_resources.php
header('Content-Type: application/json');
require_once '../config/database.php';

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $query = "SELECT 
                n.organization_name,
                r.resource_type,
                r.quantity,
                r.unit,
                r.last_updated,
                rc.camp_name,
                rc.latitude,
                rc.longitude
              FROM resources r
              JOIN ngos n ON r.ngo_id = n.id
              LEFT JOIN relief_camps rc ON n.id = rc.ngo_id
              WHERE r.quantity > 0
              ORDER BY r.last_updated DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $resources
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>