<?php
session_start();
if (!isset($_SESSION['id'])) {
    header('location: index.html');
    exit();
}

if(isset($_POST["email"]) && isset($_POST["mdp"])) {

    $email = $_POST["email"];
    $password = $_POST["mdp"];
    $yes = $_POST["yes"];

    $bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
    $req = $bdd->prepare("SELECT id, mdp, email FROM joueur WHERE email = ?");
    $req->execute([$email]);
    $user = $req->fetch();

    if (password_verify($password, $user['mdp']) && ($email == $user['email']) && ($_SESSION['id']==$user['id']) && ($yes=="on")) {
        $req = $bdd->prepare("DELETE FROM portefeuille WHERE joueur_id = ?");
        $req->execute([$_SESSION['id']]);
        $req->fetch();
        $req = $bdd->prepare("UPDATE joueur SET argent = 10000 WHERE id = ?");
        $req->execute([$_SESSION['id']]);
        $req->fetch();
        $_SESSION['rei'] = true;
        header('Location: profil.php');
        exit();
    } else {
        $_SESSION['rei'] = false;
        header('Location: reinitialisation.php');
        exit();
    }
} else {
        $_SESSION['rei'] = false;
        header('Location: reinitialisation.php');
        exit();
    }

?>
