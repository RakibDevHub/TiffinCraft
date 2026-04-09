<?php
$currentPage = $page ?? 'home';

$isHomeView = true;
// $isBusinessView = false;

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$normalizedPath = rtrim($path, '/');
if ($normalizedPath === '' || $normalizedPath === '/home') {
    $isHomeView = true;
} else {
    $isHomeView = false;
}

if ($isHomeView && !AuthHelper::isLoggedIn()) {
    include BASE_PATH . '/src/views/components/cta-bar.php';
}

$isBusinessView = str_starts_with($path, '/business');

$profileImage = $_SESSION['user_image'] ?? '';

?>

<header class="navbar">
    <div class="navbar-container">
        <!-- Logo -->
        <div class="logo">
            <a href="<?= $isBusinessView ? '/business' : '/' ?>"><img src="/assets/images/logo.png" alt="TiffinCraft">
                <?= $isBusinessView ? '<span class="logo-subtext">Business</span>' : '' ?>
            </a>
        </div>

        <?php if (!$isBusinessView): ?>
            <!-- Desktop Navigation links -->
            <nav class="nav-links desktop-menu">
                <a href="/" class="<?= $currentPage == 'home' ? 'active' : '' ?>">Home</a>
                <a href="/dishes" class="<?= $currentPage == 'dishes' ? 'active' : '' ?>">Delicious Dishes</a>
                <a href="/kitchens" class="<?= $currentPage == 'kitchens' ? 'active' : '' ?>">Browse Kitchens</a>
                <a href="/contact" class="<?= $currentPage == 'contact' ? 'active' : '' ?>">Contact</a>
            </nav>
        <?php endif; ?>

        <!-- Desktop Auth buttons -->
        <?php if (!AuthHelper::isLoggedIn()): ?>
            <div class="auth-buttons desktop">
                <a href="<?= $isBusinessView ? '/business/' : '/' ?>login" class="btn login">Login</a>
                <a href="<?= $isBusinessView ? '/business/' : '/' ?>register" class="btn register">Register</a>
            </div>
        <?php else: ?>
            <div class="auth-buttons desktop" style="position: relative;">
                <button class="profile-btn" id="desktopProfileBtn">
                    <?php if ($profileImage): ?>
                        <img src="/uploads/profile/<?= htmlspecialchars($profileImage) ?>?>" alt="Profile">
                    <?php else: ?>
                        <img src="/assets/images/M-Avatar.jpg" alt="Profile">
                    <?php endif; ?>
                </button>
                <div class="dropdown" id="dropdownMenu">
                    <?php if (AuthHelper::isLoggedIn('admin')): ?>
                        <a href="/admin/dashboard" class="<?= $currentPage == 'dashboard' ? 'active' : '' ?>"><i class="fas fa-chart-line"></i>Dashboard</a>
                        <a href="/admin/dashboard/settings" class="<?= $currentPage == 'settings' ? 'active' : '' ?>"><i class="fas fa-cog"></i>Settings</a>
                    <?php elseif (AuthHelper::isLoggedIn('seller')): ?>
                        <a href="/business/dashboard" class="<?= $currentPage == 'dashboard' ? 'active' : '' ?>"><i class="fas fa-chart-line"></i>Dashboard</a>
                        <a href="/business/dashboard/settings" class="<?= $currentPage == 'settings' ? 'active' : '' ?>"><i class="fas fa-cog"></i>Settings</a>
                    <?php elseif (AuthHelper::isLoggedIn('buyer')): ?>
                        <a href="/cart" class="<?= $currentPage == 'cart' ? 'active' : '' ?>"><i class="fa-solid fa-cart-shopping"></i>My Cart</a></li>
                        <a href="/orders" class="<?= $currentPage == 'orders' ? 'active' : '' ?>"><i class="fa-solid fa-receipt"></i>My Orders</a></li>
                        <a href="/favorites" class="<?= $currentPage == 'favorites' ? 'active' : '' ?>"><i class="fa-solid fa-heart"></i>Favorites</a></li>
                        <a href="/refunds" class="<?= $currentPage == 'refunds' ? 'active' : '' ?>"><i class="fas fa-money-bill-wave"></i>Refunds</a></li>
                        <a href="/settings" class="<?= $currentPage == 'settings' ? 'active' : '' ?>"><i class="fas fa-cog"></i>Settings</a>
                    <?php endif; ?>
                    <div class="dropdown-divider"></div>
                    <a href="/logout"><i class="fas fa-sign-out-alt"></i>Logout</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Mobile menu -->
        <div class="mobile-menu">
            <?php if (AuthHelper::isLoggedIn()): ?>
                <div class="auth-buttons" style="position: relative;">
                    <button class="profile-btn" id="mobileProfileBtn">
                        <img src="/assets/images/M-Avatar.jpg" alt="Profile">
                    </button>
                    <div class="dropdown" id="dropdownMenuMobile">
                        <?php if (AuthHelper::isLoggedIn('admin')): ?>
                            <a href="/admin/dashboard" class="<?= $currentPage == 'dashboard' ? 'active' : '' ?>"><i class="fas fa-chart-line"></i>Dashboard</a>
                            <a href="/admin/dashboard/settings" class="<?= $currentPage == 'settings' ? 'active' : '' ?>"><i class="fas fa-cog"></i>Settings</a>
                        <?php elseif (AuthHelper::isLoggedIn('seller')): ?>
                            <a href="/business/dashboard" class="<?= $currentPage == 'dashboard' ? 'active' : '' ?>"><i class="fas fa-chart-line"></i>Dashboard</a>
                            <a href="/business/dashboard/settings" class="<?= $currentPage == 'settings' ? 'active' : '' ?>"><i class="fas fa-cog"></i>Settings</a>
                        <?php elseif (AuthHelper::isLoggedIn('buyer')): ?>
                            <a href="/cart" class="<?= $currentPage == 'cart' ? 'active' : '' ?>"><i class="fa-solid fa-cart-shopping"></i>My Cart</a></li>
                            <a href="/orders" class="<?= $currentPage == 'orders' ? 'active' : '' ?>"><i class="fa-solid fa-receipt"></i>My Orders</a></li>
                            <a href="/favorites" class="<?= $currentPage == 'favorites' ? 'active' : '' ?>"><i class="fa-solid fa-heart"></i>Favorites</a></li>
                            <a href="/refunds" class="<?= $currentPage == 'refunds' ? 'active' : '' ?>"><i class="fas fa-money-bill-wave"></i>Refunds</a></li>
                            <a href="/settings" class="<?= $currentPage == 'settings' ? 'active' : '' ?>"><i class="fas fa-cog"></i>Settings</a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="/logout"><i class="fas fa-sign-out-alt"></i>Logout</a>
                    </div>
                </div>
            <?php endif; ?>
            <div class="hamburger" id="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation links -->
    <nav class="nav-links mobile-menu" id="mobileNavLinks">
        <?php if (!$isBusinessView): ?>
            <a href="/" class="<?= $currentPage == 'home' ? 'active' : '' ?>">Home</a>
            <a href="/dishes" class="<?= $currentPage == 'dishes' ? 'active' : '' ?>">Delicious Dishes</a>
            <a href="/kitchens" class="<?= $currentPage == 'kitchens' ? 'active' : '' ?>">Browse Kitchens</a>
            <!-- <a href="/about" class="<?= $currentPage == 'about' ? 'active' : '' ?>">About</a> -->
            <a href="/contact" class="<?= $currentPage == 'contact' ? 'active' : '' ?>">Contact</a>
        <?php endif; ?>
        <!-- Auth buttons for mobile view -->
        <?php if (!AuthHelper::isLoggedIn()): ?>
            <div class="auth-buttons-mobile">
                <a href="/login" class="btn login">Login</a>
                <a href="/register" class="btn register">Register</a>
            </div>
        <?php endif; ?>
    </nav>
</header>

<script>
    // Define functions in global scope
    function toggleDropdown() {
        const dropdowns = document.querySelectorAll(".dropdown");
        dropdowns.forEach((dropdown) => {
            dropdown.classList.toggle("show");
        });
    }

    function toggleMobileMenu() {
        const mobileNavLinks = document.getElementById("mobileNavLinks");
        const hamburger = document.getElementById("hamburger");

        if (mobileNavLinks) {
            mobileNavLinks.classList.toggle("active");
        }

        if (hamburger) {
            hamburger.classList.toggle("active");
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        // Mobile menu toggle
        const hamburger = document.getElementById("hamburger");
        const navLinks = document.getElementById("mobileNavLinks");

        if (hamburger && navLinks) {
            hamburger.addEventListener("click", () => {
                toggleMobileMenu();
            });
        }

        // Add shadow on scroll
        window.addEventListener("scroll", () => {
            const navbar = document.querySelector(".navbar");
            if (navbar) {
                if (window.scrollY > 50) {
                    navbar.classList.add("scrolled");
                } else {
                    navbar.classList.remove("scrolled");
                }
            }
        });

        // Popup modal for the login page
        const resendBtn = document.getElementById("resendBtn");
        const resendModal = document.getElementById("resendModal");
        const closeModal = document.getElementById("closeModal");

        if (resendBtn && resendModal) {
            resendBtn.addEventListener("click", function() {
                resendModal.style.display = "flex";
            });
        }

        if (closeModal && resendModal) {
            closeModal.addEventListener("click", function() {
                resendModal.style.display = "none";
            });
        }

        // Close when clicking outside modal
        window.addEventListener("click", function(e) {
            if (e.target === resendModal) {
                resendModal.style.display = "none";
            }
        });

        // Add event listeners to all profile buttons
        const desktopProfileBtn = document.getElementById("desktopProfileBtn");
        const mobileProfileBtn = document.getElementById("mobileProfileBtn");

        if (desktopProfileBtn) {
            desktopProfileBtn.addEventListener("click", function(e) {
                e.stopPropagation();
                toggleDropdown();
            });
        }

        if (mobileProfileBtn) {
            mobileProfileBtn.addEventListener("click", function(e) {
                e.stopPropagation();
                toggleDropdown();
            });
        }

        // Close dropdown when clicking outside
        document.addEventListener("click", function(event) {
            const dropdowns = document.querySelectorAll(".dropdown");
            const profileButtons = document.querySelectorAll(".profile-btn");

            const isClickInsideDropdown = Array.from(dropdowns).some((dropdown) =>
                dropdown.contains(event.target)
            );

            const isClickInsideProfileButton = Array.from(profileButtons).some(
                (button) => button.contains(event.target)
            );

            if (!isClickInsideDropdown && !isClickInsideProfileButton) {
                dropdowns.forEach((dropdown) => {
                    dropdown.classList.remove("show");
                });
            }
        });

        // Close mobile menu when clicking on a link
        const mobileLinks = document.querySelectorAll(".nav-links a");
        mobileLinks.forEach((link) => {
            link.addEventListener("click", function() {
                if (navLinks && navLinks.classList.contains("active")) {
                    navLinks.classList.remove("active");
                    if (hamburger) {
                        hamburger.classList.remove("active");
                    }
                }
            });
        });
    });
</script>