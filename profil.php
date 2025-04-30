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


// Recherche de joueur
if (isset($_SESSION['search_result'])) {    
    $searchResult = $_SESSION['search_result'];
    $investedActionsOfPlayer = $bdd->prepare("SELECT actions.nom, portefeuille.quantite FROM portefeuille JOIN actions ON portefeuille.action_id = actions.id WHERE portefeuille.joueur_id = ?");// Récupération des actions investies
    $investedActionsOfPlayer->execute([$searchResult["id"]]);
    $investedActionsOfPlayer = $investedActionsOfPlayer->fetchAll();
    unset($_SESSION['search_result']);
}
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
    <link rel="stylesheet" href="CSSFile/general.css">
    <link rel="stylesheet" href="CSSFile/profil.css">
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
            <div class="recherche">
                <form action="marche.php" method="post" >
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
        <div class="recherche">
            <form action="chercherJoueurScript.php" method="post" >
                    <input type="text" name="search" placeholder="e-mail/username">
                    <input type="submit" value="Rechercher">
            </form>
        </div>


    <?php //if(isset($searchResult)):?>
    <!--    <p>Username: --><?php //echo $searchResult["username"] ?><!--</p>-->
    <!--    <p>Email: --><?php //echo $searchResult["email"] ?><!--</p>-->
    <!--    <p>Solde: --><?php //echo $searchResult["argent"] ?><!--</p>-->
    <!--    --><?php
    //    // Check if the user is already following the searched user
    //    $isFollowingReq = $bdd->prepare("SELECT * FROM followers WHERE user_id = ? AND followed_user_id = ?");
    //    $isFollowingReq->execute([$_SESSION['id'], $searchResult['id']]);
    //    $isFollowing = $isFollowingReq->fetch();
    //    ?>
    <!--    <form action="followScript.php" method="post">-->
    <!--        <input type="hidden" name="followed_user_id" value="--><?php //echo $searchResult["id"]; ?><!--">-->
    <!--        --><?php //if ($isFollowing): ?>
    <!--            <input type="submit" name="follow" value="Unfollow">-->
    <!--        --><?php //else: ?>
    <!--            <input type="submit" name="follow" value="Follow">-->
    <!--        --><?php //endif; ?>
    <!--    </form>-->
    <!--    --><?php
    //    // End of Check
    //    ?>
    <!--    <h2>Actions de ce joueur</h2>-->
    <!---->
    <!---->
    <?php //if ($investedActionsOfPlayer): ?>
    <!--    <ul>-->
    <!--        --><?php //foreach ($investedActionsOfPlayer as $action): ?>
    <!--                <li>--><?php //echo htmlspecialchars($action['nom']); ?><!-- (Quantité: --><?php //echo htmlspecialchars($action['quantite']); ?><!--)</li>-->
    <!--        --><?php //endforeach; ?>
    <!--    </ul>-->
    <?php //endif; ?>
    <?php //elseif (isset($error)): echo "<p>".$error."</p>"; endif;?>

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
    </div>

    <div class="banniere">
        <a href="changerMDP.php">Changer mot de passe</a>
        <br>
        <a href="Marche.php">Marché</a>
        <br>
        <a href="classement.php?from=profil">Classement</a>
        <br>
        <a href="logout.php">Déconnexion</a>
    </div>
</body>
</html>