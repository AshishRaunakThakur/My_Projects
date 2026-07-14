<?php
require_once '../../config/database.php';
require_once '../../includes/helpers.php';
requireRole('admin');

$page_title = 'Manage NGOs';
$current_page = 'ngos';
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_ngo'])) {
    $nid = (int) $_POST['ngo_id'];
    $verified = (int) $_POST['verified'];
    $db->prepare("UPDATE ngos SET verified = :v WHERE id = :id")->execute([':v' => $verified, ':id' => $nid]);
    $message = 'NGO verification updated.';
}

$ngos = $db->query("SELECT n.*, u.full_name, u.email, u.phone FROM ngos n JOIN users u ON n.user_id = u.id ORDER BY n.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

require_once '../../includes/app_header.php';
?>

<?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

<div class="page-card" data-aos="fade-up">
    <div class="card-header bg-success"><i class="fas fa-building me-2"></i> Registered NGOs</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Organization</th><th>Contact</th><th>Reg. No.</th><th>Verified</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($ngos as $n): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($n['organization_name']); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($n['full_name']); ?></small></td>
                    <td><?php echo htmlspecialchars($n['email']); ?><br><?php echo htmlspecialchars($n['phone'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($n['registration_number'] ?? '—'); ?></td>
                    <td><?php echo $n['verified'] ? '<span class="badge bg-success">Verified</span>' : '<span class="badge bg-warning text-dark">Pending</span>'; ?></td>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="ngo_id" value="<?php echo $n['id']; ?>">
                            <input type="hidden" name="verified" value="<?php echo $n['verified'] ? 0 : 1; ?>">
                            <button type="submit" name="verify_ngo" class="btn btn-sm btn-<?php echo $n['verified'] ? 'warning' : 'success'; ?>"><?php echo $n['verified'] ? 'Revoke' : 'Verify'; ?></button>
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
