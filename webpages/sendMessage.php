<?php
session_start();
header('Content-Type: application/json');

$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'duoqueue';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in.']);
    exit;
}

$user_id  = $_SESSION['user_id'];
$match_id = $_POST['match_id'] ?? null;
$message  = trim($_POST['message'] ?? '');

if (!$match_id || $message === '') {
    echo json_encode(['success' => false, 'error' => 'Missing data.']);
    exit;
}

$normalized = preg_replace('/[\s\-().]/', '', $message);
if (preg_match('/(\+?\d{1,3})?\d{9,}/', $normalized)) {
    echo json_encode(['success' => false, 'error' => 'Sharing phone numbers is not allowed.']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT user1_id, user2_id 
    FROM matches 
    WHERE match_id = ? 
    AND (user1_id = ? OR user2_id = ?)
");
$stmt->execute([$match_id, $user_id, $user_id]);
$match = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$match) {
    echo json_encode(['success' => false, 'error' => 'Invalid match.']);
    exit;
}

$receiver_id = ($match['user1_id'] == $user_id)
    ? $match['user2_id']
    : $match['user1_id'];

$stmt = $pdo->prepare("
    INSERT INTO messages (match_id, message, sender_id, receiver_id)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([$match_id, $message, $user_id, $receiver_id]);

require_once '../config/Metrics.php';
$metrics = new Metrics();
$metrics->counter('duoqueue_messages_total', 1, 'Total messages sent');

echo json_encode(['success' => true]);
exit;