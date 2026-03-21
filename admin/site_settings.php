<?php
require_once '../includes/auth_functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: adminlogin.php');
    exit();
}

$msg = '';
$error = '';

// Fetch current settings
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('brand_name', 'brand_logo')");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $current_name = $settings['brand_name'] ?? 'Sri Lakshmi Residency & Mahal';
    $current_logo = $settings['brand_logo'] ?? '';
} catch (Exception $e) {
    $current_name = 'Sri Lakshmi Residency & Mahal';
    $current_logo = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Brand Name Update
    if (isset($_POST['brand_name'])) {
        $new_name = trim($_POST['brand_name']);
        if (!empty($new_name)) {
            try {
                $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'brand_name'");
                $stmt->execute([$new_name]);
                $current_name = $new_name;
                $msg = 'Brand name updated successfully!';
            } catch (Exception $e) {
                $error = 'Failed to update brand name: ' . $e->getMessage();
            }
        }
    }

    // Handle Logo Upload
    if (isset($_FILES['brand_logo']) && $_FILES['brand_logo']['error'] === 0) {
        $upload_dir = '../assets/images/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = pathinfo($_FILES['brand_logo']['name'], PATHINFO_EXTENSION);
        $new_logo_filename = 'logo_' . time() . '.' . $file_ext;
        $upload_path = $upload_dir . $new_logo_filename;

        if (move_uploaded_file($_FILES['brand_logo']['tmp_name'], $upload_path)) {
            try {
                $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'brand_logo'");
                $stmt->execute([$new_logo_filename]);
                $current_logo = $new_logo_filename;
                $msg = 'Brand logo updated successfully!';
            } catch (Exception $e) {
                $error = 'Database update failed: ' . $e->getMessage();
            }
        } else {
            $error = 'Failed to move uploaded logo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Branding Settings | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .logo-preview-round {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 3px solid var(--primary);
            overflow: hidden;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            box-shadow: var(--shadow-md);
        }
        .logo-preview-round img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .logo-preview-round i {
            font-size: 3rem;
            color: var(--gray-light);
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include '_sidebar.php'; ?>
    <div class="admin-main">
        <div class="admin-topbar">
            <div>
                <div style="font-weight:700;font-size:1rem;">Dynamic Branding</div>
                <div style="font-size:0.78rem;color:var(--gray);">Manage brand name and logo</div>
            </div>
        </div>

        <div class="admin-content">
            <?php if ($msg): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- Brand Name Section -->
                <div class="admin-table-card" style="padding:2rem;">
                    <h4 style="margin-bottom:1.5rem;"><i class="fas fa-font"></i> Brand Name</h4>
                    <form method="POST">
                        <div class="form-group" style="margin-bottom:1.5rem;">
                            <label>Website / Hall Name</label>
                            <input type="text" name="brand_name" data-validate="name" class="form-control" value="<?php echo htmlspecialchars($current_name); ?>" required>
                            <small style="color:var(--gray); font-size:0.75rem;">This name will appear across the entire website, page titles, and chatbot.</small>
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Brand Name</button>
                    </form>
                </div>

                <!-- Brand Logo Section -->
                <div class="admin-table-card" style="padding:2rem;">
                    <h4 style="margin-bottom:1.5rem;"><i class="fas fa-image"></i> Brand Logo</h4>
                    
                    <div style="display:flex; flex-direction:column; align-items:center; margin-bottom:2rem;">
                        <small style="margin-bottom:0.5rem; text-transform:uppercase; font-size:0.7rem; color:var(--gray); font-weight:700;">Current Round Logo Preview</small>
                        <div class="logo-preview-round">
                            <?php if ($current_logo): ?>
                                <img src="../assets/images/<?php echo htmlspecialchars($current_logo); ?>" alt="Brand Logo">
                            <?php else: ?>
                                <i class="fas fa-image"></i>
                            <?php endif; ?>
                        </div>
                    </div>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group" style="margin-bottom:1.5rem;">
                            <label>Upload New Logo</label>
                            <div class="file-upload-wrapper">
                                <input type="file" name="brand_logo" class="file-upload-input" accept="image/*" required onchange="handleFileSelect(this)">
                                <div class="file-upload-design">
                                    <i class="fas fa-image"></i>
                                    <span class="upload-text">Choose Brand Logo</span>
                                    <span class="upload-subtext">Square JPG or PNG</span>
                                </div>
                            </div>
                            <small style="color:var(--gray); font-size:0.75rem; margin-top:0.5rem; display:block;">Square images work best (e.g., 512x512px). It will be automatically cropped to a circle.</small>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Update Logo</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
    <script src="../assets/js/validation.js"></script>
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

