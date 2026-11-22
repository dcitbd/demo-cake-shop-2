<?php
/**
 * Admin Panel v3.1 POST Action Handler — Full CRUD for All Modules + Audit Tracking + Secure Password Reset
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
$act = $_POST['admin_action'] ?? '';
$pdo = db();

try {
    // 1. Add or Edit Product
    if ($act === 'add_product' || $act === 'edit_product') {
        $editId = (int)($_POST['edit_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $sku = trim($_POST['sku'] ?? ('SDC-' . rand(2000, 9999)));
        $catId = (int)($_POST['category_id'] ?? 1);
        $brandId = (int)($_POST['brand_id'] ?? 1);
        $reg = (float)($_POST['regular_price'] ?? 1000);
        $disc = (float)($_POST['discount_price'] ?? $reg);
        $pct = $reg > 0 ? max(0, (int)round((($reg - $disc) / $reg) * 100)) : 0;
        $stock = (int)($_POST['stock'] ?? 10);
        $defective = isset($_POST['defective_flag']) ? 1 : 0;

        $images = [];
        $urlList = trim($_POST['image_urls'] ?? '');
        if ($urlList !== '') {
            foreach (preg_split('/[\r\n,]+/', $urlList) as $uLine) {
                $uLine = trim($uLine);
                if ($uLine !== '') $images[] = $uLine;
            }
        }
        if (!empty($_FILES['image_files']['name'][0])) {
            foreach ($_FILES['image_files']['tmp_name'] as $i => $tmp) {
                if (!$tmp || $_FILES['image_files']['error'][$i] !== UPLOAD_ERR_OK) continue;
                $ext = strtolower(pathinfo($_FILES['image_files']['name'][$i], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                    $fname = 'cake_' . time() . '_' . $i . '.' . $ext;
                    if (move_uploaded_file($tmp, UPLOAD_DIR . '/' . $fname)) {
                        $images[] = 'assets/images/uploads/' . $fname;
                    }
                }
            }
        }
        if (empty($images)) $images = ['assets/images/cakes/chocolate-truffle.png'];

        $variants = [];
        $vW = $_POST['v_weight'] ?? []; $vF = $_POST['v_flavor'] ?? []; $vE = $_POST['v_extra'] ?? [];
        for ($i = 0; $i < count($vW); $i++) {
            if (trim($vW[$i]) !== '') {
                $variants[] = ['weight' => trim($vW[$i]), 'flavor' => trim($vF[$i] ?? 'ক্লাসিক'), 'extra' => (float)($vE[$i] ?? 0)];
            }
        }
        if (empty($variants)) $variants = [['weight' => '1 পাউন্ড', 'flavor' => 'ক্লাসিক', 'extra' => 0]];

        if ($act === 'edit_product' && $editId > 0) {
            $pdo->prepare("UPDATE products SET sku=?, name=?, category_id=?, brand_id=?, regular_price=?, discount_price=?, discount_percent=?, stock=?, defective_flag=?, short_desc=?, desc_step1=?, desc_step2=?, desc_step3=?, images_json=?, variants_json=? WHERE id=?")
                ->execute([$sku, $name, $catId, $brandId, $reg, $disc, $pct, $stock, $defective, trim($_POST['short_desc'] ?? ''), trim($_POST['desc_step1'] ?? ''), trim($_POST['desc_step2'] ?? ''), trim($_POST['desc_step3'] ?? ''), json_encode($images, JSON_UNESCAPED_UNICODE), json_encode($variants, JSON_UNESCAPED_UNICODE), $editId]);
            log_audit('EDIT_PRODUCT', "কেক আপডেট: {$name} (#{$editId})");
            $adminFlash = "✅ প্রোডাক্ট '{$name}' সফলভাবে আপডেট হয়েছে!";
        } else {
            $slug = 'cake-' . time() . '-' . strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $sku));
            $pdo->prepare("INSERT INTO products (sku, name, slug, category_id, brand_id, regular_price, discount_price, discount_percent, stock, defective_flag, short_desc, desc_step1, desc_step2, desc_step3, delivery_info, benefits_info, authentic_info, replacement_info, images_json, variants_json, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)")
                ->execute([$sku, $name, $slug, $catId, $brandId, $reg, $disc, $pct, $stock, $defective, trim($_POST['short_desc'] ?? ''), trim($_POST['desc_step1'] ?? ''), trim($_POST['desc_step2'] ?? ''), trim($_POST['desc_step3'] ?? ''), 'সেইম-ডে এক্সপ্রেস ডেলিভারি (২-৪ ঘন্টা)।', 'ফ্রি ক্যান্ডেল, কেক নাইফ ও কাস্টম নাম লেখা।', '১০০% হালাল ও প্রিমিয়াম উপাদান।', 'ড্যামেজ হলে ফ্রি রিপ্লেসমেন্ট গ্যারান্টি।', json_encode($images, JSON_UNESCAPED_UNICODE), json_encode($variants, JSON_UNESCAPED_UNICODE), date('Y-m-d H:i:s')]);
            log_audit('ADD_PRODUCT', "নতুন কেক যুক্ত: {$name} ({$sku})");
            $adminFlash = "✅ নতুন কেক '{$name}' সফলভাবে পাবলিশ হয়েছে!";
        }
    }

    elseif ($act === 'toggle_product_status') {
        $pdo->prepare("UPDATE products SET status = CASE WHEN status = 1 THEN 0 ELSE 1 END WHERE id = ?")->execute([(int)$_POST['id']]);
        log_audit('TOGGLE_PRODUCT', 'প্রোডাক্ট স্ট্যাটাস পরিবর্তন #' . (int)$_POST['id']);
        $adminFlash = '✅ প্রোডাক্ট স্ট্যাটাস পরিবর্তন হয়েছে!';
    }

    elseif ($act === 'delete_product') {
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([(int)$_POST['id']]);
        log_audit('DELETE_PRODUCT', 'প্রোডাক্ট ডিলিট #' . (int)$_POST['id']);
        $adminFlash = 'প্রোডাক্ট ডিলিট করা হয়েছে।';
    }

    elseif ($act === 'quick_stock') {
        $pdo->prepare("UPDATE products SET stock = ?, defective_flag = 0 WHERE id = ?")->execute([(int)$_POST['stock'], (int)$_POST['id']]);
        log_audit('UPDATE_STOCK', 'স্টক আপডেট #' . (int)$_POST['id'] . ' -> ' . (int)$_POST['stock']);
        $adminFlash = '✅ স্টক আপডেট হয়েছে!';
    }

    elseif ($act === 'bulk_upload_csv' && !empty($_FILES['csv_file']['tmp_name'])) {
        $fh = fopen($_FILES['csv_file']['tmp_name'], 'r');
        fgetcsv($fh);
        $cntUp = 0;
        $ins = $pdo->prepare("INSERT OR IGNORE INTO products (sku, name, slug, category_id, brand_id, regular_price, discount_price, discount_percent, stock, images_json, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)");
        while (($row = fgetcsv($fh)) !== false) {
            if (count($row) < 5) continue;
            $sku = preg_replace('/^\xEF\xBB\xBF/', '', trim($row[0]));
            $name = trim($row[1]);
            if ($sku === '' || $name === '') continue;
            $catId = (int)$row[2] ?: 1; $brId = (int)$row[3] ?: 1;
            $reg = (float)$row[4]; $disc = (float)($row[5] ?? $reg); $stk = (int)($row[6] ?? 10);
            $img = trim($row[7] ?? 'assets/images/cakes/chocolate-truffle.png');
            $pct = $reg > 0 ? max(0, (int)round((($reg - $disc) / $reg) * 100)) : 0;
            $ins->execute([$sku, $name, 'cake-csv-' . time() . '-' . $cntUp, $catId, $brId, $reg, $disc, $pct, $stk, json_encode([$img]), date('Y-m-d H:i:s')]);
            $cntUp++;
        }
        fclose($fh);
        log_audit('BULK_UPLOAD', "{$cntUp}টি কেক বাল্ক আপলোড হয়েছে");
        $adminFlash = "✅ {$cntUp}টি কেক Excel/CSV থেকে বাল্ক আপলোড হয়েছে!";
    }

    elseif ($act === 'bulk_price_update') {
        $ids = $_POST['pid'] ?? []; $regs = $_POST['reg'] ?? []; $discs = $_POST['disc'] ?? [];
        $upd = $pdo->prepare("UPDATE products SET regular_price = ?, discount_price = ?, discount_percent = ? WHERE id = ?");
        foreach ($ids as $i => $pid) {
            $r = (float)$regs[$i]; $d = (float)$discs[$i];
            $pct = $r > 0 ? max(0, (int)round((($r - $d) / $r) * 100)) : 0;
            $upd->execute([$r, $d, $pct, (int)$pid]);
        }
        log_audit('BULK_PRICE', 'বাল্ক মূল্য আপডেট সম্পন্ন');
        $adminFlash = '✅ বাল্ক মূল্য আপডেট সম্পন্ন!';
    }

    elseif ($act === 'bulk_image_update') {
        $ids = $_POST['pid'] ?? []; $urls = $_POST['img_url'] ?? [];
        $upd = $pdo->prepare("UPDATE products SET images_json = ? WHERE id = ?");
        foreach ($ids as $i => $pid) {
            $u = trim($urls[$i] ?? '');
            if ($u !== '') $upd->execute([json_encode([$u]), (int)$pid]);
        }
        log_audit('BULK_IMAGE', 'বাল্ক ইমেজ আপডেট সম্পন্ন');
        $adminFlash = '✅ বাল্ক ইমেজ আপডেট সম্পন্ন!';
    }

    // 2. Category Full CRUD (#10)
    elseif ($act === 'save_category') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name']);
        $parent = (int)($_POST['parent_id'] ?? 0);
        $icon = trim($_POST['icon'] ?: 'fa-cake-candles');
        if ($id > 0) {
            $pdo->prepare("UPDATE categories SET parent_id=?, name=?, icon=? WHERE id=?")->execute([$parent, $name, $icon, $id]);
            log_audit('EDIT_CATEGORY', "ক্যাটাগরি আপডেট: {$name}");
            $adminFlash = "✅ ক্যাটাগরি '{$name}' আপডেট হয়েছে!";
        } else {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $_POST['slug'] ?: 'cat'), '-')) . '-' . rand(100, 999);
            $pdo->prepare("INSERT INTO categories (parent_id, name, slug, icon, image) VALUES (?, ?, ?, ?, 'assets/images/cakes/vanilla-birthday.png')")->execute([$parent, $name, $slug, $icon]);
            log_audit('ADD_CATEGORY', "নতুন ক্যাটাগরি: {$name}");
            $adminFlash = "✅ ক্যাটাগরি '{$name}' যুক্ত হয়েছে!";
        }
    }
    elseif ($act === 'delete_category') {
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([(int)$_POST['id']]);
        log_audit('DELETE_CATEGORY', 'ক্যাটাগরি ডিলিট #' . (int)$_POST['id']);
        $adminFlash = 'ক্যাটাগরি ডিলিট করা হয়েছে।';
    }

    // 3. Brand Full CRUD (#12)
    elseif ($act === 'save_brand') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name']);
        $desc = trim($_POST['description'] ?? '');
        $logo = trim($_POST['logo'] ?? '');
        if (!empty($_FILES['logo_file']['tmp_name']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'svg'], true)) {
                $fn = 'brand_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['logo_file']['tmp_name'], UPLOAD_DIR . '/' . $fn)) {
                    $logo = 'assets/images/uploads/' . $fn;
                }
            }
        }
        if ($logo === '') $logo = 'assets/images/logo.png';
        if ($id > 0) {
            $pdo->prepare("UPDATE brands SET name=?, logo=?, description=? WHERE id=?")->execute([$name, $logo, $desc, $id]);
            log_audit('EDIT_BRAND', "ব্র্যান্ড আপডেট: {$name}");
            $adminFlash = "✅ ব্র্যান্ড '{$name}' আপডেট হয়েছে!";
        } else {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $name ?: 'brand'), '-')) . '-' . rand(100, 999);
            $pdo->prepare("INSERT INTO brands (name, slug, logo, description) VALUES (?, ?, ?, ?)")->execute([$name, $slug, $logo, $desc]);
            log_audit('ADD_BRAND', "নতুন ব্র্যান্ড: {$name}");
            $adminFlash = "✅ ব্র্যান্ড '{$name}' যুক্ত হয়েছে!";
        }
    }
    elseif ($act === 'delete_brand') {
        $pdo->prepare("DELETE FROM brands WHERE id = ?")->execute([(int)$_POST['id']]);
        log_audit('DELETE_BRAND', 'ব্র্যান্ড ডিলিট #' . (int)$_POST['id']);
        $adminFlash = 'ব্র্যান্ড ডিলিট করা হয়েছে।';
    }

    // 4. Color & Size Variants Full CRUD — NO PRICE (#13 & #14)
    elseif ($act === 'save_variant') {
        $id = (int)($_POST['id'] ?? 0);
        $type = $_POST['type'] ?? 'size'; $name = trim($_POST['name']); $code = trim($_POST['code'] ?? '');
        if ($id > 0) {
            $pdo->prepare("UPDATE variants SET type=?, name=?, code=?, extra_price=0 WHERE id=?")->execute([$type, $name, $code, $id]);
            log_audit('EDIT_VARIANT', "কালার/সাইজ আপডেট: {$name}");
            $adminFlash = '✅ কালার/সাইজ আপডেট হয়েছে!';
        } else {
            $pdo->prepare("INSERT INTO variants (type, name, code, extra_price) VALUES (?, ?, ?, 0)")->execute([$type, $name, $code]);
            log_audit('ADD_VARIANT', "নতুন কালার/সাইজ: {$name}");
            $adminFlash = '✅ নতুন কালার/সাইজ যুক্ত হয়েছে!';
        }
    }
    elseif ($act === 'delete_variant') {
        $pdo->prepare("DELETE FROM variants WHERE id = ?")->execute([(int)$_POST['id']]);
        log_audit('DELETE_VARIANT', 'ভ্যারিয়েন্ট ডিলিট #' . (int)$_POST['id']);
        $adminFlash = 'ভ্যারিয়েন্ট ডিলিট করা হয়েছে।';
    }

    // 5. Banner CMS Full CRUD (#15)
    elseif ($act === 'save_banner') {
        $id = (int)($_POST['id'] ?? 0);
        $img = trim($_POST['image_url'] ?? '');
        if (!empty($_FILES['banner_file']['tmp_name']) && $_FILES['banner_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['banner_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                $fn = 'banner_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['banner_file']['tmp_name'], UPLOAD_DIR . '/' . $fn)) $img = 'assets/images/uploads/' . $fn;
            }
        }
        if ($id > 0) {
            if ($img !== '') {
                $pdo->prepare("UPDATE banners SET title=?, subtitle=?, badge_text=?, btn_text=?, btn_link=?, image=?, sort_order=? WHERE id=?")
                    ->execute([trim($_POST['title']), trim($_POST['subtitle']), trim($_POST['badge_text']), trim($_POST['btn_text']), trim($_POST['btn_link']), $img, (int)($_POST['sort_order'] ?? 1), $id]);
            } else {
                $pdo->prepare("UPDATE banners SET title=?, subtitle=?, badge_text=?, btn_text=?, btn_link=?, sort_order=? WHERE id=?")
                    ->execute([trim($_POST['title']), trim($_POST['subtitle']), trim($_POST['badge_text']), trim($_POST['btn_text']), trim($_POST['btn_link']), (int)($_POST['sort_order'] ?? 1), $id]);
            }
            log_audit('EDIT_BANNER', 'ব্যানার আপডেট #' . $id);
            $adminFlash = '✅ ব্যানার আপডেট হয়েছে!';
        } else {
            if ($img === '') $img = 'assets/images/banners/banner-1.png';
            $pdo->prepare("INSERT INTO banners (title, subtitle, badge_text, btn_text, btn_link, image, sort_order, status) VALUES (?, ?, ?, ?, ?, ?, ?, 1)")
                ->execute([trim($_POST['title']), trim($_POST['subtitle']), trim($_POST['badge_text']), trim($_POST['btn_text'] ?: 'অর্ডার করুন'), trim($_POST['btn_link'] ?: 'products.php'), $img, (int)($_POST['sort_order'] ?? 1)]);
            log_audit('ADD_BANNER', 'নতুন ব্যানার: ' . trim($_POST['title']));
            $adminFlash = '✅ নতুন ব্যানার যুক্ত হয়েছে!';
        }
    }
    elseif ($act === 'toggle_banner_status') {
        $pdo->prepare("UPDATE banners SET status = CASE WHEN status = 1 THEN 0 ELSE 1 END WHERE id = ?")->execute([(int)$_POST['id']]);
        $adminFlash = '✅ ব্যানার স্ট্যাটাস পরিবর্তন হয়েছে!';
    }
    elseif ($act === 'delete_banner') {
        $pdo->prepare("DELETE FROM banners WHERE id = ?")->execute([(int)$_POST['id']]);
        log_audit('DELETE_BANNER', 'ব্যানার ডিলিট #' . (int)$_POST['id']);
        $adminFlash = 'ব্যানার ডিলিট করা হয়েছে।';
    }

    // 6. Orders CRUD (#17)
    elseif ($act === 'edit_order') {
        $pdo->prepare("UPDATE orders SET customer_name=?, customer_phone=?, shipping_address=?, total=?, payment_method=?, trx_id=?, order_status=?, payment_status=?, courier_name=?, tracking_code=? WHERE id=?")
            ->execute([trim($_POST['customer_name']), trim($_POST['customer_phone']), trim($_POST['shipping_address']), (float)$_POST['total'], trim($_POST['payment_method']), trim($_POST['trx_id']), $_POST['order_status'], $_POST['payment_status'], trim($_POST['courier_name']), trim($_POST['tracking_code']), (int)$_POST['id']]);
        log_audit('EDIT_ORDER', 'অর্ডার আপডেট #' . (int)$_POST['id']);
        $adminFlash = '✅ অর্ডার সফলভাবে আপডেট হয়েছে!';
    }
    elseif ($act === 'delete_order') {
        $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([(int)$_POST['id']]);
        log_audit('DELETE_ORDER', 'অর্ডার ডিলিট #' . (int)$_POST['id']);
        $adminFlash = 'অর্ডার ডিলিট করা হয়েছে।';
    }
    elseif ($act === 'add_manual_order') {
        $pid = (int)$_POST['product_id']; $qty = max(1, (int)$_POST['qty']);
        $p = $pdo->query("SELECT * FROM products WHERE id = {$pid}")->fetch();
        $sub = $p ? ((float)$p['discount_price'] * $qty) : 1000;
        $del = (float)($_POST['delivery_charge'] ?? 70); $tot = $sub + $del;
        $code = 'SDC-M' . date('ymd') . rand(10, 99);
        $img = $p ? product_first_image($p) : base_url('assets/images/cakes/chocolate-truffle.png');
        $items = [['id' => $pid, 'sku' => $p['sku'] ?? 'MANUAL', 'name' => $p['name'] ?? 'Custom Cake', 'image' => $img, 'variant' => trim($_POST['variant'] ?: '1 পাউন্ড | ক্লাসিক'), 'price' => $p['discount_price'] ?? $sub, 'qty' => $qty]];
        $now = date('Y-m-d H:i:s');
        $trx = trim($_POST['trx_id'] ?? '');
        $pdo->prepare("INSERT INTO orders (order_code, customer_name, customer_phone, customer_email, shipping_address, delivery_charge, subtotal, discount, total, payment_method, trx_id, payment_status, order_status, items_json, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, 'paid', 'confirmed', ?, ?)")
            ->execute([$code, trim($_POST['customer_name']), trim($_POST['customer_phone']), trim($_POST['customer_email']), trim($_POST['shipping_address']), $del, $sub, $tot, $_POST['payment_method'], $trx, json_encode($items, JSON_UNESCAPED_UNICODE), $now]);
        $oData = ['order_code' => $code, 'customer_name' => trim($_POST['customer_name']), 'customer_phone' => trim($_POST['customer_phone']), 'customer_email' => trim($_POST['customer_email']), 'shipping_address' => trim($_POST['shipping_address']), 'delivery_charge' => $del, 'subtotal' => $sub, 'discount' => 0, 'total' => $tot, 'payment_method' => $_POST['payment_method'], 'trx_id' => $trx, 'order_status' => 'confirmed', 'created_at' => $now];
        send_order_email($oData, $items);
        dispatch_order_notification($oData);
        log_audit('ADD_MANUAL_ORDER', "ম্যানুয়াল অর্ডার #{$code}");
        $adminFlash = "✅ নতুন অর্ডার #{$code} যুক্ত হয়েছে!";
    }

    // 7. Team
    elseif ($act === 'add_staff') {
        $pdo->prepare("INSERT INTO users (name, email, phone, password_hash, role, address, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute([trim($_POST['name']), trim($_POST['email']), trim($_POST['phone']), password_hash($_POST['password'], PASSWORD_DEFAULT), $_POST['role'] ?: 'staff', trim($_POST['address']), date('Y-m-d H:i:s')]);
        log_audit('ADD_STAFF', 'টিম সদস্য যুক্ত: ' . trim($_POST['email']));
        $adminFlash = '✅ নতুন টিম সদস্য যুক্ত হয়েছে!';
    }

    // 8. Courier Full CRUD (#18)
    elseif ($act === 'save_courier') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("UPDATE couriers SET name=?, api_url=?, api_key=?, secret_key=?, status=? WHERE id=?")
                ->execute([trim($_POST['name']), trim($_POST['api_url']), trim($_POST['api_key']), trim($_POST['secret_key']), (int)($_POST['status'] ?? 1), $id]);
            log_audit('EDIT_COURIER', 'কুরিয়ার আপডেট: ' . trim($_POST['name']));
            $adminFlash = '✅ কুরিয়ার API আপডেট হয়েছে!';
        } else {
            $pdo->prepare("INSERT INTO couriers (name, api_url, api_key, secret_key, status) VALUES (?, ?, ?, ?, 1)")
                ->execute([trim($_POST['name']), trim($_POST['api_url']), trim($_POST['api_key']), trim($_POST['secret_key'])]);
            log_audit('ADD_COURIER', 'নতুন কুরিয়ার API: ' . trim($_POST['name']));
            $adminFlash = '✅ নতুন কুরিয়ার API সফলভাবে যুক্ত হয়েছে!';
        }
    }
    elseif ($act === 'delete_courier') {
        $pdo->prepare("DELETE FROM couriers WHERE id = ?")->execute([(int)$_POST['id']]);
        log_audit('DELETE_COURIER', 'কুরিয়ার ডিলিট #' . (int)$_POST['id']);
        $adminFlash = 'কুরিয়ার ডিলিট করা হয়েছে।';
    }

    // 9. Payment Methods Full CRUD (#21)
    elseif ($act === 'save_payment_method') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("UPDATE payment_methods SET name=?, type=?, account_number=?, instructions=?, status=? WHERE id=?")
                ->execute([trim($_POST['name']), trim($_POST['type'] ?: 'mobile'), trim($_POST['account_number']), trim($_POST['instructions']), (int)($_POST['status'] ?? 1), $id]);
            log_audit('EDIT_PAYMENT_METHOD', 'পেমেন্ট মেথড আপডেট: ' . trim($_POST['name']));
            $adminFlash = '✅ পেমেন্ট মেথড আপডেট হয়েছে (সারা ওয়েবসাইটে লাইভ)!';
        } else {
            $pdo->prepare("INSERT INTO payment_methods (name, type, account_number, instructions, status) VALUES (?, ?, ?, ?, 1)")
                ->execute([trim($_POST['name']), trim($_POST['type'] ?: 'mobile'), trim($_POST['account_number']), trim($_POST['instructions'])]);
            log_audit('ADD_PAYMENT_METHOD', 'নতুন পেমেন্ট মেথড: ' . trim($_POST['name']));
            $adminFlash = '✅ নতুন পেমেন্ট মেথড যুক্ত হয়েছে!';
        }
    }
    elseif ($act === 'delete_payment_method') {
        $pdo->prepare("DELETE FROM payment_methods WHERE id = ?")->execute([(int)$_POST['id']]);
        log_audit('DELETE_PAYMENT_METHOD', 'পেমেন্ট মেথড ডিলিট #' . (int)$_POST['id']);
        $adminFlash = 'পেমেন্ট মেথড ডিলিট করা হয়েছে।';
    }

    // 10. Delivery Methods Full CRUD (#22)
    elseif ($act === 'save_delivery_method') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("UPDATE delivery_methods SET name=?, area=?, charge=?, estimated_time=?, status=? WHERE id=?")
                ->execute([trim($_POST['name']), trim($_POST['area'] ?: 'inside'), (float)$_POST['charge'], trim($_POST['estimated_time']), (int)($_POST['status'] ?? 1), $id]);
            log_audit('EDIT_DELIVERY_METHOD', 'ডেলিভারি মেথড আপডেট: ' . trim($_POST['name']));
            $adminFlash = '✅ ডেলিভারি মেথড আপডেট হয়েছে (চেকআউটে লাইভ)!';
        } else {
            $pdo->prepare("INSERT INTO delivery_methods (name, area, charge, estimated_time, status) VALUES (?, ?, ?, ?, 1)")
                ->execute([trim($_POST['name']), trim($_POST['area'] ?: 'inside'), (float)$_POST['charge'], trim($_POST['estimated_time'])]);
            log_audit('ADD_DELIVERY_METHOD', 'নতুন ডেলিভারি মেথড: ' . trim($_POST['name']));
            $adminFlash = '✅ নতুন ডেলিভারি মেথড যুক্ত হয়েছে!';
        }
    }
    elseif ($act === 'delete_delivery_method') {
        $pdo->prepare("DELETE FROM delivery_methods WHERE id = ?")->execute([(int)$_POST['id']]);
        log_audit('DELETE_DELIVERY_METHOD', 'ডেলিভারি মেথড ডিলিট #' . (int)$_POST['id']);
        $adminFlash = 'ডেলিভারি মেথড ডিলিট করা হয়েছে।';
    }

    // 11. Coupons Full CRUD + Per-Customer Usage Limit (#23)
    elseif ($act === 'save_coupon') {
        $id = (int)($_POST['id'] ?? 0);
        $maxU = max(1, (int)($_POST['max_uses_per_customer'] ?? 1));
        $codeUp = strtoupper(trim($_POST['code']));
        if ($id > 0) {
            $pdo->prepare("UPDATE coupons SET code=?, discount_type=?, discount_value=?, min_order=?, expiry_date=?, max_uses_per_customer=?, status=? WHERE id=?")
                ->execute([$codeUp, $_POST['discount_type'], (float)$_POST['discount_value'], (float)$_POST['min_order'], $_POST['expiry_date'], $maxU, (int)($_POST['status'] ?? 1), $id]);
            log_audit('EDIT_COUPON', "কুপন আপডেট: {$codeUp} (লিমিট: {$maxU} বার)");
            $adminFlash = '✅ কুপন আপডেট হয়েছে!';
        } else {
            $pdo->prepare("INSERT INTO coupons (code, discount_type, discount_value, min_order, expiry_date, max_uses_per_customer, status) VALUES (?, ?, ?, ?, ?, ?, 1)")
                ->execute([$codeUp, $_POST['discount_type'], (float)$_POST['discount_value'], (float)$_POST['min_order'], $_POST['expiry_date'], $maxU]);
            log_audit('ADD_COUPON', "নতুন কুপন: {$codeUp} (লিমিট: {$maxU} বার)");
            $adminFlash = '✅ নতুন কুপন তৈরি হয়েছে!';
        }
    }
    elseif ($act === 'delete_coupon') {
        $pdo->prepare("DELETE FROM coupons WHERE id = ?")->execute([(int)$_POST['id']]);
        log_audit('DELETE_COUPON', 'কুপন ডিলিট #' . (int)$_POST['id']);
        $adminFlash = 'কুপন ডিলিট করা হয়েছে।';
    }

    // 12. Website Info & All Social Media Links (#24)
    elseif ($act === 'save_settings') {
        $upd = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON CONFLICT(setting_key) DO UPDATE SET setting_value=excluded.setting_value");
        foreach ($_POST['s'] ?? [] as $k => $v) $upd->execute([$k, trim($v)]);
        get_settings(true); // Refresh static cache immediately so updated data shows live
        log_audit('UPDATE_SETTINGS', 'ওয়েবসাইট ও সোশ্যাল তথ্য আপডেট');
        $adminFlash = '✅ ওয়েবসাইট ও সোশ্যাল মিডিয়া তথ্য সারা সাইটে লাইভ আপডেট হয়েছে!';
    }

    // 13. Profile Update
    elseif ($act === 'update_profile') {
        $u = current_user();
        $pdo->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?")
            ->execute([trim($_POST['name']), trim($_POST['phone']), trim($_POST['address']), $u['id']]);
        log_audit('UPDATE_PROFILE', 'অ্যাডমিন প্রোফাইল আপডেট');
        $adminFlash = '✅ প্রোফাইল আপডেট হয়েছে!';
    }

    // 14. Secure Password Change (Requires Old Password OR Email Verification Code #25)
    elseif ($act === 'change_password_old') {
        $u = current_user();
        $old = $_POST['old_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        if (!verify_user_password($old, $u['password_hash'])) {
            $adminError = 'পুরাতন পাসওয়ার্ড সঠিক নয়!';
        } elseif (strlen($new) < 6) {
            $adminError = 'নতুন পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।';
        } else {
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([password_hash($new, PASSWORD_DEFAULT), $u['id']]);
            log_audit('PASSWORD_CHANGE', 'পুরাতন পাসওয়ার্ড যাচাই করে পাসওয়ার্ড পরিবর্তন করা হয়েছে');
            $adminFlash = '✅ পাসওয়ার্ড সফলভাবে পরিবর্তন হয়েছে!';
        }
    }
    elseif ($act === 'send_reset_code') {
        $u = current_user();
        $otp = (string)random_int(100000, 999999);
        $exp = date('Y-m-d H:i:s', time() + 900); // 15 mins
        $pdo->prepare("INSERT INTO password_reset_codes (user_id, email, code, expires_at, used) VALUES (?, ?, ?, ?, 0)")
            ->execute([$u['id'], $u['email'], $otp, $exp]);
        $siteName = setting('site_name', 'SweetDream Cakes BD');
        $siteEmail = setting('email', 'jainal.dcitbd@gmail.com');
        $body = "<h3>{$siteName} — পাসওয়ার্ড রিসেট কোড</h3><p>আপনার ৬ ডিজিটের ভেরিফিকেশন কোড: <strong style='font-size:22px;color:#b84a6b;'>{$otp}</strong> (মেয়াদ ১৫ মিনিট)।</p>";
        @file_put_contents(EMAIL_LOG_DIR . '/reset_code_' . $u['id'] . '.html', $body);
        $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: {$siteName} <{$siteEmail}>\r\n";
        @mail($u['email'], '=?UTF-8?B?' . base64_encode('পাসওয়ার্ড রিসেট কোড - ' . $siteName) . '?=', $body, $headers);
        log_audit('PASSWORD_OTP_SENT', 'ইমেইল ভেরিফিকেশন কোড পাঠানো হয়েছে: ' . $u['email']);
        $adminFlash = "📧 আপনার ইমেইল ({$u['email']})-এ ৬ ডিজিটের ভেরিফিকেশন কোড পাঠানো হয়েছে!";
    }
    elseif ($act === 'reset_password_otp') {
        $u = current_user();
        $code = trim($_POST['reset_code'] ?? '');
        $new = $_POST['new_password'] ?? '';
        $st = $pdo->prepare("SELECT * FROM password_reset_codes WHERE user_id = ? AND code = ? AND used = 0 AND expires_at > ? ORDER BY id DESC LIMIT 1");
        $st->execute([$u['id'], $code, date('Y-m-d H:i:s')]);
        $row = $st->fetch();
        if (!$row) {
            $adminError = 'ভেরিফিকেশন কোডটি সঠিক নয় অথবা মেয়াদ শেষ হয়ে গেছে!';
        } elseif (strlen($new) < 6) {
            $adminError = 'নতুন পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।';
        } else {
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([password_hash($new, PASSWORD_DEFAULT), $u['id']]);
            $pdo->prepare("UPDATE password_reset_codes SET used = 1 WHERE id = ?")->execute([$row['id']]);
            log_audit('PASSWORD_RESET_OTP', 'ইমেইল OTP কোড দিয়ে পাসওয়ার্ড রিসেট সম্পন্ন');
            $adminFlash = '✅ ইমেইল কোড যাচাই করে পাসওয়ার্ড সফলভাবে রিসেট করা হয়েছে!';
        }
    }
} catch (Throwable $e) {
    $adminError = 'ত্রুটি: ' . $e->getMessage();
}
