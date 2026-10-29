<?php
session_start();
require_once __DIR__ . "/../tools/connexion.php";
require_once __DIR__ . "/../tools/auth.php";

$currentUserId = (int) $_SESSION['user_id'];
$profilePhotoQuery = $pdo->prepare("SELECT pp_path FROM user WHERE id = ?");
$profilePhotoQuery->execute([$currentUserId]);
$profilePhoto = $profilePhotoQuery->fetchColumn() ?: "../tools/assets/icon.svg";

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['desc'] ?? '');
    $file = $_FILES['image'] ?? null;

    if ($title === '' || mb_strlen($title) > 100) {
        $errors[] = 'Le titre est obligatoire (100 caractères max).';
    }
    if ($desc === '' || mb_strlen($desc) > 200) {
        $errors[] = 'La description est obligatoire (200 caractères max).';
    }
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Erreur lors de l\'envoi de l\'image.';
    } elseif ($file['size'] > 5 * 1024 * 1024) {
        $errors[] = 'L\'image ne doit pas dépasser 5 Mo.';
    }

    if (!$errors) {
        // Vérifie le vrai type du fichier (pas celui annoncé par le navigateur)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($allowed[$mime])) {
            $errors[] = 'Format non autorisé (JPEG, PNG ou WebP uniquement).';
        }
    }

    if (!$errors) {
        $uploadDir = __DIR__ . '/uploads/post/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Nom aléatoire : on n'utilise jamais le nom d'origine
        $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];

        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            $stmt = $pdo->prepare(
                'INSERT INTO photo (user_id, title, description, image_path) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([(int) $_SESSION['user_id'], $title, $desc, 'uploads/post/' . $filename]);

            header('Location: index.php');
            exit;
        }
        $errors[] = 'Impossible d\'enregistrer l\'image.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Post</title>
    <link rel="stylesheet" href="../tools/assets/style.css">
</head>

<body>
    <main>
        <header>
            <img id="main-icon" src="../tools/assets/icon.svg" alt="Site Icon">
            <nav id="main-nav">
                <a href="profile.php?id=<?= $currentUserId ?>"><img id="profile-icon" src="<?= htmlspecialchars($profilePhoto, ENT_QUOTES, 'UTF-8') ?>" alt="Photo de profil"></a>
            </nav>
            <a href="../tools/logout.php">
                <img id="logout-icon"
                    src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAACXBIWXMAAAsTAAALEwEAmpwYAAAAhElEQVR4nO3Wyw2AIBBF0asl0BmxEgsx1mVJuhljglsXDuEh8hKWwEn4zEBPpbFM4/uAt+kAqwFwKAE7EJWA6Nk8yxkCAZhVgBHY0hqrAnBlShfxRgwUBrgQ9gDwfM1LEwDPEUgv4f+eYVB+RNExt41iZOpybDU0JNYEwNRtuakAPZTKCVq5tsvI/7GqAAAAAElFTkSuQmCC"
                    alt="exit">
            </a>
        </header>


    </main>
    <section id="post-section">

        <h1>Créer un post</h1>
        <?php foreach ($errors as $error): ?>
            <p class="error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endforeach; ?>


        <form id="post-form" method="post" enctype="multipart/form-data">
            <label for="image">Image</label>
            <input type="file" id="image" name="image" required accept="image/png, image/jpeg, image/webp">

            <label for="title">Titre du post</label>
            <input type="text" id="title" name="title" required maxlength="100">

            <label for="desc">Description</label>
            <textarea id="desc" name="desc" required rows="2" cols="25" maxlength="200"></textarea>

            <button type="button" id="close-btn"
                onclick="window.location.href='profile.php?id=<?= (int) $_SESSION['user_id'] ?>';">X</button>
            <button type="submit" id="send-post-btn">→</button>
        </form>

    </section>
</body>

</html>