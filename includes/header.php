<?php
if (!defined('PAGE_TITLE')) define('PAGE_TITLE', SITE_NAME);
$cartCount = getCartCount($conn);
$categories = getCategories($conn);
$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(PAGE_TITLE) ?> — <?= SITE_NAME ?></title>
    <meta name="description" content="<?= SITE_TAGLINE ?> — Discover the finest local products from across the Philippines.">

    <!-- Self-hosted fonts: preloaded so they're cached before first paint (no FOUT) -->
    <link rel="preload" as="font" type="font/woff2"
          href="<?= $base_url ?? '' ?>assets/fonts/fraunces.woff2" crossorigin>
    <link rel="preload" as="font" type="font/woff2"
          href="<?= $base_url ?? '' ?>assets/fonts/manrope.woff2" crossorigin>

    <!-- Bootstrap 5 (render-blocking — needed for above-the-fold layout) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome — loaded async (icons appear after first paint, no layout shift) -->
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"></noscript>

    <?php if (($body_class ?? '') === 'home'): ?>
    <link rel="preload" as="image" href="<?= $base_url ?? '' ?>assets/img/hero.avif" fetchpriority="high">
    <?php endif; ?>
    <!-- Custom CSS -->
    <link href="<?= $base_url ?? '' ?>assets/css/style.css" rel="stylesheet">
</head>
<body class="<?= htmlspecialchars($body_class ?? '') ?>">

<!-- ═══ NAV ═══════════════════════════════════════════════════════════════════ -->
<nav class="navbar navbar-expand-lg" id="mainNav">
    <div class="container">

        <!-- Brand -->
        <a class="navbar-brand" href="<?= $base_url ?? '' ?>index.php">
            <span class="brand-dot">T</span><span class="brand-text">rovery</span>
        </a>

        <!-- Toggler -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarMain" aria-label="Toggle navigation">
            <span class="toggler-icon"><i class="fas fa-bars"></i></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">

            <!-- Center Links -->
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base_url ?? '' ?>index.php">Home</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="<?= $base_url ?? '' ?>shop.php"
                       role="button" data-bs-toggle="dropdown">Shop</a>
                    <ul class="dropdown-menu mega-menu">
                        <li>
                            <a class="dropdown-item" href="<?= $base_url ?? '' ?>shop.php">
                                <i class="fas fa-th me-2 text-accent"></i> All Products
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <?php foreach ($categories as $cat): ?>
                        <li>
                            <a class="dropdown-item" href="<?= $base_url ?? '' ?>shop.php?category=<?= urlencode($cat['slug']) ?>">
                                <i class="<?= $cat['icon'] ?> me-2 text-accent"></i>
                                <?= htmlspecialchars($cat['name']) ?>
                                <span class="badge bg-light text-muted ms-auto"><?= $cat['product_count'] ?></span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base_url ?? '' ?>shop.php?sort=rating">Best Sellers</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base_url ?? '' ?>shop.php?sale=1">On Sale</a>
                </li>
            </ul>

            <!-- Right Actions -->
            <div class="nav-actions d-flex align-items-center gap-2">

                <!-- Search Toggle -->
                <button class="nav-icon-btn" id="searchToggle" title="Search">
                    <i class="fas fa-search"></i>
                </button>

                <!-- Cart -->
                <a href="<?= $base_url ?? '' ?>cart.php" class="nav-icon-btn cart-btn" title="Cart">
                    <i class="fas fa-shopping-bag"></i>
                    <?php if ($cartCount > 0): ?>
                    <span class="cart-count"><?= $cartCount ?></span>
                    <?php endif; ?>
                </a>

                <!-- Account -->
                <?php if (isLoggedIn()): ?>
                <div class="dropdown">
                    <button class="nav-icon-btn" data-bs-toggle="dropdown" title="Account">
                        <i class="fas fa-user-circle"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end account-menu">
                        <li class="px-3 py-2 border-bottom">
                            <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Account') ?></strong>
                            <div class="small text-muted"><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></div>
                        </li>
                        <li><a class="dropdown-item" href="<?= $base_url ?? '' ?>account.php"><i class="fas fa-user me-2"></i>My Account</a></li>
                        <li><a class="dropdown-item" href="<?= $base_url ?? '' ?>account.php?tab=orders"><i class="fas fa-box me-2"></i>My Orders</a></li>
                        <?php if (isAdmin()): ?>
                        <li><a class="dropdown-item" href="<?= $base_url ?? '' ?>admin/index.php"><i class="fas fa-cog me-2"></i>Admin Panel</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= $base_url ?? '' ?>logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
                <?php else: ?>
                <a href="<?= $base_url ?? '' ?>login.php" class="btn btn-primary btn-sm btn-nav-login">
                    Sign In
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Search Bar Dropdown -->
<div class="search-bar-wrapper" id="searchBar">
    <div class="container py-3">
        <form action="<?= $base_url ?? '' ?>shop.php" method="GET" class="search-form">
            <div class="input-group">
                <input type="text" name="q" class="form-control form-control-lg"
                       placeholder="Search for products, categories, sellers…"
                       value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" autofocus>
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-search"></i> Search
                </button>
                <button type="button" class="btn btn-outline-secondary" id="searchClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Alert Banner -->
<?php if ($alert): ?>
<div class="container mt-3">
    <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($alert['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>

<!-- Toast Container -->
<div id="toastContainer" class="position-fixed bottom-0 end-0 p-3" style="z-index:9999"></div>
