<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/config.php';

// Vérifier le jeton CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Erreur de validation CSRF.";
    header('Location: ' . BASE_URL . '/views/auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation des données
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Veuillez remplir tous les champs.";
        header('Location: ' . BASE_URL . '/views/auth/login.php');
        exit();
    }

    // Validation du format de l'email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "L'adresse email n'est pas valide.";
        header('Location: ' . BASE_URL . '/views/auth/login.php');
        exit();
    }

    // Vérifier si l'utilisateur existe
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['success'] = "Connexion réussie !";
        header('Location: ' . BASE_URL . '/views/index.php');
        exit();
    } else {
        $_SESSION['error'] = "Email ou mot de passe incorrect.";
        header('Location: ' . BASE_URL . '/views/auth/login.php');
        exit();
    }
} else {
    $_SESSION['error'] = "Méthode non autorisée.";
    header('Location: ' . BASE_URL . '/views/auth/login.php');
    exit();
}