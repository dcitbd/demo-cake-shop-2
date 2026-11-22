<?php
/**
 * SweetDream Cakes BD v3.1 — Responsive Animated Tree-Sidebar Admin Panel
 */
require_once dirname(__DIR__) . '/includes/functions.php';
if (!is_admin()) {
    header('Location: ../login.php?redirect=admin/index.php');
    exit;
}

$pdo = db();
$adminUser = current_user();
$tab = $_GET['tab'] ?? 'dashboard';
$adminFlash = '';
$adminError = '';

if (isset($_GET['read_notifs'])) {
    $pdo->exec("UPDATE notifications SET is_read = 1");
}

require_once __DIR__ . '/actions.php';

$cnt = [
    'products' => (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'categories' => (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
    'low_stock' => (int)$pdo->query("SELECT COUNT(*) FROM products WHERE stock > 0 AND stock < 5")->fetchColumn(),
    'out_stock' => (int)$pdo->query("SELECT COUNT(*) FROM products WHERE stock = 0")->fetchColumn(),
    'defective' => (int)$pdo->query("SELECT COUNT(*) FROM products WHERE defective_flag = 1")->fetchColumn(),
    'brands' => (int)$pdo->query("SELECT COUNT(*) FROM brands")->fetchColumn(),
    'variants' => (int)$pdo->query("SELECT COUNT(*) FROM variants")->fetchColumn(),
    'banners' => (int)$pdo->query("SELECT COUNT(*) FROM banners")->fetchColumn(),
    'orders' => (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'incomplete' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status='incomplete'")->fetchColumn(),
    'delivered' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status='delivered'")->fetchColumn(),
    'courier' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status='courier'")->fetchColumn(),
    'cancelled' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status IN ('cancelled','returned')")->fetchColumn(),
    'visitors' => (int)$pdo->query("SELECT COUNT(*) FROM live_visitors")->fetchColumn(),
    'customers' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn(),
    'reviews' => (int)$pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn(),
    'master_admin' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='master_admin'")->fetchColumn(),
    'staff' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('staff','admin')")->fetchColumn(),
    'couriers' => (int)$pdo->query("SELECT COUNT(*) FROM couriers")->fetchColumn(),
    'unread_notif' => (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0")->fetchColumn(),
];

function nav_active(string $t, string $curr): string { return $t === $curr ? 'active' : ''; }

$prodTabs = ['products', 'add_product', 'edit_product', 'categories', 'brands', 'variants', 'banners'];
$orderTabs = ['orders', 'orders_incomplete', 'orders_delivered', 'orders_courier', 'orders_cancelled', 'add_order', 'edit_order', 'live_visitors', 'customers', 'reviews'];
$teamTabs = ['master_admin', 'staff'];
$settingTabs = ['audit_logs', 'payment_methods', 'delivery_methods', 'coupons', 'site_settings', 'profile', 'password'];
?>
<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>অ্যাডমিন প্যানেল | <?= e(setting('site_name')) ?></title>
  <link href="https://fonts.maateen.me/kalpurush/font.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <script>window.APP_BASE = <?= json_encode(base_url()) ?>; window.IS_ADMIN = true;</script>
  <style>
    body { background: #fdf9fb; font-family: 'Kalpurush', system-ui, sans-serif; }
    .admin-layout { display: flex; min-height: 100vh; }
    .admin-sidebar {
      width: 280px; background: linear-gradient(180deg, #562137 0%, #2e111e 100%);
      color: #fce7f3; flex-shrink: 0; padding: 14px 10px; overflow-y: auto;
      position: sticky; top: 0; height: 100vh; z-index: 1050;
      box-shadow: 4px 0 24px rgba(46, 17, 30, 0.14);
    }
    .tree-group { margin-top: 6px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 6px; }
    .tree-trigger {
      width: 100%; background: transparent; border: none; color: #f6c967;
      display: flex; align-items: center; justify-content: space-between;
      padding: 9px 10px; border-radius: 10px; font-weight: 700; font-size: 13.5px; cursor: pointer;
      transition: background 0.22s ease;
    }
    .tree-trigger:hover { background: rgba(255,255,255,0.09); }
    .tree-trigger .arrow { transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1); font-size: 11px; }
    .tree-group.open .tree-trigger .arrow { transform: rotate(90deg); }
    .tree-children {
      max-height: 0; opacity: 0; overflow: hidden;
      transition: max-height 0.35s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.25s ease;
      padding-left: 10px; border-left: 2px dashed rgba(246, 201, 103, 0.28); margin-left: 13px;
    }
    .tree-group.open .tree-children { max-height: 640px; opacity: 1; margin-top: 4px; }
    .admin-sidebar a.menu-link {
      display: flex; align-items: center; justify-content: space-between;
      padding: 7px 10px; border-radius: 9px; font-size: 13px; color: #fce7f3; margin: 2px 0; transition: all 0.2s ease;
    }
    .admin-sidebar a.menu-link:hover, .admin-sidebar a.menu-link.active { background: #b84a6b; color: #fff; font-weight: 700; }
    .admin-sidebar .cnt-badge { background: rgba(255,255,255,0.16); color: #fde68a; font-size: 11px; font-weight: 700; padding: 1px 7px; border-radius: 20px; }
    .admin-main { flex: 1; min-width: 0; padding: 18px 22px; }
    .admin-topbar { background: #fff; border-radius: 16px; padding: 12px 18px; margin-bottom: 18px; box-shadow: 0 6px 20px rgba(184,74,107,0.06); border: 1px solid #f1dce4; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
    .sidebar-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.48); z-index: 1040; backdrop-filter: blur(2px); }
    @media (max-width: 991px) {
      .admin-sidebar { position: fixed; left: -292px; transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
      .admin-sidebar.open { left: 0; }
      .sidebar-backdrop.open { display: block; }
      .admin-main { padding: 12px; }
    }
  </style>
</head>
<body>
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleAdminSidebar(false)"></div>
<div class="admin-layout">
  <!-- Animated Tree Sidebar (#7) -->
  <aside class="admin-sidebar" id="adminSidebar">
    <div class="d-flex align-items-center justify-content-between px-2 pb-3">
      <a href="?tab=dashboard" class="d-flex align-items-center gap-2 text-white">
        <img src="../assets/images/icon-192.png" width="36" height="36" class="rounded-3">
        <div>
          <div class="fw-bold lh-1"><?= e(setting('site_name', 'SweetDream')) ?></div>
          <small class="text-warning" style="font-size:11px;">অ্যাডমিন কন্ট্রোল</small>
        </div>
      </a>
      <button type="button" class="btn btn-sm btn-outline-light d-lg-none" onclick="toggleAdminSidebar(false)">&times;</button>
    </div>

    <a href="?tab=dashboard" class="menu-link <?= nav_active('dashboard', $tab) ?>"><span><i class="fa-solid fa-gauge me-2"></i>অ্যাডমিন ড্যাশবোর্ড</span></a>

    <!-- 📦 পণ্য ব্যবস্থাপনা (Tree) -->
    <div class="tree-group <?= in_array($tab, $prodTabs, true) ? 'open' : '' ?>">
      <button type="button" class="tree-trigger" onclick="this.parentElement.classList.toggle('open')">
        <span>📦 পণ্য ব্যবস্থাপনা</span><i class="fa-solid fa-play arrow"></i>
      </button>
      <div class="tree-children">
        <a href="?tab=products" class="menu-link <?= nav_active('products', $tab) ?>"><span>সকল পণ্য তালিকা</span><span class="cnt-badge"><?= $cnt['products'] ?></span></a>
        <a href="?tab=add_product" class="menu-link <?= nav_active('add_product', $tab) ?>"><span>+ নতুন পণ্য যোগ</span></a>
        <a href="?tab=categories" class="menu-link <?= nav_active('categories', $tab) ?>"><span>🗂️ ক্যাটাগরি তালিকা</span><span class="cnt-badge"><?= $cnt['categories'] ?></span></a>
        <a href="?tab=brands" class="menu-link <?= nav_active('brands', $tab) ?>"><span>🏷️ ব্র্যান্ড সমূহ</span><span class="cnt-badge"><?= $cnt['brands'] ?></span></a>
        <a href="?tab=variants" class="menu-link <?= nav_active('variants', $tab) ?>"><span>🎨 কালার ও সাইজ</span><span class="cnt-badge"><?= $cnt['variants'] ?></span></a>
        <a href="?tab=banners" class="menu-link <?= nav_active('banners', $tab) ?>"><span>🖼️ ব্যানার CMS</span><span class="cnt-badge"><?= $cnt['banners'] ?></span></a>
      </div>
    </div>

    <!-- 📋 অর্ডার ও কাস্টমার (Tree) -->
    <div class="tree-group <?= in_array($tab, $orderTabs, true) ? 'open' : '' ?>">
      <button type="button" class="tree-trigger" onclick="this.parentElement.classList.toggle('open')">
        <span>📋 অর্ডার ও কাস্টমার</span><i class="fa-solid fa-play arrow"></i>
      </button>
      <div class="tree-children">
        <a href="?tab=orders" class="menu-link <?= nav_active('orders', $tab) ?>"><span>সকল অর্ডার তালিকা</span><span class="cnt-badge"><?= $cnt['orders'] ?></span></a>
        <a href="?tab=orders_incomplete" class="menu-link <?= nav_active('orders_incomplete', $tab) ?>"><span>⚠️ অসম্পূর্ণ অর্ডার</span><span class="cnt-badge"><?= $cnt['incomplete'] ?></span></a>
        <a href="?tab=orders_delivered" class="menu-link <?= nav_active('orders_delivered', $tab) ?>"><span>✓ সফল ডেলিভারি</span><span class="cnt-badge"><?= $cnt['delivered'] ?></span></a>
        <a href="?tab=orders_courier" class="menu-link <?= nav_active('orders_courier', $tab) ?>"><span>🚚 কুরিয়ারে / শিপিং</span><span class="cnt-badge"><?= $cnt['courier'] ?></span></a>
        <a href="?tab=orders_cancelled" class="menu-link <?= nav_active('orders_cancelled', $tab) ?>"><span>✕ বাতিল ও রিটার্ন</span><span class="cnt-badge"><?= $cnt['cancelled'] ?></span></a>
        <a href="?tab=add_order" class="menu-link <?= nav_active('add_order', $tab) ?>"><span>+ নতুন অর্ডার এন্ট্রি</span></a>
        <a href="?tab=live_visitors" class="menu-link <?= nav_active('live_visitors', $tab) ?>"><span>🟢 লাইভ ভিজিটর</span><span class="cnt-badge"><?= $cnt['visitors'] ?></span></a>
        <a href="?tab=customers" class="menu-link <?= nav_active('customers', $tab) ?>"><span>👥 কাস্টমার ডাটাবেজ</span><span class="cnt-badge"><?= $cnt['customers'] ?></span></a>
        <a href="?tab=reviews" class="menu-link <?= nav_active('reviews', $tab) ?>"><span>⭐ রিভিউ ও রেটিং</span><span class="cnt-badge"><?= $cnt['reviews'] ?></span></a>
      </div>
    </div>

    <!-- 👥 টিম ও অ্যাডমিন (Tree) -->
    <div class="tree-group <?= in_array($tab, $teamTabs, true) ? 'open' : '' ?>">
      <button type="button" class="tree-trigger" onclick="this.parentElement.classList.toggle('open')">
        <span>👥 টিম ও অ্যাডমিন</span><i class="fa-solid fa-play arrow"></i>
      </button>
      <div class="tree-children">
        <a href="?tab=master_admin" class="menu-link <?= nav_active('master_admin', $tab) ?>"><span>মাস্টার অ্যাডমিন</span><span class="cnt-badge"><?= $cnt['master_admin'] ?></span></a>
        <a href="?tab=staff" class="menu-link <?= nav_active('staff', $tab) ?>"><span>কর্মী ও স্টাফ টিম</span><span class="cnt-badge"><?= $cnt['staff'] ?></span></a>
      </div>
    </div>

    <!-- 📈 রিপোর্ট সেন্টার -->
    <div class="tree-group">
      <a href="?tab=reports" class="menu-link <?= nav_active('reports', $tab) ?>"><span>📈 রিপোর্ট সেন্টার</span></a>
    </div>

    <!-- 🚚 কুরিয়ার (#18) -->
    <div class="tree-group">
      <a href="?tab=couriers" class="menu-link <?= nav_active('couriers', $tab) ?>"><span>🚚 সংযুক্ত কুরিয়ারসমূহ</span><span class="cnt-badge"><?= $cnt['couriers'] ?></span></a>
    </div>

    <!-- ⚙️ সিস্টেম সেটিংস (Tree) -->
    <div class="tree-group <?= in_array($tab, $settingTabs, true) ? 'open' : '' ?>">
      <button type="button" class="tree-trigger" onclick="this.parentElement.classList.toggle('open')">
        <span>⚙️ সিস্টেম সেটিংস</span><i class="fa-solid fa-play arrow"></i>
      </button>
      <div class="tree-children">
        <a href="?tab=audit_logs" class="menu-link <?= nav_active('audit_logs', $tab) ?>"><span>অডিট ও কর্মী লগ</span></a>
        <a href="?tab=payment_methods" class="menu-link <?= nav_active('payment_methods', $tab) ?>"><span>পেমেন্ট মেথড আপডেট</span></a>
        <a href="?tab=delivery_methods" class="menu-link <?= nav_active('delivery_methods', $tab) ?>"><span>ডেলিভারি মেথড কনফিগ</span></a>
        <a href="?tab=coupons" class="menu-link <?= nav_active('coupons', $tab) ?>"><span>অফার ও কুপন ডিসকাউন্ট</span></a>
        <a href="?tab=site_settings" class="menu-link <?= nav_active('site_settings', $tab) ?>"><span>ওয়েবসাইট তথ্য আপডেট</span></a>
        <a href="?tab=profile" class="menu-link <?= nav_active('profile', $tab) ?>"><span>প্রোফাইল আপডেট</span></a>
        <a href="?tab=password" class="menu-link <?= nav_active('password', $tab) ?>"><span>পাসওয়ার্ড পরিবর্তন</span></a>
      </div>
    </div>
  </aside>

  <!-- Main Workspace -->
  <div class="admin-main">
    <div class="admin-topbar">
      <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-sm btn-danger d-lg-none" onclick="toggleAdminSidebar(true)"><i class="fa-solid fa-bars"></i></button>
        <h5 class="fw-bold mb-0 text-danger">🎂 <?= e(setting('site_name', 'SweetDream Cakes')) ?> — অ্যাডমিন প্যানেল</h5>
      </div>
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <button type="button" class="btn btn-sm btn-warning rounded-pill fw-bold" onclick="enableMobileNotifications()">
          <i class="fa-solid fa-volume-high me-1"></i>ভয়েস অর্ডার অ্যালার্ট (<span class="js-notif-count"><?= $cnt['unread_notif'] ?></span>)
        </button>
        <a href="../index.php" target="_blank" class="btn btn-sm btn-outline-danger rounded-pill fw-bold"><i class="fa-solid fa-globe me-1"></i>লাইভ সাইট</a>
        <a href="../logout.php" class="btn btn-sm btn-dark rounded-pill">লগআউট</a>
      </div>
    </div>

    <?php if ($adminFlash): ?><div class="alert alert-success rounded-4 shadow-sm"><?= e($adminFlash) ?></div><?php endif; ?>
    <?php if ($adminError): ?><div class="alert alert-danger rounded-4 shadow-sm"><?= e($adminError) ?></div><?php endif; ?>

    <?php
    if (in_array($tab, $prodTabs, true)) require __DIR__ . '/views_products.php';
    elseif (in_array($tab, array_merge(['dashboard'], $orderTabs), true)) require __DIR__ . '/views_orders.php';
    else require __DIR__ . '/views_extra.php';
    ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app.js"></script>
<script>
function toggleAdminSidebar(open) {
  document.getElementById('adminSidebar').classList.toggle('open', open);
  document.getElementById('sidebarBackdrop').classList.toggle('open', open);
}
</script>
</body>
</html>
