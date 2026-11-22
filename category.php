<?php
require_once __DIR__ . '/includes/functions.php';
$pdo = db();
$slug = trim($_GET['slug'] ?? '');
$favIds = get_favorite_ids();

if ($slug !== '') {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ?");
    $stmt->execute([$slug]);
    $cat = $stmt->fetch();
} else {
    $cat = null;
}

$pageTitle = $cat ? $cat['name'] : 'সকল কেক ক্যাটাগরি';
require_once __DIR__ . '/includes/header.php';
$allCats = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.status=1) as p_count FROM categories c WHERE c.status=1 ORDER BY c.sort_order ASC")->fetchAll();
?>

<div class="container py-3 py-md-4">
  <?php if ($cat):
    $pStmt = $pdo->prepare("SELECT p.*, b.name AS brand_name FROM products p LEFT JOIN brands b ON b.id = p.brand_id LEFT JOIN categories c ON c.id = p.category_id WHERE p.status=1 AND (p.category_id = ? OR c.parent_id = ?) ORDER BY p.id DESC");
    $pStmt->execute([$cat['id'], $cat['id']]);
    $items = $pStmt->fetchAll();
  ?>
    <div class="p-3 p-md-4 rounded-4 mb-4 d-flex flex-wrap justify-content-between align-items-center gap-2 soft-brand-banner shadow-sm">
      <div>
        <a href="category.php" class="text-warning small">&larr; সকল ক্যাটাগরি</a>
        <h2 class="fw-bold mb-1 mt-1"><i class="fa-solid <?= e($cat['icon']) ?> me-2"></i><?= e($cat['name']) ?></h2>
        <p class="mb-0 small opacity-90">মোট <?= count($items) ?>টি প্রিমিয়াম কেক পাওয়া গেছে</p>
      </div>
      <a href="products.php?category=<?= (int)$cat['id'] ?>" class="btn btn-warning btn-sm rounded-pill fw-bold px-3">ফিল্টারসহ দেখুন</a>
    </div>
    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-2 g-md-3">
      <?php foreach ($items as $p): ?>
        <div class="col"><?= render_cake_card($p, $p['brand_name'] ?? 'Cake Shop BD', $favIds) ?></div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <h2 class="fw-bold mb-4"><i class="fa-solid fa-layer-group text-danger me-2"></i>সকল কেক ক্যাটাগরি</h2>
    <div class="row g-2 g-md-3">
      <?php foreach ($allCats as $c): ?>
        <div class="col-6 col-md-4 col-lg-3">
          <a href="category.php?slug=<?= e($c['slug']) ?>" class="benefit-3d-card d-block text-center">
            <img src="<?= e(asset_url($c['image'] ?: 'assets/images/cakes/chocolate-truffle.png')) ?>" alt="<?= e($c['name']) ?>" class="rounded-4 mb-2 mb-md-3 w-100" style="height:130px;object-fit:cover;">
            <h6 class="fw-bold mb-1 small"><?= e($c['name']) ?></h6>
            <span class="badge bg-danger-subtle text-danger rounded-pill"><?= (int)$c['p_count'] ?>+ কেক</span>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
