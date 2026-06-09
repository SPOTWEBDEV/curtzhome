// ── Global config ──────────────────────────────────────────────────────────
var API_BASE = 'api/';

// ── Toast ──────────────────────────────────────────────────────────────────
function showToast(message, type) {
  type = type || 'success';
  var toast = document.getElementById('toast');
  if (!toast) return;
  toast.textContent = message;
  toast.className = 'show ' + type;
  setTimeout(function(){ toast.className = ''; }, 3500);
}

// ── AJAX helper ────────────────────────────────────────────────────────────
async function apiRequest(endpoint, method, data) {
  method = method || 'GET';
  var options = {
    method: method,
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    }
  };
  var token = Auth.getToken();
  if (token) options.headers['Authorization'] = 'Bearer ' + token;
  if (data) options.body = JSON.stringify(data);
  try {
    var res = await fetch(API_BASE + endpoint, options);
    var json = await res.json();
    return { ok: res.ok, data: json.data || json, status: res.status, message: json.message };
  } catch(err) {
    return { ok: false, data: { message: 'Network error. Please try again.' }, status: 0 };
  }
}

// ── Button loading ─────────────────────────────────────────────────────────
function setLoading(btn, loading, originalText) {
  if (loading) {
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span>';
  } else {
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
}

// ── Format helpers ─────────────────────────────────────────────────────────
function formatCurrency(amount, currency) {
  currency = currency || 'NGN';
  return new Intl.NumberFormat('en-NG', { style:'currency', currency:currency, maximumFractionDigits:0 }).format(amount);
}
function formatDate(dateStr) {
  return new Date(dateStr).toLocaleDateString('en-GB', { year:'numeric', month:'short', day:'numeric' });
}

// ── Auth helpers ───────────────────────────────────────────────────────────
var Auth = {
  getToken: function(){ return localStorage.getItem('est_token'); },
  getUser:  function(){ return JSON.parse(localStorage.getItem('est_user') || 'null'); },
  setSession: function(token, user) {
    localStorage.setItem('est_token', token);
    localStorage.setItem('est_user', JSON.stringify(user));
  },
  clear: function(){
    localStorage.removeItem('est_token');
    localStorage.removeItem('est_user');
  },
  isLoggedIn: function(){ return !!localStorage.getItem('est_token'); },
  isAdmin: function(){
    var u = JSON.parse(localStorage.getItem('est_user') || 'null');
    return u && u.role === 'admin';
  }
};

// ── Update nav based on auth state ─────────────────────────────────────────
function updateNavAuth() {
  // Works for both desktop and mobile elements
  var loginEls    = document.querySelectorAll('.nav-login');
  var dashEls     = document.querySelectorAll('.nav-dashboard');
  var logoutEls   = document.querySelectorAll('.nav-logout');

  if (Auth.isLoggedIn()) {
    loginEls.forEach(function(el){ el.style.display = 'none'; });
    dashEls.forEach(function(el){ el.style.display = 'inline-block'; });
    logoutEls.forEach(function(el){ el.style.display = 'inline-block'; });
  } else {
    loginEls.forEach(function(el){ el.style.display = 'inline-block'; });
    dashEls.forEach(function(el){ el.style.display = 'none'; });
    logoutEls.forEach(function(el){ el.style.display = 'none'; });
  }

  logoutEls.forEach(function(btn){
    btn.addEventListener('click', function(){
      Auth.clear();
      showToast('Logged out successfully');
      setTimeout(function(){ window.location.href = 'index.html'; }, 1000);
    });
  });
}

// ── Counter animation ──────────────────────────────────────────────────────
function animateCounter(el, target, duration) {
  duration = duration || 2000;
  var start = 0;
  var step  = target / (duration / 16);
  var timer = setInterval(function(){
    start += step;
    if (start >= target) { start = target; clearInterval(timer); }
    el.textContent = Math.floor(start).toLocaleString();
  }, 16);
}

function initCounters() {
  var counters = document.querySelectorAll('[data-count]');
  if (!counters.length) return;
  var obs = new IntersectionObserver(function(entries){
    entries.forEach(function(e){
      if (e.isIntersecting) {
        animateCounter(e.target, parseInt(e.target.dataset.count));
        obs.unobserve(e.target);
      }
    });
  }, { threshold: 0.5 });
  counters.forEach(function(c){ obs.observe(c); });
}

// ── Scroll effect on nav ───────────────────────────────────────────────────
function initNavScroll() {
  var nav = document.getElementById('site-nav');
  if (!nav) return;
  window.addEventListener('scroll', function(){
    if (window.scrollY > 50) {
      nav.style.borderBottomColor = 'rgba(201,168,76,0.28)';
    } else {
      nav.style.borderBottomColor = 'rgba(201,168,76,0.15)';
    }
  });
}

// ── Init ───────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function(){
  updateNavAuth();
  initCounters();
  initNavScroll();
});