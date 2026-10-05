<?php
/**
 * NUNIQUE form backend configuration.
 * Keep this file private and do not expose backup copies publicly.
 */
$config = [
    'bakery_email' => 'info@designcakes.de',
    'bakery_name'  => 'NUNIQUE — Cakes & Coffee',
    'bakery_phone' => '+49 170 47 42 351',
    'site_url'     => 'https://cakes-coffee.de',

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
    
    // STRATO SMTP. Production reads the password from a private file outside
    // the public website directory; the local PHP server uses config.local.php.
    'smtp_host'       => 'smtp.strato.de',
    'smtp_port'       => 465,
    'smtp_encryption' => 'ssl',
    'smtp_username'   => 'info@designcakes.de',
    'smtp_password'   => '',
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
