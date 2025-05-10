<?php
session_start();

// Supprimer toutes les variables de session
$_SESSION = array();

// Réinitialise la session
session_destroy();

// Rediriger vers index.html
header("location: index.html");
exit;
?>