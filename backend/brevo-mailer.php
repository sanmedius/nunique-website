<?php

/** Send transactional email through Brevo's HTTPS API. */
function nq_brevo_send($to, $subject, $html, $fromEmail, $fromName, $replyTo, $attachments, $config) {
    $apiKey = trim((string)($config['brevo_api_key'] ?? ''));
    if ($apiKey === '') {
        nq_smtp_set_last_error('API_CONFIG_MISSING');
        error_log('NUNIQUE Brevo API: API key is not configured.');
        return false;
    }
    $payload = [
        'sender' => ['email' => $fromEmail, 'name' => $fromName],
        'to' => [['email' => $to]],
        'subject' => $subject,
        'htmlContent' => $html,
        'replyTo' => ['email' => $replyTo],
        'tags' => ['nunique-order-form'],
    ];
    foreach ($attachments as $attachment) {
        $data = @file_get_contents((string)($attachment['tmp_name'] ?? ''));
        if ($data === false) continue;
        $payload['attachment'][] = [
            'name' => (string)($attachment['name'] ?? 'attachment'),
            'content' => base64_encode($data),
        ];
    }
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($json)) {
        nq_smtp_set_last_error('API_REQUEST_FAILED');
        error_log('NUNIQUE Brevo API: could not encode request.');
        return false;
    }
    $url = 'https://api.brevo.com/v3/smtp/email';
    $headers = ['accept: application/json', 'api-key: ' . $apiKey, 'content-type: application/json'];
    $status = 0;
    if (function_exists('curl_init')) {
        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => $json, CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($handle);
        $status = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($handle);
        curl_close($handle);
        if ($response === false) {
            nq_smtp_set_last_error('API_CONNECTION_FAILED');
            error_log('NUNIQUE Brevo API connection failed: ' . ($curlError !== '' ? $curlError : 'unknown error'));
            return false;
        }
    } else {
        $context = stream_context_create(['http' => [
            'method' => 'POST', 'header' => implode("\r\n", $headers), 'content' => $json,
            'timeout' => 30, 'ignore_errors' => true,
        ]]);
        $response = @file_get_contents($url, false, $context);
        foreach (($http_response_header ?? []) as $header) {
            if (preg_match('#^HTTP/\\S+\\s+(\\d{3})#', $header, $matches)) {
                $status = (int)$matches[1];
                break;
            }
        }
        if ($response === false && $status === 0) {
            nq_smtp_set_last_error('API_CONNECTION_FAILED');
            error_log('NUNIQUE Brevo API connection failed.');
            return false;
        }
    }
    if ($status >= 200 && $status < 300) return true;
    if ($status === 401 || $status === 403) nq_smtp_set_last_error('API_AUTH_FAILED');
    elseif ($status >= 400 && $status < 500) nq_smtp_set_last_error('API_REJECTED');
    else nq_smtp_set_last_error('API_REQUEST_FAILED');
    error_log('NUNIQUE Brevo API request failed with HTTP status ' . ($status ?: 'unknown') . '.');
    return false;
}
