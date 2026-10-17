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

    // Local abuse controls. These store only hashes and timestamps in the system temp folder.
    'rate_limit_window_seconds' => 10 * 60,
    'rate_limit_max_submits'    => 5,
    'duplicate_window_seconds'  => 15 * 60,
    
    // Transactional email provider. Production reads the credentials from a
    // private file outside the public website directory; local PHP uses
    // config.local.php. Brevo SMTP values belong in that private file.
    'smtp_host'       => '',
    'smtp_port'       => 587,
    'smtp_encryption' => 'tls',
    'smtp_username'   => '',
    'smtp_password'   => '',
    'brevo_api_key'   => '',

    // Cloudflare Turnstile secret. Keep the real value only in /private.
    'turnstile_secret_key' => '',
    'turnstile_allowed_hostnames' => [
        'munichcakes.de',
        'www.munichcakes.de',
        'test.cakes-coffee.de',
    ],
];

$privateConfigCandidates = [];
$configuredPath = getenv('NUNIQUE_CONFIG_PATH');
if (is_string($configuredPath) && trim($configuredPath) !== '') {
    $privateConfigCandidates[] = trim($configuredPath);
}

// Production: /private is a sibling of the public /test-website directory.
$privateConfigCandidates[] = dirname(__DIR__, 2) . '/private/nunique-config.php';

// Local development fallback only. Production never reads a secret from the web root.
if (PHP_SAPI === 'cli-server') {
    $privateConfigCandidates[] = __DIR__ . '/config.local.php';
}

foreach ($privateConfigCandidates as $privateConfig) {
    if (!is_file($privateConfig) || !is_readable($privateConfig)) continue;
    $local = require $privateConfig;
    if (is_array($local)) {
        $config = array_replace($config, $local);
        break;
    }
}

return $config;
