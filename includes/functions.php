<?php
/**
 * Core Helper Functions v3.1: Synced Favorites, Fast Non-Blocking Digital Email & Push Notifications
 */
require_once __DIR__ . '/config.php';

function format_tk(float $amount): string {
    return setting('currency', '৳') . number_format($amount, 0);
}

function product_images(array $p): array {
    $imgs = json_decode($p['images_json'] ?? '[]', true);
    if (!is_array($imgs) || empty($imgs)) {
        return ['assets/images/cakes/chocolate-truffle.png'];
    }
    return $imgs;
}

function product_first_image(array $p): string {
    return asset_url(product_images($p)[0]);
}

function whatsapp_order_url(array $p, string $variant = ''): string {
    $wa = preg_replace('/[^0-9]/', '', setting('whatsapp', '8801581703822'));
    $msg = "আসসালামু আলাইকুম! আমি এই কেকটি অর্ডার করতে চাই:\n"
         . "🎂 প্রোডাক্ট: " . $p['name'] . "\n"
         . "🏷️ SKU: " . $p['sku'] . "\n"
         . ($variant ? "🎨 ভ্যারিয়েন্ট: " . $variant . "\n" : "")
         . "💰 ডিসকাউন্ট মূল্য: " . format_tk((float)$p['discount_price']) . "\n"
         . "🔗 লিংক: " . base_url('product.php?slug=' . urlencode($p['slug']));
    return "https://wa.me/{$wa}?text=" . rawurlencode($msg);
}

function get_cart(): array { return $_SESSION['cart'] ?? []; }

function cart_count(): int {
    $c = 0;
    foreach (get_cart() as $item) $c += (int)($item['qty'] ?? 1);
    return $c;
}

function cart_subtotal(): float {
    $total = 0.0;
    foreach (get_cart() as $item) $total += ((float)$item['price']) * ((int)$item['qty']);
    return $total;
}

function cart_savings(): float {
    $sav = 0.0;
    foreach (get_cart() as $item) {
        $reg = (float)($item['regular_price'] ?? $item['price']);
        $sav += max(0, $reg - (float)$item['price']) * ((int)$item['qty']);
    }
    return $sav;
}

// Strict Deduplicated Favorites (Never allows duplicate entry)
function get_favorite_ids(): array {
    $u = current_user();
    if ($u) {
        $stmt = db()->prepare("SELECT DISTINCT product_id FROM favorites WHERE user_id = ?");
        $stmt->execute([$u['id']]);
        return array_values(array_unique(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN))));
    }
    return array_values(array_unique(array_map('intval', $_SESSION['favorites'] ?? [])));
}

function is_favorite(int $productId): bool {
    return in_array($productId, get_favorite_ids(), true);
}

function favorite_count(): int {
    return count(get_favorite_ids());
}

function track_live_visitor(string $page = ''): void {
    try {
        $sid = session_id();
        if (!$sid) return;
        $u = current_user();
        $uid = $u ? (int)$u['id'] : 0;
        $uname = $u ? ($u['name'] . ' (' . $u['email'] . ')') : 'Guest Visitor';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $device = preg_match('/mobile|android|iphone|ipad/i', $ua) ? 'Mobile' : 'Desktop';
        $curr = $page ?: ($_SERVER['REQUEST_URI'] ?? '/');
        $now = date('Y-m-d H:i:s');
        db()->prepare("INSERT INTO live_visitors (session_id, user_id, user_name, ip_address, current_page, device, last_seen)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON CONFLICT(session_id) DO UPDATE SET user_id=excluded.user_id, user_name=excluded.user_name,
            current_page=excluded.current_page, device=excluded.device, last_seen=excluded.last_seen")
            ->execute([$sid, $uid, $uname, $ip, $curr, $device, $now]);
    } catch (Throwable $e) {}
}

function log_audit(string $action, string $details = ''): void {
    try {
        $u = current_user();
        db()->prepare("INSERT INTO audit_logs (user_id, user_name, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([$u ? (int)$u['id'] : 0, $u ? $u['name'] : 'System', $action, $details, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', date('Y-m-d H:i:s')]);
    } catch (Throwable $e) {}
}

function send_order_email(array $order, array $items): string {
    $siteName = setting('site_name', 'Cake Shop BD');
    $siteEmail = setting('email', 'jainal.dcitbd@gmail.com');
    $sitePhone = setting('phone', '+880 1581-703822');
    $siteAddr = setting('address', 'Comilla, Bangladesh');
    $orderCode = $order['order_code'];
    $trxId = $order['trx_id'] ?? '';

    $rowsHtml = '';
    foreach ($items as $it) {
        $lineTotal = (float)$it['price'] * (int)$it['qty'];
        $rowsHtml .= "<tr>
            <td style='padding:12px;border-bottom:1px solid #f3e8ee;'>
                <strong style='color:#b84a6b;'>" . e($it['name']) . "</strong><br>
                <span style='font-size:12px;color:#64748b;'>SKU: " . e($it['sku'] ?? 'CAKE') . " | ভ্যারিয়েন্ট: " . e($it['variant'] ?? '1 পাউন্ড') . "</span>
            </td>
            <td style='padding:12px;border-bottom:1px solid #f3e8ee;text-align:center;'>" . (int)$it['qty'] . "</td>
            <td style='padding:12px;border-bottom:1px solid #f3e8ee;text-align:right;'>" . format_tk((float)$it['price']) . "</td>
            <td style='padding:12px;border-bottom:1px solid #f3e8ee;text-align:right;font-weight:700;color:#b84a6b;'>" . format_tk($lineTotal) . "</td>
        </tr>";
    }

    $html = "<!DOCTYPE html>
<html lang='bn'><head><meta charset='UTF-8'><title>অর্ডার #{$orderCode}</title></head>
<body style='margin:0;padding:24px;background:#fdf5f8;font-family:Segoe UI,Hind Siliguri,Arial,sans-serif;color:#2f2028;'>
<div style='max-width:660px;margin:0 auto;background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 12px 35px rgba(184,74,107,0.12);border:1px solid #f1dce4;'>
    <div style='background:linear-gradient(135deg,#742a44,#b84a6b);padding:26px 30px;color:#ffffff;text-align:center;'>
        <div style='display:inline-block;background:#ebb44c;color:#362107;font-weight:800;font-size:12px;padding:4px 14px;border-radius:20px;margin-bottom:8px;'>🎂 DIGITAL ORDER INVOICE</div>
        <h1 style='margin:0;font-size:24px;'>" . e($siteName) . "</h1>
        <p style='margin:6px 0 0;opacity:0.9;font-size:14px;'>অর্ডার নম্বর: <strong>#{$orderCode}</strong></p>
    </div>
    <div style='padding:24px 30px;'>
        <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:18px;background:#fffcfe;border-radius:12px;border:1px solid #f1dce4;'>
            <tr>
                <td style='padding:14px;vertical-align:top;width:50%;font-size:14px;line-height:1.6;'>
                    <div style='font-size:12px;color:#b84a6b;font-weight:700;margin-bottom:4px;'>👤 কাস্টমারের তথ্য</div>
                    <strong>" . e($order['customer_name']) . "</strong><br>
                    📞 " . e($order['customer_phone']) . "<br>
                    ✉️ " . e($order['customer_email'] ?: 'N/A') . "<br>
                    📍 " . e($order['shipping_address']) . "
                </td>
                <td style='padding:14px;vertical-align:top;width:50%;border-left:1px solid #f1dce4;font-size:14px;line-height:1.6;'>
                    <div style='font-size:12px;color:#b84a6b;font-weight:700;margin-bottom:4px;'>💳 পেমেন্ট ও অর্ডার তথ্য</div>
                    অর্ডার আইডি: <strong>#{$orderCode}</strong><br>
                    তারিখ: " . e($order['created_at']) . "<br>
                    পেমেন্ট: <strong>" . e($order['payment_method']) . "</strong><br>
                    TrxID: <strong style='color:#1c8557;'>" . e($trxId ?: 'N/A') . "</strong>
                </td>
            </tr>
        </table>
        <table width='100%' cellpadding='0' cellspacing='0' style='border-collapse:collapse;font-size:14px;'>
            <thead><tr style='background:#742a44;color:#ffffff;'>
                <th style='padding:10px 12px;text-align:left;'>কেক ও ভ্যারিয়েন্ট</th>
                <th style='padding:10px 12px;text-align:center;'>পরিমাণ</th>
                <th style='padding:10px 12px;text-align:right;'>দর</th>
                <th style='padding:10px 12px;text-align:right;'>মোট</th>
            </tr></thead>
            <tbody>{$rowsHtml}</tbody>
        </table>
        <div style='margin-top:16px;text-align:right;font-size:14px;line-height:1.8;'>
            সাবটোটাল: <strong>" . format_tk((float)$order['subtotal']) . "</strong><br>
            ডেলিভারি চার্জ: <strong>" . format_tk((float)$order['delivery_charge']) . "</strong><br>
            ডিসকাউন্ট: <strong style='color:#1c8557;'>-" . format_tk((float)$order['discount']) . "</strong><br>
            <div style='display:inline-block;margin-top:8px;background:#b84a6b;color:#ffffff;padding:8px 18px;border-radius:10px;font-size:17px;font-weight:800;'>
                সর্বমোট: " . format_tk((float)$order['total']) . "
            </div>
        </div>
    </div>
    <div style='background:#fdf5f8;padding:14px 24px;text-align:center;font-size:12px;color:#73606a;'>
        " . e($siteName) . " | {$sitePhone} | " . e($siteAddr) . "
    </div>
</div></body></html>";

    $relFile = 'storage/emails/order_' . preg_replace('/[^A-Za-z0-9_-]/', '', $orderCode) . '.html';
    @file_put_contents(APP_ROOT . '/' . $relFile, $html);

    $headers  = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\nFrom: {$siteName} <{$siteEmail}>\r\n";
    $subject = "🎂 নতুন কেক অর্ডার #{$orderCode} - {$siteName}";
    @mail($siteEmail, '=?UTF-8?B?' . base64_encode($subject) . '?=', $html, $headers);
    if (!empty($order['customer_email']) && filter_var($order['customer_email'], FILTER_VALIDATE_EMAIL)) {
        @mail($order['customer_email'], '=?UTF-8?B?' . base64_encode($subject) . '?=', $html, $headers);
    }
    return $relFile;
}

function dispatch_order_telegram(array $order): void {
    $botToken = trim(setting('telegram_bot_token', ''));
    $chatId = trim(setting('telegram_chat_id', ''));
    if ($botToken !== '' && $chatId !== '' && function_exists('curl_init')) {
        $title = "🎂 নতুন অর্ডার #{$order['order_code']}!";
        $text = "{$title}\n👤 {$order['customer_name']}\n📞 {$order['customer_phone']}\n💳 {$order['payment_method']} (TrxID: " . ($order['trx_id'] ?? '') . ")\n💰 মোট: " . format_tk((float)$order['total']);
        $ch = curl_init("https://api.telegram.org/bot{$botToken}/sendMessage");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ['chat_id' => $chatId, 'text' => $text],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT_MS => 600,
            CURLOPT_TIMEOUT_MS => 900,
        ]);
        @curl_exec($ch);
        @curl_close($ch);
    }
}

function dispatch_order_notification(array $order): void {
    $title = "🎂 নতুন অর্ডার #{$order['order_code']}!";
    $msg = "আপনার নতুন একটি অর্ডার এসেছে — {$order['customer_name']} ({$order['customer_phone']}) | মোট: " . format_tk((float)$order['total']);
    try {
        db()->prepare("INSERT INTO notifications (type, title, message, order_code, is_read, created_at) VALUES ('order', ?, ?, ?, 0, ?)")
            ->execute([$title, $msg, $order['order_code'], date('Y-m-d H:i:s')]);
    } catch (Throwable $e) {}
    dispatch_order_telegram($order);
}

// Product Card with synced data-fav-id across the entire page
function render_cake_card(array $p, string $brandName = '', array $favIds = []): string {
    $img = product_first_image($p);
    $discPct = (int)$p['discount_percent'];
    $reg = (float)$p['regular_price'];
    $disc = (float)$p['discount_price'];
    $savings = max(0, $reg - $disc);
    $pid = (int)$p['id'];
    $isFav = in_array($pid, $favIds, true);
    $rating = number_format((float)$p['rating'], 1);
    $pUrl = base_url('product.php?slug=' . urlencode($p['slug']));
    $waUrl = whatsapp_order_url($p);
    $bName = $brandName ?: ($p['brand_name'] ?? 'Cake Shop BD');

    return '
    <div class="cake-card" data-product-id="' . $pid . '">
        <div class="cake-card-media">
            ' . ($discPct > 0 ? '<span class="badge-discount">-' . $discPct . '% ছাড়</span>' : '') . '
            <button type="button" class="btn-fav-corner ' . ($isFav ? 'active' : '') . '" data-fav-id="' . $pid . '" onclick="toggleFavorite(' . $pid . ', this)" title="ফেভরিট">
                <i class="' . ($isFav ? 'fa-solid' : 'fa-regular') . ' fa-heart"></i>
            </button>
            <a href="' . e($pUrl) . '" class="cake-img-link">
                <img src="' . e($img) . '" alt="' . e($p['name']) . '" loading="lazy">
            </a>
        </div>
        <div class="cake-card-body">
            <div class="cake-meta-row">
                <span class="cake-brand"><i class="fa-solid fa-crown me-1"></i>' . e($bName) . '</span>
                <span class="cake-rating"><i class="fa-solid fa-star text-warning"></i> ' . $rating . '</span>
            </div>
            <h3 class="cake-title"><a href="' . e($pUrl) . '">' . e($p['name']) . '</a></h3>
            <div class="cake-price-row">
                <span class="price-discount">' . format_tk($disc) . '</span>
                <span class="price-regular">' . format_tk($reg) . '</span>
                <span class="price-savings">সাশ্রয় ' . format_tk($savings) . '</span>
            </div>
            <div class="cake-actions-row">
                <button type="button" class="btn btn-order-now" onclick="orderNow(' . $pid . ')">
                    <i class="fa-solid fa-bolt me-1"></i>Order Now
                </button>
                <button type="button" class="btn btn-add-cart" onclick="addToCart(' . $pid . ', 1)">
                    <i class="fa-solid fa-cart-plus me-1"></i>Cart
                </button>
            </div>
            <a href="' . e($waUrl) . '" target="_blank" rel="noopener" class="btn btn-whatsapp-order w-100 mt-2">
                <i class="fa-brands fa-whatsapp me-1"></i>Order With WhatsApp
            </a>
        </div>
    </div>';
}

function render_pagination(int $currentPage, int $totalPages, array $queryParams = []): string {
    if ($totalPages <= 1) return '';
    unset($queryParams['page']);
    $buildUrl = function (int $p) use ($queryParams): string {
        $queryParams['page'] = $p;
        return '?' . http_build_query($queryParams);
    };
    $html = '<nav aria-label="Page navigation" class="mt-4"><ul class="pagination cake-pagination justify-content-center flex-wrap">';
    $prevDisabled = $currentPage <= 1 ? ' disabled' : '';
    $html .= '<li class="page-item' . $prevDisabled . '"><a class="page-link" href="' . ($currentPage > 1 ? e($buildUrl($currentPage - 1)) : '#') . '">&laquo; পূর্বে</a></li>';
    $pages = [];
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i <= 3 || $i > $totalPages - 2 || abs($i - $currentPage) <= 1) $pages[] = $i;
        elseif (end($pages) !== '...') $pages[] = '...';
    }
    foreach ($pages as $pg) {
        if ($pg === '...') $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        else {
            $active = ($pg === $currentPage) ? ' active' : '';
            $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . e($buildUrl((int)$pg)) . '">' . $pg . '</a></li>';
        }
    }
    $nextDisabled = $currentPage >= $totalPages ? ' disabled' : '';
    $html .= '<li class="page-item' . $nextDisabled . '"><a class="page-link" href="' . ($currentPage < $totalPages ? e($buildUrl($currentPage + 1)) : '#') . '">পরবর্তী &raquo;</a></li>';
    $html .= '</ul></nav>';
    return $html;
}
