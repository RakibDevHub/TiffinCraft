<?php
$pageTitle = "Browse Kitchens - TiffinCraft";

// Data preparation
$selectedLocation = isset($_GET['location']) ? urldecode(trim($_GET['location'])) : null;
$searchTerm = isset($_GET['search']) ? strtolower(trim($_GET['search'])) : null;
$ratingSort = isset($_GET['rating']) ? $_GET['rating'] : null;
$experienceFilter = isset($_GET['experience']) ? $_GET['experience'] : null;

$isLoggedIn = $data['isLoggedIn'];

// Fetched Data
$kitchens = $data['kitchens'] ?? [];
$serviceAreas = $data['locations'] ?? [];

// Rating flags
$hasPublicRatings = $data['hasPublicRatings'] ?? false;
$hasAnyRatings = $data['hasAnyRatings'] ?? false;

// Pagination data
$totalItems = $data['totalItems'] ?? 0;
$totalPages = $data['totalPages'] ?? 1;
$currentPage = $data['page'] ?? 1;

include BASE_PATH . '/src/views/components/flash-popup.php';
?>

<main class="kitchen-page">
    <section class="kitchens-section">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h2 class="page-title">
                    <?php if ($hasPublicRatings): ?>
                        Top Rated Local Kitchens
                    <?php elseif ($hasAnyRatings): ?>
                        Local Kitchens
                    <?php else: ?>
                        Explore Local Kitchens
                    <?php endif; ?>
                </h2>
                <p class="page-subtitle">Discover homemade meals from passionate home chefs in your area.</p>
            </div>

            <!-- Filters Section -->
            <div class="filters-container">
                <!-- Advanced Filters -->
                <div class="advanced-filters">
                    <div class="filter-row">
                        <!-- Search Box -->
                        <div class="search-filter" style="flex: 2;">
                            <label class="filter-label">Search Kitchens</label>
                            <form method="get" action="/kitchens" class="search-form">
                                <div class="search-input-wrapper" style="position: relative;">
                                    <div class="search-icon">
                                        <i class="fas fa-search"></i>
                                    </div>
                                    <input type="text" id="searchInput" name="search" placeholder="Search by kitchen name or chef..."
                                        value="<?= htmlspecialchars($searchTerm ?? '') ?>"
                                        class="search-input">
                                    <?php if ($searchTerm): ?>
                                        <a href="?<?= http_build_query(array_filter([
                                                        'location' => $selectedLocation,
                                                        'rating' => $ratingSort,
                                                        'experience' => $experienceFilter,
                                                    ])) ?>"
                                            class="clear-search"
                                            aria-label="Clear search">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <!-- Preserve other filters in hidden fields -->
                                <?php if ($selectedLocation): ?>
                                    <input type="hidden" name="location" value="<?= htmlspecialchars($selectedLocation) ?>">
                                <?php endif; ?>
                                <?php if ($ratingSort): ?>
                                    <input type="hidden" name="rating" value="<?= htmlspecialchars($ratingSort) ?>">
                                <?php endif; ?>
                                <?php if ($experienceFilter): ?>
                                    <input type="hidden" name="experience" value="<?= htmlspecialchars($experienceFilter) ?>">
                                <?php endif; ?>
                            </form>
                        </div>

                        <!-- Location Filter -->
                        <div class="location-filter">
                            <label class="filter-label">Location: </label>
                            <select id="locationFilter" class="filter-select">
                                <option value="">All Locations</option>
                                <?php foreach ($serviceAreas as $area): ?>
                                    <option value="<?= htmlspecialchars($area['NAME']) ?>" <?= $selectedLocation === $area['NAME'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($area['NAME']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Rating Sort Filter -->
                        <div class="rating-filter">
                            <label class="filter-label">Sort by Rating: </label>
                            <select id="ratingSort" class="filter-select">
                                <option value="">None</option>
                                <option value="high_to_low" <?= $ratingSort === 'high_to_low' ? 'selected' : '' ?>>Highest Rated</option>
                                <option value="most_reviews" <?= $ratingSort === 'most_reviews' ? 'selected' : '' ?>>Most Reviews</option>
                            </select>
                        </div>

                        <!-- Experience Filter -->
                        <div class="experience-filter">
                            <label class="filter-label">Experience: </label>
                            <select id="experienceFilter" class="filter-select">
                                <option value="">Any</option>
                                <option value="1" <?= $experienceFilter === '1' ? 'selected' : '' ?>>1+ Years</option>
                                <option value="3" <?= $experienceFilter === '3' ? 'selected' : '' ?>>3+ Years</option>
                                <option value="5" <?= $experienceFilter === '5' ? 'selected' : '' ?>>5+ Years</option>
                                <option value="10" <?= $experienceFilter === '10' ? 'selected' : '' ?>>10+ Years</option>
                            </select>
                        </div>

                        <!-- Clear All Filters Button -->
                        <div class="clear-filter">
                            <a href="/kitchens" class="clear-btn">
                                Clear
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Filters Display -->
            <?php if ($selectedLocation || $ratingSort || $experienceFilter): ?>
                <div class="active-filters">
                    <?php if ($selectedLocation): ?>
                        <span class="active-filter-tag">
                            Location: <?= htmlspecialchars($selectedLocation) ?>
                            <a href="?<?= http_build_query(array_filter([
                                            'search' => $searchTerm,
                                            'rating' => $ratingSort,
                                            'experience' => $experienceFilter,
                                        ])) ?>" class="remove-filter">
                                <i class="fas fa-times"></i>
                            </a>
                        </span>
                    <?php endif; ?>

                    <?php if ($ratingSort): ?>
                        <span class="active-filter-tag">
                            Rating: <?= $ratingSort === 'high_to_low' ? 'Highest Rated' : 'Most Reviews' ?>
                            <a href="?<?= http_build_query(array_filter([
                                            'search' => $searchTerm,
                                            'location' => $selectedLocation,
                                            'experience' => $experienceFilter,
                                        ])) ?>" class="remove-filter">
                                <i class="fas fa-times"></i>
                            </a>
                        </span>
                    <?php endif; ?>

                    <?php if ($experienceFilter): ?>
                        <span class="active-filter-tag">
                            Experience: <?= $experienceFilter ?>+ Years
                            <a href="?<?= http_build_query(array_filter([
                                            'search' => $searchTerm,
                                            'location' => $selectedLocation,
                                            'rating' => $ratingSort,
                                        ])) ?>" class="remove-filter">
                                <i class="fas fa-times"></i>
                            </a>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($kitchens)): ?>
                <!-- Kitchens Grid View -->
                <div class="kitchens-grid">
                    <?php foreach ($kitchens as $kitchen): ?>
                        <div class="kitchen-card" data-url="/kitchens?view=kitchen&id=<?= $kitchen['KITCHEN_ID'] ?>" style="cursor: pointer;">
                            <?php if ($isLoggedIn): ?>
                                <form method="POST" action="/favorites/toggle" class="inline-form">
                                    <input type="hidden" name="reference_id" value="<?= $kitchen['KITCHEN_ID'] ?>">
                                    <input type="hidden" name="reference_type" value="KITCHEN">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <button type="submit" class="btn-favorite btn-favorite-kitchen <?= isset($data['kitchenFavorites'][$kitchen['KITCHEN_ID']]) && $data['kitchenFavorites'][$kitchen['KITCHEN_ID']] ? 'active' : '' ?>">
                                        <i class="fas fa-heart"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <button
                                    type="button"
                                    class="btn-favorite btn-favorite-kitchen"
                                    onclick="openLoginModal('Please login to continue','You need to be logged in to add kitchens to favorites.')">
                                    <i class=" fas fa-heart"></i>
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
                                                <i class="fa-solid fa-location-dot"></i>
                                                <?= strlen($kitchen['ADDRESS']) > 30
                                                    ? htmlspecialchars(substr($kitchen['ADDRESS'], 0, 27) . '...')
                                                    : htmlspecialchars($kitchen['ADDRESS']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination Controls -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <!-- Previous Button -->
                        <?php if ($currentPage > 1): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage - 1])) ?>"
                                class="pagination-btn pagination-prev">
                                &laquo; Prev
                            </a>
                        <?php else: ?>
                            <span class="pagination-btn pagination-disabled">&laquo; Prev</span>
                        <?php endif; ?>

                        <!-- Page Info -->
                        <span class="pagination-info">
                            Page <?= htmlspecialchars($currentPage) ?> of <?= htmlspecialchars($totalPages) ?>
                        </span>

                        <!-- Next Button -->
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage + 1])) ?>"
                                class="pagination-btn pagination-next">
                                Next &raquo;
                            </a>
                        <?php else: ?>
                            <span class="pagination-btn pagination-disabled">Next &raquo;</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-utensils"></i>
                    </div>

                    <h3 class="empty-title">No Kitchens Found</h3>

                    <p class="empty-subtitle">
                        <?php if ($selectedLocation || $searchTerm || $experienceFilter): ?>
                            Try adjusting your filters or search term.
                        <?php else: ?>
                            We're onboarding new local kitchens. Please check back soon!
                        <?php endif; ?>
                    </p>

                    <div class="empty-actions">
                        <?php if ($selectedLocation || $searchTerm || $experienceFilter): ?>
                            <a href="/kitchens" class="browse-btn">
                                Clear All Filters
                            </a>
                        <?php endif; ?>

                        <?php if (!$isLoggedIn): ?>
                            <a href="/register" class="secondary-btn">
                                Become a Seller
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php
    $fillColor = '#FFFBEB';
    $invert = true;
    // $offset = true;

    include BASE_PATH . '/src/views/components/divider-banner.php';
    ?>

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
                    <div>
                        <h4 id="loginModalTitle">Please login to continue</h4>
                        <p id="loginModalMessage">
                            You need to be logged in to add kitchens to favorites.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Filter change handlers
        const locationFilter = document.getElementById('locationFilter');
        const ratingSort = document.getElementById('ratingSort');
        const experienceFilter = document.getElementById('experienceFilter');

        if (locationFilter) {
            locationFilter.addEventListener('change', updateFilters);
        }
        if (ratingSort) {
            ratingSort.addEventListener('change', updateFilters);
        }
        if (experienceFilter) {
            experienceFilter.addEventListener('change', updateFilters);
        }

        function updateFilters() {
            const params = new URLSearchParams(window.location.search);

            // Get current values
            const location = locationFilter ? locationFilter.value : '';
            const rating = ratingSort ? ratingSort.value : '';
            const experience = experienceFilter ? experienceFilter.value : '';

            // Update parameters
            if (location) {
                params.set('location', location);
            } else {
                params.delete('location');
            }

            if (rating) {
                params.set('rating', rating);
            } else {
                params.delete('rating');
            }

            if (experience) {
                params.set('experience', experience);
            } else {
                params.delete('experience');
            }

            params.delete('page');

            window.location.search = params.toString();
        }
    });

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

    // Modal close events
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('loginModal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeLoginModal();
                }
            });
        }

        // Escape key close
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLoginModal();
            }
        });

        document.querySelectorAll('.kitchen-card').forEach(card => {
            card.addEventListener('click', function() {
                window.location.href = this.dataset.url;
            });
        });
    });
</script>