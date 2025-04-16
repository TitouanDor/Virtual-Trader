<?php
    session_start();
    if($_SESSION['valid'] == 0) {
        echo ('Email deja utilise');
    }
?>

<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription VT</title>
    <link rel="stylesheet" href="CSSFile\inscription.css">
</head>
<body>
    <div name="box_inscription" class="box_inscription">
        <p>Inscription à Virtual-trader</p>
        <form action="phpfile\inscription.php" method="post">
            <div class="group-from">
            <label>
                <input type="text" name="prenom" placeholder="Prénom">
            </label>
            </div>

            <div class="group-from">
            <label>
                <input type="text" name="nom" placeholder="Nom">
            </label>
            </div>

            <div class="group-from">
            <label>
                <input type="text" name="e-mail" placeholder="E-mail">
            </label>
            </div>

            <div class="group-from">
            <label>
                <input type="password" name="mdp" placeholder="mot de passe">
            </label>
            </div>

            <div class="group-from">
            <label>
                <input type="submit" value="S'inscrire">
            </label>
            </div>
        </form>
    </div>
</body>

