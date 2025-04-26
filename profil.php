<?php
session_start();
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
<div id="followed-players">
    <?php
    try {
        $bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
        $req = $bdd->prepare("SELECT j.username FROM followers f JOIN joueur j ON f.followed_user_id = j.id WHERE f.user_id = ?");
        $req->execute([$_SESSION['id']]);
        $followedPlayers = $req->fetchAll();
        if ($followedPlayers === false) {
            echo "<p>Database error</p>";
        }
    
<div id="followed-players">
    <?php
        }
    } catch(PDOException $e){
        echo "<p>Database connection error</p>";
        exit();
    }
    


    <?php
    if (isset($_SESSION['searchResult'])) {
        $user = $_SESSION['searchResult'];
        echo "<p>" . htmlspecialchars($user['username']) . "</p>";
        echo "<form action='followScript.php' method='post'>";
        echo "<input type='hidden' name='followed_user_id' value='" . $user['id'] . "'>";
        echo "<button type='submit'>" . (isset($isFollowing) && $isFollowing ? "Unfollow" : "Follow") . "</button>";
        echo "</form>";
        unset($_SESSION['searchResult']);
    }
    ?>
</div>
<div id="followed-players">
    <?php
    try {
        $bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
        $req = $bdd->prepare("SELECT j.username, j.id FROM followers f JOIN joueur j ON f.followed_user_id = j.id WHERE f.user_id = ?");
        $req->execute([$_SESSION['id']]);
        $followedPlayers = $req->fetchAll();
        if ($followedPlayers) {
            echo "<p>Followed players:</p>";
            foreach ($followedPlayers as $player) {
                echo "<p><a href='playerProfil.php?id=".htmlspecialchars($player['id'])."'>" . htmlspecialchars($player['username']) . "</a></p>";
            }
        } else {
            echo "<p>You don't follow anyone</p>";
        }
    } catch (PDOException $e) {
        echo "<p>Database connection error</p>";
        exit();
    }
    ?>
</div>


    <?php
    // Check if user is logged in
    if (!isset($_SESSION['id'])) {
        header('location: index.php');
        exit();

    }
    $user = null;
    try {
        // Database connection
        $bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
    } catch (PDOException $e) {
        echo "<p>Database connection error</p>";
        exit();
    }

    // Get user ID from session
    $userId = $_SESSION['id'];

    // Get user information
    try {
        $req = $bdd->prepare("SELECT nom, prenom, email, argent FROM joueur WHERE id = ?");
        $req->execute([$userId]);

        $user = $req->fetch(); // Fetch user data
        if ($user === false) {
            echo "<p>Database error</p>";// Handle fetch error
        }
    } catch (PDOException $e) {
        echo "<p>Database error</p>";
    }
    ?>

    <?php
    echo "<a href='marcher.php'>marche</a>";
    if (isset($user)) {
        ?>
        <div class="profile-info">
            <h2>Your Profile</h2>
            <p>Name: <?php echo htmlspecialchars($user['prenom']) . ' ' . htmlspecialchars($user['nom']); ?></p>
            <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
            <p>Cash: <?php echo htmlspecialchars($user['argent']); ?></p>
        </div>
        <?php
    }
    try {
        // Get user portfolio
        $req = $bdd->prepare("SELECT a.nom, p.quantity FROM portefeuille p JOIN actions a ON p.stock_id = a.id WHERE p.player_id = ?");
        $req->execute([$userId]);
        $portfolio = $req->fetchAll();
        if ($portfolio === false){
            echo "<p>Database error</p>";
        
        }
    } catch (PDOException $e) {
        echo "<p>Database error</p>";
    }
    if (!empty($portfolio)) {
            echo "<ul>";
            foreach ($portfolio as $stock) {
                echo "<li>" . $stock['nom'] . " - Quantity: " . $stock['quantity'] . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>You don't have any stocks in your portfolio.</p>";
        }
        ?>

</body>
</html>