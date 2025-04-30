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

//Recup portefeuille Joueur
$PortefeuilleReq = $bdd->prepare("SELECT * FROM portefeuille WHERE action_id = ?");
$PortefeuilleReq->execute([$action_id]);
$Portefeuille = $PortefeuilleReq->fetch();


    if ($action == 'Acheter') {

        $nouv_arg_joueur = $arg_joueur['argent'] - $prix_total;

        //Verif si joueur assez argent pour acheter actions
        if($nouv_arg_joueur < 1000.00) {
            echo "Vous n'avez pas assez d'argent";
            exit();
        }

        //Regarder si le portefeuille contient déjà l'action ou non et update en conséquence
        if($Portefeuille) {
            $nouvQtitPortef = $Portefeuille['quantite'] + $quantite;
            $PortefeuilleMAJ = $bdd->prepare("UPDATE portefeuille SET quantite = ? WHERE action_id = ?");
            $PortefeuilleMAJ->execute([$nouvQtitPortef, $action_id]);
        }
        else {
            $PortefeuilleReq = $bdd->prepare("INSERT INTO portefeuille (joueur_id, action_id, quantite, prix_achat) VALUES (?, ?, ?, ?)");
            $PortefeuilleReq->execute([$_SESSION['id'], $action_id, $quantite, $prix_total]);
        }

    }
    if ($action == 'Vendre') {
        $nouv_arg_joueur = $arg_joueur['argent'] + $prix_total;

        //Regarder si le portefeuille contient déjà l'action ou non et update en conséquence
        if($Portefeuille) {
            $nouvQtitPortef = $Portefeuille['quantite'] - $quantite;
            if($nouvQtitPortef <= 0) {
                $PortefeuilleMAJ = $bdd->prepare("DELETE FROM portefeuille WHERE action_id = ?");
                $PortefeuilleMAJ->execute([$action_id]);
            }
            else {
                $PortefeuilleMAJ = $bdd->prepare("UPDATE portefeuille SET quantite = ? WHERE action_id = ?");
                $PortefeuilleMAJ->execute([$nouvQtitPortef, $action_id]);
            }
        }
        else {
            echo "Vous n'avez pas assez d'action de cette entreprise";
            exit();
        }

    }

    //MAJ de l'argent du joueur
    $MAJ_Joueur = $bdd->prepare("UPDATE joueur SET argent = ? WHERE id = ?");
    $MAJ_Joueur->execute([$nouv_arg_joueur, $_SESSION['id']]);

    //MAJ de l'historique du jeu
    $HistoriqueReq = $bdd->prepare("INSERT INTO historique (action_id, joueur_id, prix, nature, quantite, game_month, game_year) VALUES (?, ?, ?, ?, ?, ?, ?) ");
    $HistoriqueReq->execute([$action_id, $_SESSION['id'], $prix_total, $action, $quantite, $currentMonth, $currentYear]);




header('Location: Marche.php');
exit();
//-------------------------------------- A FAIRE --------------------------------------//
    // IL FAUT CODER L'ACHAT ET LA VENTE D'ACTIONS, METTRE A JOUR LE PORTEFEUILLE, RENTRER LA TRANSACTION DANS L'HISTORIQUE,
