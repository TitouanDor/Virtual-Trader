<?php
session_start();
try {
    $bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de connexion à la base de données : " . $e->getMessage();
    header('Location: inscription.php');
    exit();
}
$nom = $_POST['nom'];
$prenom = $_POST['prenom'];
$email = $_POST['e-mail'];
$username = $_POST['username'];

$req = $bdd->prepare("SELECT email FROM joueur WHERE email = ?");
try {
    $req->execute([$email]);
    $data = $req->fetch();
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la vérification de l'email : " . $e->getMessage();
    header('Location: inscription.php');
    exit();
}

if ($data != null) {
    $_SESSION['valid'] = 1;
    $_SESSION['error'] = "Cet email est déjà utilisé";
    header('Location: inscription.php');
    exit();
}

try {
    $req = $bdd->prepare("INSERT INTO joueur(email, mdp, nom, prenom, username, argent) VALUES (?,?,?,?,?,?);");
    $req->execute([$email, $password, $nom, $prenom, $username, 10000.00]);

    $_SESSION['success'] = "You are now registered!";
    header('Location: index.html');
    exit();
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de l'insertion du joueur : " . $e->getMessage();
    header('Location: inscription.php');
    exit();
}






exit();
?>