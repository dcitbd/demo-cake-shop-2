<?php
$pageTitle = 'ফেভরিট লিস্ট';
require_once __DIR__ . '/includes/header.php';
$favIds = get_favorite_ids();
$items = [];
if (!empty($favIds)) {
    $in = implode(',', array_fill(0, count($favIds), '?'));
    $stmt = db()->prepare("SELECT p.*, b.name AS brand_name FROM products p LEFT JOIN brands b ON b.id = p.brand_id WHERE p.id IN ($in)");
    $stmt->execute($favIds);
    $items = $stmt->fetchAll();
}
?>
<div class="container py-3 py-md-4">
  <div class="digital-filter-bar mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-heart text-danger me-2"></i>আমার ফেভরিট কেক লিস্ট</h4>
    <span class="digital-counter-pill"><?= count($items) ?>টি কেক</span>
  </div>
  <?php if (empty($items)): ?>
    <div class="bg-white p-5 rounded-4 border text-center">
      <i class="fa-regular fa-heart fs-1 text-muted mb-3"></i>
      <h5>আপনার ফেভরিট লিস্টে এখনো কোনো কেক নেই</h5>
      <a href="products.php" class="btn btn-danger rounded-pill mt-2">কেক ব্রাউজ করুন</a>
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
