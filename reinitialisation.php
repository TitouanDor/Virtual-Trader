<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Réinitialisation</title>
    <link rel="stylesheet" href="CSSFile/general.css">
    <link rel="stylesheet" href="CSSFile/reinitialisation.css">
</head>

<body>

    <div class="box_connection">
        <p>Réinitialiser mon compte</p>
        <form action="connectionScript.php" method="post">
            <div class="group-from">
                <label for="email">
                    <input type="text" name="email" id="email" placeholder="Adresse e-mail">
                </label>
            </div>
            <div class="group-from">
                <label for="mdp">
                    <input type="password" name="mdp" id="mdp" placeholder="Mot de passe">
                </label>
            </div>
            <div class="group-from">
                <label>
                    êtes-vous sur ?
                    <input type="checkbox" name="sur?">
                </label>
            </div>
            <div class="group-from">
                <label>
                    <input type="submit" value="Réinitialiser">
                </label>
            </div>
        </form>

        <?php if(isset($_SESSION["rei"]) && $_SESSION["rei"] == false):?>
        <p>
            Réinitialisation impoosible
        </p>
        <?php endif;?>
    </div>

    <div class="banniere">
        <a href="profil.php">Profil</a>
        <a href="logout.php">Déconnexion</a>
    </div>

</body>
</html>