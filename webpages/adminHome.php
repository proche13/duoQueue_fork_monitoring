<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: home.php");
    exit;
}


$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'duoqueue';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed.");
}

$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

$totalBanned = $pdo->query("SELECT COUNT(*) FROM users WHERE is_banned = 1")->fetchColumn();

$totalGames = $pdo->query("SELECT COUNT(*) FROM available_games")->fetchColumn();

$totalReports = $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DuoQueue - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/arcade-theme.css">
</head>

<body>

    <nav class="arcade-nav">
        <div class="d-flex flex-wrap justify-content-center gap-2 gap-md-3">
            <a href="adminHome.php" class="nav-link">Home</a>
            <a href="manageGames.php" class="nav-link">Games</a>
            <a href="managePlatforms.php" class="nav-link">Platforms</a>
            <a href="moderation.php" class="nav-link">Moderation</a>
            <a href="search.php" class="nav-link">Search</a>
            <a href="logout.php" class="nav-link">Logout</a>
        </div>
    </nav>

    <div class="arcade-screen px-3 d-flex flex-column align-items-center justify-content-center">

        <h2 class="text-glow mb-4" style="font-size: clamp(10px, 1.3vw, 16px);">Welcome back, Admin!</h2>

        <div class="d-flex flex-wrap justify-content-center gap-4 mb-4">
            <div class="stat-box d-flex flex-column align-items-center">
                <span class="stat-number"><?= $totalUsers ?></span>
                <span class="stat-label">Users</span>
            </div>
            <div class="stat-box d-flex flex-column align-items-center">
                <span class="stat-number"><?= $totalBanned ?></span>
                <span class="stat-label">Banned</span>
            </div>
            <div class="stat-box d-flex flex-column align-items-center">
                <span class="stat-number"><?= $totalGames ?></span>
                <span class="stat-label">Games</span>
            </div>
            <div class="stat-box d-flex flex-column align-items-center">
                <span class="stat-number"><?= $totalReports ?></span>
                <span class="stat-label">Reports</span>
            </div>
        </div>

        <div class="d-flex flex-column gap-3 w-100" style="max-width: 300px;">
            <a href="moderation.php" class="btn-arcade btn-arcade-cyan text-center text-decoration-none d-block p-3" style="font-size: clamp(8px, 0.9vw, 11px);">Moderate Users</a>
            <a href="search.php" class="btn-arcade btn-arcade-cyan text-center text-decoration-none d-block p-3" style="font-size: clamp(8px, 0.9vw, 11px);">Search Users</a>
            <a href="manageGames.php" class="btn-arcade btn-arcade-cyan text-center text-decoration-none d-block p-3" style="font-size: clamp(8px, 0.9vw, 11px);">Manage Games</a>
            <a href="managePlatforms.php" class="btn-arcade btn-arcade-cyan text-center text-decoration-none d-block p-3" style="font-size: clamp(8px, 0.9vw, 11px);">Manage Platforms</a>
        </div>

    </div>

</body>
</html>