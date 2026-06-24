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

if (isset($_GET['delete'])) {
    $platform_id = $_GET['delete'];

    $stmt = $pdo->prepare("DELETE FROM available_platforms WHERE platform_id = :id");
    $stmt->execute(['id' => $platform_id]);

    header("Location: managePlatforms.php");
    exit();
}

if (isset($_POST['add_platform'])) {
    $platform_name = trim($_POST['platform_name']);

    if (!empty($platform_name)) {

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM available_platforms WHERE platform_name = :name");
        $stmt->execute(['name' => $platform_name]);

        if ($stmt->fetchColumn() > 0) {
            $_SESSION['message'] = "Platform already exists.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO available_platforms (platform_name) VALUES (:name)");
            $stmt->execute(['name' => $platform_name]);

            $_SESSION['message'] = "Platform added successfully!";
        }

    } else {
        $_SESSION['message'] = "Platform name cannot be empty.";
    }

    header("Location: managePlatforms.php");
    exit();
}

$stmt = $pdo->query("SELECT * FROM available_platforms ORDER BY platform_name ASC");
$platforms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DuoQueue - Manage Platforms</title>
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
            <div class="col-12 col-lg-8">

                <div class="card arcade-card mb-3">
                    <div class="card-body p-4">
                        <h2 class="card-title text-center mb-4" style="font-size: clamp(12px, 1.3vw, 16px);">Manage Platforms</h2>

                        <form method="POST">
                            <div class="d-flex gap-2">
                                <input type="text" name="platform_name" placeholder="Enter platform name"
                                    class="form-control arcade-input flex-fill" required>
                                <button type="submit" name="add_platform" class="btn-arcade btn-arcade-cyan"
                                    style="font-size: 10px; white-space: nowrap; padding: 10px 16px;">Add Platform</button>
                            </div>
                        </form>

                        <?php if (isset($_SESSION['message'])): ?>
                            <p class="arcade-success text-center mt-3"><?= htmlspecialchars($_SESSION['message']) ?></p>
                            <?php unset($_SESSION['message']); ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card arcade-card">
                    <div class="card-body p-4">
                        <h3 class="card-title mb-3" style="font-size: clamp(10px, 1.1vw, 13px);">Available Platforms</h3>

                        <?php if (empty($platforms)): ?>
                            <p style="font-size: 10px; color: rgba(255,255,255,0.6);">No platforms added yet.</p>
                        <?php else: ?>
                            <div class="neon-box p-2" style="max-height: 45vh; overflow-y: auto;">
                                <?php foreach ($platforms as $platform): ?>
                                    <div class="d-flex justify-content-between align-items-center py-2 px-2"
                                        style="border-bottom: 1px solid rgba(0, 255, 255, 0.2); font-size: 10px;">
                                        <span><?= htmlspecialchars($platform['platform_name']) ?></span>
                                        <a href="?delete=<?= $platform['platform_id'] ?>"
                                            class="btn-arcade btn-arcade-danger"
                                            style="font-size: 8px; padding: 4px 10px; text-decoration: none;"
                                            onclick="return confirm('Delete this platform?');">
                                            Remove
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>
