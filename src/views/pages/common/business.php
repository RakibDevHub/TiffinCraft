<?php
$categories = $data['categories'];
$kitchens = $data['featuredKitchens'];
$hasPublicRatings = $data['hasPublicRatings'];
$hasAnyRatings = $data['hasAnyRatings'];
$isLoggedIn = $data['isLoggedIn'];
$platform_reviews = $data['platform_reviews'];
$reviewStats = $data['reviewStats'];
$hasReviewed = $data['hasReviewed'];
$totalReviews = count($platform_reviews);


$plans = $data['subscriptionPlans'];


function dateFormat($dateString, $format = 'M j, Y')
{
    if (!$dateString) {
        return '';
    }

    $date = DateTime::createFromFormat('d-M-y h.i.s.u A', $dateString);

    if ($date) {
        return $date->format($format);
    }

    return htmlspecialchars((string)$dateString);
}

include BASE_PATH . '/src/views/components/flash-popup.php';
?>

<main class="home-page">
    <section class="home-section">

        <!-- HERO SECTION -->
        <div class="hero-section">
            <div class="hero-overlay"></div>
            <div class="hero-container">
                <h1>Grow Your Business</h1>
                <p>Reach more customers and manage your tiffin service effortlessly</p>
                <div class="hero-btn">
                    <a class="btn-primary" href="/business/register">Get Started</a>
                    <a class="btn-secondary" href="#features">Learn More</a>
                </div>
            </div>
            <div class="banner-bg"></div>

        </div>

        <!-- BUSINESS BENEFITS -->
        <div class="features-section" id="features">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Why Sell on TiffinCraft?</h2>
                    <p class="section-subtitle">
                        Expand your home kitchen business with our platform
                    </p>
                </div>
                <div class="features-grid">
                    <!-- Grow Your Customer Base Card -->
                    <div class="feature-card">
                        <div class="feature-icon orange">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon-svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                        <h3 class="feature-title">Grow Your Customer Base</h3>
                        <p class="feature-text">Reach hundreds of food lovers in your area looking for homemade meals.</p>
                    </div>

                    <!-- Easy Order Management Card -->
                    <div class="feature-card">
                        <div class="feature-icon blue">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon-svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <h3 class="feature-title">Easy Order Management</h3>
                        <p class="feature-text">Our dashboard helps you track orders, payments, and customer feedback in one place.</p>
                    </div>

                    <!-- Fair Earnings Card -->
                    <div class="feature-card">
                        <div class="feature-icon green">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon-svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="feature-title">Fair Earnings</h3>
                        <p class="feature-text">Keep most of what you earn with our low commission rates.</p>
                    </div>
                </div>
            </div>

            <?php
            $fillColor = '#f9fafb';
            $invert = true;
            $offset = true;

            include BASE_PATH . '/src/views/components/divider-banner.php';
            ?>

        </div>

        <!-- HOW IT WORKS FOR SELLERS -->
        <div id="how-it-works" class="how-it-works-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Start Selling in 3 Simple Steps</h2>
                    <p class="section-subtitle">Join our community of home chefs and turn your passion into profit</p>
                </div>

                <div class="how-steps">
                    <!-- Step 1 -->
                    <div class="how-step">
                        <div class="step-number">1</div>
                        <h3 class="step-title">Create Your Profile</h3>
                        <p class="step-text">
                            Set up your seller profile with your kitchen details and food specialties.
                        </p>
                    </div>

                    <!-- Step 2 -->
                    <div class="how-step">
                        <div class="step-number">2</div>
                        <h3 class="step-title">Add Your Menu</h3>
                        <p class="step-text">
                            Upload photos and descriptions of your dishes with prices and availability.
                        </p>
                    </div>

                    <!-- Step 3 -->
                    <div class="how-step">
                        <div class="step-number">3</div>
                        <h3 class="step-title">Start Receiving Orders</h3>
                        <p class="step-text">
                            Manage incoming orders through our seller dashboard and grow your business.
                        </p>
                    </div>

                </div>
            </div>

            <?php
            $fillColor = '#FFF7ED';
            $invert = true;
            $offset = true;

            include BASE_PATH . '/src/views/components/divider-banner.php';
            ?>
        </div>

        <!-- SUBSCRIPTION PLAN SECTION -->
        <section class="subscription-section">
            <div class="container">
                <!-- Section Header -->
                <div class="section-header">
                    <h2 class="section-title" data-aos="zoom-in">Simple, Fair Pricing</h2>
                    <p class="section-subtitle" data-aos="zoom-in" data-aos-delay="200">Pay as you grow with no hidden fees</p>
                </div>

                <!-- Pricing Cards Container -->
                <div class="pricing-grid">
                    <?php
                    if (!empty($plans)):
                        foreach ($plans as $index => $plan):
                            $isHighlight = ($plan['IS_HIGHLIGHT'] ?? 0) == 1;
                            $planName = strtolower($plan['PLAN_NAME'] ?? '');
                            $delay = $index * 200;
                    ?>
                            <!-- <?php echo htmlspecialchars($plan['PLAN_NAME'] ?? 'Plan'); ?> PLAN -->
                            <div class="pricing-card <?php echo $isHighlight ? 'highlight-card' : ''; ?>"
                                data-aos="fade-up"
                                data-aos-delay="<?php echo $delay; ?>">

                                <!-- Card Header -->
                                <div class="card-header <?php echo $isHighlight ? 'bg-highlight' : 'bg-normal'; ?>">
                                    <?php if ($isHighlight): ?>
                                        <div class="popular-badge">Featured Plan</div>
                                    <?php endif; ?>

                                    <h3 class="plan-name"><?php echo htmlspecialchars($plan['PLAN_NAME'] ?? 'Unnamed Plan'); ?></h3>

                                    <div class="plan-price">
                                        <span class="price" style="<?php echo $isHighlight ? 'color: white;' : 'color: #1f2937;' ?>">৳<?php echo number_format($plan['MONTHLY_FEE'] ?? 0); ?></span>
                                        <span class="period">/month</span>
                                    </div>

                                    <p class="plan-tagline"><?php echo htmlspecialchars($plan['DESCRIPTION'] ?? 'Perfect for home kitchens'); ?></p>
                                </div>

                                <!-- Card Body -->
                                <div class="card-body">
                                    <ul class="feature-list">
                                        <!-- Max Items Feature -->
                                        <li class="feature-item">
                                            <i class="fas fa-check-circle feature-icon"></i>

                                            <span>
                                                <?php
                                                $maxItems = $plan['MAX_ITEMS'] ?? 0;
                                                echo $maxItems >= 999999 ? 'Unlimited dishes' : $maxItems . ' active dishes';
                                                ?>
                                            </span>
                                        </li>

                                        <!-- Commission Feature -->
                                        <li class="feature-item">
                                            <i class="fas fa-check-circle feature-icon"></i>

                                            <span><?php echo $plan['COMMISSION_RATE'] ?? 0; ?>% commission</span>
                                        </li>

                                        <li class="feature-item">
                                            <i class="fas fa-check-circle feature-icon"></i>
                                            <span>support 24/7</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        <?php
                        endforeach;
                    else:
                        ?>
                        <!-- Fallback if no plans found -->
                        <div class="no-plans-message">
                            <p>Subscription plans are currently being updated. Please check back soon.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php
            $fillColor = '#f9fafb';
            $invert = true;
            $offset = true;

            include BASE_PATH . '/src/views/components/divider-banner.php';
            ?>
        </section>

        <!-- APP DOWNLOAD SECTION -->
        <div id="app-download" class="app-download-section">
            <div class="app-download-container">
                <div class="app-download-card">

                    <!-- Left Content -->
                    <div class="app-text">
                        <h2 class="app-text-title">Get the TiffinCraft App</h2>

                        <p class="app-text-subtitle">
                            Download our app for faster ordering, exclusive offers, and real-time delivery tracking.
                        </p>

                        <div class="app-buttons">
                            <!-- App Store -->
                            <a href="#" class="store-btn">
                                <svg class="store-icon" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-1.57 2.31-2.71 3.89-2.73 1.55-.03 3.17.91 3.9 2.27-3.35 1.99-2.56 6.04.54 7.14-.78 1.92-1.8 3.83-3.41 5.29zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"></path>
                                </svg>
                                App Store
                            </a>

                            <!-- Google Play -->
                            <a href="#" class="store-btn">
                                <svg class="store-icon" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"></path>
                                </svg>
                                Google Play
                            </a>
                        </div>
                    </div>

                    <!-- Right Image -->
                    <div class="app-image">
                        <img src="/assets/images/downloadapp.png" alt="App Screenshot" class="app-photo">
                    </div>

                </div>
            </div>

            <?php
            $fillColor = '#f97316';
            $invert = true;
            $offset = true;

            include BASE_PATH . '/src/views/components/divider-banner.php';
            ?>
        </div>


        <!-- CTA SECTION -->
        <div class="cta-section" style="background-color: #f97316;">
            <div class="cta-container">
                <h2 class="cta-title" style="color: #fff;">Ready to Experience Homemade Goodness?</h2>

                <p class="cta-subtitle" style="color: #fff;">
                    Join thousands of happy customers enjoying authentic home-cooked meals today
                </p>
                <div class="button-container">
                    <a href="/business/register" class="btn-orange">
                        Sign Up Now - It's Free
                    </a>
                    <a href="/business/login" class="btn-secondary">
                        Login to Your Account
                    </a>
                </div>
            </div>
    </section>
</main>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
    // Initialize Swiper for kitchens slider
    document.addEventListener('DOMContentLoaded', () => {
        const swiper = new Swiper('.myKitchensSwiper', {
            slidesPerView: 1,
            spaceBetween: 20,
            loop: true,
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            breakpoints: {
                640: {
                    slidesPerView: 2
                },
                1024: {
                    slidesPerView: 3
                },
            },
        });

        document.querySelectorAll('.kitchen-card').forEach(card => {
            card.addEventListener('click', function() {
                window.location.href = this.dataset.url;
            });
        });
    });

    // Star rating hover effect
    document.addEventListener('DOMContentLoaded', function() {
        const starLabels = document.querySelectorAll('.star-rating-label');

        starLabels.forEach(label => {
            label.addEventListener('mouseover', function() {
                let current = this;
                while (current) {
                    current.style.color = '#f59e0b';
                    current = current.nextElementSibling;
                }
            });

            label.addEventListener('mouseout', function() {
                const input = this.previousElementSibling;
                if (!input || !input.checked) {
                    let current = this;
                    while (current) {
                        current.style.color = '#d1d5db';
                        current = current.nextElementSibling;
                    }
                }
            });
        });
    });

    // Auto-hide toast after 5 seconds
    <?php if (isset($_SESSION['toast'])): ?>
        setTimeout(() => {
            const toast = document.querySelector('.fixed.bottom-5.right-5');
            if (toast) toast.remove();
        }, 5000);
    <?php endif; ?>
</script>