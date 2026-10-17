# Order Form Troubleshooting

The order form is static HTML with a PHP backend in `backend/send-order.php`.

## If the form shows an error page

The error page now displays an error code.

Common codes:

- `MISSING_DATUM` — no pickup date reached the backend.
- `MISSING_UHRZEIT` — no pickup time reached the backend.
- `MISSING_GESCHMACK` — no flavour was selected.
- `MISSING_GROESSE` — no size was selected.
- `SECURITY_TOKEN_INVALID` — the session-bound form token was missing or expired.
- `FORM_TIME_INVALID` — the form was submitted too quickly or too late.
- `DUPLICATE_SUBMISSION` — the same inquiry was already accepted recently.
- `UPLOAD_FILE_TOO_LARGE` — one uploaded image is too large.
- `UPLOAD_TOTAL_TOO_LARGE` — all uploaded images together are too large.
- `UPLOAD_TYPE_NOT_ALLOWED` — uploaded file is not JPG, PNG or WebP.
- `MAIL_OWNER_FAILED_CONFIG_MISSING` — the private SMTP configuration could not be read.
- `MAIL_OWNER_FAILED_CONNECTION_FAILED` — the web server could not connect to the SMTP relay.
- `MAIL_OWNER_FAILED_AUTH_FAILED` — the Brevo SMTP login or SMTP key was rejected.
- `MAIL_OWNER_FAILED_IP_NOT_AUTHORIZED` — Brevo SMTP IP blocking is active and the server IP is not allowed.
- `MAIL_OWNER_FAILED_SMTP_REJECTED` — Brevo rejected the sender or another SMTP request.

## If `MAIL_OWNER_FAILED` appears

The backend prefers Brevo's HTTPS API when `brevo_api_key` is configured; otherwise it
uses Brevo SMTP. Check the PHP error log for the corresponding `NUNIQUE Brevo API` or
`NUNIQUE SMTP` entry.

Copy `docs/nunique-config.example.php` to `/private/nunique-config.php`, outside
`/test-website`, and enter the normal Brevo API key there. This file is intentionally excluded from source control
and deployment. Production never reads `backend/config.local.php`. Do not use a Brevo MCP key.

If the error persists after deployment, confirm that the API key is active in Brevo,
the sender domain is authenticated, and review the hosting PHP error log for the safe
error code. Do not put the key in source control or share it in support messages.

## If the form reports a security-token error

1. Open the form through the PHP website rather than as a local `file://` page.
2. Reload the page so it can request a fresh session token.
3. Confirm that PHP sessions work on the server and that cookies are accepted.

## If the form reports a Turnstile error

Confirm that `/private/nunique-config.php` contains `turnstile_secret_key` with the
private Cloudflare secret key. Do not put that key in GitHub or the public website.
The widget hostname list in Cloudflare must include the domain used for testing and
the live domain. A Turnstile token can be used only once and expires after five minutes.
