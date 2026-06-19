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

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $_SESSION['user_id'];
$is_own_profile = ($user_id == $_SESSION['user_id']);

$sql = "SELECT 
            u.first_name,
            u.last_name,
            u.email,
            p.location,
            p.profile_photo,
            p.date_of_birth,
            p.gender,
            p.seeking,
            p.about_me,
            p.smoker,
            p.drinker
        FROM users u
        LEFT JOIN user_profiles p ON u.user_id = p.user_id
        WHERE u.user_id = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$profile = $result->fetch_assoc();

$stmt->close();

$sql_games = "SELECT ag.game_name
              FROM users_games ug
              JOIN available_games ag ON ug.game_id = ag.game_id
              WHERE ug.user_id = ?
              LIMIT 5";

$stmt_games = $conn->prepare($sql_games);

if (!$stmt_games) {
    die("Prepare failed (games): " . $conn->error);
}

$stmt_games->bind_param("i", $user_id);
$stmt_games->execute();
$result_games = $stmt_games->get_result();

$games = [];
while ($row = $result_games->fetch_assoc()) {
    $games[] = $row['game_name'];
}

$stmt_games->close();

$pictures = [];
$sql_pictures = "SELECT photo FROM user_photos WHERE user_id = ?";
$stmt_pictures = $conn->prepare($sql_pictures);
$stmt_pictures->bind_param("i", $user_id);
$stmt_pictures->execute();
$result_pictures = $stmt_pictures->get_result();

while ($row = $result_pictures->fetch_assoc()) {
    $pictures[] = $row['photo'];
}
$stmt_pictures->close();

$sql_platforms = "SELECT ap.platform_name, up.platform_username
                  FROM user_platforms up
                  JOIN available_platforms ap ON up.platform_id = ap.platform_id
                  WHERE up.user_id = ?";

$stmt_platforms = $conn->prepare($sql_platforms);

if (!$stmt_platforms) {
    die("Prepare failed (platforms): " . $conn->error);
}

$stmt_platforms->bind_param("i", $user_id);
$stmt_platforms->execute();
$result_platforms = $stmt_platforms->get_result();

$platforms = [];
while ($row = $result_platforms->fetch_assoc()) {
    $platforms[] = $row;
}
$stmt_platforms->close();
$conn->close();



$age = "";
if (!empty($profile['date_of_birth'])) {
    $dob = new DateTime($profile['date_of_birth']);
    $today = new DateTime();
    $age = $today->diff($dob)->y;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DuoQueue - Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/arcade-theme.css">
</head>

<body>

    <nav class="arcade-nav">
        <div class="d-flex flex-wrap justify-content-center gap-2 gap-md-3">
            <?php if (!empty($_SESSION['is_admin'])): ?>
                <a href="adminHome.php" class="nav-link">Home</a>
                <a href="manageGames.php" class="nav-link">Games</a>
                <a href="managePlatforms.php" class="nav-link">Platforms</a>
                <a href="moderation.php" class="nav-link">Moderation</a>
                <a href="search.php" class="nav-link">Search</a>
            <?php else: ?>
                <a href="home.php" class="nav-link">Home</a>
                <?php if (!$is_own_profile): ?>
                    <a href="profilepage.php" class="nav-link">Profile</a>
                <?php endif; ?>
                <?php if ($is_own_profile): ?>
                    <a href="profile.php" class="nav-link">Edit Profile</a>
                <?php endif; ?>
                <a href="matchmake.php" class="nav-link">Matchmake</a>
                <a href="matches.php" class="nav-link">My Duos</a>
                <a href="search.php" class="nav-link">Search</a>
                <a href="aboutus.php" class="nav-link">About Us</a>
            <?php endif; ?>
            <a href="logout.php" class="nav-link">Logout</a>
        </div>
    </nav>

    <div class="arcade-screen p-x3">
        <div class="row g-4">

            <div class="col-lg-4">
                <div class="card arcade-card mb-4">
                    <div class="card-body text-center">
                        <?php if (!empty($profile['profile_photo'])): ?>
                            <img src="<?= htmlspecialchars($profile['profile_photo']) ?>"
                                alt="Profile Photo"
                                class="img-fluid mb-3" style="border-radius: 10px;">
                        <?php else: ?>
                            <img src="assets/296fe121-5dfa-43f4-98b5-db50019738a7.jpg"
                                alt="Default Profile Photo"
                                class="img-fluid rounded-circle mb-3">
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card arcade-card mb-4">
                    <div class="card-body">
                        <h2 class="card-title">
                            <?= htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']) ?>
                        </h2>
                        <p class="mb-1"><strong>Age:</strong> <?= htmlspecialchars($age !== "" ? $age : "Not specified") ?></p>
                        <p class="mb-1"><strong>Location:</strong> <?= htmlspecialchars($profile['location'] ?? "Not specified") ?></p>
                        <p class="mb-1"><strong>Gender:</strong> <?= htmlspecialchars($profile['gender'] ?? "Not specified") ?></p>
                        <p class="mb-1"><strong>Seeking:</strong> <?= htmlspecialchars($profile['seeking'] ?? "Not specified") ?></p>
                        <p class="mb-1"><strong>Smoker:</strong> <?= isset($profile['smoker']) ? ($profile['smoker'] ? "Yes" : "No") : "Not specified" ?></p>
                        <p class="mb-1"><strong>Drinker:</strong> <?= isset($profile['drinker']) ? ($profile['drinker'] ? "Yes" : "No") : "Not specified" ?></p>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card arcade-card mb-4">
                    <div class="card-body">
                        <h3 class="card-title">About Me</h3>
                        <p><?= nl2br(htmlspecialchars($profile['about_me'] ?? "No bio provided.")) ?></p>
                    </div>
                </div>

                <div class="card arcade-card mb-4">
                    <div class="card-body">
                        <h3 class="card-title">Favorite Games</h3>
                        <?php if (!empty($games)): ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($games as $game): ?>
                                    <li class="list-group-item"><?= htmlspecialchars($game) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No games listed.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-lg-6">
                <div class="card arcade-card mb-4">
                    <div class="card-body">
                        <h3 class="card-title">Photo Gallery</h3>
                        <?php if (!empty($pictures)): ?>
                            <div class="row g-3 mb-3">
                                <?php foreach (array_slice($pictures, 0, 2) as $picture): ?>
                                    <div class="col-6">
                                        <img src="<?= htmlspecialchars($picture) ?>" alt="User Photo" class="img-fluid rounded">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($pictures) > 2): ?>
                                <button type="button" class="btn-arcade-gallery" data-bs-toggle="modal" data-bs-target="#photoGalleryModal">
                                    View All Photos
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <p>No photos uploaded.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card arcade-card mb-4">
                    <div class="card-body">
                        <h3 class="card-title">Platforms</h3>
                        <?php if (!empty($platforms)): ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($platforms as $platform): ?>
                                    <li class="list-group-item">
                                        <strong><?= htmlspecialchars($platform['platform_name']) ?></strong>
                                        <?php if (!empty($platform['platform_username'])): ?>
                                            - <?= htmlspecialchars($platform['platform_username']) ?>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No platforms listed.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php if (!empty($_SESSION['is_admin']) && !$is_own_profile): ?>
            <div class="row mt-3 mb-3">
                <div class="col-12 text-center">
                    <a href="profile.php?user_id=<?= $user_id ?>" class="text-decoration-none">
                        <button class="btn-arcade btn-arcade-cyan" style="font-size: 10px; padding: 12px 30px;">Edit Profile</button>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="modal fade" id="photoGalleryModal" tabindex="-1" aria-labelledby="photoGalleryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content arcade-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="photoGalleryModalLabel">Photo Gallery</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($pictures)): ?>
                        <div class="row g-3">
                            <?php foreach ($pictures as $picture): ?>
                                <div class="col-md-6">
                                    <img src="<?= htmlspecialchars($picture) ?>" alt="User Photo" class="img-fluid rounded">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No photos uploaded.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
