// ── Admin Auth Guard ───────────────────────────────────────────────────────
function adminGuard() {
  var token = localStorage.getItem('admin_token');
  var user  = JSON.parse(localStorage.getItem('admin_user') || 'null');
  if (!token || !user || user.role !== 'admin') {
    window.location.href = 'login.html';
  }
  return { token: token, user: user };
}

function adminHeaders() {
  return {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ' + localStorage.getItem('admin_token')
  };
}

var ADMIN_API = '../api/admin/';

async function adminFetch(endpoint, method, data) {
  method = method || 'GET';
  var opts = { method: method, headers: adminHeaders() };
  if (data) opts.body = JSON.stringify(data);
  try {
    var r = await fetch(ADMIN_API + endpoint, opts);
    var j = await r.json();
    return { ok: r.ok, data: j.data, message: j.message };
  } catch(e) {
    return { ok: false, data: null, message: 'Network error' };
  }
}

function adminLogout() {
  localStorage.removeItem('admin_token');
  localStorage.removeItem('admin_user');
  window.location.href = 'login.html';
}

// ── Sidebar toggle ─────────────────────────────────────────────────────────
function toggleAdminSidebar() {
  var sidebar = document.getElementById('admin-sidebar');
  var overlay = document.getElementById('sidebar-overlay');
  var isOpen  = sidebar.classList.toggle('sb-open');
  if (overlay) overlay.classList.toggle('sb-overlay-open', isOpen);
  document.body.style.overflow = isOpen ? 'hidden' : '';
}
function closeAdminSidebar() {
  var sidebar = document.getElementById('admin-sidebar');
  var overlay = document.getElementById('sidebar-overlay');
  if (sidebar) sidebar.classList.remove('sb-open');
  if (overlay) overlay.classList.remove('sb-overlay-open');
  document.body.style.overflow = '';
}

// ── Layout Injection ───────────────────────────────────────────────────────
function injectAdminLayout(activePage) {
  var user     = JSON.parse(localStorage.getItem('admin_user') || '{}');
  var initials = (user.first_name ? user.first_name[0] : 'A') + (user.last_name ? user.last_name[0] : 'D');

  var links = [
    { id:'dashboard',   href:'dashboard.html',  icon:'M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z',                                label:'Dashboard'    },
    { id:'users',       href:'users.html',       icon:'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M12 7a4 4 0 100 8 4 4 0 000-8z',         label:'Users'        },
    { id:'investments', href:'investments.html', icon:'M22 12l-4 0-3 9-6-18-3 9-4 0',                                                   label:'Investments'  },
    { id:'purchases',   href:'purchases.html',   icon:'M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z',                                label:'Purchases'    },
    { id:'messages',    href:'messages.html',    icon:'M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z', label:'Messages'     },
    { id:'history',     href:'history.html',     icon:'M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z',                 label:'Activity Log' },
  ];

  var sidebarLinks = links.map(function(l) {
    var isActive = activePage === l.id;
    return '<a href="' + l.href + '" class="adm-sl' + (isActive ? ' adm-sl-active' : '') + '">'
      + '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="flex-shrink:0"><path d="' + l.icon + '"/></svg>'
      + '<span>' + l.label + '</span></a>';
  }).join('');

  // Build sidebar HTML
  var sidebarHTML =
    '<div id="admin-sidebar">'
    + '<div class="adm-sb-head">'
    +   '<a href="../index.html" class="adm-logo">Curtz <span>Home</span></a>'
    +   '<p class="adm-logo-sub">Admin Panel</p>'
    + '</div>'
    + '<div class="adm-sb-user">'
    +   '<div class="adm-avatar">' + initials + '</div>'
    +   '<div style="overflow:hidden;min-width:0">'
    +     '<p class="adm-uname">' + (user.first_name||'') + ' ' + (user.last_name||'') + '</p>'
    +     '<p class="adm-urole">Administrator</p>'
    +   '</div>'
    + '</div>'
    + '<nav class="adm-nav">' + sidebarLinks + '</nav>'
    + '<div class="adm-sb-foot">'
    +   '<button class="adm-logout-btn" onclick="adminLogout()">Sign Out</button>'
    + '</div>'
    + '</div>';

  // Build main area HTML
  var mainHTML =
    '<div id="admin-main">'
    + '<div id="admin-topbar">'
    +   '<div style="display:flex;align-items:center;gap:0.9rem;">'
    +     '<button id="sb-toggle" onclick="toggleAdminSidebar()" aria-label="Toggle menu">'
    +       '<span></span><span></span><span></span>'
    +     '</button>'
    +     '<div>'
    +       '<h1 id="pageTitle" style="margin:0;font-size:1.35rem;font-weight:400;color:#F7F3ED;font-family:\'Cormorant Garamond\',serif;line-height:1.2;"></h1>'
    +       '<p id="pageSubtitle" style="margin:0;color:#8A9BB0;font-size:0.78rem;font-family:\'DM Sans\',sans-serif;"></p>'
    +     '</div>'
    +   '</div>'
    +   '<a href="../index.html" style="color:#8A9BB0;font-size:0.78rem;text-decoration:none;white-space:nowrap;font-family:\'DM Sans\',sans-serif;">← View Site</a>'
    + '</div>'
    + '<div id="adminContent"></div>'
    + '</div>';

  // Overlay
  var overlayHTML = '<div id="sidebar-overlay" onclick="closeAdminSidebar()"></div>';
  var toastHTML   = '<div id="toast"></div>';

  // Inject into body
  document.body.innerHTML = toastHTML + overlayHTML
    + '<div id="admin-wrap">' + sidebarHTML + mainHTML + '</div>';
}

// ── Utilities ──────────────────────────────────────────────────────────────
function setLoading(btn, on, orig) {
  if (on) { btn.disabled = true;  btn.innerHTML = '<span class="a-spinner"></span>'; }
  else    { btn.disabled = false; btn.innerHTML = orig; }
}
function showToast(msg, type) {
  type = type || 'success';
  var t = document.getElementById('toast');
  if (!t) return;
  t.textContent = msg;
  t.className = 'show ' + type;
  setTimeout(function(){ t.className = ''; }, 3500);
}
function formatCurrency(n) {
  return '₦' + parseFloat(n || 0).toLocaleString(undefined, { maximumFractionDigits: 0 });
}
function formatDate(d) {
  return d ? new Date(d).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' }) : '—';
}
function badge(status) {
  var map = { active:'success', completed:'success', available:'success', pending:'pending', processing:'pending', reserved:'pending', cancelled:'danger', suspended:'danger', 'new':'pending', read:'', replied:'success' };
  var cls = map[status] || '';
  return '<span class="badge' + (cls ? ' badge-' + cls : '') + '">' + (status || '—') + '</span>';
}
function spinner(msg) {
  return '<div style="padding:4rem;text-align:center;color:#8A9BB0;">' + (msg||'Loading…') + '</div>';
}
function emptyState(msg) {
  return '<div style="padding:4rem;text-align:center;color:#8A9BB0;font-size:0.9rem;">' + msg + '</div>';
}