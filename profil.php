<?php
session_start();

// Vérification si l'utilisateur est connecté
if (!isset($_SESSION['id'])) {
    header('Location: index.php');
    exit();
}

// Connexion à la base de données
$dbHost = 'localhost';
$dbName = 'virtual_trader';
$dbUser = 'root';
$dbPass = '';
$bdd = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
$bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);



// Get the current game state and update if necessary
$gameStateReq = $bdd->prepare("SELECT * FROM game_state");
    $gameStateReq->execute();
    $gameState = $gameStateReq->fetch();
    if ($gameState) {
        $lastUpdate = new DateTime($gameState['last_update']);
        $currentTime = new DateTime();

        // Check if the game has to be updated
         if ($currentTime->diff($lastUpdate)->i >= 1) {
           
            $currentMonth = $gameState['current_month'];
           $currentYear = $gameState['current_year'];
           $currentMonth++;
           if ($currentMonth > 12) {
                $currentMonth = 1;
                $currentYear++;
            }

            $updateDateReq = $bdd->prepare("UPDATE game_state SET current_month = ?, current_year = ?, last_update = ?");
            $updateDateReq->execute([$currentMonth, $currentYear, $currentTime->format('Y-m-d H:i:s')]);
            //give dividends
           $dividendReq = $bdd->prepare("SELECT joueur.id, joueur.argent, actions.dividende FROM joueur JOIN portefeuille ON joueur.id = portefeuille.joueur_id JOIN actions ON portefeuille.action_id = actions.id WHERE actions.date_dividende = ?");
           $dividendReq->execute([$currentMonth]);
           $players = $dividendReq->fetchAll();
           foreach($players as $player){
               $new_money = floatval($player['argent']) + floatval($player['dividende']);
               $updateMoney = $bdd->prepare("UPDATE joueur SET argent = ? WHERE id = ?");
               $updateMoney->execute([$new_money, $player['id']]);
           }
           // Update the price of stocks
           $stocksReq = $bdd->prepare("SELECT id, prix FROM actions");
           $stocksReq->execute();
           $stocks = $stocksReq->fetchAll();
           foreach ($stocks as $stock) {
               $lastMonth = $currentMonth == 1 ? 12 : $currentMonth - 1;
               $lastYear = $currentMonth == 1 ? $currentYear - 1 : $currentYear;
           
                // Check if we have the last price
                $lastPriceExistReq = $bdd->prepare("SELECT COUNT(*) AS count FROM cours_marche WHERE action_id = ? AND game_month = ? AND game_year = ?");
                $lastPriceExistReq->execute([$stock['id'], $lastMonth, $lastYear]);
                $lastPriceExist = $lastPriceExistReq->fetch();

                if ($lastPriceExist['count'] == 0) {
                    $percent = 0;
                } else {
                    $lastPriceReq = $bdd->prepare("SELECT valeur_action FROM cours_marche WHERE action_id = ? AND game_month = ? AND game_year = ?");
                    $lastPriceReq->execute([$stock['id'], $lastMonth, $lastYear]);
                    $lastPrice = $lastPriceReq->fetch();

                    $percent = 0;
                    if ($lastPrice !== false) {
                        $percent = $lastPrice['valeur_action'] == 0 ? 0 : ((floatval($stock['prix']) - floatval($lastPrice['valeur_action'])) / floatval($lastPrice['valeur_action'])) * 100;
                    }
                    $percent += rand(-3, 3);
                    $percent = max(-10, min(10, $percent));
                    $newPrice = floatval($stock['prix']) + (floatval($stock['prix']) * ($percent / 100));
                }
                $newPrice = max(1, $newPrice);
                $newPriceReq = $bdd->prepare("UPDATE actions SET prix = ? WHERE id = ?");
                $newPriceReq->execute([$newPrice, $stock['id']]);
                $addCoursReq = $bdd->prepare("INSERT INTO cours_marche (action_id, game_month, game_year, valeur_action) VALUES (?, ?, ?, ?)");
                $addCoursReq->execute([$stock['id'], $currentMonth, $currentYear, $newPrice]);
           }
        }        
    }

$userReq = $bdd->prepare("SELECT nom, prenom, email, argent FROM joueur WHERE id = ?"); // Récupération des informations de l'utilisateur
$userReq->execute([$_SESSION['id']]);
$user = $userReq->fetch();

$investedActionsReq = $bdd->prepare("SELECT actions.nom, portefeuille.quantite FROM portefeuille JOIN actions ON portefeuille.action_id = actions.id WHERE portefeuille.joueur_id = ?");// Récupération des actions investies
$investedActionsReq->execute([$_SESSION['id']]);
$investedActions = $investedActionsReq->fetchAll();

// Recherche de joueur
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = htmlspecialchars($_GET['search']);
    $searchReq = $bdd->prepare("SELECT id, nom, prenom, email FROM joueur WHERE email LIKE ?");
    $searchReq->execute(["%" . $search . "%"]);
    $searchResults = $searchReq->fetchAll();
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon profil</title>
</head>
<body>
    <h1>Bienvenue sur votre profil</h1>

<?php if ($user): ?>
        <p>Nom: <?php echo htmlspecialchars($user['nom']); ?></p>
        <p>Prénom: <?php echo htmlspecialchars($user['prenom']); ?></p>
        <p>Nom d'utilisateur: <?php echo htmlspecialchars($user['email']); ?></p>
        <p>Solde: <?php echo htmlspecialchars($user['argent']); ?></p>
<?php endif; ?>
        <h2>Vos Actions</h2>
<?php if ($investedActions): ?>
    <ul>
        <?php foreach ($investedActions as $action): ?>
                <li><?php echo htmlspecialchars($action['nom']); ?> (Quantité: <?php echo htmlspecialchars($action['quantite']); ?>)</li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
        <p>Vous n'avez pas encore investi dans des actions.</p>
<?php endif; ?>
    <h2>Rechercher des joueurs</h2>
<form action="profil.php" method="get">
        <input type="text" name="search" placeholder="Rechercher par e-mail">
        <input type="submit" value="Rechercher">
</form>

<?php if (isset($searchResults) && $searchResults): ?>
    <h2>Search Results</h2>
    <ul>
        <?php foreach ($searchResults as $result): ?>
            <li><a href="playerProfil.php?id=<?php echo $result['id']?>"><?php echo htmlspecialchars($result['email']); ?></a></li>
        <?php endforeach; ?>
    </ul>
<?php elseif (isset($searchResults)): ?>
    <p>Aucun joueur trouvé.</p>
<?php endif; ?>
<a href="changePassword.php">Changer de mot de passe</a>
<br>
<a href="logout.php">Déconnexion</a>
</body>
</html>

playerProfil.php

php
<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['id'])) {
    header('location: index.php');
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

// Get player's information
$playerReq = $bdd->prepare("SELECT nom, prenom, email, argent FROM joueur WHERE id = ?");
$playerReq->execute([$playerId]);
$player = $playerReq->fetch();

if (!$player) {
    header('Location: profil.php');
    exit();
}

// Get player's history
$historyReq = $bdd->prepare("SELECT historique.*, actions.nom AS action_name FROM historique JOIN actions ON historique.action_id = actions.id WHERE historique.joueur_id = ? ORDER BY historique.real_date DESC");
$historyReq->execute([$playerId]);
$history = $historyReq->fetchAll();
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