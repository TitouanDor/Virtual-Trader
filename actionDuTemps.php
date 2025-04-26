<?php
// Database connection information
$dbHost = 'localhost';
$dbName = 'virtual_trader';
$dbUser = 'root';
$dbPass = '';

// Database connection
$bdd = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
$bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get the current game state
$reqGetGameState = $bdd->prepare("SELECT current_month, current_year FROM game_state WHERE id = 1");
$reqGetGameState->execute();
$gameState = $reqGetGameState->fetch(PDO::FETCH_ASSOC);

// Increment the month
$gameState['current_month']++;
if ($gameState['current_month'] > 12) {
    $gameState['current_month'] = 1;
    $gameState['current_year']++;
}

// Update the game state in the database
$reqUpdateGameState = $bdd->prepare("UPDATE game_state SET current_month = ?, current_year = ? WHERE id = 1");
$reqUpdateGameState->execute([$gameState['current_month'], $gameState['current_year']]);

// Get all actions
$reqGetAllActions = $bdd->query("SELECT id FROM actions");
$actions = $reqGetAllActions->fetchAll(PDO::FETCH_ASSOC);

// Base price request preparation
$reqBasePrice = $bdd->prepare("SELECT prix FROM actions WHERE id = ?");

// Prepare dividend distribution request
$reqDividend = $bdd->prepare("SELECT id, dividende FROM actions WHERE date_dividende = ?");

// Process each action
foreach ($actions as $action) {
    $actionId = $action['id'];

    // Get the last price of the action
    $reqLastPrice = $bdd->prepare("SELECT valeur_action FROM cours_marche WHERE action_id = ? AND game_month = ? AND game_year = ?");
    if ($gameState['current_month'] == 1) {
        $reqLastPrice->execute([$actionId, 12, $gameState['current_year'] - 1]);
    } else {
        $reqLastPrice->execute([$actionId, $gameState['current_month'] - 1, $gameState['current_year']]);
    }

    $lastPriceData = $reqLastPrice->fetch(PDO::FETCH_ASSOC);
    if ($lastPriceData) {
        $lastPrice = $lastPriceData['valeur_action'];
    } else {
        $reqBasePrice->execute([$actionId]);
        $lastPrice = $reqBasePrice->fetch(PDO::FETCH_ASSOC)['prix'];
    }

    // Generate a random change
    $randomChange = rand(-10, 10);
    $priceChange = ($lastPrice * ($randomChange / 100));
    $newPrice = $lastPrice + $priceChange;

    // Apply limits (minimum 1, maximum 10% increase or decrease)
    $minLimit = $lastPrice * 0.9;
    $maxLimit = $lastPrice * 1.1;
    $newPrice = max(1, min($newPrice, $maxLimit));

    // Round the new price
    $newPrice = round($newPrice, 2);

    // Insert the new price into cours_marche
    $reqCours = $bdd->prepare("INSERT INTO cours_marche (action_id, game_month, game_year, valeur_action) VALUES (?,?,?,?)");
    $reqCours->execute([$actionId, $gameState['current_month'], $gameState['current_year'], $newPrice]);
    $reqHistory = $bdd->prepare("INSERT INTO historique (action_id, joueur_id, prix, nature, game_month, game_year) VALUES (?,?,?,?,?,?)");
    $reqHistory->execute([$actionId, NULL, $newPrice, 'changement_prix', $gameState['current_month'], $gameState['current_year']]);
    //update the current price
    $reqUpdatePrice = $bdd->prepare("UPDATE actions SET prix = ? WHERE id = ?");
    $reqUpdatePrice->execute([$newPrice, $actionId]);
    //distribute dividend if it's time
    $reqDividend->execute([$gameState['current_month']]);
    $actionsWithDividends = $reqDividend->fetchAll(PDO::FETCH_ASSOC);
    foreach ($actionsWithDividends as $actionWithDividends) {
        $actionId = $actionWithDividends['id'];
        $dividendeParAction = $actionWithDividends['dividende'];
        $reqHistory = $bdd->prepare("INSERT INTO historique (action_id, joueur_id, prix, nature, game_month, game_year) VALUES (?,?,?,?,?,?)");
        $reqGetPortfolio = $bdd->prepare("SELECT joueur_id, quantite FROM portefeuille WHERE action_id = ?");
        $reqGetPortfolio->execute([$actionId]);
        $joueursAvecAction = $reqGetPortfolio->fetchAll();
        foreach ($joueursAvecAction as $joueur) {
            $joueurId = $joueur['joueur_id'];
            $quantite = $joueur['quantite'];
            $dividendeTotal = $dividendeParAction * $quantite;
            $reqUpdateMoney = $bdd->prepare("UPDATE joueur SET argent = argent + ? WHERE id = ?");
            $reqUpdateMoney->execute([$dividendeTotal, $joueurId]);
            $reqHistory->execute([$actionId, $joueurId, $dividendeParAction, 'dividende', $gameState['current_month'], $gameState['current_year']]);
        }
    }
}
?>