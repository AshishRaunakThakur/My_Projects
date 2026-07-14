<?php
require_once '../../config/database.php';
require_once '../../includes/helpers.php';
requireRole('admin');

$page_title = 'Manage Users';
$current_page = 'users';
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_active'])) {
    $uid = (int) $_POST['user_id'];
    $active = (int) $_POST['is_active'];
    $db->prepare("UPDATE users SET is_active = :a WHERE id = :id AND role_id != 4")->execute([':a' => $active, ':id' => $uid]);
    $message = 'User status updated.';
}

$users = $db->query("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

require_once '../../includes/app_header.php';
?>

<?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

<div class="page-card" data-aos="fade-up">
    <div class="card-header bg-dark"><i class="fas fa-users-cog me-2"></i> All Users (<?php echo count($users); ?>)</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Phone</th><th>Status</th><th>Joined</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>#<?php echo $u['id']; ?></td>
                    <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td><span class="badge bg-danger"><?php echo ucfirst($u['role_name']); ?></span></td>
                    <td><?php echo htmlspecialchars($u['phone'] ?? '—'); ?></td>
                    <td><?php echo $u['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'; ?></td>
                    <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                    <td>
                        <?php if ($u['role_name'] !== 'admin'): ?>
                        <form method="POST" class="d-inline admin-table-actions">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                            <input type="hidden" name="is_active" value="<?php echo $u['is_active'] ? 0 : 1; ?>">
                            <button type="submit" name="toggle_active" class="btn btn-sm btn-<?php echo $u['is_active'] ? 'warning' : 'success'; ?>"><?php echo $u['is_active'] ? 'Deactivate' : 'Activate'; ?></button>
                        </form>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/app_footer.php'; ?>
