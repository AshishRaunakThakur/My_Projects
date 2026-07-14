<?php
require_once '../../config/database.php';
require_once '../../includes/helpers.php';
requireRole('admin');

$page_title = 'Emergency Requests';
$current_page = 'requests';
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $id = (int) $_POST['request_id'];
    $status = $_POST['status'];
    $allowed = ['pending','assigned','in_progress','completed','cancelled'];
    if (in_array($status, $allowed)) {
        $sql = "UPDATE emergency_requests SET status = :st" . ($status === 'completed' ? ", completed_at = NOW()" : "") . " WHERE id = :id";
        $db->prepare($sql)->execute([':st' => $status, ':id' => $id]);
        $message = 'Request status updated.';
    }
}

$requests = $db->query("SELECT er.*, u.full_name as reporter_name FROM emergency_requests er JOIN users u ON er.user_id = u.id ORDER BY er.created_at DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);

require_once '../../includes/app_header.php';
?>

<?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

<div class="page-card" data-aos="fade-up">
    <div class="card-header"><i class="fas fa-list-alt me-2"></i> All Emergency Requests</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>ID</th><th>Title</th><th>Type</th><th>Priority</th><th>Status</th><th>Reporter</th><th>Date</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($requests as $r): ?>
                <tr>
                    <td>#<?php echo $r['id']; ?></td>
                    <td><?php echo htmlspecialchars(substr($r['title'], 0, 35)); ?></td>
                    <td><?php echo ucfirst($r['request_type']); ?></td>
                    <td><span class="<?php echo priorityBadgeClass($r['priority']); ?>"><?php echo $r['priority']; ?></span></td>
                    <td><span class="badge <?php echo statusBadgeClass($r['status']); ?>"><?php echo $r['status']; ?></span></td>
                    <td><?php echo htmlspecialchars($r['reporter_name']); ?></td>
                    <td><?php echo date('M d, H:i', strtotime($r['created_at'])); ?></td>
                    <td class="admin-table-actions">
                        <a href="<?php echo SITE_URL; ?>view_request.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="request_id" value="<?php echo $r['id']; ?>">
                            <select name="status" class="form-select form-select-sm d-inline-block" style="width:auto;" onchange="this.form.submit()">
                                <?php foreach (['pending','assigned','in_progress','completed','cancelled'] as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo $r['status']==$s?'selected':''; ?>><?php echo ucfirst($s); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="update_status" value="1">
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/app_footer.php'; ?>
