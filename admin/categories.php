<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireAdmin();

$errors = [];
$editCat = null;

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Delete
    if ($action === 'delete') {
        $id = (int)($_POST['cat_id'] ?? 0);
        if ($id) {
            $conn->query("UPDATE products SET category_id = NULL WHERE category_id = $id");
            $conn->query("DELETE FROM categories WHERE id = $id");
            setAlert('success', 'Category deleted. Products uncategorized.');
        }
        header('Location: categories.php'); exit;
    }

    // Toggle active
    if ($action === 'toggle') {
        $id  = (int)($_POST['cat_id'] ?? 0);
        $val = (int)($_POST['current'] ?? 1) ? 0 : 1;
        if ($id) $conn->query("UPDATE categories SET is_active = $val WHERE id = $id");
        header('Location: categories.php'); exit;
    }

    // Add or Edit
    if (in_array($action, ['add', 'edit'])) {
        $catId      = (int)($_POST['cat_id'] ?? 0);
        $name       = trim($_POST['name']        ?? '');
        $desc       = trim($_POST['description'] ?? '');
        $icon       = trim($_POST['icon']        ?? 'fas fa-tag');
        $sortOrder  = (int)($_POST['sort_order'] ?? 0);
        $isActive   = isset($_POST['is_active'])  ? 1 : 0;

        if (!$name) $errors[] = 'Category name is required.';

        // Generate slug
        $slug     = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
        $slugBase = $slug;
        $si       = 1;
        $slugCheck = $conn->query("SELECT id FROM categories WHERE slug = '" . sanitize($conn, $slug) . "'" . ($catId ? " AND id != $catId" : ''));
        while ($slugCheck && $slugCheck->num_rows > 0) {
            $slug      = $slugBase . '-' . $si++;
            $slugCheck = $conn->query("SELECT id FROM categories WHERE slug = '" . sanitize($conn, $slug) . "'" . ($catId ? " AND id != $catId" : ''));
        }

        if (empty($errors)) {
            $nE  = sanitize($conn, $name);
            $sE  = sanitize($conn, $slug);
            $dE  = sanitize($conn, $desc);
            $iE  = sanitize($conn, $icon);

            if ($action === 'add') {
                $conn->query("INSERT INTO categories (name, slug, description, icon, sort_order, is_active)
                              VALUES ('$nE', '$sE', '$dE', '$iE', $sortOrder, $isActive)");
                setAlert('success', "Category \"$name\" added.");
            } else {
                $conn->query("UPDATE categories SET name='$nE', slug='$sE', description='$dE',
                              icon='$iE', sort_order=$sortOrder, is_active=$isActive WHERE id=$catId");
                setAlert('success', "Category \"$name\" updated.");
            }
            header('Location: categories.php'); exit;
        }

        // Re-populate form on error
        if ($action === 'edit') {
            $editCat = ['id'=>$catId,'name'=>$name,'description'=>$desc,'icon'=>$icon,'sort_order'=>$sortOrder,'is_active'=>$isActive];
        }
    }
}

// Load all categories with product count
$cats = $conn->query("SELECT c.*, COUNT(p.id) AS product_count
                      FROM categories c
                      LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
                      GROUP BY c.id
                      ORDER BY c.sort_order, c.name");
$cats = $cats ? $cats->fetch_all(MYSQLI_ASSOC) : [];

// If editing, load the category
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $r   = $conn->query("SELECT * FROM categories WHERE id = $eid");
    if ($r && $r->num_rows) $editCat = $r->fetch_assoc();
}

$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories — Trovery Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>

<main class="admin-main">
    <div class="admin-topbar">
        <h4 class="mb-0 fw-bold">Categories</h4>
        <button class="btn btn-accent btn-sm" data-bs-toggle="modal" data-bs-target="#catModal" onclick="openAddModal()">
            <i class="fas fa-plus me-1"></i> Add Category
        </button>
    </div>

    <div class="p-4">

        <?php if ($alert): ?>
        <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($alert['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px">Sort</th>
                            <th>Category</th>
                            <th>Slug</th>
                            <th class="text-center">Products</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($cats)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No categories yet.</td></tr>
                    <?php else: foreach ($cats as $c): ?>
                        <tr>
                            <td class="text-muted small"><?= $c['sort_order'] ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span style="width:32px;height:32px;border-radius:8px;background:var(--accent-light,#fff3ee);display:flex;align-items:center;justify-content:center;font-size:.85rem;color:var(--accent,#ff6b35)">
                                        <i class="<?= htmlspecialchars($c['icon']) ?>"></i>
                                    </span>
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars($c['name']) ?></div>
                                        <?php if ($c['description']): ?>
                                        <div class="text-muted small" style="max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                            <?= htmlspecialchars($c['description']) ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><code class="small"><?= htmlspecialchars($c['slug']) ?></code></td>
                            <td class="text-center">
                                <a href="products.php?cat=<?= $c['id'] ?>" class="badge bg-light text-dark text-decoration-none">
                                    <?= $c['product_count'] ?> products
                                </a>
                            </td>
                            <td class="text-center">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action"  value="toggle">
                                    <input type="hidden" name="cat_id"  value="<?= $c['id'] ?>">
                                    <input type="hidden" name="current" value="<?= $c['is_active'] ?>">
                                    <button type="submit" class="btn btn-sm <?= $c['is_active'] ? 'btn-success' : 'btn-outline-secondary' ?>" style="min-width:72px">
                                        <?= $c['is_active'] ? 'Active' : 'Inactive' ?>
                                    </button>
                                </form>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary me-1"
                                        onclick="openEditModal(<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)">
                                    <i class="fas fa-pencil"></i>
                                </button>
                                <form method="POST" class="d-inline"
                                      onsubmit="return confirm('Delete \'<?= addslashes(htmlspecialchars($c['name'])) ?>\'? Products in this category will become uncategorized.')">
                                    <input type="hidden" name="action"  value="delete">
                                    <input type="hidden" name="cat_id"  value="<?= $c['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<!-- Add / Edit Modal -->
<div class="modal fade" id="catModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action"  id="modalAction" value="add">
            <input type="hidden" name="cat_id"  id="modalCatId"  value="">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="modalName" class="form-control" required placeholder="e.g. Food &amp; Delicacies">
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="modalDesc" class="form-control" rows="2"></textarea>
                </div>
                <div class="row g-3">
                    <div class="col-7">
                        <label class="form-label">Icon <small class="text-muted">(FontAwesome class)</small></label>
                        <input type="text" name="icon" id="modalIcon" class="form-control" placeholder="fas fa-tag">
                    </div>
                    <div class="col-5">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" id="modalSort" class="form-control" value="0" min="0">
                    </div>
                </div>
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" name="is_active" id="modalActive" checked>
                    <label class="form-check-label" for="modalActive">Active (visible on shop)</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-accent">Save Category</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openAddModal() {
    document.getElementById('modalAction').value = 'add';
    document.getElementById('modalCatId').value  = '';
    document.getElementById('modalTitle').textContent = 'Add Category';
    document.getElementById('modalName').value  = '';
    document.getElementById('modalDesc').value  = '';
    document.getElementById('modalIcon').value  = 'fas fa-tag';
    document.getElementById('modalSort').value  = '0';
    document.getElementById('modalActive').checked = true;
}

function openEditModal(cat) {
    document.getElementById('modalAction').value = 'edit';
    document.getElementById('modalCatId').value  = cat.id;
    document.getElementById('modalTitle').textContent = 'Edit Category';
    document.getElementById('modalName').value  = cat.name;
    document.getElementById('modalDesc').value  = cat.description || '';
    document.getElementById('modalIcon').value  = cat.icon || 'fas fa-tag';
    document.getElementById('modalSort').value  = cat.sort_order || '0';
    document.getElementById('modalActive').checked = cat.is_active == 1;
    new bootstrap.Modal(document.getElementById('catModal')).show();
}
</script>
</body>
</html>
