<footer class="footer">
    <div class="container">
        <div style="display:grid; grid-template-columns:1.5fr 0.9fr 1.2fr 1.6fr; gap:2.5rem; align-items:flex-start; font-family:'Inter', sans-serif;">
            <div>
                <div class="footer-logo" style="display:flex; align-items:center; gap:0.75rem; font-weight:800; color:white; font-size:1.25rem; margin-bottom:1.25rem; font-family:'Poppins', sans-serif;">
                    <div style="width:40px; height:40px; border-radius:50%; overflow:hidden; border:2px solid var(--primary); display:flex; align-items:center; justify-content:center; background:white; flex-shrink:0;">
                         <?php if (!empty($brand_logo)): ?>
                            <img src="assets/images/<?php echo $brand_logo; ?>" style="width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                            <i class="fas fa-building-columns" style="color:var(--primary); font-size:1.1rem;"></i>
                        <?php endif; ?>
                    </div>
                    <span>SLR Mahal</span>
                </div>
                <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:0.6rem;">
                    <li style="font-size:0.92rem; line-height:1.5; color:rgba(255,255,255,0.85);"><i class="fas fa-check" style="color:var(--primary); margin-right:0.6rem; font-size:0.8rem;"></i>Premium AC Rooms</li>
                    <li style="font-size:0.92rem; line-height:1.5; color:rgba(255,255,255,0.85);"><i class="fas fa-check" style="color:var(--primary); margin-right:0.6rem; font-size:0.8rem;"></i>Fully AC Mahal</li>
                </ul>
            </div>
            <div>
                <div style="font-weight:700; color:white; margin-bottom:1.25rem; font-size:0.95rem; font-family:'Poppins', sans-serif; text-transform:uppercase; letter-spacing:0.03em;">Quick Links</div>
                <div class="footer-links" style="gap:0.6rem;">
                    <a href="index.php" style="font-size:0.92rem; color:rgba(255,255,255,0.8);">Home</a>
                    <a href="halls.php" style="font-size:0.92rem; color:rgba(255,255,255,0.8);">Browse Halls</a>
                    <a href="gallery.php" style="font-size:0.92rem; color:rgba(255,255,255,0.8);">Gallery</a>
                </div>
            </div>
            <div>
                <div style="font-weight:700; color:white; margin-bottom:1.25rem; font-size:0.95rem; font-family:'Poppins', sans-serif; text-transform:uppercase; letter-spacing:0.03em;">Contact</div>
                <div style="font-size:0.92rem; display:flex; flex-direction:column; gap:0.75rem; color:rgba(255,255,255,0.8);">
                    <div style="display:flex; align-items:flex-start; gap:0.75rem;">
                        <i class="fas fa-phone" style="color:var(--primary); width:18px; text-align:center; margin-top:3px;"></i>
                        <span>+91 98765 43210</span>
                    </div>
                    <div style="display:flex; align-items:flex-start; gap:0.75rem;">
                        <i class="fas fa-envelope" style="color:var(--primary); width:18px; text-align:center; margin-top:3px;"></i>
                        <span>slr@gmail.com</span>
                    </div>
                </div>
            </div>
            <div>
                <div style="font-weight:700; color:white; margin-bottom:1.25rem; font-size:0.95rem; font-family:'Poppins', sans-serif; text-transform:uppercase; letter-spacing:0.03em;">Location</div>
                <div style="border-radius:var(--radius); overflow:hidden; height:110px; border:1px solid rgba(255,255,255,0.15); box-shadow:0 4px 15px rgba(0,0,0,0.1);">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3934.8595558505026!2d77.63345247367901!3d9.520929281097514!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3b06dda3211bacff%3A0xb3b5f817bb93c64d!2sSri%20Lakshmi%20Residency%20And%20Mahal!5e0!3m2!1sen!2sin!4v1774067577631!5m2!1sen!2sin" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>

        <hr class="footer-divider" style="margin-top:2rem; margin-bottom:2rem; border-color:rgba(255,255,255,0.1);">

        <div class="footer-bottom-grid" style="display:grid; grid-template-columns:1.2fr auto 1.2fr; align-items:center; gap:2rem; font-size:0.88rem;">
            <div style="text-align:left; color:white; font-family:'Poppins', sans-serif; font-weight:700; font-size:0.85rem; letter-spacing:0.03em;">
                &copy; <?php echo date('Y'); ?> SRI LAKSHMI RESIDENCY & MAHAL.
            </div>
            <div style="text-align:center;">
                <a href="https://anjanainfotech.in/" target="_blank" style="color:rgba(255,255,255,0.9); transition:0.3s; font-weight:600; text-decoration:none;">Developed by Anjana Infotech</a>
            </div>
            <div style="text-align:right; display:flex; align-items:center; justify-content:flex-end; gap:1.25rem; margin-right:5%;">
                <span style="font-weight:700; color:white; letter-spacing:0.05em; text-transform:uppercase; font-size:0.78rem; font-family:'Poppins', sans-serif;">Follow Us</span>

                <div style="display:flex; gap:1rem; align-items:center;">
                    <a href="#" class="footer-social-link-lg" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="footer-social-link-lg" title="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="footer-social-link-lg" title="YouTube"><i class="fab fa-youtube"></i></a>
                    <a href="https://wa.me/919876543210" class="footer-social-link-lg" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
        </div>

        <style>
            .footer-social-link-lg {
                color: white;
                font-size: 1.25rem;
                transition: 0.3s;
                opacity: 0.9;
            }
            .footer-social-link-lg:hover {
                color: var(--primary);
                transform: scale(1.2);
                opacity: 1;
            }
            @media (max-width: 992px) {
                .footer-bottom-grid {
                    grid-template-columns: 1fr !important;
                    text-align: center !important;
                    gap: 1.25rem;
                }
                .footer-bottom-grid > div {
                    text-align: center !important;
                    justify-content: center !important;
                }
            }
        </style>
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
