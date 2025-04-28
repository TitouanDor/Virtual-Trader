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

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_identifier = $_POST['user_identifier'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if the user exists
    $req = $bdd->prepare("SELECT id FROM joueur WHERE username = ? OR email = ?");
    $req->execute([$user_identifier, $user_identifier]);
    $user = $req->fetch();

    if ($user) {
        // Check if the passwords match
        if ($new_password === $confirm_password) {
            // Hash the new password
            $mdp = password_hash($new_password, PASSWORD_DEFAULT);

            // Update the password in the database
            $update_req = $bdd->prepare("UPDATE joueur SET mdp = ? WHERE id = ?");
            $update_req->execute([$mdp, $user['id']]);

            // Redirect to index.html with success message
            $_SESSION['success'] = "Password updated successfully.";
            header("Location: index.html");
            exit();
        } else {
            $error = "Passwords do not match.";
        }
    } else {
        $error = "User not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recover Password</title>
</head>
<body>
    <h1>Recover Password</h1>
    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>
    <form method="POST" action="">
        <label for="user_identifier">Username or Email:</label><br><input type="text" id="user_identifier" name="user_identifier" required><br><br>
        <label for="new_password">New Password:</label><br><input type="password" id="new_password" name="new_password" required><br><br>
        <label for="confirm_password">Confirm New Password:</label><br><input type="password" id="confirm_password" name="confirm_password" required><br><br>
        <input type="submit" value="Update Password">
    </form>
</body>
</html>