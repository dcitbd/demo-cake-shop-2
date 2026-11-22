<?php
$pageTitle = 'লাইভ অর্ডার ট্র্যাকিং';
require_once __DIR__ . '/includes/header.php';
$q = trim($_GET['code'] ?? '');
$order = null;
if ($q !== '') {
    $stmt = db()->prepare("SELECT * FROM orders WHERE order_code = ? OR customer_phone = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$q, $q]);
    $order = $stmt->fetch();
}
$steps = [
    'pending' => ['অর্ডার রিসিভড', 'fa-receipt', 1],
    'confirmed' => ['কনফার্ম ও বেকিং', 'fa-fire-burner', 2],
    'courier' => ['কুরিয়ারে / শিপিং', 'fa-truck-fast', 3],
    'delivered' => ['সফল ডেলিভারি', 'fa-circle-check', 4],
];
$currStep = $order ? ($steps[$order['order_status']][2] ?? 1) : 0;
?>

<div class="container py-5">
  <div class="bg-white p-4 p-md-5 rounded-4 border shadow-sm mx-auto" style="max-width:780px;">
    <h2 class="fw-bold text-center text-danger mb-2"><i class="fa-solid fa-location-crosshairs me-2"></i>লাইভ অর্ডার ট্র্যাকিং</h2>
    <p class="text-center text-muted small mb-4">আপনার অর্ডার নম্বর অথবা মোবাইল নম্বর দিয়ে অর্ডারের বর্তমান অবস্থান জানুন।</p>

    <form method="get" class="input-group input-group-lg mb-4">
      <input type="text" name="code" class="form-control rounded-start-pill" placeholder="অর্ডার নম্বর বা মোবাইল নম্বর লিখুন" value="<?= e($q) ?>" required>
      <button class="btn btn-danger rounded-end-pill px-4 fw-bold" type="submit">ট্র্যাক করুন</button>
    </form>

    <?php if ($q !== '' && !$order): ?>
      <div class="alert alert-warning rounded-4 text-center">এই নম্বর দিয়ে কোনো অর্ডার পাওয়া যায়নি।</div>
    <?php elseif ($order): ?>
      <div class="p-4 rounded-4 bg-light border mb-4">
        <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
          <div>
            <h5 class="fw-bold mb-0">অর্ডার #<?= e($order['order_code']) ?></h5>
            <small class="text-muted">কাস্টমার: <?= e($order['customer_name']) ?> | তারিখ: <?= e($order['created_at']) ?></small>
          </div>
          <a href="voucher.php?code=<?= e($order['order_code']) ?>" target="_blank" class="btn btn-sm btn-outline-danger rounded-pill fw-bold">ভাউচার দেখুন</a>
        </div>
        <div class="row g-2 text-center my-4">
          <?php foreach ($steps as $k => [$label, $icon, $num]):
            $done = $currStep >= $num && !in_array($order['order_status'], ['cancelled', 'returned'], true);
          ?>
            <div class="col-3">
              <div class="mx-auto mb-2 rounded-circle d-flex align-items-center justify-content-center <?= $done ? 'bg-success text-white' : 'bg-secondary-subtle text-muted' ?>" style="width:52px;height:52px;"><i class="fa-solid <?= $icon ?> fs-5"></i></div>
              <div class="small fw-bold <?= $done ? 'text-success' : 'text-muted' ?>"><?= $label ?></div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="small">
          <strong>কুরিয়ার:</strong> <?= e($order['courier_name'] ?: 'প্রস্তুত হচ্ছে') ?> |
          <strong>ট্র্যাকিং কোড:</strong> <?= e($order['tracking_code'] ?: 'N/A') ?> |
          <strong>মোট বিল:</strong> <span class="text-danger fw-bold"><?= format_tk((float)$order['total']) ?></span>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
