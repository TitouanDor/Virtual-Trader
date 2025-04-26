<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Détails de l'action</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px ;
            background-color: #f4f4f4;
        }
        .stock-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .history-list {
            list-style: none;
            padding: 0;
        }
        .history-list li {
            margin-bottom: 5px;
        }
        .buy-sell-form label {
            display: block;
            margin-bottom: 5px;
        }
        .buy-sell-form input[type="number"] {
            width: 100px;
            margin-bottom: 10px;
        }
        .buy-sell-form button {
            margin-right: 10px;
        }
    </style>
</head>
<body >

<?php

session_start();
if(!isset($_SESSION['id'])){
    header("location: index.php ");
}
// Database connection
$bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
$bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>
<a href="index.php">Se deconnecter</a>
<a href="marcher.php">marche</a>
<?php



$stockId = $_GET['id'];

// Get stock information
$req = $bdd->prepare("SELECT nom, description, prix FROM actions WHERE id = ?");
$req->execute([$stockId]);
$stock = $req->fetch();
// Get price history
$historyReq = $bdd->prepare("SELECT real_date, price FROM historique WHERE stock_id = ? ORDER BY real_date DESC");
$historyReq->execute([$stockId]);
$history = $historyReq->fetchAll();

$player_id = $_SESSION['id'];
// Get player information
$playerReq = $bdd->prepare("SELECT argent FROM joueur WHERE id = ?");
$playerReq->execute([htmlspecialchars($player_id)]);
$player = $playerReq->fetch();

?>

<div class="stock-container">
        <h1><?php echo htmlspecialchars($stock['nom']); ?></h1>
        <p><strong>Description:</strong> <?php echo htmlspecialchars($stock['description']); ?></p>
        <p><strong>Prix:</strong> <?php echo htmlspecialchars($stock['prix']); ?> €</p>

        <h2>Historique des prix</h2>
        <?php if ($history): ?>
        <ul class="history-list">
            <?php foreach ($history as $record): ?>
            <li><?php echo htmlspecialchars($record['real_date']); ?>: <?php echo htmlspecialchars($record['price']); ?> €</li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
            <p>Aucun historique disponible pour cette action.</p>
        <?php endif; ?>
    </div>

    <div class="stock-container">
        <h2>Acheter / Vendre</h2>
        <p>Votre argent: <?php echo htmlspecialchars($player['argent']); ?> €</p>
        <form class="buy-sell-form" action="buySellScript.php" method="post">
            <input type="hidden" name="stock_id" value="<?php echo $stockId; ?>">
            <label for="quantity">Quantité:</label>
            <input type="number" id="quantity" name="quantity" value="0" min="0" required>
            <br />
            <button type="submit" name="action" value="buy">Acheter</button>
            <button type="submit" name="action" value="sell">Vendre</button>
        </form>
    </div>
<div>
</div>

</body>
    <?php if (!isset($_GET['id'])): header('Location: marcher.php'); endif; ?>


</html>