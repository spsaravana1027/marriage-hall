<?php
require_once '../includes/auth_functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: adminlogin.php');
    exit();
}

// ===== AUTO-MIGRATION: Entire Schema Integrity =====
if (isset($pdo) && $pdo !== null) {
    // ===== QUICK-FIX MIGRATION: Ensure all rooms columns exist =====
if (isset($pdo) && $pdo) {
    try {
        $pdo->exec("ALTER TABLE rooms ADD COLUMN IF NOT EXISTS morning_slot_price DECIMAL(10, 2) DEFAULT 0 AFTER price_per_day");
    } catch(Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE rooms ADD COLUMN IF NOT EXISTS evening_slot_price DECIMAL(10, 2) DEFAULT 0 AFTER morning_slot_price");
    } catch(Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE rooms ADD COLUMN IF NOT EXISTS advance_amount DECIMAL(10, 2) DEFAULT 0 AFTER evening_slot_price");
    } catch(Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE rooms ADD COLUMN IF NOT EXISTS total_rooms INT DEFAULT 1 AFTER capacity");
    } catch(Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE rooms ADD COLUMN IF NOT EXISTS category_id INT AFTER id");
    } catch(Exception $e) {}

    // Add some sample rooms if the table is empty
    try {
        $cnt = (int)$pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
        if ($cnt == 0) {
            $cats_stmt = $pdo->query("SELECT id FROM room_categories LIMIT 2");
            $cat_ids = $cats_stmt->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($cat_ids)) {
                $c1 = $cat_ids[0];
                $c2 = $cat_ids[1] ?? $cat_ids[0];
                $pdo->exec("
                    INSERT INTO rooms (category_id, name, location, capacity, total_rooms, price_per_day, morning_slot_price, evening_slot_price, advance_amount, description, facilities, created_at) 
                    VALUES 
                    ($c1, 'Luxury VIP Suite 101', 'First Floor', 2, 5, 2500, 1200, 1500, 500, 'Premium room.', 'AC,TV,WiFi', NOW()),
                    ($c2, 'Deluxe Guest Room 201', 'Second Floor', 2, 10, 1500, 700, 900, 400, 'Comfortable room.', 'WiFi,Bath', NOW())
                ");
            }
        }
    } catch(Exception $e) {}
}
// =====================================================
}
// =====================================================

$msg = '';
$error = '';
$action = $_GET['action'] ?? '';

// ===== HANDLE ACTIONS =====

// DELETE ROOM
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    try {
        // Check no active bookings
        $active = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ? AND status IN ('pending','confirmed')");
        $active->execute([$del_id]);
        if ($active->fetchColumn() > 0) {
            $error = 'Cannot delete this room - it has active bookings.';
        } else {
            $pdo->prepare("DELETE FROM rooms WHERE id = ?")->execute([$del_id]);
            $msg = 'Room deleted successfully.';
        }
    } catch (Exception $e) { $error = 'Error deleting room.'; }
}

// ADD or EDIT ROOM
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_room'])) {
    $edit_id            = (int)($_POST['room_id'] ?? 0);
    $name               = trim($_POST['name'] ?? '');
    $location           = trim($_POST['location'] ?? '');
    $capacity           = (int)($_POST['capacity'] ?? 0);
    $price_per_day      = (float)($_POST['price_per_day'] ?? 0);
    $morning_slot_price = (float)($_POST['morning_slot_price'] ?? 0);
    $evening_slot_price = (float)($_POST['evening_slot_price'] ?? 0);
    $advance_amount     = (float)($_POST['advance_amount'] ?? 0);
    $description        = trim($_POST['description'] ?? '');
    $facilities         = trim($_POST['facilities'] ?? '');
    $category_id        = (int)($_POST['category_id'] ?? 0);
    $total_rooms        = (int)($_POST['total_rooms'] ?? 1);

    if (empty($name) || empty($location) || $capacity <= 0 || $price_per_day <= 0) {
        $error = 'Please fill in all required fields with valid values.';
    } else {
        // Handle image upload
        $main_image = $_POST['existing_image'] ?? '';
        if (!empty($_FILES['main_image']['name'])) {
            $ext = strtolower(pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
                $error = 'Only JPG, PNG, and WebP images allowed.';
            } else {
                $img_name = 'room_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                $upload_dir = '../assets/images/rooms/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                if (move_uploaded_file($_FILES['main_image']['tmp_name'], $upload_dir . $img_name)) {
                    $main_image = $img_name;
                }
            }
        }

        if (empty($error)) {
            try {
                if ($edit_id > 0) {
                    $pdo->prepare("
                        UPDATE rooms SET category_id=?, name=?, location=?, capacity=?, total_rooms=?, price_per_day=?, morning_slot_price=?, evening_slot_price=?, advance_amount=?, description=?, facilities=?, main_image=? WHERE id=?
                    ")->execute([$category_id, $name, $location, $capacity, $total_rooms, $price_per_day, $morning_slot_price, $evening_slot_price, $advance_amount, $description, $facilities, $main_image, $edit_id]);
                    $msg = 'Room updated successfully!';
                } else {
                    $pdo->prepare("
                        INSERT INTO rooms (category_id, name, location, capacity, total_rooms, price_per_day, morning_slot_price, evening_slot_price, advance_amount, description, facilities, main_image, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW())
                    ")->execute([$category_id, $name, $location, $capacity, $total_rooms, $price_per_day, $morning_slot_price, $evening_slot_price, $advance_amount, $description, $facilities, $main_image]);
                    $msg = 'Room added successfully!';
                }
                $action = ''; // Go back to list
            } catch (Exception $e) { $error = 'Database error: ' . $e->getMessage(); }
        }
    }
}

// Fetch room for edit
$edit_room = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $edit_room = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
    $edit_room->execute([(int)$_GET['id']]);
    $edit_room = $edit_room->fetch();
}

// Fetch all rooms for listing
$filter_cat = (int)($_GET['category'] ?? 0);
$rooms = [];
try {
    $sql = "SELECT r.*, rc.name as category_name, rc.icon as category_icon 
            FROM rooms r 
            LEFT JOIN room_categories rc ON r.category_id = rc.id 
            WHERE 1=1";
    $params = [];
    if($filter_cat > 0) {
        $sql .= " AND r.category_id = ?";
        $params[] = $filter_cat;
    }
    $sql .= " ORDER BY r.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $error = "Query failed: " . $e->getMessage(); }

// Fetch categories for dropdowns
$all_categories = [];
try {
    $all_categories = $pdo->query("SELECT * FROM room_categories ORDER BY name ASC")->fetchAll();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms | Sri Lakshmi Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: var(--bg); }
        .room-admin-card { display: grid; grid-template-columns: 90px 1fr auto; gap: 1.25rem; align-items: center; padding: 1.25rem 1.5rem; border-bottom: 1px solid #f8fafc; }
        .room-admin-card:last-child { border-bottom: none; }
        .room-thumb { width: 90px; height: 64px; border-radius: var(--radius); overflow: hidden; flex-shrink: 0; }
        .room-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .room-thumb .placeholder { width: 100%; height: 100%; background: var(--gradient-primary); display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.4); font-size: 1.5rem; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media(max-width:640px) { .form-grid { grid-template-columns: 1fr; } .room-admin-card { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include '_sidebar.php'; ?>
    <div class="admin-main">
        <div class="admin-topbar">
            <div style="display:flex; align-items:center; gap:0.75rem;">
                <h2 style="font-weight:700; font-size:1.1rem; margin:0; color:var(--dark);">Manage Rooms</h2>
                <span style="font-size:0.78rem; color:var(--gray); margin-top:0.2rem;"><?php echo count($rooms); ?> rooms registered</span>
            </div>
            <a href="?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add New Room</a>
        </div>

        <div class="admin-content">
            <?php if ($msg): ?>
                <div class="alert alert-success animate-fade-in"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger animate-fade-in"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($action === 'add' || $action === 'edit'): ?>
            <!-- ADD/EDIT FORM -->
            <div class="admin-table-card" style="overflow:visible;">
                <div class="admin-table-header">
                    <h4 style="margin:0;"><?php echo $action === 'edit' ? '📝 Edit Room' : '➕ Add New Room'; ?></h4>
                    <a href="manage_rooms.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
                </div>
                <div style="padding:1.25rem;">
                    <form method="POST" enctype="multipart/form-data">
                        <?php if ($edit_room): ?>
                            <input type="hidden" name="room_id" value="<?php echo $edit_room['id']; ?>">
                            <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($edit_room['main_image'] ?? ''); ?>">
                        <?php endif; ?>

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Room Category <span style="color:var(--danger)">*</span></label>
                                <select name="category_id" class="form-control" required>
                                    <option value="">- Select Category -</option>
                                    <?php foreach ($all_categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo (isset($edit_room['category_id']) && $edit_room['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Room Name / No. <span style="color:var(--danger)">*</span></label>
                                <input type="text" name="name" data-validate="name" class="form-control" placeholder="e.g., Deluxe Room 101" required value="<?php echo htmlspecialchars($edit_room['name'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Location / Floor <span style="color:var(--danger)">*</span></label>
                                <input type="text" name="location" class="form-control" placeholder="e.g., 1st Floor" required value="<?php echo htmlspecialchars($edit_room['location'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Day Rent (Rs.) <span style="color:var(--danger)">*</span></label>
                                <input type="number" name="price_per_day" data-validate="number" class="form-control" placeholder="e.g., 1500" required min="0" step="10" value="<?php echo htmlspecialchars($edit_room['price_per_day'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Capacity (guests) <span style="color:var(--danger)">*</span></label>
                                <input type="number" name="capacity" data-validate="number" class="form-control" placeholder="e.g., 2" required min="1" value="<?php echo htmlspecialchars($edit_room['capacity'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>No. of Rooms Available <span style="color:var(--danger)">*</span></label>
                                <input type="number" name="total_rooms" class="form-control" placeholder="e.g., 10" required min="1" value="<?php echo htmlspecialchars($edit_room['total_rooms'] ?? '1'); ?>">
                                <small style="color:var(--gray-light);">How many identical rooms of this type do you have?</small>
                            </div>
                        </div>

                        <div class="form-grid" style="grid-template-columns:1fr 1fr 1fr;">
                            <div class="form-group">
                                <label><i class="fas fa-sun" style="color:#f59e0b;"></i> Morning Slot Price (Rs.)</label>
                                <input type="number" name="morning_slot_price" class="form-control" placeholder="e.g., 800" min="0" step="10" value="<?php echo htmlspecialchars($edit_room['morning_slot_price'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-moon" style="color:#6366f1;"></i> Evening Slot Price (Rs.)</label>
                                <input type="number" name="evening_slot_price" class="form-control" placeholder="e.g., 1000" min="0" step="10" value="<?php echo htmlspecialchars($edit_room['evening_slot_price'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-receipt" style="color:#e91e63;"></i> Advance Amount (Rs.)</label>
                                <input type="number" name="advance_amount" class="form-control" placeholder="e.g., 500" min="0" step="10" value="<?php echo htmlspecialchars($edit_room['advance_amount'] ?? ''); ?>">
                            </div>
                        </div>


                        <div class="form-grid">
                            <div class="form-group">
                                <label>Main Room Image</label>
                                <div class="file-upload-wrapper">
                                    <input type="file" name="main_image" class="file-upload-input" accept="image/*" onchange="handleFileSelect(this)">
                                    <div class="file-upload-design">
                                        <i class="fas fa-bed"></i>
                                        <span class="upload-text">Choose Room Main Photo</span>
                                        <span class="upload-subtext">JPG, PNG, or WebP</span>
                                    </div>
                                </div>
                                <?php if (!empty($edit_room['main_image'])): ?>
                                    <small style="color:var(--success); margin-top:0.5rem; display:block;"><i class="fas fa-image"></i> Current: <?php echo htmlspecialchars($edit_room['main_image']); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Services / Amenities</label>
                            <input type="text" name="facilities" class="form-control" placeholder="e.g., AC, WiFi, Attached Bath, TV, Room Service" value="<?php echo htmlspecialchars($edit_room['facilities'] ?? ''); ?>">
                            <small style="color:var(--gray-light);font-size:0.75rem;">Separate amenities with commas</small>
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="Describe the room, its features, and comfort level..."><?php echo htmlspecialchars($edit_room['description'] ?? ''); ?></textarea>
                        </div>

                        <div style="display:flex;gap:1rem;flex-wrap:wrap;">
                            <button type="submit" name="save_room" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo $action === 'edit' ? 'Update Room' : 'Add Room'; ?>
                            </button>
                            <a href="manage_rooms.php" class="btn btn-outline">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <?php else: ?>
            <!-- ROOM LIST -->
            <div class="admin-table-card">
                <div class="admin-table-header" style="flex-direction:column; align-items:flex-start; gap:1rem;">
                    <div>
                        <h4 style="margin:0;">All Rooms</h4>
                        <p style="margin:0;font-size:0.78rem;color:var(--gray);"><?php echo count($rooms); ?> rooms listed</p>
                        <?php if (isset($error) && $error): ?>
                           <p style="color:red; font-size:0.7rem;"><?php echo $error; ?></p>
                        <?php endif; ?>
                        <!-- DEBUG: <?php echo "PDO Status: " . (isset($pdo) ? 'OK' : 'FAIL'); ?> , ROOMS: <?php echo count($rooms); ?> -->
                    </div>
                    <!-- Category Filter Tabs -->
                    <div style="display:flex; gap:0.4rem; flex-wrap:wrap; width:100%;">
                        <a href="manage_rooms.php" style="padding:0.35rem 0.8rem; border-radius:2rem; font-size:0.75rem; font-weight:600; border:1px solid var(--border); color:var(--gray); text-decoration:none; <?php echo $filter_cat == 0 ? 'background:var(--primary); color:white; border-color:var(--primary);' : ''; ?>">All Categories</a>
                        <?php foreach ($all_categories as $cat): ?>
                            <a href="manage_rooms.php?category=<?php echo $cat['id']; ?>" style="padding:0.35rem 0.8rem; border-radius:2rem; font-size:0.75rem; font-weight:600; border:1px solid var(--border); color:var(--gray); text-decoration:none; <?php echo $filter_cat == $cat['id'] ? 'background:var(--primary); color:white; border-color:var(--primary);' : ''; ?>">
                                <i class="<?php echo $cat['icon']; ?>" style="margin-right:0.2rem;"></i> <?php echo htmlspecialchars($cat['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if (empty($rooms)): ?>
                    <div style="text-align:center;padding:4rem;color:var(--gray-light);">
                        <i class="fas fa-bed" style="font-size:3rem;margin-bottom:1rem;"></i>
                        <p>No rooms added yet. <a href="?action=add" style="color:var(--primary);">Add your first room -></a></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($rooms as $r): ?>
                        <div class="room-admin-card">
                            <div class="room-thumb">
                                <?php if ($r['main_image']): ?>
                                    <img src="../assets/images/rooms/<?php echo htmlspecialchars($r['main_image']); ?>" alt="">
                                <?php else: ?>
                                    <div class="placeholder"><i class="fas fa-bed"></i></div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.2rem;">
                                    <span style="font-weight:700;font-size:0.95rem;"><?php echo htmlspecialchars($r['name']); ?></span>
                                    <?php if ($r['category_name']): ?>
                                        <span style="background:var(--primary-light); color:var(--primary); font-size:0.65rem; padding:0.1rem 0.5rem; border-radius:10px; font-weight:700;"><i class="<?php echo $r['category_icon']; ?>" style="margin-right:0.2rem;"></i> <?php echo strtoupper($r['category_name']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div style="display:flex;gap:1.25rem;font-size:0.78rem;color:var(--gray);flex-wrap:wrap;">
                                    <span><i class="fas fa-map-marker-alt" style="color:var(--primary);"></i> <?php echo htmlspecialchars($r['location']); ?></span>
                                    <span><i class="fas fa-users" style="color:var(--primary);"></i> Capacity: <?php echo number_format($r['capacity']); ?></span>
                                    <span><i class="fas fa-th-large" style="color:var(--primary);"></i> Total Rooms: <?php echo number_format($r['total_rooms']); ?></span>
                                    <span style="white-space:nowrap;"><i class="fas fa-rupee-sign" style="color:var(--primary);"></i> Rs. <?php echo number_format($r['price_per_day']); ?>/day</span>
                                    <span><i class="fas fa-calendar-check" style="color:var(--success);"></i> <?php echo $r['confirmed_count'] ?? 0; ?> Booked</span>
                                </div>
                                <?php if ($r['facilities']): ?>
                                    <div style="display:flex;flex-wrap:wrap;gap:0.3rem;margin-top:0.5rem;">
                                        <?php foreach(array_slice(explode(',', $r['facilities']), 0, 4) as $f): ?>
                                            <span style="background:#f1f5f9;color:var(--gray);font-size:0.7rem;padding:0.15rem 0.5rem;border-radius:20px;"><?php echo trim(htmlspecialchars($f)); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div style="display:flex;gap:0.5rem;flex-shrink:0;">
                                <a href="?action=edit&id=<?php echo $r['id']; ?>" class="btn btn-outline btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                <a href="?delete=<?php echo $r['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this room? This cannot be undone.')"><i class="fas fa-trash"></i></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
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
