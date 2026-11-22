<?php
/**
 * Admin Views v3.1: Dashboard & Exact Order Table Layout (#17) with Copy, Clickable Product Image Popup,
 * Instant Order & Payment Status Dropdowns, View/Edit/Voucher/Delete, Live Visitors, Customers, Reviews
 */
if ($tab === 'dashboard'):
    $totalRev = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE order_status NOT IN ('cancelled','returned')")->fetchColumn();
    $todayRev = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE date(created_at) = date('now','localtime')")->fetchColumn();
    $recentOrders = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 8")->fetchAll();
?>
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><div class="benefit-3d-card"><div class="small text-muted">মোট বিক্রি</div><div class="fs-3 fw-bold text-danger"><?= format_tk($totalRev) ?></div><small class="text-success">আজ: <?= format_tk($todayRev) ?></small></div></div>
  <div class="col-6 col-lg-3"><div class="benefit-3d-card"><div class="small text-muted">মোট অর্ডার</div><div class="fs-3 fw-bold text-primary"><?= $cnt['orders'] ?>টি</div><small class="text-muted">ডেলিভারি: <?= $cnt['delivered'] ?> | কুরিয়ার: <?= $cnt['courier'] ?></small></div></div>
  <div class="col-6 col-lg-3"><div class="benefit-3d-card"><div class="small text-muted">সক্রিয় কেক</div><div class="fs-3 fw-bold text-success"><?= $cnt['products'] ?>টি</div><small class="text-warning">লো স্টক: <?= $cnt['low_stock'] ?> | আউট: <?= $cnt['out_stock'] ?></small></div></div>
  <div class="col-6 col-lg-3"><div class="benefit-3d-card"><div class="small text-muted">🟢 লাইভ ভিজিটর</div><div class="fs-3 fw-bold text-dark"><?= $cnt['visitors'] ?> জন</div><small class="text-muted">কাস্টমার: <?= $cnt['customers'] ?></small></div></div>
</div>

<div class="bg-white p-4 rounded-4 border shadow-sm">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">সাম্প্রতিক অর্ডারসমূহ</h5>
    <a href="?tab=orders" class="btn btn-sm btn-danger rounded-pill fw-bold">সকল অর্ডার দেখুন</a>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle small">
      <thead class="table-light"><tr><th>অর্ডার কোড</th><th>কাস্টমার</th><th>মোট বিল ও TrxID</th><th>স্ট্যাটাস</th><th>ভাউচার</th></tr></thead>
      <tbody><?php foreach ($recentOrders as $ro): ?>
        <tr>
          <td class="fw-bold text-danger">#<?= e($ro['order_code']) ?><br><small class="text-muted"><?= e($ro['created_at']) ?></small></td>
          <td><strong><?= e($ro['customer_name']) ?></strong><br><small><?= e($ro['customer_phone']) ?></small></td>
          <td><strong><?= format_tk((float)$ro['total']) ?></strong><br><small class="text-success"><?= e($ro['payment_method']) ?> <?= $ro['trx_id'] ? '('.e($ro['trx_id']).')' : '' ?></small></td>
          <td><span class="badge bg-secondary text-uppercase"><?= e($ro['order_status']) ?></span></td>
          <td><a href="../voucher.php?code=<?= e($ro['order_code']) ?>" target="_blank" class="btn btn-sm btn-outline-danger py-0">প্রিন্ট</a></td>
        </tr>
      <?php endforeach; ?></tbody>
    </table>
  </div>
</div>

<?php elseif (in_array($tab, ['orders', 'orders_incomplete', 'orders_delivered', 'orders_courier', 'orders_cancelled'], true)):
    $where = '1=1'; $title = '📋 সকল অর্ডার তালিকা';
    if ($tab === 'orders_incomplete') { $where = "order_status='incomplete'"; $title = '⚠️ অসম্পূর্ণ অর্ডার'; }
    if ($tab === 'orders_delivered') { $where = "order_status='delivered'"; $title = '✓ সফল ডেলিভারি'; }
    if ($tab === 'orders_courier') { $where = "order_status='courier'"; $title = '🚚 কুরিয়ারে / শিপিং'; }
    if ($tab === 'orders_cancelled') { $where = "order_status IN ('cancelled','returned')"; $title = '✕ বাতিল ও রিটার্ন'; }
    $orders = $pdo->query("SELECT * FROM orders WHERE {$where} ORDER BY id DESC")->fetchAll();
?>
<div class="bg-white p-4 rounded-4 border shadow-sm">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="fw-bold mb-0"><?= $title ?></h5>
    <a href="?tab=add_order" class="btn btn-sm btn-danger rounded-pill fw-bold">+ নতুন অর্ডার এন্ট্রি</a>
  </div>
  <div class="digital-filter-bar">
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <span class="digital-counter-pill">কাউন্টার: <span id="ordCnt"><?= count($orders) ?> / <?= count($orders) ?></span></span>
      <input type="text" id="ordSearch" class="form-control form-control-sm" style="width:220px;" placeholder="অর্ডার ID, নাম, ফোন বা TrxID..." oninput="filterDigitalTable('ordTbl','ordSearch','ordStatSel','ordCnt')">
      <select id="ordStatSel" class="form-select form-select-sm" style="width:140px;" onchange="filterDigitalTable('ordTbl','ordSearch','ordStatSel','ordCnt')">
        <option value="">সব স্ট্যাটাস</option><option value="pending">Pending</option><option value="confirmed">Confirmed</option><option value="courier">Courier</option><option value="delivered">Delivered</option><option value="incomplete">Incomplete</option><option value="cancelled">Cancelled</option><option value="returned">Returned</option>
      </select>
    </div>
    <button class="btn btn-sm btn-success rounded-pill fw-bold" onclick="exportTableToCSV('ordTbl','orders.csv')"><i class="fa-solid fa-file-csv me-1"></i>CSV ডাউনলোড</button>
  </div>

  <!-- Exact Requested Order Table Layout (#17) -->
  <div class="table-responsive">
    <table class="table table-hover align-middle small" id="ordTbl">
      <thead class="table-light">
        <tr>
          <th>Order ID / Date</th>
          <th>Customer / Phone / Address</th>
          <th>Image</th>
          <th>Product / Variant</th>
          <th>Total / Method / TrxID</th>
          <th>Order &amp; Payment Status</th>
          <th class="no-csv">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o):
          $items = json_decode($o['items_json'] ?? '[]', true) ?: [];
          $firstIt = $items[0] ?? ['name'=>'Cake','variant'=>'1 পাউন্ড','image'=>'assets/images/cakes/chocolate-truffle.png'];
          $imgUrl = admin_img_src($firstIt['image'] ?? 'assets/images/cakes/chocolate-truffle.png');
          $copyPayload = $o['customer_name'] . "\n" . $o['customer_phone'] . "\n" . $o['shipping_address'];
        ?>
          <tr id="ordRow_<?= (int)$o['id'] ?>" data-status="<?= e($o['order_status']) ?>">
            <td>
              <strong class="text-danger">#<?= e($o['order_code']) ?></strong><br>
              <small class="text-muted"><?= e($o['created_at']) ?></small>
            </td>
            <td style="max-width:220px;">
              <strong><?= e($o['customer_name']) ?></strong><br>
              <span><?= e($o['customer_phone']) ?></span><br>
              <small class="text-muted d-block"><?= e($o['shipping_address']) ?></small>
              <button type="button" class="btn btn-xs btn-outline-secondary btn-sm py-0 px-2 mt-1 no-csv" onclick='copyTextValue(<?= json_encode($copyPayload, JSON_UNESCAPED_UNICODE) ?>)'>📋 Copy</button>
            </td>
            <td>
              <img src="<?= e($imgUrl) ?>" width="50" height="50" class="rounded-3 object-fit-cover border" style="cursor:zoom-in;" onclick="openImagePopup(this.src)" title="বড় করে দেখুন">
            </td>
            <td>
              <strong><?= e($firstIt['name'] ?? 'Cake') ?></strong><?= count($items) > 1 ? ' <span class="badge bg-warning text-dark">+' . (count($items)-1) . '</span>' : '' ?><br>
              <small class="text-muted"><?= e($firstIt['variant'] ?? 'Standard') ?></small>
            </td>
            <td>
              <strong class="text-danger"><?= format_tk((float)$o['total']) ?></strong><br>
              <span class="badge bg-light text-dark border"><?= e($o['payment_method']) ?></span><br>
              <small class="fw-bold text-success">Trx: <?= e($o['trx_id'] ?: 'N/A') ?></small>
            </td>
            <td class="no-csv" style="min-width:140px;">
              <select class="form-select form-select-sm mb-1" onchange="quickOrderUpdate(<?= (int)$o['id'] ?>, 'order_status', this.value)">
                <?php foreach (['incomplete','pending','confirmed','courier','delivered','cancelled','returned'] as $st): ?>
                  <option value="<?= $st ?>" <?= $o['order_status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                <?php endforeach; ?>
              </select>
              <select class="form-select form-select-sm" onchange="quickOrderUpdate(<?= (int)$o['id'] ?>, 'payment_status', this.value)">
                <option value="unpaid" <?= $o['payment_status'] === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                <option value="paid" <?= $o['payment_status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
              </select>
            </td>
            <td class="no-csv">
              <div class="d-flex gap-1 mb-1">
                <button type="button" class="btn btn-sm btn-outline-info py-0 px-2" onclick='openOrderViewModal(<?= json_encode($o, JSON_UNESCAPED_UNICODE) ?>)'>View</button>
                <a href="?tab=edit_order&id=<?= (int)$o['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2">Edit</a>
              </div>
              <div class="d-flex gap-1">
                <a href="../voucher.php?code=<?= e($o['order_code']) ?>" target="_blank" class="btn btn-sm btn-outline-dark py-0 px-2">Voucher</a>
                <form method="post" onsubmit="return confirm('অর্ডারটি ডিলিট করবেন?')" class="d-inline">
                  <input type="hidden" name="admin_action" value="delete_order"><input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger py-0 px-2">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="orderViewModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content rounded-4 border-0 shadow-lg">
  <div class="modal-header bg-danger text-white rounded-top-4"><h5 class="modal-title fw-bold" id="ovTitle">অর্ডার বিস্তারিত</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <div class="modal-body" id="ovBody"></div>
</div></div></div>
<script>
function openOrderViewModal(o) {
  document.getElementById("ovTitle").textContent = "অর্ডার #" + o.order_code + " (" + o.order_status.toUpperCase() + ")";
  let items = []; try { items = JSON.parse(o.items_json || "[]"); } catch(e){}
  const rows = items.map(it => `<tr><td><strong>${it.name}</strong><br><small class="text-muted">${it.variant||""}</small></td><td>${it.qty}</td><td class="text-end fw-bold">৳${it.price}</td></tr>`).join("");
  document.getElementById("ovBody").innerHTML = `
    <div class="row g-3 mb-3 small">
      <div class="col-md-6"><div class="p-3 bg-light rounded-3 border"><strong>কাস্টমার:</strong> ${o.customer_name}<br><strong>ফোন:</strong> ${o.customer_phone}<br><strong>ইমেইল:</strong> ${o.customer_email||"N/A"}<br><strong>ঠিকানা:</strong> ${o.shipping_address}</div></div>
      <div class="col-md-6"><div class="p-3 bg-light rounded-3 border"><strong>পেমেন্ট:</strong> ${o.payment_method}<br><strong>TrxID:</strong> <span class="text-success fw-bold">${o.trx_id||"N/A"}</span><br><strong>পেমেন্ট স্ট্যাটাস:</strong> ${o.payment_status}<br><strong>তারিখ:</strong> ${o.created_at}</div></div>
    </div>
    <table class="table table-sm table-bordered"><thead><tr><th>কেক</th><th>পরিমাণ</th><th class="text-end">মূল্য</th></tr></thead><tbody>${rows}</tbody></table>
    <div class="text-end fs-5 fw-bold text-danger">সর্বমোট: ৳${o.total}</div>`;
  new bootstrap.Modal(document.getElementById("orderViewModal")).show();
}
async function quickOrderUpdate(id, field, value) {
  const res = await apiPost('admin_quick_order_update', { id, field, value });
  if (res.ok && field === 'order_status') {
    const tr = document.getElementById('ordRow_' + id);
    if (tr) tr.setAttribute('data-status', value);
  }
  showToast(res.message || 'আপডেট হয়েছে!', res.ok ? 'success' : 'danger');
}
</script>

<?php elseif ($tab === 'edit_order'):
  $eo = $pdo->query("SELECT * FROM orders WHERE id=" . (int)($_GET['id'] ?? 0))->fetch();
  if ($eo):
?>
<div class="bg-white p-4 rounded-4 border shadow-sm" style="max-width:740px;">
  <h5 class="fw-bold text-danger mb-3">✏️ অর্ডার এডিট — #<?= e($eo['order_code']) ?></h5>
  <form method="post" class="row g-3">
    <input type="hidden" name="admin_action" value="edit_order"><input type="hidden" name="id" value="<?= (int)$eo['id'] ?>">
    <div class="col-md-6"><label class="form-label small fw-bold">কাস্টমার নাম</label><input type="text" name="customer_name" value="<?= e($eo['customer_name']) ?>" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label small fw-bold">ফোন</label><input type="text" name="customer_phone" value="<?= e($eo['customer_phone']) ?>" class="form-control" required></div>
    <div class="col-12"><label class="form-label small fw-bold">ঠিকানা</label><input type="text" name="shipping_address" value="<?= e($eo['shipping_address']) ?>" class="form-control" required></div>
    <div class="col-md-4"><label class="form-label small fw-bold">মোট বিল (৳)</label><input type="number" name="total" value="<?= (float)$eo['total'] ?>" class="form-control"></div>
    <div class="col-md-4"><label class="form-label small fw-bold">পেমেন্ট মেথড</label><input type="text" name="payment_method" value="<?= e($eo['payment_method']) ?>" class="form-control"></div>
    <div class="col-md-4"><label class="form-label small fw-bold">Transaction ID</label><input type="text" name="trx_id" value="<?= e($eo['trx_id']) ?>" class="form-control"></div>
    <div class="col-md-3"><label class="form-label small fw-bold">Order Status</label><select name="order_status" class="form-select"><?php foreach (['incomplete','pending','confirmed','courier','delivered','cancelled','returned'] as $st): ?><option value="<?= $st ?>" <?= $eo['order_status']===$st?'selected':'' ?>><?= ucfirst($st) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-3"><label class="form-label small fw-bold">Payment Status</label><select name="payment_status" class="form-select"><option value="unpaid" <?= $eo['payment_status']==='unpaid'?'selected':'' ?>>Unpaid</option><option value="paid" <?= $eo['payment_status']==='paid'?'selected':'' ?>>Paid</option></select></div>
    <div class="col-md-3"><label class="form-label small fw-bold">কুরিয়ার</label><input type="text" name="courier_name" value="<?= e($eo['courier_name']) ?>" class="form-control"></div>
    <div class="col-md-3"><label class="form-label small fw-bold">ট্র্যাকিং কোড</label><input type="text" name="tracking_code" value="<?= e($eo['tracking_code']) ?>" class="form-control"></div>
    <div class="col-12"><button class="btn btn-danger rounded-pill fw-bold px-4">অর্ডার আপডেট করুন</button> <a href="?tab=orders" class="btn btn-outline-secondary rounded-pill">ফিরে যান</a></div>
  </form>
</div>
<?php endif; ?>

<?php elseif ($tab === 'add_order'):
  $prods = $pdo->query("SELECT id, name, discount_price FROM products WHERE status=1 ORDER BY id DESC")->fetchAll();
  $pms = $pdo->query("SELECT name FROM payment_methods WHERE status=1")->fetchAll(PDO::FETCH_COLUMN);
?>
<div class="bg-white p-4 rounded-4 border shadow-sm" style="max-width:760px;">
  <h5 class="fw-bold text-danger mb-3">+ নতুন অর্ডার এন্ট্রি</h5>
  <form method="post" class="row g-3">
    <input type="hidden" name="admin_action" value="add_manual_order">
    <div class="col-md-6"><label class="form-label small fw-bold">কাস্টমারের নাম *</label><input type="text" name="customer_name" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label small fw-bold">মোবাইল নম্বর *</label><input type="text" name="customer_phone" class="form-control" required></div>
    <div class="col-md-4"><label class="form-label small fw-bold">ইমেইল</label><input type="email" name="customer_email" class="form-control"></div>
    <div class="col-md-4"><label class="form-label small fw-bold">পেমেন্ট মেথড</label><select name="payment_method" class="form-select"><?php foreach ($pms as $pm): ?><option><?= e($pm) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label class="form-label small fw-bold">Transaction ID</label><input type="text" name="trx_id" class="form-control" required></div>
    <div class="col-12"><label class="form-label small fw-bold">ডেলিভারি ঠিকানা *</label><input type="text" name="shipping_address" class="form-control" required></div>
    <div class="col-md-5"><label class="form-label small fw-bold">কেক</label><select name="product_id" class="form-select"><?php foreach ($prods as $pr): ?><option value="<?= (int)$pr['id'] ?>"><?= e($pr['name']) ?> (<?= format_tk((float)$pr['discount_price']) ?>)</option><?php endforeach; ?></select></div>
    <div class="col-md-3"><label class="form-label small fw-bold">ভ্যারিয়েন্ট</label><input type="text" name="variant" value="1 পাউন্ড | ক্লাসিক" class="form-control"></div>
    <div class="col-md-2"><label class="form-label small fw-bold">পরিমাণ</label><input type="number" name="qty" value="1" min="1" class="form-control"></div>
    <div class="col-md-2"><label class="form-label small fw-bold">ডেলিভারি</label><input type="number" name="delivery_charge" value="70" class="form-control"></div>
    <div class="col-12"><button class="btn btn-danger rounded-pill fw-bold px-4">অর্ডার এন্ট্রি করুন</button></div>
  </form>
</div>

<?php elseif ($tab === 'live_visitors'):
  $vis = $pdo->query("SELECT * FROM live_visitors ORDER BY last_seen DESC")->fetchAll();
?>
<div class="bg-white p-4 rounded-4 border shadow-sm">
  <h5 class="fw-bold text-success mb-3">🟢 লাইভ ভিজিটর ও লগইন ট্র্যাকার</h5>
  <div class="digital-filter-bar"><span class="digital-counter-pill">লাইভ: <span id="visCnt"><?= count($vis) ?> / <?= count($vis) ?></span></span><input type="text" id="visSearch" class="form-control form-control-sm" style="width:200px;" placeholder="খুঁজুন..." oninput="filterDigitalTable('visTbl','visSearch','','visCnt')"><button class="btn btn-sm btn-success rounded-pill fw-bold" onclick="exportTableToCSV('visTbl','live-visitors.csv')">CSV</button></div>
  <table class="table table-sm small" id="visTbl"><thead class="table-light"><tr><th>ইউজার</th><th>পেজ</th><th>ডিভাইস</th><th>IP</th><th>সময়</th></tr></thead>
    <tbody><?php foreach ($vis as $v): ?><tr><td class="fw-bold"><?= (int)$v['user_id']>0?'<span class="badge bg-success me-1">Logged In</span>':'<span class="badge bg-secondary me-1">Guest</span>' ?><?= e($v['user_name']) ?></td><td><code><?= e($v['current_page']) ?></code></td><td><?= e($v['device']) ?></td><td><?= e($v['ip_address']) ?></td><td><?= e($v['last_seen']) ?></td></tr><?php endforeach; ?></tbody>
  </table>
</div>

<?php elseif ($tab === 'customers'):
  $custs = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.customer_id=u.id OR o.customer_phone=u.phone) as ord_cnt FROM users u WHERE u.role='customer' ORDER BY u.id DESC")->fetchAll();
?>
<div class="bg-white p-4 rounded-4 border shadow-sm">
  <h5 class="fw-bold mb-3">👥 কাস্টমার ডাটাবেজ</h5>
  <div class="digital-filter-bar"><span class="digital-counter-pill">কাস্টমার: <span id="cuCnt"><?= count($custs) ?> / <?= count($custs) ?></span></span><input type="text" id="cuSearch" class="form-control form-control-sm" style="width:200px;" placeholder="খুঁজুন..." oninput="filterDigitalTable('cuTbl','cuSearch','','cuCnt')"><button class="btn btn-sm btn-success rounded-pill fw-bold" onclick="exportTableToCSV('cuTbl','customers.csv')">CSV</button></div>
  <table class="table table-sm small" id="cuTbl"><thead class="table-light"><tr><th>নাম</th><th>ইমেইল</th><th>ফোন</th><th>ঠিকানা</th><th>মোট অর্ডার</th></tr></thead>
    <tbody><?php foreach ($custs as $c): ?><tr><td class="fw-bold"><?= e($c['name']) ?></td><td><?= e($c['email']) ?></td><td><?= e($c['phone']) ?></td><td><?= e($c['address']) ?></td><td><span class="badge bg-danger"><?= (int)$c['ord_cnt'] ?></span></td></tr><?php endforeach; ?></tbody>
  </table>
</div>

<?php elseif ($tab === 'reviews'):
  $revs = $pdo->query("SELECT r.*, p.name AS prod_name FROM reviews r LEFT JOIN products p ON p.id=r.product_id ORDER BY r.id DESC")->fetchAll();
?>
<div class="bg-white p-4 rounded-4 border shadow-sm">
  <h5 class="fw-bold mb-3">⭐ রিভিউ ও রেটিং</h5>
  <div class="digital-filter-bar"><span class="digital-counter-pill">রিভিউ: <span id="revCnt"><?= count($revs) ?> / <?= count($revs) ?></span></span><input type="text" id="revSearch" class="form-control form-control-sm" style="width:200px;" placeholder="খুঁজুন..." oninput="filterDigitalTable('revTbl','revSearch','','revCnt')"><button class="btn btn-sm btn-success rounded-pill fw-bold" onclick="exportTableToCSV('revTbl','reviews.csv')">CSV</button></div>
  <table class="table table-sm small" id="revTbl"><thead class="table-light"><tr><th>কেক</th><th>কাস্টমার</th><th>রেটিং</th><th>মতামত</th><th>তারিখ</th></tr></thead>
    <tbody><?php foreach ($revs as $rv): ?><tr><td class="fw-bold"><?= e($rv['prod_name']) ?></td><td><?= e($rv['user_name']) ?></td><td class="text-warning"><?= str_repeat('★', (int)$rv['rating']) ?></td><td><?= e($rv['comment']) ?></td><td><?= e($rv['created_at']) ?></td></tr><?php endforeach; ?></tbody>
  </table>
</div>
<?php endif; ?>
