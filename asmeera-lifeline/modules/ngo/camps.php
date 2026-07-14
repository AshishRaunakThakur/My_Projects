<?php
require_once '../../config/database.php';
require_once '../../includes/helpers.php';
requireRole('ngo');

$page_title = 'Relief Camps';
$current_page = 'camps';
$user_id = $_SESSION['user_id'];
$success = '';

$ngo = ensureNgoProfile($db, $user_id);
$ngo_id = $ngo['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_camp'])) {
    $stmt = $db->prepare("INSERT INTO relief_camps (ngo_id, camp_name, camp_type, latitude, longitude, address, capacity, contact_phone) VALUES (:nid,:name,:type,:lat,:lng,:addr,:cap,:phone)");
    $stmt->execute([
        ':nid' => $ngo_id, ':name' => $_POST['camp_name'], ':type' => $_POST['camp_type'],
        ':lat' => $_POST['latitude'] ?: null, ':lng' => $_POST['longitude'] ?: null,
        ':addr' => $_POST['address'], ':cap' => $_POST['capacity'] ?: null, ':phone' => $_POST['contact_phone']
    ]);
    $success = "Camp created successfully!";
}

$camps = $db->prepare("SELECT * FROM relief_camps WHERE ngo_id = :nid ORDER BY created_at DESC");
$camps->bindParam(':nid', $ngo_id);
$camps->execute();
$camps = $camps->fetchAll(PDO::FETCH_ASSOC);

require_once '../../includes/app_header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5" data-aos="fade-right">
        <div class="page-card">
            <div class="card-header"><i class="fas fa-plus me-2"></i> Create New Camp</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="add_camp" value="1">
                    <div class="mb-3"><label class="form-label">Camp Name *</label><input type="text" name="camp_name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Camp Type</label><select name="camp_type" class="form-select"><option value="relief">Relief Camp</option><option value="medical">Medical Camp</option><option value="food">Food Distribution</option><option value="shelter">Shelter</option></select></div>
                    <div class="mb-3"><label class="form-label">Location <small class="text-muted">(click map)</small></label><div id="campMap" class="camp-map-container"></div><input type="hidden" name="latitude" id="latitude"><input type="hidden" name="longitude" id="longitude"></div>
                    <div class="mb-3"><label class="form-label">Address *</label><textarea name="address" class="form-control" rows="2" required></textarea></div>
                    <div class="mb-3"><label class="form-label">Capacity</label><input type="number" name="capacity" class="form-control" min="1"></div>
                    <div class="mb-3"><label class="form-label">Contact Phone</label><input type="text" name="contact_phone" class="form-control"></div>
                    <button type="submit" class="btn btn-danger w-100"><i class="fas fa-campground"></i> Create Camp</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7" data-aos="fade-left">
        <div class="page-card">
            <div class="card-header bg-success"><i class="fas fa-list me-2"></i> Your Camps (<?php echo count($camps); ?>)</div>
            <div class="card-body">
                <?php if (empty($camps)): ?>
                    <p class="text-muted text-center py-4">No camps created yet.</p>
                <?php else: foreach ($camps as $camp): ?>
                <div class="card mb-3 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="mb-1"><?php echo htmlspecialchars($camp['camp_name']); ?></h5>
                                <span class="badge bg-info"><?php echo ucfirst($camp['camp_type']); ?></span>
                                <?php if (!$camp['is_active']): ?><span class="badge bg-secondary">Inactive</span><?php endif; ?>
                            </div>
                            <span class="text-muted small"><i class="fas fa-users"></i> <?php echo $camp['capacity'] ?? 'N/A'; ?></span>
                        </div>
                        <p class="small text-muted mt-2 mb-1"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($camp['address']); ?></p>
                        <?php if ($camp['contact_phone']): ?><p class="small mb-0"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($camp['contact_phone']); ?></p><?php endif; ?>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$extra_scripts = '<script>const SITE_URL="' . SITE_URL . '";</script><script src="' . SITE_URL . 'assets/js/dashboard.js"></script>
<script>document.addEventListener("DOMContentLoaded",()=>initMapPicker("campMap","latitude","longitude",null,20.5937,78.9629,5));</script>';
require_once '../../includes/app_footer.php';
?>
