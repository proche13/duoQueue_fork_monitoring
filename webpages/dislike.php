<?php
session_start();

// db connection
$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'duoqueue';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

//Check if user is logged in
if (!isset($_SESSION["user_id"])) {
   header("Location: login.php");
    exit();
}

$currentUserId = $_SESSION["user_id"];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disliked_user_id'])) {
    $dislikedUserId = (int)$_POST['disliked_user_id'];
    
    if ($dislikedUserId === $currentUserId) {
        header("Location: matchmake.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO dislikes (user_id, disliked_user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE created_timestamp = CURRENT_TIMESTAMP");
        $stmt->execute([$currentUserId, $dislikedUserId]);

        header("Location: matchmake.php");
        exit();

    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
} else {
    header("Location: matchmake.php");
    exit();
}
?>
