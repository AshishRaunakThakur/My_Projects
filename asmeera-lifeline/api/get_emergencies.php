<?php
// api/get_emergencies.php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $query = "SELECT 
                er.id, 
                er.title, 
                er.request_type, 
                er.priority,
                er.latitude, 
                er.longitude,
                er.status,
                u.full_name as reporter_name,
                er.created_at
              FROM emergency_requests er
              JOIN users u ON er.user_id = u.id
              WHERE er.status != 'completed'
              ORDER BY 
                  CASE er.priority
                      WHEN 'critical' THEN 1
                      WHEN 'high' THEN 2
                      WHEN 'medium' THEN 3
                      WHEN 'low' THEN 4
                  END,
                  er.created_at DESC
              LIMIT 100";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $emergencies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $emergencies,
        'count' => count($emergencies)
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>