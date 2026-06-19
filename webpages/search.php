<?php
session_start();
$$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'duoqueue';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database. Please try again later.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'ban_user' && !empty($_SESSION['is_admin'])) {
        $banned_user_id = (int)($_POST['ban_user_id'] ?? 0);
        $ban_duration = (int)($_POST['ban_duration'] ?? 30);
        $ban_reason = trim($_POST['ban_reason'] ?? 'Banned by admin from search.');
        $admin_id = $_SESSION['user_id'];

        if ($banned_user_id && $banned_user_id !== $admin_id) {
            $stmt = $pdo->prepare("
                INSERT INTO banned (user_id, admin_id, reason, ban_duration)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    admin_id = VALUES(admin_id),
                    reason = VALUES(reason),
                    ban_duration = VALUES(ban_duration),
                    created_timestamp = CURRENT_TIMESTAMP,
                    deleted_timestamp = NULL
            ");
            $stmt->execute([$banned_user_id, $admin_id, $ban_reason, $ban_duration]);

            $stmt = $pdo->prepare("UPDATE users SET is_banned = TRUE WHERE user_id = ?");
            $stmt->execute([$banned_user_id]);
        }

        header("Location: search.php");
        exit;
    }
}

$gamesStmt = $pdo->query("SELECT game_id, game_name FROM available_games ORDER BY game_name");
$allGames  = $gamesStmt->fetchAll(PDO::FETCH_ASSOC);

$results       = [];
$query         = trim($_GET['query'] ?? '');
$selectedGames = array_map('intval', $_GET['filter_games'] ?? []);

if (!empty($query) || !empty($selectedGames)) {
    $params = [];
    $sql    = "SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.email, u.is_banned
               FROM users u";

    if (!empty($selectedGames)) {
        $sql .= " JOIN users_games ug ON u.user_id = ug.user_id";
    }

    $sql .= " WHERE 1=1";

    if (!empty($query)) {
        $sql             .= " AND CONCAT(u.first_name, ' ', u.last_name) LIKE :query";
        $params[':query'] = '%' . $query . '%';
    }

    if (!empty($selectedGames)) {
        $gameParams = [];
        foreach ($selectedGames as $i => $gameId) {
            $key          = ':game_' . $i;
            $gameParams[] = $key;
            $params[$key] = $gameId;
        }
        $sql .= " AND ug.game_id IN (" . implode(',', $gameParams) . ")";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DuoQueue - User Search</title>
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
                <a href="logout.php" class="nav-link">Logout</a>
            <?php else: ?>
                <a href="home.php" class="nav-link">Home</a>
                <a href="profilepage.php" class="nav-link">Profile</a>
                <a href="matchmake.php" class="nav-link">Matchmake</a>
                <a href="matches.php" class="nav-link">My Duos</a>
                <a href="search.php" class="nav-link">Search</a>
                <a href="aboutus.php" class="nav-link">About Us</a>
                <a href="logout.php" class="nav-link">Logout</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="arcade-screen px-3">
        <form id="searchForm" method="GET" action="search.php">
            <div class="row g-3">

                <div class="col-4 col-md-3">
                    <div class="card arcade-card">
                        <div class="card-body p-3">
                            <h4 class="mb-3" style="font-size: clamp(8px, 0.9vw, 11px);">Filter by Game</h4>

                            <?php if (empty($allGames)): ?>
                                <p style="font-size: 9px;">No games found.</p>
                            <?php else: ?>
                                <input type="text" id="gameSearch" placeholder="Search Games..."
                                    class="form-control arcade-input mb-2" style="font-size: 9px;"
                                    onkeydown="if(event.key==='Enter') event.preventDefault();">

                                <div id="gamesList" class="neon-box p-2" style="max-height: 45vh; overflow-y: auto; font-size: 9px;">
                                    <?php foreach ($allGames as $game): ?>
                                        <label class="game-option mb-2" style="cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                                            <span><?= htmlspecialchars($game['game_name']) ?></span>
                                            <input type="checkbox"
                                                name="filter_games[]"
                                                value="<?= $game['game_id'] ?>"
                                                <?= in_array($game['game_id'], $selectedGames) ? 'checked' : '' ?>>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-8 col-md-9">
                    <div class="card arcade-card">
                        <div class="card-body p-3">
                            <h2 class="card-title text-center mb-3" style="font-size: clamp(12px, 1.3vw, 16px);">User Search</h2>

                            <div class="d-flex gap-2">
                                <input type="text" name="query" placeholder="Search by name..."
                                    class="form-control arcade-input flex-fill"
                                    value="<?= htmlspecialchars($query) ?>">
                                <button type="submit" class="btn-arcade btn-arcade-cyan" style="font-size: 10px; white-space: nowrap; padding: 10px 16px;">Search</button>
                            </div>

                            <?php if (!empty($query) || !empty($selectedGames)): ?>
                                <?php if (empty($results)): ?>
                                    <p class="text-glow text-center mt-4" style="font-size: 10px;">No users found.</p>
                                <?php else: ?>
                                    <div class="mt-3">
                                        <?php foreach ($results as $result): ?>
                                            <div class="d-flex justify-content-between align-items-center py-2 px-3 mb-2"
                                                style="border-bottom: 1px solid rgba(0, 255, 255, 0.2);">
                                                <a href="profilepage.php?user_id=<?= $result['user_id'] ?>" class="arcade-link">
                                                    <?= htmlspecialchars($result['first_name'] . ' ' . $result['last_name']) ?>
                                                </a>
                                                <?php if (!empty($_SESSION['is_admin']) && $result['user_id'] != $_SESSION['user_id']): ?>
                                                    <?php if ($result['is_banned']): ?>
                                                        <button type="button" class="btn-arcade" style="font-size: 8px; padding: 4px 10px; opacity: 0.5; cursor: default; border-color: rgba(255,255,255,0.3); color: rgba(255,255,255,0.3);" disabled>
                                                            Banned
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" class="btn-arcade btn-arcade-danger" style="font-size: 8px; padding: 4px 10px;"
                                                            onclick="document.getElementById('ban-form-<?= $result['user_id'] ?>').style.display='flex'">
                                                            Ban
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </form>
        
        <?php if (!empty($results)): ?>
            <?php foreach ($results as $result): ?>
                <?php if (!empty($_SESSION['is_admin']) && $result['user_id'] != $_SESSION['user_id'] && !$result['is_banned']): ?>
                    <div id="ban-form-<?= $result['user_id'] ?>" class="ban-form" style="display: none;">
                        <form method="POST" class="d-flex flex-column gap-2">
                            <input type="hidden" name="action" value="ban_user">
                            <input type="hidden" name="ban_user_id" value="<?= $result['user_id'] ?>">

                            <label>Ban duration:</label>
                            <select name="ban_duration" class="form-select arcade-input" required>
                                <option value="1">1 day</option>
                                <option value="7">7 days</option>
                                <option value="30">30 days</option>
                                <option value="365">1 year</option>
                                <option value="36500">Permanent</option>
                            </select>

                            <label>Ban reason:</label>
                            <textarea name="ban_reason" rows="3" required
                                class="form-control arcade-input"
                                placeholder="Reason for ban..."></textarea>

                            <div class="d-flex justify-content-center gap-3 mt-2">
                                <button type="submit" class="btn-arcade btn-arcade-danger" style="font-size: 9px; padding: 8px 20px;">Confirm Ban</button>
                                <button type="button" class="btn-arcade btn-arcade-cyan" style="font-size: 9px; padding: 8px 20px;"
                                    onclick="document.getElementById('ban-form-<?= $result['user_id'] ?>').style.display='none'">Cancel</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var gameSearch = document.getElementById("gameSearch");
            var gameOptions = document.querySelectorAll("#gamesList .game-option");

            if (gameSearch) {
                gameSearch.addEventListener("keyup", function() {
                    var search = this.value.toLowerCase();
                    for (var i = 0; i < gameOptions.length; i++) {
                        var span = gameOptions[i].querySelector("span");
                        if (span) {
                            var text = span.textContent.toLowerCase();
                            gameOptions[i].style.display = text.indexOf(search) > -1 ? "flex" : "none";
                        }
                    }
                });
            }
        });
    </script>

</body>
</html>
