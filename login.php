<?php
require_once __DIR__ . '/includes/functions.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $stmt = db()->prepare("SELECT * FROM users WHERE (email = ? OR phone = ?) AND status = 1 LIMIT 1");
    $stmt->execute([$email, $email]);
    $u = $stmt->fetch();
    if ($u && verify_user_password($pass, $u['password_hash'])) {
        $_SESSION['user_id'] = (int)$u['id'];
        // Always issue 30-day persistent login cookie so Mobile/Admin stays logged in until Logout
        issue_persistent_login((int)$u['id']);

        if (!empty($_SESSION['favorites'])) {
            $ins = db()->prepare("INSERT OR IGNORE INTO favorites (user_id, product_id) VALUES (?, ?)");
            foreach ($_SESSION['favorites'] as $fid) $ins->execute([$u['id'], (int)$fid]);
            unset($_SESSION['favorites']);
        }
        log_audit('USER_LOGIN', $u['name'] . ' (' . $u['role'] . ') লগইন করেছেন');
        $redir = trim($_GET['redirect'] ?? '');
        if ($redir !== '' && !preg_match('#^https?://#i', $redir)) header('Location: ' . $redir);
        elseif (in_array($u['role'], ['master_admin', 'admin', 'staff'], true)) header('Location: admin/index.php');
        else header('Location: customer/dashboard.php');
        exit;
    }
    $error = 'ইমেইল/ফোন অথবা পাসওয়ার্ড সঠিক নয়।';
}

$pageTitle = 'লগইন';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container py-5">
  <div class="bg-white p-4 p-md-5 rounded-4 border shadow-lg mx-auto" style="max-width:440px;">
    <div class="text-center mb-4">
      <img src="<?= e(base_url('assets/images/icon-192.png')) ?>" width="62" height="62" class="rounded-4 mb-2">
      <h3 class="fw-bold text-danger mb-1">অ্যাকাউন্ট লগইন</h3>
      <p class="small text-muted">আপনার ইমেইল অথবা মোবাইল নম্বর দিয়ে লগইন করুন</p>
    </div>
    <?php if ($error): ?><div class="alert alert-danger rounded-3 small"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <div class="mb-3">
        <label class="form-label small fw-bold">ইমেইল বা মোবাইল নম্বর</label>
        <input type="text" name="email" class="form-control rounded-3" required>
      </div>
      <div class="mb-3">
        <label class="form-label small fw-bold">পাসওয়ার্ড</label>
        <input type="password" name="password" class="form-control rounded-3" required>
      </div>
      <div class="form-check small mb-3">
        <input type="checkbox" class="form-check-input" id="remMe" checked disabled>
        <label class="form-check-label text-muted" for="remMe">লগইন মনে রাখুন (লগআউট না করা পর্যন্ত সক্রিয় থাকবে)</label>
      </div>
      <button type="submit" class="btn btn-order-now btn-lg w-100 rounded-pill mb-3">লগইন করুন</button>
    </form>
    <div class="text-center small">নতুন কাস্টমার? <a href="register.php" class="text-danger fw-bold">অ্যাকাউন্ট তৈরি করুন</a></div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
