<?php
// Preserve 100% original Customer Dashboard footer when accessed inside /customer/
if (strpos(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''), '/customer/') !== false) {
    require __DIR__ . '/footer_dashboard.php';
    return;
}
?>
</main>

<!-- Decorative Chocolate-Drip Wave Divider -->
<div class="csbd-footer-wave" aria-hidden="true">
  <svg viewBox="0 0 1440 70" preserveAspectRatio="none">
    <path fill="#2a1208" d="M0,24 C160,68 320,0 480,34 C640,68 800,4 960,36 C1120,68 1280,10 1440,34 L1440,70 L0,70 Z"></path>
  </svg>
</div>

<footer class="cake-footer">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="d-flex align-items-center gap-3 mb-3">
          <img src="<?= e(base_url('assets/images/logo.png')) ?>" alt="<?= e(setting('site_name')) ?>" width="56" height="56" class="rounded-circle border border-2 border-warning shadow-sm bg-white p-1">
          <div>
            <h5 class="mb-0 fw-bold text-white"><?= e(setting('site_name')) ?></h5>
            <small class="text-warning"><?= e(setting('site_bn_name')) ?> • Sweet Moments</small>
          </div>
        </div>
        <p class="small opacity-75"><?= e(setting('tagline')) ?></p>
        <div class="small mb-2"><i class="fa-solid fa-location-dot text-warning me-2"></i><?= e(setting('address')) ?></div>
        <div class="small mb-2"><i class="fa-solid fa-phone text-warning me-2"></i><?= e(setting('phone')) ?></div>
        <div class="small"><i class="fa-solid fa-envelope text-warning me-2"></i><?= e(setting('email')) ?></div>
      </div>

      <div class="col-6 col-lg-2">
        <h6 class="fw-bold text-warning mb-3">গুরুত্বপূর্ণ পেজ</h6>
        <ul class="list-unstyled small d-flex flex-column gap-2">
          <li><a href="<?= e(base_url('index.php')) ?>">হোম পেজ</a></li>
          <li><a href="<?= e(base_url('products.php')) ?>">সকল কেক</a></li>
          <li><a href="<?= e(base_url('category.php')) ?>">সকল ক্যাটাগরি</a></li>
          <li><a href="<?= e(base_url('tracking.php')) ?>">অর্ডার ট্র্যাকিং</a></li>
          <li><a href="<?= e(base_url('contact.php')) ?>">যোগাযোগ</a></li>
        </ul>
      </div>

      <div class="col-6 col-lg-2">
        <h6 class="fw-bold text-warning mb-3">অ্যাকাউন্ট</h6>
        <ul class="list-unstyled small d-flex flex-column gap-2">
          <li><a href="<?= e(base_url('login.php')) ?>">লগইন</a></li>
          <li><a href="<?= e(base_url('register.php')) ?>">রেজিস্ট্রেশন</a></li>
          <li><a href="<?= e(base_url('customer/dashboard.php')) ?>">কাস্টমার ড্যাশবোর্ড</a></li>
          <li><a href="<?= e(base_url('apps.php')) ?>">মোবাইল ও ডেস্কটপ অ্যাপ</a></li>
          <li><a href="<?= e(base_url('developer.php')) ?>" class="text-warning fw-bold"><i class="fa-solid fa-code me-1"></i>ডেভেলপার প্রোফাইল</a></li>
        </ul>
      </div>

      <div class="col-lg-4">
        <div class="p-3 rounded-4" style="background:rgba(255,249,242,0.06);border:1px solid rgba(242,183,73,0.22);">
          <h6 class="fw-bold text-warning mb-2"><i class="fa-solid fa-mobile-screen-button me-1"></i>অ্যাপস ও লাইভ নোটিফিকেশন</h6>
          <p class="small opacity-75 mb-3">Android, iOS ও Windows/Mac অ্যাপ ইন্সটল করে দ্রুত অর্ডার ও ইনস্ট্যান্ট আপডেট পান।</p>
          <div class="d-flex flex-wrap gap-2 mb-3">
            <button type="button" class="btn btn-warning btn-sm fw-bold rounded-pill px-3" onclick="triggerAppInstall()"><i class="fa-brands fa-android me-1"></i>Mobile App</button>
            <a href="<?= e(base_url('apps.php')) ?>" class="btn btn-outline-light btn-sm fw-bold rounded-pill px-3"><i class="fa-solid fa-desktop me-1"></i>Desktop App</a>
          </div>
          <div class="d-flex flex-wrap gap-2">
            <?php if (setting('facebook_url')): ?><a class="csbd-footer-social-btn" href="<?= e(setting('facebook_url')) ?>" target="_blank" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a><?php endif; ?>
            <?php if (setting('instagram_url')): ?><a class="csbd-footer-social-btn" href="<?= e(setting('instagram_url')) ?>" target="_blank" title="Instagram"><i class="fa-brands fa-instagram"></i></a><?php endif; ?>
            <?php if (setting('youtube_url')): ?><a class="csbd-footer-social-btn" href="<?= e(setting('youtube_url')) ?>" target="_blank" title="YouTube"><i class="fa-brands fa-youtube"></i></a><?php endif; ?>
            <?php if (setting('tiktok_url')): ?><a class="csbd-footer-social-btn" href="<?= e(setting('tiktok_url')) ?>" target="_blank" title="TikTok"><i class="fa-brands fa-tiktok"></i></a><?php endif; ?>
            <?php if (setting('linkedin_url')): ?><a class="csbd-footer-social-btn" href="<?= e(setting('linkedin_url')) ?>" target="_blank" title="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a><?php endif; ?>
            <?php if (setting('twitter_url')): ?><a class="csbd-footer-social-btn" href="<?= e(setting('twitter_url')) ?>" target="_blank" title="X / Twitter"><i class="fa-brands fa-x-twitter"></i></a><?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="border-top border-light border-opacity-25 mt-4 pt-3 d-flex flex-wrap justify-content-between align-items-center small opacity-75 gap-2">
      <div>&copy; <?= date('Y') ?> <?= e(setting('site_name')) ?> — সর্বস্বত্ব সংরক্ষিত।</div>
      <div>Crafted by <a href="<?= e(base_url('developer.php')) ?>" class="text-warning fw-bold"><i class="fa-solid fa-terminal me-1"></i>Developer Profile</a></div>
    </div>
  </div>
</footer>

<button type="button" id="csbdBackToTop" aria-label="উপরে যান" title="উপরে যান">
  <i class="fa-solid fa-arrow-up"></i>
</button>

<div class="mobile-bottom-nav">
  <a href="<?= e(base_url('index.php')) ?>" class="text-center small"><i class="fa-solid fa-house d-block fs-5 text-danger"></i>হোম</a>
  <a href="<?= e(base_url('products.php')) ?>" class="text-center small"><i class="fa-solid fa-cake-candles d-block fs-5 text-danger"></i>কেক</a>
  <a href="<?= e(base_url('cart.php')) ?>" class="text-center small"><i class="fa-solid fa-cart-shopping d-block fs-5 text-danger"></i>কার্ট (<span class="js-cart-count"><?= cart_count() ?></span>)</a>
  <a href="<?= e(base_url('favorites.php')) ?>" class="text-center small"><i class="fa-solid fa-heart d-block fs-5 text-danger"></i>ফেভরিট</a>
  <a href="<?= e(base_url('customer/dashboard.php')) ?>" class="text-center small"><i class="fa-solid fa-user d-block fs-5 text-danger"></i>অ্যাকাউন্ট</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(base_url('assets/js/app.js')) ?>"></script>
<script src="<?= e(base_url('assets/js/storefront-fx.js')) ?>"></script>
</body>
</html>
