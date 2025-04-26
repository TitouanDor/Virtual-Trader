<?php
session_start();

// Check if the user is logged in
if(!isset($_SESSION['id'])){
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
try{
    // Check if the stock_id, quantity, and action are provided
    if(!isset($_POST['stock_id']) || !isset($_POST['quantity']) || !isset($_POST['action'])){
        die("Missing data");
    }
    
    $idAction = $_POST['stock_id'];
    $quantite = $_POST['quantity'];
    $action = $_POST['action'];
    
    // Connexion à la base de données
    $idJoueur = $_SESSION['id'];
} catch(Exception $e) {
    die("Error: " . $e->getMessage());
}
try{
    // Fetch the action's information
    $reqAction = $bdd->prepare("SELECT prix FROM actions WHERE id = ?");
    $reqAction->execute([$idAction]);
    $actionData = $reqAction->fetch();
    
    if (!$actionData) {
        die("Action not found");
    }
} catch(Exception $e) {
    die("Error: " . $e->getMessage());
}
try{
    // Check if the user has lost
    $valeurPortefeuille = 0;
    $reqPortefeuille = $bdd->prepare("SELECT p.quantite, a.prix FROM portefeuille p JOIN actions a ON p.action_id = a.id WHERE p.joueur_id = ?");
    $reqPortefeuille->execute([$idJoueur]);
    $portefeuille = $reqPortefeuille->fetchAll();
    foreach ($portefeuille as $actionEnPortefeuille) {
        $valeurPortefeuille += $actionEnPortefeuille['quantite'] * $actionEnPortefeuille['prix'];
    }
} catch(Exception $e) {
    die("Error: " . $e->getMessage());
}
try{
    // Fetch the user's information
    $reqJoueur = $bdd->prepare("SELECT argent FROM joueur WHERE id = ?");
    $reqJoueur->execute([$idJoueur]);
    $joueur = $reqJoueur->fetch();
    
    if (!$joueur) {
        die("User not found");
    }
} catch(Exception $e) {
    die("Error: " . $e->getMessage());
}
try{
    $valeurPortefeuille += $joueur['argent'];
    if($valeurPortefeuille < 1000){
        die("You lost");
    }
    
    $prixAction = $actionData['prix'];
    $argentJoueur = $joueur['argent'];
} catch(Exception $e) {
    die("Error: " . $e->getMessage());
}
try{
    if($action == 'Buy'){
        $coutTotal = $prixAction * $quantite;
        if($argentJoueur < $coutTotal){
            die("Not enough money");
        }
        
        // Insert the purchase into the 'portefeuille' table or update it if it already exists
        $reqVerifPortefeuille = $bdd->prepare("SELECT quantite FROM portefeuille WHERE joueur_id=? AND action_id=?");
        $reqVerifPortefeuille->execute([$idJoueur,$idAction]);
        $portefeuilleExistant = $reqVerifPortefeuille->fetch();
        if ($portefeuilleExistant) {
            $nouvelleQuantite = $portefeuilleExistant['quantite'] + $quantite;
            $reqUpdatePortefeuille = $bdd->prepare("UPDATE portefeuille SET quantite=? WHERE joueur_id=? AND action_id=?");
            $reqUpdatePortefeuille->execute([$nouvelleQuantite,$idJoueur,$idAction]);
        } else {
            $reqInsertPortefeuille = $bdd->prepare("INSERT INTO portefeuille (joueur_id, action_id, quantite, purchase_price) VALUES (?, ?, ?, ?)");
            $reqInsertPortefeuille->execute([$idJoueur,$idAction,$quantite,$prixAction]);
        }
        
        // Update the player's money
        $nouvelArgent = $argentJoueur - $coutTotal;
        $reqUpdateJoueur = $bdd->prepare("UPDATE joueur SET argent=? WHERE id=?");
        $reqUpdateJoueur->execute([$nouvelArgent,$idJoueur]);
        
        // Insert the transaction into the 'historique' table
        $reqInsertHistorique = $bdd->prepare("INSERT INTO historique (action_id, joueur_id, prix, nature, game_month, game_year, real_date) VALUES (?, ?, ?, 'buy', (SELECT current_month FROM game_state), (SELECT current_year FROM game_state), NOW())");
        $reqInsertHistorique->execute([$idAction, $idJoueur, $prixAction]);
    }
} catch(Exception $e) {
    die("Error: " . $e->getMessage());
}
try{
    if ($action == 'Sell'){
        // Fetch the number of actions the user owns
        $reqPortefeuille = $bdd->prepare("SELECT quantite FROM portefeuille WHERE joueur_id=? AND action_id=?");
        $reqPortefeuille->execute([$idJoueur,$idAction]);
        $portefeuille = $reqPortefeuille->fetch();
        
        if(!$portefeuille || $portefeuille['quantite'] < $quantite){
            die("Not enough stock");
        }
        
        // Update the number of actions or delete it from the portefeuille table
        $nouvelleQuantite = $portefeuille['quantite'] - $quantite;
        if($nouvelleQuantite == 0){
            $reqDeletePortefeuille = $bdd->prepare("DELETE FROM portefeuille WHERE joueur_id=? AND action_id=?");
            $reqDeletePortefeuille->execute([$idJoueur,$idAction]);
        } else {
            $reqUpdatePortefeuille = $bdd->prepare("UPDATE portefeuille SET quantite=? WHERE joueur_id=? AND action_id=?");
            $reqUpdatePortefeuille->execute([$nouvelleQuantite,$idJoueur,$idAction]);
        }
        
        // Update the player's money
        $nouvelArgent = $argentJoueur + ($prixAction * $quantite);
        $reqUpdateJoueur = $bdd->prepare("UPDATE joueur SET argent = ? WHERE id = ?");
        $reqUpdateJoueur->execute([$nouvelArgent, $idJoueur]);
        
        // Insert the transaction into the 'historique' table
        $reqInsertHistorique = $bdd->prepare("INSERT INTO historique (action_id, joueur_id, prix, nature, game_month, game_year, real_date) VALUES (?, ?, ?, 'sell', (SELECT current_month FROM game_state), (SELECT current_year FROM game_state), NOW())");
        $reqInsertHistorique->execute([$idAction, $idJoueur, $prixAction]);
    }
} catch(Exception $e) {
    die("Error: " . $e->getMessage());
}

header("Location: actionMarket.php");
?>

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
if($valeurPortefeuille < 1000){
    header("Location: actionMarket.php");
    exit();
}

$prixAction = $actionData['prix'];
$argentJoueur = $joueur['argent'];
  
if($action == 'Buy'){
    $coutTotal = $prixAction * $quantite;
    if($argentJoueur < $coutTotal){
        header("Location: actionMarket.php");
        exit();
    }

    // Insert the purchase into the 'portefeuille' table or update it if it already exists
    $reqVerifPortefeuille = $bdd->prepare("SELECT quantite FROM portefeuille WHERE joueur_id=? AND action_id=?");
    $reqVerifPortefeuille->execute([$idJoueur,$idAction]);
    $portefeuilleExistant = $reqVerifPortefeuille->fetch();
    if ($portefeuilleExistant) {
        $nouvelleQuantite = $portefeuilleExistant['quantite'] + $quantite;
        $reqUpdatePortefeuille = $bdd->prepare("UPDATE portefeuille SET quantite=? WHERE joueur_id=? AND action_id=?");
        $reqUpdatePortefeuille->execute([$nouvelleQuantite,$idJoueur,$idAction]);
    } else {
        $reqInsertPortefeuille = $bdd->prepare("INSERT INTO portefeuille (joueur_id, action_id, quantite, purchase_price) VALUES (?, ?, ?, ?)");
        $reqInsertPortefeuille->execute([$idJoueur,$idAction,$quantite,$prixAction]);
    }

    // Update the player's money
    $nouvelArgent = $argentJoueur - $coutTotal;
    $reqUpdateJoueur = $bdd->prepare("UPDATE joueur SET argent=? WHERE id=?");
    $reqUpdateJoueur->execute([$nouvelArgent,$idJoueur]);
    
    // Insert the transaction into the 'historique' table
    $reqInsertHistorique = $bdd->prepare("INSERT INTO historique (action_id, joueur_id, prix, nature, game_month, game_year, real_date) VALUES (?, ?, ?, 'buy', (SELECT current_month FROM game_state), (SELECT current_year FROM game_state), NOW())");
    $reqInsertHistorique->execute([$idAction, $idJoueur, $prixAction]);
} elseif ($action == 'Sell'){
    // Fetch the number of actions the user owns
    $reqPortefeuille = $bdd->prepare("SELECT quantite FROM portefeuille WHERE joueur_id=? AND action_id=?");
    $reqPortefeuille->execute([$idJoueur,$idAction]);
    $portefeuille = $reqPortefeuille->fetch();
  
    if(!$portefeuille || $portefeuille['quantite'] < $quantite){
        header("Location: actionMarket.php");
        exit();
    }
  
    // Update the number of actions or delete it from the portefeuille table
    $nouvelleQuantite = $portefeuille['quantite'] - $quantite;
    if($nouvelleQuantite == 0){
        $reqDeletePortefeuille = $bdd->prepare("DELETE FROM portefeuille WHERE joueur_id=? AND action_id=?");
        $reqDeletePortefeuille->execute([$idJoueur,$idAction]);
    } else {
        $reqUpdatePortefeuille = $bdd->prepare("UPDATE portefeuille SET quantite=? WHERE joueur_id=? AND action_id=?");
        $reqUpdatePortefeuille->execute([$nouvelleQuantite,$idJoueur,$idAction]);
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