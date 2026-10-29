<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('products.php');

$r = $conn->query("SELECT * FROM products WHERE id = $id");
if (!$r || $r->num_rows === 0) redirect('products.php');
$product = $r->fetch_assoc();

$categories = getCategories($conn);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name      = trim($_POST['name']              ?? '');
    $cat_id    = (int)($_POST['category_id']       ?? 0);
    $price     = (float)($_POST['price']           ?? 0);
    $sale_price= trim($_POST['sale_price'] ?? '') !== '' ? (float)$_POST['sale_price'] : null;
    $stock     = (int)($_POST['stock']             ?? 0);
    $sku       = trim($_POST['sku']                ?? '');
    $short_desc= trim($_POST['short_description']  ?? '');
    $desc      = trim($_POST['description']        ?? '');
    $featured  = isset($_POST['is_featured'])      ? 1 : 0;
    $active    = isset($_POST['is_active'])        ? 1 : 0;

    if (!$name)    $errors[] = 'Product name is required.';
    if ($price <= 0) $errors[] = 'Price must be greater than 0.';

    $imageName = $product['image'];
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','gif'];
        if (in_array($ext, $allowed)) {
            $newName   = 'product-' . time() . '-' . mt_rand(100,999) . '.' . $ext;
            $uploadDir = __DIR__ . '/../assets/images/products/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newName)) {
                $imageName = $newName;
            }
        } else {
            $errors[] = 'Invalid image format.';
        }
    }

    if (empty($errors)) {
        $nE   = sanitize($conn, $name);
        $dE   = sanitize($conn, $desc);
        $sdE  = sanitize($conn, $short_desc);
        $skE  = sanitize($conn, $sku);
        $imgE = sanitize($conn, $imageName);
        $catV = $cat_id ?: 'NULL';
        $saleV= $sale_price !== null ? $sale_price : 'NULL';

        $conn->query("UPDATE products SET
            category_id = $catV,
            name = '$nE',
            description = '$dE',
            short_description = '$sdE',
            price = $price,
            sale_price = $saleV,
            stock = $stock,
            sku = '$skE',
            image = '$imgE',
            is_featured = $featured,
            is_active = $active
            WHERE id = $id");

        setAlert('success', "Product updated successfully!");
        redirect('products.php');
    }
    // Re-populate form with POST data
    $product = array_merge($product, $_POST);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product — Trovery Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="css/admin.css" rel="stylesheet">
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>

<div class="admin-main">
    <div class="admin-topbar">
        <div class="topbar-title">Edit Product</div>
        <div class="topbar-right">
            <a href="products.php" class="btn-admin-outline btn-admin-sm"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </div>

    <div class="admin-content">
        <div class="admin-page-title">Edit: <?= htmlspecialchars($product['name']) ?></div>
        <div class="admin-breadcrumb"><a href="index.php">Dashboard</a> / <a href="products.php">Products</a> / Edit</div>

        <?php if (!empty($errors)): ?>
        <div class="admin-alert admin-alert-danger mb-3">
            <i class="fas fa-exclamation-circle"></i>
            <div><?php foreach ($errors as $e) echo "<div>$e</div>"; ?></div>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="admin-form-card mb-4">
                    <h6 style="font-family:var(--font-head);font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--admin-border)">Basic Information</h6>
                    <div class="mb-3">
                        <label class="admin-label">Product Name *</label>
                        <input type="text" name="name" class="admin-input" required
                               value="<?= htmlspecialchars($product['name']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="admin-label">Short Description</label>
                        <input type="text" name="short_description" class="admin-input"
                               value="<?= htmlspecialchars($product['short_description'] ?? '') ?>">
                    </div>
                    <div class="mb-0">
                        <label class="admin-label">Full Description</label>
                        <textarea name="description" class="admin-input" rows="6"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="admin-form-card mb-4">
                    <h6 style="font-family:var(--font-head);font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--admin-border)">Pricing & Inventory</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="admin-label">Regular Price (₱) *</label>
                            <input type="number" name="price" class="admin-input" required step="0.01" min="0"
                                   value="<?= htmlspecialchars($product['price']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="admin-label">Sale Price (₱)</label>
                            <input type="number" name="sale_price" class="admin-input" step="0.01" min="0"
                                   value="<?= htmlspecialchars($product['sale_price'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="admin-label">Stock</label>
                            <input type="number" name="stock" class="admin-input" min="0"
                                   value="<?= htmlspecialchars($product['stock']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="admin-label">SKU</label>
                            <input type="text" name="sku" class="admin-input"
                                   value="<?= htmlspecialchars($product['sku'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="admin-form-card">
                    <h6 style="font-family:var(--font-head);font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--admin-border)">Product Image</h6>
                    <?php if ($product['image']): ?>
                    <div class="mb-3">
                        <img src="../assets/images/products/<?= htmlspecialchars($product['image']) ?>"
                             id="imagePreview" style="max-width:160px;border-radius:10px"
                             onerror="this.src='../placeholder.php'">
                        <div class="small text-muted mt-1">Current image — upload new to replace</div>
                    </div>
                    <?php else: ?>
                    <img id="imagePreview" style="max-width:160px;border-radius:10px;display:none">
                    <?php endif; ?>
                    <input type="file" name="image" id="imageInput" accept="image/*"
                           onchange="previewImage(this)">
                </div>
            </div>

            <div class="col-lg-4">
                <div class="admin-form-card mb-4">
                    <h6 style="font-family:var(--font-head);font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--admin-border)">Organization</h6>
                    <div class="mb-3">
                        <label class="admin-label">Category</label>
                        <select name="category_id" class="admin-input admin-select">
                            <option value="">— Select Category —</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="admin-form-card mb-4">
                    <h6 style="font-family:var(--font-head);font-weight:700;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--admin-border)">Visibility</h6>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <div style="font-weight:600;font-size:.88rem">Active</div>
                            <div style="font-size:.76rem;color:var(--admin-muted)">Visible on store</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="is_active" <?= $product['is_active'] ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div style="font-weight:600;font-size:.88rem">Featured</div>
                            <div style="font-size:.76rem;color:var(--admin-muted)">Show on homepage</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="is_featured" <?= $product['is_featured'] ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-admin-accent w-100" style="padding:12px;font-size:.95rem;justify-content:center">
                    <i class="fas fa-save"></i> Update Product
                </button>
                <a href="products.php" class="btn-admin-outline w-100 mt-2" style="justify-content:center">Cancel</a>
            </div>
        </div>
        </form>
    </div>
</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
