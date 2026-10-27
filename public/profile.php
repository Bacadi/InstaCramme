<?php
require_once __DIR__ . "/../tools/connexion.php";
require_once __DIR__ . "/../tools/auth.php";

$user_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$user_id) {
    header("Location: index.php");
    exit;
}
//check user
$usercheck = $pdo->prepare("SELECT username FROM user WHERE id = ?");
$usercheck->execute([$user_id]);
$user = $usercheck->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: index.php");
    exit;
}

// Photos partagées par l'utilisateur
$userimgs = $pdo->prepare("
    SELECT
        p.id,
        p.image_path,
        p.posted_at,
        (SELECT COUNT(*) FROM `like` l WHERE l.photo_id = p.id) AS like_count,
        EXISTS(
            SELECT 1
            FROM `like` l
            WHERE l.photo_id = p.id AND l.user_id = ?
        ) AS liked_by_me
    FROM photo p
    WHERE p.user_id = ?
    ORDER BY posted_at DESC
");
$userimgs->execute([(int) ($_SESSION['user_id'] ?? 0), $user_id]);
$imgs = $userimgs->fetchAll(PDO::FETCH_ASSOC);

$commentsByPhoto = [];
if ($imgs) {
    $photoIds = array_column($imgs, 'id');
    $placeholders = implode(',', array_fill(0, count($photoIds), '?'));

    $photoComments = $pdo->prepare("
        SELECT c.photo_id, c.content, c.written_at, u.id AS author_id, u.username
        FROM comment c
        JOIN user u ON u.id = c.user_id
        WHERE c.photo_id IN ($placeholders)
        ORDER BY c.written_at ASC
    ");
    $photoComments->execute($photoIds);

    foreach ($photoComments->fetchAll(PDO::FETCH_ASSOC) as $comment) {
        $commentsByPhoto[$comment['photo_id']][] = $comment;
    }
}

// Commentaires laissés par l'utilisateur
$usercom = $pdo->prepare("
    SELECT
        c.photo_id,
        c.content,
        c.written_at,
        p.image_path,
        p.user_id AS photo_owner_id,
        owner.username AS photo_owner_name
    FROM comment c
    LEFT JOIN photo p ON p.id = c.photo_id
    LEFT JOIN user owner ON owner.id = p.user_id
    WHERE c.user_id = ?
    ORDER BY c.written_at DESC
");
$usercom->execute([$user_id]);
$comments = $usercom->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="../tools/assets/style.css">
    <script src="js/comments.js" defer></script>
</head>

<body>
    <main>
        <header>
            <img id="main-icon" src="../tools/assets/icon.svg" alt="Site Icon">
            <nav id="main-nav">
                <a href="index.php"><img id="home-icon"
                        src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAACXBIWXMAAAsTAAALEwEAmpwYAAABUUlEQVR4nO2XUW7DIBBE5z7tcTrio7lHPnvwRKKqlEQVgnYxGK9hnoQU25JhHmTBgBBCiMMIj7YkBHADcAdwwaLh46MtJYFJ+KUksBB+CQkhE/5WuBdWmPk7gE8AH4VnlxXCP5lWAg3hp5XAivBbJKQF9PThayW4FcCG8DUSXApgh/BWCe4EsGN4iwRXAsKOB5rSu90I4A4zb1kJLgRwQHirhKnDWyRM85/f0ncc+QHFA2bezbGZDsIfJoGOwg+XQIfhh0kIRxcdAyEzvi5FmV62HQO5MTatBHo7ePxD7CmBXo+efxB7SXj3/vFRIFcD0uufbCaumWp/NgHp7vBV+8JrstVtFRAb29Z+8EtCdfgnb69f5xSQZmginlRAN6IEYMjAJAASAAmABEACIAGQAOw/PjMSAAmABEACIAGQALQ0L/1IgFZAJcsLEEIIIQSm4huBGylKDztbegAAAABJRU5ErkJggg=="
                        alt="home-page"></a>
            </nav>
            <a href="../tools/logout.php">
                <img id="logout-icon"
                    src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAACXBIWXMAAAsTAAALEwEAmpwYAAAAhElEQVR4nO3Wyw2AIBBF0asl0BmxEgsx1mVJuhljglsXDuEh8hKWwEn4zEBPpbFM4/uAt+kAqwFwKAE7EJWA6Nk8yxkCAZhVgBHY0hqrAnBlShfxRgwUBrgQ9gDwfM1LEwDPEUgv4f+eYVB+RNExt41iZOpybDU0JNYEwNRtuakAPZTKCVq5tsvI/7GqAAAAAElFTkSuQmCC"
                    alt="exit">
            </a>
        </header>


        <section id="main-profile-section">
            <div id="profile-info">
                <p><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div id="profile-nav">
                <button type="button" id="shared-img"
                    class="<?= (($_GET['view'] ?? 'photos') === 'photos') ? 'active' : '' ?>"
                    onclick="window.location.href='?id=<?= $user_id ?>&view=photos'">
                    Photos partagées
                </button>

                <button type="button" id="comments-sent"
                    class="<?= (($_GET['view'] ?? 'photos') === 'comments') ? 'active' : '' ?>"
                    onclick="window.location.href='?id=<?= $user_id ?>&view=comments'">
                    Commentaires
                </button>
            </div>

            <?php if (($_GET['view'] ?? 'photos') === 'comments'): ?>

                <section id="comments-content">
                    <?php if (!empty($comments)): ?>
                        <?php foreach ($comments as $comment): ?>
                            <article class="profile-comment">
                                <?php if ($comment['image_path']): ?>
                                    <a class="profile-comment-photo" href="profile.php?id=<?= (int) $comment['photo_owner_id'] ?>&view=photos">
                                        <img src="<?= htmlspecialchars($comment['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                                            alt="Photo commentée">
                                    </a>
                                <?php endif; ?>
                                <div class="profile-comment-details">
                                    <p><?= nl2br(htmlspecialchars($comment['content'], ENT_QUOTES, 'UTF-8')) ?></p>
                                    <small>
                                        <?php if ($comment['photo_owner_name'] !== null): ?>
                                            Sur la publication de
                                            <a href="profile.php?id=<?= (int) $comment['photo_owner_id'] ?>">
                                                <?= htmlspecialchars($comment['photo_owner_name'], ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                            ·
                                        <?php endif; ?>
                                        <?= htmlspecialchars($comment['written_at'], ENT_QUOTES, 'UTF-8') ?>
                                    </small>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Aucun commentaire pour le moment.</p>
                    <?php endif; ?>
                </section>

            <?php else: ?>

                <section id="shared-content">
                    <?php if (!empty($imgs)): ?>
                        <div class="profile-gallery">
                            <?php foreach ($imgs as $img): ?>
                                <article class="profile-photo-card">
                                    <img src="<?= htmlspecialchars(
                                    $img['image_path'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                    ) ?>" alt="Photo partagée">
                                    <div class="profile-photo-details">
                                        <time datetime="<?= htmlspecialchars(
                                            $img['posted_at'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>">
                                            <?= htmlspecialchars(
                                                $img['posted_at'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </time>
                                        <span class="profile-like-count"
                                            aria-label="<?= (int) $img['like_count'] ?> <?= (int) $img['like_count'] === 1 ? 'like' : 'likes' ?>">
                                            <span class="like-icon" aria-hidden="true"><?= $img['liked_by_me'] ? '❤️' : '🤍' ?></span>
                                            <span class="like-count"><?= (int) $img['like_count'] ?></span>
                                            <span class="like-label"><?= (int) $img['like_count'] === 1 ? 'like' : 'likes' ?></span>
                                        </span>
                                    </div>
                                    <ul class="post-comments" aria-label="Commentaires" aria-live="polite">
                                        <?php foreach ($commentsByPhoto[$img['id']] ?? [] as $comment): ?>
                                            <li class="post-comment">
                                                <a class="comment-author" href="profile.php?id=<?= (int) $comment['author_id'] ?>">
                                                    <?= htmlspecialchars($comment['username'], ENT_QUOTES, 'UTF-8') ?>
                                                </a>
                                                :
                                                <span class="comment-content"><?= htmlspecialchars($comment['content'], ENT_QUOTES, 'UTF-8') ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>Aucune photo partagée pour le moment.</p>
                    <?php endif; ?>
                </section>

            <?php endif; ?>
        </section>
    </main>
    <a href="add-post.php" id="add-post-btn">+</a>
</body>

</html>