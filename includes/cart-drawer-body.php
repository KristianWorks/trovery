<?php
// Renders the inside of the cart drawer (header count, items, totals, footer).
// Expects $conn from the caller.
$cd_items    = getCartItems($conn);
$cd_subtotal = getCartTotal($conn);
$cd_count    = array_sum(array_map(fn($i) => (int)$i['quantity'], $cd_items));
$cd_remain   = max(0, FREE_SHIPPING_THRESHOLD - $cd_subtotal);
?>
<div class="cart-drawer-head">
    <h3>Your Bag <span class="count">(<?= $cd_count ?>)</span></h3>
    <button type="button" class="cart-drawer-close" data-drawer-close aria-label="Close">
        <i class="fas fa-times"></i>
    </button>
</div>

<?php if (empty($cd_items)): ?>
<div class="cart-drawer-body">
    <div class="cart-drawer-empty">
        <i class="fas fa-shopping-bag"></i>
        <h4>Your bag is empty</h4>
        <p>Pieces you save will appear here.</p>
        <a href="shop.php" class="pdp-btn-buy" data-drawer-close style="display:inline-flex;height:44px;padding:0 28px;font-size:.78rem;margin:0">
            Browse the Shop
        </a>
    </div>
</div>
<?php else: ?>
<div class="cart-drawer-body">
    <?php foreach ($cd_items as $item):
        $ip = $item['sale_price'] ?: $item['price'];
        $lt = $ip * $item['quantity'];
    ?>
    <div class="cart-line" data-cart-id="<?= (int)$item['id'] ?>">
        <a href="product.php?slug=<?= urlencode($item['slug']) ?>">
            <img class="cart-line-img"
                 src="<?= htmlspecialchars(productImgSrc($item['image'] ?? '')) ?>"
                 alt="<?= htmlspecialchars($item['name']) ?>"
                 onerror="this.src='placeholder.php'">
        </a>
        <div class="cart-line-info">
            <a class="name" href="product.php?slug=<?= urlencode($item['slug']) ?>">
                <?= htmlspecialchars($item['name']) ?>
            </a>
            <div class="unit"><?= formatPrice($ip) ?> each</div>
            <div class="cart-line-controls">
                <div class="drawer-qty">
                    <button type="button" class="qty-btn" data-action="minus">−</button>
                    <input type="number" class="qty-input drawer-qty-input"
                           value="<?= (int)$item['quantity'] ?>"
                           min="0" max="<?= (int)$item['stock'] ?>"
                           data-cart-id="<?= (int)$item['id'] ?>">
                    <button type="button" class="qty-btn" data-action="plus">+</button>
                </div>
                <button type="button" class="cart-line-remove" data-remove="<?= (int)$item['id'] ?>">
                    Remove
                </button>
            </div>
        </div>
        <div class="cart-line-price"><?= formatPrice($lt) ?></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="cart-drawer-foot">
    <?php if ($cd_remain > 0): ?>
    <div class="ship-msg">
        <i class="fas fa-truck me-1" style="color:var(--brand-gold)"></i>
        Add <strong><?= formatPrice($cd_remain) ?></strong> more for free shipping
    </div>
    <?php else: ?>
    <div class="ship-msg" style="color:#5C8C4A">
        <i class="fas fa-check-circle me-1"></i>
        You've unlocked free shipping
    </div>
    <?php endif; ?>

    <div class="subtotal-row">
        <span class="label">Subtotal</span>
        <span class="amount"><?= formatPrice($cd_subtotal) ?></span>
    </div>
    <div class="tax-note">Shipping and taxes calculated at checkout.</div>

    <a href="checkout.php" class="btn-checkout">Checkout</a>
    <a href="cart.php" class="view-cart">View full bag</a>
</div>
<?php endif; ?>
