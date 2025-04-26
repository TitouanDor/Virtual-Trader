php
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

try {
    $bdd = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Get all actions
$actionsReq = $bdd->prepare("SELECT id, nom, description, prix FROM actions");
$actionsReq->execute();
$actions = $actionsReq->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Market</title>
</head>
<body>
    <h1>Stock Market</h1>
    <ul>
        <?php foreach ($actions as $action): ?>
            <li>
                <h2><?php echo htmlspecialchars($action['nom']); ?></h2>
                <p><?php echo htmlspecialchars($action['description']); ?></p>
                <p>Current price: <?php echo htmlspecialchars($action['prix']); ?></p>
            </li>
        <?php endforeach; ?>
    </ul>
    <a href="profil.php">Return to my profil</a>
</body>
</html>