<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Marché</title>
</head>
<body>
<div>
    <a href="index.html">Se déconnecter</a>
    <a href="profil.php">Profil</a>
</div>
<?php
// Connexion à la base de données
$bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');

// Préparation et exécution de la requête SQL
$req = $bdd->prepare("SELECT nom, description, prix FROM actions;");
$req->execute();

?>
<div>
    <table>
        <tr>
            <th>Nom </th>
            <th>Description</th>
            <th>prix</th>
        </tr>
        <?php
        while($data = $req->fetch())
        {
            if($data === false) {
               
            }
            ?>
            <tr>
                <td><?php echo htmlspecialchars($data["nom"]); ?> </td>
                <td><?php echo htmlspecialchars($data["description"]); ?> </td>
                <td><?php echo htmlspecialchars($data["prix"]); ?> </td>
            </tr>
            <?php
        }
        ?>
    </table>
</div>
</body>
</html>