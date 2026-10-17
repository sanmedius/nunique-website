<?php

// Copy this file on STRATO to /private/nunique-config.php.
// /private must be outside the public /test-website directory.
// Never commit the completed file or paste its secrets into support messages.
// Use a normal Brevo API key (not an MCP key). The form prefers HTTPS API delivery.
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
    'site_url'        => 'https://munichcakes.de',
];
