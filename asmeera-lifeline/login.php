<?php
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "SELECT u.*, r.role_name FROM users u 
              JOIN roles r ON u.role_id = r.id 
              WHERE u.email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role_name'];

            $update = "UPDATE users SET last_login = NOW() WHERE id = :id";
            $stmt = $db->prepare($update);
            $stmt->bindParam(':id', $user['id']);
            $stmt->execute();

            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User not found!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Asmeera Lifeline</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    <style>
        .auth-page {
            background: linear-gradient(135deg, #0a0e1a 0%, #1a1a2e 50%, #2d1b3d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 40px 0;
            position: relative;
            overflow: hidden;
        }

        .auth-page::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(220, 38, 38, 0.08) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .auth-page::after {
            content: '';
            position: absolute;
            bottom: -40%;
            left: -20%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(220, 38, 38, 0.05) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .auth-back {
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 30px;
        }

        .auth-back:hover {
            color: #dc3545;
            transform: translateX(-5px);
        }

        .auth-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
            justify-content: center;
        }

        .auth-logo i {
            font-size: 2.5rem;
            color: #dc3545;
            background: rgba(220, 38, 38, 0.15);
            padding: 15px;
            border-radius: 16px;
            box-shadow: 0 0 40px rgba(220, 38, 38, 0.15);
        }

        .auth-logo h3 {
            color: #fff;
            font-weight: 700;
            font-size: 1.8rem;
            margin: 0;
            background: linear-gradient(135deg, #fff 30%, #dc3545 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .auth-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0,0,0,0.4);
            transition: all 0.3s ease;
        }

        .auth-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 40px 100px rgba(0,0,0,0.5);
        }

        .auth-card .card-header {
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.2), rgba(220, 38, 38, 0.05));
            border-bottom: 1px solid rgba(255,255,255,0.06);
            padding: 25px 30px;
        }

        .auth-card .card-header h4 {
            margin: 0;
            font-weight: 700;
            color: #fff;
            font-size: 1.4rem;
        }

        .auth-card .card-header h4 i {
            color: #dc3545;
            margin-right: 10px;
        }

        .auth-card .card-body {
            padding: 30px;
        }

        .auth-card .form-label {
            color: rgba(255,255,255,0.7);
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 6px;
        }

        .auth-card .input-group {
            background: rgba(255,255,255,0.06);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.08);
            transition: all 0.3s ease;
        }

        .auth-card .input-group:focus-within {
            border-color: #dc3545;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.15);
        }

        .auth-card .input-group-text {
            background: transparent;
            border: none;
            color: rgba(255,255,255,0.4);
            padding: 12px 16px;
        }

        .auth-card .form-control {
            background: transparent;
            border: none;
            color: #fff;
            padding: 12px 16px 12px 0;
            font-size: 0.95rem;
        }

        .auth-card .form-control:focus {
            box-shadow: none;
            background: transparent;
            color: #fff;
        }

        .auth-card .form-control::placeholder {
            color: rgba(255,255,255,0.3);
        }

        .auth-card .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 8px 30px rgba(220, 38, 38, 0.3);
        }

        .auth-card .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(220, 38, 38, 0.4);
        }

        .auth-card .btn-danger i {
            margin-right: 8px;
        }

        .auth-card hr {
            border-color: rgba(255,255,255,0.06);
            margin: 20px 0;
        }

        .auth-card p {
            color: rgba(255,255,255,0.5);
            font-size: 0.95rem;
        }

        .auth-card p a {
            color: #dc3545;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .auth-card p a:hover {
            color: #ff6b6b;
        }

        .auth-card .alert {
            border-radius: 12px;
            border: none;
            padding: 14px 18px;
            font-weight: 500;
        }

        .auth-card .alert-danger {
            background: rgba(220, 38, 38, 0.15);
            color: #ff6b6b;
            border-left: 4px solid #dc3545;
        }

        /* Decorative shapes */
        .auth-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
            overflow: hidden;
        }

        .auth-shapes .shape {
            position: absolute;
            border-radius: 50%;
            opacity: 0.04;
        }

        .auth-shapes .shape-1 {
            width: 300px;
            height: 300px;
            background: #dc3545;
            top: -100px;
            right: -100px;
        }

        .auth-shapes .shape-2 {
            width: 200px;
            height: 200px;
            background: #ff6b6b;
            bottom: -50px;
            left: -50px;
        }

        .auth-shapes .shape-3 {
            width: 150px;
            height: 150px;
            background: #dc3545;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .password-toggle {
            cursor: pointer;
            padding: 12px 16px;
            color: rgba(255,255,255,0.4);
            transition: all 0.3s ease;
            background: transparent;
            border: none;
        }

        .password-toggle:hover {
            color: rgba(255,255,255,0.7);
        }

        @media (max-width: 768px) {
            .auth-page {
                padding: 20px 15px;
            }
            .auth-card .card-body {
                padding: 20px;
            }
            .auth-logo h3 {
                font-size: 1.4rem;
            }
            .auth-logo i {
                font-size: 2rem;
                padding: 12px;
            }
        }
    </style>
</head>
<body class="auth-page">
    <div class="auth-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>

    <div class="container position-relative">
        <a href="<?php echo SITE_URL; ?>" class="auth-back" data-aos="fade-right">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="auth-logo" data-aos="fade-down">
                    <i class="fas fa-hand-holding-heart"></i>
                    <h3>Asmeera Lifeline</h3>
                </div>
                <div class="card auth-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="card-header text-white text-center">
                        <h4><i class="fas fa-sign-in-alt"></i> Welcome Back</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger" data-aos="fade-up">
                                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST" id="loginForm">
                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter password" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword()">
                                        <i class="fas fa-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-danger w-100 py-2">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </button>
                        </form>
                        <hr>
                        <p class="text-center mb-0">
                            Don't have an account? <a href="register.php">Register here</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true,
            offset: 50
        });

        function togglePassword() {
            const password = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = this.querySelector('input[name="email"]');
            const password = this.querySelector('input[name="password"]');
            
            if (!email.value.trim() || !password.value.trim()) {
                e.preventDefault();
                // Show error
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger';
                alert.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please fill in all fields';
                this.prepend(alert);
                setTimeout(() => alert.remove(), 3000);
            }
        });
    </script>
</body>
</html>