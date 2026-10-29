<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireAdmin();

$categories = getCategories($conn);
$errors = [];
$values = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = $_POST;
    $name      = trim($_POST['name']              ?? '');
    $cat_id    = (int)($_POST['category_id']       ?? 0);
    $price     = (float)($_POST['price']           ?? 0);
    $sale_price= $_POST['sale_price'] !== '' ? (float)$_POST['sale_price'] : 'NULL';
    $stock     = (int)($_POST['stock']             ?? 0);
    $sku       = trim($_POST['sku']                ?? '');
    $short_desc= trim($_POST['short_description']  ?? '');
    $desc      = trim($_POST['description']        ?? '');
    $featured  = isset($_POST['is_featured'])      ? 1 : 0;
    $active    = isset($_POST['is_active'])        ? 1 : 0;

    if (!$name)    $errors[] = 'Product name is required.';
    if ($price <= 0) $errors[] = 'Price must be greater than 0.';

    // Generate slug
    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
    $slugBase = $slug;
    $si = 1;
    while ($conn->query("SELECT id FROM products WHERE slug = '" . sanitize($conn, $slug) . "'")->num_rows > 0) {
        $slug = $slugBase . '-' . $si++;
    }

    // Handle image upload
    $imageName = '';
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext   = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','gif'];
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Invalid image format. Use JPG, PNG, or WebP.';
        } else {
            $imageName = 'product-' . time() . '-' . mt_rand(100, 999) . '.' . $ext;
            $uploadDir = __DIR__ . '/../assets/images/products/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName)) {
                $errors[] = 'Image upload failed.';
                $imageName = '';
            }
        }
    }

    if (empty($errors)) {
        $nE  = sanitize($conn, $name);
        $sE  = sanitize($conn, $slug);
        $dE  = sanitize($conn, $desc);
        $sdE = sanitize($conn, $short_desc);
        $skE = sanitize($conn, $sku);
        $imgE= sanitize($conn, $imageName);
        $saleVal = is_string($sale_price) ? 'NULL' : $sale_price;
        $catVal  = $cat_id ?: 'NULL';

        $conn->query("INSERT INTO products
            (category_id, name, slug, description, short_description,
             price, sale_price, stock, sku, image, is_featured, is_active)
            VALUES
            ($catVal, '$nE', '$sE', '$dE', '$sdE',
             $price, $saleVal, $stock, '$skE', '$imgE', $featured, $active)");

        setAlert('success', "Product '$name' added successfully!");
        redirect('products.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product — Trovery Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="css/admin.css" rel="stylesheet">
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>

<div class="admin-main">
    <div class="admin-topbar">
        <div class="topbar-title">Add Product</div>
        <div class="topbar-right">
            <a href="products.php" class="btn-admin-outline btn-admin-sm">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="admin-content">
        <div class="admin-page-title">Add New Product</div>
        <div class="admin-breadcrumb"><a href="index.php">Dashboard</a> / <a href="products.php">Products</a> / Add</div>

        <?php if (!empty($errors)): ?>
        <div class="admin-alert admin-alert-danger mb-3">
            <i class="fas fa-exclamation-circle"></i>
            <div><?php foreach ($errors as $e) echo "<div>$e</div>"; ?></div>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
        <div class="row g-4">
            <div class="col-lg-8">

                <!-- Basic Info -->
                <div class="admin-form-card mb-4">
                    <h6 style="font-family:var(--font-head);font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--admin-border)">
                        Basic Information
                    </h6>
                    <div class="mb-3">
                        <label class="admin-label">Product Name *</label>
                        <input type="text" name="name" class="admin-input" required
                               value="<?= htmlspecialchars($values['name'] ?? '') ?>"
                               placeholder="e.g., Wild Forest Honey">
                    </div>
                    <div class="mb-3">
                        <label class="admin-label">Short Description</label>
                        <input type="text" name="short_description" class="admin-input"
                               value="<?= htmlspecialchars($values['short_description'] ?? '') ?>"
                               placeholder="One sentence summary (shows on product card)">
                    </div>
                    <div class="mb-0">
                        <label class="admin-label">Full Description</label>
                        <textarea name="description" class="admin-input" rows="6"
                                  placeholder="Detailed product description…"><?= htmlspecialchars($values['description'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Pricing & Stock -->
                <div class="admin-form-card mb-4">
                    <h6 style="font-family:var(--font-head);font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--admin-border)">
                        Pricing & Inventory
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="admin-label">Regular Price (₱) *</label>
                            <input type="number" name="price" class="admin-input" required
                                   step="0.01" min="0"
                                   value="<?= htmlspecialchars($values['price'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="admin-label">Sale Price (₱) <small style="color:var(--admin-muted)">(optional)</small></label>
                            <input type="number" name="sale_price" class="admin-input"
                                   step="0.01" min="0"
                                   value="<?= htmlspecialchars($values['sale_price'] ?? '') ?>"
                                   placeholder="Leave empty if no sale">
                        </div>
                        <div class="col-md-4">
                            <label class="admin-label">Stock Quantity</label>
                            <input type="number" name="stock" class="admin-input"
                                   min="0" value="<?= htmlspecialchars($values['stock'] ?? '100') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="admin-label">SKU</label>
                            <input type="text" name="sku" class="admin-input"
                                   value="<?= htmlspecialchars($values['sku'] ?? '') ?>"
                                   placeholder="e.g., TRV-FD-001">
                        </div>
                    </div>
                </div>

                <!-- Image -->
                <div class="admin-form-card">
                    <h6 style="font-family:var(--font-head);font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--admin-border)">
                        Product Image
                    </h6>
                    <div style="border:2px dashed var(--admin-border);border-radius:10px;padding:32px;text-align:center;cursor:pointer"
                         onclick="document.getElementById('imageInput').click()">
                        <i class="fas fa-cloud-upload-alt" style="font-size:2rem;color:var(--admin-muted);display:block;margin-bottom:10px"></i>
                        <p style="color:var(--admin-muted);font-size:.88rem;margin:0">Click to upload product image (JPG, PNG, WebP)</p>
                        <p style="color:var(--admin-muted);font-size:.78rem;margin:4px 0 0">Recommended: 800×800px, max 2MB</p>
                    </div>
                    <input type="file" name="image" id="imageInput" accept="image/*" style="display:none"
                           onchange="previewImage(this)">
                    <img id="imagePreview" style="max-width:200px;border-radius:10px;margin-top:12px;display:none">
                </div>

            </div>

            <!-- Sidebar Options -->
            <div class="col-lg-4">
                <div class="admin-form-card mb-4">
                    <h6 style="font-family:var(--font-head);font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--admin-border)">
                        Organization
                    </h6>
                    <div class="mb-3">
                        <label class="admin-label">Category</label>
                        <select name="category_id" class="admin-input admin-select">
                            <option value="">— Select Category —</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"
                                    <?= ($values['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="admin-form-card mb-4">
                    <h6 style="font-family:var(--font-head);font-weight:700;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--admin-border)">
                        Visibility
                    </h6>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <div style="font-weight:600;font-size:.88rem">Active (Visible)</div>
                            <div style="font-size:.76rem;color:var(--admin-muted)">Show on the store</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="is_active" <?= isset($values['is_active']) || !$_POST ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div style="font-weight:600;font-size:.88rem">Featured</div>
                            <div style="font-size:.76rem;color:var(--admin-muted)">Show on homepage</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="is_featured" <?= isset($values['is_featured']) ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-admin-accent w-100" style="padding:12px;font-size:.95rem;justify-content:center">
                    <i class="fas fa-save"></i> Save Product
                </button>
                <a href="products.php" class="btn-admin-outline w-100 mt-2" style="justify-content:center">
                    Cancel
                </a>
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
