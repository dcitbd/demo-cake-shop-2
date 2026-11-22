<?php
$pageTitle = 'সকল কেক কালেকশন';
require_once __DIR__ . '/includes/header.php';

$pdo = db();
$favIds = get_favorite_ids();

$catId = (int)($_GET['category'] ?? 0);
$brandId = (int)($_GET['brand'] ?? 0);
$minRating = (float)($_GET['rating'] ?? 0);
$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : 15000;
$stockFilter = $_GET['stock'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 48;

$where = ['p.status = 1'];
$params = [];

if ($catId > 0) { $where[] = '(p.category_id = ? OR c.parent_id = ?)'; $params[] = $catId; $params[] = $catId; }
if ($brandId > 0) { $where[] = 'p.brand_id = ?'; $params[] = $brandId; }
if ($minRating > 0) { $where[] = 'p.rating >= ?'; $params[] = $minRating; }
if ($minPrice > 0) { $where[] = 'p.discount_price >= ?'; $params[] = $minPrice; }
if ($maxPrice < 15000) { $where[] = 'p.discount_price <= ?'; $params[] = $maxPrice; }
if ($stockFilter === 'in_stock') $where[] = 'p.stock >= 5';
elseif ($stockFilter === 'low_stock') $where[] = 'p.stock > 0 AND p.stock < 5';
elseif ($stockFilter === 'out_of_stock') $where[] = 'p.stock = 0';

switch ($sort) {
    case 'price_asc':  $orderBy = 'p.discount_price ASC'; break;
    case 'price_desc': $orderBy = 'p.discount_price DESC'; break;
    case 'discount':   $orderBy = 'p.discount_percent DESC'; break;
    case 'rating':     $orderBy = 'p.rating DESC'; break;
    default:           $orderBy = 'p.id DESC'; break;
}

$whereSql = implode(' AND ', $where);
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE {$whereSql}");
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalItems / $perPage));
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT p.*, b.name AS brand_name FROM products p LEFT JOIN brands b ON b.id = p.brand_id LEFT JOIN categories c ON c.id = p.category_id WHERE {$whereSql} ORDER BY {$orderBy} LIMIT {$perPage} OFFSET {$offset}");
$stmt->execute($params);
$products = $stmt->fetchAll();

$allCats = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.status=1) as p_count FROM categories c WHERE c.status = 1 ORDER BY c.sort_order ASC")->fetchAll();
$parentCats = [];
$childCats = [];
$catById = [];
$activeParentId = 0;
foreach ($allCats as $c) {
    $catById[(int)$c['id']] = $c;
    if ((int)$c['parent_id'] === 0) {
        $parentCats[] = $c;
    } else {
        $childCats[(int)$c['parent_id']][] = $c;
        if ((int)$c['id'] === $catId) $activeParentId = (int)$c['parent_id'];
    }
}
$brands = $pdo->query("SELECT b.*, (SELECT COUNT(*) FROM products p WHERE p.brand_id = b.id AND p.status=1) as p_count FROM brands b WHERE b.status = 1")->fetchAll();
$brandById = [];
foreach ($brands as $br) $brandById[(int)$br['id']] = $br;
$totalActiveProducts = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status=1")->fetchColumn();

$activeCount = ($catId > 0 ? 1 : 0) + ($brandId > 0 ? 1 : 0) + ($minRating > 0 ? 1 : 0) + ($minPrice > 0 || $maxPrice < 15000 ? 1 : 0) + ($stockFilter !== '' ? 1 : 0);
?>

<div class="container py-3 py-md-4">
  <div class="row g-3 g-lg-4">
    <!-- Left Sidebar: Luxury Digital Filter Studio + Mobile Toggle Drawer (#1, #3) -->
    <aside class="col-lg-3">
      <div class="d-lg-none mb-2">
        <button type="button" class="mobile-filter-toggle-btn" onclick="toggleMobileFilterDrawer()">
          <span><i class="fa-solid fa-sliders me-2"></i>ডিজিটাল ফিল্টার ও ক্যাটাগরি</span>
          <span class="badge bg-warning text-dark rounded-pill"><?= $activeCount > 0 ? $activeCount . ' সক্রিয়' : 'খুলুন' ?></span>
        </button>
      </div>

      <div id="mobileFilterWrap" class="mobile-filter-collapse d-lg-block">
        <form method="get" action="products.php" id="productFilterForm" class="filter-studio-card">
          <div class="filter-studio-header">
            <div class="d-flex align-items-center gap-2">
              <i class="fa-solid fa-sliders"></i>
              <span class="fw-bold">ডিজিটাল ফিল্টার</span>
              <?php if ($activeCount > 0): ?>
                <span class="badge bg-warning text-dark rounded-pill"><?= $activeCount ?></span>
              <?php endif; ?>
            </div>
            <a href="products.php" class="btn btn-sm btn-light rounded-pill py-0 px-2 fw-bold text-danger" style="font-size:12px;">রিসেট</a>
          </div>

          <div class="filter-studio-body">
            <!-- 1. Click-to-Open Category Tree -->
            <div class="filter-block">
              <div class="filter-block-title">
                <span><i class="fa-solid fa-diagram-project me-1 text-danger"></i>ক্যাটাগরি ট্রি</span>
                <span class="badge bg-white text-danger border rounded-pill"><?= count($parentCats) ?></span>
              </div>
              <input type="hidden" name="category" id="filterCatInput" value="<?= $catId ?>">
              <div>
                <div class="cat-tree-row <?= $catId === 0 ? 'active' : '' ?>" style="cursor:pointer;" onclick="selectTreeCat(0)">
                  <span><i class="fa-solid fa-border-all me-1 text-danger"></i>সকল ক্যাটাগরি</span>
                  <span class="badge <?= $catId === 0 ? 'bg-white text-danger' : 'bg-light text-dark border' ?> rounded-pill"><?= $totalActiveProducts ?></span>
                </div>
                <?php foreach ($parentCats as $pc):
                  $pid = (int)$pc['id'];
                  $hasKids = !empty($childCats[$pid]);
                  $isOpen = ($catId === $pid || $activeParentId === $pid);
                  $totalBranchCount = (int)$pc['p_count'];
                  if ($hasKids) {
                      foreach ($childCats[$pid] as $ccItem) $totalBranchCount += (int)$ccItem['p_count'];
                  }
                ?>
                  <div>
                    <div class="cat-tree-row <?= $catId === $pid ? 'active' : '' ?>">
                      <span style="cursor:pointer;flex:1;" onclick="selectTreeCat(<?= $pid ?>)">
                        <i class="fa-solid <?= e($pc['icon']) ?> me-1 text-danger"></i><?= e($pc['name']) ?>
                      </span>
                      <div class="d-flex align-items-center gap-1">
                        <span class="badge <?= $catId === $pid ? 'bg-white text-danger' : 'bg-light text-dark border' ?> rounded-pill"><?= $totalBranchCount ?></span>
                        <?php if ($hasKids): ?>
                          <button type="button" class="cat-tree-toggle" onclick="toggleCatTree(<?= $pid ?>, this, event)" title="সাব-ক্যাটাগরি দেখুন">
                            <i class="fa-solid fa-chevron-<?= $isOpen ? 'down' : 'right' ?>"></i>
                          </button>
                        <?php endif; ?>
                      </div>
                    </div>
                    <?php if ($hasKids): ?>
                      <div class="cat-tree-children <?= $isOpen ? 'open' : '' ?>" id="catKids_<?= $pid ?>">
                        <?php foreach ($childCats[$pid] as $cc): ?>
                          <div class="cat-sub-pill <?= $catId === (int)$cc['id'] ? 'active' : '' ?>" onclick="selectTreeCat(<?= (int)$cc['id'] ?>)">
                            <span>↳ <?= e($cc['name']) ?></span>
                            <span><?= (int)$cc['p_count'] ?></span>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- 2. Price Ratio Filter -->
            <div class="filter-block">
              <div class="filter-block-title">
                <span><i class="fa-solid fa-bangladeshi-taka-sign me-1 text-success"></i>মূল্য সীমা</span>
                <span class="price-live-badge" id="priceRangeLabel">সর্বোচ্চ ৳<?= number_format(min(10000, $maxPrice)) ?></span>
              </div>
              <div class="row g-2 mb-2">
                <div class="col-6">
                  <input type="number" name="min_price" class="form-control form-control-sm rounded-3 auto-filter-debounced" placeholder="সর্বনিম্ন ৳" value="<?= $minPrice ?: '' ?>">
                </div>
                <div class="col-6">
                  <input type="number" name="max_price" id="maxPriceInput" class="form-control form-control-sm rounded-3 auto-filter-debounced" placeholder="সর্বোচ্চ ৳" value="<?= $maxPrice < 15000 ? $maxPrice : '' ?>">
                </div>
              </div>
              <input type="range" id="priceRangeSlider" class="form-range filter-range-rose" min="500" max="10000" step="100" value="<?= min(10000, $maxPrice) ?>">
            </div>

            <!-- 3. Brand Filter -->
            <div class="filter-block">
              <div class="filter-block-title">
                <span><i class="fa-solid fa-crown me-1 text-warning"></i>কেক ব্র্যান্ড</span>
              </div>
              <select name="brand" class="form-select form-select-sm rounded-3 auto-filter-input">
                <option value="0">সকল ব্র্যান্ড</option>
                <?php foreach ($brands as $br): ?>
                  <option value="<?= (int)$br['id'] ?>" <?= $brandId === (int)$br['id'] ? 'selected' : '' ?>><?= e($br['name']) ?> (<?= (int)$br['p_count'] ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- 4. Interactive Star Rating Pills -->
            <div class="filter-block">
              <div class="filter-block-title">
                <span><i class="fa-solid fa-star me-1 text-warning"></i>কাস্টমার রেটিং</span>
              </div>
              <input type="hidden" name="rating" id="filterRatingInput" value="<?= $minRating ?: '' ?>">
              <div class="filter-chip-grid">
                <button type="button" class="filter-chip-btn <?= $minRating == 0 ? 'active' : '' ?>" onclick="setChipFilter('filterRatingInput','0')">সব রেটিং</button>
                <button type="button" class="filter-chip-btn <?= $minRating == 4.8 ? 'active' : '' ?>" onclick="setChipFilter('filterRatingInput','4.8')">★ 4.8+</button>
                <button type="button" class="filter-chip-btn <?= $minRating == 4.5 ? 'active' : '' ?>" onclick="setChipFilter('filterRatingInput','4.5')">★ 4.5+</button>
                <button type="button" class="filter-chip-btn <?= $minRating == 4.0 ? 'active' : '' ?>" onclick="setChipFilter('filterRatingInput','4.0')">★ 4.0+</button>
              </div>
            </div>

            <!-- 5. Interactive Stock Status Chips -->
            <div class="filter-block">
              <div class="filter-block-title">
                <span><i class="fa-solid fa-boxes-stacked me-1 text-primary"></i>স্টক স্ট্যাটাস</span>
              </div>
              <input type="hidden" name="stock" id="filterStockInput" value="<?= e($stockFilter) ?>">
              <div class="filter-chip-grid">
                <button type="button" class="filter-chip-btn <?= $stockFilter === '' ? 'active' : '' ?>" onclick="setChipFilter('filterStockInput','')">সকল স্টক</button>
                <button type="button" class="filter-chip-btn <?= $stockFilter === 'in_stock' ? 'active' : '' ?>" onclick="setChipFilter('filterStockInput','in_stock')">✅ ইন স্টক</button>
                <button type="button" class="filter-chip-btn <?= $stockFilter === 'low_stock' ? 'active' : '' ?>" onclick="setChipFilter('filterStockInput','low_stock')">⚠️ লো স্টক</button>
                <button type="button" class="filter-chip-btn <?= $stockFilter === 'out_of_stock' ? 'active' : '' ?>" onclick="setChipFilter('filterStockInput','out_of_stock')">🚫 স্টক আউট</button>
              </div>
            </div>
          </div>
          <input type="hidden" name="sort" value="<?= e($sort) ?>">
        </form>
      </div>
    </aside>

    <!-- Main Product Grid (2-Column on Mobile, 4-Column on Desktop) -->
    <div class="col-lg-9">
      <div class="digital-filter-bar">
        <div class="d-flex align-items-center flex-wrap gap-2">
          <span class="digital-counter-pill"><i class="fa-solid fa-cake-candles"></i>মোট <?= $totalItems ?>টি কেক</span>
          <span class="small text-muted">পেজ <?= $page ?> / <?= $totalPages ?></span>
        </div>
        <form method="get" class="d-flex align-items-center gap-2">
          <?php foreach ($_GET as $k => $v): if ($k === 'sort' || $k === 'page') continue; ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endforeach; ?>
          <select name="sort" class="form-select form-select-sm rounded-pill" onchange="this.form.submit()">
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>নতুন কেক আগে</option>
            <option value="discount" <?= $sort === 'discount' ? 'selected' : '' ?>>সর্বোচ্চ ডিসকাউন্ট</option>
            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>কম দাম থেকে বেশি</option>
            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>বেশি দাম থেকে কম</option>
            <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>সর্বোচ্চ রেটিং</option>
          </select>
        </form>
      </div>

      <?php if ($activeCount > 0): ?>
        <div class="active-filter-tags">
          <?php if ($catId > 0 && isset($catById[$catId])): ?>
            <span class="active-filter-tag">ক্যাটাগরি: <?= e($catById[$catId]['name']) ?> <a href="javascript:void(0)" onclick="selectTreeCat(0)" class="text-danger">&times;</a></span>
          <?php endif; ?>
          <?php if ($brandId > 0 && isset($brandById[$brandId])): ?>
            <span class="active-filter-tag">ব্র্যান্ড: <?= e($brandById[$brandId]['name']) ?></span>
          <?php endif; ?>
          <?php if ($minRating > 0): ?>
            <span class="active-filter-tag">রেটিং: ★ <?= $minRating ?>+ <a href="javascript:void(0)" onclick="setChipFilter('filterRatingInput','0')" class="text-danger">&times;</a></span>
          <?php endif; ?>
          <?php if ($stockFilter !== ''): ?>
            <span class="active-filter-tag">স্টক: <?= e($stockFilter) ?> <a href="javascript:void(0)" onclick="setChipFilter('filterStockInput','')" class="text-danger">&times;</a></span>
          <?php endif; ?>
          <a href="products.php" class="active-filter-tag bg-white text-danger">সব মুছুন</a>
        </div>
      <?php endif; ?>

      <?php if (empty($products)): ?>
        <div class="text-center p-5 bg-white rounded-4 border">
          <i class="fa-solid fa-cake-candles fs-1 text-muted mb-3"></i>
          <h5>এই ফিল্টারে কোনো কেক পাওয়া যায়নি</h5>
          <a href="products.php" class="btn btn-danger rounded-pill mt-2">সব কেক দেখুন</a>
        </div>
      <?php else: ?>
        <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-2 g-md-3">
          <?php foreach ($products as $p): ?>
            <div class="col"><?= render_cake_card($p, $p['brand_name'] ?? 'Cake Shop BD', $favIds) ?></div>
          <?php endforeach; ?>
        </div>
        <?= render_pagination($page, $totalPages, $_GET) ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function toggleMobileFilterDrawer() {
  document.getElementById('mobileFilterWrap')?.classList.toggle('open');
}
function toggleCatTree(pid, btn, ev) {
  if (ev) ev.stopPropagation();
  const el = document.getElementById('catKids_' + pid);
  if (!el) return;
  const open = el.classList.toggle('open');
  const ic = btn.querySelector('i');
  if (ic) ic.className = 'fa-solid fa-chevron-' + (open ? 'down' : 'right');
}
function selectTreeCat(id) {
  document.getElementById('filterCatInput').value = id;
  document.getElementById('productFilterForm').submit();
}
function setChipFilter(inputId, val) {
  document.getElementById(inputId).value = val;
  document.getElementById('productFilterForm').submit();
}
document.querySelectorAll('.auto-filter-input').forEach(el => {
  el.addEventListener('change', () => document.getElementById('productFilterForm').submit());
});
let priceTimer;
document.querySelectorAll('.auto-filter-debounced').forEach(el => {
  el.addEventListener('input', () => {
    clearTimeout(priceTimer);
    priceTimer = setTimeout(() => document.getElementById('productFilterForm').submit(), 550);
  });
});
const rangeSlider = document.getElementById('priceRangeSlider');
if (rangeSlider) {
  rangeSlider.addEventListener('input', () => {
    document.getElementById('maxPriceInput').value = rangeSlider.value;
    const lbl = document.getElementById('priceRangeLabel');
    if (lbl) lbl.textContent = 'সর্বোচ্চ ৳' + Number(rangeSlider.value).toLocaleString();
  });
  rangeSlider.addEventListener('change', () => {
    document.getElementById('maxPriceInput').value = rangeSlider.value;
    document.getElementById('productFilterForm').submit();
  });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
