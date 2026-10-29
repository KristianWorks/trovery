/* ════════════════════════════════════════════════════════════
   Trovery — Main JS
════════════════════════════════════════════════════════════ */

// ── Navbar Scroll ─────────────────────────────────────────────
const mainNav = document.getElementById('mainNav');
if (mainNav) {
    window.addEventListener('scroll', () => {
        mainNav.classList.toggle('scrolled', window.scrollY > 60);
    }, { passive: true });
}

// ── Search Toggle ─────────────────────────────────────────────
const searchToggle = document.getElementById('searchToggle');
const searchBar    = document.getElementById('searchBar');
const searchClose  = document.getElementById('searchClose');

if (searchToggle && searchBar) {
    searchToggle.addEventListener('click', () => {
        searchBar.classList.toggle('open');
        if (searchBar.classList.contains('open')) {
            searchBar.querySelector('input')?.focus();
        }
    });
}
if (searchClose && searchBar) {
    searchClose.addEventListener('click', () => searchBar.classList.remove('open'));
}
document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && searchBar) searchBar.classList.remove('open');
});

// ── Toast ─────────────────────────────────────────────────────
function showToast(message, type = 'default') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const icons = {
        default: 'fas fa-shopping-cart',
        success: 'fas fa-check-circle',
        error:   'fas fa-exclamation-circle',
        info:    'fas fa-info-circle',
    };
    const cls   = type !== 'default' ? `toast-${type}` : '';
    const toast = document.createElement('div');
    toast.className = `trovery-toast ${cls} mb-2`;
    toast.innerHTML = `<i class="${icons[type] || icons.default} toast-icon"></i>
                       <span class="toast-msg">${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity    = '0';
        toast.style.transform  = 'translateX(20px)';
        toast.style.transition = 'all .3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ── Add to Cart (AJAX) ────────────────────────────────────────
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-add-cart');
    if (!btn) return;

    const productId = btn.dataset.id;
    if (!productId) return;

    const origIcon = btn.innerHTML;
    btn.innerHTML  = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled   = true;

    const formData = new FormData();
    formData.append('action',     'add');
    formData.append('product_id', productId);
    formData.append('quantity',   1);

    fetch('ajax/cart.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                updateCartBadge(data.cart_count);
                btn.innerHTML = '<i class="fas fa-check"></i>';
                document.dispatchEvent(new CustomEvent('cart:added', { detail: data }));
                setTimeout(() => {
                    btn.innerHTML = origIcon;
                    btn.disabled  = false;
                }, 1500);
            } else {
                showToast(data.message || "Couldn't add that to your bag.", 'error');
                btn.innerHTML = origIcon;
                btn.disabled  = false;
            }
        })
        .catch(() => {
            showToast("Connection's spotty — give it another try?", 'error');
            btn.innerHTML = origIcon;
            btn.disabled  = false;
        });
});

function updateCartBadge(count) {
    let badge = document.querySelector('.cart-count');
    const cartBtn = document.querySelector('.cart-btn');
    if (!cartBtn) return;
    if (count > 0) {
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'cart-count';
            cartBtn.appendChild(badge);
        }
        badge.textContent = count;
    } else if (badge) {
        badge.remove();
    }
}

// ── Quantity Controls ─────────────────────────────────────────
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.qty-btn');
    if (!btn) return;
    const wrapper = btn.closest('.qty-control');
    if (!wrapper) return;
    const input = wrapper.querySelector('.qty-input');
    if (!input) return;

    const min = parseInt(input.min) || 1;
    const max = parseInt(input.max) || 9999;
    let val = parseInt(input.value) || 1;

    if (btn.dataset.action === 'minus') val = Math.max(min, val - 1);
    if (btn.dataset.action === 'plus')  val = Math.min(max, val + 1);
    input.value = val;

    // Fire change for cart update forms
    input.dispatchEvent(new Event('change', { bubbles: true }));
});

// ── Cart Page: Qty Change → AJAX Update ──────────────────────
document.addEventListener('change', function (e) {
    const input = e.target.closest('.cart-qty-input');
    if (!input) return;

    const cartId = input.dataset.cartId;
    const qty    = parseInt(input.value) || 0;
    if (!cartId) return;

    const formData = new FormData();
    formData.append('action',   qty <= 0 ? 'remove' : 'update');
    formData.append('cart_id',  cartId);
    formData.append('quantity', qty);

    fetch('ajax/cart.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                updateCartBadge(data.cart_count);
                location.reload();
            } else {
                showToast("Couldn't update your bag.", 'error');
            }
        })
        .catch(() => showToast("Connection's spotty — give it another try?", 'error'));
});

// ── Newsletter Form ───────────────────────────────────────────
window.handleNewsletter = function (e) {
    e.preventDefault();
    const input = e.target.querySelector('input[type="email"]');
    const email = input?.value?.trim();
    if (!email) return;

    const formData = new FormData();
    formData.append('email', email);

    fetch('ajax/newsletter.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            showToast(data.message || "You're subscribed!", data.success ? 'success' : 'error');
            if (data.success && input) input.value = '';
        })
        .catch(() => showToast('Could not subscribe. Try again.', 'error'));
};

// ── Payment Option Selection ──────────────────────────────────
document.querySelectorAll('.payment-option').forEach(opt => {
    opt.addEventListener('click', function () {
        document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('checked'));
        this.classList.add('checked');
        const radio = this.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
    });
});

// ── Star Rating Input ─────────────────────────────────────────
document.querySelectorAll('.star-rating-input label').forEach(label => {
    label.addEventListener('mouseover', function () {
        const stars = this.closest('.star-rating-input').querySelectorAll('label');
        let found = false;
        stars.forEach(s => {
            s.style.color = found ? '' : '#f5b82e';
            if (s === this) found = true;
        });
    });
    label.addEventListener('mouseout', function () {
        this.closest('.star-rating-input').querySelectorAll('label').forEach(s => s.style.color = '');
    });
});

// ── Image Gallery (Product Detail) ───────────────────────────
document.querySelectorAll('.thumb-img, .pdp-thumb').forEach(thumb => {
    thumb.addEventListener('click', function () {
        const main = document.querySelector('.main-product-img');
        const src  = this.dataset.src || this.querySelector('img')?.src || this.src;
        if (main && src) main.src = src;
        document.querySelectorAll('.thumb-img, .pdp-thumb').forEach(t => t.classList.remove('active-thumb'));
        this.classList.add('active-thumb');
    });
});

// ── PDP Zoom-on-hover (mouse position → transform-origin) ────
document.querySelectorAll('.pdp-main-img').forEach(el => {
    el.addEventListener('mousemove', e => {
        const r = el.getBoundingClientRect();
        const x = ((e.clientX - r.left) / r.width)  * 100;
        const y = ((e.clientY - r.top)  / r.height) * 100;
        el.style.setProperty('--zx', x + '%');
        el.style.setProperty('--zy', y + '%');
    });
});

// ── PDP Variant pills ────────────────────────────────────────
document.querySelectorAll('.pdp-pills').forEach(group => {
    group.addEventListener('click', e => {
        const pill = e.target.closest('.pdp-pill');
        if (!pill) return;
        group.querySelectorAll('.pdp-pill').forEach(p => p.classList.remove('active'));
        pill.classList.add('active');
        const picked = group.closest('.pdp-variant')?.querySelector('.picked');
        if (picked) picked.textContent = pill.dataset.label || pill.textContent.trim();
        const hidden = group.closest('.pdp-variant')?.querySelector('input[type="hidden"]');
        if (hidden) hidden.value = pill.dataset.value || pill.textContent.trim();
    });
});

// ── PDP Related-products scroll-snap carousel buttons ───────
document.querySelectorAll('[data-rel-control]').forEach(btn => {
    btn.addEventListener('click', () => {
        const track = document.querySelector(btn.dataset.relTarget || '.pdp-rel-track');
        if (!track) return;
        const card = track.firstElementChild;
        if (!card) return;
        const step = card.getBoundingClientRect().width + 20;
        track.scrollBy({
            left: btn.dataset.relControl === 'prev' ? -step : step,
            behavior: 'smooth'
        });
    });
});

// ── Image skeleton: mark wrapper .is-loaded when image arrives ──
function markImagesLoaded(root = document) {
    root.querySelectorAll('.product-img-wrapper img').forEach(img => {
        const wrap = img.parentElement;
        if (!wrap) return;
        if (img.complete && img.naturalWidth > 0) {
            wrap.classList.add('is-loaded');
        } else {
            const done = () => wrap.classList.add('is-loaded');
            img.addEventListener('load',  done, { once: true });
            img.addEventListener('error', done, { once: true });
        }
    });
}
markImagesLoaded();

// ── Scroll-Reveal (simple fade-in) ───────────────────────────
const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity    = '1';
            entry.target.style.transform  = 'translateY(0)';
            revealObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.1 });

document.querySelectorAll('.reveal').forEach(el => {
    el.style.opacity   = '0';
    el.style.transform = 'translateY(28px)';
    el.style.transition = 'opacity .55s ease, transform .55s ease';
    revealObserver.observe(el);
});

// ── Auto-dismiss Alerts ───────────────────────────────────────
document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => {
        const bsAlert = bootstrap?.Alert?.getOrCreateInstance(alert);
        bsAlert?.close();
    }, 5000);
});

// ── Mobile Filter Toggle ──────────────────────────────────────
const filterToggle = document.getElementById('filterToggle');
const filterPanel  = document.getElementById('filterPanel');
if (filterToggle && filterPanel) {
    filterToggle.addEventListener('click', () => {
        filterPanel.classList.toggle('show');
        filterToggle.textContent = filterPanel.classList.contains('show')
            ? '✕ Hide Filters'
            : '⚙ Show Filters';
    });
}

// ════════════════════════════════════════════════════════════
//   CART DRAWER
// ════════════════════════════════════════════════════════════
const cartDrawer    = document.getElementById('cartDrawer');
const cartBackdrop  = document.querySelector('.cart-backdrop');
const cartDrawerInner = document.getElementById('cartDrawerInner');

function openCartDrawer() {
    if (!cartDrawer || !cartBackdrop) return;
    cartDrawer.classList.add('open');
    cartBackdrop.classList.add('open');
    cartDrawer.setAttribute('aria-hidden', 'false');
    document.body.classList.add('no-scroll');
}
function closeCartDrawer() {
    if (!cartDrawer || !cartBackdrop) return;
    cartDrawer.classList.remove('open');
    cartBackdrop.classList.remove('open');
    cartDrawer.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('no-scroll');
}
function refreshCartDrawer(autoOpen = false) {
    if (!cartDrawerInner) return Promise.resolve();

    // Skeleton during fetch so the drawer never looks frozen
    const skel = `
        <div class="cart-drawer-head">
            <h3>Your Bag</h3>
            <button type="button" class="cart-drawer-close" data-drawer-close aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <div class="cart-drawer-body">
            ${'<div class="drawer-skeleton-line"><div class="ph-img"></div><div class="ph-text"><div class="ph-line w90"></div><div class="ph-line w40"></div><div class="ph-line w70"></div></div></div>'.repeat(2)}
        </div>`;
    cartDrawerInner.innerHTML = skel;

    return fetch('ajax/cart-drawer.php', { credentials: 'same-origin' })
        .then(r => r.text())
        .then(html => {
            cartDrawerInner.innerHTML = html;
            markImagesLoaded(cartDrawerInner);
            if (autoOpen) openCartDrawer();
        })
        .catch(() => { /* silent */ });
}

// Open on cart-btn click (intercept the cart.php link)
document.addEventListener('click', function (e) {
    const trigger = e.target.closest('.cart-btn');
    if (trigger) {
        e.preventDefault();
        openCartDrawer();
        return;
    }
    const closer = e.target.closest('[data-drawer-close]');
    if (closer) {
        e.preventDefault();
        closeCartDrawer();
    }
});

// ESC closes drawer
document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && cartDrawer?.classList.contains('open')) {
        closeCartDrawer();
    }
});

// Drawer qty input change → AJAX update + refresh body
document.addEventListener('change', function (e) {
    const input = e.target.closest('.drawer-qty-input');
    if (!input) return;
    const cartId = input.dataset.cartId;
    const qty    = parseInt(input.value, 10) || 0;
    if (!cartId) return;

    const fd = new FormData();
    fd.append('action',   qty <= 0 ? 'remove' : 'update');
    fd.append('cart_id',  cartId);
    fd.append('quantity', qty);

    fetch('ajax/cart.php', { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            if (!data.success) { showToast("Couldn't update your bag.", 'error'); return; }
            updateCartBadge(data.cart_count);
            return refreshCartDrawer(false);
        })
        .catch(() => showToast('Network error.', 'error'));
});

// Drawer remove button
document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-remove]');
    if (!btn) return;
    const cartId = btn.dataset.remove;
    if (!cartId) return;

    const fd = new FormData();
    fd.append('action',  'remove');
    fd.append('cart_id', cartId);

    fetch('ajax/cart.php', { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            if (!data.success) { showToast('Could not remove item.', 'error'); return; }
            updateCartBadge(data.cart_count);
            return refreshCartDrawer(false);
        })
        .catch(() => showToast('Network error.', 'error'));
});

// Hook into the existing add-to-cart success → refresh + auto-open drawer
document.addEventListener('cart:added', function () {
    refreshCartDrawer(true);
});

// PDP form: intercept submit → AJAX add-to-cart → open drawer
const pdpForm = document.getElementById('pdp-form');
if (pdpForm) {
    pdpForm.addEventListener('submit', function (e) {
        // Only intercept the add-to-cart submission, not Buy Now (which is a link)
        const submitter = e.submitter;
        if (!submitter || submitter.name !== 'add_to_cart') return;

        e.preventDefault();
        const pid = pdpForm.dataset.productId;
        const qty = pdpForm.querySelector('[name="quantity"]')?.value || 1;
        if (!pid) return;

        submitter.disabled = true;
        const origText = submitter.innerHTML;
        submitter.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        const fd = new FormData();
        fd.append('action',     'add');
        fd.append('product_id', pid);
        fd.append('quantity',   qty);

        fetch('ajax/cart.php', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                submitter.innerHTML = origText;
                submitter.disabled  = false;
                if (data.success) {
                    updateCartBadge(data.cart_count);
                    document.dispatchEvent(new CustomEvent('cart:added', { detail: data }));
                } else {
                    showToast(data.message || 'Could not add to cart.', 'error');
                }
            })
            .catch(() => {
                submitter.innerHTML = origText;
                submitter.disabled  = false;
                // Fallback: let the form do a regular POST
                pdpForm.submit();
            });
    });
}

// ════════════════════════════════════════════════════════════
//   RECENTLY VIEWED (PDP only, localStorage)
// ════════════════════════════════════════════════════════════
(function () {
    const cur = window.__troveryProduct;
    const KEY = 'trovery:recently_viewed';
    const MAX = 8;

    let list = [];
    try { list = JSON.parse(localStorage.getItem(KEY) || '[]'); } catch (e) { list = []; }
    if (!Array.isArray(list)) list = [];

    // If on a PDP, prepend the current product (deduped)
    if (cur && cur.id) {
        list = list.filter(p => p && p.id !== cur.id);
        list.unshift(cur);
        list = list.slice(0, MAX);
        localStorage.setItem(KEY, JSON.stringify(list));
    }

    const section = document.getElementById('recentlyViewedSection');
    const track   = document.getElementById('recentlyViewedTrack');
    if (!section || !track) return;

    // Show everything except the current product
    const others = list.filter(p => p && (!cur || p.id !== cur.id));
    if (others.length === 0) return;

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => (
        { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]
    ));

    track.innerHTML = others.map(p => `
        <a href="product.php?slug=${encodeURIComponent(p.slug)}" class="rv-card">
            <div class="rv-img" style="background-image:url('${esc(p.image)}')"></div>
            <div class="rv-name">${esc(p.name)}</div>
            <div class="rv-price">${esc(p.price)}</div>
        </a>
    `).join('');
    section.hidden = false;

    document.getElementById('rvClear')?.addEventListener('click', () => {
        // Keep only the current product so the strip empties cleanly
        localStorage.setItem(KEY, cur ? JSON.stringify([cur]) : '[]');
        section.hidden = true;
    });
})();

// ════════════════════════════════════════════════════════════
//   LIGHTBOX (review photos, generic)
// ════════════════════════════════════════════════════════════
(function () {
    const box = document.getElementById('lightbox');
    if (!box) return;
    const img = document.getElementById('lightboxImg');
    const closeBtn = box.querySelector('.lightbox-close');

    function open(src) {
        if (!src || !img) return;
        img.src = src;
        box.classList.add('open');
        box.setAttribute('aria-hidden', 'false');
        document.body.classList.add('no-scroll');
    }
    function close() {
        box.classList.remove('open');
        box.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('no-scroll');
    }

    document.addEventListener('click', e => {
        const trigger = e.target.closest('[data-lightbox]');
        if (trigger) { open(trigger.dataset.lightbox); return; }
        if (e.target === box) close();
    });
    closeBtn?.addEventListener('click', close);
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && box.classList.contains('open')) close();
    });
})();

// ════════════════════════════════════════════════════════════
//   REVIEW PHOTO PICKER (live preview)
// ════════════════════════════════════════════════════════════
(function () {
    const picker = document.querySelector('.review-photo-pick input[type="file"]');
    const preview = document.getElementById('reviewPhotoPreview');
    if (!picker || !preview) return;

    picker.addEventListener('change', () => {
        preview.innerHTML = '';
        const files = Array.from(picker.files || []).slice(0, 3);
        files.forEach(f => {
            if (!f.type.startsWith('image/')) return;
            const url = URL.createObjectURL(f);
            const el = document.createElement('img');
            el.src = url;
            el.onload = () => URL.revokeObjectURL(url);
            preview.appendChild(el);
        });
    });
})();

// ════════════════════════════════════════════════════════════
//   CHECKOUT V2 — 3-step flow
// ════════════════════════════════════════════════════════════
(function () {
    const root = document.getElementById('checkoutFlow');
    if (!root) return;

    const panels = root.querySelectorAll('.checkout-panel');
    const nodes  = root.querySelectorAll('.step-node');
    const bars   = root.querySelectorAll('.step-bar');
    let current  = 1;

    function goToStep(n) {
        n = Math.max(1, Math.min(panels.length, n));

        // Validate when moving forward
        if (n > current) {
            const activePanel = root.querySelector(`.checkout-panel[data-step="${current}"]`);
            if (!validatePanel(activePanel)) return;
        }

        // If moving to review step, populate the summary
        if (n === 3) populateReview();

        current = n;
        panels.forEach(p => p.classList.toggle('active', +p.dataset.step === n));
        nodes.forEach(node => {
            const idx = +node.dataset.step;
            node.classList.toggle('active', idx === n);
            node.classList.toggle('done',   idx <  n);
        });
        bars.forEach((bar, i) => bar.classList.toggle('done', i + 1 < n));

        // Scroll to top of panels (nice on mobile)
        root.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function validatePanel(panel) {
        if (!panel) return true;
        let ok = true;
        panel.querySelectorAll('[required]').forEach(el => {
            el.classList.remove('is-invalid');
            if (!el.value.trim() || (el.type === 'email' && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(el.value))) {
                el.classList.add('is-invalid');
                if (ok) el.focus();
                ok = false;
            }
        });
        if (!ok) showToast('Please complete the required fields.', 'error');
        return ok;
    }

    function populateReview() {
        const get = name => root.querySelector(`[name="${name}"]`)?.value?.trim() || '';
        const payRadio = root.querySelector('input[name="payment"]:checked');
        const pay = payRadio ? payRadio.closest('.pay-card')?.querySelector('strong')?.textContent : '';

        const ship = root.querySelector('#review-shipping');
        const cont = root.querySelector('#review-contact');
        const payEl = root.querySelector('#review-payment');
        if (cont) cont.textContent = `${get('first_name')} ${get('last_name')} · ${get('email')} · ${get('phone')}`.trim();
        if (ship) {
            const parts = [get('address'), get('city'), get('state'), get('zip')].filter(Boolean);
            ship.textContent = parts.join(', ');
        }
        if (payEl) payEl.textContent = pay || 'Cash on Delivery';
    }

    root.addEventListener('click', e => {
        const next = e.target.closest('[data-step-next]');
        const back = e.target.closest('[data-step-back]');
        const edit = e.target.closest('[data-step-goto]');
        if (next) { e.preventDefault(); goToStep(current + 1); }
        if (back) { e.preventDefault(); goToStep(current - 1); }
        if (edit) { e.preventDefault(); goToStep(+edit.dataset.stepGoto); }

        // Payment card click to check the radio
        const card = e.target.closest('.pay-card');
        if (card) {
            root.querySelectorAll('.pay-card').forEach(c => c.classList.remove('checked'));
            card.classList.add('checked');
            const radio = card.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        }
    });

    // Clear invalid state on input
    root.addEventListener('input', e => {
        if (e.target.classList?.contains('is-invalid')) e.target.classList.remove('is-invalid');
    });

    // Initialize: re-check first pay card as checked if any
    const checked = root.querySelector('input[name="payment"]:checked');
    if (checked) checked.closest('.pay-card')?.classList.add('checked');
})();
