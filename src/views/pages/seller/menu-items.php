<?php

$user = $data['currentUser'];
$kitchen = $data['kitchen'];
$menuItems = $data['menuItems'];
$allCategories = $data['allCategories'];
$currentItemCount = $data['currentItemCount'];
$maxItems = $data['maxItems'];
$canAddMore = $data['canAddMore'];
$activeSubscription = $data['activeSubscription'];

// Categorize menu items by their actual status
$availableItems = [];
$manuallyUnavailableItems = [];
$outOfStockItems = [];

foreach ($menuItems as $item) {
    if ($item['IS_AVAILABLE'] == 1 && $item['DAILY_STOCK'] > 0) {
        $availableItems[] = $item; // ACTIVE: Available and in stock
    } elseif ($item['IS_AVAILABLE'] == 0) {
        $manuallyUnavailableItems[] = $item; // HIDDEN: Manually hidden by seller
    } elseif ($item['DAILY_STOCK'] == 0) {
        $outOfStockItems[] = $item; // OUT OF STOCK: Available toggle ON but no stock
    }
}

// For the "Unavailable" tab - combine all items that customers cannot order
$unavailableItems = array_merge($manuallyUnavailableItems, $outOfStockItems);

// For stats
$availableCount = count($availableItems);
$unavailableCount = count($unavailableItems);
$outOfStockCount = count($outOfStockItems);
$hiddenCount = count($manuallyUnavailableItems);

// For the item limit check - still use total items
$totalItems = count($menuItems);

// ... rest of the code ...

function formatPrice($price)
{
    return '৳' . number_format($price, 2);
}

function getSpiceLevelText($level)
{
    $levels = [1 => 'Mild', 2 => 'Medium', 3 => 'Spicy'];
    return $levels[$level] ?? 'Mild';
}

function getSpiceLevelIcon($level)
{
    $icons = [
        1 => '<i class="fas fa-pepper-hot" style="opacity: 0.5;"></i>',
        2 => '<i class="fas fa-pepper-hot"></i>',
        3 => '<i class="fas fa-pepper-hot" style="color: #ef4444;"></i>'
    ];
    return $icons[$level] ?? '<i class="fas fa-pepper"></i>';
}

include BASE_PATH . '/src/views/components/flash-popup.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title"><?= htmlspecialchars(ucfirst($title)) ?></h1>
    <p class="page-subtitle">Manage your menu items and categories</p>
</div>

<!-- Menu Statistics Grid -->
<div class="stats-grid-wrapper">
    <div class="stats-grid">
        <!-- Current Items Card -->
        <div class="stat-card">
            <div class="stat-icon-wrapper" style="background: rgba(79, 70, 229, 0.1); color: #4f46e5;">
                <i class="fas fa-utensils"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $currentItemCount ?></div>
                <div class="stat-label">Current Items</div>
                <div class="stat-trend">
                    <span class="trend-badge">
                        <i class="fas fa-box"></i> <?= count($availableItems) ?> available
                    </span>
                </div>
            </div>
        </div>

        <!-- Plan Limit Card -->
        <div class="stat-card">
            <div class="stat-icon-wrapper" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $maxItems === 0 ? '∞' : $maxItems ?></div>
                <div class="stat-label">Plan Limit</div>
                <div class="stat-trend">
                    <span class="trend-badge">
                        <i class="fas fa-crown"></i> <?= htmlspecialchars($activeSubscription['PLAN_NAME'] ?? 'Current Plan') ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Can Add Card -->
        <div class="stat-card">
            <div class="stat-icon-wrapper" style="background: <?= $canAddMore ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)' ?>; 
                color: <?= $canAddMore ? '#10b981' : '#ef4444' ?>;">
                <i class="fas <?= $canAddMore ? 'fa-plus-circle' : 'fa-ban' ?>"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $maxItems === 0 ? '∞' : ($maxItems - $currentItemCount) ?></div>
                <div class="stat-label">Can Add</div>
                <div class="stat-trend">
                    <span class="trend-badge <?= !$canAddMore && $maxItems > 0 ? 'warning' : '' ?>">
                        <?php if ($maxItems === 0): ?>
                            <i class="fas fa-infinity"></i> Unlimited
                        <?php elseif ($canAddMore): ?>
                            <i class="fas fa-check-circle"></i> Slots available
                        <?php else: ?>
                            <i class="fas fa-exclamation-triangle"></i> Limit reached
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Stock Overview Card -->
        <div class="stat-card">
            <div class="stat-icon-wrapper" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $outOfStockCount ?></div>
                <div class="stat-label">Out of Stock</div>
                <div class="stat-trend">
                    <span class="trend-badge" style="background: rgba(107, 114, 128, 0.1); color: #6b7280;">
                        <i class="fas fa-eye-slash"></i> <?= $hiddenCount ?> hidden
                    </span>
                    <span class="trend-badge" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                        <i class="fas fa-exclamation-circle"></i> <?= $outOfStockCount ?> out of stock
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Limit Warning Alert -->
<?php if (!$canAddMore && $maxItems > 0): ?>
    <div class="dashboard-card" style="background: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.2); margin-bottom: 2rem;">
        <div class="card-body" style="display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem;">
            <div style="background: rgba(245, 158, 11, 0.1); color: #f59e0b; width: 2.5rem; height: 2.5rem; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div style="flex: 1;">
                <strong style="color: var(--gray-800);">Item limit reached</strong>
                <p style="margin: 0.25rem 0 0 0; color: var(--gray-600); font-size: 0.875rem;">
                    You have reached your maximum limit of <?= $maxItems ?> menu items.
                    <a href="/business/dashboard/subscriptions" style="color: #f59e0b; text-decoration: none; font-weight: 500; margin-left: 0.5rem;">
                        Upgrade your plan <i class="fas fa-arrow-right" style="font-size: 0.75rem;"></i>
                    </a>
                </p>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Menu Management Dashboard Card -->
<div class="dashboard-card">
    <div class="card-header">
        <div class="header-left">
            <i class="fas fa-utensils"></i>
            <h3>Menu Items</h3>
        </div>

        <div class="header-tabs">
            <button class="tab-btn active" data-tab="all">
                <i class="fas fa-list"></i>
                All Items
                <span class="tab-count"><?= $totalItems ?></span>
            </button>

            <button class="tab-btn" data-tab="available">
                <i class="fas fa-check-circle"></i>
                Available
                <span class="tab-count"><?= $availableCount ?></span>
            </button>

            <button class="tab-btn" data-tab="unavailable">
                <i class="fas fa-times-circle"></i>
                Unavailable
                <span class="tab-count"><?= $unavailableCount ?></span>
            </button>
        </div>

        <div class="header-actions">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="menuSearch" placeholder="Search menu items..." class="search-input">
            </div>

            <div class="filter-dropdown">
                <select id="categoryFilter" class="filter-select">
                    <option value="">All Categories</option>
                    <?php foreach ($allCategories as $category): ?>
                        <option value="<?= htmlspecialchars($category['NAME']) ?>">
                            <?= htmlspecialchars($category['NAME']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <i class="fas fa-chevron-down"></i>
            </div>

            <div class="filter-dropdown">
                <select id="spiceFilter" class="filter-select">
                    <option value="">All Spice Levels</option>
                    <option value="1">Mild</option>
                    <option value="2">Medium</option>
                    <option value="3">Spicy</option>
                </select>
                <i class="fas fa-chevron-down"></i>
            </div>

            <button class="btn btn-secondary" id="clearFiltersBtn">
                <i class="fas fa-times"></i> Clear
            </button>

            <?php if ($canAddMore): ?>
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Add Item
                </button>
            <?php else: ?>
                <button class="btn btn-primary" disabled style="opacity:0.5;cursor:not-allowed;" title="Item limit reached (<?= $maxItems ?> max)">
                    <i class="fas fa-plus"></i> Add Item
                </button>
            <?php endif; ?>
        </div>
    </div>


    <div class="card-body">

        <!-- ALL ITEMS TAB -->
        <div class="tab-pane active" id="allTab">

            <div class="menu-items-grid" id="menuItemsGrid">
                <?php foreach ($menuItems as $item): ?>
                    <?php menu_item_card($item, $csrfToken); ?>
                <?php endforeach; ?>
            </div>

            <div class="empty-state" style="<?= empty($menuItems) ? '' : 'display:none;' ?>">
                <div class="empty-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h4>No items found</h4>
                <p>Try adjusting your search or filter criteria.</p>
            </div>

        </div>


        <!-- AVAILABLE TAB -->
        <div class="tab-pane" id="availableTab">

            <div class="menu-items-grid">
                <?php foreach ($availableItems as $item): ?>
                    <?php menu_item_card($item, $csrfToken); ?>
                <?php endforeach; ?>
            </div>

            <div class="empty-state" style="<?= empty($availableItems) ? '' : 'display:none;' ?>">
                <div class="empty-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h4>No available items</h4>
                <p>You don't have any items available for ordering.</p>
            </div>

        </div>


        <!-- UNAVAILABLE TAB -->
        <div class="tab-pane" id="unavailableTab">

            <div class="menu-items-grid">
                <?php foreach ($unavailableItems as $item): ?>
                    <?php menu_item_card($item, $csrfToken); ?>
                <?php endforeach; ?>
            </div>

            <div class="empty-state" style="<?= empty($unavailableItems) ? '' : 'display:none;' ?>">
                <div class="empty-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <h4>No unavailable items</h4>
                <p>All your menu items are currently available for ordering.</p>
            </div>

        </div>

    </div>
</div>

<!-- Add Item Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <form method="POST" enctype="multipart/form-data">

            <div class="modal-header">
                <div class="modal-title-wrapper">
                    <i class="fas fa-plus" style="color: #4f46e5;"></i>
                    <h3 class="modal-title">Add New Menu Item</h3>
                </div>
                <button type="button" class="modal-close" onclick="closeModal('addModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add_item">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div class="form-group">
                    <label class="form-label">Item Name <span class="required">*</span></label>
                    <input type="text" class="form-control" name="name" required maxlength="100"
                        placeholder="e.g., Grilled Chicken Sandwich">
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="3" maxlength="500"
                        placeholder="Describe your menu item..."></textarea>
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Price (৳) <span class="required">*</span></label>
                        <input type="number" class="form-control" name="price" step="0.01" min="0" required
                            placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Portion Size</label>
                        <input type="text" class="form-control" name="portion_size" maxlength="20"
                            placeholder="e.g., Regular, Large">
                    </div>
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Daily Stock <span class="required">*</span></label>
                        <input type="number" class="form-control" name="daily_stock" min="0" value="10" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Spice Level <span class="required">*</span></label>
                        <select class="form-control" name="spice_level" required>
                            <option value="1">🌶️ Mild</option>
                            <option value="2" selected>🌶️🌶️ Medium</option>
                            <option value="3">🌶️🌶️🌶️ Spicy</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Categories</label>
                    <div class="categories-grid">
                        <?php foreach ($allCategories as $category): ?>
                            <label class="category-checkbox">
                                <input type="checkbox" name="category_ids[]" value="<?= $category['CATEGORY_ID'] ?>">
                                <span class="checkmark"></span>
                                <?= htmlspecialchars($category['NAME']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?php if (empty($allCategories)): ?>
                        <p class="form-text" style="color: var(--gray-500); margin-top: 0.5rem;">
                            No categories available. Please contact admin to add categories.
                        </p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label">Item Image</label>
                    <div class="file-upload-area">
                        <input type="file" id="add_imageUpload" name="item_image"
                            accept="image/jpeg,image/jpg,image/png,image/webp"
                            onchange="previewImage(this, 'addPreview')" hidden>
                        <div class="file-upload-placeholder" onclick="document.getElementById('add_imageUpload').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Click to upload image</p>
                            <span>JPG, PNG or WebP (Max 2MB)</span>
                        </div>
                        <div id="addPreview" class="image-preview"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="toggle-switch">
                        <input type="checkbox" name="is_available" value="1" checked>
                        <span class="toggle-slider"></span>
                        <span class="toggle-label">Available for ordering</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Menu Item
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Item Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <form id="editForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_item">
            <input type="hidden" name="item_id" id="edit_item_id">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-header">
                <div class="modal-title-wrapper">
                    <i class="fas fa-edit" style="color: #4f46e5;"></i>
                    <h3 class="modal-title">Edit Menu Item</h3>
                </div>
                <button type="button" class="modal-close" onclick="closeModal('editModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">


                <div class="form-group">
                    <label class="form-label">Item Name <span class="required">*</span></label>
                    <input type="text" class="form-control" id="edit_name" name="name" required maxlength="100">
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea id="edit_description" class="form-control" name="description" rows="3" maxlength="500"></textarea>
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Price (৳) <span class="required">*</span></label>
                        <input type="number" class="form-control" id="edit_price" name="price" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Portion Size</label>
                        <input type="text" class="form-control" id="edit_portion_size" name="portion_size" maxlength="20">
                    </div>
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Spice Level <span class="required">*</span></label>
                        <select class="form-control" id="edit_spice_level" name="spice_level" required>
                            <option value="1">🌶️ Mild</option>
                            <option value="2">🌶️🌶️ Medium</option>
                            <option value="3">🌶️🌶️🌶️ Spicy</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Daily Stock <span class="required">*</span></label>
                        <input type="number" class="form-control" id="edit_daily_stock" name="daily_stock" min="0" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Categories</label>
                    <div class="categories-grid" id="editCategoriesGrid">
                        <?php foreach ($allCategories as $category): ?>
                            <label class="category-checkbox">
                                <input type="checkbox" name="category_ids[]" value="<?= $category['CATEGORY_ID'] ?>"
                                    class="edit-category" id="edit_category_<?= $category['CATEGORY_ID'] ?>">
                                <span class="checkmark"></span>
                                <?= htmlspecialchars($category['NAME']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Item Image</label>
                    <div class="file-upload-area">
                        <input type="file" id="edit_imageUpload" name="item_image"
                            accept="image/jpeg,image/jpg,image/png,image/webp"
                            onchange="previewImage(this, 'editPreview')" hidden>
                        <div class="file-upload-placeholder" onclick="document.getElementById('edit_imageUpload').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Click to change image</p>
                            <span>Leave empty to keep current image</span>
                        </div>
                        <div id="editPreview" class="image-preview"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="toggle-switch">
                        <input type="checkbox" name="is_available" value="1" id="edit_is_available">
                        <span class="toggle-slider"></span>
                        <span class="toggle-label">Available for ordering</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Item
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal modal-sm">
        <div class="modal-header">
            <div class="modal-title-wrapper">
                <i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i>
                <h3 class="modal-title">Delete Menu Item</h3>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('deleteModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form method="POST" id="deleteItemForm">
                <input type="hidden" name="action" value="delete_item">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="item_id" id="deleteItemId">

                <div style="text-align: center; padding: 1rem;">
                    <div style="font-size: 3rem; color: #ef4444; margin-bottom: 1rem;">
                        <i class="fas fa-trash-alt"></i>
                    </div>
                    <h4 style="margin: 0 0 0.5rem 0; color: var(--gray-800);">Delete Menu Item</h4>
                    <p style="color: var(--gray-600); margin-bottom: 1rem;">
                        Are you sure you want to delete <strong id="deleteItemName"></strong>?
                    </p>
                    <p style="color: var(--gray-500); font-size: 0.875rem;">
                        This action cannot be undone. Order history will be preserved.
                    </p>
                </div>

                <div class="form-actions" style="justify-content: center;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
function menu_item_card($item, $csrfToken)
{
?>
    <div class="menu-item-card <?= (!$item['IS_AVAILABLE'] || $item['DAILY_STOCK'] == 0) ? 'unavailable' : '' ?>"
        data-item-id="<?= $item['ITEM_ID'] ?>"
        data-name="<?= htmlspecialchars(strtolower($item['NAME'])) ?>"
        data-status="<?= ($item['IS_AVAILABLE'] && $item['DAILY_STOCK'] > 0) ? 'available' : 'unavailable' ?>"
        data-spice="<?= $item['SPICE_LEVEL'] ?>"
        data-categories="<?= htmlspecialchars(strtolower($item['CATEGORIES'] ?? '')) ?>">

        <div class="menu-item-image-wrapper">
            <?php if ($item['ITEM_IMAGE']): ?>
                <img src="/uploads/menu/<?= htmlspecialchars($item['ITEM_IMAGE']) ?>"
                    alt="<?= htmlspecialchars($item['NAME']) ?>"
                    class="menu-item-image">
            <?php else: ?>
                <div class="menu-item-image-placeholder">
                    <i class="fas fa-utensils"></i>
                </div>
            <?php endif; ?>

            <?php if (!$item['IS_AVAILABLE'] || $item['DAILY_STOCK'] == 0): ?>
                <div class="menu-item-status-overlay">
                    <span class="status-badge hidden">
                        <i class="fas fa-eye-slash"></i> Unavailable
                    </span>
                </div>
            <?php endif; ?>

            <div class="menu-item-badges">
                <span class="price-badge">
                    <i class="fas fa-tag"></i> <?= formatPrice($item['PRICE']) ?>
                </span>
                <span class="spice-badge level-<?= $item['SPICE_LEVEL'] ?>">
                    <?= getSpiceLevelIcon($item['SPICE_LEVEL']) ?>
                    <?= getSpiceLevelText($item['SPICE_LEVEL']) ?>
                </span>
            </div>
        </div>

        <div class="menu-item-content">
            <div class="menu-item-header">
                <h3 class="menu-item-name"><?= htmlspecialchars($item['NAME']) ?></h3>
                <?php if ($item['PORTION_SIZE']): ?>
                    <span class="portion-badge">
                        <i class="fas fa-utensil-spoon"></i> <?= htmlspecialchars($item['PORTION_SIZE']) ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($item['DESCRIPTION']): ?>
                <p class="menu-item-description">
                    <!-- <?= strlen($item['DESCRIPTION']) > 100
                                ? htmlspecialchars(substr($item['DESCRIPTION'], 0, 97) . '...')
                                : htmlspecialchars($item['DESCRIPTION']) ?> -->
                    <?= htmlspecialchars($item['DESCRIPTION']) ?>
                </p>
            <?php endif; ?>

            <?php if ($item['CATEGORIES']): ?>
                <div class="menu-item-categories">
                    <i class="fas fa-tags"></i>
                    <?= htmlspecialchars($item['CATEGORIES']) ?>
                </div>
            <?php endif; ?>

            <div class="menu-item-stats">
                <div class="stock-info">
                    <i class="fas fa-box"></i>
                    <span class="stock-count <?= $item['DAILY_STOCK'] == 0 ? 'out-of-stock' : ($item['DAILY_STOCK'] <= 5 ? 'low-stock' : '') ?>">
                        <?= $item['DAILY_STOCK'] ?> in stock
                    </span>
                </div>
                <span class="menu-item-status <?= $item['IS_AVAILABLE'] && $item['DAILY_STOCK'] > 0 ? 'available' : 'unavailable' ?>">
                    <i class="fas <?= $item['IS_AVAILABLE'] && $item['DAILY_STOCK'] > 0 ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                    <?= $item['IS_AVAILABLE'] && $item['DAILY_STOCK'] > 0 ? 'Available' : 'Unavailable' ?>
                </span>
            </div>

            <div class="menu-item-actions">
                <!-- Stock Update Form -->
                <form method="POST" class="stock-form">
                    <input type="hidden" name="action" value="update_stock">
                    <input type="hidden" name="item_id" value="<?= $item['ITEM_ID'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <div class="stock-update">
                        <input type="number" name="daily_stock" value="<?= $item['DAILY_STOCK'] ?>" min="0"
                            class="stock-input" placeholder="Stock">
                        <button type="submit" class="btn btn-sm btn-info" title="Update Stock">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </form>

                <!-- Toggle Availability Form -->
                <form method="POST" class="toggle-form">
                    <input type="hidden" name="action" value="toggle_availability">
                    <input type="hidden" name="item_id" value="<?= $item['ITEM_ID'] ?>">
                    <input type="hidden" name="is_available" value="<?= $item['IS_AVAILABLE'] ? '0' : '1' ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <button type="submit"
                        class="btn btn-sm <?= $item['IS_AVAILABLE'] ? 'btn-warning' : 'btn-success' ?>"
                        title="<?= $item['IS_AVAILABLE'] ? 'Hide from menu' : 'Show in menu' ?>">
                        <i class="fas <?= $item['IS_AVAILABLE'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                    </button>
                </form>

                <!-- Edit Button -->
                <button type="button" class="btn btn-sm btn-primary"
                    onclick="editItem(<?= htmlspecialchars(json_encode($item)) ?>)"
                    title="Edit item">
                    <i class="fas fa-edit"></i>
                </button>

                <!-- Delete Button -->
                <button type="button" class="btn btn-sm btn-danger"
                    onclick="openDeleteModal(<?= $item['ITEM_ID'] ?>, '<?= htmlspecialchars(addslashes($item['NAME'])) ?>')"
                    title="Delete item">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
<?php
}
?>