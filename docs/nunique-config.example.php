<?php

// Copy this file on STRATO to /private/nunique-config.php.
// /private must be outside the public /test-website directory.
// Never commit the completed file or paste its secrets into support messages.
// Use a Brevo SMTP key, never the password for a real mailbox.
return [
    'smtp_host'       => 'smtp-relay.brevo.com',
    'smtp_port'       => 465,
    'smtp_encryption' => 'ssl',
    'smtp_username'   => 'YOUR_BREVO_SMTP_LOGIN',
    'smtp_password'   => 'YOUR_BREVO_SMTP_KEY',
    'from_email'      => 'orders@designcakes.de',
    'from_name'       => 'NUNIQUE Cakes & Coffee',
    'site_url'        => 'https://munichcakes.de',
];
