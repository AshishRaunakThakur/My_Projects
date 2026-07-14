<?php
require_once 'config/database.php';
require_once 'includes/helpers.php';
requireLogin();

$request_id = (int) ($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT er.*, u.full_name, u.phone, u.email FROM emergency_requests er JOIN users u ON er.user_id = u.id WHERE er.id = :id");
$stmt->bindParam(':id', $request_id);
$stmt->execute();
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    header('Location: ' . SITE_URL . 'dashboard.php');
    exit();
}

$page_title = 'Request #' . $request['id'];
$current_page = 'dashboard';

require_once 'includes/app_header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="page-card" data-aos="fade-up">
            <div class="card-header"><i class="fas fa-exclamation-triangle me-2"></i> Emergency Request #<?php echo $request['id']; ?></div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <p><strong>Title:</strong> <?php echo htmlspecialchars($request['title']); ?></p>
                        <p><strong>Type:</strong> <span class="badge bg-secondary"><?php echo ucfirst($request['request_type']); ?></span></p>
                        <p><strong>Priority:</strong> <span class="<?php echo priorityBadgeClass($request['priority']); ?>"><?php echo strtoupper($request['priority']); ?></span></p>
                        <p><strong>Status:</strong> <span class="badge <?php echo statusBadgeClass($request['status']); ?>"><?php echo ucfirst(str_replace('_',' ',$request['status'])); ?></span></p>
                        <p><strong>Affected People:</strong> <?php echo $request['affected_people']; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Reported By:</strong> <?php echo htmlspecialchars($request['full_name']); ?></p>
                        <p><strong>Phone:</strong> <a href="tel:<?php echo $request['phone']; ?>"><?php echo htmlspecialchars($request['phone']); ?></a></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($request['email']); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($request['location_name'] ?? '—'); ?></p>
                        <p><strong>Reported:</strong> <?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?></p>
                    </div>
                    <div class="col-12">
                        <p><strong>Description:</strong></p>
                        <p class="bg-light p-3 rounded"><?php echo nl2br(htmlspecialchars($request['description'])); ?></p>
                    </div>
                    <?php if ($request['image_path']): ?>
                    <div class="col-12">
                        <p><strong>Evidence:</strong></p>
                        <img src="<?php echo SITE_URL; ?>assets/uploads/<?php echo htmlspecialchars($request['image_path']); ?>" class="img-fluid rounded" style="max-height:300px;" alt="Evidence">
                    </div>
                    <?php endif; ?>
                    <?php if ($request['latitude'] && $request['longitude']): ?>
                    <div class="col-12"><div id="locationMap" class="map-container"></div></div>
                    <?php endif; ?>
                </div>
                <div class="mt-4 d-flex gap-2 flex-wrap">
                    <a href="<?php echo SITE_URL; ?>dashboard.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Dashboard</a>
                    <?php if ($_SESSION['user_role'] == 'volunteer' && $request['status'] == 'pending'): ?>
                    <button class="btn btn-danger" onclick="acceptTask(<?php echo $request['id']; ?>)"><i class="fas fa-hand-paper"></i> Accept Task</button>
                    <?php endif; ?>
                    <?php if ($_SESSION['user_role'] == 'admin'): ?>
                    <a href="<?php echo SITE_URL; ?>modules/admin/requests.php" class="btn btn-dark"><i class="fas fa-cog"></i> Manage</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extra_scripts = '<script>
function acceptTask(id){
    fetch("' . SITE_URL . 'api/accept_task.php",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({request_id:id})})
    .then(r=>r.json()).then(d=>{if(d.success){showToast("Task accepted!","success");setTimeout(()=>location.reload(),1000);}else showToast(d.message||"Failed","error");});
}
</script>';
if ($request['latitude'] && $request['longitude']) {
    $extra_scripts .= '<script>const SITE_URL="' . SITE_URL . '";</script><script src="' . SITE_URL . 'assets/js/dashboard.js"></script>
    <script>document.addEventListener("DOMContentLoaded",()=>initMapPicker("locationMap",null,null,null,' . $request['latitude'] . ',' . $request['longitude'] . ',14));</script>';
}
require_once 'includes/app_footer.php';
?>
