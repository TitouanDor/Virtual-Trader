<?php
session_start();

// Database connection
$dbHost = 'localhost';
$dbName = 'virtual_trader';
$dbUser = 'root';
$dbPass = '';
$bdd = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
$bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if all fields are filled
    if (!empty($_POST['email']) && !empty($_POST['password'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        $req = $bdd->prepare("SELECT id, mdp FROM joueur WHERE email = ?");
        $req->execute([$email]);
        $user = $req->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Password is correct, start a new session
            $_SESSION['id'] = $user['id'];

            // Redirect to profil.php
            header('Location: profil.php');
            exit();
        } else {
            header('Location: index.php');
            exit();
        }
    } else {
        header('Location: index.php');
        exit();
    }
}
?>