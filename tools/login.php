<?php
session_start();
require_once __DIR__ . "/../tools/connexion.php";

const USERNAME_MAX_LENGTH = 100;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');

    if ($username === '') {
        $warning = 'Please enter a username.';
    } elseif (mb_strlen($username) > USERNAME_MAX_LENGTH) {
        $warning = 'Username must be at most ' . USERNAME_MAX_LENGTH . ' characters.';
    } else {
        $stmt = $pdo->prepare('SELECT id, username FROM user WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $userId = $user['id'];
            $username = $user['username'];
        } else {
            $stmt = $pdo->prepare('INSERT INTO user (username) VALUES (?)');
            $stmt->execute([$username]);
            $userId = $pdo->lastInsertId();
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $userId;
        $_SESSION['username'] = $username;
        header('Location: ../public/index.php');
        exit;
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
        <form id="login-form" method="post">
            <label for="username">Pseudo</label>
            <input type="text" id="username" name="username" required maxlength="100">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required maxlength="100">
            <button type="submit" id="login-btn">→</button>
        </form>
    </section>

</body>

</html>