/**
 * CodeByTushu — Admin Panel JavaScript v2.0
 * Features: Theme system, sidebar, search, notifications, profile dropdown,
 *           toast stack, modal manager, AJAX helpers, chart factory, confirm dialogs.
 */
'use strict';

/* ══════════════════════════════════════════════════════════════
   THEME MANAGER
   ══════════════════════════════════════════════════════════════ */
const Theme = {
  KEY: 'cbt_admin_theme',

  get current() {
    return localStorage.getItem(this.KEY) || 'dark';
  },

  apply(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem(this.KEY, theme);
    // Update toggle button icon
    const btn = document.getElementById('themeToggle');
    if (btn) btn.innerHTML = theme === 'dark' ? SvgIcons.sun : SvgIcons.moon;
    // Dispatch for charts that need re-coloring
    document.dispatchEvent(new CustomEvent('themeChange', { detail: theme }));
  },

  toggle() {
    this.apply(this.current === 'dark' ? 'light' : 'dark');
  },

  init() {
    this.apply(this.current);
  }
};

/* ══════════════════════════════════════════════════════════════
   SVG ICONS (inline, theme-aware)
   ══════════════════════════════════════════════════════════════ */
const SvgIcons = {
  sun: `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
    <circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/>
    <line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
    <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
  </svg>`,
  moon: `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
  </svg>`,
  bell: `<svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
  </svg>`,
  search: `<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
  </svg>`,
  chevronDown: `<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5">
    <polyline points="6 9 12 15 18 9"/>
  </svg>`,
};

/* ══════════════════════════════════════════════════════════════
   SIDEBAR MANAGER
   ══════════════════════════════════════════════════════════════ */
const Sidebar = {
  KEY: 'cbt_sidebar_collapsed',

  get isCollapsed() {
    return localStorage.getItem(this.KEY) === '1';
  },

  toggle() {
    const collapsed = !this.isCollapsed;
    localStorage.setItem(this.KEY, collapsed ? '1' : '0');
    document.body.classList.toggle('sidebar-collapsed', collapsed);
  },

  // Mobile: open/close via overlay
  mobileOpen() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar?.classList.add('mobile-open');
    overlay?.classList.add('active');
    document.body.style.overflow = 'hidden';
  },

  mobileClose() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar?.classList.remove('mobile-open');
    overlay?.classList.remove('active');
    document.body.style.overflow = '';
  },

  init() {
    // Desktop collapsed state
    if (this.isCollapsed) {
      document.body.classList.add('sidebar-collapsed');
    }

    // Sidebar toggle button
    document.getElementById('sidebarToggle')?.addEventListener('click', () => {
      if (window.innerWidth < 768) this.mobileOpen();
      else this.toggle();
    });

    // Mobile overlay close
    document.getElementById('sidebarOverlay')?.addEventListener('click', () => this.mobileClose());

    // Handle resize
    window.addEventListener('resize', () => {
      if (window.innerWidth >= 768) this.mobileClose();
    });
  }
};

/* ══════════════════════════════════════════════════════════════
   DROPDOWN MANAGER
   ══════════════════════════════════════════════════════════════ */
const Dropdown = {
  openId: null,

  toggle(panelId, triggerId) {
    const panel   = document.getElementById(panelId);
    const trigger = triggerId ? document.getElementById(triggerId) : null;
    if (!panel) return;
    const isOpen = panel.classList.contains('open');
    this.closeAll();
    if (!isOpen) {
      panel.classList.add('open');
      trigger?.classList.add('open');
      this.openId = panelId;
    }
  },

  closeAll() {
    document.querySelectorAll(
      '.notif-panel, .profile-dropdown, .search-results'
    ).forEach(el => el.classList.remove('open'));
    document.querySelectorAll('.profile-btn').forEach(el => el.classList.remove('open'));
    this.openId = null;
  },

  init() {
    document.addEventListener('click', (e) => {
      const inPanel   = e.target.closest('.header-dropdown-wrap, .header-search');
      const inSidebar = e.target.closest('.sidebar');
      if (!inPanel && !inSidebar) this.closeAll();
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') this.closeAll();
    });
  }
};

/* ══════════════════════════════════════════════════════════════
   SEARCH
   ══════════════════════════════════════════════════════════════ */
const Search = {
  input: null,
  results: null,
  timer: null,

  // Searchable pages — extend as needed
  pages: [
    { label: 'Dashboard',   url: '/admin/',               icon: '📊' },
    { label: 'Users',       url: '/admin/users.php',      icon: '👥' },
    { label: 'LeetCode',    url: '/admin/leetcode.php',   icon: '💡' },
    { label: 'Blogs',       url: '/admin/blogs.php',      icon: '📝' },
    { label: 'Courses',     url: '/admin/courses.php',    icon: '🎓' },
    { label: 'Categories',  url: '/admin/categories.php', icon: '🏷️' },
    { label: 'Messages',    url: '/admin/messages.php',   icon: '✉️' },
    { label: 'Uploads',     url: '/admin/uploads.php',    icon: '📁' },
    { label: 'Analytics',   url: '/admin/analytics.php',  icon: '📈' },
    { label: 'Settings',    url: '/admin/settings.php',   icon: '⚙️' },
    { label: 'My Profile',  url: '/user/profile.php',     icon: '👤' },
    { label: 'View Site',   url: '/',                     icon: '🌐' },
  ],

  doSearch(q) {
    if (!q.trim()) { this.hide(); return; }
    const matches = this.pages.filter(p =>
      p.label.toLowerCase().includes(q.toLowerCase())
    );
    this.render(matches, q);
  },

  render(items, q) {
    if (!this.results) return;
    this.results.innerHTML = '';
    if (!items.length) {
      this.results.innerHTML = `<div style="padding:14px 16px;color:var(--text-muted);font-size:13px;">No results for "<strong>${escHtml(q)}</strong>"</div>`;
    } else {
      items.forEach(item => {
        const div = document.createElement('a');
        div.href = item.url;
        div.className = 'search-result-item';
        div.innerHTML = `
          <span class="search-result-icon">${item.icon}</span>
          <div class="search-result-text">
            <div class="search-result-title">${escHtml(item.label)}</div>
            <div class="search-result-sub">${item.url}</div>
          </div>
        `;
        this.results.appendChild(div);
      });
    }
    this.results.classList.add('open');
  },

  hide() {
    this.results?.classList.remove('open');
  },

  init() {
    this.input   = document.getElementById('headerSearch');
    this.results = document.getElementById('searchResults');
    if (!this.input) return;

    this.input.addEventListener('input', () => {
      clearTimeout(this.timer);
      this.timer = setTimeout(() => this.doSearch(this.input.value), 200);
    });

    this.input.addEventListener('focus', () => {
      if (this.input.value) this.doSearch(this.input.value);
    });

    // Keyboard navigation
    this.input.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') { this.hide(); this.input.blur(); }
      if (e.key === 'Enter') {
        const first = this.results?.querySelector('.search-result-item');
        if (first) first.click();
      }
    });

    // Global keyboard shortcut: Cmd/Ctrl+K
    document.addEventListener('keydown', (e) => {
      if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        this.input.focus();
        this.input.select();
      }
    });
  }
};

/* ══════════════════════════════════════════════════════════════
   TOAST SYSTEM
   ══════════════════════════════════════════════════════════════ */
const Toast = {
  stack: null,

  init() {
    if (!this.stack) {
      this.stack = document.createElement('div');
      this.stack.className = 'toast-stack';
      this.stack.id = 'toastStack';
      document.body.appendChild(this.stack);
    }
  },

  show(title, message = '', type = 'info', duration = 4500) {
    this.init();
    const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
    const el = document.createElement('div');
    el.className = `toast toast-${type}`;
    el.innerHTML = `
      <span class="toast-icon">${icons[type] || 'ℹ️'}</span>
      <div class="toast-body">
        <div class="toast-title">${escHtml(title)}</div>
        ${message ? `<div class="toast-msg">${escHtml(message)}</div>` : ''}
      </div>
      <button class="toast-close" aria-label="Close">✕</button>
    `;
    el.querySelector('.toast-close').onclick = () => this.remove(el);
    this.stack.appendChild(el);

    if (duration > 0) {
      setTimeout(() => this.remove(el), duration);
    }
    return el;
  },

  remove(el) {
    if (!el || !el.isConnected) return;
    el.classList.add('removing');
    setTimeout(() => el.remove(), 280);
  },

  success(title, msg) { return this.show(title, msg, 'success'); },
  error(title, msg)   { return this.show(title, msg, 'error', 6000); },
  warning(title, msg) { return this.show(title, msg, 'warning'); },
  info(title, msg)    { return this.show(title, msg, 'info'); },
};

/* ══════════════════════════════════════════════════════════════
   MODAL MANAGER
   ══════════════════════════════════════════════════════════════ */
const Modal = {
  open(id) {
    const overlay = document.getElementById(id);
    if (!overlay) return;
    overlay.classList.add('show');
    document.body.style.overflow = 'hidden';
    setTimeout(() => {
      const first = overlay.querySelector('input:not([type=hidden]), textarea, select');
      first?.focus();
    }, 150);
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) this.close(id);
    }, { once: false });
  },

  close(id) {
    const overlay = document.getElementById(id);
    if (!overlay) return;
    overlay.classList.remove('show');
    document.body.style.overflow = '';
    // Reset any forms inside
    overlay.querySelectorAll('form').forEach(f => f.reset?.());
  },

  confirm(message, onConfirm, options = {}) {
    const {
      title     = 'Confirm Action',
      confirmText = 'Confirm',
      cancelText  = 'Cancel',
      type        = 'danger',
    } = options;

    const id = 'confirmModal';
    let modal = document.getElementById(id);
    if (!modal) {
      modal = document.createElement('div');
      modal.id = id;
      modal.className = 'modal-overlay';
      modal.innerHTML = `
        <div class="modal" style="max-width:420px;">
          <div class="modal-header">
            <h2 class="modal-title" id="confirmTitle"></h2>
            <button class="modal-close" onclick="Modal.close('${id}')">✕</button>
          </div>
          <div class="modal-body">
            <p id="confirmMsg" style="font-size:14px;color:var(--text-muted);line-height:1.7;"></p>
          </div>
          <div class="modal-footer">
            <button class="btn btn-ghost" id="confirmCancel"></button>
            <button class="btn" id="confirmOk"></button>
          </div>
        </div>
      `;
      document.body.appendChild(modal);
    }
    modal.querySelector('#confirmTitle').textContent = title;
    modal.querySelector('#confirmMsg').textContent   = message;
    const okBtn = modal.querySelector('#confirmOk');
    okBtn.textContent = confirmText;
    okBtn.className   = `btn btn-${type}`;
    modal.querySelector('#confirmCancel').textContent = cancelText;

    // Reset listeners
    const newOk = okBtn.cloneNode(true);
    okBtn.parentNode.replaceChild(newOk, okBtn);
    newOk.addEventListener('click', () => {
      this.close(id);
      onConfirm();
    });

    this.open(id);
  }
};

/* ══════════════════════════════════════════════════════════════
   AJAX HELPERS
   ══════════════════════════════════════════════════════════════ */
function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

async function apiPost(url, data = {}) {
  try {
    const form = new FormData();
    form.append('csrf_token', getCsrfToken());
    Object.entries(data).forEach(([k, v]) => form.append(k, v ?? ''));
    const res = await fetch(url, { method: 'POST', body: form });
    return await res.json();
  } catch (err) {
    return { success: false, error: 'Network error. Please check your connection.' };
  }
}

async function apiGet(url) {
  try {
    const res = await fetch(url, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    return await res.json();
  } catch (err) {
    return { success: false, error: 'Network error.' };
  }
}

/* ══════════════════════════════════════════════════════════════
   CHART FACTORY
   Uses Chart.js (loaded separately on pages that need it).
   ══════════════════════════════════════════════════════════════ */
function getChartColors() {
  const theme = document.documentElement.getAttribute('data-theme') || 'dark';
  return {
    gridColor:  theme === 'dark' ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.06)',
    textColor:  theme === 'dark' ? '#8888aa' : '#666680',
  };
}

function makeLineChart(canvasId, labels, datasets, options = {}) {
  const canvas = document.getElementById(canvasId);
  if (!canvas || typeof Chart === 'undefined') return null;
  if (canvas._chart) canvas._chart.destroy();

  const { gridColor, textColor } = getChartColors();

  const chart = new Chart(canvas, {
    type: 'line',
    data: { labels, datasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { intersect: false, mode: 'index' },
      plugins: {
        legend: {
          display: datasets.length > 1,
          labels: { color: textColor, font: { size: 12 }, boxWidth: 12, boxHeight: 12 },
        },
        tooltip: {
          backgroundColor: 'var(--card-bg)',
          borderColor: 'var(--border-mid)',
          borderWidth: 1,
          titleColor: 'var(--text)',
          bodyColor: 'var(--text-muted)',
          padding: 10,
        },
      },
      scales: {
        x: {
          grid: { color: gridColor },
          ticks: { color: textColor, font: { size: 11 } },
        },
        y: {
          grid: { color: gridColor },
          ticks: { color: textColor, font: { size: 11 } },
          beginAtZero: true,
        },
      },
      ...options,
    },
  });
  canvas._chart = chart;

  // Recolor on theme change
  document.addEventListener('themeChange', () => {
    const c = getChartColors();
    chart.options.scales.x.grid.color = c.gridColor;
    chart.options.scales.x.ticks.color = c.textColor;
    chart.options.scales.y.grid.color = c.gridColor;
    chart.options.scales.y.ticks.color = c.textColor;
    chart.options.plugins.legend.labels.color = c.textColor;
    chart.update();
  });
  return chart;
}

function makeDoughnutChart(canvasId, labels, data, colors, options = {}) {
  const canvas = document.getElementById(canvasId);
  if (!canvas || typeof Chart === 'undefined') return null;
  if (canvas._chart) canvas._chart.destroy();
  const { textColor } = getChartColors();

  const chart = new Chart(canvas, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{
        data,
        backgroundColor: colors || ['#ffc400','#3b82f6','#22c55e','#ef4444','#8b5cf6'],
        borderWidth: 0,
        hoverOffset: 6,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '72%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: { color: textColor, font: { size: 12 }, padding: 16, boxWidth: 12, boxHeight: 12 },
        },
        tooltip: {
          backgroundColor: 'rgba(0,0,0,.85)',
          titleColor: '#fff',
          bodyColor: '#ccc',
          padding: 10,
        },
      },
      ...options,
    },
  });
  canvas._chart = chart;
  return chart;
}

/* ══════════════════════════════════════════════════════════════
   FORM HELPERS
   ══════════════════════════════════════════════════════════════ */
function ajaxForm(formId, {
  onSuccess = null,
  onError   = null,
  loadingText = 'Saving…',
} = {}) {
  const form = document.getElementById(formId);
  if (!form) return;
  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    const submitBtn = form.querySelector('[type=submit]');
    const origText  = submitBtn?.textContent || '';
    if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = loadingText; }
    const fd = new FormData(form);
    fd.append('csrf_token', getCsrfToken());
    try {
      const res  = await fetch(form.action || window.location.href, { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        Toast.success('Success', data.message || 'Saved.');
        if (typeof onSuccess === 'function') onSuccess(data);
      } else {
        Toast.error('Error', data.error || 'Something went wrong.');
        if (typeof onError === 'function') onError(data);
      }
    } catch {
      Toast.error('Network Error', 'Could not connect to the server.');
    }
    if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = origText; }
  });
}

/* ══════════════════════════════════════════════════════════════
   UTILITY FUNCTIONS
   ══════════════════════════════════════════════════════════════ */
function escHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
    .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function formatNumber(n) {
  return new Intl.NumberFormat().format(n);
}

function copyToClipboard(text) {
  navigator.clipboard.writeText(text).then(() => {
    Toast.success('Copied!', 'Text copied to clipboard.');
  });
}

function debounce(fn, delay = 300) {
  let timer;
  return (...args) => { clearTimeout(timer); timer = setTimeout(() => fn(...args), delay); };
}

/* Tab system */
function initTabs(containerSelector = '.tabs') {
  document.querySelectorAll(containerSelector).forEach(tabs => {
    tabs.querySelectorAll('.tab-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const target = btn.dataset.tab;
        tabs.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        // Find sibling tab contents
        const container = tabs.closest('[data-tabs-parent]') || tabs.parentElement;
        container.querySelectorAll('.tab-content').forEach(c => {
          c.classList.toggle('active', c.id === target || c.dataset.tab === target);
        });
      });
    });
  });
}

/* ══════════════════════════════════════════════════════════════
   NOTIFICATION BADGE updater
   ══════════════════════════════════════════════════════════════ */
const NotifBadge = {
  set(count) {
    const badge = document.getElementById('notifBadge');
    const dot   = document.getElementById('notifDot');
    if (badge) badge.textContent = count > 99 ? '99+' : count;
    if (dot)   dot.style.display = count > 0 ? 'block' : 'none';
  },
  clear() { this.set(0); }
};

/* ══════════════════════════════════════════════════════════════
   BOOT
   ══════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
  Theme.init();
  Sidebar.init();
  Dropdown.init();
  Search.init();
  Toast.init();
  initTabs();
});
