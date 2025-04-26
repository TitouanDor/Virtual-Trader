<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Recovery</title>
</head>
<body>
    <h1>Password Recovery</h1>
    <form method="POST" action="passwordRecoveryScript.php">
        <label for="user_identifier">Username or Email:</label><br>
        <input type="text" id="user_identifier" name="user_identifier" required><br><br>

        <input type="submit" value="Recover Password">
    </form>
</body>
</html>