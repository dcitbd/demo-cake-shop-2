/**
 * SweetDream Cakes BD v3.2 — Ultra-Fast 0.5s Loader, Synced Favorites, Responsive Category Dropdown & Bengali Voice Alert
 */
const APP_BASE = window.APP_BASE || '';

// 1. Ultra-Fast 0.5 Second Digital Preloader (#2)
(function initDigitalLoader() {
  const loader = document.getElementById('digitalLoader');
  const pctEl = document.getElementById('loaderPercent');
  if (!loader) return;
  let pct = 50;
  const iv = setInterval(() => {
    pct = Math.min(98, pct + 18);
    if (pctEl) pctEl.textContent = pct + '%';
  }, 60);
  const finish = () => {
    clearInterval(iv);
    if (pctEl) pctEl.textContent = '100%';
    loader.classList.add('hidden');
  };
  // Strictly complete within 0.5 seconds (500ms)
  setTimeout(finish, 420);
  if (document.readyState === 'complete') setTimeout(finish, 180);
  else document.addEventListener('DOMContentLoaded', () => setTimeout(finish, 260));
})();

// 2. Soft Cake Background Animation
(function initCakeBackground() {
  const bg = document.getElementById('cakeBgCanvas');
  if (!bg || document.getElementById('csbdBgStage')) return;
  const icons = ['🎂', '🍰', '🧁', '🍓', '🍫', '✨', '🍩', '🎀'];
  const count = window.innerWidth < 768 ? 6 : 12;
  for (let i = 0; i < count; i++) {
    const el = document.createElement('div');
    el.className = 'floating-cake-item';
    el.textContent = icons[i % icons.length];
    el.style.left = (Math.random() * 94) + '%';
    el.style.fontSize = (16 + Math.random() * 16) + 'px';
    el.style.animationDuration = (16 + Math.random() * 14) + 's';
    el.style.animationDelay = (-Math.random() * 18) + 's';
    bg.appendChild(el);
  }
})();

// 3. Bulletproof Responsive Category Dropdown Toggle
function toggleCatMenu(e) {
  if (e) e.stopPropagation();
  const panel = document.getElementById('navCatPanel');
  const btn = document.getElementById('navCatBtn');
  if (!panel) return;
  const isOpen = panel.classList.toggle('open');
  if (btn) btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
}
document.addEventListener('click', (e) => {
  const wrap = document.getElementById('navCatWrap');
  const panel = document.getElementById('navCatPanel');
  if (wrap && panel && !wrap.contains(e.target)) {
    panel.classList.remove('open');
    const btn = document.getElementById('navCatBtn');
    if (btn) btn.setAttribute('aria-expanded', 'false');
  }
});

// 4. Dark / Light Theme
function toggleTheme() {
  const curr = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', curr);
  localStorage.setItem('sdc_theme', curr);
}
(function loadTheme() {
  const saved = localStorage.getItem('sdc_theme');
  if (saved) document.documentElement.setAttribute('data-theme', saved);
})();

// 5. PWA Install
let deferredInstallPrompt = null;
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register(APP_BASE + '/sw.js').catch(() => {});
}
window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  deferredInstallPrompt = e;
  document.querySelectorAll('.pwa-install-btn').forEach(btn => btn.classList.remove('d-none'));
});
async function triggerAppInstall() {
  if (deferredInstallPrompt) {
    deferredInstallPrompt.prompt();
    const res = await deferredInstallPrompt.userChoice;
    if (res.outcome === 'accepted') showToast('🎉 অ্যাপ সফলভাবে ইন্সটল হয়েছে!', 'success');
    deferredInstallPrompt = null;
  } else {
    window.location.href = APP_BASE + '/apps.php';
  }
}

// 6. Toast Helper
function showToast(msg, type = 'primary') {
  let box = document.getElementById('sdcToastBox');
  if (!box) {
    box = document.createElement('div');
    box.id = 'sdcToastBox';
    box.style.cssText = 'position:fixed;bottom:76px;right:14px;left:auto;z-index:99999;display:flex;flex-direction:column;gap:8px;max-width:calc(100vw - 28px);';
    document.body.appendChild(box);
  }
  const el = document.createElement('div');
  const bg = type === 'success' ? '#1c8557' : (type === 'danger' ? '#c53030' : '#742a44');
  el.style.cssText = `background:${bg};color:#fff;padding:11px 16px;border-radius:14px;font-weight:700;font-size:14px;box-shadow:0 10px 26px rgba(0,0,0,0.22);max-width:340px;`;
  el.innerHTML = msg;
  box.appendChild(el);
  setTimeout(() => el.remove(), 3400);
}

// 7. Image Popup Lightbox
function openImagePopup(src) {
  let lb = document.getElementById('sdcLightbox');
  if (!lb) {
    lb = document.createElement('div');
    lb.id = 'sdcLightbox';
    lb.innerHTML = '<img id="sdcLightboxImg" src="" alt="Preview"><button type="button" onclick="closeImagePopup()" style="position:fixed;top:16px;right:18px;background:#fff;color:#2f2028;border:none;width:40px;height:40px;border-radius:50%;font-size:22px;font-weight:800;cursor:pointer;box-shadow:0 4px 16px rgba(0,0,0,0.25);">&times;</button>';
    lb.addEventListener('click', (e) => { if (e.target === lb) closeImagePopup(); });
    document.body.appendChild(lb);
  }
  document.getElementById('sdcLightboxImg').src = src;
  lb.classList.add('open');
}
function closeImagePopup() {
  const lb = document.getElementById('sdcLightbox');
  if (lb) lb.classList.remove('open');
}
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') closeImagePopup();
});

// 8. Copy Text Helper
function copyTextValue(text) {
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(text).then(() => {
      showToast('📋 কপি সম্পন্ন হয়েছে!', 'success');
    }).catch(() => fallbackCopy(text));
  } else {
    fallbackCopy(text);
  }
}
function fallbackCopy(text) {
  const ta = document.createElement('textarea');
  ta.value = text;
  document.body.appendChild(ta);
  ta.select();
  document.execCommand('copy');
  ta.remove();
  showToast('📋 কপি সম্পন্ন হয়েছে!', 'success');
}

// 9. Cart, Order Now & Globally Synced Favorite Buttons
async function apiPost(action, data = {}) {
  const fd = new FormData();
  fd.append('action', action);
  Object.entries(data).forEach(([k, v]) => fd.append(k, v));
  const res = await fetch(APP_BASE + '/api/ajax.php', { method: 'POST', body: fd });
  return res.json();
}

async function addToCart(productId, qty = 1, variant = '1 পাউন্ড | ক্লাসিক') {
  try {
    const res = await apiPost('add_cart', { product_id: productId, qty, variant });
    if (res.ok) {
      document.querySelectorAll('.js-cart-count').forEach(el => el.textContent = res.cart_count);
      showToast('🛒 কেকটি কার্টে যুক্ত হয়েছে!', 'success');
    } else showToast(res.message || 'কার্টে যুক্ত করা যায়নি', 'danger');
  } catch (e) { showToast('নেটওয়ার্ক ত্রুটি!', 'danger'); }
}

async function orderNow(productId, qty = 1, variant = '1 পাউন্ড | ক্লাসিক') {
  try {
    const res = await apiPost('add_cart', { product_id: productId, qty, variant });
    if (res.ok) window.location.href = APP_BASE + '/checkout.php';
    else showToast(res.message || 'অর্ডার করা যায়নি', 'danger');
  } catch (e) { showToast('নেটওয়ার্ক ত্রুটি!', 'danger'); }
}

const favBusy = {};
async function toggleFavorite(productId, btn) {
  if (favBusy[productId]) return;
  favBusy[productId] = true;
  try {
    const res = await apiPost('toggle_fav', { product_id: productId });
    if (res.ok) {
      document.querySelectorAll('.js-fav-count').forEach(el => el.textContent = res.fav_count);
      document.querySelectorAll('[data-fav-id="' + productId + '"]').forEach(el => {
        el.classList.toggle('active', !!res.is_fav);
        const icon = el.querySelector('i');
        if (icon) {
          const hasMe = icon.classList.contains('me-1');
          icon.className = (res.is_fav ? 'fa-solid' : 'fa-regular') + ' fa-heart' + (hasMe ? ' me-1' : '');
        }
      });
      if (!res.is_fav && window.location.pathname.indexOf('favorites.php') !== -1) {
        const card = btn ? btn.closest('.col') : null;
        if (card) card.remove();
      }
      showToast(res.is_fav ? '❤️ ফেভরিটে যুক্ত হয়েছে!' : 'ফেভরিট থেকে সরানো হয়েছে', 'primary');
    }
  } catch (e) {} finally {
    setTimeout(() => { delete favBusy[productId]; }, 280);
  }
}

// 10. Live Search
(function initLiveSearch() {
  const input = document.getElementById('liveSearchInput');
  const drop = document.getElementById('liveSearchDropdown');
  if (!input || !drop) return;
  let timer;
  input.addEventListener('input', () => {
    clearTimeout(timer);
    const q = input.value.trim();
    if (q.length < 2) { drop.style.display = 'none'; return; }
    timer = setTimeout(async () => {
      try {
        const res = await fetch(APP_BASE + '/api/ajax.php?action=live_search&q=' + encodeURIComponent(q));
        const data = await res.json();
        if (!data.items || !data.items.length) {
          drop.innerHTML = '<div class="p-3 text-muted small">কোনো কেক পাওয়া যায়নি...</div>';
        } else {
          drop.innerHTML = data.items.map(it => `
            <a href="${APP_BASE}/product.php?slug=${encodeURIComponent(it.slug)}" class="d-flex align-items-center gap-2 p-2 border-bottom text-decoration-none">
              <img src="${it.image}" style="width:44px;height:44px;border-radius:8px;object-fit:cover;">
              <div class="flex-grow-1">
                <div class="fw-bold small text-truncate">${it.name}</div>
                <div class="small text-danger fw-bold">৳${it.discount_price} <span class="text-muted text-decoration-line-through fw-normal">৳${it.regular_price}</span></div>
              </div>
            </a>
          `).join('');
        }
        drop.style.display = 'block';
      } catch (e) {}
    }, 160);
  });
  document.addEventListener('click', (e) => {
    if (!input.contains(e.target) && !drop.contains(e.target)) drop.style.display = 'none';
  });
})();

function slideCategory(id, dir) {
  const el = document.getElementById(id);
  if (el) el.scrollBy({ left: dir * 260, behavior: 'smooth' });
}

// 11. New Order Voice & Chime Notification ("আপনার নতুন একটি অর্ডার এসেছে, এখনি প্রসেস করুন")
function playNewOrderVoiceAlert() {
  try {
    const AudioCtx = window.AudioContext || window.webkitAudioContext;
    if (AudioCtx) {
      const ctx = new AudioCtx();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = 'sine';
      osc.frequency.setValueAtTime(880, ctx.currentTime);
      osc.frequency.setValueAtTime(1174.66, ctx.currentTime + 0.14);
      gain.gain.setValueAtTime(0.18, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.45);
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start();
      osc.stop(ctx.currentTime + 0.45);
    }
  } catch (e) {}

  try {
    if ('speechSynthesis' in window) {
      window.speechSynthesis.cancel();
      const utter = new SpeechSynthesisUtterance('আপনার নতুন একটি অর্ডার এসেছে, এখনি প্রসেস করুন');
      utter.lang = 'bn-BD';
      utter.rate = 0.96;
      utter.pitch = 1.04;
      window.speechSynthesis.speak(utter);
    }
  } catch (e) {}
}

function enableMobileNotifications() {
  localStorage.setItem('sdc_voice_enabled', '1');
  playNewOrderVoiceAlert();
  if (!('Notification' in window)) {
    showToast('🔔 ভয়েস ও স্ক্রিন অর্ডার অ্যালার্ট চালু হয়েছে!', 'success');
    return;
  }
  Notification.requestPermission().then(perm => {
    if (perm === 'granted') {
      showToast('🔔 মোবাইল নোটিফিকেশন ও ভয়েস অ্যালার্ট চালু হয়েছে!', 'success');
      new Notification('Cake Shop BD', {
        body: 'আপনার নতুন একটি অর্ডার এসেছে, এখনি প্রসেস করুন',
        icon: APP_BASE + '/assets/images/icon-192.png'
      });
    } else {
      showToast('🔔 স্ক্রিন ও ভয়েস অ্যালার্ট চালু আছে!', 'primary');
    }
  });
}

let lastNotifId = parseInt(localStorage.getItem('sdc_last_notif_id') || '0', 10);
async function pollNotifications() {
  try {
    const res = await fetch(APP_BASE + '/api/ajax.php?action=check_notifications', { cache: 'no-store' });
    const data = await res.json();
    if (!data.ok) return;
    document.querySelectorAll('.js-notif-count').forEach(el => el.textContent = data.unread_count);
    if (data.latest && parseInt(data.latest.id, 10) > lastNotifId) {
      const isFirstLoad = lastNotifId === 0;
      lastNotifId = parseInt(data.latest.id, 10);
      localStorage.setItem('sdc_last_notif_id', String(lastNotifId));
      const shouldAlert = !isFirstLoad && (window.IS_ADMIN || localStorage.getItem('sdc_voice_enabled') === '1');
      if (shouldAlert) {
        playNewOrderVoiceAlert();
        showToast('🔔 ' + data.latest.title + '<br><small>আপনার নতুন একটি অর্ডার এসেছে, এখনি প্রসেস করুন</small>', 'success');
        if ('Notification' in window && Notification.permission === 'granted') {
          new Notification(data.latest.title, {
            body: 'আপনার নতুন একটি অর্ডার এসেছে, এখনি প্রসেস করুন — ' + data.latest.message,
            icon: APP_BASE + '/assets/images/icon-192.png'
          });
        }
      }
    }
  } catch (e) {}
}
pollNotifications();
setInterval(pollNotifications, window.IS_ADMIN ? 6000 : 12000);

// 12. Universal Digital Counter Filter & CSV Export
function filterDigitalTable(tableId, searchInputId, statusSelectId, counterId) {
  const table = document.getElementById(tableId);
  if (!table) return;
  const qEl = searchInputId ? document.getElementById(searchInputId) : null;
  const stEl = statusSelectId ? document.getElementById(statusSelectId) : null;
  const q = (qEl ? qEl.value : '').toLowerCase().trim();
  const st = (stEl ? stEl.value : '').toLowerCase().trim();
  let visible = 0;
  const rows = table.querySelectorAll('tbody tr');
  rows.forEach(tr => {
    const text = tr.textContent.toLowerCase();
    const rowSt = (tr.getAttribute('data-status') || '').toLowerCase();
    const ok = (!q || text.includes(q)) && (!st || rowSt === st || text.includes(st));
    tr.style.display = ok ? '' : 'none';
    if (ok) visible++;
  });
  const cEl = document.getElementById(counterId);
  if (cEl) cEl.textContent = visible + ' / ' + rows.length;
}

function exportTableToCSV(tableId, filename = 'export.csv') {
  const table = document.getElementById(tableId);
  if (!table) return;
  const rows = [];
  table.querySelectorAll('tr').forEach(tr => {
    if (tr.style.display === 'none') return;
    const cols = [];
    tr.querySelectorAll('th, td').forEach(td => {
      if (td.classList.contains('no-csv')) return;
      let val = td.innerText.replace(/\s+/g, ' ').trim().replace(/"/g, '""');
      cols.push('"' + val + '"');
    });
    if (cols.length) rows.push(cols.join(','));
  });
  const blob = new Blob(["\uFEFF" + rows.join("\n")], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = filename;
  link.click();
  showToast('📥 CSV ডাউনলোড হয়েছে: ' + filename, 'success');
}
