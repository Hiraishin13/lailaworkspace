<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/config.php';
require_once '../../controllers/UserController.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/views/auth/login.php');
    exit();
}

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $username = trim($_POST['username'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $experience = trim($_POST['experience'] ?? '');

    // Valider les données (exemple simple)
    if (empty($username) || empty($first_name) || empty($last_name) || empty($email) || empty($phone)) {
        $_SESSION['error'] = "Les champs obligatoires doivent être remplis.";
        header('Location: ' . BASE_URL . '/views/user/settings.php');
        exit();
    }

    // Vérifier si l'email ou le nom d'utilisateur est déjà utilisé par un autre utilisateur
    $stmt = $pdo->prepare("SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ?");
    $stmt->execute([$email, $username, $user_id]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = "Cet email ou nom d'utilisateur est déjà utilisé.";
        header('Location: ' . BASE_URL . '/views/user/settings.php');
        exit();
    }

    // Mettre à jour l'utilisateur dans la base de données
    try {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, first_name = ?, last_name = ?, email = ?, phone = ?, experience = ? WHERE id = ?");
        $stmt->execute([$username, $first_name, $last_name, $email, $phone, $experience, $user_id]);
        $_SESSION['success'] = "Profil mis à jour avec succès.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la mise à jour du profil : " . $e->getMessage();
    }

    // Rediriger vers la page des paramètres
    header('Location: ' . BASE_URL . '/views/user/settings.php');
    exit();
}