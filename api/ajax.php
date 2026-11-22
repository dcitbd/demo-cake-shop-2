<?php
/**
 * High-Speed AJAX API v2.0: Cart, Deduplicated Favorites, Per-Customer Coupon Limits, Live Search, Notifications, Instant Admin Status Updates
 */
require_once dirname(__DIR__) . '/includes/functions.php';
header('Content-Type: application/json; charset=UTF-8');

$action = $_REQUEST['action'] ?? '';
$pdo = db();

try {
    if ($action === 'add_cart') {
        $pid = (int)($_POST['product_id'] ?? 0);
        $qty = max(1, (int)($_POST['qty'] ?? 1));
        $variant = trim($_POST['variant'] ?? '1 পাউন্ড | ক্লাসিক');
        $extraPrice = (float)($_POST['extra_price'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 1");
        $stmt->execute([$pid]);
        $p = $stmt->fetch();
        if (!$p) { echo json_encode(['ok' => false, 'message' => 'কেকটি পাওয়া যায়নি']); exit; }
        if ((int)$p['stock'] <= 0) { echo json_encode(['ok' => false, 'message' => 'স্টক আউট!']); exit; }
        $key = $pid . '_' . md5($variant);
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        if (isset($_SESSION['cart'][$key])) $_SESSION['cart'][$key]['qty'] += $qty;
        else {
            $_SESSION['cart'][$key] = [
                'key' => $key, 'id' => (int)$p['id'], 'sku' => $p['sku'], 'name' => $p['name'], 'slug' => $p['slug'],
                'image' => product_first_image($p), 'regular_price' => (float)$p['regular_price'] + $extraPrice,
                'price' => (float)$p['discount_price'] + $extraPrice, 'variant' => $variant, 'qty' => $qty,
            ];
        }
        echo json_encode(['ok' => true, 'cart_count' => cart_count(), 'subtotal' => cart_subtotal()]);
        exit;
    }

    if ($action === 'update_cart') {
        $key = $_POST['key'] ?? ''; $qty = max(1, (int)($_POST['qty'] ?? 1));
        if (isset($_SESSION['cart'][$key])) $_SESSION['cart'][$key]['qty'] = $qty;
        echo json_encode(['ok' => true, 'cart_count' => cart_count(), 'subtotal' => cart_subtotal()]);
        exit;
    }

    if ($action === 'remove_cart') {
        unset($_SESSION['cart'][$_POST['key'] ?? '']);
        echo json_encode(['ok' => true, 'cart_count' => cart_count(), 'subtotal' => cart_subtotal()]);
        exit;
    }

    if ($action === 'toggle_fav') {
        $pid = (int)($_POST['product_id'] ?? 0);
        $u = current_user();
        $isFav = false;
        if ($u) {
            $exists = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND product_id = ?");
            $exists->execute([$u['id'], $pid]);
            if ($exists->fetch()) {
                $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ?")->execute([$u['id'], $pid]);
                $isFav = false;
            } else {
                $pdo->prepare("INSERT OR IGNORE INTO favorites (user_id, product_id) VALUES (?, ?)")->execute([$u['id'], $pid]);
                $isFav = true;
            }
        } else {
            $favs = array_values(array_unique(array_map('intval', $_SESSION['favorites'] ?? [])));
            if (in_array($pid, $favs, true)) {
                $favs = array_values(array_diff($favs, [$pid]));
                $isFav = false;
            } else {
                $favs[] = $pid;
                $favs = array_values(array_unique($favs));
                $isFav = true;
            }
            $_SESSION['favorites'] = $favs;
        }
        echo json_encode(['ok' => true, 'is_fav' => $isFav, 'fav_count' => favorite_count()]);
        exit;
    }

    if ($action === 'live_search') {
        $q = trim($_GET['q'] ?? '');
        $stmt = $pdo->prepare("SELECT id, name, slug, regular_price, discount_price, images_json FROM products WHERE status = 1 AND (name LIKE ? OR sku LIKE ? OR short_desc LIKE ?) LIMIT 8");
        $like = '%' . $q . '%';
        $stmt->execute([$like, $like, $like]);
        $items = [];
        foreach ($stmt->fetchAll() as $r) {
            $items[] = [
                'id' => (int)$r['id'], 'name' => $r['name'], 'slug' => $r['slug'],
                'regular_price' => (float)$r['regular_price'], 'discount_price' => (float)$r['discount_price'],
                'image' => product_first_image($r),
            ];
        }
        echo json_encode(['ok' => true, 'items' => $items]);
        exit;
    }

    if ($action === 'check_notifications') {
        $unread = (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0")->fetchColumn();
        $latest = $pdo->query("SELECT * FROM notifications ORDER BY id DESC LIMIT 1")->fetch();
        echo json_encode(['ok' => true, 'unread_count' => $unread, 'latest' => $latest ?: null]);
        exit;
    }

    if ($action === 'apply_coupon') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $phone = preg_replace('/[^0-9]/', '', trim($_POST['phone'] ?? ''));
        $sub = cart_subtotal();
        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE UPPER(code) = ? AND status = 1");
        $stmt->execute([$code]);
        $c = $stmt->fetch();
        if (!$c) { echo json_encode(['ok' => false, 'message' => 'সঠিক কুপন কোড দিন']); exit; }
        if (!empty($c['expiry_date']) && $c['expiry_date'] < date('Y-m-d')) {
            echo json_encode(['ok' => false, 'message' => 'এই কুপনের মেয়াদ শেষ হয়ে গেছে']); exit;
        }
        if ($sub < (float)$c['min_order']) {
            echo json_encode(['ok' => false, 'message' => 'ন্যূনতম অর্ডার ৳' . number_format($c['min_order']) . ' হতে হবে']); exit;
        }
        // Check per-customer usage limit (#23)
        $u = current_user();
        $custKey = $u ? ('user_' . $u['id']) : ($phone ? ('phone_' . $phone) : '');
        $maxUses = max(1, (int)($c['max_uses_per_customer'] ?? 1));
        if ($custKey !== '') {
            $uCnt = $pdo->prepare("SELECT COUNT(*) FROM coupon_usages WHERE UPPER(coupon_code)=? AND customer_key=?");
            $uCnt->execute([$code, $custKey]);
            if ((int)$uCnt->fetchColumn() >= $maxUses) {
                echo json_encode(['ok' => false, 'message' => "এই কুপনটি আপনি সর্বোচ্চ {$maxUses} বার ব্যবহার করে ফেলেছেন"]); exit;
            }
        }
        $disc = $c['discount_type'] === 'percent' ? round($sub * ((float)$c['discount_value'] / 100)) : (float)$c['discount_value'];
        $_SESSION['applied_coupon'] = ['code' => $c['code'], 'discount' => $disc];
        echo json_encode(['ok' => true, 'code' => $c['code'], 'discount' => $disc, 'message' => 'কুপন প্রয়োগ হয়েছে! ছাড়: ৳' . $disc]);
        exit;
    }

    if ($action === 'submit_review') {
        $u = current_user();
        if (!$u) { echo json_encode(['ok' => false, 'message' => 'রিভিউ দিতে লগইন করুন।']); exit; }
        $pid = (int)($_POST['product_id'] ?? 0);
        $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        $comment = trim($_POST['comment'] ?? '');
        if ($comment === '') { echo json_encode(['ok' => false, 'message' => 'মতামত লিখুন।']); exit; }
        $pdo->prepare("INSERT INTO reviews (product_id, user_id, user_name, rating, comment, status, created_at) VALUES (?, ?, ?, ?, ?, 1, ?)")
            ->execute([$pid, $u['id'], $u['name'], $rating, $comment, date('Y-m-d H:i:s')]);
        $agg = $pdo->prepare("SELECT AVG(rating) as avg_r, COUNT(*) as cnt FROM reviews WHERE product_id = ? AND status = 1");
        $agg->execute([$pid]);
        $a = $agg->fetch();
        $pdo->prepare("UPDATE products SET rating = ?, review_count = ? WHERE id = ?")->execute([round((float)$a['avg_r'], 1), (int)$a['cnt'], $pid]);
        echo json_encode(['ok' => true, 'message' => 'আপনার রিভিউ প্রকাশিত হয়েছে!']);
        exit;
    }

    // Instant Admin Order / Payment Status AJAX Update (#17)
    if ($action === 'admin_quick_order_update') {
        if (!is_admin()) { echo json_encode(['ok' => false, 'message' => 'Unauthorized']); exit; }
        $oid = (int)($_POST['id'] ?? 0);
        $field = $_POST['field'] ?? '';
        $val = trim($_POST['value'] ?? '');
        if ($field === 'order_status') {
            $pdo->prepare("UPDATE orders SET order_status = ? WHERE id = ?")->execute([$val, $oid]);
            log_audit('ORDER_STATUS', "Order #{$oid} -> {$val}");
        } elseif ($field === 'payment_status') {
            $pdo->prepare("UPDATE orders SET payment_status = ? WHERE id = ?")->execute([$val, $oid]);
            log_audit('PAYMENT_STATUS', "Order #{$oid} -> {$val}");
        }
        echo json_encode(['ok' => true, 'message' => 'স্ট্যাটাস আপডেট হয়েছে!']);
        exit;
    }

    echo json_encode(['ok' => false, 'message' => 'Unknown action']);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
