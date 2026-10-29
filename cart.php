<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
define('PAGE_TITLE', 'Cart');
$base_url = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $cart_id = (int)($_POST['cart_id'] ?? 0);
    $qty     = (int)($_POST['quantity'] ?? 1);

    if ($action === 'update' && $cart_id) {
        updateCartQty($conn, $cart_id, $qty);
    } elseif ($action === 'remove' && $cart_id) {
        removeFromCart($conn, $cart_id);
        setAlert('info', 'Item removed from cart.');
    } elseif ($action === 'clear') {
        clearCart($conn);
        setAlert('info', 'Cart cleared.');
    }
    header('Location: cart.php');
    exit;
}

$cartItems  = getCartItems($conn);
$subtotal   = getCartTotal($conn);
$shipping   = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_FEE;
$tax        = $subtotal * TAX_RATE;
$total      = $subtotal + $shipping + $tax;
?>
<?php require_once 'includes/header.php'; ?>

<div class="page-header">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">Cart</li>
            </ol>
        </nav>
        <h1 class="mt-1">Shopping Cart
            <?php if (!empty($cartItems)): ?>
            <span class="fs-5 fw-normal text-white opacity-75 ms-2">(<?= count($cartItems) ?> items)</span>
            <?php endif; ?>
        </h1>
    </div>
</div>

<div class="container py-5">

    <?php if (empty($cartItems)): ?>
    <!-- Empty Cart -->
    <div class="empty-state py-5">
        <div class="empty-icon"><i class="fas fa-shopping-bag"></i></div>
        <h4>Your cart is empty</h4>
        <p>Looks like you haven't added anything yet. Discover amazing local products.</p>
        <a href="shop.php" class="btn btn-navy btn-lg">
            <i class="fas fa-store me-2"></i> Start Shopping
        </a>
    </div>

    <?php else: ?>
    <div class="row g-4">

        <!-- Cart Items -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">Cart Items</h6>
                        <form method="POST" onsubmit="return confirm('Clear your entire cart?')">
                            <input type="hidden" name="action" value="clear">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-trash me-1"></i> Clear Cart
                            </button>
                        </form>
                    </div>
                </div>

                <form method="POST" id="cartUpdateForm">
                    <input type="hidden" name="action" value="update">
                    <div class="card-body p-0">
                        <?php foreach ($cartItems as $item):
                            $itemPrice = $item['sale_price'] ?: $item['price'];
                            $lineTotal = $itemPrice * $item['quantity'];
                        ?>
                        <div class="d-flex align-items-center gap-3 p-4 border-bottom cart-item-row"
                             data-cart-id="<?= $item['id'] ?>">

                            <!-- Image -->
                            <a href="product.php?slug=<?= urlencode($item['slug']) ?>">
                                <img src="<?= productImgSrc($item['image'] ?? '') ?>"
                                     alt="<?= htmlspecialchars($item['name']) ?>"
                                     class="cart-item-img"
                                     onerror="this.src='placeholder.php'">
                            </a>

                            <!-- Info -->
                            <div class="flex-grow-1 min-width-0">
                                <a href="product.php?slug=<?= urlencode($item['slug']) ?>"
                                   class="fw-bold text-dark text-decoration-none d-block mb-1"
                                   style="font-size:.92rem">
                                    <?= htmlspecialchars($item['name']) ?>
                                </a>
                                <?php if ($item['sale_price']): ?>
                                <div class="small">
                                    <span class="text-decoration-line-through text-muted"><?= formatPrice($item['price']) ?></span>
                                    <span class="text-accent fw-bold ms-1"><?= formatPrice($item['sale_price']) ?></span>
                                </div>
                                <?php else: ?>
                                <div class="small text-muted"><?= formatPrice($item['price']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Qty -->
                            <div class="qty-control">
                                <button type="button" class="qty-btn" data-action="minus">−</button>
                                <input type="number" name="quantity" value="<?= $item['quantity'] ?>"
                                       min="0" max="<?= $item['stock'] ?>"
                                       class="qty-input cart-qty-input"
                                       data-cart-id="<?= $item['id'] ?>">
                                <button type="button" class="qty-btn" data-action="plus">+</button>
                            </div>

                            <!-- Line Total -->
                            <div class="text-end" style="min-width:80px">
                                <div class="fw-bold" style="font-family:var(--font-head)">
                                    <?= formatPrice($lineTotal) ?>
                                </div>
                            </div>

                            <!-- Remove -->
                            <form method="POST" class="ms-1">
                                <input type="hidden" name="action"  value="remove">
                                <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle"
                                        style="width:32px;height:32px;padding:0" title="Remove">
                                    <i class="fas fa-times" style="font-size:.75rem"></i>
                                </button>
                            </form>

                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-footer bg-white p-3">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="cart_id" id="updateCartId" value="">
                    </div>
                </form>
            </div>

            <!-- Continue Shopping -->
            <a href="shop.php" class="btn btn-outline-navy mt-3">
                <i class="fas fa-arrow-left me-2"></i> Continue Shopping
            </a>
        </div>

        <!-- Order Summary -->
        <div class="col-lg-4">
            <div class="cart-summary">
                <h5 class="fw-bold mb-4">Order Summary</h5>

                <div class="cart-summary-row">
                    <span>Subtotal</span>
                    <span><?= formatPrice($subtotal) ?></span>
                </div>
                <div class="cart-summary-row">
                    <span>Shipping</span>
                    <span>
                        <?php if ($shipping == 0): ?>
                            <span class="text-success fw-bold">FREE</span>
                        <?php else: ?>
                            <?= formatPrice($shipping) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="cart-summary-row">
                    <span>VAT (12%)</span>
                    <span><?= formatPrice($tax) ?></span>
                </div>

                <?php if ($subtotal < FREE_SHIPPING_THRESHOLD): ?>
                <div class="alert alert-info small py-2 my-2">
                    <i class="fas fa-info-circle me-1"></i>
                    Add <strong><?= formatPrice(FREE_SHIPPING_THRESHOLD - $subtotal) ?></strong> more for free shipping!
                </div>
                <?php endif; ?>

                <div class="cart-summary-row total-row">
                    <strong>Total</strong>
                    <span class="total-amount"><?= formatPrice($total) ?></span>
                </div>

                <a href="checkout.php" class="btn btn-accent w-100 btn-lg mt-4">
                    <i class="fas fa-lock me-2"></i> Proceed to Checkout
                </a>

                <div class="mt-3 text-center">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1 text-accent"></i>
                        Secure SSL encrypted checkout
                    </small>
                </div>

                <!-- Payment Icons -->
                <div class="d-flex justify-content-center gap-3 mt-3">
                    <i class="fab fa-cc-visa fa-2x text-muted"></i>
                    <i class="fab fa-cc-mastercard fa-2x text-muted"></i>
                    <i class="fab fa-cc-paypal fa-2x text-muted"></i>
                    <i class="fas fa-money-bill-wave fa-2x text-muted" title="COD"></i>
                </div>
            </div>
        </div>

    </div>
    <?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>
