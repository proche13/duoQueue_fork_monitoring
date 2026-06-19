<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
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
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName      = trim($_POST["first_name"]);
    $lastName       = trim($_POST["last_name"]);
    $email          = trim($_POST["email"]);
    $password       = $_POST["password"];
    $repeatPassword = $_POST["repeat_password"];

    $minPassLength = 8;
    if (strlen($password) <= $minPassLength) {
        $error = "Password must be at least $minPassLength characters or longer!";
    } elseif ($password !== $repeatPassword) {
        $error = "Passwords do not match!";
    } else {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = "An account with that email already exists.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, is_admin, is_banned) VALUES (?, ?, ?, ?, ?, ?)");
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmt->execute([$firstName, $lastName, $email, $hashedPassword, 0, 0]);

                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['first_name'] = $firstName;
                $_SESSION['last_name'] = $lastName;
                header("Location: profile.php");
                exit;
            } catch (PDOException $e) {
                $error = "Registration failed: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DuoQueue - Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/arcade-theme.css">
</head>
<body class="d-flex align-items-center justify-content-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-11 col-sm-8 col-md-6 col-lg-5">

                <div class="neon-box neon-box-lg p-4 p-md-5 text-center">
                    <h2 class="text-white mb-4" style="letter-spacing: 3px;">Register</h2>

                    <?php if (!empty($error)): ?>
                        <p class="arcade-error text-center mb-3"><?= htmlspecialchars($error) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($success)): ?>
                        <p class="arcade-success text-center mb-3"><?= htmlspecialchars($success) ?></p>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <input type="text" name="first_name" class="form-control arcade-input" placeholder="First Name" required>
                        </div>
                        <div class="mb-3">
                            <input type="text" name="last_name" class="form-control arcade-input" placeholder="Last Name" required>
                        </div>
                        <div class="mb-3">
                            <input type="email" name="email" class="form-control arcade-input" placeholder="Email" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" name="password" class="form-control arcade-input" placeholder="Password" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" name="repeat_password" class="form-control arcade-input" placeholder="Repeat Password" required>
                        </div>
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn-arcade">Create my account</button>
                        </div>
                        <div class="text-center mt-4">
                            <a href="login.php" class="arcade-link">Log in</a>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</body>
</html>
