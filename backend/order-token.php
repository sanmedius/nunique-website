<?php
require __DIR__ . '/form-security.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, private');
echo json_encode(nq_issue_order_security(), JSON_UNESCAPED_UNICODE);
