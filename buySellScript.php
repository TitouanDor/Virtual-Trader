<?php
session_start();

if (!isset($_SESSION['id'])) { header("location: index.php"); exit(); }

$idJoueur = $_SESSION['id'];

// Vérifie si l'ID de l'action, la quantité et l'action sont fournis
if (!isset($_POST['stock_id']) || !isset($_POST['quantity']) || !isset($_POST['action'])) {
    header('Location: marcher.php'); exit();
}

$idAction = $_POST['stock_id'];
$quantite = $_POST['quantity'];
$action = $_POST['action'];

// Connexion à la base de données
$bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
$bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Récupère les informations de l'action
$reqAction = $bdd->prepare("SELECT nom, prix FROM actions WHERE id = ?");
$reqAction->execute([$idAction]);
$action = $reqAction->fetch();

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