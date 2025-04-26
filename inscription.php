<?php
session_start();

if(isset($_SESSION['error'])){
    echo($_SESSION['error'] . "<br>");
    unset($_SESSION['error']);
}
?>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription VT</title>
    <link rel="stylesheet" href="CSSFile\inscription.css">
</head>
<body>
<div>
    <a href="index.php">
        <button>
        Retour
    </button>
    </a>
</div>
    <div name="box_inscription" class="box_inscription">
        <p>Inscription à Virtual-trader</p>
        <form action="inscriptionScript.php" method="post">
            <div class="group-from">
            <label>
                <input type="text" name="prenom" placeholder="Prénom" value="<?php echo isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : '' ?>">
            </label>
            </div>

            <div class="group-from">
            <label>
                <input type="text" name="nom" placeholder="Nom" value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : '' ?>">
            </label>
            </div>

            <div class="group-from">
                <label>
                    <input type="text" name="username" placeholder="Nom d'utilisateur" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                </label>
            </div>

            <div class="group-from">
            <label>
                <input type="text" name="email" placeholder="E-mail" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            </label>
            </div>

            <div class="group-from">
            <label>
                <input type="password" name="password" placeholder="mot de passe">
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

