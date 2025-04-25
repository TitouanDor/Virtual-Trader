php
<?php
session_start();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Changer le mot de passe</title>
</head>
<body>

    <h1>Changer le mot de passe</h1>

    <?php
    if (isset($_SESSION['error'])) {
        echo "<p>" . str_replace("\n", "<br>", $_SESSION['error']) . "</p>";
        unset($_SESSION['error']);
    }
    ?>

    <form action="changePasswordScript.php" method="post">
        <div>
            <label for="current_password">Mot de passe actuel:</label>
            <input type="password" id="current_password" name="current_password" required>
        </div>
        <div>
            <label for="new_password">Nouveau mot de passe:</label>
            <input type="password" id="new_password" name="new_password" required>
        </div>
        <div>
            <label for="confirm_password">Confirmer le nouveau mot de passe:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        <div>
            <input type="submit" value="Changer le mot de passe">
        </div>
    </form>
    <a href="profil.php">Retour au profil</a>

</body>
</html>