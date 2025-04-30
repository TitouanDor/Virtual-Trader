<?php
session_start();

// Connection BDD
try {
    $bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Changement de MDP
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_identifier = $_POST['user_identifier'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Verif si utilisateur existe
    $req = $bdd->prepare("SELECT id FROM joueur WHERE username = ? OR email = ?");
    $req->execute([$user_identifier, $user_identifier]);
    $user = $req->fetch();

    if ($user) {
        // Changement MDP BDD
        if ($new_password === $confirm_password) {
            // Hash the new password
            $mdp = password_hash($new_password, PASSWORD_DEFAULT);

            // MAJ du MDP dans la base de donnée
            $update_req = $bdd->prepare("UPDATE joueur SET mdp = ? WHERE id = ?");
            $update_req->execute([$mdp, $user['id']]);

            // MDP mis a jour, redirection vers la page de connection
            header("Location: index.html");
            exit();
        } else {
            $error = "Mot de passe incorrect.";
        }
    } else {
        $error = "Utilisateur n'existe pas.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSSFile/general.css">
    <link rel="stylesheet" href="CSSFile/mdp.css">
    <title>Recover Password</title>
</head>
<body>
<div class="bandeau">Recover Password</div>




    <div class="box_mdp">
        <form method="POST" action="">
            <label for="user_identifier">Username or Email:</label><br><input type="text" id="user_identifier" name="user_identifier" required><br><br>
            <label for="new_password">New Password:</label><br><input type="password" id="new_password" name="new_password" required><br><br>
            <label for="confirm_password">Confirm New Password:</label><br><input type="password" id="confirm_password" name="confirm_password" required><br><br>
            <input type="submit" value="Update Password">
            <?php if (isset($error)): ?>
                <p style="color: red;"><?php echo $error; ?></p>
            <?php endif; ?>
        </form>
    </div>

    <div class="banniere">
        <a href="profil.php">Profil</a>
        <a href="logout.php">Déconnexion</a>
    </div>
</body>
</html>