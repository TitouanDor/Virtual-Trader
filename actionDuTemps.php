<?php
// Permet de faire passer le temps
// Informations de connexion à la base de données
$dbHost = 'localhost';
$dbName = 'virtual_trader';
$dbUser = 'root';
$dbPass = '';

// Nouvelle connexion à la base de données
$bdd = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
$bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Préparer la requête de base pour le prix
$reqBasePrice = $bdd->prepare("SELECT prix FROM actions WHERE id = ?");

// Récupérer l'état actuel du jeu une fois
$reqGetGameState = $bdd->prepare("SELECT current_month, current_year FROM game_state WHERE id = 1");
$reqGetGameState->execute();
$gameState = $reqGetGameState->fetch(PDO::FETCH_ASSOC);


// Distribuer les dividendes
$req = $bdd->prepare("SELECT id, dividende FROM actions WHERE date_dividende = ?");
$req->execute([$gameState['current_month']]);
$actionsWithDividends = $req->fetchAll(PDO::FETCH_ASSOC);

foreach ($actionsWithDividends as $action) {
    $actionId = $action['id'];
    $dividendeParAction = $action['dividende'];
    // Ajouter le dividende dans l'historique
    $reqHistory = $bdd->prepare("INSERT INTO historique (action_id, joueur_id, prix, nature, game_month, game_year) VALUES (?,?,?,?,?,?)");

    // Récupérer les joueurs qui possèdent l'action
    $req = $bdd->prepare("SELECT joueur_id, quantite FROM portefeuille WHERE action_id = ?");
    $req->execute([$actionId]);
    $joueursAvecAction = $req->fetchAll();

    foreach ($joueursAvecAction as $joueur) {
        $joueurId = $joueur['player_id'];
        $quantite = $joueur['quantite'];
        $dividendeTotal = $dividendeParAction * $quantite;

        // Mettre à jour l'argent du joueur
        $req = $bdd->prepare("UPDATE joueur SET argent = argent + ? WHERE id = ?");
        $req->execute([$dividendeTotal, $joueurId]);
        $reqHistory->execute([$actionId, $joueurId, $dividendeParAction, 'dividende', $gameState['current_month'], $gameState['current_year']]);
    }if (count($joueursAvecAction) == 0) {
        $reqHistory->execute([$actionId, $joueurId, $dividendeParAction, 'dividende', $gameState['current_month'], $gameState['current_year']]);
    }
}
// Mettre à jour les prix des actions

$req = $bdd->prepare("SELECT id FROM actions");
$req->execute();
$stocks = $req->fetchAll(PDO::FETCH_ASSOC);

foreach ($stocks as $action) {
    $actionId = $action['id'];

    $changementAleatoire = rand(-3,3);
    // Récupérer le prix du mois dernier

    $reqLastPrice = $bdd->prepare("SELECT valeur_action FROM cours_marche WHERE action_id = ? AND game_month = ? AND game_year = ? ");
    if ($gameState['current_month'] == 1 && $gameState['current_year'] == 1) {
        $reqLastPrice->execute([$actionId, 12, 1]);
    } elseif ($gameState['current_month'] == 1) {
        $reqLastPrice->execute([$actionId, 12, $gameState['current_year'] - 1]);
    } else {
        $reqLastPrice->execute([$actionId, $gameState['current_month'] - 1, $gameState['current_year']]);
    }
    $donneesDernierPrix = $reqLastPrice->fetchAll(PDO::FETCH_ASSOC);
    if ($donneesDernierPrix) {

        $dernierPrix = $donneesDernierPrix[0]['valeur_action'];
    } else {
        $reqBasePrice->execute([$actionId]);
        $dernierPrix = $reqBasePrice->fetch(PDO::FETCH_ASSOC)['prix'];

    }

    // Calculer le nouveau prix
    $changementDePrix = ($dernierPrix * ($changementAleatoire / 100));
    $nouveauPrix = $dernierPrix + $changementDePrix;

    // Appliquer les limites
    $limiteMin = $dernierPrix * 0.9;
    $limiteMax = $dernierPrix * 1.1;
    $nouveauPrix = max(1, min($nouveauPrix, $limiteMax));

    $nouveauPrix = round($nouveauPrix, 2);


    // Ajouter le changement de prix dans l'historique
    $req = $bdd->prepare("INSERT INTO historique (action_id, joueur_id, prix, nature, game_month, game_year) VALUES (?,?,?,?,?,?)");

    // Insérer le nouveau prix dans cours_marche
    $reqCours = $bdd->prepare("INSERT INTO cours_marche (action_id, game_month, game_year, valeur_action) VALUES (?,?,?,?)");
    $reqCours->execute([$actionId, $gameState['current_month'], $gameState['current_year'], $nouveauPrix]);
    $req->execute([$actionId, NULL, $nouveauPrix, 'changement_prix', $gameState['current_month'], $gameState['current_year']]);

    // Mettre à jour le prix dans actions
    $req = $bdd->prepare("UPDATE actions SET prix = ? WHERE id = ?");
    $req->execute([$nouveauPrix, $actionId]);
}
// Incrémenter le mois et l'année
// Récupérer le dernier état du jeu
$reqGetGameState = $bdd->prepare("SELECT current_month, current_year FROM game_state WHERE id = 1");
$reqGetGameState->execute();
$gameState = $reqGetGameState->fetch(PDO::FETCH_ASSOC);
$gameState['current_month']++;
if ($gameState['current_month'] > 12) {
    $gameState['current_month'] = 1;
    $gameState['current_year']++;
}
$req = $bdd->prepare("UPDATE game_state SET current_month = ?, current_year = ? WHERE id = 1");
$req->execute([$gameState['current_month'], $gameState['current_year']]);

?>