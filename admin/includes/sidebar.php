<?php
$pendingOrders = 0;
$r = $conn->query("SELECT COUNT(*) AS n FROM orders WHERE status = 'pending'");
if ($r) $pendingOrders = (int)$r->fetch_assoc()['n'];

$currentPage = basename($_SERVER['PHP_SELF']);
function activeIf(string $page, string $current): string {
    return $page === $current ? 'active' : '';
}
?>
<aside class="admin-sidebar">
    <a href="../index.php" class="sidebar-brand">
        <div class="brand-dot">T</div>
        <span class="brand-name">rovery</span>
    </a>

    <div class="sidebar-label">Dashboard</div>
    <a href="index.php" class="sidebar-item <?= activeIf('index.php', $currentPage) ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>

    <div class="sidebar-label">Catalog</div>
    <a href="products.php" class="sidebar-item <?= activeIf('products.php', $currentPage) ?>">
        <i class="fas fa-box"></i> Products
    </a>
    <a href="add-product.php" class="sidebar-item <?= activeIf('add-product.php', $currentPage) ?>">
        <i class="fas fa-plus-circle"></i> Add Product
    </a>
    <a href="categories.php" class="sidebar-item <?= activeIf('categories.php', $currentPage) ?>">
        <i class="fas fa-th-large"></i> Categories
    </a>

    <div class="sidebar-label">Sales</div>
    <a href="orders.php" class="sidebar-item <?= activeIf('orders.php', $currentPage) ?>">
        <i class="fas fa-shopping-cart"></i> Orders
        <?php if ($pendingOrders > 0): ?>
        <span class="sidebar-badge"><?= $pendingOrders ?></span>
        <?php endif; ?>
    </a>

    <div class="sidebar-label">People</div>
    <a href="users.php" class="sidebar-item <?= activeIf('users.php', $currentPage) ?>">
        <i class="fas fa-users"></i> Customers
    </a>

    <div style="margin-top:auto;padding:16px 20px;border-top:1px solid rgba(255,255,255,.06)">
        <a href="../account.php" class="sidebar-item" style="padding:8px 0">
            <i class="fas fa-user-circle"></i>
            <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></span>
        </a>
        <a href="../logout.php" class="sidebar-item text-danger" style="padding:8px 0">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</aside>
