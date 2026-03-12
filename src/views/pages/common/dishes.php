<?php
$pageTitle = "Delicious Dishes - TiffinCraft";

// Data preparation
$selectedCategory = isset($_GET['category']) ? urldecode(trim($_GET['category'])) : "";
$searchTerm = isset($_GET['search']) ? strtolower(trim($_GET['search'])) : "";
$priceSort = isset($_GET['price']) ? $_GET['price'] : "";
$selectedLocation = isset($_GET['location']) ? urldecode(trim($_GET['location'])) : "";

$isLoggedIn = $data['isLoggedIn'];

// Fetched Data
$menuItems = $data['menuItems'] ?? [];
$categories = $data['categories'] ?? [];
$serviceAreas = $data['locations'] ?? [];

// Pagination data
$totalItems = $data['totalItems'] ?? 0;
$totalPages = $data['totalPages'] ?? 1;
$currentPage = $data['page'] ?? 1;

include BASE_PATH . '/src/views/components/flash-popup.php';
?>

<!-- Main Content Section -->
<main class="dishes-page">
    <section class="dishes-section">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h2 class="page-title">
                    Our Delicious Menu
                </h2>
                <p class="page-subtitle">Explore meals handcrafted by local home chefs.</p>
            </div>

            <!-- Filters Section -->
            <div class="filters-container">
                <!-- Category Filter -->
                <div class="category-filters">
                    <a href="?<?= http_build_query(array_filter([
                                    'search' => $searchTerm,
                                    'location' => $selectedLocation,
                                    'price' => $priceSort
                                ])) ?>"
                        class="category-filter <?= !$selectedCategory ? 'active' : '' ?>">
                        All
                    </a>
                    <?php foreach ($categories as $category): ?>
                        <a href="?<?= http_build_query(array_filter([
                                        'category' => $category['NAME'],
                                        'search' => $searchTerm,
                                        'location' => $selectedLocation,
                                        'price' => $priceSort
                                    ])) ?>"
                            class="category-filter <?= (strtolower($selectedCategory) === strtolower($category['NAME'])) ? 'active' : '' ?>">
                            <?= htmlspecialchars($category['NAME']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Advanced Filters -->
                <div class="advanced-filters">

                    <div class="filter-row">
                        <!-- Search Box -->
                        <div class="search-filter">
                            <label class="filter-label">Search Dishes</label>
                            <form method="get" action="/dishes" class="search-form">
                                <div class="search-input-wrapper">
                                    <div class="search-icon">
                                        <i class="fas fa-search"></i>
                                    </div>
                                    <input type="text" id="searchInput" name="search" placeholder="Search..."
                                        value="<?= htmlspecialchars($searchTerm ?? '') ?>"
                                        class="search-input">
                                    <?php if ($searchTerm): ?>
                                        <a href="?<?= http_build_query(array_filter([
                                                        'category' => $selectedCategory,
                                                        'location' => $selectedLocation,
                                                        'price' => $priceSort
                                                    ])) ?>"
                                            class="clear-search"
                                            aria-label="Clear search">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <!-- Preserve other filters in hidden fields -->
                                <?php if ($selectedCategory): ?>
                                    <input type="hidden" name="category" value="<?= htmlspecialchars($selectedCategory) ?>">
                                <?php endif; ?>
                                <?php if ($selectedLocation): ?>
                                    <input type="hidden" name="location" value="<?= htmlspecialchars($selectedLocation) ?>">
                                <?php endif; ?>
                                <?php if ($priceSort): ?>
                                    <input type="hidden" name="price" value="<?= htmlspecialchars($priceSort) ?>">
                                <?php endif; ?>
                            </form>
                        </div>
                        <!-- Location Filter -->
                        <div class="location-filter">
                            <label class="filter-label">Select a Location: </label>
                            <select id="locationFilter" class="filter-select">
                                <option value="">All Locations</option>
                                <?php foreach ($serviceAreas as $area): ?>
                                    <option value="<?= htmlspecialchars($area['NAME']) ?>" <?= $selectedLocation === $area['NAME'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($area['NAME']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Price Sort Filter -->
                        <div class="price-filter">
                            <label class="filter-label">Sort by Price: </label>
                            <select id="priceSort" class="filter-select">
                                <option value="">None</option>
                                <option value="low_to_high" <?= $priceSort === 'low_to_high' ? 'selected' : '' ?>>Low to High</option>
                                <option value="high_to_low" <?= $priceSort === 'high_to_low' ? 'selected' : '' ?>>High to Low</option>
                            </select>
                        </div>

                        <!-- Clear All Filters Button -->
                        <div class="clear-filter">
                            <a href="/dishes" class="clear-btn">
                                Clear
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Filters Display -->
            <?php if ($selectedLocation || $priceSort): ?>
                <div class="active-filters">
                    <?php if ($selectedLocation): ?>
                        <span class="active-filter-tag">
                            Location: <?= htmlspecialchars($selectedLocation) ?>
                            <a href="?<?= http_build_query(array_filter([
                                            'category' => $selectedCategory,
                                            'search' => $searchTerm,
                                            'price' => $priceSort
                                        ])) ?>" class="remove-filter">
                                <i class="fas fa-times"></i>
                            </a>
                        </span>
                    <?php endif; ?>

                    <?php if ($priceSort): ?>
                        <span class="active-filter-tag">
                            Price: <?= $priceSort === 'low_to_high' ? 'Low to High' : 'High to Low' ?>
                            <a href="?<?= http_build_query(array_filter([
                                            'category' => $selectedCategory,
                                            'search' => $searchTerm,
                                            'location' => $selectedLocation
                                        ])) ?>" class="remove-filter">
                                <i class="fas fa-times"></i>
                            </a>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($menuItems)): ?>
                <!-- Menu Card View -->
                <div class="menu-grid">
                    <?php foreach ($menuItems as $item): ?>
                        <div class="menu-item-card" data-url="/dishes?view=item&id=<?= $item['ITEM_ID'] ?>" style="cursor: pointer;">
                            <?php if ($isLoggedIn): ?>
                                <form method="POST" action="/favorites/toggle" class="inline-form">
                                    <input type="hidden" name="reference_id" value="<?= $item['ITEM_ID'] ?>">
                                    <input type="hidden" name="reference_type" value="ITEM">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <button type="submit" class="btn-favorite btn-favorite-item <?= isset($data['itemFavorites'][$item['ITEM_ID']]) && $data['itemFavorites'][$item['ITEM_ID']] ? 'active' : '' ?>">
                                        <i class="fas fa-heart"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <button
                                    type="button"
                                    class="btn-favorite btn-favorite-item"
                                    onclick="openLoginModal('Please login to continue','You need to be logged in to add item to favorites.')">
                                    <i class="fas fa-heart"></i>
                                </button>
                            <?php endif; ?>
                            <div class="k-badge">
                                <i class="fas fa-utensils"></i>
                                <a href="/kitchens?view=kitchen&id=<?= $item['KITCHEN_ID'] ?>">
                                    <?= htmlspecialchars($item['KITCHEN_NAME']) ?>
                                </a>
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

                                        <div class="daily-stock">
                                            <i class="fa-solid fa-boxes-stacked"></i>
                                            <?= htmlspecialchars($item['DAILY_STOCK']) ?>
                                        </div>
                                    </div>

                                    <!-- Rating -->
                                    <div class="item-rating">
                                        <i class="fas fa-star"></i>
                                        <?= round($item['AVG_RATING'], 1) ?>
                                        <span>(<?= $item['REVIEW_COUNT'] ?>)</span>
                                    </div>
                                </div>
                                <!-- <div class="service-areas">
                                    <i class="fa-solid fa-person-biking"></i>
                                    <span title="<?= !empty($item['SERVICE_AREAS']) ? htmlspecialchars($item['SERVICE_AREAS']) : '' ?>">
                                        <?php if (!empty($item['SERVICE_AREAS'])): ?>
                                            <?php
                                            $areas = explode(', ', $item['SERVICE_AREAS']);
                                            if (count($areas) > 4) {
                                                echo htmlspecialchars(implode(', ', array_slice($areas, 0, 4)) . '...');
                                            } else {
                                                echo htmlspecialchars($item['SERVICE_AREAS']);
                                            }
                                            ?>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </span>
                                </div> -->
                            </div>

                            <!-- Item Footer -->
                            <div class="item-footer">
                                <div class="item-price">
                                    ৳<?= htmlspecialchars($item['PRICE']) ?>
                                </div>

                                <div class="item-actions">
                                    <?php if ($isLoggedIn): ?>
                                        <form method="POST" action="/cart/add" class="inline-form">
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

                    <h3 class="empty-title">No Dishes Available Right Now</h3>

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
    </section>

    <?php
    $fillColor = '#FFFBEB';
    $invert = true;

    include BASE_PATH . '/src/views/components/divider-banner.php';
    ?>

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
                    <div>
                        <h4 id="loginModalTitle">Please login to continue</h4>
                        <p id="loginModalMessage">
                            You need to be logged in to add dishes to favorites.
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
        const priceSort = document.getElementById('priceSort');

        if (locationFilter) {
            locationFilter.addEventListener('change', updateFilters);
        }

        if (priceSort) {
            priceSort.addEventListener('change', updateFilters);
        }

        function updateFilters() {
            const params = new URLSearchParams(window.location.search);

            // Get current values
            const location = locationFilter ? locationFilter.value : '';
            const price = priceSort ? priceSort.value : '';

            // Update parameters
            if (location) {
                params.set('location', location);
            } else {
                params.delete('location');
            }

            if (price) {
                params.set('price', price);
            } else {
                params.delete('price');
            }

            // Remove page parameter when filters change
            params.delete('page');

            // Update URL
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

        document.querySelectorAll('.menu-item-card').forEach(card => {
            card.addEventListener('click', function() {
                window.location.href = this.dataset.url;
            });
        });
    });
</script>