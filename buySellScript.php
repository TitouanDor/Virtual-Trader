<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['id'])) {
    header('location: index.html');
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
    header("Location: actionMarket.php");
    exit();
}

// Check if the required fields are set
if (isset($_POST['action_id'], $_POST['quantity'], $_POST['action'])) {
    $stockId = $_POST['action_id'];
    $quantity = $_POST['quantity'];
    $action = $_POST['action'];
    $userId = $_SESSION['id'];

    // Get the stock price
    try {
        $stockReq = $bdd->prepare("SELECT prix FROM actions WHERE id = ?");
        $stockReq->execute([$stockId]);
        $stock = $stockReq->fetch();
        if (!$stock) {
            header("Location: actionMarket.php");
            exit();
        }
        $stockPrice = $stock['prix'];
    } catch (PDOException $e) {
        header("Location: actionMarket.php");
        exit();
    }

    //Get the current game state
    try {
        $gameStateReq = $bdd->query("SELECT current_month, current_year FROM game_state");
        $gameState = $gameStateReq->fetch();
        if(!$gameState){
            header("Location: actionMarket.php");
            exit();
        }
        $currentMonth = $gameState['current_month'];
        $currentYear = $gameState['current_year'];
    } catch (PDOException $e) {
        header("Location: actionMarket.php");
        exit();
    }

    // Get the user's balance
    try {
        $userReq = $bdd->prepare("SELECT argent FROM joueur WHERE id = ?");
        $userReq->execute([$userId]);
        $user = $userReq->fetch();
        if (!$user) {
            header("Location: actionMarket.php");
            exit();
        }
        $userBalance = $user['argent'];
    } catch (PDOException $e) {
        header("Location: actionMarket.php");
        exit();
    }

    if (isset($_POST['action']) && $_POST['action'] == "Buy") {
    if ($actionType == "Buy") {
        // Check if the user has enough money
        $totalCost = $stockPrice * $quantity;
        if ($userBalance >= $totalCost) {
            // Update the user's balance
            try {
                $newUserBalance = $userBalance - $totalCost;
                $updateUserBalanceReq = $bdd->prepare("UPDATE joueur SET argent = ? WHERE id = ?");
                $updateUserBalanceReq->execute([$newUserBalance, $userId]);
            } catch (PDOException $e) {
                header("Location: actionMarket.php");
                exit();
            }

            // Check if the user already has the stock
            try {
                $portfolioReq = $bdd->prepare("SELECT quantite FROM portefeuille WHERE joueur_id = ? AND action_id = ?");
                $portfolioReq->execute([$userId, $stockId]);
                $portfolio = $portfolioReq->fetch();
            } catch (PDOException $e) {
                header("Location: actionMarket.php");
                exit();
            }

            if ($portfolio) {
                // Update the quantity
                try {
                    $newQuantity = $portfolio['quantite'] + $quantity;
                    $updatePortfolioReq = $bdd->prepare("UPDATE portefeuille SET quantite = ? WHERE joueur_id = ? AND action_id = ?");
                    $updatePortfolioReq->execute([$newQuantity, $userId, $stockId]);
                } catch (PDOException $e) {
                    header("Location: actionMarket.php");
                    exit();
                }
            } else {
                // Add the stock to the portfolio
                try {
                    $addToPortfolioReq = $bdd->prepare("INSERT INTO portefeuille (joueur_id, action_id, quantite, purchase_price) VALUES (?, ?, ?, ?)");
                    $addToPortfolioReq->execute([$userId, $stockId, $quantity, $stockPrice]);
                } catch (PDOException $e) {
                    header("Location: actionMarket.php");
                    exit();
                }
            }
            // Add a line in the historique
            try {
                $insertHistoriqueReq = $bdd->prepare("INSERT INTO historique (action_id, joueur_id, prix, nature, game_month, game_year) VALUES (?, ?, ?, ?, ?, ?)");
                $insertHistoriqueReq->execute([$stockId, $userId, $stockPrice, "Buy", $currentMonth, $currentYear]);
            } catch (PDOException $e) {
                header("Location: actionMarket.php");
                exit();
            }
            // Redirect to marcher.php
            header("Location: actionMarket.php");
            exit();
        } else {
            // Not enough money
            header("Location: actionMarket.php");
            exit();
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == "Sell") {
        // Check if the user has enough stock
        try {
            $portfolioReq = $bdd->prepare("SELECT quantite FROM portefeuille WHERE joueur_id = ? AND action_id = ?");
            $portfolioReq->execute([$userId, $stockId]);
            $portfolio = $portfolioReq->fetch();
        } catch (PDOException $e) {
            header("Location: actionMarket.php");
            exit();
        }

        if ($portfolio && $portfolio['quantite'] >= $quantity) {
            // Update the user's balance
            try {
                $totalIncome = $stockPrice * $quantity;
                $newUserBalance = $userBalance + $totalIncome;
                $updateUserBalanceReq = $bdd->prepare("UPDATE joueur SET argent = ? WHERE id = ?");
                $updateUserBalanceReq->execute([$newUserBalance, $userId]);
            } catch (PDOException $e) {
                header("Location: actionMarket.php");
                exit();
            }

            // Update the quantity in the portfolio
            $newQuantity = $portfolio['quantite'] - $quantity;
            if ($newQuantity > 0) {
                // Update the quantity
                try {
                    $updatePortfolioReq = $bdd->prepare("UPDATE portefeuille SET quantite = ? WHERE joueur_id = ? AND action_id = ?");
                    $updatePortfolioReq->execute([$newQuantity, $userId, $stockId]);
                } catch (PDOException $e) {
                    header("Location: actionMarket.php");
                    exit();
                }
            } else {
                // Remove the stock from the portfolio
                try {
                    $removePortfolioReq = $bdd->prepare("DELETE FROM portefeuille WHERE joueur_id = ? AND action_id = ?");
                    $removePortfolioReq->execute([$userId, $stockId]);
                } catch (PDOException $e) {
                    header("Location: actionMarket.php");
                    exit();
                }
            }
            // Add a line in the historique
            try {
                $insertHistoriqueReq = $bdd->prepare("INSERT INTO historique (action_id, joueur_id, prix, nature, game_month, game_year) VALUES (?, ?, ?, ?, ?, ?)");
                $insertHistoriqueReq->execute([$stockId, $userId, $stockPrice, "Sell", $currentMonth, $currentYear]);
            } catch (PDOException $e) {
                header("Location: actionMarket.php");
                exit();
            }

            // Redirect to marcher.php
            header("Location: actionMarket.php");
            exit();
        } else {
            // Not enough stock
            header("Location: actionMarket.php");
            exit();
        }
    }
} else {
    // Missing fields
    header("Location: actionMarket.php");
    exit();
} 
}