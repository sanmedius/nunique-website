<?php

// Local development only: copy this file to config.local.php.
// Never upload config.local.php. Production uses /private/nunique-config.php.
// The Turnstile values below are Cloudflare's official always-pass test keys.
return [
    'smtp_password' => 'PASTE_YOUR_STRATO_SMTP_PASSWORD_HERE',
    'turnstile_site_key' => '1x00000000000000000000AA',
    'turnstile_secret_key' => '1x0000000000000000000000000000000AA',
    'turnstile_allowed_hostnames' => ['localhost'],
];
