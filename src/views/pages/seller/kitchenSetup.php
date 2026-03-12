<?php
$user = $data['currentUser'];
$serviceAreas = $data['serviceAreas'];

include BASE_PATH . '/src/views/components/flash-popup.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title"><?= htmlspecialchars(ucfirst($title)) ?></h1>
    <p class="page-subtitle">Set up your kitchen to start receiving orders</p>
</div>

<!-- Progress Steps -->
<div class="progress-steps">
    <div class="step active">
        <div class="step-number">1</div>
        <div class="step-label">Kitchen Setup</div>
    </div>
    <div class="step">
        <div class="step-number">2</div>
        <div class="step-label">Subscription</div>
    </div>
    <div class="step">
        <div class="step-number">3</div>
        <div class="step-label">Payment</div>
    </div>
</div>

<!-- Kitchen Setup Form -->
<div class="dashboard-card">
    <div class="card-header">
        <h3>Kitchen Information</h3>
        <p>Fill in your kitchen details to get started</p>
    </div>

    <div class="card-body">
        <form method="POST" action="/business/dashboard/kitchen-setup" enctype="multipart/form-data" id="kitchenSetupForm">
            <input type="hidden" name="action" value="create_kitchen">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <!-- Basic Information Section -->
            <div class="form-section">
                <h4 class="section-title">
                    <i class="fas fa-info-circle"></i>
                    Basic Information
                </h4>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Kitchen Name *</label>
                        <input type="text" class="form-control" name="name" required
                            placeholder="e.g., Spice Delight Kitchen" maxlength="100">
                        <small class="form-help">This will be displayed to customers</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Cover Image</label>
                        <div class="file-upload">
                            <input type="file" id="coverImage" name="cover_image" accept="image/*"
                                class="file-input" onchange="previewImage(this, 'coverImagePreview')">
                            <label for="coverImage" class="file-upload-label">
                                <i class="fas fa-upload"></i>
                                <span>Choose Cover Image</span>
                            </label>
                        </div>
                        <div id="coverImagePreview" class="image-preview" style="display: none;">
                            <img src="" alt="Cover preview" class="preview-image">
                            <button type="button" class="preview-remove" onclick="removeImage('coverImage', 'coverImagePreview')">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <small class="form-help">Recommended: 1200x600px, JPG or PNG</small>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="4"
                        placeholder="Describe your kitchen, specialty dishes, cooking style..."></textarea>
                    <small class="form-help">Tell customers what makes your kitchen special</small>
                </div>
            </div>

            <!-- Location & Contact Section -->
            <div class="form-section">
                <h4 class="section-title">
                    <i class="fas fa-map-marker-alt"></i>
                    Location & Contact
                </h4>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Address *</label>
                        <textarea class="form-control" name="address" rows="3" required
                            placeholder="Full kitchen address"></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Google Maps URL</label>
                        <input type="url" class="form-control" name="google_maps_url"
                            placeholder="https://maps.google.com/...">
                        <small class="form-help">Help customers find your location easily</small>
                    </div>
                </div>
            </div>

            <!-- Service Areas Section -->
            <div class="form-section">
                <h4 class="section-title">
                    <i class="fas fa-truck"></i>
                    Service Areas
                </h4>

                <div class="form-group">
                    <label class="form-label">Select Service Areas *</label>
                    <small class="form-help">Choose areas where you can deliver food</small>

                    <div class="service-areas-grid">
                        <?php if (empty($serviceAreas)): ?>
                            <div class="no-areas">
                                <i class="fas fa-map-marker-alt"></i>
                                <p>No service areas available</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($serviceAreas as $area): ?>
                                <label class="area-checkbox">
                                    <input type="checkbox" name="service_areas[]" value="<?= $area['AREA_ID'] ?>">
                                    <span class="checkmark"></span>
                                    <div class="area-info">
                                        <span class="area-name"><?= htmlspecialchars($area['NAME']) ?></span>
                                        <span class="area-city"><?= htmlspecialchars($area['CITY']) ?></span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Kitchen Details Section -->
            <div class="form-section">
                <h4 class="section-title">
                    <i class="fas fa-utensils"></i>
                    Kitchen Details
                </h4>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Years of Experience</label>
                        <input type="number" class="form-control" name="years_experience"
                            min="0" max="50" placeholder="e.g., 5">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Signature Dish</label>
                        <input type="text" class="form-control" name="signature_dish"
                            placeholder="e.g., Biryani Special" maxlength="100">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Average Preparation Time (minutes)</label>
                        <input type="number" class="form-control" name="avg_prep_time"
                            value="30" min="15" max="120">
                        <small class="form-help">Estimated time to prepare orders</small>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions" style="flex-direction: row-reverse; gap: 1rem; border: none; margin: 0; padding: 0; align-items: center; justify-content: space-between;">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Save & Continue to Subscription
                </button>

                <div class="form-note">
                    <i class="fas fa-info-circle"></i>
                    Your kitchen will be reviewed and activated after subscription payment
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function previewImage(input, previewId) {
        const preview = document.getElementById(previewId);
        const file = input.files[0];

        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.style.display = 'block';
                preview.querySelector('img').src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    }

    function removeImage(inputId, previewId) {
        document.getElementById(inputId).value = '';
        document.getElementById(previewId).style.display = 'none';
    }

    // Form validation
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('kitchenSetupForm');

        form.addEventListener('submit', function(e) {
            const serviceAreas = form.querySelectorAll('input[name="service_areas[]"]:checked');
            if (serviceAreas.length === 0) {
                e.preventDefault();
                alert('Please select at least one service area');
                return false;
            }

            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Setting up kitchen...';
        });
    });
</script>