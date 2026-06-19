<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

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

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$limit  = 1;

$gamesStmt = $pdo->prepare("
    SELECT ag.game_id, ag.game_name 
    FROM users_games ug
    JOIN available_games ag ON ug.game_id = ag.game_id
    WHERE ug.user_id = ?
");
$gamesStmt->execute([$userId]);
$userGames = $gamesStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['exclude_games'])) {
    $_SESSION['exclude_games'] = array_map('intval', $_POST['exclude_games']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['liked_user_id'], $_POST['disliked_user_id'])) {
}

$excludedGameIds = $_SESSION['exclude_games'] ?? [];

$excludeParams = [];
if (!empty($excludedGameIds)) {
    $paramNames = [];
    foreach ($excludedGameIds as $i => $gameId) {
        $paramNames[]           = ':excl_' . $i;
        $excludeParams[':excl_' . $i] = $gameId;
    }
    $gamesExclusion = "AND ug1.game_id NOT IN (" . implode(',', $paramNames) . ")";
} else {
    $gamesExclusion = "";
}

$sql = "
    SELECT
        u.user_id,
        u.first_name,
        u.last_name,
        up.profile_photo,
        up.about_me,
        up.location,
        up.gender,
        up.seeking,
        up.date_of_birth,

        (
            COALESCE(games_score.pts, 0)
            + COALESCE(plat_score.pts, 0)
            + IF(up.gender = seeker.seeking, 20, 0)
            + CASE
                WHEN ABS(YEAR(seeker.date_of_birth) - YEAR(up.date_of_birth)) <= 5 THEN 15
                WHEN ABS(YEAR(seeker.date_of_birth) - YEAR(up.date_of_birth)) <= 10 THEN 5
                ELSE 0
              END
            + IF(up.location = seeker.location, 5, 0)
        ) AS match_score

    FROM users u
    JOIN user_profiles up ON u.user_id = up.user_id

    JOIN (
        SELECT gender, seeking, location, date_of_birth
        FROM user_profiles
        WHERE user_id = :uid_seeker
    ) AS seeker ON 1=1

    LEFT JOIN (
        SELECT ug2.user_id, COUNT(*) * 10 AS pts
        FROM users_games ug1
        JOIN users_games ug2 ON ug1.game_id = ug2.game_id
        WHERE ug1.user_id = :uid_games
        $gamesExclusion
        GROUP BY ug2.user_id
    ) AS games_score ON games_score.user_id = u.user_id

    LEFT JOIN (
        SELECT up2.user_id, COUNT(*) * 5 AS pts
        FROM user_platforms up1
        JOIN user_platforms up2 ON up1.platform_id = up2.platform_id
        WHERE up1.user_id = :uid_platforms
        GROUP BY up2.user_id
    ) AS plat_score ON plat_score.user_id = u.user_id

    WHERE u.user_id <> :uid_exclude
        AND u.is_banned = 0
        AND u.is_admin = 0
        AND (up.gender = seeker.seeking OR seeker.seeking = 'other')
        AND (seeker.gender = up.seeking OR up.seeking = 'other')
        AND u.user_id NOT IN (
            SELECT liked_user_id FROM likes WHERE user_id = :uid_likes
        )
        AND u.user_id NOT IN (
            SELECT disliked_user_id FROM dislikes WHERE user_id = :uid_dislikes
        )
        AND u.user_id NOT IN (
            SELECT user2_id FROM matches WHERE user1_id = :uid_matches1
            UNION
            SELECT user1_id FROM matches WHERE user2_id = :uid_matches2
        )

    ORDER BY match_score DESC
    LIMIT :limit";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':uid_seeker',    $userId, PDO::PARAM_INT);
$stmt->bindValue(':uid_games',     $userId, PDO::PARAM_INT);
$stmt->bindValue(':uid_platforms', $userId, PDO::PARAM_INT);
$stmt->bindValue(':uid_exclude',   $userId, PDO::PARAM_INT);
$stmt->bindValue(':uid_likes',     $userId, PDO::PARAM_INT);
$stmt->bindValue(':uid_dislikes',  $userId, PDO::PARAM_INT);
$stmt->bindValue(':uid_matches1',  $userId, PDO::PARAM_INT);
$stmt->bindValue(':uid_matches2',  $userId, PDO::PARAM_INT);
$stmt->bindValue(':limit',         $limit,  PDO::PARAM_INT);

foreach ($excludeParams as $param => $value) {
    $stmt->bindValue($param, $value, PDO::PARAM_INT);
}

$stmt->execute();
$potentialMatch = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DuoQueue - Matchmake</title>
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

    <div class="arcade-screen px-3">

        <?php if (isset($_GET['matched'])): ?>
            <div class="match-notification text-center mb-3">
                <h3>It's a Match with <?= htmlspecialchars($_GET['name']) ?>!</h3>
            </div>
        <?php endif; ?>

        <?php if ($potentialMatch): ?>

            <div class="card arcade-card mb-3">
                <div class="card-body p-3" style="min-height: 35vh;">
                    <div class="row g-4">

                        <div class="col-4">
                            <?php if (!empty($potentialMatch['profile_photo'])): ?>
                                <img src="<?= htmlspecialchars($potentialMatch['profile_photo']) ?>"
                                    alt="Profile Photo" class="img-fluid rounded mb-2"
                                    style="border: 2px solid var(--cyan); max-height: 150px; object-fit: cover;">
                            <?php else: ?>
                                <div class="neon-box d-flex align-items-center justify-content-center mb-2"
                                    style="height: 120px; font-size: 9px; text-align: center;">
                                    No Photo
                                </div>
                            <?php endif; ?>
                            <p class="text-glow mb-1" style="font-size: clamp(9px, 1vw, 12px);">
                                <?= htmlspecialchars($potentialMatch['first_name']) ?>
                            </p>
                            <p class="mb-0" style="font-size: 9px;">
                                <?= htmlspecialchars($potentialMatch['gender'] ?? '') ?>
                            </p>
                        </div>

                        <div class="col-4">
                            <h4 class="mb-2" style="font-size: clamp(8px, 0.9vw, 11px);">About Me</h4>
                            <p style="font-size: 9px; line-height: 1.6; max-height: 140px; overflow-y: auto;">
                                <?= htmlspecialchars($potentialMatch['about_me'] ?? 'No bio provided.') ?>
                            </p>
                        </div>

                        <div class="col-2">
                            <h4 class="mb-2" style="font-size: clamp(8px, 0.9vw, 11px);">Games</h4>
                            <?php
                                $gStmt = $pdo->prepare("SELECT ag.game_name FROM users_games ug JOIN available_games ag ON ug.game_id = ag.game_id WHERE ug.user_id = ? LIMIT 5");
                                $gStmt->execute([$potentialMatch['user_id']]);
                                $matchGames = $gStmt->fetchAll(PDO::FETCH_COLUMN);
                            ?>
                            <?php if (!empty($matchGames)): ?>
                                <?php foreach ($matchGames as $g): ?>
                                    <p class="mb-1" style="font-size: 8px;"><?= htmlspecialchars($g) ?></p>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="font-size: 8px;">None listed.</p>
                            <?php endif; ?>
                        </div>

                        <div class="col-2">
                            <h4 class="mb-2" style="font-size: clamp(8px, 0.9vw, 11px);">Platforms</h4>
                            <?php
                                $pStmt = $pdo->prepare("SELECT ap.platform_name FROM user_platforms up JOIN available_platforms ap ON up.platform_id = ap.platform_id WHERE up.user_id = ?");
                                $pStmt->execute([$potentialMatch['user_id']]);
                                $matchPlatforms = $pStmt->fetchAll(PDO::FETCH_COLUMN);
                            ?>
                            <?php if (!empty($matchPlatforms)): ?>
                                <?php foreach ($matchPlatforms as $pl): ?>
                                    <p class="mb-1" style="font-size: 8px;"><?= htmlspecialchars($pl) ?></p>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="font-size: 8px;">None listed.</p>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>

            <div class="row g-3 p-3 align-items-start">

                <div class="col-4">
                    <form method="POST" action="matchmake.php">
                        <div class="card arcade-card">
                            <div class="card-body p-3">
                                <h4 class="mb-2" style="font-size: clamp(8px, 0.9vw, 11px);">Exclude Games</h4>
                                <?php if (empty($userGames)): ?>
                                    <p style="font-size: 9px;">No games on your profile yet.</p>
                                <?php else: ?>
                                    <?php foreach ($userGames as $game): ?>
                                        <label class="d-block mb-2" style="font-size: 9px; cursor: pointer;">
                                            <input type="checkbox" name="exclude_games[]"
                                                value="<?= $game['game_id'] ?>"
                                                <?= in_array($game['game_id'], $excludedGameIds) ? 'checked' : '' ?>>
                                            <?= htmlspecialchars($game['game_name']) ?>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <button type="submit" class="btn-arcade btn-arcade-cyan w-100 mt-2" style="font-size: 9px; padding: 8px;">Apply</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="col-8 d-flex justify-content-center align-items-center gap-4" style="min-height: 120px;">
                    <form action="like.php" method="POST">
                        <input type="hidden" name="liked_user_id" value="<?= $potentialMatch['user_id'] ?>">
                        <button class="btn-arcade-like px-4 py-3" style="font-size: 12px;">👍 Like</button>
                    </form>
                    <form action="dislike.php" method="POST">
                        <input type="hidden" name="disliked_user_id" value="<?= $potentialMatch['user_id'] ?>">
                        <button class="btn-arcade-dislike px-4 py-3" style="font-size: 12px;">👎 Dislike</button>
                    </form>
                </div>

            </div>

        <?php else: ?>
            <div class="d-flex align-items-center justify-content-center" style="height: 60vh;">
                <div class="match-card">
                    <h2>No Matches Available</h2>
                </div>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>
