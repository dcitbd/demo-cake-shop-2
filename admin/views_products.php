<?php
/**
 * Admin Views v3.1: Consolidated Product List (Bulk/Stock/Defective/Active/Edit/Preview/Image Popup),
 * Smart Auto-Description Add/Edit Form, Category CRUD, Brand + Variant CRUD, Color & Size (No Price), Banner CMS CRUD
 */
$allCats = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS p_count FROM categories c ORDER BY c.parent_id ASC, c.id ASC")->fetchAll();
$allBrands = $pdo->query("SELECT b.*, (SELECT COUNT(*) FROM products p WHERE p.brand_id = b.id) AS p_count FROM brands b ORDER BY b.id ASC")->fetchAll();
$catNameMap = [];
foreach ($allCats as $cm) $catNameMap[(int)$cm['id']] = $cm['name'];

if ($tab === 'products'):
    $sub = $_GET['sub'] ?? 'all';
    $where = '1=1';
    if ($sub === 'low_stock') $where = 'p.stock > 0 AND p.stock < 5';
    elseif ($sub === 'out_stock') $where = 'p.stock = 0';
    elseif ($sub === 'defective') $where = 'p.defective_flag = 1';
    $rows = $pdo->query("SELECT p.*, c.name AS cat_name, b.name AS brand_name FROM products p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN brands b ON b.id=p.brand_id WHERE {$where} ORDER BY p.id DESC")->fetchAll();
?>
<div class="bg-white p-4 rounded-4 border shadow-sm">
  <!-- Consolidated Sub-Navigation Pills (#11) -->
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-3 border-bottom">
    <div class="d-flex flex-wrap gap-2">
      <a href="?tab=products&sub=all" class="btn btn-sm rounded-pill fw-bold <?= $sub==='all'?'btn-danger':'btn-outline-danger' ?>">📦 সকল পণ্য (<?= $cnt['products'] ?>)</a>
      <a href="?tab=products&sub=low_stock" class="btn btn-sm rounded-pill fw-bold <?= $sub==='low_stock'?'btn-warning':'btn-outline-warning text-dark' ?>">⚠️ লো স্টক (&lt;৫) (<?= $cnt['low_stock'] ?>)</a>
      <a href="?tab=products&sub=out_stock" class="btn btn-sm rounded-pill fw-bold <?= $sub==='out_stock'?'btn-dark':'btn-outline-dark' ?>">🚫 আউট অব স্টক (<?= $cnt['out_stock'] ?>)</a>
      <a href="?tab=products&sub=defective" class="btn btn-sm rounded-pill fw-bold <?= $sub==='defective'?'btn-secondary':'btn-outline-secondary' ?>">⚙️ ত্রুটিযুক্ত (<?= $cnt['defective'] ?>)</a>
      <a href="?tab=products&sub=bulk_upload" class="btn btn-sm rounded-pill fw-bold <?= $sub==='bulk_upload'?'btn-success':'btn-outline-success' ?>">📥 বাল্ক আপলোড (Excel/CSV)</a>
      <a href="?tab=products&sub=bulk_price" class="btn btn-sm rounded-pill fw-bold <?= $sub==='bulk_price'?'btn-primary':'btn-outline-primary' ?>">🏷️ বাল্ক মূল্য</a>
      <a href="?tab=products&sub=bulk_image" class="btn btn-sm rounded-pill fw-bold <?= $sub==='bulk_image'?'btn-info text-dark':'btn-outline-info text-dark' ?>">🖼️ বাল্ক ইমেজ</a>
    </div>
    <a href="?tab=add_product" class="btn btn-sm btn-danger rounded-pill fw-bold px-3">+ নতুন কেক যোগ</a>
  </div>

  <?php if ($sub === 'bulk_upload'): ?>
    <h6 class="fw-bold text-success mb-2">📥 বাল্ক পণ্য আপলোড (Excel / CSV ফাইল)</h6>
    <p class="small text-muted">কলাম: <code>sku, name, category_id, brand_id, regular_price, discount_price, stock, image_url</code></p>
    <form method="post" enctype="multipart/form-data" id="bulkExcelForm" class="d-flex gap-2 flex-wrap align-items-center">
      <input type="hidden" name="admin_action" value="bulk_upload_csv">
      <input type="file" name="csv_file" id="bulkExcelInput" class="form-control" style="max-width:340px;" accept=".csv,.xls,.xlsx" required>
      <button type="submit" class="btn btn-success rounded-pill fw-bold px-4">Excel / CSV আপলোড</button>
      <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="downloadSampleCakeCSV()">📥 টেমপ্লেট ফাইল ডাউনলোড</button>
    </form>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script>
    function downloadSampleCakeCSV() {
      const csv = "\uFEFFsku,name,category_id,brand_id,regular_price,discount_price,stock,image_url\nSDC-9001,Royal Chocolate Cake,2,1,1800,1450,15,assets/images/cakes/chocolate-truffle.png\n";
      const a = document.createElement("a");
      a.href = URL.createObjectURL(new Blob([csv], {type:"text/csv;charset=utf-8;"}));
      a.download = "cake-bulk-template.csv"; a.click();
    }
    document.getElementById("bulkExcelInput")?.addEventListener("change", async (e) => {
      const f = e.target.files[0];
      if (!f || f.name.toLowerCase().endsWith(".csv") || typeof XLSX === "undefined") return;
      const buf = await f.arrayBuffer();
      const wb = XLSX.read(buf, {type:"array"});
      const csvStr = XLSX.utils.sheet_to_csv(wb.Sheets[wb.SheetNames[0]]);
      const dt = new DataTransfer();
      dt.items.add(new File([csvStr], "converted.csv", {type:"text/csv"}));
      e.target.files = dt.files;
      showToast("✅ Excel ফাইল প্রস্তুত হয়েছে, এখন আপলোড চাপুন!", "success");
    });
    </script>

  <?php elseif ($sub === 'bulk_price'): ?>
    <form method="post">
      <input type="hidden" name="admin_action" value="bulk_price_update">
      <div class="table-responsive"><table class="table table-sm align-middle small">
        <thead class="table-light"><tr><th>SKU</th><th>কেক</th><th>রেগুলার মূল্য</th><th>ডিসকাউন্ট মূল্য</th></tr></thead>
        <tbody><?php foreach (array_slice($rows, 0, 40) as $pr): ?>
          <tr><td><?= e($pr['sku']) ?><input type="hidden" name="pid[]" value="<?= (int)$pr['id'] ?>"></td><td class="fw-bold"><?= e($pr['name']) ?></td><td><input type="number" name="reg[]" value="<?= (float)$pr['regular_price'] ?>" class="form-control form-control-sm"></td><td><input type="number" name="disc[]" value="<?= (float)$pr['discount_price'] ?>" class="form-control form-control-sm"></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
      <button class="btn btn-danger rounded-pill fw-bold px-4">সব মূল্য আপডেট করুন</button>
    </form>

  <?php elseif ($sub === 'bulk_image'): ?>
    <form method="post">
      <input type="hidden" name="admin_action" value="bulk_image_update">
      <div class="table-responsive"><table class="table table-sm align-middle small">
        <thead class="table-light"><tr><th>প্রিভিউ</th><th>কেক</th><th>ইমেজ URL লিংক</th></tr></thead>
        <tbody><?php foreach (array_slice($rows, 0, 30) as $pr): $im = product_images($pr)[0]; ?>
          <tr><td><img src="<?= e(admin_img_src($im)) ?>" width="42" height="42" class="rounded object-fit-cover" style="cursor:zoom-in;" onclick="openImagePopup(this.src)"></td><td class="fw-bold"><?= e($pr['name']) ?><input type="hidden" name="pid[]" value="<?= (int)$pr['id'] ?>"></td><td><input type="text" name="img_url[]" value="<?= e($im) ?>" class="form-control form-control-sm"></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
      <button class="btn btn-danger rounded-pill fw-bold px-4">সব ইমেজ আপডেট করুন</button>
    </form>

  <?php else: ?>
    <div class="digital-filter-bar">
      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="digital-counter-pill">কাউন্টার: <span id="admProdCnt"><?= count($rows) ?> / <?= count($rows) ?></span></span>
        <input type="text" id="admProdSearch" class="form-control form-control-sm" style="width:220px;" placeholder="SKU, নাম বা ব্র্যান্ড..." oninput="filterDigitalTable('admProdTbl','admProdSearch','admProdStat','admProdCnt')">
        <select id="admProdStat" class="form-select form-select-sm" style="width:130px;" onchange="filterDigitalTable('admProdTbl','admProdSearch','admProdStat','admProdCnt')">
          <option value="">সব স্ট্যাটাস</option><option value="active">Active</option><option value="inactive">Deactive</option>
        </select>
      </div>
      <button class="btn btn-sm btn-success rounded-pill fw-bold" onclick="exportTableToCSV('admProdTbl','products.csv')"><i class="fa-solid fa-file-csv me-1"></i>CSV ডাউনলোড</button>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle small" id="admProdTbl">
        <thead class="table-light"><tr><th>ছবি (পপআপ)</th><th>SKU ও নাম</th><th>ক্যাটাগরি / ব্র্যান্ড</th><th>মূল্য</th><th>স্টক</th><th>স্ট্যাটাস</th><th class="no-csv">অ্যাকশন</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): $imgSrc = admin_img_src(product_images($r)[0]); $isAct = (int)$r['status'] === 1; ?>
            <tr data-status="<?= $isAct ? 'active' : 'inactive' ?>">
              <td><img src="<?= e($imgSrc) ?>" width="48" height="48" class="rounded-3 object-fit-cover border" style="cursor:zoom-in;" onclick="openImagePopup(this.src)" title="বড় করে দেখতে ক্লিক করুন"></td>
              <td><span class="badge bg-light text-dark border"><?= e($r['sku']) ?></span><div class="fw-bold"><?= e($r['name']) ?></div></td>
              <td><?= e($r['cat_name']) ?><br><small class="text-muted"><?= e($r['brand_name']) ?></small></td>
              <td><strong class="text-danger"><?= format_tk((float)$r['discount_price']) ?></strong><br><small class="text-muted text-decoration-line-through"><?= format_tk((float)$r['regular_price']) ?></small></td>
              <td>
                <form method="post" class="d-flex gap-1" style="width:95px;">
                  <input type="hidden" name="admin_action" value="quick_stock"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <input type="number" name="stock" value="<?= (int)$r['stock'] ?>" class="form-control form-control-sm text-center">
                  <button class="btn btn-sm btn-outline-success py-0 px-2">✓</button>
                </form>
              </td>
              <td>
                <form method="post">
                  <input type="hidden" name="admin_action" value="toggle_product_status"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button class="btn btn-sm py-0 rounded-pill fw-bold <?= $isAct ? 'btn-success' : 'btn-secondary' ?>"><?= $isAct ? 'Active' : 'Deactive' ?></button>
                </form>
              </td>
              <td class="no-csv">
                <div class="d-flex gap-1 flex-wrap">
                  <a href="../product.php?slug=<?= e($r['slug']) ?>" target="_blank" class="btn btn-sm btn-outline-info py-0" title="প্রিভিউ">👁️</a>
                  <a href="?tab=edit_product&id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary py-0" title="এডিট">✏️</a>
                  <form method="post" onsubmit="return confirm('ডিলিট করবেন?')" class="d-inline">
                    <input type="hidden" name="admin_action" value="delete_product"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger py-0" title="ডিলিট">🗑️</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php elseif ($tab === 'add_product' || $tab === 'edit_product'):
  $editP = null;
  if ($tab === 'edit_product' && !empty($_GET['id'])) {
      $st = $pdo->prepare("SELECT * FROM products WHERE id = ?"); $st->execute([(int)$_GET['id']]); $editP = $st->fetch();
  }
  $eImgs = $editP ? implode("\n", product_images($editP)) : '';
  $eVars = $editP ? (json_decode($editP['variants_json'] ?? '[]', true) ?: []) : [['weight'=>'1 পাউন্ড','flavor'=>'ক্লাসিক','extra'=>0]];
  $savedVariants = $pdo->query("SELECT * FROM variants ORDER BY type, id")->fetchAll();
?>
<div class="bg-white p-4 rounded-4 border shadow-sm">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="fw-bold text-danger mb-0"><?= $editP ? '✏️ প্রোডাক্ট এডিট করুন' : '+ নতুন কেক যোগ করুন (স্মার্ট অটো ডেসক্রিপশন + ভ্যারিয়েন্ট)' ?></h5>
    <button type="button" class="btn btn-sm btn-warning rounded-pill fw-bold" onclick="autoGenerateCakeDesc(true)">✨ অটো ডেসক্রিপশন জেনারেট করুন</button>
  </div>
  <form method="post" enctype="multipart/form-data" class="row g-3">
    <input type="hidden" name="admin_action" value="<?= $editP ? 'edit_product' : 'add_product' ?>">
    <?php if ($editP): ?><input type="hidden" name="edit_id" value="<?= (int)$editP['id'] ?>"><?php endif; ?>
    <div class="col-md-6"><label class="form-label small fw-bold">কেকের নাম * (নাম লিখলেই অটো ডেসক্রিপশন তৈরি হবে)</label><input type="text" name="name" id="prodNameInput" class="form-control" value="<?= e($editP['name'] ?? '') ?>" required oninput="autoGenerateCakeDesc(false)"></div>
    <div class="col-md-3"><label class="form-label small fw-bold">SKU কোড *</label><input type="text" name="sku" class="form-control" value="<?= e($editP['sku'] ?? ('SDC-' . rand(2000,9999))) ?>" required></div>
    <div class="col-md-3"><label class="form-label small fw-bold">স্টক</label><input type="number" name="stock" class="form-control" value="<?= (int)($editP['stock'] ?? 20) ?>" required></div>
    <div class="col-md-4"><label class="form-label small fw-bold">ক্যাটাগরি</label><select name="category_id" class="form-select"><?php foreach ($allCats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= ($editP && $editP['category_id']==$c['id'])?'selected':'' ?>><?= (int)$c['parent_id']>0?'↳ ':'' ?><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label class="form-label small fw-bold">ব্র্যান্ড</label><select name="brand_id" class="form-select"><?php foreach ($allBrands as $b): ?><option value="<?= (int)$b['id'] ?>" <?= ($editP && $editP['brand_id']==$b['id'])?'selected':'' ?>><?= e($b['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><label class="form-label small fw-bold">রেগুলার মূল্য (৳)</label><input type="number" name="regular_price" class="form-control" value="<?= (float)($editP['regular_price'] ?? 1800) ?>" required></div>
    <div class="col-md-2"><label class="form-label small fw-bold">ডিসকাউন্ট মূল্য (৳)</label><input type="number" name="discount_price" class="form-control" value="<?= (float)($editP['discount_price'] ?? 1450) ?>" required></div>

    <div class="col-md-6"><label class="form-label small fw-bold">🖼️ ছবি আপলোড (ডিভাইস থেকে)</label><input type="file" name="image_files[]" class="form-control" accept="image/*" multiple onchange="previewProductFiles(this)"></div>
    <div class="col-md-6"><label class="form-label small fw-bold">🔗 অথবা ইমেজ লিংক (প্রতি লাইনে একটি URL)</label><textarea name="image_urls" id="imgUrlInput" class="form-control" rows="2" oninput="previewProductUrls()"><?= e($eImgs) ?></textarea></div>
    <div class="col-12"><div class="p-2 rounded-3 bg-light border"><div id="liveImagePreviewBox" class="d-flex flex-wrap gap-2"><span class="text-muted small">ইমেজ প্রিভিউ এখানে দেখাবে...</span></div></div></div>

    <div class="col-12">
      <div class="p-3 rounded-3 bg-light border">
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
          <span class="small fw-bold">🎨 ভ্যারিয়েন্ট ও অতিরিক্ত মূল্য (এখানে দাম লিখুন):</span>
          <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" onclick="addVariantRow()">+ ভ্যারিয়েন্ট রো</button>
        </div>
        <?php if (!empty($savedVariants)): ?>
          <div class="d-flex flex-wrap gap-1 mb-2">
            <?php foreach ($savedVariants as $sv): ?>
              <button type="button" class="btn btn-xs btn-sm py-0 px-2 btn-outline-secondary rounded-pill" onclick="addVariantPreset('<?= e($sv['type']) ?>','<?= e($sv['name']) ?>')">+ <?= e($sv['name']) ?></button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <div id="variantRowsWrap" class="d-flex flex-column gap-2">
          <?php foreach ($eVars as $v): ?>
            <div class="row g-2">
              <div class="col-4"><input type="text" name="v_weight[]" class="form-control form-control-sm" value="<?= e($v['weight'] ?? '') ?>" placeholder="সাইজ/ওজন"></div>
              <div class="col-4"><input type="text" name="v_flavor[]" class="form-control form-control-sm" value="<?= e($v['flavor'] ?? '') ?>" placeholder="কালার/ফ্লেভার"></div>
              <div class="col-4"><input type="number" name="v_extra[]" class="form-control form-control-sm" value="<?= (float)($v['extra'] ?? 0) ?>" placeholder="অতিরিক্ত দাম (৳)"></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="col-12"><label class="form-label small fw-bold">সংক্ষিপ্ত ডেসক্রিপশন</label><input type="text" name="short_desc" id="shortDescInput" class="form-control" value="<?= e($editP['short_desc'] ?? '') ?>"></div>
    <div class="col-md-4"><label class="form-label small fw-bold">ডেসক্রিপশন ধাপ ১ (উপাদান ও স্বাদ)</label><textarea name="desc_step1" id="descStep1" class="form-control" rows="3"><?= e($editP['desc_step1'] ?? '') ?></textarea></div>
    <div class="col-md-4"><label class="form-label small fw-bold">ডেসক্রিপশন ধাপ ২ (কাস্টমাইজেশন ও যত্ন)</label><textarea name="desc_step2" id="descStep2" class="form-control" rows="3"><?= e($editP['desc_step2'] ?? '') ?></textarea></div>
    <div class="col-md-4"><label class="form-label small fw-bold">ডেসক্রিপশন ধাপ ৩ (পুষ্টি ও গ্যারান্টি)</label><textarea name="desc_step3" id="descStep3" class="form-control" rows="3"><?= e($editP['desc_step3'] ?? '') ?></textarea></div>

    <div class="col-12">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="defective_flag" id="defFlagChk" <?= !empty($editP['defective_flag']) ? 'checked' : '' ?>>
        <label class="form-check-label small fw-bold text-muted" for="defFlagChk">⚙️ ত্রুটিযুক্ত পণ্য হিসেবে চিহ্নিত করুন</label>
      </div>
    </div>

    <div class="col-12"><button type="submit" class="btn btn-danger btn-lg rounded-pill px-5 fw-bold"><?= $editP ? 'আপডেট সেভ করুন' : 'প্রোডাক্ট পাবলিশ করুন' ?></button></div>
  </form>
</div>
<script>
let descTouched = <?= $editP ? 'true' : 'false' ?>;
['shortDescInput','descStep1','descStep2','descStep3'].forEach(id => document.getElementById(id)?.addEventListener('input', () => descTouched = true));
function autoGenerateCakeDesc(force) {
  if (descTouched && !force) return;
  const n = document.getElementById('prodNameInput').value.trim();
  if (!n) return;
  const low = n.toLowerCase();
  let flavorNote = '১০০% খাঁটি প্রিমিয়াম বাটার, ফ্রেশ হুইপড ক্রিম এবং আমদানিকৃত ফ্লেভার';
  if (low.includes('চকলেট') || low.includes('chocolate')) flavorNote = 'বেলজিয়ান ডার্ক কোকো গানাশ, রিচ চকলেট স্পঞ্জ ও ফ্রেশ ক্রিম';
  else if (low.includes('স্ট্রবেরি') || low.includes('strawberry')) flavorNote = 'তাজা স্ট্রবেরি কম্পোট, সফট ভ্যানিলা স্পঞ্জ ও সিল্কি হুইপড ক্রিম';
  else if (low.includes('ভেলভেট') || low.includes('velvet')) flavorNote = 'অথেন্টিক রেড ভেলভেট স্পঞ্জ ও প্রিমিয়াম ক্রিম-চিজ ফ্রস্টিং';
  else if (low.includes('কিটো') || low.includes('সুগার') || low.includes('sugar') || low.includes('keto')) flavorNote = 'স্টেভিয়া সুইটেনার, আমন্ড ফ্লাওয়ার ও লো-কার্ব হেলদি উপাদান';
  else if (low.includes('বেন্টো') || low.includes('bento')) flavorNote = 'কোরিয়ান মিনি লাঞ্চবক্স স্টাইল সফট স্পঞ্জ ও প্যাস্টেল ক্রিম আর্ট';

  document.getElementById('shortDescInput').value = n + ' — অর্ডার করার পর ফ্রেশ বেকড প্রিমিয়াম কেক, ফ্রি ক্যান্ডেল ও কেক নাইফ সহ।';
  document.getElementById('descStep1').value = `${n} তৈরি করা হয়েছে ${flavorNote} দিয়ে। প্রতিটি অর্ডার পাওয়ার পর সম্পূর্ণ ফ্রেশ বেক করা হয়।`;
  document.getElementById('descStep2').value = `${n}-এর উপর আপনার পছন্দের নাম, বয়স বা শুভেচ্ছা বার্তা ফ্রি কাস্টমাইজ করে নিতে পারবেন। ডেলিভারির পর ঠান্ডা স্থানে সংরক্ষণ করুন।`;
  document.getElementById('descStep3').value = `সম্পূর্ণ স্বাস্থ্যসম্মত ও হালাল উপাদানে প্রস্তুত। মজবুত ফুড-গ্রেড বক্স, ফ্রি কেক নাইফ ও বার্থডে ক্যান্ডেলসহ ২-৪ ঘন্টায় দ্রুত হোম ডেলিভারি।`;
}
function addVariantRow(w = '', f = '', e = 0) {
  const d = document.createElement('div'); d.className = 'row g-2';
  d.innerHTML = `<div class="col-4"><input type="text" name="v_weight[]" class="form-control form-control-sm" value="${w}" placeholder="সাইজ/ওজন"></div><div class="col-4"><input type="text" name="v_flavor[]" class="form-control form-control-sm" value="${f}" placeholder="কালার/ফ্লেভার"></div><div class="col-4"><input type="number" name="v_extra[]" class="form-control form-control-sm" value="${e}" placeholder="অতিরিক্ত দাম (৳)"></div>`;
  document.getElementById('variantRowsWrap').appendChild(d);
}
function addVariantPreset(type, name) {
  if (type === 'size') addVariantRow(name, 'ক্লাসিক', 0);
  else addVariantRow('1 পাউন্ড', name, 0);
}
function previewProductFiles(input) {
  const box = document.getElementById('liveImagePreviewBox'); box.innerHTML = '';
  Array.from(input.files).forEach(f => { const i = document.createElement('img'); i.src = URL.createObjectURL(f); i.style.cssText = 'width:76px;height:76px;object-fit:cover;border-radius:10px;border:2px solid #b84a6b;'; box.appendChild(i); });
}
function previewProductUrls() {
  const box = document.getElementById('liveImagePreviewBox');
  const lines = document.getElementById('imgUrlInput').value.split(/[\r\n,]+/).map(s=>s.trim()).filter(Boolean);
  if (lines.length) box.innerHTML = lines.map(u => `<img src="${u.startsWith('http')?u:'../'+u}" style="width:76px;height:76px;object-fit:cover;border-radius:10px;border:2px solid #b84a6b;">`).join('');
}
previewProductUrls();
</script>

<?php elseif ($tab === 'categories'):
  $editCat = !empty($_GET['edit']) ? $pdo->query("SELECT * FROM categories WHERE id=".(int)$_GET['edit'])->fetch() : null;
?>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="bg-white p-4 rounded-4 border shadow-sm">
      <h6 class="fw-bold text-danger mb-3"><?= $editCat ? '✏️ ক্যাটাগরি এডিট' : '+ ক্যাটাগরি / সাব-ক্যাটাগরি যুক্ত' ?></h6>
      <form method="post" class="d-flex flex-column gap-2">
        <input type="hidden" name="admin_action" value="save_category">
        <input type="hidden" name="id" value="<?= (int)($editCat['id'] ?? 0) ?>">
        <input type="text" name="name" class="form-control" value="<?= e($editCat['name'] ?? '') ?>" placeholder="ক্যাটাগরির নাম *" required>
        <?php if (!$editCat): ?><input type="text" name="slug" class="form-control" placeholder="Slug (ইংরেজিতে)"><?php endif; ?>
        <select name="parent_id" class="form-select">
          <option value="0">মূল ক্যাটাগরি</option>
          <?php foreach ($allCats as $c): if ((int)$c['parent_id'] !== 0) continue; ?>
            <option value="<?= (int)$c['id'] ?>" <?= ($editCat && $editCat['parent_id']==$c['id'])?'selected':'' ?>>সাব-ক্যাটাগরি: <?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <input type="text" name="icon" class="form-control" value="<?= e($editCat['icon'] ?? 'fa-cake-candles') ?>">
        <button class="btn btn-danger rounded-pill fw-bold mt-2"><?= $editCat ? 'আপডেট করুন' : 'সেভ করুন' ?></button>
      </form>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="bg-white p-4 rounded-4 border shadow-sm">
      <div class="digital-filter-bar">
        <span class="digital-counter-pill">ক্যাটাগরি: <span id="catCnt"><?= count($allCats) ?> / <?= count($allCats) ?></span></span>
        <input type="text" id="catSearch" class="form-control form-control-sm" style="width:180px;" placeholder="খুঁজুন..." oninput="filterDigitalTable('catTbl','catSearch','','catCnt')">
        <button class="btn btn-sm btn-success rounded-pill fw-bold" onclick="exportTableToCSV('catTbl','categories.csv')">CSV</button>
      </div>
      <table class="table table-sm align-middle small" id="catTbl">
        <thead class="table-light"><tr><th>ID</th><th>নাম</th><th>ধরণ</th><th>পণ্য</th><th class="no-csv">অ্যাকশন</th></tr></thead>
        <tbody><?php foreach ($allCats as $c): $pId = (int)$c['parent_id']; ?>
          <tr>
            <td><?= (int)$c['id'] ?></td>
            <td class="fw-bold"><?= $pId > 0 ? '↳ ' : '' ?><?= e($c['name']) ?></td>
            <td><?= $pId > 0 ? '<span class="badge bg-info text-dark">সাব: ' . e($catNameMap[$pId] ?? '') . '</span>' : '<span class="badge bg-danger">মূল</span>' ?></td>
            <td><span class="badge bg-light text-dark border"><?= (int)$c['p_count'] ?></span></td>
            <td class="no-csv">
              <a href="?tab=categories&edit=<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-primary py-0">এডিট</a>
              <form method="post" class="d-inline" onsubmit="return confirm('ডিলিট করবেন?')"><input type="hidden" name="admin_action" value="delete_category"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn btn-sm btn-outline-danger py-0">ডিলিট</button></form>
            </td>
          </tr>
        <?php endforeach; ?></tbody>
      </table>
    </div>
  </div>
</div>

<?php elseif ($tab === 'brands'):
  $editBr = !empty($_GET['edit']) ? $pdo->query("SELECT * FROM brands WHERE id=".(int)$_GET['edit'])->fetch() : null;
?>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="bg-white p-4 rounded-4 border shadow-sm mb-3">
      <h6 class="fw-bold text-danger mb-3"><?= $editBr ? '✏️ ব্র্যান্ড এডিট' : '+ নতুন ব্র্যান্ড যুক্ত করুন' ?></h6>
      <form method="post" enctype="multipart/form-data" class="d-flex flex-column gap-2">
        <input type="hidden" name="admin_action" value="save_brand">
        <input type="hidden" name="id" value="<?= (int)($editBr['id'] ?? 0) ?>">
        <input type="text" name="name" class="form-control" value="<?= e($editBr['name'] ?? '') ?>" placeholder="ব্র্যান্ডের নাম *" required>
        <input type="file" name="logo_file" class="form-control form-control-sm" accept="image/*">
        <input type="text" name="logo" class="form-control form-control-sm" value="<?= e($editBr['logo'] ?? '') ?>" placeholder="অথবা লোগো URL">
        <textarea name="description" class="form-control" rows="2" placeholder="বিবরণ"><?= e($editBr['description'] ?? '') ?></textarea>
        <button class="btn btn-danger rounded-pill fw-bold"><?= $editBr ? 'আপডেট করুন' : 'ব্র্যান্ড সেভ করুন' ?></button>
      </form>
    </div>
    <!-- + ভ্যারিয়েন্ট যুক্ত Moved Inside Brands Page (#14) -->
    <div class="bg-white p-4 rounded-4 border shadow-sm">
      <h6 class="fw-bold text-primary mb-2">+ ভ্যারিয়েন্ট যুক্ত করুন</h6>
      <form method="post" class="d-flex flex-column gap-2">
        <input type="hidden" name="admin_action" value="save_variant">
        <select name="type" class="form-select form-select-sm"><option value="size">সাইজ / ওজন</option><option value="color">কালার / ফ্লেভার</option></select>
        <input type="text" name="name" class="form-control form-control-sm" placeholder="ভ্যারিয়েন্টের নাম *" required>
        <input type="text" name="code" class="form-control form-control-sm" placeholder="কোড / হেক্স কালার">
        <button class="btn btn-primary btn-sm rounded-pill fw-bold">ভ্যারিয়েন্ট যুক্ত করুন</button>
      </form>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="bg-white p-4 rounded-4 border shadow-sm">
      <div class="digital-filter-bar">
        <span class="digital-counter-pill">ব্র্যান্ড: <span id="brCnt"><?= count($allBrands) ?> / <?= count($allBrands) ?></span></span>
        <input type="text" id="brSearch" class="form-control form-control-sm" style="width:180px;" placeholder="খুঁজুন..." oninput="filterDigitalTable('brTbl','brSearch','','brCnt')">
        <button class="btn btn-sm btn-success rounded-pill fw-bold" onclick="exportTableToCSV('brTbl','brands.csv')">CSV</button>
      </div>
      <table class="table table-sm align-middle small" id="brTbl">
        <thead class="table-light"><tr><th>লোগো</th><th>নাম</th><th>বিবরণ</th><th>পণ্য</th><th class="no-csv">অ্যাকশন</th></tr></thead>
        <tbody><?php foreach ($allBrands as $b): ?>
          <tr>
            <td><img src="<?= e(admin_img_src($b['logo'] ?: 'assets/images/icon-192.png')) ?>" width="34" height="34" class="rounded-2 object-fit-cover border"></td>
            <td class="fw-bold"><?= e($b['name']) ?></td><td><?= e($b['description']) ?></td>
            <td><span class="badge bg-light text-dark border"><?= (int)$b['p_count'] ?></span></td>
            <td class="no-csv">
              <a href="?tab=brands&edit=<?= (int)$b['id'] ?>" class="btn btn-sm btn-outline-primary py-0">এডিট</a>
              <form method="post" class="d-inline" onsubmit="return confirm('ডিলিট করবেন?')"><input type="hidden" name="admin_action" value="delete_brand"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><button class="btn btn-sm btn-outline-danger py-0">ডিলিট</button></form>
            </td>
          </tr>
        <?php endforeach; ?></tbody>
      </table>
    </div>
  </div>
</div>

<?php elseif ($tab === 'variants'):
  $vars = $pdo->query("SELECT * FROM variants ORDER BY type, id")->fetchAll();
  $editVar = !empty($_GET['edit']) ? $pdo->query("SELECT * FROM variants WHERE id=".(int)$_GET['edit'])->fetch() : null;
?>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="bg-white p-4 rounded-4 border shadow-sm">
      <h6 class="fw-bold text-danger mb-3"><?= $editVar ? '✏️ কালার ও সাইজ এডিট' : '🎨 নতুন কালার ও সাইজ' ?></h6>
      <form method="post" class="d-flex flex-column gap-2">
        <input type="hidden" name="admin_action" value="save_variant">
        <input type="hidden" name="id" value="<?= (int)($editVar['id'] ?? 0) ?>">
        <select name="type" class="form-select"><option value="size" <?= ($editVar && $editVar['type']==='size')?'selected':'' ?>>সাইজ / ওজন</option><option value="color" <?= ($editVar && $editVar['type']==='color')?'selected':'' ?>>কালার / ফ্লেভার</option></select>
        <input type="text" name="name" class="form-control" value="<?= e($editVar['name'] ?? '') ?>" placeholder="নাম *" required>
        <input type="text" name="code" class="form-control" value="<?= e($editVar['code'] ?? '') ?>" placeholder="কোড / কালার হেক্স (যেমন: #b84a6b)">
        <button class="btn btn-danger rounded-pill fw-bold mt-2"><?= $editVar ? 'আপডেট করুন' : 'যুক্ত করুন' ?></button>
      </form>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="bg-white p-4 rounded-4 border shadow-sm">
      <div class="digital-filter-bar">
        <span class="digital-counter-pill">কালার ও সাইজ: <span id="varCnt"><?= count($vars) ?> / <?= count($vars) ?></span></span>
        <input type="text" id="varSearch" class="form-control form-control-sm" style="width:180px;" placeholder="খুঁজুন..." oninput="filterDigitalTable('varTbl','varSearch','','varCnt')">
        <button class="btn btn-sm btn-success rounded-pill fw-bold" onclick="exportTableToCSV('varTbl','color-size.csv')">CSV</button>
      </div>
      <table class="table table-sm align-middle small" id="varTbl">
        <thead class="table-light"><tr><th>ধরণ</th><th>নাম</th><th>কোড</th><th class="no-csv">অ্যাকশন</th></tr></thead>
        <tbody><?php foreach ($vars as $v): ?>
          <tr>
            <td><span class="badge bg-secondary"><?= e($v['type']) ?></span></td>
            <td class="fw-bold"><?= e($v['name']) ?></td>
            <td>
              <?php if (preg_match('/^#[0-9a-fA-F]{3,6}$/', trim($v['code']))): ?>
                <span style="display:inline-block;width:14px;height:14px;border-radius:4px;background:<?= e($v['code']) ?>;vertical-align:middle;border:1px solid #ccc;margin-right:4px;"></span>
              <?php endif; ?>
              <?= e($v['code']) ?>
            </td>
            <td class="no-csv">
              <a href="?tab=variants&edit=<?= (int)$v['id'] ?>" class="btn btn-sm btn-outline-primary py-0">এডিট</a>
              <form method="post" class="d-inline" onsubmit="return confirm('ডিলিট করবেন?')"><input type="hidden" name="admin_action" value="delete_variant"><input type="hidden" name="id" value="<?= (int)$v['id'] ?>"><button class="btn btn-sm btn-outline-danger py-0">ডিলিট</button></form>
            </td>
          </tr>
        <?php endforeach; ?></tbody>
      </table>
    </div>
  </div>
</div>

<?php elseif ($tab === 'banners'):
  $bans = $pdo->query("SELECT * FROM banners ORDER BY sort_order ASC, id DESC")->fetchAll();
  $editBan = !empty($_GET['edit']) ? $pdo->query("SELECT * FROM banners WHERE id=".(int)$_GET['edit'])->fetch() : null;
?>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="bg-white p-4 rounded-4 border shadow-sm">
      <h6 class="fw-bold text-danger mb-3"><?= $editBan ? '✏️ ব্যানার এডিট' : '🖼️ নতুন ব্যানার যুক্ত করুন' ?></h6>
      <form method="post" enctype="multipart/form-data" class="d-flex flex-column gap-2">
        <input type="hidden" name="admin_action" value="save_banner">
        <input type="hidden" name="id" value="<?= (int)($editBan['id'] ?? 0) ?>">
        <input type="text" name="title" class="form-control" value="<?= e($editBan['title'] ?? '') ?>" placeholder="শিরোনাম *" required>
        <input type="text" name="subtitle" class="form-control" value="<?= e($editBan['subtitle'] ?? '') ?>" placeholder="সাবটাইটেল">
        <input type="text" name="badge_text" class="form-control" value="<?= e($editBan['badge_text'] ?? '') ?>" placeholder="ব্যাজ টেক্সট">
        <input type="text" name="btn_text" class="form-control" value="<?= e($editBan['btn_text'] ?? 'অর্ডার করুন') ?>">
        <input type="text" name="btn_link" class="form-control" value="<?= e($editBan['btn_link'] ?? 'products.php') ?>">
        <input type="number" name="sort_order" class="form-control" value="<?= (int)($editBan['sort_order'] ?? 1) ?>" placeholder="ক্রম">
        <input type="file" name="banner_file" class="form-control form-control-sm" accept="image/*">
        <input type="text" name="image_url" class="form-control form-control-sm" value="<?= e($editBan['image'] ?? '') ?>" placeholder="অথবা ইমেজ URL">
        <button class="btn btn-danger rounded-pill fw-bold mt-2"><?= $editBan ? 'ব্যানার আপডেট করুন' : 'ব্যানার পাবলিশ করুন' ?></button>
      </form>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="bg-white p-4 rounded-4 border shadow-sm">
      <div class="digital-filter-bar">
        <span class="digital-counter-pill">মোট ব্যানার: <span id="banCnt"><?= count($bans) ?> / <?= count($bans) ?></span></span>
        <input type="text" id="banSearch" class="form-control form-control-sm" style="width:180px;" placeholder="খুঁজুন..." oninput="filterDigitalTable('banTbl','banSearch','','banCnt')">
        <button class="btn btn-sm btn-success rounded-pill fw-bold" onclick="exportTableToCSV('banTbl','banners.csv')">CSV</button>
      </div>
      <table class="table table-sm align-middle small" id="banTbl">
        <thead class="table-light"><tr><th>ছবি</th><th>শিরোনাম</th><th>স্ট্যাটাস</th><th class="no-csv">অ্যাকশন</th></tr></thead>
        <tbody><?php foreach ($bans as $b): $bAct = (int)$b['status'] === 1; ?>
          <tr>
            <td><img src="<?= e(admin_img_src($b['image'])) ?>" width="86" height="36" class="rounded object-fit-cover" style="cursor:zoom-in;" onclick="openImagePopup(this.src)"></td>
            <td class="fw-bold"><?= e($b['title']) ?><br><small class="text-muted"><?= e($b['btn_link']) ?></small></td>
            <td>
              <form method="post">
                <input type="hidden" name="admin_action" value="toggle_banner_status"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                <button class="btn btn-sm py-0 rounded-pill <?= $bAct?'btn-success':'btn-secondary' ?>"><?= $bAct?'Active':'Off' ?></button>
              </form>
            </td>
            <td class="no-csv">
              <a href="?tab=banners&edit=<?= (int)$b['id'] ?>" class="btn btn-sm btn-outline-primary py-0">এডিট</a>
              <form method="post" class="d-inline" onsubmit="return confirm('ডিলিট করবেন?')"><input type="hidden" name="admin_action" value="delete_banner"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><button class="btn btn-sm btn-outline-danger py-0">ডিলিট</button></form>
            </td>
          </tr>
        <?php endforeach; ?></tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>
