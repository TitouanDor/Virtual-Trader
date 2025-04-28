<?php
session_start();

// Verif si joueur connecte
if (!isset($_SESSION['id'])) {
    header('location: index.html');
    exit();
}

// Connection BDD
$bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
$bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Recup data pour classement
$leaderboardReq = $bdd->prepare("SELECT username, argent FROM joueur ORDER BY argent DESC");
$leaderboardReq->execute();
$leaderboard = $leaderboardReq->fetchAll();

// Determiner où le bouton retour doit mener
$from = isset($_GET['from']) ? $_GET['from'] : '';
$returnLink = 'profil.php'; // Default return link

if ($from === 'index') {
    $returnLink = 'index.html';
} elseif ($from === 'profil') {
    $returnLink = 'profil.php';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard</title>
</head>
<body>

<h1>Classement</h1>

<a href="<?php echo $returnLink; ?>">Retour</a>

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
                        <td><?php echo htmlspecialchars($player['argent']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
    </table>
<?php else: ?>
    <p>Pas d'utilisateurs trouvé.</p>
<?php endif; ?>

</body>
</html>
