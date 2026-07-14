<?php
// api/get_live_locations.php - Get all live users on map
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $type = $_GET['type'] ?? 'all';
    
    try {
        $response = ['success' => true, 'data' => []];
        
        // Get active emergencies
        if ($type == 'all' || $type == 'emergencies') {
            $emergencies = $db->query("SELECT 
                er.id, er.title, er.request_type, er.priority, er.status,
                er.latitude, er.longitude, er.location_name,
                'emergency' as marker_type,
                CASE er.priority
                    WHEN 'critical' THEN 'danger'
                    WHEN 'high' THEN 'warning'
                    WHEN 'medium' THEN 'info'
                    ELSE 'success'
                END as color,
                u.full_name as reporter_name
                FROM emergency_requests er
                JOIN users u ON er.user_id = u.id
                WHERE er.status IN ('pending', 'assigned', 'in_progress')
                AND er.latitude IS NOT NULL
                ORDER BY FIELD(er.priority, 'critical', 'high', 'medium', 'low')")->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($emergencies as $e) {
                $response['data'][] = $e;
            }
        }
        
        // Get active volunteers
        if ($type == 'all' || $type == 'volunteers') {
            $volunteers = $db->query("SELECT 
                u.id, u.full_name, u.phone,
                v.current_location_lat as latitude, 
                v.current_location_lng as longitude,
                'volunteer' as marker_type,
                'success' as color,
                v.availability,
                v.total_tasks_completed,
                v.last_updated as last_update
                FROM users u
                JOIN volunteers v ON u.id = v.user_id
                WHERE u.is_active = 1 
                AND v.availability = 'available'
                AND v.current_location_lat IS NOT NULL
                AND v.current_location_lng IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($volunteers as $v) {
                $v['is_online'] = true;
                $v['location_name'] = '';
                $response['data'][] = $v;
            }
        }
        
        // Get NGOs
        if ($type == 'all' || $type == 'ngos') {
            $ngos = $db->query("SELECT 
                n.id, n.organization_name, n.contact_person,
                n.latitude, n.longitude,
                'ngo' as marker_type,
                'primary' as color,
                n.verified
                FROM ngos n
                WHERE n.latitude IS NOT NULL
                AND n.verified = 1")->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($ngos as $ngo) {
                $response['data'][] = $ngo;
            }
        }
        
        // Get safe check-ins (citizens)
        if ($type == 'all' || $type == 'citizens') {
            $citizens = $db->query("SELECT 
                sc.id, u.full_name,
                sc.latitude, sc.longitude, sc.location_name,
                'citizen' as marker_type,
                'info' as color,
                sc.message, sc.checked_in_at
                FROM safe_checkins sc
                JOIN users u ON sc.user_id = u.id
                WHERE sc.checked_in_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                ORDER BY sc.checked_in_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($citizens as $c) {
                $response['data'][] = $c;
            }
        }
        
        echo json_encode($response);
        
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>