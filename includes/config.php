<?php
/**
 * SweetDream Cakes BD v3.1 - Core Config, Persistent 365-Day Login & Ultra-Fast SQLite3 (PHP 7.0+ Compatible)
 */
if (session_status() === PHP_SESSION_NONE) {
    $cookieLife = 31536000; // 365 days persistent login on mobile & desktop until explicit logout
    @ini_set('session.gc_maxlifetime', (string)$cookieLife);
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => $cookieLife,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    } else {
        session_set_cookie_params($cookieLife, '/; samesite=Lax', '', false, true);
    }
    session_start();
}

date_default_timezone_set('Asia/Dhaka');

define('APP_ROOT', dirname(__DIR__));
define('STORAGE_DIR', APP_ROOT . '/storage');
define('DB_PATH', STORAGE_DIR . '/cakes_store.sqlite');
define('EMAIL_LOG_DIR', STORAGE_DIR . '/emails');
define('UPLOAD_DIR', APP_ROOT . '/assets/images/uploads');

foreach ([STORAGE_DIR, EMAIL_LOG_DIR, UPLOAD_DIR] as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
}

function base_url(string $path = ''): string {
    static $base = null;
    if ($base === null) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        $scriptDir = preg_replace('#/(admin|customer|api)(/.*)?$#', '', $scriptDir);
        $base = rtrim($scheme . '://' . $host . $scriptDir, '/');
    }
    return $base . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

function asset_url(string $path): string {
    if (preg_match('#^https?://#i', $path)) return $path;
    return base_url($path);
}

function admin_img_src(string $path): string {
    if (preg_match('#^https?://#i', $path)) return $path;
    if (strpos($path, '../') === 0) return $path;
    return '../' . ltrim($path, '/');
}

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $isNew = !file_exists(DB_PATH) || filesize(DB_PATH) === 0;
        $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA journal_mode = WAL;');
        $pdo->exec('PRAGMA synchronous = NORMAL;');
        $pdo->exec('PRAGMA temp_store = MEMORY;');
        $pdo->exec('PRAGMA cache_size = -16000;');
        $pdo->exec('PRAGMA foreign_keys = ON;');
        if ($isNew) {
            require_once __DIR__ . '/db_init.php';
            init_database_schema($pdo);
        }
    }
    return $pdo;
}

function get_settings(bool $forceReload = false): array {
    static $settings = null;
    if ($settings === null || $forceReload) {
        $settings = [
            'site_name' => 'Cake Shop BD',
            'site_bn_name' => 'কেক শপ বিডি',
            'tagline' => 'সকল প্রকার প্রিমিয়াম ও কাস্টম কেকের বিশ্বস্ত অনলাইন বেকারি',
            'phone' => '+880 1581-703822',
            'phone2' => '+880 1818-273838',
            'whatsapp' => '8801581703822',
            'email' => 'jainal.dcitbd@gmail.com',
            'address' => 'চৌধুরী প্লাজা, নিচতলা, রুম নং ৩, পদুয়ার বাজার বিশ্বরোড, কুমিল্লা-৩৫০০, বাংলাদেশ',
            'notice_text' => '🎂 যেকোনো কেক অর্ডারে পাচ্ছেন ৩৫% পর্যন্ত ছাড়! সেইম-ডে এক্সপ্রেস ডেলিভারি চালু আছে | হটলাইন: 01581-703822 | কাস্টম ফটো ও বার্থডে কেকের জন্য হোয়াটসঅ্যাপে অর্ডার করুন।',
            'currency' => '৳',
            'delivery_inside' => '70',
            'delivery_outside' => '130',
            'free_delivery_min' => '2500',
            'facebook_url' => 'https://facebook.com/cakeshopbd',
            'instagram_url' => 'https://instagram.com/cakeshopbd',
            'youtube_url' => 'https://youtube.com/@cakeshopbd',
            'tiktok_url' => 'https://tiktok.com/@cakeshopbd',
            'linkedin_url' => 'https://linkedin.com/company/cakeshopbd',
            'twitter_url' => 'https://x.com/cakeshopbd',
            'map_embed_url' => 'https://www.google.com/maps?q=Paduar+Bazar+Bishwa+Road+Comilla+Bangladesh&output=embed',
            'business_hours' => 'প্রতিদিন সকাল ৯:০০ টা — রাত ১১:০০ টা (সপ্তাহে ৭ দিন)',
            'telegram_bot_token' => '',
            'telegram_chat_id' => '',
            'meta_pixel_id' => '',
        ];
        try {
            $rows = db()->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
            foreach ($rows as $r) {
                $settings[$r['setting_key']] = $r['setting_value'];
            }
        } catch (Throwable $e) {}
    }
    return $settings;
}

function setting(string $key, string $default = ''): string {
    $all = get_settings();
    return $all[$key] ?? $default;
}

function e($str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

// Persistent 365-Day Login Token (Stays logged in on Mobile/Desktop until explicit Logout)
function issue_persistent_login(int $userId): void {
    try {
        $raw = bin2hex(random_bytes(32));
        $hash = hash('sha256', $raw);
        $ttl = 31536000; // 365 days
        $exp = date('Y-m-d H:i:s', time() + $ttl);
        db()->prepare("INSERT INTO auth_tokens (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, ?)")
            ->execute([$userId, $hash, $exp, date('Y-m-d H:i:s')]);
        if (PHP_VERSION_ID >= 70300) {
            setcookie('sdc_remember', $raw, [
                'expires' => time() + $ttl,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } else {
            setcookie('sdc_remember', $raw, time() + $ttl, '/', '', false, true);
        }
    } catch (Throwable $e) {}
}

function clear_persistent_login(): void {
    if (!empty($_COOKIE['sdc_remember'])) {
        try {
            $hash = hash('sha256', $_COOKIE['sdc_remember']);
            db()->prepare("DELETE FROM auth_tokens WHERE token_hash = ?")->execute([$hash]);
        } catch (Throwable $e) {}
        setcookie('sdc_remember', '', time() - 3600, '/');
    }
}

function current_user(): ?array {
    static $u = false;
    if ($u !== false) return $u;

    // Auto-restore session from persistent cookie if PHP session expired
    if (empty($_SESSION['user_id']) && !empty($_COOKIE['sdc_remember'])) {
        try {
            $hash = hash('sha256', $_COOKIE['sdc_remember']);
            $st = db()->prepare("SELECT user_id FROM auth_tokens WHERE token_hash = ? AND expires_at > ?");
            $st->execute([$hash, date('Y-m-d H:i:s')]);
            $row = $st->fetch();
            if ($row) {
                $_SESSION['user_id'] = (int)$row['user_id'];
            }
        } catch (Throwable $e) {}
    }

    if (empty($_SESSION['user_id'])) {
        $u = null;
        return null;
    }

    $stmt = db()->prepare("SELECT * FROM users WHERE id = ? AND status = 1");
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch() ?: null;
    if ($u) {
        @db()->prepare("UPDATE users SET last_active = ? WHERE id = ?")->execute([date('Y-m-d H:i:s'), $u['id']]);
    }
    return $u;
}

function is_logged_in(): bool { return current_user() !== null; }
function is_admin(): bool {
    $u = current_user();
    return $u && in_array($u['role'], ['master_admin', 'admin', 'staff'], true);
}
function verify_user_password(string $plain, string $stored): bool {
    if (strpos($stored, 'sha256:') === 0) {
        return hash_equals(substr($stored, 7), hash('sha256', $plain));
    }
    return password_verify($plain, $stored);
}
