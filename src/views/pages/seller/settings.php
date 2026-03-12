<?php
$user = $data['currentUser'];
$kitchen = $data['kitchen'] ?? [];

if (!empty($user['GENDER'])) {
    if (strtolower($user['GENDER']) === 'male') {
        $defaultAvatar = '/assets/images/M-Avatar.jpg';
    } elseif (strtolower($user['GENDER']) === 'female') {
        $defaultAvatar = '/assets/images/M-Avatar.jpg';
    }
}

$profileImage = !empty($user['PROFILE_IMAGE'])
    ? '/uploads/profile/' . $user['PROFILE_IMAGE']
    : $defaultAvatar;

$coverImage = !empty($kitchen['COVER_IMAGE'])
    ? '/uploads/kitchen/' . $kitchen['COVER_IMAGE']
    : '/assets/images/default-kitchen.jpeg';

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

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title"><?= htmlspecialchars(ucfirst($title)) ?></h1>
    <p class="page-subtitle">Manage your profile and kitchen information</p>
</div>

<div class="profile-settings-container">
    <div style="display: flex; flex-direction: row; gap: 1.5rem;">
        <!-- Profile Card -->
        <div class="dashboard-card" style="flex: 2;">
            <div class="card-header" style="flex-direction: column;">
                <h3>Profile Information</h3>
                <p>Update your personal information and profile photo</p>
            </div>

            <div class="card-body">
                <form method="POST" action="/business/dashboard/settings" enctype="multipart/form-data" class="profile-form">
                    <input type="hidden" name="action" value="update_profile">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div class="profile-image-section" style="margin-top: 8px;">
                        <div class="profile-image-container">
                            <img src="<?= htmlspecialchars($profileImage) ?>" alt="Profile Image" class="profile-image" id="profileImagePreview">
                            <label for="profileImage" class="profile-image-upload">
                                <i class="fas fa-camera"></i>
                                <span>Change Photo</span>
                            </label>
                            <input type="file" id="profileImage" name="profile_image" accept="image/*" class="hidden" onchange="previewImage(this, 'profileImagePreview')">
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Full Name *</label>
                            <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($user['NAME'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($user['EMAIL'] ?? '') ?>" disabled>
                            <small class="form-help">Email can be changed in account settings below</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Phone Number *</label>
                            <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($user['PHONE'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Gender</label>
                            <select class="form-control" name="gender">
                                <option value="">Select Gender</option>
                                <option value="male" <?= ($user['GENDER'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                                <option value="female" <?= ($user['GENDER'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Account Info Card -->
        <div class="dashboard-card" style="flex: 1;">
            <div class="card-header" style="flex-direction: column;">
                <h3>Account Information</h3>
                <p>Your account details and activity</p>
            </div>

            <div class="card-body" style="border: none;">
                <div class="account-info-grid">
                    <div class="info-item">
                        <span class="info-label">Role</span>
                        <span class="info-value badge badge-seller">
                            <?= htmlspecialchars(ucfirst($user['ROLE'] ?? 'User')) ?>
                        </span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Account Status</span>
                        <span class="info-value badge badge-<?= strtolower($user['STATUS'] ?? 'pending') ?>">
                            <?= htmlspecialchars(ucfirst($user['STATUS'] ?? 'Pending')) ?>
                        </span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Member Since</span>
                        <span class="info-value"><?= !empty($user['CREATED_AT']) ? dateFormat($user['CREATED_AT']) : 'N/A' ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Last Updated</span>
                        <span class="info-value"><?= !empty($user['UPDATED_AT']) ? dateFormat($user['UPDATED_AT']) : 'N/A' ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Kitchen Information Card -->
    <div class="dashboard-card" id="kitchen-details">
        <div class="card-header" style="flex-direction: column;">
            <h3>Kitchen Information</h3>
            <p>Manage your kitchen details and settings</p>
        </div>

        <div class="card-body" style="border: none;">
            <form method="POST" action="/business/dashboard/settings" enctype="multipart/form-data" class="profile-form">
                <input type="hidden" name="action" value="update_kitchen">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div class="kitchen-image-section">
                    <div class="kitchen-image-container">
                        <img src="<?= htmlspecialchars($coverImage) ?>" alt="Kitchen Cover Image" class="kitchen-cover-image" id="kitchenImagePreview">
                        <label for="kitchenImage" class="kitchen-image-upload">
                            <i class="fas fa-camera"></i>
                            <span>Change Cover Photo</span>
                        </label>
                        <input type="file" id="kitchenImage" name="cover_image" accept="image/*" class="hidden" onchange="previewImage(this, 'kitchenImagePreview')">
                    </div>
                </div>

                <div class="form-grid" style="margin-bottom: 0;">
                    <div class="form-group">
                        <label class="form-label">Kitchen Name *</label>
                        <input type="text" class="form-control" name="kitchen_name" value="<?= htmlspecialchars($kitchen['NAME'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Signature Dish</label>
                        <input type="text" class="form-control" name="signature_dish" value="<?= htmlspecialchars($kitchen['SIGNATURE_DISH'] ?? '') ?>" placeholder="Your most popular dish">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Years of Experience</label>
                        <input type="number" class="form-control" name="years_experience" value="<?= htmlspecialchars($kitchen['YEARS_EXPERIENCE'] ?? '') ?>" min="0" max="50" placeholder="e.g., 5">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Average Preparation Time (minutes)</label>
                        <input type="number" class="form-control" name="avg_prep_time" value="<?= htmlspecialchars($kitchen['AVG_PREP_TIME'] ?? '30') ?>" min="10" max="180">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Address *</label>
                        <textarea class="form-control" name="address" rows="3" required><?= htmlspecialchars($kitchen['ADDRESS'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Kitchen Description</label>
                        <textarea class="form-control" name="description" rows="4" placeholder="Tell customers about your kitchen, cooking style, specialties..."><?= htmlspecialchars($kitchen['DESCRIPTION'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Google Maps URL</label>
                        <input type="url" class="form-control" name="google_maps_url" value="<?= htmlspecialchars($kitchen['GOOGLE_MAPS_URL'] ?? '') ?>" placeholder="https://maps.google.com/...">
                    </div>

                    <div class="form-group full-width">
                        <?php if (!empty($kitchen['GOOGLE_MAPS_URL'])): ?>
                            <div class="map-preview-container" style="border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0;">
                                <?php
                                $mapsUrl = $kitchen['GOOGLE_MAPS_URL'];

                                // Extract coordinates from @lat,lng format
                                if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $mapsUrl, $coords)) {
                                    $lat = $coords[1];
                                    $lng = $coords[2];

                                    // Create a static map image URL (no API key required for basic embed)
                                    $staticMapUrl = "https://maps.google.com/maps?q={$lat},{$lng}&z=15&output=embed";
                                ?>
                                    <iframe
                                        width="100%"
                                        height="300"
                                        style="border:0; display: block;"
                                        loading="lazy"
                                        allowfullscreen
                                        src="<?= htmlspecialchars($staticMapUrl) ?>">
                                    </iframe>
                                <?php
                                }
                                // Fallback for other URL formats
                                else {
                                ?>
                                    <div style="padding: 2rem; text-align: center; background: #f8f9fa;">
                                        <i class="fas fa-map-marker-alt" style="font-size: 3rem; color: #dc3545; margin-bottom: 1rem;"></i>
                                        <p>View kitchen location on Google Maps:</p>
                                        <a href="<?= htmlspecialchars($mapsUrl) ?>" target="_blank" class="btn btn-primary" style="display: inline-block; margin-top: 0.5rem;">
                                            <i class="fas fa-external-link-alt"></i> Open in Google Maps
                                        </a>
                                    </div>
                                <?php
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-actions" style="margin-top: 0;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-utensils"></i> Update Kitchen Information
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Account Settings Card -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3>Account Settings</h3>
            <p>Manage your email and password</p>
        </div>

        <div class="card-body" style="display: flex; gap: 1.5rem;">
            <!-- Email Update Form -->
            <form method="POST" action="/business/dashboard/settings" class="account-form" style="flex: 1;">
                <input type="hidden" name="action" value="update_email">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <h4 style="margin-top: 0.5rem;">Change Email Address</h4>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Current Email</label>
                        <input type="email" class="form-control" name="current_email" value="<?= htmlspecialchars($user['EMAIL'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">New Email Address *</label>
                        <input type="email" class="form-control" name="new_email" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirm Password *</label>
                        <input type="password" class="form-control" name="password" required>
                        <small class="form-help">Enter your current password to confirm changes</small>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-envelope"></i> Update Email
                    </button>
                </div>
            </form>

            <hr class="form-divider">

            <!-- Password Update Form -->
            <form method="POST" action="/business/dashboard/settings" class="account-form" style="flex: 1;">
                <input type="hidden" name="action" value="update_password">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <h4 style="margin-top: 0.5rem;">Change Password</h4>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Current Password *</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">New Password *</label>
                        <input type="password" class="form-control" name="new_password" required>
                        <small class="form-help">Minimum 8 characters</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirm New Password *</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-lock"></i> Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function previewImage(input, previewId) {
        const preview = document.getElementById(previewId);
        const file = input.files[0];

        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    }

    // Form validation
    document.addEventListener('DOMContentLoaded', function() {

        const forms = document.querySelectorAll('form');

        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            });
        });
    });
</script>