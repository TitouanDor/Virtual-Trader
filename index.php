<?php
session_start();

// Database connection
try {
    $dbHost = 'localhost';
    $dbName = 'virtual_trader';
    $dbUser = 'root';
    $dbPass = '';
    $bdd = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $_SESSION['error'] = "Database connection error<br>" . $e->getMessage();
    header('location: index.html');
    exit();
}

// Check if the user is connected and lost
if(isset($_SESSION['id'])){
    try{
        $req = $bdd->prepare("SELECT j.id FROM joueur j WHERE j.id = ? AND j.id NOT IN (SELECT p.player_id FROM portefeuille p) AND j.argent < 1000");
    } catch(PDOException $e) {
        $_SESSION['error'] = "Database error";
        header('location: index.html');
        exit();
    }
    $req->execute([$_SESSION['id']]);
    if($req->fetch()){
        $_SESSION['error'] = "You have lost the game !";
    }}

// Get the current game state
try{
    $gameStateReq = $bdd->prepare("SELECT * FROM game_state");
}catch(PDOException $e) {
    $_SESSION['error'] = "Database error\n";
    header('location: index.html');
    exit();
}

if (!$gameStateReq->execute()) {
    $_SESSION['error'] = "Database error\n";
    header('location: index.html');
    exit();
}
$gameState = $gameStateReq->fetch();

$lastUpdate = new DateTime($gameState['last_update']);
$currentTime = new DateTime();

//check if the game has to be updated
if ($currentTime->diff($lastUpdate)->i >= 1) {
    // Update the date
    $currentMonth = $gameState['current_month'];
    $currentYear = $gameState['current_year'];
    $currentMonth++;
    if ($currentMonth > 12) {
        $currentMonth = 1;
        $currentYear++;
    }
    try{
        $updateDateReq = $bdd->prepare("UPDATE game_state SET current_month = ?, current_year = ?, last_update = ?");
    } catch(PDOException $e) {
        $_SESSION['error'] = "Database error";
        header('location: index.html');
        exit();
    }
    if (!$updateDateReq->execute([$currentMonth, $currentYear, $currentTime->format('Y-m-d H:i:s')])) {
        $_SESSION['error'] = "Database error\n";
        header('location: index.html');
        exit();
    }
    //give dividends
    try{
        $dividendReq = $bdd->prepare("SELECT j.id, j.argent, a.dividende FROM joueur j JOIN portefeuille p ON j.id = p.player_id JOIN actions a ON p.stock_id = a.id WHERE a.date_dividende = ?");
    } catch(PDOException $e) {
        $_SESSION['error'] = "Database error";
        header('location: index.html');
        exit();
    }
    if (!$dividendReq->execute([$currentMonth])) {
        $_SESSION['error'] = "Database error\n";
        header('location: index.html');
        exit();
    }
    $players = $dividendReq->fetchAll();
    foreach($players as $player){
        $new_money = floatval($player['argent']) + floatval($player['dividende']);
        try{
            $updateMoney = $bdd->prepare("UPDATE joueur SET argent = ? WHERE id = ?");
        } catch(PDOException $e) {
            $_SESSION['error'] = "Database error";
            header('location: index.html');
            exit();
        }
        if (!$updateMoney->execute([$new_money, $player['id']])) {
            $_SESSION['error'] = "Database error\n";
            header('location: index.html');
            exit();
        }
        }
    //update the price of stocks
    try{
        $stocksReq = $bdd->prepare("SELECT id, prix FROM actions");
    } catch(PDOException $e) {
        $_SESSION['error'] = "Database error";
        header('location: index.html');
        exit();
    }
    if (!$stocksReq->execute()) {
        $_SESSION['error'] = "Database error\n";
        header('location: index.html');
        exit();
    }
    $stocks = $stocksReq->fetchAll();
    foreach ($stocks as $stock) {
        $lastMonth = $currentMonth == 1 ? 12 : $currentMonth - 1;
        $lastYear = $currentMonth == 1 ? $currentYear - 1 : $currentYear;

        //check if we have the last price
        try{
            $lastPriceExistReq = $bdd->prepare("SELECT COUNT(*) AS count FROM cours_marche WHERE stock_id = ? AND game_month = ? AND game_year = ?");
        } catch(PDOException $e) {
            $_SESSION['error'] = "Database error";
            header('location: index.html');
            exit();
        }
        if (!$lastPriceExistReq->execute([$stock['id'], $lastMonth, $lastYear])) {
            $_SESSION['error'] = "Database error\n";
            header('location: index.html');
            exit();
        }
        $lastPriceExist = $lastPriceExistReq->fetch();
        if ($lastPriceExist['count'] == 0) {
            $percent = 0;
        } else {
            try{
                $lastPriceReq = $bdd->prepare("SELECT valeur_action FROM cours_marche WHERE stock_id = ? AND game_month = ? AND game_year = ?");
            } catch(PDOException $e) {
                $_SESSION['error'] = "Database error";
                header('location: index.html');
                exit();
            }
            if (!$lastPriceReq->execute([$stock['id'], $lastMonth, $lastYear])) {
                $_SESSION['error'] = "Database error\n";
                header('location: index.html');
                exit();
            }
            $lastPrice = $lastPriceReq->fetch();
            $percent = 0;
            if ($lastPrice === false){
                $percent = 0;
            } else {
                $percent = $lastPrice['valeur_action'] == 0 ? 0 : ((floatval($stock['prix']) - floatval($lastPrice['valeur_action'])) / floatval($lastPrice['valeur_action'])) * 100;}
            $percent += rand(-3, 3);
        if($percent > 10){
            $percent = 10;
        }
        if($percent < -10){
            $percent = -10;
            }
            $newPrice = floatval($stock['prix']) + (floatval($stock['prix']) * ($percent/100));
        }
        if($newPrice < 1){
            $newPrice = 1;
        }
        try{
            $newPriceReq = $bdd->prepare("UPDATE actions SET prix = ? WHERE id = ?");
        } catch(PDOException $e) {
            $_SESSION['error'] = "Database error";
            header('location: index.html');
            exit();
        }
        if (!$newPriceReq->execute([$newPrice, $stock['id']])) {
            $_SESSION['error'] = "Database error\n";
            header('location: index.html');
            exit();
        }
        try{
            $addCoursReq = $bdd->prepare("INSERT INTO cours_marche (stock_id, game_month, game_year, valeur_action) VALUES (?, ?, ?, ?)");
        } catch(PDOException $e) {
            $_SESSION['error'] = "Database error";
            header('location: index.html');
            exit();
        }
        if (!$addCoursReq->execute([$stock['id'], $currentMonth, $currentYear, $newPrice])) {
            $_SESSION['error'] = "Database error\n";
            header('location: index.html');
            exit();
        }
    }
}
?>
<a href="leaderboard.php">Leaderboard</a>
<br>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="CSSFile\index.css">
    <title>Virtual-trader</title>
</head>
<body>

<?php
if (isset($_SESSION['error'])) {
    echo "<p>";
    echo str_replace("\n", "<br>", $_SESSION['error']);
    echo "</p>";
    unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
    echo "<p>";
    echo str_replace("\n", "<br>", $_SESSION['success']);
    echo "</p>";
    unset($_SESSION['success']);
}
if(!isset($_SESSION['id'])): ?>

<?php endif; ?>

</body>
</html>


<?php