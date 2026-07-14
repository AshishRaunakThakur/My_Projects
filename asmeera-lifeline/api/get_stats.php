<?php
// api/get_stats.php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    // Categories statistics
    $catQuery = "SELECT request_type, COUNT(*) as count 
                 FROM emergency_requests 
                 GROUP BY request_type";
    $catStmt = $db->prepare($catQuery);
    $catStmt->execute();
    $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Priority statistics
    $priQuery = "SELECT priority, COUNT(*) as count 
                 FROM emergency_requests 
                 GROUP BY priority";
    $priStmt = $db->prepare($priQuery);
    $priStmt->execute();
    $priorities = $priStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Resource statistics
    $resQuery = "SELECT resource_type, SUM(quantity) as total 
                 FROM resources 
                 GROUP BY resource_type";
    $resStmt = $db->prepare($resQuery);
    $resStmt->execute();
    $resources = $resStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'categories' => $categories,
        'priorities' => $priorities,
        'resources' => $resources
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>