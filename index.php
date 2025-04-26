<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="CSSFile/index.css">
    <title>Virtual-trader</title>
</head>
<body>

<a href="leaderboard.php">Leaderboard</a>
<br>


<?php if (!isset($_SESSION['id'])): ?>
    <div name="box_connection" class="box_connection">
        <p>Connection à Virtual-trader</p>
        <form action="connectionScript.php" method="post">
            <div class="group-from">
                <label for="email">
                    <input type="text" name="email" id="email" placeholder="E-mail" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </label>
            </div>
            <div class="group-from">
                <label for="password">
                    <input type="password" name="password" id="password" placeholder="mot de passe">
                </label>
            </div>
            <div class="group-from">
                <label>
                    <input type="submit" value="Se connecter">
                </label>
            </div>
        </form>
        <p><a href="inscription.php">Inscription</a></p>
    </div>
<?php else: ?>
    <div>
        <p>Bienvenue!</p>
    </div>
<?php endif; ?>

</body>
</html>