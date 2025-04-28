<?php
// Connection BDD
$dbHost = 'localhost';
$dbName = 'virtual_trader';
$dbUser = 'root';
$dbPass = '';

$bdd = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
$bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Recup mois et annee
$gameStateReq = $bdd->query("SELECT current_month, current_year FROM game_state");
$gameState = $gameStateReq->fetch();
$currentMonth = $gameState['current_month'];
$currentYear = $gameState['current_year'];

// Ajouter mois et annee
$currentMonth++;
if ($currentMonth > 12) {
    $currentMonth = 1;
    $currentYear++;
}

// MAJ game_state dans BDD
$updateGameStateReq = $bdd->prepare("UPDATE game_state SET current_month = ?, current_year = ?");
$updateGameStateReq->execute([$currentMonth, $currentYear]);

// Recup tt actions pour MAJ
$actionsReq = $bdd->query("SELECT id, prix FROM actions");
$actions = $actionsReq->fetchAll();

foreach ($actions as $action) {
    $actionId = $action['id'];
    $initialPrice = $action['prix'];

    // Verif derniers prix
    $lastPriceReq = $bdd->prepare("SELECT valeur_action FROM cours_marche WHERE action_id = ? ORDER BY game_year DESC, game_month DESC LIMIT 1");
    $lastPriceReq->execute([$actionId]);
    $lastPrice = $lastPriceReq->fetch();

    // Verif si derniers prix existe
    if ($lastPrice) {
        $lastPrice = $lastPrice['valeur_action'];

        // Change le prix de l'action
        $change = rand(-3, 3) / 100;
        $newPrice = $lastPrice * (1 + $change);


        //----------------------- CHANGER QUE SI ENTRE + ou - 10%, ON NE PEUT AGMENTER MAX QUE DE +-10% -----------------------//





    } else {
        // Nouveau prix avec date
        $insertPriceReq = $bdd->prepare("INSERT INTO cours_marche (action_id, game_month, game_year, valeur_action) VALUES (?, ?, ?, ?)");
        $insertPriceReq->execute([$actionId, $currentMonth, $currentYear, $initialPrice]);
        $newPrice = $initialPrice;
    }

    // Le prix ne peut pas tomber en dessous de 1.
    $newPrice = max(1.00, $newPrice);

    // MAJ prix dans cours marche
    $insertPriceReq = $bdd->prepare("INSERT INTO cours_marche (action_id, game_month, game_year, valeur_action) VALUES (?, ?, ?, ?)");
    $insertPriceReq->execute([$actionId, $currentMonth, $currentYear, $newPrice]);

    // MAJ prix
    $reqUpdatePrice = $bdd->prepare("UPDATE actions SET prix = ? WHERE id = ?");
    $reqUpdatePrice->execute([$newPrice, $actionId]);

    // MAJ historique
    $reqHistory = $bdd->prepare("INSERT INTO historique (action_id, joueur_id, prix, nature, game_month, game_year) VALUES (?,?,?,?,?,?)");
    $reqHistory->execute([$actionId, NULL, $newPrice, 'change_prix', $currentMonth, $currentYear]);

}
// Donner dividendes si besoin
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
