/**
 * purchasing-common.js
 * -----------------------------------------------------------------------
 * Shared configuration, formatting helpers, UI utilities (toast, confirm
 * modal), and a lightweight demo auth/role layer used across every page
 * of the Purchasing module.
 *
 * Everything is namespaced under `window.Purchasing` so this module can be
 * dropped into a larger ERP shell later without polluting the global scope
 * or colliding with other modules (Sales, Inventory, Finance, ...).
 * -----------------------------------------------------------------------
 */
(function (global) {
  'use strict';

  /* ======================================================================
   * 1. CONFIG
   * ----------------------------------------------------------------------
   * Single source of truth for values that used to be scattered/hard-coded
   * (e.g. tax rate). When this module is wired to a real backend, most of
   * these values should instead come from a `/api/settings` endpoint -
   * they are kept here as sane defaults / fallbacks for the mock layer.
   * ==================================================================== */
  const CONFIG = {
    // NOTE: Indonesian PPN policy has changed more than once in the last
    // few years (11% general rate vs. 12% nominal rate applied to an
    // adjusted tax base for non-luxury goods, 12% flat for luxury goods).
    // Keep this as ONE configurable constant instead of hard-coding "11%"
    // in every formula, so finance/ops can update it in one place - and
    // keep it in sync with api/config.php's 'tax_rate', which is what
    // actually gets persisted server-side.
    TAX_RATE: 0.11, // 11% - verify against api/config.php before go-live
    CURRENCY: 'IDR',
    LOCALE: 'id-ID',
    APP_NAME: 'Purchasing',
    PAGE_SIZE: 10,
  };

  /* ======================================================================
   * 2. AUTH (real session, server-rendered)
   * ----------------------------------------------------------------------
   * There is no more client-side "demo user" - every protected page is
   * rendered by PHP only after auth/guard.php confirms a valid session,
   * and that same guard prints the logged-in user's name/role and a CSRF
   * token into <meta> tags (see partials/header.php). This just reads
   * those back out and wires up the Logout button; it is NOT how access
   * is enforced (the backend re-checks the session and role on every API
   * call regardless of what this file does).
   * ==================================================================== */
  function metaContent(name) {
    const el = document.querySelector(`meta[name="${name}"]`);
    return el ? el.getAttribute('content') : '';
  }

  const Auth = {
    getCurrentUser() {
      return {
        id: parseInt(metaContent('pur-user-id'), 10) || null,
        name: metaContent('pur-user-name'),
        email: metaContent('pur-user-email'),
        role: metaContent('pur-user-role'),
      };
    },

    getCsrfToken() {
      return metaContent('pur-csrf-token');
    },

    logout() {
      window.location.href = '../logout.php';
    },
  };

  /* ======================================================================
   * 3. FORMATTING HELPERS
   * ==================================================================== */
  const Fmt = {
    /** Format a number as Indonesian Rupiah, e.g. 1500000 -> "Rp 1.500.000" */
    currency(value) {
      const n = Number(value) || 0;
      return (
        'Rp ' +
        n.toLocaleString(CONFIG.LOCALE, {
          minimumFractionDigits: 0,
          maximumFractionDigits: 0,
        })
      );
    },

    /**
     * Parse a formatted/raw string back into a plain number (never trust
     * input formatting). Follows Indonesian number formatting convention:
     * '.' is a THOUSANDS separator and ',' is the DECIMAL separator (the
     * opposite of en-US) - so "Rp 1.500.000" must parse to 1500000, not
     * 1.5. Naively stripping non-digit characters gets this wrong.
     */
    number(value) {
      if (typeof value === 'number') return value;
      if (!value) return 0;
      let s = String(value).trim().replace(/[^0-9.,-]/g, '');
      if (s.indexOf(',') !== -1) {
        // Has a decimal comma: dots before it are thousands separators.
        s = s.replace(/\./g, '').replace(',', '.');
      } else {
        // No decimal comma: every '.' present is a thousands separator.
        s = s.replace(/\./g, '');
      }
      const n = parseFloat(s);
      return isNaN(n) ? 0 : n;
    },

    date(value, withTime) {
      if (!value) return '-';
      const d = new Date(value);
      if (isNaN(d.getTime())) return value;
      const opts = { day: '2-digit', month: 'short', year: 'numeric' };
      if (withTime) {
        opts.hour = '2-digit';
        opts.minute = '2-digit';
      }
      return d.toLocaleDateString(CONFIG.LOCALE, opts);
    },

    dateInput(value) {
      // yyyy-mm-dd for <input type="date">
      const d = value ? new Date(value) : new Date();
      if (isNaN(d.getTime())) return '';
      const pad = (n) => String(n).padStart(2, '0');
      return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    },

    statusBadge(status) {
      const map = {
        Pending: 'text-bg-warning',
        Completed: 'text-bg-success',
        Cancelled: 'text-bg-danger',
      };
      const cls = map[status] || 'text-bg-secondary';
      return `<span class="badge ${cls}">${Util.escapeHtml(status)}</span>`;
    },
  };

  /* ======================================================================
   * 4. GENERAL UTILITIES
   * ==================================================================== */
  const Util = {
    escapeHtml(str) {
      if (str === null || str === undefined) return '';
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    },

    uid(prefix) {
      return (
        (prefix || 'id') +
        '_' +
        Date.now().toString(36) +
        Math.random().toString(36).slice(2, 8)
      );
    },

    qs(name) {
      return new URLSearchParams(window.location.search).get(name);
    },

    debounce(fn, wait) {
      let t;
      return function (...args) {
        clearTimeout(t);
        t = setTimeout(() => fn.apply(this, args), wait || 250);
      };
    },

    sleep(ms) {
      return new Promise((resolve) => setTimeout(resolve, ms));
    },
  };

  /* ======================================================================
   * 5. TOAST NOTIFICATIONS
   * ----------------------------------------------------------------------
   * Lightweight Bootstrap 5 toast helper. Expects a <div id="pur-toast-
   * container"> to exist on the page (added by the shared layout partial).
   * ==================================================================== */
  const Toast = {
    _ensureContainer() {
      let el = document.getElementById('pur-toast-container');
      if (!el) {
        el = document.createElement('div');
        el.id = 'pur-toast-container';
        el.className = 'toast-container position-fixed top-0 end-0 p-3';
        el.style.zIndex = 1200;
        document.body.appendChild(el);
      }
      return el;
    },

    show(message, type) {
      type = type || 'success'; // success | danger | warning | info
      const icons = {
        success: 'bi-check-circle-fill',
        danger: 'bi-x-circle-fill',
        warning: 'bi-exclamation-triangle-fill',
        info: 'bi-info-circle-fill',
      };
      const container = this._ensureContainer();
      const toastEl = document.createElement('div');
      toastEl.className = `toast align-items-center text-bg-${type} border-0`;
      toastEl.setAttribute('role', 'alert');
      toastEl.setAttribute('aria-live', 'assertive');
      toastEl.setAttribute('aria-atomic', 'true');
      toastEl.innerHTML = `
        <div class="d-flex">
          <div class="toast-body">
            <i class="bi ${icons[type] || icons.info} me-2" aria-hidden="true"></i>${Util.escapeHtml(message)}
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>`;
      container.appendChild(toastEl);
      const bsToast = new bootstrap.Toast(toastEl, { delay: 4000 });
      toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
      bsToast.show();
    },

    success(msg) {
      this.show(msg, 'success');
    },
    error(msg) {
      this.show(msg, 'danger');
    },
    warning(msg) {
      this.show(msg, 'warning');
    },
    info(msg) {
      this.show(msg, 'info');
    },
  };

  /* ======================================================================
   * 6. CONFIRM MODAL
   * ----------------------------------------------------------------------
   * Promise-based confirmation dialog reusing a single Bootstrap modal
   * injected once into the page. Used before Complete / Cancel actions.
   * ==================================================================== */
  const Confirm = {
    _modalEl: null,

    _ensureModal() {
      if (this._modalEl) return this._modalEl;
      const wrapper = document.createElement('div');
      wrapper.innerHTML = `
        <div class="modal fade" id="pur-confirm-modal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="pur-confirm-title">Konfirmasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body" id="pur-confirm-body">Apakah Anda yakin?</div>
              <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" id="pur-confirm-cancel-btn" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="pur-confirm-ok-btn">Ya, Lanjutkan</button>
              </div>
            </div>
          </div>
        </div>`;
      document.body.appendChild(wrapper.firstElementChild);
      this._modalEl = document.getElementById('pur-confirm-modal');
      return this._modalEl;
    },

    /**
     * @param {Object} opts {title, message, confirmText, confirmClass}
     * @returns {Promise<boolean>}
     */
    ask(opts) {
      opts = opts || {};
      const modalEl = this._ensureModal();
      document.getElementById('pur-confirm-title').textContent = opts.title || 'Konfirmasi';
      document.getElementById('pur-confirm-body').innerHTML =
        opts.message || 'Apakah Anda yakin ingin melanjutkan?';
      const okBtn = document.getElementById('pur-confirm-ok-btn');
      okBtn.textContent = opts.confirmText || 'Ya, Lanjutkan';
      okBtn.className = 'btn ' + (opts.confirmClass || 'btn-primary');

      const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);

      return new Promise((resolve) => {
        const cleanup = () => {
          okBtn.removeEventListener('click', onOk);
          modalEl.removeEventListener('hidden.bs.modal', onDismiss);
        };
        const onOk = () => {
          cleanup();
          bsModal.hide();
          resolve(true);
        };
        const onDismiss = () => {
          cleanup();
          resolve(false);
        };
        okBtn.addEventListener('click', onOk);
        modalEl.addEventListener('hidden.bs.modal', onDismiss);
        bsModal.show();
      });
    },
  };

  /* ======================================================================
   * 7. LAYOUT HELPERS (sidebar active state, navbar user info, role switch)
   * ==================================================================== */
  const Layout = {
    init(activeKey) {
      this._highlightSidebar(activeKey);
      this._renderUserMenu();
    },

    _highlightSidebar(activeKey) {
      document.querySelectorAll('[data-nav-key]').forEach((el) => {
        if (el.getAttribute('data-nav-key') === activeKey) {
          el.classList.add('active');
          const parentTreeview = el.closest('.nav-treeview');
          if (parentTreeview) {
            parentTreeview.classList.add('d-block');
            const parentLink = parentTreeview.previousElementSibling;
            if (parentLink) parentLink.classList.add('active');
          }
        }
      });
    },

    _renderUserMenu() {
      const logoutBtn = document.getElementById('pur-logout-btn');
      if (logoutBtn) {
        logoutBtn.addEventListener('click', async (e) => {
          e.preventDefault();
          const ok = await Confirm.ask({
            title: 'Logout',
            message: 'Anda yakin ingin keluar dari sesi ini?',
            confirmText: 'Ya, Logout',
            confirmClass: 'btn-danger',
          });
          if (!ok) return;
          await Auth.logout();
        });
      }
    },

    setPageLoading(isLoading, targetEl) {
      const el = targetEl || document.getElementById('pur-page-content');
      if (!el) return;
      if (isLoading) {
        el.setAttribute('aria-busy', 'true');
      } else {
        el.removeAttribute('aria-busy');
      }
    },
  };

  /* ======================================================================
   * EXPORT
   * ==================================================================== */
  global.Purchasing = global.Purchasing || {};
  global.Purchasing.CONFIG = CONFIG;
  global.Purchasing.Auth = Auth;
  global.Purchasing.Fmt = Fmt;
  global.Purchasing.Util = Util;
  global.Purchasing.Toast = Toast;
  global.Purchasing.Confirm = Confirm;
  global.Purchasing.Layout = Layout;
})(window);
