<?php
require_once __DIR__ . "/../tools/connexion.php";
require_once __DIR__ . "/../tools/auth.php";

$currentUserId = (int) ($_SESSION['user_id'] ?? 0);

$profilePhotoQuery = $pdo->prepare("SELECT pp_path FROM user WHERE id = ?");
$profilePhotoQuery->execute([$currentUserId]);
$profilePhoto = $profilePhotoQuery->fetchColumn() ?: "../tools/assets/icon.svg";

// 1. Toutes les photos + auteur + nombre de likes + "est-ce que j'ai liké ?"
$stmt = $pdo->prepare("
    SELECT
        p.id,
        p.image_path,
        p.title,
        p.description,
        p.posted_at,
        u.id AS author_id,
        u.username,
        u.pp_path AS author_photo_path,
        (SELECT COUNT(*) FROM `like` l WHERE l.photo_id = p.id) AS like_count,
        EXISTS(SELECT 1 FROM `like` l WHERE l.photo_id = p.id AND l.user_id = ?) AS liked_by_me
    FROM photo p
    JOIN user u ON u.id = p.user_id
    ORDER BY p.posted_at DESC
");
$stmt->execute([$currentUserId]);
$photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Commentaires de toutes ces photos, avec le pseudo de l'auteur
$commentsByPhoto = [];

if ($photos) {
    $photoIds = array_column($photos, 'id');
    $placeholders = implode(',', array_fill(0, count($photoIds), '?'));

    $stmt = $pdo->prepare("
        SELECT c.photo_id, c.content, c.written_at, u.id AS author_id, u.username,
            u.pp_path AS author_photo_path
        FROM comment c
        JOIN user u ON u.id = c.user_id
        WHERE c.photo_id IN ($placeholders)
        ORDER BY c.written_at ASC
    ");
    $stmt->execute($photoIds);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $comment) {
        $commentsByPhoto[$comment['photo_id']][] = $comment;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InstaCramme Main</title>
    <link rel="stylesheet" href="../tools/assets/style.css">
    <script src="js/comments.js" defer></script>
</head>

<script src="js/likes.js" defer></script>

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

        <section id="main-index-section">
            <h1 class="fx-ember">InstaCramme</h1>
            <div id="img-container">
                <?php foreach ($photos as $photo): ?>
                    <article class="brule">
                        <div class="post-author">
                            <a class="avatar-link" href="profile.php?id=<?= (int) $photo['author_id'] ?>">
                                <img class="user-avatar"
                                    src="<?= htmlspecialchars($photo['author_photo_path'] ?: '../tools/assets/icon.svg', ENT_QUOTES, 'UTF-8') ?>"
                                    alt="">
                            </a>
                            <a href="profile.php?id=<?= (int) $photo['author_id'] ?>">
                                <?= htmlspecialchars($photo['username'], ENT_QUOTES, 'UTF-8') ?></a>
                            <time>
                                <?= htmlspecialchars($photo['posted_at'], ENT_QUOTES, 'UTF-8') ?>
                            </time>
                        </div>

                        <img class="post-img" src="<?= htmlspecialchars($photo['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                            alt="<?= htmlspecialchars($photo['title'], ENT_QUOTES, 'UTF-8') ?>">

                        <h2><?= htmlspecialchars($photo['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <p><?= nl2br(htmlspecialchars($photo['description'], ENT_QUOTES, 'UTF-8')) ?></p>

                        <button type="button" class="like-btn<?= $photo['liked_by_me'] ? ' liked' : '' ?>"
                            data-photo-id="<?= (int) $photo['id'] ?>"
                            aria-pressed="<?= $photo['liked_by_me'] ? 'true' : 'false' ?>">
                            <span class="like-icon"><?= $photo['liked_by_me'] ? '❤️' : '🤍' ?></span>
                            <span class="like-count"><?= (int) $photo['like_count'] ?></span>
                        </button>

                        <ul class="post-comments" aria-label="Commentaires" aria-live="polite">
                            <?php foreach ($commentsByPhoto[$photo['id']] ?? [] as $comment): ?>
                                <li class="post-comment">
                                    <a class="avatar-link" href="profile.php?id=<?= (int) $comment['author_id'] ?>">
                                        <img class="user-avatar"
                                            src="<?= htmlspecialchars($comment['author_photo_path'] ?: '../tools/assets/icon.svg', ENT_QUOTES, 'UTF-8') ?>"
                                            alt="">
                                    </a>
                                    <a class="comment-author" href="profile.php?id=<?= (int) $comment['author_id'] ?>">
                                        <?= htmlspecialchars($comment['username'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                    :
                                    <span class="comment-content"><?= htmlspecialchars($comment['content'], ENT_QUOTES, 'UTF-8') ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <form class="comment-form" data-photo-id="<?= (int) $photo['id'] ?>">
                            <label class="visually-hidden" for="comment-<?= (int) $photo['id'] ?>">Ajouter un commentaire</label>
                            <input id="comment-<?= (int) $photo['id'] ?>" name="content" type="text"
                                maxlength="1000" placeholder="Ajouter un commentaire..." required>
                            <button type="submit">Publier</button>
                            <p class="comment-feedback" role="status" aria-live="polite"></p>
                        </form>
                    </article>
                <?php endforeach; ?>

                <?php if (!$photos): ?>
                    <p>Aucune photo pour le moment.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <a href="add-post.php" id="add-post-btn">+</a>
</body>

</html>