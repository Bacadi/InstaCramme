<?php
require_once __DIR__ . "/../tools/connexion.php";
require_once __DIR__ . "/../tools/auth.php";

$user_id = filter_input(INPUT_GET,"id", FILTER_VALIDATE_INT);
if (!$user_id) {
    header("Location: index.php");
    exit;
}
//check user
$usercheck= $pdo->prepare("SELECT username FROM user WHERE id = ?");
$usercheck->execute([$user_id]);
$user = $usercheck->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: index.php");
    exit;
}

// user images
$userimgs = $pdo->prepare("SELECT posted_at,image_path FROM photo WHERE user_id = ? ORDER BY posted_at DESC");
$userimgs->execute([$user_id]);
$imgs= $userimgs->fetchAll(PDO::FETCH_ASSOC);

//user comments
$usercom = $pdo->prepare("SELECT photo_id, content, written_at FROM comment WHERE user_id = ? ORDER BY written_at DESC");
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
</head>
<script src="js/likes.js" defer></script>
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
                <p><?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div id="profile-nav">
                <button id="shared-img">Photos partagées</button>
                <button id="comments-sent">Commentaires</button>
            </div>
        </section>
    </main>
    <a href="add-post.php" id="add-post-btn">+</a>
</body>

</html>