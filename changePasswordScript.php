php
<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("location: index.html");
    exit();
}

if (!isset($_POST['currentPassword']) || !isset($_POST['newPassword']) || !isset($_POST['confirmNewPassword'])) {
    $_SESSION['error'] = "Please fill in all fields.";
    header("location: changePassword.php");
    exit();
}

$currentPassword = $_POST['currentPassword'];
$newPassword = $_POST['newPassword'];
$confirmNewPassword = $_POST['confirmNewPassword'];
$userId = $_SESSION['id'];

try {
    $bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $_SESSION['error'] = "Database connection error";
    header("location: changePassword.php");
    exit();
}

try {
    $req = $bdd->prepare("SELECT mdp FROM joueur WHERE id = ?");
    $req->execute([$userId]);
    $data = $req->fetch();

    if ($data === false) {
        $_SESSION['error'] = "Database error";
        header("location: changePassword.php");
        exit();
    }
    $hashedPassword = $data['mdp'];

    if (!password_verify($currentPassword, $hashedPassword)) {
        $_SESSION['error'] = "Incorrect current password.";
        header("location: changePassword.php");
        exit();
    }

    if ($newPassword !== $confirmNewPassword) {
        $_SESSION['error'] = "New passwords do not match.";
        header("location: changePassword.php");
        exit();
    }

    $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $updateReq = $bdd->prepare("UPDATE joueur SET mdp = ? WHERE id = ?");
    $updateReq->execute([$hashedNewPassword, $userId]);

    $_SESSION['success'] = "Password changed successfully!";
    header("location: profil.php");
    exit();
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error";
    header("location: changePassword.php");
    exit();
}
?>