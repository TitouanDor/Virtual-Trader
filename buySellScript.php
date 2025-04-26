<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['id'])) {
    header("location: index.html");
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

// Check if the stock_id, quantity, and action are provided
if (!isset($_POST['stock_id']) || !isset($_POST['quantity']) || !isset($_POST['action'])) {
    header('Location: actionMarket.php');
    exit();
}

$idAction = $_POST['stock_id'];
$quantite = $_POST['quantity'];
$action = $_POST['action'];

// Connexion à la base de données
$idJoueur = $_SESSION['id'];

// Fetch the action's information
$reqAction = $bdd->prepare("SELECT prix FROM actions WHERE id = ?");
$reqAction->execute([$idAction]);
$actionData = $reqAction->fetch();

if (!$actionData) {
    header("Location: actionMarket.php");
    exit();
}

// Check if the user has lost
$valeurPortefeuille = 0;
$reqPortefeuille = $bdd->prepare("SELECT p.quantite, a.prix FROM portefeuille p JOIN actions a ON p.action_id = a.id WHERE p.joueur_id = ?");
$reqPortefeuille->execute([$idJoueur]);
$portefeuille = $reqPortefeuille->fetchAll();
foreach ($portefeuille as $actionEnPortefeuille) {
    $valeurPortefeuille += $actionEnPortefeuille['quantite'] * $actionEnPortefeuille['prix'];
}

// Fetch the user's information
$reqJoueur = $bdd->prepare("SELECT argent FROM joueur WHERE id = ?");
$reqJoueur->execute([$idJoueur]);
$joueur = $reqJoueur->fetch();

if (!$joueur) {
    header("Location: actionMarket.php");
    exit();
}

$valeurPortefeuille += $joueur['argent'];
if ($valeurPortefeuille < 1000) {
    header("Location: actionMarket.php");
    exit();
}

$prixAction = $actionData['prix'];
$argentJoueur = $joueur['argent'];

if ($action == 'Buy') {
    $coutTotal = $prixAction * $quantite;
    if ($argentJoueur < $coutTotal) {
        header("Location: actionMarket.php");
        exit();
    }

    // Insert the purchase into the 'portefeuille' table or update it if it already exists
    $reqVerifPortefeuille = $bdd->prepare("SELECT quantite FROM portefeuille WHERE joueur_id = ? AND action_id = ?");
    $reqVerifPortefeuille->execute([$idJoueur, $idAction]);
    $portefeuilleExistant = $reqVerifPortefeuille->fetch();
    if ($portefeuilleExistant) {
        $nouvelleQuantite = $portefeuilleExistant['quantite'] + $quantite;
        $reqUpdatePortefeuille = $bdd->prepare("UPDATE portefeuille SET quantite = ? WHERE joueur_id = ? AND action_id = ?");
        $reqUpdatePortefeuille->execute([$nouvelleQuantite, $idJoueur, $idAction]);
    } else {
        $reqInsertPortefeuille = $bdd->prepare("INSERT INTO portefeuille (joueur_id, action_id, quantite, purchase_price, purchase_date) VALUES (?, ?, ?, ?, NOW())");
        $reqInsertPortefeuille->execute([$idJoueur, $idAction, $quantite, $prixAction]);
    }

    // Update the player's money
    $nouvelArgent = $argentJoueur - $coutTotal;
    $reqUpdateJoueur = $bdd->prepare("UPDATE joueur SET argent = ? WHERE id = ?");
    $reqUpdateJoueur->execute([$nouvelArgent, $idJoueur]);

    // Insert the transaction into the 'historique' table
    $reqInsertHistorique = $bdd->prepare("INSERT INTO historique (action_id, joueur_id, prix, nature, game_month, game_year, real_date) VALUES (?, ?, ?, 'buy', (SELECT current_month FROM game_state), (SELECT current_year FROM game_state), NOW())");
    $reqInsertHistorique->execute([$idAction, $idJoueur, $prixAction]);
} elseif ($action == 'Sell') {
    // Fetch the number of actions the user owns
    $reqPortefeuille = $bdd->prepare("SELECT quantite FROM portefeuille WHERE joueur_id = ? AND action_id = ?");
    $reqPortefeuille->execute([$idJoueur, $idAction]);
    $portefeuille = $reqPortefeuille->fetch();

    if (!$portefeuille || $portefeuille['quantite'] < $quantite) {
        header("Location: actionMarket.php");
        exit();
    }

    // Update the number of actions or delete it from the portefeuille table
    $nouvelleQuantite = $portefeuille['quantite'] - $quantite;
    if ($nouvelleQuantite == 0) {
        $reqDeletePortefeuille = $bdd->prepare("DELETE FROM portefeuille WHERE joueur_id = ? AND action_id = ?");
        $reqDeletePortefeuille->execute([$idJoueur, $idAction]);
    } else {
        $reqUpdatePortefeuille = $bdd->prepare("UPDATE portefeuille SET quantite = ? WHERE joueur_id = ? AND action_id = ?");
        $reqUpdatePortefeuille->execute([$nouvelleQuantite, $idJoueur, $idAction]);
    }

    // Update the player's money
    $nouvelArgent = $argentJoueur + ($prixAction * $quantite);
    $reqUpdateJoueur = $bdd->prepare("UPDATE joueur SET argent = ? WHERE id = ?");
    $reqUpdateJoueur->execute([$nouvelArgent, $idJoueur]);

    // Insert the transaction into the 'historique' table
    $reqInsertHistorique = $bdd->prepare("INSERT INTO historique (action_id, joueur_id, prix, nature, game_month, game_year, real_date) VALUES (?, ?, ?, 'sell', (SELECT current_month FROM game_state), (SELECT current_year FROM game_state), NOW())");
    $reqInsertHistorique->execute([$idAction, $idJoueur, $prixAction]);
}

header("Location: actionMarket.php");
?>

if (!$action) { header("Location: marcher.php"); exit(); }

// Vérifier si le joueur a perdu
$valeurPortefeuille = 0;
$reqPortefeuille = $bdd->prepare("SELECT p.quantity, a.prix FROM portefeuille p JOIN actions a ON p.stock_id = a.id WHERE p.player_id = ?");
$reqPortefeuille->execute([$idJoueur]);
$portefeuille = $reqPortefeuille->fetchAll();
foreach ($portefeuille as $actionEnPortefeuille) {
  $valeurPortefeuille += $actionEnPortefeuille['quantity'] * $actionEnPortefeuille['prix'];
}

// Récupère les informations du joueur
$reqJoueur = $bdd->prepare("SELECT argent FROM joueur WHERE id = ?");
$reqJoueur->execute([$idJoueur]);
$joueur = $reqJoueur->fetch();

if (!$joueur) { header("Location: marcher.php"); exit(); }

$valeurPortefeuille += $joueur['argent'];
if ($valeurPortefeuille < 1000) { header("Location: marcher.php"); exit(); }

$prixAction = $action['prix'];
$argentJoueur = $joueur['argent'];

if ($action == 'buy') {
    $coutTotal = $prixAction * $quantite;
    if ($argentJoueur < $coutTotal) {
        header("Location: pageAction.php?id=" . $idAction);
        exit();
    }

    // Insère l'achat dans la table 'portefeuille' ou le met à jour si elle existe déjà
    $reqVerifPortefeuille = $bdd->prepare("SELECT quantity FROM portefeuille WHERE player_id = ? AND stock_id = ?");
    $reqVerifPortefeuille->execute([$idJoueur, $idAction]);
    $portefeuilleExistant = $reqVerifPortefeuille->fetch();
    if($portefeuilleExistant){
      $nouvelleQuantite = $portefeuilleExistant['quantity'] + $quantite;
      $reqUpdatePortefeuille = $bdd->prepare("UPDATE portefeuille SET quantity = ? WHERE player_id = ? AND stock_id = ?");
      $reqUpdatePortefeuille->execute([$nouvelleQuantite, $idJoueur, $idAction]);
    }else{
      $reqInsertPortefeuille = $bdd->prepare("INSERT INTO portefeuille (player_id, stock_id, quantity, purchase_price, purchase_date) VALUES (?, ?, ?, ?, NOW())");
      $reqInsertPortefeuille->execute([$idJoueur, $idAction, $quantite, $prixAction]);
    }

    // Met à jour l'argent du joueur
    $nouvelArgent = $argentJoueur - $coutTotal;
    $reqUpdateJoueur = $bdd->prepare("UPDATE joueur SET argent = ? WHERE id = ?");
    $reqUpdateJoueur->execute([$nouvelArgent, $idJoueur]);

    // Insère la transaction dans la table 'historique'
    $reqInsertHistorique = $bdd->prepare("INSERT INTO historique (stock_id, player_id, price, nature, real_date) VALUES (?, ?, ?, 'buy', NOW())");
    $reqInsertHistorique->execute([$idAction, $idJoueur, $prixAction]);
} elseif ($action == 'sell') {
    // Récupère le nombre d'actions que l'utilisateur possède
    $reqPortefeuille = $bdd->prepare("SELECT quantity FROM portefeuille WHERE player_id = ? AND stock_id = ?");
    $reqPortefeuille->execute([$idJoueur, $idAction]);
    $portefeuille = $reqPortefeuille->fetch();

    if (!$portefeuille || $portefeuille['quantity'] < $quantite) {
        header("Location: pageAction.php?id=" . $idAction);
        exit();
    }

    // Met à jour le nombre d'actions ou le supprime de la table portefeuille
    $nouvelleQuantite = $portefeuille['quantity'] - $quantite;
    if ($nouvelleQuantite == 0) {
        $reqDeletePortefeuille = $bdd->prepare("DELETE FROM portefeuille WHERE player_id = ? AND stock_id = ?");
        $reqDeletePortefeuille->execute([$idJoueur, $idAction]);
    } else {
        $reqUpdatePortefeuille = $bdd->prepare("UPDATE portefeuille SET quantity = ? WHERE player_id = ? AND stock_id = ?");
        $reqUpdatePortefeuille->execute([$nouvelleQuantite, $idJoueur, $idAction]);
    }

    // Met à jour l'argent du joueur
    $nouvelArgent = $argentJoueur + ($prixAction * $quantite);
    $reqUpdateJoueur = $bdd->prepare("UPDATE joueur SET argent = ? WHERE id = ?");
    $reqUpdateJoueur->execute([$nouvelArgent, $idJoueur]);

    // Insère la transaction dans la table 'historique'
    $reqInsertHistorique = $bdd->prepare("INSERT INTO historique (stock_id, player_id, price, nature, real_date) VALUES (?, ?, ?, 'sell', NOW())");
    $reqInsertHistorique->execute([$idAction, $idJoueur, $prixAction]);
}
header("Location: pageAction.php?id=" . $idAction);
?>