/**
 * purchasing-api.js
 * -----------------------------------------------------------------------
 * Centralized API service layer. No page should ever call fetch()
 * directly - everything goes through `PurchasingAPI`.
 *
 * v2: the client-side mock/localStorage layer from the previous version
 * has been retired. A real login wall now guards every page, and
 * authentication is fundamentally a server-side concern - there is no
 * meaningful way to "mock" a login system purely in the browser. This
 * file always talks to the PHP + MySQL backend in /api.
 * -----------------------------------------------------------------------
 */
(function (global) {
  'use strict';

  const API_BASE_URL = './api';

  class ApiError extends Error {
    constructor(message, status, fieldErrors) {
      super(message);
      this.name = 'ApiError';
      this.status = status || 400;
      this.fieldErrors = fieldErrors || null;
    }
  }

  /**
   * Every mutating request carries the CSRF token from the page's <meta
   * name="pur-csrf-token"> tag (rendered server-side - see
   * partials/header.php and Purchasing.Auth.getCsrfToken()). `credentials:
   * 'same-origin'` ensures the PHP session cookie is sent, which is what
   * actually identifies the caller - the token only proves the request
   * came from a page this session rendered, not who the session belongs to.
   */
  async function request(path, options) {
    options = options || {};
    const headers = { 'Content-Type': 'application/json' };
    if (!['GET', 'HEAD'].includes((options.method || 'GET').toUpperCase())) {
      headers['X-CSRF-Token'] = global.Purchasing.Auth.getCsrfToken();
    }

    let res;
    try {
      res = await fetch(API_BASE_URL + path, {
        method: options.method || 'GET',
        credentials: 'same-origin',
        headers,
        body: options.body ? JSON.stringify(options.body) : undefined,
      });
    } catch (networkErr) {
      throw new ApiError('Tidak dapat menghubungi server. Periksa koneksi Anda.', 0);
    }

    const json = await res.json().catch(() => ({}));

    if (res.status === 401) {
      // Session expired/invalid - bounce to login rather than showing a
      // confusing inline error on whatever page the user was on.
      window.location.href = '../index.php';
      throw new ApiError('Sesi berakhir.', 401);
    }

    if (!res.ok) {
      throw new ApiError(json.message || 'Terjadi kesalahan pada server.', res.status, json.errors);
    }
    return json;
  }

  function qs(params) {
    const clean = {};
    Object.keys(params || {}).forEach((k) => {
      if (params[k] !== undefined && params[k] !== null && params[k] !== '') clean[k] = params[k];
    });
    const s = new URLSearchParams(clean).toString();
    return s ? '?' + s : '';
  }

  const PurchasingAPI = {
    // ---- Auth ------------------------------------------------------------
    me: () => request('/auth/me'),

    // ---- Dashboard ---------------------------------------------------------
    getDashboard: () => request('/purchasing/dashboard'),

    // ---- Purchase Orders (PO) ----------------------------------------------
    getTransactions: (params) => request('/purchasing/transactions' + qs(params)),
    getTransaction: (id) => request(`/purchasing/transactions/${id}`),
    createTransaction: (payload) => request('/purchasing/transactions', { method: 'POST', body: payload }),
    updateTransaction: (id, payload) => request(`/purchasing/transactions/${id}`, { method: 'PUT', body: payload }),
    cancelTransaction: (id, reason) => request(`/purchasing/transactions/${id}/cancel`, { method: 'POST', body: { reason } }),

    // ---- Master data --------------------------------------------------------
    getProducts: (params) => request('/products' + qs(params)),
    createProduct: (payload) => request('/products', { method: 'POST', body: payload }),
    updateProduct: (id, payload) => request(`/products/${id}`, { method: 'PUT', body: payload }),
    deleteProduct: (id) => request(`/products/${id}`, { method: 'DELETE' }),

    getDepartments: () => request('/departments'),

    getSuppliers: (params) => request('/suppliers' + qs(params)),
    createSupplier: (payload) => request('/suppliers', { method: 'POST', body: payload }),
    updateSupplier: (id, payload) => request(`/suppliers/${id}`, { method: 'PUT', body: payload }),
    deleteSupplier: (id) => request(`/suppliers/${id}`, { method: 'DELETE' }),

    // ---- Purchase Requisition (PR) ------------------------------------------
    getRequisitions: (params) => request('/pr' + qs(params)),
    getRequisition: (id) => request(`/pr/${id}`),
    createRequisition: (payload) => request('/pr', { method: 'POST', body: payload }),
    updateRequisition: (id, payload) => request(`/pr/${id}`, { method: 'PUT', body: payload }),
    submitRequisition: (id) => request(`/pr/${id}/submit`, { method: 'POST' }),
    approveRequisition: (id) => request(`/pr/${id}/approve`, { method: 'POST' }),
    rejectRequisition: (id, reason) => request(`/pr/${id}/reject`, { method: 'POST', body: { reason } }),
    convertRequisition: (id, payload) => request(`/pr/${id}/convert`, { method: 'POST', body: payload }),

    // ---- Goods Receipt Note (GRN) --------------------------------------------
    getGoodsReceipts: (params) => request('/grn' + qs(params)),
    getGoodsReceipt: (id) => request(`/grn/${id}`),
    createGoodsReceipt: (payload) => request('/grn', { method: 'POST', body: payload }),

    // ---- Vendor Invoices (3-way matching) -------------------------------------
    getInvoices: (params) => request('/invoices' + qs(params)),
    getInvoice: (id) => request(`/invoices/${id}`),
    createInvoice: (payload) => request('/invoices', { method: 'POST', body: payload }),
    approveInvoice: (id, notes) => request(`/invoices/${id}/approve`, { method: 'POST', body: { notes } }),
    rejectInvoice: (id, notes) => request(`/invoices/${id}/reject`, { method: 'POST', body: { notes } }),

    // ---- Purchase Returns -----------------------------------------------------
    getReturns: (params) => request('/returns' + qs(params)),
    createReturn: (payload) => request('/returns', { method: 'POST', body: payload }),

    // ---- Vendor Scorecard -------------------------------------------------------
    getVendorScorecard: () => request('/vendor-scorecard'),

    // ---- Activity Log (Admin only) ------------------------------------------
    getActivityLog: (params) => request('/activity-log' + qs(params)),
  };

  global.Purchasing = global.Purchasing || {};
  global.Purchasing.ApiError = ApiError;
  global.PurchasingAPI = PurchasingAPI;
})(window);
