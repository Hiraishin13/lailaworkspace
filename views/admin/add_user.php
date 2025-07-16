<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/config.php';

// Vérifier les permissions admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/views/auth/login.php');
    exit();
}

$message = '';
$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    
    // Validation
    if (empty($email) || empty($password)) {
        $error = 'Tous les champs sont obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } elseif ($password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } else {
        try {
            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Cette adresse email est déjà utilisée.';
            } else {
                // Insérer le nouvel utilisateur
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (email, password, role_id, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$email, $hashed_password, $role]);
                
                $message = 'Utilisateur ajouté avec succès !';
                
                // Réinitialiser le formulaire
                $email = '';
                $password = '';
                $confirm_password = '';
                $role = 1; // Utilisateur par défaut
            }
        } catch (PDOException $e) {
            $error = 'Erreur lors de l\'ajout de l\'utilisateur : ' . $e->getMessage();
        }
    }
}

// Récupérer les rôles disponibles
try {
    $stmt = $pdo->query("SELECT id, name FROM user_roles ORDER BY id");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $roles = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un utilisateur - Back-office Laila Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .navbar { background-color: #2c3e50 !important; }
        .sidebar { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .admin-card { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { background: linear-gradient(135deg, #2c3e50, #34495e); color: white; padding: 20px 25px; border: none; }
        .card-body { padding: 25px; }
        .nav-link { color: #34495e; padding: 12px 15px; border-radius: 8px; margin-bottom: 5px; }
        .nav-link.active { background-color: #2c3e50; color: white; }
        .nav-link:hover { background-color: #ecf0f1; color: #2c3e50; }
        .form-control:focus { border-color: #2c3e50; box-shadow: 0 0 0 0.2rem rgba(44, 62, 80, 0.25); }
        .btn-primary { background-color: #2c3e50; border-color: #2c3e50; }
        .btn-primary:hover { background-color: #34495e; border-color: #34495e; }
    </style>
</head>
<body>
    <!-- Navbar Admin -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-shield-lock"></i> Laila Workspace - Back-office
            </a>
            
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['user_role']) ?>
                </span>
                <a href="<?= BASE_URL ?>/views/auth/logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Déconnexion
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2">
                <div class="sidebar">
                    <h6 style="color: #2c3e50; font-weight: 600; margin-bottom: 15px;">Navigation</h6>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="add_user.php">
                                <i class="bi bi-person-plus"></i> Ajouter Utilisateur
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="bi bi-people"></i> Utilisateurs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="projects.php">
                                <i class="bi bi-kanban"></i> Projets
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="analytics.php">
                                <i class="bi bi-graph-up"></i> Analytics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="partnerships.php">
                                <i class="bi bi-handshake"></i> Partenariats B2B
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="audit.php">
                                <i class="bi bi-shield-check"></i> Audit
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Contenu principal -->
            <div class="col-md-9 col-lg-10">
                <h1 style="color: #2c3e50; margin-bottom: 30px;">
                    <i class="bi bi-person-plus"></i> Ajouter un utilisateur
                </h1>

                <!-- Messages -->
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Formulaire -->
                <div class="admin-card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Laila Workspace" style="max-width: 40px; height: auto; margin-right: 15px;">
                            <h5 style="margin: 0; font-weight: 600;">
                                <i class="bi bi-person-plus"></i> Nouvel utilisateur
                            </h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Adresse email *</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?= htmlspecialchars($email ?? '') ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="role" class="form-label">Rôle *</label>
                                        <select class="form-select" id="role" name="role" required>
                                            <?php foreach ($roles as $role_option): ?>
                                                <option value="<?= $role_option['id'] ?>" 
                                                        <?= (isset($role) && $role == $role_option['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($role_option['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Mot de passe *</label>
                                        <input type="password" class="form-control" id="password" name="password" 
                                               minlength="6" required>
                                        <div class="form-text">Minimum 6 caractères</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirmer le mot de passe *</label>
                                        <input type="password" class="form-control" id="confirm_password" 
                                               name="confirm_password" minlength="6" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Retour au dashboard
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-person-plus"></i> Ajouter l'utilisateur
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="mb-2">
                        <i class="bi bi-shield-lock"></i> Laila Workspace - Back-office
                    </h6>
                    <p class="mb-0 text-muted small">
                        Plateforme de gestion Business Model Canvas - Version 2.0
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="d-flex flex-column flex-md-row justify-content-md-end align-items-md-center">
                        <div class="me-md-3 mb-2 mb-md-0">
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle"></i> Gestion Utilisateurs
                            </span>
                        </div>
                        <div class="text-muted small">
                            <i class="bi bi-clock"></i> Dernière mise à jour : <?= date('d/m/Y H:i') ?>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">
                            © 2024 Laila Workspace - Tous droits réservés
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</body>
</html> 