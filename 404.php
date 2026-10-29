<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
define('PAGE_TITLE', 'Page Not Found');
$base_url = '';
http_response_code(404);
?>
<?php require_once 'includes/header.php'; ?>

<section class="notfound-wrap">
    <div class="container">
        <div class="notfound-card">
            <div class="notfound-code">404</div>
            <h1 class="notfound-title">This page wandered off.</h1>
            <p class="notfound-msg">
                Maybe the link is older than the catalog, or maybe a piece sold out and went home with someone.
                Either way — here are a few better places to be.
            </p>
            <div class="notfound-actions">
                <a href="index.php" class="pdp-btn-add" style="text-decoration:none;height:48px;padding:0 30px;font-size:.82rem">
                    Back to Home
                </a>
                <a href="shop.php" class="pdp-btn-buy" style="height:48px;padding:0 30px;font-size:.82rem;margin:0">
                    Browse the Shop
                </a>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
