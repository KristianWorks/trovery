<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireAdmin();

// Stats
$totalOrders   = $conn->query("SELECT COUNT(*) AS n FROM orders")->fetch_assoc()['n'];
$totalRevenue  = $conn->query("SELECT COALESCE(SUM(total),0) AS n FROM orders WHERE status != 'cancelled'")->fetch_assoc()['n'];
$totalProducts = $conn->query("SELECT COUNT(*) AS n FROM products WHERE is_active = 1")->fetch_assoc()['n'];
$totalUsers    = $conn->query("SELECT COUNT(*) AS n FROM users WHERE role='customer'")->fetch_assoc()['n'];
$pendingOrders = $conn->query("SELECT COUNT(*) AS n FROM orders WHERE status='pending'")->fetch_assoc()['n'];

// Recent Orders
$recentOrders  = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 8");
$recentOrders  = $recentOrders ? $recentOrders->fetch_all(MYSQLI_ASSOC) : [];

// Top Products
$topProducts   = $conn->query("SELECT p.name, p.price, p.sale_price, p.stock, p.rating,
                                       COALESCE(SUM(oi.quantity),0) AS sold
                                FROM products p
                                LEFT JOIN order_items oi ON p.id = oi.product_id
                                GROUP BY p.id
                                ORDER BY sold DESC, p.rating DESC
                                LIMIT 5");
$topProducts   = $topProducts ? $topProducts->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Trovery Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="css/admin.css" rel="stylesheet">
</head>
<body>

<?php require_once 'includes/sidebar.php'; ?>

<div class="admin-main">
    <!-- Topbar -->
    <div class="admin-topbar">
        <div class="topbar-title">Dashboard</div>
        <div class="topbar-right">
            <a href="../index.php" target="_blank" class="btn-admin-outline btn-admin-sm">
                <i class="fas fa-external-link-alt"></i> View Site
            </a>
            <a href="add-product.php" class="btn-admin-accent btn-admin-sm">
                <i class="fas fa-plus"></i> Add Product
            </a>
        </div>
    </div>

    <div class="admin-content">
        <div class="admin-page-title">Dashboard</div>
        <div class="admin-breadcrumb">Welcome back, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?> 👋</div>

        <?php if ($pendingOrders > 0): ?>
        <div class="admin-alert admin-alert-info mb-4">
            <i class="fas fa-info-circle"></i>
            <span>You have <strong><?= $pendingOrders ?> pending order<?= $pendingOrders > 1 ? 's' : '' ?></strong> waiting for review.
                <a href="orders.php?status=pending" style="color:inherit;font-weight:700;text-decoration:underline">View now →</a>
            </span>
        </div>
        <?php endif; ?>

        <!-- Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <div class="stat-card card-orders">
                    <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                    <div>
                        <div class="stat-value"><?= number_format($totalOrders) ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card card-revenue">
                    <div class="stat-icon"><i class="fas fa-peso-sign"></i></div>
                    <div>
                        <div class="stat-value"><?= formatPrice($totalRevenue) ?></div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card card-products">
                    <div class="stat-icon"><i class="fas fa-box"></i></div>
                    <div>
                        <div class="stat-value"><?= number_format($totalProducts) ?></div>
                        <div class="stat-label">Active Products</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card card-users">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div>
                        <div class="stat-value"><?= number_format($totalUsers) ?></div>
                        <div class="stat-label">Customers</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">

            <!-- Recent Orders -->
            <div class="col-lg-7">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h6>Recent Orders</h6>
                        <a href="orders.php" class="btn-admin-outline btn-admin-sm">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td>
                                        <a href="view-order.php?id=<?= $order['id'] ?>"
                                           style="color:var(--admin-navy);font-weight:600;text-decoration:none">
                                            <?= htmlspecialchars($order['order_number']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                    <td><strong><?= formatPrice($order['total']) ?></strong></td>
                                    <td>
                                        <?php
                                        $statusMap = [
                                            'pending'    => 'ab-pending',
                                            'processing' => 'ab-processing',
                                            'shipped'    => 'ab-shipped',
                                            'delivered'  => 'ab-delivered',
                                            'cancelled'  => 'ab-cancelled',
                                        ];
                                        $cls = $statusMap[$order['status']] ?? 'ab-pending';
                                        ?>
                                        <span class="admin-badge <?= $cls ?>"><?= ucfirst($order['status']) ?></span>
                                    </td>
                                    <td class="text-muted"><?= date('M j', strtotime($order['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recentOrders)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No orders yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Top Products -->
            <div class="col-lg-5">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h6>Top Products</h6>
                        <a href="products.php" class="btn-admin-outline btn-admin-sm">All Products</a>
                    </div>
                    <div class="admin-card-body p-0">
                        <?php foreach ($topProducts as $p): ?>
                        <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
                            <div class="flex-grow-1 min-width-0">
                                <div class="fw-bold small text-truncate"><?= htmlspecialchars($p['name']) ?></div>
                                <div class="text-muted" style="font-size:.75rem">
                                    <?= formatPrice($p['sale_price'] ?: $p['price']) ?> ·
                                    <?= $p['sold'] ?> sold ·
                                    <?= $p['stock'] ?> in stock
                                </div>
                            </div>
                            <div class="text-end">
                                <div style="color:#f5b82e;font-size:.75rem">
                                    <?= number_format($p['rating'], 1) ?> ★
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($topProducts)): ?>
                        <div class="text-center text-muted p-4 small">No products yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
