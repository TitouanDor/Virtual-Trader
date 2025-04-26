<?php
session_start();

// Database connection
$bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
$bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Sanitize and filter inputs
$nom = htmlspecialchars(filter_var($_POST['nom'], FILTER_SANITIZE_STRING));
$prenom = htmlspecialchars(filter_var($_POST['prenom'], FILTER_SANITIZE_STRING));
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$username = htmlspecialchars(filter_var($_POST['username'], FILTER_SANITIZE_STRING));
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

// Check if the email is valid
if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
    header('Location: inscription.php');
    exit();
}

// Check if username and email already exist
$checkReq = $bdd->prepare("SELECT COUNT(*) as count FROM joueur WHERE email = ? OR username = ?");
$checkReq->execute([$email, $username]);
$check = $checkReq->fetch();
if($check['count'] > 0){
    header('Location: inscription.php');
    exit();
}

// Insert new user
$insertReq = $bdd->prepare("INSERT INTO joueur (nom, prenom, email, username, password) VALUES (?, ?, ?, ?, ?)");
$insertReq->execute([$nom, $prenom, $email, $username, $password]);

$_SESSION['success'] = "Account created successfully! You can now log in";
header('Location: index.php');
exit();
?>