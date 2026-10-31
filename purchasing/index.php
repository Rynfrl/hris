<?php
/**
 * index.php
 * -----------------------------------------------------------------------
 * Entry point for the Purchasing module. Authentication is handled by HRIS.
 * -----------------------------------------------------------------------
 */
require_once __DIR__ . '/api/helpers.php';
require_once __DIR__ . '/api/auth.php';

pur_start_session();
header('Location: ' . (pur_is_logged_in() ? './dashboard.php' : '../index.php'));
exit;
