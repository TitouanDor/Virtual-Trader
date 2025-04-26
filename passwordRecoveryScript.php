<?php
session_start();

if (isset($_POST['user_indentifier'])) {
    $user = $_POST['user_indentifier'];

    $dbHost = 'localhost';
    $dbName = 'virtual_trader';
    $dbUser = 'root';
    $dbPass = '';

    try {
        $bdd = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
        $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }

    $req = $bdd->prepare("SELECT id FROM joueur WHERE username = ? OR email = ?");
    $req->execute([$user, $user]);
    $result = $req->fetch();

    if ($result) {
        $userId = $result['id'];
        $token = bin2hex

