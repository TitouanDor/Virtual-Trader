<?php
session_start();

// Verif si joueur connecte
if (!isset($_SESSION['id'])) {
    header('location: index.html');
    exit();
}

// Connection BDD
try {
    $bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header("Location: Marche.php");
    exit();




    //-------------------------------------- A FAIRE --------------------------------------//
    // IL FAUT CODER L'ACHAT ET LA VENTE D'ACTIONS, METTRE A JOUR LE PORTEFEUILLE DU JOUR, RENTRER LA TRANSACTION DANS L'HISTORIQUE,
}