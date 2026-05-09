<?php
// ============================================================
// NUNIQUE — Cakes & Coffee — Tortenbestellung
// ============================================================
$BAKERY_EMAIL = 'info@designcakes.de';
$BAKERY_NAME  = 'NUNIQUE — Cakes & Coffee';
$BAKERY_PHONE = '+49 170 47 42 351';
$BAKERY_URL   = 'https://munichcakes.de';
// ============================================================

if (!empty($_POST["website"] ?? "")) { http_response_code(200); exit; }
$captcha_expected = trim($_POST["captcha_expected"] ?? "7");
if (trim($_POST["captcha"] ?? "") !== $captcha_expected) { header("Location: order.html?captcha=failed"); exit; }

if (($_SERVER["REQUEST_METHOD"] ?? "") !== "POST") { header('Location: index.html'); exit; }

$lang           = $_POST['lang'] ?? 'de';
$vorname        = h($_POST['vorname'] ?? '');
$nachname       = h($_POST['nachname'] ?? '');
$adresse        = h($_POST['adresse'] ?? '');
$email          = filter_var($_POST['customer_email'] ?? '', FILTER_VALIDATE_EMAIL);
$phone          = h($_POST['phone'] ?? '');
$anlass_raw      = h($_POST['anlass'] ?? '');
$anlass_sonstig  = h($_POST['anlass_sonstig'] ?? '');
$anlass          = ($anlass_raw === 'Sonstiges' && $anlass_sonstig)
                   ? 'Sonstiges: ' . $anlass_sonstig
                   : $anlass_raw;
$datum          = h($_POST['datum'] ?? '');
$uhrzeit        = h($_POST['uhrzeit'] ?? '');
$g1 = h($_POST['geschmack_1'] ?? '');
$g2 = h($_POST['geschmack_2'] ?? '');
$geschmack_arr = array_filter([$g1, $g2]);
if (empty($geschmack_arr) && isset($_POST['geschmack'])) {
    $geschmack_arr = array_map('htmlspecialchars', (array)$_POST['geschmack']);
}
$geschmack = implode(', ', $geschmack_arr);
$form_shape     = h($_POST['form'] ?? '');
$groesse        = h($_POST['groesse'] ?? '');
$farbe          = h($_POST['farbe'] ?? '');
$schriftzug     = h($_POST['schriftzug'] ?? '');
$verzierung     = h($_POST['verzierung'] ?? '');
$allergien_hidden = h($_POST['allergien_hidden'] ?? '');
if ($allergien_hidden) {
    $allergien = $allergien_hidden;
} elseif (isset($_POST['allergien'])) {
    $allergien = implode(', ', array_map('h', (array)$_POST['allergien']));
} else {
    $allergien = 'Keine Angabe';
}
$allergie_sonst = h($_POST['allergie_sonstig'] ?? '');
$anmerkungen    = h($_POST['anmerkungen'] ?? '');

function h($s) { return htmlspecialchars(trim($s)); }

if (!$email || !$vorname || !$nachname || !$anlass || !$datum || !$geschmack || !$groesse) {
    showPage('error', $lang); exit;
}

$name = "$vorname $nachname";
$ts = strtotime($datum);
if (!$ts) { // try DD.MM.YYYY
    $parts = explode('.', $datum);
    if (count($parts) === 3) $ts = mktime(0,0,0,(int)$parts[1],(int)$parts[0],(int)$parts[2]);
}
$datum_fmt = $ts ? date('d.m.Y', $ts) : $datum;
$ref = 'NQ-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 5));

// Colors
$hdr = 'background:#E91E8C';
$foot = 'background:#E91E80';
$acc = '#00BCD4';
$soft = '#E0F7FA';
$lbl = 'padding:6px 0;color:#999;width:140px;font-size:13px';
$val = 'padding:6px 0;font-size:13px';
$sep = 'padding:10px 0 4px;border-top:1px solid #eee;color:#E91E80;font-weight:600;font-size:13px';

// ---- EMAIL TO YOU ----
$subj = "🎂 Tortenanfrage von $name — $datum_fmt";
$body = "<html><body style='font-family:Arial,sans-serif;color:#1A1A1A;max-width:600px'>
<div style='$hdr;padding:20px;border-radius:12px 12px 0 0'>
<h2 style='color:#fff;margin:0;font-size:18px'>🎂 Neue Tortenanfrage</h2>
<p style='color:rgba(255,255,255,.75);margin:4px 0 0;font-size:13px'>Referenz: $ref</p></div>
<div style='background:#fff;padding:20px;border:1px solid #eee;border-top:none;border-radius:0 0 12px 12px'>
<table style='width:100%;border-collapse:collapse'>
<tr><td colspan='2' style='$sep'>Kunde</td></tr>
<tr><td style='$lbl'>Name:</td><td style='$val'><strong>$name</strong></td></tr>
<tr><td style='$lbl'>E-Mail:</td><td style='$val'><a href='mailto:$email' style='color:$acc'>$email</a></td></tr>
<tr><td style='$lbl'>Telefon:</td><td style='$val'>$phone</td></tr>"
.($adresse?"<tr><td style='$lbl'>Adresse:</td><td style='$val'>$adresse</td></tr>":'')
."<tr><td colspan='2' style='$sep'>Abholung</td></tr>
<tr><td style='$lbl'>Anlass:</td><td style='$val'>$anlass</td></tr>
<tr><td style='$lbl'>Datum/Uhrzeit:</td><td style='$val'><strong>$datum_fmt, $uhrzeit</strong></td></tr>
<tr><td colspan='2' style='$sep'>Torte</td></tr>
<tr><td style='$lbl'>Geschmack:</td><td style='$val'><strong>$geschmack</strong></td></tr>
<tr><td style='$lbl'>Form:</td><td style='$val'>".($form_shape?:'-')."</td></tr>
<tr><td style='$lbl'>Größe/Personen:</td><td style='$val'>$groesse</td></tr>
<tr><td style='$lbl'>Farbe:</td><td style='$val'>".($farbe?:'-')."</td></tr>
<tr><td style='$lbl'>Schriftzug:</td><td style='$val'>".($schriftzug?:'-')."</td></tr>
<tr><td style='$lbl'>Verzierung:</td><td style='$val'>".($verzierung?:'-')."</td></tr>
<tr><td colspan='2' style='$sep'>Allergien</td></tr>
<tr><td style='$lbl'>Allergien:</td><td style='$val'>$allergien".($allergie_sonst?" ($allergie_sonst)":'')."</td></tr>
</table>"
.($anmerkungen?"<div style='margin-top:12px;padding:10px;background:$soft;border-radius:8px;font-size:13px'><strong>Anmerkungen:</strong><br>$anmerkungen</div>":'')
."</div></body></html>";

$boundary = md5(time());
$hdrs = "From: $BAKERY_NAME <$BAKERY_EMAIL>\r\nReply-To: $email\r\nMIME-Version: 1.0\r\n";

$has_files = !empty($_FILES['inspiration']['name'][0]);
if ($has_files) {
    $hdrs .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
    $msg = "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 7bit\r\n\r\n$body\r\n\r\n";
    $fc = min(count($_FILES['inspiration']['name']), 5);
    for ($i=0;$i<$fc;$i++) {
        if ($_FILES['inspiration']['error'][$i]===UPLOAD_ERR_OK && $_FILES['inspiration']['size'][$i]<=5242880) {
            $fn=$_FILES['inspiration']['name'][$i]; $ft=$_FILES['inspiration']['type'][$i];
            $fd=file_get_contents($_FILES['inspiration']['tmp_name'][$i]);
            $msg.="--$boundary\r\nContent-Type: $ft; name=\"$fn\"\r\nContent-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"$fn\"\r\n\r\n".chunk_split(base64_encode($fd))."\r\n";
        }
    }
    $msg.="--$boundary--\r\n";
    mail($BAKERY_EMAIL,$subj,$msg,$hdrs);
} else {
    $hdrs .= "Content-Type: text/html; charset=UTF-8\r\n";
    mail($BAKERY_EMAIL,$subj,$body,$hdrs);
}

// ---- CONFIRMATION TO CUSTOMER ----
$en = ($lang==='en');
$cs = $en ? "Your cake inquiry — $BAKERY_NAME (Ref: $ref)" : "Ihre Tortenanfrage — $BAKERY_NAME (Ref: $ref)";
$cg = $en ? "Dear $name" : "Liebe/r $name";
$ct = $en
    ? "Thank you for your cake inquiry! We have received your request and will get back to you within 48 hours."
    : "Vielen Dank für Ihre Tortenanfrage! Wir haben Ihre Anfrage erhalten und melden uns innerhalb von 48 Stunden.";
$notice = $en
    ? "<strong>Please note: This is an inquiry only!</strong> The order is only confirmed upon explicit confirmation from our side (orders placed in person at the shop, by phone, or electronically are excluded from this)."
    : "<strong>Das ist eine Anfrage!</strong> Die Bestellung gilt als bestätigt nur bei einer expliziten Rückmeldung von unserer Seite (persönliche Bestellungen im Shop, telefonisch oder elektronisch sind davon ausgenommen).";
$stitle = $en ? "Your Inquiry Summary" : "Ihre Anfrage im Überblick";
$rows = [
    ($en?'Name':'Name') => $name,
    ($en?'Email':'E-Mail') => $email,
    ($en?'Phone':'Telefon') => ($phone ?: '-'),
    ($en?'Address':'Adresse') => ($adresse ?: '-'),
    ($en?'Occasion':'Anlass') => $anlass,
    ($en?'Pickup Date / Time':'Abholung / Uhrzeit') => "$datum_fmt, $uhrzeit",
    ($en?'Flavor':'Geschmack') => $geschmack,
    ($en?'Size':'Größe') => $groesse,
    ($en?'Shape':'Form') => ($form_shape ?: '-'),
    ($en?'Colour':'Farbe') => ($farbe ?: '-'),
    ($en?'Inscription':'Schriftzug') => ($schriftzug ?: '-'),
    ($en?'Decoration':'Verzierung') => ($verzierung ?: '-'),
    ($en?'Allergies / intolerances':'Allergien / Unverträglichkeiten') => ($allergien . ($allergie_sonst ? ' (' . $allergie_sonst . ')' : '')),
    ($en?'Notes':'Anmerkungen') => ($anmerkungen ?: '-'),
];
$tbl='';foreach($rows as $k=>$v) $tbl.="<tr><td style='padding:4px 0;color:#999;width:120px;font-size:13px'>$k:</td><td style='padding:4px 0;font-size:13px'>$v</td></tr>";
$cq = $en?"Questions? Contact us:":"Fragen? Kontaktieren Sie uns:";
$cc = $en?"We look forward to creating your dream cake!":"Wir freuen uns darauf, Ihre Traumtorte zu gestalten!";
$cteam = $en?"Your $BAKERY_NAME Team":"Ihr $BAKERY_NAME Team";

$cbody="<html><body style='font-family:Arial,sans-serif;color:#1A1A1A;max-width:600px;margin:0 auto'>
<div style='$hdr;padding:22px;border-radius:12px 12px 0 0;text-align:center'>
<h1 style='color:#fff;margin:0;font-size:20px;letter-spacing:2px'>NUNIQUE</h1>
<p style='color:rgba(255,255,255,.7);margin:3px 0 0;font-size:12px'>Cakes & Coffee</p></div>
<div style='background:#fff;padding:22px;border:1px solid #eee;border-top:none'>
<p style='font-size:15px'>$cg,</p>
<p style='color:#4A4A4A;line-height:1.6;font-size:14px'>$ct</p>
<p style='color:#999;font-size:12px;margin-top:8px'>Ref: <strong>$ref</strong></p>
<div style='background:$soft;border-radius:10px;padding:15px;margin:16px 0'>
<h3 style='color:#0097A7;margin:0 0 10px;font-size:14px'>$stitle</h3>
<table style='width:100%'>$tbl</table></div>
<div style='background:#FFF3E0;border-left:4px solid #E91E8C;border-radius:0 8px 8px 0;padding:12px 14px;margin:16px 0;font-size:13px;color:#333;line-height:1.6'>
$notice</div>
<p style='color:#4A4A4A;font-size:14px'>$cc</p>
<div style='margin-top:16px;padding-top:12px;border-top:1px solid #eee'>
<p style='color:#999;font-size:12px;margin:0'>$cq</p>
<p style='font-size:12px;margin:4px 0'>📧 <a href='mailto:$BAKERY_EMAIL' style='color:$acc'>$BAKERY_EMAIL</a></p>
<p style='font-size:12px;margin:4px 0'>📱 $BAKERY_PHONE</p>
<p style='font-size:12px;margin:4px 0'>🌐 <a href='$BAKERY_URL' style='color:$acc'>$BAKERY_URL</a></p></div></div>
<div style='$foot;padding:12px;border-radius:0 0 12px 12px;text-align:center'>
<p style='color:rgba(255,255,255,.8);margin:0;font-size:12px'>$cteam</p></div>
</body></html>";

$ch="From: $BAKERY_NAME <$BAKERY_EMAIL>\r\nReply-To: $BAKERY_EMAIL\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
mail($email,$cs,$cbody,$ch);

showPage('success',$lang,$name,$ref);

function showPage($type,$lang,$name='',$ref='') {
    global $BAKERY_PHONE,$BAKERY_EMAIL,$BAKERY_URL;
    $en=($lang==='en');
    if($type==='success'){
        $title=$en?'Thank You!':'Vielen Dank!';$icon='✦';
        $msg=$en?"Dear $name, your cake inquiry has been received! A confirmation has been sent to your email. We'll get back to you within 48 hours.":"Liebe/r $name, Ihre Tortenanfrage ist bei uns eingegangen! Eine Bestätigung wurde an Ihre E-Mail gesendet. Wir melden uns innerhalb von 48 Stunden.";
        $rl=$en?'Your reference':'Ihre Referenz';
        $urg=$en?"Urgent? Call us: $BAKERY_PHONE":"Dringend? Rufen Sie uns an: $BAKERY_PHONE";
        $grad='#E91E8C';
    } else {
        $title=$en?'Something went wrong':'Etwas ist schiefgelaufen';$icon='⚠️';
        $msg=$en?'Please check your entries and try again.':'Bitte überprüfen Sie Ihre Angaben und versuchen Sie es erneut.';
        $rl='';$ref='';$urg=$en?"Contact: $BAKERY_EMAIL":"Kontakt: $BAKERY_EMAIL";$grad='#D32F2F';
    }
    $back=$en?'← Back to form':'← Zurück zum Formular';
    $home=$en?'Go to homepage':'Zur Startseite';
    $page_notice = $type==='success' ? (
        $en
        ? "<div style='background:#FFF3E0;border-left:3px solid #E91E8C;border-radius:0 6px 6px 0;padding:10px 12px;margin:12px 0;font-size:.78rem;color:#333;line-height:1.6;text-align:left'><strong>Please note:</strong> This is an inquiry only! The order is only confirmed upon explicit confirmation from our side.</div>"
        : "<div style='background:#FFF3E0;border-left:3px solid #E91E8C;border-radius:0 6px 6px 0;padding:10px 12px;margin:12px 0;font-size:.78rem;color:#333;line-height:1.6;text-align:left'><strong>Bitte beachten:</strong> Dies ist eine Anfrage! Die Bestellung gilt als bestätigt nur bei einer expliziten Rückmeldung von unserer Seite.</div>"
    ) : '';
    echo "<!DOCTYPE html><html lang='$lang'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'><title>$title — NUNIQUE</title>
<link href='https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600&family=Outfit:wght@300;500&display=swap' rel='stylesheet'>
<style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Outfit',sans-serif;font-weight:300;background:#FAFAFA;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem}
.card{background:#fff;border:1px solid #e0e0e0;border-radius:16px;max-width:460px;width:100%;text-align:center;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.06);animation:si .4s ease-out}
.card-top{background:$grad;padding:2rem}.card-icon{font-size:2.5rem;margin-bottom:.5rem}
.card-top h1{font-family:'Cormorant Garamond',serif;color:#fff;font-size:1.5rem;font-weight:400;letter-spacing:.04em}
.card-body{padding:2rem}.card-body p{color:#4A4A4A;line-height:1.7;font-size:.92rem}
.ref{display:inline-block;background:#E0F7FA;border:1px solid #b2ebf2;padding:.35rem 1rem;border-radius:50px;font-size:.78rem;color:#0097A7;margin:1rem 0;font-weight:500}
.urg{font-size:.82rem;color:#999;margin-top:1.2rem;padding-top:.8rem;border-top:1px solid #eee}
.links{display:flex;gap:1rem;justify-content:center;margin-top:1.2rem;flex-wrap:wrap}
.links a{color:#00BCD4;text-decoration:none;font-size:.82rem;font-weight:500;transition:color .2s}.links a:hover{color:#E91E80}
@keyframes si{from{opacity:0;transform:scale(.95)}to{opacity:1;transform:scale(1)}}</style></head>
<body><div class='card'><div class='card-top'><div class='card-icon'>$icon</div><h1>$title</h1></div>
<div class='card-body'><p>$msg</p>".($ref?"<div class='ref'>$rl: <strong>$ref</strong></div>":'')."$page_notice<p class='urg'>$urg</p>
<div class='links'><a href='order'>$back</a><a href='$BAKERY_URL'>$home</a></div></div></div></body></html>";
}
?>
