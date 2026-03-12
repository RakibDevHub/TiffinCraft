<main class="auth-section">
    <section class="register-page auth-page">
        <div class="main-container">

            <div class="auth-card">

                <!-- Header -->
                <div class="auth-card-header">
                    <h1 class="auth-title">Create Your Account</h1>
                    <p class="auth-subtitle">
                        Join TiffinCraft as a buyer or a seller
                    </p>
                </div>

                <!-- Form -->
                <div class="auth-card-body">
                    <?php if ($msg = Session::flash('error')): ?>
                        <div class="flash flash-warning"><?= htmlspecialchars($msg) ?></div>
                    <?php elseif ($msg = Session::flash('warning')): ?>
                        <div class="flash flash-info"><?= htmlspecialchars($msg) ?></div>
                    <?php elseif ($msg = Session::flash('success')): ?>
                        <div class="flash flash-success"><?= htmlspecialchars($msg) ?></div>
                    <?php endif; ?>

                    <form action="/register" method="POST" enctype="multipart/form-data" class="auth-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                        <!-- Common Fields -->
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" name="name" id="name" placeholder="Your full name" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" name="email" id="email" placeholder="you@example.com" required>
                            </div>

                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="text" name="phone" id="phone" placeholder="01234567890" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <!-- User Gender  -->
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select name="gender" id="gender">
                                    <option value="">Select</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>

                            <!-- User Role -->
                            <div class="form-group">
                                <label for="role">Register as</label>
                                <select name="role" id="role" required>
                                    <option value="buyer">Buyer</option>
                                    <option value="seller">Seller</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">

                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" name="password" id="password" placeholder="••••••••" required>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password</label>
                                <input type="password" name="confirm_password" id="confirm_password" placeholder="••••••••"
                                    required>
                            </div>
                        </div>
                        <button type="submit" class="btn-submit">Register</button>
                    </form>

                    <div class="auth-link">
                        Already have an account?
                        <a href="/login">Login here</a>
                    </div>
                </div>
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