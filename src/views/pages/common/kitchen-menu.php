<?php
$kitchen = $data['kitchen'];
$menuItems = $data['menuItems'];
$reviews = $data['reviews'];
$reviewStats = $data['reviewStats'] ?? ['total' => 0, 'average' => 0, 'breakdown' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0]];
$avgRating = $data['avgRating'] ?? 0;
$reviewCount = $data['reviewCount'] ?? 0;
$userHasOrdered = $data['userHasOrdered'] ?? false;
$userReview = $data['userReview'] ?? null;
$isFavorite = $data['isFavorite'] ?? false;
$itemFavorites = $data['itemFavorites'] ?? [];
$isLoggedIn = $data['isLoggedIn'] ?? false;
$csrfToken = $data['csrfToken'] ?? $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));

function dateFormat($dateString, $format = 'M j, Y g:i A')
{
    if (!$dateString) return '';
    $date = DateTime::createFromFormat('d-M-y h.i.s.u A', $dateString);
    return $date ? $date->format($format) : htmlspecialchars((string)$dateString);
}

include BASE_PATH . '/src/views/components/flash-popup.php';
?>

<main class="kitchen-details-page">
    <section class="kitchen-details-section">
        <!-- Kitchen Header -->
        <div class="kitchen-info-section">
            <div class="container">
                <div class="kitchen-header">
                    <!-- Kitchen Cover Image -->
                    <div class="kitchen-cover">
                        <?php if (!empty($kitchen['COVER_IMAGE'])): ?>
                            <?php if (str_starts_with($kitchen['COVER_IMAGE'], 'http')): ?>
                                <img src="<?= htmlspecialchars($kitchen['COVER_IMAGE']) ?>"
                                    alt="<?= htmlspecialchars($kitchen['KITCHEN_NAME']) ?>"
                                    class="cover-image">
                            <?php else: ?>
                                <img src="/uploads/kitchen/<?= htmlspecialchars($kitchen['COVER_IMAGE']) ?>"
                                    alt="<?= htmlspecialchars($kitchen['KITCHEN_NAME']) ?>"
                                    class="cover-image">
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="cover-placeholder">
                                <i class="fas fa-utensils"></i>
                            </div>
                        <?php endif; ?>

                        <!-- Favorite Button -->
                        <div class="kitchen-favorite-btn">
                            <?php if ($isLoggedIn): ?>
                                <form method="POST" action="/favorites/toggle" class="inline-form">
                                    <input type="hidden" name="reference_id" value="<?= $kitchen['KITCHEN_ID'] ?>">
                                    <input type="hidden" name="reference_type" value="KITCHEN">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <button type="submit" class="btn-favorite-kitchen btn-big <?= $isFavorite ? 'active' : '' ?>">
                                        <i class="fas fa-heart"></i>
                                        <span><?= $isFavorite ? 'Saved' : 'Save' ?></span>
                                    </button>
                                </form>
                            <?php else: ?>
                                <button
                                    type="button"
                                    class="btn-favorite-kitchen btn-big"
                                    onclick="openLoginModal('Please login to continue','You need to be logged in to add kitchens to favorites.')">
                                    <i class="fas fa-heart"></i>
                                    <span>Save</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Kitchen Info -->
                    <div class="kitchen-info">
                        <div class="kitchen-meta">
                            <h1 class="kitchen-name"><?= htmlspecialchars($kitchen['KITCHEN_NAME']) ?></h1>

                            <div class="kitchen-stats">
                                <?php if ($reviewCount > 0): ?>
                                    <a href="#reviews-section" class="kitchen-rating-link">
                                        <div class="kitchen-rating">
                                            <div class="stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?= $i <= round($avgRating) ? 'filled' : '' ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="rating-value"><?= $avgRating ?></span>
                                            <span class="review-count">(<?= $reviewCount ?> reviews)</span>
                                        </div>
                                    </a>
                                <?php endif; ?>

                                <!-- Orders Delivered -->
                                <div class="kitchen-orders">
                                    <i class="fas fa-shipping-fast"></i>
                                    <span><?= $kitchen['ORDERS_DELIVERED'] ?? 0 ?> orders delivered</span>
                                </div>

                                <!-- Experience -->
                                <?php if ($kitchen['YEARS_EXPERIENCE']): ?>
                                    <div class="kitchen-experience">
                                        <i class="fas fa-clock"></i>
                                        <span><?= $kitchen['YEARS_EXPERIENCE'] ?>+ years experience</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Chef/Owner Info -->
                        <div class="owner-info">
                            <div class="owner-avatar">
                                <?php if (!empty($kitchen['OWNER_PROFILE_IMAGE'])): ?>
                                    <?php if (str_starts_with($kitchen['OWNER_PROFILE_IMAGE'], 'http')): ?>
                                        <img src="<?= htmlspecialchars($kitchen['OWNER_PROFILE_IMAGE']) ?>"
                                            alt="<?= htmlspecialchars($kitchen['OWNER_NAME']) ?>">
                                    <?php else: ?>
                                        <img src="/uploads/profile/<?= htmlspecialchars($kitchen['OWNER_PROFILE_IMAGE']) ?>"
                                            alt="<?= htmlspecialchars($kitchen['OWNER_NAME']) ?>">
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="avatar-placeholder">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="owner-details">
                                <h3 class="owner-name">Chef <?= htmlspecialchars($kitchen['OWNER_NAME']) ?></h3>
                                <p class="owner-role">Home Chef & Kitchen Owner</p>
                            </div>
                        </div>

                        <!-- Kitchen Tags -->
                        <div class="kitchen-tags">
                            <?php if ($kitchen['SIGNATURE_DISH']): ?>
                                <span class="kitchen-tag">
                                    <i class="fas fa-star"></i>
                                    Signature: <?= htmlspecialchars($kitchen['SIGNATURE_DISH']) ?>
                                </span>
                            <?php endif; ?>

                            <?php if ($kitchen['AVG_PREP_TIME']): ?>
                                <span class="kitchen-tag">
                                    <i class="fas fa-clock"></i>
                                    Avg. Prep: <?= $kitchen['AVG_PREP_TIME'] ?> mins
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Service Areas -->
                        <?php if (!empty($kitchen['SERVICE_AREAS'])): ?>
                            <div class="service-areas">
                                <h4><i class="fas fa-map-marker-alt"></i> Service Areas</h4>
                                <div class="areas-list">
                                    <?php
                                    $areas = explode(', ', $kitchen['SERVICE_AREAS']);
                                    foreach ($areas as $area):
                                        $trimmedArea = trim($area);
                                        if (!empty($trimmedArea)):
                                    ?>
                                            <a href="/kitchens?location=<?= urlencode($trimmedArea) ?>"
                                                class="area-tag"
                                                title="Filter kitchens in <?= htmlspecialchars($trimmedArea) ?>">
                                                <?= htmlspecialchars($trimmedArea) ?>
                                            </a>
                                    <?php
                                        endif;
                                    endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Kitchen Description -->
                        <?php if (!empty($kitchen['DESCRIPTION'])): ?>
                            <div class="kitchen-description">
                                <h4>About This Kitchen</h4>
                                <p><?= nl2br(htmlspecialchars($kitchen['DESCRIPTION'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menu Section -->
        <div class="menu-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Menu Items</h2>
                    <p class="section-subtitle"><?= count($menuItems) ?> items available</p>
                </div>

                <?php if (!empty($menuItems)): ?>
                    <div class="menu-grid">
                        <?php foreach ($menuItems as $item): ?>
                            <div class="menu-item-card" onclick="window.location.href='/item/<?= $item['ITEM_ID'] ?>'">
                                <!-- Favorite Button -->
                                <?php if ($isLoggedIn): ?>
                                    <form method="POST" action="/favorites/toggle" class="inline-form" onclick="event.stopPropagation()">
                                        <input type="hidden" name="reference_id" value="<?= $item['ITEM_ID'] ?>">
                                        <input type="hidden" name="reference_type" value="ITEM">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <button type="submit" class="btn-favorite-item <?= isset($itemFavorites[$item['ITEM_ID']]) && $itemFavorites[$item['ITEM_ID']] ? 'active' : '' ?>">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button
                                        type="button"
                                        class="btn-favorite-item"
                                        onclick="openLoginModal('Please login to continue','You need to be logged in to add item to favorites.')">
                                        <i class="fas fa-heart"></i>
                                    </button>
                                <?php endif; ?>

                                <!-- Kitchen Badge -->
                                <div class="k-badge" onclick="event.stopPropagation()">
                                    <i class="fas fa-utensils"></i>
                                    <span><?= htmlspecialchars($kitchen['KITCHEN_NAME']) ?></span>
                                </div>

                                <!-- Item Image -->
                                <div class="item-image">
                                    <?php if (!empty($item['ITEM_IMAGE'])): ?>
                                        <img src="/uploads/menu/<?= htmlspecialchars($item['ITEM_IMAGE']) ?>"
                                            alt="<?= htmlspecialchars($item['NAME']) ?>">
                                    <?php else: ?>
                                        <div class="image-placeholder">
                                            <i class="fas fa-utensils"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Item Info -->
                                <div class="item-info">
                                    <h3 class="item-name"><?= htmlspecialchars($item['NAME']) ?></h3>

                                    <?php if (!empty($item['CATEGORY_NAME'])): ?>
                                        <div class="item-category-badge">
                                            <?php foreach (explode(',', $item['CATEGORY_NAME']) as $category): ?>
                                                <div class="category-badge">
                                                    <?= htmlspecialchars(trim($category)) ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <p class="item-description">
                                        <?= strlen($item['DESCRIPTION']) > 100
                                            ? htmlspecialchars(substr($item['DESCRIPTION'], 0, 97) . '...')
                                            : htmlspecialchars($item['DESCRIPTION']) ?>
                                    </p>

                                    <!-- Spice Level -->
                                    <?php if ($item['SPICE_LEVEL']): ?>
                                        <div class="spice-level">
                                            <span>Spice: </span>
                                            <?php for ($i = 1; $i <= 3; $i++): ?>
                                                <i class="fas fa-pepper-hot <?= $i <= $item['SPICE_LEVEL'] ? 'active' : '' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div style="display: flex; align-items: center; justify-content: space-between;">
                                        <div style="display: flex; align-items: center; gap: 1rem;">
                                            <!-- Portion Size -->
                                            <?php if ($item['PORTION_SIZE']): ?>
                                                <div class="portion-size">
                                                    <i class="fas fa-weight"></i>
                                                    <?= htmlspecialchars($item['PORTION_SIZE']) ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($item['DAILY_STOCK']): ?>
                                                <div class="daily-stock">
                                                    <i class="fa-solid fa-boxes-stacked"></i>
                                                    <?= htmlspecialchars($item['DAILY_STOCK']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Rating -->
                                        <div class="item-rating">
                                            <i class="fas fa-star"></i>
                                            <?= round($item['AVG_RATING'] ?? 0, 1) ?>
                                            <span>(<?= $item['REVIEW_COUNT'] ?? 0 ?>)</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Item Footer -->
                                <div class="item-footer">
                                    <div class="item-price">
                                        ৳<?= htmlspecialchars($item['PRICE']) ?>
                                    </div>

                                    <div class="item-actions">
                                        <?php if ($isLoggedIn): ?>
                                            <form method="POST" action="/cart/add" class="inline-form" onclick="event.stopPropagation()">
                                                <input type="hidden" name="dish_id" value="<?= $item['ITEM_ID'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                <button type="submit" class="btn-add-to-cart">
                                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button
                                                type="button"
                                                class="btn-add-to-cart"
                                                onclick="openLoginModal('Please login to continue','You need to be logged in to add item to cart.')">
                                                <i class="fas fa-cart-plus"></i> Add to Cart
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-menu">
                        <i class="fas fa-utensils"></i>
                        <h3>No Menu Items Available</h3>
                        <p>This kitchen hasn't added any menu items yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Reviews Section -->
        <div class="reviews-section" id="reviews-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Customer Reviews</h2>
                    <p class="section-subtitle">What customers say about <?= htmlspecialchars($kitchen['KITCHEN_NAME']) ?></p>
                </div>

                <div style="display: flex; flex-direction: column;">
                    <div style="display: flex; flex-direction: column; width: 100%; flex: 1;">
                        <!-- Rating Summary & Write Review Button -->
                        <div class="reviews-header">
                            <div class="rating-summary-large">
                                <div class="average-rating">
                                    <span class="rating-number"><?= number_format($avgRating, 1) ?></span>
                                    <div class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?= $i <= round($avgRating) ? 'active' : '' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="total-reviews"><?= $reviewCount ?> reviews</span>
                                </div>
                            </div>

                            <!-- Write Review Button -->
                            <?php if ($isLoggedIn): ?>
                                <?php if ($userHasOrdered): ?>
                                    <?php if ($userReview): ?>
                                        <button onclick="openKitchenReviewForm('edit', <?= $kitchen['KITCHEN_ID'] ?>, <?= $userReview['REVIEW_ID'] ?>, <?= $userReview['RATING'] ?>, '<?= htmlspecialchars(addslashes($userReview['COMMENTS'] ?? '')) ?>')"
                                            class="btn-write-review">
                                            <i class="fas fa-edit"></i> Edit Your Review
                                        </button>
                                    <?php else: ?>
                                        <button onclick="openKitchenReviewForm('create', <?= $kitchen['KITCHEN_ID'] ?>)"
                                            class="btn-write-review">
                                            <i class="fas fa-star"></i> Write a Review
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="btn-write-review disabled" disabled>
                                        <i class="fas fa-shopping-cart"></i> Order to Review
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <button onclick="openLoginModal('Please login to continue','You need to be logged in to write a review.')" class="btn-write-review">
                                    <i class="fas fa-sign-in-alt"></i> Login to Review
                                </button>
                            <?php endif; ?>
                        </div>

                        <!-- Rating Breakdown -->
                        <?php if ($reviewStats['total'] > 0): ?>
                            <div class="rating-breakdown">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <div class="rating-row">
                                        <span class="rating-label"><?= $i ?> stars</span>
                                        <div class="rating-bar">
                                            <div class="rating-fill" style="width: <?= ($reviewStats['breakdown'][$i] ?? 0) / max($reviewStats['total'], 1) * 100 ?>%"></div>
                                        </div>
                                        <span class="rating-count"><?= $reviewStats['breakdown'][$i] ?? 0 ?></span>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Reviews List -->
                    <?php if (!empty($reviews)): ?>
                        <div class="reviews-list-full" style="flex: 1;">
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-card">
                                    <div class="reviewer-avatar">
                                        <?php if (!empty($review['REVIEWER_IMAGE'])): ?>
                                            <img src="/uploads/profile/<?= htmlspecialchars($review['REVIEWER_IMAGE']) ?>"
                                                alt="<?= htmlspecialchars($review['REVIEWER_NAME']) ?>">
                                        <?php else: ?>
                                            <div class="avatar-placeholder">
                                                <?= strtoupper(substr($review['REVIEWER_NAME'] ?? 'U', 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="review-content">
                                        <div class="review-header">
                                            <span class="reviewer-name"><?= htmlspecialchars($review['REVIEWER_NAME']) ?></span>
                                            <span class="review-date"><?= dateFormat($review['REVIEW_DATE'] ?? null) ?></span>
                                        </div>
                                        <div class="review-rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?= $i <= $review['RATING'] ? 'active' : '' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <?php if (!empty($review['COMMENTS'])): ?>
                                            <p class="review-comment"><?= nl2br(htmlspecialchars($review['COMMENTS'])) ?></p>
                                        <?php endif; ?>

                                        <?php if ($isLoggedIn && isset($review['REVIEWER_ID']) && $review['REVIEWER_ID'] == ($_SESSION['user_id'] ?? 0)): ?>
                                            <div class="review-actions">
                                                <button onclick="openKitchenReviewForm('edit', <?= $kitchen['KITCHEN_ID'] ?>, <?= $review['REVIEW_ID'] ?>, <?= $review['RATING'] ?>, '<?= htmlspecialchars(addslashes($review['COMMENTS'] ?? '')) ?>')"
                                                    class="btn-edit-review">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (count($reviews) > 6): ?>
                            <div class="view-all-reviews">
                                <a href="/kitchen/reviews?kitchen=<?= $kitchen['KITCHEN_ID'] ?>" class="btn-view-all">
                                    View All <?= $reviewCount ?> Reviews
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="no-reviews-large">
                            <i class="fas fa-comment-alt"></i>
                            <h3>No Reviews Yet</h3>
                            <p>Be the first to share your experience with this kitchen!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Kitchen Review Form Modal -->
    <div class="modal-overlay" id="kitchenReviewFormModal">
        <div class="modal review-form-modal">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-star"></i>
                    <span id="kitchenReviewFormTitle">Write a Review</span>
                </h3>
                <button type="button" class="modal-close" onclick="closeKitchenReviewFormModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="kitchenReviewForm" method="POST" action="/kitchen/review">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="reference_type" value="KITCHEN">
                    <input type="hidden" name="reference_id" id="kitchenReviewReferenceId" value="">
                    <input type="hidden" name="review_id" id="kitchenReviewId" value="">
                    <input type="hidden" name="action" id="kitchenReviewAction" value="">

                    <div class="form-group">
                        <label class="form-label">
                            Rating <span class="required">*</span>
                        </label>
                        <div class="star-rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio"
                                    id="kitchen_star<?= $i ?>"
                                    name="rating"
                                    value="<?= $i ?>"
                                    class="star-rating-input">
                                <label for="kitchen_star<?= $i ?>" class="star-rating-label">
                                    <i class="fas fa-star"></i>
                                </label>
                            <?php endfor; ?>
                        </div>
                        <div class="rating-labels">
                            <span>Poor</span>
                            <span>Excellent</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="kitchenReviewComments" class="form-label">Comments</label>
                        <textarea
                            id="kitchenReviewComments"
                            name="comments"
                            class="form-textarea"
                            rows="4"
                            placeholder="Share your experience with this kitchen... (Optional)"></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeKitchenReviewFormModal()">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Submit Review
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Login Modal -->
    <div class="modal-overlay" id="loginModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-user-circle"></i>
                    Login Required
                </h3>
                <button type="button" class="modal-close" onclick="closeLoginModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="login-modal-content">
                    <div class="login-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h4 id="loginModalTitle">Please login to continue</h4>
                    <p id="loginModalMessage">
                        You need to be logged in to perform this action.
                    </p>
                    <div class="login-actions">
                        <a href="/login" class="btn btn-primary">Login</a>
                        <a href="/register" class="btn btn-secondary">Register</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    // Kitchen Review Form Functions
    function openKitchenReviewForm(action, kitchenId, reviewId = null, rating = null, comments = '') {
        const modal = document.getElementById('kitchenReviewFormModal');
        const title = document.getElementById('kitchenReviewFormTitle');
        const form = document.getElementById('kitchenReviewForm');

        // Set form values
        document.getElementById('kitchenReviewReferenceId').value = kitchenId;
        document.getElementById('kitchenReviewAction').value = action;

        // Reset form
        form.reset();

        // Reset star colors
        document.querySelectorAll('#kitchenReviewFormModal .star-rating-label').forEach(label => {
            label.style.color = '#ddd';
        });

        if (action === 'edit' && reviewId) {
            document.getElementById('kitchenReviewId').value = reviewId;
            title.textContent = 'Edit Your Review';

            // Set rating
            if (rating) {
                const ratingInput = document.getElementById('kitchen_star' + rating);
                if (ratingInput) {
                    ratingInput.checked = true;

                    // Update star colors
                    let label = ratingInput.nextElementSibling;
                    while (label) {
                        label.style.color = '#ffc107';
                        label = label.nextElementSibling;
                    }
                }
            }

            // Set comments
            document.getElementById('kitchenReviewComments').value = comments;
        } else {
            document.getElementById('kitchenReviewId').value = '';
            title.textContent = 'Write a Review';
        }

        // Show modal
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeKitchenReviewFormModal() {
        const modal = document.getElementById('kitchenReviewFormModal');
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    // Login Modal Functions
    function openLoginModal(titleText, messageText) {
        const modal = document.getElementById('loginModal');
        const titleEl = document.getElementById('loginModalTitle');
        const messageEl = document.getElementById('loginModalMessage');

        if (titleText) titleEl.textContent = titleText;
        if (messageText) messageEl.textContent = messageText;

        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeLoginModal() {
        const modal = document.getElementById('loginModal');
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    // Star rating hover effect for kitchen review form
    document.addEventListener('DOMContentLoaded', function() {
        const starLabels = document.querySelectorAll('#kitchenReviewFormModal .star-rating-label');

        starLabels.forEach(label => {
            label.addEventListener('mouseover', function() {
                let current = this;
                while (current) {
                    current.style.color = '#ffc107';
                    current = current.nextElementSibling;
                }
            });

            label.addEventListener('mouseout', function() {
                const input = this.previousElementSibling;
                if (!input || !input.checked) {
                    let current = this;
                    while (current) {
                        current.style.color = '#ddd';
                        current = current.nextElementSibling;
                    }
                }
            });
        });

        // Modal close on overlay click
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                    document.body.style.overflow = 'auto';
                }
            });
        });

        // Escape key close
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                    modal.classList.remove('active');
                    document.body.style.overflow = 'auto';
                });
            }
        });

        // Menu item card click
        document.querySelectorAll('.menu-item-card').forEach(card => {
            card.addEventListener('click', function(e) {
                // Don't trigger if clicking on form or button
                if (e.target.tagName === 'FORM' || e.target.tagName === 'BUTTON' ||
                    e.target.closest('form') || e.target.closest('button')) {
                    return;
                }
                const url = this.getAttribute('onclick')?.match(/window\.location\.href='([^']+)'/)?.[1];
                if (url) {
                    window.location.href = url;
                }
            });
        });
    });
</script>