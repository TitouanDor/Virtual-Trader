<?php
session_start();

// Database connection
$dbHost = 'localhost';
$dbName = 'virtual_trader';
$dbUser = 'root';
$dbPass = '';

try {
    $bdd = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Check if the form has been submitted
if (isset($_POST['user'])) {
    $user = $_POST['user'];

    // Search for the user
    $req = $bdd->prepare("SELECT id, email FROM joueur WHERE username = ? OR email = ?");
    $req->execute([$user, $user]);
    $result = $req->fetch();

    if ($result) {
        $userId = $result['id'];
        $userEmail = $result['email'];

        // Generate a unique token
        $token = bin2hex(random_bytes(32));

        // Save the token to the user's data
        $updateTokenReq = $bdd->prepare("UPDATE joueur SET token = ? WHERE id = ?");
        $updateTokenReq->execute([$token, $userId]);

        // Send an email with the recovery link
        $recoveryLink = "http://localhost/passwordUpdate.php?token=$token"; // Replace with your actual URL
        $subject = "Password Recovery";
        $message = "Click the following link to recover your password: $recoveryLink";
        $headers = "From: webmaster@example.com"; // Replace with your email

        if (mail($userEmail, $subject, $message, $headers)) {
             $_SESSION['success'] = "An email has been sent to your email adress.";
        } else {
            $_SESSION['error'] = "Error sending the email. Please try again.";
        }
        // Redirect to index.html with success message
        header('Location: index.html');
        exit();
    } else {
        // User not found
        $_SESSION['error'] = "User not found.";
        header('Location: passwordRecovery.php');
        exit();
    }
 } else{
    header('Location: passwordRecovery.php');
    exit();
}
?>