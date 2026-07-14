<?php
require_once '../../config/database.php';
require_once '../../includes/helpers.php';
requireRole('admin');

$page_title = 'Safe Check-ins';
$current_page = 'checkins';

$checkins = $db->query("SELECT sc.*, u.full_name, u.phone FROM safe_checkins sc JOIN users u ON sc.user_id = u.id ORDER BY sc.checked_in_at DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);

require_once '../../includes/app_header.php';
?>

<div class="page-card" data-aos="fade-up">
    <div class="card-header bg-success"><i class="fas fa-heart me-2"></i> Recent Safe Check-ins</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>User</th><th>Phone</th><th>Location</th><th>Message</th><th>Time</th></tr></thead>
                <tbody>
                <?php if (empty($checkins)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No check-ins yet.</td></tr>
                <?php else: foreach ($checkins as $c): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($c['full_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($c['phone'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars(substr($c['location_name'], 0, 50)); ?></td>
                    <td><?php echo htmlspecialchars(substr($c['message'] ?? '—', 0, 60)); ?></td>
                    <td><?php echo date('M d, Y H:i', strtotime($c['checked_in_at'])); ?></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/app_footer.php'; ?>
