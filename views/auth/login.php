<?php
session_start();
require_once '../../includes/config.php';
require_once '../../vendor/autoload.php';
require_once '../../includes/db_connect.php';
require_once '../layouts/navbar.php';

// Générer un jeton CSRF pour la sécurité
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Vérifier si un message d'erreur est présent
$error_message = isset($_SESSION['error']) ? htmlspecialchars($_SESSION['error']) : '';
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Laila Workspace</title>
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
        
        .form-check-input:checked {
            background-color: #007bff;
            border-color: #007bff;
        }
        
        .form-check-label {
            color: #636e72;
            font-size: 0.9rem;
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
            <h1><i class="fas fa-sign-in-alt me-2"></i>Connexion</h1>
            <p>Accédez à votre espace de travail</p>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger py-3 text-center mb-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= $error_message ?>
                </div>
            <?php endif; ?>
            
            <form action="process_login.php" method="POST" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope me-2"></i>Adresse email
                    </label>
                    <input type="email" class="form-control" id="email" name="email" required 
                           placeholder="exemple@gmail.com">
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock me-2"></i>Mot de passe
                    </label>
                    <input type="password" class="form-control" id="password" name="password" required 
                           placeholder="Votre mot de passe">
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">
                            Se souvenir de moi
                        </label>
                    </div>
                    <a href="<?= BASE_URL ?>/views/auth/forgot_password.php" class="text-link">
                        Mot de passe oublié ?
                    </a>
                </div>
                
                <div class="form-actions mb-3">
                    <button type="button" class="btn btn-light w-100 mb-2" style="border:1px solid #e9ecef; display:flex; align-items:center; justify-content:center; gap:8px;">
                        <img src='https://cdn.jsdelivr.net/gh/devicons/devicon/icons/google/google-original.svg' alt="Google" style="width:20px; height:20px;"> Se connecter avec Google
                    </button>
                    <button type="button" class="btn btn-light w-100 mb-3" style="border:1px solid #e9ecef; display:flex; align-items:center; justify-content:center; gap:8px;">
                        <img src='https://cdn.jsdelivr.net/gh/devicons/devicon/icons/apple/apple-original.svg' alt="Apple" style="width:20px; height:20px;"> Se connecter avec Apple
                    </button>
                </div>
                <!-- Bouton principal de connexion -->

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>Se connecter
                    </button>
                </div>
            </form>
            
            <div class="auth-divider">
                <span>ou</span>
            </div>
            
            <div class="text-center">
                <span style="font-size: 1rem; color: #636e72;">
                    Pas de compte ? 
                    <a href="<?= BASE_URL ?>/views/auth/register.php" class="text-link">
                        Inscrivez-vous
                    </a>
                </span>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(event) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!emailRegex.test(email)) {
                event.preventDefault();
                alert('Veuillez entrer une adresse email valide.');
                return;
            }
            
            if (password.length < 8) {
                event.preventDefault();
                alert('Le mot de passe doit contenir au moins 8 caractères.');
                return;
            }
        });
    </script>
</body>
</html>