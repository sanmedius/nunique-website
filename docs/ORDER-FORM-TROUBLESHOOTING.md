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
- `BOT_CHECK_FAILED` — Cloudflare Turnstile was missing, expired, or rejected.
- `FORM_TIME_INVALID` — the form was submitted too quickly or too late.
- `UPLOAD_FILE_TOO_LARGE` — one uploaded image is too large.
- `UPLOAD_TOTAL_TOO_LARGE` — all uploaded images together are too large.
- `UPLOAD_TYPE_NOT_ALLOWED` — uploaded file is not JPG, PNG or WebP.
- `MAIL_OWNER_FAILED` — PHP accepted the form data, but the email to the bakery could not be sent.

## If `MAIL_OWNER_FAILED` appears

The backend sends through STRATO SMTP. Check the PHP error log for the corresponding
`NUNIQUE SMTP` entry; it distinguishes a missing password, a connection failure, and
an SMTP response error.

For STRATO, copy `docs/nunique-config.example.php` to
`/private/nunique-config.php`, outside `/test-website`, and enter the SMTP password
and Turnstile keys there. This file is intentionally excluded from source control
and deployment. Production never reads `backend/config.local.php`.

If the error persists after deployment, confirm that the SMTP password belongs to
`info@designcakes.de` and review the hosting PHP error log for the SMTP response code.
Do not put the password in source control or share it in support messages.

## If the security check does not load

1. Confirm that `/private/nunique-config.php` contains both Turnstile keys.
2. Add `test.cakes-coffee.de` and the production domains to the Turnstile widget's
   allowed hostnames.
3. Confirm that the browser can reach `https://challenges.cloudflare.com`.
4. Check the PHP error log for a `NUNIQUE Turnstile` entry.
