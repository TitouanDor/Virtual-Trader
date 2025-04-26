php
<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id'])) {
    header("Location: index.html"); // Rediriger vers la page de connexion si non connecté
    exit();
}

// Récupérer l'ID de l'utilisateur suivi à partir de la requête POST
if (!isset($_POST['followed_user_id'])) {
    header("Location: profil.php"); // Rediriger vers le profil si l'ID est manquant
    exit();
}

$utilisateurSuiviId = htmlspecialchars($_POST['followed_user_id']);
$utilisateurId = $_SESSION['id'];

// Vérifier si l'utilisateur essaie de se suivre lui-même
if ($utilisateurSuiviId == $utilisateurId) {
    header("Location: profil.php"); // Rediriger vers le profil si l'utilisateur essaie de se suivre lui-même
    exit();
}

// Connexion à la base de données
$bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');

// Vérifier si l'utilisateur est déjà suivi
$req = $bdd->prepare("SELECT * FROM followers WHERE user_id = ? AND followed_user_id = ?");
$req->execute([$utilisateurId, $utilisateurSuiviId]);
$suivi = $req->fetch();

// Suivre ou ne plus suivre l'utilisateur
if ($suivi) {
    // Ne plus suivre
    $req = $bdd->prepare("DELETE FROM followers WHERE user_id = ? AND followed_user_id = ?");
    $req->execute([$utilisateurId, $utilisateurSuiviId]);
} else {
    // Suivre
    $req = $bdd->prepare("INSERT INTO followers (user_id, followed_user_id) VALUES (?, ?)");
    $req->execute([$utilisateurId, $utilisateurSuiviId]);
}

header("Location: profil.php"); // Rediriger vers le profil
?>