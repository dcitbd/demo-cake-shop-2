<?php
require_once dirname(__DIR__) . '/includes/functions.php';
$u = current_user();
if (!$u) {
    header('Location: ../login.php?redirect=customer/dashboard.php');
    exit;
}

$pdo = db();
$tab = $_GET['tab'] ?? 'overview';
$msg = '';
$errMsg = '';

// Profile & Secure Password Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['form_type'] ?? '') === 'profile') {
        $pdo->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?")
            ->execute([trim($_POST['name']), trim($_POST['phone']), trim($_POST['address']), $u['id']]);
        $msg = 'প্রোফাইল সফলভাবে আপডেট হয়েছে!';
        $u = db()->query("SELECT * FROM users WHERE id = " . (int)$u['id'])->fetch();
    } elseif (($_POST['form_type'] ?? '') === 'password') {
        $oldP = $_POST['old_password'] ?? '';
        $np = $_POST['new_password'] ?? '';
        if (!verify_user_password($oldP, $u['password_hash'])) {
            $errMsg = 'পুরাতন পাসওয়ার্ড সঠিক নয়!';
        } elseif (strlen($np) < 6) {
            $errMsg = 'নতুন পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।';
        } else {
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([password_hash($np, PASSWORD_DEFAULT), $u['id']]);
            $msg = 'পাসওয়ার্ড সফলভাবে পরিবর্তন হয়েছে!';
        }
    }
}

// Fetch Orders, Favorites, Cart
$oStmt = $pdo->prepare("SELECT * FROM orders WHERE customer_id = ? OR (customer_phone != '' AND customer_phone = ?) ORDER BY id DESC");
$oStmt->execute([$u['id'], $u['phone']]);
$myOrders = $oStmt->fetchAll();

$fStmt = $pdo->prepare("SELECT p.*, b.name AS brand_name, c.name AS cat_name FROM favorites f JOIN products p ON p.id = f.product_id LEFT JOIN brands b ON b.id = p.brand_id LEFT JOIN categories c ON c.id = p.category_id WHERE f.user_id = ? ORDER BY f.id DESC");
$fStmt->execute([$u['id']]);
$myFavs = $fStmt->fetchAll();

$myCart = get_cart();

$pageTitle = 'কাস্টমার ড্যাশবোর্ড';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="container py-4">
  <div class="row g-4">
    <!-- Customer Sidebar -->
    <div class="col-lg-3">
      <div class="bg-white p-3 rounded-4 border shadow-sm">
        <div class="text-center pb-3 mb-3 border-bottom">
          <div class="rounded-circle bg-danger text-white d-inline-flex align-items-center justify-content-center fs-3 fw-bold mb-2" style="width:64px;height:64px;">
            <?= e(mb_substr($u['name'], 0, 1)) ?>
          </div>
          <h6 class="fw-bold mb-0"><?= e($u['name']) ?></h6>
          <small class="text-muted"><?= e($u['email']) ?></small>
        </div>
        <div class="list-group list-group-flush small fw-bold">
          <a href="?tab=overview" class="list-group-item list-group-item-action rounded-3 <?= $tab === 'overview' ? 'active bg-danger border-danger' : '' ?>"><i class="fa-solid fa-gauge me-2"></i>ড্যাশবোর্ড</a>
          <a href="?tab=orders" class="list-group-item list-group-item-action rounded-3 <?= $tab === 'orders' ? 'active bg-danger border-danger' : '' ?>"><i class="fa-solid fa-box me-2"></i>অর্ডার লিস্ট <span class="badge bg-warning text-dark float-end"><?= count($myOrders) ?></span></a>
          <a href="?tab=favorites" class="list-group-item list-group-item-action rounded-3 <?= $tab === 'favorites' ? 'active bg-danger border-danger' : '' ?>"><i class="fa-solid fa-heart me-2"></i>ফেভরিট লিস্ট <span class="badge bg-warning text-dark float-end"><?= count($myFavs) ?></span></a>
          <a href="?tab=cart" class="list-group-item list-group-item-action rounded-3 <?= $tab === 'cart' ? 'active bg-danger border-danger' : '' ?>"><i class="fa-solid fa-cart-shopping me-2"></i>কার্ট লিস্ট <span class="badge bg-warning text-dark float-end"><?= count($myCart) ?></span></a>
          <a href="?tab=profile" class="list-group-item list-group-item-action rounded-3 <?= $tab === 'profile' ? 'active bg-danger border-danger' : '' ?>"><i class="fa-solid fa-user-pen me-2"></i>প্রোফাইল</a>
          <a href="?tab=settings" class="list-group-item list-group-item-action rounded-3 <?= $tab === 'settings' ? 'active bg-danger border-danger' : '' ?>"><i class="fa-solid fa-gear me-2"></i>সেটিংস</a>
          <?php if (is_admin()): ?>
            <a href="../admin/index.php" class="list-group-item list-group-item-action rounded-3 text-primary"><i class="fa-solid fa-shield-halved me-2"></i>অ্যাডমিন প্যানেল</a>
          <?php endif; ?>
          <a href="../logout.php" class="list-group-item list-group-item-action rounded-3 text-danger"><i class="fa-solid fa-right-from-bracket me-2"></i>লগআউট</a>
        </div>
      </div>
    </div>

    <!-- Main Dashboard Content -->
    <div class="col-lg-9">
      <?php if ($msg): ?><div class="alert alert-success rounded-4"><?= e($msg) ?></div><?php endif; ?>
      <?php if ($errMsg): ?><div class="alert alert-danger rounded-4"><?= e($errMsg) ?></div><?php endif; ?>

      <?php if ($tab === 'overview'): ?>
        <div class="row g-3 mb-4">
          <div class="col-md-4"><div class="benefit-3d-card"><div class="small text-muted">মোট অর্ডার</div><div class="fs-2 fw-bold text-danger"><?= count($myOrders) ?></div></div></div>
          <div class="col-md-4"><div class="benefit-3d-card"><div class="small text-muted">ফেভরিট কেক</div><div class="fs-2 fw-bold text-warning"><?= count($myFavs) ?></div></div></div>
          <div class="col-md-4"><div class="benefit-3d-card"><div class="small text-muted">কার্ট আইটেম</div><div class="fs-2 fw-bold text-success"><?= cart_count() ?></div></div></div>
        </div>
        <div class="bg-white p-4 rounded-4 border shadow-sm">
          <h5 class="fw-bold mb-3">সাম্প্রতিক অর্ডারসমূহ</h5>
          <p class="small text-muted">আপনার সকল অর্ডার, ফেভরিট এবং কার্ট লিস্টে ডিজিটাল কাউন্টার ফিল্টার ও CSV ডাউনলোড সুবিধা রয়েছে।</p>
          <a href="?tab=orders" class="btn btn-danger rounded-pill fw-bold btn-sm">অর্ডার লিস্ট দেখুন</a>
        </div>

      <?php elseif ($tab === 'orders'): ?>
        <div class="bg-white p-4 rounded-4 border shadow-sm">
          <h5 class="fw-bold mb-3"><i class="fa-solid fa-box text-danger me-2"></i>আমার অর্ডার লিস্ট</h5>
          <div class="digital-filter-bar">
            <div class="d-flex flex-wrap gap-2 align-items-center">
              <span class="digital-counter-pill"><i class="fa-solid fa-hashtag"></i>কাউন্টার: <span id="custOrderCnt"><?= count($myOrders) ?> / <?= count($myOrders) ?></span></span>
              <input type="text" id="custOrderSearch" class="form-control form-control-sm" style="width:200px;" placeholder="অর্ডার খুঁজুন..." oninput="filterDigitalTable('custOrderTbl','custOrderSearch','custOrderStatus','custOrderCnt')">
              <select id="custOrderStatus" class="form-select form-select-sm" style="width:150px;" onchange="filterDigitalTable('custOrderTbl','custOrderSearch','custOrderStatus','custOrderCnt')">
                <option value="">সকল স্ট্যাটাস</option>
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="courier">Courier</option>
                <option value="delivered">Delivered</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            <button type="button" class="btn btn-sm btn-success rounded-pill fw-bold" onclick="exportTableToCSV('custOrderTbl','my-orders.csv')"><i class="fa-solid fa-file-csv me-1"></i>CSV ডাউনলোড</button>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle small" id="custOrderTbl">
              <thead class="table-light">
                <tr><th>অর্ডার কোড</th><th>তারিখ</th><th>পেমেন্ট</th><th>মোট বিল</th><th>স্ট্যাটাস</th><th class="no-csv">অ্যাকশন</th></tr>
              </thead>
              <tbody>
                <?php foreach ($myOrders as $o): ?>
                  <tr data-status="<?= e($o['order_status']) ?>">
                    <td class="fw-bold text-danger">#<?= e($o['order_code']) ?></td>
                    <td><?= e($o['created_at']) ?></td>
                    <td><?= e($o['payment_method']) ?></td>
                    <td class="fw-bold"><?= format_tk((float)$o['total']) ?></td>
                    <td><span class="badge bg-secondary text-uppercase"><?= e($o['order_status']) ?></span></td>
                    <td class="no-csv">
                      <a href="../voucher.php?code=<?= e($o['order_code']) ?>" target="_blank" class="btn btn-xs btn-outline-danger btn-sm py-0">ভাউচার</a>
                      <a href="../tracking.php?code=<?= e($o['order_code']) ?>" class="btn btn-xs btn-outline-primary btn-sm py-0">ট্র্যাক</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

      <?php elseif ($tab === 'favorites'): ?>
        <div class="bg-white p-4 rounded-4 border shadow-sm">
          <h5 class="fw-bold mb-3"><i class="fa-solid fa-heart text-danger me-2"></i>আমার ফেভরিট লিস্ট</h5>
          <div class="digital-filter-bar">
            <div class="d-flex flex-wrap gap-2 align-items-center">
              <span class="digital-counter-pill"><i class="fa-solid fa-hashtag"></i>কাউন্টার: <span id="custFavCnt"><?= count($myFavs) ?> / <?= count($myFavs) ?></span></span>
              <input type="text" id="custFavSearch" class="form-control form-control-sm" style="width:220px;" placeholder="কেক বা ব্র্যান্ড খুঁজুন..." oninput="filterDigitalTable('custFavTbl','custFavSearch','','custFavCnt')">
            </div>
            <button type="button" class="btn btn-sm btn-success rounded-pill fw-bold" onclick="exportTableToCSV('custFavTbl','my-favorites.csv')"><i class="fa-solid fa-file-csv me-1"></i>CSV ডাউনলোড</button>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle small" id="custFavTbl">
              <thead class="table-light"><tr><th>SKU</th><th>কেকের নাম</th><th>ব্র্যান্ড</th><th>ডিসকাউন্ট মূল্য</th><th class="no-csv">অ্যাকশন</th></tr></thead>
              <tbody>
                <?php foreach ($myFavs as $fp): ?>
                  <tr>
                    <td><?= e($fp['sku']) ?></td>
                    <td class="fw-bold"><a href="../product.php?slug=<?= e($fp['slug']) ?>"><?= e($fp['name']) ?></a></td>
                    <td><?= e($fp['brand_name']) ?></td>
                    <td class="fw-bold text-danger"><?= format_tk((float)$fp['discount_price']) ?></td>
                    <td class="no-csv"><button class="btn btn-sm btn-danger py-0" onclick="addToCart(<?= (int)$fp['id'] ?>,1)">কার্টে নিন</button></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

      <?php elseif ($tab === 'cart'): ?>
        <div class="bg-white p-4 rounded-4 border shadow-sm">
          <h5 class="fw-bold mb-3"><i class="fa-solid fa-cart-shopping text-danger me-2"></i>আমার কার্ট লিস্ট</h5>
          <div class="digital-filter-bar">
            <div class="d-flex flex-wrap gap-2 align-items-center">
              <span class="digital-counter-pill"><i class="fa-solid fa-hashtag"></i>কাউন্টার: <span id="custCartCnt"><?= count($myCart) ?> / <?= count($myCart) ?></span></span>
              <input type="text" id="custCartSearch" class="form-control form-control-sm" style="width:220px;" placeholder="কার্টে খুঁজুন..." oninput="filterDigitalTable('custCartTbl','custCartSearch','','custCartCnt')">
            </div>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-sm btn-success rounded-pill fw-bold" onclick="exportTableToCSV('custCartTbl','my-cart.csv')"><i class="fa-solid fa-file-csv me-1"></i>CSV ডাউনলোড</button>
              <a href="../checkout.php" class="btn btn-sm btn-danger rounded-pill fw-bold">চেকআউট</a>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle small" id="custCartTbl">
              <thead class="table-light"><tr><th>SKU</th><th>কেক</th><th>ভ্যারিয়েন্ট</th><th>পরিমাণ</th><th>মোট</th></tr></thead>
              <tbody>
                <?php foreach ($myCart as $ci): ?>
                  <tr>
                    <td><?= e($ci['sku']) ?></td>
                    <td class="fw-bold"><?= e($ci['name']) ?></td>
                    <td><?= e($ci['variant']) ?></td>
                    <td><?= (int)$ci['qty'] ?></td>
                    <td class="fw-bold text-danger"><?= format_tk((float)$ci['price'] * (int)$ci['qty']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

      <?php elseif ($tab === 'profile'): ?>
        <div class="bg-white p-4 rounded-4 border shadow-sm">
          <h5 class="fw-bold mb-3">প্রোফাইল আপডেট</h5>
          <form method="post" class="row g-3">
            <input type="hidden" name="form_type" value="profile">
            <div class="col-md-6"><label class="form-label small fw-bold">পূর্ণ নাম</label><input type="text" name="name" class="form-control" value="<?= e($u['name']) ?>" required></div>
            <div class="col-md-6"><label class="form-label small fw-bold">মোবাইল নম্বর</label><input type="text" name="phone" class="form-control" value="<?= e($u['phone']) ?>"></div>
            <div class="col-12"><label class="form-label small fw-bold">ঠিকানা</label><textarea name="address" class="form-control" rows="2"><?= e($u['address']) ?></textarea></div>
            <div class="col-12"><button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">সেভ করুন</button></div>
          </form>
        </div>

      <?php elseif ($tab === 'settings'): ?>
        <div class="bg-white p-4 rounded-4 border shadow-sm">
          <h5 class="fw-bold mb-3">অ্যাকাউন্ট ও নোটিফিকেশন সেটিংস</h5>
          <form method="post" class="row g-3 mb-4">
            <input type="hidden" name="form_type" value="password">
            <div class="col-md-6"><label class="form-label small fw-bold">পুরাতন পাসওয়ার্ড *</label><input type="password" name="old_password" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label small fw-bold">নতুন পাসওয়ার্ড (কমপক্ষে ৬ অক্ষর) *</label><input type="password" name="new_password" class="form-control" minlength="6" required></div>
            <div class="col-12"><button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">পাসওয়ার্ড পরিবর্তন করুন</button></div>
          </form>
          <hr>
          <button type="button" class="btn btn-warning rounded-pill fw-bold" onclick="enableMobileNotifications()"><i class="fa-solid fa-bell me-2"></i>মোবাইল ও ব্রাউজার নোটিফিকেশন চালু করুন</button>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
