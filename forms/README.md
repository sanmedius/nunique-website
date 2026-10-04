# Forms

The order form is handled by the isolated PHP backend:

```text
order.html -> backend/send-order.php
```

The public website is still static. PHP is only used where it is necessary: receiving and validating the form submission, handling temporary uploads, and sending emails.

If emails do not arrive, the likely cause is hosting mail deliverability. In that case, keep the same structure but replace PHP `mail()` inside `backend/send-order.php` with authenticated SMTP/PHPMailer.
