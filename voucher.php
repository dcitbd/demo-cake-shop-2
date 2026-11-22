<?php
require_once __DIR__ . '/includes/functions.php';
$code = trim($_GET['code'] ?? '');
$stmt = db()->prepare("SELECT * FROM orders WHERE order_code = ?");
$stmt->execute([$code]);
$order = $stmt->fetch();
if (!$order) {
    $order = db()->query("SELECT * FROM orders ORDER BY id DESC LIMIT 1")->fetch();
}
$items = json_decode($order['items_json'] ?? '[]', true) ?: [];
$mode = ($_GET['mode'] ?? 'a4') === 'pos' ? 'pos' : 'a4';
?>
<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ভাউচার #<?= e($order['order_code'] ?? '') ?> | <?= e(setting('site_name')) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { font-family: 'Kalpurush', sans-serif; background: #fff1f2; padding: 24px; }
    .voucher-sheet { background: #fff; margin: 0 auto; border-radius: 16px; box-shadow: 0 12px 30px rgba(0,0,0,0.1); padding: 28px; max-width: <?= $mode === 'pos' ? '340px' : '780px' ?>; border: 2px solid #fecdd3; }
    @media print {
      body { background: #fff; padding: 0; }
      .no-print { display: none !important; }
      .voucher-sheet { box-shadow: none; border: none; max-width: 100%; }
    }
  </style>
  <link href="https://fonts.maateen.me/kalpurush/font.css" rel="stylesheet">
</head>
<body>
  <div class="text-center mb-3 no-print d-flex justify-content-center gap-2 flex-wrap">
    <button onclick="window.print()" class="btn btn-danger rounded-pill fw-bold px-4">🖨️ প্রিন্ট করুন</button>
    <a href="?code=<?= e($order['order_code']) ?>&mode=a4" class="btn btn-outline-danger rounded-pill">A4 ইনভয়েস</a>
    <a href="?code=<?= e($order['order_code']) ?>&mode=pos" class="btn btn-outline-dark rounded-pill">POS থার্মাল (80mm)</a>
    <a href="index.php" class="btn btn-secondary rounded-pill">হোম</a>
  </div>

  <div class="voucher-sheet">
    <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3 flex-wrap gap-2">
      <div class="d-flex align-items-center gap-2">
        <img src="<?= e(base_url('assets/images/icon-192.png')) ?>" width="52" height="52" class="rounded-3">
        <div>
          <h4 class="fw-bold text-danger mb-0"><?= e(setting('site_name')) ?></h4>
          <div class="small text-muted"><?= e(setting('phone')) ?> | <?= e(setting('email')) ?></div>
          <div class="small text-muted"><?= e(setting('address')) ?></div>
        </div>
      </div>
      <div class="text-end">
        <span class="badge bg-danger fs-6 px-3 py-2">INVOICE #<?= e($order['order_code']) ?></span>
        <div class="small text-muted mt-1">তারিখ: <?= e($order['created_at']) ?></div>
        <div class="small fw-bold text-uppercase">স্ট্যাটাস: <?= e($order['order_status']) ?></div>
      </div>
    </div>

    <div class="row g-3 mb-3 small">
      <div class="col-md-6">
        <div class="p-2 rounded bg-light border">
          <strong>কাস্টমার:</strong> <?= e($order['customer_name']) ?><br>
          <strong>ফোন:</strong> <?= e($order['customer_phone']) ?><br>
          <strong>ঠিকানা:</strong> <?= e($order['shipping_address']) ?>
        </div>
      </div>
      <div class="col-md-6">
        <div class="p-2 rounded bg-light border">
          <strong>পেমেন্ট মেথড:</strong> <?= e($order['payment_method']) ?><br>
          <strong>পেমেন্ট স্ট্যাটাস:</strong> <?= e(strtoupper($order['payment_status'])) ?><br>
          <strong>কুরিয়ার:</strong> <?= e($order['courier_name'] ?: 'Assigned Soon') ?> <?= $order['tracking_code'] ? '(' . e($order['tracking_code']) . ')' : '' ?>
        </div>
      </div>
    </div>

    <table class="table table-bordered table-sm align-middle mb-3">
      <thead class="table-danger">
        <tr>
          <th>কেকের বিবরণ</th>
          <th class="text-center">পরিমাণ</th>
          <th class="text-end">দর</th>
          <th class="text-end">মোট</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td><strong><?= e($it['name']) ?></strong><br><small class="text-muted"><?= e($it['variant'] ?? '') ?></small></td>
            <td class="text-center"><?= (int)$it['qty'] ?></td>
            <td class="text-end"><?= format_tk((float)$it['price']) ?></td>
            <td class="text-end fw-bold"><?= format_tk((float)$it['price'] * (int)$it['qty']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="row justify-content-end">
      <div class="col-md-6">
        <table class="table table-sm mb-0">
          <tr><td>সাবটোটাল</td><td class="text-end fw-bold"><?= format_tk((float)$order['subtotal']) ?></td></tr>
          <tr><td>ডেলিভারি চার্জ</td><td class="text-end fw-bold"><?= format_tk((float)$order['delivery_charge']) ?></td></tr>
          <tr><td>ডিসকাউন্ট</td><td class="text-end text-success fw-bold">-<?= format_tk((float)$order['discount']) ?></td></tr>
          <tr class="table-danger fs-5"><th>সর্বমোট</th><th class="text-end"><?= format_tk((float)$order['total']) ?></th></tr>
        </table>
      </div>
    </div>

    <div class="text-center small text-muted mt-4 pt-3 border-top">
      ধন্যবাদ! আমাদের সাথে থাকার জন্য কৃতজ্ঞতা। যেকোনো প্রয়োজনে কল করুন: <?= e(setting('phone')) ?>
    </div>
  </div>
</body>
</html>
