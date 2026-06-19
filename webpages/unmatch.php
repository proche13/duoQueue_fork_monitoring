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
        die("Could not connect to the database.");
    }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['match_id'])) {

    $user_id  = $_SESSION['user_id'];
    $match_id = $_POST['match_id'];

    $stmt = $pdo->prepare("
        DELETE FROM matches 
        WHERE match_id = ? 
        AND (user1_id = ? OR user2_id = ?)
    ");
    $stmt->execute([$match_id, $user_id, $user_id]);

    header("Location: matches.php");
    exit;
}

header("Location: matches.php");
exit;
?>