<?php
require_once __DIR__ . '/includes/functions.php';
$pdo = db();

$slug = trim($_GET['slug'] ?? '');
$id = (int)($_GET['id'] ?? 0);
if ($slug !== '') {
    $stmt = $pdo->prepare("SELECT p.*, c.name AS cat_name, c.slug AS cat_slug, b.name AS brand_name FROM products p LEFT JOIN categories c ON c.id = p.category_id LEFT JOIN brands b ON b.id = p.brand_id WHERE p.slug = ?");
    $stmt->execute([$slug]);
} else {
    $stmt = $pdo->prepare("SELECT p.*, c.name AS cat_name, c.slug AS cat_slug, b.name AS brand_name FROM products p LEFT JOIN categories c ON c.id = p.category_id LEFT JOIN brands b ON b.id = p.brand_id WHERE p.id = ?");
    $stmt->execute([$id ?: 1]);
}
$p = $stmt->fetch();
if (!$p) { header('Location: products.php'); exit; }

$pageTitle = $p['name'];
require_once __DIR__ . '/includes/header.php';

$imgs = product_images($p);
$variants = json_decode($p['variants_json'] ?? '[]', true) ?: [['weight' => '1 পাউন্ড', 'flavor' => 'ক্লাসিক', 'extra' => 0]];
$pid = (int)$p['id'];
$isFav = is_favorite($pid);
$savings = max(0, (float)$p['regular_price'] - (float)$p['discount_price']);
$waUrl = whatsapp_order_url($p);

$revStmt = $pdo->prepare("SELECT * FROM reviews WHERE product_id = ? AND status = 1 ORDER BY id DESC");
$revStmt->execute([$pid]);
$reviews = $revStmt->fetchAll();

$relStmt = $pdo->prepare("SELECT p.*, b.name AS brand_name FROM products p LEFT JOIN brands b ON b.id = p.brand_id WHERE p.category_id = ? AND p.id != ? AND p.status = 1 ORDER BY RANDOM() LIMIT 4");
$relStmt->execute([$p['category_id'], $pid]);
$related = $relStmt->fetchAll();
$favIds = get_favorite_ids();
$loggedInUser = current_user();
?>

<div class="container py-4">
  <!-- Breadcrumb Only (Removed Duplicate Title Below Breadcrumb per #26) -->
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small mb-0">
      <li class="breadcrumb-item"><a href="index.php" class="text-danger">হোম</a></li>
      <li class="breadcrumb-item"><a href="products.php" class="text-danger">সকল কেক</a></li>
      <li class="breadcrumb-item"><a href="category.php?slug=<?= e($p['cat_slug']) ?>" class="text-danger"><?= e($p['cat_name']) ?></a></li>
      <li class="breadcrumb-item active"><?= e($p['name']) ?></li>
    </ol>
  </nav>

  <div class="row g-4 mb-5">
    <!-- Left: Overflow-Locked Auto-Slide Gallery (Handles 10+ Photos Cleanly #26) -->
    <div class="col-lg-5 pdp-gallery-col">
      <div class="pdp-gallery-main" id="pdpGalleryWrap">
        <?php if ((int)$p['discount_percent'] > 0): ?>
          <span class="badge-discount">-<?= (int)$p['discount_percent'] ?>% ছাড়</span>
        <?php endif; ?>
        <button type="button" class="btn-fav-corner <?= $isFav ? 'active' : '' ?>" data-fav-id="<?= $pid ?>" onclick="toggleFavorite(<?= $pid ?>, this)">
          <i class="<?= $isFav ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
        </button>
        <span class="pdp-img-counter" id="pdpImgCounter">1 / <?= count($imgs) ?></span>
        <img id="pdpMainImg" src="<?= e(asset_url($imgs[0])) ?>" alt="<?= e($p['name']) ?>" onclick="openImagePopup(this.src)">
        <?php if (count($imgs) > 1): ?>
          <button type="button" class="btn btn-light btn-sm rounded-circle position-absolute top-50 start-0 translate-middle-y ms-2 shadow" onclick="pdpSlide(-1)"><i class="fa-solid fa-chevron-left"></i></button>
          <button type="button" class="btn btn-light btn-sm rounded-circle position-absolute top-50 end-0 translate-middle-y me-2 shadow" onclick="pdpSlide(1)"><i class="fa-solid fa-chevron-right"></i></button>
        <?php endif; ?>
      </div>
      <div class="pdp-thumbs-wrap">
        <?php if (count($imgs) > 4): ?>
          <button type="button" class="btn btn-sm btn-outline-danger rounded-circle px-2" onclick="document.getElementById('pdpThumbsTrack').scrollBy({left:-160,behavior:'smooth'})"><i class="fa-solid fa-chevron-left"></i></button>
        <?php endif; ?>
        <div class="pdp-thumbs" id="pdpThumbsTrack">
          <?php foreach ($imgs as $idx => $im): ?>
            <div class="pdp-thumb <?= $idx === 0 ? 'active' : '' ?>" onclick="pdpGoTo(<?= $idx ?>)">
              <img src="<?= e(asset_url($im)) ?>" alt="Photo <?= $idx + 1 ?>">
            </div>
          <?php endforeach; ?>
        </div>
        <?php if (count($imgs) > 4): ?>
          <button type="button" class="btn btn-sm btn-outline-danger rounded-circle px-2" onclick="document.getElementById('pdpThumbsTrack').scrollBy({left:160,behavior:'smooth'})"><i class="fa-solid fa-chevron-right"></i></button>
        <?php endif; ?>
      </div>
    </div>

    <!-- Right: Brand | SKU | Category | Fav Icon -> Name -> Price|Regular|Savings|Stock -> Buttons -->
    <div class="col-lg-7">
      <div class="bg-white p-4 rounded-4 border shadow-sm h-100">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom small">
          <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="badge bg-danger-subtle text-danger fw-bold"><i class="fa-solid fa-crown me-1"></i><?= e($p['brand_name']) ?></span>
            <span class="text-muted">|</span>
            <span>SKU: <strong><?= e($p['sku']) ?></strong></span>
            <span class="text-muted">|</span>
            <span>ক্যাটাগরি: <a href="category.php?slug=<?= e($p['cat_slug']) ?>" class="text-danger fw-bold"><?= e($p['cat_name']) ?></a></span>
          </div>
          <button type="button" class="btn btn-sm btn-outline-danger rounded-pill <?= $isFav ? 'active' : '' ?>" data-fav-id="<?= $pid ?>" onclick="toggleFavorite(<?= $pid ?>, this)">
            <i class="<?= $isFav ? 'fa-solid' : 'fa-regular' ?> fa-heart me-1"></i>ফেভরিট
          </button>
        </div>

        <h1 class="h3 fw-bold mb-2"><?= e($p['name']) ?></h1>
        <div class="mb-3 small">
          <span class="text-warning fw-bold"><i class="fa-solid fa-star"></i> <?= number_format((float)$p['rating'], 1) ?></span>
          <span class="text-muted ms-1">(<?= count($reviews) ?>টি কাস্টমার রিভিউ)</span>
        </div>

        <div class="p-3 rounded-3 mb-4 d-flex flex-wrap align-items-center gap-3" style="background:var(--brand-soft);border:1px solid var(--brand-border);">
          <div>
            <div class="small text-muted">ডিসকাউন্ট মূল্য</div>
            <div class="fs-2 fw-bold text-danger" id="pdpDiscPrice"><?= format_tk((float)$p['discount_price']) ?></div>
          </div>
          <div>
            <div class="small text-muted">রেগুলার মূল্য</div>
            <div class="fs-5 text-muted text-decoration-line-through" id="pdpRegPrice"><?= format_tk((float)$p['regular_price']) ?></div>
          </div>
          <div><span class="badge bg-success fs-6 px-3 py-2 rounded-pill">মোট সাশ্রয় <?= format_tk($savings) ?></span></div>
          <div class="ms-auto">
            <?php if ((int)$p['stock'] > 0): ?>
              <span class="badge bg-success-subtle text-success border border-success px-3 py-2 rounded-pill fw-bold"><i class="fa-solid fa-circle-check me-1"></i>ইন স্টক (<?= (int)$p['stock'] ?>টি)</span>
            <?php else: ?>
              <span class="badge bg-danger px-3 py-2 rounded-pill fw-bold">আউট অব স্টক</span>
            <?php endif; ?>
          </div>
        </div>

        <div class="row g-3 mb-4">
          <div class="col-md-7">
            <label class="form-label small fw-bold">ভ্যারিয়েন্ট (ওজন ও ফ্লেভার):</label>
            <select id="pdpVariantSelect" class="form-select rounded-3" onchange="updatePdpPrice()">
              <?php foreach ($variants as $v):
                $vLabel = ($v['weight'] ?? '1 পাউন্ড') . ' | ' . ($v['flavor'] ?? 'ক্লাসিক');
                $extra = (float)($v['extra'] ?? 0);
              ?>
                <option value="<?= e($vLabel) ?>" data-extra="<?= $extra ?>"><?= e($vLabel) ?><?= $extra > 0 ? ' (+৳' . number_format($extra) . ')' : '' ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-5">
            <label class="form-label small fw-bold">পরিমাণ:</label>
            <div class="input-group">
              <button class="btn btn-outline-danger" type="button" onclick="chgPdpQty(-1)">-</button>
              <input type="number" id="pdpQty" class="form-control text-center fw-bold" value="1" min="1" max="20">
              <button class="btn btn-outline-danger" type="button" onclick="chgPdpQty(1)">+</button>
            </div>
          </div>
        </div>

        <div class="d-grid gap-2 mb-3">
          <button type="button" class="btn btn-order-now btn-lg py-3 rounded-3 fs-5" onclick="pdpOrderNow()">
            <i class="fa-solid fa-bolt me-2"></i>Order Now — এখনই অর্ডার করুন
          </button>
        </div>
        <div class="row g-2">
          <div class="col-4"><button type="button" class="btn btn-add-cart w-100 py-2" onclick="pdpAddCart()"><i class="fa-solid fa-cart-plus me-1"></i>Cart</button></div>
          <div class="col-4"><button type="button" class="btn btn-outline-danger w-100 py-2 fw-bold <?= $isFav ? 'active' : '' ?>" data-fav-id="<?= $pid ?>" onclick="toggleFavorite(<?= $pid ?>, this)"><i class="<?= $isFav ? 'fa-solid' : 'fa-regular' ?> fa-heart me-1"></i>Favorite</button></div>
          <div class="col-4"><a href="<?= e($waUrl) ?>" target="_blank" class="btn btn-whatsapp-order w-100 py-2"><i class="fa-brands fa-whatsapp me-1"></i>WhatsApp</a></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Delivery, সুবিধা, অথেনটিক তথ্য, রিপ্লেসমেন্ট তথ্য -->
  <div class="row g-3 mb-5">
    <div class="col-md-6 col-lg-3"><div class="benefit-3d-card"><h6 class="fw-bold text-danger"><i class="fa-solid fa-truck-fast me-2"></i>ডেলিভারি তথ্য</h6><p class="small text-muted mb-0"><?= e($p['delivery_info']) ?></p></div></div>
    <div class="col-md-6 col-lg-3"><div class="benefit-3d-card"><h6 class="fw-bold text-success"><i class="fa-solid fa-gift me-2"></i>বিশেষ সুবিধা</h6><p class="small text-muted mb-0"><?= e($p['benefits_info']) ?></p></div></div>
    <div class="col-md-6 col-lg-3"><div class="benefit-3d-card"><h6 class="fw-bold text-primary"><i class="fa-solid fa-certificate me-2"></i>অথেনটিক তথ্য</h6><p class="small text-muted mb-0"><?= e($p['authentic_info']) ?></p></div></div>
    <div class="col-md-6 col-lg-3"><div class="benefit-3d-card"><h6 class="fw-bold text-warning"><i class="fa-solid fa-rotate-left me-2"></i>রিপ্লেসমেন্ট তথ্য</h6><p class="small text-muted mb-0"><?= e($p['replacement_info']) ?></p></div></div>
  </div>

  <!-- 3-Step Product Description -->
  <section class="bg-white p-4 rounded-4 border shadow-sm mb-5">
    <h4 class="fw-bold mb-3 text-danger"><i class="fa-solid fa-list-check me-2"></i>প্রোডাক্ট ডেসক্রিপশন (৩ ধাপে বিস্তারিত)</h4>
    <div class="row g-3">
      <div class="col-md-4"><div class="p-3 rounded-4 h-100" style="background:#fdf2f8;border:1px solid #f3d5e3;"><span class="badge bg-danger rounded-pill mb-2">ধাপ ১</span><h6 class="fw-bold">উপাদান ও স্বাদ</h6><p class="small text-muted mb-0"><?= e($p['desc_step1']) ?></p></div></div>
      <div class="col-md-4"><div class="p-3 rounded-4 h-100" style="background:#fefce8;border:1px solid #fde68a;"><span class="badge bg-warning text-dark rounded-pill mb-2">ধাপ ২</span><h6 class="fw-bold">কাস্টমাইজেশন ও যত্ন</h6><p class="small text-muted mb-0"><?= e($p['desc_step2']) ?></p></div></div>
      <div class="col-md-4"><div class="p-3 rounded-4 h-100" style="background:#f0fdf4;border:1px solid #bbf7d0;"><span class="badge bg-success rounded-pill mb-2">ধাপ ৩</span><h6 class="fw-bold">পুষ্টি ও ডেলিভারি গ্যারান্টি</h6><p class="small text-muted mb-0"><?= e($p['desc_step3']) ?></p></div></div>
    </div>
  </section>

  <!-- Reviews (Logged-in Customers Only) -->
  <section class="bg-white p-4 rounded-4 border shadow-sm mb-5" id="reviews">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
      <h4 class="fw-bold mb-0"><i class="fa-solid fa-star text-warning me-2"></i>কাস্টমার রিভিউ ও রেটিং (<?= count($reviews) ?>)</h4>
      <?php if ($loggedInUser): ?>
        <span class="badge bg-success-subtle text-success border border-success rounded-pill px-3 py-2"><i class="fa-solid fa-circle-dot me-1"></i>লগইন অবস্থায়: <?= e($loggedInUser['name']) ?></span>
      <?php endif; ?>
    </div>
    <div class="row g-4">
      <div class="col-lg-5">
        <?php if ($loggedInUser): ?>
          <form id="reviewForm" class="p-3 rounded-4 bg-light border">
            <input type="hidden" name="product_id" value="<?= $pid ?>">
            <div class="mb-3"><label class="form-label small fw-bold">রেটিং দিন</label><select name="rating" class="form-select"><option value="5">★★★★★ (৫)</option><option value="4">★★★★☆ (৪)</option><option value="3">★★★☆☆ (৩)</option><option value="2">★★☆☆☆ (২)</option><option value="1">★☆☆☆☆ (১)</option></select></div>
            <div class="mb-3"><label class="form-label small fw-bold">মতামত</label><textarea name="comment" class="form-control" rows="3" required></textarea></div>
            <button type="submit" class="btn btn-danger rounded-pill fw-bold px-4">রিভিউ সাবমিট করুন</button>
          </form>
        <?php else: ?>
          <div class="p-4 rounded-4 text-center border" style="background:var(--brand-soft);">
            <i class="fa-solid fa-lock fs-2 text-danger mb-2"></i>
            <h6 class="fw-bold">রিভিউ দিতে লগইন করুন</h6>
            <a href="login.php?redirect=<?= urlencode('product.php?slug=' . $p['slug'] . '#reviews') ?>" class="btn btn-danger rounded-pill fw-bold px-4 mt-2">লগইন করুন</a>
          </div>
        <?php endif; ?>
      </div>
      <div class="col-lg-7">
        <?php if (empty($reviews)): ?>
          <div class="text-muted p-4 text-center border rounded-4">এখনো কোনো রিভিউ নেই।</div>
        <?php else: ?>
          <div class="d-flex flex-column gap-3">
            <?php foreach ($reviews as $rv): ?>
              <div class="p-3 rounded-4 border">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <strong><i class="fa-solid fa-user-circle text-danger me-1"></i><?= e($rv['user_name']) ?></strong>
                  <span class="text-warning small"><?= str_repeat('★', (int)$rv['rating']) . str_repeat('☆', 5 - (int)$rv['rating']) ?></span>
                </div>
                <p class="small mb-1"><?= e($rv['comment']) ?></p>
                <small class="text-muted" style="font-size:11px;"><?= e($rv['created_at']) ?></small>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <?php if (!empty($related)): ?>
  <section class="mb-4">
    <h4 class="fw-bold mb-3"><i class="fa-solid fa-sparkles text-warning me-2"></i>রিলেটেড প্রোডাক্টস</h4>
    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-2 g-md-3">
      <?php foreach ($related as $rp): ?><div class="col"><?= render_cake_card($rp, $rp['brand_name'] ?? 'Cake Shop BD', $favIds) ?></div><?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
</div>

<script>
const pdpImages = <?= json_encode(array_map('asset_url', $imgs)) ?>;
const baseDisc = <?= (float)$p['discount_price'] ?>;
const baseReg = <?= (float)$p['regular_price'] ?>;
const pid = <?= $pid ?>;
let pdpIdx = 0;

function pdpGoTo(idx) {
  pdpIdx = (idx + pdpImages.length) % pdpImages.length;
  document.getElementById('pdpMainImg').src = pdpImages[pdpIdx];
  document.getElementById('pdpImgCounter').textContent = (pdpIdx + 1) + ' / ' + pdpImages.length;
  document.querySelectorAll('.pdp-thumb').forEach((t, i) => t.classList.toggle('active', i === pdpIdx));
}
function pdpSlide(dir) { pdpGoTo(pdpIdx + dir); }
let autoSlide = setInterval(() => { if (pdpImages.length > 1) pdpSlide(1); }, 3800);
document.getElementById('pdpGalleryWrap')?.addEventListener('mouseenter', () => clearInterval(autoSlide));
document.getElementById('pdpGalleryWrap')?.addEventListener('mouseleave', () => { autoSlide = setInterval(() => { if (pdpImages.length > 1) pdpSlide(1); }, 3800); });

function getSelectedVariant() {
  const sel = document.getElementById('pdpVariantSelect');
  const opt = sel.options[sel.selectedIndex];
  return { label: sel.value, extra: parseFloat(opt.getAttribute('data-extra') || '0') };
}
function updatePdpPrice() {
  const v = getSelectedVariant();
  document.getElementById('pdpDiscPrice').textContent = '৳' + Math.round(baseDisc + v.extra).toLocaleString();
  document.getElementById('pdpRegPrice').textContent = '৳' + Math.round(baseReg + v.extra).toLocaleString();
}
function chgPdpQty(d) {
  const el = document.getElementById('pdpQty');
  el.value = Math.max(1, Math.min(20, parseInt(el.value || '1', 10) + d));
}
function pdpAddCart() {
  const v = getSelectedVariant();
  const q = parseInt(document.getElementById('pdpQty').value || '1', 10);
  apiPost('add_cart', { product_id: pid, qty: q, variant: v.label, extra_price: v.extra }).then(res => {
    if (res.ok) {
      document.querySelectorAll('.js-cart-count').forEach(el => el.textContent = res.cart_count);
      showToast('🛒 কেকটি কার্টে যুক্ত হয়েছে!', 'success');
    } else showToast(res.message, 'danger');
  });
}
function pdpOrderNow() {
  const v = getSelectedVariant();
  const q = parseInt(document.getElementById('pdpQty').value || '1', 10);
  apiPost('add_cart', { product_id: pid, qty: q, variant: v.label, extra_price: v.extra }).then(res => {
    if (res.ok) window.location.href = APP_BASE + '/checkout.php';
    else showToast(res.message, 'danger');
  });
}
document.getElementById('reviewForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action', 'submit_review');
  const res = await fetch(APP_BASE + '/api/ajax.php', { method: 'POST', body: fd }).then(r => r.json());
  showToast(res.message, res.ok ? 'success' : 'danger');
  if (res.ok) setTimeout(() => location.reload(), 800);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
