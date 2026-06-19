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
    exit;
}

if (!empty($_SESSION['is_admin']) && isset($_GET['user_id'])) {
    $userId = (int)$_GET['user_id'];
} else {
    $userId = $_SESSION['user_id'];
}
$success = "";
$error   = "";

$stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

$isNewProfile = empty($profile);

$currentPhoto = !empty($profile['profile_photo'])
    ? '/' . $profile['profile_photo']
    : 'https://via.placeholder.com/150';

$stmt = $pdo->query("SELECT game_id, game_name FROM available_games ORDER BY game_name");
$allGames = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT game_id FROM users_games WHERE user_id = ?");
$stmt->execute([$userId]);
$selectedGames = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare("SELECT platform_id, platform_name FROM available_platforms ORDER BY platform_name");
$stmt->execute();
$allPlatforms = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT platform_id FROM user_platforms WHERE user_id = ?");
$stmt->execute([$userId]);
$selectedPlatforms = $stmt->fetchAll(PDO::FETCH_COLUMN);

$selectedGameNames = [];
foreach ($allGames as $game) {
    if (in_array($game['game_id'], $selectedGames, true)) {
        $selectedGameNames[] = $game['game_name'];
    }
}

$selectedPlatformNames = [];
foreach ($allPlatforms as $platform) {
    if (in_array($platform['platform_id'], $selectedPlatforms, true)) {
        $selectedPlatformNames[] = $platform['platform_name'];
    }
}

$stmt = $pdo->prepare("SELECT photo FROM user_photos WHERE user_id = ?");
$stmt->execute([$userId]);
$existingGalleryPhotos = $stmt->fetchAll(PDO::FETCH_COLUMN);


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST['delete_gallery_photo'])) {
        $photoToDelete = $_POST['delete_gallery_photo'];

        $stmt = $pdo->prepare("SELECT photo FROM user_photos WHERE user_id = ? AND photo = ?");
        $stmt->execute([$userId, $photoToDelete]);
        $existingPhoto = $stmt->fetchColumn();

        if ($existingPhoto) {
            $stmt = $pdo->prepare("DELETE FROM user_photos WHERE user_id = ? AND photo = ?");
            $stmt->execute([$userId, $photoToDelete]);

            if (file_exists($existingPhoto)) {
                unlink($existingPhoto);
            }

            $success = "Gallery photo removed.";

            $stmt = $pdo->prepare("SELECT photo FROM user_photos WHERE user_id = ?");
            $stmt->execute([$userId]);
            $existingGalleryPhotos = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $error = "Photo not found.";
        }
    }

    $location    = trim($_POST["location"] ?? '');
    $dateOfBirth = trim($_POST["date_of_birth"] ?? '');
    $gender      = trim($_POST["gender"] ?? '');
    $seeking     = trim($_POST["seeking"] ?? '');
    $aboutMe     = trim($_POST["about_me"] ?? '');
    $smoker      = isset($_POST["smoker"]) ? 1 : 0;
    $drinker     = isset($_POST["drinker"]) ? 1 : 0;
    $selectedGames = $_POST["games"] ?? [];
    $selectedPlatforms = $_POST["platforms"] ?? [];
    $profilePhoto = "";

    if (count($selectedGames) > 5) {
        $error = "You can select up to 5 games to add to your favourite games!.";
    }

    if (!empty($dateOfBirth)) {
        $dob = DateTime::createFromFormat('Y-m-d', $dateOfBirth);
        if (!$dob || $dob->format('Y-m-d') !== $dateOfBirth) {
            $error = "Invalid date format.";
        } else {
            $today = new DateTime();
            $age = $today->diff($dob)->y;
            if ($age < 18) {
                $error = "You must be at least 18 years old to use this service.";
            }
        }
    }

    if ($isNewProfile && empty($error)) {
        if (empty($location))    $error = "Location is required.";
        elseif (empty($dateOfBirth)) $error = "Date of birth is required.";
        elseif (empty($gender))      $error = "Gender is required.";
        elseif (empty($seeking))     $error = "Seeking is required.";
        elseif (empty($aboutMe))     $error = "Bio is required.";
        elseif (empty($selectedGames))  $error = "Please select at least 1 favourite game.";
        elseif (empty($selectedPlatforms))  $error = "Please select at least 1 platform.";
    }

    if (empty($error) && isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_photo'];

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            $error = "Only image files (jpg, png, gif, webp) are allowed.";
        }
        if (empty($error) && $file['size'] > 2 * 1024 * 1024) {
            $error = "Photo must be under 2MB.";
        }

        if (empty($error)) {
            $uploadDir = 'uploads/profile_photos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $extension    = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename     = $userId . '_' . time() . '.' . $extension;
            $profilePhoto = 'uploads/profile_photos/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                if (!empty($profile['profile_photo']) && file_exists($profile['profile_photo'])) {
                    unlink($profile['profile_photo']);
                }
            } else {
                $error = "Could not save the photo.";
            }
        }
    }

    $galleryPhotos = [];
if (empty($error) && isset($_FILES['gallery_photos']) && !empty($_FILES['gallery_photos']['name'][0])) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $uploadDir = 'uploads/gallery_photos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $totalExistingStmt = $pdo->prepare("SELECT COUNT(*) FROM user_photos WHERE user_id = ?");
    $totalExistingStmt->execute([$userId]);
    $existingCount = (int)$totalExistingStmt->fetchColumn();

    $newCount = count($_FILES['gallery_photos']['name']);
    $maxPhotos = 5;

    if (($existingCount + $newCount) > $maxPhotos) {
        $error = "You can have a maximum of 5 gallery photos. You currently have $existingCount.";
    } else {
        for ($i = 0; $i < count($_FILES['gallery_photos']['name']); $i++) {
            if ($_FILES['gallery_photos']['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $fileType = $_FILES['gallery_photos']['type'][$i];
            $fileSize = $_FILES['gallery_photos']['size'][$i];
            $tmpName = $_FILES['gallery_photos']['tmp_name'][$i];
            $ogName = $_FILES['gallery_photos']['name'][$i];

            if (!in_array($fileType, $allowedTypes, true)) {
                $error = "Only image files (jpg, png, webp) are allowed for gallery photos.";
                break;
            }

            if ($fileSize > 2 * 1024 * 1024) {
                $error = "Each gallery photo must be under 2MB.";
                break;
            }

            $extension = pathinfo($ogName, PATHINFO_EXTENSION);
            $filename = $userId . '_' . time() . '_' . $i . '.' . $extension;
            $filePath = $uploadDir . $filename;

            if (move_uploaded_file($tmpName, $filePath)) {
                $galleryPhotos[] = $filePath;
            } else {
                $error = "Could not save a gallery photo.";
                break;
            }
        }
    }
}

if (empty($error)) {
    try {
        $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, location, date_of_birth, gender, seeking, about_me, smoker, drinker, profile_photo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                location      = VALUES(location),
                date_of_birth = VALUES(date_of_birth),
                gender        = VALUES(gender),
                seeking       = VALUES(seeking),
                about_me      = VALUES(about_me),
                smoker        = VALUES(smoker),
                drinker       = VALUES(drinker),
                profile_photo = IF(VALUES(profile_photo) = '', profile_photo, VALUES(profile_photo))");
        $stmt->execute([$userId, $location, $dateOfBirth, $gender, $seeking, $aboutMe, $smoker, $drinker, $profilePhoto]);

        $stmt = $pdo->prepare("DELETE FROM users_games WHERE user_id = ?");
        $stmt->execute([$userId]);

        $stmt = $pdo->prepare("DELETE FROM user_platforms WHERE user_id = ?");
        $stmt->execute([$userId]);

        if (!empty($selectedGames)) {
            $stmt = $pdo->prepare("INSERT INTO users_games (user_id, game_id) VALUES (?, ?)");
            foreach ($selectedGames as $gameId) {
                $stmt->execute([$userId, $gameId]);
            }
        }

        if (!empty($selectedPlatforms)) {
            $stmt = $pdo->prepare("INSERT INTO user_platforms (user_id, platform_id, platform_username) VALUES (?, ?, ?)");
            foreach ($selectedPlatforms as $platformId) {
                $stmt->execute([$userId, $platformId, '']);
            }
        }

        if (!empty($galleryPhotos)) {
            $stmt = $pdo->prepare("INSERT INTO user_photos (user_id, photo) VALUES (?, ?)");
            foreach ($galleryPhotos as $photoPath) {
                $stmt->execute([$userId, $photoPath]);
            }
        }

        $profile = [
            'location'      => $location,
            'date_of_birth' => $dateOfBirth,
            'gender'        => $gender,
            'seeking'       => $seeking,
            'about_me'      => $aboutMe,
            'smoker'        => $smoker,
            'drinker'       => $drinker,
            'profile_photo' => $profilePhoto ?: ($profile['profile_photo'] ?? '')
        ];

        $isNewProfile = false;
        $success = "Profile saved successfully!";

        if (!empty($profilePhoto)) {
            $currentPhoto = '/' . $profilePhoto;
        }

        header("Location: profilepage.php?user_id=" . $userId);
    } catch (PDOException $e) {
        $error = "Profile update failed: " . $e->getMessage();
    }
}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DuoQueue - <?= $isNewProfile ? 'Set Up Profile' : 'Edit Profile' ?></title>
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
        </div>
    </nav>

    <div class="arcade-screen px-3">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card arcade-card mb-4">
                    <div class="card-body text-center">
                        <img src="<?= htmlspecialchars($currentPhoto) ?>"
                            id="profileImage"
                            class="img-fluid mb-3"
                            style="width: 150px; height: 150px; object-fit: cover; border-radius: 25%; border: 2px solid var(--cyan);">
                        <h2 class="card-title" style="font-size: clamp(10px, 1.2vw, 14px);">
                            <?= htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']) ?>
                        </h2>
                        <p class="mb-1" style="font-size: 10px;" id="previewGender"><?= htmlspecialchars($profile['gender'] ?? 'Gender') ?></p>
                        <p class="mb-1" style="font-size: 10px;" id="previewLocation"><?= htmlspecialchars($profile['location'] ?? 'Location') ?></p>
                        <p class="mb-1" style="font-size: 10px;" id="previewOrientation">Seeking: <?= htmlspecialchars($profile['seeking'] ?? '') ?></p>
                        <p class="mb-1" style="font-size: 10px;" id="previewGames">Favorite Games: <?= htmlspecialchars(!empty($selectedGameNames) ? implode(', ', $selectedGameNames) : '') ?></p>
                        <p class="mb-1" style="font-size: 10px;" id="previewPlatforms">Platforms: <?= htmlspecialchars(!empty($selectedPlatformNames) ? implode(', ', $selectedPlatformNames) : '') ?></p>
                        <p class="mb-0" style="font-size: 10px;" id="previewBio"><?= htmlspecialchars($profile['about_me'] ?? 'Your bio will appear here...') ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <div class="card arcade-card mb-4">
                    <div class="card-body">

                        <h2 class="card-title mb-3" style="font-size: clamp(10px, 1.2vw, 14px);">
                            <?= $isNewProfile ? 'Set Up Your Profile' : 'Edit Profile' ?>
                        </h2>

                        <?php if ($isNewProfile): ?>
                            <p class="arcade-alert text-center p-2 mb-3">Welcome! Please fill in all fields to get started.</p>
                        <?php endif; ?>

                        <?php if (!empty($success)): ?>
                            <p class="arcade-success text-center mb-3"><?= htmlspecialchars($success) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($error)): ?>
                            <p class="arcade-error text-center mb-3"><?= htmlspecialchars($error) ?></p>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">

                            <div class="row g-3">

                                <div class="col-md-6">
                                    <label class="text-glow mb-1" style="font-size: 10px;">Profile Photo:</label>
                                    <input type="file" name="profile_photo" accept="image/*" id="photoInput" class="form-control arcade-input">
                                </div>

                                <div class="col-md-6">
                                    <label class="text-glow mb-1" style="font-size: 10px;">Photo Gallery:</label>
                                    <input type="file" name="gallery_photos[]" accept="image/*" id="galleryInput" multiple class="form-control arcade-input">
                                </div>

                                <?php if (!empty($existingGalleryPhotos)): ?>
                                    <div class="col-12">
                                        <label class="text-glow mb-2" style="font-size: 10px;">Current Gallery Photos:</label>
                                        <div class="d-flex flex-wrap gap-3">
                                            <?php foreach ($existingGalleryPhotos as $galleryPhoto): ?>
                                                <div class="text-center">
                                                    <img src="<?= htmlspecialchars($galleryPhoto) ?>"
                                                        alt="Gallery Photo"
                                                        class="img-fluid rounded mb-2"
                                                        style="width: 100px; height: 100px; object-fit: cover; border: 2px solid var(--cyan);">
                                                    <div>
                                                        <button type="submit"
                                                            name="delete_gallery_photo"
                                                            value="<?= htmlspecialchars($galleryPhoto) ?>"
                                                            class="btn-arcade btn-arcade-danger"
                                                            style="font-size: 8px; padding: 4px 8px;"
                                                            onclick="return confirm('Remove this photo from your gallery?');">
                                                            Delete
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="col-md-6">
                                    <label class="text-glow mb-1" style="font-size: 10px;">Location:</label>
                                    <input type="text" name="location" placeholder="Location" id="locationInput"
                                        class="form-control arcade-input"
                                        value="<?= htmlspecialchars($profile['location'] ?? '') ?>"
                                        <?= $isNewProfile ? 'required' : '' ?>>
                                </div>

                                <div class="col-md-6">
                                    <label class="text-glow mb-1" style="font-size: 10px;">Date of Birth:</label>
                                    <?php $maxDob = date('Y-m-d', strtotime('-18 years')); ?>
                                    <input type="date" name="date_of_birth"
                                        class="form-control arcade-input"
                                        max="<?= $maxDob ?>"
                                        value="<?= htmlspecialchars($profile['date_of_birth'] ?? '') ?>"
                                        <?= $isNewProfile ? 'required' : '' ?>>
                                </div>

                                <div class="col-md-6">
                                    <label class="text-glow mb-1" style="font-size: 10px;">Gender:</label>
                                    <select name="gender" id="genderInput" class="form-select arcade-input" <?= $isNewProfile ? 'required' : '' ?>>
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?= ($profile['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                        <option value="Female" <?= ($profile['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                        <option value="Other" <?= ($profile['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="text-glow mb-1" style="font-size: 10px;">Seeking:</label>
                                    <select name="seeking" id="seekingInput" class="form-select arcade-input" <?= $isNewProfile ? 'required' : '' ?>>
                                        <option value="">Select</option>
                                        <option value="Male" <?= ($profile['seeking'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                        <option value="Female" <?= ($profile['seeking'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                        <option value="Other" <?= ($profile['seeking'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="text-glow mb-1" style="font-size: 10px;">Favorite Games:</label>
                                    <input type="text" id="gameSearch" placeholder="Search Games..." class="form-control arcade-input mb-2">
                                    <div id="gamesList" class="neon-box p-2" style="max-height: 180px; overflow-y: auto; font-size: 10px;">
                                        <?php foreach ($allGames as $game): ?>
                                            <label class="game-option mb-2" style="cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                                                <span><?= htmlspecialchars($game['game_name']) ?></span>
                                                <input type="checkbox" name="games[]" value="<?= htmlspecialchars($game['game_id']) ?>"
                                                    <?= in_array($game['game_id'], $selectedGames) ? 'checked' : '' ?>>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="text-glow mb-1" style="font-size: 10px;">Platforms:</label>
                                    <input type="text" id="platformSearch" placeholder="Search Platforms..." class="form-control arcade-input mb-2">
                                    <div id="platformsList" class="neon-box p-2" style="max-height: 180px; overflow-y: auto; font-size: 10px;">
                                        <?php foreach ($allPlatforms as $platform): ?>
                                            <label class="platform-option mb-2" style="cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                                                <span><?= htmlspecialchars($platform['platform_name']) ?></span>
                                                <input type="checkbox" name="platforms[]" value="<?= htmlspecialchars($platform['platform_id']) ?>"
                                                    <?= in_array($platform['platform_id'], $selectedPlatforms) ? 'checked' : '' ?>>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="text-glow mb-1" style="font-size: 10px;">About Me:</label>
                                    <textarea name="about_me" placeholder="Write a short bio..." id="bioInput"
                                        class="form-control arcade-input"
                                        rows="4"
                                        <?= $isNewProfile ? 'required' : '' ?>><?= htmlspecialchars($profile['about_me'] ?? '') ?></textarea>
                                </div>

                                <div class="col-md-6">
                                    <label class="text-white" style="font-size: 10px; cursor: pointer;">
                                        <input type="checkbox" name="smoker" <?= !empty($profile['smoker']) ? 'checked' : '' ?>> Smoker
                                    </label>
                                </div>

                                <div class="col-md-6">
                                    <label class="text-white" style="font-size: 10px; cursor: pointer;">
                                        <input type="checkbox" name="drinker" <?= !empty($profile['drinker']) ? 'checked' : '' ?>> Drinker
                                    </label>
                                </div>

                                <div class="col-12 mt-3">
                                    <div class="d-grid">
                                        <button type="submit" class="btn-arcade">Save Profile</button>
                                    </div>
                                </div>

                            </div>
                        </form>

                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.getElementById("locationInput").oninput = e =>
            document.getElementById("previewLocation").textContent = e.target.value || "Location";

        document.getElementById("genderInput").onchange = e =>
            document.getElementById("previewGender").textContent = e.target.value || "Gender";

        document.getElementById("seekingInput").onchange = e =>
            document.getElementById("previewOrientation").textContent = "Seeking: " + (e.target.value || "");

        document.getElementById("bioInput").oninput = e =>
            document.getElementById("previewBio").textContent = e.target.value || "Your bio will appear here...";

        document.getElementById("photoInput").onchange = function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => document.getElementById("profileImage").src = e.target.result;
                reader.readAsDataURL(file);
            }
        };

        const gameSearch = document.getElementById("gameSearch");
        const gameOptions = document.querySelectorAll("#gamesList .game-option");
        const gameCheckboxes = document.querySelectorAll('#gamesList input[type="checkbox"]');
        const previewGames = document.getElementById("previewGames");

        function updatePreviewGames() {
            const selected = Array.from(gameCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.parentElement.querySelector("span").textContent);
            previewGames.textContent = "Favorite Games: " + (selected.length ? selected.join(", ") : "");
        }

        gameSearch.addEventListener("input", function() {
            const search = this.value.toLowerCase();
            gameOptions.forEach(option => {
                const text = option.textContent.toLowerCase();
                option.style.display = text.includes(search) ? "flex" : "none";
            });
        });

        gameCheckboxes.forEach(checkbox => {
            checkbox.addEventListener("change", function() {
                const checkedCount = Array.from(gameCheckboxes).filter(cb => cb.checked).length;
                if (checkedCount > 5) {
                    this.checked = false;
                    alert("You can select up to 5 favourite games only.");
                }
                updatePreviewGames();
            });
        });
        updatePreviewGames();

        const platformSearch = document.getElementById("platformSearch");
        const platformOptions = document.querySelectorAll("#platformsList .platform-option");
        const platformCheckboxes = document.querySelectorAll('#platformsList input[type="checkbox"]');
        const previewPlatforms = document.getElementById("previewPlatforms");

        function updatePreviewPlatforms() {
            const selected = Array.from(platformCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.parentElement.querySelector("span").textContent);
            previewPlatforms.textContent = "Platforms: " + (selected.length ? selected.join(", ") : "");
        }

        platformSearch.addEventListener("input", function() {
            const search = this.value.toLowerCase();
            platformOptions.forEach(option => {
                const text = option.textContent.toLowerCase();
                option.style.display = text.includes(search) ? "flex" : "none";
            });
        });

        platformCheckboxes.forEach(checkbox => {
            checkbox.addEventListener("change", function() {
                updatePreviewPlatforms();
            });
        });
        updatePreviewPlatforms();
    </script>

</body>
</html>
