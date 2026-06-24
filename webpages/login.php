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

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $found_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($found_user && password_verify($password, $found_user['password'])) {

        if ($found_user['is_banned']) {
            $stmt = $pdo->prepare("
                SELECT created_timestamp, ban_duration 
                FROM banned 
                WHERE user_id = ? AND deleted_timestamp IS NULL
            ");
            $stmt->execute([$found_user['user_id']]);
            $ban = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($ban) {
                $ban_start = new DateTime($ban['created_timestamp']);
                $ban_end = clone $ban_start;
                $ban_end->modify('+' . $ban['ban_duration'] . ' days');
                $now = new DateTime();

                if ($now >= $ban_end) {
                    $stmt = $pdo->prepare("UPDATE banned SET deleted_timestamp = NOW() WHERE user_id = ? AND deleted_timestamp IS NULL");
                    $stmt->execute([$found_user['user_id']]);

                    $stmt = $pdo->prepare("UPDATE users SET is_banned = FALSE WHERE user_id = ?");
                    $stmt->execute([$found_user['user_id']]);
                } else {
                    // Still banned
                    $remaining = $now->diff($ban_end);
                    $error = "Your account is banned. " . $remaining->days . " day(s) remaining.";
                }
            }
        }

        if (empty($error)) {
            $_SESSION['user_id'] = $found_user['user_id'];
            $_SESSION['is_admin'] = $found_user['is_admin'];

            if ($found_user['is_admin']) {
                header("Location: adminHome.php");
            } else {
                header("Location: home.php");
            }
            exit;
        }

    } else {
        $error = "Invalid email or password.";
    }
}



?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DuoQueue - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/arcade-theme.css">
</head>
<body class="d-flex align-items-center justify-content-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-11 col-sm-8 col-md-6 col-lg-5">

                <div class="neon-box neon-box-lg p-4 p-md-5 text-center">
                    <h2 class="text-white mb-4" style="letter-spacing: 3px;">Login</h2>

                    <?php if (!empty($error)): ?>
                        <p class="arcade-error text-center mb-3"><?= htmlspecialchars($error) ?></p>
                    <?php endif; ?>

                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <input type="email" name="email" class="form-control arcade-input" placeholder="Email" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" name="password" class="form-control arcade-input" placeholder="Password" required>
                        </div>
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn-arcade">Login</button>
                        </div>
                        <div class="text-center mt-4">
                            <a href="register.php" class="arcade-link">Sign up</a>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</body>
</html>