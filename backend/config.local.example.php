<?php

// Local development only: copy this file to config.local.php.
// Never upload config.local.php. Production uses /private/nunique-config.php.
// Use a Brevo SMTP key, never the password for a real mailbox.
return [
    'smtp_host'       => 'smtp-relay.brevo.com',
    'smtp_port'       => 465,
    'smtp_encryption' => 'ssl',
    'smtp_username'   => 'YOUR_BREVO_SMTP_LOGIN',
    'smtp_password'   => 'YOUR_BREVO_SMTP_KEY',
    'from_email'      => 'orders@designcakes.de',
    'from_name'       => 'NUNIQUE Cakes & Coffee',
];
