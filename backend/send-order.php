<?php
/**
 * NUNIQUE secure order form handler.
 * Static frontend + isolated PHP backend.
 */

$config = require __DIR__ . '/config.php';
require_once __DIR__ . '/form-security.php';
require_once __DIR__ . '/smtp-mailer.php';

function nq_clean_text($value, $maxLength = 1000) {
    $value = is_string($value) ? $value : '';
    $value = str_replace(["\0", "\r"], '', $value);
    $value = trim($value);
    if (function_exists('mb_substr')) {
        $value = mb_substr($value, 0, $maxLength, 'UTF-8');
    } else {
        $value = substr($value, 0, $maxLength);
    }
    return $value;
}

function nq_html($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function nq_header_text($value, $maxLength = 180) {
    $value = nq_clean_text($value, $maxLength);
    return preg_replace('/[^\p{L}\p{N}\s\-_.@+&—]/u', '', $value);
}

function nq_redirect($path) {
    header('Location: ' . $path, true, 303);
    exit;
}

function nq_client_ip() {
    // STRATO serves this endpoint directly. Never trust client-controlled forwarded headers.
    $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
    return 'unknown';
}

function nq_rate_limited($config) {
    $ip = nq_client_ip();
    $hash = hash('sha256', $ip . '|nunique-order-form');
    $file = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'nq_rate_' . $hash . '.json';
    $now = time();
    $window = (int)$config['rate_limit_window_seconds'];
    $max = (int)$config['rate_limit_max_submits'];
    $data = ['start' => $now, 'count' => 0];

    if (is_file($file)) {
        $decoded = json_decode((string)file_get_contents($file), true);
        if (is_array($decoded) && isset($decoded['start'], $decoded['count'])) {
            $data = $decoded;
        }
    }

    if (($now - (int)$data['start']) > $window) {
        $data = ['start' => $now, 'count' => 0];
    }

    $data['count'] = (int)$data['count'] + 1;
    @file_put_contents($file, json_encode($data), LOCK_EX);

    return $data['count'] > $max;
}

function nq_build_html_table($rows) {
    $out = '<table style="width:100%;border-collapse:collapse">';
    foreach ($rows as $label => $value) {
        $out .= '<tr>'
            . '<td style="padding:6px 0;color:#777;width:160px;font-size:13px;vertical-align:top">' . nq_html($label) . ':</td>'
            . '<td style="padding:6px 0;font-size:13px;vertical-align:top">' . nl2br(nq_html($value ?: '-')) . '</td>'
            . '</tr>';
    }
    return $out . '</table>';
}

function nq_mail_html($to, $subject, $html, $fromEmail, $fromName, $replyTo = null, $attachments = [], $smtpConfig = []) {
    $subject = nq_header_text($subject, 180);
    $fromName = nq_header_text($fromName, 120);
    $fromEmail = filter_var($fromEmail, FILTER_VALIDATE_EMAIL) ? $fromEmail : 'noreply@localhost';
    $replyTo = filter_var($replyTo, FILTER_VALIDATE_EMAIL) ? $replyTo : $fromEmail;

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';

    $headers = [];
    $headers[] = 'From: ' . $encodedFromName . ' <' . $fromEmail . '>';
    $headers[] = 'Reply-To: ' . $replyTo;
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'X-Mailer: PHP/' . phpversion();

    if (!empty($attachments)) {
        $boundary = 'nq_' . bin2hex(random_bytes(16));
        $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';

        $message = '--' . $boundary . "\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $message .= quoted_printable_encode($html) . "\r\n";

        foreach ($attachments as $attachment) {
            $filename = nq_header_text($attachment['name'], 120);
            $mime = nq_header_text($attachment['mime'], 80);
            $data = file_get_contents($attachment['tmp_name']);
            if ($data === false) continue;
            $message .= '--' . $boundary . "\r\n";
            $message .= 'Content-Type: ' . $mime . '; name="' . addslashes($filename) . '"' . "\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n";
            $message .= 'Content-Disposition: attachment; filename="' . addslashes($filename) . '"' . "\r\n\r\n";
            $message .= chunk_split(base64_encode($data)) . "\r\n";
        }

        $message .= '--' . $boundary . "--\r\n";
    } else {
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: quoted-printable';
        $message = quoted_printable_encode($html);
    }

    $headerString = implode("\r\n", $headers);
    // Many shared hosts require a valid envelope sender from the hosted domain.
    $params = '';
    if (stripos(PHP_OS, 'WIN') !== 0 && filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        $params = '-f' . $fromEmail;
    }
    return nq_smtp_send($to, $encodedSubject, $headerString, $message, $smtpConfig);
}

function nq_show_result($type, $lang, $name = '', $ref = '', $config = [], $code = '') {
    $en = $lang === 'en';
    $site = $config['site_url'] ?? '/';
    $phone = $config['bakery_phone'] ?? '';
    $email = $config['bakery_email'] ?? '';

    if ($type === 'success') {
        $title = $en ? 'Thank you!' : 'Vielen Dank!';
        $message = $en
            ? 'Your cake inquiry has been received. A confirmation has been sent to your email. We will get back to you as soon as possible.'
            : 'Ihre Tortenanfrage ist bei uns eingegangen. Eine Bestätigung wurde an Ihre E-Mail gesendet. Wir melden uns schnellstmöglich.';
        $notice = $en
            ? 'Please note: this is an inquiry only. The order is confirmed only after explicit confirmation from us.'
            : 'Bitte beachten: Dies ist eine Anfrage. Die Bestellung ist erst nach expliziter Bestätigung durch uns verbindlich.';
        $color = '#E91E8C';
    } else {
        $title = $en ? 'Something went wrong' : 'Etwas ist schiefgelaufen';
        $message = $en
            ? 'Please go back, check your entries and try again. If the problem persists, contact us directly.'
            : 'Bitte gehen Sie zurück, prüfen Sie Ihre Angaben und versuchen Sie es erneut. Falls das Problem bestehen bleibt, kontaktieren Sie uns direkt.';
        $notice = $en ? 'No inquiry was sent.' : 'Es wurde keine Anfrage gesendet.';
        $color = '#B00020';
    }

    $back = $en ? 'Back to form' : 'Zurück zum Formular';
    $home = $en ? 'Homepage' : 'Startseite';
    $refLabel = $en ? 'Reference' : 'Referenz';
    $contactLabel = $en ? 'Urgent? Call us:' : 'Dringend? Rufen Sie uns an:';

    echo '<!doctype html><html lang="' . nq_html($lang) . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>' . nq_html($title) . ' — NUNIQUE</title>'
        . '<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#FAFAFA;color:#1A1A1A;font-family:Arial,sans-serif;padding:24px}.card{max-width:520px;background:#fff;border:1px solid #eee;border-radius:18px;box-shadow:0 12px 40px rgba(0,0,0,.08);overflow:hidden}.top{background:' . $color . ';padding:32px;text-align:center;color:white}.top h1{margin:0;font-family:Georgia,serif;font-weight:400}.body{padding:28px;line-height:1.65}.ref{display:inline-block;background:#E0F7FA;color:#007C89;border-radius:999px;padding:6px 14px;margin:14px 0;font-size:14px}.notice{background:#FFF3E0;border-left:4px solid #E91E8C;padding:12px 14px;margin:16px 0;border-radius:0 8px 8px 0;font-size:14px}.links{display:flex;gap:14px;flex-wrap:wrap;margin-top:20px}.links a{color:#00A9B8;text-decoration:none;font-weight:700}</style></head><body>'
        . '<main class="card"><div class="top"><h1>' . nq_html($title) . '</h1></div><div class="body">'
        . '<p>' . nq_html($message) . '</p>'
        . ($ref ? '<div class="ref">' . nq_html($refLabel) . ': <strong>' . nq_html($ref) . '</strong></div>' : '')
        . '<div class="notice">' . nq_html($notice) . '</div>'
        . (($type !== 'success' && $code !== '') ? '<p style="font-size:13px;color:#777">Fehlercode / Error code: <strong>' . nq_html($code) . '</strong></p>' : '')
        . '<p>' . nq_html($contactLabel) . ' ' . nq_html($phone) . '<br><a href="mailto:' . nq_html($email) . '">' . nq_html($email) . '</a></p>'
        . '<div class="links"><a href="../order">← ' . nq_html($back) . '</a><a href="' . nq_html($site) . '">' . nq_html($home) . '</a></div>'
        . '</div></main></body></html>';
    exit;
}

function nq_error($lang, $code, $config) {
    error_log('NUNIQUE order form error: ' . $code);
    nq_show_result('error', $lang, '', '', $config, $code);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    nq_redirect('../order');
}

$lang = ($_POST['lang'] ?? 'de') === 'en' ? 'en' : 'de';

// Spam traps. Return success-like empty response for honeypot bots.
if (!empty($_POST['website'] ?? '')) {
    http_response_code(204);
    exit;
}

if (nq_rate_limited($config)) {
    nq_error($lang, 'RATE_LIMITED', $config);
}

$started = (int)($_POST['form_started_at'] ?? 0);
if ($started > 0) {
    $age = time() - $started;
    if ($age < 3 || $age > 8 * 60 * 60) {
        nq_error($lang, 'FORM_TIME_INVALID', $config);
    }
}

if (!nq_verify_order_security($_POST)) {
    nq_error($lang, 'SECURITY_TOKEN_INVALID', $config);
}

if (!nq_verify_turnstile($_POST, $config)) {
    nq_error($lang, 'BOT_CHECK_FAILED', $config);
}

$vorname = nq_clean_text($_POST['vorname'] ?? '', 80);
$nachname = nq_clean_text($_POST['nachname'] ?? '', 80);
$adresse = nq_clean_text($_POST['adresse'] ?? '', 240);
$email = filter_var($_POST['customer_email'] ?? '', FILTER_VALIDATE_EMAIL);
$phone = nq_clean_text($_POST['phone'] ?? '', 80);
$anlassRaw = nq_clean_text($_POST['anlass'] ?? '', 80);
$anlassOther = nq_clean_text($_POST['anlass_sonstig'] ?? '', 120);
$anlass = ($anlassRaw === 'Sonstiges' && $anlassOther !== '') ? 'Sonstiges: ' . $anlassOther : $anlassRaw;
$datum = nq_clean_text($_POST['datum'] ?? '', 40);
$uhrzeit = nq_clean_text($_POST['uhrzeit'] ?? '', 40);
$geschmack1 = nq_clean_text($_POST['geschmack_1'] ?? '', 80);
$geschmack2 = nq_clean_text($_POST['geschmack_2'] ?? '', 80);
$geschmack = implode(', ', array_filter([$geschmack1, $geschmack2]));
$formShape = nq_clean_text($_POST['form'] ?? '', 80);
$groesse = nq_clean_text($_POST['groesse'] ?? '', 120);
$groesseCustom = nq_clean_text($_POST['groesse_custom'] ?? '', 120);
if ($groesse === '' && $groesseCustom !== '') {
    $groesse = $groesseCustom;
}
$farbe = nq_clean_text($_POST['farbe'] ?? '', 120);
$schriftzug = nq_clean_text($_POST['schriftzug'] ?? '', 180);
$verzierung = nq_clean_text($_POST['verzierung'] ?? '', 180);
$allergien = nq_clean_text($_POST['allergien_hidden'] ?? '', 240);
$allergieSonstig = nq_clean_text($_POST['allergie_sonstig'] ?? '', 180);
$anmerkungen = nq_clean_text($_POST['anmerkungen'] ?? '', 2000);
$agbAccepted = isset($_POST['agb_accepted']);

$missing = [];
if (!$email) $missing[] = 'email';
if ($vorname === '') $missing[] = 'vorname';
if ($nachname === '') $missing[] = 'nachname';
if ($phone === '') $missing[] = 'phone';
if ($anlass === '') $missing[] = 'anlass';
if ($datum === '') $missing[] = 'datum';
if ($uhrzeit === '') $missing[] = 'uhrzeit';
if ($geschmack === '') $missing[] = 'geschmack';
if ($groesse === '') $missing[] = 'groesse';
if (!$agbAccepted) $missing[] = 'agb_accepted';
if (!empty($missing)) {
    nq_error($lang, 'MISSING_' . strtoupper(implode('_', $missing)), $config);
}

$attachments = [];
$totalBytes = 0;
if (!empty($_FILES['inspiration']) && is_array($_FILES['inspiration']['name'])) {
    $names = $_FILES['inspiration']['name'];
    $tmpNames = $_FILES['inspiration']['tmp_name'];
    $errors = $_FILES['inspiration']['error'];
    $sizes = $_FILES['inspiration']['size'];
    $maxFiles = (int)$config['max_files'];
    $count = min(count($names), $maxFiles);
    $finfo = class_exists('finfo') ? new finfo(FILEINFO_MIME_TYPE) : null;

    for ($i = 0; $i < $count; $i++) {
        if ($errors[$i] === UPLOAD_ERR_NO_FILE) continue;
        if ($errors[$i] !== UPLOAD_ERR_OK) nq_error($lang, 'UPLOAD_ERROR', $config);
        if ($sizes[$i] <= 0 || $sizes[$i] > $config['max_file_bytes']) nq_error($lang, 'UPLOAD_FILE_TOO_LARGE', $config);
        $totalBytes += (int)$sizes[$i];
        if ($totalBytes > $config['max_total_bytes']) nq_error($lang, 'UPLOAD_TOTAL_TOO_LARGE', $config);
        if (!is_uploaded_file($tmpNames[$i])) nq_error($lang, 'UPLOAD_INVALID_SOURCE', $config);

        if (!$finfo) {
            nq_error($lang, 'UPLOAD_VALIDATION_UNAVAILABLE', $config);
        }
        $mime = $finfo->file($tmpNames[$i]);
        if (!isset($config['allowed_mime'][$mime])) nq_error($lang, 'UPLOAD_TYPE_NOT_ALLOWED', $config);

        $base = pathinfo((string)$names[$i], PATHINFO_FILENAME);
        $base = preg_replace('/[^\p{L}\p{N}\-_]+/u', '-', $base);
        $base = trim($base, '-');
        if ($base === '') $base = 'inspiration';
        $safeName = substr($base, 0, 70) . '.' . $config['allowed_mime'][$mime];
        $attachments[] = ['tmp_name' => $tmpNames[$i], 'name' => $safeName, 'mime' => $mime];
    }
}

$name = trim($vorname . ' ' . $nachname);
$ref = 'NQ-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
$timestamp = date('d.m.Y H:i');

$rows = [
    'Referenz' => $ref,
    'Eingang' => $timestamp,
    'Name' => $name,
    'E-Mail' => $email,
    'Telefon' => $phone,
    'Adresse' => $adresse,
    'Anlass' => $anlass,
    'Abholdatum' => $datum,
    'Abholzeit' => $uhrzeit,
    'Geschmack' => $geschmack,
    'Form' => $formShape,
    'Größe / Personen' => $groesse,
    'Farbe' => $farbe,
    'Schriftzug' => $schriftzug,
    'Verzierung' => $verzierung,
    'Allergien' => trim($allergien . ($allergieSonstig ? ' (' . $allergieSonstig . ')' : '')),
    'Anmerkungen' => $anmerkungen,
];

$ownerHtml = '<html><body style="font-family:Arial,sans-serif;color:#1A1A1A;max-width:680px">'
    . '<div style="background:#E91E8C;padding:20px;border-radius:14px 14px 0 0"><h2 style="color:white;margin:0">Neue Tortenanfrage</h2><p style="color:white;margin:6px 0 0;opacity:.85">' . nq_html($ref) . '</p></div>'
    . '<div style="border:1px solid #eee;border-top:0;padding:22px;border-radius:0 0 14px 14px">'
    . nq_build_html_table($rows)
    . '</div></body></html>';

$en = $lang === 'en';
$customerSubject = $en ? 'Your cake inquiry — NUNIQUE' : 'Ihre Tortenanfrage — NUNIQUE';
$summaryTitle = $en ? 'Your inquiry summary' : 'Ihre Anfrage im Überblick';
$customerNotice = $en
    ? 'Please note: this is an inquiry only. The order is confirmed only after explicit confirmation from us.'
    : 'Bitte beachten: Dies ist eine Anfrage. Die Bestellung ist erst nach expliziter Bestätigung durch uns verbindlich.';
$customerIntro = $en
    ? 'Thank you for your cake inquiry. We have received your request and will get back to you as soon as possible.'
    : 'Vielen Dank für Ihre Tortenanfrage. Wir haben Ihre Anfrage erhalten und melden uns schnellstmöglich.';
$customerRows = [
    $en ? 'Reference' : 'Referenz' => $ref,
    $en ? 'Name' : 'Name' => $name,
    $en ? 'Email' : 'E-Mail' => $email,
    $en ? 'Phone' : 'Telefon' => $phone,
    $en ? 'Occasion' : 'Anlass' => $anlass,
    $en ? 'Pickup date' : 'Abholdatum' => $datum,
    $en ? 'Pickup time' : 'Abholzeit' => $uhrzeit,
    $en ? 'Flavour' : 'Geschmack' => $geschmack,
    $en ? 'Size' : 'Größe' => $groesse,
    $en ? 'Notes' : 'Anmerkungen' => $anmerkungen,
];

$customerHtml = '<html><body style="font-family:Arial,sans-serif;color:#1A1A1A;max-width:680px;margin:0 auto">'
    . '<div style="background:#E91E8C;padding:22px;border-radius:14px 14px 0 0;text-align:center"><h1 style="color:#fff;margin:0;font-size:22px;letter-spacing:2px">NUNIQUE</h1><p style="color:#fff;opacity:.85;margin:4px 0 0">Cakes & Coffee</p></div>'
    . '<div style="border:1px solid #eee;border-top:0;padding:22px">'
    . '<p>' . nq_html($customerIntro) . '</p>'
    . '<h3 style="color:#0097A7">' . nq_html($summaryTitle) . '</h3>'
    . nq_build_html_table($customerRows)
    . '<div style="background:#FFF3E0;border-left:4px solid #E91E8C;border-radius:0 8px 8px 0;padding:12px 14px;margin:16px 0;font-size:13px;line-height:1.6">' . nq_html($customerNotice) . '</div>'
    . '<p style="font-size:13px;color:#777">' . nq_html($config['bakery_email']) . '<br>' . nq_html($config['bakery_phone']) . '<br>' . nq_html($config['site_url']) . '</p>'
    . '</div><div style="background:#E91E8C;color:#fff;padding:12px;text-align:center;border-radius:0 0 14px 14px;font-size:13px">NUNIQUE — Cakes & Coffee</div></body></html>';

$ownerSubject = 'Tortenanfrage von ' . $name . ' — ' . $datum;
$ownerOk = nq_mail_html($config['bakery_email'], $ownerSubject, $ownerHtml, $config['from_email'], $config['from_name'], $email, $attachments, $config);
$customerOk = nq_mail_html($email, $customerSubject . ' (' . $ref . ')', $customerHtml, $config['from_email'], $config['from_name'], $config['bakery_email'], [], $config);

if (!$ownerOk) {
    error_log('NUNIQUE order form: owner email failed for ' . $ref);
    nq_error($lang, 'MAIL_OWNER_FAILED', $config);
}
if (!$customerOk) {
    error_log('NUNIQUE order form: customer confirmation failed for ' . $ref);
}

nq_show_result('success', $lang, $name, $ref, $config);
