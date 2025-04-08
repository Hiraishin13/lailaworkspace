<?php
// Définir la constante BASE_URL pour les chemins absolus
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/laila_workspace');
}

// Démarrer la session une seule fois
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=localhost;dbname=laila_workspace", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES 'utf8'");
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>