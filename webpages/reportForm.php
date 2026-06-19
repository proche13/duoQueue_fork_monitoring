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

$reporting_user_id = $_SESSION['user_id'];
$reported_user_id = $_GET['user_id'] ?? null;

if (!$reported_user_id || $reported_user_id == $reporting_user_id) {
    die("Invalid report target.");
}

$stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
$stmt->execute([$reported_user_id]);
$reported_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reported_user) {
    die("User not found.");
}

$reported_name = htmlspecialchars($reported_user['first_name'] . ' ' . $reported_user['last_name']);
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = trim($_POST['reason'] ?? '');
    $posted_user_id = $_POST['reported_user_id'] ?? null;

    if (!$reason) {
        $error = "Please provide a reason for your report.";
    } elseif ($posted_user_id != $reported_user_id) {
        $error = "Invalid report submission.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO reports (reporting_user_id, reported_user_id, reason)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$reporting_user_id, $reported_user_id, $reason]);
        $success = true;
    }
}

$date = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DuoQueue - Report User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/arcade-theme.css">
</head>

<body>

    <div class="arcade-screen px-3 d-flex flex-column">

        <div class="d-flex justify-content-between align-items-center mb-2">
            <a href="matches.php" class="btn-arcade btn-arcade-cyan" style="font-size: 9px; padding: 6px 12px; text-decoration: none;">&lt; Back</a>
            <span class="report-date"><?= $date ?></span>
        </div>

        <div class="card arcade-card flex-grow-1 d-flex flex-column overflow-hidden">

            <div class="p-3" style="border-bottom: 3px solid var(--cyan);">
                <h3 class="mb-0" style="font-size: clamp(10px, 1.2vw, 15px);">Report: <?= $reported_name ?></h3>
            </div>

            <?php if ($success): ?>
                <div class="flex-grow-1 d-flex align-items-center justify-content-center">
                    <p class="arcade-success" style="font-size: clamp(9px, 1vw, 12px);">Report submitted. Thank you.</p>
                </div>
            <?php else: ?>

                <form method="POST" action="reportForm.php?user_id=<?= $reported_user_id ?>" class="flex-grow-1 d-flex flex-column p-3">
                    <input type="hidden" name="reported_user_id" value="<?= $reported_user_id ?>">

                    <?php if ($error): ?>
                        <p class="arcade-error text-center mb-3"><?= htmlspecialchars($error) ?></p>
                    <?php endif; ?>

                    <label class="text-glow mb-2" style="font-size: clamp(8px, 0.9vw, 11px);">Reason for report:</label>
                    <textarea name="reason" id="reason" required
                        class="form-control arcade-input flex-grow-1 mb-3"
                        placeholder="Describe the issue..."></textarea>

                    <div class="d-flex justify-content-center" style="border-top: 3px solid var(--cyan); padding-top: 12px;">
                        <button type="submit" class="btn-arcade btn-arcade-cyan" style="font-size: 9px; padding: 10px 25px;">Submit Report</button>
                    </div>
                </form>

            <?php endif; ?>

        </div>

    </div>

</body>
</html>
