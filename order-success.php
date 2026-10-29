<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
define('PAGE_TITLE', 'Order Confirmed');
$base_url = '';

$orderNum = trim($_GET['order'] ?? '');
if (!$orderNum) { redirect('index.php'); }

$on  = sanitize($conn, $orderNum);
$r   = $conn->query("SELECT * FROM orders WHERE order_number = '$on'");
$order = $r ? $r->fetch_assoc() : null;
if (!$order) { redirect('index.php'); }

$items = $conn->query("SELECT * FROM order_items WHERE order_id = {$order['id']}");
$items = $items ? $items->fetch_all(MYSQLI_ASSOC) : [];
?>
<?php require_once 'includes/header.php'; ?>

<div class="success-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="success-card">
                    <div class="success-icon-wrap">
                        <i class="fas fa-check"></i>
                    </div>
                    <h2 class="fw-bold mb-2">Order Confirmed!</h2>
                    <p class="text-muted mb-1">Thank you, <strong><?= htmlspecialchars($order['customer_name']) ?></strong>!</p>
                    <p class="text-muted mb-4">
                        Your order <strong class="text-navy"><?= htmlspecialchars($order['order_number']) ?></strong>
                        has been placed successfully. A confirmation will be sent to
                        <strong><?= htmlspecialchars($order['customer_email']) ?></strong>.
                    </p>

                    <!-- Order Details -->
                    <div class="text-start border rounded-3 overflow-hidden mb-4">
                        <div class="bg-warm px-4 py-3 border-bottom">
                            <div class="row">
                                <div class="col-6">
                                    <div class="small text-muted">Order Number</div>
                                    <strong><?= htmlspecialchars($order['order_number']) ?></strong>
                                </div>
                                <div class="col-6 text-end">
                                    <div class="small text-muted">Order Date</div>
                                    <strong><?= date('M j, Y', strtotime($order['created_at'])) ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="px-4 py-3">
                            <?php foreach ($items as $item): ?>
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <div>
                                    <div class="fw-bold small"><?= htmlspecialchars($item['product_name']) ?></div>
                                    <div class="text-muted small">Qty: <?= $item['quantity'] ?> × <?= formatPrice($item['price']) ?></div>
                                </div>
                                <div class="fw-bold small"><?= formatPrice($item['subtotal']) ?></div>
                            </div>
                            <?php endforeach; ?>

                            <div class="d-flex justify-content-between py-2 small">
                                <span class="text-muted">Subtotal</span>
                                <span><?= formatPrice($order['subtotal']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between py-1 small">
                                <span class="text-muted">Shipping</span>
                                <span><?= $order['shipping_fee'] == 0 ? '<span class="text-success">FREE</span>' : formatPrice($order['shipping_fee']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between py-1 small">
                                <span class="text-muted">VAT (12%)</span>
                                <span><?= formatPrice($order['tax']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-top mt-1">
                                <strong>Total</strong>
                                <strong class="text-navy" style="font-size:1.1rem"><?= formatPrice($order['total']) ?></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Address -->
                    <div class="text-start bg-warm rounded-3 p-3 mb-4">
                        <h6 class="fw-bold mb-2"><i class="fas fa-map-marker-alt me-2 text-accent"></i>Shipping To</h6>
                        <p class="small text-muted mb-0">
                            <?= htmlspecialchars($order['shipping_address']) ?>,
                            <?= htmlspecialchars($order['shipping_city']) ?>,
                            <?= htmlspecialchars($order['shipping_state']) ?>
                            <?= $order['shipping_zip'] ? ', ' . htmlspecialchars($order['shipping_zip']) : '' ?>
                        </p>
                    </div>

                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="shop.php" class="btn btn-navy btn-lg">
                            <i class="fas fa-store me-2"></i> Continue Shopping
                        </a>
                        <?php if (isLoggedIn()): ?>
                        <a href="account.php?tab=orders" class="btn btn-outline-navy btn-lg">
                            <i class="fas fa-box me-2"></i> My Orders
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
