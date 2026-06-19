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
    die("Could not connect to the database. Please try again later.");
}

$admin_id = $_SESSION['user_id'];
$report_id = $_GET['report_id'] ?? null;

if (!$report_id) {
    die("No report specified.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'remove_report') {
        $stmt = $pdo->prepare("DELETE FROM reports WHERE report_id = ?");
        $stmt->execute([$report_id]);
        header("Location: moderation.php");
        exit;

    } elseif ($action === 'ban_user') {
        $banned_user_id = $_POST['reported_user_id'] ?? null;
        $ban_reason = trim($_POST['ban_reason'] ?? '');
        $ban_duration = (int)($_POST['ban_duration'] ?? 0);

        if ($banned_user_id && $ban_reason && $ban_duration > 0) {
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

            $stmt = $pdo->prepare("DELETE FROM reports WHERE report_id = ?");
            $stmt->execute([$report_id]);

            header("Location: moderation.php");
            exit;
        }
    }
}


$stmt = $pdo->prepare("
    SELECT 
        r.report_id, r.reason, r.created_timestamp,
        reported.user_id AS reported_user_id,
        reported.first_name AS reported_name,
        reporter.user_id AS reporter_user_id,
        reporter.first_name AS reporter_name
    FROM reports r
    JOIN users reported ON r.reported_user_id = reported.user_id
    JOIN users reporter ON r.reporting_user_id = reporter.user_id
    WHERE r.report_id = :report_id
");
$stmt->execute(['report_id' => $report_id]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    die("Report not found.");
}


$stmt = $pdo->prepare("
    SELECT m.message, m.sender_id, m.created_timestamp
    FROM messages m
    JOIN matches ma ON m.match_id = ma.match_id
    WHERE (ma.user1_id = :u1 AND ma.user2_id = :u2)
       OR (ma.user1_id = :u3 AND ma.user2_id = :u4)
    ORDER BY m.created_timestamp ASC
");
$stmt->execute([
    'u1' => $report['reported_user_id'],
    'u2' => $report['reporter_user_id'],
    'u3' => $report['reporter_user_id'],
    'u4' => $report['reported_user_id']
]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$reported = $report['reported_name'];
$reported_user_id = $report['reported_user_id'];
$reporter = $report['reporter_name'];
$reason = $report['reason'];
$date = $report['created_timestamp'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DuoQueue - Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/arcade-theme.css">
</head>

<body>

    <div class="arcade-screen px-3 d-flex flex-column">

        <div class="d-flex justify-content-between align-items-center mb-2">
            <a href="moderation.php" class="btn-arcade btn-arcade-cyan" style="font-size: 9px; padding: 6px 12px; text-decoration: none;">&lt; Back</a>
            <span class="report-date"><?= $date ?></span>
        </div>
        
        <div class="card arcade-card flex-grow-1 d-flex flex-column overflow-hidden">

            <div class="p-3" style="border-bottom: 3px solid var(--cyan);">
                <h3 class="mb-2" style="font-size: clamp(10px, 1.2vw, 15px);">Report: <?= htmlspecialchars($reported) ?></h3>
                <div class="d-flex flex-column gap-1">
                    <span class="report-detail">Reported by: <span><?= htmlspecialchars($reporter) ?></span></span>
                    <span class="report-detail">Reason: <span><?= htmlspecialchars($reason) ?></span></span>
                </div>
            </div>

            <div class="flex-grow-1 d-flex flex-column p-3 overflow-hidden">
                <span class="text-glow text-center mb-2" style="font-size: clamp(8px, 0.9vw, 11px);">Chatlogs</span>
                <div class="neon-box p-3 flex-grow-1" style="overflow-y: auto;">
                    <?php if (empty($messages)): ?>
                        <p class="text-center" style="font-size: 9px; color: rgba(255,255,255,0.5);">No messages found.</p>
                    <?php else: ?>
                        <?php foreach ($messages as $message):
                            $class = ($message['sender_id'] == $reported_user_id) ? "sent" : "received";
                        ?>
                            <div class="message <?= $class ?>">
                                <?= htmlspecialchars($message['message']) ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex justify-content-center gap-4 p-3" style="border-top: 3px solid var(--cyan);">
                <form method="POST" onsubmit="return confirm('Remove this report?');" class="d-inline">
                    <input type="hidden" name="action" value="remove_report">
                    <button type="submit" class="btn-arcade btn-arcade-cyan" style="font-size: 9px; padding: 10px 20px;">Remove Report</button>
                </form>
                <button class="btn-arcade btn-arcade-danger" style="font-size: 9px; padding: 10px 20px;"
                    onclick="document.getElementById('ban-form').style.display='flex'">
                    Ban User
                </button>
            </div>

            <div id="ban-form" class="ban-form" style="display: none;">
                <form method="POST" class="d-flex flex-column gap-2">
                    <input type="hidden" name="action" value="ban_user">
                    <input type="hidden" name="reported_user_id" value="<?= $reported_user_id ?>">

                    <label for="ban_duration">Ban duration:</label>
                    <select name="ban_duration" id="ban_duration" class="form-select arcade-input" required>
                        <option value="1">1 day</option>
                        <option value="7">7 days</option>
                        <option value="30">30 days</option>
                        <option value="365">1 year</option>
                        <option value="36500">Permanent</option>
                    </select>

                    <label for="ban_reason">Ban reason:</label>
                    <textarea name="ban_reason" id="ban_reason" rows="3" required
                        class="form-control arcade-input"
                        placeholder="Reason for ban..."><?= htmlspecialchars($reason) ?></textarea>

                    <div class="d-flex justify-content-center gap-3 mt-2">
                        <button type="submit" class="btn-arcade btn-arcade-danger" style="font-size: 9px; padding: 8px 20px;">Confirm Ban</button>
                        <button type="button" class="btn-arcade btn-arcade-cyan" style="font-size: 9px; padding: 8px 20px;"
                            onclick="document.getElementById('ban-form').style.display='none'">Cancel</button>
                    </div>
                </form>
            </div>

        </div>

    </div>

</body>
</html>
