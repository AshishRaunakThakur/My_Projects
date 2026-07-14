<?php
// includes/helpers.php

function ensureVolunteerProfile($db, $user_id) {
    $stmt = $db->prepare("SELECT id, availability, current_location_lat, current_location_lng FROM volunteers WHERE user_id = :uid");
    $stmt->bindParam(':uid', $user_id);
    $stmt->execute();
    $vol = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$vol) {
        $ins = $db->prepare("INSERT INTO volunteers (user_id, skills, availability) VALUES (:uid, '', 'available')");
        $ins->bindParam(':uid', $user_id);
        $ins->execute();
        return ['id' => $db->lastInsertId(), 'availability' => 'available', 'current_location_lat' => null, 'current_location_lng' => null];
    }
    return $vol;
}

function ensureNgoProfile($db, $user_id, $org_name = 'My Organization') {
    $stmt = $db->prepare("SELECT id, organization_name, verified FROM ngos WHERE user_id = :uid");
    $stmt->bindParam(':uid', $user_id);
    $stmt->execute();
    $ngo = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$ngo) {
        $ins = $db->prepare("INSERT INTO ngos (user_id, organization_name) VALUES (:uid, :name)");
        $ins->bindParam(':uid', $user_id);
        $ins->bindParam(':name', $org_name);
        $ins->execute();
        return ['id' => $db->lastInsertId(), 'organization_name' => $org_name, 'verified' => 0];
    }
    return $ngo;
}

function getDashboardStats($db, $role, $user_id) {
    $stats = [];
    try {
        switch ($role) {
            case 'admin':
                $stats['total_requests'] = (int) $db->query("SELECT COUNT(*) FROM emergency_requests")->fetchColumn();
                $stats['active_requests'] = (int) $db->query("SELECT COUNT(*) FROM emergency_requests WHERE status != 'completed'")->fetchColumn();
                $stats['total_volunteers'] = (int) $db->query("SELECT COUNT(*) FROM users WHERE role_id = 2")->fetchColumn();
                $stats['total_ngos'] = (int) $db->query("SELECT COUNT(*) FROM users WHERE role_id = 3")->fetchColumn();
                $stats['missing_persons'] = (int) $db->query("SELECT COUNT(*) FROM missing_persons WHERE status = 'missing'")->fetchColumn();
                $stats['safe_checkins'] = (int) $db->query("SELECT COUNT(*) FROM safe_checkins WHERE checked_in_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
                break;
            case 'citizen':
                $stmt = $db->prepare("SELECT COUNT(*) FROM emergency_requests WHERE user_id = :uid");
                $stmt->bindParam(':uid', $user_id);
                $stmt->execute();
                $stats['my_requests'] = (int) $stmt->fetchColumn();
                $stmt = $db->prepare("SELECT COUNT(*) FROM emergency_requests WHERE user_id = :uid AND status = 'pending'");
                $stmt->bindParam(':uid', $user_id);
                $stmt->execute();
                $stats['pending'] = (int) $stmt->fetchColumn();
                $stmt = $db->prepare("SELECT COUNT(*) FROM emergency_requests WHERE user_id = :uid AND status = 'completed'");
                $stmt->bindParam(':uid', $user_id);
                $stmt->execute();
                $stats['completed'] = (int) $stmt->fetchColumn();
                $stmt = $db->prepare("SELECT COUNT(*) FROM safe_checkins WHERE user_id = :uid");
                $stmt->bindParam(':uid', $user_id);
                $stmt->execute();
                $stats['checkins'] = (int) $stmt->fetchColumn();
                break;
            case 'volunteer':
                $vol = ensureVolunteerProfile($db, $user_id);
                $vid = $vol['id'];
                $stmt = $db->prepare("SELECT COUNT(*) FROM emergency_requests WHERE assigned_to = :vid AND status IN ('assigned','in_progress')");
                $stmt->bindParam(':vid', $vid);
                $stmt->execute();
                $stats['active_tasks'] = (int) $stmt->fetchColumn();
                $stmt = $db->prepare("SELECT total_tasks_completed FROM volunteers WHERE id = :vid");
                $stmt->bindParam(':vid', $vid);
                $stmt->execute();
                $stats['completed_tasks'] = (int) ($stmt->fetchColumn() ?: 0);
                $stats['availability'] = $vol['availability'];
                $stmt = $db->prepare("SELECT COUNT(*) FROM emergency_requests WHERE status = 'pending' AND priority IN ('critical','high')");
                $stmt->execute();
                $stats['available_nearby'] = (int) $stmt->fetchColumn();
                break;
            case 'ngo':
                $ngo = ensureNgoProfile($db, $user_id);
                $nid = $ngo['id'];
                $stmt = $db->prepare("SELECT COALESCE(SUM(quantity),0) FROM resources WHERE ngo_id = :nid");
                $stmt->bindParam(':nid', $nid);
                $stmt->execute();
                $stats['total_resources'] = (int) $stmt->fetchColumn();
                $stmt = $db->prepare("SELECT COUNT(*) FROM relief_camps WHERE ngo_id = :nid AND is_active = 1");
                $stmt->bindParam(':nid', $nid);
                $stmt->execute();
                $stats['active_camps'] = (int) $stmt->fetchColumn();
                $stmt = $db->prepare("SELECT COUNT(DISTINCT resource_type) FROM resources WHERE ngo_id = :nid");
                $stmt->bindParam(':nid', $nid);
                $stmt->execute();
                $stats['resource_types'] = (int) $stmt->fetchColumn();
                $stats['verified'] = $ngo['verified'] ? 'Yes' : 'Pending';
                break;
        }
    } catch (Exception $e) {
        // Return empty stats on DB error
    }
    return $stats;
}

function mapRequestTypeToResource($request_type) {
    $map = [
        'food' => 'food_packets',
        'water' => 'water_bottles',
        'medical' => 'medicines',
        'shelter' => 'blankets',
        'rescue' => 'emergency_kits',
        'other' => 'other'
    ];
    return $map[$request_type] ?? 'other';
}

function priorityBadgeClass($priority) {
    $classes = ['critical' => 'badge-critical', 'high' => 'badge-high', 'medium' => 'badge-medium', 'low' => 'badge-low'];
    return $classes[$priority] ?? 'badge-low';
}

function statusBadgeClass($status) {
    $classes = [
        'pending' => 'bg-warning text-dark',
        'assigned' => 'bg-info',
        'in_progress' => 'bg-primary',
        'completed' => 'bg-success',
        'cancelled' => 'bg-secondary'
    ];
    return $classes[$status] ?? 'bg-secondary';
}

function ensureUploadDirs() {
    $dirs = [
        UPLOAD_PATH,
        UPLOAD_PATH . 'missing/'
    ];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
