<?php
$pageTitle = 'শপিং কার্ট';
require_once __DIR__ . '/includes/header.php';
$cart = get_cart();
$subtotal = cart_subtotal();
$savings = cart_savings();
$coupon = $_SESSION['applied_coupon'] ?? null;
$couponDisc = $coupon ? (float)$coupon['discount'] : 0;
?>

<div class="container py-4">
  <h2 class="fw-bold mb-4"><i class="fa-solid fa-cart-shopping text-danger me-2"></i>আপনার শপিং কার্ট (<?= cart_count() ?>)</h2>

  <?php if (empty($cart)): ?>
    <div class="bg-white p-5 rounded-4 border text-center shadow-sm">
      <i class="fa-solid fa-cart-arrow-down fs-1 text-muted mb-3"></i>
      <h5>আপনার কার্ট এখনো খালি!</h5>
      <p class="text-muted small">আপনার পছন্দের কেকটি বেছে নিন।</p>
      <a href="products.php" class="btn btn-danger rounded-pill px-4 fw-bold">কেক শপিং শুরু করুন</a>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="bg-white rounded-4 border shadow-sm overflow-hidden">
          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>কেক</th>
                  <th>দর</th>
                  <th style="width:130px;">পরিমাণ</th>
                  <th class="text-end">মোট</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($cart as $key => $it): ?>
                  <tr>
                    <td>
                      <div class="d-flex align-items-center gap-3">
                        <img src="<?= e($it['image']) ?>" width="64" height="64" class="rounded-3 object-fit-cover border">
                        <div>
                          <a href="product.php?slug=<?= e($it['slug']) ?>" class="fw-bold d-block"><?= e($it['name']) ?></a>
                          <small class="text-muted"><?= e($it['variant']) ?> | SKU: <?= e($it['sku']) ?></small>
                        </div>
                      </div>
                    </td>
                    <td class="fw-bold text-danger"><?= format_tk((float)$it['price']) ?></td>
                    <td>
                      <input type="number" class="form-control form-control-sm text-center fw-bold" value="<?= (int)$it['qty'] ?>" min="1" max="20" onchange="updateCartQty('<?= e($key) ?>', this.value)">
                    </td>
                    <td class="text-end fw-bold"><?= format_tk((float)$it['price'] * (int)$it['qty']) ?></td>
                    <td class="text-end">
                      <button type="button" class="btn btn-sm btn-outline-danger rounded-circle" onclick="removeCartItem('<?= e($key) ?>')"><i class="fa-solid fa-trash"></i></button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="bg-white p-4 rounded-4 border shadow-sm">
          <h5 class="fw-bold mb-3">অর্ডার সামারি</h5>
          <div class="input-group mb-3">
            <input type="text" id="couponInput" class="form-control" placeholder="কুপন কোড লিখুন" value="<?= e($coupon['code'] ?? '') ?>">
            <button class="btn btn-outline-danger fw-bold" type="button" onclick="applyCoupon()">প্রয়োগ</button>
          </div>
          <div class="d-flex justify-content-between mb-2"><span>সাবটোটাল</span><strong><?= format_tk($subtotal) ?></strong></div>
          <div class="d-flex justify-content-between mb-2 text-success"><span>মোট সাশ্রয়</span><strong><?= format_tk($savings) ?></strong></div>
          <?php if ($couponDisc > 0): ?>
            <div class="d-flex justify-content-between mb-2 text-success"><span>কুপন ছাড় (<?= e($coupon['code']) ?>)</span><strong>-<?= format_tk($couponDisc) ?></strong></div>
          <?php endif; ?>
          <hr>
          <div class="d-flex justify-content-between fs-5 fw-bold mb-4"><span>আনুমানিক মোট</span><span class="text-danger"><?= format_tk(max(0, $subtotal - $couponDisc)) ?></span></div>
          <a href="checkout.php" class="btn btn-order-now btn-lg w-100 rounded-pill py-2">চেকআউট করুন &rarr;</a>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
async function updateCartQty(key, qty) {
  await apiPost('update_cart', { key, qty });
  location.reload();
}
async function removeCartItem(key) {
  await apiPost('remove_cart', { key });
  location.reload();
}
async function applyCoupon() {
  const code = document.getElementById('couponInput').value;
  const res = await apiPost('apply_coupon', { code });
  showToast(res.message, res.ok ? 'success' : 'danger');
  if (res.ok) setTimeout(() => location.reload(), 600);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
