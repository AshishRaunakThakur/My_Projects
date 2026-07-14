<?php
require_once '../../config/database.php';
require_once '../../includes/helpers.php';
requireRole('ngo');

$page_title = 'Manage Resources';
$current_page = 'resources';
$user_id = $_SESSION['user_id'];
$success = '';

$ngo = ensureNgoProfile($db, $user_id);
$ngo_id = $ngo['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $resource_type = $_POST['resource_type'];
    $quantity = (int) $_POST['quantity'];

    $check = $db->prepare("SELECT id FROM resources WHERE ngo_id = :nid AND resource_type = :type");
    $check->execute([':nid' => $ngo_id, ':type' => $resource_type]);

    if ($check->rowCount() > 0) {
        $db->prepare("UPDATE resources SET quantity = :qty, last_updated = NOW() WHERE ngo_id = :nid AND resource_type = :type")
           ->execute([':qty' => $quantity, ':nid' => $ngo_id, ':type' => $resource_type]);
    } else {
        $db->prepare("INSERT INTO resources (ngo_id, resource_type, quantity) VALUES (:nid, :type, :qty)")
           ->execute([':nid' => $ngo_id, ':type' => $resource_type, ':qty' => $quantity]);
    }
    $success = "Resources updated successfully!";
}

$resources = $db->prepare("SELECT * FROM resources WHERE ngo_id = :nid ORDER BY resource_type");
$resources->bindParam(':nid', $ngo_id);
$resources->execute();
$resources = $resources->fetchAll(PDO::FETCH_ASSOC);

require_once '../../includes/app_header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check"></i> <?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5" data-aos="fade-right">
        <div class="page-card">
            <div class="card-header"><i class="fas fa-plus-circle me-2"></i> Add / Update Resources</div>
            <div class="card-body">
                <p class="text-muted small">Organization: <strong><?php echo htmlspecialchars($ngo['organization_name']); ?></strong></p>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Resource Type</label>
                        <select name="resource_type" class="form-select" required>
                            <option value="food_packets">🍱 Food Packets</option>
                            <option value="water_bottles">💧 Water Bottles</option>
                            <option value="medicines">💊 Medicines</option>
                            <option value="blankets">🛏️ Blankets</option>
                            <option value="emergency_kits">🆘 Emergency Kits</option>
                            <option value="other">📦 Other</option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Quantity</label><input type="number" name="quantity" class="form-control" min="0" required></div>
                    <button type="submit" class="btn btn-danger w-100"><i class="fas fa-save"></i> Update Inventory</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7" data-aos="fade-left">
        <div class="page-card">
            <div class="card-header bg-success"><i class="fas fa-chart-bar me-2"></i> Current Inventory</div>
            <div class="card-body p-0">
                <?php if (empty($resources)): ?>
                    <p class="text-muted text-center py-4 mb-0">No resources added yet.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Resource</th><th>Quantity</th><th>Last Updated</th></tr></thead>
                        <tbody>
                        <?php foreach ($resources as $r): ?>
                        <tr>
                            <td><?php echo str_replace('_', ' ', ucfirst($r['resource_type'])); ?></td>
                            <td><span class="badge bg-primary fs-6"><?php echo $r['quantity']; ?></span></td>
                            <td><?php echo date('M d, Y H:i', strtotime($r['last_updated'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/app_footer.php'; ?>
