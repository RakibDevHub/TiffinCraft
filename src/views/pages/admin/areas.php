<?php
$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 50;
$totalAreas = $data['totalAreas'] ?? 0;
$totalPages = ceil($totalAreas / $limit);
$areasData = $data['areasData'] ?? [];

include BASE_PATH . '/src/views/components/flash-popup.php';
?>

<!-- Page Header  -->
<div class="page-header">
    <h1 class="page-title"><?= htmlspecialchars(ucfirst($title)) ?></h1>
    <p class="page-subtitle">Manage service areas, edit details, and track kitchen associations</p>
</div>

<!-- Filters and Search -->
<div class="filters-container">
    <div class="search-box">
        <i class="fas fa-search search-icon"></i>
        <input type="text" class="search-input" placeholder="Search areas by name..." id="areaSearch">
    </div>

    <div class="filter-group">
        <select class="filter-select" id="statusFilter">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
        <select class="filter-select" id="cityFilter">
            <option value="">All Cities</option>
            <?php
            $cities = array_unique(array_column($areasData, 'CITY'));
            foreach ($cities as $city):
                if (!empty($city)):
            ?>
                    <option value="<?= htmlspecialchars($city) ?>"><?= htmlspecialchars($city) ?></option>
            <?php
                endif;
            endforeach; ?>
        </select>
    </div>

    <div class="action-buttons-group">
        <button class="btn btn-secondary" id="clearFilters">
            <i class="fas fa-times"></i> Clear
        </button>
        <button class="btn btn-primary" id="addAreaBtn">
            <i class="fas fa-plus"></i> Add Area
        </button>
    </div>
</div>

<!-- Areas Table -->
<div class="dashboard-card">
    <!-- Top Pagination -->
    <div class="card-header">
        <div class="pagination-info">
            Showing <?= ($page - 1) * $limit + 1 ?> to <?= min($page * $limit, $totalAreas) ?> of <?= $totalAreas ?> areas
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
                        <th>Area Name</th>
                        <th>City</th>
                        <th>Associated Kitchens</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($areasData)): ?>
                        <tr>
                            <td colspan="5" class="no-data">
                                <i class="fas fa-map-marker-alt"></i>
                                <p>No service areas found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($areasData as $area): ?>
                            <tr data-area-id="<?= htmlspecialchars($area['AREA_ID'] ?? '') ?>"
                                data-status="<?= strtolower($area['STATUS'] ?? 'active') ?>"
                                data-city="<?= htmlspecialchars($area['CITY'] ?? '') ?>">
                                <td style="width: auto;">
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <i class="fas fa-map-marker-alt fa-2x" style="color: #4a6cf7;"></i>
                                        </div>
                                        <div class="user-details">
                                            <h4><?= htmlspecialchars($area['NAME'] ?? '') ?></h4>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="city-badge">
                                        <i class="fas fa-city"></i>
                                        <?= htmlspecialchars($area['CITY'] ?? 'Dhaka') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="performance-metrics">
                                        <div class="metric">
                                            <span class="metric-value"><?= htmlspecialchars($area['TOTAL_KITCHENS'] ?? '0') ?></span>
                                            <span class="metric-label">Kitchens</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($area['STATUS'] ?? 'active') ?>">
                                        <i class="fas <?= ($area['STATUS'] ?? 'active') === 'active' ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                                        <?= htmlspecialchars($area['STATUS'] ?? 'Active') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action btn-edit"
                                            onclick="openEditAreaModal(
                                                '<?= $area['AREA_ID'] ?>',
                                                '<?= htmlspecialchars(addslashes($area['NAME'])) ?>',
                                                '<?= htmlspecialchars(addslashes($area['CITY'] ?? 'Dhaka')) ?>',
                                                '<?= $area['STATUS'] ?? 'active' ?>'
                                            )"
                                            title="Edit Area">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-action btn-delete"
                                            onclick="openDeleteAreaModal(
                                                '<?= $area['AREA_ID'] ?>',
                                                '<?= htmlspecialchars(addslashes($area['NAME'])) ?>'
                                            )"
                                            title="Delete Area">
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
                    Showing <?= ($page - 1) * $limit + 1 ?> to <?= min($page * $limit, $totalAreas) ?> of <?= $totalAreas ?> areas
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

<!-- Add Area Modal -->
<div class="modal-overlay" id="addAreaModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-plus"></i>
                Add New Service Area
            </h2>
            <button class="modal-close" onclick="closeModal('addAreaModal')">&times;</button>
        </div>
        <form method="POST" action="/admin/dashboard/areas" id="addAreaForm">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Area Name *</label>
                    <input type="text" class="form-control" name="name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">City *</label>
                    <input type="text" class="form-control" name="city" value="Dhaka" required>
                </div>
                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="status" value="active" checked>
                        <span class="checkmark"></span>
                        Active Area
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addAreaModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Area</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Area Modal -->
<div class="modal-overlay" id="editAreaModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-edit"></i>
                Edit Service Area
            </h2>
            <button class="modal-close" onclick="closeModal('editAreaModal')">&times;</button>
        </div>
        <form method="POST" action="/admin/dashboard/areas" id="editAreaForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="area_id" id="editAreaId">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Area Name *</label>
                    <input type="text" class="form-control" name="name" id="editAreaName" required>
                </div>
                <div class="form-group">
                    <label class="form-label">City *</label>
                    <input type="text" class="form-control" name="city" id="editAreaCity" required>
                </div>
                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="status" id="editAreaStatus" value="active">
                        <span class="checkmark"></span>
                        Active Area
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editAreaModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Area</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Area Modal -->
<div class="modal-overlay" id="deleteAreaModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-trash"></i>
                Delete Service Area
            </h2>
            <button class="modal-close" onclick="closeModal('deleteAreaModal')">&times;</button>
        </div>
        <form method="POST" action="/admin/dashboard/areas" id="deleteAreaForm">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="area_id" id="deleteAreaId">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-body">
                <div class="confirmation-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Confirm Deletion</h3>
                    <p>Are you sure you want to delete the service area: <strong id="deleteAreaName"></strong>?</p>
                    <p>This action cannot be undone. Any kitchens associated with this area will have this area removed from their service zones.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('deleteAreaModal')">Cancel</button>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete Area
                </button>
            </div>
        </form>
    </div>
</div>