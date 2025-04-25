php
<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: index.html");
    exit();
}

// Get the followed_user_id from the POST request
if (!isset($_POST['followed_user_id'])) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: profil.php");
    exit();
}

$followedUserId = htmlspecialchars($_POST['followed_user_id']);
$userId = $_SESSION['id'];

// Check if the user is trying to follow himself
if ($followedUserId == $userId) {
    $_SESSION['error'] = "You cannot follow yourself.";
    header("Location: profil.php");
    exit();
}

// Database connection
try {
    $bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $_SESSION['error'] = "Database connection error.";
    header("Location: profil.php");
    exit();
}

// Check if the user is already followed
try {
    $req = $bdd->prepare("SELECT * FROM followers WHERE user_id = ? AND followed_user_id = ?");
    $req->execute([$userId, $followedUserId]);
    $follow = $req->fetch();
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error.";
    header("Location: profil.php");
    exit();
}

// Follow or unfollow the user
if ($follow) {
    // Unfollow
    try {
        $req = $bdd->prepare("DELETE FROM followers WHERE user_id = ? AND followed_user_id = ?");
        $req->execute([$userId, $followedUserId]);
        $_SESSION['success'] = "You have unfollowed this user.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error.";
        header("Location: profil.php");
        exit();
    }
} else {
    // Follow
    try {
        $req = $bdd->prepare("INSERT INTO followers (user_id, followed_user_id) VALUES (?, ?)");
        $req->execute([$userId, $followedUserId]);
        $_SESSION['success'] = "You are now following this user.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error.";
        header("Location: profil.php");
        exit();
    }
}

header("Location: profil.php");
?>