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

foreach ($actionsWithDividends as $action){
    $actionId = $action['id'];
     //add the dividend in the history even if there is no player owning the stock
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

        $reqHistory->execute([$actionId, $playerId, $totalDividend,'dividend', $currentMonth, $currentYear]);
    }
    if(count($playersWithStock) == 0){
          $reqHistory->execute([$actionId, 0, $dividendPerShare,'dividend', $currentMonth, $currentYear]);
    }   
}

// Update stock prices
$req = $bdd->prepare("SELECT id FROM actions");
$req->execute();
$stocks = $req->fetchAll();

foreach ($stocks as $stock) {
     $stockId = $stock['id'];
        
        // Get the last month price
        $reqLastPrice = $bdd->prepare("SELECT valeur_action FROM cours_marche WHERE stock_id = ? AND game_month = ? AND game_year = ?");
        if($currentMonth == 1 && $currentYear == 1){
            $reqLastPrice->execute([$stockId, 12, 1]);
        }
        elseif($currentMonth == 1){
            $lastYear = $currentYear - 1;
            $reqLastPrice->execute([$stockId, 12, $lastYear]);
        }
        else{
            $reqLastPrice->execute([$stockId, $currentMonth-1, $currentYear]);
        }
        $lastPriceData = $reqLastPrice->fetchAll(PDO::FETCH_ASSOC);
        if ($lastPriceData){
            $lastPrice = $lastPriceData[0]['valeur_action'];
        } else {
            $reqBasePrice = $bdd->prepare("SELECT prix FROM actions WHERE id = ?");
            $reqBasePrice->execute([$stockId]);
            $lastPriceData = $reqBasePrice->fetchAll(PDO::FETCH_ASSOC);
            $lastPrice = $lastPriceData[0]['prix'];
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

    // add the price change in the history
    $req = $bdd->prepare("INSERT INTO historique (stock_id, player_id, price, nature, game_month, game_year) VALUES (?,?,?,?,?,?)");

    // Insert new price in cours_marche
    $reqCours = $bdd->prepare("INSERT INTO cours_marche (stock_id, game_month, game_year, valeur_action) VALUES (?,?,?,?)");
    $reqCours->execute([$stockId, $currentMonth, $currentYear, $newPrice]);
    
    // Update the price in actions
    $req = $bdd->prepare("UPDATE actions SET prix = ? WHERE id = ?");
    $req->execute([$newPrice, $stockId]);
    $req->execute([$stockId, 0, $newPrice, 'price_change', $currentMonth, $currentYear]); 
}
?>