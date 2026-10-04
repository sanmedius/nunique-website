# Order Form Troubleshooting

The order form is static HTML with a PHP backend in `backend/send-order.php`.

## If the form shows an error page

The error page now displays an error code.

Common codes:

- `MISSING_DATUM` — no pickup date reached the backend.
- `MISSING_UHRZEIT` — no pickup time reached the backend.
- `MISSING_GESCHMACK` — no flavour was selected.
- `MISSING_GROESSE` — no size was selected.
- `CAPTCHA_INVALID` — the security question was missing or wrong.
- `FORM_TIME_INVALID` — the form was submitted too quickly or too late.
- `UPLOAD_FILE_TOO_LARGE` — one uploaded image is too large.
- `UPLOAD_TOTAL_TOO_LARGE` — all uploaded images together are too large.
- `UPLOAD_TYPE_NOT_ALLOWED` — uploaded file is not JPG, PNG or WebP.
- `MAIL_OWNER_FAILED` — PHP accepted the form data, but the email to the bakery could not be sent.

## If `MAIL_OWNER_FAILED` appears

This usually means the host does not send via PHP `mail()` reliably or requires special mail settings.

Next options:

1. Confirm that `info@designcakes.de` exists on the hosting account.
2. Check whether the host allows PHP `mail()` with `-f info@designcakes.de`.
3. If not, replace the mail function with SMTP via PHPMailer.

The frontend can remain unchanged.
