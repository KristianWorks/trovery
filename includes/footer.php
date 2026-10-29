
<!-- ═══ FOOTER ════════════════════════════════════════════════════════════════ -->
<footer class="site-footer">
    <div class="footer-top">
        <div class="container">
            <div class="row g-4">

                <!-- Brand Column -->
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="<?= $base_url ?? '' ?>index.php" class="footer-brand">
                        <span class="brand-dot">T</span><span class="brand-text">rovery</span>
                    </a>
                    <p class="footer-tagline mt-2">Made somewhere. Made by someone.</p>
                    <p class="footer-desc">
                        Trovery puts the maker's province on the label. Every weave, carving,
                        and pour comes from a named Filipino artisan — and a fair share of
                        every peso goes back to them.
                    </p>
                    <div class="footer-social">
                        <a href="#" class="social-link" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link" title="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link" title="TikTok"><i class="fab fa-tiktok"></i></a>
                        <a href="#" class="social-link" title="YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-6 col-md-3 col-lg-2">
                    <h6 class="footer-heading">Quick Links</h6>
                    <ul class="footer-links">
                        <li><a href="<?= $base_url ?? '' ?>index.php">Home</a></li>
                        <li><a href="<?= $base_url ?? '' ?>shop.php">Shop</a></li>
                        <li><a href="<?= $base_url ?? '' ?>shop.php?sort=rating">Best Sellers</a></li>
                        <li><a href="<?= $base_url ?? '' ?>shop.php?sale=1">Sale Items</a></li>
                        <li><a href="<?= $base_url ?? '' ?>account.php">My Account</a></li>
                        <li><a href="<?= $base_url ?? '' ?>cart.php">Cart</a></li>
                    </ul>
                </div>

                <!-- Categories -->
                <div class="col-6 col-md-3 col-lg-2">
                    <h6 class="footer-heading">Categories</h6>
                    <ul class="footer-links">
                        <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                        <li>
                            <a href="<?= $base_url ?? '' ?>shop.php?category=<?= urlencode($cat['slug']) ?>">
                                <?= htmlspecialchars($cat['name']) ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Contact & Newsletter -->
                <div class="col-12 col-lg-4">
                    <h6 class="footer-heading">Stay in the Loop</h6>
                    <p class="text-muted small mb-3">Get exclusive deals, new arrivals, and local stories delivered to your inbox.</p>
                    <form class="footer-newsletter" onsubmit="handleNewsletter(event)">
                        <div class="input-group">
                            <input type="email" class="form-control" placeholder="your@email.com" required>
                            <button class="btn btn-accent" type="submit">Subscribe</button>
                        </div>
                    </form>
                    <div class="footer-contact mt-3">
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span><?= SITE_EMAIL ?></span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span><?= SITE_PHONE ?></span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Philippines — Nationwide Delivery</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?= date('Y') ?> Trovery. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end mt-2 mt-md-0">
                    <div class="payment-icons">
                        <span class="payment-icon"><i class="fab fa-cc-visa"></i></span>
                        <span class="payment-icon"><i class="fab fa-cc-mastercard"></i></span>
                        <span class="payment-icon"><i class="fab fa-cc-paypal"></i></span>
                        <span class="payment-icon" title="GCash"><i class="fas fa-mobile-alt"></i></span>
                        <span class="payment-icon" title="Cash on Delivery"><i class="fas fa-money-bill-wave"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- ═══ CART DRAWER ════════════════════════════════════════════════════════ -->
<div class="cart-backdrop" data-drawer-close></div>
<aside class="cart-drawer" id="cartDrawer" aria-label="Shopping bag" aria-hidden="true">
    <div id="cartDrawerInner">
        <?php include __DIR__ . '/cart-drawer-body.php'; ?>
    </div>
</aside>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?= $base_url ?? '' ?>assets/js/main.js"></script>
</body>
</html>
