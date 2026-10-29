<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Auth ──────────────────────────────────────────────────────────────────────

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

function requireLogin(string $redirect = 'login.php'): void {
    if (!isLoggedIn()) {
        setAlert('warning', 'Please log in to continue.');
        redirect($redirect);
    }
}

function requireAdmin(): void {
    if (!isAdmin()) {
        redirect('../index.php');
    }
}

// ── Utilities ─────────────────────────────────────────────────────────────────

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function sanitize(mysqli $conn, mixed $input): string {
    return $conn->real_escape_string(trim((string)$input));
}

function formatPrice(float $price): string {
    return CURRENCY_SYMBOL . number_format($price, 2);
}

function truncate(string $text, int $length = 100): string {
    return mb_strlen($text) > $length ? mb_substr($text, 0, $length) . '…' : $text;
}

function generateOrderNumber(): string {
    return 'TRV-' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 8));
}

function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)      return 'just now';
    if ($diff < 3600)    return floor($diff / 60) . 'm ago';
    if ($diff < 86400)   return floor($diff / 3600) . 'h ago';
    if ($diff < 604800)  return floor($diff / 86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}

function statusBadge(string $status): string {
    $map = [
        'pending'    => 'badge-pending',
        'processing' => 'badge-processing',
        'shipped'    => 'badge-shipped',
        'delivered'  => 'badge-delivered',
        'cancelled'  => 'badge-cancelled',
        'refunded'   => 'badge-refunded',
        'paid'       => 'badge-delivered',
        'failed'     => 'badge-cancelled',
    ];
    $cls = $map[$status] ?? 'badge-pending';
    return "<span class=\"status-badge $cls\">" . ucfirst($status) . "</span>";
}

// ── Flash Alerts ──────────────────────────────────────────────────────────────

function setAlert(string $type, string $message): void {
    $_SESSION['alert'] = compact('type', 'message');
}

function getAlert(): ?array {
    if (isset($_SESSION['alert'])) {
        $a = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return $a;
    }
    return null;
}

function renderAlert(): void {
    $a = getAlert();
    if (!$a) return;
    $icons = ['success' => 'check-circle', 'danger' => 'exclamation-circle',
              'warning' => 'exclamation-triangle', 'info' => 'info-circle'];
    $icon = $icons[$a['type']] ?? 'info-circle';
    echo "<div class=\"alert alert-{$a['type']} alert-dismissible fade show shadow-sm\" role=\"alert\">
            <i class=\"fas fa-$icon me-2\"></i>{$a['message']}
            <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>
          </div>";
}

// ── Stars ─────────────────────────────────────────────────────────────────────

function starsHtml(float $rating, ?int $count = null): string {
    $html = '<span class="stars">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating)          $html .= '<i class="fas fa-star"></i>';
        elseif ($i - 0.5 <= $rating) $html .= '<i class="fas fa-star-half-alt"></i>';
        else                          $html .= '<i class="far fa-star"></i>';
    }
    $html .= '</span>';
    if ($count !== null) $html .= " <small class=\"rating-count\">($count)</small>";
    return $html;
}

// ── Product Image ─────────────────────────────────────────────────────────────

function productImgSrc(string $image = '', string $base = ''): string {
    if ($image) {
        $path = $base . 'assets/images/products/' . $image;
        return $path;
    }
    return $base . 'placeholder.php';
}

// ── Product Card HTML ─────────────────────────────────────────────────────────

function productCard(array $p, string $base = ''): string {
    $effectivePrice = $p['sale_price'] ? (float)$p['sale_price'] : (float)$p['price'];
    $img    = productImgSrc($p['image'] ?? '', $base);
    $name   = htmlspecialchars($p['name'], ENT_QUOTES);
    $slug   = htmlspecialchars($p['slug'], ENT_QUOTES);
    $cat    = htmlspecialchars($p['category_name'] ?? '', ENT_QUOTES);
    $stars  = starsHtml((float)$p['rating'], (int)$p['review_count']);
    $priceHtml = $p['sale_price']
        ? '<span class="price-old">' . formatPrice($p['price']) . '</span> <span class="price-new">' . formatPrice($p['sale_price']) . '</span>'
        : '<span class="price-new">' . formatPrice($p['price']) . '</span>';

    $badgeHtml = '';
    $stock = (int)($p['stock'] ?? 0);
    if ($stock <= 0)                       $badgeHtml .= '<span class="product-badge badge-out">Sold Out</span>';
    elseif ($stock > 0 && $stock <= 5)     $badgeHtml .= '<span class="product-badge badge-low">Only ' . $stock . ' left</span>';
    if ($p['sale_price'])                  $badgeHtml .= '<span class="product-badge badge-sale">Sale</span>';
    if ($p['is_featured'])                 $badgeHtml .= '<span class="product-badge badge-featured">Featured</span>';

    $shortDesc = truncate($p['short_description'] ?? '', 80);
    $disabled  = $stock <= 0 ? 'disabled' : '';
    $btnTitle  = $stock <= 0 ? 'Sold out' : 'Add to bag';

    return <<<HTML
    <div class="col-6 col-md-4 col-lg-3 mb-4">
        <div class="product-card h-100">
            <a href="{$base}product.php?slug={$slug}" class="product-img-link">
                <div class="product-img-wrapper">
                    <img src="{$img}" alt="{$name}" loading="lazy"
                         width="600" height="600" decoding="async"
                         onerror="this.src='{$base}placeholder.php'">
                    <div class="product-badges">{$badgeHtml}</div>
                    <div class="product-hover-overlay">
                        <a href="{$base}product.php?slug={$slug}" class="btn-quick">
                            <i class="fas fa-eye me-1"></i> Quick View
                        </a>
                    </div>
                </div>
            </a>
            <div class="product-card-body">
                <span class="product-category">{$cat}</span>
                <h6 class="product-name">
                    <a href="{$base}product.php?slug={$slug}">{$name}</a>
                </h6>
                <div class="product-rating">{$stars}</div>
                <p class="product-short-desc">{$shortDesc}</p>
                <div class="product-footer">
                    <div class="product-price">{$priceHtml}</div>
                    <button class="btn-add-cart" data-id="{$p['id']}" title="{$btnTitle}" aria-label="{$btnTitle}" {$disabled}>
                        <i class="fas fa-shopping-bag"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    HTML;
}

// ── Cart ──────────────────────────────────────────────────────────────────────

function cartIdentifier(): array {
    if (isLoggedIn()) return ['type' => 'user', 'value' => (int)$_SESSION['user_id']];
    if (empty($_SESSION['cart_token'])) $_SESSION['cart_token'] = bin2hex(random_bytes(16));
    return ['type' => 'session', 'value' => $_SESSION['cart_token']];
}

function cartWhere(mysqli $conn): string {
    $id = cartIdentifier();
    if ($id['type'] === 'user') {
        return "c.user_id = " . (int)$id['value'];
    }
    $tok = $conn->real_escape_string($id['value']);
    return "c.session_id = '$tok' AND c.user_id IS NULL";
}

function getCartCount(mysqli $conn): int {
    $where  = cartWhere($conn);
    $result = $conn->query("SELECT COALESCE(SUM(quantity),0) AS n FROM cart c WHERE $where");
    return (int)$result->fetch_assoc()['n'];
}

function getCartItems(mysqli $conn): array {
    $where = cartWhere($conn);
    $sql   = "SELECT c.id, c.quantity,
                     p.id AS product_id, p.name, p.slug, p.price, p.sale_price,
                     p.image, p.stock
              FROM cart c
              JOIN products p ON c.product_id = p.id
              WHERE $where AND p.is_active = 1";
    $r = $conn->query($sql);
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

function getCartTotal(mysqli $conn): float {
    $total = 0.0;
    foreach (getCartItems($conn) as $item) {
        $price = $item['sale_price'] ?: $item['price'];
        $total += (float)$price * (int)$item['quantity'];
    }
    return $total;
}

function addToCart(mysqli $conn, int $product_id, int $qty = 1): bool {
    $r = $conn->query("SELECT stock, is_active FROM products WHERE id = $product_id");
    if (!$r || $r->num_rows === 0) return false;
    $p = $r->fetch_assoc();
    if (!$p['is_active']) return false;

    $id = cartIdentifier();
    if ($id['type'] === 'user') {
        $uid  = (int)$id['value'];
        $chk  = $conn->query("SELECT id, quantity FROM cart WHERE user_id = $uid AND product_id = $product_id");
        if ($chk->num_rows > 0) {
            $row    = $chk->fetch_assoc();
            $newQty = min((int)$row['quantity'] + $qty, (int)$p['stock']);
            $conn->query("UPDATE cart SET quantity = $newQty WHERE id = {$row['id']}");
        } else {
            $conn->query("INSERT INTO cart (user_id, product_id, quantity) VALUES ($uid, $product_id, $qty)");
        }
    } else {
        $tok = $conn->real_escape_string($id['value']);
        $chk = $conn->query("SELECT id, quantity FROM cart WHERE session_id = '$tok' AND product_id = $product_id AND user_id IS NULL");
        if ($chk->num_rows > 0) {
            $row    = $chk->fetch_assoc();
            $newQty = min((int)$row['quantity'] + $qty, (int)$p['stock']);
            $conn->query("UPDATE cart SET quantity = $newQty WHERE id = {$row['id']}");
        } else {
            $conn->query("INSERT INTO cart (session_id, product_id, quantity) VALUES ('$tok', $product_id, $qty)");
        }
    }
    return true;
}

function updateCartQty(mysqli $conn, int $cart_id, int $qty): void {
    if ($qty <= 0) { removeFromCart($conn, $cart_id); return; }
    $where = cartWhere($conn);
    $conn->query("UPDATE cart c SET quantity = $qty WHERE c.id = $cart_id AND $where");
}

function removeFromCart(mysqli $conn, int $cart_id): void {
    $where = cartWhere($conn);
    $conn->query("DELETE c FROM cart c WHERE c.id = $cart_id AND $where");
}

function clearCart(mysqli $conn): void {
    $where = cartWhere($conn);
    $conn->query("DELETE c FROM cart c WHERE $where");
}

function mergeGuestCart(mysqli $conn, int $user_id): void {
    if (empty($_SESSION['cart_token'])) return;
    $tok = $conn->real_escape_string($_SESSION['cart_token']);
    $items = $conn->query("SELECT product_id, quantity FROM cart WHERE session_id = '$tok' AND user_id IS NULL");
    if (!$items) return;
    while ($item = $items->fetch_assoc()) {
        $pid = (int)$item['product_id'];
        $qty = (int)$item['quantity'];
        $chk = $conn->query("SELECT id, quantity FROM cart WHERE user_id = $user_id AND product_id = $pid");
        if ($chk->num_rows > 0) {
            $row = $chk->fetch_assoc();
            $nq  = (int)$row['quantity'] + $qty;
            $conn->query("UPDATE cart SET quantity = $nq WHERE id = {$row['id']}");
        } else {
            $conn->query("INSERT INTO cart (user_id, product_id, quantity) VALUES ($user_id, $pid, $qty)");
        }
    }
    $conn->query("DELETE FROM cart WHERE session_id = '$tok' AND user_id IS NULL");
    unset($_SESSION['cart_token']);
}

// ── Queries ───────────────────────────────────────────────────────────────────

function getFeaturedProducts(mysqli $conn, int $limit = 8): array {
    $r = $conn->query("SELECT p.*, c.name AS category_name
                       FROM products p
                       LEFT JOIN categories c ON p.category_id = c.id
                       WHERE p.is_featured = 1 AND p.is_active = 1
                       ORDER BY p.created_at DESC LIMIT $limit");
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

function getNewArrivals(mysqli $conn, int $limit = 8): array {
    $r = $conn->query("SELECT p.*, c.name AS category_name
                       FROM products p
                       LEFT JOIN categories c ON p.category_id = c.id
                       WHERE p.is_active = 1
                       ORDER BY p.created_at DESC LIMIT $limit");
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

function getCategories(mysqli $conn): array {
    $r = $conn->query("SELECT c.*,
                              COUNT(p.id) AS product_count
                       FROM categories c
                       LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
                       WHERE c.is_active = 1
                       GROUP BY c.id
                       ORDER BY c.sort_order");
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

function getProductBySlug(mysqli $conn, string $slug): ?array {
    $s = $conn->real_escape_string($slug);
    $r = $conn->query("SELECT p.*, c.name AS category_name, c.slug AS category_slug
                       FROM products p
                       LEFT JOIN categories c ON p.category_id = c.id
                       WHERE p.slug = '$s' AND p.is_active = 1");
    if (!$r || $r->num_rows === 0) return null;
    return $r->fetch_assoc();
}

function getRelatedProducts(mysqli $conn, int $category_id, int $exclude_id, int $limit = 4): array {
    $r = $conn->query("SELECT p.*, c.name AS category_name
                       FROM products p
                       LEFT JOIN categories c ON p.category_id = c.id
                       WHERE p.category_id = $category_id
                         AND p.id != $exclude_id
                         AND p.is_active = 1
                       ORDER BY RAND() LIMIT $limit");
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

function getProductReviews(mysqli $conn, int $product_id): array {
    $r = $conn->query("SELECT rv.*, u.name AS user_name, u.avatar
                       FROM reviews rv
                       JOIN users u ON rv.user_id = u.id
                       WHERE rv.product_id = $product_id AND rv.is_approved = 1
                       ORDER BY rv.created_at DESC");
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

// ── Schema introspection (lets features degrade gracefully if migrations aren't run)
function hasColumn(mysqli $conn, string $table, string $col): bool {
    static $cache = [];
    $key = "$table.$col";
    if (isset($cache[$key])) return $cache[$key];
    $tEsc = $conn->real_escape_string($table);
    $cEsc = $conn->real_escape_string($col);
    $r = $conn->query("SHOW COLUMNS FROM `$tEsc` LIKE '$cEsc'");
    return $cache[$key] = ($r && $r->num_rows > 0);
}

// ── Review image uploads ───────────────────────────────────────────────────────
function saveReviewImages(array $files, int $maxFiles = 3): array {
    $saved = [];
    if (empty($files['name']) || !is_array($files['name'])) return $saved;

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $maxBytes = 5 * 1024 * 1024; // 5MB
    $dir = __DIR__ . '/../assets/img/reviews';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);

    $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;

    foreach ($files['name'] as $i => $name) {
        if (count($saved) >= $maxFiles) break;
        if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
        if ($files['size'][$i] > $maxBytes) continue;

        $tmp  = $files['tmp_name'][$i];
        $mime = $finfo ? finfo_file($finfo, $tmp) : mime_content_type($tmp);
        if (!isset($allowed[$mime])) continue;

        $ext = $allowed[$mime];
        $newName = 'r_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dest = $dir . '/' . $newName;
        if (move_uploaded_file($tmp, $dest)) {
            $saved[] = 'assets/img/reviews/' . $newName;
        }
    }
    if ($finfo) finfo_close($finfo);
    return $saved;
}
