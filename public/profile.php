<?php
require_once __DIR__ . "/../tools/connexion.php";
require_once __DIR__ . "/../tools/auth.php";

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Vérifier que le formulaire a été envoyé
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Récupérer l'identifiant de l'utilisateur connecté
    $logged_user_id = (int) ($_SESSION['user_id'] ?? 0);

    // Récupérer l'identifiant du profil consulté
    $user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    // Vérifier que l'utilisateur modifie son propre profil
    if ($logged_user_id <= 0 || !$user_id || $logged_user_id !== $user_id) {
        http_response_code(403);
        exit("Modification non autorisée.");
    }

    // Vérifier le jeton de sécurité CSRF
    $submitted_token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submitted_token) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $submitted_token)
    ) {
        exit("Erreur de sécurité. Recharge la page et réessaie.");
    }

    // Récupérer les informations du formulaire
    $username = trim($_POST['username'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    // Vérifier les données
    if ($username === '') {
        exit("Le pseudo ne peut pas être vide.");
    }

    if (mb_strlen($username) > 100) {
        exit("Le pseudo ne doit pas dépasser 100 caractères.");
    }

    if (mb_strlen($bio) > 255) {
        exit("La bio ne doit pas dépasser 255 caractères.");
    }


    // Conserver le chemin de la photo actuelle
    $currentPhoto = $pdo->prepare(
        "SELECT pp_path FROM user WHERE id = ?"
    );
    $currentPhoto->execute([$logged_user_id]);
    $oldPhotoPath = $currentPhoto->fetchColumn();

    // Par défaut, conserver l'ancienne photo
    $photoPath = $oldPhotoPath;

    // Vérifier si une nouvelle photo a été sélectionnée
    if (
        isset($_FILES['profile_photo']) &&
        $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        $file = $_FILES['profile_photo'];

        // Vérifier que l'envoi du fichier a réussi
        if ($file['error'] !== UPLOAD_ERR_OK) {
            exit("Erreur lors de l'envoi de la photo.");
        }

        // Limiter la taille à 5 Mo
        if ($file['size'] > 5 * 1024 * 1024) {
            exit("La photo ne doit pas dépasser 5 Mo.");
        }

        // Vérifier le véritable type de fichier
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        if (!isset($allowedTypes[$mimeType])) {
            exit("Format non autorisé. Utilise JPG, PNG ou WebP.");
        }

        // Vérifier que le fichier est bien une image
        if (getimagesize($file['tmp_name']) === false) {
            exit("Le fichier envoyé n'est pas une image valide.");
            $imageInfo = getimagesize($file['tmp_name']);
        }

        if ($imageInfo === false) {
            exit("Le fichier envoyé n'est pas une image valide.");
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];

        if ($width > 1000 || $height > 1000) {
            exit("La photo ne doit pas dépasser 1000 × 1000 pixels.");
        }


        // Dossier de destination sur le serveur
        $uploadDirectory = __DIR__ . '/uploads/profiles/';

        if (
            !is_dir($uploadDirectory) &&
            !mkdir($uploadDirectory, 0755, true)
        ) {
            exit("Impossible de créer le dossier des photos.");
        }

        if (!is_writable($uploadDirectory)) {
            exit("Le dossier des photos n'est pas accessible en écriture.");
        }

        // Générer un nom de fichier aléatoire
        $filename = bin2hex(random_bytes(16))
            . '.' . $allowedTypes[$mimeType];

        $destination = $uploadDirectory . $filename;

        // Déplacer le fichier temporaire vers le dossier définitif
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            exit("Impossible d'enregistrer la photo sur le serveur.");
        }

        // Chemin qui sera enregistré dans la base de données
        $photoPath = 'uploads/profiles/' . $filename;
    }

    // Enregistrer le pseudo, la bio et le chemin de la photo
    $update = $pdo->prepare(
        "UPDATE user
         SET username = :username,
             bio = :bio,
             pp_path = :pp_path
         WHERE id = :id"
    );

    $update->execute([
        ':username' => $username,
        ':bio' => $bio,
        ':pp_path' => $photoPath,
        ':id' => $logged_user_id
    ]);

    // Rediriger vers le profil actualisé
    header("Location: profile.php?id=" . $logged_user_id);
    exit;
}

$user_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$user_id) {
    header("Location: index.php");
    exit;
}
//check user
$usercheck = $pdo->prepare("SELECT username, pp_path, bio FROM user WHERE id = ?");
$usercheck->execute([$user_id]);
$user = $usercheck->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: index.php");
    exit;
}
$isOwnProfile = (int) $_SESSION['user_id'] === (int) $user_id;

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
$postCount = count($imgs);
$totalLikeCount = array_sum(array_column($imgs, 'like_count'));

$commentsByPhoto = [];
if ($imgs) {
    $photoIds = array_column($imgs, 'id');
    $placeholders = implode(',', array_fill(0, count($photoIds), '?'));

    $photoComments = $pdo->prepare("
        SELECT c.id, c.photo_id, c.content, c.written_at, u.id AS author_id, u.username,
            u.pp_path AS author_photo_path
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
        owner.username AS photo_owner_name,
        commenter.id AS author_id,
        commenter.username AS author_name,
        commenter.pp_path AS author_photo_path
    FROM comment c
    LEFT JOIN photo p ON p.id = c.photo_id
    LEFT JOIN user owner ON owner.id = p.user_id
    JOIN user commenter ON commenter.id = c.user_id
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
    <link rel="stylesheet" href="../tools/assets/style.css?v=3">
    <script src="js/comments.js" defer></script>
    <script src="js/profile.js" defer></script>
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
                <img id="pp" src="<?= htmlspecialchars($user['pp_path'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <div class="profile-username">
                    <p><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php if ($isOwnProfile): ?>
                        <button type="button" id="edit-profile-btn">Modifier</button>
                    <?php endif; ?>
                </div>
                <div class="profile-stats">
                    <p class="profile-post-count">
                        <strong><?= $postCount ?></strong>
                        <?= $postCount === 1 ? 'publication' : 'publications' ?>
                    </p>
                    <p class="profile-post-count">
                        <strong><?= $totalLikeCount ?></strong>
                        <?= $totalLikeCount === 1 ? 'like' : 'likes' ?>
                    </p>
                </div>
                <p id="bio"><?= htmlspecialchars($user['bio'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <?php if ($isOwnProfile): ?>
                <form id="edit-profile-form" method="POST" action="profile.php?id=<?= (int) $user_id ?>"
                    enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="text" name="username" maxlength="100" required
                        value="<?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Entrer votre pseudo">
                    <textarea id="profile-bio" name="bio" maxlength="255"
                        placeholder="Entrer votre bio"><?= htmlspecialchars($user['bio'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    <output id="bio-character-count" for="profile-bio"
                        aria-live="polite"><?= mb_strlen($user['bio'] ?? '') ?>/255</output>
                    <label for="profile-photo">Nouvelle photo de profil</label>
                    <input type="file" id="profile-photo" name="profile_photo" accept="image/jpeg,image/png,image/webp">
                    <button type="submit">Enregistrer</button>
                    <button type="button" id="cancel-edit">Annuler</button>
                </form>
            <?php endif; ?>
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
                                    <a class="profile-comment-photo"
                                        href="profile.php?id=<?= (int) $comment['photo_owner_id'] ?>&view=photos">
                                        <img src="<?= htmlspecialchars($comment['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                                            alt="Photo commentée">
                                    </a>
                                <?php endif; ?>
                                <div class="profile-comment-details">
                                    <div class="profile-comment-author">
                                        <img class="user-avatar"
                                            src="<?= htmlspecialchars($comment['author_photo_path'] ?: '../tools/assets/icon.svg', ENT_QUOTES, 'UTF-8') ?>"
                                            alt="">
                                        <a href="profile.php?id=<?= (int) $comment['author_id'] ?>">
                                            <?= htmlspecialchars($comment['author_name'], ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </div>
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
                                    <div class="profile-post-author">
                                        <a class="avatar-link" href="profile.php?id=<?= (int) $user_id ?>">
                                            <img class="user-avatar"
                                                src="<?= htmlspecialchars($user['pp_path'] ?: '../tools/assets/icon.svg', ENT_QUOTES, 'UTF-8') ?>"
                                                alt="">
                                        </a>
                                        <a href="profile.php?id=<?= (int) $user_id ?>">
                                            <?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </div>
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
                                            <span class="like-icon"
                                                aria-hidden="true"><?= $img['liked_by_me'] ? '❤️' : '🤍' ?></span>
                                            <span class="like-count"><?= (int) $img['like_count'] ?></span>
                                            <span class="like-label"><?= (int) $img['like_count'] === 1 ? 'like' : 'likes' ?></span>
                                        </span>
                                    </div>
                                    <ul class="post-comments" aria-label="Commentaires" aria-live="polite">
                                        <?php foreach ($commentsByPhoto[$img['id']] ?? [] as $comment): ?>
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
                                                <span
                                                    class="comment-content"><?= htmlspecialchars($comment['content'], ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php if ($isOwnProfile): ?>
                                                    <form method="post" action="delete_comment.php" class="delete-comment-form"
                                                        onsubmit="return confirm('Supprimer ce commentaire ?');">
                                                        <input type="hidden" name="comment_id" value="<?= (int) $comment['id'] ?>">
                                                        <input type="hidden" name="csrf_token"
                                                            value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                                        <button type="submit" class="delete-comment-btn"
                                                            aria-label="Supprimer ce commentaire">🗑</button>
                                                    </form>
                                                <?php endif; ?>
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