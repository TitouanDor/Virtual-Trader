<?php
session_start();

// Connection BDD
if (!isset($_SESSION['id'])) {
    header('location: index.html');
    exit();
}

// Connection BDD

try {
    $bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}


if (($_SERVER["REQUEST_METHOD"] == "POST") && !(empty($_POST['searchAction']))) { //cherche l'action chercher
    $actionsReq = $bdd->prepare("SELECT id, nom, description, prix FROM actions WHERE nom = ?");
    $actionsReq->execute([$_POST["searchAction"]]);
    $actions = $actionsReq->fetchAll();
} else{ // Recup toutes les actions
    $actionsReq = $bdd->prepare("SELECT id, nom, description, prix FROM actions");
    $actionsReq->execute();
    $actions = $actionsReq->fetchAll();
}

// Recup prix historiques des actions
foreach ($actions as &$action) {
    $actionId = $action['id'];
    $historicalPricesReq = $bdd->prepare("SELECT game_month, game_year, valeur_action FROM cours_marche WHERE action_id = ? ORDER BY game_year DESC, game_month DESC LIMIT 12");
    $historicalPricesReq->execute([$actionId]);
    $historicalPrices = $historicalPricesReq->fetchAll();
    $action['historical_prices'] = $historicalPrices;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSSFile/general.css">
    <link rel="stylesheet" href="CSSFile/marche.css">
    <title>Marché</title>
</head>
<body>
    <h1>Marché</h1>
    <ul>
        <?php foreach ($actions as $action): ?>
            <li>
                <form action="buySellScript.php" method="POST" name="form_<?php echo $action['id']?>">
                    <h2><?php echo htmlspecialchars($action['nom']); ?></h2>
                    <p><?php echo htmlspecialchars($action['description']); ?></p>
                    <p>Prix actuel: <?php echo htmlspecialchars($action['prix']); ?></p>
                    <h3>Prix Historiques</h3>
                    <ul><?php foreach ($action['historical_prices'] as $historicalPrice): ?>
                        <li><?php echo htmlspecialchars($historicalPrice['game_month']."/".$historicalPrice['game_year'].": ".$historicalPrice['valeur_action']); ?></li><?php endforeach; ?>
                    </ul>                
                    <input type="hidden" name="action_id" value="<?php echo $action['id']; ?>">
                    <label for="quantity">Quantity:</label>
                    <input type="number" id="quantity" name="quantity" value="1" min="1">

                    <button type="submit" name="action" value="Acheter">Acheter</button>
                    <button type="submit" name="action" value="Vendre">Vendre</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="banniere">
        <a href="profil.php">Profil</a>
        <a href="classement.php?from=profil">Classement</a>
        <a href="logout.php">Déconnexion</a>
    </div>
</body>
</html>
