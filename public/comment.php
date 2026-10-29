<?php
require_once __DIR__ . "/../tools/auth.php";
require_once __DIR__ . "/../tools/connexion.php";

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

$userId = (int) ($_SESSION['user_id'] ?? 0);
$photoId = filter_input(INPUT_POST, 'photo_id', FILTER_VALIDATE_INT);
$submittedContent = $_POST['content'] ?? '';

if (!$photoId) {
    http_response_code(400);
    echo json_encode(['error' => 'Publication invalide']);
    exit;
}

if (!is_string($submittedContent)) {
    http_response_code(400);
    echo json_encode(['error' => 'Commentaire invalide']);
    exit;
}

$content = trim($submittedContent);
if ($content === '' || mb_strlen($content) > 1000) {
    http_response_code(400);
    echo json_encode(['error' => 'Le commentaire doit contenir entre 1 et 1000 caractères']);
    exit;
}

$stmt = $pdo->prepare('
    SELECT 1
    FROM photo
    WHERE id = ?
');
$stmt->execute([$photoId]);

if (!$stmt->fetchColumn()) {
    http_response_code(404);
    echo json_encode(['error' => 'Publication introuvable']);
    exit;
}

$stmt = $pdo->prepare('
    INSERT INTO comment (user_id, photo_id, content, written_at)
    VALUES (?, ?, ?, NOW())
');
$stmt->execute([$userId, $photoId, $content]);

$stmt = $pdo->prepare('
    SELECT id, written_at
    FROM comment
    WHERE id = ?
');
$stmt->execute([(int) $pdo->lastInsertId()]);
$comment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$comment) {
    http_response_code(500);
    echo json_encode(['error' => 'Impossible de récupérer le commentaire enregistré']);
    exit;
}

$stmt = $pdo->prepare('SELECT username, pp_path FROM user WHERE id = ?');
$stmt->execute([$userId]);
$author = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$author) {
    http_response_code(500);
    echo json_encode(['error' => 'Utilisateur introuvable']);
    exit;
}

echo json_encode([
    'id' => (int) $comment['id'],
    'photo_id' => $photoId,
    'content' => $content,
    'written_at' => $comment['written_at'],
    'author_id' => $userId,
    'username' => $author['username'],
    'author_photo_path' => $author['pp_path'],
]);
