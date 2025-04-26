<?php
// Database connection information
$dbHost = 'localhost';
$dbName = 'virtual_trader';
$dbUser = 'root';
$dbPass = '';

// Database connection
$bdd = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
$bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get the current month and year
$gameStateReq = $bdd->query("SELECT current_month, current_year FROM game_state");
$gameState = $gameStateReq->fetch();
$currentMonth = $gameState['current_month'];
$currentYear = $gameState['current_year'];

// Increment the month
$currentMonth++;
if ($currentMonth > 12) {
    $currentMonth = 1;
    $currentYear++;
}

// Update the game state in the database
$updateGameStateReq = $bdd->prepare("UPDATE game_state SET current_month = ?, current_year = ?");
$updateGameStateReq->execute([$currentMonth, $currentYear]);

// Get all actions
$actionsReq = $bdd->query("SELECT id, prix FROM actions");
$actions = $actionsReq->fetchAll();

// Process each action
foreach ($actions as $action) {
    $actionId = $action['id'];
    $initialPrice = $action['prix'];

    // Get the last price
    $lastPriceReq = $bdd->prepare("SELECT valeur_action FROM cours_marche WHERE action_id = ? ORDER BY game_year DESC, game_month DESC LIMIT 1");
    $lastPriceReq->execute([$actionId]);
    $lastPrice = $lastPriceReq->fetch();

    //check if a price exist
    if ($lastPrice) {
        $lastPrice = $lastPrice['valeur_action'];

        // Generate a random change
        $change = rand(-10, 10) / 100; // Change between -10% and +10%

        // Update the price
        $newPrice = $lastPrice * (1 + $change);

    } else {
        //Insert a price with the current month and year and the initial price
        $insertPriceReq = $bdd->prepare("INSERT INTO cours_marche (action_id, game_month, game_year, valeur_action) VALUES (?, ?, ?, ?)");
        $insertPriceReq->execute([$actionId, $currentMonth, $currentYear, $initialPrice]);
        $newPrice = $initialPrice;
    }

    // Apply limits (minimum 1)
    $newPrice = max(1, $newPrice);

    // Insert the new price into cours_marche
    $insertPriceReq = $bdd->prepare("INSERT INTO cours_marche (action_id, game_month, game_year, valeur_action) VALUES (?, ?, ?, ?)");
    $insertPriceReq->execute([$actionId, $currentMonth, $currentYear, $newPrice]);

    //update the current price
    $reqUpdatePrice = $bdd->prepare("UPDATE actions SET prix = ? WHERE id = ?");
    $reqUpdatePrice->execute([$newPrice, $actionId]);

    //add to the history
    $reqHistory = $bdd->prepare("INSERT INTO historique (action_id, joueur_id, prix, nature, game_month, game_year) VALUES (?,?,?,?,?,?)");
    $reqHistory->execute([$actionId, NULL, $newPrice, 'changement_prix', $currentMonth, $currentYear]);

}
//distribute dividend if it's time
$reqDividend = $bdd->prepare("SELECT id, dividende FROM actions WHERE date_dividende = ?");
$reqDividend->execute([$currentMonth]);
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
        $reqHistory->execute([$actionId, $joueurId, $dividendeParAction, 'dividende', $currentMonth, $currentYear]);
    }
}
?>