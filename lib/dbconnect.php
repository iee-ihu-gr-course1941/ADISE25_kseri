<?php
require_once 'credentials.php';

// Create connection with timeout
$mysqli = mysqli_init();
if (!$mysqli) {
    http_response_code(500);
    echo json_encode(['error' => 'mysqli_init failed']);
    exit;
}

$mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

if (!$mysqli->real_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, null, $DB_SOCKET)) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database connection failed: ' . $mysqli->connect_error
    ]);
    exit;
}

$mysqli->set_charset("utf8mb4");
?>