<?php
/**
 * api/handlers/auth.php
 */

/** Mints (or returns the existing) CSRF token for the current HRIS session. */
function h_auth_csrf(): void
{
    pur_json_response(['csrf_token' => pur_csrf_token()]);
}

function h_auth_me(): void
{
    $user = pur_current_user();
    if (!$user) {
        pur_json_error(401, 'Belum login.');
    }
    pur_json_response([
        'user' => $user,
        'csrf_token' => pur_csrf_token(),
        'menu' => pur_role_menu($user['role']),
    ]);
}
