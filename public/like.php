<?php
require_once __DIR__ . "/../tools/auth.php";
require_once __DIR__ . "/../tools/connexion.php";

header('Content-Type: application/json; charset=utf-8');

// Il faut être connecté et envoyer un POST
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non connecté']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

$userId  = (int) $_SESSION['user_id'];
$photoId = filter_input(INPUT_POST, 'photo_id', FILTER_VALIDATE_INT);

if (!$photoId) {
    http_response_code(400);
    echo json_encode(['error' => 'Photo invalide']);
    exit;
}

// La photo existe-t-elle ?
$stmt = $pdo->prepare('SELECT 1 FROM photo WHERE id = ?');
$stmt->execute([$photoId]);
if (!$stmt->fetchColumn()) {
    http_response_code(404);
    echo json_encode(['error' => 'Photo introuvable']);
    exit;
}

// Le like existe-t-il déjà ?
$stmt = $pdo->prepare('SELECT id FROM `like` WHERE user_id = ? AND photo_id = ?');
$stmt->execute([$userId, $photoId]);
$existingLike = $stmt->fetchColumn();

if ($existingLike) {
    // Déjà liké : on retire le like
    $stmt = $pdo->prepare('DELETE FROM `like` WHERE user_id = ? AND photo_id = ?');
    $stmt->execute([$userId, $photoId]);
    $liked = false;
} else {
    // Pas encore liké : on l'ajoute (INSERT IGNORE protège contre un double clic simultané)
    $stmt = $pdo->prepare('INSERT IGNORE INTO `like` (user_id, photo_id) VALUES (?, ?)');
    $stmt->execute([$userId, $photoId]);
    $liked = true;
}

// Nouveau compteur
$stmt = $pdo->prepare('SELECT COUNT(*) FROM `like` WHERE photo_id = ?');
$stmt->execute([$photoId]);

echo json_encode([
    'likes' => (int) $stmt->fetchColumn(),
    'liked' => $liked,
]);

