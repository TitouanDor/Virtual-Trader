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

// Check if the required fields are set
if (isset($_POST['token'], $_POST['new_password'], $_POST['confirm_password'])) {
    $token = $_POST['token'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Check if the token is valid
    $checkTokenReq = $bdd->prepare("SELECT id FROM joueur WHERE token = ?");
    $checkTokenReq->execute([$token]);    
    $result = $checkTokenReq->fetch();    

    if ($result) {
         // Check if the passwords match
         if ($newPassword === $confirmPassword) {
             // Hash the new password
             $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

             // Update the password
             $updatePasswordReq = $bdd->prepare("UPDATE joueur SET password = ? WHERE id = ?");
             $updatePasswordReq->execute([$hashedPassword, $result['id']]);

             // Remove the token
             $removeTokenReq = $bdd->prepare("UPDATE joueur SET token = NULL WHERE id = ?");
             $removeTokenReq->execute([$result['id']]);
             $_SESSION['success'] = "Password updated successfully!";
             // Redirect to index.html
             header('Location: index.html');
             exit();
         } else {
             // Passwords do not match
             $_SESSION['error'] = "Passwords do not match.";
             header('Location: passwordUpdate.php?token=' . $token);
             exit();
         }
    } else {
        // Invalid token
        header('Location: index.html');
        exit();
    }

}