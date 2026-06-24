<?php

$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_NAME = getenv('DB_NAME') ?: 'my_database';
$DB_USER = getenv('DB_USER') ?: 'duo_queue_admin';
$DB_PASS = getenv('DB_PASS') ?: 'gamingforever123';
$DB_PORT = getenv('DB_PORT') ?: 3306;

$DB_DSN = "mysql:host=$DB_HOST;dbname=$DB_NAME;port=$DB_PORT;charset=utf8mb4";

try {
    $pdo = new PDO($DB_DSN, $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

?>