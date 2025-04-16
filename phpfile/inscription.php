<?php
$bdd = new PDO('mysql:host=localhost;dbname=..\virtual_trader;charset=utf8', 'root', '');
$nom = $_POST['nom'];
$prenom = $_POST['prenom'];
$email = $_POST['e-mail'];
$password = PASSWORD_HASH($_POST['mdp']);
$req = $bdd->prepare("INSERT INTO users(email, mdp, nom,prenom,argent) VALUES (?,?,?,?,?);");
$req->execute([$email,$password,$nom,$prenom]);
header('Location: ..\index.html');
exit();
?>