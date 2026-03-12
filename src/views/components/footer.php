<?php
$isBusinessView = strpos($_SERVER['REQUEST_URI'], '/business') !== false;
$requestUri = $_SERVER['REQUEST_URI'];
?>

<footer class="footer <?= $isBusinessView ? 'footer-business' : 'footer-home'; ?>">
    <div class="footer-container">
        <!-- Logo & Description -->
        <div class="footer-logo-section">
            <div class="logo-wrapper">
                <img src="/assets/images/main-logo.png" alt="TiffinCraft <?= $isBusinessView ? 'Business' : 'Home' ?>" class="logo-img">
                <?php if ($isBusinessView): ?>
                    <span class="business-badge">BUSINESS</span>
                <?php endif; ?>
            </div>
            <p class="footer-description">
                Connecting home chefs with food lovers. Explore delicious homemade dishes crafted with care.
            </p>
        </div>

        <!-- Links Section -->
        <div class="footer-links-section">
            <!-- Quick Links -->
            <div class="footer-links-block">
                <h3 class="links-title">Quick Links</h3>
                <ul class="links-list">
                    <li><a href="/" class="<?= $requestUri === '/' ? 'active-link' : 'link' ?>">TiffinCraft</a></li>
                    <li><a href="/dishes" class="<?= strpos($requestUri, '/dishes') === 0 ? 'active-link' : 'link' ?>">Browse Dishes</a></li>
                    <li><a href="/kitchens" class="<?= strpos($requestUri, '/kitchens') === 0 ? 'active-link' : 'link' ?>">Browse Kitchens</a></li>
                    <li><a href="/login" class="<?= strpos($requestUri, '/login') === 0 ? 'active-link' : 'link' ?>">Login to Your Account</a></li>
                    <li><a href="/register" class="<?= strpos($requestUri, '/register') === 0 ? 'active-link' : 'link' ?>">Register Now</a></li>
                </ul>
            </div>

            <!-- Business Links -->
            <!-- <div class="footer-links-block">
                <h3 class="links-title">TiffinCraft Business</h3>
                <ul class="links-list">
                    <li><a href="/business" class="<?= strpos($requestUri, '/business') === 0 ? 'active-link' : 'link' ?>">Sell on Our Platform</a></li>
                    <li><a href="/business/login" class="<?= strpos($requestUri, '/business/login') === 0 ? 'active-link' : 'link' ?>">Login to Your Account</a></li>
                    <li><a href="/business/register" class="<?= strpos($requestUri, '/business/register') === 0 ? 'active-link' : 'link' ?>">Open a Business Account</a></li>
                </ul>
            </div> -->

            <!-- Contact -->
            <div class="footer-links-block">
                <h3 class="links-title">Contact Us</h3>
                <ul class="links-list">
                    <li><a href="mailto:info@tiffincraft.com" class="link">info@tiffincraft.com</a></li>
                    <li>Phone: +1-555-123-4567</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">
        <div class="footer-bottom-container">
            <p>&copy; <?= date('Y'); ?> TiffinCraft. All rights reserved.</p>
            <div class="footer-social">
                <span class="social-label">Follow Us:</span>
                <a href="#" target="_blank" class="social-link"><i class="fab fa-facebook-f"></i></a>
                <a href="#" target="_blank" class="social-link twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" target="_blank" class="social-link instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" target="_blank" class="social-link linkedin"><i class="fab fa-linkedin-in"></i></a>
            </div>
        </div>
    </div>
</footer>