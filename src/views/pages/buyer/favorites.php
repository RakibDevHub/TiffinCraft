<?php
$favorites = $data['favorites'] ?? [];
$selectedType = $_GET['type'] ?? 'all';
$searchTerm = $_GET['search'] ?? '';
$currentPage = $data['currentPage'] ?? 1;
$totalPages = $data['totalPages'] ?? 1;
$totalItems = $data['totalItems'] ?? 0;

include BASE_PATH . '/src/views/components/flash-popup.php';
?>

<!-- Main Content Section -->
<main class="favorites-page">
    <section class="favorites-section">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h2 class="page-title">My Favorites</h2>
                <p class="page-subtitle">Your saved kitchens and menu items</p>
            </div>

            <!-- Filters Section -->
            <div class="filters-container">
                <div class="category-filters">
                    <a href="/favorites?<?= http_build_query(['search' => $searchTerm, 'page' => 1]) ?>"
                        class="category-filter <?= $selectedType === 'all' ? 'active' : '' ?>">
                        All
                    </a>
                    <a href="/favorites?<?= http_build_query(['type' => 'kitchens', 'search' => $searchTerm, 'page' => 1]) ?>"
                        class="category-filter <?= $selectedType === 'kitchens' ? 'active' : '' ?>">
                        <i class="fas fa-utensils"></i> Kitchens
                    </a>
                    <a href="/favorites?<?= http_build_query(['type' => 'menu-items', 'search' => $searchTerm, 'page' => 1]) ?>"
                        class="category-filter <?= $selectedType === 'menu-items' ? 'active' : '' ?>">
                        <i class="fas fa-hamburger"></i> Menu Items
                    </a>
                </div>

                <div class="filter-row">
                    <!-- Search Box -->
                    <div class="search-filter" style="flex: 2;">
                        <label class="filter-label">Search Favorites</label>
                        <form method="GET" action="/favorites" class="search-form">
                            <div class="search-input-wrapper">
                                <div class="search-icon">
                                    <i class="fas fa-search"></i>
                                </div>
                                <input type="text"
                                    name="search"
                                    placeholder="Search by name, kitchen, description..."
                                    value="<?= htmlspecialchars($searchTerm) ?>"
                                    class="search-input"
                                    onkeypress="if(event.key === 'Enter') this.form.submit()">
                            </div>
                            <input type="hidden" name="type" value="<?= htmlspecialchars($selectedType) ?>">
                            <input type="hidden" name="page" value="1">
                        </form>
                    </div>

                    <!-- Results Count -->
                    <div class="results-count" style="color: #6c757d; font-size: 0.9rem; align-self: flex-end; padding-bottom: 0.5rem;">
                        <span id="resultsCount"><?= $totalItems ?> favorites</span>
                    </div>

                    <!-- Clear Filters Button -->
                    <div class="clear-filter">
                        <a href="/favorites" class="clear-btn">
                            Clear Filters
                        </a>
                    </div>

                    <!-- Clear All Favorites Button -->
                    <?php if ($totalItems > 0): ?>
                        <div class="clear-all-filter">
                            <button class="btn btn-danger btn-sm" onclick="openConfirmModal('clear_all_favorites')">
                                <i class="fas fa-trash-alt"></i> Remove All Favorites
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Active Filters Display -->
            <?php if ($selectedType !== 'all' || $searchTerm): ?>
                <div class="active-filters">
                    <?php if ($selectedType !== 'all'): ?>
                        <span class="active-filter-tag">
                            Type: <?= htmlspecialchars($selectedType === 'kitchens' ? 'Kitchens' : 'Menu Items') ?>
                            <a href="?<?= http_build_query(['search' => $searchTerm, 'page' => 1]) ?>" class="remove-filter">
                                <i class="fas fa-times"></i>
                            </a>
                        </span>
                    <?php endif; ?>

                    <?php if ($searchTerm): ?>
                        <span class="active-filter-tag">
                            Search: "<?= htmlspecialchars($searchTerm) ?>"
                            <a href="?<?= http_build_query(['type' => $selectedType, 'page' => 1]) ?>" class="remove-filter">
                                <i class="fas fa-times"></i>
                            </a>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($favorites)): ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-heart"></i>
                    </div>

                    <h3 class="empty-title">No Favorites Found</h3>

                    <p class="empty-subtitle">
                        <?php if ($searchTerm): ?>
                            No favorites found for "<?= htmlspecialchars($searchTerm) ?>"
                        <?php elseif ($selectedType !== 'all'): ?>
                            No <?= htmlspecialchars($selectedType === 'kitchens' ? 'kitchens' : 'menu items') ?> found in favorites
                        <?php else: ?>
                            Start saving your favorite kitchens and menu items to see them here
                        <?php endif; ?>
                    </p>

                    <div class="empty-actions">
                        <a href="/dishes" class="browse-btn">
                            <i class="fas fa-utensils"></i> Browse Dishes
                        </a>
                        <a href="/kitchens" class="secondary-btn">
                            <i class="fas fa-store"></i> Browse Kitchens
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Favorites Grid -->
                <div class="favorites-grid">

                    <?php foreach ($favorites as $favorite): ?>

                        <?php if ($favorite['REFERENCE_TYPE'] === 'ITEM'): ?>

                            <!-- MENU ITEM CARD (Same as dishes page) -->
                            <div class="menu-item-card" data-url="/dishes?view=item&id=<?= $favorite['ITEM_ID'] ?>" style="cursor: pointer;">
                                <!-- Favorite Button -->
                                <form method="POST" action="/favorites/toggle" class="inline-form">
                                    <input type="hidden" name="reference_id" value="<?= $favorite['REFERENCE_ID'] ?>">
                                    <input type="hidden" name="reference_type" value="ITEM">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <button type="submit" class="btn-favorite-item active">
                                        <i class="fas fa-heart"></i>
                                    </button>
                                </form>

                                <!-- Kitchen Badge -->
                                <div class="k-badge">
                                    <i class="fas fa-utensils"></i>
                                    <a href="/kitchens?view=kitchen&id=<?= $favorite['KITCHEN_ID'] ?>">
                                        <?= htmlspecialchars($favorite['KITCHEN_NAME']) ?>
                                    </a>
                                </div>

                                <!-- Image -->
                                <div class="item-image">
                                    <?php if (!empty($favorite['IMAGE'])): ?>
                                        <img src="/uploads/menu/<?= htmlspecialchars($favorite['IMAGE']) ?>"
                                            alt="<?= htmlspecialchars($favorite['NAME']) ?>">
                                    <?php else: ?>
                                        <div class="image-placeholder">
                                            <i class="fas fa-utensils"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Info -->
                                <div class="item-info">
                                    <h3 class="item-name"><?= htmlspecialchars($favorite['NAME']) ?></h3>

                                    <?php if (!empty($favorite['CATEGORY_NAME'])): ?>
                                        <div class="item-category-badge">
                                            <?php foreach (explode(',', $favorite['CATEGORY_NAME']) as $cat): ?>
                                                <div class="category-badge">
                                                    <?= htmlspecialchars(trim($cat)) ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($favorite['DESCRIPTION'])): ?>
                                        <p class="item-description">
                                            <?= strlen($favorite['DESCRIPTION']) > 100
                                                ? htmlspecialchars(substr($favorite['DESCRIPTION'], 0, 97) . '...')
                                                : htmlspecialchars($favorite['DESCRIPTION']) ?>
                                        </p>
                                    <?php endif; ?>

                                    <!-- Spice Level -->
                                    <?php if ($favorite['SPICE_LEVEL']): ?>
                                        <div class="spice-level">
                                            <span>Spice: </span>
                                            <?php for ($i = 1; $i <= 3; $i++): ?>
                                                <i class="fas fa-pepper-hot <?= $i <= $favorite['SPICE_LEVEL'] ? 'active' : '' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div style="display: flex; align-items: center; justify-content: space-between;">
                                        <div style="display: flex; align-items: center; gap: 1rem;">
                                            <!-- Portion Size -->
                                            <?php if ($favorite['PORTION_SIZE']): ?>
                                                <div class="portion-size">
                                                    <i class="fas fa-weight"></i>
                                                    <?= htmlspecialchars($favorite['PORTION_SIZE']) ?>
                                                </div>
                                            <?php endif; ?>

                                            <div class="daily-stock">
                                                <i class="fa-solid fa-boxes-stacked"></i>
                                                <?= htmlspecialchars($favorite['DAILY_STOCK']) ?>
                                            </div>
                                        </div>

                                        <div class="item-rating">
                                            <i class="fas fa-star"></i>
                                            <?= round($favorite['RATING'] ?? 0, 1) ?>
                                            <span>(<?= (int)($favorite['REVIEW_COUNT'] ?? 0) ?>)</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer -->
                                <div class="item-footer">
                                    <div class="item-price">
                                        ৳<?= number_format($favorite['PRICE'], 2) ?>
                                    </div>

                                    <form method="POST" action="/cart/add" class="inline-form">
                                        <input type="hidden" name="dish_id" value="<?= $favorite['REFERENCE_ID'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <button type="submit" class="btn-add-to-cart">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </button>
                                    </form>
                                </div>

                            </div>

                        <?php else: ?>

                            <!-- KITCHEN CARD (Same as kitchens page) -->
                            <div class="kitchen-card" data-url="/kitchens?view=kitchen&id=<?= $favorite['KITCHEN_ID'] ?>" style="cursor: pointer;">
                                <!-- Favorite Button -->
                                <form method="POST" action="/favorites/toggle" class="inline-form">
                                    <input type="hidden" name="reference_id" value="<?= $favorite['REFERENCE_ID'] ?>">
                                    <input type="hidden" name="reference_type" value="KITCHEN">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <button type="submit" class="btn-favorite-kitchen active">
                                        <i class="fas fa-heart"></i>
                                    </button>
                                </form>

                                <!-- Image -->
                                <div class="kitchen-image-wrapper">
                                    <?php if (!empty($favorite['IMAGE'])): ?>
                                        <img src="/uploads/kitchen/<?= htmlspecialchars($favorite['IMAGE']) ?>"
                                            alt="<?= htmlspecialchars($favorite['NAME']) ?>"
                                            class="kitchen-image">
                                    <?php else: ?>
                                        <div class="kitchen-image-placeholder">
                                            <i class="fas fa-utensils"></i>
                                            <span>No Image</span>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Rating Badge -->
                                    <?php if ($favorite['REVIEW_COUNT'] > 0 && $favorite['RATING'] > 0): ?>
                                        <div class="rating-badge">
                                            <i class="fas fa-star"></i>
                                            <span class="rating-value"><?= round($favorite['RATING'], 1) ?></span>
                                            <span class="rating-count">(<?= $favorite['REVIEW_COUNT'] ?>)</span>
                                        </div>
                                    <?php elseif ($favorite['REVIEW_COUNT'] > 0): ?>
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

                                <!-- Details -->
                                <div class="kitchen-details">
                                    <div class="top-info">
                                        <div class="name-owner">
                                            <h3 class="kitchen-name">
                                                <?= htmlspecialchars($favorite['NAME']) ?>
                                            </h3>
                                            <p class="owner-info">
                                                <i class="fa-solid fa-user"></i>
                                                <?= htmlspecialchars($favorite['OWNER_NAME']) ?>
                                            </p>
                                        </div>
                                    </div>


                                    <?php if (!empty($favorite['DESCRIPTION'])): ?>
                                        <p class="kitchen-description">
                                            <?= strlen($favorite['DESCRIPTION']) > 120
                                                ? htmlspecialchars(substr($favorite['DESCRIPTION'], 0, 117) . '...')
                                                : htmlspecialchars($favorite['DESCRIPTION']) ?>
                                        </p>
                                    <?php endif; ?>

                                    <!-- Kitchen Tags -->
                                    <div class="kitchen-tags">
                                        <?php if ($favorite['YEARS_EXPERIENCE']): ?>
                                            <span class="kitchen-tag">
                                                <i class="fas fa-clock"></i>
                                                <?= $favorite['YEARS_EXPERIENCE'] ?>+ years
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Footer -->
                                    <div class="kitchen-footer">
                                        <!-- Service Areas -->
                                        <div class="service-areas">
                                            <i class="fa-solid fa-person-biking"></i>
                                            <span title="<?= !empty($favorite['SERVICE_AREAS']) ? htmlspecialchars($favorite['SERVICE_AREAS']) : '' ?>">
                                                <?php if (!empty($favorite['SERVICE_AREAS'])): ?>
                                                    <?php
                                                    $areas = explode(', ', $favorite['SERVICE_AREAS']);
                                                    if (count($areas) > 4) {
                                                        echo htmlspecialchars(implode(', ', array_slice($areas, 0, 4)) . '...');
                                                    } else {
                                                        echo htmlspecialchars($favorite['SERVICE_AREAS']);
                                                    }
                                                    ?>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </span>
                                        </div>

                                        <div class="bottom-row">
                                            <!-- Address -->
                                            <?php if (!empty($favorite['ADDRESS'])): ?>
                                                <p class="kitchen-address"
                                                    title="<?= htmlspecialchars($favorite['ADDRESS']) ?>">
                                                    <i class="fa-solid fa-location-dot"></i>
                                                    <?= strlen($favorite['ADDRESS']) > 30
                                                        ? htmlspecialchars(substr($favorite['ADDRESS'], 0, 27) . '...')
                                                        : htmlspecialchars($favorite['ADDRESS']) ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>

                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($currentPage > 1): ?>
                            <a href="?<?= http_build_query([
                                            'type' => $selectedType,
                                            'search' => $searchTerm,
                                            'page' => $currentPage - 1
                                        ]) ?>" class="pagination-btn pagination-prev">
                                &laquo; Prev
                            </a>
                        <?php else: ?>
                            <span class="pagination-btn pagination-disabled">&laquo; Prev</span>
                        <?php endif; ?>

                        <span class="pagination-info">
                            Page <?= htmlspecialchars($currentPage) ?> of <?= htmlspecialchars($totalPages) ?>
                        </span>

                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?<?= http_build_query([
                                            'type' => $selectedType,
                                            'search' => $searchTerm,
                                            'page' => $currentPage + 1
                                        ]) ?>" class="pagination-btn pagination-next">
                                Next &raquo;
                            </a>
                        <?php else: ?>
                            <span class="pagination-btn pagination-disabled">Next &raquo;</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <?php
    $fillColor = '#FFFBEB';
    $invert = true;
    // $offset = true;

    include BASE_PATH . '/src/views/components/divider-banner.php';
    ?>

    <!-- Favorite Details Modal -->
    <div class="modal-overlay" id="favoriteDetailsModal">
        <div class="modal modal-large">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-info-circle"></i>
                    <span id="favoriteModalTitle">Details</span>
                </h3>
                <button type="button" class="modal-close" onclick="closeFavoriteModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body">
                <?php foreach ($favorites as $favorite): ?>
                    <div class="favorite-details-content"
                        id="favorite-details-<?= $favorite['REFERENCE_TYPE'] ?>-<?= $favorite['REFERENCE_ID'] ?>"
                        style="display:none;">

                        <?php if ($favorite['REFERENCE_TYPE'] === 'ITEM'): ?>
                            <!-- ===== MENU ITEM DETAILS ===== -->
                            <div class="details-header">
                                <div class="details-image">
                                    <?php if ($favorite['IMAGE']): ?>
                                        <img src="/uploads/menu/<?= htmlspecialchars($favorite['IMAGE']) ?>"
                                            class="details-img"
                                            alt="<?= htmlspecialchars($favorite['NAME']) ?>">
                                    <?php else: ?>
                                        <div class="details-placeholder">
                                            <i class="fas fa-hamburger"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="details-info">
                                    <h4 class="details-name"><?= htmlspecialchars($favorite['NAME']) ?></h4>
                                    <p class="details-kitchen">
                                        <i class="fas fa-utensils"></i>
                                        <?= htmlspecialchars($favorite['KITCHEN_NAME'] ?? 'Unknown Kitchen') ?>
                                    </p>
                                    <p class="details-price">৳<?= number_format($favorite['PRICE'], 2) ?></p>

                                    <?php if (!empty($favorite['CATEGORY_NAME']) || !empty($favorite['CATEGORIES'])): ?>
                                        <div class="favorite-category">
                                            <i class="fas fa-tags"></i>
                                            <?php
                                            $categories = !empty($favorite['CATEGORIES']) ? $favorite['CATEGORIES'] : $favorite['CATEGORY_NAME'];
                                            $categoryList = array_map('trim', explode(',', $categories));
                                            $displayCategories = array_slice($categoryList, 0, 2);
                                            foreach ($displayCategories as $category):
                                            ?>
                                                <span class="category-tag"><?= htmlspecialchars($category) ?></span>
                                            <?php endforeach; ?>
                                            <?php if (count($categoryList) > 2): ?>
                                                <span class="more-categories">+<?= count($categoryList) - 2 ?> more</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($favorite['RATING'])): ?>
                                        <div class="details-rating">
                                            <i class="fas fa-star"></i>
                                            <?= number_format((float)$favorite['RATING'], 1) ?>
                                            <?php if (!empty($favorite['REVIEW_COUNT'])): ?>
                                                <span class="rating-count">
                                                    (<?= (int)$favorite['REVIEW_COUNT'] ?> reviews)
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($favorite['MIN_PREP_TIME'])): ?>
                                        <p class="details-prep-time">
                                            <i class="fas fa-clock"></i>
                                            Preparation time:
                                            <?= (int)$favorite['MIN_PREP_TIME'] ?>-<?= (int)$favorite['MAX_PREP_TIME'] ?> minutes
                                        </p>
                                    <?php endif; ?>

                                    <p class="details-status">
                                        <i class="fas fa-check-circle"></i>
                                        Status:
                                        <span class="status-<?= $favorite['IS_AVAILABLE'] ? 'available' : 'unavailable' ?>">
                                            <?= $favorite['IS_AVAILABLE'] ? 'Available' : 'Currently Unavailable' ?>
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <div class="details-body">
                                <div class="details-section">
                                    <h5><i class="fas fa-align-left"></i> Description</h5>
                                    <p><?= nl2br(htmlspecialchars($favorite['DESCRIPTION'] ?? 'No description available.')) ?></p>
                                </div>

                                <div class="details-section">
                                    <h5><i class="fas fa-calendar-alt"></i> Added to Favorites</h5>
                                    <p><?= date('F j, Y \a\t g:i A', strtotime($favorite['ADDED_AT'])) ?></p>
                                </div>
                            </div>

                            <div class="details-actions">
                                <!-- Add to cart -->
                                <?php if ($favorite['IS_AVAILABLE']): ?>
                                    <form method="POST" action="/cart/add" class="inline-form">
                                        <input type="hidden" name="dish_id" value="<?= $favorite['REFERENCE_ID'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <button type="submit" class="btn-add-cart">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <!-- View kitchen -->
                                <a href="/kitchen?view=<?= $favorite['KITCHEN_ID'] ?>" class="btn-view-kitchen">
                                    <i class="fas fa-store"></i> View Kitchen
                                </a>
                            </div>

                        <?php else: ?>
                            <!-- ===== KITCHEN DETAILS ===== -->
                            <div class="details-header">
                                <div class="details-image">
                                    <?php if ($favorite['IMAGE']): ?>
                                        <img src="/uploads/kitchen/<?= htmlspecialchars($favorite['IMAGE']) ?>"
                                            class="details-img"
                                            alt="<?= htmlspecialchars($favorite['NAME']) ?>">
                                    <?php else: ?>
                                        <div class="details-placeholder">
                                            <i class="fas fa-store"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="details-info">
                                    <h4 class="details-name"><?= htmlspecialchars($favorite['NAME']) ?></h4>

                                    <?php if (!empty($favorite['YEARS_EXPERIENCE'])): ?>
                                        <p class="details-experience">
                                            <i class="fas fa-clock"></i>
                                            <?= $favorite['YEARS_EXPERIENCE'] ?>+ years experience
                                        </p>
                                    <?php endif; ?>

                                    <?php if (!empty($favorite['CITY'])): ?>
                                        <p class="details-location">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?= htmlspecialchars($favorite['CITY']) ?>
                                        </p>
                                    <?php endif; ?>

                                    <?php if (!empty($favorite['RATING'])): ?>
                                        <div class="details-rating">
                                            <i class="fas fa-star"></i>
                                            <?= number_format((float)$favorite['RATING'], 1) ?>
                                            <?php if (!empty($favorite['REVIEW_COUNT'])): ?>
                                                <span class="rating-count">
                                                    (<?= (int)$favorite['REVIEW_COUNT'] ?> reviews)
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <p class="details-status">
                                        <i class="fas fa-check-circle"></i>
                                        Status:
                                        <span class="status-<?= !$favorite['IS_SUSPENDED'] ? 'active' : 'suspended' ?>">
                                            <?= !$favorite['IS_SUSPENDED'] ? 'Active' : 'Suspended' ?>
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <div class="details-body">
                                <div class="details-section">
                                    <h5><i class="fas fa-align-left"></i> Description</h5>
                                    <p><?= nl2br(htmlspecialchars($favorite['DESCRIPTION'] ?? 'No description available.')) ?></p>
                                </div>

                                <?php if (!empty($favorite['SERVICE_AREAS'])): ?>
                                    <div class="details-section">
                                        <h5><i class="fas fa-map-marker-alt"></i> Service Areas</h5>
                                        <div class="service-areas-list">
                                            <?php
                                            $areas = array_map('trim', explode(',', $favorite['SERVICE_AREAS']));
                                            foreach ($areas as $area):
                                            ?>
                                                <span class="service-area-tag"><?= htmlspecialchars($area) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($favorite['ADDRESS'])): ?>
                                    <div class="details-section">
                                        <h5><i class="fas fa-home"></i> Address</h5>
                                        <p><?= htmlspecialchars($favorite['ADDRESS']) ?></p>
                                    </div>
                                <?php endif; ?>

                                <div class="details-section">
                                    <h5><i class="fas fa-calendar-alt"></i> Added to Favorites</h5>
                                    <p><?= date('F j, Y \a\t g:i A', strtotime($favorite['ADDED_AT'])) ?></p>
                                </div>
                            </div>

                            <div class="details-actions">
                                <!-- View kitchen -->
                                <a href="/kitchen?view=<?= $favorite['REFERENCE_ID'] ?>"
                                    class="btn-view-kitchen">
                                    <i class="fas fa-store"></i> View Kitchen Menu
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal-overlay" id="confirmModal">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title">
                    <i class="fas fa-question-circle" id="confirmIcon"></i>
                    <span id="confirmTitle">Confirm Action</span>
                </h2>
                <button class="modal-close" onclick="closeConfirmModal()">&times;</button>
            </div>

            <form method="POST" id="confirmForm" class="confirm-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div class="modal-body">
                    <div class="confirm-content">
                        <p id="confirmMessage" class="confirm-message"></p>
                        <p id="confirmDetails" class="confirm-details"></p>

                        <!-- For clear all favorites confirmation -->
                        <div class="form-group" id="clearAllConfirmGroup" style="display:none;">
                            <label class="form-label">Type "DELETE ALL" to confirm *</label>
                            <input type="text"
                                class="form-control"
                                id="clearAllConfirmInput"
                                placeholder="DELETE ALL"
                                pattern="DELETE ALL">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeConfirmModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="confirmActionBtn" disabled>
                        <i class="fas fa-check"></i>
                        <span id="confirmActionText">Confirm</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    // Modal functions
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }
    }

    function closeAllModals() {
        document.querySelectorAll('.modal-overlay.active').forEach(modal => {
            modal.classList.remove('active');
        });
        document.body.style.overflow = 'auto';
    }

    // Favorite details modal
    function openFavoriteModal(type, id) {
        // Hide all favorite detail blocks
        document.querySelectorAll('.favorite-details-content')
            .forEach(el => el.style.display = 'none');

        // Show selected block
        const target = document.getElementById(
            'favorite-details-' + type + '-' + id
        );

        if (target) {
            target.style.display = 'block';
        }

        // Set modal title
        document.getElementById('favoriteModalTitle').textContent =
            type === 'ITEM' ? 'Menu Item Details' : 'Kitchen Details';

        openModal('favoriteDetailsModal');
    }

    function closeFavoriteModal() {
        closeModal('favoriteDetailsModal');
    }

    // Confirmation modal
    function openConfirmModal(action) {
        const title = document.getElementById("confirmTitle");
        const icon = document.getElementById("confirmIcon");
        const message = document.getElementById("confirmMessage");
        const details = document.getElementById("confirmDetails");
        const actionBtn = document.getElementById("confirmActionBtn");
        const actionText = document.getElementById("confirmActionText");
        const confirmForm = document.getElementById("confirmForm");
        const clearAllGroup = document.getElementById("clearAllConfirmGroup");
        const clearAllInput = document.getElementById("clearAllConfirmInput");

        // Reset
        if (clearAllInput) clearAllInput.value = "";
        if (confirmForm) confirmForm.reset();
        actionBtn.disabled = false;
        if (clearAllGroup) clearAllGroup.style.display = "none";

        if (action === "clear_all_favorites") {
            title.textContent = "Clear All Favorites";
            icon.className = "fas fa-trash-alt";
            message.textContent = "Are you sure you want to remove ALL your favorites?";
            details.textContent = "This will permanently remove all your saved kitchens and menu items from favorites. This action cannot be undone.";
            actionBtn.className = "btn btn-danger";
            actionText.textContent = "Clear All Favorites";
            confirmForm.action = "/favorites/clear-all";
            confirmForm.method = "POST";

            clearAllGroup.style.display = "block";
            actionBtn.disabled = true;

            if (clearAllInput) {
                clearAllInput.oninput = function() {
                    actionBtn.disabled = this.value !== "DELETE ALL";
                };
            }
        }

        openModal("confirmModal");
    }

    function closeConfirmModal() {
        closeModal("confirmModal");
        const form = document.getElementById("confirmForm");
        if (form) form.reset();
    }

    // Event listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Close modal when clicking outside
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeAllModals();
                }
            });
        });

        // ESC key to close modals
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAllModals();
            }
        });

        document.querySelectorAll('.kitchen-card').forEach(card => {
            card.addEventListener('click', function() {
                window.location.href = this.dataset.url;
            });
        });

        document.querySelectorAll('.menu-item-card').forEach(card => {
            card.addEventListener('click', function() {
                window.location.href = this.dataset.url;
            });
        });
    });
</script>