<?php
session_start(); 

if (!isset($_SESSION['id'])) {
    header('location: index.php');
    exit();
}
    $bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
    $req = $bdd->prepare("SELECT j.username, j.argent + COALESCE(SUM(p.quantite * a.prix), 0) AS valeur_portefeuille
                          FROM joueur j
                          LEFT JOIN portefeuille p ON j.id = p.player_id
                          LEFT JOIN actions a ON p.stock_id = a.id
                          GROUP BY j.id
                          ORDER BY valeur_portefeuille DESC");
    $req->execute();
$leaderboard = $req->fetchAll();


?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Leaderboard</title>
    <a href="index.php">Retour à l'index</a>
</head>
<body>

<h1>Classement</h1>


<?php if ($leaderboard): ?>
    <table>
        <thead>
            <tr>
                <th>Nom d'utilisateur</th>
                <th>Valeur du portefeuille</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($leaderboard as $player): ?>
                <tr>
                    <td><?php echo htmlspecialchars($player['username']); ?></td>
                    <td><?php echo htmlspecialchars($player['valeur_portefeuille']); ?> €</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No players found.</p>
<?php endif; ?>

</body>
</html>
