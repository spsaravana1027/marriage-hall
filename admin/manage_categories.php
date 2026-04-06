<?php
require_once '../includes/auth_functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: adminlogin.php');
    exit();
}

$msg = '';
$error = '';
$action = $_GET['action'] ?? '';

// ===== HANDLE ACTIONS =====

// DELETE CATEGORY
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    try {
        // Check if any rooms are using this category
        $in_use = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE category_id = ?");
        $in_use->execute([$del_id]);
        if ($in_use->fetchColumn() > 0) {
            $error = 'Cannot delete this category - it is assigned to existing rooms.';
        } else {
            $pdo->prepare("DELETE FROM room_categories WHERE id = ?")->execute([$del_id]);
            $msg = 'Category deleted successfully.';
        }
    } catch (Exception $e) { $error = 'Error deleting category.'; }
}

// ADD or EDIT CATEGORY
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    $edit_id     = (int)($_POST['category_id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $icon        = trim($_POST['icon'] ?? 'fas fa-bed');

    if (empty($name)) {
        $error = 'Category name is required.';
    } else {
        try {
            if ($edit_id > 0) {
                $pdo->prepare("UPDATE room_categories SET name=?, description=?, icon=? WHERE id=?")
                    ->execute([$name, $description, $icon, $edit_id]);
                $msg = 'Category updated successfully!';
            } else {
                $pdo->prepare("INSERT INTO room_categories (name, description, icon) VALUES (?,?,?)")
                    ->execute([$name, $description, $icon]);
                $msg = 'Category added successfully!';
            }
            $action = '';
        } catch (Exception $e) { $error = 'Database error: ' . $e->getMessage(); }
    }
}

// Fetch category for edit
$edit_cat = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $edit_cat = $pdo->prepare("SELECT * FROM room_categories WHERE id = ?");
    $edit_cat->execute([(int)$_GET['id']]);
    $edit_cat = $edit_cat->fetch();
}

// Fetch all categories
$categories = [];
try {
    $categories = $pdo->query("
        SELECT rc.*, 
               (SELECT COUNT(*) FROM rooms WHERE category_id = rc.id) AS room_count
        FROM room_categories rc 
        ORDER BY rc.name ASC
    ")->fetchAll();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories | Sri Lakshmi Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: var(--bg); }
        .cat-card { display: grid; grid-template-columns: 60px 1fr auto; gap: 1rem; align-items: center; padding: 1.25rem 1.5rem; border-bottom: 1px solid #f8fafc; }
        .cat-card:last-child { border-bottom: none; }
        .cat-icon-circle { width: 48px; height: 48px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include '_sidebar.php'; ?>
    <div class="admin-main">
        <div class="admin-topbar">
            <div style="display:flex; align-items:center; gap:0.75rem;">
                <h2 style="font-weight:700; font-size:1.1rem; margin:0; color:var(--dark);">Room Categories</h2>
                <span style="font-size:0.78rem; color:var(--gray); margin-top:0.2rem;"><?php echo count($categories); ?> categories active</span>
            </div>
            <a href="?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add New Category</a>
        </div>

        <div class="admin-content">
            <?php if ($msg): ?>
                <div class="alert alert-success animate-fade-in"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger animate-fade-in"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($action === 'add' || $action === 'edit'): ?>
            <div class="admin-table-card">
                <div class="admin-table-header">
                    <h4 style="margin:0;"><?php echo $action === 'edit' ? '📝 Edit Category' : '➕ Add New Category'; ?></h4>
                    <a href="manage_categories.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
                </div>
                <div style="padding:1.25rem;">
                    <form method="POST">
                        <?php if ($edit_cat): ?>
                            <input type="hidden" name="category_id" value="<?php echo $edit_cat['id']; ?>">
                        <?php endif; ?>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.25rem;">
                            <div class="form-group">
                                <label>Category Name <span style="color:var(--danger)">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="e.g., VIP Suite" required value="<?php echo htmlspecialchars($edit_cat['name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Icon (FontAwesome Class)</label>
                                <div style="display:flex; gap:0.5rem;">
                                    <input type="text" name="icon" id="iconInput" class="form-control" placeholder="fas fa-crown" value="<?php echo htmlspecialchars($edit_cat['icon'] ?? 'fas fa-bed'); ?>">
                                    <div id="iconPreview" class="cat-icon-circle" style="flex-shrink:0;"><i class="<?php echo ($edit_cat['icon'] ?? 'fas fa-bed'); ?>"></i></div>
                                </div>
                                <small style="color:var(--gray-light);">Search icons on FontAwesome website</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Briefly describe this category..."><?php echo htmlspecialchars($edit_cat['description'] ?? ''); ?></textarea>
                        </div>

                        <div style="display:flex; gap:1rem;">
                            <button type="submit" name="save_category" class="btn btn-primary"><i class="fas fa-save"></i> Save Category</button>
                            <a href="manage_categories.php" class="btn btn-outline">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <?php else: ?>
            <div class="admin-table-card">
                <div class="admin-table-header">
                    <h4 style="margin:0;">Active Categories</h4>
                </div>
                
                <?php if (empty($categories)): ?>
                    <div style="text-align:center; padding:4rem; color:var(--gray-light);">
                        <p>No categories found. Add one to start organizing your rooms.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($categories as $c): ?>
                        <div class="cat-card">
                            <div class="cat-icon-circle"><i class="<?php echo htmlspecialchars($c['icon']); ?>"></i></div>
                            <div>
                                <div style="font-weight:700; font-size:1rem;"><?php echo htmlspecialchars($c['name']); ?></div>
                                <div style="font-size:0.8rem; color:var(--gray); margin-bottom:0.25rem;"><?php echo htmlspecialchars($c['description']); ?></div>
                                <div style="font-size:0.75rem; color:var(--primary); font-weight:600;">
                                    <i class="fas fa-bed"></i> <?php echo $c['room_count']; ?> rooms assigned
                                </div>
                            </div>
                            <div style="display:flex; gap:0.5rem;">
                                <a href="?action=edit&id=<?php echo $c['id']; ?>" class="btn btn-outline btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                <a href="?delete=<?php echo $c['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this category? This will fail if rooms are assigned to it.')"><i class="fas fa-trash"></i></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
    const iconInput = document.getElementById('iconInput');
    const iconPreview = document.getElementById('iconPreview').querySelector('i');
    
    if(iconInput) {
        iconInput.addEventListener('input', (e) => {
            iconPreview.className = e.target.value || 'fas fa-bed';
        });
    }
</script>
</body>
</html>
