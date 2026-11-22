<?php
/**
 * Admin Views v3.1: Team, Report Center, Couriers Full CRUD (#18), Payment/Delivery/Coupon Full CRUD (#21-#23),
 * All Social Media + Website Settings (#24), Secure Old-Password OR Email-Code Password Reset (#25)
 */
if ($tab === 'master_admin' || $tab === 'staff'):
  $roleSql = $tab === 'master_admin' ? "role='master_admin'" : "role IN ('staff','admin')";
  $members = $pdo->query("SELECT * FROM users WHERE {$roleSql} ORDER BY id DESC")->fetchAll();
?>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="bg-white p-4 rounded-4 border shadow-sm">
      <h6 class="fw-bold text-danger mb-3">+ নতুন টিম সদস্য যুক্ত</h6>
      <form method="post" class="d-flex flex-column gap-2">
        <input type="hidden" name="admin_action" value="add_staff">
        <input type="text" name="name" class="form-control" placeholder="নাম *" required>
        <input type="email" name="email" class="form-control" placeholder="ইমেইল *" required>
        <input type="text" name="phone" class="form-control" placeholder="ফোন">
        <input type="password" name="password" class="form-control" placeholder="পাসওয়ার্ড *" required>
        <select name="role" class="form-select"><option value="staff">কর্মী / স্টাফ</option><option value="admin">অ্যাডমিন</option><option value="master_admin">মাস্টার অ্যাডমিন</option></select>
        <input type="text" name="address" class="form-control" placeholder="ঠিকানা">
        <button class="btn btn-danger rounded-pill fw-bold">যুক্ত করুন</button>
      </form>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="bg-white p-4 rounded-4 border shadow-sm">
      <div class="digital-filter-bar"><span class="digital-counter-pill">সদস্য: <span id="stfCnt"><?= count($members) ?> / <?= count($members) ?></span></span><input type="text" id="stfSearch" class="form-control form-control-sm" style="width:180px;" placeholder="খুঁজুন..." oninput="filterDigitalTable('stfTbl','stfSearch','','stfCnt')"><button class="btn btn-sm btn-success rounded-pill fw-bold" onclick="exportTableToCSV('stfTbl','team.csv')">CSV</button></div>
      <table class="table table-sm small" id="stfTbl"><thead class="table-light"><tr><th>নাম</th><th>ইমেইল</th><th>ফোন</th><th>রোল</th></tr></thead>
        <tbody><?php foreach ($members as $m): ?><tr><td class="fw-bold"><?= e($m['name']) ?></td><td><?= e($m['email']) ?></td><td><?= e($m['phone']) ?></td><td><span class="badge bg-danger"><?= e($m['role']) ?></span></td></tr><?php endforeach; ?></tbody>
      </table>
    </div>
  </div>
</div>

<?php elseif ($tab === 'reports'):
  $period = $_GET['period'] ?? 'monthly';
  switch ($period) {
      case 'daily':  $cond = "date(created_at) = date('now','localtime')"; break;
      case 'weekly': $cond = "created_at >= datetime('now','-7 days')"; break;
      case 'yearly': $cond = "strftime('%Y', created_at) = strftime('%Y', 'now')"; break;
      default:       $cond = "created_at >= datetime('now','-30 days')"; break;
  }
  $repOrders = $pdo->query("SELECT * FROM orders WHERE {$cond} ORDER BY id DESC")->fetchAll();
  $repTotal = 0.0;
  $repDelivered = 0;
  foreach ($repOrders as $ro) {
      $repTotal += (float)$ro['total'];
      if ($ro['order_status'] === 'delivered') $repDelivered++;
  }
?>
<div class="bg-white p-4 rounded-4 border shadow-sm">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="fw-bold text-danger mb-0">📈 রিপোর্ট সেন্টার (দৈনিক, সাপ্তাহিক, মাসিক, বাৎসরিক)</h5>
    <div class="btn-group btn-group-sm">
      <a href="?tab=reports&period=daily" class="btn <?= $period==='daily'?'btn-danger':'btn-outline-danger' ?>">দৈনিক</a>
      <a href="?tab=reports&period=weekly" class="btn <?= $period==='weekly'?'btn-danger':'btn-outline-danger' ?>">সাপ্তাহিক</a>
      <a href="?tab=reports&period=monthly" class="btn <?= $period==='monthly'?'btn-danger':'btn-outline-danger' ?>">মাসিক</a>
      <a href="?tab=reports&period=yearly" class="btn <?= $period==='yearly'?'btn-danger':'btn-outline-danger' ?>">বাৎসরিক</a>
    </div>
  </div>
  <div class="row g-3 mb-4">
    <div class="col-md-4"><div class="p-3 rounded-4 bg-light border"><div class="small text-muted">মোট বিক্রয়</div><div class="fs-3 fw-bold text-danger"><?= format_tk($repTotal) ?></div></div></div>
    <div class="col-md-4"><div class="p-3 rounded-4 bg-light border"><div class="small text-muted">মোট অর্ডার</div><div class="fs-3 fw-bold text-primary"><?= count($repOrders) ?>টি</div></div></div>
    <div class="col-md-4"><div class="p-3 rounded-4 bg-light border"><div class="small text-muted">সফল ডেলিভারি</div><div class="fs-3 fw-bold text-success"><?= $repDelivered ?>টি</div></div></div>
  </div>
  <div class="digital-filter-bar"><span class="digital-counter-pill">রেকর্ড: <span id="repCnt"><?= count($repOrders) ?> / <?= count($repOrders) ?></span></span><input type="text" id="repSearch" class="form-control form-control-sm" style="width:180px;" placeholder="খুঁজুন..." oninput="filterDigitalTable('repTbl','repSearch','','repCnt')"><button class="btn btn-sm btn-success rounded-pill fw-bold" onclick="exportTableToCSV('repTbl','report-<?= e($period) ?>.csv')">CSV</button></div>
  <table class="table table-sm small" id="repTbl"><thead class="table-light"><tr><th>তারিখ</th><th>কোড</th><th>কাস্টমার</th><th>পেমেন্ট</th><th>স্ট্যাটাস</th><th>মোট</th></tr></thead>
    <tbody><?php foreach ($repOrders as $ro): ?><tr><td><?= e($ro['created_at']) ?></td><td class="fw-bold">#<?= e($ro['order_code']) ?></td><td><?= e($ro['customer_name']) ?></td><td><?= e($ro['payment_method']) ?></td><td><?= e($ro['order_status']) ?></td><td class="fw-bold text-danger"><?= format_tk((float)$ro['total']) ?></td></tr><?php endforeach; ?></tbody>
  </table>
</div>

<?php elseif ($tab === 'couriers'):
  $cours = $pdo->query("SELECT * FROM couriers ORDER BY id DESC")->fetchAll();
  $editCr = !empty($_GET['edit']) ? $pdo->query("SELECT * FROM couriers WHERE id=".(int)$_GET['edit'])->fetch() : null;
?>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="bg-white p-4 rounded-4 border shadow-sm">
      <h6 class="fw-bold text-danger mb-3"><?= $editCr ? '✏️ কুরিয়ার API এডিট' : '+ কুরিয়ার API যুক্ত করুন' ?></h6>
      <form method="post" class="d-flex flex-column gap-2">
        <input type="hidden" name="admin_action" value="save_courier"><input type="hidden" name="id" value="<?= (int)($editCr['id'] ?? 0) ?>">
        <input type="text" name="name" value="<?= e($editCr['name'] ?? '') ?>" class="form-control" placeholder="কুরিয়ারের নাম (Steadfast/Pathao/RedX) *" required>
        <input type="url" name="api_url" value="<?= e($editCr['api_url'] ?? '') ?>" class="form-control" placeholder="API Endpoint URL *" required>
        <input type="text" name="api_key" value="<?= e($editCr['api_key'] ?? '') ?>" class="form-control" placeholder="API Key *" required>
        <input type="text" name="secret_key" value="<?= e($editCr['secret_key'] ?? '') ?>" class="form-control" placeholder="Secret Key">
        <select name="status" class="form-select"><option value="1" <?= (!$editCr || $editCr['status']==1)?'selected':'' ?>>Active</option><option value="0" <?= ($editCr && $editCr['status']==0)?'selected':'' ?>>Inactive</option></select>
        <button class="btn btn-danger rounded-pill fw-bold mt-2"><?= $editCr ? 'আপডেট করুন' : 'কুরিয়ার কানেক্ট করুন' ?></button>
      </form>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="bg-white p-4 rounded-4 border shadow-sm">
      <div class="digital-filter-bar">
        <div class="d-flex gap-2 align-items-center flex-wrap">
          <span class="digital-counter-pill">সংযুক্ত কুরিয়ার: <span id="corCnt"><?= count($cours) ?> / <?= count($cours) ?></span></span>
          <input type="text" id="corSearch" class="form-control form-control-sm" style="width:180px;" placeholder="খুঁজুন..." oninput="filterDigitalTable('corTbl','corSearch','','corCnt')">
        </div>
        <button class="btn btn-sm btn-success rounded-pill fw-bold" onclick="exportTableToCSV('corTbl','couriers.csv')">CSV</button>
      </div>
      <table class="table table-sm align-middle small" id="corTbl"><thead class="table-light"><tr><th>কুরিয়ার</th><th>API URL</th><th>স্ট্যাটাস</th><th class="no-csv">অ্যাকশন</th></tr></thead>
        <tbody><?php foreach ($cours as $cr): ?>
          <tr>
            <td class="fw-bold"><?= e($cr['name']) ?></td><td><code><?= e($cr['api_url']) ?></code></td>
            <td><span class="badge <?= $cr['status']?'bg-success':'bg-secondary' ?>"><?= $cr['status']?'Active':'Inactive' ?></span></td>
            <td class="no-csv">
              <a href="?tab=couriers&edit=<?= (int)$cr['id'] ?>" class="btn btn-sm btn-outline-primary py-0">এডিট</a>
              <form method="post" class="d-inline" onsubmit="return confirm('ডিলিট করবেন?')"><input type="hidden" name="admin_action" value="delete_courier"><input type="hidden" name="id" value="<?= (int)$cr['id'] ?>"><button class="btn btn-sm btn-outline-danger py-0">ডিলিট</button></form>
            </td>
          </tr>
        <?php endforeach; ?></tbody>
      </table>
    </div>
  </div>
</div>

<?php elseif ($tab === 'audit_logs'):
  $logs = $pdo->query("SELECT * FROM audit_logs ORDER BY id DESC LIMIT 100")->fetchAll();
?>
<div class="bg-white p-4 rounded-4 border shadow-sm">
  <h5 class="fw-bold mb-3">📜 অডিট ও কর্মী লগ</h5>
  <div class="digital-filter-bar"><span class="digital-counter-pill">লগ: <span id="logCnt"><?= count($logs) ?> / <?= count($logs) ?></span></span><input type="text" id="logSearch" class="form-control form-control-sm" style="width:180px;" placeholder="খুঁজুন..." oninput="filterDigitalTable('logTbl','logSearch','','logCnt')"><button class="btn btn-sm btn-success rounded-pill fw-bold" onclick="exportTableToCSV('logTbl','audit-logs.csv')">CSV</button></div>
  <table class="table table-sm small" id="logTbl"><thead class="table-light"><tr><th>সময়</th><th>ইউজার</th><th>অ্যাকশন</th><th>বিস্তারিত</th><th>IP</th></tr></thead>
    <tbody><?php foreach ($logs as $lg): ?><tr><td><?= e($lg['created_at']) ?></td><td class="fw-bold"><?= e($lg['user_name']) ?></td><td><span class="badge bg-dark"><?= e($lg['action']) ?></span></td><td><?= e($lg['details']) ?></td><td><?= e($lg['ip_address']) ?></td></tr><?php endforeach; ?></tbody>
  </table>
</div>

<?php elseif ($tab === 'payment_methods'):
  $pms = $pdo->query("SELECT * FROM payment_methods ORDER BY id ASC")->fetchAll();
  $editPm = !empty($_GET['edit']) ? $pdo->query("SELECT * FROM payment_methods WHERE id=".(int)$_GET['edit'])->fetch() : null;
?>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="bg-white p-4 rounded-4 border shadow-sm">
      <h6 class="fw-bold text-danger mb-3"><?= $editPm ? '✏️ পেমেন্ট মেথড এডিট' : '+ নতুন অনলাইন পেমেন্ট মেথড' ?></h6>
      <form method="post" class="d-flex flex-column gap-2">
        <input type="hidden" name="admin_action" value="save_payment_method"><input type="hidden" name="id" value="<?= (int)($editPm['id'] ?? 0) ?>">
        <input type="text" name="name" value="<?= e($editPm['name'] ?? '') ?>" class="form-control" placeholder="নাম (যেমন: bKash Personal) *" required>
        <select name="type" class="form-select"><option value="mobile" <?= (!$editPm || $editPm['type']==='mobile')?'selected':'' ?>>Mobile Banking</option><option value="gateway" <?= ($editPm && $editPm['type']==='gateway')?'selected':'' ?>>Online Gateway</option><option value="bank" <?= ($editPm && $editPm['type']==='bank')?'selected':'' ?>>Bank Transfer</option></select>
        <input type="text" name="account_number" value="<?= e($editPm['account_number'] ?? '') ?>" class="form-control" placeholder="অ্যাকাউন্ট / মার্চেন্ট নম্বর *" required>
        <textarea name="instructions" class="form-control" rows="2" placeholder="পেমেন্ট নির্দেশনা"><?= e($editPm['instructions'] ?? '') ?></textarea>
        <select name="status" class="form-select"><option value="1" <?= (!$editPm || $editPm['status']==1)?'selected':'' ?>>Active</option><option value="0" <?= ($editPm && $editPm['status']==0)?'selected':'' ?>>Inactive</option></select>
        <button class="btn btn-danger rounded-pill fw-bold mt-2"><?= $editPm ? 'আপডেট করুন' : 'যুক্ত করুন' ?></button>
      </form>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="bg-white p-4 rounded-4 border shadow-sm">
      <div class="digital-filter-bar">
        <div class="d-flex gap-2 align-items-center flex-wrap">
          <span class="digital-counter-pill">পেমেন্ট মেথড: <span id="pmCnt"><?= count($pms) ?> / <?= count($pms) ?></span></span>
          <input type="text" id="pmSearch" class="form-control form-control-sm" style="width:180px;" placeholder="খুঁজুন..." oninput="filterDigitalTable('pmTbl','pmSearch','','pmCnt')">
        </div>
        <button class="btn btn-sm btn-success rounded-pill fw-bold" onclick="exportTableToCSV('pmTbl','payment-methods.csv')">CSV</button>
      </div>
      <table class="table table-sm align-middle small" id="pmTbl"><thead class="table-light"><tr><th>নাম</th><th>অ্যাকাউন্ট নম্বর</th><th>স্ট্যাটাস</th><th class="no-csv">অ্যাকশন</th></tr></thead>
        <tbody><?php foreach ($pms as $pm): ?>
          <tr>
            <td class="fw-bold"><?= e($pm['name']) ?><br><small class="text-muted"><?= e($pm['instructions']) ?></small></td>
            <td class="fw-bold text-danger"><?= e($pm['account_number']) ?></td>
            <td><span class="badge <?= $pm['status']?'bg-success':'bg-secondary' ?>"><?= $pm['status']?'Active':'Inactive' ?></span></td>
            <td class="no-csv">
              <a href="?tab=payment_methods&edit=<?= (int)$pm['id'] ?>" class="btn btn-sm btn-outline-primary py-0">এডিট</a>
              <form method="post" class="d-inline" onsubmit="return confirm('ডিলিট করবেন?')"><input type="hidden" name="admin_action" value="delete_payment_method"><input type="hidden" name="id" value="<?= (int)$pm['id'] ?>"><button class="btn btn-sm btn-outline-danger py-0">ডিলিট</button></form>
            </td>
          </tr>
        <?php endforeach; ?></tbody>
      </table>
    </div>
  </div>
</div>

<?php elseif ($tab === 'delivery_methods'):
  $dms = $pdo->query("SELECT * FROM delivery_methods ORDER BY id ASC")->fetchAll();
  $editDm = !empty($_GET['edit']) ? $pdo->query("SELECT * FROM delivery_methods WHERE id=".(int)$_GET['edit'])->fetch() : null;
?>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="bg-white p-4 rounded-4 border shadow-sm">
      <h6 class="fw-bold text-danger mb-3"><?= $editDm ? '✏️ ডেলিভারি মেথড এডিট' : '+ নতুন ডেলিভারি মেথড' ?></h6>
      <form method="post" class="d-flex flex-column gap-2">
        <input type="hidden" name="admin_action" value="save_delivery_method"><input type="hidden" name="id" value="<?= (int)($editDm['id'] ?? 0) ?>">
        <input type="text" name="name" value="<?= e($editDm['name'] ?? '') ?>" class="form-control" placeholder="ডেলিভারি মেথডের নাম *" required>
        <input type="text" name="area" value="<?= e($editDm['area'] ?? 'inside') ?>" class="form-control" placeholder="এরিয়া কোড (inside/outside/pickup)">
        <input type="number" name="charge" value="<?= (float)($editDm['charge'] ?? 70) ?>" class="form-control" placeholder="চার্জ (৳) *" required>
        <input type="text" name="estimated_time" value="<?= e($editDm['estimated_time'] ?? '২-৪ ঘন্টা') ?>" class="form-control" placeholder="আনুমানিক সময়">
        <select name="status" class="form-select"><option value="1" <?= (!$editDm || $editDm['status']==1)?'selected':'' ?>>Active</option><option value="0" <?= ($editDm && $editDm['status']==0)?'selected':'' ?>>Inactive</option></select>
        <button class="btn btn-danger rounded-pill fw-bold mt-2"><?= $editDm ? 'আপডেট করুন' : 'যুক্ত করুন' ?></button>
      </form>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="bg-white p-4 rounded-4 border shadow-sm">
      <div class="digital-filter-bar">
        <div class="d-flex gap-2 align-items-center flex-wrap">
          <span class="digital-counter-pill">ডেলিভারি মেথড: <span id="dmCnt"><?= count($dms) ?> / <?= count($dms) ?></span></span>
          <input type="text" id="dmSearch" class="form-control form-control-sm" style="width:180px;" placeholder="খুঁজুন..." oninput="filterDigitalTable('dmTbl','dmSearch','','dmCnt')">
        </div>
        <button class="btn btn-sm btn-success rounded-pill fw-bold" onclick="exportTableToCSV('dmTbl','delivery-methods.csv')">CSV</button>
      </div>
      <table class="table table-sm align-middle small" id="dmTbl"><thead class="table-light"><tr><th>নাম</th><th>চার্জ</th><th>সময়</th><th>স্ট্যাটাস</th><th class="no-csv">অ্যাকশন</th></tr></thead>
        <tbody><?php foreach ($dms as $dm): ?>
          <tr>
            <td class="fw-bold"><?= e($dm['name']) ?></td><td class="fw-bold text-danger"><?= format_tk((float)$dm['charge']) ?></td><td><?= e($dm['estimated_time']) ?></td>
            <td><span class="badge <?= $dm['status']?'bg-success':'bg-secondary' ?>"><?= $dm['status']?'Active':'Inactive' ?></span></td>
            <td class="no-csv">
              <a href="?tab=delivery_methods&edit=<?= (int)$dm['id'] ?>" class="btn btn-sm btn-outline-primary py-0">এডিট</a>
              <form method="post" class="d-inline" onsubmit="return confirm('ডিলিট করবেন?')"><input type="hidden" name="admin_action" value="delete_delivery_method"><input type="hidden" name="id" value="<?= (int)$dm['id'] ?>"><button class="btn btn-sm btn-outline-danger py-0">ডিলিট</button></form>
            </td>
          </tr>
        <?php endforeach; ?></tbody>
      </table>
    </div>
  </div>
</div>

<?php elseif ($tab === 'coupons'):
  $cps = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM coupon_usages u WHERE UPPER(u.coupon_code)=UPPER(c.code)) AS used_total FROM coupons c ORDER BY c.id DESC")->fetchAll();
  $editCp = !empty($_GET['edit']) ? $pdo->query("SELECT * FROM coupons WHERE id=".(int)$_GET['edit'])->fetch() : null;
?>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="bg-white p-4 rounded-4 border shadow-sm">
      <h6 class="fw-bold text-danger mb-3"><?= $editCp ? '✏️ কুপন এডিট' : '+ নতুন অফার ও কুপন' ?></h6>
      <form method="post" class="d-flex flex-column gap-2">
        <input type="hidden" name="admin_action" value="save_coupon"><input type="hidden" name="id" value="<?= (int)($editCp['id'] ?? 0) ?>">
        <input type="text" name="code" value="<?= e($editCp['code'] ?? '') ?>" class="form-control" placeholder="কুপন কোড *" required>
        <select name="discount_type" class="form-select"><option value="percent" <?= (!$editCp || $editCp['discount_type']==='percent')?'selected':'' ?>>শতাংশ (%)</option><option value="fixed" <?= ($editCp && $editCp['discount_type']==='fixed')?'selected':'' ?>>নির্দিষ্ট টাকা (৳)</option></select>
        <input type="number" name="discount_value" value="<?= (float)($editCp['discount_value'] ?? 10) ?>" class="form-control" placeholder="ছাড়ের পরিমাণ *" required>
        <input type="number" name="min_order" value="<?= (float)($editCp['min_order'] ?? 500) ?>" class="form-control" placeholder="ন্যূনতম অর্ডার (৳)">
        <label class="small fw-bold mb-0">একজন কাস্টমার সর্বোচ্চ কয়বার ব্যবহার করতে পারবে:</label>
        <input type="number" name="max_uses_per_customer" value="<?= (int)($editCp['max_uses_per_customer'] ?? 1) ?>" min="1" class="form-control" required>
        <input type="date" name="expiry_date" value="<?= e($editCp['expiry_date'] ?? '2027-12-31') ?>" class="form-control">
        <select name="status" class="form-select"><option value="1" <?= (!$editCp || $editCp['status']==1)?'selected':'' ?>>Active</option><option value="0" <?= ($editCp && $editCp['status']==0)?'selected':'' ?>>Inactive</option></select>
        <button class="btn btn-danger rounded-pill fw-bold mt-2"><?= $editCp ? 'আপডেট করুন' : 'কুপন তৈরি করুন' ?></button>
      </form>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="bg-white p-4 rounded-4 border shadow-sm">
      <div class="digital-filter-bar">
        <div class="d-flex gap-2 align-items-center flex-wrap">
          <span class="digital-counter-pill">কুপন: <span id="cpCnt"><?= count($cps) ?> / <?= count($cps) ?></span></span>
          <input type="text" id="cpSearch" class="form-control form-control-sm" style="width:180px;" placeholder="খুঁজুন..." oninput="filterDigitalTable('cpTbl','cpSearch','','cpCnt')">
        </div>
        <button class="btn btn-sm btn-success rounded-pill fw-bold" onclick="exportTableToCSV('cpTbl','coupons.csv')">CSV</button>
      </div>
      <table class="table table-sm align-middle small" id="cpTbl"><thead class="table-light"><tr><th>কোড</th><th>ছাড়</th><th>ন্যূনতম</th><th>লিমিট/কাস্টমার</th><th>ব্যবহৃত</th><th>মেয়াদ</th><th class="no-csv">অ্যাকশন</th></tr></thead>
        <tbody><?php foreach ($cps as $cp): ?>
          <tr>
            <td class="fw-bold text-danger"><?= e($cp['code']) ?></td>
            <td class="fw-bold"><?= e($cp['discount_value']) ?><?= $cp['discount_type']==='percent'?'%':'৳' ?></td>
            <td><?= format_tk((float)$cp['min_order']) ?></td>
            <td><span class="badge bg-primary"><?= (int)($cp['max_uses_per_customer'] ?? 1) ?> বার</span></td>
            <td><span class="badge bg-light text-dark border"><?= (int)($cp['used_total'] ?? 0) ?></span></td>
            <td><?= e($cp['expiry_date']) ?></td>
            <td class="no-csv">
              <a href="?tab=coupons&edit=<?= (int)$cp['id'] ?>" class="btn btn-sm btn-outline-primary py-0">এডিট</a>
              <form method="post" class="d-inline" onsubmit="return confirm('ডিলিট করবেন?')"><input type="hidden" name="admin_action" value="delete_coupon"><input type="hidden" name="id" value="<?= (int)$cp['id'] ?>"><button class="btn btn-sm btn-outline-danger py-0">ডিলিট</button></form>
            </td>
          </tr>
        <?php endforeach; ?></tbody>
      </table>
    </div>
  </div>
</div>

<?php elseif ($tab === 'site_settings'):
  $s = get_settings();
?>
<div class="bg-white p-4 rounded-4 border shadow-sm">
  <h5 class="fw-bold text-danger mb-3">⚙️ ওয়েবসাইট তথ্য ও সকল সোশ্যাল মিডিয়া লিংক আপডেট</h5>
  <form method="post" class="row g-3">
    <input type="hidden" name="admin_action" value="save_settings">
    <div class="col-md-4"><label class="form-label small fw-bold">ওয়েবসাইটের নাম</label><input type="text" name="s[site_name]" value="<?= e($s['site_name']) ?>" class="form-control"></div>
    <div class="col-md-4"><label class="form-label small fw-bold">বাংলা নাম</label><input type="text" name="s[site_bn_name]" value="<?= e($s['site_bn_name']) ?>" class="form-control"></div>
    <div class="col-md-4"><label class="form-label small fw-bold">ট্যাগলাইন</label><input type="text" name="s[tagline]" value="<?= e($s['tagline']) ?>" class="form-control"></div>
    <div class="col-md-3"><label class="form-label small fw-bold">হটলাইন ১</label><input type="text" name="s[phone]" value="<?= e($s['phone']) ?>" class="form-control"></div>
    <div class="col-md-3"><label class="form-label small fw-bold">হটলাইন ২</label><input type="text" name="s[phone2]" value="<?= e($s['phone2']) ?>" class="form-control"></div>
    <div class="col-md-3"><label class="form-label small fw-bold">WhatsApp (880 সহ)</label><input type="text" name="s[whatsapp]" value="<?= e($s['whatsapp']) ?>" class="form-control"></div>
    <div class="col-md-3"><label class="form-label small fw-bold">অফিশিয়াল ইমেইল</label><input type="email" name="s[email]" value="<?= e($s['email']) ?>" class="form-control"></div>
    <div class="col-12"><label class="form-label small fw-bold">টপ নোটিশ বার</label><input type="text" name="s[notice_text]" value="<?= e($s['notice_text']) ?>" class="form-control"></div>
    <div class="col-md-6"><label class="form-label small fw-bold">শোরুম ঠিকানা</label><input type="text" name="s[address]" value="<?= e($s['address']) ?>" class="form-control"></div>
    <div class="col-md-6"><label class="form-label small fw-bold">খোলার সময় (Business Hours)</label><input type="text" name="s[business_hours]" value="<?= e($s['business_hours'] ?? '') ?>" class="form-control"></div>
    <div class="col-12"><label class="form-label small fw-bold">Google Map Embed URL</label><input type="url" name="s[map_embed_url]" value="<?= e($s['map_embed_url'] ?? '') ?>" class="form-control"></div>

    <div class="col-12 pt-2"><h6 class="fw-bold text-primary mb-0">🌐 সকল সোশ্যাল মিডিয়া লিংক</h6></div>
    <div class="col-md-4"><label class="form-label small fw-bold">Facebook URL</label><input type="url" name="s[facebook_url]" value="<?= e($s['facebook_url'] ?? '') ?>" class="form-control"></div>
    <div class="col-md-4"><label class="form-label small fw-bold">Instagram URL</label><input type="url" name="s[instagram_url]" value="<?= e($s['instagram_url'] ?? '') ?>" class="form-control"></div>
    <div class="col-md-4"><label class="form-label small fw-bold">YouTube URL</label><input type="url" name="s[youtube_url]" value="<?= e($s['youtube_url'] ?? '') ?>" class="form-control"></div>
    <div class="col-md-4"><label class="form-label small fw-bold">TikTok URL</label><input type="url" name="s[tiktok_url]" value="<?= e($s['tiktok_url'] ?? '') ?>" class="form-control"></div>
    <div class="col-md-4"><label class="form-label small fw-bold">LinkedIn URL</label><input type="url" name="s[linkedin_url]" value="<?= e($s['linkedin_url'] ?? '') ?>" class="form-control"></div>
    <div class="col-md-4"><label class="form-label small fw-bold">X / Twitter URL</label><input type="url" name="s[twitter_url]" value="<?= e($s['twitter_url'] ?? '') ?>" class="form-control"></div>

    <div class="col-md-6"><label class="form-label small fw-bold">Telegram Mobile Push Bot Token</label><input type="text" name="s[telegram_bot_token]" value="<?= e($s['telegram_bot_token'] ?? '') ?>" class="form-control"></div>
    <div class="col-md-6"><label class="form-label small fw-bold">Telegram Chat ID</label><input type="text" name="s[telegram_chat_id]" value="<?= e($s['telegram_chat_id'] ?? '') ?>" class="form-control"></div>
    <div class="col-12"><button class="btn btn-danger rounded-pill fw-bold px-5">সব তথ্য লাইভ সেভ করুন</button></div>
  </form>
</div>

<?php elseif ($tab === 'profile'): ?>
<div class="bg-white p-4 rounded-4 border shadow-sm" style="max-width:600px;">
  <h5 class="fw-bold text-danger mb-3">অ্যাডমিন প্রোফাইল আপডেট</h5>
  <form method="post" class="d-flex flex-column gap-3">
    <input type="hidden" name="admin_action" value="update_profile">
    <input type="text" name="name" value="<?= e($adminUser['name']) ?>" class="form-control" required>
    <input type="text" name="phone" value="<?= e($adminUser['phone']) ?>" class="form-control">
    <input type="text" name="address" value="<?= e($adminUser['address']) ?>" class="form-control">
    <button class="btn btn-danger rounded-pill fw-bold">আপডেট করুন</button>
  </form>
</div>

<?php elseif ($tab === 'password'): ?>
<div class="row g-4">
  <div class="col-lg-6">
    <div class="bg-white p-4 rounded-4 border shadow-sm h-100">
      <h5 class="fw-bold text-danger mb-3">🔐 পুরাতন পাসওয়ার্ড দিয়ে পরিবর্তন</h5>
      <form method="post" class="d-flex flex-column gap-3">
        <input type="hidden" name="admin_action" value="change_password_old">
        <div><label class="form-label small fw-bold">পুরাতন পাসওয়ার্ড *</label><input type="password" name="old_password" class="form-control" required></div>
        <div><label class="form-label small fw-bold">নতুন পাসওয়ার্ড (কমপক্ষে ৬ অক্ষর) *</label><input type="password" name="new_password" class="form-control" minlength="6" required></div>
        <button class="btn btn-danger rounded-pill fw-bold">পাসওয়ার্ড পরিবর্তন করুন</button>
      </form>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="bg-white p-4 rounded-4 border shadow-sm h-100">
      <h5 class="fw-bold text-primary mb-2">📧 ইমেইল ভেরিফিকেশন কোড দিয়ে রিসেট</h5>
      <p class="small text-muted">পুরাতন পাসওয়ার্ড ভুলে গেলে আপনার ইমেইল (<strong><?= e($adminUser['email']) ?></strong>)-এ ৬ ডিজিটের কোড পাঠিয়ে পাসওয়ার্ড রিসেট করুন।</p>
      <form method="post" class="mb-3">
        <input type="hidden" name="admin_action" value="send_reset_code">
        <button class="btn btn-outline-primary btn-sm rounded-pill fw-bold px-3">ইমেইলে ৬ ডিজিটের কোড পাঠান</button>
      </form>
      <form method="post" class="d-flex flex-column gap-2">
        <input type="hidden" name="admin_action" value="reset_password_otp">
        <input type="text" name="reset_code" class="form-control" placeholder="৬ ডিজিটের ইমেইল কোড *" required>
        <input type="password" name="new_password" class="form-control" placeholder="নতুন পাসওয়ার্ড (কমপক্ষে ৬ অক্ষর) *" minlength="6" required>
        <button class="btn btn-primary rounded-pill fw-bold mt-1">কোড যাচাই করে রিসেট করুন</button>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>
