/* ============================================================
   Inventory Access Control + Toast Notification System
   ============================================================ */
if (!window.__INV_ACCESS_CONTROL_LOADED__) {
window.__INV_ACCESS_CONTROL_LOADED__ = true;

/* ----------------------------------------------------------
   Dark Mode Auto-Applicator
   ---------------------------------------------------------- */
(function() {
  function applyTheme(pref) {
    const serverTheme = document.documentElement.getAttribute('data-theme');
    const storedTheme = localStorage.getItem('inv_theme');
    // Prioritaskan pref eksplisit, kemudian atribut serverTheme dari database, lalu storedTheme di localStorage
    const theme = pref || (serverTheme && serverTheme.trim() !== '' ? serverTheme : null) || storedTheme || 'light';
    const isDark = theme === 'dark' || (theme === 'system' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
    document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
    if (document.body) {
      document.body.classList.toggle('dark-theme', isDark);
    }
    try {
      if (theme) localStorage.setItem('inv_theme', theme);
    } catch (e) {}
  }

  // Jalankan segera saat file diload
  applyTheme();
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => applyTheme());
  }

  // Pantau preferensi OS jika mode sistem
  if (window.matchMedia) {
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
      const current = localStorage.getItem('inv_theme');
      if (current === 'system') applyTheme('system');
    });
  }

  window.InvTheme = {
    apply: applyTheme,
    set: function(val) {
      localStorage.setItem('inv_theme', val);
      applyTheme(val);
    },
    get: function() {
      return localStorage.getItem('inv_theme') || 'light';
    }
  };
})();

/* ----------------------------------------------------------
   Toast Helper
   ---------------------------------------------------------- */
window.InvToast = window.InvToast || (() => {
  let container = null;

  function getContainer() {
    if (!container) {
      container = document.createElement('div');
      container.id = 'inv-toast-container';
      document.body.appendChild(container);
    }
    return container;
  }

  const ICONS = {
    warning: `<svg class="inv-toast-icon warning" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
              </svg>`,
    danger:  `<svg class="inv-toast-icon danger" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
              </svg>`,
    success: `<svg class="inv-toast-icon success" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
              </svg>`,
    info:    `<svg class="inv-toast-icon info" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="16" x2="12" y2="12"/>
                <line x1="12" y1="8" x2="12.01" y2="8"/>
              </svg>`
  };

  function show({ type = 'warning', title = '', message = '', duration = 4000 }) {
    const c = getContainer();
    const toast = document.createElement('div');
    toast.className = `inv-toast inv-toast-${type}`;
    toast.innerHTML = `
      ${ICONS[type] || ICONS.warning}
      <div class="inv-toast-body">
        ${title ? `<div class="inv-toast-title">${title}</div>` : ''}
        ${message ? `<div class="inv-toast-msg">${message}</div>` : ''}
      </div>
      <button class="inv-toast-close" aria-label="Tutup">&times;</button>
      <div class="inv-toast-progress" style="animation-duration:${duration}ms"></div>
    `;

    const dismiss = () => {
      toast.style.animation = 'inv-toast-out 0.25s ease forwards';
      toast.addEventListener('animationend', () => toast.remove(), { once: true });
    };

    toast.querySelector('.inv-toast-close').addEventListener('click', dismiss);
    c.appendChild(toast);
    setTimeout(dismiss, duration);
  }

  return { show };
})();

window.showToast = function(type, title, message) {
  if (window.InvToast && window.InvToast.show) {
    window.InvToast.show({ type: type || 'info', title: title || '', message: message || '' });
  }
};

/* ----------------------------------------------------------
   Session Check & Access Control
   ---------------------------------------------------------- */
(() => {
  fetch('../api/session.php', { credentials: 'same-origin' })
    .then(async response => {
      const result = await response.json();
      if (!response.ok || result.status !== 'success') {
        window.location.replace('../index.php');
        return null;
      }
      return result;
    })
    .then(result => {
      if (!result) return;

      const isAdmin = result.permissions.manage_products;
      window.currentUserSession = result;
      window.isInventoryAdmin = isAdmin;

      const userName = result.user.name.trim() || 'Pengguna';
      const initials = userName
        .split(/\s+/)
        .slice(0, 2)
        .map(part => part[0])
        .join('')
        .toUpperCase();

      document.querySelectorAll('.user-name, .header-user-name').forEach(el => {
        el.textContent = userName;
      });
      document.querySelectorAll('.user-role').forEach(el => {
        el.textContent = result.user.role;
      });
      document.querySelectorAll('.sidebar-footer .user-avatar, .header-user-avatar').forEach(el => {
        el.textContent = initials;
      });

      if (isAdmin) return;

      /* ---- Redirect jika staff membuka halaman yang dilarang langsung dari URL ---- */
      const currentPath = window.location.pathname.toLowerCase();
      if (currentPath.includes('product-form.html')) {
        window.location.replace('products.html');
        return;
      }
      if (currentPath.includes('reports.html') || currentPath.includes('settings.html')) {
        window.location.replace('dashboard.html');
        return;
      }

      /* ---- Intercept tombol "Tambah Produk" & link product-form ---- */
      const interceptRestrictedLinks = () => {
        // Tambah Produk
        document.querySelectorAll('a[href*="product-form.html"]').forEach(link => {
          if (link.dataset.intercepted) return;
          link.dataset.intercepted = 'true';
          link.addEventListener('click', e => {
            e.preventDefault();
            window.InvToast.show({
              type: 'warning',
              title: 'Akses Dibatasi',
              message: 'Anda tidak memiliki izin untuk menambah atau mengedit produk. Hubungi Admin Gudang.',
              duration: 4500,
            });
          });
        });
      };

      /* ---- Sembunyikan menu/tombol yang dilarang bagi staff ---- */
      const hideRestrictedControls = () => {
        // Menu Laporan dan Pengaturan di sidebar
        document.querySelectorAll(
          'a[href*="reports.html"], a[href*="settings.html"], ' +
          '.btn-danger, .btn-icon-danger'
        ).forEach(el => el.hidden = true);

        // Tombol Tambah di halaman Master Data (Supplier & Kategori)
        const addSupBtn = document.getElementById('btn-add-supplier');
        if (addSupBtn) addSupBtn.style.display = 'none';

        const addCatBtn = document.getElementById('btn-add-category');
        if (addCatBtn) addCatBtn.style.display = 'none';

        // Tombol Tambah Produk di katalog produk
        const addProdBtn = document.getElementById('btn-add-product');
        if (addProdBtn) addProdBtn.style.display = 'none';

        document.querySelectorAll('button').forEach(btn => {
          if (btn.closest('.prd-box') || btn.closest('.prd-overlay') || btn.closest('.stk-box') || btn.closest('.stk-overlay')) return;
          if (btn.classList.contains('js-delete-product')) return;
          if (/hapus/i.test(btn.textContent || '')) btn.hidden = true;
        });

        interceptRestrictedLinks();
      };

      hideRestrictedControls();
      new MutationObserver(hideRestrictedControls).observe(document.body, {
        childList: true,
        subtree: true,
      });
    })
    .catch(error => {
      console.error('Gagal memverifikasi akses Inventory:', error);
      window.location.replace('../index.php');
    });
})();
}
