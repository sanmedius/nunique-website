<?php
/**
 * NUNIQUE form backend configuration.
 * Keep this file private and do not expose backup copies publicly.
 */
$config = [
    'bakery_email' => 'info@designcakes.de',
    'bakery_name'  => 'NUNIQUE — Cakes & Coffee',
    'bakery_phone' => '+49 170 47 42 351',
    'site_url'     => 'https://munichcakes.de',

    // Use an address from your own domain for better deliverability.
    'from_email'   => 'info@designcakes.de',
    'from_name'    => 'NUNIQUE — Cakes & Coffee',
    'mail_subject_prefix' => '[NUNIQUE Bestellung]',

    // Upload limits for inspiration images.
    'max_files'        => 5,
    'max_file_bytes'   => 5 * 1024 * 1024,
    'max_total_bytes'  => 12 * 1024 * 1024,
    'allowed_mime'     => [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ],

    // Simple rate limit. This stores small counter files in the system temp folder.
    'rate_limit_window_seconds' => 10 * 60,
    'rate_limit_max_submits'    => 5,
    
    // STRATO SMTP. Keep the password in backend/config.local.php.
    'smtp_host'       => 'smtp.strato.de',
    'smtp_port'       => 465,
    'smtp_encryption' => 'ssl',
    'smtp_username'   => 'info@designcakes.de',
    'smtp_password'   => '',
];

$localConfig = __DIR__ . '/config.local.php';
if (is_file($localConfig)) {
    $local = require $localConfig;
    if (is_array($local)) $config = array_replace($config, $local);
}

return $config;
