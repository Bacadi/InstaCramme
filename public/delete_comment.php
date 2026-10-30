<?php
require_once __DIR__ . "/../tools/connexion.php";
require_once __DIR__ . "/../tools/auth.php";

$logged_user_id = (int) ($_SESSION['user_id'] ?? 0);

// Seulement en POST, utilisateur connecté
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $logged_user_id <= 0) {
    http_response_code(403);
    exit("Action non autorisée.");
}

// Vérification du jeton CSRF
$submitted_token = $_POST['csrf_token'] ?? '';
if (
    !is_string($submitted_token) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $submitted_token)
) {
    http_response_code(403);
    exit("Erreur de sécurité. Recharge la page et réessaie.");
}

// Identifiant du commentaire
$comment_id = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
if (!$comment_id) {
    http_response_code(400);
    exit("Commentaire invalide.");
}

// Suppression uniquement si la photo appartient à l'utilisateur connecté
$delete = $pdo->prepare("
    DELETE c FROM comment c
    JOIN photo p ON p.id = c.photo_id
    WHERE c.id = ? AND p.user_id = ?
");
$delete->execute([$comment_id, $logged_user_id]);

// Retour sur le profil
header("Location: profile.php?id=" . $logged_user_id);
exit;