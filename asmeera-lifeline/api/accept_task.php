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
    $request_id = (int) ($data['request_id'] ?? 0);
    $user_id = $_SESSION['user_id'];

    $vol = ensureVolunteerProfile($db, $user_id);

    $stmt = $db->prepare("UPDATE emergency_requests SET assigned_to = :vid, status = 'assigned' WHERE id = :rid AND status = 'pending'");
    $stmt->execute([':vid' => $vol['id'], ':rid' => $request_id]);

    if ($stmt->rowCount() > 0) {
        $req = $db->prepare("SELECT user_id, title FROM emergency_requests WHERE id = :id");
        $req->execute([':id' => $request_id]);
        $reqData = $req->fetch(PDO::FETCH_ASSOC);
        if ($reqData) {
            $msg = 'A volunteer has accepted your request: ' . $reqData['title'];
            $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (:uid, 'Task Assigned', :msg, 'assignment')")
               ->execute([':uid' => $reqData['user_id'], ':msg' => $msg]);
        }
        echo json_encode(['success' => true, 'message' => 'Task accepted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Task no longer available']);
    }
}
