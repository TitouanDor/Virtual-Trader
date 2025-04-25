<?php
session_start();
$password = PASSWORD_HASH($_POST['mdp'], PASSWORD_DEFAULT);
try {
    $bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de connexion à la base de données : " . $e->getMessage();
    header('Location: inscription.php');
    exit();
}
$nom = $_POST['nom'];
$prenom = $_POST['prenom'];
$email = $_POST['e-mail'];
$username = $_POST['username'];

$req = $bdd->prepare("SELECT email FROM joueur WHERE email = ?");
try {
    $req->execute([$email]);
    $data = $req->fetch();
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la vérification de l'email : " . $e->getMessage();
    header('Location: inscription.php');
    exit();
}

if ($data != null) {
    $_SESSION['valid'] = 1;
    $_SESSION['error'] = "Cet email est déjà utilisé";
    header('Location: inscription.php');
    exit();
}

try {
    $req = $bdd->prepare("INSERT INTO joueur(email, mdp, nom, prenom, username, argent) VALUES (?,?,?,?,?,?);");
    $req->execute([$email, $password, $nom, $prenom, $username, 10000.00]);
    // Check if a game already exists
    $req = $bdd->prepare("SELECT * FROM game_state");
    $req->execute();
    $game = $req->fetch();

    if (!$game) {
        // If no game exists, create a new game
        // Initialize game state
        $req = $bdd->prepare("INSERT INTO game_state (current_month, current_year) VALUES (1, 1)");
        $req->execute();

        // Get the new game id
        $gameId = $bdd->lastInsertId();

        // Get all stock IDs
        $req = $bdd->prepare("SELECT id FROM actions");
        $req->execute();
        $stocks = $req->fetchAll();

        // Create market data for the next 12 months for each stock
        $game_month = 1;
        $game_year = 1;
        for ($i = 0; $i < 12; $i++) {
            foreach ($stocks as $stock) {
                // Get the value of the stock
                $req = $bdd->prepare("SELECT prix FROM actions WHERE id = ?");
                $req->execute([$stock['id']]);
                $stock_prix = $req->fetch();
                // Insert market data for each stock
                $req = $bdd->prepare("INSERT INTO cours_marche (stock_id, game_month, game_year, valeur_action) VALUES (?,?,?,?)");
                $req->execute([$stock['id'], $game_month, $game_year, $stock_prix['prix']]);
            }
            $game_month++;
            if($game_month > 12){
                $game_month = 1;
                $game_year ++;
            }
        }
    }

    $_SESSION['success'] = "You are now registered!";
    header('Location: index.php');
    exit();
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de l'insertion du joueur : " . $e->getMessage();
    header('Location: inscription.php');
    exit();
}






exit();
?>