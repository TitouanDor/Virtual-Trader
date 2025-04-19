<?php

if (!isset($_POST['e-mail'])){
    header('location: index.html');
}

if (!isset($_POST['mdp'])){
    header('location: index.html');
}
$email = $_POST['e-mail'];
$password = PASSWORD_HASH($_POST['mdp'], PASSWORD_DEFAULT);

//faire la requete

$bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
$req = $bdd->prepare("SELECT * FROM joueur WHERE email = ?");
$req->execute([$email]);
$data = $req->fetch();

if($$password==$data['password']){
    session_start();
    $_SESSION['id'] = $data['id'];
    header('location: profil.php');
}
else{
    header('location: inscription.php');
}
?>
