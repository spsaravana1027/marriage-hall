<?php
require_once 'includes/auth_functions.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if (isset($_GET['registered'])) {
    $success = 'Account created successfully! Please login.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = trim($_POST['login_id'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login_id) || empty($password)) {
        $error = 'Please fill in all fields.';
    } elseif (loginUser($pdo, $login_id, $password)) {
        $login_success = true;
        $redirect_url = isAdmin() ? 'admin/dashboard.php' : 'index.php';
    } else {
        $error = 'Invalid email/phone or password. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?php echo $brand_name; ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: var(--bg); min-height: 100vh; display: flex; }
        .auth-left {
            width: 45%;
            background: var(--gradient-hero);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 3rem;
        }
        .auth-right {
            width: 55%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
        }
        @media(max-width:1150px) {
            .auth-left { display: none; }
            .auth-right { width: 100%; }
        }
        @media(max-width:480px) {
            .auth-right { padding: 1.5rem 1rem; }
            .auth-form-wrap { max-width: 100%; }
            h1 { font-size: 1.5rem !important; }
        }
        .auth-form-wrap { width: 100%; max-width: 420px; }
        .auth-brand { font-family: 'Poppins',sans-serif; font-weight: 800; font-size: 1.8rem; color: white; margin-bottom: 2rem; }
        .auth-brand i { color: var(--secondary); }
        .auth-quote { color: rgba(255,255,255,0.7); font-size: 1rem; line-height: 1.7; margin-bottom: 2.5rem; }
        .auth-feature { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; }
        .auth-feature-icon { width: 40px; height: 40px; border-radius: 10px; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; color: var(--secondary); flex-shrink: 0; }
        .auth-feature p { color: rgba(255,255,255,0.8); font-size: 0.875rem; }
        .auth-orb { position: absolute; border-radius: 50%; filter: blur(60px); }
        .divider { display: flex; align-items: center; gap: 1rem; margin: 1.5rem 0; color: var(--gray-light); font-size: 0.8rem; }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: var(--border); }
    </style>
</head>
<body>
    <!-- LEFT PANEL -->
    <div class="auth-left reveal">
        <div class="auth-orb" style="width:350px;height:350px;background:var(--primary);top:-100px;left:-100px;opacity:0.2;"></div>
        <div class="auth-orb" style="width:250px;height:250px;background:var(--secondary);bottom:-80px;right:-60px;opacity:0.15;"></div>

        <div style="position:relative;z-index:2;width:100%;max-width:380px;">
            <div class="auth-brand">
                <?php if (!empty($brand_logo)): ?>
                    <img src="assets/images/<?php echo $brand_logo; ?>" style="width:40px; height:40px; border-radius:50%; object-fit:cover; margin-right:0.5rem; border:2px solid var(--secondary);">
                <?php else: ?>
                    <i class="fas fa-building-columns"></i>
                <?php endif; ?>
                <?php echo $brand_name; ?>
            </div>
            
            <div style="margin: 1.5rem 0; text-align: center;">
                <img src="assets/images/wedding_illust.svg" alt="Welcome" style="width: 100%; max-width: 250px; filter: drop-shadow(0 10px 20px rgba(0,0,0,0.1));">
            </div>

            <p class="auth-quote">"Book your dream venue in minutes   Real availability, transparent pricing, instant confirmation."</p>

            <div class="auth-feature">
                <div class="auth-feature-icon"><i class="fas fa-calendar-check"></i></div>
                <p>Real-time availability checking for all halls</p>
            </div>
            <div class="auth-feature">
                <div class="auth-feature-icon"><i class="fas fa-shield-alt"></i></div>
                <p>100% verified venues with secure bookings</p>
            </div>
            <div class="auth-feature">
                <div class="auth-feature-icon"><i class="fas fa-headset"></i></div>
                <p>24/7 AI chatbot support for instant help</p>
            </div>
        </div>
    </div>

    <!-- RIGHT FORM PANEL -->
    <div class="auth-right reveal delay-100">
        <div class="auth-form-wrap">
            <a href="index.php" style="display:inline-flex;align-items:center;gap:0.5rem;color:var(--gray);font-size:0.85rem;margin-bottom:2rem;transition:var(--transition);" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--gray)'">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>

            <h1 style="font-size:2rem;margin-bottom:0.5rem;">Welcome Back!</h1>
            <p style="color:var(--gray);margin-bottom:2rem;">Login to manage your hall bookings.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Email or Phone Number</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-user"></i>
                        <input type="text" name="login_id" class="form-control" placeholder="Email Address or Phone Number" required value="<?php echo htmlspecialchars($_POST['login_id'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                        <label style="margin-bottom:0;">Password</label>
                        <a href="forgot_password.php" style="font-size:0.85rem; color:var(--primary); font-weight:600; text-decoration:none;">Forgot Password?</a>
                    </div>
                    <div class="input-icon-wrap" style="position:relative;">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                        <i class="fas fa-eye" id="togglePwd" onclick="togglePwd()" style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--gray-light);left:auto;"></i>
                    </div>
                </div>


                <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;margin-top:0.5rem;">
                    <i class="fas fa-sign-in-alt"></i> Login to Account
                </button>
            </form>



            <p style="text-align:center;margin-top:1.5rem;color:var(--gray);font-size:0.875rem;">
                Don't have an account? <a href="register.php" style="color:var(--primary);font-weight:600;">Register here -></a>
            </p>
        </div>
    </div>

    <script src="assets/js/validation.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Reveal trigger
        window.addEventListener('load', () => {
            document.querySelectorAll('.reveal').forEach(el => el.classList.add('active'));
        });

        function togglePwd() {
            const p = document.getElementById('password');
            const i = document.getElementById('togglePwd');
            if (p.type === 'password') { p.type = 'text'; i.classList.replace('fa-eye','fa-eye-slash'); }
            else { p.type = 'password'; i.classList.replace('fa-eye-slash','fa-eye'); }
        }

        <?php if (isset($login_success) && $login_success): ?>
        Swal.fire({
            icon: 'success',
            title: 'Login Successful',
            text: 'Welcome back!',
            timer: 1500,
            showConfirmButton: false,
            timerProgressBar: true,
            backdrop: 'rgba(0,0,0,0.85)'
        }).then(() => {
            window.location.href = '<?php echo $redirect_url; ?>';
        });
        <?php endif; ?>
    </script>

</body>
</html>


