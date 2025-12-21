<?php

$host = 'localhost';
$db = 'adise25_db';
$user = 'root';
$pass = '';

// Try to connect to host or localhost
if (gethostname() == 'users.iee.ihu.gr') {
    $mysqli = new mysqli(
        $host,
        $user,
        $pass,
        $db,
            null,
        '/home/student/it/2018/it185328/mysql/run/mysql.sock'
    );
} else {
    $mysqli = new mysqli (
        $host,
        $user,
        $pass,
        $db);
}

if ($mysqli -> connect_errno) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database connection failed'
    ]);
    exit;
}

$mysqli->set_charset("utf8mb4");
