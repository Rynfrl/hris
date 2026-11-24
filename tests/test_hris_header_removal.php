<?php
/**
 * Test Suite: HRIS Top Header & Horizontal Navigation Removal Verification
 * 
 * Verifies:
 * 1. Top header and horizontal navigation bar are completely removed from dashboard.php.
 * 2. Welcome card (User identity, username, email, role, division) is preserved.
 * 3. Form Tambah User and all other HR operational sections (Employees, Divisions, Payroll, Users) are preserved.
 * 4. Layout starts directly from top with clean container and no empty leftover space.
 * 5. Logout backend endpoint (logout.php) and session handling remain intact.
 * 6. ERP module sidebars and headers (Purchasing, Sales, Inventory, Expense) remain 100% intact.
 * 7. Simulated renders for Admin and Employee sessions succeed without errors.
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

echo "===============================================================\n";
echo "TEST SUITE: HRIS TOP HEADER & HORIZONTAL NAVIGATION REMOVAL\n";
echo "===============================================================\n\n";

// 1. Static Source Code Checks on dashboard.php
echo "--- 1. Checking dashboard.php Source Code ---\n";
$dashboardFile = __DIR__ . '/../dashboard.php';
assertTrue(file_exists($dashboardFile), "dashboard.php exists");
$dashboardContent = file_get_contents($dashboardFile);

// Check complete removal of top header components
assertTrue(!str_contains($dashboardContent, '<header class="hris-header"'), "Element '<header class=\"hris-header\"' is completely removed");
assertTrue(!str_contains($dashboardContent, 'class="hris-topbar"'), "Element 'hris-topbar' is completely removed");
assertTrue(!str_contains($dashboardContent, 'class="hris-brand-area"'), "Brand area 'hris-brand-area' is completely removed");
assertTrue(!str_contains($dashboardContent, 'class="hris-module-tag"'), "Badge 'hris-module-tag' is completely removed");
assertTrue(!str_contains($dashboardContent, 'class="logout-btn"'), "Header logout button 'logout-btn' is completely removed");

// Check complete removal of horizontal navigation bar components
assertTrue(!str_contains($dashboardContent, '<nav class="hris-nav-bar"'), "Navbar '<nav class=\"hris-nav-bar\"' is completely removed");
assertTrue(!str_contains($dashboardContent, 'class="hris-nav-container"'), "Nav container 'hris-nav-container' is completely removed");
assertTrue(!str_contains($dashboardContent, 'class="hris-nav-pill'), "Nav pills 'hris-nav-pill' are completely removed");
assertTrue(!str_contains($dashboardContent, 'class="hris-erp-pill"'), "ERP pills 'hris-erp-pill' are completely removed");
assertTrue(!str_contains($dashboardContent, 'class="hris-erp-group"'), "ERP group 'hris-erp-group' is completely removed");

// Check complete removal of sidebar components
assertTrue(!str_contains($dashboardContent, '<aside class="hris-sidebar"'), "Sidebar '<aside class=\"hris-sidebar\"' is absent");
assertTrue(!str_contains($dashboardContent, 'id="hrisSidebar"'), "Element 'hrisSidebar' is absent");
assertTrue(!str_contains($dashboardContent, 'id="hrisToggleBtn"'), "Element 'hrisToggleBtn' is absent");

// 2. Preserved Components in dashboard.php
echo "\n--- 2. Checking Preserved Components in dashboard.php ---\n";
assertTrue(str_contains($dashboardContent, 'class="welcome-card"'), "Welcome card 'welcome-card' is preserved");
assertTrue(str_contains($dashboardContent, 'Selamat datang, <?= htmlspecialchars($_SESSION[\'name\']) ?>!'), "Welcome card title is preserved");
assertTrue(str_contains($dashboardContent, '<strong>Username:</strong>'), "Username label in welcome card is preserved");
assertTrue(str_contains($dashboardContent, '<strong>Email:</strong>'), "Email label in welcome card is preserved");
assertTrue(str_contains($dashboardContent, '<strong>Role:</strong>'), "Role label in welcome card is preserved");
assertTrue(str_contains($dashboardContent, '<strong>Divisi:</strong>'), "Division label in welcome card is preserved");
assertTrue(str_contains($dashboardContent, 'id="users"'), "Section id 'users' is preserved");
assertTrue(str_contains($dashboardContent, 'Tambah User'), "Form 'Tambah User' is preserved");
assertTrue(str_contains($dashboardContent, 'proses_user.php'), "Form action 'proses_user.php' is preserved");
assertTrue(str_contains($dashboardContent, 'name="action" value="add_user"'), "Action 'add_user' is preserved");
assertTrue(str_contains($dashboardContent, 'id="employees"'), "Section id 'employees' is preserved");
assertTrue(str_contains($dashboardContent, 'id="divisions"'), "Section id 'divisions' is preserved");
assertTrue(str_contains($dashboardContent, 'id="payrolls"'), "Section id 'payrolls' is preserved");

// Check clean layout styles
assertTrue(str_contains($dashboardContent, '.container {'), "Container CSS rule is present");
assertTrue(str_contains($dashboardContent, 'max-width: 1400px;'), "Container max-width 1400px is present");

// 3. Logout Backend & Auth Integrity
echo "\n--- 3. Checking Logout Backend & Session Integrity ---\n";
$logoutFile = __DIR__ . '/../logout.php';
assertTrue(file_exists($logoutFile), "logout.php endpoint exists and is intact");
$logoutContent = file_get_contents($logoutFile);
assertTrue(str_contains($logoutContent, 'session_destroy()'), "logout.php properly destroys session");
assertTrue(str_contains($logoutContent, 'header("Location: index.php")'), "logout.php properly redirects to login");

// 4. ERP Module Integrity
echo "\n--- 4. Checking ERP Module Sidebars & Headers Integrity ---\n";
$purchasingHeader = __DIR__ . '/../purchasing/partials/header.php';
assertTrue(file_exists($purchasingHeader) && str_contains(file_get_contents($purchasingHeader), '$navItems'), "Purchasing ERP navigation is intact");
$salesSidebar = __DIR__ . '/../sales/includes/sidebar.php';
assertTrue(file_exists($salesSidebar) && str_contains(file_get_contents($salesSidebar), 'class="main-sidebar'), "Sales ERP sidebar is intact");
$inventoryPage = __DIR__ . '/../Inventory/page.php';
assertTrue(file_exists($inventoryPage), "Inventory ERP page.php is intact");
$expenseLayout = __DIR__ . '/../expenses/app/Views/layouts/main.php';
assertTrue(file_exists($expenseLayout) && str_contains(file_get_contents($expenseLayout), '<aside class="sidebar'), "Expense ERP sidebar is intact");

// 5. Execution Simulation (Admin Session)
echo "\n--- 5. Simulating Render for Admin User ---\n";
$cmdAdmin = 'php -r "'
    . 'session_start();'
    . '$_SESSION[\'login\'] = true;'
    . '$_SESSION[\'user_id\'] = 1;'
    . '$_SESSION[\'name\'] = \'Yudha Dwi Pratama (Admin HR)\';'
    . '$_SESSION[\'username\'] = \'Yuraa_n\';'
    . '$_SESSION[\'email\'] = \'yudha@gmail.com\';'
    . '$_SESSION[\'role\'] = \'Admin_HR\';'
    . '$_SESSION[\'csrf_token\'] = \'test-token\';'
    . 'ob_start();'
    . 'include \'dashboard.php\';'
    . '$html = ob_get_clean();'
    . 'assert(!str_contains($html, \'class=\"hris-header\"\'));'
    . 'assert(!str_contains($html, \'class=\"hris-topbar\"\'));'
    . 'assert(!str_contains($html, \'class=\"hris-nav-bar\"\'));'
    . 'assert(str_contains($html, \'Selamat datang, Yudha Dwi Pratama (Admin HR)!\'));'
    . 'assert(str_contains($html, \'Yuraa_n\'));'
    . 'assert(str_contains($html, \'yudha@gmail.com\'));'
    . 'assert(str_contains($html, \'Admin_HR\'));'
    . 'assert(str_contains($html, \'Tambah User\'));'
    . 'assert(str_contains($html, \'Simpan User\'));'
    . 'echo \'Admin render simulation passed.\';'
    . '"';

$outputAdmin = shell_exec($cmdAdmin);
assertTrue(str_contains((string)$outputAdmin, 'Admin render simulation passed.'), "Admin session renders cleanly without header or horizontal nav");

// 6. Execution Simulation (Employee Session)
echo "\n--- 6. Simulating Render for Employee User ---\n";
$cmdEmp = 'php -r "'
    . 'session_start();'
    . '$_SESSION[\'login\'] = true;'
    . '$_SESSION[\'user_id\'] = 999;'
    . '$_SESSION[\'name\'] = \'Staff Employee\';'
    . '$_SESSION[\'username\'] = \'staff_emp\';'
    . '$_SESSION[\'email\'] = \'staff@example.com\';'
    . '$_SESSION[\'role\'] = \'EMPLOYEE\';'
    . '$_SESSION[\'csrf_token\'] = \'test-token\';'
    . 'ob_start();'
    . 'include \'dashboard.php\';'
    . '$html = ob_get_clean();'
    . 'assert(!str_contains($html, \'class=\"hris-header\"\'));'
    . 'assert(!str_contains($html, \'class=\"hris-topbar\"\'));'
    . 'assert(!str_contains($html, \'class=\"hris-nav-bar\"\'));'
    . 'assert(str_contains($html, \'Selamat datang, Staff Employee!\'));'
    . 'assert(!str_contains($html, \'Tambah User\'));'
    . 'assert(str_contains($html, \'Formulir operasional hanya tersedia untuk akun Admin.\'));'
    . 'echo \'Employee render simulation passed.\';'
    . '"';

$outputEmp = shell_exec($cmdEmp);
assertTrue(str_contains((string)$outputEmp, 'Employee render simulation passed.'), "Employee session renders cleanly with role restriction and without header");

echo "\n===============================================================\n";
echo "SUMMARY: " . ($assertions - count($failures)) . "/$assertions assertions passed.\n";
if (!empty($failures)) {
    echo "Failures encountered:\n";
    foreach ($failures as $f) {
        echo " - " . $f . "\n";
    }
    exit(1);
} else {
    echo "ALL HRIS HEADER & HORIZONTAL NAVIGATION REMOVAL CHECKS PASSED!\n";
    echo "===============================================================\n";
    exit(0);
}
