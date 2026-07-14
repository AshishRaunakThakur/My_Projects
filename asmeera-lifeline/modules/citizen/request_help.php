<?php
require_once '../../config/database.php';
require_once '../../includes/helpers.php';
requireRole('citizen');

$page_title = 'Request Emergency Help';
$current_page = 'request_help';
$success = '';
ensureUploadDirs();

function calculatePriority($request_type, $affected_people) {
    if ($request_type == 'medical') return 'critical';
    if ($affected_people > 50) return 'critical';
    if ($affected_people > 20) return 'high';
    if (in_array($request_type, ['food', 'water'])) return 'high';
    if ($request_type == 'shelter') return 'medium';
    return 'low';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $request_type = $_POST['request_type'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $affected_people = (int) $_POST['affected_people'];
    $latitude = $_POST['latitude'] ?: null;
    $longitude = $_POST['longitude'] ?: null;
    $location_name = trim($_POST['location_name']);
    $priority = calculatePriority($request_type, $affected_people);

    $image_path = '';
    if (isset($_FILES['evidence_image']) && $_FILES['evidence_image']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['evidence_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $image_path = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            move_uploaded_file($_FILES['evidence_image']['tmp_name'], UPLOAD_PATH . $image_path);
        }
    }

    $query = "INSERT INTO emergency_requests (user_id, request_type, title, description, priority, affected_people, latitude, longitude, location_name, image_path) 
              VALUES (:user_id, :request_type, :title, :description, :priority, :affected_people, :latitude, :longitude, :location_name, :image_path)";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':user_id' => $user_id, ':request_type' => $request_type, ':title' => $title,
        ':description' => $description, ':priority' => $priority, ':affected_people' => $affected_people,
        ':latitude' => $latitude, ':longitude' => $longitude, ':location_name' => $location_name,
        ':image_path' => $image_path
    ]);

    if ($stmt->rowCount()) {
        $success = "Emergency request submitted! Priority: " . strtoupper($priority);
        $notify = $db->prepare("INSERT INTO notifications (user_id, title, message, type) 
            SELECT u.id, 'New Emergency Request', :title, 'emergency' FROM users u 
            JOIN volunteers v ON u.id = v.user_id WHERE v.availability = 'available'");
        $notify->bindParam(':title', $title);
        $notify->execute();
    }
}

require_once '../../includes/app_header.php';
?>

<?php if ($success): ?><div class="alert alert-success" data-aos="fade-up"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="page-card" data-aos="fade-up">
            <div class="card-header"><i class="fas fa-exclamation-triangle me-2"></i> Request Emergency Help</div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" id="requestForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Request Type *</label>
                            <select name="request_type" class="form-select" required>
                                <option value="food">🍱 Food</option>
                                <option value="water">💧 Water</option>
                                <option value="medical">🏥 Medical</option>
                                <option value="shelter">🏠 Shelter</option>
                                <option value="rescue">🆘 Rescue</option>
                                <option value="other">📋 Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Affected People *</label>
                            <input type="number" name="affected_people" class="form-control" min="1" required>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Title *</label>
                            <input type="text" name="title" class="form-control" placeholder="Brief title of emergency" required>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Description *</label>
                            <textarea name="description" rows="4" class="form-control" placeholder="Describe the situation in detail..." required></textarea>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Location * <small class="text-muted">(Click on map)</small></label>
                            <div id="locationMap" class="map-container"></div>
                            <input type="hidden" name="latitude" id="latitude" required>
                            <input type="hidden" name="longitude" id="longitude" required>
                            <input type="text" name="location_name" id="location_name" class="form-control mt-2" placeholder="Location name" required>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Evidence Photo</label>
                            <input type="file" name="evidence_image" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-danger"><i class="fas fa-paper-plane"></i> Submit Request</button>
                        <a href="<?php echo SITE_URL; ?>dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$extra_scripts = '<script>const SITE_URL="' . SITE_URL . '";</script><script src="' . SITE_URL . 'assets/js/dashboard.js"></script>
<script>document.addEventListener("DOMContentLoaded",()=>initMapPicker("locationMap","latitude","longitude","location_name"));</script>';
require_once '../../includes/app_footer.php';
?>
