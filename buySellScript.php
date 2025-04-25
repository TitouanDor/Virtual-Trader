php
<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header('location: index.html');
    exit();
}

// Check if required POST parameters are set
if (!isset($_POST['stockId']) || !isset($_POST['quantity']) || !isset($_POST['action'])) {
    header('location: marcher.php');
    exit();
}

// Get data from POST request
$stockId = $_POST['stockId'];
$quantity = $_POST['quantity'];
$action = $_POST['action'];
$userId = $_SESSION['id'];

// Database connection
$bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');

// Get stock price
$req = $bdd->prepare("SELECT prix FROM actions WHERE id = ?");
$req->execute([$stockId]);
$stockPrice = $req->fetchColumn();

// Get user cash
$req = $bdd->prepare("SELECT argent FROM joueur WHERE id = ?");
$req->execute([$userId]);
$userCash = $req->fetchColumn();

// Get user current quantity of the stock
$req = $bdd->prepare("SELECT quantity FROM portefeuille WHERE player_id = ? AND stock_id = ?");
$req->execute([$userId, $stockId]);
$userStockQuantity = $req->fetchColumn();
if ($userStockQuantity === false) {
    $userStockQuantity = 0;
}

$totalPrice = $stockPrice * $quantity;

// Handle buy action
if ($action == 'buy') {
    if ($userCash < $totalPrice) {
        header('location: pageAction.php?id=' . $stockId . '&error=notEnoughMoney');
        exit();
    }

    // Update user cash
    $newCash = $userCash - $totalPrice;
    $req = $bdd->prepare("UPDATE joueur SET argent = ? WHERE id = ?");
    $req->execute([$newCash, $userId]);

    // Check if the stock is in the portfolio
    if ($userStockQuantity > 0) {
        // Update portfolio
        $newQuantity = $userStockQuantity + $quantity;
        $req = $bdd->prepare("UPDATE portefeuille SET quantity = ? WHERE player_id = ? AND stock_id = ?");
        $req->execute([$newQuantity, $userId, $stockId]);

    } else {
        // Add stock to portfolio
        $req = $bdd->prepare("INSERT INTO portefeuille (player_id, stock_id, quantity, purchase_price) VALUES (?, ?, ?, ?)");
        $req->execute([$userId, $stockId, $quantity, $stockPrice]);
    }
    // Add transaction to history
    $req = $bdd->prepare("INSERT INTO historique (stock_id, player_id, price, nature, game_month, game_year) VALUES (?, ?, ?, 'buy', (SELECT current_month FROM game_state WHERE id=1),(SELECT current_year FROM game_state WHERE id=1))");
    $req->execute([$stockId, $userId, $totalPrice]);
}

// Handle sell action
if ($action == 'sell') {
    if ($userStockQuantity < $quantity) {
        header('location: pageAction.php?id=' . $stockId . '&error=notEnoughStocks');
        exit();
    }

    // Update user cash
    $newCash = $userCash + $totalPrice;
    $req = $bdd->prepare("UPDATE joueur SET argent = ? WHERE id = ?");
    $req->execute([$newCash, $userId]);

    // Update portfolio
    $newQuantity = $userStockQuantity - $quantity;

    if ($newQuantity == 0) {
        // Delete stock from portfolio
        $req = $bdd->prepare("DELETE FROM portefeuille WHERE player_id = ? AND stock_id = ?");
        $req->execute([$userId, $stockId]);
    } else {
        $req = $bdd->prepare("UPDATE portefeuille SET quantity = ? WHERE player_id = ? AND stock_id = ?");
        $req->execute([$newQuantity, $userId, $stockId]);
    }

        // Add transaction to history
        $req = $bdd->prepare("INSERT INTO historique (stock_id, player_id, price, nature, game_month, game_year) VALUES (?, ?, ?, 'sell', (SELECT current_month FROM game_state WHERE id=1),(SELECT current_year FROM game_state WHERE id=1))");
        $req->execute([$stockId, $userId, $totalPrice]);
}

// Redirect to pageAction.php
header('location: pageAction.php?id=' . $stockId);
exit();
?>