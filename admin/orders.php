<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireAdmin();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId   = (int)$_POST['order_id'];
    $newStatus = sanitize($conn, $_POST['status']);
    $allowed   = ['pending','processing','shipped','delivered','cancelled','refunded'];
    if (in_array($newStatus, $allowed)) {
        $conn->query("UPDATE orders SET status = '$newStatus' WHERE id = $orderId");
        setAlert('success', "Order status updated to '$newStatus'.");
    }
    header('Location: orders.php' . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''));
    exit;
}

$status  = trim($_GET['status'] ?? '');
$search  = trim($_GET['q']      ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

$where = ['1=1'];
if ($status) $where[] = "status = '" . sanitize($conn, $status) . "'";
if ($search) $where[] = "(order_number LIKE '%" . sanitize($conn, $search) . "%' OR customer_name LIKE '%" . sanitize($conn, $search) . "%' OR customer_email LIKE '%" . sanitize($conn, $search) . "%')";
$whereSQL = implode(' AND ', $where);

$total      = (int)$conn->query("SELECT COUNT(*) AS n FROM orders WHERE $whereSQL")->fetch_assoc()['n'];
$totalPages = max(1, ceil($total / $perPage));

$orders = $conn->query("SELECT * FROM orders WHERE $whereSQL ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$orders = $orders ? $orders->fetch_all(MYSQLI_ASSOC) : [];

$statusCounts = [];
$r = $conn->query("SELECT status, COUNT(*) AS n FROM orders GROUP BY status");
while ($row = $r->fetch_assoc()) $statusCounts[$row['status']] = $row['n'];

$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders — Trovery Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="css/admin.css" rel="stylesheet">
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>

<div class="admin-main">
    <div class="admin-topbar">
        <div class="topbar-title">Orders</div>
    </div>

    <div class="admin-content">
        <div class="admin-page-title">Orders</div>
        <div class="admin-breadcrumb"><a href="index.php">Dashboard</a> / Orders</div>

        <?php if ($alert): ?>
        <div class="admin-alert admin-alert-<?= $alert['type'] === 'success' ? 'success' : 'danger' ?>">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($alert['message']) ?>
        </div>
        <?php endif; ?>

        <!-- Status Filter Tabs -->
        <div class="d-flex gap-2 flex-wrap mb-3">
            <?php
            $tabs = [
                ''           => 'All',
                'pending'    => 'Pending',
                'processing' => 'Processing',
                'shipped'    => 'Shipped',
                'delivered'  => 'Delivered',
                'cancelled'  => 'Cancelled',
            ];
            foreach ($tabs as $val => $label):
                $cnt = $val ? ($statusCounts[$val] ?? 0) : array_sum($statusCounts);
                $active = $status === $val;
            ?>
            <a href="orders.php<?= $val ? '?status=' . $val : '' ?>"
               class="admin-page-link <?= $active ? 'active' : '' ?>" style="white-space:nowrap">
                <?= $label ?> <span style="opacity:.7">(<?= $cnt ?>)</span>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Search -->
        <div class="admin-card mb-3">
            <div class="admin-card-body" style="padding:14px">
                <form method="GET" class="d-flex gap-2">
                    <?php if ($status): ?><input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>"><?php endif; ?>
                    <div class="admin-search" style="flex:1">
                        <i class="fas fa-search"></i>
                        <input type="text" name="q" class="admin-input" placeholder="Search order #, customer name, email…"
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <button type="submit" class="btn-admin-primary btn-admin-sm"><i class="fas fa-search"></i> Search</button>
                    <?php if ($search): ?><a href="orders.php<?= $status ? '?status='.$status : '' ?>" class="btn-admin-outline btn-admin-sm">Clear</a><?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h6>Orders <span style="color:var(--admin-muted);font-weight:400">(<?= $total ?>)</span></h6>
            </div>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $order):
                        $itemCount = $conn->query("SELECT SUM(quantity) AS n FROM order_items WHERE order_id = {$order['id']}")->fetch_assoc()['n'];
                        $statusClasses = [
                            'pending'    => 'ab-pending',
                            'processing' => 'ab-processing',
                            'shipped'    => 'ab-shipped',
                            'delivered'  => 'ab-delivered',
                            'cancelled'  => 'ab-cancelled',
                            'refunded'   => 'ab-cancelled',
                        ];
                        $sCls = $statusClasses[$order['status']] ?? 'ab-pending';
                    ?>
                    <tr>
                        <td>
                            <a href="view-order.php?id=<?= $order['id'] ?>"
                               style="color:var(--admin-navy);font-weight:700;text-decoration:none;font-size:.86rem">
                                <?= htmlspecialchars($order['order_number']) ?>
                            </a>
                        </td>
                        <td>
                            <div style="font-size:.86rem;font-weight:600"><?= htmlspecialchars($order['customer_name']) ?></div>
                            <div style="font-size:.75rem;color:var(--admin-muted)"><?= htmlspecialchars($order['customer_email']) ?></div>
                        </td>
                        <td class="text-center"><?= (int)$itemCount ?></td>
                        <td><strong><?= formatPrice($order['total']) ?></strong></td>
                        <td style="font-size:.82rem;text-transform:capitalize"><?= htmlspecialchars($order['payment_method']) ?></td>
                        <td><span class="admin-badge <?= $sCls ?>"><?= ucfirst($order['status']) ?></span></td>
                        <td style="font-size:.82rem;color:var(--admin-muted)"><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                        <td>
                            <div class="d-flex gap-1 align-items-center">
                                <a href="view-order.php?id=<?= $order['id'] ?>" class="btn-admin-primary btn-admin-sm" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <!-- Quick Status Update -->
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <input type="hidden" name="update_status" value="1">
                                    <select name="status" class="admin-input admin-select" style="padding:4px 26px 4px 8px;font-size:.78rem;height:auto"
                                            onchange="this.form.submit()">
                                        <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $s): ?>
                                        <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($orders)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-5">No orders found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="p-3 border-top d-flex justify-content-center">
                <div class="admin-pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++):
                        $q = array_merge($_GET, ['page' => $i]);
                    ?>
                    <a href="?<?= http_build_query($q) ?>" class="admin-page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
