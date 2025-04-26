<?php
session_start();

// Connexion à la base de données
$bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', ''); 

// Nettoyage et filtrage des entrées
$nom = htmlspecialchars(filter_var($_POST['nom'], FILTER_SANITIZE_STRING));
$prenom = htmlspecialchars(filter_var($_POST['prenom'], FILTER_SANITIZE_STRING));
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$nomUtilisateur = htmlspecialchars(filter_var($_POST['username'], FILTER_SANITIZE_STRING));
$mdp = password_hash($_POST['password'], PASSWORD_DEFAULT);

// Vérification de la validité de l'email
if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
    header('Location: inscription.php');
    exit();
}

// Vérification si le nom d'utilisateur et l'email existent déjà
$verificationReq = $bdd->prepare("SELECT COUNT(*) as count FROM joueur WHERE email = ? OR username = ?");
$verificationReq->execute([$email, $nomUtilisateur]);
$verification = $verificationReq->fetch();

// Insertion du nouvel utilisateur
$insertionReq = $bdd->prepare("INSERT INTO joueur (nom, prenom, email, username, mdp) VALUES (?, ?, ?, ?, ?)");
$insertionReq->execute([$nom, $prenom, $email, $nomUtilisateur, $mdp]);

$_SESSION['success'] = "Compte créé avec succès ! Vous pouvez maintenant vous connecter";
header('Location: index.php');
exit();
?>