<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['id'])) {
    header('location: index.html');
    exit();
}
// Database connection
$dbHost = 'localhost';
$dbName = 'virtual_trader';
$dbUser = 'root';
$dbPass = '';
$bdd = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
$bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if all fields are filled
    if (!empty($_POST['new_password']) && !empty($_POST['confirm_password']) && !empty($_POST['old_password'])) {
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        $oldPassword = $_POST['old_password'];

        // Check if passwords match
        if ($newPassword === $confirmPassword) {
            // Get current user's password
            $req = $bdd->prepare("SELECT password FROM joueur WHERE id = ?");
            $req->execute([$_SESSION['id']]);
            $user = $req->fetch();

            // Verify old password
            if(password_verify($oldPassword, $user['password'])){
                // Hash the new password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                // Update the password
                $updateReq = $bdd->prepare("UPDATE joueur SET password = ? WHERE id = ?");
                $updateReq->execute([$hashedPassword, $_SESSION['id']]);

                $_SESSION['success'] = "Password changed successfully!";
                header('Location: profil.php');
                exit();
            }
            else{
                header('Location: changePassword.php');
                exit();
            }
        } else {
            header('Location: changePassword.php');
            exit();
        }
    } else {
        header('Location: changePassword.php');
        exit();
    }
}
if (isset($_SESSION['success'])) {
    echo "<p>" . str_replace("\n", "<br>", $_SESSION['success']) . "</p>";
    unset($_SESSION['success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
</head>
<body>
<h1>Change Your Password</h1>

<form method="POST" action="changePassword.php">
    <label for="old_password">Old Password:</label><br>
    <input type="password" id="old_password" name="old_password" required><br><br>

    <label for="new_password">New Password:</label><br>
    <input type="password" id="new_password" name="new_password" required><br><br>

    <label for="confirm_password">Confirm New Password:</label><br>
    <input type="password" id="confirm_password" name="confirm_password" required><br><br>

    <input type="submit" value="Change Password">
</form>
<br>
<a href="profil.php">Return to my profil</a><br>
<a href="leaderboard.php">Leaderboard</a>


</body>
</html>