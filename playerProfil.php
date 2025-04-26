<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['id'])) {
    header('location: index.php');
    exit();
}

// Database connection
try {
    $dbHost = 'localhost';
    $dbName = 'virtual_trader';
    $dbUser = 'root';
    $dbPass = '';
    $bdd = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $_SESSION['error'] = "Database connection error: " . $e->getMessage();
    header('Location: index.php');
    exit();
}

// Check if player ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Player ID is missing.";
    header('Location: profil.php');
    exit();
}

$playerId = htmlspecialchars($_GET['id']);

// Get player's information
try {
    $playerReq = $bdd->prepare("SELECT nom, prenom, email, argent FROM joueur WHERE id = ?");
    $playerReq->execute([$playerId]);
    $player = $playerReq->fetch();
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header('Location: profil.php');
    exit();
}

if (!$player) {
    $_SESSION['error'] = "Player not found.";
    header('Location: profil.php');
    exit();
}

// Get player's history
try {
    $historyReq = $bdd->prepare("SELECT h.*, a.nom AS action_name FROM historique h JOIN actions a ON h.action_id = a.id WHERE h.player_id = ? ORDER BY h.real_date DESC");
    $historyReq->execute([$playerId]);
    $history = $historyReq->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header('Location: profil.php');
    exit();
}

// Check for and display session messages
if (isset($_SESSION['error'])) {
    echo "<p>" . str_replace("\n", "<br>", $_SESSION['error']) . "</p>";
    unset($_SESSION['error']);
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
    <title>Player Profile</title>
</head>
<body>
<h1><?php echo htmlspecialchars($player['email']); ?>'s Profile</h1>

<p>Name: <?php echo htmlspecialchars($player['nom']); ?></p>
<p>Surname: <?php echo htmlspecialchars($player['prenom']); ?></p>
<p>Balance: <?php echo htmlspecialchars($player['argent']); ?></p>

<h2>Last Actions</h2>
<?php if ($history): ?>
    <ul>
        <?php foreach ($history as $record): ?>
            <li><?php echo htmlspecialchars($record['real_date']); ?>: <?php echo htmlspecialchars($record['nature']); ?> of <?php echo htmlspecialchars($record['action_name']); ?> (price : <?php echo htmlspecialchars($record['prix']); ?>)</li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>No actions found for this player.</p>
<?php endif; ?>
<a href="profil.php">Return to my profil</a>
</body>
</html>