<?php
require_once __DIR__ . '/includes/functions.php';
$siteName = setting('site_name', 'Cake Shop BD');
$devEmail = 'jainal.dcitbd@gmail.com';
$devPhone = '+880 1581-703822';
$devWa = '8801581703822';

$mailSuccess = '';
$mailError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['dev_action'] ?? '') === 'send_dev_email') {
    $senderName = trim($_POST['sender_name'] ?? '');
    $senderEmail = trim($_POST['sender_email'] ?? '');
    $senderPhone = trim($_POST['sender_phone'] ?? '');
    $subject = trim($_POST['subject'] ?? 'Project / Collaboration Inquiry');
    $message = trim($_POST['message'] ?? '');

    if ($senderName === '' || $senderEmail === '' || $message === '') {
        $mailError = 'অনুগ্রহ করে আপনার নাম, ইমেইল এবং বার্তা সঠিকভাবে পূরণ করুন।';
    } elseif (!filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
        $mailError = 'সঠিক ইমেইল ঠিকানা লিখুন।';
    } else {
        $now = date('Y-m-d H:i:s');
        try {
            db()->prepare("INSERT INTO contact_messages (name, email, phone, subject, message, created_at) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$senderName, $senderEmail, $senderPhone, '[Developer] ' . $subject, $message, $now]);
            log_audit('DEVELOPER_EMAIL', "ডেভেলপার মেসেজ: {$senderName} ({$senderEmail}) — {$subject}");
        } catch (Throwable $e) {}

        $htmlBody = "<!DOCTYPE html><html lang='bn'><head><meta charset='UTF-8'></head><body style='font-family:Kalpurush,Segoe UI,Arial,sans-serif;background:#0f172a;color:#e2e8f0;padding:24px;'>
          <div style='max-width:600px;margin:0 auto;background:#1e293b;border-radius:16px;padding:24px;border:1px solid #38bdf8;'>
            <h2 style='color:#38bdf8;margin-top:0;'>📩 নতুন ডেভেলপার প্রোফাইল ইমেইল</h2>
            <p><strong>নাম:</strong> " . e($senderName) . "<br>
            <strong>ইমেইল:</strong> " . e($senderEmail) . "<br>
            <strong>ফোন:</strong> " . e($senderPhone ?: 'N/A') . "<br>
            <strong>বিষয়:</strong> " . e($subject) . "<br>
            <strong>সময়:</strong> {$now}</p>
            <div style='background:#0f172a;padding:16px;border-radius:12px;border:1px solid #334155;white-space:pre-line;'>" . e($message) . "</div>
          </div></body></html>";

        @file_put_contents(EMAIL_LOG_DIR . '/dev_msg_' . date('Ymd_His') . '.html', $htmlBody);
        $headers  = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$siteName} <{$devEmail}>\r\n";
        $headers .= "Reply-To: {$senderName} <{$senderEmail}>\r\n";
        @mail($devEmail, '=?UTF-8?B?' . base64_encode('Developer Inquiry: ' . $subject) . '?=', $htmlBody, $headers);

        $mailSuccess = 'ধন্যবাদ! আপনার ইমেইলটি সফলভাবে পাঠানো হয়েছে। খুব শীঘ্রই যোগাযোগ করা হবে।';
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Developer Profile | Jainal Abedin — Full-Stack Web Developer &amp; IT Architect</title>
  <link rel="preconnect" href="https://fonts.maateen.me" crossorigin>
  <link href="https://fonts.maateen.me/kalpurush/font.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <style>
    :root {
      --bg: #050812;
      --surface: rgba(15, 23, 42, 0.86);
      --surface-dark: #030712;
      --border: rgba(56, 189, 248, 0.18);
      --accent: #38bdf8;
      --accent2: #f472b6;
      --gold: #fbbf24;
      --green: #22c55e;
      --text: #e2e8f0;
      --muted: #94a3b8;
    }
    * { box-sizing: border-box; }
    html { overflow-x: hidden; scroll-behavior: smooth; }
    body {
      background-color: var(--bg);
      background-image:
        radial-gradient(circle at 12% 14%, rgba(56, 189, 248, 0.15), transparent 36%),
        radial-gradient(circle at 88% 10%, rgba(244, 114, 182, 0.13), transparent 34%),
        linear-gradient(rgba(56, 189, 248, 0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(56, 189, 248, 0.03) 1px, transparent 1px);
      background-size: 100% 100%, 100% 100%, 38px 38px, 38px 38px;
      color: var(--text);
      font-family: 'Kalpurush', 'Plus Jakarta Sans', system-ui, sans-serif;
      min-height: 100vh;
      overflow-x: hidden;
    }
    .mono { font-family: 'Fira Code', monospace; }
    .dev-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 22px;
      padding: 26px;
      backdrop-filter: blur(16px);
      box-shadow: 0 18px 42px rgba(0, 0, 0, 0.35);
      transition: transform 0.28s ease, border-color 0.28s ease, box-shadow 0.28s ease;
      animation: fadeUp 0.55s ease both;
    }
    .dev-card:hover {
      transform: translateY(-4px);
      border-color: rgba(56, 189, 248, 0.45);
      box-shadow: 0 22px 50px rgba(56, 189, 248, 0.12);
    }
    @keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
    .terminal-bar {
      display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
      padding-bottom: 14px; margin-bottom: 20px;
      border-bottom: 1px solid rgba(148, 163, 184, 0.16);
    }
    .dot { width: 11px; height: 11px; border-radius: 50%; display: inline-block; }
    .glow-badge {
      background: rgba(56, 189, 248, 0.1);
      color: var(--accent);
      border: 1px solid rgba(56, 189, 248, 0.32);
      padding: 6px 14px; border-radius: 50px;
      font-size: 12px; font-weight: 700;
      display: inline-flex; align-items: center; gap: 7px;
    }
    .pulse-dot {
      width: 9px; height: 9px; border-radius: 50%;
      background: var(--green); box-shadow: 0 0 12px var(--green);
      animation: pulse 1.6s infinite;
    }
    @keyframes pulse { 0%,100%{ opacity:1; transform:scale(1); } 50%{ opacity:0.45; transform:scale(1.35); } }
    .portrait-ring {
      width: 158px; height: 158px; border-radius: 26px; padding: 4px;
      background: linear-gradient(135deg, var(--accent), var(--accent2), var(--gold));
      box-shadow: 0 14px 36px rgba(56, 189, 248, 0.25);
      flex-shrink: 0;
    }
    .portrait-ring img {
      width: 100%; height: 100%; object-fit: cover; object-position: top center;
      border-radius: 22px; display: block; background: #0f172a;
    }
    .timeline-item {
      position: relative; padding-left: 20px; padding-bottom: 18px;
      border-left: 2px solid rgba(56, 189, 248, 0.25);
    }
    .timeline-item:last-child { padding-bottom: 0; }
    .timeline-item::before {
      content: ''; position: absolute; left: -6px; top: 5px;
      width: 10px; height: 10px; border-radius: 50%;
      background: var(--accent); box-shadow: 0 0 10px var(--accent);
    }
    .sub-box {
      background: var(--surface-dark);
      border: 1px solid rgba(148, 163, 184, 0.16);
      border-radius: 15px; padding: 15px;
      transition: border-color 0.22s ease, transform 0.22s ease;
    }
    .sub-box:hover { border-color: rgba(56, 189, 248, 0.42); }
    .tech-chip {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 6px 12px; border-radius: 10px;
      background: var(--surface-dark);
      border: 1px solid rgba(148, 163, 184, 0.2);
      color: #cbd5e1; font-size: 13px; font-weight: 600;
      transition: all 0.2s ease;
    }
    .tech-chip:hover { border-color: var(--accent); color: #fff; transform: translateY(-2px); }
    .skill-bar { height: 8px; border-radius: 50px; background: #1e293b; overflow: hidden; }
    .skill-fill { height: 100%; border-radius: 50px; background: linear-gradient(90deg, var(--accent), var(--accent2)); animation: growBar 1.2s ease forwards; }
    @keyframes growBar { from { width: 0; } }
    .dev-input {
      background: #030712 !important;
      border: 1px solid rgba(148, 163, 184, 0.24) !important;
      color: #f8fafc !important;
      border-radius: 12px !important;
      padding: 10px 14px !important;
    }
    .dev-input:focus {
      border-color: var(--accent) !important;
      box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.16) !important;
    }
    .dev-input::placeholder { color: #64748b !important; }
    @media (max-width: 767px) {
      .dev-card { padding: 18px; border-radius: 18px; }
      .portrait-ring { width: 120px; height: 120px; border-radius: 20px; }
      .portrait-ring img { border-radius: 16px; }
      h1.display-5 { font-size: 1.75rem; }
    }
  </style>
</head>
<body class="py-3 py-md-5">
<div class="container" style="max-width:1160px;">
  <!-- Top Bar -->
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <a href="index.php" class="btn btn-outline-light btn-sm rounded-pill px-3 py-2 mono">&larr; <?= e($siteName) ?></a>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="#contactDeveloper" class="btn btn-info btn-sm rounded-pill px-3 fw-bold text-dark"><i class="fa-solid fa-envelope me-1"></i>ইমেইল পাঠান</a>
      <span class="glow-badge mono"><span class="pulse-dot"></span> DEVELOPER_MODE</span>
    </div>
  </div>

  <!-- 1. Hero Profile Card (CV Verified + Real Photo) -->
  <div class="dev-card mb-4">
    <div class="terminal-bar">
      <span class="dot bg-danger"></span>
      <span class="dot bg-warning"></span>
      <span class="dot bg-success"></span>
      <span class="mono small text-secondary ms-2">~/jainal-abedin/cv-profile.sh</span>
      <span class="ms-auto mono small text-info d-none d-sm-inline">Cumilla-3500, Bangladesh</span>
    </div>
    <div class="d-flex flex-column flex-md-row align-items-center align-items-md-start gap-4">
      <div class="portrait-ring">
        <img src="assets/images/developer-jainal.jpg" alt="Jainal Abedin">
      </div>
      <div class="flex-grow-1 text-center text-md-start">
        <div class="mono text-info small mb-1">$ whoami :: Full-Stack Web Developer, Data Analyzer &amp; Senior IT Trainer</div>
        <h1 class="display-5 fw-bold text-white mb-1">Jainal Abedin</h1>
        <div class="text-warning fw-bold mb-3">Manager — Techno World In Russia &bull; System Developer — Techno World BD &bull; Senior Trainer — Dream Career IT</div>
        <p class="mb-3" style="color:var(--muted);line-height:1.7;">
          প্রযুক্তি, ওয়েব ডেভেলপমেন্ট, ডাটাবেস আর্কিটেকচার, ই-কমার্স ম্যানেজমেন্ট, ডিজিটাল মার্কেটিং ও অ্যানালিটিক্সে অভিজ্ঞ ফুল-স্ট্যাক ডেভেলপার এবং আইটি প্রফেশনাল। একসাথে একাধিক প্রজেক্ট দক্ষতার সাথে পরিচালনা ও নির্ধারিত সময়ে ডেলিভারি নিশ্চিত করা আমার মূল লক্ষ্য।
        </p>
        <div class="d-flex flex-wrap justify-content-center justify-content-md-start gap-2">
          <a href="tel:<?= e($devPhone) ?>" class="btn btn-info rounded-pill fw-bold px-3 text-dark btn-sm py-2"><i class="fa-solid fa-phone me-1"></i><?= e($devPhone) ?></a>
          <a href="mailto:<?= e($devEmail) ?>" class="btn btn-outline-light rounded-pill fw-bold px-3 btn-sm py-2"><i class="fa-solid fa-envelope me-1"></i><?= e($devEmail) ?></a>
          <a href="https://wa.me/<?= e($devWa) ?>" target="_blank" rel="noopener" class="btn btn-success rounded-pill fw-bold px-3 btn-sm py-2"><i class="fa-brands fa-whatsapp me-1"></i>WhatsApp</a>
          <a href="https://github.com/dcitbd" target="_blank" rel="noopener" class="btn btn-outline-info rounded-pill fw-bold px-3 btn-sm py-2"><i class="fa-brands fa-github me-1"></i>GitHub</a>
          <a href="https://dcitbd.github.io/Jainal-Abedin/" target="_blank" rel="noopener" class="btn btn-outline-warning rounded-pill fw-bold px-3 btn-sm py-2"><i class="fa-solid fa-globe me-1"></i>Portfolio</a>
        </div>
      </div>
    </div>
  </div>

  <!-- 2. Experience Timeline & Education/Certifications -->
  <div class="row g-4 mb-4">
    <div class="col-lg-7">
      <div class="dev-card h-100">
        <h5 class="fw-bold text-white mb-4 mono"><i class="fa-solid fa-briefcase text-info me-2"></i>Work_Experience[]</h5>
        <?php
        $experiences = [
          ['Techno World BD', 'Data Analyzer & System Developer (Part-time)', 'March 2026 – Present', 'Bara Para, Adarsha Sadar, Cumilla-3500'],
          ['Food City Agro', 'Audit Officer (Part-time)', 'November 2025 – Present', 'Sunaimuri, Barura, Cumilla-3500'],
          ['Techno World In Russia', 'Manager (Prev. E-commerce Support Manager)', 'December 2024 – Present', 'Bara Para, Adarsha Sadar, Cumilla-3500'],
          ['Dream Career IT, Cumilla', 'Senior Computer Trainer (Part-time)', 'December 2024 – Present', 'Poduar Bazar, Cumilla-3500'],
          ['UNICEF Farmer Field Nutrition School (FFNS)', 'Team / School Leader (Field Facilitator)', 'June 2021 – November 2024', 'Moheshkhali, Cox’s Bazar-4710'],
          ['Dream Career IT Bangladesh', 'Computer Trainer (Maheshkhali Branch)', 'April 2021 – November 2024', 'Bara Maheshkhali, Cox’s Bazar-4710'],
          ['Codesleepers.com (Software Company)', 'Web Designer & Developer', 'August 2020 – May 2021', 'New Market, Kandirpar, Cumilla-3500'],
          ['Amader Gram Computer Training Centre', 'Senior Computer Trainer (Part-time)', 'June 2018 – April 2021', 'Poduar Bazar, Bishwo Road, Cumilla-3500'],
          ['Rangs Motor’s Limited', 'Computer Operator', 'January 2018 – July 2020', 'Poduar Bazar, Bishwo Road, Cumilla-3500'],
        ];
        foreach ($experiences as $ex): ?>
          <div class="timeline-item">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-1">
              <strong class="text-white"><?= e($ex[1]) ?></strong>
              <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 mono" style="font-size:11px;"><?= e($ex[2]) ?></span>
            </div>
            <div class="small text-warning fw-semibold"><?= e($ex[0]) ?></div>
            <div class="small" style="color:var(--muted);"><i class="fa-solid fa-location-dot me-1"></i><?= e($ex[3]) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="col-lg-5 d-flex flex-column gap-4">
      <!-- Education -->
      <div class="dev-card">
        <h5 class="fw-bold text-white mb-3 mono"><i class="fa-solid fa-graduation-cap text-warning me-2"></i>Education[]</h5>
        <div class="d-flex flex-column gap-2">
          <div class="sub-box">
            <div class="fw-bold text-info">Honors (BSS) — Political Science</div>
            <div class="small text-white">Cox’s Bazar City College (National University)</div>
            <div class="small" style="color:var(--muted);">Passing Year: 2026</div>
          </div>
          <div class="sub-box">
            <div class="fw-bold text-warning">H.S.C — Humanities</div>
            <div class="small text-white">Moheshkhali University College (Chittagong Board)</div>
            <div class="small" style="color:var(--muted);">Passing Year: 2019</div>
          </div>
          <div class="sub-box">
            <div class="fw-bold text-success">S.S.C — Humanities</div>
            <div class="small text-white">Moheshkhali Island High School (Chittagong Board)</div>
            <div class="small" style="color:var(--muted);">Passing Year: 2016</div>
          </div>
        </div>
      </div>

      <!-- Certifications -->
      <div class="dev-card flex-grow-1">
        <h5 class="fw-bold text-white mb-3 mono"><i class="fa-solid fa-certificate text-info me-2"></i>Certifications[]</h5>
        <div class="d-flex flex-column gap-2 small">
          <div class="sub-box py-2"><strong class="text-white">Web Design &amp; Development (12 Months)</strong><div style="color:var(--muted);">Codesleepers.com, Cumilla (2019)</div></div>
          <div class="sub-box py-2"><strong class="text-white">Digital Marketing (6 Months)</strong><div style="color:var(--muted);">ABCL IT, Noyapolton, Dhaka (2023)</div></div>
          <div class="sub-box py-2"><strong class="text-white">Graphic Design Advance (6 Months)</strong><div style="color:var(--muted);">Amader Gram Computer Training Centre (2018)</div></div>
          <div class="sub-box py-2"><strong class="text-white">Microsoft Office Advance (3 Months)</strong><div style="color:var(--muted);">Amader Gram Computer Training Centre (2018)</div></div>
          <div class="sub-box py-2"><strong class="text-white">Field Farmer Nutrition Service (FFNS)</strong><div style="color:var(--muted);">DAE-UNICEF, Cox’s Bazar</div></div>
          <div class="sub-box py-2"><strong class="text-white">Reception &amp; Customer Service (RCS)</strong><div style="color:var(--muted);">National Youth Development Institute, Savar</div></div>
        </div>
      </div>
    </div>
  </div>

  <!-- 3. Portfolio Creations & Skills -->
  <div class="row g-4 mb-4">
    <div class="col-lg-6">
      <div class="dev-card h-100">
        <h5 class="fw-bold text-white mb-3 mono"><i class="fa-solid fa-microchip text-info me-2"></i>Technical_Skills[]</h5>
        <?php
        $skills = [
          ['Web Design & Full-Stack Development (PHP, JS, SQLite/MySQL)', 98],
          ['E-Commerce Platforms, Admin CMS & PWA Apps', 97],
          ['Database, MS Excel/Access & Data Analysis', 95],
          ['Graphic Design, Photoshop & UI/UX Branding', 94],
          ['Digital Marketing, Multi-Category Ads & SEO', 93],
          ['Computer Hardware, Software Maintenance & IT Training', 96],
        ];
        foreach ($skills as $sk): ?>
          <div class="mb-3">
            <div class="d-flex justify-content-between small mb-1">
              <span class="fw-bold"><?= e($sk[0]) ?></span>
              <span class="mono text-info"><?= $sk[1] ?>%</span>
            </div>
            <div class="skill-bar"><div class="skill-fill" style="width:<?= $sk[1] ?>%;"></div></div>
          </div>
        <?php endforeach; ?>

        <div class="mt-3 pt-3 border-top border-secondary border-opacity-25">
          <div class="mono small text-secondary mb-2">// Languages &amp; Soft Skills</div>
          <div class="d-flex flex-wrap gap-2">
            <span class="tech-chip"><i class="fa-solid fa-language text-info"></i>Bengali (Native)</span>
            <span class="tech-chip"><i class="fa-solid fa-language text-warning"></i>English (Fluent)</span>
            <span class="tech-chip"><i class="fa-solid fa-language text-success"></i>Hindi</span>
            <span class="tech-chip"><i class="fa-solid fa-users text-info"></i>Project Management</span>
            <span class="tech-chip"><i class="fa-solid fa-shield-halved text-warning"></i>Confidentiality &amp; Accuracy</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="dev-card h-100">
        <h5 class="fw-bold text-white mb-3 mono"><i class="fa-solid fa-layer-group text-warning me-2"></i>Portfolio_Creations[]</h5>
        <div class="row g-2">
          <?php
          $projects = [
            ['🎂 Cake Shop BD', 'Live E-Commerce & Admin CMS Platform'],
            ['🛒 Dream Cart BD & Store', 'Multi-Category Online Shopping Ecosystem'],
            ['🎓 Dream Career IT Bangladesh', 'IT Training Institute & Career Platform'],
            ['🏢 Green City Developments', 'Real Estate & Corporate Web Presence'],
            ['📦 Wholesale Electronics & Dubai Optom', 'Wholesale Import & Product Catalog Systems'],
            ['🌾 Food City Agro', 'Agro Business & Audit Management'],
            ['🏥 Patient List & Stock Management', 'Custom Database, Excel & Access Automation'],
            ['📣 Multi-Category Social & Ads Suite', 'Digital Marketing & Social Media Campaigns'],
          ];
          foreach ($projects as $pr): ?>
            <div class="col-sm-6">
              <div class="sub-box h-100">
                <div class="fw-bold text-info small mb-1"><?= e($pr[0]) ?></div>
                <div style="color:var(--muted);font-size:12.5px;"><?= e($pr[1]) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- 4. Direct Developer Email & Contact System (#5) -->
  <div class="dev-card mb-4" id="contactDeveloper">
    <div class="terminal-bar">
      <span class="dot bg-danger"></span>
      <span class="dot bg-warning"></span>
      <span class="dot bg-success"></span>
      <span class="mono small text-secondary ms-2">~/mail/send-direct-email.php</span>
      <span class="ms-auto mono small text-success"><i class="fa-solid fa-paper-plane me-1"></i>Direct SMTP / Mail Active</span>
    </div>
    <div class="row g-4 align-items-start">
      <div class="col-lg-5">
        <h4 class="fw-bold text-white mb-2"><i class="fa-solid fa-envelope-open-text text-info me-2"></i>ডেভেলপারের সাথে সরাসরি যোগাযোগ</h4>
        <p class="small mb-4" style="color:var(--muted);line-height:1.7;">
          নতুন ওয়েবসাইট, ই-কমার্স সিস্টেম, কাস্টম সফটওয়্যার, ডাটাবেস অটোমেশন অথবা ডিজিটাল মার্কেটিং প্রজেক্টের জন্য সরাসরি ইমেইল পাঠান।
        </p>
        <div class="d-flex flex-column gap-2 small">
          <div class="sub-box d-flex align-items-center gap-3">
            <i class="fa-solid fa-envelope fs-5 text-info"></i>
            <div><div class="text-secondary">অফিশিয়াল ইমেইল</div><a href="mailto:<?= e($devEmail) ?>" class="text-white fw-bold"><?= e($devEmail) ?></a></div>
          </div>
          <div class="sub-box d-flex align-items-center gap-3">
            <i class="fa-solid fa-phone fs-5 text-success"></i>
            <div><div class="text-secondary">ফোন / হোয়াটসঅ্যাপ</div><a href="tel:<?= e($devPhone) ?>" class="text-white fw-bold"><?= e($devPhone) ?></a></div>
          </div>
          <div class="sub-box d-flex align-items-center gap-3">
            <i class="fa-solid fa-location-dot fs-5 text-warning"></i>
            <div><div class="text-secondary">ঠিকানা</div><div class="text-white fw-bold">Chapapur, Adarsha Sadar, Cumilla-3500</div></div>
          </div>
        </div>
      </div>

      <div class="col-lg-7">
        <?php if ($mailSuccess): ?>
          <div class="alert alert-success rounded-4 border-0 fw-bold"><i class="fa-solid fa-circle-check me-2"></i><?= e($mailSuccess) ?></div>
        <?php endif; ?>
        <?php if ($mailError): ?>
          <div class="alert alert-danger rounded-4 border-0 fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= e($mailError) ?></div>
        <?php endif; ?>

        <form method="post" action="#contactDeveloper" class="row g-3">
          <input type="hidden" name="dev_action" value="send_dev_email">
          <div class="col-md-6">
            <label class="form-label small fw-bold text-info">আপনার নাম *</label>
            <input type="text" name="sender_name" class="form-control dev-input" placeholder="আপনার পূর্ণ নাম" required>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold text-info">আপনার ইমেইল *</label>
            <input type="email" name="sender_email" class="form-control dev-input" placeholder="you@example.com" required>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold text-info">মোবাইল নম্বর</label>
            <input type="tel" name="sender_phone" class="form-control dev-input" placeholder="+880 1XXX-XXXXXX">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold text-info">বিষয় *</label>
            <input type="text" name="subject" class="form-control dev-input" placeholder="যেমন: ই-কমার্স ওয়েবসাইট তৈরি" required>
          </div>
          <div class="col-12">
            <label class="form-label small fw-bold text-info">বিস্তারিত বার্তা *</label>
            <textarea name="message" class="form-control dev-input" rows="4" placeholder="আপনার প্রজেক্ট বা বার্তা বিস্তারিত লিখুন..." required></textarea>
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-info btn-lg rounded-pill px-5 fw-bold text-dark">
              <i class="fa-solid fa-paper-plane me-2"></i>ইমেইল পাঠান
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="text-center mono small text-secondary pb-2">
    &lt;/&gt; Designed &amp; Engineered by <span class="text-white fw-bold">Jainal Abedin</span> &bull; Cumilla, Bangladesh
  </div>
</div>
</body>
</html>
