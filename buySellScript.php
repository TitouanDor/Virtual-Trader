<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("location: index.php");
    exit();
}
$player_id = $_SESSION['id'];
// Check if stock ID, quantity and action are provided
if (!isset($_POST['stock_id']) || !isset($_POST['quantity']) || !isset($_POST['action'])) {
    $_SESSION['error'] = "Invalid data provided.";
    header('Location: marcher.php');
    exit();
}

$stockId = $_POST['stock_id'];
$quantity = $_POST['quantity'];
$action = $_POST['action'];

try {
    // Database connection
    $bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get stock information
    $req = $bdd->prepare("SELECT nom, prix FROM actions WHERE id = ?");
    $req->execute([$stockId]);
    $stock = $req->fetch();

    if (!$stock) {
        $_SESSION['error'] = "Stock not found.";
        header("Location: marcher.php");
        exit();
    }
    //check if the player has lost
    $portfolioValue = 0;
    $portfolioReq = $bdd->prepare("SELECT p.quantity, a.prix FROM portefeuille p JOIN actions a ON p.stock_id = a.id WHERE p.player_id = ?");
    $portfolioReq->execute([$player_id]);
    $portfolio = $portfolioReq->fetchAll();
    foreach ($portfolio as $stockInPortfolio) {
      $portfolioValue += $stockInPortfolio['quantity'] * $stockInPortfolio['prix'];
    }
    // Get player information
    $playerReq = $bdd->prepare("SELECT argent FROM joueur WHERE id = ?");
    $playerReq->execute([$player_id]);
    $player = $playerReq->fetch();

    if (!$player) {
        $_SESSION['error'] = "Player not found.";
        header("Location: marcher.php");
        exit();
    }
    $portfolioValue += $player['argent'];
    if ($portfolioValue < 1000) {
        $_SESSION['lost'] = true;
        $_SESSION['error'] = "You have lost the game because your portfolio value is under 1000€.";
        header("Location: index.php");// Check if stock ID, quantity and action are provided
        exit();
    }
        $_SESSION['error'] = "Player not found.";
        header("Location: marcher.php");
        exit();
    }

    $stockPrice = $stock['prix'];
    $playerMoney = $player['argent'];

    if ($action == 'buy') {
        $totalCost = $stockPrice * $quantity;
        if ($playerMoney < $totalCost) {
            $_SESSION['error'] = "Insufficient funds to buy this stock.";
            header("Location: pageAction.php?id=" . $stockId);
            exit();
        }

        // Insert the purchase in the 'portefeuille' table or update it if it already exists
        $portfolioCheckReq = $bdd->prepare("SELECT quantity FROM portefeuille WHERE player_id = ? AND stock_id = ?");
        $portfolioCheckReq->execute([$player_id, $stockId]);
        $existingPortfolio = $portfolioCheckReq->fetch();
        if($existingPortfolio){
          $newQuantity = $existingPortfolio['quantity'] + $quantity;
          $updatePortfolioReq = $bdd->prepare("UPDATE portefeuille SET quantity = ? WHERE player_id = ? AND stock_id = ?");
          $updatePortfolioReq->execute([$newQuantity, $player_id, $stockId]);
        }else{
          $insertPortfolioReq = $bdd->prepare("INSERT INTO portefeuille (player_id, stock_id, quantity, purchase_price, purchase_date) VALUES (?, ?, ?, ?, NOW())");
          $insertPortfolioReq->execute([$player_id, $stockId, $quantity, $stockPrice]);
        }

        // Update the player's money
        $newMoney = $playerMoney - $totalCost;
        $updatePlayerReq = $bdd->prepare("UPDATE joueur SET argent = ? WHERE id = ?");
        $updatePlayerReq->execute([$newMoney, $player_id]);

        // Insert the transaction in the 'historique' table
        $insertHistoryReq = $bdd->prepare("INSERT INTO historique (stock_id, player_id, price, nature, real_date) VALUES (?, ?, ?, 'buy', NOW())");
        $insertHistoryReq->execute([$stockId, $player_id, $stockPrice]);
    } elseif ($action == 'sell') {
        // Get the number of stocks the user possesses
        $portfolioReq = $bdd->prepare("SELECT quantity FROM portefeuille WHERE player_id = ? AND stock_id = ?");
        $portfolioReq->execute([$player_id, $stockId]);
        $portfolio = $portfolioReq->fetch();

        if (!$portfolio || $portfolio['quantity'] < $quantity) {
            $_SESSION['error'] = "Insufficient stocks to sell.";
            header("Location: pageAction.php?id=" . $stockId);
            exit();
        }

        // Update the number of stocks or delete it from portefeuille table
        $newQuantity = $portfolio['quantity'] - $quantity;
        if ($newQuantity == 0) {
            $deletePortfolioReq = $bdd->prepare("DELETE FROM portefeuille WHERE player_id = ? AND stock_id = ?");
            $deletePortfolioReq->execute([$player_id, $stockId]);
        } else {
            $updatePortfolioReq = $bdd->prepare("UPDATE portefeuille SET quantity = ? WHERE player_id = ? AND stock_id = ?");
            $updatePortfolioReq->execute([$newQuantity, $player_id, $stockId]);
        }

        // Update the player's money
        $newMoney = $playerMoney + ($stockPrice * $quantity);
        $updatePlayerReq = $bdd->prepare("UPDATE joueur SET argent = ? WHERE id = ?");
        $updatePlayerReq->execute([$newMoney, $player_id]);

        // Insert the transaction in the 'historique' table
        $insertHistoryReq = $bdd->prepare("INSERT INTO historique (stock_id, player_id, price, nature, real_date) VALUES (?, ?, ?, 'sell', NOW())");
        $insertHistoryReq->execute([$stockId, $player_id, $stockPrice]);
    }
    header("Location: pageAction.php?id=" . $stockId);
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error.";
    header("Location: pageAction.php?id=" . $stockId);
}
?>