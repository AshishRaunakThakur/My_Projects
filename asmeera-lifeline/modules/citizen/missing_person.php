<?php
require_once '../../config/database.php';
require_once '../../includes/helpers.php';
requireLogin();

$page_title = 'Missing Persons';
$current_page = 'missing_person';
$success = '';
$error = '';
ensureUploadDirs();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $photo_path = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $photo_path = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], UPLOAD_PATH . 'missing/' . $photo_path);
        }
    }

    $stmt = $db->prepare("INSERT INTO missing_persons (reported_by, full_name, age, gender, photo_path, last_seen_location, last_seen_lat, last_seen_lng, last_seen_date, description, contact_info) VALUES (:rb,:fn,:age,:gen,:photo,:loc,:lat,:lng,:dt,:desc,:contact)");
    if ($stmt->execute([
        ':rb' => $_SESSION['user_id'], ':fn' => $_POST['full_name'], ':age' => $_POST['age'] ?: null,
        ':gen' => $_POST['gender'], ':photo' => $photo_path, ':loc' => $_POST['last_seen_location'],
        ':lat' => $_POST['last_seen_lat'] ?: null, ':lng' => $_POST['last_seen_lng'] ?: null,
        ':dt' => $_POST['last_seen_date'], ':desc' => $_POST['description'], ':contact' => $_POST['contact_info']
    ])) {
        $success = "Missing person report submitted successfully!";
    } else {
        $error = "Failed to submit report.";
    }
}

$missing_persons = $db->query("SELECT mp.*, u.full_name as reporter_name FROM missing_persons mp JOIN users u ON mp.reported_by = u.id WHERE mp.status = 'missing' ORDER BY mp.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

require_once '../../includes/app_header.php';
?>

<div class="row g-4">
    <div class="col-lg-5" data-aos="fade-right">
        <div class="page-card">
            <div class="card-header"><i class="fas fa-user-friends me-2"></i> Report Missing Person</div>
            <div class="card-body">
                <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3"><label class="form-label">Full Name *</label><input type="text" name="full_name" class="form-control" required></div>
                    <div class="row">
                        <div class="col-6 mb-3"><label class="form-label">Age</label><input type="number" name="age" class="form-control"></div>
                        <div class="col-6 mb-3"><label class="form-label">Gender</label><select name="gender" class="form-select"><option value="male">Male</option><option value="female">Female</option><option value="other">Other</option></select></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Photo</label><input type="file" name="photo" class="form-control" accept="image/*"></div>
                    <div class="mb-3">
                        <label class="form-label">Last Seen Location *</label>
                        <input type="text" name="last_seen_location" id="last_seen_location" class="form-control" required>
                        <div id="locationMap" class="map-container mt-2"></div>
                        <input type="hidden" name="last_seen_lat" id="last_seen_lat">
                        <input type="hidden" name="last_seen_lng" id="last_seen_lng">
                    </div>
                    <div class="mb-3"><label class="form-label">Last Seen Date *</label><input type="date" name="last_seen_date" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                    <div class="mb-3"><label class="form-label">Contact Info *</label><input type="text" name="contact_info" class="form-control" required></div>
                    <button type="submit" class="btn btn-danger w-100"><i class="fas fa-paper-plane"></i> Submit Report</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7" data-aos="fade-left">
        <div class="page-card">
            <div class="card-header bg-dark"><i class="fas fa-search me-2"></i> Missing Persons Database (<?php echo count($missing_persons); ?>)</div>
            <div class="card-body">
                <div class="row g-3">
                    <?php if (empty($missing_persons)): ?>
                        <p class="text-muted text-center py-4">No missing person reports yet.</p>
                    <?php else: foreach ($missing_persons as $person): ?>
                    <div class="col-md-6">
                        <div class="card missing-person-card h-100">
                            <?php if ($person['photo_path']): ?>
                                <img src="<?php echo SITE_URL; ?>assets/uploads/missing/<?php echo htmlspecialchars($person['photo_path']); ?>" alt="">
                            <?php else: ?>
                                <div class="no-photo"><i class="fas fa-user"></i></div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h6><?php echo htmlspecialchars($person['full_name']); ?></h6>
                                <p class="small text-muted mb-2"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars(substr($person['last_seen_location'], 0, 40)); ?></p>
                                <a href="<?php echo SITE_URL; ?>view_missing.php?id=<?php echo $person['id']; ?>" class="btn btn-sm btn-danger">View Details</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extra_scripts = '<script>const SITE_URL="' . SITE_URL . '";</script><script src="' . SITE_URL . 'assets/js/dashboard.js"></script>
<script>document.addEventListener("DOMContentLoaded",()=>initMapPicker("locationMap","last_seen_lat","last_seen_lng","last_seen_location"));</script>';
require_once '../../includes/app_footer.php';
?>
