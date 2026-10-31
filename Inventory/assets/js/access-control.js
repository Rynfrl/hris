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
      const userName = result.user.name.trim() || 'Pengguna';
      const initials = userName
        .split(/\s+/)
        .slice(0, 2)
        .map(part => part[0])
        .join('')
        .toUpperCase();
      document.querySelectorAll('.user-name, .header-user-name').forEach(element => {
        element.textContent = userName;
      });
      document.querySelectorAll('.user-role').forEach(element => {
        element.textContent = result.user.role;
      });
      document.querySelectorAll('.sidebar-footer .user-avatar, .header-user-avatar').forEach(element => {
        element.textContent = initials;
      });

      if (isAdmin) return;

      const hideRestrictedControls = () => {
        document.querySelectorAll(
          'a[href*="categories.html"], a[href*="reports.html"], a[href*="settings.html"], ' +
          'a[href*="product-form.html"], .btn-danger, .btn-icon-danger'
        ).forEach(element => element.hidden = true);

        document.querySelectorAll('button').forEach(button => {
          if (/hapus/i.test(button.textContent || '')) button.hidden = true;
        });
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
