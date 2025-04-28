<?php
session_start();

$search = $_POST['search'];
if (!isset($_POST['search'])) {
    header('location: profil.php');
    exit();
}

try {
    $bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $req = $bdd->prepare("SELECT id, username, email, argent FROM joueur WHERE username = ? OR email = ?");
    $req->execute([$search,$search]);
    $result = $req->fetch();

    if ($result) {
        $_SESSION['search_result'] = $result;
        header('Location: profilJoueur.php?id='.$result['id']);
        exit();
    }

} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header('location: profil.php');
    exit();
}
