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
}

    $action_id = $_POST['action_id'];
    $quantite = $_POST['quantity'];
    $action = $_POST['action'];


    $achat_venteReq = $bdd->prepare("SELECT * FROM actions WHERE id = ?");
    $achat_venteReq->execute([$action_id]);
    $achat_vente = $achat_venteReq->fetch();

    $arg_joueurReq = $bdd->prepare("SELECT argent FROM joueur WHERE id = ?");
    $arg_joueurReq->execute([$_SESSION['id']]);
    $arg_joueur = $arg_joueurReq->fetch();

    $prix_total = $achat_vente['prix'] * $quantite;

    // Recup mois et annee
    $gameStateReq = $bdd->query("SELECT current_month, current_year FROM game_state");
    $gameState = $gameStateReq->fetch();
    $currentMonth = $gameState['current_month'];
    $currentYear = $gameState['current_year'];

    $nouv_arg_joueur = $arg_joueur['argent'];


    if ($action == 'Acheter') {
        $nouv_arg_joueur = $arg_joueur['argent'] - $prix_total;

        $PortefeuilleReq = $bdd->prepare("INSERT INTO portefeuille (joueur_id, action_id, quantite, prix_achat) VALUES (?, ?, ?, ?)");
        $PortefeuilleReq->execute([$_SESSION['id'], $action_id, $quantite, $prix_total]);
    }
    if ($action == 'Vendre') {
        $nouv_arg_joueur = $arg_joueur['argent'] + $prix_total;
    }

    //MAJ de l'argent du joueur
    $MAJ_Joueur = $bdd->prepare("UPDATE joueur SET argent = ?");
    $MAJ_Joueur->execute([$nouv_arg_joueur]);

    //MAJ de l'historique du jeu
    $HistoriqueReq = $bdd->prepare("INSERT INTO historique (action_id, joueur_id, prix, nature, quantite, game_month, game_year) VALUES (?, ?, ?, ?, ?, ?, ?) ");
    $HistoriqueReq->execute([$action_id, $_SESSION['id'], $prix_total, $action, $quantite, $currentMonth, $currentYear]);




header('Location: Marche.php');
//-------------------------------------- A FAIRE --------------------------------------//
    // IL FAUT CODER L'ACHAT ET LA VENTE D'ACTIONS, METTRE A JOUR LE PORTEFEUILLE, RENTRER LA TRANSACTION DANS L'HISTORIQUE,
