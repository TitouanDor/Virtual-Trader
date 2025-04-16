<?php
$bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
$nom = $_POST['nom'];
$prenom = $_POST['prenom'];
$email = $_POST['e-mail'];
$password = $_POST['mdp'];
$req = $bdd->prepare("INSERT INTO users(nom, prenom, email,mot_de_passe,argent) VALUES (?,?,?,?,?);");
$req->execute([$prenom,$nom,$email,$password,1000]);
header('Location: ..\index.html');
exit();
?>