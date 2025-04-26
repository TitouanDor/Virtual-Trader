<?php
session_start();

// Connexion à la base de données
$dbHost = 'localhost'; // Hôte de la base de données
$dbName = 'virtual_trader'; // Nom de la base de données
$dbUser = 'root'; // Nom d'utilisateur de la base de données
$dbPass = ''; // Mot de passe de la base de données
$bdd = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass); // Nouvelle connexion PDO
$bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Vérifier si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") { // Si la requête est de type POST
    // Vérifier si tous les champs sont remplis
    if (!empty($_POST['email']) && !empty($_POST['mdp'])) { // Si les champs email et mot de passe sont remplis
        $email = $_POST['email']; // Récupérer l'email depuis le formulaire
        $mdp = $_POST['mdp']; // Récupérer le mot de passe depuis le formulaire (nom modifié en 'mdp')
        $req = $bdd->prepare("SELECT id, mdp FROM joueur WHERE email = ?"); // Préparer la requête SQL pour sélectionner l'id et le mot de passe de l'utilisateur
        $req->execute([$email]); // Exécuter la requête avec l'email fourni
        $user = $req->fetch(); // Récupérer l'utilisateur trouvé

        if ($user && password_verify($mdp, $user['mdp'])) { // Si un utilisateur est trouvé et que le mot de passe est correct
            // Le mot de passe est correct, démarrer une nouvelle session
            $_SESSION['id'] = $user['id']; // Stocker l'id de l'utilisateur dans la session

            // Rediriger vers profil.php
            header('Location: profil.php'); // Rediriger l'utilisateur vers profil.php
            exit(); // Arrêter l'exécution du script
        } else {
            header('Location: index.php');
            exit();
        }
    } else {
        header('Location: index.php');
        exit();
    }
} 
?>
?>