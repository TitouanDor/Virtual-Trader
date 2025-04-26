<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['id'])) {
    header('location: index.php');
    exit();
}

// Database connection
try {
    $dbHost = 'localhost';
    $dbName = 'virtual_trader';
    $dbUser = 'root';
    $dbPass = '';
    $bdd = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $_SESSION['error'] = "Database connection error: " . $e->getMessage();
    header('Location: index.php');
    exit();
}

// Initialize error message
$errorMessage = "";

// Check if the user has lost the game
try {
    $req = $bdd->prepare("SELECT j.id FROM joueur j WHERE j.id = ? AND j.id NOT IN (SELECT p.player_id FROM portefeuille p) AND j.argent < 1000");
    $req->execute([$_SESSION['id']]);
    if ($req->fetch()) {
        $errorMessage = "You have lost the game!";
    }
} catch (PDOException $e) {
    $errorMessage = "Database error: " . $e->getMessage();
}
// Get the current game state and update if necessary
if (empty($errorMessage)) {
    try {
        $gameStateReq = $bdd->prepare("SELECT * FROM game_state");
        $gameStateReq->execute();
        $gameState = $gameStateReq->fetch();
    } catch (PDOException $e) {
        $errorMessage = "Database error: " . $e->getMessage();
    }

    if ($gameState) {
        $lastUpdate = new DateTime($gameState['last_update']);
        $currentTime = new DateTime();

        // Check if the game has to be updated
        if ($currentTime->diff($lastUpdate)->i >= 1) {
            // Update the date
            $currentMonth = $gameState['current_month'];
            $currentYear = $gameState['current_year'];
            $currentMonth++;
            if ($currentMonth > 12) {
                $currentMonth = 1;
                $currentYear++;
            }

            try {
                $updateDateReq = $bdd->prepare("UPDATE game_state SET current_month = ?, current_year = ?, last_update = ?");
                $updateDateReq->execute([$currentMonth, $currentYear, $currentTime->format('Y-m-d H:i:s')]);
            } catch (PDOException $e) {
                $errorMessage = "Database error: " . $e->getMessage();
            }
            //give dividends
            if(empty($errorMessage)){
                try {
                    $dividendReq = $bdd->prepare("SELECT j.id, j.argent, a.dividende FROM joueur j JOIN portefeuille p ON j.id = p.player_id JOIN actions a ON p.stock_id = a.id WHERE a.date_dividende = ?");
                    $dividendReq->execute([$currentMonth]);
                    $players = $dividendReq->fetchAll();
                } catch (PDOException $e) {
                    $errorMessage = "Database error: " . $e->getMessage();
                }
                foreach($players as $player){
                    $new_money = floatval($player['argent']) + floatval($player['dividende']);
                    try{
                        $updateMoney = $bdd->prepare("UPDATE joueur SET argent = ? WHERE id = ?");
                        $updateMoney->execute([$new_money, $player['id']]);
                    } catch (PDOException $e) {
                        $errorMessage = "Database error: " . $e->getMessage();
                    }
                }
            }

            // Update the price of stocks
            if(empty($errorMessage)){
                try {
                    $stocksReq = $bdd->prepare("SELECT id, prix FROM actions");
                    $stocksReq->execute();
                    $stocks = $stocksReq->fetchAll();
                } catch (PDOException $e) {
                    $errorMessage = "Database error: " . $e->getMessage();
                }
                foreach ($stocks as $stock) {
                    $lastMonth = $currentMonth == 1 ? 12 : $currentMonth - 1;
                    $lastYear = $currentMonth == 1 ? $currentYear - 1 : $currentYear;

                    // Check if we have the last price
                    try {
                        $lastPriceExistReq = $bdd->prepare("SELECT COUNT(*) AS count FROM cours_marche WHERE stock_id = ? AND game_month = ? AND game_year = ?");
                        $lastPriceExistReq->execute([$stock['id'], $lastMonth, $lastYear]);
                        $lastPriceExist = $lastPriceExistReq->fetch();
                    } catch (PDOException $e) {
                        $errorMessage = "Database error: " . $e->getMessage();
                    }

                    if ($lastPriceExist['count'] == 0) {
                        $percent = 0;
                    } else {
                        try {
                            $lastPriceReq = $bdd->prepare("SELECT valeur_action FROM cours_marche WHERE stock_id = ? AND game_month = ? AND game_year = ?");
                            $lastPriceReq->execute([$stock['id'], $lastMonth, $lastYear]);
                            $lastPrice = $lastPriceReq->fetch();
                        } catch (PDOException $e) {
                            $errorMessage = "Database error: " . $e->getMessage();
                        }

                        $percent = 0;
                        if ($lastPrice !== false) {
                            $percent = $lastPrice['valeur_action'] == 0 ? 0 : ((floatval($stock['prix']) - floatval($lastPrice['valeur_action'])) / floatval($lastPrice['valeur_action'])) * 100;
                        }
                        $percent += rand(-3, 3);
                        $percent = max(-10, min(10, $percent));
                        $newPrice = floatval($stock['prix']) + (floatval($stock['prix']) * ($percent / 100));
                    }
                    $newPrice = max(1, $newPrice);
                    try{
                        $newPriceReq = $bdd->prepare("UPDATE actions SET prix = ? WHERE id = ?");
                        $newPriceReq->execute([$newPrice, $stock['id']]);
                        $addCoursReq = $bdd->prepare("INSERT INTO cours_marche (stock_id, game_month, game_year, valeur_action) VALUES (?, ?, ?, ?)");
                        $addCoursReq->execute([$stock['id'], $currentMonth, $currentYear, $newPrice]);
                    } catch (PDOException $e) {
                        $errorMessage = "Database error: " . $e->getMessage();
                    }
                }
            }
        }
    }
}

// Check for and set error messages (if any)
if (!empty($errorMessage)) {
    $_SESSION['error'] = $errorMessage;
    header('Location: index.php');
    exit();
}
// Display any error or success messages from the session
if (isset($_SESSION['error'])) {
    echo "<p>" . str_replace("\n", "<br>", $_SESSION['error']) . "</p>";
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    echo "<p>" . str_replace("\n", "<br>", $_SESSION['success']) . "</p>";
    unset($_SESSION['success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
</head>
<body>
<h1>Welcome to your profile</h1>
</body>
</html>