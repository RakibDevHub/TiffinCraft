<?php
$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 50;
$totalReviews = $data['totalReviews'] ?? 0;
$reviewStats = $data['reviewStats'] ?? [];
$totalPages = ceil($totalReviews / $limit);
$reviewsData = $data['reviewsData'] ?? [];
$user = $data['currentUser'];
$csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));

// Filter reviews by type
$kitchenReviews = array_filter($reviewsData, function ($review) {
    return $review['REFERENCE_TYPE'] === 'KITCHEN';
});

$itemReviews = array_filter($reviewsData, function ($review) {
    return $review['REFERENCE_TYPE'] === 'ITEM';
});

$platformReviews = array_filter($reviewsData, function ($review) {
    return $review['REFERENCE_TYPE'] === 'TIFFINCRAFT';
});

function formatDate($dateString, $format = 'M j, Y g:i A')
{
    if (!$dateString) return '';
    $date = DateTime::createFromFormat('d-M-y h.i.s.u A', $dateString);
    return $date ? $date->format($format) : htmlspecialchars($dateString);
}

function generateStarRating($rating)
{
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<i class="fas fa-star active"></i>';
        } else {
            $stars .= '<i class="fas fa-star"></i>';
        }
    }
    return $stars;
}

function getReviewTypeBadge($referenceType, $referenceName = '')
{
    switch ($referenceType) {
        case 'KITCHEN':
            return '<span class="type-badge kitchen"><i class="fas fa-store"></i> Kitchen Review</span>';
        case 'ITEM':
            return '<span class="type-badge item"><i class="fas fa-utensils"></i> ' . htmlspecialchars($referenceName) . '</span>';
        case 'TIFFINCRAFT':
            return '<span class="type-badge platform"><i class="fas fa-globe"></i> Platform Review</span>';
        default:
            return '<span class="type-badge">' . htmlspecialchars($referenceType) . '</span>';
    }
}

function getStatusBadge($status)
{
    $statusClasses = [
        'PUBLIC' => 'status-badge public',
        'HIDDEN' => 'status-badge hidden',
        'REPORTED' => 'status-badge reported'
    ];

    $class = $statusClasses[$status] ?? 'status-badge';
    $icon = $status === 'PUBLIC' ? 'check-circle' : ($status === 'HIDDEN' ? 'eye-slash' : 'flag');

    return '<span class="' . $class . '"><i class="fas fa-' . $icon . '"></i> ' . htmlspecialchars($status) . '</span>';
}

include BASE_PATH . '/src/views/components/flash-popup.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Reviews Management</h1>
    <p class="page-subtitle">Manage customer reviews, moderate content, and track feedback across the platform</p>
</div>

<!-- Review Statistics Grid -->
<div class="stats-grid-wrapper">
    <div class="stats-grid">
        <!-- Total Reviews Card -->
        <div class="stat-card">
            <div class="stat-icon-wrapper" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                <i class="fas fa-comments"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($reviewStats['total_reviews'] ?? 0) ?></div>
                <div class="stat-label">Total Reviews</div>
                <div class="stat-trend">
                    <span class="trend-badge">
                        <i class="fas fa-store"></i> <?= $reviewStats['kitchen_reviews'] ?? 0 ?> kitchen
                    </span>
                    <span class="trend-badge">
                        <i class="fas fa-utensils"></i> <?= $reviewStats['item_reviews'] ?? 0 ?> items
                    </span>
                    <span class="trend-badge">
                        <i class="fas fa-globe"></i> <?= $reviewStats['platform_reviews'] ?? 0 ?> platform
                    </span>
                </div>
            </div>
        </div>

        <!-- Average Rating Card -->
        <div class="stat-card">
            <div class="stat-icon-wrapper" style="background: rgba(79, 70, 229, 0.1); color: #4f46e5;">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($reviewStats['average_rating'] ?? 0, 1) ?></div>
                <div class="stat-label">Average Rating</div>
                <div class="stat-trend">
                    <div class="rating-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?= $i <= round($reviewStats['average_rating'] ?? 0) ? 'active' : '' ?>"></i>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reported Reviews Card -->
        <div class="stat-card">
            <div class="stat-icon-wrapper" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                <i class="fas fa-flag"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($reviewStats['reported_reviews'] ?? 0) ?></div>
                <div class="stat-label">Reported Reviews</div>
                <div class="stat-trend">
                    <span class="trend-badge warning">
                        <i class="fas fa-clock"></i> Needs action
                    </span>
                </div>
            </div>
        </div>

        <!-- Hidden Reviews Card -->
        <div class="stat-card">
            <div class="stat-icon-wrapper" style="background: rgba(107, 114, 128, 0.1); color: #6b7280;">
                <i class="fas fa-eye-slash"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($reviewStats['hidden_reviews'] ?? 0) ?></div>
                <div class="stat-label">Hidden Reviews</div>
                <div class="stat-trend">
                    <span class="trend-badge">
                        <i class="fas fa-shield-alt"></i> Moderated
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Rating Distribution Card -->
<div class="dashboard-card">
    <div class="card-header">
        <div class="header-left">
            <i class="fas fa-chart-pie"></i>
            <h3>Rating Distribution</h3>
        </div>
        <span class="badge badge-info"><?= $reviewStats['total_reviews'] ?? 0 ?> total reviews</span>
    </div>
    <div class="card-body">
        <div class="rating-distribution">
            <?php
            $starMap = [
                5 => 'five_star',
                4 => 'four_star',
                3 => 'three_star',
                2 => 'two_star',
                1 => 'one_star'
            ];
            for ($i = 5; $i >= 1; $i--):
                $count = $reviewStats[$starMap[$i]] ?? 0;
                $percentage = $reviewStats['total_reviews'] > 0 ? ($count / $reviewStats['total_reviews']) * 100 : 0;
            ?>
                <div class="rating-row">
                    <div class="rating-label">
                        <span class="star-count"><?= $i ?></span>
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="rating-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= $percentage ?>%"></div>
                        </div>
                    </div>
                    <div class="rating-count">
                        <span class="count"><?= $count ?></span>
                        <span class="percentage">(<?= round($percentage) ?>%)</span>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- Reviews Dashboard Card -->
<div class="dashboard-card">
    <div class="card-header">
        <div class="header-left">
            <i class="fas fa-comment-dots"></i>
            <h3>All Reviews</h3>
        </div>

        <div class="header-tabs">
            <button class="tab-btn active" data-tab="all">
                <i class="fas fa-list"></i>
                All Reviews
                <span class="tab-count"><?= count($reviewsData) ?></span>
            </button>
            <button class="tab-btn" data-tab="kitchen">
                <i class="fas fa-store"></i>
                Kitchen
                <span class="tab-count"><?= count($kitchenReviews) ?></span>
            </button>
            <button class="tab-btn" data-tab="items">
                <i class="fas fa-utensils"></i>
                Menu Items
                <span class="tab-count"><?= count($itemReviews) ?></span>
            </button>
            <button class="tab-btn" data-tab="platform">
                <i class="fas fa-globe"></i>
                Platform
                <span class="tab-count"><?= count($platformReviews) ?></span>
            </button>
        </div>

        <div class="header-actions">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="reviewSearch" placeholder="Search reviews..." class="search-input">
            </div>

            <div class="filter-dropdown">
                <select id="statusFilter" class="filter-select">
                    <option value="">All Statuses</option>
                    <option value="PUBLIC">Public</option>
                    <option value="REPORTED">Reported</option>
                    <option value="HIDDEN">Hidden</option>
                </select>
                <i class="fas fa-chevron-down"></i>
            </div>

            <div class="filter-dropdown">
                <select id="ratingFilter" class="filter-select">
                    <option value="">All Ratings</option>
                    <option value="5">5 Stars</option>
                    <option value="4">4 Stars</option>
                    <option value="3">3 Stars</option>
                    <option value="2">2 Stars</option>
                    <option value="1">1 Star</option>
                </select>
                <i class="fas fa-chevron-down"></i>
            </div>

            <button class="btn btn-secondary" id="clearFiltersBtn">
                <i class="fas fa-times"></i> Clear
            </button>

            <button class="btn btn-primary" id="exportReviews">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
    </div>

    <div class="card-body">
        <!-- All Reviews Tab -->
        <div class="tab-pane active" id="allTab">
            <?php if (empty($reviewsData)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h4>No reviews yet</h4>
                    <p>There are no reviews in the system at the moment.</p>
                </div>
            <?php else: ?>
                <div class="reviews-list">
                    <?php foreach ($reviewsData as $review): ?>
                        <?php include_review_card($review, $csrfToken); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Kitchen Reviews Tab -->
        <div class="tab-pane" id="kitchenTab">
            <?php if (empty($kitchenReviews)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <h4>No kitchen reviews</h4>
                    <p>There are no kitchen reviews in the system at the moment.</p>
                </div>
            <?php else: ?>
                <div class="reviews-list">
                    <?php foreach ($kitchenReviews as $review): ?>
                        <?php include_review_card($review, $csrfToken); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Item Reviews Tab -->
        <div class="tab-pane" id="itemsTab">
            <?php if (empty($itemReviews)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h4>No menu item reviews</h4>
                    <p>There are no menu item reviews in the system at the moment.</p>
                </div>
            <?php else: ?>
                <div class="reviews-list">
                    <?php foreach ($itemReviews as $review): ?>
                        <?php include_review_card($review, $csrfToken); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Platform Reviews Tab -->
        <div class="tab-pane" id="platformTab">
            <?php if (empty($platformReviews)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <h4>No platform reviews</h4>
                    <p>There are no platform reviews in the system at the moment.</p>
                </div>
            <?php else: ?>
                <div class="reviews-list">
                    <?php foreach ($platformReviews as $review): ?>
                        <?php include_review_card($review, $csrfToken); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="card-footer">
            <div class="pagination-container">
                <div class="pagination-info">
                    Showing <?= (($page - 1) * $limit) + 1 ?> to <?= min($page * $limit, $totalReviews) ?> of <?= $totalReviews ?> reviews
                </div>
                <div class="pagination-controls">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&limit=<?= $limit ?>" class="pagination-btn">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);

                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <a href="?page=<?= $i ?>&limit=<?= $limit ?>"
                            class="pagination-btn <?= $i == $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&limit=<?= $limit ?>" class="pagination-btn">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="pagination-limit">
                    <label>Show:</label>
                    <select onchange="changeLimit(this.value)" class="filter-select">
                        <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                        <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100</option>
                    </select>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- No Results Message (for search/filter) -->
<div id="noResults" class="empty-state" style="display: none;">
    <div class="empty-icon">
        <i class="fas fa-search"></i>
    </div>
    <h4>No reviews found</h4>
    <p>Try adjusting your search or filter criteria.</p>
    <div class="empty-actions">
        <button class="btn btn-secondary" onclick="clearFilters()">
            <i class="fas fa-times"></i> Clear
        </button>
    </div>
</div>

<?php
function include_review_card($review, $csrfToken)
{
    $hiddenInfo = '';
    if ($review['STATUS'] === 'HIDDEN' && !empty($review['HIDDEN_BY_NAME'])) {
        $hiddenInfo = 'Hidden by ' . $review['HIDDEN_BY_NAME'] .
            (!empty($review['HIDDEN_REASON']) ? ' - ' . $review['HIDDEN_REASON'] : '');
    }
?>
    <div class="review-card <?= $review['STATUS'] !== 'PUBLIC' ? 'review-hidden' : '' ?>"
        data-review-id="<?= $review['REVIEW_ID'] ?>"
        data-rating="<?= $review['RATING'] ?>"
        data-status="<?= strtolower($review['STATUS']) ?>"
        data-type="<?= strtolower($review['REFERENCE_TYPE']) ?>"
        data-search="<?= htmlspecialchars(strtolower(
                            ($review['REVIEWER_NAME'] ?? '') . ' ' .
                                ($review['REVIEWER_EMAIL'] ?? '') . ' ' .
                                ($review['COMMENTS'] ?? '') . ' ' .
                                ($review['REFERENCE_NAME'] ?? '')
                        )) ?>">

        <div class="review-card-header">
            <div class="reviewer-info">
                <div class="reviewer-avatar">
                    <?php if (!empty($review['PROFILE_IMAGE'])): ?>
                        <img src="/uploads/profile/<?= htmlspecialchars($review['PROFILE_IMAGE']) ?>"
                            alt="<?= htmlspecialchars($review['REVIEWER_NAME'] ?? '') ?>">
                    <?php else: ?>
                        <div class="avatar-placeholder">
                            <?= strtoupper(substr($review['REVIEWER_NAME'] ?? 'U', 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="reviewer-details">
                    <h4><?= htmlspecialchars($review['REVIEWER_NAME'] ?? 'Anonymous') ?></h4>
                    <p class="reviewer-email"><?= htmlspecialchars($review['REVIEWER_EMAIL'] ?? '') ?></p>
                    <div class="review-meta">
                        <div class="rating-stars">
                            <?= generateStarRating($review['RATING']) ?>
                        </div>
                        <span class="review-date">
                            <i class="fas fa-calendar-alt"></i> <?= formatDate($review['REVIEW_DATE'], 'M j, Y') ?>
                        </span>
                        <?= getReviewTypeBadge($review['REFERENCE_TYPE'], $review['REFERENCE_NAME'] ?? '') ?>
                        <?= getStatusBadge($review['STATUS']) ?>
                    </div>
                </div>
            </div>

            <div class="review-actions">
                <button class="action-btn view-btn"
                    onclick="openViewReviewModal(<?= htmlspecialchars(json_encode($review)) ?>)"
                    title="View Details">
                    <i class="fas fa-eye"></i>
                    <span>View</span>
                </button>

                <button class="action-btn edit-btn"
                    onclick="openEditReviewModal('<?= $review['REVIEW_ID'] ?>', '<?= $review['STATUS'] ?>')"
                    title="Edit Status">
                    <i class="fas fa-edit"></i>
                    <span>Edit</span>
                </button>

                <button class="action-btn delete-btn"
                    onclick="openDeleteReviewModal('<?= $review['REVIEW_ID'] ?>', '<?= htmlspecialchars(addslashes($review['REVIEWER_NAME'] ?? 'Anonymous')) ?>')"
                    title="Delete Review">
                    <i class="fas fa-trash"></i>
                    <span>Delete</span>
                </button>
            </div>
        </div>

        <?php if (!empty($review['COMMENTS'])): ?>
            <div class="review-content">
                <p><?= nl2br(htmlspecialchars($review['COMMENTS'])) ?></p>
            </div>
        <?php endif; ?>

        <?php if ($review['STATUS'] !== 'PUBLIC'): ?>
            <div class="review-status-alert <?= strtolower($review['STATUS']) ?>">
                <i class="fas fa-info-circle"></i>
                <span>
                    <?php if ($review['STATUS'] === 'REPORTED'): ?>
                        <strong>Reported</strong> - This review has been reported and needs moderation.
                    <?php elseif ($review['STATUS'] === 'HIDDEN'): ?>
                        <strong>Hidden by <?= htmlspecialchars($review['HIDDEN_BY_NAME'] ?? 'Admin') ?></strong>
                        <?php if (!empty($review['HIDDEN_REASON'])): ?>
                            - <?= htmlspecialchars($review['HIDDEN_REASON']) ?>
                        <?php endif; ?>
                        <?php if (!empty($review['HIDDEN_AT'])): ?>
                            <span class="hidden-date">(<?= formatDate($review['HIDDEN_AT'], 'M j, Y') ?>)</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </span>
            </div>
        <?php endif; ?>

        <?php if ($review['REFERENCE_TYPE'] === 'ITEM' && !empty($review['ITEM_NAME'])): ?>
            <div class="review-item-context">
                <i class="fas fa-hamburger"></i>
                <span>Reviewed item: <strong><?= htmlspecialchars($review['ITEM_NAME']) ?></strong></span>
            </div>
        <?php elseif ($review['REFERENCE_TYPE'] === 'KITCHEN' && !empty($review['KITCHEN_NAME'])): ?>
            <div class="review-item-context">
                <i class="fas fa-store"></i>
                <span>Reviewed kitchen: <strong><?= htmlspecialchars($review['KITCHEN_NAME']) ?></strong></span>
            </div>
        <?php endif; ?>
    </div>
<?php
}
?>

<!-- View Review Modal -->
<div class="modal-overlay" id="viewReviewModal">
    <div class="modal modal-large">
        <div class="modal-header">
            <div class="modal-title-wrapper">
                <i class="fas fa-eye" style="color: #4f46e5;"></i>
                <h3 class="modal-title">Review Details</h3>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('viewReviewModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="review-details-container">
                <div class="review-detail-section">
                    <h4>Reviewer Information</h4>
                    <div class="detail-row">
                        <span class="detail-label">Name:</span>
                        <span class="detail-value" id="viewReviewerName"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value" id="viewReviewerEmail"></span>
                    </div>
                </div>

                <div class="review-detail-section">
                    <h4>Review Information</h4>
                    <div class="detail-row">
                        <span class="detail-label">Type:</span>
                        <span class="detail-value" id="viewReviewType"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Reference:</span>
                        <span class="detail-value" id="viewReferenceName"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Rating:</span>
                        <span class="detail-value" id="viewRating"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Date:</span>
                        <span class="detail-value" id="viewReviewDate"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value" id="viewReviewStatus"></span>
                    </div>
                </div>

                <div class="review-detail-section">
                    <h4>Comments</h4>
                    <div class="review-comments" id="viewReviewComments"></div>
                </div>

                <div class="review-detail-section" id="hiddenInfoSection" style="display: none;">
                    <h4>Moderation Information</h4>
                    <div class="detail-row">
                        <span class="detail-label">Hidden By:</span>
                        <span class="detail-value" id="viewHiddenBy"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Hidden At:</span>
                        <span class="detail-value" id="viewHiddenAt"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Reason:</span>
                        <span class="detail-value" id="viewHiddenReason"></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('viewReviewModal')">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Edit Review Modal -->
<div class="modal-overlay" id="editReviewModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title-wrapper">
                <i class="fas fa-edit" style="color: #4f46e5;"></i>
                <h3 class="modal-title">Update Review Status</h3>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('editReviewModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form method="POST" action="/admin/dashboard/reviews" id="editReviewForm">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="review_id" id="editReviewId">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div class="form-group">
                    <label class="form-label">Review Status <span class="required">*</span></label>
                    <select class="form-select" name="status" id="editReviewStatus" required>
                        <option value="PUBLIC">Public</option>
                        <option value="HIDDEN">Hidden</option>
                        <option value="REPORTED">Reported</option>
                    </select>
                </div>

                <div class="form-group" id="reasonField" style="display: none;">
                    <label class="form-label">Reason <span class="required">*</span></label>
                    <textarea class="form-textarea" name="reason" id="editReviewReason"
                        rows="3" placeholder="Enter reason for moderation action..."></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editReviewModal')">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Review Modal -->
<div class="modal-overlay" id="deleteReviewModal">
    <div class="modal modal-sm">
        <div class="modal-header">
            <div class="modal-title-wrapper">
                <i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i>
                <h3 class="modal-title">Delete Review</h3>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('deleteReviewModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form method="POST" action="/admin/dashboard/reviews" id="deleteReviewForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="review_id" id="deleteReviewId">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div style="text-align: center; padding: 1rem;">
                    <div style="font-size: 3rem; color: #ef4444; margin-bottom: 1rem;">
                        <i class="fas fa-trash-alt"></i>
                    </div>
                    <h4 style="margin: 0 0 0.5rem 0; color: var(--gray-800);">Confirm Deletion</h4>
                    <p style="color: var(--gray-600); margin-bottom: 1rem;">
                        Are you sure you want to delete the review by <strong id="deleteReviewerName"></strong>?
                    </p>
                    <p style="color: var(--gray-500); font-size: 0.875rem;">
                        This action cannot be undone.
                    </p>
                </div>

                <div class="form-actions" style="justify-content: center;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('deleteReviewModal')">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete Review
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    :root {
        --primary: #4f46e5;
        --primary-dark: #4338ca;
        --primary-light: #6366f1;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --info: #3b82f6;
        --gray-50: #f9fafb;
        --gray-100: #f3f4f6;
        --gray-200: #e5e7eb;
        --gray-300: #d1d5db;
        --gray-400: #9ca3af;
        --gray-500: #6b7280;
        --gray-600: #4b5563;
        --gray-700: #374151;
        --gray-800: #1f2937;
        --gray-900: #111827;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        --radius-sm: 0.375rem;
        --radius-md: 0.5rem;
        --radius-lg: 0.75rem;
        --radius-xl: 1rem;
    }

    .page-title {
        margin: 0 0 0.5rem 0;
        font-size: 1.875rem;
        font-weight: 700;
        color: var(--gray-900);
    }

    .page-subtitle {
        margin: 0;
        font-size: 0.95rem;
        color: var(--gray-500);
    }

    /* Stats Grid */
    .stats-grid-wrapper {
        margin-bottom: 2rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.5rem;
    }

    .stat-card {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1.5rem;
        background: white;
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-md);
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }

    .stat-icon-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 3.5rem;
        height: 3.5rem;
        background: rgba(16, 185, 129, 0.1);
        color: var(--success);
        border-radius: var(--radius-lg);
        font-size: 1.5rem;
    }

    .stat-content {
        flex: 1;
    }

    .stat-value {
        font-size: 1.875rem;
        font-weight: 700;
        color: var(--gray-900);
        line-height: 1.2;
        margin-bottom: 0.25rem;
    }

    .stat-label {
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--gray-500);
        margin-bottom: 0.5rem;
    }

    .stat-trend {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .trend-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.75rem;
        background: var(--gray-100);
        border-radius: var(--radius-lg);
        font-size: 0.75rem;
        font-weight: 500;
        color: var(--gray-600);
    }

    .trend-badge.warning {
        background: rgba(245, 158, 11, 0.1);
        color: var(--warning);
    }

    /* Dashboard Cards */
    .dashboard-card {
        background: white;
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-md);
        overflow: hidden;
        transition: box-shadow 0.2s;
        margin-bottom: 2rem;
    }

    .dashboard-card:hover {
        box-shadow: var(--shadow-lg);
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--gray-200);
        flex-wrap: wrap;
        gap: 1rem;
    }

    .header-left {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .header-left i {
        font-size: 1.25rem;
        color: var(--primary);
    }

    .header-left h3 {
        margin: 0;
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--gray-800);
    }

    .card-body {
        padding: 1.5rem;
    }

    /* Rating Distribution */
    .rating-distribution {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .rating-row {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .rating-label {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        min-width: 3rem;
    }

    .star-count {
        font-weight: 600;
        color: var(--gray-700);
    }

    .rating-label i {
        color: #fbbf24;
        font-size: 0.875rem;
    }

    .rating-progress {
        flex: 1;
    }

    .progress-bar {
        width: 100%;
        height: 0.5rem;
        background: var(--gray-200);
        border-radius: 1rem;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #fbbf24, #f59e0b);
        border-radius: 1rem;
        transition: width 0.3s ease;
    }

    .rating-count {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        min-width: 5rem;
    }

    .rating-count .count {
        font-weight: 600;
        color: var(--gray-700);
    }

    .rating-count .percentage {
        color: var(--gray-500);
        font-size: 0.875rem;
    }

    /* Header Tabs */
    .header-tabs {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .tab-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: transparent;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-lg);
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--gray-600);
        cursor: pointer;
        transition: all 0.2s;
    }

    .tab-btn i {
        font-size: 0.875rem;
    }

    .tab-btn .tab-count {
        padding: 0.125rem 0.375rem;
        background: var(--gray-200);
        border-radius: 1rem;
        font-size: 0.75rem;
        color: var(--gray-700);
    }

    .tab-btn:hover {
        background: var(--gray-50);
        border-color: var(--gray-300);
    }

    .tab-btn.active {
        background: var(--primary);
        border-color: var(--primary);
        color: white;
    }

    .tab-btn.active .tab-count {
        background: rgba(255, 255, 255, 0.2);
        color: white;
    }

    /* Header Actions */
    .header-actions {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
        width: 100%;
    }

    .search-box {
        position: relative;
        display: flex;
        align-items: center;
    }

    .search-box i {
        position: absolute;
        left: 1rem;
        color: var(--gray-400);
        font-size: 0.875rem;
    }

    .search-box input {
        padding: 0.625rem 1rem 0.625rem 2.5rem;
        border: 1px solid var(--gray-300);
        border-radius: var(--radius-lg);
        font-size: 0.875rem;
        min-width: 250px;
        transition: all 0.2s;
    }

    .search-box input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .filter-dropdown {
        position: relative;
        display: flex;
        align-items: center;
    }

    .filter-select {
        padding: 0.625rem 2rem 0.625rem 1rem;
        border: 1px solid var(--gray-300);
        border-radius: var(--radius-lg);
        font-size: 0.875rem;
        color: var(--gray-700);
        background: white;
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
    }

    .filter-select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .filter-dropdown i {
        position: absolute;
        right: 0.75rem;
        color: var(--gray-400);
        font-size: 0.75rem;
        pointer-events: none;
    }

    /* Tab Panes */
    .tab-pane {
        display: none;
    }

    .tab-pane.active {
        display: flex;
        justify-content: center;
    }

    /* Reviews List */
    .reviews-list {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        width: 100%;
    }

    .review-card {
        padding: 1.5rem;
        background: white;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-lg);
        transition: all 0.2s;
    }

    .review-card:hover {
        border-color: var(--gray-300);
        box-shadow: var(--shadow-md);
    }

    .review-card.review-hidden {
        background: var(--gray-50);
        border-color: var(--gray-300);
        opacity: 0.9;
    }

    .review-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .reviewer-info {
        display: flex;
        gap: 1rem;
    }

    .reviewer-avatar {
        flex-shrink: 0;
    }

    .reviewer-avatar img {
        width: 3rem;
        height: 3rem;
        border-radius: 50%;
        object-fit: cover;
    }

    .avatar-placeholder {
        width: 3rem;
        height: 3rem;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        font-weight: 600;
    }

    .reviewer-details h4 {
        margin: 0 0 0.5rem 0;
        font-size: 1rem;
        font-weight: 600;
        color: var(--gray-800);
    }

    .review-meta {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .rating-stars {
        display: flex;
        gap: 0.125rem;
    }

    .rating-stars i {
        font-size: 0.875rem;
        color: #d1d5db;
    }

    .rating-stars i.active {
        color: #fbbf24;
    }

    .review-date {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.875rem;
        color: var(--gray-500);
    }

    .review-date i {
        font-size: 0.75rem;
    }

    /* Review Actions */
    .review-actions {
        display: flex;
        gap: 0.5rem;
    }

    .action-btn {
        display: flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.5rem 1rem;
        background: transparent;
        border: 1px solid var(--gray-300);
        border-radius: var(--radius-lg);
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--gray-600);
        cursor: pointer;
        transition: all 0.2s;
    }

    .action-btn:hover {
        background: var(--gray-100);
        border-color: var(--gray-400);
    }

    .action-btn.report-btn {
        color: var(--warning);
        border-color: rgba(245, 158, 11, 0.3);
    }

    .action-btn.report-btn:hover {
        background: rgba(245, 158, 11, 0.1);
        border-color: var(--warning);
    }

    /* Review Content */
    .review-content {
        margin-bottom: 1rem;
        padding: 1rem;
        background: var(--gray-50);
        border-radius: var(--radius-lg);
    }

    .review-content p {
        margin: 0;
        line-height: 1.6;
        color: var(--gray-700);
        font-size: 0.95rem;
    }

    /* Review Status Alert */
    .review-status-alert {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        background: var(--gray-100);
        border-radius: var(--radius-lg);
        margin-top: 1rem;
    }

    .review-status-alert.reported {
        background: rgba(245, 158, 11, 0.1);
        color: var(--warning);
    }

    .review-status-alert.hidden {
        background: rgba(107, 114, 128, 0.1);
        color: var(--gray-600);
    }

    .review-status-alert i {
        font-size: 1rem;
    }

    .review-status-alert span {
        font-size: 0.875rem;
    }

    .review-status-alert strong {
        font-weight: 600;
    }

    .hidden-date {
        margin-left: 0.5rem;
        font-size: 0.8125rem;
        color: var(--gray-500);
    }

    /* Review Item Context */
    .review-item-context {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.75rem;
        padding-top: 0.75rem;
        border-top: 1px dashed var(--gray-200);
        font-size: 0.875rem;
        color: var(--gray-600);
    }

    .review-item-context i {
        color: var(--primary);
    }

    /* Badge Styles */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.625rem;
        border-radius: 1rem;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .badge-info {
        background: rgba(59, 130, 246, 0.1);
        color: var(--info);
    }

    .badge-warning {
        background: rgba(245, 158, 11, 0.1);
        color: var(--warning);
    }

    /* Type Badges */
    .type-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.625rem;
        border-radius: 1rem;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .type-badge.kitchen {
        background: rgba(79, 70, 229, 0.1);
        color: var(--primary);
    }

    .type-badge.item {
        background: rgba(16, 185, 129, 0.1);
        color: var(--success);
    }

    /* Status Badges */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.625rem;
        border-radius: 1rem;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .status-badge.public {
        background: rgba(16, 185, 129, 0.1);
        color: var(--success);
    }

    .status-badge.reported {
        background: rgba(245, 158, 11, 0.1);
        color: var(--warning);
    }

    .status-badge.hidden {
        background: rgba(107, 114, 128, 0.1);
        color: var(--gray-600);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 3rem;
    }

    .empty-icon {
        width: 5rem;
        height: 5rem;
        margin: 0 auto 1.5rem;
        background: var(--gray-100);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: var(--gray-400);
    }

    .empty-state h4 {
        margin: 0 0 0.5rem 0;
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--gray-800);
    }

    .empty-state p {
        margin: 0 0 1.5rem 0;
        color: var(--gray-500);
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }

    .empty-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
    }

    /* Modal Styles */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }

    .modal-overlay.active {
        display: flex;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .modal {
        background: white;
        border-radius: var(--radius-xl);
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: var(--shadow-xl);
        animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
        from {
            transform: translateY(20px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem;
        border-bottom: 1px solid var(--gray-200);
    }

    .modal-title-wrapper {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .modal-title-wrapper i {
        font-size: 1.5rem;
    }

    .modal-title {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--gray-800);
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 1.25rem;
        color: var(--gray-400);
        cursor: pointer;
        padding: 0.5rem;
        transition: all 0.2s;
    }

    .modal-close:hover {
        color: var(--gray-600);
        transform: rotate(90deg);
    }

    .modal-body {
        padding: 1.5rem;
    }

    /* Form Styles */
    .form-group {
        margin-bottom: 1.25rem;
    }

    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--gray-700);
    }

    .form-label .required {
        color: var(--danger);
    }

    .form-select,
    .form-textarea {
        width: 100%;
        padding: 0.625rem 0.875rem;
        border: 1px solid var(--gray-300);
        border-radius: var(--radius-lg);
        font-size: 0.875rem;
        color: var(--gray-700);
        transition: all 0.2s;
    }

    .form-select {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
        background-position: right 0.5rem center;
        background-repeat: no-repeat;
        background-size: 1.5em 1.5em;
        padding-right: 2.5rem;
    }

    .form-select:focus,
    .form-textarea:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .form-textarea {
        resize: vertical;
        min-height: 100px;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        margin-top: 1.5rem;
    }

    /* Button Styles */
    .btn {
        padding: 0.625rem 1.25rem;
        border: none;
        border-radius: var(--radius-lg);
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-sm {
        padding: 0.375rem 0.875rem;
        font-size: 0.8125rem;
    }

    .btn-primary {
        background: var(--primary);
        color: white;
    }

    .btn-primary:hover {
        background: var(--primary-dark);
        transform: translateY(-1px);
        box-shadow: var(--shadow-md);
    }

    .btn-secondary {
        background: var(--gray-200);
        color: var(--gray-700);
    }

    .btn-secondary:hover {
        background: var(--gray-300);
    }

    .btn-warning {
        background: var(--warning);
        color: white;
    }

    .btn-warning:hover {
        background: #e07b0c;
    }

    .btn-outline {
        background: transparent;
        border: 1px solid var(--gray-300);
        color: var(--gray-700);
    }

    .btn-outline:hover {
        background: var(--gray-50);
        border-color: var(--gray-400);
    }

    /* Responsive */
    @media (max-width: 1280px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .card-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .header-tabs {
            width: 100%;
            overflow-x: auto;
            padding-bottom: 0.5rem;
        }

        .header-actions {
            width: 100%;
            flex-direction: column;
        }

        .search-box input {
            width: 100%;
        }

        .filter-dropdown {
            width: 100%;
        }

        .filter-select {
            width: 100%;
        }
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .review-card-header {
            flex-direction: column;
        }

        .review-actions {
            width: 100%;
        }

        .action-btn {
            width: 100%;
            justify-content: center;
        }

        .review-meta {
            flex-direction: column;
            align-items: flex-start;
        }
    }

    @media (max-width: 640px) {
        .page-header {
            padding: 1rem;
            flex-direction: column;
            align-items: flex-start;
        }

        .page-title {
            font-size: 1.5rem;
        }

        .stat-card {
            padding: 1rem;
        }

        .card-body {
            padding: 1rem;
        }

        .review-card {
            padding: 1rem;
        }

        .reviewer-info {
            flex-direction: column;
            align-items: flex-start;
        }

        .empty-state {
            padding: 2rem 1rem;
        }

        .empty-actions {
            flex-direction: column;
        }
    }

    /* Platform badge style */
    .type-badge.platform {
        background: rgba(59, 130, 246, 0.1);
        color: #3b82f6;
    }

    /* Review detail sections */
    .review-detail-section {
        margin-bottom: 2rem;
    }

    .review-detail-section h4 {
        margin: 0 0 1rem 0;
        font-size: 1rem;
        font-weight: 600;
        color: var(--gray-800);
        padding-bottom: 0.5rem;
        border-bottom: 1px solid var(--gray-200);
    }

    .review-comments {
        padding: 1rem;
        background: var(--gray-50);
        border-radius: var(--radius-lg);
        font-size: 0.875rem;
        line-height: 1.6;
        color: var(--gray-700);
    }

    /* Pagination styles */
    .card-footer {
        padding: 1.25rem 1.5rem;
        border-top: 1px solid var(--gray-200);
        background: var(--gray-50);
    }

    .pagination-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .pagination-info {
        font-size: 0.875rem;
        color: var(--gray-600);
    }

    .pagination-controls {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    .pagination-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.5rem 0.875rem;
        background: white;
        border: 1px solid var(--gray-300);
        border-radius: var(--radius-lg);
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--gray-700);
        text-decoration: none;
        transition: all 0.2s;
    }

    .pagination-btn:hover {
        background: var(--gray-100);
        border-color: var(--gray-400);
        color: var(--gray-800);
    }

    .pagination-btn.active {
        background: var(--primary);
        border-color: var(--primary);
        color: white;
    }

    .pagination-limit {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
        color: var(--gray-600);
    }

    .pagination-limit select {
        width: auto;
        padding: 0.375rem 1.5rem 0.375rem 0.5rem;
    }

    /* Reviewer email */
    .reviewer-email {
        margin: 0 0 0.25rem 0;
        font-size: 0.8125rem;
        color: var(--gray-500);
    }

    /* Action button variants */
    .action-btn.view-btn:hover {
        background: rgba(79, 70, 229, 0.1);
        border-color: var(--primary);
        color: var(--primary);
    }

    .action-btn.edit-btn:hover {
        background: rgba(16, 185, 129, 0.1);
        border-color: var(--success);
        color: var(--success);
    }

    .action-btn.delete-btn:hover {
        background: rgba(239, 68, 68, 0.1);
        border-color: var(--danger);
        color: var(--danger);
    }

    /* Responsive adjustments */
    @media (max-width: 1280px) {
        .pagination-container {
            flex-direction: column;
            align-items: flex-start;
        }

        .pagination-controls {
            width: 100%;
            overflow-x: auto;
            padding-bottom: 0.5rem;
        }
    }
</style>