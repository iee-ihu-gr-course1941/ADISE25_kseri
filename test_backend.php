<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$mysqli = new mysqli(
	null,
	"it185328",
	"!Loveispain0405!",
	"adise_project_db",
	0,
	"/home/student/it/2018/it185328/mysql/run/mysql.sock"
);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$result = $mysqli->query("SELECT * FROM test_table");

echo "<h1>Test Table Data</h1><ul>";
while($row = $result->fetch_assoc()) {
    echo "<li>ID: {$row['id']}, Name: {$row['name']}, Value: {$row['value']}</li>";
}
echo "</ul>";

$mysqli->close();
?>
