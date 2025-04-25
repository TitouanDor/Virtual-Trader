<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Marche</title>
</head>
<body>
<?php
// Connect to the database and execute the request
$bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
$req = $bdd->prepare("SELECT nom, description, prix FROM actions;");
$req->execute();

?>
<div>
    <table>
        <tr>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Date de naissance</th>
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