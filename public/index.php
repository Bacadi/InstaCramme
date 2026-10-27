<?php
require_once __DIR__ . "/../tools/connexion.php";
require_once __DIR__ . "/../tools/auth.php";


?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InstaCramme Main</title>
    <link rel="stylesheet" href="../tools/assets/style.css">
</head>

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
            <h1>InstaCramme</h1>
            <div id="img-container">
                <?= htmlspecialchars($_SESSION["user_id"], ENT_QUOTES, 'UTF-8') ?>
            </div>
        </section>
    </main>
    <a href="add-post.php" id="add-post-btn">+</a>
</body>

</html>