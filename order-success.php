<?php
$pageTitle = 'অর্ডার সফল হয়েছে!';
require_once __DIR__ . '/includes/header.php';
$code = trim($_GET['code'] ?? '');
$stmt = db()->prepare("SELECT * FROM orders WHERE order_code = ?");
$stmt->execute([$code]);
$order = $stmt->fetch();
$emailPreview = $_SESSION['last_order_email_preview'] ?? ('storage/emails/order_' . preg_replace('/[^A-Za-z0-9_-]/', '', $code) . '.html');
?>

<div class="container py-5">
  <div class="bg-white p-4 p-md-5 rounded-4 border shadow-lg text-center mx-auto" style="max-width:720px;">
    <div class="display-1 text-success mb-2"><i class="fa-solid fa-circle-check"></i></div>
    <span class="badge bg-success-subtle text-success border border-success rounded-pill px-3 py-2 mb-2">🔔 অর্ডার কনফার্মেশন ও ডিজিটাল ইনভয়েস পাঠানো হয়েছে</span>
    <h2 class="fw-bold text-danger mb-2">ধন্যবাদ! আপনার কেক অর্ডার কনফার্ম হয়েছে</h2>
    <?php if ($order): ?>
      <p class="lead mb-4">অর্ডার নম্বর: <strong class="text-dark">#<?= e($order['order_code']) ?></strong> | সর্বমোট: <strong class="text-danger"><?= format_tk((float)$order['total']) ?></strong></p>
      <div class="p-3 rounded-4 bg-light border text-start small mb-4">
        <div class="mb-1"><strong>কাস্টমার:</strong> <?= e($order['customer_name']) ?> (<?= e($order['customer_phone']) ?>)</div>
        <div class="mb-1"><strong>ঠিকানা:</strong> <?= e($order['shipping_address']) ?></div>
        <div class="mb-1"><strong>পেমেন্ট মেথড:</strong> <?= e($order['payment_method']) ?></div>
        <?php if (!empty($order['trx_id'])): ?>
          <div><strong>Transaction ID:</strong> <span class="text-success fw-bold"><?= e($order['trx_id']) ?></span></div>
        <?php endif; ?>
      </div>
      <div class="d-flex flex-wrap justify-content-center gap-2">
        <a href="voucher.php?code=<?= e($order['order_code']) ?>" target="_blank" class="btn btn-danger rounded-pill px-4 fw-bold">
          <i class="fa-solid fa-print me-2"></i>ভাউচার প্রিন্ট করুন
        </a>
        <a href="tracking.php?code=<?= e($order['order_code']) ?>" class="btn btn-outline-danger rounded-pill px-4 fw-bold">
          <i class="fa-solid fa-truck-fast me-2"></i>অর্ডার ট্র্যাক করুন
        </a>
        <?php if (file_exists(APP_ROOT . '/' . $emailPreview)): ?>
          <a href="<?= e(base_url($emailPreview)) ?>" target="_blank" class="btn btn-warning rounded-pill px-4 fw-bold">
            <i class="fa-solid fa-envelope-open-text me-2"></i>ডিজিটাল ইনভয়েস
          </a>
        <?php endif; ?>
        <a href="products.php" class="btn btn-outline-secondary rounded-pill px-4">আরও কেক দেখুন</a>
      </div>
    <?php else: ?>
      <p class="text-muted">অর্ডারটি খুঁজে পাওয়া যায়নি।</p>
      <a href="index.php" class="btn btn-danger rounded-pill">হোম পেজে যান</a>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
