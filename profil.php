<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['id'])) {
    header('location: index.php');
    exit();
}

// Display any error or success messages from the session
if (isset($_SESSION['error'])) {
    echo "<p>" . str_replace("\n", "<br>", $_SESSION['error']) . "</p>";
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    echo "<p>" . str_replace("\n", "<br>", $_SESSION['success']) . "</p>";
    unset($_SESSION['success']);
}

$user = null;
try {
    // Establish a PDO database connection
    $bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');

    // Get the user ID from the session
    $userId = $_SESSION['id'];

    // Fetch user data
    try {
        // Prepare the SQL query
        $req = $bdd->prepare("SELECT nom, prenom, email, argent FROM joueur WHERE id = ?");
        
        // Execute the query with error handling
        if (!$req->execute([$userId])) {
            throw new PDOException("Error executing user query: " . $req->errorInfo()[2]);
        }
        
        // Fetch the user data with error handling
        $user = $req->fetch();
        if ($user === false) {
            throw new PDOException("Error fetching user data");
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header('Location: index.php'); // Redirect to index.php to display error
        exit(); // Exit to prevent further code execution
    }

    // Fetch portfolio data
    try {
        $req = $bdd->prepare("SELECT a.nom, p.quantity FROM portefeuille p JOIN actions a ON p.stock_id = a.id WHERE p.player_id = ?");
        if (!$req->execute([$userId])) {
            throw new PDOException("Error executing portfolio query: " . $req->errorInfo()[2]);
        }
        $portfolio = $req->fetchAll();
        if ($portfolio === false) {
            throw new PDOException("Error fetching portfolio data");
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header('Location: index.php');
        exit();
    }

    // Get followed players
    try {
        $req = $bdd->prepare("SELECT j.username, j.id FROM followers f JOIN joueur j ON f.followed_user_id = j.id WHERE f.user_id = ?");
        if(!$req->execute([$_SESSION['id']])){
            throw new PDOException("Error executing followed player query: " . $req->errorInfo()[2]);
        }
        $followedPlayers = $req->fetchAll();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header('Location: index.php');
        exit();
    }

    // Check if a player search result is in the session
    if (isset($_SESSION['searchResult'])) {
        $userToFollow = $_SESSION['searchResult'];
        unset($_SESSION['searchResult']);
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Database connection error: " . $e->getMessage();
    header('Location: index.php'); // Redirect to index.php to display error
    exit(); // Exit to prevent further code execution
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
