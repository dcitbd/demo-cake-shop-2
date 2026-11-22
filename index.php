<?php
$pageTitle = 'হোম — সকল প্রকার প্রিমিয়াম কেক';
require_once __DIR__ . '/includes/header.php';

$pdo = db();
$banners = $pdo->query("SELECT * FROM banners WHERE status = 1 ORDER BY sort_order ASC, id DESC")->fetchAll();
$brands = $pdo->query("SELECT * FROM brands WHERE status = 1 ORDER BY id ASC")->fetchAll();
$mainCats = $pdo->query("SELECT * FROM categories WHERE parent_id = 0 AND status = 1 ORDER BY sort_order ASC")->fetchAll();
$favIds = get_favorite_ids();
?>

<div class="container py-4">
  <!-- 1. Cinematic Patisserie Hero Showcase -->
  <section class="mb-5">
    <div id="heroBannerCarousel" class="carousel slide carousel-fade hero-banner-card" data-bs-ride="carousel" data-bs-interval="5000">
      <div class="carousel-indicators">
        <?php foreach ($banners as $i => $b): ?>
          <button type="button" data-bs-target="#heroBannerCarousel" data-bs-slide-to="<?= $i ?>" class="<?= $i === 0 ? 'active' : '' ?>" aria-label="Slide <?= $i + 1 ?>"></button>
        <?php endforeach; ?>
      </div>
      <div class="carousel-inner">
        <?php foreach ($banners as $i => $b): ?>
          <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
            <img src="<?= e(asset_url($b['image'])) ?>" class="hero-slide-img" alt="<?= e($b['title']) ?>">
            <div class="hero-overlay-caption">
              <div class="csbd-hero-glass-box">
                <div class="csbd-live-pill">
                  <span class="csbd-live-dot"></span>
                  <span><?= e(!empty($b['badge_text']) ? $b['badge_text'] : 'FRESHLY BAKED • SAME-DAY DELIVERY') ?></span>
                </div>
                <h1><?= e($b['title']) ?></h1>
                <?php if (!empty($b['subtitle'])): ?>
                  <p><?= e($b['subtitle']) ?></p>
                <?php endif; ?>
                <div class="d-flex gap-2 flex-wrap">
                  <a href="<?= e(base_url($b['btn_link'] ?: 'products.php')) ?>" class="btn btn-warning btn-lg rounded-pill fw-bold px-4 shadow-sm">
                    <i class="fa-solid fa-cake-candles me-2"></i><?= e($b['btn_text'] ?: 'অর্ডার করুন') ?>
                  </a>
                  <a href="<?= e(base_url('products.php')) ?>" class="btn btn-outline-light btn-lg rounded-pill fw-bold px-4">
                    সব কেক দেখুন <i class="fa-solid fa-arrow-right ms-1"></i>
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php if (count($banners) > 1): ?>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroBannerCarousel" data-bs-slide="prev" aria-label="Previous"><span class="carousel-control-prev-icon"></span></button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroBannerCarousel" data-bs-slide="next" aria-label="Next"><span class="carousel-control-next-icon"></span></button>
      <?php endif; ?>
    </div>
  </section>

  <!-- 2. Artisanal Patisserie Benefits Bento -->
  <section class="mb-5">
    <div class="row g-3">
      <div class="col-6 col-lg-3">
        <div class="benefit-3d-card">
          <div class="benefit-icon-box"><i class="fa-solid fa-truck-fast"></i></div>
          <h6 class="fw-bold mb-1">দ্রুত এক্সপ্রেস ডেলিভারি</h6>
          <p class="small text-muted mb-0">২-৪ ঘন্টায় সেইম-ডে কুল-বক্স ডেলিভারি আপনার দোরগোড়ায়।</p>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="benefit-3d-card">
          <div class="benefit-icon-box" style="background:linear-gradient(135deg,#15803d,#22c55e);"><i class="fa-solid fa-award"></i></div>
          <h6 class="fw-bold mb-1">১০০% ফ্রেশ ও হালাল মান</h6>
          <p class="small text-muted mb-0">অর্ডার পাওয়ার পর প্রিমিয়াম বেলজিয়ান উপাদানে ফ্রেশ বেক করা হয়।</p>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="benefit-3d-card">
          <div class="benefit-icon-box" style="background:linear-gradient(135deg,#b45309,#f2b749);"><i class="fa-solid fa-tags"></i></div>
          <h6 class="fw-bold mb-1">৩৫% পর্যন্ত ডিসকাউন্ট</h6>
          <p class="small text-muted mb-0">প্রতিটি কেকে স্পেশাল ছাড়, কুপন অফার ও ফ্রি ক্যান্ডেল-নাইফ।</p>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="benefit-3d-card">
          <div class="benefit-icon-box" style="background:linear-gradient(135deg,#3b1a0d,#7c3a1f);"><i class="fa-solid fa-headset"></i></div>
          <h6 class="fw-bold mb-1">২৪/৭ কাস্টম সাপোর্ট</h6>
          <p class="small text-muted mb-0">ফটো কেক ও কাস্টম ডিজাইনের জন্য কল বা হোয়াটসঅ্যাপ সাপোর্ট।</p>
        </div>
      </div>
    </div>
  </section>

  <!-- 3. Signature Brands Medallion Grid -->
  <section class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <div class="csbd-section-head">
        <h4 class="fw-bold mb-0"><i class="fa-solid fa-crown text-warning me-2"></i>আমাদের প্রিমিয়াম কেক ব্র্যান্ডসমূহ</h4>
        <small class="text-muted">বিশ্বস্ত আর্টিসান বেকারি ও শেফ ব্র্যান্ড</small>
      </div>
      <a href="<?= e(base_url('products.php')) ?>" class="btn btn-sm btn-outline-danger rounded-pill fw-bold px-3">সব দেখুন &rarr;</a>
    </div>
    <div class="row g-3">
      <?php foreach ($brands as $br): ?>
        <div class="col-6 col-md-4 col-lg-3">
          <a href="<?= e(base_url('products.php?brand=' . (int)$br['id'])) ?>" class="brand-pill-card">
            <img src="<?= e(asset_url($br['logo'] ?: 'assets/images/logo.png')) ?>" alt="<?= e($br['name']) ?>" width="46" height="46">
            <div class="overflow-hidden">
              <div class="fw-bold small text-truncate"><?= e($br['name']) ?></div>
              <div class="text-muted" style="font-size:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($br['description']) ?></div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- 4. Category-Wise Signature Cake Sliders -->
  <?php foreach ($mainCats as $cat):
    $stmt = $pdo->prepare("
      SELECT p.*, b.name AS brand_name
      FROM products p
      LEFT JOIN brands b ON b.id = p.brand_id
      LEFT JOIN categories c ON c.id = p.category_id
      WHERE p.status = 1 AND (p.category_id = ? OR c.parent_id = ?)
      ORDER BY p.id DESC LIMIT 6
    ");
    $stmt->execute([$cat['id'], $cat['id']]);
    $catProducts = $stmt->fetchAll();
    if (empty($catProducts)) continue;
    $sliderId = 'catSlider_' . (int)$cat['id'];
  ?>
  <section class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <div class="d-flex align-items-center gap-3 csbd-section-head">
        <span class="badge bg-danger rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm" style="width:42px;height:42px;"><i class="fa-solid <?= e($cat['icon']) ?> fs-6"></i></span>
        <div>
          <h4 class="fw-bold mb-0"><?= e($cat['name']) ?></h4>
          <small class="text-muted">সর্বাধিক জনপ্রিয় ৬টি কেক — স্লাইড করে দেখুন</small>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button type="button" class="slider-nav-btn" onclick="slideCategory('<?= $sliderId ?>', -1)" aria-label="Previous"><i class="fa-solid fa-chevron-left"></i></button>
        <button type="button" class="slider-nav-btn" onclick="slideCategory('<?= $sliderId ?>', 1)" aria-label="Next"><i class="fa-solid fa-chevron-right"></i></button>
        <a href="<?= e(base_url('category.php?slug=' . urlencode($cat['slug']))) ?>" class="btn btn-sm btn-danger rounded-pill fw-bold px-3">সব দেখুন</a>
      </div>
    </div>

    <div class="category-slider-track" id="<?= $sliderId ?>">
      <?php foreach ($catProducts as $cp): ?>
        <div class="slider-slide-item">
          <?= render_cake_card($cp, $cp['brand_name'] ?? 'Cake Shop BD', $favIds) ?>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endforeach; ?>

  <!-- 5. Mobile & Desktop Apps Download CTA -->
  <section class="my-5">
    <div class="p-4 p-md-5 rounded-4 shadow-lg soft-brand-banner">
      <div class="row align-items-center g-4 position-relative" style="z-index:2;">
        <div class="col-lg-8">
          <span class="badge bg-warning text-dark rounded-pill px-3 py-2 fw-bold mb-2"><i class="fa-solid fa-mobile-screen-button me-1"></i>MOBILE &amp; DESKTOP APPS</span>
          <h2 class="fw-bold mb-2">Cake Shop BD অ্যাপ ডাউনলোড করুন!</h2>
          <p class="mb-0 opacity-90">Android, iOS, Windows এবং Mac-এ সরাসরি অ্যাপ হিসেবে ব্যবহার করুন। নতুন অফার ও অর্ডার আপডেটের ইনস্ট্যান্ট মোবাইল নোটিফিকেশন পান।</p>
        </div>
        <div class="col-lg-4 d-flex flex-wrap gap-2 justify-content-lg-end">
          <button type="button" class="btn btn-warning btn-lg rounded-pill fw-bold px-4" onclick="triggerAppInstall()">
            <i class="fa-solid fa-download me-2"></i>ইনস্টল করুন
          </button>
          <a href="<?= e(base_url('apps.php')) ?>" class="btn btn-outline-light btn-lg rounded-pill fw-bold px-4">
            ডাউনলোড গাইড
          </a>
        </div>
      </div>
    </div>
  </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
