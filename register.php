<?php
require_once 'includes/auth_functions.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!preg_match('/^[6-9]\d{9}$/', $phone)) {
        $error = 'Please enter a valid 10-digit Indian phone number.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email already exists
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'This email is already registered. Please login instead.';
        } elseif (registerUser($pdo, $name, $email, $phone, $password)) {
            header('Location: login.php?registered=1');
            exit();
        } else {
            $error = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | <?php echo $brand_name; ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: var(--bg); min-height: 100vh; display: flex; }
        .auth-left {
            width: 40%;
            background: var(--gradient-hero);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
        }
        .auth-right {
            width: 60%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 2rem;
            overflow-y: auto;
        }
        .auth-form-wrap { width: 100%; max-width: 500px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media(max-width:1150px) {
            .auth-left { display: none; }
            .auth-right { width: 100%; }
        }
        @media(max-width:600px) {
            .form-row { grid-template-columns: 1fr; }
            .auth-right { padding: 1.5rem 1rem; }
            .auth-form-wrap { max-width: 100%; }
            h1 { font-size: 1.5rem !important; }
        }
        .strength-bar { height: 4px; border-radius: 2px; background: var(--border); margin-top: 0.4rem; overflow: hidden; }
        .strength-fill { height: 100%; border-radius: 2px; transition: width 0.3s, background 0.3s; }
    </style>
</head>
<body>
    <!-- LEFT -->
    <div class="auth-left reveal">
        <div style="position:absolute;width:300px;height:300px;background:var(--primary);opacity:0.15;border-radius:50%;filter:blur(60px);top:-80px;right:-80px;"></div>
        <div style="position:absolute;width:200px;height:200px;background:var(--secondary);opacity:0.15;border-radius:50%;filter:blur(60px);bottom:-60px;left:-40px;"></div>

        <div style="position:relative;z-index:2;text-align:center;width:100%;max-width:380px;">
            <div style="width:70px;height:70px;background:rgba(255,255,255,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;border:1px solid rgba(255,255,255,0.2);">
                <i class="fas fa-building-columns" style="font-size:1.8rem;color:var(--secondary);"></i>
            </div>
            
            <div style="margin-bottom: 1.5rem; text-align: center;">
                <img src="assets/images/wedding_illust.svg" alt="Join Us" style="width: 100%; max-width: 220px; filter: drop-shadow(0 10px 20px rgba(0,0,0,0.1));">
            </div>

            <h2 style="color:white;font-size:1.6rem;margin-bottom:0.75rem;">Join <?php echo $brand_name; ?></h2>
            <p style="color:rgba(255,255,255,0.7);font-size:0.85rem;line-height:1.6;margin-bottom:1.5rem;">Create your free account and start booking premium halls for your special events today.</p>

            <div style="display:flex;flex-direction:column;gap:1rem;text-align:left;">
                <?php foreach ([
                    ['fa-check-circle','#10b981','Free registration, no hidden fees'],
                    ['fa-calendar-check','var(--secondary)', 'Instant booking confirmation'],
                    ['fa-shield-alt','#f59e0b', 'Secure and protected bookings'],
                ] as [$ic,$col,$txt]): ?>
                    <div style="display:flex;align-items:center;gap:0.75rem;background:rgba(255,255,255,0.07);padding:0.75rem 1rem;border-radius:10px;">
                        <i class="fas <?php echo $ic; ?>" style="color:<?php echo $col; ?>;"></i>
                        <span style="color:rgba(255,255,255,0.85);font-size:0.875rem;"><?php echo $txt; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- RIGHT -->
    <div class="auth-right reveal delay-100">
        <div class="auth-form-wrap">
            <a href="index.php" style="display:inline-flex;align-items:center;gap:0.5rem;color:var(--gray);font-size:0.85rem;margin-bottom:2rem;">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>

            <h1 style="font-size:1.9rem;margin-bottom:0.4rem;">Create Account</h1>
            <p style="color:var(--gray);margin-bottom:1.75rem;">Register to book your perfect hall.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" id="regForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name <span style="color:var(--danger)">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-user"></i>
                            <input type="text" name="name" data-validate="name" class="form-control" placeholder="Your full name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Phone Number <span style="color:var(--danger)">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-phone"></i>
                            <input type="tel" name="phone" data-validate="phone" class="form-control" placeholder="10-digit mobile" required maxlength="10" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Email Address <span style="color:var(--danger)">*</span></label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" class="form-control" placeholder="your@email.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Password <span style="color:var(--danger)">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" id="pwd" class="form-control" placeholder="Min. 6 characters" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password <span style="color:var(--danger)">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="confirm_password" id="cpwd" class="form-control" placeholder="Re-enter password" required>
                        </div>
                    </div>
                </div>


                <div style="display:flex;align-items:flex-start;gap:0.75rem;margin-bottom:1.5rem;padding:1rem;background:#f8fafc;border-radius:var(--radius);border:1px solid var(--border);">
                    <input type="checkbox" id="agreeTerms" required style="margin-top:3px;width:16px;height:16px;accent-color:var(--primary);flex-shrink:0;">
                    <label for="agreeTerms" style="font-size:0.85rem;color:var(--gray);cursor:pointer;">I agree to the <a href="#" style="color:var(--primary);">Terms of Service</a> and <a href="#" style="color:var(--primary);">Privacy Policy</a></label>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;">
                    <i class="fas fa-user-plus"></i> Create Free Account
                </button>
            </form>

            <p style="text-align:center;margin-top:1.5rem;color:var(--gray);font-size:0.875rem;">
                Already have an account? <a href="login.php" style="color:var(--primary);font-weight:600;">Login here â†’</a>
            </p>
        </div>
    </div>

    <script src="assets/js/validation.js"></script>
    <script>
        // Reveal trigger
        window.addEventListener('load', () => {
            document.querySelectorAll('.reveal').forEach(el => el.classList.add('active'));
        });
    </script>

</body>
</html>


