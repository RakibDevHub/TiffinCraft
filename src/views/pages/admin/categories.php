<?php
$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 50;
$totalCategories = $data['totalCategories'] ?? 0;
$totalPages = ceil($totalCategories / $limit);
$categoriesData = $data['categoriesData'] ?? [];

include BASE_PATH . '/src/views/components/flash-popup.php';
?>


<!-- Page Header  -->
<div class="page-header">
    <h1 class="page-title"><?= htmlspecialchars(ucfirst($title)) ?></h1>
    <p class="page-subtitle">Manage food categories, edit details, and track menu item associations</p>
</div>

<!-- Filters and Search -->
<div class="filters-container">
    <div class="search-box">
        <i class="fas fa-search search-icon"></i>
        <input type="text" class="search-input" placeholder="Search categories by name..." id="categorySearch">
    </div>

    <div class="action-buttons-group">
        <button class="btn btn-secondary" id="clearFilters">
            <i class="fas fa-times"></i> Clear
        </button>
        <button class="btn btn-primary" id="addCategoryBtn">
            <i class="fas fa-plus"></i> Add Category
        </button>
    </div>
</div>

<!-- Categories Table -->
<div class="dashboard-card">
    <!-- Top Pagination -->
    <div class="card-header">
        <div class="pagination-info">
            Showing <?= ($page - 1) * $limit + 1 ?> to <?= min($page * $limit, $totalCategories) ?> of <?= $totalCategories ?> categories
        </div>
        <?php if ($totalPages > 1): ?>
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
        <?php endif; ?>
        <div class="pagination-limit">
            <label>Show:</label>
            <select onchange="changeLimit(this.value)">
                <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
                <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100</option>
            </select>
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Menu Items</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categoriesData)): ?>
                        <tr>
                            <td colspan="5" class="no-data">
                                <i class="fas fa-tags"></i>
                                <p>No categories found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categoriesData as $category): ?>
                            <tr data-category-id="<?= htmlspecialchars($category['CATEGORY_ID'] ?? '') ?>">
                                <td>
                                    <div class="user-info">
                                        <?php if (!empty($category['IMAGE'])): ?>
                                            <img src="/uploads/categories/<?= htmlspecialchars($category['IMAGE']) ?>" class="user-avatar" alt="<?= htmlspecialchars($category['NAME'] ?? '') ?>">
                                        <?php else: ?>
                                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($category['NAME'] ?? '') ?>&background=4a6cf7&color=fff" class="user-avatar" alt="<?= htmlspecialchars($category['NAME'] ?? '') ?>">
                                        <?php endif; ?>
                                        <div class="user-details">
                                            <h4><?= htmlspecialchars($category['NAME'] ?? '') ?></h4>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="category-description">
                                        <?= !empty($category['DESCRIPTION']) ? htmlspecialchars($category['DESCRIPTION']) : 'No description provided' ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="performance-metrics">
                                        <div class="metric">
                                            <span class="metric-value"><?= htmlspecialchars($category['TOTAL_ITEMS'] ?? '0') ?></span>
                                            <span class="metric-label">Total Items</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action btn-edit"
                                            onclick="openEditCategoryModal(
                                                '<?= $category['CATEGORY_ID'] ?>',
                                                '<?= htmlspecialchars(addslashes($category['NAME'])) ?>',
                                                '<?= !empty($category['DESCRIPTION']) ? htmlspecialchars(addslashes($category['DESCRIPTION'])) : '' ?>',
                                                '<?= !empty($category['IMAGE']) ? htmlspecialchars($category['IMAGE']) : '' ?>'
                                            )"
                                            title="Edit Category">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-action btn-delete"
                                            onclick="openDeleteCategoryModal(
                                                '<?= $category['CATEGORY_ID'] ?>',
                                                '<?= htmlspecialchars(addslashes($category['NAME'])) ?>'
                                            )"
                                            title="Delete Category">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bottom Pagination -->
    <div class="card-footer">
        <?php if ($totalPages > 1): ?>
            <div class="table-pagination-bottom">
                <div class="pagination-info">
                    Showing <?= ($page - 1) * $limit + 1 ?> to <?= min($page * $limit, $totalCategories) ?> of <?= $totalCategories ?> categories
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
                    <select onchange="changeLimit(this.value)">
                        <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                        <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100</option>
                    </select>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal-overlay" id="addCategoryModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-plus"></i>
                Add New Category
            </h2>
            <button class="modal-close" onclick="closeModal('addCategoryModal')">&times;</button>
        </div>
        <form method="POST" action="/admin/dashboard/categories" id="addCategoryForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Category Name *</label>
                    <input type="text" class="form-control" name="name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="3" placeholder="Enter category description..."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Category Image</label>

                    <!-- Preview for Add -->
                    <div id="imagePreview" class="preview-container" style="margin-top: 10px;"></div>


                    <input type="file" id="imageUpload" name="image" accept="image/*" class="hidden">
                    <label for="imageUpload" class="file-upload-label">
                        <i class="fas fa-upload"></i> Choose Image
                    </label>

                    <small class="form-text">Recommended size: 300x300 pixels</small>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addCategoryModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal-overlay" id="editCategoryModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-edit"></i>
                Edit Category
            </h2>
            <button class="modal-close" onclick="closeModal('editCategoryModal')">&times;</button>
        </div>
        <form method="POST" action="/admin/dashboard/categories" id="editCategoryForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="category_id" id="editCategoryId">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Category Name *</label>
                    <input type="text" class="form-control" name="name" id="editCategoryName" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" id="editCategoryDescription" rows="3" placeholder="Enter category description..."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Category Image</label>
                    <!-- <input type="file" class="form-control" name="image" accept="image/*"> -->
                    
                    <input type="file" id="changeImage" name="image" accept="image/*" class="hidden">
                    <label for="changeImage" class="file-upload-label">
                        <i class="fas fa-upload"></i> Choose Image
                    </label>

                    <small class="form-text">Leave empty to keep current image</small>
                    <div id="currentImageContainer" class="current-image-container" style="margin-top: 10px;"></div>

                    <div id="editImagePreview" class="preview-container" style="margin-top: 10px;"></div>

                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editCategoryModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Category Modal -->
<div class="modal-overlay" id="deleteCategoryModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-trash"></i>
                Delete Category
            </h2>
            <button class="modal-close" onclick="closeModal('deleteCategoryModal')">&times;</button>
        </div>
        <form method="POST" action="/admin/dashboard/categories" id="deleteCategoryForm">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="category_id" id="deleteCategoryId">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-body">
                <div class="confirmation-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Confirm Deletion</h3>
                    <p>Are you sure you want to delete the category: <strong id="deleteCategoryName"></strong>?</p>
                    <p>This action cannot be undone. Any menu items associated with this category will have this category removed.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('deleteCategoryModal')">Cancel</button>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete Category
                </button>
            </div>
        </form>
    </div>
</div>