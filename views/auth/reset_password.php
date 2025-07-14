<?php
session_start();
require_once '../../includes/config.php';
require_once '../../vendor/autoload.php';
require_once '../../includes/db_connect.php';
require_once '../layouts/navbar.php';
require_once '../../models/User.php';

// Générer un jeton CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Vérifier le jeton de réinitialisation
$token = $_GET['token'] ?? '';
$valid_token = false;
$email = '';

if ($token) {
    try {
        $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($reset) {
            $email = $reset['email'];
            $valid_token = true;
        } else {
            $_SESSION['error'] = "Le lien de réinitialisation est invalide ou a expiré.";
            error_log("Jeton de réinitialisation invalide ou expiré: $token");
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur : " . $e->getMessage();
        error_log("Erreur PDO dans reset_password.php: " . $e->getMessage());
    }
} else {
    $_SESSION['error'] = "Aucun jeton de réinitialisation fourni.";
    error_log("Aucun jeton fourni dans reset_password.php");
}

// Gérer la soumission du nouveau mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    // Vérifier le jeton CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        error_log("Erreur CSRF dans reset_password.php");
        header('Location: ' . BASE_URL . '/views/auth/reset_password.php?token=' . urlencode($token));
        exit;
    }

    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (strlen($password) < 8) {
        $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères.";
        error_log("Mot de passe trop court dans reset_password.php");
        header('Location: ' . BASE_URL . '/views/auth/reset_password.php?token=' . urlencode($token));
        exit;
    }

    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
        error_log("Mots de passe non correspondants dans reset_password.php");
        header('Location: ' . BASE_URL . '/views/auth/reset_password.php?token=' . urlencode($token));
        exit;
    }

    // Mettre à jour le mot de passe
    try {
        $userModel = new User($pdo);
        $userModel->updatePassword($email, $password);

        // Supprimer le jeton utilisé
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);

        $_SESSION['success'] = "Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.";
        error_log("Mot de passe réinitialisé pour $email");
        header('Location: ' . BASE_URL . '/views/auth/login.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la réinitialisation : " . $e->getMessage();
        error_log("Erreur PDO lors de la réinitialisation: " . $e->getMessage());
        header('Location: ' . BASE_URL . '/views/auth/reset_password.php?token=' . urlencode($token));
        exit;
    }
}

// Récupérer les messages d'erreur
$error_message = isset($_SESSION['error']) ? htmlspecialchars($_SESSION['error']) : '';
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser le mot de passe - Laila Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        
        .auth-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 3rem 2.5rem;
            max-width: 450px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .auth-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #007bff, #00c4b4);
        }
        
        .auth-card h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: #1e272e;
            margin-bottom: 0.5rem;
            text-align: center;
        }
        
        .auth-card p {
            color: #636e72;
            margin-bottom: 2rem;
            text-align: center;
            font-size: 1.1rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #2d3436;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 6px;
            padding: 0.875rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }
        
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
            background: white;
        }
        
        .form-control::placeholder {
            color: #adb5bd;
        }
        
        .btn-primary {
            background: linear-gradient(90deg, #007bff, #00c4b4);
            border: none;
            color: white;
            font-weight: 600;
            border-radius: 6px;
            padding: 0.875rem 2rem;
            font-size: 1.1rem;
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
            display: block;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary:hover {
            background: linear-gradient(90deg, #0056b3, #009a8e);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.3);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .text-link {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .text-link:hover {
            color: #0056b3;
            text-decoration: underline;
        }
        
        .alert {
            border: none;
            border-radius: 12px;
            font-weight: 500;
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border-left: 4px solid #dc3545;
        }
        
        .auth-divider {
            text-align: center;
            margin: 1.5rem 0;
            position: relative;
        }
        
        .auth-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e9ecef;
        }
        
        .auth-divider span {
            background: rgba(255, 255, 255, 0.95);
            padding: 0 1rem;
            color: #636e72;
            font-size: 0.9rem;
        }
        
        .form-actions {
            text-align: center;
            margin: 1.5rem 0;
        }
        
        .form-actions .btn {
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
        }
        
        .text-center-link {
            text-align: center;
            margin-top: 1rem;
        }
        
        @media (max-width: 576px) {
            .auth-card {
                padding: 2rem 1.5rem;
                margin: 1rem;
            }
            
            .auth-card h1 {
                font-size: 1.8rem;
            }
            
            .btn-primary {
                max-width: 100%;
                padding: 1rem 1.5rem;
                font-size: 1rem;
            }
            
            .form-actions .btn {
                max-width: 100%;
            }
            
            .auth-card {
                max-width: 100%;
                margin: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1><i class="fas fa-lock me-2"></i>Nouveau mot de passe</h1>
            <p>Choisissez votre nouveau mot de passe</p>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger py-3 text-center mb-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= $error_message ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$valid_token): ?>
                <div class="text-center">
                    <a href="<?= BASE_URL ?>/views/auth/login.php" class="text-link">
                        <i class="fas fa-arrow-left me-2"></i>Retour à la connexion
                    </a>
                </div>
            <?php else: ?>
                <form action="reset_password.php?token=<?= urlencode($token) ?>" method="POST" id="resetForm">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock me-2"></i>Nouveau mot de passe *
                        </label>
                        <input type="password" class="form-control" id="password" name="password" required 
                               placeholder="Votre nouveau mot de passe">
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">
                            <i class="fas fa-lock me-2"></i>Confirmer le mot de passe *
                        </label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required 
                               placeholder="Confirmez votre mot de passe">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Réinitialiser le mot de passe
                        </button>
                    </div>
                    
                    <div class="auth-divider">
                        <span>ou</span>
                    </div>
                    
                    <div class="text-center">
                        <a href="<?= BASE_URL ?>/views/auth/login.php" class="text-link">
                            <i class="fas fa-arrow-left me-2"></i>Retour à la connexion
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('resetForm')?.addEventListener('submit', function(event) {
            const password = document.getElementById('password').value;
            const confirm_password = document.getElementById('confirm_password').value;
            
            if (password.length < 8) {
                event.preventDefault();
                alert('Le mot de passe doit contenir au moins 8 caractères.');
                return;
            }
            
            if (password !== confirm_password) {
                event.preventDefault();
                alert('Les mots de passe ne correspondent pas.');
                return;
            }
        });
    </script>
</body>
</html>