<?php
session_start();

$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'duoqueue';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    exit("Connection failed");
}

if (!isset($_SESSION['user_id']) || !isset($_GET['match_id'])) {
    exit("Unauthorized or missing parameters");
}

$user_id = $_SESSION['user_id'];
$match_id = $_GET['match_id'];

$check = $pdo->prepare("SELECT user1_id, user2_id FROM matches WHERE match_id = ? AND (user1_id = ? OR user2_id = ?)");
$check->execute([$match_id, $user_id, $user_id]);

if ($check->rowCount() === 0) {
    exit("Unauthorized match access");
}


$stmt = $pdo->prepare("
    SELECT * FROM messages 
    WHERE match_id = ? 
    ORDER BY created_timestamp ASC
");
$stmt->execute([$match_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($messages as $message) {
    $class = ($message['sender_id'] == $user_id) ? "sent" : "received";
    
    echo '<div class="message ' . $class . '">';
    echo htmlspecialchars($message['message']);
    echo '</div>';
}