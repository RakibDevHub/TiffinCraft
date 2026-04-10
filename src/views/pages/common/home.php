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
        <div class="hero-section hero">
            <div class="hero-overlay"></div>
            <div class="hero-container">
                <h1>Authentic Homemade Meals Delivered to Your Door</h1>
                <p>Discover local home chefs preparing fresh, traditional meals with love and care</p>
                <div class="hero-btn">
                    <a class="btn-primary" href="/dishes">Browes Our Menu</a>
                    <a class="btn-secondary" href="#how-it-works">How It Works</a>
                </div>
            </div>
            <div class="banner-bg"></div>

        </div>

        <!-- FEATURES SECTION  -->
        <div class="features-section">
            <div class="container">
                <div class="features-grid">
                    <!-- Fast Delivery Card -->
                    <div class="feature-card">
                        <div class="feature-icon orange">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon-svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="feature-title">Fast Delivery</h3>
                        <p class="feature-text">Fresh meals delivered in under 45 minutes</p>
                    </div>

                    <!-- Quality Assured Card -->
                    <div class="feature-card">
                        <div class="feature-icon blue">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon-svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A12.955 12.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <h3 class="feature-title">Quality Assured</h3>
                        <p class="feature-text">All home chefs pass rigorous quality checks</p>
                    </div>

                    <!-- Affordable Prices Card -->
                    <div class="feature-card">
                        <div class="feature-icon green">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon-svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="feature-title">Affordable Prices</h3>
                        <p class="feature-text">Home-cooked meals at restaurant quality prices</p>
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

        <!-- FEATURED CATEGORIES -->
        <div id="explore" class="explore-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Explore Our Menu Categories</h2>
                    <p class="section-subtitle">
                        From traditional thalis to regional specialties, discover authentic homemade flavors
                    </p>
                </div>

                <div class="section-grid">
                    <?php foreach ($categories as $category): ?>
                        <a href="/dishes?category=<?= htmlspecialchars(urlencode($category['NAME'])) ?>"
                            class="category-card-link"
                            aria-label="View dishes in <?= htmlspecialchars($category['NAME']) ?> category">

                            <div class="category-card">
                                <?php if ($category['IMAGE']): ?>
                                    <img src="/uploads/categories/<?= htmlspecialchars($category['IMAGE']) ?>"
                                        alt="<?= htmlspecialchars($category['IMAGE']) ?>"
                                        class="category-image">
                                <?php else: ?>
                                    <div class="category-placeholder">
                                        <i class="fa-solid fa-book-open category-placeholder-icon"></i>
                                    </div>
                                <?php endif ?>

                                <div class="category-overlay">
                                    <h3 class="category-title">
                                        <?= htmlspecialchars($category['NAME']) ?>
                                    </h3>
                                </div>
                            </div>

                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php
            $fillColor = '#fff7ed';
            $invert = true;
            $offset = true;

            include BASE_PATH . '/src/views/components/divider-banner.php';
            ?>
        </div>

        <!-- FEATURED KITCHENS -->
        <div class="local-kitchens-section">
            <div class="container">
                <div class="section-header">
                    <?php if ($hasPublicRatings): ?>
                        <h2 class="section-title">Meet Top Rated Local Kitchens</h2>
                        <p class="section-subtitle">Based on customer reviews and ratings</p>
                    <?php elseif ($hasAnyRatings): ?>
                        <h2 class="section-title">Local Kitchens</h2>
                        <p class="section-subtitle">Some kitchens have ratings under review</p>
                    <?php else: ?>
                        <h2 class="section-title">Our Newest Local Kitchens</h2>
                        <p class="section-subtitle">No reviews yet — be the first to rate these kitchens!</p>
                    <?php endif; ?>
                </div>

                <?php if (!empty($kitchens)): ?>

                    <!-- Kitchen Swiper -->
                    <div class="swiper myKitchensSwiper kitchens-swiper">
                        <div class="swiper-wrapper">
                            <?php foreach ($kitchens as $kitchen): ?>
                                <div class="swiper-slide">
                                    <div class="kitchen-card" data-url="/kitchens?view=kitchen&id=<?= $kitchen['KITCHEN_ID'] ?>" style="cursor: pointer;">
                                        <?php if ($isLoggedIn): ?>
                                            <form method="POST" action="/favorites/toggle" class="inline-form">
                                                <input type="hidden" name="reference_id" value="<?= $kitchen['KITCHEN_ID'] ?>">
                                                <input type="hidden" name="reference_type" value="KITCHEN">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                <button type="submit"
                                                    class="btn-favorite-kitchen <?= isset($data['kitchenFavorites'][$kitchen['KITCHEN_ID']]) && $data['kitchenFavorites'][$kitchen['KITCHEN_ID']] ? 'active' : '' ?>">
                                                    <i class="fas fa-heart"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button
                                                type="button"
                                                class="btn-favorite-kitchen"
                                                onclick="openLoginModal('Please login to continue','You need to be logged in to add kitchens to favorites.')">
                                                <i class="fas fa-heart"></i>
                                            </button>
                                        <?php endif; ?>

                                        <!-- Kitchen Image -->
                                        <div class="kitchen-image-wrapper">
                                            <?php if (!empty($kitchen['COVER_IMAGE'])): ?>
                                                <?php if (str_starts_with($kitchen['COVER_IMAGE'], 'http')): ?>
                                                    <img src="<?= htmlspecialchars($kitchen['COVER_IMAGE']) ?>"
                                                        alt="<?= htmlspecialchars($kitchen['KITCHEN_NAME']) ?>"
                                                        class="kitchen-image">
                                                <?php else: ?>
                                                    <img src="/uploads/kitchen/<?= htmlspecialchars($kitchen['COVER_IMAGE']) ?>"
                                                        alt="<?= htmlspecialchars($kitchen['KITCHEN_NAME']) ?>"
                                                        class="kitchen-image">
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div class="kitchen-image-placeholder">
                                                    <i class="fas fa-utensils"></i>
                                                    <span>No Image</span>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Rating Badge -->
                                            <?php if ($kitchen['REVIEW_COUNT'] > 0 && $kitchen['AVG_RATING'] > 0): ?>
                                                <div class="rating-badge">
                                                    <i class="fas fa-star"></i>
                                                    <span class="rating-value"><?= round($kitchen['AVG_RATING'], 1) ?></span>
                                                    <span class="rating-count">(<?= $kitchen['REVIEW_COUNT'] ?>)</span>
                                                </div>
                                            <?php elseif ($kitchen['REVIEW_COUNT'] > 0): ?>
                                                <div class="rating-badge rating-badge--pending">
                                                    <i class="fas fa-clock"></i>
                                                    <span class="rating-text">Pending</span>
                                                </div>
                                            <?php else: ?>
                                                <div class="rating-badge rating-badge--new">
                                                    <i class="fas fa-bolt"></i>
                                                    <span class="rating-text">New</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Kitchen Details -->
                                        <div class="kitchen-details">
                                            <div class="top-info">
                                                <div class="name-owner">
                                                    <h3 class="kitchen-name"><?= htmlspecialchars($kitchen['KITCHEN_NAME']) ?></h3>
                                                    <p class="owner-info">
                                                        <i class="fa-solid fa-user"></i>
                                                        <?= htmlspecialchars($kitchen['OWNER_NAME']) ?>
                                                    </p>
                                                </div>

                                                <?php if (!empty($kitchen['DESCRIPTION'])): ?>
                                                    <p class="kitchen-description">
                                                        <?= strlen($kitchen['DESCRIPTION']) > 120
                                                            ? htmlspecialchars(substr($kitchen['DESCRIPTION'], 0, 117) . '...')
                                                            : htmlspecialchars($kitchen['DESCRIPTION']) ?>
                                                    </p>
                                                <?php endif; ?>

                                                <!-- Kitchen Tags -->
                                                <div class="kitchen-tags">
                                                    <?php if ($kitchen['YEARS_EXPERIENCE']): ?>
                                                        <span class="kitchen-tag">
                                                            <i class="fas fa-clock"></i>
                                                            <?= $kitchen['YEARS_EXPERIENCE'] ?>+ years
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- Footer -->
                                            <div class="kitchen-footer">
                                                <!-- Service Areas -->
                                                <div class="service-areas">
                                                    <i class="fa-solid fa-person-biking"></i>
                                                    <span title="<?= !empty($kitchen['SERVICE_AREAS']) ? htmlspecialchars($kitchen['SERVICE_AREAS']) : '' ?>">
                                                        <?php if (!empty($kitchen['SERVICE_AREAS'])): ?>
                                                            <?php
                                                            $areas = explode(', ', $kitchen['SERVICE_AREAS']);
                                                            if (count($areas) > 4) {
                                                                echo htmlspecialchars(implode(', ', array_slice($areas, 0, 4)) . '...');
                                                            } else {
                                                                echo htmlspecialchars($kitchen['SERVICE_AREAS']);
                                                            }
                                                            ?>
                                                        <?php else: ?>
                                                            N/A
                                                        <?php endif; ?>
                                                    </span>
                                                </div>

                                                <div class="bottom-row">
                                                    <!-- Address -->
                                                    <?php if (!empty($kitchen['ADDRESS'])): ?>
                                                        <p class="kitchen-address"
                                                            title="<?= htmlspecialchars($kitchen['ADDRESS']) ?>">
                                                            <i class="fa-solid fa-location-dot" style="font-size: 13px;"></i>
                                                            <?= strlen($kitchen['ADDRESS']) > 30
                                                                ? htmlspecialchars(substr($kitchen['ADDRESS'], 0, 27) . '...')
                                                                : htmlspecialchars($kitchen['ADDRESS']) ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Swiper Nav -->
                        <div class="swiper-button-prev kitchens-prev"></div>
                        <div class="swiper-button-next kitchens-next"></div>
                        <div class="swiper-pagination kitchens-pagination"></div>
                    </div>

                    <div class="browse-more">
                        <a href="/kitchens" class="browse-btn">Browse More Kitchens</a>
                    </div>

                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-utensils"></i>
                        </div>

                        <h3 class="empty-title">No Kitchens Available Right Now</h3>

                        <p class="empty-subtitle">
                            We’re onboarding new local kitchens.
                            Please check back soon!
                        </p>

                        <div class="empty-actions">
                            <!-- <a href="/kitchens" class="browse-btn">
                            Browse All Kitchens
                        </a> -->

                            <?php if (!$isLoggedIn): ?>
                                <a href="/register" class="secondary-btn">
                                    Become a Seller
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

            <?php
            $fillColor = '#F9FAFB';
            $invert = true;
            $offset = true;

            include BASE_PATH . '/src/views/components/divider-banner.php';
            ?>

        </div>

        <!-- HOW IT WORKS -->
        <div id="how-it-works" class="how-it-works-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">How TiffinCraft Works</h2>
                    <p class="section-subtitle">Getting homemade food has never been easier</p>
                </div>

                <div class="how-steps">
                    <!-- Step 1 -->
                    <div class="how-step">
                        <div class="step-number">1</div>
                        <h3 class="step-title">Browse Local Chefs</h3>
                        <p class="step-text">
                            Explore menus from home chefs in your neighborhood, with photos, ratings, and detailed descriptions.
                        </p>
                    </div>

                    <!-- Step 2 -->
                    <div class="how-step">
                        <div class="step-number">2</div>
                        <h3 class="step-title">Place Your Order</h3>
                        <p class="step-text">
                            Select your favorite dishes, choose delivery time, and checkout securely with multiple payment options.
                        </p>
                    </div>

                    <!-- Step 3 -->
                    <div class="how-step">
                        <div class="step-number">3</div>
                        <h3 class="step-title">Enjoy Homemade Goodness</h3>
                        <p class="step-text">
                            Receive fresh, hot meals delivered to your doorstep and savor authentic homemade flavors.
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

        <!-- TESTIMONIALS -->
        <div id="testimonials" class="testimonials-section">
            <div class="testimonials-container">
                <!-- Section Header -->
                <div class="testimonials-header">
                    <h2 class="testimonials-title">What Our Users Say</h2>
                    <p class="testimonials-subtitle">
                        Loved by both home cooks and food lovers across Bangladesh
                    </p>

                    <!-- Rating Summary -->
                    <?php if (isset($reviewStats) && ($reviewStats['total_reviews'] ?? 0) > 0): ?>
                        <div class="rating-summary">
                            <div class="average-rating">
                                <span class="rating-number"><?= number_format($reviewStats['average_rating'] ?? 0, 1) ?></span>
                                <div class="rating-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?= $i <= round($reviewStats['average_rating'] ?? 0) ? 'active' : '' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <span class="total-reviews">Based on <?= $reviewStats['total_reviews'] ?? 0 ?> reviews</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Reviews Grid -->
                <?php if (!empty($platform_reviews)): ?>
                    <div class="testimonials-grid">
                        <?php
                        $chunks = array_chunk($platform_reviews, 3);
                        foreach ($chunks as $rowIndex => $rowReviews):
                        ?>
                            <div class="testimonials-row <?= $rowIndex === 1 ? 'second-row' : '' ?>">
                                <?php foreach ($rowReviews as $review): ?>
                                    <div class="testimonial-card">
                                        <div class="testimonial-user">
                                            <div class="testimonial-avatar-wrapper">
                                                <?php if (!empty($review['REVIEWER_IMAGE'])): ?>
                                                    <img src="/uploads/profile/<?= htmlspecialchars($review['REVIEWER_IMAGE']) ?>"
                                                        alt="<?= htmlspecialchars($review['REVIEWER_NAME']) ?>"
                                                        class="testimonial-avatar"
                                                        onerror="this.src='/assets/images/default-avatar.png'">
                                                <?php else: ?>
                                                    <div class="testimonial-avatar-placeholder">
                                                        <?= strtoupper(substr($review['REVIEWER_NAME'] ?? 'U', 0, 1)) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="testimonial-user-info">
                                                <h4 class="reviewer-name"><?= htmlspecialchars($review['REVIEWER_NAME']) ?></h4>
                                                <div class="stars">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <span class="<?= $i <= $review['RATING'] ? 'star-filled' : 'star-empty' ?>">★</span>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="review-content">
                                            <p class="review-text">
                                                "<?= htmlspecialchars($review['COMMENTS']) ?>"
                                            </p>
                                            <span class="review-date">
                                                <?= dateFormat($review['REVIEW_DATE']) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- View All Reviews Link -->
                    <!-- <?php if ($totalReviews > 6): ?>
                        <div class="view-all-reviews">
                            <a href="/reviews" class="btn-view-all">
                                View All <?= $totalReviews ?> Reviews
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    <?php endif; ?> -->

                <?php else: ?>
                    <!-- Empty State -->
                    <div class="testimonials-empty">
                        <div class="empty-icon">
                            <i class="fas fa-comment-alt"></i>
                        </div>
                        <h3>No reviews yet</h3>
                        <p>Be the first to share your experience with TiffinCraft!</p>
                    </div>
                <?php endif; ?>

                <!-- Review Form -->
                <div class="review-form-wrapper">
                    <div class="review-form-card">
                        <h3 class="form-title">Share Your Experience</h3>

                        <?php if (!$isLoggedIn): ?>
                            <div class="login-prompt" >
                                <!-- <i class="fas fa-sign-in-alt"></i> -->
                                <p>Please <a href="/login">login</a> to write a review</p>
                            </div>
                        <?php elseif (isset($hasReviewed) && $hasReviewed): ?>
                            <div class="already-reviewed">
                                <i class="fas fa-check-circle" style="color: #10b981; font-size: 48px; margin-bottom: 1rem;"></i>
                                <h4>Thank You for Your Review!</h4>
                                <p>You've already shared your experience with us.</p>
                            </div>
                        <?php else: ?>
                            <form action="/reviews" method="POST" class="review-form">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="reference_type" value="TIFFINCRAFT">

                                <div class="form-group">
                                    <label class="form-label">Your Rating <span class="required">*</span></label>
                                    <div class="star-rating">
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                            <input type="radio"
                                                name="rating"
                                                id="star<?= $i ?>"
                                                value="<?= $i ?>"
                                                class="star-rating-input"
                                                required>
                                            <label for="star<?= $i ?>" class="star-rating-label">
                                                <i class="fas fa-star"></i>
                                            </label>
                                        <?php endfor; ?>
                                    </div>
                                </div>

                                <div class="form-group" style="margin-bottom: 1rem;">
                                    <label for="comments" class="form-label">Your Review <span class="required">*</span></label>
                                    <textarea
                                        name="comments"
                                        id="comments"
                                        rows="4"
                                        class="form-textarea"
                                        placeholder="Tell us about your experience with TiffinCraft..."
                                        required></textarea>
                                </div>

                                <button type="submit" class="form-submit-btn">
                                    <i class="fas fa-paper-plane"></i>
                                    Submit Review
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php
            $fillColor = '#F9FAFB';
            $invert = true;
            $offset = true;

            include BASE_PATH . '/src/views/components/divider-banner.php';
            ?>
        </div>

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
        </div>

        <!-- CTA SECTION -->
        <div class="cta-section">
            <div class="cta-container">
                <h2 class="cta-title">Ready to Experience Homemade Goodness?</h2>

                <p class="cta-subtitle">
                    Join thousands of happy customers enjoying authentic home-cooked meals today
                </p>

                <a href="#explore" class="cta-button">
                    Order Now
                </a>
            </div>

            <?php
            $fillColor = '#FFFBEB';
            $invert = true;
            $offset = true;

            include BASE_PATH . '/src/views/components/divider-banner.php';
            ?>
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