<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireAdmin();

// Handle quick toggle active/featured
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid    = (int)($_POST['pid']    ?? 0);
    $field  = in_array($_POST['field'] ?? '', ['is_active','is_featured']) ? $_POST['field'] : null;
    $val    = (int)($_POST['val'] ?? 0) ? 0 : 1;
    if ($pid && $field) {
        $conn->query("UPDATE products SET $field = $val WHERE id = $pid");
    }
    if (isset($_POST['delete_product'])) {
        $delId = (int)$_POST['delete_id'];
        $conn->query("DELETE FROM products WHERE id = $delId");
        setAlert('success', 'Product deleted.');
    }
    header('Location: products.php' . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''));
    exit;
}

$search  = trim($_GET['q']     ?? '');
$catId   = (int)($_GET['cat']  ?? 0);
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

$where = ['1=1'];
if ($search) $where[] = "p.name LIKE '%" . sanitize($conn, $search) . "%'";
if ($catId)  $where[] = "p.category_id = $catId";
$whereSQL = implode(' AND ', $where);

$total      = (int)$conn->query("SELECT COUNT(*) AS n FROM products p WHERE $whereSQL")->fetch_assoc()['n'];
$totalPages = max(1, ceil($total / $perPage));

$products = $conn->query("SELECT p.*, c.name AS category_name
                           FROM products p
                           LEFT JOIN categories c ON p.category_id = c.id
                           WHERE $whereSQL
                           ORDER BY p.created_at DESC
                           LIMIT $perPage OFFSET $offset");
$products = $products ? $products->fetch_all(MYSQLI_ASSOC) : [];
$categories = getCategories($conn);
$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products — Trovery Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="css/admin.css" rel="stylesheet">
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>

<div class="admin-main">
    <div class="admin-topbar">
        <div class="topbar-title">Products</div>
        <div class="topbar-right">
            <a href="add-product.php" class="btn-admin-accent">
                <i class="fas fa-plus"></i> Add Product
            </a>
        </div>
    </div>

    <div class="admin-content">
        <div class="admin-page-title">Products</div>
        <div class="admin-breadcrumb"><a href="index.php">Dashboard</a> / Products</div>

        <?php if ($alert): ?>
        <div class="admin-alert admin-alert-<?= $alert['type'] === 'success' ? 'success' : 'danger' ?>">
            <i class="fas fa-<?= $alert['type'] === 'success' ? 'check' : 'exclamation' ?>-circle"></i>
            <?= htmlspecialchars($alert['message']) ?>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="admin-card mb-3">
            <div class="admin-card-body" style="padding:16px">
                <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
                    <div class="admin-search" style="flex:1;min-width:200px">
                        <i class="fas fa-search"></i>
                        <input type="text" name="q" class="admin-input" placeholder="Search products…"
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <select name="cat" class="admin-input admin-select" style="width:auto">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $catId == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-admin-primary btn-admin-sm">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="products.php" class="btn-admin-outline btn-admin-sm">Clear</a>
                </form>
            </div>
        </div>

        <!-- Products Table -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h6>All Products <span style="color:var(--admin-muted);font-weight:400">(<?= $total ?>)</span></h6>
            </div>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width:50px">#</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Featured</th>
                            <th>Active</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td class="text-muted"><?= $p['id'] ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="../<?= productImgSrc($p['image'] ?? '') ?>"
                                     alt="<?= htmlspecialchars($p['name']) ?>"
                                     class="product-thumb"
                                     onerror="this.src='../placeholder.php'">
                                <div>
                                    <div class="fw-bold" style="font-size:.86rem"><?= htmlspecialchars($p['name']) ?></div>
                                    <div class="text-muted" style="font-size:.74rem"><?= htmlspecialchars($p['sku'] ?? '') ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-muted small"><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                        <td>
                            <?php if ($p['sale_price']): ?>
                            <span style="text-decoration:line-through;color:var(--admin-muted);font-size:.78rem"><?= formatPrice($p['price']) ?></span><br>
                            <strong style="color:var(--admin-orange)"><?= formatPrice($p['sale_price']) ?></strong>
                            <?php else: ?>
                            <strong><?= formatPrice($p['price']) ?></strong>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="<?= $p['stock'] < 10 ? 'text-danger' : ($p['stock'] < 30 ? 'text-warning' : 'text-success') ?> fw-bold">
                                <?= $p['stock'] ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="pid"   value="<?= $p['id'] ?>">
                                <input type="hidden" name="field" value="is_featured">
                                <input type="hidden" name="val"   value="<?= $p['is_featured'] ?>">
                                <label class="toggle-switch">
                                    <input type="checkbox" <?= $p['is_featured'] ? 'checked' : '' ?> onchange="this.closest('form').submit()">
                                    <span class="toggle-slider"></span>
                                </label>
                            </form>
                        </td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="pid"   value="<?= $p['id'] ?>">
                                <input type="hidden" name="field" value="is_active">
                                <input type="hidden" name="val"   value="<?= $p['is_active'] ?>">
                                <label class="toggle-switch">
                                    <input type="checkbox" <?= $p['is_active'] ? 'checked' : '' ?> onchange="this.closest('form').submit()">
                                    <span class="toggle-slider"></span>
                                </label>
                            </form>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="edit-product.php?id=<?= $p['id'] ?>" class="btn-admin-primary btn-admin-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" onsubmit="return confirm('Delete this product permanently?')">
                                    <input type="hidden" name="delete_product" value="1">
                                    <input type="hidden" name="delete_id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn-admin-danger btn-admin-sm" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($products)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-5">No products found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="p-3 border-top d-flex justify-content-center">
                <div class="admin-pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++):
                        $q = array_merge($_GET, ['page' => $i]);
                    ?>
                    <a href="?<?= http_build_query($q) ?>"
                       class="admin-page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
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
