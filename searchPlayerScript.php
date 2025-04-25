php
<?php
session_start();

if (!isset($_SESSION['id'])) {
    header('location: index.php');
    exit();
}

if (!isset($_POST['username'])) {
    $_SESSION['error'] = "Please enter a username to search.";
    header('location: profil.php');
    exit();
}

$username = $_POST['username'];

try {
    $bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $req = $bdd->prepare("SELECT id, username FROM joueur WHERE username = ?");
    $req->execute([$username]);
    $result = $req->fetch();

    if ($result) {
        $_SESSION['search_result'] = $result;
    } else {
        $_SESSION['error'] = "No user found with that username.";
    }

    header('location: profil.php');
    exit();

} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header('location: profil.php');
    exit();
}
?>