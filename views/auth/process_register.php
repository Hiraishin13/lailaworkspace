<?php
require_once '../../includes/db_connect.php';

// Vérifier le jeton CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Erreur de sécurité : jeton CSRF invalide.";
    header('Location: register.php');
    exit();
}

// Vérifier les champs
if (!isset($_POST['username']) || !isset($_POST['email']) || !isset($_POST['phone']) || !isset($_POST['password'])) {
    $_SESSION['error'] = "Veuillez remplir tous les champs.";
    header('Location: register.php');
    exit();
}

$username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

// Validation supplémentaire pour le numéro de téléphone
if (!preg_match('/^\+[0-9]{1,3}[0-9]{9,10}$/', $phone)) {
    $_SESSION['error'] = "Le numéro de téléphone doit être au format international (ex. +33612345678).";
    header('Location: register.php');
    exit();
}

try {
    // Vérifier si l'email existe déjà
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['error'] = "Cet email est déjà utilisé.";
        header('Location: register.php');
        exit();
    }

    // Vérifier si le numéro de téléphone existe déjà
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE phone = :phone");
    $stmt->execute(['phone' => $phone]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['error'] = "Ce numéro de téléphone est déjà utilisé.";
        header('Location: register.php');
        exit();
    }

    // Insérer l'utilisateur
    $stmt = $pdo->prepare("INSERT INTO users (username, email, phone, password) VALUES (:username, :email, :phone, :password)");
    $stmt->execute([
        'username' => $username,
        'email' => $email,
        'phone' => $phone,
        'password' => $password
    ]);

    // Connecter l'utilisateur automatiquement après l'inscription
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    // Rediriger vers generate_bmc.php au lieu de index.php
    header('Location: ../bmc/generate_bmc.php');
    exit();
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur : " . $e->getMessage();
    header('Location: register.php');
    exit();
}