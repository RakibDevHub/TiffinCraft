<?php
$pageTitle = ($data['item']['NAME'] ?? 'Item') . ' - TiffinCraft';

// Extract data with defaults
$item = $data['item'] ?? [];
$reviews = $data['reviews'] ?? [];
$reviewStats = $data['reviewStats'] ?? [
    'total' => 0,
    'average' => 0,
    'breakdown' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0]
];
$userHasOrdered = $data['userHasOrdered'] ?? false;
$userReview = $data['userReview'] ?? null;
$relatedItems = $data['relatedItems'] ?? [];
$isFavorite = $data['isFavorite'] ?? false;
$isLoggedIn = $data['isLoggedIn'] ?? false;

// Helper function for CLOB handling
function getClobValue($clob)
{
    if (is_null($clob)) return '';
    if (is_object($clob) && method_exists($clob, 'load')) {
        return $clob->load();
    }
    return (string) $clob;
}

include BASE_PATH . '/src/views/components/flash-popup.php';
?>

<main class="item-details-page">
    <div class="container">

        <!-- Item Details Grid -->
        <div class="item-details-grid">
            <!-- ========== LEFT COLUMN - IMAGE & KITCHEN ========== -->
            <div class="item-image-section">
                <!-- Main Image -->
                <div class="main-image">
                    <?php if (!empty($item['ITEM_IMAGE'])): ?>
                        <img src="/uploads/menu/<?= htmlspecialchars($item['ITEM_IMAGE']) ?>"
                            alt="<?= htmlspecialchars($item['NAME'] ?? '') ?>">
                    <?php else: ?>
                        <div class="image-placeholder">
                            <i class="fas fa-utensils"></i>
                        </div>
                    <?php endif; ?>

                    <!-- Favorite Button -->
                    <?php if ($isLoggedIn): ?>
                        <form method="POST" action="/favorites/toggle" class="favorite-form">
                            <input type="hidden" name="reference_id" value="<?= $item['ITEM_ID'] ?? '' ?>">
                            <input type="hidden" name="reference_type" value="ITEM">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <button type="submit" class="btn-favorite <?= $isFavorite ? 'active' : '' ?>">
                                <i class="fas fa-heart"></i>
                            </button>
                        </form>
                    <?php else: ?>
                        <button type="button" class="btn-favorite" onclick="openLoginModal()">
                            <i class="fas fa-heart"></i>
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Kitchen Info Card -->
                <?php if (!empty($item['KITCHEN_NAME'])): ?>
                    <div class="kitchen-info-card">
                        <div class="kitchen-header" style="padding: 1rem; color: #fff; background-color: #f97416da;">
                            <i class="fas fa-utensils"></i>
                            <h3 style="color: #fff; font-weight: 500;">Kitchen Details</h3>
                        </div>
                        <div class="kitchen-details">
                            <h4><?= htmlspecialchars($item['KITCHEN_NAME']) ?></h4>
                            <?php if (!empty($item['KITCHEN_ID'])): ?>
                                <a href="/kitchens?view=kitchen&id=<?= $item['KITCHEN_ID'] ?>" class="view-kitchen-link">
                                    View Kitchen <i class="fas fa-arrow-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ========== RIGHT COLUMN - DETAILS & REVIEWS ========== -->
            <div class="item-info-section">
                <!-- Item Header -->
                <div class="item-header">
                    <h1 class="item-title"><?= htmlspecialchars($item['NAME'] ?? '') ?></h1>

                    <!-- Rating Summary -->
                    <div class="rating-summary-large">
                        <div class="average-rating">
                            <span class="rating-number"><?= number_format($reviewStats['average'] ?? 0, 1) ?></span>
                            <div class="rating-stars" style="width: min-content;">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?= $i <= round($reviewStats['average'] ?? 0) ? 'active' : '' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="total-reviews"><?= $reviewStats['total'] ?? 0 ?> reviews</span>
                        </div>
                    </div>
                </div>

                <!-- Price & Stock -->
                <div class="price-stock-section">
                    <div class="item-price-large">
                        ৳<?= htmlspecialchars($item['PRICE'] ?? '0') ?>
                    </div>
                    <div class="stock-info">
                        <i class="fa-solid fa-boxes-stacked"></i>
                        Stock: <?= htmlspecialchars($item['DAILY_STOCK'] ?? '0') ?>
                    </div>
                </div>

                <!-- Item Details List -->
                <div class="item-details-list">
                    <?php if (!empty($item['CATEGORY_NAME'])): ?>
                        <div class="detail-item">
                            <span class="detail-label">Category:</span>
                            <span class="detail-value"><?= htmlspecialchars($item['CATEGORY_NAME']) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($item['PORTION_SIZE'])): ?>
                        <div class="detail-item">
                            <span class="detail-label">Portion Size:</span>
                            <span class="detail-value"><?= htmlspecialchars($item['PORTION_SIZE']) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($item['SPICE_LEVEL'])): ?>
                        <div class="detail-item">
                            <span class="detail-label">Spice Level:</span>
                            <span class="detail-value spice-indicator">
                                <?php for ($i = 1; $i <= 3; $i++): ?>
                                    <i class="fas fa-pepper-hot <?= $i <= ($item['SPICE_LEVEL'] ?? 0) ? 'active' : '' ?>"></i>
                                <?php endfor; ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($item['PREP_TIME'])): ?>
                        <div class="detail-item">
                            <span class="detail-label">Prep Time:</span>
                            <span class="detail-value"><?= htmlspecialchars($item['PREP_TIME']) ?> mins</span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($item['CATEGORIES'])): ?>
                        <div class="detail-item">
                            <span class="detail-label">Categories</span>
                            <span class="detail-value">
                                <div class="item-category-badge" style="margin: 0;">
                                    <?php foreach (explode(',', $item['CATEGORIES']) as $category): ?>
                                        <div class="category-badge" style="font-size: 14px;">
                                            <?= htmlspecialchars(trim($category)) ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </span>
                        </div>
                    <?php endif; ?>


                </div>

                <!-- Description -->
                <?php if (!empty($item['DESCRIPTION'])): ?>
                    <div class="item-description-section">
                        <h3>Description</h3>
                        <p class="item-description">
                            <?= htmlspecialchars($item['DESCRIPTION']) ?>
                        </p>
                    </div>
                <?php endif; ?>



                <!-- Add to Cart -->
                <div class="add-to-cart-section">
                    <?php if ($isLoggedIn): ?>
                        <!-- <form method="POST" action="/cart/add" class="add-to-cart-form"> -->

                        <a href="/cart/add" class="btn-add-to-cart btn">
                            <i class="fas fa-cart-plus"></i> Add to Cart
                        </a>
                        <!-- </form> -->
                    <?php else: ?>
                        <button type="button" class="btn-add-to-cart btn" onclick="openLoginModal()">
                            <i class="fas fa-cart-plus"></i> Login to Add to Cart
                        </button>
                    <?php endif; ?>
                </div>

                <!-- ========== REVIEWS SECTION ========== -->
                <div class="reviews-section">
                    <div class="reviews-header">
                        <h2>Customer Reviews</h2>

                        <!-- Write Review Button -->
                        <?php if ($isLoggedIn): ?>
                            <?php if ($userHasOrdered): ?>
                                <?php if ($userReview): ?>
                                    <button onclick="showReviewForm('edit', <?= $item['ITEM_ID'] ?? 'null' ?>, <?= $userReview['REVIEW_ID'] ?? 'null' ?>, <?= $userReview['RATING'] ?? 0 ?>, '<?= htmlspecialchars(addslashes(getClobValue($userReview['COMMENTS'] ?? ''))) ?>')"
                                        class="btn-write-review">
                                        <i class="fas fa-edit"></i> Edit Your Review
                                    </button>
                                <?php else: ?>
                                    <button onclick="showReviewForm('create', <?= $item['ITEM_ID'] ?? 'null' ?>)"
                                        class="btn-write-review">
                                        <i class="fas fa-star"></i> Write a Review
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <button onclick="openLoginModal()" class="btn-write-review">
                                <i class="fas fa-sign-in-alt"></i> Login to Review
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Rating Breakdown -->
                    <?php if (($reviewStats['total'] ?? 0) > 0): ?>
                        <div class="rating-breakdown">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <div class="rating-row">
                                    <span class="rating-label"><?= $i ?> stars</span>
                                    <div class="rating-bar">
                                        <div class="rating-fill" style="width: <?= ($reviewStats['breakdown'][$i] ?? 0) / max($reviewStats['total'] ?? 1, 1) * 100 ?>%"></div>
                                    </div>
                                    <span class="rating-count"><?= $reviewStats['breakdown'][$i] ?? 0 ?></span>
                                </div>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Reviews List -->
                    <div class="reviews-list-full">
                        <?php if (!empty($reviews)): ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-card">
                                    <div class="reviewer-avatar">
                                        <?php if (!empty($review['reviewer_image'])): ?>
                                            <img src="/uploads/profile/<?= htmlspecialchars($review['reviewer_image']) ?>"
                                                alt="<?= htmlspecialchars($review['reviewer_name'] ?? '') ?>">
                                        <?php else: ?>
                                            <div class="avatar-placeholder">
                                                <?= strtoupper(substr($review['reviewer_name'] ?? 'U', 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="review-content">
                                        <div class="review-header">
                                            <span class="reviewer-name"><?= htmlspecialchars($review['reviewer_name'] ?? 'Anonymous') ?></span>
                                            <span class="review-date"><?= $review['formatted_date'] ?? '' ?></span>
                                        </div>
                                        <div class="review-rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?= $i <= ($review['rating'] ?? 0) ? 'active' : '' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <?php if (!empty($review['comments'])): ?>
                                            <p class="review-comment"><?= nl2br(htmlspecialchars(getClobValue($review['comments']))) ?></p>
                                        <?php endif; ?>

                                        <?php if ($isLoggedIn && isset($review['reviewer_id']) && $review['reviewer_id'] == ($_SESSION['user_id'] ?? 0)): ?>
                                            <div class="review-actions">
                                                <button onclick="showReviewForm('edit', <?= $item['ITEM_ID'] ?? 'null' ?>, <?= $review['review_id'] ?? 'null' ?>, <?= $review['rating'] ?? 0 ?>, '<?= htmlspecialchars(addslashes(getClobValue($review['comments'] ?? ''))) ?>')"
                                                    class="btn-edit-review">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-reviews-large">
                                <i class="fas fa-comment-alt"></i>
                                <h3>No reviews yet</h3>
                                <p>Be the first to share your experience with this dish!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== RELATED ITEMS SECTION ========== -->
        <?php if (!empty($relatedItems)): ?>
            <div class="related-items-section">
                <h2>You Might Also Like</h2>
                <div class="related-items-grid">
                    <?php foreach ($relatedItems as $related): ?>
                        <div class="menu-item-card related-item-card" data-url="/dishes?view=item&id=<?= $related['ITEM_ID'] ?>" style="cursor: pointer;">
                            <!-- Favorite Button -->
                            <?php if ($isLoggedIn): ?>
                                <form method="POST" action="/favorites/toggle" class="inline-form">
                                    <input type="hidden" name="reference_id" value="<?= $related['ITEM_ID'] ?? '' ?>">
                                    <input type="hidden" name="reference_type" value="ITEM">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <button type="submit" class="btn-favorite-item <?= isset($data['itemFavorites'][$related['ITEM_ID']]) && $data['itemFavorites'][$related['ITEM_ID']] ? 'active' : '' ?>">
                                        <i class="fas fa-heart"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <button type="button" class="btn-favorite-item" onclick="openLoginModal()">
                                    <i class="fas fa-heart"></i>
                                </button>
                            <?php endif; ?>

                            <!-- Kitchen Badge -->
                            <div class="k-badge">
                                <i class="fas fa-utensils"></i>
                                <a href="/kitchens?view=kitchen&id=<?= $related['KITCHEN_ID'] ?? '' ?>">
                                    <?= htmlspecialchars($related['KITCHEN_NAME'] ?? 'Unknown Kitchen') ?>
                                </a>
                            </div>

                            <!-- Item Image -->
                            <div class="item-image">
                                <?php if (!empty($related['ITEM_IMAGE'])): ?>
                                    <img src="/uploads/menu/<?= htmlspecialchars($related['ITEM_IMAGE']) ?>"
                                        alt="<?= htmlspecialchars($related['NAME'] ?? '') ?>">
                                <?php else: ?>
                                    <div class="image-placeholder">
                                        <i class="fas fa-utensils"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Item Info -->
                            <div class="item-info">
                                <h3 class="item-name"><?= htmlspecialchars($related['NAME'] ?? '') ?></h3>

                                <?php if (!empty($related['CATEGORY_NAME'])): ?>
                                    <div class="item-category-badge">
                                        <?php foreach (explode(',', $related['CATEGORY_NAME']) as $category): ?>
                                            <div class="category-badge">
                                                <?= htmlspecialchars(trim($category)) ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <p class="item-description">
                                    <?= !empty($related['DESCRIPTION'])
                                        ? (strlen($related['DESCRIPTION']) > 40
                                            ? htmlspecialchars(substr($related['DESCRIPTION'], 0, 37) . '...')
                                            : htmlspecialchars($related['DESCRIPTION']))
                                        : '' ?>
                                </p>

                                <!-- Spice Level -->
                                <?php if (!empty($related['SPICE_LEVEL'])): ?>
                                    <div class="spice-level">
                                        <span>Spice: </span>
                                        <?php for ($i = 1; $i <= 3; $i++): ?>
                                            <i class="fas fa-pepper-hot <?= $i <= ($related['SPICE_LEVEL'] ?? 0) ? 'active' : '' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                <?php endif; ?>

                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <!-- Portion Size -->
                                        <?php if (!empty($related['PORTION_SIZE'])): ?>
                                            <div class="portion-size">
                                                <i class="fas fa-weight"></i>
                                                <?= htmlspecialchars($related['PORTION_SIZE']) ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="daily-stock">
                                            <i class="fa-solid fa-boxes-stacked"></i>
                                            <?= htmlspecialchars($related['DAILY_STOCK'] ?? '0') ?>
                                        </div>
                                    </div>

                                    <!-- Rating -->
                                    <div class="item-rating">
                                        <i class="fas fa-star"></i>
                                        <?= round($related['AVG_RATING'] ?? 0, 1) ?>
                                        <span>(<?= $related['REVIEW_COUNT'] ?? 0 ?>)</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Item Footer -->
                            <div class="item-footer">
                                <div class="item-price">
                                    ৳<?= htmlspecialchars($related['PRICE'] ?? '0') ?>
                                </div>

                                <div class="item-actions">
                                    <?php if ($isLoggedIn): ?>
                                        <form method="POST" action="/cart/add" class="inline-form" onclick="event.stopPropagation()">
                                            <input type="hidden" name="dish_id" value="<?= $related['ITEM_ID'] ?? '' ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <button type="submit" class="btn-add-to-cart">
                                                <i class="fas fa-cart-plus"></i> Add to Cart
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button type="button" class="btn-add-to-cart" onclick="openLoginModal()">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- ========== REVIEW FORM MODAL ========== -->
<div class="modal-overlay" id="reviewFormModal">
    <div class="modal review-form-modal">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-star"></i>
                <span id="reviewFormTitle">Write a Review</span>
            </h3>
            <button type="button" class="modal-close" onclick="closeReviewFormModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="reviewForm" method="POST" action="/dishe/review">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="reference_type" value="ITEM">
                <input type="hidden" name="reference_id" id="reviewReferenceId" value="">
                <input type="hidden" name="review_id" id="reviewId" value="">
                <input type="hidden" name="action" id="reviewAction" value="">

                <div class="form-group">
                    <label class="form-label">
                        Rating <span class="required">*</span>
                    </label>
                    <div class="star-rating">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio"
                                id="star<?= $i ?>"
                                name="rating"
                                value="<?= $i ?>"
                                class="star-rating-input">
                            <label for="star<?= $i ?>" class="star-rating-label">
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
                    <label for="reviewComments" class="form-label">Comments</label>
                    <textarea
                        id="reviewComments"
                        name="comments"
                        class="form-textarea"
                        rows="4"
                        placeholder="Share your experience with this dish... (Optional)"></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeReviewFormModal()">
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

<!-- ========== LOGIN MODAL ========== -->
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
                <h4>Please login to continue</h4>
                <p>You need to be logged in to perform this action.</p>
                <div class="login-actions">
                    <a href="/login" class="btn btn-primary">Login</a>
                    <a href="/register" class="btn btn-secondary">Register</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========== JAVASCRIPT ========== -->
<script>
    // Review Form Functions
    function showReviewForm(action, itemId, reviewId = null, rating = null, comments = '') {
        const modal = document.getElementById('reviewFormModal');
        const title = document.getElementById('reviewFormTitle');

        document.getElementById('reviewReferenceId').value = itemId;
        document.getElementById('reviewAction').value = action;

        document.getElementById('reviewForm').reset();

        // Reset star colors
        document.querySelectorAll('.star-rating-label').forEach(label => {
            label.style.color = '#ddd';
        });

        if (action === 'edit' && reviewId) {
            document.getElementById('reviewId').value = reviewId;
            title.textContent = 'Edit Your Review';

            if (rating) {
                const ratingInput = document.getElementById('star' + rating);
                if (ratingInput) {
                    ratingInput.checked = true;
                    let label = ratingInput.nextElementSibling;
                    while (label) {
                        label.style.color = '#ffc107';
                        label = label.nextElementSibling;
                    }
                }
            }

            document.getElementById('reviewComments').value = comments;
        } else {
            document.getElementById('reviewId').value = '';
            title.textContent = 'Write a Review';
        }

        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeReviewFormModal() {
        const modal = document.getElementById('reviewFormModal');
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    // Login Modal Functions
    function openLoginModal() {
        const modal = document.getElementById('loginModal');
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeLoginModal() {
        const modal = document.getElementById('loginModal');
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    // Quantity Selector
    document.addEventListener('DOMContentLoaded', function() {
        const minusBtn = document.querySelector('.qty-btn.minus');
        const plusBtn = document.querySelector('.qty-btn.plus');
        const qtyInput = document.querySelector('.qty-input');

        if (minusBtn && plusBtn && qtyInput) {
            minusBtn.addEventListener('click', function() {
                let val = parseInt(qtyInput.value);
                if (val > 1) {
                    qtyInput.value = val - 1;
                }
            });

            plusBtn.addEventListener('click', function() {
                let val = parseInt(qtyInput.value);
                let max = parseInt(qtyInput.max);
                if (val < max) {
                    qtyInput.value = val + 1;
                }
            });
        }

        // Star Rating Hover Effect
        const starLabels = document.querySelectorAll('.star-rating-label');
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

        // Modal Close on Overlay Click
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                    document.body.style.overflow = 'auto';
                }
            });
        });

        // Escape Key Close
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                    modal.classList.remove('active');
                    document.body.style.overflow = 'auto';
                });
            }
        });

        document.querySelectorAll('.menu-item-card').forEach(card => {
            card.addEventListener('click', function() {
                window.location.href = this.dataset.url;
            });
        });
    });
</script>