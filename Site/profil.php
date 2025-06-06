<?php
include "actionDuTemps.php";
session_start();

// Verif si joueur connecte
if (!isset($_SESSION['id'])) {
    header('Location: index.html');
    exit();
}

// Connection BDD
$bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
$bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get the current game state and update if necessary
$gameStateReq = $bdd->prepare("SELECT * FROM game_state");
    $gameStateReq->execute();
    $gameState = $gameStateReq->fetch();

$userReq = $bdd->prepare("SELECT * FROM joueur WHERE id = ?"); // Récupération des informations de l'utilisateur
$userReq->execute([$_SESSION['id']]);
$user = $userReq->fetch();

$investedActionsReq = $bdd->prepare("SELECT actions.nom, portefeuille.quantite FROM portefeuille JOIN actions ON portefeuille.action_id = actions.id WHERE portefeuille.joueur_id = ?");// Récupération des actions investies
$investedActionsReq->execute([$_SESSION['id']]);
$investedActions = $investedActionsReq->fetchAll();

$followedPlayersReq = $bdd->prepare("SELECT j.id, j.username FROM joueur j JOIN followers f ON j.id = f.followed_user_id WHERE f.user_id = ?");
$followedPlayersReq->execute([$_SESSION['id']]);
$followedPlayers = $followedPlayersReq->fetchAll();


if(isset($_SESSION["error"])){
    $error = $_SESSION["error"];
    unset($_SESSION["error"]);
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon profil</title>
    <link rel="stylesheet" href="../CSSFile/general.css">
    <link rel="stylesheet" href="../CSSFile/profil.css">
</head>
<body>
    <div class="bandeau">Bienvenue sur votre profil</div>

    <div class="container">

    <div class="box_profil">
        <div class="titre">Informations</div>

    <?php if ($user): ?>
        <p>Nom: <?php echo htmlspecialchars($user['nom']); ?></p>
        <p>Prénom: <?php echo htmlspecialchars($user['prenom']); ?></p>
        <p>Nom d'utilisateur: <?php echo htmlspecialchars($user['username']); ?></p>
        <p>Solde: <?php echo htmlspecialchars($user['argent']); ?></p>
    <?php endif; ?>
    </div>

    <div class="box_action">
        <div class="titre">Vos Actions</div>
            <div class="boutonCentrer">
                <form action="Marche.php" method="post" >
                    <input type="text" name="searchAction" placeholder="nom entreprise">
                    <input type="submit" value="Rechercher">
                </form>
            </div>
    <?php if ($investedActions): ?>
    <ul>
        <?php foreach ($investedActions as $action): ?>
                <li><?php echo htmlspecialchars($action['nom']); ?> (Quantité: <?php echo htmlspecialchars($action['quantite']); ?>)</li>
        <?php endforeach; ?>
    </ul>
    <?php else: ?>
        <p>Vous n'avez pas encore investi dans des actions.</p>
    <?php endif; ?>
    </div>

    <div class="box_recherche_joueur">
    <div class="titre">Rechercher des joueurs</div>

        <div class="boutonCentrer">
            <form action="chercherJoueurScript.php" method="post" >
                    <input type="text" name="search" placeholder="e-mail/username">
                    <input type="submit" value="Rechercher">
            </form>
        </div>

    <?php if ($followedPlayers): ?>
        <h2>Joueurs que vous suivez</h2>
        <ul>
            <?php foreach ($followedPlayers as $followedPlayer): ?>
                <li><a href="profilJoueur.php?id=<?php echo $followedPlayer['id']; ?>"><?php echo htmlspecialchars($followedPlayer['username']); ?></a></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Vous ne suivez personne.</p>
    <?php endif; ?>
    </div>


    <div class="banniere">
        <a href="changerMDP.php">Changer mot de passe</a>
        <a href="Marche.php">Marché</a>
        <a href="classement.php?from=profil">Classement</a>
        <a href="logout.php">Déconnexion</a>
        <a href="reinitialisation.php">Réinitialisation</a>
    </div>
</body>
</html>