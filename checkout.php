<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
define('PAGE_TITLE', 'Checkout');
$base_url = '';

$cartItems = getCartItems($conn);
if (empty($cartItems)) { redirect('cart.php'); }

$subtotal = getCartTotal($conn);
$shipping = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_FEE;
$tax      = $subtotal * TAX_RATE;
$total    = $subtotal + $shipping + $tax;

// Pre-fill from session
$user = null;
if (isLoggedIn()) {
    $uid  = (int)$_SESSION['user_id'];
    $r    = $conn->query("SELECT * FROM users WHERE id = $uid");
    $user = $r ? $r->fetch_assoc() : null;
}

$errors = [];

// ── Handle Checkout Submit (only fires from step 3) ─────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname   = trim($_POST['first_name']  ?? '');
    $lname   = trim($_POST['last_name']   ?? '');
    $email   = trim($_POST['email']       ?? '');
    $phone   = trim($_POST['phone']       ?? '');
    $address = trim($_POST['address']     ?? '');
    $city    = trim($_POST['city']        ?? '');
    $state   = trim($_POST['state']       ?? '');
    $zip     = trim($_POST['zip']         ?? '');
    $payment = trim($_POST['payment']     ?? 'cod');
    $notes   = trim($_POST['notes']       ?? '');

    if (!$fname || !$lname) $errors[] = 'We need your name to put on the package.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "That email doesn't look right — double-check it so we can send your receipt.";
    if (!$phone)   $errors[] = 'Our courier may text on delivery day — what number should they use?';
    if (!$address) $errors[] = 'Where should we send this?';
    if (!$city)    $errors[] = 'City or municipality, please.';
    if (!$state)   $errors[] = 'Which province or region?';

    if (empty($errors)) {
        $orderNum    = generateOrderNumber();
        $custName    = sanitize($conn, "$fname $lname");
        $custEmail   = sanitize($conn, $email);
        $custPhone   = sanitize($conn, $phone);
        $shipAddr    = sanitize($conn, $address);
        $shipCity    = sanitize($conn, $city);
        $shipState   = sanitize($conn, $state);
        $shipZip     = sanitize($conn, $zip);
        $payMethod   = sanitize($conn, $payment);
        $notesSan    = sanitize($conn, $notes);
        $userId      = isLoggedIn() ? (int)$_SESSION['user_id'] : 'NULL';
        $subSan      = (float)$subtotal;
        $shipSan     = (float)$shipping;
        $taxSan      = (float)$tax;
        $totSan      = (float)$total;

        $conn->begin_transaction();
        try {
            $conn->query("INSERT INTO orders
                (user_id, order_number, subtotal, shipping_fee, tax, total,
                 customer_name, customer_email, customer_phone,
                 shipping_address, shipping_city, shipping_state, shipping_zip,
                 payment_method, notes)
                VALUES
                ($userId, '$orderNum', $subSan, $shipSan, $taxSan, $totSan,
                 '$custName', '$custEmail', '$custPhone',
                 '$shipAddr', '$shipCity', '$shipState', '$shipZip',
                 '$payMethod', '$notesSan')");

            $orderId = $conn->insert_id;

            foreach ($cartItems as $item) {
                $itemPrice = $item['sale_price'] ?: $item['price'];
                $itemQty   = (int)$item['quantity'];
                $itemSub   = $itemPrice * $itemQty;
                $itemName  = sanitize($conn, $item['name']);
                $itemImg   = sanitize($conn, $item['image'] ?? '');
                $pid       = (int)$item['product_id'];
                $conn->query("INSERT INTO order_items
                    (order_id, product_id, product_name, product_image, price, quantity, subtotal)
                    VALUES ($orderId, $pid, '$itemName', '$itemImg', $itemPrice, $itemQty, $itemSub)");
                $conn->query("UPDATE products SET stock = stock - $itemQty WHERE id = $pid AND stock >= $itemQty");
            }

            $conn->commit();
            clearCart($conn);
            redirect("order-success.php?order=$orderNum");
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Something hiccupped on our end. Please try again — your bag is safe.";
        }
    }
}

// Defaults for form values
$v_first = $user ? explode(' ', $user['name'])[0] : ($_POST['first_name'] ?? '');
$v_last  = $user ? (explode(' ', $user['name'], 2)[1] ?? '') : ($_POST['last_name'] ?? '');
$v_email = $user['email']   ?? ($_POST['email']   ?? '');
$v_phone = $user['phone']   ?? ($_POST['phone']   ?? '');
$v_addr  = $user['address'] ?? ($_POST['address'] ?? '');
$v_city  = $user['city']    ?? ($_POST['city']    ?? '');
$v_state = $user['state']   ?? ($_POST['state']   ?? '');
$v_zip   = $user['zip']     ?? ($_POST['zip']     ?? '');
$v_pay   = $_POST['payment'] ?? 'cod';
$v_notes = $_POST['notes']  ?? '';
?>
<?php require_once 'includes/header.php'; ?>

<div class="checkout-v2-wrap">
    <div class="container">

        <div class="checkout-head">
            <h1>Checkout</h1>
            <div class="secure"><i class="fas fa-lock"></i> Secure checkout · SSL encrypted</div>
        </div>

        <!-- ═══ Step Indicator ═════════════════════════════════════════ -->
        <div class="step-indicator">
            <div class="step-node active" data-step="1">
                <div class="bubble"><span class="num">1</span></div>
                <div class="label">Shipping</div>
            </div>
            <div class="step-bar"></div>
            <div class="step-node" data-step="2">
                <div class="bubble"><span class="num">2</span></div>
                <div class="label">Payment</div>
            </div>
            <div class="step-bar"></div>
            <div class="step-node" data-step="3">
                <div class="bubble"><span class="num">3</span></div>
                <div class="label">Review</div>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="POST" id="checkoutFlow">
            <div class="row g-4">

                <!-- ═══ LEFT: STEP PANELS ══════════════════════════════════ -->
                <div class="col-lg-7">

                    <!-- ── Step 1: Shipping ── -->
                    <div class="checkout-panel active" data-step="1">
                        <h3>Shipping details</h3>

                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label">First Name</label>
                                <input type="text" name="first_name" class="form-control" required
                                       value="<?= htmlspecialchars($v_first) ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" class="form-control" required
                                       value="<?= htmlspecialchars($v_last) ?>">
                            </div>
                            <div class="col-md-7">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required
                                       value="<?= htmlspecialchars($v_email) ?>">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Phone</label>
                                <input type="tel" name="phone" class="form-control" required
                                       placeholder="+63 900 000 0000"
                                       value="<?= htmlspecialchars($v_phone) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Street Address</label>
                                <input type="text" name="address" class="form-control" required
                                       placeholder="House/Unit no., Street, Barangay"
                                       value="<?= htmlspecialchars($v_addr) ?>">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control" required
                                       value="<?= htmlspecialchars($v_city) ?>">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Province / Region</label>
                                <input type="text" name="state" class="form-control" required
                                       value="<?= htmlspecialchars($v_state) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">ZIP</label>
                                <input type="text" name="zip" class="form-control" maxlength="10"
                                       value="<?= htmlspecialchars($v_zip) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Order Notes (optional)</label>
                                <textarea name="notes" class="form-control" rows="2"
                                          placeholder="Delivery instructions, gift notes..."><?= htmlspecialchars($v_notes) ?></textarea>
                            </div>
                        </div>

                        <div class="step-nav">
                            <a href="cart.php" class="step-back" style="text-decoration:none">Back to bag</a>
                            <button type="button" class="step-next" data-step-next>
                                Continue to Payment
                            </button>
                        </div>
                    </div>

                    <!-- ── Step 2: Payment ── -->
                    <div class="checkout-panel" data-step="2">
                        <h3>Payment method</h3>

                        <label class="pay-card <?= $v_pay === 'cod' ? 'checked' : '' ?>">
                            <input type="radio" name="payment" value="cod" <?= $v_pay === 'cod' ? 'checked' : '' ?>>
                            <span class="pay-icon"><i class="fas fa-money-bill-wave"></i></span>
                            <div>
                                <strong>Cash on Delivery</strong>
                                <small>Pay in cash when your order arrives.</small>
                            </div>
                        </label>

                        <label class="pay-card <?= $v_pay === 'gcash' ? 'checked' : '' ?>">
                            <input type="radio" name="payment" value="gcash" <?= $v_pay === 'gcash' ? 'checked' : '' ?>>
                            <span class="pay-icon"><i class="fas fa-mobile-alt"></i></span>
                            <div>
                                <strong>GCash</strong>
                                <small>Pay via your GCash mobile wallet.</small>
                            </div>
                        </label>

                        <label class="pay-card <?= $v_pay === 'bank' ? 'checked' : '' ?>">
                            <input type="radio" name="payment" value="bank" <?= $v_pay === 'bank' ? 'checked' : '' ?>>
                            <span class="pay-icon"><i class="fas fa-university"></i></span>
                            <div>
                                <strong>Bank Transfer</strong>
                                <small>Direct bank deposit or transfer.</small>
                            </div>
                        </label>

                        <div class="step-nav">
                            <button type="button" class="step-back" data-step-back>Back to Shipping</button>
                            <button type="button" class="step-next" data-step-next>
                                Review Order
                            </button>
                        </div>
                    </div>

                    <!-- ── Step 3: Review ── -->
                    <div class="checkout-panel" data-step="3">
                        <h3>Review &amp; place order</h3>

                        <div class="review-section">
                            <div class="review-section-head">
                                <h5>Contact</h5>
                                <button type="button" class="edit-link" data-step-goto="1">Edit</button>
                            </div>
                            <div id="review-contact" class="review-section-body">—</div>
                        </div>

                        <div class="review-section">
                            <div class="review-section-head">
                                <h5>Shipping address</h5>
                                <button type="button" class="edit-link" data-step-goto="1">Edit</button>
                            </div>
                            <div id="review-shipping" class="review-section-body">—</div>
                        </div>

                        <div class="review-section">
                            <div class="review-section-head">
                                <h5>Payment method</h5>
                                <button type="button" class="edit-link" data-step-goto="2">Edit</button>
                            </div>
                            <div id="review-payment" class="review-section-body">—</div>
                        </div>

                        <div class="review-section">
                            <div class="review-section-head">
                                <h5>Items (<?= count($cartItems) ?>)</h5>
                                <a class="edit-link" href="cart.php" style="text-decoration:underline">Edit bag</a>
                            </div>
                            <div class="review-section-body">
                                <?php foreach ($cartItems as $item):
                                    $ip = $item['sale_price'] ?: $item['price'];
                                ?>
                                <div style="display:flex;justify-content:space-between;padding:4px 0;font-size:.9rem">
                                    <span><?= htmlspecialchars($item['name']) ?> × <?= $item['quantity'] ?></span>
                                    <span style="font-family:var(--font-head);font-weight:600"><?= formatPrice($ip * $item['quantity']) ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="step-nav">
                            <button type="button" class="step-back" data-step-back>Back to Payment</button>
                            <button type="submit" class="step-next place-order">
                                Place Order — <?= formatPrice($total) ?>
                            </button>
                        </div>
                    </div>

                </div>

                <!-- ═══ RIGHT: ORDER SUMMARY ═══════════════════════════════ -->
                <div class="col-lg-5">
                    <div class="checkout-summary">
                        <h4>Order Summary</h4>

                        <?php foreach ($cartItems as $item):
                            $ip = $item['sale_price'] ?: $item['price'];
                        ?>
                        <div class="cs-line">
                            <img src="<?= htmlspecialchars(productImgSrc($item['image'] ?? '')) ?>"
                                 alt="<?= htmlspecialchars($item['name']) ?>"
                                 onerror="this.src='placeholder.php'">
                            <div class="cs-line-info">
                                <div class="nm"><?= htmlspecialchars($item['name']) ?></div>
                                <div class="q">Qty <?= $item['quantity'] ?></div>
                            </div>
                            <div class="cs-line-price"><?= formatPrice($ip * $item['quantity']) ?></div>
                        </div>
                        <?php endforeach; ?>

                        <div class="cs-totals">
                            <div class="cs-row">
                                <span>Subtotal</span>
                                <span><?= formatPrice($subtotal) ?></span>
                            </div>
                            <div class="cs-row">
                                <span>Shipping</span>
                                <span><?= $shipping == 0 ? '<span class="free">FREE</span>' : formatPrice($shipping) ?></span>
                            </div>
                            <div class="cs-row">
                                <span>VAT (12%)</span>
                                <span><?= formatPrice($tax) ?></span>
                            </div>
                            <div class="cs-row total-row">
                                <span>Total</span>
                                <span><?= formatPrice($total) ?></span>
                            </div>
                        </div>

                        <div class="checkout-trust">
                            <div class="checkout-trust-row">
                                <div class="pay-list">
                                    <i class="fab fa-cc-visa"        title="Visa"></i>
                                    <i class="fab fa-cc-mastercard"  title="Mastercard"></i>
                                    <i class="fas fa-mobile-alt"     title="GCash"></i>
                                    <i class="fas fa-university"     title="Bank Transfer"></i>
                                    <i class="fas fa-money-bill-wave" title="Cash on Delivery"></i>
                                </div>
                            </div>
                            <div class="checkout-trust-line">
                                <i class="fas fa-shield-alt"></i>
                                <span><strong>SSL-encrypted checkout.</strong> Your details never leave the page unencrypted.</span>
                            </div>
                            <div class="checkout-trust-line" style="margin-top:8px">
                                <i class="fas fa-undo"></i>
                                <span><strong>7-day returns</strong> on anything that arrives damaged or not as described.</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
