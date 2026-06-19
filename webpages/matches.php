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
    die("User not logged in");
}

$user_id = $_SESSION['user_id'];

$sql = "
    SELECT m.*,
    u1.first_name AS user1_first, u1.last_name AS user1_last,
    u2.first_name AS user2_first, u2.last_name AS user2_last,
    p1.profile_photo AS user1_photo,
    p2.profile_photo AS user2_photo
    FROM matches m
    JOIN users u1 ON m.user1_id = u1.user_id
    JOIN users u2 ON m.user2_id = u2.user_id
    LEFT JOIN user_profiles p1 ON u1.user_id = p1.user_id
    LEFT JOIN user_profiles p2 ON u2.user_id = p2.user_id
    WHERE m.user1_id = ? OR m.user2_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id, $user_id]);
$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selected_match_id = $_GET['match_id'] ?? null;
$messages = [];

if ($selected_match_id) {
    $check = $pdo->prepare("
        SELECT * FROM matches 
        WHERE match_id = ? 
        AND (user1_id = ? OR user2_id = ?)
    ");
    $check->execute([$selected_match_id, $user_id, $user_id]);

    if ($check->rowCount() > 0) {
        $stmt = $pdo->prepare("
            SELECT * FROM messages 
            WHERE match_id = ?
            ORDER BY created_timestamp ASC
        ");
        $stmt->execute([$selected_match_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$other_user_id = null;
$other_user_name = "No Match Selected";
$other_user_photo = null;

if ($selected_match_id) {
    foreach ($matches as $m) {
        if ($m['match_id'] == $selected_match_id) {
            if ($m['user1_id'] == $user_id) {
                $other_user_id = $m['user2_id'];
                $other_user_name = $m['user2_first'] . ' ' . $m['user2_last'];
                $other_user_photo = $m['user2_photo'];
            } else {
                $other_user_id = $m['user1_id'];
                $other_user_name = $m['user1_first'] . ' ' . $m['user1_last'];
                $other_user_photo = $m['user1_photo'];
            }
            break;
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $match_id = $_POST['match_id'];
    $message = trim($_POST['message']);

    $normalized = preg_replace('/[\s\-().]/', '', $message);

    if (preg_match('/(\+?\d{1,3})?\d{9,}/', $normalized)) {
        $_SESSION['error'] = "Sharing phone numbers is not allowed.";
        header("Location: matches.php?match_id=" . $match_id);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT user1_id, user2_id 
        FROM matches 
        WHERE match_id = ? 
        AND (user1_id = ? OR user2_id = ?)
    ");
    $stmt->execute([$match_id, $user_id, $user_id]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$match) {
        die("Invalid match.");
    }

    $receiver_id = ($match['user1_id'] == $user_id)
        ? $match['user2_id']
        : $match['user1_id'];

    $stmt = $pdo->prepare("
        INSERT INTO messages (match_id, message, sender_id, receiver_id)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$match_id, $message, $user_id, $receiver_id]);

    header("Location: matches.php?match_id=" . $match_id);
    exit;
}

$messageError = $_SESSION['error'] ?? "";
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DuoQueue - My Duos</title>
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
        <div class="card arcade-card d-flex flex-row overflow-hidden" style="height: 70vh;">

            <!-- Sidebar -->
            <div class="d-flex flex-column flex-shrink-1" style="width: 180px; min-width: 100px; border-right: 3px solid var(--cyan); overflow-y: auto;">

                <?php if (empty($matches)): ?>
                    <p class="text-center p-3" style="font-size: 9px; color: rgba(255,255,255,0.5);">No matches yet.</p>
                <?php else: ?>
                    <?php foreach ($matches as $match):
                        $sidebar_name = ($match['user1_id'] == $user_id)
                            ? $match['user2_first'] . ' ' . $match['user2_last']
                            : $match['user1_first'] . ' ' . $match['user1_last'];
                        $is_active = ($match['match_id'] == $selected_match_id);
                    ?>
                        <a href="matches.php?match_id=<?= htmlspecialchars($match['match_id']) ?>"
                            class="match-user <?= $is_active ? 'active' : '' ?>">
                            <?= htmlspecialchars($sidebar_name) ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>

            <div class="flex-grow-1 d-flex flex-column overflow-hidden">

                <div class="d-flex align-items-center flex-wrap gap-2 p-2 flex-shrink-0" style="border-bottom: 3px solid var(--cyan); min-height: 60px;">
                    <img src="<?= $other_user_photo ? htmlspecialchars($other_user_photo) : '../assets/profile.jpg' ?>" class="profile-pic">
                    <span class="flex-grow-1" style="font-size: 10px; margin-left: 10px;">
                        <?= htmlspecialchars($other_user_name) ?>
                    </span>

                    <?php if ($selected_match_id && $other_user_id): ?>
                        <div class="d-flex gap-1 flex-wrap">
                            <a href="profilepage.php?user_id=<?= htmlspecialchars($other_user_id) ?>" class="text-decoration-none">
                                <button class="btn-arcade btn-arcade-cyan" style="font-size: 7px; padding: 6px 8px;">View Profile</button>
                            </a>

                            <form action="unmatch.php" method="POST"
                                onsubmit="return confirm('Are you sure you want to unmatch?');"
                                class="d-inline">
                                <input type="hidden" name="match_id" value="<?= htmlspecialchars($selected_match_id) ?>">
                                <button type="submit" class="btn-arcade btn-arcade-danger" style="font-size: 7px; padding: 6px 8px;">Unmatch</button>
                            </form>

                            <a href="reportForm.php?user_id=<?= htmlspecialchars($other_user_id) ?>" class="text-decoration-none">
                                <button class="btn-arcade btn-arcade-danger" style="font-size: 7px; padding: 6px 8px;">Report</button>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="chat-messages flex-grow-1 p-3" style="overflow-y: auto;">
                    <?php if (empty($messages) && $selected_match_id): ?>
                        <p class="text-center" style="font-size: 9px; color: rgba(255,255,255,0.5);">No messages yet. Say hello!</p>
                    <?php else: ?>
                        <?php foreach ($messages as $message):
                            $class = ($message['sender_id'] == $user_id) ? "sent" : "received";
                        ?>
                            <div class="message <?= $class ?>">
                                <?= htmlspecialchars($message['message']) ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="flex-shrink-0 chat-input" style="border-top: 3px solid var(--cyan);">
                    <?php if ($selected_match_id): ?>
                        <form method="POST" class="d-flex w-100">
                            <input type="hidden" name="match_id" value="<?= htmlspecialchars($selected_match_id) ?>">

                            <input
                                type="text"
                                name="message"
                                class="form-control arcade-input flex-grow-1 border-0 <?= !empty($messageError) ? 'input-error' : '' ?>"
                                style="border-radius: 0;"
                                placeholder="<?= htmlspecialchars($messageError ?: 'Type message...') ?>"
                                required
                            >

                            <button type="submit" class="btn-arcade btn-arcade-cyan" style="font-size: 10px; padding: 10px 20px; border-radius: 0;">Send</button>
                        </form>
                    <?php endif; ?>
                </div>

            </div>

        </div>
    </div>

    <script>
    function loadMessages() {
        const urlParams = new URLSearchParams(window.location.search);
        const matchId = urlParams.get('match_id');
        if (!matchId) return;

        fetch(`fetch_messages.php?match_id=${matchId}`)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.text();
            })
            .then(data => {
                const chatBox = document.querySelector('.chat-messages');
                if (chatBox) {
                    chatBox.innerHTML = data;
                    chatBox.scrollTop = chatBox.scrollHeight;
                }
            })
            .catch(error => console.error('Error loading messages:', error));
    }

    setInterval(loadMessages, 3000);
    loadMessages();
    </script>

</body>
</html>
