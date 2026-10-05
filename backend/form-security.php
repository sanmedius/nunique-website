<?php

function nq_security_start_session() {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $https,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

function nq_issue_order_security() {
    nq_security_start_session();
    $_SESSION['nq_order_security'] = [
        'csrf' => bin2hex(random_bytes(32)),
        'expires' => time() + 7200,
    ];
    return ['token' => $_SESSION['nq_order_security']['csrf']];
}

function nq_verify_order_security($post) {
    nq_security_start_session();
    $state = $_SESSION['nq_order_security'] ?? null;
    $token = is_string($post['csrf_token'] ?? null) ? $post['csrf_token'] : '';
    $valid = is_array($state)
        && ($state['expires'] ?? 0) >= time()
        && hash_equals((string)($state['csrf'] ?? ''), $token);
    unset($_SESSION['nq_order_security']);
    return $valid;
}
