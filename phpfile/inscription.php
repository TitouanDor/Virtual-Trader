<?php
$bdd = new PDO('mysql:host=localhost;dbname=..\virtual_trader;charset=utf8', 'root', '');
$nom = $_POST['nom'];
$prenom = $_POST['prenom'];
$email = $_POST['e-mail'];
$password = PASSWORD_HASH($_POST['mdp']);

$req = $bdd->prepare("SELECT email FROM users WHERE email = ?");
$req->execute([$email]);
$data = $req->fetch();

if($data == null){
    session_start();
    $_SESSION['valid'] = 1;
    header('Location: ..\inscription.php');
    exit();
}

$req = $bdd->prepare("INSERT INTO users(email, mdp, nom,prenom,argent) VALUES (?,?,?,?,10000);");
$req->execute([$email,$password,$nom,$prenom]);
header('Location: ..\index.html');
exit();

?>