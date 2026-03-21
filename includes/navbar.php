<?php
// Determine current page for active link highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar" id="navbar">
    <!-- Animated Heart Logo -->
    <a href="index.php" class="navbar-brand" style="display:flex; align-items:center; gap:1rem; line-height:1;">
        <div class="brand-logo-circle" style="width:60px; height:60px; border-radius:50%; overflow:hidden; display:flex; align-items:center; justify-content:center; background:white; flex-shrink:0;">
            <?php if (!empty($brand_logo)): ?>
                <img src="assets/images/<?php echo $brand_logo; ?>" style="width:100%; height:100%; object-fit:cover;">
            <?php else: ?>
                <i class="fa-solid fa-heart" style="color:var(--primary); font-size:1.6rem;"></i>
            <?php endif; ?>
        </div>
        <span style="font-weight:800; font-size:1.3rem; color:#ad1457; white-space:nowrap; letter-spacing:-0.02em; font-family:'Cinzel', serif;"><?php echo $brand_name; ?></span>
    </a>

    <!-- Hamburger for mobile -->
    <div class="navbar-toggler" onclick="document.getElementById('navLinks').classList.toggle('open'); this.classList.toggle('active')">
        <span></span><span></span><span></span>
    </div>

    <!-- Nav Links -->
    <ul class="nav-links" id="navLinks">
        <!-- Mobile Close Button -->
        <li class="mobile-only" style="display:none; position:absolute; top:12px; right:16px; z-index:10;">
            <div class="close-nav" onclick="document.getElementById('navLinks').classList.remove('open'); document.querySelector('.navbar-toggler').classList.remove('active')" style="font-size:1.4rem; color:var(--primary); cursor:pointer;"><i class="fas fa-times"></i></div>
        </li>
        <li class="mobile-only" style="display:none; text-align:center; padding:1rem 1rem 0.5rem;">
            <img src="assets/images/wedding_illust.svg" alt="Celebrate" style="width:80px; filter: drop-shadow(0 6px 12px rgba(0,0,0,0.1)); margin-bottom:0.5rem;">
            <div style="font-family:'Poppins',sans-serif; font-weight:700; font-size:0.8rem; color:var(--primary);"><?php echo $brand_name; ?></div>
            <div style="font-size:0.65rem; color:var(--gray); margin-top:0.15rem;">Where Comfort Meets Celebration</div>
            <div style="width:30px; height:2px; background:var(--secondary); margin:0.6rem auto 0; border-radius:1px; opacity:0.5;"></div>
        </li>
        <li>
            <a href="index.php" class="<?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Home
            </a>
        </li>
        <li>
            <a href="about.php" class="<?php echo $current_page === 'about.php' ? 'active' : ''; ?>">
                <i class="fas fa-building"></i> About Us
            </a>
        </li>
        <li>
            <a href="halls.php" class="<?php echo $current_page === 'halls.php' ? 'active' : ''; ?>">
                <i class="fas fa-layer-group"></i> Services
            </a>
        </li>
        <li>
            <a href="gallery.php" class="<?php echo $current_page === 'gallery.php' ? 'active' : ''; ?>">
                <i class="fas fa-images"></i> Gallery
            </a>
        </li>
        <li>
            <a href="contact.php" class="<?php echo $current_page === 'contact.php' ? 'active' : ''; ?>">
                <i class="fas fa-envelope"></i> Contact Us
            </a>
        </li>

        <?php if (isLoggedIn()): ?>
            <li>
                <a href="my_bookings.php" class="<?php echo $current_page === 'my_bookings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i> My Bookings
                </a>
            </li>
            <?php if (isAdmin()): ?>
                <li>
                    <a href="admin/dashboard.php">
                        <i class="fas fa-cog"></i> Admin
                    </a>
                </li>
            <?php endif; ?>
            <li class="nav-btn-wrap">
                <a href="logout.php" class="btn btn-outline btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        <?php else: ?>
            <li class="nav-btn-wrap">
                <a href="login.php" class="btn btn-outline btn-sm <?php echo $current_page === 'login.php' ? 'active' : ''; ?>">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="register.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-user-plus"></i> Register
                </a>
            </li>
        <?php endif; ?>
    </ul>
</nav>

<style>
@media (max-width: 768px) {
    .brand-logo-circle { width: 34px !important; height: 34px !important; }
}
@media (min-width: 321px) and (max-width: 426px){
    .navbar-brand span { font-size: 0.82rem !important; max-width: 220px; line-height: 1.3; }
}
@media (max-width: 320px){
    .navbar-brand span { font-size: 0.82rem !important; white-space: normal !important; max-width: 160px; line-height: 1.3; }
}
@media (max-width: 1150px) {
    .nav-links {
        background: #ffffff !important;
        padding: 3rem 1.25rem 1.25rem !important;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
    }
    .nav-links li.mobile-only { display: block !important; }
    .nav-links a:not(.btn) {
        font-size: 0.8rem !important;
        padding: 0.7rem 1rem !important;
        margin-bottom: 0.2rem !important;
    }
    .nav-links .nav-btn-wrap {
        padding: 0.75rem 0 0 !important;
        margin-top: 0.5rem !important;
        border-top: 1px solid var(--border);
    }
    .nav-links .nav-btn-wrap .btn {
        padding: 0.6rem 1rem !important;
        font-size: 0.8rem !important;
    }
}
</style>

<script>
// Navbar scroll effect
window.addEventListener('scroll', function() {
    const nav = document.getElementById('navbar');
    if (nav) nav.classList.toggle('scrolled', window.scrollY > 50);
});
// Close nav on outside click
document.addEventListener('click', function(e) {
    const nav = document.getElementById('navLinks');
    const toggle = document.querySelector('.navbar-toggler');
    if (nav && toggle && !nav.contains(e.target) && !toggle.contains(e.target)) {
        nav.classList.remove('open');
        toggle.classList.remove('active');
    }
});
</script>
