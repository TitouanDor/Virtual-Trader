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

// Check if a token is provided or if it's a direct password change from the profile
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Check if the token is valid
    $checkTokenReq = $bdd->prepare("SELECT id FROM joueur WHERE token = ?");
    $checkTokenReq->execute([$token]);
    $user = $checkTokenReq->fetch();

    if (!$user) {
        header('Location: index.html');
        exit();
    }
    $userId = $user['id'];
} else {
    // Check if the user is logged in
    if (!isset($_SESSION['id'])) {
        header('Location: index.html');
        exit();
    }

    $userId = $_SESSION['id'];
    // Generate a new token
    $token = bin2hex(random_bytes(32));

    // Save the token to the user's data
    $updateTokenReq = $bdd->prepare("UPDATE joueur SET token = ? WHERE id = ?");
    $updateTokenReq->execute([$token, $userId]);
    exit();
}

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password</title>
</head>
<body>
    <h1>Update Your Password</h1>

    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <form action="passwordUpdateScript.php" method="POST">
        <input type="hidden" name="token" value="<?php echo $token; ?>" >

        <label for="new_password">New Password:</label><br>
        <input type="password" id="new_password" name="new_password" required><br><br>

        <label for="confirm_password">Confirm New Password:</label><br>
        <input type="password" id="confirm_password" name="confirm_password" required><br><br>

        <input type="submit" value="Update Password">
    </form>
</body>
</html>