<?php
session_start();
require_once __DIR__ . "/../tools/connexion.php";

const USERNAME_MAX_LENGTH = 100;
$warning = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $warning = 'Veuillez saisir un pseudo et un mot de passe.';
    } elseif (mb_strlen($username) > USERNAME_MAX_LENGTH) {
        $warning = 'Le pseudo ne doit pas dépasser ' . USERNAME_MAX_LENGTH . ' caractères.';
    } else {
        $stmt = $pdo->prepare('SELECT id, username, password FROM user WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Pseudo connu : on vérifie le mot de passe
            if (!password_verify($password, $user['password'])) {
                $warning = 'Mot de passe incorrect.';
            } else {
                $userId = $user['id'];
                $username = $user['username'];
            }
        } else {
            // Pseudo inconnu : on crée le compte avec un mot de passe haché
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO user (username, password) VALUES (?, ?)');
            $stmt->execute([$username, $hash]);
            $userId = $pdo->lastInsertId();
        }

        if ($warning === '') {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $userId;
            $_SESSION['username'] = $username;
            header('Location: ../public/index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InstaCramme Login</title>
    <link rel="stylesheet" href="../tools/assets/style.css">
</head>

<body>

    <section id="login-section">
        <h1>Connexion à InstaCramme</h1>
        <?php if ($warning): ?>
            <p class="error">
                <?= htmlspecialchars($warning, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>
        <form id="login-form" method="post">
            <label for="username">Pseudo</label>
            <input type="text" id="username" name="username" required maxlength="100">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required maxlength="255">
            <button type="submit" id="login-btn">→</button>
        </form>
    </section>

</body>

</html>