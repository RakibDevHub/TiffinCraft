<?php
$user = $data['currentUser'];

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

<main class="settings-page">
    <section class="settings-section">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title"><?= htmlspecialchars(ucfirst($title)) ?></h1>
                <p class="page-subtitle">Manage your profile information and account settings</p>
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
                            <form method="POST" action="/settings" enctype="multipart/form-data" class="profile-form">
                                <input type="hidden" name="action" value="update_profile">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                                <div class="profile-image-section">
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
                                            <option value="other" <?= ($user['GENDER'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                                            <option value="prefer_not_to_say" <?= ($user['GENDER'] ?? '') === 'prefer_not_to_say' ? 'selected' : '' ?>>Prefer not to say</option>
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

                        <div class="card-body">
                            <div class="account-info-grid">
                                <div class="info-item">
                                    <span class="info-label">Role</span>
                                    <span class="info-value badge badge-buyer">
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

                                <!-- <?php if (!empty($user['VERIFICATION_TOKEN'])): ?>
                                    <div class="info-item">
                                        <span class="info-label">Email Verification</span>
                                        <span class="info-value badge badge-warning">Pending</span>
                                    </div>
                                <?php else: ?>
                                    <div class="info-item">
                                        <span class="info-label">Email Verification</span>
                                        <span class="info-value badge badge-success">Verified</span>
                                    </div>
                                <?php endif; ?> -->
                            </div>
                        </div>
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
                        <form method="POST" action="/settings" class="account-form" style="flex: 1;">
                            <input type="hidden" name="action" value="update_email">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                            <h4>Change Email Address</h4>

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
                        <form method="POST" action="/settings" class="account-form" style="flex: 1;">
                            <input type="hidden" name="action" value="update_password">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                            <h4>Change Password</h4>

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

                <!-- Security Card -->
                <!-- <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Security Settings</h3>
                        <p>Manage your account security and preferences</p>
                    </div>
        
                    <div class="card-body">
                        <div class="security-settings">
                            <div class="security-item">
                                <div class="security-info">
                                    <h4>Email Verification</h4>
                                    <p>Verify your email address to secure your account and receive important notifications</p>
                                </div>
                                <div class="security-action">
                                    <?php if (!empty($user['VERIFICATION_TOKEN'])): ?>
                                        <span class="badge badge-warning">Pending Verification</span>
                                        <button type="button" class="btn btn-outline" onclick="resendVerificationEmail()">
                                            <i class="fas fa-paper-plane"></i> Resend Verification
                                        </button>
                                    <?php else: ?>
                                        <span class="badge badge-success">Verified</span>
                                    <?php endif; ?>
                                </div>
                            </div>
        
                            <div class="security-item">
                                <div class="security-info">
                                    <h4>Two-Factor Authentication</h4>
                                    <p>Add an extra layer of security to your account</p>
                                </div>
                                <div class="security-action">
                                    <span class="badge badge-secondary">Coming Soon</span>
                                </div>
                            </div>
        
                            <div class="security-item">
                                <div class="security-info">
                                    <h4>Login Activity</h4>
                                    <p>View your recent login history and active sessions</p>
                                </div>
                                <div class="security-action">
                                    <button type="button" class="btn btn-outline" onclick="viewLoginActivity()">
                                        <i class="fas fa-history"></i> View Activity
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> -->
            </div>
        </div>
    </section>

    <?php
    $fillColor = '#FFFBEB';
    $invert = true;
    // $offset = true;

    include BASE_PATH . '/src/views/components/divider-banner.php';
    ?>
</main>

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