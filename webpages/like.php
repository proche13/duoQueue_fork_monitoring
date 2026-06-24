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

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
   header("Location: login.php");
    exit();
}

$currentUserId = $_SESSION["user_id"];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['liked_user_id'])) {
    $likedUserId = (int)$_POST['liked_user_id'];

    if ($likedUserId === $currentUserId) {
        header("Location: matchmake.php");
        exit();
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO likes (user_id, liked_user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE created_timestamp = CURRENT_TIMESTAMP");
        $stmt->execute([$currentUserId, $likedUserId]);

        $stmt = $pdo->prepare("SELECT like_id FROM likes WHERE user_id = ? AND liked_user_id = ?");
        $stmt->execute([$likedUserId, $currentUserId]);
        $mutualLike = $stmt->fetch(PDO::FETCH_ASSOC);

        $isMatch = false;
        $matchedUserName = '';

        if ($mutualLike) {
            $stmt = $pdo->prepare("INSERT INTO matches (user1_id, user2_id) VALUES (?, ?)");
            $stmt->execute([min($currentUserId, $likedUserId), max($currentUserId, $likedUserId)]);

            $stmt = $pdo->prepare("UPDATE likes SET status = 'MATCHED' WHERE (user_id = ? AND liked_user_id = ?) OR (user_id = ? AND liked_user_id = ?)");
            $stmt->execute([$currentUserId, $likedUserId, $likedUserId, $currentUserId]);

            $isMatch = true;

            $stmt = $pdo->prepare("SELECT first_name FROM users WHERE user_id = ?");
            $stmt->execute([$likedUserId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $matchedUserName = $user['first_name'];
        }

        $pdo->commit();

        require_once '../config/Metrics.php';
        $metrics = new Metrics();
        if ($isMatch) {
            $metrics->counter('duoqueue_matches_total', 1, 'Total matches made');
        }
        $metrics->counter('duoqueue_likes_total', 1, 'Total likes given');

        if ($isMatch) {
            header("Location: matchmake.php?matched=true&name=" . urlencode($matchedUserName));
        } else {
            header("Location: matchmake.php");
        }
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Database error: " . $e->getMessage());
    }
} else {
    header("Location: matchmake.php");
    exit();
}
?>
