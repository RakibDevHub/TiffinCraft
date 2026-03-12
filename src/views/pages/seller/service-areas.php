<?php
$user = $data['currentUser'];
$kitchen = $data['kitchen'];
$serviceAreas = $data['serviceAreas'];
$availableAreas = $data['availableAreas'];

include BASE_PATH . '/src/views/components/flash-popup.php';
?>

<!-- Service Areas Management Page -->
<div class="page-header">
    <h1 class="page-title">Manage Service Areas</h1>
    <p class="page-subtitle">Configure delivery areas and fees for your kitchen</p>
</div>

<div class="dashboard-grid">
    <!-- Current Service Areas -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3>Current Service Areas</h3>

            <div style="display:flex; gap:10px; align-items:center;">
                <span class="badge"><?= count($serviceAreas) ?> areas</span>

                <button class="btn btn-primary"
                    onclick="openModal('addModal')">
                    <i class="fas fa-plus"></i> Add Service Area
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($serviceAreas)): ?>
                <div class="no-data">
                    <i class="fas fa-map-marker-alt"></i>
                    <p>No service areas configured</p>
                    <p class="text-muted">Add service areas to start receiving orders</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Area Name</th>
                                <th>City</th>
                                <th>Delivery Fee</th>
                                <th>Min Order</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($serviceAreas as $area): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($area['AREA_NAME']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($area['CITY']) ?></td>
                                    <td>
                                        <span class="text-success">৳<?= number_format($area['DELIVERY_FEE'], 2) ?></span>
                                    </td>
                                    <td>
                                        <span class="text-info">৳<?= number_format($area['MIN_ORDER'], 2) ?></span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-edit"
                                                onclick="openEditModal(<?= $area['AREA_ID'] ?>, '<?= htmlspecialchars($area['AREA_NAME']) ?>', <?= $area['DELIVERY_FEE'] ?>, <?= $area['MIN_ORDER'] ?>)"
                                                title="Edit Area">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-action btn-delete"
                                                onclick="openDeleteModal(<?= $area['AREA_ID'] ?>, '<?= htmlspecialchars($area['AREA_NAME']) ?>')"
                                                title="Remove Area">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add New Area -->
<!-- <div class="dashboard-card">
    <div class="card-header">
        <h3>Add Service Area</h3>
    </div>
    <div class="card-body">
        <form method="POST" id="addAreaForm">
            <input type="hidden" name="action" value="add_area">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="form-group">
                <label class="form-label">Select Area</label>
                <select class="form-control" name="area_id" id="areaSelect" required>
                    <option value="">Choose an area...</option>
                    <?php foreach ($availableAreas as $area): ?>
                        <?php
                        $isAdded = in_array($area['AREA_ID'], array_column($serviceAreas, 'AREA_ID'));
                        ?>
                        <option value="<?= $area['AREA_ID'] ?>"
                            <?= $isAdded ? 'disabled style="color: #6c757d;"' : '' ?>
                            data-city="<?= htmlspecialchars($area['CITY']) ?>">
                            <?= htmlspecialchars($area['NAME']) ?>
                            (<?= htmlspecialchars($area['CITY']) ?>)
                            <?= $isAdded ? ' - Already added' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="form-text">Select an area from your city to serve</small>
            </div>

            <div class="form-group">
                <label class="form-label">Delivery Fee (৳)</label>
                <input type="number" class="form-control" name="delivery_fee"
                    min="0" step="0.01" value="30" required>
                <small class="form-text">Delivery charge for this area</small>
            </div>

            <div class="form-group">
                <label class="form-label">Minimum Order (৳)</label>
                <input type="number" class="form-control" name="min_order"
                    min="0" step="0.01" value="150" required>
                <small class="form-text">Minimum order amount for delivery</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Service Area
                </button>
            </div>
        </form>
    </div>
</div> -->


<!-- Add Service Area Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-plus"></i>
                Add Service Area
            </h2>
            <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
        </div>

        <form method="POST" id="addAreaForm">
            <input type="hidden" name="action" value="add_area">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="modal-body">

                <div class="form-group">
                    <label class="form-label">Select Area</label>
                    <select class="form-control" name="area_id" required>
                        <option value="">Choose an area...</option>

                        <?php foreach ($availableAreas as $area): ?>
                            <?php
                            $isAdded = in_array($area['AREA_ID'], array_column($serviceAreas, 'AREA_ID'));
                            ?>

                            <option value="<?= $area['AREA_ID'] ?>"
                                <?= $isAdded ? 'disabled' : '' ?>>
                                <?= htmlspecialchars($area['NAME']) ?>
                                (<?= htmlspecialchars($area['CITY']) ?>)
                                <?= $isAdded ? ' - Already added' : '' ?>
                            </option>

                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Delivery Fee (৳)</label>
                    <input type="number" class="form-control"
                        name="delivery_fee"
                        min="0"
                        step="0.01"
                        value="30"
                        required>
                </div>

                <div class="form-group">
                    <label class="form-label">Minimum Order (৳)</label>
                    <input type="number"
                        class="form-control"
                        name="min_order"
                        min="0"
                        step="0.01"
                        value="150"
                        required>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button"
                    class="btn btn-secondary"
                    onclick="closeModal('addModal')">
                    Cancel
                </button>

                <button type="submit"
                    class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Area
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Area Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-edit"></i>
                Edit Service Area
            </h2>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST" id="editAreaForm">
            <input type="hidden" name="action" value="update_area">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="area_id" id="editAreaId">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Area Name</label>
                    <input type="text" class="form-control" id="editAreaName" readonly>
                </div>

                <div class="form-group">
                    <label class="form-label">Delivery Fee (৳)</label>
                    <input type="number" class="form-control" name="delivery_fee" id="editDeliveryFee"
                        min="0" step="0.01" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Minimum Order (৳)</label>
                    <input type="number" class="form-control" name="min_order" id="editMinOrder"
                        min="0" step="0.01" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Area</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal modal-sm">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-exclamation-triangle text-warning"></i>
                Confirm Removal
            </h2>
            <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
        </div>
        <form method="POST" id="deleteAreaForm">
            <input type="hidden" name="action" value="remove_area">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="area_id" id="deleteAreaId">

            <div class="modal-body">
                <div class="confirmation-content">
                    <div class="confirmation-icon">
                        <i class="fas fa-trash-alt text-danger"></i>
                    </div>
                    <div class="confirmation-text">
                        <h4>Remove Service Area</h4>
                        <p>Are you sure you want to remove <strong id="deleteAreaName"></strong> from your service areas?</p>
                        <p class="text-muted">This action cannot be undone. Orders from this area will no longer be accepted.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Remove Area
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) {
        document.getElementById(id).classList.add('active');
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    function openEditModal(areaId, areaName, deliveryFee, minOrder) {
        document.getElementById('editAreaId').value = areaId;
        document.getElementById('editAreaName').value = areaName;
        document.getElementById('editDeliveryFee').value = deliveryFee;
        document.getElementById('editMinOrder').value = minOrder;

        openModal('editModal');
    }

    function openDeleteModal(areaId, areaName) {
        document.getElementById('deleteAreaId').value = areaId;
        document.getElementById('deleteAreaName').textContent = areaName;

        openModal('deleteModal');
    }

    document.addEventListener('DOMContentLoaded', function() {

        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    closeModal(overlay.id);
                }
            });
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal('addModal');
                closeModal('editModal');
                closeModal('deleteModal');
            }
        });

    });
</script>