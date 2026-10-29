<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('orders.php');

$r = $conn->query("SELECT o.*, u.name AS user_account FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = $id");
if (!$r || $r->num_rows === 0) redirect('orders.php');
$order = $r->fetch_assoc();

$items = $conn->query("SELECT * FROM order_items WHERE order_id = $id");
$items = $items ? $items->fetch_all(MYSQLI_ASSOC) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = sanitize($conn, $_POST['status']);
    $tracking  = sanitize($conn, $_POST['tracking_number'] ?? '');
    $conn->query("UPDATE orders SET status='$newStatus', tracking_number='$tracking' WHERE id=$id");
    setAlert('success', 'Order updated.');
    redirect("view-order.php?id=$id");
}
$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order <?= htmlspecialchars($order['order_number']) ?> — Trovery Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="css/admin.css" rel="stylesheet">
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>

<div class="admin-main">
    <div class="admin-topbar">
        <div class="topbar-title">Order Detail</div>
        <div class="topbar-right">
            <a href="orders.php" class="btn-admin-outline btn-admin-sm"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </div>

    <div class="admin-content">
        <div class="admin-page-title"><?= htmlspecialchars($order['order_number']) ?></div>
        <div class="admin-breadcrumb"><a href="index.php">Dashboard</a> / <a href="orders.php">Orders</a> / <?= htmlspecialchars($order['order_number']) ?></div>

        <?php if ($alert): ?>
        <div class="admin-alert admin-alert-success mb-3">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($alert['message']) ?>
        </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-8">

                <!-- Order Items -->
                <div class="admin-card mb-4">
                    <div class="admin-card-header">
                        <h6>Order Items</h6>
                        <span style="font-size:.82rem;color:var(--admin-muted)"><?= count($items) ?> item<?= count($items) != 1 ? 's' : '' ?></span>
                    </div>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="../<?= productImgSrc($item['product_image'] ?? '') ?>"
                                         style="width:44px;height:44px;border-radius:8px;object-fit:cover;background:var(--admin-light)"
                                         onerror="this.src='../placeholder.php'">
                                    <span style="font-weight:600;font-size:.86rem"><?= htmlspecialchars($item['product_name']) ?></span>
                                </div>
                            </td>
                            <td><?= formatPrice($item['price']) ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td><strong><?= formatPrice($item['subtotal']) ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="p-4 border-top">
                        <div class="d-flex justify-content-end">
                            <div style="min-width:220px">
                                <div class="d-flex justify-content-between mb-1 small">
                                    <span class="text-muted">Subtotal</span>
                                    <span><?= formatPrice($order['subtotal']) ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-1 small">
                                    <span class="text-muted">Shipping</span>
                                    <span><?= $order['shipping_fee'] == 0 ? '<span style="color:#28C76F">FREE</span>' : formatPrice($order['shipping_fee']) ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2 small">
                                    <span class="text-muted">VAT (12%)</span>
                                    <span><?= formatPrice($order['tax']) ?></span>
                                </div>
                                <div class="d-flex justify-content-between border-top pt-2">
                                    <strong>Total</strong>
                                    <strong style="font-size:1.1rem;color:var(--admin-navy)"><?= formatPrice($order['total']) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer & Shipping -->
                <div class="admin-card">
                    <div class="admin-card-header"><h6>Customer & Shipping Info</h6></div>
                    <div class="admin-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="admin-label mb-1">Customer</div>
                                <div class="fw-bold"><?= htmlspecialchars($order['customer_name']) ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($order['customer_email']) ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($order['customer_phone']) ?></div>
                                <?php if ($order['user_account']): ?>
                                <div class="small mt-1" style="color:var(--admin-orange)">
                                    <i class="fas fa-user me-1"></i>Registered: <?= htmlspecialchars($order['user_account']) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <div class="admin-label mb-1">Shipping Address</div>
                                <div class="small">
                                    <?= htmlspecialchars($order['shipping_address']) ?><br>
                                    <?= htmlspecialchars($order['shipping_city']) ?>,
                                    <?= htmlspecialchars($order['shipping_state']) ?>
                                    <?= $order['shipping_zip'] ? ', ' . htmlspecialchars($order['shipping_zip']) : '' ?><br>
                                    <?= htmlspecialchars($order['shipping_country']) ?>
                                </div>
                            </div>
                            <?php if ($order['notes']): ?>
                            <div class="col-12">
                                <div class="admin-label mb-1">Notes</div>
                                <div class="small p-2 rounded" style="background:var(--admin-light)">
                                    <?= nl2br(htmlspecialchars($order['notes'])) ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Panel -->
            <div class="col-lg-4">

                <!-- Order Status -->
                <div class="admin-card mb-4">
                    <div class="admin-card-header"><h6>Update Order</h6></div>
                    <div class="admin-card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="admin-label">Order Status</label>
                                <select name="status" class="admin-input admin-select">
                                    <?php foreach (['pending','processing','shipped','delivered','cancelled','refunded'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="admin-label">Tracking Number</label>
                                <input type="text" name="tracking_number" class="admin-input"
                                       value="<?= htmlspecialchars($order['tracking_number'] ?? '') ?>"
                                       placeholder="e.g., JRS-123456789">
                            </div>
                            <button type="submit" name="update_status" class="btn-admin-primary w-100" style="justify-content:center">
                                <i class="fas fa-save"></i> Update
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="admin-card">
                    <div class="admin-card-header"><h6>Order Summary</h6></div>
                    <div class="admin-card-body">
                        <div class="d-flex flex-column gap-2" style="font-size:.86rem">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Order #</span>
                                <strong><?= htmlspecialchars($order['order_number']) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Date</span>
                                <span><?= date('M j, Y g:iA', strtotime($order['created_at'])) ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Status</span>
                                <?php
                                $sc = ['pending'=>'ab-pending','processing'=>'ab-processing','shipped'=>'ab-shipped','delivered'=>'ab-delivered','cancelled'=>'ab-cancelled'];
                                ?>
                                <span class="admin-badge <?= $sc[$order['status']] ?? 'ab-pending' ?>"><?= ucfirst($order['status']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Payment</span>
                                <span style="text-transform:capitalize"><?= htmlspecialchars($order['payment_method']) ?></span>
                            </div>
                            <?php if ($order['tracking_number']): ?>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Tracking</span>
                                <span class="fw-bold"><?= htmlspecialchars($order['tracking_number']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
