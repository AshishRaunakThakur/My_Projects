<?php
require_once '../../config/database.php';
require_once '../../includes/helpers.php';
requireRole('admin');

$page_title = 'Missing Persons';
$current_page = 'missing_persons';
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_found'])) {
    $id = (int) $_POST['person_id'];
    $db->prepare("UPDATE missing_persons SET status = 'found', found_date = CURDATE() WHERE id = :id")->execute([':id' => $id]);
    $message = 'Person marked as found.';
}

$persons = $db->query("SELECT mp.*, u.full_name as reporter_name FROM missing_persons mp JOIN users u ON mp.reported_by = u.id ORDER BY mp.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

require_once '../../includes/app_header.php';
?>

<?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

<div class="page-card" data-aos="fade-up">
    <div class="card-header"><i class="fas fa-search me-2"></i> Missing Persons Reports</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Name</th><th>Age/Gender</th><th>Last Seen</th><th>Status</th><th>Reporter</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($persons as $p): ?>
                <tr>
                    <td><a href="<?php echo SITE_URL; ?>view_missing.php?id=<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['full_name']); ?></a></td>
                    <td><?php echo ($p['age'] ?? '?') . ' / ' . ucfirst($p['gender'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars(substr($p['last_seen_location'], 0, 40)); ?><br><small><?php echo $p['last_seen_date']; ?></small></td>
                    <td><span class="badge bg-<?php echo $p['status']=='missing'?'danger':'success'; ?>"><?php echo ucfirst($p['status']); ?></span></td>
                    <td><?php echo htmlspecialchars($p['reporter_name']); ?></td>
                    <td>
                        <?php if ($p['status'] == 'missing'): ?>
                        <form method="POST" class="d-inline"><input type="hidden" name="person_id" value="<?php echo $p['id']; ?>"><button type="submit" name="mark_found" class="btn btn-sm btn-success">Mark Found</button></form>
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
