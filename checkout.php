<?php
require_once __DIR__ . '/includes/functions.php';
$pdo = db();
$cart = get_cart();
if (empty($cart) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cart.php');
    exit;
}

$u = current_user();
$delMethods = $pdo->query("SELECT * FROM delivery_methods WHERE status = 1 ORDER BY id ASC")->fetchAll();
$payMethods = $pdo->query("SELECT * FROM payment_methods WHERE status = 1 AND type != 'cod' ORDER BY id ASC")->fetchAll();
$subtotal = cart_subtotal();
$coupon = $_SESSION['applied_coupon'] ?? null;
$discount = $coupon ? (float)$coupon['discount'] : 0;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($cart)) {
    $name = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['customer_phone'] ?? '');
    $email = trim($_POST['customer_email'] ?? '');
    $address = trim($_POST['shipping_address'] ?? '');
    $delId = (int)($_POST['delivery_method_id'] ?? 0);
    $payMethod = trim($_POST['payment_method'] ?? '');
    $trxId = trim($_POST['trx_id'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($name === '' || $phone === '' || $address === '') {
        $error = 'নাম, মোবাইল নম্বর এবং সম্পূর্ণ ঠিকানা পূরণ করুন।';
    } elseif ($payMethod === '' || strlen($trxId) < 4) {
        $error = 'অনলাইন পেমেন্ট মেথড নির্বাচন করে সঠিক Transaction ID দিন।';
    } else {
        $dmStmt = $pdo->prepare("SELECT * FROM delivery_methods WHERE id = ?");
        $dmStmt->execute([$delId]);
        $dm = $dmStmt->fetch();
        $delCharge = $dm ? (float)$dm['charge'] : (float)setting('delivery_inside', '70');
        $area = $dm ? $dm['area'] : 'inside';

        // Re-verify per-customer coupon usage limit (#23)
        $couponCode = '';
        if ($coupon && !empty($coupon['code'])) {
            $cStmt = $pdo->prepare("SELECT * FROM coupons WHERE UPPER(code) = ? AND status = 1");
            $cStmt->execute([strtoupper($coupon['code'])]);
            $cRow = $cStmt->fetch();
            if ($cRow) {
                $custKey = $u ? ('user_' . $u['id']) : ('phone_' . preg_replace('/[^0-9]/', '', $phone));
                $uCnt = $pdo->prepare("SELECT COUNT(*) FROM coupon_usages WHERE UPPER(coupon_code)=? AND customer_key=?");
                $uCnt->execute([strtoupper($cRow['code']), $custKey]);
                $usedCount = (int)$uCnt->fetchColumn();
                $maxUses = max(1, (int)($cRow['max_uses_per_customer'] ?? 1));
                if ($usedCount >= $maxUses) {
                    $discount = 0;
                    unset($_SESSION['applied_coupon']);
                } else {
                    $couponCode = $cRow['code'];
                }
            }
        }

        $total = max(0, $subtotal + $delCharge - $discount);
        $orderCode = 'SDC-' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
        $now = date('Y-m-d H:i:s');
        $itemsArr = array_values($cart);
        $itemsJson = json_encode($itemsArr, JSON_UNESCAPED_UNICODE);

        $pdo->beginTransaction();
        $pdo->prepare("INSERT INTO orders (
            order_code, customer_id, customer_name, customer_phone, customer_email, shipping_address,
            delivery_area, delivery_charge, subtotal, discount, total, payment_method, trx_id, payment_status,
            order_status, coupon_code, notes, items_json, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid', 'pending', ?, ?, ?, ?)")
            ->execute([
                $orderCode, $u ? (int)$u['id'] : 0, $name, $phone, $email, $address,
                $area, $delCharge, $subtotal, $discount, $total, $payMethod, $trxId,
                $couponCode, $notes, $itemsJson, $now
            ]);

        if ($couponCode !== '') {
            $custKey = $u ? ('user_' . $u['id']) : ('phone_' . preg_replace('/[^0-9]/', '', $phone));
            $pdo->prepare("INSERT INTO coupon_usages (coupon_code, customer_key, order_code, used_at) VALUES (?, ?, ?, ?)")
                ->execute([strtoupper($couponCode), $custKey, $orderCode, $now]);
        }

        $updStock = $pdo->prepare("UPDATE products SET stock = MAX(0, stock - ?) WHERE id = ?");
        foreach ($itemsArr as $it) $updStock->execute([(int)$it['qty'], (int)$it['id']]);

        // Write DB notification immediately so logged-in mobile admins get instant voice alert
        $notifTitle = "🎂 নতুন অর্ডার #{$orderCode}!";
        $notifMsg = "আপনার নতুন একটি অর্ডার এসেছে — {$name} ({$phone}) | মোট: " . format_tk($total);
        $pdo->prepare("INSERT INTO notifications (type, title, message, order_code, is_read, created_at) VALUES ('order', ?, ?, ?, 0, ?)")
            ->execute([$notifTitle, $notifMsg, $orderCode, $now]);
        $pdo->commit();

        $orderData = [
            'order_code' => $orderCode, 'customer_name' => $name, 'customer_phone' => $phone,
            'customer_email' => $email, 'shipping_address' => $address, 'delivery_charge' => $delCharge,
            'subtotal' => $subtotal, 'discount' => $discount, 'total' => $total,
            'payment_method' => $payMethod, 'trx_id' => $trxId, 'order_status' => 'pending', 'created_at' => $now,
        ];

        $relEmail = 'storage/emails/order_' . preg_replace('/[^A-Za-z0-9_-]/', '', $orderCode) . '.html';
        $_SESSION['last_order_email_preview'] = $relEmail;
        unset($_SESSION['cart'], $_SESSION['applied_coupon']);
        session_write_close();

        // Ultra-fast instant redirect; finish email + external push asynchronously after response flush
        header('Location: order-success.php?code=' . urlencode($orderCode));
        header('Connection: close');
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        send_order_email($orderData, $itemsArr);
        dispatch_order_telegram($orderData);
        log_audit('NEW_ORDER', "নতুন অর্ডার #{$orderCode} — {$name} | {$payMethod} ({$trxId}) — ৳{$total}");
        exit;
    }
}

$pageTitle = 'চেকআউট';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
  <h2 class="fw-bold mb-4"><i class="fa-solid fa-bag-shopping text-danger me-2"></i>চেকআউট</h2>
  <?php if ($error): ?><div class="alert alert-danger rounded-4"><?= e($error) ?></div><?php endif; ?>

  <form method="post" id="checkoutForm" class="row g-4">
    <div class="col-lg-7">
      <div class="bg-white p-4 rounded-4 border shadow-sm">
        <h5 class="fw-bold mb-3 text-danger">১. ডেলিভারি তথ্য</h5>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label small fw-bold">পূর্ণ নাম *</label>
            <input type="text" name="customer_name" class="form-control rounded-3" value="<?= e($u['name'] ?? '') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">মোবাইল নম্বর *</label>
            <input type="tel" name="customer_phone" id="chkPhone" class="form-control rounded-3" value="<?= e($u['phone'] ?? '') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">ইমেইল</label>
            <input type="email" name="customer_email" class="form-control rounded-3" value="<?= e($u['email'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">ডেলিভারি মেথড *</label>
            <select name="delivery_method_id" id="delMethodSel" class="form-select rounded-3" onchange="recalcCheckout()">
              <?php foreach ($delMethods as $dm): ?>
                <option value="<?= (int)$dm['id'] ?>" data-charge="<?= (float)$dm['charge'] ?>"><?= e($dm['name']) ?> (<?= format_tk((float)$dm['charge']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label small fw-bold">সম্পূর্ণ ঠিকানা *</label>
            <textarea name="shipping_address" class="form-control rounded-3" rows="2" required><?= e($u['address'] ?? '') ?></textarea>
          </div>
          <div class="col-12">
            <label class="form-label small fw-bold">কেকের উপর লেখা / নোট (ঐচ্ছিক)</label>
            <input type="text" name="notes" class="form-control rounded-3">
          </div>
        </div>

        <h5 class="fw-bold mt-4 mb-3 text-danger">২. অনলাইন পেমেন্ট নির্বাচন করুন *</h5>
        <div class="row g-2 mb-3">
          <?php foreach ($payMethods as $idx => $pm): ?>
            <div class="col-md-6">
              <label class="border rounded-3 p-3 d-block h-100 bg-light" style="cursor:pointer;">
                <input type="radio" name="payment_method" value="<?= e($pm['name']) ?>" <?= $idx === 0 ? 'checked' : '' ?> class="form-check-input me-2" required>
                <strong><?= e($pm['name']) ?></strong>
                <div class="small text-danger fw-bold mt-1"><?= e($pm['account_number']) ?></div>
                <?php if (!empty($pm['instructions'])): ?>
                  <div class="small text-muted"><?= e($pm['instructions']) ?></div>
                <?php endif; ?>
              </label>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="p-3 rounded-3 border border-danger" style="background:var(--brand-soft);">
          <label class="form-label small fw-bold text-danger mb-1">Transaction ID *</label>
          <input type="text" name="trx_id" id="trxIdInput" class="form-control rounded-3 fw-bold" required minlength="4" placeholder="পেমেন্টের Transaction ID লিখুন">
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="bg-white p-4 rounded-4 border shadow-sm">
        <h5 class="fw-bold mb-3">অর্ডার সামারি (<?= cart_count() ?>)</h5>
        <div class="d-flex flex-column gap-2 mb-3">
          <?php foreach ($cart as $it): ?>
            <div class="d-flex align-items-center gap-2 pb-2 border-bottom">
              <img src="<?= e($it['image']) ?>" width="48" height="48" class="rounded-3 object-fit-cover">
              <div class="flex-grow-1 small">
                <div class="fw-bold"><?= e($it['name']) ?></div>
                <div class="text-muted"><?= e($it['variant']) ?> &times; <?= (int)$it['qty'] ?></div>
              </div>
              <strong class="text-danger"><?= format_tk((float)$it['price'] * (int)$it['qty']) ?></strong>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="input-group input-group-sm mb-3">
          <input type="text" id="chkCouponInput" class="form-control" placeholder="কুপন কোড" value="<?= e($coupon['code'] ?? '') ?>">
          <button type="button" class="btn btn-outline-danger fw-bold" onclick="applyCheckoutCoupon()">প্রয়োগ</button>
        </div>

        <div class="d-flex justify-content-between mb-2 small"><span>সাবটোটাল</span><strong><?= format_tk($subtotal) ?></strong></div>
        <div class="d-flex justify-content-between mb-2 small"><span>ডেলিভারি চার্জ</span><strong id="chkDelCharge">৳0</strong></div>
        <div class="d-flex justify-content-between mb-2 small text-success <?= $discount > 0 ? '' : 'd-none' ?>" id="chkDiscRow"><span>কুপন ছাড়</span><strong id="chkDiscVal">-<?= format_tk($discount) ?></strong></div>
        <hr>
        <div class="d-flex justify-content-between fs-4 fw-bold mb-3"><span>সর্বমোট</span><span class="text-danger" id="chkGrandTotal"><?= format_tk($subtotal) ?></span></div>
        <button type="submit" id="confirmOrderBtn" class="btn btn-order-now btn-lg w-100 rounded-pill py-3 fs-5">
          <i class="fa-solid fa-check-circle me-2"></i>অর্ডার কনফার্ম করুন
        </button>
      </div>
    </div>
  </form>
</div>

<script>
const chkSub = <?= (float)$subtotal ?>;
let chkDisc = <?= (float)$discount ?>;
function recalcCheckout() {
  const sel = document.getElementById('delMethodSel');
  const chg = sel && sel.selectedIndex >= 0 ? parseFloat(sel.options[sel.selectedIndex].getAttribute('data-charge') || '0') : 0;
  document.getElementById('chkDelCharge').textContent = '৳' + Math.round(chg).toLocaleString();
  document.getElementById('chkGrandTotal').textContent = '৳' + Math.round(Math.max(0, chkSub + chg - chkDisc)).toLocaleString();
}
recalcCheckout();

async function applyCheckoutCoupon() {
  const code = document.getElementById('chkCouponInput').value.trim();
  const phone = document.getElementById('chkPhone').value.trim();
  const res = await apiPost('apply_coupon', { code, phone });
  showToast(res.message, res.ok ? 'success' : 'danger');
  if (res.ok) {
    chkDisc = parseFloat(res.discount || '0');
    document.getElementById('chkDiscRow').classList.remove('d-none');
    document.getElementById('chkDiscVal').textContent = '-৳' + Math.round(chkDisc).toLocaleString();
    recalcCheckout();
  }
}

document.getElementById('checkoutForm').addEventListener('submit', (e) => {
  const pmChecked = document.querySelector('input[name="payment_method"]:checked');
  const trx = document.getElementById('trxIdInput').value.trim();
  if (!pmChecked) {
    e.preventDefault();
    showToast('অনুগ্রহ করে অনলাইন পেমেন্ট মেথড নির্বাচন করুন!', 'danger');
    return;
  }
  if (trx.length < 4) {
    e.preventDefault();
    showToast('Transaction ID ছাড়া অর্ডার সম্পন্ন করা যাবে না!', 'danger');
    document.getElementById('trxIdInput').focus();
    return;
  }
  const btn = document.getElementById('confirmOrderBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>অর্ডার সম্পন্ন হচ্ছে...';
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
