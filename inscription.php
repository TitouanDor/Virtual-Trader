<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription</title>
    <link rel="stylesheet" href="CSSFile\inscription.css">
</head>
<body>
<div>
    <a href="index.html">
        <button>
            Retour
        </button>
    </a>
</div>
    <div name="box_inscription" class="box_inscription">
        <p>Inscription à Virtual-trader</p>
        <form action="inscriptionScript.php" method="post">
            <div class="group-from">
            <label>
                <input type="text" name="prenom" placeholder="Prénom">
            </label>
            </div>

            <div class="group-from">
            <label>
                <input type="text" name="nom" placeholder="Nom">
            </label>
            </div>

            <div class="group-from">
                <label>
                    <input type="text" name="username" placeholder="Nom d'utilisateur">
                </label>
            </div>

            <div class="group-from">
            <label>
                <input type="text" name="email" placeholder="E-mail">
            </label>
            </div>

            <div class="group-from">
            <label>
                <input type="password" name="password" placeholder="Mot de passe">
            </label>
            </div>

            <div class="group-from">
            <label>
                <input type="submit" value="S'inscrire">
            </label>
            </div>
        </form>
    </div>
</body>

