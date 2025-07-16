<?php
// Configuration pour Hostinger
// REMPLACEZ CES VALEURS PAR CELLES DE VOTRE HÉBERGEMENT HOSTINGER

// URL de votre site sur Hostinger
if (!defined('BASE_URL')) {
    define('BASE_URL', 'https://votre-domaine.com'); // REMPLACEZ par votre domaine
}

// Configuration SMTP Hostinger
define('SMTP_HOST', 'smtp.hostinger.com'); // SMTP Hostinger
define('SMTP_USERNAME', 'votre-email@votre-domaine.com'); // Votre email Hostinger
define('SMTP_PASSWORD', 'votre-mot-de-passe-smtp'); // Mot de passe SMTP Hostinger
define('SMTP_PORT', 587);

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connexion à la base de données Hostinger
// REMPLACEZ par vos informations de base de données Hostinger
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=VOTRE_NOM_DB", // REMPLACEZ par votre nom de DB
        "VOTRE_USERNAME_DB", // REMPLACEZ par votre username DB
        "VOTRE_PASSWORD_DB"  // REMPLACEZ par votre password DB
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES 'utf8'");
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?> 