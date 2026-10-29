<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireAdmin();

$search  = trim($_GET['q']    ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where = ["role = 'customer'"];
if ($search) $where[] = "(name LIKE '%" . sanitize($conn, $search) . "%' OR email LIKE '%" . sanitize($conn, $search) . "%')";
$whereSQL = implode(' AND ', $where);

$total      = (int)$conn->query("SELECT COUNT(*) AS n FROM users WHERE $whereSQL")->fetch_assoc()['n'];
$totalPages = max(1, ceil($total / $perPage));

$users = $conn->query("SELECT u.*,
                              COUNT(DISTINCT o.id) AS order_count,
                              COALESCE(SUM(o.total),0) AS total_spent
                       FROM users u
                       LEFT JOIN orders o ON u.id = o.user_id
                       WHERE $whereSQL
                       GROUP BY u.id
                       ORDER BY u.created_at DESC
                       LIMIT $perPage OFFSET $offset");
$users = $users ? $users->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers — Trovery Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="css/admin.css" rel="stylesheet">
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>

<div class="admin-main">
    <div class="admin-topbar">
        <div class="topbar-title">Customers</div>
    </div>

    <div class="admin-content">
        <div class="admin-page-title">Customers</div>
        <div class="admin-breadcrumb"><a href="index.php">Dashboard</a> / Customers</div>

        <div class="admin-card mb-3">
            <div class="admin-card-body" style="padding:14px">
                <form method="GET" class="d-flex gap-2">
                    <div class="admin-search" style="flex:1">
                        <i class="fas fa-search"></i>
                        <input type="text" name="q" class="admin-input" placeholder="Search by name or email…"
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <button type="submit" class="btn-admin-primary btn-admin-sm"><i class="fas fa-search"></i></button>
                    <?php if ($search): ?><a href="users.php" class="btn-admin-outline btn-admin-sm">Clear</a><?php endif; ?>
                </form>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-header">
                <h6>All Customers <span style="color:var(--admin-muted);font-weight:400">(<?= $total ?>)</span></h6>
            </div>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Joined</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="text-muted"><?= $u['id'] ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:34px;height:34px;background:var(--admin-navy);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-family:var(--font-head);font-weight:700;font-size:.85rem;flex-shrink:0">
                                    <?= strtoupper($u['name'][0]) ?>
                                </div>
                                <div>
                                    <div style="font-weight:600;font-size:.86rem"><?= htmlspecialchars($u['name']) ?></div>
                                    <div style="font-size:.75rem;color:var(--admin-muted)"><?= htmlspecialchars($u['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="small text-muted"><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                        <td><strong><?= $u['order_count'] ?></strong></td>
                        <td><strong><?= formatPrice($u['total_spent']) ?></strong></td>
                        <td class="small text-muted"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <span class="admin-badge <?= $u['is_active'] ? 'ab-active' : 'ab-inactive' ?>">
                                <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-5">No customers found.</td></tr>
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
