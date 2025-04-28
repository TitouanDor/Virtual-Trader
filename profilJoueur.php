<?php
session_start();

// Database connection
$bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
$bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Check if player ID is provided

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: profil.php');
    exit();
}

$playerId = htmlspecialchars($_GET['id']);

// Get player information
$playerReq = $bdd->prepare("SELECT nom, prenom, username, argent FROM joueur WHERE id = ?");
$playerReq->execute([$playerId]);
$player = $playerReq->fetch();
if (!$player) {
    header('Location: profil.php');
    exit();
}

if(!isset($_SESSION["id"])){
    header("Location: index.html");
    exit();
}

// Check if the user is already following the player
$userId = $_SESSION['id'];
$checkFollowReq = $bdd->prepare("SELECT * FROM followers WHERE user_id = ? AND followed_user_id = ?");
$checkFollowReq->execute([$userId, $playerId]);
$isFollowing = $checkFollowReq->fetch();

$followButtonValue = "Follow";

if ($isFollowing){
    $followButtonValue = "Unfollow";
}
if ($userId == $playerId){
    $followButtonValue = "joueur";
}


// Récupérer l'historique du joueur
$historyReq = $bdd->prepare("SELECT historique.*, actions.nom AS action_name FROM historique JOIN actions ON historique.action_id = actions.id WHERE historique.joueur_id = ? ORDER BY historique.real_date DESC");
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
<h1>Profil de <?php echo htmlspecialchars($player['username']);
 if($followButtonValue == "joueur"){
    echo " (C'est vous!)";
 }?></h1>

<p>Nom: <?php echo htmlspecialchars($player['nom']); ?></p>
<p>Prénom: <?php echo htmlspecialchars($player['prenom']); ?></p>
<p>Solde: <?php echo htmlspecialchars($player['argent']); ?></p>
<?php if ($followButtonValue != "It's you"): ?>
<form method="POST" action="followScript.php">
    <input type="hidden" name="followed_user_id" value="<?php echo $playerId; ?>">
    <input type="submit" name="follow" value="<?php echo $followButtonValue?>">
</form>
<?php endif ?>



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
<br>
<a href="classement.php">Classement</a>

</body>
</html>

