<?php
require_once '../../config/database.php';
require_once '../../includes/helpers.php';
requireRole('citizen');

$page_title = 'I Am Safe';
$current_page = 'safe_checkin';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $location_name = trim($_POST['location_name']);
    $message = trim($_POST['message']) ?: 'I am safe at this location.';

    $stmt = $db->prepare("INSERT INTO safe_checkins (user_id, latitude, longitude, location_name, message) VALUES (:uid, :lat, :lng, :loc, :msg)");
    if ($stmt->execute([':uid' => $user_id, ':lat' => $latitude, ':lng' => $longitude, ':loc' => $location_name, ':msg' => $message])) {
        $success = "You have been marked as SAFE! Authorities have been notified.";
        $user_name = $_SESSION['user_name'];
        $notify_msg = "$user_name checked in safe: $location_name";
        $notify = $db->prepare("INSERT INTO notifications (user_id, title, message, type) SELECT id, 'Safe Check-in', :msg, 'alert' FROM users WHERE role_id = 4");
        $notify->bindParam(':msg', $notify_msg);
        $notify->execute();
    } else {
        $error = "Failed to check in. Please try again.";
    }
}

require_once '../../includes/app_header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <div class="page-card" data-aos="fade-up">
            <div class="card-header bg-success"><i class="fas fa-shield-alt me-2"></i> Safe Check-in</div>
            <div class="card-body text-center">
                <i class="fas fa-shield-alt fa-4x text-success mb-3"></i>
                <h4>Let others know you're safe</h4>
                <p class="text-muted mb-4">Share your location so family and authorities know you're okay.</p>
                <form method="POST">
                    <div class="mb-3 text-start">
                        <label class="form-label">Your Location</label>
                        <div id="locationMap" class="map-container"></div>
                        <input type="hidden" name="latitude" id="latitude" required>
                        <input type="hidden" name="longitude" id="longitude" required>
                        <input type="text" name="location_name" id="location_name" class="form-control mt-2" placeholder="Location name" required>
                    </div>
                    <div class="mb-3 text-start">
                        <label class="form-label">Message (Optional)</label>
                        <textarea name="message" class="form-control" rows="3" placeholder="I am safe and at this location..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-success btn-lg w-100"><i class="fas fa-check-circle"></i> Mark Myself Safe</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$extra_scripts = '<script>const SITE_URL="' . SITE_URL . '";</script><script src="' . SITE_URL . 'assets/js/dashboard.js"></script>
<script>
document.addEventListener("DOMContentLoaded",()=>{
    initMapPicker("locationMap","latitude","longitude","location_name");
    if(navigator.geolocation){
        navigator.geolocation.getCurrentPosition(p=>{
            const lat=p.coords.latitude,lng=p.coords.longitude;
            document.getElementById("latitude").value=lat;
            document.getElementById("longitude").value=lng;
        });
    }
});
</script>';
require_once '../../includes/app_footer.php';
?>
