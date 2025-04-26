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

// Fetch all actions
$actionsReq = $bdd->prepare("SELECT id, nom, description, prix FROM actions");
$actionsReq->execute();
$actions = $actionsReq->fetchAll();

// Fetch historical prices for each action
foreach ($actions as &$action) {
    $actionId = $action['id'];
    $historicalPricesReq = $bdd->prepare("SELECT game_month, game_year, valeur_action FROM cours_marche WHERE action_id = ? ORDER BY game_year DESC, game_month DESC LIMIT 12");
    $historicalPricesReq->execute([$actionId]);
    $historicalPrices = $historicalPricesReq->fetchAll();
    $action['historical_prices'] = $historicalPrices;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marché</title>
</head>
<body>
    <h1>Marché</h1>
    <ul>
        <?php foreach ($actions as $action): ?>
            <li>
                <form action="buySellScript.php" method="POST">
                    <h2><?php echo htmlspecialchars($action['nom']); ?></h2>
                    <p><?php echo htmlspecialchars($action['description']); ?></p>
                    <p>Prix actuel: <?php echo htmlspecialchars($action['prix']); ?></p>
                    <h3>Prix Historiques</h3>
                    <ul>
                      <?php foreach ($action['historical_prices'] as $historicalPrice): ?>
                        <li><?php echo htmlspecialchars($historicalPrice['game_month']."/".$historicalPrice['game_year'].": ".$historicalPrice['valeur_action']); ?></li>
                      <?php endforeach; ?>
                    </ul>
                    <input type="hidden" name="action_id" value="<?php echo $action['id']; ?>">
                    <label for="quantity">Quantity:</label>
                    <input type="number" id="quantity" name="quantity" value="1" min="1">
                    <button type="submit" name="buy" value="buy">Buy</button>
                    <button type="submit" name="sell" value="sell">Sell</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
    <a href="profil.php">Return to my profil</a>
</body>
</html>
