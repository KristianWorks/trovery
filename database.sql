-- ============================================================
-- Trovery E-Commerce Database
-- "Local Roots. National Reach."
-- Import this into phpMyAdmin or run via MySQL CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS trovery_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE trovery_db;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================
-- USERS
-- =====================
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL,
    email       VARCHAR(150)  NOT NULL UNIQUE,
    password    VARCHAR(255)  NOT NULL,
    phone       VARCHAR(20)   DEFAULT NULL,
    address     TEXT          DEFAULT NULL,
    city        VARCHAR(100)  DEFAULT NULL,
    state       VARCHAR(100)  DEFAULT NULL,
    zip         VARCHAR(20)   DEFAULT NULL,
    role        ENUM('customer','admin') DEFAULT 'customer',
    avatar      VARCHAR(255)  DEFAULT NULL,
    is_active   TINYINT(1)    DEFAULT 1,
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================
-- CATEGORIES
-- =====================
CREATE TABLE IF NOT EXISTS categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL,
    slug        VARCHAR(100)  NOT NULL UNIQUE,
    description TEXT          DEFAULT NULL,
    image       VARCHAR(255)  DEFAULT NULL,
    icon        VARCHAR(80)   DEFAULT 'fas fa-tag',
    sort_order  INT           DEFAULT 0,
    is_active   TINYINT(1)    DEFAULT 1,
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);

-- =====================
-- PRODUCTS
-- =====================
CREATE TABLE IF NOT EXISTS products (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id         INT UNSIGNED  DEFAULT NULL,
    name                VARCHAR(200)  NOT NULL,
    slug                VARCHAR(200)  NOT NULL UNIQUE,
    description         TEXT          DEFAULT NULL,
    short_description   VARCHAR(500)  DEFAULT NULL,
    price               DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    sale_price          DECIMAL(10,2) DEFAULT NULL,
    stock               INT           DEFAULT 100,
    sku                 VARCHAR(100)  DEFAULT NULL UNIQUE,
    image               VARCHAR(255)  DEFAULT NULL,
    is_featured         TINYINT(1)    DEFAULT 0,
    is_active           TINYINT(1)    DEFAULT 1,
    rating              DECIMAL(3,2)  DEFAULT 4.50,
    review_count        INT           DEFAULT 0,
    created_at          TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- =====================
-- CART
-- =====================
CREATE TABLE IF NOT EXISTS cart (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED  DEFAULT NULL,
    session_id  VARCHAR(255)  DEFAULT NULL,
    product_id  INT UNSIGNED  NOT NULL,
    quantity    INT           DEFAULT 1,
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- =====================
-- ORDERS
-- =====================
CREATE TABLE IF NOT EXISTS orders (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED  DEFAULT NULL,
    order_number     VARCHAR(50)   NOT NULL UNIQUE,
    status           ENUM('pending','processing','shipped','delivered','cancelled','refunded') DEFAULT 'pending',
    subtotal         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    shipping_fee     DECIMAL(10,2) DEFAULT 0.00,
    tax              DECIMAL(10,2) DEFAULT 0.00,
    discount         DECIMAL(10,2) DEFAULT 0.00,
    total            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    customer_name    VARCHAR(200)  DEFAULT NULL,
    customer_email   VARCHAR(150)  DEFAULT NULL,
    customer_phone   VARCHAR(20)   DEFAULT NULL,
    shipping_address TEXT          DEFAULT NULL,
    shipping_city    VARCHAR(100)  DEFAULT NULL,
    shipping_state   VARCHAR(100)  DEFAULT NULL,
    shipping_zip     VARCHAR(20)   DEFAULT NULL,
    shipping_country VARCHAR(100)  DEFAULT 'Philippines',
    payment_method   VARCHAR(50)   DEFAULT 'cod',
    payment_status   ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
    notes            TEXT          DEFAULT NULL,
    tracking_number  VARCHAR(100)  DEFAULT NULL,
    created_at       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================
-- ORDER ITEMS
-- =====================
CREATE TABLE IF NOT EXISTS order_items (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id       INT UNSIGNED  NOT NULL,
    product_id     INT UNSIGNED  DEFAULT NULL,
    product_name   VARCHAR(200)  NOT NULL,
    product_image  VARCHAR(255)  DEFAULT NULL,
    price          DECIMAL(10,2) NOT NULL,
    quantity       INT           NOT NULL,
    subtotal       DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- =====================
-- REVIEWS
-- =====================
CREATE TABLE IF NOT EXISTS reviews (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id  INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    rating      TINYINT(1)   NOT NULL DEFAULT 5,
    title       VARCHAR(200) DEFAULT NULL,
    comment     TEXT         DEFAULT NULL,
    is_approved TINYINT(1)   DEFAULT 1,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_review (product_id, user_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE
);

-- =====================
-- WISHLIST
-- =====================
CREATE TABLE IF NOT EXISTS wishlist (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    product_id  INT UNSIGNED NOT NULL,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wishlist (user_id, product_id),
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- =====================
-- NEWSLETTER SUBSCRIBERS
-- =====================
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(150) NOT NULL UNIQUE,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

SET FOREIGN_KEY_CHECKS = 1;

-- =====================
-- SEED: CATEGORIES
-- =====================
INSERT IGNORE INTO categories (name, slug, description, icon, sort_order) VALUES
('Food & Delicacies',     'food-delicacies',     'Authentic local flavors and artisanal food products from across the archipelago', 'fas fa-utensils',    1),
('Handcrafted & Artisan', 'handcrafted-artisan', 'Unique handmade pieces crafted with love, skill, and generations of tradition',    'fas fa-hands',       2),
('Fashion & Clothing',    'fashion-clothing',    'Locally designed and made fashion celebrating Filipino culture and craftsmanship',  'fas fa-tshirt',      3),
('Beauty & Wellness',     'beauty-wellness',     'Natural and organic beauty products from local herbalists and small-batch makers',  'fas fa-spa',         4),
('Home & Living',         'home-living',         'Handcrafted home decor and functional living essentials made with local materials', 'fas fa-home',        5),
('Farm & Agriculture',    'farm-agriculture',    'Fresh from the farm — organic produce, heirloom grains, and raw ingredients',      'fas fa-seedling',    6);

-- =====================
-- SEED: PRODUCTS
-- =====================
INSERT IGNORE INTO products (category_id, name, slug, description, short_description, price, sale_price, stock, sku, is_featured, rating, review_count) VALUES

-- Food & Delicacies
(1, 'Wild Forest Honey',
    'wild-forest-honey',
    'Pure, raw honey sourced from wild forest beehives in the highlands of Bukidnon. Rich in natural enzymes, antioxidants, and local pollen. Zero additives, zero preservatives — just honey the way nature intended. Each jar captures a different season, so every batch has its own unique flavor profile. Great for teas, cooking, wound care, or eating straight from the jar.',
    'Pure raw honey from Bukidnon highland forest beehives. No additives, no preservatives.',
    450.00, 390.00, 85, 'TRV-FD-001', 1, 4.90, 128),

(1, 'Homemade Chili Garlic Sauce',
    'homemade-chili-garlic-sauce',
    'Handcrafted chili garlic sauce using locally grown siling labuyo and freshly harvested garlic from Batanes. Slow-fermented for 30 days for a deep, complex heat that builds and lingers. Not just a hot sauce — it is a flavor bomb. Works as a dipping sauce, a marinade, a stir-fry base, or spooned directly on anything you are eating.',
    'Slow-fermented chili garlic sauce made with local siling labuyo. 30-day ferment.',
    180.00, NULL, 200, 'TRV-FD-002', 1, 4.80, 94),

(1, 'Artisan Barako Coffee (Whole Bean)',
    'artisan-barako-coffee',
    'Single-origin Barako beans from Batangas, slow-roasted in small 2kg batches to coax out its signature bold, earthy character with notes of dark chocolate and leather. Unlike commercial blends, this coffee is grown by a single farm family who have been cultivating Barako for four generations. Available whole bean or coarsely ground. A cup of heritage in every sip.',
    'Single-origin Batangas Barako, small-batch roasted by a fourth-generation farm family.',
    320.00, NULL, 150, 'TRV-FD-003', 1, 4.95, 211),

(1, 'Muscovado Sugar (500g)',
    'muscovado-sugar',
    'Unrefined muscovado sugar with a rich, deep molasses flavor that white sugar simply cannot replicate. Sourced directly from small-scale sugar farmers in Negros Occidental. Perfect for baking, sweetening coffee, making sauces, and as a more nutritious everyday sweetener. Darker and moister than regular brown sugar.',
    'Rich, unrefined muscovado sugar sourced directly from Negros Occidental farmers.',
    150.00, 120.00, 300, 'TRV-FD-004', 0, 4.70, 67),

(1, 'Spiced Vinegar Trio',
    'spiced-vinegar-trio',
    'A curated trio of artisanal Filipino spiced vinegars — sukang paombong, sukang iloko, and fresh coconut vinegar — each infused with local herbs, garlic, and native peppers. Every bottle is small-batch and hand-labeled. Perfect as a kitchen essential, a gift set, or a tasting experience for vinegar enthusiasts.',
    'Three-bottle artisanal set: sukang paombong, sukang iloko, and coconut vinegar.',
    380.00, 320.00, 75, 'TRV-FD-005', 0, 4.85, 43),

(1, 'Local Cacao Tablea Set (20 pcs)',
    'local-cacao-tablea',
    'Authentic tablea made from 100% single-origin cacao from Davao. Stone-ground slowly over 4 hours to develop a silky texture, then formed into traditional tablets. Absolutely no sugar, no milk powder, no additives — just pure dark cacao. Makes the richest, most soul-warming tsokolate you will ever taste. Box of 20 tablets, enough for 10 generous cups.',
    'Pure stone-ground Davao cacao tablea. No additives. Box of 20 traditional tablets.',
    350.00, 299.00, 120, 'TRV-FD-006', 1, 4.90, 165),

-- Handcrafted & Artisan
(2, 'Handwoven Rattan Basket (Large)',
    'handwoven-rattan-basket',
    'Hand-woven rattan storage basket crafted by Palawan indigenous artisans using traditional techniques passed down through generations. Each piece takes 3–5 days to complete and is entirely unique — no two are exactly the same. Natural rattan color with a food-safe lacquer finish. Approximately 40cm diameter x 35cm height. Perfect for pantry storage, plant pots, laundry, or decorative display.',
    'Handwoven by Palawan indigenous artisans. Traditional techniques, unique every piece.',
    750.00, NULL, 40, 'TRV-HA-001', 1, 4.80, 55),

(2, 'Carved Hardwood Serving Board',
    'carved-hardwood-serving-board',
    'Hand-carved from reclaimed hardwood — narra or acacia depending on availability — each serving board features a unique grain pattern that is impossible to replicate. Food-safe natural oil finish, no varnish or chemicals. Gently scalloped edges, comfortable hand grip. Approximately 35cm x 22cm. Ideal for charcuterie boards, bread serving, cheese plates, or kitchen wall display.',
    'Hand-carved reclaimed narra or acacia. Food-safe oil finish. Every grain unique.',
    1200.00, 980.00, 25, 'TRV-HA-002', 1, 4.90, 72),

(2, 'Burnay Clay Pot Set (3 pcs)',
    'burnay-clay-pot-set',
    'Set of 3 traditional Burnay clay pots from Vigan, Ilocos Norte — small (0.5L), medium (1.5L), and large (3L). Kiln-fired using the same methods practiced since the Spanish colonial era. Unglazed interior is porous and ideal for slow cooking, fermenting, or storing liquids. The set is stackable and comes wrapped in traditional cloth. Fully food-safe.',
    'Set of 3 kiln-fired Vigan Burnay clay pots. Traditional Spanish-era technique.',
    1500.00, 1200.00, 20, 'TRV-HA-003', 1, 4.85, 29),

(2, 'Macramé Wall Hanging',
    'macrame-wall-hanging',
    'Hand-knotted macramé wall hanging made with natural unbleached cotton rope. Each piece requires 8–12 hours of meticulous knotting. Includes a polished driftwood hanger collected from Palawan beaches. Approximately 45cm wide x 70cm long with fringe. Adds warmth and a boho-natural touch to any room. Made by a cooperative of women artisans in Quezon Province.',
    'Hand-knotted natural cotton macramé by a Quezon Province women\'s cooperative.',
    890.00, NULL, 30, 'TRV-HA-004', 0, 4.75, 38),

-- Fashion & Clothing
(3, 'Hand-Dyed Indigo Tote Bag',
    'hand-dyed-indigo-tote-bag',
    'Heavy canvas tote bag hand-dyed using traditional indigo dyeing (shibori-inspired) techniques by a Cebu artisan. Each bag is dipped, folded, and bound differently, making every single piece a one-of-a-kind wearable art. Reinforced stitching at stress points, natural hemp rope handles, interior zip pocket. Holds up to 15kg. Gets better with age and washing.',
    'One-of-a-kind hand-dyed indigo canvas tote. Reinforced, 15kg capacity.',
    550.00, NULL, 60, 'TRV-FC-001', 1, 4.70, 88),

(3, 'Hablon Cotton Graphic Shirt',
    'hablon-cotton-graphic-shirt',
    'Lightweight short-sleeve shirt constructed from locally woven Hablon fabric — a traditional weave from Iloilo — combined with a modern graphic print celebrating Philippine landscapes. Incredibly breathable and soft, with a relaxed unisex cut. Available in S, M, L, XL. Wash cold, air dry. Ships within 3 days.',
    'Modern graphic print on locally woven Iloilo Hablon cotton. Breathable, unisex.',
    680.00, 580.00, 120, 'TRV-FC-002', 0, 4.60, 103),

(3, 'Modern Malong Wrap',
    'modern-malong-wrap',
    'The malong — the traditional tubular garment of Mindanao — reimagined with modern geometric patterns while preserving authentic handloom weaving. Each malong can be worn as a sarong, a strapless dress, a shoulder bag, a prayer wrap, or a blanket. One size fits most (up to 42" waist). Woven by Maranao weavers in Lanao del Sur.',
    'Authentic Mindanao handloom malong in modern geometric patterns. Multi-wear.',
    950.00, NULL, 45, 'TRV-FC-003', 0, 4.80, 51),

-- Beauty & Wellness
(4, 'Moringa Facial Soap Bar',
    'moringa-facial-soap',
    'Cold-process soap handcrafted with moringa leaf powder, virgin coconut oil, shea butter, and a blend of local essential oils (ylang-ylang and calamansi). Cured for 6 weeks for a hard, long-lasting bar. Sulfate-free, paraben-free, artificial fragrance-free. Gentle enough for daily use on face and body. One 100g bar lasts 6–8 weeks with proper drying.',
    'Cold-process moringa + VCO soap. Cured 6 weeks. Sulfate-free, paraben-free.',
    220.00, NULL, 200, 'TRV-BW-001', 1, 4.85, 176),

(4, 'Ginger Ylang-Ylang Hair Oil (150ml)',
    'ginger-ylang-hair-oil',
    'Cold-pressed virgin coconut oil as the base, infused for 2 weeks with fresh ginger root and ylang-ylang petals. Ginger stimulates the scalp and promotes hair growth. Ylang-ylang conditions and adds shine. No mineral oil, no silicones, no synthetic fragrance. Dark amber glass bottle to preserve potency. Suitable for all hair types.',
    'Cold-pressed VCO infused 2 weeks with fresh ginger root and ylang-ylang petals.',
    380.00, 320.00, 90, 'TRV-BW-002', 1, 4.75, 134),

(4, 'Activated Charcoal Clay Mask (75ml)',
    'activated-charcoal-clay-mask',
    'Deep-cleansing mask combining activated coconut charcoal, white kaolin clay, bentonite clay, tea tree essential oil, and aloe vera gel. Draws out excess oil, unclogs pores, and leaves skin feeling tight and refined. 75ml glass jar with spatula — approximately 20–25 full-face applications. Suitable for normal to oily skin.',
    'Activated coconut charcoal + kaolin clay mask with tea tree and aloe vera.',
    290.00, NULL, 150, 'TRV-BW-003', 0, 4.65, 98),

(4, 'Sampaguita Herbal Bath Soak (250g)',
    'sampaguita-herbal-bath-soak',
    'A deeply relaxing bath soak blending Himalayan pink salt, dried sampaguita flowers, lavender buds, pandan leaves, and nourishing argan oil. The sampaguita and lavender calm the nervous system while the salts draw out toxins and ease tired muscles. 250g resealable kraft bag — 5–7 full baths. Also works as a foot soak.',
    'Himalayan salt soak with sampaguita, lavender, and pandan. 250g, 5–7 baths.',
    250.00, NULL, 120, 'TRV-BW-004', 0, 4.80, 62),

-- Home & Living
(5, 'Hand-painted Ceramic Mug Set (4 pcs)',
    'handpainted-ceramic-mug-set',
    'Set of 4 handpainted stoneware mugs, each featuring a different local Philippine flora — Rafflesia, Sampaguita, Waling-waling, and Jade Vine. Painted by local ceramicists in Vigan using food-safe ceramic glazes. Each mug holds 300ml. Microwave and dishwasher safe. Comes nested in a kraft gift box with tissue.',
    '4 handpainted stoneware mugs with Philippine flora designs. 300ml, gift boxed.',
    1800.00, 1500.00, 35, 'TRV-HL-001', 1, 4.90, 89),

(5, 'Bayong Woven Desk Organizer',
    'bayong-woven-desk-organizer',
    'The classic bayong bag — a Filipino market staple — transformed into a modern desk and shelf organizer. Handwoven buri palm with natural plant dye (indigo or turmeric finish), leather-wrapped handles, and a cotton liner. 30cm wide x 20cm deep x 25cm tall. Perfect for books, magazines, office supplies, bathroom storage, or as a toy bin.',
    'Handwoven buri palm desk organizer with leather handles and cotton liner.',
    1100.00, 900.00, 30, 'TRV-HL-002', 1, 4.80, 66),

(5, 'Hand-loomed Table Runner (40x180cm)',
    'hand-loomed-table-runner',
    'Traditional hand-loomed table runner in indigenous Mindanao okir geometric patterns, woven with a cotton-silk blend that gives it a subtle sheen. 40cm wide, 180cm long — fits most standard dining tables. Adds an authentic Filipino cultural touch to dining tables, sideboards, or shelves. Machine washable on cold gentle cycle, hang dry.',
    'Cotton-silk blend table runner in Mindanao okir patterns. 40x180cm. Machine washable.',
    680.00, 580.00, 70, 'TRV-HL-003', 0, 4.75, 57),

(5, 'Bamboo Picture Frame Set (3 pcs)',
    'bamboo-picture-frame-set',
    'Set of 3 handcrafted frames in sizes 4x6, 5x7, and 8x10. Made from sustainably harvested bamboo poles that are hand-split, sanded, and joined without chemical adhesives. Each frame has a unique natural texture — bamboo nodes visible at corners. Includes glass insert, archival backing board, and freestanding back support. Lightweight yet durable.',
    'Sustainably harvested bamboo frames in 4x6, 5x7, 8x10. Glass, archival backing.',
    950.00, NULL, 55, 'TRV-HL-004', 0, 4.70, 44),

-- Farm & Agriculture
(6, 'Organic Turmeric Powder (100g)',
    'organic-turmeric-powder',
    'Fresh-harvested turmeric rhizomes from certified organic farms in Bukidnon, sun-dried for 10 days to lock in curcumin content, then stone-ground to a fine powder. No fillers, no anti-caking agents, no irradiation. Vibrant orange-yellow color and intensely aromatic. Batch-tested for purity. 100g in a resealable kraft pouch with oxygen absorber.',
    'Organic Bukidnon turmeric, sun-dried and stone-ground. Lab-tested. 100g.',
    180.00, NULL, 300, 'TRV-FA-001', 0, 4.85, 143),

(6, 'Heirloom Red Unoy Rice (2kg)',
    'heirloom-red-unoy-rice',
    'Premium Red Unoy heirloom rice from the Cordillera highlands, grown in ancient terraces carved into the mountains over 2,000 years ago. Cultivated without synthetic fertilizers or pesticides, harvested by hand, and dried on traditional bamboo racks. Nutty flavor, slightly chewy texture, deep brick-red color. Higher in fiber and minerals than commercial white rice. 2kg vacuum-sealed pack.',
    'Cordillera heirloom Red Unoy. Organic, ancient terraces, harvested by hand. 2kg.',
    420.00, NULL, 200, 'TRV-FA-002', 1, 4.95, 87),

(6, 'Dried Moringa Leaf Powder (50g)',
    'dried-moringa-leaf-powder',
    'Organic moringa (malunggay) leaves from Davao farms, sun-dried under shade nets to preserve nutrients, then finely milled. Rich in iron, calcium, Vitamin A and C, and complete amino acids. Add to smoothies, soups, pasta dough, bread, or brew as an herbal tea. 50g resealable foil bag with oxygen absorber. Shelf-stable for 18 months sealed.',
    'Shade-dried organic moringa leaf powder from Davao. 50g. Tea, smoothies, cooking.',
    120.00, 100.00, 500, 'TRV-FA-003', 0, 4.80, 219),

(6, 'Cordillera Arabica Coffee (Whole Bean)',
    'cordillera-arabica-coffee',
    'Washed Arabica beans from small-holder farms in Sagada and Benguet. Grown at 1,500+ meters above sea level, harvested by hand, and naturally processed. Light to medium roast with floral notes, bright citrus acidity, and a clean, lingering finish. 250g in a valve-sealed craft bag to preserve freshness post-roast.',
    'High-altitude Sagada/Benguet Arabica. Washed process. Floral, citrus, bright. 250g.',
    290.00, NULL, 100, 'TRV-FA-004', 1, 4.88, 97);
