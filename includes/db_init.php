<?php
/**
 * Database Schema Initializer v3.1 (Auto-runs if SQLite DB does not exist)
 */
function init_database_schema(PDO $pdo): void {
    $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS settings (
    setting_key TEXT PRIMARY KEY,
    setting_value TEXT
);
CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    parent_id INTEGER DEFAULT 0,
    name TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    icon TEXT DEFAULT 'fa-cake-candles',
    image TEXT DEFAULT '',
    sort_order INTEGER DEFAULT 0,
    status INTEGER DEFAULT 1
);
CREATE TABLE IF NOT EXISTS brands (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    logo TEXT DEFAULT '',
    description TEXT DEFAULT '',
    status INTEGER DEFAULT 1
);
CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sku TEXT UNIQUE NOT NULL,
    name TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    category_id INTEGER NOT NULL,
    brand_id INTEGER NOT NULL,
    regular_price REAL NOT NULL,
    discount_price REAL NOT NULL,
    discount_percent INTEGER DEFAULT 0,
    stock INTEGER DEFAULT 10,
    defective_flag INTEGER DEFAULT 0,
    defective_note TEXT DEFAULT '',
    rating REAL DEFAULT 4.8,
    review_count INTEGER DEFAULT 0,
    short_desc TEXT DEFAULT '',
    desc_step1 TEXT DEFAULT '',
    desc_step2 TEXT DEFAULT '',
    desc_step3 TEXT DEFAULT '',
    delivery_info TEXT DEFAULT '',
    benefits_info TEXT DEFAULT '',
    authentic_info TEXT DEFAULT '',
    replacement_info TEXT DEFAULT '',
    images_json TEXT DEFAULT '[]',
    variants_json TEXT DEFAULT '[]',
    is_featured INTEGER DEFAULT 1,
    status INTEGER DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS variants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT NOT NULL,
    name TEXT NOT NULL,
    code TEXT DEFAULT '',
    extra_price REAL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS banners (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    subtitle TEXT DEFAULT '',
    badge_text TEXT DEFAULT '',
    btn_text TEXT DEFAULT 'অর্ডার করুন',
    btn_link TEXT DEFAULT 'products.php',
    image TEXT NOT NULL,
    sort_order INTEGER DEFAULT 0,
    status INTEGER DEFAULT 1
);
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    phone TEXT DEFAULT '',
    password_hash TEXT NOT NULL,
    role TEXT DEFAULT 'customer',
    address TEXT DEFAULT '',
    avatar TEXT DEFAULT '',
    status INTEGER DEFAULT 1,
    last_active TEXT DEFAULT CURRENT_TIMESTAMP,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS auth_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token_hash TEXT UNIQUE NOT NULL,
    expires_at TEXT NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS password_reset_codes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    email TEXT NOT NULL,
    code TEXT NOT NULL,
    expires_at TEXT NOT NULL,
    used INTEGER DEFAULT 0
);
CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_code TEXT UNIQUE NOT NULL,
    customer_id INTEGER DEFAULT 0,
    customer_name TEXT NOT NULL,
    customer_phone TEXT NOT NULL,
    customer_email TEXT DEFAULT '',
    shipping_address TEXT NOT NULL,
    delivery_area TEXT DEFAULT 'inside',
    delivery_charge REAL DEFAULT 70,
    subtotal REAL NOT NULL,
    discount REAL DEFAULT 0,
    total REAL NOT NULL,
    payment_method TEXT DEFAULT 'bKash',
    trx_id TEXT DEFAULT '',
    payment_status TEXT DEFAULT 'paid',
    order_status TEXT DEFAULT 'pending',
    courier_name TEXT DEFAULT '',
    tracking_code TEXT DEFAULT '',
    coupon_code TEXT DEFAULT '',
    notes TEXT DEFAULT '',
    items_json TEXT NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS reviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    user_name TEXT NOT NULL,
    rating INTEGER NOT NULL,
    comment TEXT NOT NULL,
    status INTEGER DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS favorites (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, product_id)
);
CREATE TABLE IF NOT EXISTS coupons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE NOT NULL,
    discount_type TEXT DEFAULT 'percent',
    discount_value REAL NOT NULL,
    min_order REAL DEFAULT 0,
    expiry_date TEXT DEFAULT '',
    max_uses_per_customer INTEGER DEFAULT 1,
    status INTEGER DEFAULT 1
);
CREATE TABLE IF NOT EXISTS coupon_usages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    coupon_code TEXT NOT NULL,
    customer_key TEXT NOT NULL,
    order_code TEXT DEFAULT '',
    used_at TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS couriers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    api_url TEXT DEFAULT '',
    api_key TEXT DEFAULT '',
    secret_key TEXT DEFAULT '',
    status INTEGER DEFAULT 1
);
CREATE TABLE IF NOT EXISTS payment_methods (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    type TEXT DEFAULT 'mobile',
    account_number TEXT DEFAULT '',
    instructions TEXT DEFAULT '',
    status INTEGER DEFAULT 1
);
CREATE TABLE IF NOT EXISTS delivery_methods (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    area TEXT DEFAULT 'inside',
    charge REAL DEFAULT 70,
    estimated_time TEXT DEFAULT '২-৪ ঘন্টা',
    status INTEGER DEFAULT 1
);
CREATE TABLE IF NOT EXISTS live_visitors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id TEXT UNIQUE NOT NULL,
    user_id INTEGER DEFAULT 0,
    user_name TEXT DEFAULT 'Guest Visitor',
    ip_address TEXT DEFAULT '',
    current_page TEXT DEFAULT '/',
    device TEXT DEFAULT 'Desktop',
    last_seen TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT DEFAULT 'order',
    title TEXT NOT NULL,
    message TEXT NOT NULL,
    order_code TEXT DEFAULT '',
    is_read INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER DEFAULT 0,
    user_name TEXT DEFAULT 'System',
    action TEXT NOT NULL,
    details TEXT DEFAULT '',
    ip_address TEXT DEFAULT '',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS contact_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT DEFAULT '',
    phone TEXT DEFAULT '',
    subject TEXT DEFAULT '',
    message TEXT NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
SQL;
    $pdo->exec($sql);
}
