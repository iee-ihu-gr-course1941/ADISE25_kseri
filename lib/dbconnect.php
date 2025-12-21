<?php
require_once 'credentials.php';

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, null, $DB_SOCKET);

if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database connection failed'
    ]);
    exit;
}

$mysqli->set_charset("utf8mb4");
