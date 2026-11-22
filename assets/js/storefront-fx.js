/**
 * Cake Shop BD — Storefront Interactive Animations & Patisserie Atmosphere Engine
 * Loaded ONLY on customer-view pages (never touches Admin Panel or Customer Dashboard)
 */
(function initCakeShopStorefrontFX() {
  if (!document.body.classList.contains('csbd-storefront')) return;

  // 1. Multi-layer Golden Sparkles, Cocoa Bokeh & Rings Background Animation
  const bgStage = document.getElementById('csbdBgStage');
  if (bgStage) {
    const isMobile = window.innerWidth < 768;
    const count = isMobile ? 14 : 26;
    const palette = [
      'rgba(224, 159, 62, 0.34)',
      'rgba(200, 29, 37, 0.22)',
      'rgba(92, 42, 22, 0.18)',
      'rgba(246, 202, 107, 0.36)'
    ];
    for (let i = 0; i < count; i++) {
      const p = document.createElement('span');
      const kind = i % 5 === 0 ? 'is-ring' : (i % 4 === 0 ? 'is-diamond' : '');
      p.className = 'csbd-sparkle-particle ' + kind;
      const sz = kind === 'is-ring' ? (14 + Math.random() * 18) : (6 + Math.random() * 11);
      p.style.width = sz + 'px';
      p.style.height = sz + 'px';
      p.style.left = (Math.random() * 98) + '%';
      p.style.background = palette[i % palette.length];
      p.style.animationDuration = (14 + Math.random() * 18) + 's';
      p.style.animationDelay = (-Math.random() * 20) + 's';
      bgStage.appendChild(p);
    }

    // Subtle desktop parallax on aurora orbs
    if (!isMobile) {
      const orbs = bgStage.querySelectorAll('.csbd-aurora-orb');
      let rafId = null;
      window.addEventListener('mousemove', (e) => {
        if (rafId) return;
        rafId = requestAnimationFrame(() => {
          const nx = (e.clientX / window.innerWidth - 0.5) * 24;
          const ny = (e.clientY / window.innerHeight - 0.5) * 24;
          orbs.forEach((orb, idx) => {
            const factor = (idx + 1) * 0.45;
            orb.style.marginLeft = (nx * factor) + 'px';
            orb.style.marginTop = (ny * factor) + 'px';
          });
          rafId = null;
        });
      }, { passive: true });
    }
  }

  // 2. Scroll Progress Ribbon, Sticky Navbar Shadow & Back-to-Top Button
  const progressBar = document.getElementById('csbdScrollProgress');
  const navbar = document.querySelector('.cake-navbar');
  const backTopBtn = document.getElementById('csbdBackToTop');

  function onScrollUpdate() {
    const scrollTop = window.scrollY || document.documentElement.scrollTop;
    const docHeight = document.documentElement.scrollHeight - window.innerHeight;
    if (progressBar && docHeight > 0) {
      const pct = Math.min(100, Math.max(0, (scrollTop / docHeight) * 100));
      progressBar.style.width = pct + '%';
    }
    if (navbar) {
      navbar.classList.toggle('is-scrolled', scrollTop > 24);
    }
    if (backTopBtn) {
      backTopBtn.classList.toggle('visible', scrollTop > 360);
    }
  }
  window.addEventListener('scroll', onScrollUpdate, { passive: true });
  onScrollUpdate();

  if (backTopBtn) {
    backTopBtn.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  // 3. Smooth Scroll-Reveal Entrance (IntersectionObserver with safe fallback)
  const revealTargets = document.querySelectorAll('section, .benefit-3d-card, .brand-pill-card, .cake-card');
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.06, rootMargin: '0px 0px -24px 0px' });

    revealTargets.forEach((el, idx) => {
      if (el.closest('#heroBannerCarousel')) return;
      el.classList.add('csbd-reveal');
      el.style.transitionDelay = ((idx % 4) * 55) + 'ms';
      io.observe(el);
    });

    // Safety guarantee: ensure everything becomes visible within 1.4s even if observer didn't trigger
    setTimeout(() => {
      document.querySelectorAll('.csbd-reveal:not(.is-visible)').forEach(el => el.classList.add('is-visible'));
    }, 1400);
  }

  // 4. Micro-Bounce Pop on Cart & Favorite Counter Updates
  document.addEventListener('click', (e) => {
    if (e.target.closest('.btn-add-cart, .btn-fav-corner')) {
      document.querySelectorAll('.js-cart-count, .js-fav-count').forEach((badge) => {
        badge.classList.remove('csbd-pop-anim');
        void badge.offsetWidth;
        badge.classList.add('csbd-pop-anim');
      });
    }
  });
})();
