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

// Initialize error message
$errorMessage = "";

// Check if the user has lost the game
$req = $bdd->prepare("SELECT j.id FROM joueur j WHERE j.id = ? AND j.id NOT IN (SELECT p.player_id FROM portefeuille p) AND j.argent < 1000");
$req->execute([$_SESSION['id']]);
if ($req->fetch()) {
    $errorMessage = "You have lost the game!";
}
// Get the current game state and update if necessary
if (empty($errorMessage)) {
    $gameStateReq = $bdd->prepare("SELECT * FROM game_state");
    $gameStateReq->execute();
    $gameState = $gameStateReq->fetch();
    if ($gameState) {
        $lastUpdate = new DateTime($gameState['last_update']);
        $currentTime = new DateTime();

        // Check if the game has to be updated
        if ($currentTime->diff($lastUpdate)->i >= 1) {
            // Update the date
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
            $dividendReq = $bdd->prepare("SELECT j.id, j.argent, a.dividende FROM joueur j JOIN portefeuille p ON j.id = p.player_id JOIN actions a ON p.stock_id = a.id WHERE a.date_dividende = ?");
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
                $lastPriceExistReq = $bdd->prepare("SELECT COUNT(*) AS count FROM cours_marche WHERE stock_id = ? AND game_month = ? AND game_year = ?");
                $lastPriceExistReq->execute([$stock['id'], $lastMonth, $lastYear]);
                $lastPriceExist = $lastPriceExistReq->fetch();

                if ($lastPriceExist['count'] == 0) {
                    $percent = 0;
                } else {
                    $lastPriceReq = $bdd->prepare("SELECT valeur_action FROM cours_marche WHERE stock_id = ? AND game_month = ? AND game_year = ?");
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
                $addCoursReq = $bdd->prepare("INSERT INTO cours_marche (stock_id, game_month, game_year, valeur_action) VALUES (?, ?, ?, ?)");
                $addCoursReq->execute([$stock['id'], $currentMonth, $currentYear, $newPrice]);
            }
        }
    }
}

// Get user's information
$userReq = $bdd->prepare("SELECT nom, prenom, email, argent FROM joueur WHERE id = ?");
$userReq->execute([$_SESSION['id']]);
$user = $userReq->fetch();

// Get user's invested actions
$investedActionsReq = $bdd->prepare("SELECT a.nom, p.quantite FROM portefeuille p JOIN actions a ON p.stock_id = a.id WHERE p.player_id = ?");
$investedActionsReq->execute([$_SESSION['id']]);
$investedActions = $investedActionsReq->fetchAll();

// Handle player search
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = htmlspecialchars($_GET['search']);
    $searchReq = $bdd->prepare("SELECT id, nom, prenom, email FROM joueur WHERE email LIKE ?");
    $searchReq->execute(["%" . $search . "%"]);
    $searchResults = $searchReq->fetchAll();
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
    <title>Profile</title>
</head>
<body>
<h1>Welcome to your profile</h1>

<?php if (!empty($errorMessage)): ?>
    <p><?php echo $errorMessage; ?></p>
<?php endif; ?>

<?php if ($user): ?>
    <p>Name: <?php echo htmlspecialchars($user['nom']); ?></p>
    <p>Surname: <?php echo htmlspecialchars($user['prenom']); ?></p>
    <p>Username: <?php echo htmlspecialchars($user['email']); ?></p>
    <p>Balance: <?php echo htmlspecialchars($user['argent']); ?></p>
<?php endif; ?>
<h2>Your Actions</h2>
<?php if ($investedActions): ?>
    <ul>
        <?php foreach ($investedActions as $action): ?>
            <li><?php echo htmlspecialchars($action['nom']); ?> (Quantity: <?php echo htmlspecialchars($action['quantite']); ?>)</li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>You have not invested in any actions yet.</p>
<?php endif; ?>
<h2>Search for players</h2>
<form action="profil.php" method="get">
    <input type="text" name="search" placeholder="Search by email">
    <input type="submit" value="Search">
</form>

<?php if (isset($searchResults) && $searchResults): ?>
    <h2>Search Results</h2>
    <ul>
        <?php foreach ($searchResults as $result): ?>
            <li><a href="playerProfil.php?id=<?php echo $result['id']?>"><?php echo htmlspecialchars($result['email']); ?></a></li>
        <?php endforeach; ?>
    </ul>
<?php elseif (isset($searchResults)): ?>
    <p>No players found.</p>
<?php endif; ?>
<a href="changePassword.php">Change password</a>
<br>
<a href="logout.php">Logout</a>
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
$historyReq = $bdd->prepare("SELECT h.*, a.nom AS action_name FROM historique h JOIN actions a ON h.action_id = a.id WHERE h.player_id = ? ORDER BY h.real_date DESC");
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