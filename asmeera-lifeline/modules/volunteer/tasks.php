<?php
require_once '../../config/database.php';
require_once '../../includes/helpers.php';
requireRole('volunteer');

$page_title = 'My Tasks';
$current_page = 'tasks';
$user_id = $_SESSION['user_id'];
$volunteer = ensureVolunteerProfile($db, $user_id);
$volunteer_id = $volunteer['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $task_id = (int) $_POST['task_id'];
    $action = $_POST['action'];

    if ($action == 'start') {
        $stmt = $db->prepare("UPDATE emergency_requests SET status = 'in_progress' WHERE id = :tid AND assigned_to = :vid");
        $stmt->execute([':tid' => $task_id, ':vid' => $volunteer_id]);
    } elseif ($action == 'complete') {
        $stmt = $db->prepare("UPDATE emergency_requests SET status = 'completed', completed_at = NOW() WHERE id = :tid AND assigned_to = :vid");
        $stmt->execute([':tid' => $task_id, ':vid' => $volunteer_id]);
        $db->prepare("UPDATE volunteers SET total_tasks_completed = total_tasks_completed + 1 WHERE id = :vid")->execute([':vid' => $volunteer_id]);
    }
    header("Location: tasks.php");
    exit();
}

$tasksStmt = $db->prepare("SELECT er.*, u.full_name as requester_name, u.phone FROM emergency_requests er JOIN users u ON er.user_id = u.id WHERE er.assigned_to = :vid AND er.status IN ('assigned','in_progress') ORDER BY FIELD(er.priority,'critical','high','medium','low'), er.created_at ASC");
$tasksStmt->bindParam(':vid', $volunteer_id);
$tasksStmt->execute();
$assignedTasks = $tasksStmt->fetchAll(PDO::FETCH_ASSOC);

if ($volunteer['current_location_lat'] && $volunteer['current_location_lng']) {
    $availQuery = "SELECT er.*, u.full_name as requester_name,
        (6371 * acos(cos(radians(:lat)) * cos(radians(er.latitude)) * cos(radians(er.longitude) - radians(:lng)) + sin(radians(:lat)) * sin(radians(er.latitude)))) as distance
        FROM emergency_requests er JOIN users u ON er.user_id = u.id
        WHERE er.status = 'pending' AND er.priority IN ('critical','high') AND er.latitude IS NOT NULL
        HAVING distance < 100 ORDER BY distance ASC LIMIT 20";
    $availStmt = $db->prepare($availQuery);
    $availStmt->execute([':lat' => $volunteer['current_location_lat'], ':lng' => $volunteer['current_location_lng']]);
} else {
    $availQuery = "SELECT er.*, u.full_name as requester_name, NULL as distance FROM emergency_requests er JOIN users u ON er.user_id = u.id WHERE er.status = 'pending' AND er.priority IN ('critical','high') ORDER BY er.created_at DESC LIMIT 20";
    $availStmt = $db->prepare($availQuery);
    $availStmt->execute();
}
$availableTasks = $availStmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../../includes/app_header.php';
?>

<div class="page-card mb-4" data-aos="fade-up">
    <div class="card-header bg-info"><i class="fas fa-clipboard-list me-2"></i> My Assigned Tasks (<?php echo count($assignedTasks); ?>)</div>
    <div class="card-body p-0">
        <?php if (empty($assignedTasks)): ?>
            <p class="text-muted text-center py-4 mb-0">No tasks assigned yet. Check available tasks below.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>ID</th><th>Title</th><th>Priority</th><th>Status</th><th>Location</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($assignedTasks as $task): ?>
                <tr>
                    <td>#<?php echo $task['id']; ?></td>
                    <td><?php echo htmlspecialchars($task['title']); ?></td>
                    <td><span class="<?php echo priorityBadgeClass($task['priority']); ?>"><?php echo strtoupper($task['priority']); ?></span></td>
                    <td><span class="badge <?php echo statusBadgeClass($task['status']); ?>"><?php echo ucfirst(str_replace('_',' ',$task['status'])); ?></span></td>
                    <td><?php echo htmlspecialchars(substr($task['location_name'] ?? '—', 0, 30)); ?></td>
                    <td class="admin-table-actions">
                        <a href="<?php echo SITE_URL; ?>view_request.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                        <?php if ($task['status'] == 'assigned'): ?>
                        <form method="POST" class="d-inline"><input type="hidden" name="task_id" value="<?php echo $task['id']; ?>"><input type="hidden" name="action" value="start"><button class="btn btn-sm btn-warning">Start</button></form>
                        <?php elseif ($task['status'] == 'in_progress'): ?>
                        <form method="POST" class="d-inline"><input type="hidden" name="task_id" value="<?php echo $task['id']; ?>"><input type="hidden" name="action" value="complete"><button class="btn btn-sm btn-success">Complete</button></form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="page-card" data-aos="fade-up">
    <div class="card-header"><i class="fas fa-map-marker-alt me-2"></i> Available Urgent Tasks</div>
    <div class="card-body p-0">
        <?php if (empty($availableTasks)): ?>
            <p class="text-muted text-center py-4 mb-0">No urgent tasks available. Enable location in Availability for nearby matches.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>ID</th><th>Title</th><th>Priority</th><th>Distance</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($availableTasks as $task): ?>
                <tr>
                    <td>#<?php echo $task['id']; ?></td>
                    <td><?php echo htmlspecialchars($task['title']); ?></td>
                    <td><span class="<?php echo priorityBadgeClass($task['priority']); ?>"><?php echo strtoupper($task['priority']); ?></span></td>
                    <td><?php echo $task['distance'] ? round($task['distance'], 1) . ' km' : '—'; ?></td>
                    <td><button class="btn btn-sm btn-success" onclick="acceptTask(<?php echo $task['id']; ?>)"><i class="fas fa-hand-paper"></i> Accept</button></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$extra_scripts = '<script>
function acceptTask(id){
    fetch("' . SITE_URL . 'api/accept_task.php",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({request_id:id})})
    .then(r=>r.json()).then(d=>{if(d.success){showToast("Task accepted!","success");setTimeout(()=>location.reload(),1000);}else showToast(d.message||"Failed","error");});
}
</script>';
require_once '../../includes/app_footer.php';
?>
