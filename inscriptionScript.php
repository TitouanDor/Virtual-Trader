<?php
session_start();

// Database connection
try {
    $bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
} catch (PDOException $e) {
    $_SESSION['error'] = "Database connection error";
    header('Location: inscription.php');
    exit();
}

// Sanitize and filter inputs
$nom = htmlspecialchars(filter_var($_POST['nom'], FILTER_SANITIZE_STRING));
$prenom = htmlspecialchars(filter_var($_POST['prenom'], FILTER_SANITIZE_STRING));
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$username = htmlspecialchars(filter_var($_POST['username'], FILTER_SANITIZE_STRING));
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

// Check if the email is valid
if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
    $_SESSION['error'] = "Invalid email";
    header('Location: inscription.php');
    exit();
}

// Check if the email already exists
try {
    $req = $bdd->prepare("SELECT email FROM joueur WHERE email = ?");
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error";
    header('Location: inscription.php');
    exit();
}
    $req->execute([$email]);
    $data = $req->fetch();
} catch (PDOException $e) {
    
    $_SESSION['error'] = "Error while verifying the email";
    header('Location: inscription.php');
    exit();
}

if ($data != null) {
    $_SESSION['error'] = "This email is already used";
    header('Location: inscription.php');
    exit();
}

// Insert the new user
try {
    $req = $bdd->prepare("INSERT INTO joueur(email, mdp, nom, prenom, username, argent) VALUES (?,?,?,?,?,?)");
    $req->execute([$email, $password, $nom, $prenom, $username, 10000.00]);
    // Check if a game already exists
    try {
        $req = $bdd->prepare("SELECT * FROM game_state");
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error";
        header('Location: inscription.php');
        exit();
    }
    $req->execute();
    $game = $req->fetch();

    if (!$game) {
        // If no game exists, create a new game
        // Initialize game state
        try{
            $req = $bdd->prepare("INSERT INTO game_state (current_month, current_year, last_update) VALUES (1, 1, NOW())");
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error";
            header('Location: inscription.php');
            exit();
        }
        $req->execute();

        // Get the new game id
        $gameId = $bdd->lastInsertId();

        // Get all stock IDs
        try{
            $req = $bdd->prepare("SELECT id FROM actions");
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error";
            header('Location: inscription.php');
            exit();
        }
        $req->execute();
        $stocks = $req->fetchAll();

        // Create market data for the next 12 months for each stock
        $game_month = 1;
        $game_year = 1;
        for ($i = 0; $i < 12; $i++) {    
            foreach ($stocks as $stock) {
                // Get the value of the stock
                try{
                    $req = $bdd->prepare("SELECT prix FROM actions WHERE id = ?");
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Database error";
                    header('Location: inscription.php');
                    exit();
                }
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
    header('Location: index.php');//
    exit();
} catch (PDOException $e) {
    $_SESSION['error'] = "Error during the creation of the user";
    header('Location: inscription.php');
    exit();
}



?>