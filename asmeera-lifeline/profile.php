<?php
require_once 'config/database.php';
require_once 'includes/helpers.php';
requireLogin();

$page_title = 'My Profile';
$current_page = 'profile';
$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

$stmt = $db->prepare("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = :id");
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    if (empty($full_name)) {
        $error = 'Full name is required.';
    } else {
        $db->prepare("UPDATE users SET full_name = :fn, phone = :ph, address = :ad WHERE id = :id")
           ->execute([':fn' => $full_name, ':ph' => $phone, ':ad' => $address, ':id' => $user_id]);
        $_SESSION['user_name'] = $full_name;
        $user['full_name'] = $full_name;
        $user['phone'] = $phone;
        $user['address'] = $address;
        $success = 'Profile updated successfully!';
    }

    if (!empty($_POST['new_password']) && !empty($_POST['current_password'])) {
        if (password_verify($_POST['current_password'], $user['password'])) {
            $hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $db->prepare("UPDATE users SET password = :pw WHERE id = :id")->execute([':pw' => $hash, ':id' => $user_id]);
            $success = 'Profile and password updated!';
        } else {
            $error = 'Current password is incorrect.';
        }
    }
}

require_once 'includes/app_header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="page-card" data-aos="fade-up">
            <div class="card-header"><i class="fas fa-user-circle me-2"></i> My Profile</div>
            <div class="card-body">
                <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

                <div class="profile-header mb-4">
                    <div class="profile-avatar"><i class="fas fa-user"></i></div>
                    <div>
                        <h4 class="mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h4>
                        <span class="badge bg-danger"><?php echo ucfirst($user['role_name']); ?></span>
                    </div>
                </div>

                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Email</label><input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Member Since</label><input type="text" class="form-control" value="<?php echo date('M d, Y', strtotime($user['created_at'])); ?>" disabled></div>
                        <div class="col-12 mb-3"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea></div>
                    </div>
                    <hr>
                    <h6 class="mb-3"><i class="fas fa-lock"></i> Change Password</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-control"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control"></div>
                    </div>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-save"></i> Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/app_footer.php'; ?>
