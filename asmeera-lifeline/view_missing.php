<?php
require_once 'config/database.php';
require_once 'includes/helpers.php';
requireLogin();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT mp.*, u.full_name as reporter_name, u.phone as reporter_phone FROM missing_persons mp JOIN users u ON mp.reported_by = u.id WHERE mp.id = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();
$person = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$person) {
    header('Location: ' . SITE_URL . 'dashboard.php');
    exit();
}

$page_title = 'Missing: ' . $person['full_name'];
$current_page = 'missing_person';

require_once 'includes/app_header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="page-card" data-aos="fade-up">
            <div class="card-header"><i class="fas fa-user-friends me-2"></i> Missing Person Details</div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-4 text-center">
                        <?php if ($person['photo_path']): ?>
                            <img src="<?php echo SITE_URL; ?>assets/uploads/missing/<?php echo htmlspecialchars($person['photo_path']); ?>" class="img-fluid rounded" style="max-height:280px;object-fit:cover;" alt="">
                        <?php else: ?>
                            <div class="missing-person-card no-photo rounded"><i class="fas fa-user fa-4x"></i></div>
                        <?php endif; ?>
                        <span class="badge bg-<?php echo $person['status']=='missing'?'danger':'success'; ?> mt-2 fs-6"><?php echo strtoupper($person['status']); ?></span>
                    </div>
                    <div class="col-md-8">
                        <h3><?php echo htmlspecialchars($person['full_name']); ?></h3>
                        <p><strong>Age:</strong> <?php echo $person['age'] ?? 'Unknown'; ?> &nbsp; <strong>Gender:</strong> <?php echo ucfirst($person['gender'] ?? '—'); ?></p>
                        <p><strong>Last Seen:</strong> <?php echo htmlspecialchars($person['last_seen_location']); ?></p>
                        <p><strong>Date:</strong> <?php echo $person['last_seen_date']; ?></p>
                        <p><strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($person['description'] ?? 'No description.')); ?></p>
                        <p><strong>Contact:</strong> <?php echo htmlspecialchars($person['contact_info']); ?></p>
                        <p><strong>Reported by:</strong> <?php echo htmlspecialchars($person['reporter_name']); ?> (<?php echo htmlspecialchars($person['reporter_phone'] ?? ''); ?>)</p>
                        <?php if ($person['last_seen_lat'] && $person['last_seen_lng']): ?>
                        <div id="locationMap" class="map-container mt-3"></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="<?php echo SITE_URL; ?>dashboard.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                    <?php if ($_SESSION['user_role'] === 'admin' && $person['status'] === 'missing'): ?>
                    <form method="POST" action="<?php echo SITE_URL; ?>modules/admin/missing_persons.php" class="d-inline">
                        <input type="hidden" name="person_id" value="<?php echo $person['id']; ?>">
                        <button type="submit" name="mark_found" class="btn btn-success"><i class="fas fa-check"></i> Mark as Found</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($person['last_seen_lat'] && $person['last_seen_lng']):
$extra_scripts = '<script>const SITE_URL="' . SITE_URL . '";</script><script src="' . SITE_URL . 'assets/js/dashboard.js"></script>
<script>document.addEventListener("DOMContentLoaded",()=>initMapPicker("locationMap",null,null,null,' . $person['last_seen_lat'] . ',' . $person['last_seen_lng'] . ',14));</script>';
else:
$extra_scripts = '';
endif;
require_once 'includes/app_footer.php';
?>
