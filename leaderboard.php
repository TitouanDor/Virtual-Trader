php
<?php
session_start();

if (!isset($_SESSION['id'])) {
    header('location: index.php');
    exit();
}

try {
    $bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
} catch (PDOException $e) {
    session_start();
    $_SESSION['error'] = "Database connection error: \n" . $e->getMessage();
    header('location: index.php');
    exit();
}

try {
    $req = $bdd->prepare("SELECT j.username, j.argent + COALESCE(SUM(p.quantity * a.prix), 0) AS portfolio_value
                          FROM joueur j
                          LEFT JOIN portefeuille p ON j.id = p.player_id
                          LEFT JOIN actions a ON p.stock_id = a.id
                          GROUP BY j.id
                          ORDER BY portfolio_value DESC");
    $req->execute();
    $leaderboard = $req->fetchAll();
} catch (PDOException $e) {
    session_start();
    $_SESSION['error'] = "Database error: \n" . $e->getMessage();
    header('location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Leaderboard</title>
</head>
<body>

<a href="index.php">Back to index</a>

<h1>Leaderboard</h1>

<?php if ($leaderboard): ?>
    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>Portfolio Value</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($leaderboard as $player): ?>
                <tr>
                    <td><?php echo htmlspecialchars($player['username']); ?></td>
                    <td><?php echo htmlspecialchars($player['portfolio_value']); ?> €</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No players found.</p>
<?php endif; ?>

</body>
</html>