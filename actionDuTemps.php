<?php
//permet de faire passer le temps
// Database credentials - Replace with your actual credentials
$dbHost = 'localhost';
$dbName = 'virtual_trader';
$dbUser = 'root'; // Replace with your database username
$dbPass = ''; // Replace with your database password

// New database connection
$bdd = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);

//Prepare the base request for the price
$reqBasePrice = $bdd->prepare("SELECT prix FROM actions WHERE id = ?");

// Get current game state
$req = $bdd->prepare("SELECT current_month, current_year FROM game_state WHERE id = 1");
$req->execute();
$gameState = $req->fetch(PDO::FETCH_ASSOC);



// Distribute dividends
$req = $bdd->prepare("SELECT id, dividende FROM actions WHERE date_dividende = ?");
$req->execute([$gameState['current_month']]);
$actionsWithDividends = $req->fetchAll(PDO::FETCH_ASSOC);

foreach ($actionsWithDividends as $action) {
    $actionId = $action['id'];
    $dividendPerShare = $action['dividende'];
    //add the dividend in the history even if there is no player owning the stock
    // Get current game state
    $reqGetGameState = $bdd->prepare("SELECT current_month, current_year FROM game_state WHERE id = 1");
    $reqGetGameState->execute();
    $gameState = $reqGetGameState->fetch(PDO::FETCH_ASSOC);
    $currentMonth = $gameState['current_month'];
    $reqHistory = $bdd->prepare("INSERT INTO historique (stock_id, player_id, price, nature, game_month, game_year) VALUES (?,?,?,?,?,?)");
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
        $reqHistory->execute([$actionId, $playerId, $dividendPerShare, 'dividend', $gameState['current_month'], $gameState['current_year']]);
    }
    if (count($playersWithStock) == 0) {
        $reqHistory->execute([$actionId, 0, $dividendPerShare, 'dividend', $gameState['current_month'], $gameState['current_year']]);
    }
}
// Update stock prices
// Update stock prices

$req = $bdd->prepare("SELECT id FROM actions");
$req->execute();
$stocks = $req->fetchAll(PDO::FETCH_ASSOC);

foreach ($stocks as $stock) {
    $stockId = $stock['id'];

    $randomChange = rand(-3,3);
    // Get the last month price
      // Get current game state
      $reqGetGameState = $bdd->prepare("SELECT current_month, current_year FROM game_state WHERE id = 1");
      $reqGetGameState->execute();
      $gameState = $reqGetGameState->fetch(PDO::FETCH_ASSOC);
    $reqLastPrice = $bdd->prepare("SELECT valeur_action FROM cours_marche WHERE stock_id = ? AND game_month = ? AND game_year = ? ");
    if ($gameState['current_month'] == 1 && $gameState['current_year'] == 1) {
        $reqLastPrice->execute([$stockId, 12, 1]);
    } elseif ($gameState['current_month'] == 1) {
        $reqLastPrice->execute([$stockId, 12, $gameState['current_year'] - 1]);
    } else {
        $reqLastPrice->execute([$stockId, $gameState['current_month'] - 1, $gameState['current_year']]);
    }
    $lastPriceData = $reqLastPrice->fetchAll(PDO::FETCH_ASSOC);
    if ($lastPriceData) {

        $lastPrice = $lastPriceData[0]['valeur_action'];
    } else {
        $reqBasePrice->execute([$stockId]);
        $lastPrice = $reqBasePrice->fetch(PDO::FETCH_ASSOC)['prix'];
        
    }

    // Calculate new price
    $priceChange = ($lastPrice * ($randomChange / 100));
    $newPrice = $lastPrice + $priceChange;

    // Apply limits
    $minLimit = $lastPrice * 0.9;
    $maxLimit = $lastPrice * 1.1;
    $newPrice = max(1, min($newPrice, $maxLimit));

    $newPrice = round($newPrice, 2);


    // add the price change in the history
    $req = $bdd->prepare("SELECT current_month, current_year FROM game_state WHERE id = 1");
    $req->execute();
    $gameState = $req->fetch(PDO::FETCH_ASSOC);
    $req = $bdd->prepare("INSERT INTO historique (stock_id, player_id, price, nature, game_month, game_year) VALUES (?,?,?,?,?,?)");

    // Insert new price in cours_marche
    $reqCours = $bdd->prepare("INSERT INTO cours_marche (stock_id, game_month, game_year, valeur_action) VALUES (?,?,?,?)");
    $reqCours->execute([$stockId, $gameState['current_month'], $gameState['current_year'], $newPrice]);
    $req->execute([$stockId, 0, $newPrice, 'price_change', $gameState['current_month'], $gameState['current_year']]);

    // Update the price in actions
    $req = $bdd->prepare("UPDATE actions SET prix = ? WHERE id = ?");
    $req->execute([$newPrice, $stockId]);
}
//Increment month and year
    // Get the last game state
    $reqGetGameState = $bdd->prepare("SELECT current_month, current_year FROM game_state WHERE id = 1");
    $reqGetGameState->execute();
    $gameState = $reqGetGameState->fetch(PDO::FETCH_ASSOC);
    $gameState['current_month']++;
    if ($gameState['current_month'] > 12) { $gameState['current_month'] = 1; $gameState['current_year']++;}
    $req = $bdd->prepare("UPDATE game_state SET current_month = ?, current_year = ? WHERE id = 1");
    $req->execute([$gameState['current_month'], $gameState['current_year']]);

?>