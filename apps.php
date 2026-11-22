<?php
require_once __DIR__ . '/includes/functions.php';

// Direct Windows Desktop App Shortcut (.url) Download
if (isset($_GET['download']) && $_GET['download'] === 'desktop_win') {
    $url = base_url('index.php');
    header('Content-Type: application/internet-shortcut');
    header('Content-Disposition: attachment; filename="Cake-Shop-BD-Desktop-App.url"');
    echo "[InternetShortcut]\r\nURL={$url}\r\nIconIndex=0\r\n";
    exit;
}

$pageTitle = 'মোবাইল ও ডেস্কটপ অ্যাপস ডাউনলোড';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
  <div class="text-center mb-5">
    <span class="badge bg-danger rounded-pill px-3 py-2 mb-2">📱 PWA + DESKTOP APP SYSTEM</span>
    <h1 class="fw-bold">Cake Shop BD — Mobile &amp; Desktop Apps</h1>
    <p class="text-muted">Android, iOS, Windows এবং macOS-এ সুপার ফাস্ট অ্যাপ হিসেবে ইন্সটল করুন এবং নতুন অর্ডার ও অফারের লাইভ নোটিফিকেশন পান।</p>
  </div>

  <div class="row g-4">
    <div class="col-md-6 col-lg-4">
      <div class="benefit-3d-card p-4 text-center">
        <div class="benefit-icon-box mx-auto" style="background:linear-gradient(135deg,#16a34a,#15803d);"><i class="fa-brands fa-android"></i></div>
        <h5 class="fw-bold">Android Mobile App</h5>
        <p class="small text-muted">১ ক্লিকে আপনার Android ফোনের হোম স্ক্রিনে ইন্সটল করুন — কোনো Play Store ঝামেলা ছাড়াই।</p>
        <button type="button" class="btn btn-success rounded-pill fw-bold px-4 w-100" onclick="triggerAppInstall()">
          <i class="fa-solid fa-download me-2"></i>Android অ্যাপ ইন্সটল
        </button>
      </div>
    </div>

    <div class="col-md-6 col-lg-4">
      <div class="benefit-3d-card p-4 text-center">
        <div class="benefit-icon-box mx-auto" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);"><i class="fa-brands fa-windows"></i></div>
        <h5 class="fw-bold">Windows &amp; Mac Desktop App</h5>
        <p class="small text-muted">Chrome/Edge থেকে সরাসরি Desktop App হিসেবে ইন্সটল করুন অথবা ডেস্কটপ লঞ্চার ডাউনলোড করুন।</p>
        <div class="d-grid gap-2">
          <button type="button" class="btn btn-primary rounded-pill fw-bold" onclick="triggerAppInstall()">
            <i class="fa-solid fa-desktop me-2"></i>Install Desktop PWA
          </button>
          <a href="apps.php?download=desktop_win" class="btn btn-outline-primary btn-sm rounded-pill fw-bold">
            <i class="fa-solid fa-file-arrow-down me-1"></i>Windows Desktop Launcher (.url)
          </a>
        </div>
      </div>
    </div>

    <div class="col-md-12 col-lg-4">
      <div class="benefit-3d-card p-4 text-center">
        <div class="benefit-icon-box mx-auto" style="background:linear-gradient(135deg,#d97706,#b45309);"><i class="fa-solid fa-bell"></i></div>
        <h5 class="fw-bold">iOS &amp; Push Notification</h5>
        <p class="small text-muted">iPhone/iPad-এ Safari &rarr; Share &rarr; "Add to Home Screen" চাপুন। আর নতুন অর্ডার অ্যালার্টের জন্য নোটিফিকেশন চালু করুন।</p>
        <button type="button" class="btn btn-warning rounded-pill fw-bold px-4 w-100" onclick="enableMobileNotifications()">
          <i class="fa-solid fa-bell-ring me-2"></i>মোবাইল নোটিফিকেশন চালু করুন
        </button>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
