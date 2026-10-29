<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
$base_url = '';

$slug    = trim($_GET['slug'] ?? '');
$product = $slug ? getProductBySlug($conn, $slug) : null;
if (!$product) { header('Location: shop.php'); exit; }

define('PAGE_TITLE', $product['name']);

// ── POST: Add to Cart ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $qty = max(1, (int)($_POST['quantity'] ?? 1));
    if (addToCart($conn, (int)$product['id'], $qty)) {
        setAlert('success', '"' . $product['name'] . '" is in your bag.');
    } else {
        setAlert('danger', "Couldn't add that — looks like we're short on stock.");
    }
    header("Location: product.php?slug=$slug");
    exit;
}

// ── POST: Review ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isLoggedIn()) {
        setAlert('warning', 'Please log in to leave a review.');
    } else {
        $rating  = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        $title   = trim($_POST['review_title'] ?? '');
        $comment = trim($_POST['comment'] ?? '');
        $userId  = (int)$_SESSION['user_id'];
        $pid     = (int)$product['id'];

        // Handle uploaded review photos (degrades silently if column doesn't exist)
        $imagesJson = null;
        if (hasColumn($conn, 'reviews', 'images') && !empty($_FILES['review_images']['name'][0])) {
            $paths = saveReviewImages($_FILES['review_images'], 3);
            if (!empty($paths)) $imagesJson = json_encode($paths);
        }

        if ($imagesJson !== null) {
            $stmt = $conn->prepare("INSERT INTO reviews (product_id, user_id, rating, title, comment, images)
                                    VALUES (?, ?, ?, ?, ?, ?)
                                    ON DUPLICATE KEY UPDATE rating=VALUES(rating), title=VALUES(title), comment=VALUES(comment), images=VALUES(images)");
            $stmt->bind_param('iiisss', $pid, $userId, $rating, $title, $comment, $imagesJson);
        } else {
            $stmt = $conn->prepare("INSERT INTO reviews (product_id, user_id, rating, title, comment)
                                    VALUES (?, ?, ?, ?, ?)
                                    ON DUPLICATE KEY UPDATE rating=VALUES(rating), title=VALUES(title), comment=VALUES(comment)");
            $stmt->bind_param('iiiss', $pid, $userId, $rating, $title, $comment);
        }

        if ($stmt->execute()) {
            $conn->query("UPDATE products p SET
                            rating       = (SELECT AVG(rating) FROM reviews WHERE product_id = $pid AND is_approved=1),
                            review_count = (SELECT COUNT(*)    FROM reviews WHERE product_id = $pid AND is_approved=1)
                          WHERE p.id = $pid");
            setAlert('success', 'Thanks for the review — it helps other shoppers a lot.');
        } else {
            setAlert('danger', "Couldn't save your review. Mind trying again?");
        }
    }
    header("Location: product.php?slug=$slug#reviews");
    exit;
}

$related = getRelatedProducts($conn, (int)$product['category_id'], (int)$product['id'], 6);
$reviews = getProductReviews($conn, (int)$product['id']);

$inCart = false;
if (isLoggedIn()) {
    $pid  = (int)$product['id'];
    $uid  = (int)$_SESSION['user_id'];
    $chk  = $conn->query("SELECT id FROM cart WHERE user_id=$uid AND product_id=$pid");
    $inCart = $chk && $chk->num_rows > 0;
}

$effective_price = $product['sale_price'] ?: $product['price'];
$discount_pct    = $product['sale_price']
    ? round(100 - ($product['sale_price'] / $product['price'] * 100))
    : 0;

// ── Gallery: detect extra images via <base>-2.jpg, -3.jpg, -4.jpg ──
$mainImgPath = $product['image'] ?? '';
$mainImgSrc  = productImgSrc($mainImgPath);
$gallery     = [$mainImgSrc];
if ($mainImgPath) {
    $info = pathinfo($mainImgPath);
    $dir  = $info['dirname']   ?? '.';
    $name = $info['filename']  ?? '';
    $ext  = $info['extension'] ?? 'jpg';
    for ($i = 2; $i <= 4; $i++) {
        $candidate = ($dir === '.' ? '' : $dir . '/') . $name . '-' . $i . '.' . $ext;
        if (is_file(__DIR__ . '/' . ltrim($candidate, '/'))) {
            $gallery[] = productImgSrc($candidate);
        }
    }
}

// ── Variants: optional `variants` JSON column on products ──
// Example payload: {"Size":["S","M","L"],"Color":[{"name":"Tabako","hex":"#B8732C"},{"name":"Mangrove","hex":"#2E4632"}]}
$variants = [];
if (!empty($product['variants']) && is_string($product['variants'])) {
    $decoded = json_decode($product['variants'], true);
    if (is_array($decoded)) $variants = $decoded;
}
?>
<?php require_once 'includes/header.php'; ?>

<!-- ═══ Breadcrumb ════════════════════════════════════════════════ -->
<div class="pdp-crumb">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="shop.php">Shop</a></li>
                <?php if ($product['category_name']): ?>
                <li class="breadcrumb-item">
                    <a href="shop.php?category=<?= urlencode($product['category_slug']) ?>">
                        <?= htmlspecialchars($product['category_name']) ?>
                    </a>
                </li>
                <?php endif; ?>
                <li class="breadcrumb-item active"><?= htmlspecialchars($product['name']) ?></li>
            </ol>
        </nav>
    </div>
</div>

<!-- ═══ Main: Gallery + Info ════════════════════════════════════ -->
<section class="pdp-main">
    <div class="container">
        <div class="row g-5">

            <!-- ── Gallery ── -->
            <div class="col-lg-7">
                <div class="pdp-gallery">
                    <div class="pdp-main-img">
                        <img src="<?= htmlspecialchars($mainImgSrc) ?>"
                             alt="<?= htmlspecialchars($product['name']) ?>"
                             class="main-product-img"
                             onerror="this.src='placeholder.php?text=<?= urlencode($product['name']) ?>'">
                    </div>

                    <?php if (count($gallery) > 1): ?>
                    <div class="pdp-thumbs">
                        <?php foreach ($gallery as $i => $g): ?>
                        <button type="button"
                                class="pdp-thumb <?= $i === 0 ? 'active-thumb' : '' ?>"
                                data-src="<?= htmlspecialchars($g) ?>">
                            <img src="<?= htmlspecialchars($g) ?>"
                                 alt="View <?= $i + 1 ?>"
                                 onerror="this.src='placeholder.php'">
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── Info ── -->
            <div class="col-lg-5">
                <div class="pdp-info">

                    <?php if ($product['category_name']): ?>
                    <a href="shop.php?category=<?= urlencode($product['category_slug']) ?>" class="pdp-eyebrow">
                        <?= htmlspecialchars($product['category_name']) ?>
                    </a>
                    <?php endif; ?>

                    <h1 class="pdp-title"><?= htmlspecialchars($product['name']) ?></h1>

                    <div class="pdp-rating-row">
                        <?= starsHtml((float)$product['rating']) ?>
                        <a href="#reviews"><?= (int)$product['review_count'] ?> reviews</a>
                    </div>

                    <div class="pdp-price">
                        <?php if ($product['sale_price']): ?>
                            <span class="now"><?= formatPrice($product['sale_price']) ?></span>
                            <span class="was"><?= formatPrice($product['price']) ?></span>
                            <span class="save">Save <?= $discount_pct ?>%</span>
                        <?php else: ?>
                            <span class="now"><?= formatPrice($product['price']) ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($product['short_description'])): ?>
                    <p class="pdp-short"><?= htmlspecialchars($product['short_description']) ?></p>
                    <?php endif; ?>

                    <!-- Variants (renders only when products.variants JSON exists) -->
                    <?php foreach ($variants as $vName => $vOptions): ?>
                    <div class="pdp-variant">
                        <div class="pdp-variant-label">
                            <span><?= htmlspecialchars($vName) ?></span>
                            <span class="picked"><?php
                                $first = $vOptions[0] ?? '';
                                echo htmlspecialchars(is_array($first) ? ($first['name'] ?? '') : $first);
                            ?></span>
                        </div>
                        <input type="hidden" name="variant_<?= htmlspecialchars(strtolower($vName)) ?>"
                               value="<?= htmlspecialchars(is_array($first) ? ($first['name'] ?? '') : $first) ?>">
                        <div class="pdp-pills">
                            <?php foreach ($vOptions as $idx => $opt):
                                $isColor = is_array($opt) && isset($opt['hex']);
                                $label   = $isColor ? $opt['name'] : (string)$opt;
                                $value   = $label;
                                $active  = $idx === 0 ? 'active' : '';
                            ?>
                                <?php if ($isColor): ?>
                                <button type="button"
                                        class="pdp-pill swatch <?= $active ?>"
                                        style="background: <?= htmlspecialchars($opt['hex']) ?>;"
                                        data-label="<?= htmlspecialchars($label) ?>"
                                        data-value="<?= htmlspecialchars($value) ?>"
                                        title="<?= htmlspecialchars($label) ?>"></button>
                                <?php else: ?>
                                <button type="button"
                                        class="pdp-pill <?= $active ?>"
                                        data-label="<?= htmlspecialchars($label) ?>"
                                        data-value="<?= htmlspecialchars($value) ?>">
                                    <?= htmlspecialchars($label) ?>
                                </button>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <!-- Stock pill -->
                    <?php if ($product['stock'] > 10): ?>
                        <div class="pdp-stock in"><span class="dot"></span>In stock — ready to ship</div>
                    <?php elseif ($product['stock'] > 0): ?>
                        <div class="pdp-stock low"><span class="dot"></span>Only <?= $product['stock'] ?> left</div>
                    <?php else: ?>
                        <div class="pdp-stock out"><span class="dot"></span>Sold out</div>
                    <?php endif; ?>

                    <?php if ($product['stock'] > 0): ?>
                    <form method="POST" id="pdp-form" data-product-id="<?= (int)$product['id'] ?>">
                        <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                        <div class="pdp-purchase">
                            <div class="pdp-qty">
                                <button type="button" class="qty-btn" data-action="minus" aria-label="Decrease">−</button>
                                <input type="number" name="quantity" value="1" min="1"
                                       max="<?= (int)$product['stock'] ?>" class="qty-input" aria-label="Quantity">
                                <button type="button" class="qty-btn" data-action="plus" aria-label="Increase">+</button>
                            </div>
                            <button type="submit" name="add_to_cart" class="pdp-btn-add">
                                <i class="fas fa-shopping-bag"></i>
                                <?= $inCart ? 'Add More to Bag' : 'Add to Bag' ?>
                            </button>
                        </div>
                        <a href="checkout.php" class="pdp-btn-buy">
                            Buy It Now
                        </a>
                    </form>
                    <?php else: ?>
                    <div class="alert alert-warning mb-4">This piece is currently sold out.</div>
                    <?php endif; ?>

                    <div class="pdp-trust-row">
                        <span><i class="fas fa-shield-alt"></i> Authentic & verified</span>
                        <span><i class="fas fa-truck"></i> Nationwide shipping</span>
                        <span><i class="fas fa-undo"></i> 7-day returns</span>
                    </div>

                </div>
            </div>
        </div>

        <!-- ═══ Accordion: Details / Shipping / Returns ════════════════════ -->
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <div class="accordion pdp-accordion" id="pdpAccordion">

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#accDetails">
                                Details
                            </button>
                        </h2>
                        <div id="accDetails" class="accordion-collapse collapse show" data-bs-parent="#pdpAccordion">
                            <div class="accordion-body">
                                <?php if (!empty($product['description'])): ?>
                                    <?= nl2br(htmlspecialchars($product['description'])) ?>
                                <?php else: ?>
                                    <em style="color:rgba(28,22,20,.45)">No additional details provided for this piece yet.</em>
                                <?php endif; ?>

                                <?php if (!empty($product['sku'])): ?>
                                <p style="margin-top:18px"><strong>SKU:</strong> <?= htmlspecialchars($product['sku']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#accShipping">
                                Shipping
                            </button>
                        </h2>
                        <div id="accShipping" class="accordion-collapse collapse" data-bs-parent="#pdpAccordion">
                            <div class="accordion-body">
                                <ul>
                                    <li><strong>Standard:</strong> 2–7 business days nationwide.</li>
                                    <li><strong>Free shipping</strong> on orders over <?= formatPrice(FREE_SHIPPING_THRESHOLD) ?>.</li>
                                    <li><strong>Flat rate</strong> of <?= formatPrice(SHIPPING_FEE) ?> on smaller orders.</li>
                                    <li>Every piece is hand-packed by the maker's province. Larger crafts may add 1–2 days.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#accReturns">
                                Returns
                            </button>
                        </h2>
                        <div id="accReturns" class="accordion-collapse collapse" data-bs-parent="#pdpAccordion">
                            <div class="accordion-body">
                                <p>We accept returns within <strong>7 days</strong> of delivery for any piece that arrives damaged or not as described.</p>
                                <p>Because each item is handmade, slight variations in weave, grain, or glaze are part of the craft — not a defect.</p>
                                <p>To start a return, reply to your order confirmation email or contact <a href="mailto:<?= SITE_EMAIL ?>"><?= SITE_EMAIL ?></a>.</p>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ═══ Reviews ═══════════════════════════════════════════════ -->
                <section class="pdp-reviews" id="reviews">
                    <h3>Customer Reviews</h3>

                    <div class="row g-4">
                        <div class="col-md-4">
                            <div style="background:var(--brand-cream);padding:28px;border-radius:4px">
                                <div style="font-family:var(--font-head);font-size:3rem;font-weight:700;color:var(--brand-ink);line-height:1">
                                    <?= number_format((float)$product['rating'], 1) ?>
                                </div>
                                <div class="mt-2"><?= starsHtml((float)$product['rating']) ?></div>
                                <div class="text-muted small mt-1">Based on <?= (int)$product['review_count'] ?> reviews</div>
                            </div>

                            <?php if (isLoggedIn()): ?>
                            <div class="mt-4 p-3" style="border:1px solid rgba(28,22,20,.1);border-radius:4px">
                                <h6 class="fw-bold mb-3" style="letter-spacing:.3px">Write a review</h6>
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="mb-2">
                                        <div class="star-rating-input">
                                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                            <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>">
                                            <label for="star<?= $i ?>"><i class="fas fa-star"></i></label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <input type="text" name="review_title" class="form-control form-control-sm mb-2"
                                           placeholder="Title (optional)" maxlength="200">
                                    <textarea name="comment" class="form-control form-control-sm mb-3"
                                              rows="4" placeholder="Share your experience..."></textarea>

                                    <?php if (hasColumn($conn, 'reviews', 'images')): ?>
                                    <label class="review-photo-pick">
                                        <input type="file" name="review_images[]" accept="image/jpeg,image/png,image/webp" multiple>
                                        <span class="rp-label">
                                            <i class="fas fa-camera"></i>
                                            Add photos <small>(up to 3, optional)</small>
                                        </span>
                                        <div class="rp-preview" id="reviewPhotoPreview"></div>
                                    </label>
                                    <?php endif; ?>

                                    <button type="submit" name="submit_review" class="pdp-btn-add" style="height:44px;font-size:.78rem;width:100%">
                                        Submit Review
                                    </button>
                                </form>
                            </div>
                            <?php else: ?>
                            <div class="mt-4 p-3 text-center" style="border:1px solid rgba(28,22,20,.1);border-radius:4px">
                                <p class="small text-muted mb-2">Sign in to leave a review</p>
                                <a href="login.php" class="pdp-btn-buy" style="height:40px;font-size:.78rem;margin:0">Sign In</a>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-8">
                            <?php if (empty($reviews)): ?>
                            <div class="empty-state py-4">
                                <div class="empty-icon"><i class="fas fa-star"></i></div>
                                <h5>No reviews yet</h5>
                                <p>Be the first to share your experience.</p>
                            </div>
                            <?php else: ?>
                            <?php foreach ($reviews as $rv): ?>
                            <div class="review-card">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="author-avatar" style="width:38px;height:38px;font-size:.9rem">
                                            <?= strtoupper($rv['user_name'][0]) ?>
                                        </div>
                                        <div>
                                            <strong style="font-size:.9rem"><?= htmlspecialchars($rv['user_name']) ?></strong>
                                            <div class="small text-muted"><?= timeAgo($rv['created_at']) ?></div>
                                        </div>
                                    </div>
                                    <div><?= starsHtml($rv['rating']) ?></div>
                                </div>
                                <?php if ($rv['title']): ?>
                                <p class="fw-bold small mb-1"><?= htmlspecialchars($rv['title']) ?></p>
                                <?php endif; ?>
                                <p class="small text-muted mb-0"><?= nl2br(htmlspecialchars($rv['comment'])) ?></p>
                                <?php
                                $rvImgs = [];
                                if (!empty($rv['images'])) {
                                    $decoded = json_decode($rv['images'], true);
                                    if (is_array($decoded)) $rvImgs = $decoded;
                                }
                                if (!empty($rvImgs)): ?>
                                <div class="review-photos">
                                    <?php foreach ($rvImgs as $img): ?>
                                    <button type="button" class="review-photo" data-lightbox="<?= htmlspecialchars($img) ?>" aria-label="Open review photo">
                                        <img src="<?= htmlspecialchars($img) ?>" alt="Customer photo" loading="lazy">
                                    </button>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

            </div>
        </div>

    </div>
</section>

<!-- ═══ You might also like ═════════════════════════════════════ -->
<?php if (!empty($related)): ?>
<section class="pdp-related">
    <div class="container">
        <div class="pdp-related-head">
            <div>
                <span class="eyebrow-line">More from this collection</span>
                <h3>You might also like</h3>
            </div>
            <div class="pdp-rel-controls">
                <button type="button" class="pdp-rel-btn" data-rel-control="prev" aria-label="Previous">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <button type="button" class="pdp-rel-btn" data-rel-control="next" aria-label="Next">
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <div class="pdp-rel-track">
            <?php foreach ($related as $p): ?>
                <?= productCard($p, $base_url) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ═══ Recently Viewed (localStorage-driven) ═══════════════════ -->
<section class="pdp-recent" id="recentlyViewedSection" hidden>
    <div class="container">
        <div class="pdp-related-head" style="margin-bottom:28px">
            <div>
                <span class="eyebrow-line">Just looked at</span>
                <h3 style="font-size:clamp(1.4rem,2.5vw,1.8rem);letter-spacing:-.3px;margin:0">Recently viewed</h3>
            </div>
            <button type="button" class="rv-clear" id="rvClear" aria-label="Clear recently viewed">Clear</button>
        </div>
        <div class="rv-track" id="recentlyViewedTrack"></div>
    </div>
</section>

<!-- ═══ Mobile sticky add-to-cart bar ═══════════════════════════ -->
<?php if ($product['stock'] > 0): ?>
<div class="pdp-sticky-bar">
    <div class="ssb-price"><?= formatPrice($effective_price) ?></div>
    <button type="submit" form="pdp-form" name="add_to_cart" value="1" class="ssb-add">
        Add to Bag
    </button>
</div>
<?php endif; ?>

<!-- ═══ Lightbox (used by review photos) ════════════════════════ -->
<div class="lightbox" id="lightbox" aria-hidden="true">
    <button type="button" class="lightbox-close" aria-label="Close">&times;</button>
    <img alt="" id="lightboxImg">
</div>

<!-- Expose the current product for the Recently Viewed JS -->
<script>
    window.__troveryProduct = <?= json_encode([
        'id'    => (int)$product['id'],
        'slug'  => $product['slug'],
        'name'  => $product['name'],
        'image' => productImgSrc($product['image'] ?? ''),
        'price' => formatPrice($effective_price),
    ]) ?>;
</script>

<?php require_once 'includes/footer.php'; ?>
