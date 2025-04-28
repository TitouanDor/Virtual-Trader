<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: index.html");
    exit();
}

// Get the followed user ID from POST
if (!isset($_POST['followed_user_id'])) {
    header("Location: profil.php");
    exit();
}

$followedUserId = htmlspecialchars($_POST['followed_user_id']);
$userId = $_SESSION['id'];

// Check if the user is trying to follow themselves
if ($followedUserId == $userId) {
    header("Location: profil.php");
    exit();
}

// Database connection
$bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');

// Check if the user is already following the player
$req = $bdd->prepare("SELECT * FROM followers WHERE user_id = ? AND followed_user_id = ?");
$req->execute([$userId, $followedUserId]);
$following = $req->fetch();

// Follow or unfollow the user
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