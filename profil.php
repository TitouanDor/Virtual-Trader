<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header('location: index.php');
    exit();
}

// Display any error or success messages
if (isset($_SESSION['error'])) {
    echo "<p>" . str_replace("\\n", "<br>", $_SESSION['error']) . "</p>";
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    echo "<p>" . str_replace("\\n", "<br>", $_SESSION['success']) . "</p>";
    unset($_SESSION['success']);
}

    $user = null;
    try {
        $bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
        try{
            $userId = $_SESSION['id'];
            $req = $bdd->prepare("SELECT nom, prenom, email, argent FROM joueur WHERE id = ?");
        }catch(PDOException $e) {
            echo "<p>Database error</p>";
            exit();
        }
        if(!$req->execute([$userId])){
           echo "<p>Database error</p>";
        }
        $user = $req->fetch();
        if ($user === false) {
           echo "<p>Database error</p>";
        }
        try{
           $req = $bdd->prepare("SELECT a.nom, p.quantity FROM portefeuille p JOIN actions a ON p.stock_id = a.id WHERE p.player_id = ?");
        }catch(PDOException $e) {
            echo "<p>Database error</p>";
            exit();
        }
        if(!$req->execute([$userId])){
           echo "<p>Database error</p>";
        }
        $portfolio = $req->fetchAll();
        if ($portfolio === false){
           echo "<p>Database error</p>";
        }
        
         // Get followed players
        $req = $bdd->prepare("SELECT j.username, j.id FROM followers f JOIN joueur j ON f.followed_user_id = j.id WHERE f.user_id = ?");
        $req->execute([$_SESSION['id']]);
        $followedPlayers = $req->fetchAll();

        //check if a player is search
        if (isset($_SESSION['searchResult'])) {
            $userToFollow = $_SESSION['searchResult'];
            unset($_SESSION['searchResult']);
        }
    }catch (PDOException $e) {
        echo "<p>Database connection error</p>";
        exit();
    }
    
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon profil</title>
    <style>
        body {
            font-family: sans-serif;
        }
        .profile-info {
            border: 1px solid #ccc;
            padding: 20px;
            margin: 20px;
        }
        .portfolio {
            border: 1px solid #ccc;
            padding: 20px;
            margin: 20px;
        }
    </style>
</head>

<body>
    <div>
        <a href="changePassword.php">Change password</a>
    </div>
    <div>
        <a href="index.php">Se deconnecter</a>
    </div>
    <div>
        <form action="searchPlayerScript.php" method="post">
            <input type="text" name="username" placeholder="username">
            <button type="submit" value="search">search</button>
        </form>
    </div>

    <?php if (isset($userToFollow)): ?>
        <p><?php echo htmlspecialchars($userToFollow['username']); ?></p>
        <form action='followScript.php' method='post'>
            <input type='hidden' name='followed_user_id' value='<?php echo $userToFollow['id']; ?>'>
            <button type='submit'><?php echo (isset($isFollowing) && $isFollowing ? "Unfollow" : "Follow"); ?></button>
        </form>
    <?php endif; ?>

    <div id="followed-players">
        <?php if ($followedPlayers): ?>
            <p>Followed players:</p>
            <?php foreach ($followedPlayers as $player): ?>
                <p><a href='playerProfil.php?id=<?php echo htmlspecialchars($player['id']); ?>'><?php echo htmlspecialchars($player['username']); ?></a></p>
            <?php endforeach; ?>
        <?php else: ?>
            <p>You don't follow anyone</p>
        <?php endif; ?>
    </div>

    <?php if (isset($user)): ?>
        <a href='marcher.php'>marche</a>
        <div class="profile-info">
            <h2>Your Profile</h2>
            <p>Name: <?php echo htmlspecialchars($user['prenom']) . ' ' . htmlspecialchars($user['nom']); ?></p>
            <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
            <p>Cash: <?php echo htmlspecialchars($user['argent']); ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($portfolio)): ?>
        <div class="portfolio">
            <h2>Your portfolio</h2>
            <ul>
                <?php foreach ($portfolio as $stock): ?>
                    <li><?php echo $stock['nom']; ?> - Quantity: <?php echo $stock['quantity']; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else: ?>
        <p>You don't have any stocks in your portfolio.</p>
    <?php endif; ?>

</body>
</html>