<?php
// Définir la constante BASE_URL pour les chemins absolus
if (!defined('BASE_URL')) {
    define('BASE_URL', 'https://lailaworkspace.com');
}

// Configuration SMTP pour PHPMailer
define('SMTP_HOST', 'smtp.gmail.com'); // Hôte SMTP (Gmail, ou utilisez le SMTP de Hostinger)
define('SMTP_USERNAME', 'support@lailaworkspace.com'); // Votre adresse email
define('SMTP_PASSWORD', 'votre-mot-de-passe-d-application'); // Remplacer par votre mot de passe d'application
define('SMTP_PORT', 587); // Port SMTP (587 pour TLS, 465 pour SSL)

// Démarrer la session une seule fois
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connexion à la base de données Hostinger
try {
    $pdo = new PDO("mysql:host=localhost;dbname=u343759769_laila_db", "u343759769_Lailaworkspace", "Mauvaisgarcon04.com");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES 'utf8'");
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>