<?php
require_once '../includes/auth_functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: adminlogin.php');
    exit();
}

$msg = '';
$error = '';

// Fetch current banner
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'home_banner'");
    $stmt->execute();
    $current_banner = $stmt->fetchColumn() ?: 'hall image1.webp';
} catch (Exception $e) {
    $current_banner = 'hall image1.webp';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === 0) {
        $upload_dir = '../assets/images/banners/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION);
        $new_filename = 'home_banner_' . time() . '.' . $file_ext;
        $upload_path = $upload_dir . $new_filename;

        if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $upload_path)) {
            try {
                $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'home_banner'");
                $stmt->execute([$new_filename]);
                $current_banner = $new_filename;
                $msg = 'Banner image updated successfully!';
            } catch (Exception $e) {
                $error = 'Database update failed: ' . $e->getMessage();
            }
        } else {
            $error = 'Failed to move uploaded file.';
        }
    } else {
        $error = 'Please select a valid image file.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banner Settings | Sri Lakshmi Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="admin-layout">
    <?php include '_sidebar.php'; ?>
    <div class="admin-main">
        <div class="admin-topbar">
            <div>
                <div style="font-weight:700;font-size:1rem;">Site Settings</div>
                <div style="font-size:0.78rem;color:var(--gray);">Manage homepage appearance</div>
            </div>
        </div>

        <div class="admin-content">
            <?php if ($msg): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <div class="admin-table-card" style="padding:2rem;">
                <h4 style="margin-bottom:1.5rem;"><i class="fas fa-image"></i> Homepage Banner Image</h4>
                
                <div style="margin-bottom:2rem; background: #f8fafc; padding: 1rem; border-radius: var(--radius-lg); border: 1px solid var(--border);">
                    <small style="display:block; color:var(--gray); margin-bottom:0.5rem; text-transform:uppercase; font-size:0.7rem; letter-spacing:0.05em;">Current Banner Preview</small>
                    <div style="width:100%; height:250px; border-radius:var(--radius); overflow:hidden; background:#eee;">
                        <?php 
                        $image_path = (strpos($current_banner, 'hall image') !== false) ? '../' . $current_banner : '../assets/images/banners/' . $current_banner;
                        ?>
                        <img src="<?php echo htmlspecialchars($image_path); ?>" alt="Banner Preview" style="width:100%; height:100%; object-fit:cover;">
                    </div>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group" style="margin-bottom:1.5rem;">
                        <label>Upload New Banner Image</label>
                        <div class="file-upload-wrapper">
                            <input type="file" name="banner_image" class="file-upload-input" accept="image/*" required onchange="handleFileSelect(this)">
                            <div class="file-upload-design">
                                <i class="fas fa-image"></i>
                                <span class="upload-text">Choose Homepage Banner</span>
                                <span class="upload-subtext">High quality (1920x1080px)</span>
                            </div>
                        </div>
                        <small style="color:var(--gray); font-size:0.75rem; margin-top:0.5rem; display:block;">Recommended size: 1920x1080px. High-quality landscape images work best.</small>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Update Banner Image</button>
                </form>
            </div>
        </div>
    </div>
</div>
    <script>
        function handleFileSelect(input) {
            const wrapper = input.closest('.file-upload-wrapper');
            const placeholder = wrapper.querySelector('.upload-text');
            const subtext = wrapper.querySelector('.upload-subtext');
            const icon = wrapper.querySelector('i');

            if (input.files && input.files[0]) {
                const fileName = input.files[0].name;
                placeholder.textContent = fileName;
                placeholder.style.color = 'var(--secondary)';
                subtext.textContent = 'File selected successfully';
                icon.className = 'fas fa-check-circle';
                wrapper.classList.add('has-file');
            }
        }
    </script>
</body>
</html>
