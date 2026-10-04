
// ══════════════════════════════════════════════════════════════════════════════
// NUNIQUE — Language Switcher
// All translatable elements have data-de and data-en attributes.
// ══════════════════════════════════════════════════════════════════════════════
(function() {
    var LANG_KEY = 'nunique_lang';
    var currentLang = localStorage.getItem(LANG_KEY) || 'de';

    function applyLang(lang) {
        currentLang = lang;
        localStorage.setItem(LANG_KEY, lang);
        // Update all translatable elements
        document.querySelectorAll('[data-de]').forEach(function(el) {
            var txt = lang === 'de' ? el.getAttribute('data-de') : el.getAttribute('data-en');
            if (txt !== null) el.textContent = txt;
        });
        // Update buttons
        document.querySelectorAll('.lang-btn').forEach(function(btn) {
            btn.classList.toggle('active', btn.getAttribute('data-lang') === lang);
        });
        // Update html lang attribute and hidden form language fields
        document.documentElement.lang = lang;
        document.querySelectorAll('input[name="lang"], #langField').forEach(function(field) {
            field.value = lang;
        });
        window.NUNIQUE_CURRENT_LANG = lang;
        // Translate placeholders
        document.querySelectorAll('[data-de-ph]').forEach(function(el) {
            el.placeholder = lang === 'de' ? el.getAttribute('data-de-ph') : el.getAttribute('data-en-ph');
        });

        document.querySelectorAll('[data-de-alt]').forEach(function(el) {
            var alt = lang === 'de' ? el.getAttribute('data-de-alt') : el.getAttribute('data-en-alt');
            if (alt !== null) el.setAttribute('alt', alt);
        });

        document.querySelectorAll('[data-de-title]').forEach(function(el) {
            var title = lang === 'de' ? el.getAttribute('data-de-title') : el.getAttribute('data-en-title');
            if (title !== null) el.setAttribute('title', title);
        });

        document.querySelectorAll('[data-de-content]').forEach(function(el) {
            var content = lang === 'de' ? el.getAttribute('data-de-content') : el.getAttribute('data-en-content');
            if (content !== null) el.setAttribute('content', content);
        });

        document.querySelectorAll('[data-de-aria]').forEach(function(el) {
            var label = lang === 'de' ? el.getAttribute('data-de-aria') : el.getAttribute('data-en-aria');
            if (label !== null) el.setAttribute('aria-label', label);
        });

        document.querySelectorAll('[data-de-caption]').forEach(function(el) {
            var caption = lang === 'de' ? el.getAttribute('data-de-caption') : el.getAttribute('data-en-caption');
            if (caption !== null) el.setAttribute('data-caption', caption);
        });

        // Update TORTE ANFRAGEN buttons (data-de-attr for href text)
        document.querySelectorAll('[data-de-html]').forEach(function(el) {
            el.innerHTML = lang === 'de' ? el.getAttribute('data-de-html') : el.getAttribute('data-en-html');
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Wire up lang buttons
        var langToggle = document.getElementById('langToggle');
        var langLabelDE = document.getElementById('langLabelDE');
        var langLabelEN = document.getElementById('langLabelEN');

        function updateToggleVisual(lang) {
            if (!langToggle) return;
            if (lang === 'en') {
                langToggle.setAttribute('aria-checked', 'true');
                langToggle.classList.add('is-en');
                if (langLabelDE) langLabelDE.classList.remove('lang-label-active');
                if (langLabelEN) langLabelEN.classList.add('lang-label-active');
            } else {
                langToggle.setAttribute('aria-checked', 'false');
                langToggle.classList.remove('is-en');
                if (langLabelDE) langLabelDE.classList.add('lang-label-active');
                if (langLabelEN) langLabelEN.classList.remove('lang-label-active');
            }
        }

        if (langToggle) {
            langToggle.addEventListener('click', function() {
                var newLang = (currentLang === 'de') ? 'en' : 'de';
                applyLang(newLang);
                updateToggleVisual(newLang);
            });
        }
        // Also allow clicking the labels
        if (langLabelDE) langLabelDE.addEventListener('click', function() { applyLang('de'); updateToggleVisual('de'); });
        if (langLabelEN) langLabelEN.addEventListener('click', function() { applyLang('en'); updateToggleVisual('en'); });

        updateToggleVisual(currentLang);
        // Apply saved language immediately (anti-flicker: html[data-lang] already set)
        applyLang(currentLang);
    });
})();

// ══════════════════════════════════════════
// NUNIQUE — Shared JS
// ══════════════════════════════════════════

document.addEventListener('DOMContentLoaded', () => {

    // Give keyboard users a direct route past the repeated navigation.
    const mainTarget = document.querySelector('main, .page-hero, .order-hero');
    if (mainTarget && !mainTarget.id) mainTarget.id = 'main-content';
    if (mainTarget && !document.querySelector('.skip-link')) {
        const skipLink = document.createElement('a');
        skipLink.className = 'skip-link';
        skipLink.href = '#main-content';
        skipLink.textContent = 'Zum Inhalt springen / Skip to content';
        document.body.prepend(skipLink);
    }

    // ── CAKE GALLERY CAROUSEL (cakeshop.html) ──
    var cakeTrack = document.getElementById('cakeGalleryTrack');
    if (cakeTrack) {
        var cakeDots   = document.getElementById('cakeGalleryDots');
        var cakePrev   = document.getElementById('cakeGalleryPrev');
        var cakeNext   = document.getElementById('cakeGalleryNext');
        var cakeImgs   = cakeTrack.querySelectorAll('img');
        var cakeCur    = 0;
        var cakeTimer;

        function cakePerView() { return window.innerWidth < 600 ? 1 : 3; }

        function cakeTotalSlides() { return Math.ceil(cakeImgs.length / cakePerView()); }

        function cakeBuildDots() {
            if (!cakeDots) return;
            cakeDots.innerHTML = '';
            for (var i = 0; i < cakeTotalSlides(); i++) {
                var d = document.createElement('button');
                d.className = 'dot' + (i === 0 ? ' active' : '');
                d.setAttribute('aria-label', 'Bild ' + (i + 1));
                d.dataset.i = i;
                d.addEventListener('click', function() { cakeGoTo(+this.dataset.i); cakeResetAuto(); });
                cakeDots.appendChild(d);
            }
        }

        function cakeGoTo(idx) {
            var max = cakeTotalSlides() - 1;
            cakeCur = Math.max(0, Math.min(idx, max));
            // Each image width = track clientWidth / perView
            var perV = cakePerView();
            var imgW = (cakeTrack.offsetWidth / perV);
            cakeTrack.style.transform = 'translateX(-' + (cakeCur * perV * imgW) + 'px)';
            if (cakeDots) {
                cakeDots.querySelectorAll('.dot').forEach(function(d, i) {
                    d.classList.toggle('active', i === cakeCur);
                });
            }
        }

        function cakeAutoAdv() { cakeGoTo(cakeCur >= cakeTotalSlides() - 1 ? 0 : cakeCur + 1); }
        function cakeResetAuto() { clearInterval(cakeTimer); cakeTimer = setInterval(cakeAutoAdv, 4000); }

        if (cakePrev) cakePrev.addEventListener('click', function() { cakeGoTo(cakeCur - 1); cakeResetAuto(); });
        if (cakeNext) cakeNext.addEventListener('click', function() { cakeGoTo(cakeCur + 1); cakeResetAuto(); });

        // Lightbox on gallery image click
        cakeImgs.forEach(function(img) {
            img.addEventListener('click', function() { openLightbox(this.src, this.alt); });
        });

        cakeBuildDots();
        cakeResetAuto();

        window.addEventListener('resize', function() { cakeBuildDots(); cakeGoTo(0); });
    }


    // ── SCROLL TO TOP BUTTON ──
    const scrollBtn = document.getElementById('scrollTopBtn');
    if (scrollBtn) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) scrollBtn.classList.add('visible');
            else scrollBtn.classList.remove('visible');
        }, { passive: true });
        scrollBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // ── LIGHTBOX for gallery images ──
    const overlay = document.getElementById('lightboxOverlay');
    const lbImg   = document.getElementById('lightboxImg');
    const lbClose = document.getElementById('lightboxClose');

    function openLightbox(src, altText) {
        if (!overlay || !lbImg) return;
        lbImg.src = src;
        lbImg.alt = altText || '';
        const cap = document.getElementById('lightboxCaption');
        if (cap) cap.textContent = altText || '';
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeLightbox() {
        if (!overlay) return;
        overlay.classList.remove('open');
        document.body.style.overflow = '';
        setTimeout(() => { if (lbImg) lbImg.src = ''; }, 300);
    }

    // Gallery images (index.html)
    document.querySelectorAll('.gallery-grid img, [data-lightbox]').forEach(img => {
        img.addEventListener('click', () => openLightbox(img.src, img.alt));
    });
    if (overlay) {
        overlay.addEventListener('click', e => { if (e.target === overlay) closeLightbox(); });
    }
    if (lbClose) lbClose.addEventListener('click', closeLightbox);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });


    // ── HEADER SCROLL BEHAVIOUR ──
    const headerNav = document.getElementById('headerNav');
    const siteHeader = document.getElementById('siteHeader');
    let lastScrollY = 0;

    function handleHeaderScroll() {
        const scrollY = window.scrollY;
        if (headerNav) {
            if (scrollY > 80) {
                headerNav.classList.add('scrolled-away');
            } else {
                headerNav.classList.remove('scrolled-away');
            }
        }
        lastScrollY = scrollY;
    }

    window.addEventListener('scroll', handleHeaderScroll, { passive: true });
    handleHeaderScroll();

    // ── MOBILE NAV ──
    const toggle = document.getElementById('navToggle') || document.querySelector('.nav-toggle');
    const drawer = document.getElementById('navDrawer') || document.querySelector('.nav-drawer');
    if (toggle && drawer) {
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-controls', 'navDrawer');
        toggle.addEventListener('click', () => {
            toggle.classList.toggle('open');
            drawer.classList.toggle('open');
            toggle.setAttribute('aria-expanded', String(drawer.classList.contains('open')));
        });
        // Close on link click
        drawer.querySelectorAll('a').forEach(a => {
            a.addEventListener('click', () => {
                toggle.classList.remove('open');
                drawer.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
            });
        });
    }

    // ── ACTIVE NAV LINK ──
    const current = (window.location.pathname.replace(/\/$/, '').split('/').pop() || 'index').replace(/\.html$/, '');
    document.querySelectorAll('.nav-links a, .nav-drawer a').forEach(a => {
        const href = a.getAttribute('href');
        const target = (href || '').split('#')[0].replace(/^\.\//, '').replace(/\.html$/, '') || 'index';
        if (target === current) {
            a.classList.add('active');
        }
    });

    // ── FAQ ACCORDION ──
    document.querySelectorAll('.faq-q').forEach(btn => {
        btn.addEventListener('click', () => {
            const item = btn.closest('.faq-item');
            const answer = item.querySelector('.faq-a');
            const isOpen = item.classList.contains('open');

            // Close all
            document.querySelectorAll('.faq-item.open').forEach(open => {
                open.classList.remove('open');
                open.querySelector('.faq-a').style.maxHeight = '0';
            });

            // Open clicked (if was closed)
            if (!isOpen) {
                item.classList.add('open');
                answer.style.maxHeight = answer.scrollHeight + 'px';
            }
        });
    });

    // ── SCROLL ANIMATIONS ──
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, i) => {
            if (entry.isIntersecting) {
                // Stagger children
                setTimeout(() => {
                    entry.target.classList.add('visible');
                }, i * 80);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12 });

    document.querySelectorAll('.fade-up').forEach(el => observer.observe(el));

    // ── SMOOTH ANCHOR SCROLL ──
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', e => {
            const target = document.querySelector(a.getAttribute('href'));
            if (target) {
                e.preventDefault();
                const offset = 80;
                const top = target.getBoundingClientRect().top + window.scrollY - offset;
                window.scrollTo({ top, behavior: 'smooth' });
            }
        });
    });

    // ── CONTACT FORM ──
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type=submit]');
            btn.textContent = (window.NUNIQUE_CURRENT_LANG === 'en') ? 'Sending...' : 'Wird gesendet...';
            btn.disabled = true;

            // Submit via fetch to contact.php
            const data = new FormData(this);
            fetch('contact.php', { method: 'POST', body: data })
                .then(r => r.text())
                .then(() => {
                    contactForm.innerHTML = `
                        <div style="text-align:center;padding:2rem 0">
                            <div style="font-size:2.5rem;margin-bottom:1rem">✓</div>
                            <h3 style="font-family:'Cormorant Garamond',serif;margin-bottom:.5rem">${window.NUNIQUE_CURRENT_LANG === 'en' ? 'Message received!' : 'Nachricht erhalten!'}</h3>
                            <p style="color:#4A4A4A">${window.NUNIQUE_CURRENT_LANG === 'en' ? 'We will get back to you as soon as possible.' : 'Wir melden uns so schnell wie möglich.'}</p>
                        </div>`;
                })
                .catch(() => {
                    btn.textContent = (window.NUNIQUE_CURRENT_LANG === 'en') ? 'Send' : 'Absenden';
                    btn.disabled = false;
                    alert((window.NUNIQUE_CURRENT_LANG === 'en') ? 'Error while sending. Please try again or contact us directly.' : 'Fehler beim Senden. Bitte versuchen Sie es erneut oder kontaktieren Sie uns direkt.');
                });
        });
    }
});

    // ── COOKIE BANNER ──
    const cookieBanner = document.getElementById('cookie-banner');
    if (cookieBanner) {
        const consent = localStorage.getItem('nunique_cookie_consent');
        if (!consent) {
            cookieBanner.style.display = 'block';
        }
        document.getElementById('cookie-accept').addEventListener('click', () => {
            localStorage.setItem('nunique_cookie_consent', 'accepted');
            cookieBanner.style.display = 'none';
        });
        document.getElementById('cookie-decline').addEventListener('click', () => {
            localStorage.setItem('nunique_cookie_consent', 'declined');
            cookieBanner.style.display = 'none';
        });
        const rejectBtn = document.getElementById('cookie-reject');
        if (rejectBtn) {
            rejectBtn.addEventListener('click', () => {
                localStorage.setItem('nunique_cookie_consent', 'rejected');
                cookieBanner.style.display = 'none';
            });
        }
    }

    // ── REVIEWS CAROUSEL ──
    const track = document.getElementById('reviewsTrack');
    const prevBtn = document.getElementById('reviewPrev');
    const nextBtn = document.getElementById('reviewNext');
    const dotsContainer = document.getElementById('reviewsDots');

    if (track && prevBtn && nextBtn) {
        const cards = track.querySelectorAll('.review-card');
        const visibleCount = () => window.innerWidth < 600 ? 1 : window.innerWidth < 900 ? 2 : 3;
        let currentIndex = 0;

        // Build dots
        const totalDots = () => Math.ceil(cards.length / visibleCount());
        function buildDots() {
            if (!dotsContainer) return;
            dotsContainer.innerHTML = '';
            for (let i = 0; i < totalDots(); i++) {
                const d = document.createElement('button');
                d.className = 'dot' + (i === 0 ? ' active' : '');
                d.setAttribute('aria-label', `Bewertung ${i+1}`);
                d.addEventListener('click', () => goTo(i));
                dotsContainer.appendChild(d);
            }
        }

        function updateDots() {
            if (!dotsContainer) return;
            dotsContainer.querySelectorAll('.dot').forEach((d, i) => {
                d.classList.toggle('active', i === currentIndex);
            });
        }

        function goTo(index) {
            const max = totalDots() - 1;
            currentIndex = Math.max(0, Math.min(index, max));
            const cardWidth = cards[0].offsetWidth + 24; // 24 = gap
            track.scrollTo({ left: currentIndex * visibleCount() * cardWidth, behavior: 'smooth' });
            updateDots();
        }

        prevBtn.addEventListener('click', () => goTo(currentIndex - 1));
        nextBtn.addEventListener('click', () => goTo(currentIndex + 1));

        buildDots();
        window.addEventListener('resize', buildDots);
    }

// ── ATELIER V3: generic carousels, gallery lightbox carousel, modern FAQ ──
document.addEventListener('DOMContentLoaded', function () {
  // Generic horizontal carousels for portfolio + reviews
  document.querySelectorAll('[data-carousel]').forEach(function (carousel) {
    const track = carousel.querySelector('.carousel-track');
    const prev = carousel.querySelector('.carousel-prev');
    const next = carousel.querySelector('.carousel-next');
    if (!track) return;
    const scrollAmount = () => Math.max(280, Math.round(track.clientWidth * 0.82));
    if (prev) prev.addEventListener('click', function () { track.scrollBy({ left: -scrollAmount(), behavior: 'smooth' }); });
    if (next) next.addEventListener('click', function () { track.scrollBy({ left: scrollAmount(), behavior: 'smooth' }); });
  });

  // Lightbox carousel: works from portfolio links, gallery images, and any data-lightbox element
  const overlay = document.getElementById('lightboxOverlay');
  const lbImg = document.getElementById('lightboxImg');
  const lbCaption = document.getElementById('lightboxCaption');
  const lbClose = document.getElementById('lightboxClose');
  const lbPrev = document.getElementById('lightboxPrev');
  const lbNext = document.getElementById('lightboxNext');
  if (overlay && lbImg) {
    const triggers = Array.from(document.querySelectorAll('a.lightbox, [data-lightbox], .gallery-grid img, .portfolio-card img'));
    let current = 0;

    function itemSrc(el) {
      if (!el) return '';
      if (el.tagName === 'A') return el.getAttribute('href');
      const parent = el.closest('a.lightbox');
      return el.getAttribute('data-full') || (parent && parent.getAttribute('href')) || el.currentSrc || el.src;
    }
    function itemCaption(el) {
      const parent = el && el.closest('a.lightbox');
      return (parent && parent.getAttribute('data-caption')) || el.getAttribute('data-caption') || el.getAttribute('alt') || '';
    }
    function render(index) {
      if (!triggers.length) return;
      current = (index + triggers.length) % triggers.length;
      const el = triggers[current];
      lbImg.src = itemSrc(el);
      lbImg.alt = itemCaption(el);
      if (lbCaption) lbCaption.textContent = itemCaption(el);
    }
    function open(index) {
      render(index);
      overlay.classList.add('open');
      overlay.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }
    function close() {
      overlay.classList.remove('open');
      overlay.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }
    triggers.forEach(function (el, index) {
      el.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        open(index);
      });
    });
    if (lbPrev) lbPrev.addEventListener('click', function (e) { e.stopPropagation(); render(current - 1); });
    if (lbNext) lbNext.addEventListener('click', function (e) { e.stopPropagation(); render(current + 1); });
    if (lbClose) lbClose.addEventListener('click', function (e) { e.stopPropagation(); close(); });
    overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });
    document.addEventListener('keydown', function (e) {
      if (!overlay.classList.contains('open')) return;
      if (e.key === 'Escape') close();
      if (e.key === 'ArrowLeft') render(current - 1);
      if (e.key === 'ArrowRight') render(current + 1);
    });
  }

  // FAQ accordion: keep one item open for clarity
  document.querySelectorAll('[data-faq-accordion]').forEach(function (accordion) {
    accordion.querySelectorAll('details').forEach(function (detail) {
      detail.addEventListener('toggle', function () {
        if (!detail.open) return;
        accordion.querySelectorAll('details[open]').forEach(function (other) {
          if (other !== detail) other.removeAttribute('open');
        });
      });
    });
  });
});

// V4 cake-gallery scroll patch
document.addEventListener('DOMContentLoaded', function(){
  var track=document.getElementById('cakeGalleryTrack'); if(!track) return;
  var prev=document.getElementById('cakeGalleryPrev'), next=document.getElementById('cakeGalleryNext');
  function step(){return Math.min(track.clientWidth*.82,760)}
  if(prev) prev.addEventListener('click', function(e){e.preventDefault(); e.stopImmediatePropagation(); track.style.transform='none'; track.scrollBy({left:-step(),behavior:'smooth'});}, true);
  if(next) next.addEventListener('click', function(e){e.preventDefault(); e.stopImmediatePropagation(); track.style.transform='none'; track.scrollBy({left:step(),behavior:'smooth'});}, true);
  track.querySelectorAll('img').forEach(function(img){img.setAttribute('data-lightbox','');});
});

// V4 robot-check validation
document.addEventListener('submit', function(e){
  var form=e.target; if(!form.matches('form[data-robot-check]')) return;
  var hp=form.querySelector('input[name="website"]'); if(hp && hp.value.trim()!==''){e.preventDefault(); return false;}
  var cap=form.querySelector('[data-captcha-answer]'); if(cap && String(cap.value).trim()!==String(cap.dataset.captchaAnswer).trim()){e.preventDefault(); cap.setCustomValidity((window.NUNIQUE_CURRENT_LANG === 'en') ? 'Please solve the security question.' : 'Bitte lösen Sie die Sicherheitsfrage.'); cap.reportValidity(); setTimeout(function(){cap.setCustomValidity('')},1500); return false;}
}, true);
