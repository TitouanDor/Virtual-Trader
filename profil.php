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
<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header('location: index.html');
    exit();
}

// Database connection
$bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'user', 'password');

// Get user ID from session
$userId = $_SESSION['id'];

// Get user information
$req = $bdd->prepare("SELECT nom, prenom, email, argent FROM joueur WHERE id = ?");
$req->execute([$userId]);
$user = $req->fetch();

?>

<div class="profile-info">
    <h2>Your Profile</h2>
    <p>Name: <?php echo $user['prenom'] . ' ' . $user['nom']; ?></p>
    <p>Email: <?php echo $user['email']; ?></p>
    <p>Cash: <?php echo $user['argent']; ?></p>
</div>

<div class="portfolio">
    <h3>Your Portfolio</h3>
    <?php
    // Get user portfolio
    $req = $bdd->prepare("SELECT a.nom, p.quantity FROM portefeuille p JOIN actions a ON p.stock_id = a.id WHERE p.player_id = ?");
    $req->execute([$userId]);
    $portfolio = $req->fetchAll();

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