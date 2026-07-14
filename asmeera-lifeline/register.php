<?php
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';
$role = isset($_GET['role']) ? $_GET['role'] : 'citizen';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $role_id = $_POST['role_id'];

    if (empty($full_name) || empty($email) || empty($_POST['password']) || empty($phone)) {
        $error = "Please fill in all required fields!";
    } else {
        $check = "SELECT id FROM users WHERE email = :email";
        $stmt = $db->prepare($check);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $error = "Email already registered!";
        } else {
            $query = "INSERT INTO users (full_name, email, password, phone, address, role_id) 
                      VALUES (:full_name, :email, :password, :phone, :address, :role_id)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':role_id', $role_id);

            if ($stmt->execute()) {
                $user_id = $db->lastInsertId();

                if ($role_id == 2) {
                    $vol_query = "INSERT INTO volunteers (user_id, skills, availability) VALUES (:user_id, :skills, 'available')";
                    $vol_stmt = $db->prepare($vol_query);
                    $vol_stmt->bindParam(':user_id', $user_id);
                    $vol_stmt->bindValue(':skills', $_POST['skills'] ?? '');
                    $vol_stmt->execute();
                } elseif ($role_id == 3) {
                    $ngo_query = "INSERT INTO ngos (user_id, organization_name, registration_number, contact_person) 
                                  VALUES (:user_id, :org_name, :reg_no, :contact_person)";
                    $ngo_stmt = $db->prepare($ngo_query);
                    $ngo_stmt->bindParam(':user_id', $user_id);
                    $ngo_stmt->bindParam(':org_name', $_POST['organization_name']);
                    $ngo_stmt->bindParam(':reg_no', $_POST['registration_number']);
                    $ngo_stmt->bindParam(':contact_person', $_POST['contact_person']);
                    $ngo_stmt->execute();
                }

                $success = "Registration successful! Please login.";
            } else {
                $error = "Registration failed! Please try again.";
            }
        }
    }
}

$roles_query = "SELECT * FROM roles WHERE role_name != 'admin'";
$roles_stmt = $db->prepare($roles_query);
$roles_stmt->execute();
$roles = $roles_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Asmeera Lifeline</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        html,body{height:100%;overflow-y:auto!important;}
        body{
            font-family:'Inter',sans-serif;
            background:linear-gradient(135deg,#0a0e1a 0%,#1a1a2e 50%,#2d1b3d 100%);
            min-height:100vh;
            padding:30px 20px;
            position:relative;
            overflow-y:auto!important;
        }

        /* Animated Background Shapes */
        .bg-shapes{position:fixed;width:100%;height:100%;top:0;left:0;pointer-events:none;overflow:hidden;z-index:0;}
        .bg-shapes .shape{
            position:absolute;
            border-radius:50%;
            opacity:0.05;
            animation:floatShape 20s infinite ease-in-out;
        }
        .bg-shapes .shape-1{
            width:500px;height:500px;
            background:radial-gradient(circle,#dc3545,transparent);
            top:-100px;right:-100px;
            animation-delay:0s;
        }
        .bg-shapes .shape-2{
            width:400px;height:400px;
            background:radial-gradient(circle,#ff6b6b,transparent);
            bottom:-100px;left:-100px;
            animation-delay:-5s;
            animation-duration:25s;
        }
        .bg-shapes .shape-3{
            width:300px;height:300px;
            background:radial-gradient(circle,#dc3545,transparent);
            top:50%;left:50%;
            transform:translate(-50%,-50%);
            animation-delay:-10s;
            animation-duration:30s;
            opacity:0.03;
        }
        .bg-shapes .shape-4{
            width:200px;height:200px;
            background:radial-gradient(circle,#ff6b6b,transparent);
            top:20%;right:10%;
            animation-delay:-3s;
            animation-duration:18s;
            opacity:0.04;
        }
        .bg-shapes .shape-5{
            width:250px;height:250px;
            background:radial-gradient(circle,#dc3545,transparent);
            bottom:30%;left:5%;
            animation-delay:-7s;
            animation-duration:22s;
            opacity:0.03;
        }

        @keyframes floatShape{
            0%,100%{transform:translate(0,0) scale(1) rotate(0deg);}
            25%{transform:translate(30px,-40px) scale(1.1) rotate(5deg);}
            50%{transform:translate(-20px,30px) scale(0.9) rotate(-3deg);}
            75%{transform:translate(40px,20px) scale(1.05) rotate(8deg);}
        }

        /* Floating Particles */
        .particles{position:fixed;width:100%;height:100%;top:0;left:0;pointer-events:none;z-index:0;overflow:hidden;}
        .particle{
            position:absolute;
            width:4px;height:4px;
            background:rgba(220,38,38,0.3);
            border-radius:50%;
            animation:floatParticle 15s infinite linear;
        }
        .particle:nth-child(1){left:10%;animation-delay:0s;animation-duration:12s;}
        .particle:nth-child(2){left:20%;animation-delay:2s;animation-duration:15s;width:6px;height:6px;}
        .particle:nth-child(3){left:30%;animation-delay:4s;animation-duration:18s;}
        .particle:nth-child(4){left:40%;animation-delay:1s;animation-duration:14s;width:3px;height:3px;}
        .particle:nth-child(5){left:50%;animation-delay:3s;animation-duration:16s;}
        .particle:nth-child(6){left:60%;animation-delay:5s;animation-duration:13s;width:5px;height:5px;}
        .particle:nth-child(7){left:70%;animation-delay:2s;animation-duration:17s;}
        .particle:nth-child(8){left:80%;animation-delay:4s;animation-duration:19s;width:3px;height:3px;}
        .particle:nth-child(9){left:90%;animation-delay:1s;animation-duration:11s;}
        .particle:nth-child(10){left:95%;animation-delay:3s;animation-duration:20s;width:6px;height:6px;}

        @keyframes floatParticle{
            0%{transform:translateY(100vh) scale(0);opacity:0;}
            10%{opacity:1;}
            90%{opacity:1;}
            100%{transform:translateY(-100vh) scale(1);opacity:0;}
        }

        .auth-container{width:100%;max-width:900px;margin:0 auto;position:relative;z-index:1;}
        .auth-back{
            color:rgba(255,255,255,0.6);
            text-decoration:none;
            font-size:0.9rem;
            font-weight:500;
            transition:all .3s ease;
            display:inline-flex;
            align-items:center;
            gap:8px;
            margin-bottom:25px;
        }
        .auth-back:hover{color:#dc3545;transform:translateX(-5px);}
        .auth-logo{
            display:flex;
            align-items:center;
            gap:14px;
            margin-bottom:30px;
            justify-content:center;
        }
        .auth-logo i{
            font-size:2.8rem;
            color:#dc3545;
            background:rgba(220,38,38,0.12);
            padding:16px;
            border-radius:16px;
            box-shadow:0 0 40px rgba(220,38,38,0.1);
            animation:pulseGlow 3s infinite ease-in-out;
        }
        @keyframes pulseGlow{
            0%,100%{box-shadow:0 0 40px rgba(220,38,38,0.1);}
            50%{box-shadow:0 0 60px rgba(220,38,38,0.2);}
        }
        .auth-logo h3{
            color:#fff;
            font-weight:700;
            font-size:2rem;
            margin:0;
            background:linear-gradient(135deg,#fff 30%,#dc3545 100%);
            -webkit-background-clip:text;
            -webkit-text-fill-color:transparent;
            background-clip:text;
        }
        .auth-card{
            background:rgba(255,255,255,0.04);
            backdrop-filter:blur(30px);
            border:1px solid rgba(255,255,255,0.06);
            border-radius:28px;
            overflow:hidden;
            box-shadow:0 40px 100px rgba(0,0,0,0.5);
            margin-bottom:30px;
            transition:all .4s ease;
        }
        .auth-card:hover{transform:translateY(-5px);box-shadow:0 50px 120px rgba(0,0,0,0.6);}
        .auth-card .card-header{
            background:linear-gradient(135deg,rgba(220,38,38,0.15),rgba(220,38,38,0.03));
            border-bottom:1px solid rgba(255,255,255,0.05);
            padding:20px 30px;
        }
        .auth-card .card-header h4{
            margin:0;
            font-weight:700;
            color:#fff;
            font-size:1.4rem;
        }
        .auth-card .card-header h4 i{color:#dc3545;margin-right:10px;}
        .auth-card .card-body{padding:30px;}
        .auth-card .form-label{
            color:rgba(255,255,255,0.75);
            font-weight:500;
            font-size:0.85rem;
            margin-bottom:5px;
        }
        .auth-card .form-label .required{color:#dc3545;margin-left:2px;}
        .auth-card .form-control,.auth-card .form-select{
            background:rgba(255,255,255,0.05);
            border:1px solid rgba(255,255,255,0.07);
            border-radius:12px;
            color:#fff;
            padding:11px 16px;
            font-size:0.9rem;
            transition:all .3s ease;
        }
        .auth-card .form-control:focus,.auth-card .form-select:focus{
            background:rgba(255,255,255,0.08);
            border-color:#dc3545;
            box-shadow:0 0 0 4px rgba(220,38,38,0.1);
            color:#fff;
        }
        .auth-card .form-control::placeholder{color:rgba(255,255,255,0.25);}
        .auth-card .form-control.is-invalid{border-color:#dc3545;}
        .auth-card .form-select{
            color:rgba(255,255,255,0.8);
            appearance:none;
            background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='rgba(255,255,255,0.5)' stroke-width='1.5' fill='none'/%3E%3C/svg%3E");
            background-repeat:no-repeat;
            background-position:right 16px center;
            padding-right:40px;
            cursor:pointer;
        }
        .auth-card .form-select option{background:#1a1a2e;color:#fff;}
        .auth-card textarea.form-control{min-height:60px;resize:vertical;}
        .auth-card .input-group{
            background:rgba(255,255,255,0.05);
            border-radius:12px;
            overflow:hidden;
            border:1px solid rgba(255,255,255,0.07);
            transition:all .3s ease;
        }
        .auth-card .input-group:focus-within{
            border-color:#dc3545;
            box-shadow:0 0 0 4px rgba(220,38,38,0.1);
        }
        .auth-card .input-group .form-control{border:none;border-radius:0;background:transparent;}
        .auth-card .input-group .form-control:focus{box-shadow:none;background:transparent;}
        .password-toggle{
            cursor:pointer;
            padding:11px 16px;
            color:rgba(255,255,255,0.4);
            transition:all .3s ease;
            background:transparent;
            border:none;
            display:flex;
            align-items:center;
            justify-content:center;
        }
        .password-toggle:hover{color:rgba(255,255,255,0.8);}
        .btn-danger{
            background:linear-gradient(135deg,#dc3545,#c82333);
            border:none;
            border-radius:12px;
            padding:13px;
            font-weight:600;
            font-size:1rem;
            transition:all .3s ease;
            box-shadow:0 8px 30px rgba(220,38,38,0.25);
            position:relative;
            overflow:hidden;
        }
        .btn-danger::before{
            content:'';
            position:absolute;
            top:0;left:-100%;
            width:100%;height:100%;
            background:linear-gradient(90deg,transparent,rgba(255,255,255,0.1),transparent);
            transition:left .5s ease;
        }
        .btn-danger:hover::before{left:100%;}
        .btn-danger:hover{
            transform:translateY(-2px);
            box-shadow:0 15px 45px rgba(220,38,38,0.35);
        }
        .btn-danger i{margin-right:8px;}
        .auth-card hr{border-color:rgba(255,255,255,0.06);margin:20px 0;}
        .auth-card .login-link{
            color:rgba(255,255,255,0.5);
            font-size:0.9rem;
            text-align:center;
        }
        .auth-card .login-link a{
            color:#dc3545;
            text-decoration:none;
            font-weight:600;
            transition:all .3s ease;
            position:relative;
        }
        .auth-card .login-link a::after{
            content:'';
            position:absolute;
            bottom:-2px;left:0;
            width:0;height:2px;
            background:#dc3545;
            transition:width .3s ease;
        }
        .auth-card .login-link a:hover::after{width:100%;}
        .auth-card .login-link a:hover{color:#ff6b6b;}
        .auth-card .alert{
            border-radius:12px;
            border:none;
            padding:12px 18px;
            font-weight:500;
            margin-bottom:18px;
            font-size:0.9rem;
            animation:slideInAlert .5s ease;
        }
        @keyframes slideInAlert{
            from{opacity:0;transform:translateY(-20px);}
            to{opacity:1;transform:translateY(0);}
        }
        .auth-card .alert-danger{
            background:rgba(220,38,38,0.12);
            color:#ff6b6b;
            border-left:4px solid #dc3545;
        }
        .auth-card .alert-success{
            background:rgba(40,167,69,0.12);
            color:#6bff8a;
            border-left:4px solid #28a745;
        }
        .auth-card .alert-success a{color:#6bff8a;font-weight:600;text-decoration:underline;}
        .auth-card .alert-success a:hover{color:#fff;}
        .role-field{
            background:rgba(255,255,255,0.02);
            border-radius:12px;
            padding:14px 16px;
            border:1px solid rgba(255,255,255,0.04);
            margin-top:10px;
            transition:all .4s ease;
            display:none;
        }
        .role-field.show{
            display:block;
            animation:fadeSlideIn .4s ease forwards;
            border-color:rgba(220,38,38,0.15);
            background:rgba(220,38,38,0.03);
        }
        @keyframes fadeSlideIn{
            from{opacity:0;transform:translateY(-10px) scale(0.95);}
            to{opacity:1;transform:translateY(0) scale(1);}
        }
        .role-field label{color:rgba(255,255,255,0.6);font-size:0.85rem;font-weight:500;margin-bottom:4px;}
        .role-field small{color:rgba(255,255,255,0.25)!important;font-size:0.75rem;margin-top:4px;display:block;}
        .role-field .form-control{margin-top:4px;}

        /* Responsive */
        @media(max-width:992px){
            .auth-container{max-width:700px;}
            .auth-card .card-body{padding:25px;}
        }
        @media(max-width:768px){
            body{padding:20px 15px;}
            .auth-container{max-width:100%;}
            .auth-card .card-body{padding:20px;}
            .auth-card .card-header{padding:16px 20px;}
            .auth-card .card-header h4{font-size:1.2rem;}
            .auth-logo h3{font-size:1.5rem;}
            .auth-logo i{font-size:2.2rem;padding:14px;}
            .role-field{padding:12px 14px;}
            .auth-card .form-control,.auth-card .form-select{padding:10px 14px;font-size:0.85rem;}
            .btn-danger{padding:12px;font-size:0.9rem;}
            .bg-shapes .shape-1{width:300px;height:300px;}
            .bg-shapes .shape-2{width:250px;height:250px;}
            .bg-shapes .shape-3{width:200px;height:200px;}
        }
        @media(max-width:480px){
            body{padding:15px 10px;}
            .auth-card .card-body{padding:16px 14px;}
            .auth-card .card-header{padding:14px 16px;}
            .auth-card .card-header h4{font-size:1rem;}
            .auth-logo h3{font-size:1.2rem;}
            .auth-logo i{font-size:1.8rem;padding:12px;}
            .auth-logo{gap:10px;margin-bottom:18px;}
        }
        .btn-loading{opacity:0.7;pointer-events:none;}
        .btn-loading .spinner{display:inline-block;animation:spin .8s linear infinite;}
        @keyframes spin{to{transform:rotate(360deg);}}
        ::-webkit-scrollbar{width:6px;}
        ::-webkit-scrollbar-track{background:rgba(255,255,255,0.05);border-radius:4px;}
        ::-webkit-scrollbar-thumb{background:rgba(220,38,38,0.3);border-radius:4px;transition:all .3s ease;}
        ::-webkit-scrollbar-thumb:hover{background:rgba(220,38,38,0.5);}
        .field-error{font-size:0.8rem;margin-top:4px;color:#ff6b6b!important;}
        @keyframes shake{
            0%,100%{transform:translateX(0);}
            25%{transform:translateX(-8px);}
            75%{transform:translateX(8px);}
        }
        .is-invalid{animation:shake .5s ease;}
    </style>
</head>
<body>
    <!-- Animated Background Shapes -->
    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
        <div class="shape shape-5"></div>
    </div>

    <!-- Floating Particles -->
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="auth-container">
        <a href="<?php echo SITE_URL; ?>" class="auth-back" data-aos="fade-right">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>

        <div class="auth-logo" data-aos="fade-down">
            <i class="fas fa-hand-holding-heart"></i>
            <h3>Asmeera Lifeline</h3>
        </div>

        <div class="card auth-card" data-aos="fade-up" data-aos-delay="100">
            <div class="card-header">
                <h4><i class="fas fa-user-plus"></i> Create Account</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?> <a href="login.php">Login here</a></div>
                <?php endif; ?>

                <form method="POST" id="registerForm">
                    <div class="row g-3">
                        <!-- Row 1: Full Name + Email -->
                        <div class="col-md-6">
                            <label class="form-label">Full Name <span class="required">*</span></label>
                            <input type="text" name="full_name" class="form-control" placeholder="John Doe" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="required">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        </div>

                        <!-- Row 2: Password + Phone -->
                        <div class="col-md-6">
                            <label class="form-label">Password <span class="required">*</span></label>
                            <div class="input-group">
                                <input type="password" name="password" id="password" class="form-control" placeholder="Min 8 characters" required>
                                <button type="button" class="password-toggle" onclick="togglePassword()">
                                    <i class="fas fa-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone <span class="required">*</span></label>
                            <input type="text" name="phone" class="form-control" placeholder="+91 98765 43210" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
                        </div>

                        <!-- Row 3: Address (Full Width) -->
                        <div class="col-md-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2" placeholder="Your full address"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                        </div>

                        <!-- Row 4: Role Select -->
                        <div class="col-md-12">
                            <label class="form-label">Register as <span class="required">*</span></label>
                            <select name="role_id" id="role_id" class="form-select" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?php echo $r['id']; ?>" <?php echo ($r['role_name'] == $role) ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($r['role_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Row 5: Role Fields -->
                        <div class="col-md-12">
                            <div id="volunteer_fields" class="role-field">
                                <label class="form-label">Skills <span class="required">*</span></label>
                                <textarea name="skills" class="form-control" rows="2" placeholder="First Aid, Rescue, Medical, Driving"><?php echo isset($_POST['skills']) ? htmlspecialchars($_POST['skills']) : ''; ?></textarea>
                                <small><i class="fas fa-info-circle"></i> List your skills for emergency matching</small>
                            </div>

                            <div id="ngo_fields" class="role-field">
                                <div class="row g-2">
                                    <div class="col-md-12">
                                        <label class="form-label">Organization Name <span class="required">*</span></label>
                                        <input type="text" name="organization_name" class="form-control" placeholder="NGO Name" value="<?php echo isset($_POST['organization_name']) ? htmlspecialchars($_POST['organization_name']) : ''; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Registration Number</label>
                                        <input type="text" name="registration_number" class="form-control" placeholder="Reg. No." value="<?php echo isset($_POST['registration_number']) ? htmlspecialchars($_POST['registration_number']) : ''; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Contact Person</label>
                                        <input type="text" name="contact_person" class="form-control" placeholder="Primary Contact" value="<?php echo isset($_POST['contact_person']) ? htmlspecialchars($_POST['contact_person']) : ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-danger w-100 mt-3" id="submitBtn">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>
                </form>

                <hr>
                <p class="login-link">Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        AOS.init({duration:800,once:true,offset:30});

        function togglePassword(){
            const p=document.getElementById('password'),i=document.getElementById('toggleIcon');
            if(p.type==='password'){p.type='text';i.classList.remove('fa-eye');i.classList.add('fa-eye-slash');}
            else{p.type='password';i.classList.remove('fa-eye-slash');i.classList.add('fa-eye');}
        }

        document.getElementById('role_id').addEventListener('change',function(){
            const r=this.options[this.selectedIndex].text.toLowerCase();
            const v=document.getElementById('volunteer_fields'),n=document.getElementById('ngo_fields');
            v.classList.remove('show');n.classList.remove('show');
            if(r==='volunteer')setTimeout(()=>v.classList.add('show'),50);
            else if(r==='ngo')setTimeout(()=>n.classList.add('show'),50);
        });
        document.addEventListener('DOMContentLoaded',()=>document.getElementById('role_id').dispatchEvent(new Event('change')));

        document.getElementById('registerForm').addEventListener('submit',function(e){
            e.preventDefault();
            const f=this,b=document.getElementById('submitBtn'),req=f.querySelectorAll('[required]');
            let valid=true,first=null;
            document.querySelectorAll('.field-error').forEach(el=>el.remove());
            req.forEach(field=>{
                field.classList.remove('is-invalid');
                if(!field.value.trim()){
                    field.classList.add('is-invalid');valid=false;
                    if(!first)first=field;
                    const err=document.createElement('div');
                    err.className='field-error';
                    err.innerHTML='<i class="fas fa-exclamation-circle"></i> This field is required';
                    field.parentNode.insertBefore(err,field.nextSibling);
                }
            });
            const p=f.querySelector('input[name="password"]');
            if(p.value.length>0&&p.value.length<8){
                p.classList.add('is-invalid');valid=false;
                if(!first)first=p;
                const err=document.createElement('div');
                err.className='field-error';
                err.innerHTML='<i class="fas fa-exclamation-circle"></i> Password must be at least 8 characters';
                p.parentNode.insertBefore(err,p.nextSibling);
            }
            const email=f.querySelector('input[name="email"]');
            if(email.value&&!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)){
                email.classList.add('is-invalid');valid=false;
                if(!first)first=email;
                const err=document.createElement('div');
                err.className='field-error';
                err.innerHTML='<i class="fas fa-exclamation-circle"></i> Valid email required';
                email.parentNode.insertBefore(err,email.nextSibling);
            }
            if(!valid){if(first){first.focus();first.scrollIntoView({behavior:'smooth',block:'center'});}return;}
            b.classList.add('btn-loading');
            b.innerHTML='<i class="fas fa-spinner spinner"></i> Creating...';
            setTimeout(()=>f.submit(),300);
        });

        document.querySelectorAll('.alert').forEach(a=>setTimeout(()=>{
            a.style.transition='all .5s ease';a.style.opacity='0';a.style.transform='translateY(-10px)';
            setTimeout(()=>a.style.display='none',500);
        },5000));
    </script>
</body>
</html>