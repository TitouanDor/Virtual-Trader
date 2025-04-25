<?php
//permet de faire passer le temps
// Database credentials - Replace with your actual credentials
$dbHost = 'localhost';
$dbName = 'virtual_trader';
$dbUser = 'root'; // Replace with your database username
$dbPass = ''; // Replace with your database password

// New database connection
$bdd = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);

// Get current game state
$req = $bdd->prepare("SELECT current_month, current_year FROM game_state WHERE id = 1");
$req->execute();
$gameState = $req->fetch();

$currentMonth = $gameState['current_month'];
$currentYear = $gameState['current_year'];

// Increment month
$currentMonth++;

// Check if year needs to be incremented
if ($currentMonth > 12) {
    $currentMonth = 1;
    $currentYear++;
}

// Update game state
$req = $bdd->prepare("UPDATE game_state SET current_month = ?, current_year = ? WHERE id = 1");
$req->execute([$currentMonth, $currentYear]);

// Distribute dividends
$req = $bdd->prepare("SELECT id, dividende FROM actions WHERE date_dividende = ?");
$req->execute([$currentMonth]);
$actionsWithDividends = $req->fetchAll();

foreach ($actionsWithDividends as $action) {
    $actionId = $action['id'];
    $dividendPerShare = $action['dividende'];

    // Get players who own the stock
    $req = $bdd->prepare("SELECT player_id, quantity FROM portefeuille WHERE stock_id = ?");
    $req->execute([$actionId]);
    $playersWithStock = $req->fetchAll();

    foreach ($playersWithStock as $player) {
        $playerId = $player['player_id'];
        $quantity = $player['quantity'];
        $totalDividend = $dividendPerShare * $quantity;

        // Update player's cash
        $req = $bdd->prepare("UPDATE joueur SET argent = argent + ? WHERE id = ?");
        $req->execute([$totalDividend, $playerId]);
        
        // add the dividend in the history
        $req = $bdd->prepare("INSERT INTO historique (stock_id, player_id, price, nature, game_month, game_year) VALUES (?,?,?,?,?,?)");
        $req->execute([$actionId, $playerId, $totalDividend,'dividend', $currentMonth, $currentYear]);
    }
}

// Update stock prices
$req = $bdd->prepare("SELECT id FROM actions");
$req->execute();
$stocks = $req->fetchAll();

foreach ($stocks as $stock) {
    $stockId = $stock['id'];
    
    // Get the last month price
    $req = $bdd->prepare("SELECT valeur_action FROM cours_marche WHERE stock_id = ? AND game_month = ? AND game_year = ?");
    if($currentMonth == 1){
        $req->execute([$stockId, 12, $currentYear - 1]);
    }
    else{
        $req->execute([$stockId, $currentMonth-1, $currentYear]);
    }
    $lastPrice = $req->fetch();
    if ($lastPrice){
        $lastPrice = $lastPrice['valeur_action'];
    }else{
        $req = $bdd->prepare("SELECT prix FROM actions WHERE id = ?");
        $req->execute([$stockId]);
        $lastPrice = $req->fetch();
        $lastPrice = $lastPrice['prix'];
    }

    // Calculate new price
    $randomChange = rand(-3, 3);
    $priceChange = ($lastPrice * ($randomChange / 100));
    $newPrice = $lastPrice + $priceChange;


    // Apply limits
    $minLimit = $lastPrice * 0.9;
    $maxLimit = $lastPrice * 1.1;
    $newPrice = max(1, min($newPrice, $maxLimit, $minLimit));
    
    $newPrice = round($newPrice, 2);

    // Insert new price in cours_marche
    $req = $bdd->prepare("INSERT INTO cours_marche (stock_id, game_month, game_year, valeur_action) VALUES (?,?,?,?)");
    $req->execute([$stockId, $currentMonth, $currentYear, $newPrice]);
}

?>