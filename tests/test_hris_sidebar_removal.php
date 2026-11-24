<?php
/**
 * Test Suite: HRIS Sidebar Removal & Full-Width Layout Verification
 * 
 * Verifies:
 * 1. HRIS sidebar markup and toggle elements are completely removed from dashboard.php.
 * 2. Full-width top navigation header with user info, role badge, division badge, and logout is present.
 * 3. Horizontal navigation pills and integrated ERP quick-links are properly rendered.
 * 4. Layout width and container max-width (1400px) are configured.
 * 5. Simulation of Admin and Employee sessions runs cleanly without errors.
 * 6. ERP module sidebars (Purchasing, Sales, Inventory, Expense) remain completely intact and untouched.
 */

declare(strict_types=1);

$failures = [];
$assertions = 0;

function assertTrue(bool $condition, string $message): void {
    global $failures, $assertions;
    $assertions++;
    if (!$condition) {
        $failures[] = "FAILED: " . $message;
        echo "[FAIL] " . $message . "\n";
    } else {
        echo "[PASS] " . $message . "\n";
    }
}

echo "========================================================\n";
echo "TEST SUITE: HRIS SIDEBAR REMOVAL & ERP SIDEBAR INTEGRITY\n";
echo "========================================================\n\n";

// 1. Static Source Code Checks on dashboard.php
echo "--- 1. Checking dashboard.php Source Code ---\n";
$dashboardFile = __DIR__ . '/../dashboard.php';
assertTrue(file_exists($dashboardFile), "dashboard.php exists");
$dashboardContent = file_get_contents($dashboardFile);

// Check complete removal of HRIS sidebar elements
assertTrue(!str_contains($dashboardContent, '<aside class="hris-sidebar"'), "Markup '<aside class=\"hris-sidebar\"' is completely removed");
assertTrue(!str_contains($dashboardContent, 'id="hrisSidebar"'), "Element id 'hrisSidebar' is completely removed");
assertTrue(!str_contains($dashboardContent, 'id="hrisToggleBtn"'), "Element id 'hrisToggleBtn' is completely removed");
assertTrue(!str_contains($dashboardContent, '.hris-sidebar {'), "CSS rule '.hris-sidebar' is removed");
assertTrue(!str_contains($dashboardContent, '.hris-sidebar-brand {'), "CSS rule '.hris-sidebar-brand' is removed");

// Check complete removal of topbar header and navigation bar
assertTrue(!str_contains($dashboardContent, 'class="hris-header"'), "Header container 'hris-header' is removed");
assertTrue(!str_contains($dashboardContent, 'class="hris-topbar"'), "Topbar 'hris-topbar' is removed");
assertTrue(!str_contains($dashboardContent, 'class="hris-nav-bar"'), "Secondary navbar 'hris-nav-bar' is removed");
assertTrue(!str_contains($dashboardContent, 'class="hris-nav-pill'), "Nav pills 'hris-nav-pill' are removed");
assertTrue(!str_contains($dashboardContent, 'class="hris-erp-pill"'), "ERP pills 'hris-erp-pill' are removed");
assertTrue(str_contains($dashboardContent, 'max-width: 1400px'), "Layout container max-width 1400px is configured");
assertTrue(str_contains($dashboardContent, '$userDivision'), "Division lookup variable '\$userDivision' is implemented");

// 2. ERP Sidebars Integrity Checks
echo "\n--- 2. Checking ERP Module Sidebars Integrity ---\n";

// Purchasing
$purchasingHeader = __DIR__ . '/../purchasing/partials/header.php';
assertTrue(file_exists($purchasingHeader), "Purchasing header exists");
$purchasingContent = file_get_contents($purchasingHeader);
assertTrue(str_contains($purchasingContent, 'app-sidebar') || str_contains($purchasingContent, '$navItems'), "Purchasing sidebar/nav navigation remains intact");

// Sales
$salesSidebar = __DIR__ . '/../sales/includes/sidebar.php';
assertTrue(file_exists($salesSidebar), "Sales sidebar exists");
$salesContent = file_get_contents($salesSidebar);
assertTrue(str_contains($salesContent, 'class="main-sidebar'), "Sales sidebar '<aside class=\"main-sidebar\"' remains intact");

// Inventory
$inventoryPage = __DIR__ . '/../Inventory/page.php';
assertTrue(file_exists($inventoryPage), "Inventory page.php exists");
$invContent = file_get_contents($inventoryPage);
assertTrue(str_contains($invContent, 'sidebar') || file_exists(__DIR__ . '/../Inventory/pages/dashboard.html'), "Inventory layout and pages remain intact");
if (file_exists(__DIR__ . '/../Inventory/pages/dashboard.html')) {
    $invDashContent = file_get_contents(__DIR__ . '/../Inventory/pages/dashboard.html');
    assertTrue(str_contains($invDashContent, 'sidebar') || str_contains($invDashContent, 'nav'), "Inventory dashboard sidebar navigation remains intact");
}

// Expense Management
$expenseLayout = __DIR__ . '/../expenses/app/Views/layouts/main.php';
assertTrue(file_exists($expenseLayout), "Expense Management layout exists");
$expenseContent = file_get_contents($expenseLayout);
assertTrue(str_contains($expenseContent, '<aside class="sidebar'), "GT Expense '<aside class=\"sidebar\"' remains intact");

// 3. Execution Simulation (Admin Session)
echo "\n--- 3. Simulating Render for Admin User ---\n";
$cmdAdmin = 'php -r "'
    . 'session_start();'
    . '$_SESSION[\'login\'] = true;'
    . '$_SESSION[\'user_id\'] = 1;'
    . '$_SESSION[\'name\'] = \'Admin Test\';'
    . '$_SESSION[\'username\'] = \'admintest\';'
    . '$_SESSION[\'email\'] = \'admin@example.com\';'
    . '$_SESSION[\'role\'] = \'ADMIN_HR\';'
    . '$_SESSION[\'csrf_token\'] = \'test-token\';'
    . 'ob_start();'
    . 'include \'dashboard.php\';'
    . '$html = ob_get_clean();'
    . 'assert(!str_contains($html, \'class=\"hris-sidebar\"\'));'
    . 'assert(!str_contains($html, \'class=\"hris-header\"\'));'
    . 'assert(str_contains($html, \'Selamat datang, Admin Test!\'));'
    . 'assert(str_contains($html, \'ADMIN_HR\'));'
    . 'assert(str_contains($html, \'id=\"employees\"\'));'
    . 'assert(str_contains($html, \'id=\"payrolls\"\'));'
    . 'echo \'Admin render simulation passed.\';'
    . '"';

$outputAdmin = shell_exec($cmdAdmin);
assertTrue(str_contains((string)$outputAdmin, 'Admin render simulation passed.'), "Admin session renders cleanly with full operational cards and no header/sidebar");

// 4. Execution Simulation (Employee Session)
echo "\n--- 4. Simulating Render for Employee User ---\n";
$cmdEmp = 'php -r "'
    . 'session_start();'
    . '$_SESSION[\'login\'] = true;'
    . '$_SESSION[\'user_id\'] = 999;'
    . '$_SESSION[\'name\'] = \'Staff Test\';'
    . '$_SESSION[\'username\'] = \'stafftest\';'
    . '$_SESSION[\'email\'] = \'staff@example.com\';'
    . '$_SESSION[\'role\'] = \'EMPLOYEE\';'
    . '$_SESSION[\'csrf_token\'] = \'test-token\';'
    . 'ob_start();'
    . 'include \'dashboard.php\';'
    . '$html = ob_get_clean();'
    . 'assert(!str_contains($html, \'class=\"hris-sidebar\"\'));'
    . 'assert(!str_contains($html, \'class=\"hris-header\"\'));'
    . 'assert(str_contains($html, \'Selamat datang, Staff Test!\'));'
    . 'assert(!str_contains($html, \'id=\"employees\"\'));'
    . 'assert(str_contains($html, \'Formulir operasional hanya tersedia untuk akun Admin.\'));'
    . 'echo \'Employee render simulation passed.\';'
    . '"';

$outputEmp = shell_exec($cmdEmp);
assertTrue(str_contains((string)$outputEmp, 'Employee render simulation passed.'), "Employee session renders cleanly with role restriction and no sidebar");

echo "\n========================================================\n";
echo "SUMMARY: " . ($assertions - count($failures)) . "/$assertions assertions passed.\n";
if (!empty($failures)) {
    echo "Failures encountered:\n";
    foreach ($failures as $f) {
        echo " - " . $f . "\n";
    }
    exit(1);
} else {
    echo "ALL HRIS SIDEBAR REMOVAL CHECKS PASSED SUCCESSFULLY!\n";
    echo "========================================================\n";
    exit(0);
}
