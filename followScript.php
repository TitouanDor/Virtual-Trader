<?php
session_start();

// Verif si joueur connecte
if (!isset($_SESSION['id'])) {
    header("Location: index.html");
    exit();
}

// Recup id du joueur
if (!isset($_POST['followed_user_id'])) {
    header("Location: profil.php");
    exit();
}

$followedUserId = htmlspecialchars($_POST['followed_user_id']);
$userId = $_SESSION['id'];

// Verif si le joueur essaye de se suivre lui-même
if ($followedUserId == $userId) {
    header("Location: profil.php");
    exit();
}

// Connection BDD
$bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');

// Verif si le joueur suit deja ce joueur
$req = $bdd->prepare("SELECT * FROM followers WHERE user_id = ? AND followed_user_id = ?");
$req->execute([$userId, $followedUserId]);
$following = $req->fetch();

// Follow/Unfollow
if ($following) {
    // Unfollow
    $req = $bdd->prepare("DELETE FROM followers WHERE user_id = ? AND followed_user_id = ?");
    $req->execute([$userId, $followedUserId]);
} else {
    // Follow
    $req = $bdd->prepare("INSERT INTO followers (user_id, followed_user_id) VALUES (?, ?)");
    $req->execute([$userId, $followedUserId]);
}

header("Location: profil.php");
?>