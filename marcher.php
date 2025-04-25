<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Marche</title>
</head>
<body>
<?php
// Connect to the database and execute the request
$dbHost = "localhost";
$dbName = "virtual_trader";
$dbUser = "your_db_user"; // Replace with your database user
$dbPassword = "your_db_password"; // Replace with your database password

$bdd = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPassword);
$req = $bdd->prepare("SELECT nom, description, prix FROM actions;");
$req->execute();

?>
<div>
    <table>
        <tr>
            <th>Nom</th>
            <th>description</th>
            <th>prix</th>
        </tr>
        <?php
        while($data = $req->fetch())
        {
            ?>
            <tr>
                <td><?php echo $data["nom"]; ?></td>
                <td><?php echo $data["description"]; ?></td>
                <td><?php echo $data["prix"]; ?></td>
            </tr>
            <?php

        }
        ?>
    </table>

</div>
</body>
</html>