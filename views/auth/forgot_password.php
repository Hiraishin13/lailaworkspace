<?php
session_start();
require_once '../../includes/config.php';
require_once '../../vendor/autoload.php';
require_once '../../includes/db_connect.php';
require_once '../layouts/navbar.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Générer un jeton CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Gérer la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le jeton CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        error_log("Erreur CSRF dans forgot_password.php");
        header('Location: ' . BASE_URL . '/views/auth/forgot_password.php');
        exit;
    }

    $input = trim($_POST['input'] ?? '');
    if (empty($input)) {
        $_SESSION['error'] = "Veuillez entrer un email ou un numéro de téléphone.";
        error_log("Champ input vide dans forgot_password.php");
        header('Location: ' . BASE_URL . '/views/auth/forgot_password.php');
        exit;
    }

    // Vérifier si l'input est un email ou un téléphone
    $is_email = filter_var($input, FILTER_VALIDATE_EMAIL);
    $field = $is_email ? 'email' : 'phone';
    $value = $input;

    // Rechercher l'utilisateur
    try {
        $stmt = $pdo->prepare("SELECT id, email FROM users WHERE $field = ?");
        $stmt->execute([$value]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $_SESSION['error'] = "Aucun compte associé à cet email ou numéro de téléphone.";
            error_log("Utilisateur non trouvé pour $field: $value");
            header('Location: ' . BASE_URL . '/views/auth/forgot_password.php');
            exit;
        }

        // Générer un jeton de réinitialisation
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Stocker le jeton
        $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$user['email'], $token, $expires_at]);

        // Envoyer l'email
        $mail = new PHPMailer(true);
        try {
            // Configuration SMTP
            $mail->isSMTP();
            $mail->Host = defined('SMTP_HOST') && SMTP_HOST ? SMTP_HOST : 'localhost';
            $mail->SMTPAuth = defined('SMTP_USERNAME') && SMTP_USERNAME;
            if ($mail->SMTPAuth) {
                $mail->Username = SMTP_USERNAME;
                $mail->Password = SMTP_PASSWORD;
            }
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = defined('SMTP_PORT') && SMTP_PORT ? SMTP_PORT : 1025;

            // Destinataire
            $from_email = defined('SMTP_USERNAME') && SMTP_USERNAME ? SMTP_USERNAME : 'no-reply@laila-workspace.local';
            $mail->setFrom($from_email, 'Laila Workspace');
            $mail->addAddress($user['email']);

            // Contenu
            $reset_link = BASE_URL . '/views/auth/reset_password.php?token=' . $token;
            $mail->isHTML(true);
            $mail->Subject = 'Réinitialisation de votre mot de passe - Laila Workspace';
            $mail->Body = '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;">
                    <h2 style="color: #007bff;">Réinitialisation de mot de passe</h2>
                    <p>Vous avez demandé à réinitialiser votre mot de passe pour Laila Workspace.</p>
                    <p><a href="' . $reset_link . '" style="display: inline-block; padding: 10px 20px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 5px;">Réinitialiser mon mot de passe</a></p>
                    <p>Ce lien expire dans 1 heure.</p>
                    <p>Si vous n\'avez pas fait cette demande, ignorez cet email.</p>
                    <p style="color: #6c757d; font-size: 0.9rem;">Laila Workspace © ' . date('Y') . '</p>
                </div>
            ';
            $mail->AltBody = "Vous avez demandé à réinitialiser votre mot de passe. Visitez ce lien : $reset_link\nCe lien expire dans 1 heure.\nSi vous n'avez pas fait cette demande, ignorez cet email.";

            $mail->send();
            $_SESSION['success'] = "Un email de réinitialisation a été envoyé à votre adresse.";
            error_log("Email de réinitialisation envoyé à {$user['email']}");
            header('Location: ' . BASE_URL . '/views/auth/forgot_password.php');
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur lors de l'envoi de l'email. Veuillez réessayer plus tard.";
            error_log("Erreur PHPMailer: " . $mail->ErrorInfo);
            header('Location: ' . BASE_URL . '/views/auth/forgot_password.php');
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur : " . $e->getMessage();
        error_log("Erreur PDO dans forgot_password.php: " . $e->getMessage());
        header('Location: ' . BASE_URL . '/views/auth/forgot_password.php');
        exit;
    }
}

// Récupérer les messages
$error_message = isset($_SESSION['error']) ? htmlspecialchars($_SESSION['error']) : '';
$success_message = isset($_SESSION['success']) ? htmlspecialchars($_SESSION['success']) : '';
unset($_SESSION['error'], $_SESSION['success']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - Laila Workspace</title>
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
        
        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border-left: 4px solid #28a745;
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
            <div class="text-center mb-4">
                <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Laila Workspace" style="max-width: 120px; height: auto; margin-bottom: 1rem;">
            </div>
            <h1><i class="fas fa-key me-2"></i>Mot de passe oublié</h1>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger py-3 text-center mb-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= $error_message ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success py-3 text-center mb-3">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= $success_message ?>
                </div>
            <?php endif; ?>
            
            <form action="forgot_password.php" method="POST" id="forgotForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="mb-4">
                    <label for="input" class="form-label">
                        <i class="fas fa-envelope me-2"></i>Email ou téléphone
                    </label>
                    <input type="text" class="form-control" id="input" name="input" required 
                           placeholder="exemple@gmail.com ou +33 6 12 34 56 78">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Envoyer le lien de réinitialisation
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
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('forgotForm').addEventListener('submit', function(event) {
            const input = document.getElementById('input').value.trim();
            if (!input) {
                event.preventDefault();
                alert('Veuillez entrer un email ou un numéro de téléphone.');
                return;
            }
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
            if (!emailRegex.test(input) && !phoneRegex.test(input)) {
                event.preventDefault();
                alert('Veuillez entrer un email ou un numéro de téléphone valide.');
                return;
            }
        });
    </script>
</body>
</html>