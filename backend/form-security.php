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

function nq_verify_turnstile($post, $config) {
    $secret = trim((string)($config['turnstile_secret_key'] ?? ''));
    $responseToken = trim((string)($post['cf-turnstile-response'] ?? ''));
    if ($secret === '' || $responseToken === '') {
        error_log('NUNIQUE Turnstile: configuration or response token is missing.');
        return false;
    }

    $payload = [
        'secret' => $secret,
        'response' => $responseToken,
    ];
    $remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    if (filter_var($remoteAddress, FILTER_VALIDATE_IP)) {
        $payload['remoteip'] = $remoteAddress;
    }

    $body = null;
    if (function_exists('curl_init')) {
        $curl = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $body = curl_exec($curl);
        curl_close($curl);
    } else {
        $context = stream_context_create(['http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($payload),
            'timeout' => 10,
            'ignore_errors' => true,
        ]]);
        $body = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $context);
    }

    if (!is_string($body) || $body === '') {
        error_log('NUNIQUE Turnstile: verification service could not be reached.');
        return false;
    }

    $result = json_decode($body, true);
    if (!is_array($result) || ($result['success'] ?? false) !== true) {
        error_log('NUNIQUE Turnstile: token verification failed.');
        return false;
    }
    if (($result['action'] ?? '') !== 'order_form') {
        error_log('NUNIQUE Turnstile: unexpected action.');
        return false;
    }

    $allowedHostnames = $config['turnstile_allowed_hostnames'] ?? [];
    $verifiedHostname = strtolower((string)($result['hostname'] ?? ''));
    if (!is_array($allowedHostnames) || !in_array($verifiedHostname, array_map('strtolower', $allowedHostnames), true)) {
        error_log('NUNIQUE Turnstile: unexpected hostname.');
        return false;
    }
    return true;
}
