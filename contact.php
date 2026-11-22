<?php
$pageTitle = 'যোগাযোগ করুন';
require_once __DIR__ . '/includes/header.php';
$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    db()->prepare("INSERT INTO contact_messages (name, email, phone, subject, message, created_at) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([trim($_POST['name'] ?? ''), trim($_POST['email'] ?? ''), trim($_POST['phone'] ?? ''), trim($_POST['subject'] ?? ''), trim($_POST['message'] ?? ''), date('Y-m-d H:i:s')]);
    $sent = true;
}
$hourNow = (int)date('G');
$isOpenNow = ($hourNow >= 9 && $hourNow < 23);
$addrText = setting('address');
$mapDirUrl = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($addrText);
?>

<div class="container py-5">
  <!-- Digital Header Banner -->
  <div class="p-4 p-md-5 rounded-4 soft-brand-banner shadow-sm mb-5 text-center">
    <div class="d-inline-flex align-items-center gap-2 bg-white bg-opacity-25 rounded-pill px-3 py-1 small fw-bold mb-3">
      <span style="width:9px;height:9px;border-radius:50%;background:<?= $isOpenNow ? '#4ade80' : '#fbbf24' ?>;display:inline-block;"></span>
      <?= $isOpenNow ? 'এখন খোলা আছে — লাইভ সাপোর্ট চালু' : 'অনলাইন অর্ডার ২৪/৭ চালু আছে' ?>
    </div>
    <h1 class="fw-bold mb-2">আমাদের সাথে যোগাযোগ করুন</h1>
    <p class="mb-0 opacity-90 mx-auto" style="max-width:640px;">কাস্টম কেক, ফটো কেক, বিবাহ ও কর্পোরেট ইভেন্ট বা যেকোনো অর্ডারের দ্রুত সহায়তার জন্য সরাসরি যোগাযোগ করুন।</p>
  </div>

  <!-- Smart Digital Contact Cards -->
  <div class="row g-3 mb-5">
    <div class="col-md-6 col-lg-3">
      <div class="benefit-3d-card text-center">
        <div class="benefit-icon-box mx-auto"><i class="fa-solid fa-phone-volume"></i></div>
        <h6 class="fw-bold">হটলাইন কল</h6>
        <a href="tel:<?= e(setting('phone')) ?>" class="small fw-bold text-danger d-block"><?= e(setting('phone')) ?></a>
        <a href="tel:<?= e(setting('phone2')) ?>" class="small text-muted d-block mb-2"><?= e(setting('phone2')) ?></a>
        <a href="tel:<?= e(setting('phone')) ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold">কল করুন</a>
      </div>
    </div>
    <div class="col-md-6 col-lg-3">
      <div class="benefit-3d-card text-center">
        <div class="benefit-icon-box mx-auto" style="background:linear-gradient(135deg,#1c8557,#28a972);"><i class="fa-brands fa-whatsapp"></i></div>
        <h6 class="fw-bold">হোয়াটসঅ্যাপ অর্ডার</h6>
        <p class="small text-muted mb-2">তাৎক্ষণিক ছবি পাঠিয়ে কাস্টম কেক অর্ডার করুন</p>
        <a href="https://wa.me/<?= e(setting('whatsapp')) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-success rounded-pill px-3 fw-bold">চ্যাট শুরু করুন</a>
      </div>
    </div>
    <div class="col-md-6 col-lg-3">
      <div class="benefit-3d-card text-center">
        <div class="benefit-icon-box mx-auto" style="background:linear-gradient(135deg,#d99b38,#ebb44c);"><i class="fa-solid fa-clock"></i></div>
        <h6 class="fw-bold">শোরুম খোলার সময়</h6>
        <p class="small text-muted mb-2"><?= e(setting('business_hours')) ?></p>
        <span class="badge bg-warning-subtle text-dark border rounded-pill px-3">সেইম-ডে ডেলিভারি</span>
      </div>
    </div>
    <div class="col-md-6 col-lg-3">
      <div class="benefit-3d-card text-center">
        <div class="benefit-icon-box mx-auto" style="background:linear-gradient(135deg,#4f67d6,#6e83e8);"><i class="fa-solid fa-envelope-open-text"></i></div>
        <h6 class="fw-bold">অফিশিয়াল ইমেইল</h6>
        <a href="mailto:<?= e(setting('email')) ?>" class="small fw-bold text-primary d-block mb-2 text-break"><?= e(setting('email')) ?></a>
        <a href="mailto:<?= e(setting('email')) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">ইমেইল পাঠান</a>
      </div>
    </div>
  </div>

  <!-- Contact Form + Interactive Location Map -->
  <div class="row g-4 align-items-stretch mb-5">
    <div class="col-lg-6">
      <div class="bg-white p-4 rounded-4 border shadow-sm h-100">
        <h4 class="fw-bold text-danger mb-1"><i class="fa-solid fa-paper-plane me-2"></i>সরাসরি মেসেজ পাঠান</h4>
        <p class="small text-muted mb-3">আপনার যেকোনো প্রশ্ন বা কাস্টম অর্ডারের বিবরণ লিখে পাঠান, আমাদের টিম দ্রুত যোগাযোগ করবে।</p>
        <?php if ($sent): ?>
          <div class="alert alert-success rounded-4"><i class="fa-solid fa-circle-check me-2"></i>ধন্যবাদ! আপনার বার্তাটি সফলভাবে গৃহীত হয়েছে।</div>
        <?php endif; ?>
        <form method="post" class="row g-3">
          <div class="col-md-6"><label class="form-label small fw-bold">আপনার নাম *</label><input type="text" name="name" class="form-control rounded-3" required></div>
          <div class="col-md-6"><label class="form-label small fw-bold">মোবাইল নম্বর *</label><input type="tel" name="phone" class="form-control rounded-3" required></div>
          <div class="col-md-6"><label class="form-label small fw-bold">ইমেইল</label><input type="email" name="email" class="form-control rounded-3"></div>
          <div class="col-md-6"><label class="form-label small fw-bold">বিষয়</label><input type="text" name="subject" class="form-control rounded-3" placeholder="যেমন: কাস্টম বার্থডে কেক"></div>
          <div class="col-12"><label class="form-label small fw-bold">বিস্তারিত বার্তা *</label><textarea name="message" class="form-control rounded-3" rows="4" required></textarea></div>
          <div class="col-12"><button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold py-2"><i class="fa-solid fa-paper-plane me-2"></i>বার্তা পাঠান</button></div>
        </form>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="bg-white p-3 p-md-4 rounded-4 border shadow-sm h-100 d-flex flex-column">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 pb-3">
          <div>
            <h5 class="fw-bold text-danger mb-1"><i class="fa-solid fa-map-location-dot me-2"></i>আমাদের শোরুম লোকেশন</h5>
            <p class="small text-muted mb-0"><?= e($addrText) ?></p>
          </div>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" onclick='copyTextValue(<?= json_encode($addrText, JSON_UNESCAPED_UNICODE) ?>)'><i class="fa-regular fa-copy me-1"></i>কপি</button>
            <a href="<?= e($mapDirUrl) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-danger rounded-pill"><i class="fa-solid fa-diamond-turn-right me-1"></i>ম্যাপে দেখুন</a>
          </div>
        </div>
        <div class="flex-grow-1 rounded-4 overflow-hidden border" style="min-height:330px;">
          <iframe src="<?= e(setting('map_embed_url')) ?>" width="100%" height="100%" style="border:0;min-height:330px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick Social & Support Bar -->
  <div class="bg-white p-4 rounded-4 border shadow-sm d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
      <h6 class="fw-bold mb-1 text-danger"><i class="fa-solid fa-share-nodes me-2"></i>সোশ্যাল মিডিয়াতে আমাদের সাথে যুক্ত থাকুন</h6>
      <small class="text-muted">নতুন কেক ডিজাইন, অফার ও লাইভ আপডেট পেতে ফলো করুন</small>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <?php if (setting('facebook_url')): ?><a href="<?= e(setting('facebook_url')) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold"><i class="fa-brands fa-facebook me-1"></i>Facebook</a><?php endif; ?>
      <?php if (setting('instagram_url')): ?><a href="<?= e(setting('instagram_url')) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold"><i class="fa-brands fa-instagram me-1"></i>Instagram</a><?php endif; ?>
      <?php if (setting('youtube_url')): ?><a href="<?= e(setting('youtube_url')) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold"><i class="fa-brands fa-youtube me-1"></i>YouTube</a><?php endif; ?>
      <?php if (setting('tiktok_url')): ?><a href="<?= e(setting('tiktok_url')) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-dark rounded-pill px-3 fw-bold"><i class="fa-brands fa-tiktok me-1"></i>TikTok</a><?php endif; ?>
      <?php if (setting('linkedin_url')): ?><a href="<?= e(setting('linkedin_url')) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-info rounded-pill px-3 fw-bold"><i class="fa-brands fa-linkedin me-1"></i>LinkedIn</a><?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
