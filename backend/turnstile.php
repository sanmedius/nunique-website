<?php

function nq_turnstile_set_last_error($code) {
    $GLOBALS['nq_turnstile_last_error'] = (string)$code;
}

function nq_turnstile_last_error() {
    return (string)($GLOBALS['nq_turnstile_last_error'] ?? 'UNKNOWN');
}

function nq_turnstile_verify($token, $config, $remoteIp) {
    $secret = trim((string)($config['turnstile_secret_key'] ?? ''));
    if ($secret === '') {
        nq_turnstile_set_last_error('CONFIG_MISSING');
        error_log('NUNIQUE Turnstile: secret key is not configured.');
        return false;
    }
    if (!is_string($token) || $token === '' || strlen($token) > 2048) {
        nq_turnstile_set_last_error('TOKEN_MISSING');
        return false;
    }

    $data = [
        'secret' => $secret,
        'response' => $token,
    ];
    if (filter_var($remoteIp, FILTER_VALIDATE_IP)) {
        $data['remoteip'] = $remoteIp;
    }
    $body = http_build_query($data, '', '&', PHP_QUERY_RFC3986);
    $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $status = 0;

    if (function_exists('curl_init')) {
        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['content-type: application/x-www-form-urlencoded'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($handle);
        $status = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($handle);
        curl_close($handle);
        if ($response === false) {
            nq_turnstile_set_last_error('CONNECTION_FAILED');
            error_log('NUNIQUE Turnstile connection failed: ' . ($curlError !== '' ? $curlError : 'unknown error'));
            return false;
        }
    } else {
        $context = stream_context_create(['http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $body,
            'timeout' => 15,
            'ignore_errors' => true,
        ]]);
        $response = @file_get_contents($url, false, $context);
        foreach (($http_response_header ?? []) as $header) {
            if (preg_match('#^HTTP/\\S+\\s+(\\d{3})#', $header, $matches)) {
                $status = (int)$matches[1];
                break;
            }
        }
        if ($response === false && $status === 0) {
            nq_turnstile_set_last_error('CONNECTION_FAILED');
            error_log('NUNIQUE Turnstile connection failed.');
            return false;
        }
    }

    $result = is_string($response) ? json_decode($response, true) : null;
    if ($status < 200 || $status >= 300 || !is_array($result) || empty($result['success'])) {
        nq_turnstile_set_last_error('REJECTED');
        error_log('NUNIQUE Turnstile validation rejected the submission.');
        return false;
    }

    $allowedHosts = array_map('strtolower', (array)($config['turnstile_allowed_hostnames'] ?? []));
    $hostname = strtolower((string)($result['hostname'] ?? ''));
    if ($allowedHosts !== [] && !in_array($hostname, $allowedHosts, true)) {
        nq_turnstile_set_last_error('HOSTNAME_MISMATCH');
        error_log('NUNIQUE Turnstile validation returned an unexpected hostname.');
        return false;
    }
    return true;
}
