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
    die("Could not connect to the database. Please try again later.");
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$sql = "
    SELECT 
        r.report_id,
        r.reason,
        r.created_timestamp,
        reported.user_id AS reported_user_id,
        reported.first_name AS reported_name,
        reporter.first_name AS reporter_name
    FROM reports r
    JOIN users reported ON r.reported_user_id = reported.user_id
    JOIN users reporter ON r.reporting_user_id = reporter.user_id
    WHERE reported.is_banned = FALSE
    ORDER BY r.created_timestamp DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DuoQueue - Moderation</title>
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

    <div class="arcade-screen px-3">
        <div class="row justify-content-center">
            <div class="col-12">

                <div class="card arcade-card">
                    <div class="card-body p-0" style="max-height: 68vh; overflow-y: auto;">

                        <?php if (empty($reports)): ?>
                            <p class="text-center text-glow p-4" style="font-size: 10px;">No reports to review.</p>
                        <?php else: ?>
                            <?php foreach ($reports as $report):
                                $reported_username = $report['reported_name'];
                                $reported_user_id = $report['reported_user_id'];
                                $reporting_user = $report['reporter_name'];
                                $reason = $report['reason'];
                                $date = $report['created_timestamp'];
                            ?>
                                <div class="report-row d-flex align-items-center gap-3 px-3">
                                    <img src="../assets/profile.jpg" class="profile-pic flex-shrink-0">
                                    <div class="flex-grow-1 d-flex flex-column gap-1">
                                        <span class="text-glow" style="font-size: clamp(8px, 0.9vw, 11px);"><?= htmlspecialchars($reported_username) ?></span>
                                        <span class="report-detail">Reported by: <span><?= htmlspecialchars($reporting_user) ?></span></span>
                                        <span class="report-detail">Reason: <span><?= htmlspecialchars($reason) ?></span></span>
                                        <span class="report-detail"><?= htmlspecialchars($date) ?></span>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <a href="report.php?report_id=<?= $report['report_id'] ?>" class="text-decoration-none">
                                            <button class="btn-arcade btn-arcade-cyan" style="font-size: 8px; padding: 8px 12px;">View Report</button>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>