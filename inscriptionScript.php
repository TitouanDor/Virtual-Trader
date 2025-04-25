<?php

$dbHost = "localhost";
$dbName = "virtual_trader";
$dbUser = "your_db_user"; // Replace with your database username
$dbPass = "your_db_password"; // Replace with your database password

$bdd = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
$nom = $_POST['nom'];
$prenom = $_POST['prenom'];
$email = $_POST['e-mail'];
$username = $_POST['username'];
$password = PASSWORD_HASH($_POST['mdp'], PASSWORD_DEFAULT);

$req = $bdd->prepare("SELECT email FROM joueur WHERE email = ?");
$req->execute([$email]);
$data = $req->fetch();

if($data != null){
    session_start();
    $_SESSION['valid'] = 1;
    header('Location: inscription.php');
    exit();
}

$req = $bdd->prepare("INSERT INTO joueur(email, mdp, nom,prenom,username,argent) VALUES (?,?,?,?,?,?);");
$req->execute([$email,$password,$nom,$prenom,$username,10000]);
header('Location: index.html');
exit();
?>