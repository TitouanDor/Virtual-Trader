php
<?php
session_start();

if (!isset($_SESSION['id'])) {
    header('location: index.php');
    exit();
}

if (!isset($_GET['player_id'])) {
    $_SESSION['error'] = "Invalid player ID";
    header('location: profil.php');
    exit();
}

$player_id = $_GET['player_id'];

try {
    $bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $_SESSION['error'] = "Database connection error: " . $e->getMessage();
    header('location: index.php');
    exit();
}

try {
    $req->execute([$player_id]);
    $player = $req->fetch();

    if (!$player) {
        session_start();
        $_SESSION['error'] = "Player not found";
        header('location: profil.php');
        exit();
    }

    $historyReq = $bdd->prepare("SELECT a.nom AS action_name, h.nature, h.prix, h.real_date FROM historique h JOIN actions a ON h.stock_id = a.id WHERE h.player_id = ? ORDER BY h.real_date DESC LIMIT 10");
    $historyReq->execute([$player_id]);
    $history = $historyReq->fetchAll();

} catch (PDOException $e) {
    session_start();
    $_SESSION['error'] = "Database error: " . $e->getMessage();   
    header('location: profil.php');
    exit();
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Player Profile</title>
</head>
<body>
<div>
    <a href="profil.php">Back to profile</a>
</div>
<h1><?php echo htmlspecialchars($player['username']); ?>'s Profile</h1>
<p>Name: <?php echo htmlspecialchars($player['prenom']) . ' ' . htmlspecialchars($player['nom']); ?></p>

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
</body>
</html>