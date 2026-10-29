<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
define('PAGE_TITLE', 'Home');

$featuredProducts = getFeaturedProducts($conn, 8);
$categories       = array_slice(getCategories($conn), 0, 6);
$base_url         = '';
$body_class       = 'home';
?>
<?php require_once 'includes/header.php'; ?>

<!-- ═══ 2 · HERO ═════════════════════════════════════════════════════════════ -->
<section class="hero-v2">
    <div class="container">
        <div class="hero-v2-content">
            <span class="hero-v2-eyebrow">Filipino-made · Province-stamped</span>
            <h1 class="hero-v2-title">
                Made somewhere.<br>
                Made by <em>someone</em>.
            </h1>
            <p class="hero-v2-sub">
                Trovery puts the maker's province on the label — and a fair share of
                every peso back in their hands. Inabel from Ilocos. Narra from Pampanga.
                Capiz from Cebu. All of it named.
            </p>
            <a href="shop.php" class="hero-v2-cta">
                Shop the Collection
                <span class="arrow">→</span>
            </a>
        </div>
    </div>
    <div class="hero-v2-scroll">Scroll</div>
</section>

<!-- ═══ 3 · SHOP BY CATEGORY ═════════════════════════════════════════════════ -->
<section class="section-pad">
    <div class="container">
        <div class="text-center reveal" style="margin-bottom:56px">
            <span class="eyebrow-line">Browse by Category</span>
            <h2 class="section-title mt-2" style="font-size:clamp(2rem,4vw,3rem);letter-spacing:-1px">
                Six crafts. Many provinces.
            </h2>
        </div>

        <div class="cat-grid-v2">
            <?php foreach ($categories as $i => $cat):
                $slug = htmlspecialchars($cat['slug']);
                // Try .avif first, fall back to .jpg if not present
                $bgPath = file_exists(__DIR__ . "/assets/img/cat-{$slug}.avif")
                    ? "assets/img/cat-{$slug}.avif"
                    : "assets/img/cat-{$slug}.jpg";
            ?>
            <a href="shop.php?category=<?= urlencode($cat['slug']) ?>"
               class="cat-tile reveal"
               style="background-image: linear-gradient(180deg, transparent 30%, rgba(28,22,20,.85) 100%), url('<?= $bgPath ?>');">
                <div class="cat-tile-fallback">
                    <i class="<?= htmlspecialchars($cat['icon'] ?? 'fas fa-leaf') ?>"></i>
                </div>
                <div class="cat-tile-label">
                    <div class="cat-name-lg"><?= htmlspecialchars($cat['name']) ?></div>
                    <div class="cat-count-sm"><?= (int)$cat['product_count'] ?> Pieces</div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══ 4 · FEATURED PRODUCTS ════════════════════════════════════════════════ -->
<section class="section-pad" style="background:var(--brand-cream)">
    <div class="container">
        <div class="section-header-editorial reveal">
            <div>
                <span class="eyebrow-line">This Season</span>
                <h2>Featured pieces from named makers.</h2>
            </div>
            <a href="shop.php?featured=1" class="section-link">
                View the full collection →
            </a>
        </div>
        <div class="row">
            <?php foreach ($featuredProducts as $p): ?>
                <?= productCard($p, $base_url) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══ 5 · OUR STORY ════════════════════════════════════════════════════════ -->
<section class="section-pad our-story">
    <div class="container">
        <div class="row g-5 align-items-center">

            <div class="col-lg-5 reveal">
                <div class="story-image"></div>
            </div>

            <div class="col-lg-6 offset-lg-1 reveal story-text">
                <span class="eyebrow-line">Our Story</span>
                <h2>
                    We started by asking <em style="font-style:italic;font-family:var(--font-head);color:var(--brand-gold)">who</em>,
                    not what.
                </h2>
                <p>
                    Trovery began as a search — not for products, but for the people behind them.
                    The Ilocana weaver, the Pampangueño woodcarver, the Cebuano leather-smith whose
                    work fills Manila boutiques without ever bearing their name.
                </p>
                <p>
                    We built a marketplace that puts the maker's province on the label and a fair
                    share of the price in their hands. Every item is a small act of reclaiming —
                    Filipino craft, sold honestly, shipped with the dignity it was made with.
                </p>
                <div class="story-signature">
                    — The Trovery team, Manila
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ═══ 6 · TESTIMONIAL CAROUSEL ═════════════════════════════════════════════ -->
<section class="t-carousel section-pad-lg">
    <div class="container position-relative" style="z-index:2">
        <div id="testimonialCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="7000">

            <div class="carousel-inner">
                <?php
                $testimonials = [
                    ['name' => 'Maria Santos',       'loc' => 'Quezon City',  'text' => 'I ordered the Barako coffee and the wild forest honey. The honey especially — I have never tasted anything like it. Genuinely can\'t go back to supermarket honey after this.'],
                    ['name' => 'Javier Reyes',      'loc' => 'Cebu City',     'text' => 'The handwoven rattan basket is absolutely stunning. I got it as a gift and the recipient cried. You can feel the craftsmanship in every weave.'],
                    ['name' => 'Andrea Cruz',        'loc' => 'Davao City',   'text' => 'Trovery is what local e-commerce should look like. Ordered three times already. I love that I know exactly where my money is going.'],
                    ['name' => 'Liza Ocampo',        'loc' => 'Baguio City',  'text' => 'As someone from the Cordillera, seeing our Red Unoy rice on this platform makes me proud. The quality is authentic and the story is told with such respect.'],
                ];
                foreach ($testimonials as $i => $t):
                ?>
                <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                    <p class="t-quote">"<?= htmlspecialchars($t['text']) ?>"</p>
                    <div class="t-attrib">
                        <strong><?= htmlspecialchars($t['name']) ?></strong>
                        <?= htmlspecialchars($t['loc']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="carousel-indicators">
                <?php foreach ($testimonials as $i => $_): ?>
                <button type="button" data-bs-target="#testimonialCarousel"
                        data-bs-slide-to="<?= $i ?>"
                        <?= $i === 0 ? 'class="active" aria-current="true"' : '' ?>
                        aria-label="Story <?= $i + 1 ?>"></button>
                <?php endforeach; ?>
            </div>

            <button class="carousel-control-prev" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </div>
</section>

<!-- ═══ 7 · NEWSLETTER ═══════════════════════════════════════════════════════ -->
<section class="newsletter-v2">
    <div class="container">
        <div class="reveal">
            <span class="eyebrow-line" style="justify-content:center">Stay Close</span>
            <h2>Stories from the provinces, in your inbox.</h2>
            <p>New arrivals, the maker behind each piece, and the occasional invitation to early access.</p>
            <form class="nl-form" onsubmit="handleNewsletter(event)">
                <input type="email" placeholder="your@email.com" required>
                <button type="submit">Subscribe →</button>
            </form>
            <small>No spam. Unsubscribe anytime.</small>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
