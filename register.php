<?php
require_once __DIR__ . '/includes/functions.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $pass = $_POST['password'] ?? '';
    if ($name === '' || $email === '' || strlen($pass) < 6) {
        $error = 'নাম, ইমেইল এবং কমপক্ষে ৬ অক্ষরের পাসওয়ার্ড দিন।';
    } else {
        try {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = db()->prepare("INSERT INTO users (name, email, phone, password_hash, role, address, created_at) VALUES (?, ?, ?, ?, 'customer', ?, ?)");
            $stmt->execute([$name, $email, $phone, $hash, $address, date('Y-m-d H:i:s')]);
            $_SESSION['user_id'] = (int)db()->lastInsertId();
            header('Location: customer/dashboard.php');
            exit;
        } catch (Throwable $e) {
            $error = 'এই ইমেইল দিয়ে ইতিমধ্যে অ্যাকাউন্ট আছে।';
        }
    }
}
$pageTitle = 'নতুন অ্যাকাউন্ট তৈরি';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container py-5">
  <div class="bg-white p-4 p-md-5 rounded-4 border shadow-lg mx-auto" style="max-width:480px;">
    <h3 class="fw-bold text-danger text-center mb-3">নতুন কাস্টমার রেজিস্ট্রেশন</h3>
    <?php if ($error): ?><div class="alert alert-danger rounded-3 small"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="d-flex flex-column gap-3">
      <div><label class="form-label small fw-bold">পূর্ণ নাম *</label><input type="text" name="name" class="form-control" required></div>
      <div><label class="form-label small fw-bold">ইমেইল *</label><input type="email" name="email" class="form-control" required></div>
      <div><label class="form-label small fw-bold">মোবাইল নম্বর *</label><input type="tel" name="phone" class="form-control" required></div>
      <div><label class="form-label small fw-bold">ঠিকানা</label><input type="text" name="address" class="form-control"></div>
      <div><label class="form-label small fw-bold">পাসওয়ার্ড (কমপক্ষে ৬ অক্ষর) *</label><input type="password" name="password" class="form-control" required minlength="6"></div>
      <button type="submit" class="btn btn-order-now btn-lg rounded-pill">অ্যাকাউন্ট তৈরি করুন</button>
    </form>
    <div class="text-center small mt-3">ইতিমধ্যে অ্যাকাউন্ট আছে? <a href="login.php" class="text-danger fw-bold">লগইন করুন</a></div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
