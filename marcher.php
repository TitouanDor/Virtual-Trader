<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Marche</title>
</head>
<body>
<div>
    <a href="index.html">Se deconnecter</a>
    <a href="profil.php">Profil</a>
</div>
<?php
try {
    // Connect to the database
    $bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare and execute the SQL query
    $req = $bdd->prepare("SELECT nom, description, prix FROM actions;");
    if (!$req->execute()) {
        throw new Exception("Database error");
    }

} catch (PDOException $e) {
    echo "<p>Database connection error</p>";
    exit();
} catch (Exception $e) {
    echo "<p>Database error</p>";
    exit();
}

?>
<div>
    <table>
        <tr>
            <th>Nom</th>
            <th>Description</th>
            <th>prix</th>
        </tr>
        <?php
        while($data = $req->fetch())
        {
            if($data === false) {
                throw new Exception("Database error");
            }
            ?>
            <tr>
                <td><?php echo htmlspecialchars($data["nom"]); ?></td>
                <td><?php echo htmlspecialchars($data["description"]); ?></td>
                <td><?php echo htmlspecialchars($data["prix"]); ?></td>
            </tr>
            <?php

        }
        ?>
    </table>

</div>
</body>
</html>