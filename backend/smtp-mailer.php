<?php

function nq_smtp_read_response($socket) {
    $response = '';
    while (!feof($socket)) {
        $line = fgets($socket, 515);
        if ($line === false) break;
        $response .= $line;
        if (strlen($line) >= 4 && $line[3] === ' ') break;
    }
    return $response;
}

function nq_smtp_expect($socket, $codes) {
    $response = nq_smtp_read_response($socket);
    $code = (int)substr($response, 0, 3);
    if (!in_array($code, $codes, true)) {
        throw new RuntimeException('SMTP response ' . ($code ?: 'unknown'));
    }
}

function nq_smtp_command($socket, $command, $codes) {
    fwrite($socket, $command . "\r\n");
    nq_smtp_expect($socket, $codes);
}

function nq_smtp_send($to, $subject, $headers, $message, $config) {
    $password = (string)($config['smtp_password'] ?? '');
    if ($password === '') {
        error_log('NUNIQUE SMTP: password is not configured.');
        return false;
    }
    $host = (string)$config['smtp_host'];
    $port = (int)$config['smtp_port'];
    $transport = ($config['smtp_encryption'] ?? '') === 'ssl' ? 'ssl://' : 'tcp://';
    $socket = @stream_socket_client($transport . $host . ':' . $port, $errno, $error, 15, STREAM_CLIENT_CONNECT);
    if (!$socket) {
        error_log('NUNIQUE SMTP connection failed: ' . $errno);
        return false;
    }
    stream_set_timeout($socket, 15);
    try {
        nq_smtp_expect($socket, [220]);
        nq_smtp_command($socket, 'EHLO munichcakes.de', [250]);
        nq_smtp_command($socket, 'AUTH LOGIN', [334]);
        nq_smtp_command($socket, base64_encode((string)$config['smtp_username']), [334]);
        nq_smtp_command($socket, base64_encode($password), [235]);
        nq_smtp_command($socket, 'MAIL FROM:<' . $config['from_email'] . '>', [250]);
        nq_smtp_command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
        nq_smtp_command($socket, 'DATA', [354]);
        $payload = 'To: <' . $to . ">\r\nSubject: " . $subject . "\r\n" . $headers . "\r\n\r\n" . $message;
        $payload = preg_replace('/(?m)^\./', '..', $payload);
        fwrite($socket, $payload . "\r\n.\r\n");
        nq_smtp_expect($socket, [250]);
        nq_smtp_command($socket, 'QUIT', [221]);
        fclose($socket);
        return true;
    } catch (Throwable $exception) {
        error_log('NUNIQUE SMTP delivery failed: ' . $exception->getMessage());
        fclose($socket);
        return false;
    }
}
