<?php
try {
    $bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
} catch (PDOException $e) {
    echo "Erreur de connexion à la base de données : " . $e->getMessage();
    exit();
}
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