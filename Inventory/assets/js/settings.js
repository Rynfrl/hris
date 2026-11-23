/**
 * settings.js - Logika Pengaturan Sistem Inventory
 * Inventoria - Modern Inventory Management
 */

(function () {
  'use strict';

  // DOM Elements
  const form = document.getElementById('settings-form');
  const btnSubmit = document.getElementById('btn-submit-settings');

  // Fields
  const fields = {
    company_name: document.getElementById('company_name'),
    company_npwp: document.getElementById('company_npwp'),
    company_email: document.getElementById('company_email'),
    company_phone: document.getElementById('company_phone'),
    company_address: document.getElementById('company_address'),
    theme_pref: document.getElementById('theme_pref')
  };

  // Inisialisasi
  document.addEventListener('DOMContentLoaded', () => {
    loadSettings();
    setupEventListeners();
  });

  function setupEventListeners() {
    // Form Submit
    if (form) {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        saveSettings();
      });
    }

    // Live Theme Preview saat dropdown preferensi tema diganti
    if (fields.theme_pref) {
      fields.theme_pref.addEventListener('change', (e) => {
        const selectedTheme = e.target.value;
        if (window.InvTheme && window.InvTheme.set) {
          window.InvTheme.set(selectedTheme);
        }
        if (window.InvToast) {
          window.InvToast.show({
            type: 'info',
            title: 'Tema Diterapkan',
            message: `Mode tema antarmuka diubah ke: ${getThemeLabel(selectedTheme)}`,
            duration: 2500
          });
        }
      });
    }
  }

  function getThemeLabel(val) {
    if (val === 'dark') return 'Dark Mode (Mode Gelap)';
    if (val === 'system') return 'Mengikuti Tema Sistem / OS';
    return 'Light Mode (Mode Terang)';
  }

  // Muat Pengaturan dari Database
  async function loadSettings() {
    try {
      const response = await fetch('../api/settings.php');
      if (!response.ok) throw new Error(`HTTP error ${response.status}`);

      const res = await response.json();
      if (res.status !== 'success' || !res.data) throw new Error(res.message || 'Gagal memuat pengaturan');

      const d = res.data;

      if (fields.company_name) fields.company_name.value = d.company_name || '';
      if (fields.company_npwp) fields.company_npwp.value = d.company_npwp || '';
      if (fields.company_email) fields.company_email.value = d.company_email || '';
      if (fields.company_phone) fields.company_phone.value = d.company_phone || '';
      if (fields.company_address) fields.company_address.value = d.company_address || '';

      if (fields.theme_pref) {
        const curTheme = d.theme_pref || localStorage.getItem('inv_theme') || 'light';
        fields.theme_pref.value = curTheme;
        if (window.InvTheme && window.InvTheme.apply) {
          window.InvTheme.apply(curTheme);
        }
      }

    } catch (err) {
      console.error('Gagal mengambil pengaturan:', err);
      if (window.InvToast) {
        window.InvToast.show({
          type: 'danger',
          title: 'Gagal Memuat Pengaturan',
          message: err.message || 'Terjadi kesalahan saat memuat konfigurasi dari database.'
        });
      }
    }
  }

  // Simpan Pengaturan ke Database
  async function saveSettings() {
    if (!fields.company_name.value.trim()) {
      if (window.InvToast) {
        window.InvToast.show({
          type: 'warning',
          title: 'Periksa Input',
          message: 'Nama perusahaan wajib diisi.'
        });
      }
      fields.company_name.focus();
      return;
    }

    setSubmitting(true);

    const payload = {
      company_name: fields.company_name.value.trim(),
      company_npwp: fields.company_npwp.value.trim(),
      company_email: fields.company_email.value.trim(),
      company_phone: fields.company_phone.value.trim(),
      company_address: fields.company_address.value.trim(),
      theme_pref: fields.theme_pref.value
    };

    try {
      const response = await fetch('../api/settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const res = await response.json();
      if (!response.ok || res.status !== 'success') {
        throw new Error(res.message || 'Gagal menyimpan pengaturan.');
      }

      // Simpan preferensi lokal
      localStorage.setItem('inv_theme', payload.theme_pref);

      if (window.InvTheme && window.InvTheme.apply) {
        window.InvTheme.apply(payload.theme_pref);
      }

      if (window.InvToast) {
        window.InvToast.show({
          type: 'success',
          title: 'Pengaturan Berhasil Disimpan',
          message: 'Identitas perusahaan dan preferensi tema antarmuka berhasil diperbarui.',
          duration: 4000
        });
      }

    } catch (err) {
      console.error('Error saving settings:', err);
      if (window.InvToast) {
        window.InvToast.show({
          type: 'danger',
          title: 'Gagal Menyimpan',
          message: err.message || 'Terjadi kesalahan server saat menyimpan pengaturan.'
        });
      }
    } finally {
      setSubmitting(false);
    }
  }

  function setSubmitting(isSubmitting) {
    if (btnSubmit) {
      btnSubmit.disabled = isSubmitting;
      btnSubmit.innerHTML = isSubmitting
        ? '<span class="spinner-small"></span> Menyimpan...'
        : '<svg viewBox="0 0 24 24" width="16" height="16"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg> <span>Simpan Pengaturan</span>';
    }
  }

})();
