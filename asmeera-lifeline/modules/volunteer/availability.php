<?php
require_once '../../config/database.php';
require_once '../../includes/helpers.php';
requireRole('volunteer');

$page_title = 'Availability';
$current_page = 'availability';
$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

$volunteer = ensureVolunteerProfile($db, $user_id);
$current_avail = $volunteer['availability'] ?? 'offline';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $availability = $_POST['availability'];
    $stmt = $db->prepare("UPDATE volunteers SET availability = :av WHERE user_id = :uid");
    if ($stmt->execute([':av' => $availability, ':uid' => $user_id])) {
        $message = "Availability updated successfully!";
        $current_avail = $availability;
    } else {
        $error = "Failed to update availability.";
    }
}

require_once '../../includes/app_header.php';
?>

<?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-lg-6" data-aos="fade-up">
        <div class="page-card">
            <div class="card-header"><i class="fas fa-user-clock me-2"></i> Current Status</div>
            <div class="card-body">
                <div class="availability-status">
                    <div class="availability-dot <?php echo htmlspecialchars($current_avail); ?>"><i class="fas fa-circle"></i></div>
                    <h3><?php echo ucfirst($current_avail); ?></h3>
                </div>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Change Status</label>
                        <select name="availability" class="form-select form-select-lg">
                            <option value="available" <?php echo $current_avail == 'available' ? 'selected' : ''; ?>>✅ Available — Ready for tasks</option>
                            <option value="busy" <?php echo $current_avail == 'busy' ? 'selected' : ''; ?>>🔄 Busy — On a task</option>
                            <option value="offline" <?php echo $current_avail == 'offline' ? 'selected' : ''; ?>>⛔ Offline — Not accepting</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-danger w-100 btn-lg"><i class="fas fa-save"></i> Update Availability</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6" data-aos="fade-up">
        <div class="page-card">
            <div class="card-header bg-info"><i class="fas fa-info-circle me-2"></i> Guidelines</div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>Available</strong> — You receive emergency notifications</li>
                    <li class="list-group-item"><strong>Busy</strong> — Currently helping someone</li>
                    <li class="list-group-item"><strong>Offline</strong> — No notifications sent</li>
                </ul>
                <div class="alert alert-warning mt-3 mb-0"><i class="fas fa-map-marker-alt"></i> Your GPS location is auto-updated for nearby task matching.</div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/app_footer.php'; ?>
