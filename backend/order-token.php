<?php
$config = require __DIR__ . '/config.php';
require __DIR__ . '/form-security.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, private');
$security = nq_issue_order_security();
$security['turnstileSiteKey'] = (string)($config['turnstile_site_key'] ?? '');
echo json_encode($security, JSON_UNESCAPED_UNICODE);
