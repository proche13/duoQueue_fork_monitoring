<?php
    session_start();
        if (!isset($_SESSION['user_id'])) {
            header("Location: login.php");
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

    $uid = $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM matches WHERE user1_id = ? OR user2_id = ?");
    $stmt->execute([$uid, $uid]);
    $matchCount = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE user_id = ?");
    $stmt->execute([$uid]);
    $likesSent = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$uid]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    $profileComplete = !empty($profile['about_me']) && !empty($profile['profile_photo']);
    ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DuoQueue - Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/arcade-theme.css">
</head>
<body>

    <nav class="arcade-nav">
        <div class="d-flex flex-wrap justify-content-center gap-2 gap-md-3">
            <a href="home.php" class="nav-link">Home</a>
            <a href="profilepage.php" class="nav-link">Profile</a>
            <a href="matchmake.php" class="nav-link">Matchmake</a>
            <a href="matches.php" class="nav-link">My Duos</a>
            <a href="search.php" class="nav-link">Search</a>
            <a href="aboutus.php" class="nav-link">About Us</a>
            <a href="logout.php" class="nav-link">Logout</a>
        </div>
    </nav>

    <div class="arcade-screen d-flex flex-column align-items-center justify-content-center p-3">

        <h2 class="text-glow mb-4" style="font-size: clamp(10px, 1.3vw, 16px);">Welcome back, Player!</h2>

        <?php if (!$profileComplete): ?>
            <div class="arcade-alert text-center p-2 px-4 mb-4">
                Complete your profile to get more matches!
                <a href="profile.php" class="arcade-link ms-2">Edit Profile</a>
            </div>
        <?php endif; ?>

        <div class="d-flex flex-wrap justify-content-center gap-4 mb-4">
            <div class="stat-box d-flex flex-column align-items-center">
                <span class="stat-number"><?= $matchCount ?></span>
                <span class="stat-label">Duos</span>
            </div>
            <div class="stat-box d-flex flex-column align-items-center">
                <span class="stat-number"><?= $likesSent ?></span>
                <span class="stat-label">Likes Sent</span>
            </div>
        </div>

        <div class="d-flex flex-column gap-3 w-100" style="max-width: 300px;">
            <a href="matchmake.php" class="btn-arcade btn-arcade-cyan text-center text-decoration-none d-block p-3" style="font-size: clamp(8px, 0.9vw, 11px);">Start Matchmaking</a>
            <a href="matches.php" class="btn-arcade btn-arcade-cyan text-center text-decoration-none d-block p-3" style="font-size: clamp(8px, 0.9vw, 11px);">My Duos</a>
            <a href="profilepage.php" class="btn-arcade btn-arcade-cyan text-center text-decoration-none d-block p-3" style="font-size: clamp(8px, 0.9vw, 11px);">View Profile</a>
        </div>

    </div>

</body>
</html>