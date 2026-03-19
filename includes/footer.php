<footer class="footer">
    <div class="container">
        <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1.5fr;gap:2rem;">
            <div>
                <div class="footer-logo" style="display:flex; align-items:center; gap:1rem; font-weight:800; color:white; font-size:1.3rem;">
                    <div style="width:50px; height:50px; border-radius:50%; overflow:hidden; border:2px solid var(--primary); display:flex; align-items:center; justify-content:center; background:white; flex-shrink:0;">
                         <?php if (!empty($brand_logo)): ?>
                            <img src="assets/images/<?php echo $brand_logo; ?>" style="width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                            <i class="fas fa-building-columns" style="color:var(--primary); font-size:1.2rem;"></i>
                        <?php endif; ?>
                    </div>
                    <span>Sri Lakshmi Residency & Mahal</span>
                </div>
                <ul style="list-style:none;padding:0;margin:1rem 0 0;display:flex;flex-direction:column;gap:0.6rem;">
                    <li style="font-size:0.875rem;line-height:1.6;"><i class="fas fa-check" style="color:var(--primary);margin-right:0.5rem;"></i>Premium AC Rooms with Breakfast</li>
                    <li style="font-size:0.875rem;line-height:1.6;"><i class="fas fa-check" style="color:var(--primary);margin-right:0.5rem;"></i>Fully AC Mahal for Grand Celebrations</li>
                    <li style="font-size:0.875rem;line-height:1.6;"><i class="fas fa-check" style="color:var(--primary);margin-right:0.5rem;"></i>Trusted Hospitality &amp; Event Services</li>
                </ul>
            </div>
            <div>
                <div style="font-weight:700;color:white;margin-bottom:1rem;font-size:0.9rem;">Quick Links</div>
                <div class="footer-links">
                    <a href="index.php">Home</a>
                    <a href="halls.php">Browse Halls</a>
                    <a href="gallery.php">Photo Gallery</a>
                    <a href="my_bookings.php">My Bookings</a>
                </div>
            </div>
            <div>
                <div style="font-weight:700;color:white;margin-bottom:1rem;font-size:0.9rem;">Account</div>
                <div class="footer-links">
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                </div>
            </div>
            <div>
                <div style="font-weight:700;color:white;margin-bottom:1.25rem;font-size:0.9rem;">Contact</div>
                <div style="font-size:0.875rem; display:flex; flex-direction:column; gap:0.9rem;">
                    <div style="display:flex; align-items:center; gap:0.75rem;">
                        <i class="fas fa-phone" style="color:var(--primary); width:20px; text-align:center;"></i>
                        <span>+91 98765 43210</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:0.75rem;">
                        <i class="fas fa-envelope" style="color:var(--primary); width:20px; text-align:center;"></i>
                        <span><?php echo strtolower($brand_name); ?>@gmail.com</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:0.75rem;">
                        <i class="fas fa-map-marker-alt" style="color:var(--primary); width:20px; text-align:center;"></i>
                        <span>Srivilliputhur, Tamil Nadu</span>
                    </div>
                </div>
            </div>
        </div>
        <hr class="footer-divider">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;font-size:0.8rem;">
            <span>&copy; <?php echo date('Y'); ?> Sri Lakshmi Residency & Mahal. All rights reserved.</span>
            <span><a href="https://anjanainfotech.in/" target="_blank">Developed by Anjana Infotech</a></span>
            <span>Designed for comfort. Built for celebrations</span>
        </div>
    </div>
</footer>

<style>
@media (max-width: 900px) {
    .footer .container > div:first-child {
        grid-template-columns: 1fr 1fr !important;
    }
}
@media (max-width: 576px) {
    .footer .container > div:first-child {
        grid-template-columns: 1fr !important;
    }
}
</style>
