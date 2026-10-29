<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
define('PAGE_TITLE', 'Shop');
$base_url = '';

// ── Filters ───────────────────────────────────────────────────
$search      = trim($_GET['q']          ?? '');
$catSlug     = trim($_GET['category']   ?? '');
$sort        = trim($_GET['sort']       ?? 'newest');
$sale        = isset($_GET['sale'])     ? 1 : 0;
$featured    = isset($_GET['featured']) ? 1 : 0;
// Treat empty string and "0" as "no filter" — a Max of 0 makes no sense
$minPrice    = (isset($_GET['min_price']) && $_GET['min_price'] !== '')
                ? (float)$_GET['min_price'] : 0;
$maxPrice    = (isset($_GET['max_price']) && $_GET['max_price'] !== '' && (float)$_GET['max_price'] > 0)
                ? (float)$_GET['max_price'] : 99999;
$page        = max(1, (int)($_GET['page'] ?? 1));
$perPage     = ITEMS_PER_PAGE;
$offset      = ($page - 1) * $perPage;

// Build WHERE
$where = ["p.is_active = 1"];
if ($search)  $where[] = "(p.name LIKE '%" . sanitize($conn, $search) . "%' OR p.short_description LIKE '%" . sanitize($conn, $search) . "%')";
if ($catSlug) $where[] = "c.slug = '" . sanitize($conn, $catSlug) . "'";
if ($sale)    $where[] = "p.sale_price IS NOT NULL";
if ($featured)$where[] = "p.is_featured = 1";
if ($minPrice > 0) $where[] = "COALESCE(p.sale_price, p.price) >= $minPrice";
if ($maxPrice < 99999) $where[] = "COALESCE(p.sale_price, p.price) <= $maxPrice";

$whereSQL = implode(' AND ', $where);

$orderMap = [
    'newest'   => 'p.created_at DESC',
    'oldest'   => 'p.created_at ASC',
    'price_asc'=> 'COALESCE(p.sale_price, p.price) ASC',
    'price_desc'=> 'COALESCE(p.sale_price, p.price) DESC',
    'rating'   => 'p.rating DESC',
    'name'     => 'p.name ASC',
];
$orderSQL = $orderMap[$sort] ?? 'p.created_at DESC';

// Total count (filtered)
$countRes  = $conn->query("SELECT COUNT(*) AS n FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE $whereSQL");
$totalItems = (int)$countRes->fetch_assoc()['n'];
$totalPages = max(1, (int)ceil($totalItems / $perPage));

// Total count (unfiltered) — for the "All Categories" badge
$totalActive = (int)$conn->query("SELECT COUNT(*) AS n FROM products WHERE is_active = 1")->fetch_assoc()['n'];

// Products
$products   = $conn->query("SELECT p.*, c.name AS category_name, c.slug AS category_slug
                             FROM products p
                             LEFT JOIN categories c ON p.category_id = c.id
                             WHERE $whereSQL
                             ORDER BY $orderSQL
                             LIMIT $perPage OFFSET $offset");
$products   = $products ? $products->fetch_all(MYSQLI_ASSOC) : [];

$categories = getCategories($conn);
$activeCat  = null;
foreach ($categories as $cat) {
    if ($cat['slug'] === $catSlug) { $activeCat = $cat; break; }
}

$pageTitle = $activeCat ? $activeCat['name'] : ($search ? "Search: $search" : 'All Products');
?>
<?php require_once 'includes/header.php'; ?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="shop.php">Shop</a></li>
                <?php if ($activeCat): ?>
                <li class="breadcrumb-item active"><?= htmlspecialchars($activeCat['name']) ?></li>
                <?php elseif ($search): ?>
                <li class="breadcrumb-item active">Search results</li>
                <?php endif; ?>
            </ol>
        </nav>
        <h1 class="mt-1"><?= htmlspecialchars($pageTitle) ?></h1>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">

        <!-- ── Filter Sidebar ── -->
        <div class="col-lg-3">
            <div class="d-lg-none mb-3">
                <button class="btn btn-outline-navy w-100" id="filterToggle">
                    <i class="fas fa-filter me-2"></i> Filters
                </button>
            </div>
            <div class="filter-card" id="filterPanel">
                <form method="GET" id="filterForm">
                    <?php if ($search): ?><input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>

                    <!-- Categories -->
                    <div class="mb-4">
                        <div class="filter-title"><i class="fas fa-th-large me-2 text-accent"></i>Category</div>
                        <div class="filter-option">
                            <input type="radio" name="category" id="cat_all" value=""
                                   <?= !$catSlug ? 'checked' : '' ?> onchange="this.form.submit()">
                            <label for="cat_all">All Categories</label>
                            <span class="filter-count"><?= $totalActive ?></span>
                        </div>
                        <?php foreach ($categories as $cat): ?>
                        <div class="filter-option">
                            <input type="radio" name="category" id="cat_<?= $cat['id'] ?>"
                                   value="<?= htmlspecialchars($cat['slug']) ?>"
                                   <?= $catSlug === $cat['slug'] ? 'checked' : '' ?>
                                   onchange="this.form.submit()">
                            <label for="cat_<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></label>
                            <span class="filter-count"><?= $cat['product_count'] ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Price Range -->
                    <div class="mb-4">
                        <div class="filter-title"><i class="fas fa-tag me-2 text-accent"></i>Price Range</div>
                        <div class="price-range-inputs">
                            <input type="number" name="min_price" placeholder="Min"
                                   value="<?= $minPrice > 0 ? $minPrice : '' ?>" min="0" step="50">
                            <span class="text-muted">—</span>
                            <input type="number" name="max_price" placeholder="Max"
                                   value="<?= $maxPrice < 99999 ? $maxPrice : '' ?>" min="0" step="50">
                        </div>
                    </div>

                    <!-- Special Filters -->
                    <div class="mb-3">
                        <div class="filter-title"><i class="fas fa-sliders-h me-2 text-accent"></i>Special</div>
                        <div class="filter-option">
                            <input type="checkbox" name="sale" id="filter_sale" value="1"
                                   <?= $sale ? 'checked' : '' ?> onchange="this.form.submit()">
                            <label for="filter_sale">On Sale Only</label>
                        </div>
                        <div class="filter-option">
                            <input type="checkbox" name="featured" id="filter_featured" value="1"
                                   <?= $featured ? 'checked' : '' ?> onchange="this.form.submit()">
                            <label for="filter_featured">Featured</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-navy w-100 btn-sm">Apply Filters</button>
                    <a href="shop.php" class="btn btn-outline-secondary w-100 btn-sm mt-2">Clear All</a>
                </form>
            </div>
        </div>

        <!-- ── Product Grid ── -->
        <div class="col-lg-9">

            <!-- Toolbar -->
            <div class="shop-toolbar">
                <span class="toolbar-result-count">
                    <?php if ($totalItems > 0): ?>
                        Showing <strong><?= $offset + 1 ?>–<?= min($offset + $perPage, $totalItems) ?></strong>
                        of <strong><?= $totalItems ?></strong> products
                    <?php else: ?>
                        No products found
                    <?php endif; ?>
                </span>
                <div class="toolbar-sort d-flex align-items-center gap-2">
                    <label class="small text-muted mb-0">Sort:</label>
                    <select class="form-select form-select-sm" name="sort" style="width:auto"
                            onchange="
                                const u = new URL(window.location);
                                u.searchParams.set('sort', this.value);
                                u.searchParams.delete('page');
                                window.location = u;
                            ">
                        <option value="newest"    <?= $sort === 'newest'    ? 'selected' : '' ?>>Newest</option>
                        <option value="rating"    <?= $sort === 'rating'    ? 'selected' : '' ?>>Top Rated</option>
                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_desc"<?= $sort === 'price_desc'? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="name"      <?= $sort === 'name'      ? 'selected' : '' ?>>Name A–Z</option>
                    </select>
                </div>
            </div>

            <!-- Products -->
            <?php if (empty($products)): ?>
            <?php $q = trim($_GET['q'] ?? ''); ?>
            <div class="search-empty">
                <div class="se-icon"><i class="fas fa-search"></i></div>
                <?php if ($q !== ''): ?>
                <h4>We couldn't find anything for <span class="se-query">"<?= htmlspecialchars($q) ?>"</span></h4>
                <p>It happens. A few things that might help:</p>
                <ul class="se-suggest">
                    <li>Check the spelling — Filipino names can be tricky (try "inabel" not "enabel")</li>
                    <li>Try a single word instead of a phrase</li>
                    <li>Browse a category from the menu above</li>
                </ul>
                <?php else: ?>
                <h4>No pieces match those filters yet</h4>
                <p>Try widening the price range, clearing a filter, or browsing all of our work.</p>
                <?php endif; ?>
                <div class="d-inline-flex gap-2 flex-wrap justify-content-center">
                    <a href="shop.php" class="pdp-btn-add" style="text-decoration:none;height:46px;padding:0 28px;font-size:.78rem">Browse Everything</a>
                    <a href="index.php" class="pdp-btn-buy" style="height:46px;padding:0 28px;font-size:.78rem;margin:0">Back to Home</a>
                </div>
            </div>
            <?php else: ?>
            <div class="row">
                <?php foreach ($products as $p): ?>
                    <?= productCard($p, $base_url) ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center flex-wrap">
                    <?php
                    $queryParams = $_GET;
                    if ($page > 1):
                        $queryParams['page'] = $page - 1;
                    ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query($queryParams) ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++):
                        $queryParams['page'] = $i;
                    ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query($queryParams) ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages):
                        $queryParams['page'] = $page + 1;
                    ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query($queryParams) ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
