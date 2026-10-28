<?php
require_once __DIR__ . "/../tools/connexion.php";
require_once __DIR__ . "/../tools/auth.php";

$currentUserId = (int) ($_SESSION['user_id'] ?? 0);

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
        SELECT c.photo_id, c.content, c.written_at, u.id AS author_id, u.username
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
                <a href="profile.php?id=<?= (int) $_SESSION['user_id'] ?>"><img id="profile-icon"
                        src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAACXBIWXMAAAsTAAALEwEAmpwYAAAFLklEQVR4nO2a328VRRSAv6KlEGoM6h+AgFRiCwQCRYKJUQsiBB4kwfjgE1rf1AfE+geoKIRKtD74pNYIVt8UNEhMCsRQlQeDUYo/qsQXbQlSqlSa1kxybnJzcvbemd3ZvSXZL5lkc++cc3Znds85c2agpKSkpKQkb9qAbuAt4ATwE3AJ+E/aJfnN/dcnfZdxg7MCOABcBGZStt+B/aLrhqAJ2AqczvDQSe0U8IjYmJWsAc7k8OC6fQWsZhYxD+gFpmrc9BXgU2AvsE18wkKgWdpC+W2b9DkKjNfQ52wdBFoa/fBLgbMJNzkNfAI8mvJG3cDulIGbTrDxLbCEBnEvMJbw4IeB9oi2OoAPEwZhFOikYDYBV42b+RG4P0e7DwDnDbvuXrooiHUJD/8u0FqA/fnA24b9CWBDEd/8mPHK76F4XjB8w1/A4rwMzjMcnruB3QE6msWx9QM/yJs0Ltf98p/r48uTxiB8nVd06DVeu5CZ3wEMe8T5Yenry15Dh8seoyc5U8rIO56yc4CXA5MdN6sviawP7yn568AqItFkZHjO2y/wlA99+OrmBsGHVuPtcqlzFLYas+Mb6nYY3+i/ksWtk0FcINe98p+2td3T1oPGAG4mAqeVUpfk+NBszMpvdRKkDlkBap/g6xgHlOwgGVlpzMg9nrI7jZn3yQ7dIFxTsk6XDyuMNy5TRnpAKXO5vS/9Sta99r68rmSdk/PlqJJ9lQxcTDkTiKOsll0bINtpOF1fdinZEVLSZixpQxKMK0o+JE2+xbAdkirrVN1lsME8rZS4ZWkIf0ccgMuBtj9T8k+Rgj6lxGVcIZxT8i7U+bJeyX4XaLtHyb9BCk4oJa5aE4LOzlyc9+VQyqyzwnYlf5wU/KqUhJaptxhh0IU4n9Crw+DDgbbvVvI/k4JRpeT2QPmbge+NRKijThzXidA50RXCHcYyOZhJpWRuCh33GYnJNYnz68Uxtsr1IWPmnezGFHZbDJsNGQDHM0pPSHuedEQZgNGMn0CFOYZD9WlfBCyHc/kEfsnoBCvOKMtmyRnRkcZutR6371h4GNxiJENp2uUUUSBKGOzLkAhtMhxa5Vv8AHhMUu1KPaBNfjts+J6KXFfRiVC3UuJWWT7clbCtdQRY5CF/Z8ImyLjo9uFzJeuKp8EsM27AVYZrcRMwpOSmJBKE8qxRhxwSG/UWQxMxFkMYSUm95fATxsylefgKzxn6nI1ClsNIedm3IOKKpxeM1z4rA0aJrNYZgWOq/74sxtuNzCwpld2o+k5G2qVZZDjUDTXWETrz9Fl/1OSUUugclMU+1c95+1gcUbpfSej3ceyiKHIsRX+HbpdWM6j6uG8xFo8r3V8afR4y7tOF48w0ybGUasXnjQrPH6pPzFNeujznapW6gqT9z8mI9llthCS9P6A3NWJulbcq3f/UKb5Mxdwaq3DQeMXcFnWjedG4r9fyMNQCfGNEhVQFx0h0G15/KMPSvS5LjGXydIqCaayZ1w//p6TSubI2Idf/CLg1b+Pi8N437E/Iwa1C6Eo4JzQsu7R52tXefkYmxIXBQumUSsuM0QYin+9daSQ51a99yJ5DVBbLmRzrxqZlCb1LVmihzJcawbEaByWHivjmfaLDfjmWMpPQrsp2VY9Ua5YDt4m3nivXy+VARY+s5/WStrpdl1CXm7dPwypj3ZBHOymfxKxls7EuiNEGY+X2RdEuhxNGMjz0iKwyY547bghLpS73plRoL8iJ00lpYxJCj0uf3VnKWCUlJSUlJXjxP1+hj/GGg7fcAAAAAElFTkSuQmCC"
                        alt="user-male-circle"></a>
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
                    <article>
                        <div>
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