<?php
session_start();
$currentTime = new DateTime();
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

// Check if player ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: profil.php');
    exit();
}

$playerId = htmlspecialchars($_GET['id']);

// Récupérer les informations du joueur
$playerReq = $bdd->prepare("SELECT nom, prenom, email, argent FROM joueur WHERE id = ?");
$playerReq->execute([$playerId]);
$player = $playerReq->fetch();
if (!$player) {
    header('Location: profil.php');
    exit();
}


// Récupérer l'historique du joueur
$historyReq = $bdd->prepare("SELECT h.*, a.nom AS action_name FROM historique h JOIN actions a ON h.action_id = a.id WHERE h.player_id = ? ORDER BY h.real_date DESC");
$historyReq->execute([$playerId]);
$history = $historyReq->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil du joueur</title>
</head>
<body>
<h1>Profil de <?php echo htmlspecialchars($player['email']); ?></h1>

<p>Nom: <?php echo htmlspecialchars($player['nom']); ?></p>
<p>Prénom: <?php echo htmlspecialchars($player['prenom']); ?></p>
<p>Solde: <?php echo htmlspecialchars($player['argent']); ?></p>

<h2>Dernières Actions</h2>
<?php if ($history): ?>
    <ul>
        <?php foreach ($history as $record): ?>
            <li><?php echo htmlspecialchars($record['real_date']); ?>: <?php echo htmlspecialchars($record['nature']); ?> de <?php echo htmlspecialchars($record['action_name']); ?> (prix : <?php echo htmlspecialchars($record['prix']); ?>)</li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>Aucune action trouvée pour ce joueur.</p>
<?php endif; ?>
<a href="profil.php">Retourner à mon profil</a>
</body>
</html>

