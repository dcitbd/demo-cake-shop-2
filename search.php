<?php
$q = trim($_GET['q'] ?? '');
$pageTitle = 'সার্চ রেজাল্ট: ' . $q;
require_once __DIR__ . '/includes/header.php';
$pdo = db();
$favIds = get_favorite_ids();

$items = [];
if ($q !== '') {
    $like = '%' . $q . '%';
    $stmt = $pdo->prepare("SELECT p.*, b.name AS brand_name FROM products p LEFT JOIN brands b ON b.id = p.brand_id LEFT JOIN categories c ON c.id = p.category_id WHERE p.status=1 AND (p.name LIKE ? OR p.sku LIKE ? OR p.short_desc LIKE ? OR c.name LIKE ? OR b.name LIKE ?) ORDER BY p.id DESC");
    $stmt->execute([$like, $like, $like, $like, $like]);
    $items = $stmt->fetchAll();
}
?>
<div class="container py-4">
  <div class="digital-filter-bar mb-4">
    <div>
      <h4 class="fw-bold mb-0"><i class="fa-solid fa-magnifying-glass text-danger me-2"></i>সার্চ রেজাল্ট: "<?= e($q) ?>"</h4>
    </div>
    <span class="digital-counter-pill">মোট <?= count($items) ?>টি কেক পাওয়া গেছে</span>
  </div>
  <?php if (empty($items)): ?>
    <div class="bg-white p-5 rounded-4 border text-center">
      <i class="fa-solid fa-face-meh fs-1 text-muted mb-3"></i>
      <h5>"<?= e($q) ?>" দিয়ে কোনো কেক পাওয়া যায়নি</h5>
      <a href="products.php" class="btn btn-danger rounded-pill mt-2">সকল কেক দেখুন</a>
    </div>
  <?php else: ?>
    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-2 g-md-3">
      <?php foreach ($items as $p): ?>
        <div class="col"><?= render_cake_card($p, $p['brand_name'] ?? 'Cake Shop BD', $favIds) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
