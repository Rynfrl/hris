<?php
/**
 * partials/footer.php
 * Closes the markup opened by partials/header.php and loads the shared
 * script stack. The page including this may set $pageScript to its own
 * page-specific JS file (e.g. 'assets/js/purchasing-pr-list.js').
 */
?>
          </div>
        </div>
      </main>

      <footer class="app-footer">
        <strong>Modul Purchasing &copy; 2026 ERP System.</strong> Dibangun di atas AdminLTE v4.9.1 (Bootstrap 5).
      </footer>
    </div>

    <!--begin::Scripts-->
    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
    <script src="./assets/vendor/adminlte/js/adminlte.min.js"></script>
    <?php if (!empty($useChartJs)): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" crossorigin="anonymous"></script>
    <?php endif; ?>

    <script src="./assets/js/purchasing-common.js"></script>
    <script src="./assets/js/purchasing-api.js"></script>
    <?php if (!empty($pageScript)): ?>
    <script src="./<?= htmlspecialchars($pageScript) ?>"></script>
    <?php endif; ?>
  </body>
</html>
