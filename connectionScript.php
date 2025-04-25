<?php

// Check if email and password are set in the POST request
if (!isset($_POST['e-mail']) || !isset($_POST['mdp'])) {
    header('location: index.html');
    exit(); 
}

$email = $_POST['e-mail'];
$password = $_POST['mdp'];

// Database connection with username and password
try {
    $dbHost = 'localhost'; // Replace with your database host
    $dbName = 'virtual_trader'; 
    $dbUser = 'root'; 
    $dbPass = ''; 
    $bdd = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Erreur de connexion à la base de données : ' . $e->getMessage());
}

// Fetch user data based on email
$req = $bdd->prepare("SELECT * FROM joueur WHERE email = ?");
$req->execute([$email]);
$data = $req->fetch();

// Check if user exists and password is correct
if ($data && password_verify($password, $data['mdp'])) {
    session_start();
    $_SESSION['id'] = $data['id'];
    header('location: profil.php');
} else {
    header('location: index.html');
}
?>
