<?php

if (!isset($_POST['e-mail'])){
    header('location: ..\index.html');
}

if (!isset($_POST['mdp'])){
    header('location: ..\index.html');
}
$email = $_POST['e-mail'];
$password = PASSWORD_HASH($_POST['mdp']);

//faire la requete

$bdd = new PDO('mysql:host=localhost;dbname=..\virtual_trader;charset=utf8', 'root', '');
$req = $bdd->prepare("SELECT * FROM users WHERE email = ?"); //a modifier
$req->execute([$email]);
$data = $req->fetch();

if($data!=null && password_verify($password, $data['password'])){
    session_start();
    $_SESSION['id'] = $data['id'];
    header('location: ..\profil.html');
}
else{
    header('location: ..\profil.html');
}
?>
