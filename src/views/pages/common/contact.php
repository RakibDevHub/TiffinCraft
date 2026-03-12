<main class="contact-page">
    <section class="contact-section">
        <div class="contact-container">

            <!-- Header -->
            <div class="contact-header" data-aos="zoom-in">
                <h1 class="contact-title">Get in Touch</h1>
                <p class="contact-subtitle">
                    Have questions or feedback? We'd love to hear from you!
                </p>
            </div>

            <div class="contact-grid" data-aos="zoom-in" data-aos-delay="200">

                <!-- Business Hours -->
                <div class="info-card" style="height: fit-content;">
                    <div class="info-card-header">
                        <h2>Business Hours</h2>
                    </div>

                    <div class="info-card-body">
                        <ul class="hours-list">
                            <li>
                                <span>Monday - Friday</span>
                                <span>9:00 AM - 8:00 PM</span>
                            </li>
                            <li>
                                <span>Saturday</span>
                                <span>10:00 AM - 6:00 PM</span>
                            </li>
                            <li>
                                <span>Sunday</span>
                                <span class="closed">Closed</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="contact-info">
                    <!-- Contact Info -->
                    <div class="info-card">
                        <div class="info-card-header">
                            <h2>Contact Information</h2>
                        </div>

                        <div class="info-card-body">
                            <div class="info-row">
                                <div class="info-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div>
                                    <h3>Our Location</h3>
                                    <p>Bashundhara R/A, Block B, Dhaka 1212</p>
                                </div>
                            </div>

                            <div class="info-row">
                                <div class="info-icon">
                                    <i class="fas fa-phone-alt"></i>
                                </div>
                                <div>
                                    <h3>Phone Number</h3>
                                    <p>+880 1XXX-XXXXXX</p>
                                    <p>+880 1XXX-XXXXXX</p>
                                </div>
                            </div>

                            <div class="info-row">
                                <div class="info-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div>
                                    <h3>Email Address</h3>
                                    <p>support@tiffincraft.com</p>
                                    <p>info@tiffincraft.com</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Form -->
                <!-- <div class="contact-form-card" data-aos="zoom-in" data-aos-delay="300">
                    <div class="form-card-header">
                        <h2>Send us a message</h2>
                    </div>

                    <div class="form-card-body">

                        <?php if (isset($_SESSION['contact_success'])): ?>
                            <div class="flash flash-success">
                                <i class="fas fa-check-circle"></i>
                                <?= htmlspecialchars($_SESSION['contact_success']) ?>
                                <?php unset($_SESSION['contact_success']); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['contact_error'])): ?>
                            <div class="flash flash-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <?= htmlspecialchars($_SESSION['contact_error']) ?>
                                <?php unset($_SESSION['contact_error']); ?>
                            </div>
                        <?php endif; ?>

                        <form action="/contact/submit" method="POST" class="contact-form">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" name="name" id="name" required placeholder="Your name">
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" name="email" id="email" required placeholder="your@email.com">
                            </div>

                            <div class="form-group">
                                <label for="subject">Subject</label>
                                <select name="subject" id="subject" required>
                                    <option value="" disabled selected>Select a subject</option>
                                    <option value="General Inquiry">General Inquiry</option>
                                    <option value="Order Issues">Order Issues</option>
                                    <option value="Business Partnership">Business Partnership</option>
                                    <option value="Feedback">Feedback</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="message">Message</label>
                                <textarea name="message" id="message" rows="4" required
                                    placeholder="Your message here..."></textarea>
                            </div>

                            <button type="submit" class="btn-submit">
                                Send Message <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>

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