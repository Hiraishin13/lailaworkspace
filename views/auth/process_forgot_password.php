<?php
session_start();
require_once '../../vendor/autoload.php';
require_once '../../includes/db_connect.php';

// Vérifier le jeton CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Erreur de sécurité. Veuillez réessayer.";
    header('Location: reset_password.php?token=' . $_POST['token']);
    exit();
}

// Vérifier si le jeton est fourni
if (!isset($_POST['token']) || empty($_POST['token'])) {
    $_SESSION['error'] = "Jeton de réinitialisation manquant.";
    header('Location: login.php');
    exit();
}

$token = $_POST['token'];

// Vérifier la validité du jeton
try {
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = :token AND used = 0 AND expires_at > NOW()");
    $stmt->execute(['token' => $token]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reset) {
        $_SESSION['error'] = "Jeton invalide ou expiré.";
        header('Location: login.php');
        exit();
    }

    $email = $reset['email'];
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la vérification du jeton : " . $e->getMessage();
    header('Location: login.php');
    exit();
}

// Vérifier les champs du mot de passe
if (!isset($_POST['password']) || !isset($_POST['confirm_password'])) {
    $_SESSION['error'] = "Veuillez remplir tous les champs.";
    header('Location: reset_password.php?token=' . $token);
    exit();
}

$password = trim($_POST['password']);
$confirm_password = trim($_POST['confirm_password']);

// Vérifier si les mots de passe correspondent
if ($password !== $confirm_password) {
    $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
    header('Location: reset_password.php?token=' . $token);
    exit();
}

// Vérifier la longueur du mot de passe (par exemple, minimum 8 caractères)
if (strlen($password) < 8) {
    $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères.";
    header('Location: reset_password.php?token=' . $token);
    exit();
}

// Hacher le nouveau mot de passe
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Mettre à jour le mot de passe de l'utilisateur
try {
    $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE email = :email");
    $stmt->execute([
        'password' => $hashed_password,
        'email' => $email,
    ]);

    // Marquer le jeton comme utilisé
    $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = :token");
    $stmt->execute(['token' => $token]);

    $_SESSION['success'] = "Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.";
    header('Location: login.php');
    exit();
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la réinitialisation du mot de passe : " . $e->getMessage();
    header('Location: reset_password.php?token=' . $token);
    exit();
}