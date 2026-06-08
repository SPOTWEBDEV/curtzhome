// ============================================================
// GLOBAL UTILITIES
// ============================================================

const API_BASE = 'api/'; // Adjust to your PHP API path

// Toast notification
function showToast(message, type = 'success') {
  const toast = document.getElementById('toast');
  if (!toast) return;
  toast.textContent = message;
  toast.className = `show ${type}`;
  setTimeout(() => toast.className = '', 3500);
}

// AJAX helper
async function apiRequest(endpoint, method = 'GET', data = null) {
  const options = {
    method,
    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
  };
  if (data) options.body = JSON.stringify(data);
  try {
    const res = await fetch(API_BASE + endpoint, options);
    const json = await res.json();
    return { ok: res.ok, data: json, status: res.status };
  } catch (err) {
    return { ok: false, data: { message: 'Network error. Please try again.' }, status: 0 };
  }
}

// Set button loading state
function setLoading(btn, loading, originalText) {
  if (loading) {
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner"></span>`;
  } else {
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
}

// Format currency
function formatCurrency(amount, currency = 'NGN') {
  return new Intl.NumberFormat('en-NG', { style: 'currency', currency, maximumFractionDigits: 0 }).format(amount);
}

// Format date
function formatDate(dateStr) {
  return new Date(dateStr).toLocaleDateString('en-NG', { year: 'numeric', month: 'short', day: 'numeric' });
}

// Auth helpers
const Auth = {
  getToken: () => localStorage.getItem('est_token'),
  getUser: () => JSON.parse(localStorage.getItem('est_user') || 'null'),
  setSession: (token, user) => {
    localStorage.setItem('est_token', token);
    localStorage.setItem('est_user', JSON.stringify(user));
  },
  clear: () => {
    localStorage.removeItem('est_token');
    localStorage.removeItem('est_user');
  },
  isLoggedIn: () => !!localStorage.getItem('est_token'),
  isAdmin: () => {
    const user = JSON.parse(localStorage.getItem('est_user') || 'null');
    return user && user.role === 'admin';
  }
};

// Mobile menu toggle
function initMobileMenu() {
  const toggle = document.getElementById('menuToggle');
  const menu = document.getElementById('mobileMenu');
  if (toggle && menu) {
    toggle.addEventListener('click', () => menu.classList.toggle('open'));
  }
}

// Scroll-based navbar opacity
function initNavScroll() {
  const nav = document.querySelector('nav');
  if (!nav) return;
  window.addEventListener('scroll', () => {
    if (window.scrollY > 50) nav.style.borderBottomColor = 'rgba(201,168,76,0.25)';
    else nav.style.borderBottomColor = 'rgba(201,168,76,0.15)';
  });
}

// Counter animation
function animateCounter(el, target, duration = 2000) {
  let start = 0;
  const step = target / (duration / 16);
  const timer = setInterval(() => {
    start += step;
    if (start >= target) { start = target; clearInterval(timer); }
    el.textContent = Math.floor(start).toLocaleString();
  }, 16);
}

// Intersection observer for counters
function initCounters() {
  const counters = document.querySelectorAll('[data-count]');
  if (!counters.length) return;
  const obs = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        animateCounter(e.target, parseInt(e.target.dataset.count));
        obs.unobserve(e.target);
      }
    });
  }, { threshold: 0.5 });
  counters.forEach(c => obs.observe(c));
}

// Update nav based on auth state
function updateNavAuth() {
  const loginLinks = document.querySelectorAll('.nav-login');
  const dashLinks = document.querySelectorAll('.nav-dashboard');
  const logoutBtns = document.querySelectorAll('.nav-logout');

  if (Auth.isLoggedIn()) {
    loginLinks.forEach(el => el.style.display = 'none');
    dashLinks.forEach(el => el.style.display = 'inline-flex');
    logoutBtns.forEach(el => el.style.display = 'inline-flex');
  } else {
    loginLinks.forEach(el => el.style.display = 'inline-flex');
    dashLinks.forEach(el => el.style.display = 'none');
    logoutBtns.forEach(el => el.style.display = 'none');
  }

  logoutBtns.forEach(btn => btn.addEventListener('click', () => {
    Auth.clear();
    showToast('Logged out successfully');
    setTimeout(() => window.location.href = 'index.html', 1000);
  }));
}

// Init all on DOM ready
document.addEventListener('DOMContentLoaded', () => {
  initMobileMenu();
  initNavScroll();
  initCounters();
  updateNavAuth();
});
