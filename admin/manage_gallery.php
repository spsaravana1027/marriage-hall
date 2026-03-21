<?php
require_once '../includes/auth_functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: adminlogin.php');
    exit();
}

$msg = '';
$error = '';

// Handle Image Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_image'])) {
    if (isset($_FILES['gallery_image']) && $_FILES['gallery_image']['error'] === 0) {
        $upload_dir = '../assets/images/gallery/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $category = $_POST['category'] ?? 'mahal';
        $title = trim($_POST['image_title']);
        $desc_en = trim($_POST['description_en']);

        $file_ext = pathinfo($_FILES['gallery_image']['name'], PATHINFO_EXTENSION);
        $new_filename = 'gallery_' . time() . '_' . rand(100, 999) . '.' . $file_ext;
        $upload_path = $upload_dir . $new_filename;

        if (move_uploaded_file($_FILES['gallery_image']['tmp_name'], $upload_path)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO gallery (category, image_path, title, description_en) VALUES (?, ?, ?, ?)");
                $stmt->execute([$category, $new_filename, $title, $desc_en]);
                $msg = 'Image uploaded to gallery successfully!';
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

// Handle Image Deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("SELECT image_path FROM gallery WHERE id = ?");
        $stmt->execute([$id]);
        $image = $stmt->fetchColumn();

        if ($image) {
            $file_path = '../assets/images/gallery/' . $image;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            $delete_stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
            $delete_stmt->execute([$id]);
            $msg = 'Image deleted from gallery.';
        }
    } catch (Exception $e) {
        $error = 'Deletion failed: ' . $e->getMessage();
    }
}

// Fetch all gallery images
try {
    $stmt = $pdo->query("SELECT id, category, image_path, title, title_ta, created_at FROM gallery ORDER BY created_at DESC");
    $gallery_images = $stmt->fetchAll();
} catch (Exception $e) {
    $gallery_images = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Gallery | Sri Lakshmi Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        .gallery-item {
            background: white;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            transition: var(--transition);
            position: relative;
        }
        .gallery-item:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); }
        .gallery-item img { width: 100%; height: 150px; object-fit: cover; }
        .gallery-info { padding: 0.75rem; }
        .gallery-info h5 { margin: 0; font-size: 0.85rem; color: var(--dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .gallery-delete {
            position: absolute; top: 0.5rem; right: 0.5rem;
            width: 30px; height: 30px; border-radius: 50%;
            background: rgba(239, 68, 68, 0.9); color: white;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.8rem; border: none; cursor: pointer;
            transition: var(--transition);
        }
        .gallery-delete:hover { background: var(--danger); transform: scale(1.1); }

        /* Premium Category Filter Styles */
        .category-filter-container {
            background: white;
            border-radius: var(--radius-lg);
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }
        .category-filter-label {
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }
        .category-filter-scroll {
            display: flex;
            gap: 0.75rem;
            overflow-x: auto;
            flex-wrap: nowrap;
            padding-bottom: 5px; /* For scrollbar breathing room */
            scrollbar-width: none; /* Hide scrollbar for clean look */
        }
        .category-filter-scroll::-webkit-scrollbar { display: none; }
        
        .filter-btn {
            padding: 0.6rem 1.25rem;
            background: #f1f5f9;
            color: var(--dark-2);
            border: 1px solid var(--border);
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            white-space: nowrap;
        }
        .filter-btn:hover { background: var(--primary-light); color: var(--primary); border-color: var(--primary); }
        .filter-btn.active { background: var(--primary); color: white; border-color: var(--primary); box-shadow: 0 4px 12px rgba(233, 30, 99, 0.2); }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include '_sidebar.php'; ?>
    <div class="admin-main">
        <div class="admin-topbar">
            <div>
                <div style="font-weight:700;font-size:1rem;">Gallery Management</div>
                <div style="font-size:0.78rem;color:var(--gray);">Add or remove photos from your website gallery</div>
            </div>
        </div>

        <div class="admin-content">
            <?php if ($msg): ?>
                <div class="alert alert-success animate-fade-in"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger animate-fade-in"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <div class="admin-table-card" style="padding:1.25rem;">
                <h4 style="margin-bottom:1.5rem;"><i class="fas fa-plus-circle"></i> Add New Gallery Image</h4>
                <form method="POST" enctype="multipart/form-data">
                    <div style="display:grid; grid-template-columns: 1.1fr 0.9fr; gap:1.5rem;">
                        <div style="display:flex; flex-direction:column; gap:1.25rem;">
                            <div class="form-group">
                                <label>Category</label>
                                <select name="category" class="form-control" required>
                                    <option value="mahal">Mahal Gallery</option>
                                    <option value="room">Rooms Gallery</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Title (English)</label>
                                <input type="text" name="image_title" class="form-control" placeholder="e.g. Deluxe AC Room">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Select Image</label>
                            <div class="file-upload-wrapper" id="galleryUpload">
                                <input type="file" name="gallery_image" class="file-upload-input" accept="image/*" required onchange="handleFileSelect(this)">
                                <div class="file-upload-design" style="padding: 1.85rem 1.5rem;">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span class="upload-text">Choose Venue Photo</span>
                                    <span class="upload-subtext">Images only (JPG, PNG)</span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Description</label>
                            <textarea name="description_en" class="form-control" rows="2" placeholder="e.g. Comfort with elegance"></textarea>
                        </div>
                    </div>
                    <div style="text-align:right; margin-top:1.5rem;">
                        <button type="submit" name="upload_image" class="btn btn-primary"><i class="fas fa-upload"></i> Upload to Gallery</button>
                    </div>
                </form>
            </div>

            <!-- Category Filter Bar (Horizontal Scroll) -->
            <div class="category-filter-container">
                <div class="category-filter-label"><i class="fas fa-filter"></i> Filter by:</div>
                <div class="category-filter-scroll">
                    <button class="filter-btn active" onclick="filterGallery('all', this)">All Collections</button>
                    <button class="filter-btn" onclick="filterGallery('mahal', this)">Mahal Gallery</button>
                    <button class="filter-btn" onclick="filterGallery('room', this)">Rooms Gallery</button>
                    <!-- Add more if needed in future -->
                </div>
            </div>

            <div class="gallery-grid" id="galleryGrid">
                <?php if (empty($gallery_images)): ?>
                    <div style="grid-column: 1/-1; text-align:center; padding:4rem; color:var(--gray-light);">
                        <i class="fas fa-images" style="font-size:3rem; margin-bottom:1rem; opacity:0.3;"></i>
                        <p>No images in gallery yet. Start by uploading one!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($gallery_images as $img): ?>
                        <div class="gallery-item animate-fade-in" data-category="<?php echo htmlspecialchars($img['category'] ?? 'mahal'); ?>">
                            <a href="?delete=<?php echo $img['id']; ?>" class="gallery-delete" title="Delete Image" onclick="return confirm('Remove this image from gallery?')">
                                <i class="fas fa-trash"></i>
                            </a>
                            <div style="position:absolute; top:0.5rem; left:0.5rem; background:var(--primary); color:white; padding:0.25rem 0.6rem; border-radius:var(--radius-sm); font-size:0.65rem; font-weight:700; text-transform:uppercase;">
                                <?php echo htmlspecialchars($img['category'] ?? 'mahal'); ?>
                            </div>
                            <img src="../assets/images/gallery/<?php echo htmlspecialchars($img['image_path']); ?>" alt="Gallery Image">
                            <div class="gallery-info">
                                <h5><?php echo htmlspecialchars($img['title'] ?: 'No Title'); ?></h5>
                                <small style="font-size:0.65rem; color:var(--gray-light);"><?php echo date('d M Y', strtotime($img['created_at'])); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
            }
        }

        // Premium Filter Logic
        function filterGallery(category, btn) {
            // Update Active Button
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Filter Grid Items
            const items = document.querySelectorAll('.gallery-item');
            
            items.forEach(item => {
                const itemCategory = item.getAttribute('data-category');
                
                if (category === 'all' || itemCategory === category) {
                    item.style.display = 'block';
                    // Optional: Re-trigger animation
                    item.style.animation = 'none';
                    item.offsetHeight; // trigger reflow
                    item.style.animation = null;
                } else {
                    item.style.display = 'none';
                }
            });

            // Handle empty state if needed
            const visibleItems = Array.from(items).filter(i => i.style.display !== 'none');
            // ... could show a "No images in this category" msg
        }
    </script>
</body>
</html>
