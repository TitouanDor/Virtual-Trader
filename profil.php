<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Mon profil</title>
<style>
    body {
        font-family: sans-serif;
    }
    .profile-info {
        border: 1px solid #ccc;
        padding: 20px;
        margin: 20px;
    }
    .portfolio {
        border: 1px solid #ccc;
        padding: 20px;
        margin: 20px;
    }
</style>
</head>
<body>
<div>
    <a href="index.html">Se deconnecter</a>
    <br>
    <a href="marcher.php">marche</a>
    <br>
</div>
    <?php
    

    // Check if user is logged in
    if (!isset($_SESSION['id'])) {
        header('location: index.html');
        exit();
    }

    try {
        // Database connection
        $bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
    } catch (PDOException $e) {
        echo "<p>Database connection error</p>";
        exit();
    }

    // Get user ID from session
    $userId = $_SESSION['id'];

    // Get user information
    try {
        $req = $bdd->prepare("SELECT nom, prenom, email, argent FROM joueur WHERE id = ?");
        $req->execute([$userId]);
        $user = $req->fetch();
        if ($user == false){
            echo "<p>Database error</p>";
        }
    } catch (PDOException $e) {
        echo "<p>Database error</p>";
    }
    ?>

    <?php
    if (isset($user)) {
        ?>
        <div class="profile-info">
            <h2>Your Profile</h2>
            <p>Name: <?php echo htmlspecialchars($user['prenom']) . ' ' . htmlspecialchars($user['nom']); ?></p>
            <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
            <p>Cash: <?php echo htmlspecialchars($user['argent']); ?></p>
        </div>
        <?php
    }
    try {
        // Get user portfolio
        $req = $bdd->prepare("SELECT a.nom, p.quantity FROM portefeuille p JOIN actions a ON p.stock_id = a.id WHERE p.player_id = ?");
        $req->execute([$userId]);
        $portfolio = $req->fetchAll();
    } catch (PDOException $e) {
        echo "<p>Database error</p>";
    }

    if ($portfolio) {
        echo "<ul>";
        foreach ($portfolio as $stock) {
            echo "<li>" . $stock['nom'] . " - Quantity: " . $stock['quantity'] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>You don't have any stocks in your portfolio.</p>";
    }
    ?>
</div>
</body>
</html>