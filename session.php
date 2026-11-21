<?php
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }

  function hr_normalize_role($role): string
  {
    return strtoupper(trim((string)$role));
  }

  function hr_is_admin($role): bool
  {
    return in_array(hr_normalize_role($role), ['ADMIN', 'ADMIN_HR'], true);
  }

  function hr_is_purchasing_role($role): bool
  {
    return in_array(hr_normalize_role($role), [
      'ADMIN_PURCHASE',
      'ADMIN_PURCHASING',
      'PURCHASING_STAFF',
    ], true);
  }

  function hr_is_sales_role($role): bool
  {
    return in_array(hr_normalize_role($role), [
      'ADMIN_SALES',
      'MARKETING_STAFF',
    ], true);
  }

  function hr_is_inventory_role($role): bool
  {
    return in_array(hr_normalize_role($role), [
      'ADMIN_WAREHOUSE',
      'WAREHOUSE_STAFF',
    ], true);
  }

  function hr_is_expenses_role($role): bool
  {
    return in_array(hr_normalize_role($role), [
      'ADMIN',
      'ADMIN_EXPENSE',
      'MANAGER',
      'FINANCE',
    ], true);
  }

  function hr_is_expenses_user($role, $username = ''): bool
  {
    $normRole = hr_normalize_role($role);
    $normUser = strtolower(trim((string)$username));

    // EMPLOYEE role is excluded from expense workflow
    if ($normRole === 'EMPLOYEE' && !in_array($normUser, ['admin_gte'], true)) {
      return false;
    }

    if (hr_is_expenses_role($normRole)) {
      return true;
    }

    if ($normUser === 'admin_gte' || in_array($normUser, ['rina_manager', 'dimas_finance'], true)) {
      return true;
    }

    if ($normRole === 'ADMIN' && $normUser !== 'yuraa_n') {
      return true;
    }

    return false;
  }
?>
