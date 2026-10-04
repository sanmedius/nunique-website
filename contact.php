<?php
// ══════════════════════════════════════════
// NUNIQUE — Contact Form Handler
// ══════════════════════════════════════════
$TO    = 'info@designcakes.de';
$NAME  = 'NUNIQUE — Cakes & Coffee';

if (!empty($_POST["website"] ?? "")) { http_response_code(200); exit; }
$captcha_expected = trim($_POST["captcha_expected"] ?? "7");
if (trim($_POST["captcha"] ?? "") !== $captcha_expected) { header("Location: index.html#contact?captcha=failed"); exit; }

if (($_SERVER["REQUEST_METHOD"] ?? "") !== "POST") {
    header('Location: index.html'); exit;
}

function h($s) { return htmlspecialchars(trim($s)); }

$email    = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$name     = h($_POST['name'] ?? '');
$telefon  = h($_POST['telefon'] ?? '');
$nachricht = h($_POST['nachricht'] ?? '');

if (!$email || !$name || !$nachricht) {
    header('Location: index.html#contact'); exit;
}

$subject = "Kontaktanfrage von $name";
$body = "<html><body style='font-family:Arial,sans-serif;max-width:600px'>
<div style='background:#E91E8C;padding:20px;border-radius:12px 12px 0 0'>
<h2 style='color:#fff;margin:0'>Neue Kontaktanfrage</h2></div>
<div style='background:#fff;padding:20px;border:1px solid #eee;border-top:none;border-radius:0 0 12px 12px'>
<p><strong>Name:</strong> $name</p>
<p><strong>E-Mail:</strong> <a href='mailto:$email'>$email</a></p>"
.($telefon ? "<p><strong>Telefon:</strong> $telefon</p>" : '')
."<p><strong>Nachricht:</strong></p>
<p style='background:#f9f9f9;padding:12px;border-radius:8px'>$nachricht</p>
</div></body></html>";

$headers  = "From: $NAME <$TO>\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";

mail($TO, $subject, $body, $headers);

// JSON response for JS fetch
header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>
