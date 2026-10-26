<?php
require_once __DIR__ . "/connexion.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        exit("Veuillez remplir tous les champs.");
    }

    if (strlen($username) > 100) {
        exit("Le pseudo ne peut pas dépasser 100 caractères.");
    }

    try {
        $pdo->beginTransaction();

        $check = $pdo->prepare(
            "SELECT id, password FROM user WHERE username = ?"
        );
        $check->execute([$username]);
        $user = $check->fetch();

        if ($user) {
            if (!password_verify($password, $user["password"])) {
                $pdo->rollBack();
                exit("Mot de passe incorrect.");
            }

            $userId = $user["id"];
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $nextId = (int) $pdo
                ->query("SELECT COALESCE(MAX(id), 0) + 1 FROM user")
                ->fetchColumn();

            $stmt = $pdo->prepare(
                "INSERT INTO user (id, username, created_at, password)
                 VALUES (?, ?, CURRENT_TIMESTAMP, ?)"
            );
            $stmt->execute([$nextId, $username, $hashedPassword]);
            $userId = $nextId;
        }

        $pdo->commit();

        session_regenerate_id(true);
        $_SESSION["user_id"] = $userId;
        $_SESSION["username"] = $username;
        header("Location: ../public/index.php");
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log($e->getMessage());
        exit("La connexion ou l'inscription n'a pas pu aboutir.");
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
            <input type="text" id="username" name="username" required>
            <label for="password">Mot de passe</label>
            <input type="password_hash" id="password" name="password" required>
            <button type="submit" id="login-btn">→</button>
        </form>
    </section>

</body>

</html>