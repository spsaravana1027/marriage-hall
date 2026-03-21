<?php
// Determine active page for sidebar highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-logo" style="display:flex; align-items:center; gap:0.8rem; padding:1.5rem 1.25rem;">
        <div style="font-weight:800; font-size:1.25rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:200px; line-height:1.2; color: white;">
            <?php echo $brand_name; ?>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-label">Main</div>
        <a href="dashboard.php" class="sidebar-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>

        <div class="sidebar-label">
            Management
        </div>
        <div class="sidebar-submenu">
            <div style="padding-left:0.5rem;">
                <a href="manage_halls.php" class="sidebar-link <?php echo $current_page === 'manage_halls.php' ? 'active' : ''; ?>" style="font-size:0.85rem;">
                    <i class="fas fa-angle-right" style="font-size:0.7rem; opacity:0.5; margin-right:0.4rem;"></i> <i class="fas fa-building" style="font-size:0.9rem;"></i> Manage Halls
                </a>
                <a href="manage_bookings.php" class="sidebar-link <?php echo $current_page === 'manage_bookings.php' ? 'active' : ''; ?>" style="font-size:0.85rem;">
                    <i class="fas fa-angle-right" style="font-size:0.7rem; opacity:0.5; margin-right:0.4rem;"></i> <i class="fas fa-calendar-check" style="font-size:0.9rem;"></i> Manage Bookings
                </a>
                <a href="manage_users.php" class="sidebar-link <?php echo $current_page === 'manage_users.php' ? 'active' : ''; ?>" style="font-size:0.85rem;">
                    <i class="fas fa-angle-right" style="font-size:0.7rem; opacity:0.5; margin-right:0.4rem;"></i> <i class="fas fa-users" style="font-size:0.9rem;"></i> Manage Users
                </a>
                <a href="contact_inquiries.php" class="sidebar-link <?php echo $current_page === 'contact_inquiries.php' ? 'active' : ''; ?>" style="font-size:0.85rem;">
                    <i class="fas fa-angle-right" style="font-size:0.7rem; opacity:0.5; margin-right:0.4rem;"></i> <i class="fas fa-envelope" style="font-size:0.9rem;"></i> Contact Inquiries
                </a>
            </div>
        </div>

        <div class="sidebar-label">
            Venue Models (Categories)
        </div>
        <div class="sidebar-submenu">
            <div class="sidebar-models-scroll" style="padding-left:0.5rem;">
                <?php 
                try {
                    $sidebar_halls = $pdo->query("SELECT id, name FROM halls ORDER BY name ASC")->fetchAll();
                    foreach ($sidebar_halls as $shall): 
                        $is_h_active = (isset($_GET['id']) && $_GET['id'] == $shall['id'] && $current_page === 'manage_halls.php');
                ?>
                    <a href="manage_halls.php?id=<?php echo $shall['id']; ?>" class="sidebar-link <?php echo $is_h_active ? 'active' : ''; ?>" style="padding: 0.5rem 1rem; font-size: 0.82rem; margin-bottom: 0;">
                        <i class="fas fa-angle-right" style="font-size:0.7rem; opacity:0.5; margin-right:0.4rem;"></i> <i class="fas fa-hotel" style="font-size:0.8rem; opacity: 0.7;"></i> <?php echo htmlspecialchars($shall['name']); ?>
                    </a>
                <?php 
                    endforeach; 
                } catch (Exception $e) {} 
                ?>
            </div>
        </div>

        <div class="sidebar-label">
            Site
        </div>
        <div class="sidebar-submenu">
            <div style="padding-left:0.5rem;">
                <a href="banner_settings.php" class="sidebar-link <?php echo $current_page === 'banner_settings.php' ? 'active' : ''; ?>" style="font-size:0.85rem;">
                    <i class="fas fa-angle-right" style="font-size:0.7rem; opacity:0.5; margin-right:0.4rem;"></i> <i class="fas fa-image" style="font-size:0.9rem;"></i> Banner Settings
                </a>
                <a href="manage_gallery.php" class="sidebar-link <?php echo $current_page === 'manage_gallery.php' ? 'active' : ''; ?>" style="font-size:0.85rem;">
                    <i class="fas fa-angle-right" style="font-size:0.7rem; opacity:0.5; margin-right:0.4rem;"></i> <i class="fas fa-images" style="font-size:0.9rem;"></i> Manage Gallery
                </a>
                <a href="site_settings.php" class="sidebar-link <?php echo $current_page === 'site_settings.php' ? 'active' : ''; ?>" style="font-size:0.85rem;">
                    <i class="fas fa-angle-right" style="font-size:0.7rem; opacity:0.5; margin-right:0.4rem;"></i> <i class="fas fa-cog" style="font-size:0.9rem;"></i> Site Settings
                </a>
                <a href="../index.php" class="sidebar-link" target="_blank" style="font-size:0.85rem;">
                    <i class="fas fa-angle-right" style="font-size:0.7rem; opacity:0.5; margin-right:0.4rem;"></i> <i class="fas fa-external-link-alt" style="font-size:0.9rem;"></i> View Website
                </a>
            </div>
        </div>
    </nav>

    <div style="padding: 1rem; text-align: center; opacity: 0.5;">
        <div style="font-size: 0.65rem; color: var(--gray-light); letter-spacing: 0.05em;"><?php echo $brand_name; ?> Admin</div>
    </div>

    <div class="sidebar-footer">
        <a href="../logout.php" class="sidebar-link" style="background:rgba(239,68,68,0.1);color:#fca5a5;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</aside>
<?php include '../includes/alerts.php'; ?>

