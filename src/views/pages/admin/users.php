<?php
$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 50;
$totalUsers = $data['totalUsers'] ?? 0;
$totalPages = ceil($totalUsers / $limit);
$userdata = $data['userdata'] ?? [];

function dateFormat($dateString, $format = 'M j, Y')
{
    if (!$dateString) {
        return '';
    }

    $date = DateTime::createFromFormat('d-M-y h.i.s.u A', $dateString);

    if ($date) {
        return $date->format($format);
    }

    return htmlspecialchars((string)$dateString);
}

include BASE_PATH . '/src/views/components/flash-popup.php';
?>

<!-- Page Header  -->
<div class="page-header">
    <h1 class="page-title"><?= htmlspecialchars(ucfirst($title)) ?></h1>
    <p class="page-subtitle">Manage buyer and seller accounts, suspend users, and add administrative staff</p>
</div>

<!-- Filters and Search -->
<div class="filters-container">
    <div class="search-box">
        <i class="fas fa-search search-icon"></i>
        <input type="text" class="search-input" placeholder="Search users by name or email..." id="userSearch">
    </div>

    <div class="filter-group">
        <select class="filter-select" id="roleFilter">
            <option value="">All Roles</option>
            <option value="buyer">Buyers</option>
            <option value="seller">Sellers</option>
            <option value="admin">Admins</option>
        </select>
        <select class="filter-select" id="statusFilter">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="suspended">Suspended</option>
            <option value="pending">Pending</option>
        </select>
    </div>

    <div class="action-buttons-group">
        <button class="btn btn-secondary" id="clearFilters">
            <i class="fas fa-times"></i> Clear
        </button>
        <button class="btn btn-primary" id="addAdminBtn">
            <i class="fas fa-user-plus"></i> Add Admin
        </button>
    </div>
</div>

<!-- Users Table -->
<div class="dashboard-card">
    <!-- Top Pagination -->
    <div class="card-header">
        <div class="pagination-info">
            Showing <?= ($page - 1) * $limit + 1 ?> to <?= min($page * $limit, $totalUsers) ?> of <?= $totalUsers ?> users
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
        <!-- <div class="table-responsive"> -->
        <table class="users-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($userdata)): ?>
                    <tr>
                        <td colspan="5" class="no-data">
                            <i class="fas fa-users"></i>
                            <p>No users found</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($userdata as $user): ?>
                        <?php
                        $roleClass = 'role-badge role-' . strtolower($user['ROLE'] ?? '');
                        $isCurrentUser = ($user['USER_ID'] ?? '') === ($currentUser['USER_ID'] ?? '');
                        $isAdmin = ($user['ROLE'] ?? '') === 'admin';
                        $isSuspended = !empty($user['is_suspended']) || ($user['STATUS'] ?? '') === 'suspended';
                        ?>
                        <tr data-user-id="<?= htmlspecialchars($user['USER_ID'] ?? '') ?>"
                            data-role="<?= strtolower($user['ROLE'] ?? '') ?>"
                            data-status="<?= $isSuspended ? 'suspended' : strtolower($user['STATUS'] ?? '') ?>">
                            <td>
                                <div class="user-info">
                                    <?php if (!empty($user['PROFILE_IMAGE'])): ?>
                                        <img src="/uploads/profile/<?= htmlspecialchars($user['PROFILE_IMAGE']) ?>" class="user-avatar" alt="<?= htmlspecialchars($user['NAME'] ?? '') ?>">
                                    <?php else: ?>
                                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['NAME'] ?? '') ?>&background=4a6cf7&color=fff" class="user-avatar" alt="<?= htmlspecialchars($user['NAME'] ?? '') ?>">
                                    <?php endif; ?>
                                    <div class="user-details">
                                        <h4><?= htmlspecialchars($user['NAME'] ?? '') ?></h4>
                                        <p><?= htmlspecialchars($user['EMAIL'] ?? '') ?></p>
                                        <?php if (!empty($user['PHONE'])): ?>
                                            <small class="text-muted"><?= htmlspecialchars($user['PHONE']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="<?= $roleClass ?>">
                                    <i class="fas <?= $isAdmin ? 'fa-user-shield' : ($user['ROLE'] === 'seller' ? 'fa-store' : 'fa-user') ?>"></i>
                                    <?= htmlspecialchars($user['ROLE'] ?? '') ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($isSuspended): ?>
                                    <div class="status-with-details">
                                        <span class="status-badge status-suspended">
                                            <i class="fas fa-ban"></i> Suspended
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <span class="status-badge status-<?= strtolower($user['STATUS'] ?? 'active') ?>">
                                        <i class="fas <?= ($user['STATUS'] ?? '') === 'active' ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                                        <?= htmlspecialchars($user['STATUS'] ?? 'Active') ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="date-info">
                                    <?php
                                    if (!empty($user['CREATED_AT'])) {
                                        echo dateFormat($user['CREATED_AT'], 'M j, Y g:i A');
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($isAdmin && !$isCurrentUser): ?>
                                        <button class="btn-action btn-delete" onclick="openDeleteModal('<?= $user['USER_ID'] ?>', '<?= htmlspecialchars(addslashes($user['NAME'])) ?>')" title="Delete User">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php elseif (!$isAdmin): ?>
                                        <?php if ($isSuspended): ?>
                                            <button class="btn-action btn-activate"
                                                onclick="openLiftSuspensionModal(
                                                        '<?= $user['USER_ID'] ?>', 
                                                        '<?= htmlspecialchars(addslashes($user['NAME'])) ?>',
                                                        '<?= !empty($user['SUSPENSION_REASON']) ? htmlspecialchars(addslashes($user['SUSPENSION_REASON'])) : '' ?>',
                                                        '<?= !empty($user['SUSPENDED_UNTIL']) ? dateFormat($user['SUSPENDED_UNTIL'], 'M j, Y g:i A') : '' ?>'
                                                    )"
                                                title="Lift Suspension">
                                                <i class="fas fa-unlock"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn-action btn-suspend"
                                                onclick="openSuspendModal('<?= $user['USER_ID'] ?>', '<?= htmlspecialchars(addslashes($user['NAME'])) ?>')"
                                                title="Suspend User">
                                                <i class="fa-solid fa-lock"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn-action btn-view" onclick="viewUserDetails('<?= $user['USER_ID'] ?>')" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($isCurrentUser): ?>
                                        <span class="text-muted" style="padding: 4px 10px; border: 1px solid; border-radius: 15px; font-size: 10px; font-weight: 700; color: #52c41a; background-color: #f6ffed;">Current User</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <!-- </div> -->
    </div>

    <!-- Bottom Pagination -->
    <div class="card-footer">
        <?php if ($totalPages > 1): ?>
            <div class="table-pagination-bottom">
                <div class="pagination-info">
                    Showing <?= ($page - 1) * $limit + 1 ?> to <?= min($page * $limit, $totalUsers) ?> of <?= $totalUsers ?> users
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

<!-- Suspend User Modal -->
<div class="modal-overlay" id="suspendModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-ban"></i>
                Suspend User
            </h2>
            <button class="modal-close" onclick="closeModal('suspendModal')">&times;</button>
        </div>
        <form method="POST" action="/admin/dashboard/users" id="suspendForm">
            <input type="hidden" name="action" value="suspend">
            <input type="hidden" name="user_id" id="suspendUserId">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">User</label>
                    <input type="text" class="form-control" id="suspendUserName" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">Suspension Period *</label>
                    <select class="form-control" name="period" id="suspendPeriod" required>
                        <option value="">Select period</option>
                        <option value="1">1 day</option>
                        <option value="3">3 days</option>
                        <option value="7">7 days</option>
                        <option value="30">30 days</option>
                        <option value="permanent">Permanent</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Reason for Suspension *</label>
                    <textarea class="form-control form-textarea" name="reason" id="suspendReason" placeholder="Please provide a detailed reason for suspension..." required rows="4"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('suspendModal')">Cancel</button>
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-ban"></i> Suspend User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal modal-danger">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-exclamation-triangle"></i>
                Delete User
            </h2>
            <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
        </div>
        <form method="POST" action="/admin/dashboard/users" id="deleteForm">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="user_id" id="deleteUserId">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-body">
                <div class="warning-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <h3>Warning: This action cannot be undone</h3>
                    <p>You are about to delete user: <strong id="deleteUserName"></strong></p>
                    <p>All user data will be permanently removed from the system.</p>
                </div>
                <div class="form-group">
                    <label class="form-label">Type "DELETE" to confirm *</label>
                    <input type="text" class="form-control" name="confirmation" placeholder="DELETE" required pattern="DELETE">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete Permanently
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add Admin Modal -->
<div class="modal-overlay" id="addAdminModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-user-plus"></i>
                Add New Admin
            </h2>
            <button class="modal-close" onclick="closeModal('addAdminModal')">&times;</button>
        </div>
        <form method="POST" action="/admin/dashboard/users" id="addAdminForm">
            <input type="hidden" name="action" value="add_admin">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" class="form-control" name="name" placeholder="Enter full name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address *</label>
                    <input type="email" class="form-control" name="email" placeholder="Enter email address" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number *</label>
                    <input type="text" class="form-control" name="phone" placeholder="01XXXXXXXXX" required minlength="11" maxlength="11" pattern="01[0-9]{9}">
                    <small class="form-text">Must be 11 digits starting with 01</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addAdminModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Add Admin
                </button>
            </div>
        </form>
    </div>
</div>

<!-- User Details Modal -->
<div class="modal-overlay" id="userDetailsModal">
    <div class="modal modal-large">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-user"></i>
                User Details
            </h2>
            <button class="modal-close" onclick="closeModal('userDetailsModal')">&times;</button>
        </div>
        <div class="modal-body">
            <?php foreach ($userdata as $user): ?>
                <div class="user-details-content" id="user-details-<?= htmlspecialchars($user['USER_ID'] ?? '') ?>" style="display: none;">
                    <div class="user-details-container">
                        <div class="user-profile-header">
                            <div class="user-avatar-large">
                                <?php if (!empty($user['PROFILE_IMAGE'])): ?>
                                    <img src="/uploads/profile/<?= htmlspecialchars($user['PROFILE_IMAGE']) ?>" alt="<?= htmlspecialchars($user['NAME'] ?? '') ?>">
                                <?php else: ?>
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['NAME'] ?? '') ?>&background=4a6cf7&color=fff" alt="<?= htmlspecialchars($user['NAME'] ?? '') ?>">
                                <?php endif; ?>
                            </div>
                            <div class="user-profile-info">
                                <h2><?= htmlspecialchars($user['NAME'] ?? 'N/A') ?></h2>
                                <p class="user-email"><?= htmlspecialchars($user['EMAIL'] ?? 'N/A') ?></p>
                                <div class="user-badges">
                                    <?php
                                    $roleClass = 'role-badge role-' . strtolower($user['ROLE'] ?? '');
                                    $isSuspended = !empty($user['is_suspended']) || (isset($user['STATUS']) && strtolower($user['STATUS']) === 'suspended');
                                    $statusClass = 'status-badge status-' . ($isSuspended ? 'suspended' : strtolower($user['STATUS'] ?? 'active'));
                                    ?>
                                    <span class="<?= $roleClass ?>">
                                        <i class="fas <?= ($user['ROLE'] ?? '') === 'admin' ? 'fa-user-shield' : (($user['ROLE'] ?? '') === 'seller' ? 'fa-store' : 'fa-user') ?>"></i>
                                        <?= htmlspecialchars($user['ROLE'] ?? 'N/A') ?>
                                    </span>
                                    <span class="<?= $statusClass ?>">
                                        <i class="fas <?= $isSuspended ? 'fa-ban' : (($user['STATUS'] ?? '') === 'active' ? 'fa-check-circle' : 'fa-clock') ?>"></i>
                                        <?= $isSuspended ? 'Suspended' : htmlspecialchars($user['STATUS'] ?? 'Active') ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="user-details-grid">
                            <div class="detail-section">
                                <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                                <div class="detail-row">
                                    <div class="detail-label">Full Name</div>
                                    <div class="detail-value"><?= htmlspecialchars($user['NAME'] ?? 'N/A') ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Email Address</div>
                                    <div class="detail-value"><?= htmlspecialchars($user['EMAIL'] ?? 'N/A') ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Phone Number</div>
                                    <div class="detail-value"><?= !empty($user['PHONE']) ? htmlspecialchars($user['PHONE']) : 'N/A' ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">User ID</div>
                                    <div class="detail-value"><?= htmlspecialchars($user['USER_ID'] ?? 'N/A') ?></div>
                                </div>
                            </div>

                            <div class="detail-section">
                                <h3><i class="fas fa-calendar-alt"></i> Account Information</h3>
                                <div class="detail-row">
                                    <div class="detail-label">Member Since</div>
                                    <div class="detail-value">
                                        <?php
                                        if (!empty($user['CREATED_AT'])) {
                                            echo dateFormat($user['CREATED_AT'], 'M j, Y g:i A');
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Account Status</div>
                                    <div class="detail-value">
                                        <span class="<?= $statusClass ?>">
                                            <?= $isSuspended ? 'Suspended' : htmlspecialchars($user['STATUS'] ?? 'Active') ?>
                                        </span>
                                        <?php if (!empty($user['SUSPENSION_REASON'])): ?>
                                            <div class="suspension-reason"><strong>Reason:</strong> <?= htmlspecialchars($user['SUSPENSION_REASON']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($user['SUSPENDED_UNTIL'])): ?>
                                            <div class="suspension-until">
                                                <strong>Until:</strong>
                                                <?php
                                                echo dateFormat($user['SUSPENDED_UNTIL'], 'M j, Y g:i A');
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <?php if (($user['ROLE'] ?? '') === 'seller'): ?>
                                <div class="detail-section">
                                    <h3><i class="fas fa-store"></i> Seller Information</h3>
                                    <div class="detail-row">
                                        <div class="detail-label">Business Name</div>
                                        <div class="detail-value"><?= !empty($user['BUSINESS_NAME']) ? htmlspecialchars($user['BUSINESS_NAME']) : 'N/A' ?></div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">Business Address</div>
                                        <div class="detail-value"><?= !empty($user['BUSINESS_ADDRESS']) ? htmlspecialchars($user['BUSINESS_ADDRESS']) : 'N/A' ?></div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">Total Products</div>
                                        <div class="detail-value"><?= !empty($user['TOTAL_PRODUCTS']) ? htmlspecialchars($user['TOTAL_PRODUCTS']) : '0' ?></div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">Seller Rating</div>
                                        <div class="detail-value">
                                            <?php if (!empty($user['AVERAGE_RATING'])): ?>
                                                <div class="rating-stars" style="width: 100%; justify-content: flex-end;">
                                                    <?php
                                                    $rating = floatval($user['AVERAGE_RATING']);
                                                    $fullStars = floor($rating);
                                                    $hasHalfStar = ($rating - $fullStars) >= 0.5;

                                                    for ($i = 1; $i <= 5; $i++):
                                                        if ($i <= $fullStars):
                                                            echo '<i class="fas fa-star"></i>';
                                                        elseif ($i === $fullStars + 1 && $hasHalfStar):
                                                            echo '<i class="fas fa-star-half-alt"></i>';
                                                        else:
                                                            echo '<i class="far fa-star"></i>';
                                                        endif;
                                                    endfor;
                                                    ?>
                                                    <span>(<?= htmlspecialchars($user['AVERAGE_RATING']) ?>/5)</span>
                                                </div>
                                            <?php else: ?>
                                                No ratings yet
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (($user['ROLE'] ?? '') === 'buyer'): ?>
                                <div class="detail-section">
                                    <h3><i class="fas fa-shopping-cart"></i> Buyer Information</h3>
                                    <div class="detail-row">
                                        <div class="detail-label">Total Orders</div>
                                        <div class="detail-value"><?= !empty($user['TOTAL_ORDERS']) ? htmlspecialchars($user['TOTAL_ORDERS']) : '0' ?></div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">Cancelled Orders</div>
                                        <div class="detail-value"><?= !empty($user['CANCELLED_ORDERS']) ? htmlspecialchars($user['COMPLETED_ORDERS']) : '0' ?></div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">Completed Orders</div>
                                        <div class="detail-value"><?= !empty($user['COMPLETED_ORDERS']) ? htmlspecialchars($user['COMPLETED_ORDERS']) : '0' ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('userDetailsModal')">Close</button>
        </div>
    </div>
</div>

<!-- Lift Suspension Confirmation Modal -->
<div class="modal-overlay" id="liftSuspensionModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-unlock"></i>
                Lift Suspension
            </h2>
            <button class="modal-close" onclick="closeModal('liftSuspensionModal')">&times;</button>
        </div>
        <form method="POST" action="/admin/dashboard/users" id="liftSuspensionForm">
            <input type="hidden" name="action" value="lift_suspension">
            <input type="hidden" name="user_id" id="liftSuspensionUserId">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="modal-body">
                <div class="confirmation-message">
                    <i class="fas fa-question-circle"></i>
                    <h3>Confirm Lifting Suspension</h3>
                    <p>You are about to lift the suspension for user: <strong id="liftSuspensionUserName"></strong></p>
                    <p>This will restore their access to the system immediately.</p>
                </div>

                <!-- Optional: Show suspension details if you want to keep this feature -->
                <div class="suspension-details" id="suspensionDetails" style="display: none;">
                    <h4>Suspension Details:</h4>
                    <div class="detail-row">
                        <span class="detail-label">Reason:</span>
                        <span class="detail-value" id="suspensionReason"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Suspended Until:</span>
                        <span class="detail-value" id="suspendedUntil"></span>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('liftSuspensionModal')">Cancel</button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-unlock"></i> Lift Suspension
                </button>
            </div>
        </form>
    </div>
</div>