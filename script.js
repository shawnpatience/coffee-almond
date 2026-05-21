/* ════════════════════════════════════════════
   Coffee & Almond — script.js
════════════════════════════════════════════ */

(() => {

  /* ── Navbar scroll ─────────────────────── */
  const navbar = document.getElementById('navbar');
  function updateNav() {
    navbar.classList.toggle('scrolled', window.scrollY > 40);
  }
  window.addEventListener('scroll', updateNav, { passive: true });
  updateNav();

  /* ── Hamburger menu ─────────────────────── */
  const hamburger = document.getElementById('nav-hamburger');
  const navLinks  = document.getElementById('nav-links');
  if (hamburger && navLinks) {
    hamburger.addEventListener('click', () => {
      const open = navLinks.classList.toggle('open');
      hamburger.classList.toggle('open', open);
      document.body.style.overflow = open ? 'hidden' : '';
    });
    navLinks.querySelectorAll('a').forEach(a => {
      a.addEventListener('click', () => {
        navLinks.classList.remove('open');
        hamburger.classList.remove('open');
        document.body.style.overflow = '';
      });
    });
  }


  /* ── Scroll reveal (.fade-up) ──────────── */
  const revealObs = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.add('visible');
        revealObs.unobserve(e.target);
      }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

  function observeFadeUps(root) {
    (root || document).querySelectorAll('.fade-up:not(.visible)').forEach(el => revealObs.observe(el));
  }
  observeFadeUps();


  /* ── Smooth scroll ─────────────────────── */
  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
      const target = document.querySelector(a.getAttribute('href'));
      if (!target) return;
      e.preventDefault();
      const offset = navbar.offsetHeight + 16;
      window.scrollTo({
        top: target.getBoundingClientRect().top + window.scrollY - offset,
        behavior: 'smooth'
      });
    });
  });


  /* ── Benefit tabs ──────────────────────── */
  window.switchTab = function(name) {
    /* hide all panels */
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    /* hide temp grids */
    const guashaTemp  = document.getElementById('tab-guasha-temp');
    const eyemaskTemp = document.getElementById('tab-eyemask-temp');
    if (guashaTemp)  guashaTemp.style.display  = 'none';
    if (eyemaskTemp) eyemaskTemp.style.display = 'none';

    /* show target panel */
    const panel = document.getElementById('tab-' + name);
    if (panel) panel.classList.add('active');

    /* show matching temp grid */
    if (name === 'guasha'  && guashaTemp)  guashaTemp.style.display  = '';
    if (name === 'eyemask' && eyemaskTemp) eyemaskTemp.style.display = '';

    /* update active button */
    document.querySelectorAll('.tab-btn').forEach(btn => {
      btn.classList.toggle('active', btn.getAttribute('onclick') === `switchTab('${name}')`);
    });

    /* observe any newly visible fade-ups */
    if (panel) observeFadeUps(panel);
  };

  /* initialise guasha & eyemask temp grids as hidden (already hidden via inline style,
     but set display:none explicitly so switchTab show/hide works correctly) */
  const gt = document.getElementById('tab-guasha-temp');
  const et = document.getElementById('tab-eyemask-temp');
  if (gt) gt.style.display = 'none';
  if (et) et.style.display = 'none';

})();
