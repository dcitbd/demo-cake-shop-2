<?php
// Original Dashboard Header — preserves 100% original Customer Dashboard design with updated Cake Shop BD logo
require_once __DIR__ . '/functions.php';
track_live_visitor();
$siteName = setting('site_name', 'Cake Shop BD');
$pageTitle = isset($pageTitle) ? ($pageTitle . ' | ' . $siteName) : $siteName;
$user = current_user();
$currScript = basename($_SERVER['PHP_SELF'] ?? 'index.php');

$allNavCats = db()->query("SELECT c.id, c.parent_id, c.name, c.slug, c.icon, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.status = 1) AS p_count FROM categories c WHERE c.status = 1 ORDER BY c.sort_order ASC, c.id ASC")->fetchAll();
$navMainCats = [];
$navSubCats = [];
foreach ($allNavCats as $nc) {
    if ((int)$nc['parent_id'] === 0) $navMainCats[] = $nc;
    else $navSubCats[(int)$nc['parent_id']][] = $nc;
}
$unreadNotif = (int)db()->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e(setting('tagline')) ?>">
  <meta name="theme-color" content="#b84a6b">
  <link rel="manifest" href="<?= e(base_url('manifest.json')) ?>">
  <link rel="icon" type="image/png" href="<?= e(base_url('assets/images/icon-192.png')) ?>">
  <link rel="preconnect" href="https://fonts.maateen.me" crossorigin>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.maateen.me/kalpurush/font.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link href="<?= e(base_url('assets/css/style.css')) ?>" rel="stylesheet">
  <script>window.APP_BASE = <?= json_encode(base_url()) ?>; window.IS_ADMIN = <?= is_admin() ? 'true' : 'false' ?>;</script>
</head>
<body>

<div id="digitalLoader">
  <div class="loader-ring"><i class="fa-solid fa-cake-candles loader-cake-icon"></i></div>
  <div class="loader-percent"><?= e($siteName) ?> • <span id="loaderPercent">50%</span></div>
</div>

<div class="cake-bg-canvas" id="cakeBgCanvas" aria-hidden="true"></div>

<!-- Top Contact & Notice Bar -->
<div class="top-notice-bar">
  <div class="container">
    <div class="row align-items-center g-1">
      <div class="col-lg-3 d-none d-lg-flex align-items-center gap-3">
        <a href="tel:<?= e(setting('phone')) ?>"><i class="fa-solid fa-phone-volume me-1 text-warning"></i><?= e(setting('phone')) ?></a>
        <a href="mailto:<?= e(setting('email')) ?>"><i class="fa-solid fa-envelope me-1 text-warning"></i>ইমেইল</a>
      </div>
      <div class="col-12 col-lg-6">
        <div class="notice-marquee-wrap"><div class="notice-marquee"><?= e(setting('notice_text')) ?></div></div>
      </div>
      <div class="col-lg-3 d-none d-lg-flex justify-content-end align-items-center gap-3">
        <a href="<?= e(base_url('tracking.php')) ?>"><i class="fa-solid fa-truck-fast me-1 text-warning"></i>ট্র্যাকিং</a>
        <a href="<?= e(base_url('apps.php')) ?>"><i class="fa-solid fa-mobile-screen-button me-1 text-warning"></i>Apps</a>
        <button type="button" class="btn btn-sm btn-outline-light py-0 px-2 rounded-pill" onclick="toggleTheme()" title="থিম পরিবর্তন"><i class="fa-solid fa-circle-half-stroke"></i></button>
      </div>
    </div>
  </div>
</div>

<!-- Navbar (Original Customer Dashboard Layout) -->
<header class="cake-navbar">
  <div class="container">
    <div class="nav-top-row d-flex align-items-center justify-content-between gap-2 flex-wrap">
      <a href="<?= e(base_url('index.php')) ?>" class="d-flex align-items-center gap-2 nav-brand-logo">
        <img src="<?= e(base_url('assets/images/logo.png')) ?>" alt="<?= e($siteName) ?>">
        <span class="fw-bold" style="font-size:18px;color:var(--brand-primary-dark);"><?= e($siteName) ?></span>
      </a>

      <form action="<?= e(base_url('search.php')) ?>" method="get" class="nav-search-box order-3 order-lg-2 mt-2 mt-lg-0 w-100">
        <input type="text" id="liveSearchInput" name="q" class="form-control" placeholder="কেক খুঁজুন... (চকলেট, বার্থডে, বেন্টো)" value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off">
        <button type="submit" class="nav-search-btn" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
        <div class="live-search-dropdown" id="liveSearchDropdown"></div>
      </form>

      <div class="d-flex align-items-center gap-1 gap-sm-2 order-2 order-lg-3 nav-actions-cluster">
        <button type="button" class="nav-icon-btn d-none d-sm-inline-flex" onclick="enableMobileNotifications()" title="অর্ডার নোটিফিকেশন">
          <i class="fa-solid fa-bell"></i>
          <span class="nav-badge-count js-notif-count"><?= $unreadNotif ?></span>
        </button>
        <a href="<?= e(base_url('favorites.php')) ?>" class="nav-icon-btn" title="ফেভরিট">
          <i class="fa-solid fa-heart"></i>
          <span class="nav-badge-count js-fav-count"><?= favorite_count() ?></span>
        </a>
        <a href="<?= e(base_url('cart.php')) ?>" class="nav-icon-btn" title="কার্ট">
          <i class="fa-solid fa-cart-shopping"></i>
          <span class="nav-badge-count js-cart-count"><?= cart_count() ?></span>
        </a>
        <?php if ($user): ?>
          <div class="dropdown">
            <button type="button" class="btn btn-sm btn-danger rounded-pill px-2 px-sm-3 fw-bold dropdown-toggle nav-user-btn" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fa-solid fa-user-check me-1"></i><span class="d-none d-sm-inline"><?= e(explode(' ', $user['name'])[0]) ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4">
              <li><a class="dropdown-item" href="<?= e(base_url('customer/dashboard.php')) ?>"><i class="fa-solid fa-gauge me-2 text-danger"></i>কাস্টমার ড্যাশবোর্ড</a></li>
              <?php if (is_admin()): ?>
                <li><a class="dropdown-item fw-bold text-primary" href="<?= e(base_url('admin/index.php')) ?>"><i class="fa-solid fa-shield-halved me-2"></i>অ্যাডমিন প্যানেল</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="<?= e(base_url('logout.php')) ?>"><i class="fa-solid fa-right-from-bracket me-2"></i>লগআউট</a></li>
            </ul>
          </div>
        <?php else: ?>
          <a href="<?= e(base_url('login.php')) ?>" class="btn btn-sm btn-danger rounded-pill px-3 fw-bold nav-user-btn"><i class="fa-solid fa-user me-1"></i>লগইন</a>
        <?php endif; ?>
      </div>
    </div>

    <nav class="nav-menu-bar" aria-label="Main navigation">
      <div class="nav-links-group">
        <a href="<?= e(base_url('index.php')) ?>" class="nav-pill-link <?= $currScript === 'index.php' ? 'active' : '' ?>">
          <i class="fa-solid fa-house text-danger"></i>হোম
        </a>
        <a href="<?= e(base_url('products.php')) ?>" class="nav-pill-link <?= $currScript === 'products.php' ? 'active' : '' ?>">
          <i class="fa-solid fa-cake-candles text-danger"></i>সকল কেক
        </a>

        <div class="cat-dropdown-wrap" id="navCatWrap">
          <button type="button" id="navCatBtn" class="nav-pill-link <?= $currScript === 'category.php' ? 'active' : '' ?>" onclick="toggleCatMenu(event)" aria-expanded="false">
            <i class="fa-solid fa-layer-group text-danger"></i>ক্যাটাগরি <i class="fa-solid fa-chevron-down" style="font-size:10px;"></i>
          </button>
          <div class="cat-dropdown-panel" id="navCatPanel">
            <a class="cat-drop-item text-danger fw-bold border-bottom mb-1 pb-2" href="<?= e(base_url('category.php')) ?>">
              <span><i class="fa-solid fa-border-all me-2"></i>সকল ক্যাটাগরি দেখুন</span>
              <i class="fa-solid fa-arrow-right small"></i>
            </a>
            <?php foreach ($navMainCats as $nc): ?>
              <a class="cat-drop-item" href="<?= e(base_url('category.php?slug=' . urlencode($nc['slug']))) ?>">
                <span><i class="fa-solid <?= e($nc['icon']) ?> me-2 text-danger"></i><?= e($nc['name']) ?></span>
                <?php if ((int)$nc['p_count'] > 0): ?><span class="badge bg-light text-dark border rounded-pill"><?= (int)$nc['p_count'] ?></span><?php endif; ?>
              </a>
              <?php if (!empty($navSubCats[(int)$nc['id']])): ?>
                <?php foreach ($navSubCats[(int)$nc['id']] as $subc): ?>
                  <a class="cat-drop-item cat-drop-sub" href="<?= e(base_url('category.php?slug=' . urlencode($subc['slug']))) ?>">
                    <span>↳ <?= e($subc['name']) ?></span>
                    <?php if ((int)$subc['p_count'] > 0): ?><span class="small text-muted">(<?= (int)$subc['p_count'] ?>)</span><?php endif; ?>
                  </a>
                <?php endforeach; ?>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        </div>

        <a href="<?= e(base_url('tracking.php')) ?>" class="nav-pill-link <?= $currScript === 'tracking.php' ? 'active' : '' ?>">
          <i class="fa-solid fa-location-dot text-danger"></i>ট্র্যাকিং
        </a>
        <a href="<?= e(base_url('apps.php')) ?>" class="nav-pill-link <?= $currScript === 'apps.php' ? 'active' : '' ?>">
          <i class="fa-solid fa-mobile-screen-button text-danger"></i>অ্যাপস
        </a>
        <a href="<?= e(base_url('contact.php')) ?>" class="nav-pill-link <?= $currScript === 'contact.php' ? 'active' : '' ?>">
          <i class="fa-solid fa-headset text-danger"></i>যোগাযোগ
        </a>
      </div>
      <div class="d-none d-lg-flex align-items-center gap-2">
        <button type="button" class="btn btn-sm btn-warning rounded-pill fw-bold px-3 pwa-install-btn" onclick="triggerAppInstall()"><i class="fa-solid fa-download me-1"></i>Install App</button>
        <?php if (is_admin()): ?>
          <a href="<?= e(base_url('admin/index.php')) ?>" class="btn btn-sm btn-outline-danger rounded-pill fw-bold px-3"><i class="fa-solid fa-user-shield me-1"></i>অ্যাডমিন</a>
        <?php endif; ?>
      </div>
    </nav>
  </div>
</header>

<main class="main-content-wrapper">
