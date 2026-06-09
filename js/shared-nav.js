function insertNavbar(activePage) {
  activePage = activePage || '';
  var html = `
<style>
  /* ── Navbar base ─────────────────────────────────────── */
  #site-nav {
    position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
    background: rgba(11,22,41,0.96);
    backdrop-filter: blur(14px);
    border-bottom: 1px solid rgba(201,168,76,0.15);
    transition: border-color 0.3s;
  }
  #site-nav .inner {
    max-width: 1280px; margin: 0 auto; padding: 0 2rem;
    height: 65px; display: flex; align-items: center; justify-content: space-between;
    gap: 1rem;
  }
  #site-nav .logo-wrap {
    font-family: 'Cormorant Garamond', serif; font-weight: 700;
    font-size: 1.45rem; letter-spacing: 0.04em;
    color: #C9A84C; text-decoration: none; white-space: nowrap; flex-shrink: 0;
  }
  #site-nav .logo-wrap span { color: #F7F3ED; font-weight: 300; }

  /* desktop links */
  #site-nav .desk-links {
    display: flex; align-items: center; gap: 2.2rem; flex: 1; justify-content: center;
  }
  #site-nav .desk-links a {
    font-size: 0.8rem; letter-spacing: 0.12em; text-transform: uppercase;
    color: #8A9BB0; text-decoration: none; transition: color 0.25s; white-space: nowrap;
  }
  #site-nav .desk-links a:hover,
  #site-nav .desk-links a.active { color: #C9A84C; }

  /* right-side action buttons */
  #site-nav .nav-actions {
    display: flex; align-items: center; gap: 0.7rem; flex-shrink: 0;
  }
  #site-nav .nav-actions a,
  #site-nav .nav-actions button {
    font-size: 0.75rem; letter-spacing: 0.1em; text-transform: uppercase;
    padding: 0.48rem 1.1rem; cursor: pointer; text-decoration: none;
    white-space: nowrap; transition: all 0.25s; font-family: 'DM Sans', sans-serif;
    font-weight: 500;
  }
  #site-nav .btn-n-outline {
    border: 1px solid #C9A84C; color: #C9A84C; background: transparent;
  }
  #site-nav .btn-n-outline:hover { background: #C9A84C; color: #0B1629; }
  #site-nav .btn-n-gold {
    background: linear-gradient(135deg,#9A7A2E,#C9A84C,#E8C97A);
    color: #0B1629; border: none;
  }
  #site-nav .btn-n-gold:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(201,168,76,0.3); }

  /* hamburger — hidden by default */
  #nav-burger {
    display: none; background: none; border: none; cursor: pointer;
    flex-direction: column; gap: 5px; padding: 4px; flex-shrink: 0;
  }
  #nav-burger span { width: 22px; height: 2px; background: #C9A84C; display: block; border-radius: 2px; transition: all 0.25s; }
  #nav-burger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
  #nav-burger.open span:nth-child(2) { opacity: 0; }
  #nav-burger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

  /* ── Mobile drawer ──────────────────────────────────── */
  #mobile-drawer {
    display: none; position: fixed; top: 65px; left: 0; right: 0; bottom: 0;
    background: rgba(6,15,30,0.98); z-index: 999;
    flex-direction: column; padding: 2rem;
    border-top: 1px solid rgba(201,168,76,0.2);
    overflow-y: auto;
    animation: drawerIn 0.22s ease;
  }
  #mobile-drawer.open { display: flex; }
  @keyframes drawerIn { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }
  #mobile-drawer a {
    font-size: 1rem; letter-spacing: 0.1em; text-transform: uppercase;
    color: #8A9BB0; text-decoration: none; padding: 1rem 0;
    border-bottom: 1px solid rgba(201,168,76,0.08); transition: color 0.2s;
    font-family: 'DM Sans', sans-serif;
  }
  #mobile-drawer a:hover, #mobile-drawer a.active { color: #C9A84C; }
  #mobile-drawer .mob-actions { margin-top: 1.5rem; display: flex; flex-direction: column; gap: 0.8rem; }
  #mobile-drawer .mob-actions a,
  #mobile-drawer .mob-actions button {
    text-align: center; padding: 0.85rem; font-size: 0.82rem;
    letter-spacing: 0.12em; text-transform: uppercase; cursor: pointer;
    text-decoration: none; font-family: 'DM Sans', sans-serif; font-weight: 500;
    transition: all 0.25s;
  }

  /* ── Responsive breakpoints ─────────────────────────── */
  @media (max-width: 900px) {
    #site-nav .desk-links { gap: 1.4rem; }
    #site-nav .desk-links a { font-size: 0.74rem; }
  }
  @media (max-width: 768px) {
    #site-nav .desk-links { display: none !important; }
    #site-nav .nav-actions .btn-n-outline,
    #site-nav .nav-actions .btn-n-gold,
    #site-nav .nav-actions .nav-logout { display: none !important; }
    #nav-burger { display: flex !important; }
  }
</style>

<nav id="site-nav">
  <div class="inner">
    <a href="index.html" class="logo-wrap">Curtz <span>Home</span></a>

    <div class="desk-links" id="desktopNav">
      <a href="index.html"      class="${activePage==='home'       ?'active':''}">Home</a>
      <a href="about.html"      class="${activePage==='about'      ?'active':''}">About</a>
      <a href="housing.html"    class="${activePage==='housing'    ?'active':''}">Properties</a>
      <a href="investment.html" class="${activePage==='investment' ?'active':''}">Investment</a>
      <a href="contact.html"    class="${activePage==='contact'    ?'active':''}">Contact</a>
    </div>

    <div class="nav-actions">
      <a href="login.html"    class="btn-n-outline nav-login">Login</a>
      <a href="dashboard.html"  class="btn-n-gold nav-dashboard" style="display:none;">Dashboard</a>
      <button                 class="btn-n-outline nav-logout" style="display:none;">Logout</button>
      <button id="nav-burger" onclick="toggleMobileMenu()" aria-label="Toggle menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</nav>

<!-- Mobile Drawer -->
<div id="mobile-drawer">
  <a href="index.html"      class="${activePage==='home'       ?'active':''}">Home</a>
  <a href="about.html"      class="${activePage==='about'      ?'active':''}">About</a>
  <a href="housing.html"    class="${activePage==='housing'    ?'active':''}">Properties</a>
  <a href="investment.html" class="${activePage==='investment' ?'active':''}">Investment</a>
  <a href="contact.html"    class="${activePage==='contact'    ?'active':''}">Contact</a>
  <div class="mob-actions">
    <a href="login.html"   style="border:1px solid #C9A84C;color:#C9A84C;" class="nav-login mob-btn">Login</a>
    <a href="dashboard.html" style="background:linear-gradient(135deg,#9A7A2E,#C9A84C,#E8C97A);color:#0B1629;" class="nav-dashboard mob-btn" style="display:none;">Dashboard</a>
    <button                style="border:1px solid #C9A84C;color:#C9A84C;background:none;" class="nav-logout mob-btn" style="display:none;">Logout</button>
  </div>
</div>

<div id="toast"></div>`;

  document.body.insertAdjacentHTML('afterbegin', html);
}

function toggleMobileMenu() {
  var drawer = document.getElementById('mobile-drawer');
  var burger = document.getElementById('nav-burger');
  var isOpen = drawer.classList.toggle('open');
  burger.classList.toggle('open', isOpen);
  document.body.style.overflow = isOpen ? 'hidden' : '';
}

// Close drawer on outside click
document.addEventListener('click', function(e) {
  var drawer = document.getElementById('mobile-drawer');
  var burger = document.getElementById('nav-burger');
  if (!drawer || !drawer.classList.contains('open')) return;
  if (!drawer.contains(e.target) && !burger.contains(e.target)) {
    drawer.classList.remove('open');
    burger.classList.remove('open');
    document.body.style.overflow = '';
  }
});