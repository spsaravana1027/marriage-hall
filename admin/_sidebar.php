<?php
// Determine active page for sidebar highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-logo" style="display:flex; align-items:center; gap:0.8rem; padding:1rem 1.25rem;">
        <div class="logo-wrap" style="width:55px; height:55px; border-radius:50%; overflow:hidden; border:2px solid var(--primary); display:flex; align-items:center; justify-content:center; flex-shrink:0; background:white; box-shadow:var(--shadow-md);">
            <?php if (!empty($brand_logo)): ?>
                <img src="../assets/images/<?php echo $brand_logo; ?>" style="width:100%; height:100%; object-fit:cover;">
            <?php else: ?>
                <i class="fa-solid fa-heart" style="color:var(--primary); font-size:1.4rem;"></i>
            <?php endif; ?>
        </div>
        <div style="font-weight:800; font-size:1.15rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:160px; line-height:1.2;">
            <?php echo $brand_name; ?>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-label">Main</div>
        <a href="dashboard.php" class="sidebar-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>

        <div class="sidebar-label" style="margin-top:1.25rem;">Management</div>
        <a href="manage_halls.php" class="sidebar-link <?php echo $current_page === 'manage_halls.php' ? 'active' : ''; ?>">
            <i class="fas fa-building"></i> Manage Halls
        </a>
        <a href="manage_bookings.php" class="sidebar-link <?php echo $current_page === 'manage_bookings.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-check"></i> Manage Bookings
        </a>
        <a href="manage_users.php" class="sidebar-link <?php echo $current_page === 'manage_users.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> Manage Users
        </a>
        <a href="contact_inquiries.php" class="sidebar-link <?php echo $current_page === 'contact_inquiries.php' ? 'active' : ''; ?>">
            <i class="fas fa-envelope"></i> Contact Inquiries
        </a>

        <div class="sidebar-label" style="margin-top:1.25rem;">Site</div>
        <a href="banner_settings.php" class="sidebar-link <?php echo $current_page === 'banner_settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-image"></i> Banner Settings
        </a>
        <a href="manage_gallery.php" class="sidebar-link <?php echo $current_page === 'manage_gallery.php' ? 'active' : ''; ?>">
            <i class="fas fa-images"></i> Manage Gallery
        </a>
        <a href="site_settings.php" class="sidebar-link <?php echo $current_page === 'site_settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i> Site Settings
        </a>
        <a href="../index.php" class="sidebar-link" target="_blank">
            <i class="fas fa-external-link-alt"></i> View Website
        </a>
    </nav>

    <div style="padding: 1.5rem; text-align: center; opacity: 1;">
        <img src="../assets/images/wedding_illust.svg" alt="Branding" style="width: 100%; max-width: 120px; filter: grayscale(1) brightness(1.5);">
        <div style="font-size: 0.65rem; color: var(--gray-light); margin-top: 0.5rem; letter-spacing: 0.05em;"><?php echo $brand_name; ?> Admin</div>
    </div>

    <div class="sidebar-footer">
        <a href="../logout.php" class="sidebar-link" style="background:rgba(239,68,68,0.1);color:#fca5a5;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</aside>