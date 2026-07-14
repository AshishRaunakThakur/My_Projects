<?php
require_once '../../config/database.php';
require_once '../../includes/helpers.php';
requireRole('admin');

$page_title = 'Manage Volunteers';
$current_page = 'volunteers';
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_vol'])) {
    $vid = (int) $_POST['volunteer_id'];
    $verified = (int) $_POST['verified'];
    $db->prepare("UPDATE volunteers SET verified = :v WHERE id = :id")->execute([':v' => $verified, ':id' => $vid]);
    $message = 'Volunteer verification updated.';
}

$volunteers = $db->query("SELECT v.*, u.full_name, u.email, u.phone FROM volunteers v JOIN users u ON v.user_id = u.id ORDER BY v.joined_date DESC")->fetchAll(PDO::FETCH_ASSOC);

require_once '../../includes/app_header.php';
?>

<?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

<div class="page-card" data-aos="fade-up">
    <div class="card-header bg-info"><i class="fas fa-hands-helping me-2"></i> Registered Volunteers</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Name</th><th>Contact</th><th>Skills</th><th>Availability</th><th>Tasks Done</th><th>Verified</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($volunteers as $v): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($v['full_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($v['email']); ?></td>
                    <td><?php echo htmlspecialchars(substr($v['skills'] ?? '—', 0, 40)); ?></td>
                    <td><span class="badge bg-<?php echo $v['availability']=='available'?'success':($v['availability']=='busy'?'warning':'secondary'); ?>"><?php echo ucfirst($v['availability']); ?></span></td>
                    <td><?php echo $v['total_tasks_completed']; ?></td>
                    <td><?php echo $v['verified'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-warning text-dark">No</span>'; ?></td>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="volunteer_id" value="<?php echo $v['id']; ?>">
                            <input type="hidden" name="verified" value="<?php echo $v['verified'] ? 0 : 1; ?>">
                            <button type="submit" name="verify_vol" class="btn btn-sm btn-<?php echo $v['verified'] ? 'warning' : 'success'; ?>"><?php echo $v['verified'] ? 'Revoke' : 'Verify'; ?></button>
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
