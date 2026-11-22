# 🎂 Cake Shop BD — লাইভ ডিপ্লয়মেন্ট ও GitHub গাইড (cPanel, XAMPP ও GitHub)

## ১. সুপার-ফাস্ট আর্কিটেকচার
- **Backend:** PHP 8+ (PHP 7.4+ Compatible) + SQLite3 (WAL Mode + Memory Cache — জিরো MySQL কনফিগারেশন, ইনস্ট্যান্ট লাইভ!)
- **Python Turbo Engine:** `python_engine/speed_optimizer.py` (JSON ক্যাশ প্রি-ওয়ার্ম ও Facebook Shop Catalog CSV জেনারেটর)
- **Frontend:** Bootstrap 5.3 + Font Awesome 6 + Kalpurush + Cake Shop BD Storefront Theme (`assets/css/storefront-theme.css` ও `assets/js/storefront-fx.js`) + PWA (`manifest.json` + `sw.js`)

## ২. cPanel (`public_html`) বা XAMPP-এ লাইভ করার নিয়ম
1. `cake-shop-bd.zip` ফাইলটি আপনার cPanel-এর `public_html` (অথবা XAMPP-এর `htdocs/cake-shop-bd/`) ফোল্ডারে Extract করুন।
2. `storage/` এবং `assets/images/uploads/` ফোল্ডারের পারমিশন `775` নিশ্চিত করুন।
3. সরাসরি ব্রাউজারে আপনার ডোমেইন ভিজিট করুন — ওয়েবসাইট, ৬৪টি কেক প্রোডাক্ট, ১৮টি ডেমো অর্ডার ও ডাটাবেজ সাথে সাথেই লাইভ হয়ে যাবে!

## ৩. ডেমো লগইন তথ্য
- **মাস্টার অ্যাডমিন প্যানেল (`/admin/index.php`):**
  - Email: `admin@sweetdreamcakes.com`
  - Password: `Admin@123`
- **কাস্টমার ড্যাশবোর্ড (`/customer/dashboard.php`):**
  - Email: `customer@sweetdreamcakes.com`
  - Password: `Customer@123`

## ৪. মোবাইল ও ইমেইল অর্ডার নোটিফিকেশন
- **ডিজিটাল HTML ইমেইল:** নতুন অর্ডার এলেই অ্যাডমিন ও কাস্টমারের ইমেইলে ডিজিটাল ইনভয়েস মেইল চলে যাবে এবং `storage/emails/` ফোল্ডারে HTML প্রিভিউ সেভ থাকবে।
- **মোবাইল পুশ নোটিফিকেশন:** হেডার বা অ্যাডমিন প্যানেলের 🔔 বাটনে ক্লিক করে ব্রাউজার/মোবাইল নোটিফিকেশন Allow করুন। এছাড়া **Admin -> ওয়েবসাইট তথ্য আপডেট**-এ Telegram Bot Token ও Chat ID দিলে প্রতি অর্ডারে সাথে সাথে মোবাইলে Telegram অ্যালার্ট যাবে।

## ৫. ফেসবুক পেজ সেটআপ কিট
- `facebook-page-setup/` ফোল্ডারে লোগো (`logo-square-1024.png`, `logo.svg`), কভার ব্যানার (`fb-cover-banner-1640x624.png`) এবং সম্পূর্ণ বাংলা SEO গাইড (`FB_PAGE_SETUP_GUIDE.html`) দেওয়া আছে।

## ৬. GitHub-এ প্রজেক্ট পুশ করার সম্পূর্ণ পদ্ধতি

### ধাপ ১: GitHub-এ নতুন Repository তৈরি করুন
1. [GitHub](https://github.com)-এ লগইন করে উপরের ডানদিকে **+** আইকন থেকে **New repository** ক্লিক করুন।
2. Repository name দিন: `cake-shop-bd` (Public অথবা Private যেকোনোটি বেছে নিন)।
3. **Add a README file** বা **.gitignore** টিক দিবেন না (ফাঁকা রিপোজিটরি রাখুন) এবং **Create repository** ক্লিক করুন।
4. তৈরি হওয়া রিপোজিটরির HTTPS লিংকটি কপি করুন (যেমন: `https://github.com/YOUR_USERNAME/cake-shop-bd.git`)।

### ধাপ ২: লোকাল ফোল্ডার থেকে প্রথমবার GitHub-এ পুশ করুন
`cake-shop-bd.zip` এক্সট্র্যাক্ট করে `cake-shop-bd` ফোল্ডারের ভেতরে **Git Bash / Terminal / VS Code Terminal** ওপেন করে নিচের কমান্ডগুলো একে একে চালান:

```bash
git init
git add .
git commit -m "Initial commit: Cake Shop BD redesigned storefront"
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/cake-shop-bd.git
git push -u origin main
```

> **লগইন টিপস:** প্রথমবার `git push` করলে GitHub ব্রাউজার লগইন বা Personal Access Token (PAT) চাইতে পারে। ব্রাউজার পপআপ এলে **Sign in with your browser** দিয়ে লগইন করলেই পুশ হয়ে যাবে।

### ধাপ ৩: পরবর্তীতে যেকোনো আপডেট GitHub-এ পুশ করার নিয়ম
কোড বা ডিজাইন পরিবর্তন করার পর প্রতিবার শুধু এই ৩টি কমান্ড চালালেই GitHub আপডেট হয়ে যাবে:

```bash
git add .
git commit -m "Update storefront or content"
git push
```

### ধাপ ৪: GitHub থেকে সরাসরি cPanel-এ Pull করার সুবিধা (ঐচ্ছিক)
- cPanel-এর **Git™ Version Control** অপশনে গিয়ে আপনার GitHub রিপোজিটরির URL দিয়ে Clone করতে পারবেন এবং পরে যেকোনো আপডেট **Pull or Deploy** ক্লিক করেই লাইভ সার্ভারে নিতে পারবেন।
