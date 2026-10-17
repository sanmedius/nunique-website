<?php

// Local development only: copy this file to config.local.php.
// Never upload config.local.php. Production uses /private/nunique-config.php.
// Use a normal Brevo API key here; do not use an MCP key or real mailbox password.
return [
    'brevo_api_key'   => 'YOUR_BREVO_API_KEY',
    'turnstile_secret_key' => 'YOUR_TURNSTILE_SECRET_KEY',
    'turnstile_allowed_hostnames' => ['munichcakes.de', 'www.munichcakes.de', 'test.cakes-coffee.de'],
    'smtp_host'       => 'smtp-relay.brevo.com',
    'smtp_port'       => 587,
    'smtp_encryption' => 'tls',
    'smtp_username'   => 'YOUR_BREVO_SMTP_LOGIN',
    'smtp_password'   => 'YOUR_BREVO_SMTP_KEY',
    'from_email'      => 'orders@designcakes.de',
    'from_name'       => 'NUNIQUE Cakes & Coffee',
];
