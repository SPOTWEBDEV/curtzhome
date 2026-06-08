// shared-nav.js — inject this into pages via insertNavbar()

function insertNavbar(activePage = '') {
  const nav = `
  <nav>
    <div style="max-width:1280px;margin:0 auto;padding:0 2rem;display:flex;align-items:center;justify-content:space-between;height:65px;">
      <a href="index.html" class="logo-text" style="color:var(--gold);font-size:1.4rem;text-decoration:none;">
        Elysian<span style="color:var(--cream);font-weight:300;">Estates</span>
      </a>

      <div style="display:flex;gap:2.5rem;align-items:center;" class="desktop-nav hidden-mobile">
        <a href="index.html" class="nav-link ${activePage==='home'?'active':''}">Home</a>
        <a href="about.html" class="nav-link ${activePage==='about'?'active':''}">About</a>
        <a href="housing.html" class="nav-link ${activePage==='housing'?'active':''}">Properties</a>
        <a href="investment.html" class="nav-link ${activePage==='investment'?'active':''}">Investment</a>
        <a href="contact.html" class="nav-link ${activePage==='contact'?'active':''}">Contact</a>
      </div>

      <div style="display:flex;gap:1rem;align-items:center;">
        <a href="login.html" class="btn-outline nav-login" style="padding:0.5rem 1.2rem;font-size:0.75rem;">Login</a>
        <a href="billing.html" class="btn-gold" style="padding:0.5rem 1.2rem;font-size:0.75rem;display:none;" class="nav-dashboard">Dashboard</a>
        <button class="btn-outline nav-logout" style="padding:0.5rem 1.2rem;font-size:0.75rem;display:none;">Logout</button>
        <button id="menuToggle" style="background:none;border:none;cursor:pointer;display:none;flex-direction:column;gap:5px;" class="show-mobile">
          <span style="width:22px;height:2px;background:var(--gold);display:block;"></span>
          <span style="width:22px;height:2px;background:var(--gold);display:block;"></span>
          <span style="width:22px;height:2px;background:var(--gold);display:block;"></span>
        </button>
      </div>
    </div>
  </nav>

  <div id="mobileMenu" class="mobile-menu">
    <a href="index.html" class="nav-link">Home</a>
    <a href="about.html" class="nav-link">About</a>
    <a href="housing.html" class="nav-link">Properties</a>
    <a href="investment.html" class="nav-link">Investment</a>
    <a href="contact.html" class="nav-link">Contact</a>
    <a href="login.html" class="nav-link">Login</a>
  </div>

  <div id="toast"></div>

  <style>
    @media (max-width: 768px) {
      .desktop-nav { display: none !important; }
      #menuToggle { display: flex !important; }
    }
  </style>
  `;
  document.body.insertAdjacentHTML('afterbegin', nav);
}
