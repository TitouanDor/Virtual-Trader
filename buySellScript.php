<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['id'])) {
    header('location: index.html');
    exit();
}

// Database connection
$dbHost = 'localhost';
$dbName = 'virtual_trader';
$dbUser = 'root';
$dbPass = '';
try {
    $bdd = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header("Location: Marche.php");
    exit();




    //-------------------------------------- A FAIRE --------------------------------------//
    // IL FAUT CODER L'ACHAT ET LA VENTE D'ACTIONS, METTRE A JOUR LE PORTEFEUILLE DU JOUR, RENTRER LA TRANSACTION DANS L'HISTORIQUE,
}