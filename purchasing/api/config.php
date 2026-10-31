<?php
/**
 * api/config.php
 * -----------------------------------------------------------------------
 * Purchasing-specific settings. Database credentials are shared with HRIS
 * through config/database.php.
 * -----------------------------------------------------------------------
 */

$databaseConfig = require __DIR__ . '/../../config/database.php';

return [
    'db' => $databaseConfig,

    // Indonesian PPN rate used to recompute totals server-side. Keep this
    // in sync with assets/js/purchasing-common.js CONFIG.TAX_RATE - the
    // frontend only shows a live preview; this value is the one that
    // actually gets persisted.
    'tax_rate' => 0.11,

    // Purchase Requisition approval matrix: a PR whose grand_total is at
    // or below this amount is auto-approved on submit; above it, the PR
    // is routed to a Finance Manager/Admin for manual Approve/Reject.
    'pr_approval_threshold' => 5_000_000,

    // Tolerance used by 3-way matching (PO vs GRN value vs Vendor Invoice)
    // before a vendor invoice is flagged "Mismatched". Expressed as a
    // fraction (0.01 = 1%).
    'three_way_match_tolerance' => 0.01,

    // Same-origin (frontend + API on the same host/port) needs no CORS
    // headers at all and is the supported/default setup. Only add an
    // origin here if you deliberately serve the frontend from a DIFFERENT
    // origin/port during development (e.g. Live Server on :5500 while the
    // API runs on :8000) - since real login sessions use cookies, this
    // must be an exact origin, never '*' (browsers refuse wildcard origin
    // together with credentialed requests).
    'cors_allowed_origins' => [
        // 'http://localhost:5500',
    ],

    // ---------------------------------------------------------------------
    // Security
    // ---------------------------------------------------------------------
    // true while developing locally: error responses include technical
    // detail (helps you fix a bad DB config fast). Set to FALSE before this
    // is ever reachable outside your own machine - detailed error messages
    // can leak internal information to an attacker.
    'debug' => true,

    // Very small built-in per-request abuse guard (per-IP counter,
    // file-based - no extra services required). This is NOT real DDoS
    // protection: it only slows down a single misbehaving client hitting
    // this one PHP process; a genuine distributed denial-of-service attack
    // must be stopped upstream (firewall/reverse proxy/CDN, e.g.
    // Cloudflare, nginx `limit_req`, fail2ban) before traffic ever reaches
    // PHP.
    'rate_limit' => [
        'enabled' => true,
        'max_requests' => 120,   // per window, per IP
        'window_seconds' => 60,
    ],

    // Reject request bodies larger than this before they are even parsed,
    // to blunt naive payload-flooding attempts.
    'max_request_body_bytes' => 1_000_000, // 1 MB

    // Session cookie lifetime (seconds of inactivity before auto-logout).
    'session_lifetime_seconds' => 8 * 3600,
];
