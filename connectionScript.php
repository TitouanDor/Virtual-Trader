<?php

session_start();

// Check if email and password are set in the POST request
if (!isset($_POST['email']) || !isset($_POST['password'])) {
    header('location: index.php');
    exit();
}

$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$password = $_POST['password'];

// Database connection with username and password

try {
    $dbHost = 'localhost';
    $dbName = 'virtual_trader';
    $dbUser = 'root';
    $dbPass = '';
    $bdd = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $_SESSION['error'] = "Database connection error<br>" . htmlspecialchars($e->getMessage());
    header('location: index.php'); 
    exit();
}

// Fetch user data based on email
$req = $bdd->prepare("SELECT * FROM joueur WHERE email = ?");
$req->execute([$email]);
$data = $req->fetch();
if ($data === false) {
    $_SESSION['error'] = "An error occurred while checking for the user.";
    header('location: index.php');
    exit();
}
// Check if the user exists
if ($data) {
    if(password_verify($password, $data['mdp'])){
         $_SESSION['id'] = $data['id'];
        header('location: profil.php');
        exit();
    } else {
        $_SESSION['error'] = "Incorrect email or password";
        header('location: index.php');
        exit();
    }
}
?>
