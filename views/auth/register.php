<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/config.php';
require_once '../../models/User.php';

// Générer un jeton CSRF pour la sécurité
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le jeton CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Erreur de validation CSRF.";
        header('Location: ' . BASE_URL . '/views/auth/register.php');
        exit();
    }

    $username = trim($_POST['username'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms_accepted = isset($_POST['terms']) ? true : false;

    // Validation des données
    if (empty($username) || empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = "Tous les champs obligatoires doivent être remplis.";
        header('Location: ' . BASE_URL . '/views/auth/register.php');
        exit();
    }

    if (!$terms_accepted) {
        $_SESSION['error'] = "Vous devez accepter les conditions générales d'utilisation pour continuer.";
        header('Location: ' . BASE_URL . '/views/auth/register.php');
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "L'adresse email n'est pas valide.";
        header('Location: ' . BASE_URL . '/views/auth/register.php');
        exit();
    }

    if (strlen($password) < 8) {
        $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères.";
        header('Location: ' . BASE_URL . '/views/auth/register.php');
        exit();
    }

    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
        header('Location: ' . BASE_URL . '/views/auth/register.php');
        exit();
    }

    // Vérifier si l'email ou le nom d'utilisateur existe déjà
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = "Cet email ou nom d'utilisateur est déjà utilisé.";
        header('Location: ' . BASE_URL . '/views/auth/register.php');
        exit();
    }

    // Créer un nouvel utilisateur
    try {
        $userModel = new User($pdo);
        $user_id = $userModel->create($username, $first_name, $last_name, $email, $phone, '', $password); // Experience vide
        $_SESSION['user_id'] = $user_id;
        $_SESSION['success'] = "Inscription réussie ! Vous êtes maintenant connecté.";
        header('Location: ' . BASE_URL . '/views/index.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de l'inscription : " . $e->getMessage();
        header('Location: ' . BASE_URL . '/views/auth/register.php');
        exit();
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
    <title>Inscription - Laila Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
    <style>
        .auth-container {
            min-height: calc(100vh - 80px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            margin-top: 80px;
        }
        
        .auth-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 3rem 2.5rem;
            max-width: 500px;
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
        
        .row {
            margin-left: -0.5rem;
            margin-right: -0.5rem;
        }
        
        .col-md-6 {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
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
            .auth-container {
                margin-top: 70px;
                padding: 1rem 0.5rem;
            }
            
            .auth-card {
                padding: 2rem 1.5rem;
                margin: 0.5rem;
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
            
            .row {
                margin-left: 0;
                margin-right: 0;
            }
            
            .col-md-6 {
                padding-left: 0;
                padding-right: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include '../layouts/navbar.php'; ?>

    <div class="auth-container">
        <div class="auth-card">
            <div class="text-center mb-4">
                <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Laila Workspace" style="max-width: 120px; height: auto; margin-bottom: 1rem;">
            </div>

            
            <?php if ($error_message): ?>
                <div class="alert alert-danger py-3 text-center mb-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= $error_message ?>
                </div>
            <?php endif; ?>
            
            <form action="register.php" method="POST" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">
                                <i class="fas fa-user me-2"></i>Prénom *
                            </label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required 
                                   placeholder="Votre prénom">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="last_name" class="form-label">
                                <i class="fas fa-user me-2"></i>Nom *
                            </label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required 
                                   placeholder="Votre nom">
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="username" class="form-label">
                        <i class="fas fa-at me-2"></i>Nom d'utilisateur *
                    </label>
                    <input type="text" class="form-control" id="username" name="username" required 
                           placeholder="nom_utilisateur">
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope me-2"></i>Adresse email *
                    </label>
                    <input type="email" class="form-control" id="email" name="email" required 
                           placeholder="exemple@gmail.com">
                </div>
                
                <div class="mb-3">
                    <label for="phone" class="form-label">
                        <i class="fas fa-phone me-2"></i>Téléphone *
                    </label>
                    <input type="tel" class="form-control" id="phone" name="phone" required 
                           placeholder="+33 6 12 34 56 78">
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-2"></i>Mot de passe *
                            </label>
                            <input type="password" class="form-control" id="password" name="password" required 
                                   placeholder="********">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-lock me-2"></i>Confirmer *
                            </label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required 
                                   placeholder="********">
                        </div>
                    </div>
                </div>
                
                <div class="mb-4 form-check">
                    <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                    <label class="form-check-label" for="terms">
                        J'accepte les <a href="#" class="text-link">conditions générales d'utilisation</a> *
                    </label>
                </div>
                
                <!-- Bouton principal -->
                <div class="form-actions mb-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus me-2"></i>Inscription
                    </button>
                </div>

                <div class="form-actions mb-3">
                    <div class="d-flex justify-content-center gap-3">
                        <button type="button" class="btn btn-light" style="border:1px solid #e9ecef; display:flex; align-items:center; justify-content:center; padding:8px 16px; gap:8px;">
                            <img src='https://cdn.jsdelivr.net/gh/devicons/devicon/icons/google/google-original.svg' alt="Google" style="width:20px; height:20px;">
                            <span>Google</span>
                        </button>
                        <button type="button" class="btn btn-light" style="border:1px solid #e9ecef; display:flex; align-items:center; justify-content:center; padding:8px 16px; gap:8px;">
                            <img src='https://cdn.jsdelivr.net/gh/devicons/devicon/icons/apple/apple-original.svg' alt="Apple" style="width:20px; height:20px;">
                            <span>Apple</span>
                        </button>
                    </div>
                </div>
            </form>
            
            <div class="auth-divider">
                <span>ou</span>
            </div>
            
            <div class="text-center">
                <span style="font-size: 1rem; color: #636e72;">
                    Déjà un compte ? 
                    <a href="<?= BASE_URL ?>/views/auth/login.php" class="text-link">
                        Connexion
                    </a>
                </span>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('registerForm').addEventListener('submit', function(event) {
            const username = document.getElementById('username').value.trim();
            const first_name = document.getElementById('first_name').value.trim();
            const last_name = document.getElementById('last_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const password = document.getElementById('password').value;
            const confirm_password = document.getElementById('confirm_password').value;
            const terms = document.getElementById('terms').checked;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
            
            if (!username || !first_name || !last_name || !email || !phone || !password || !confirm_password) {
                event.preventDefault();
                alert('Tous les champs obligatoires doivent être remplis.');
                return;
            }
            
            if (!emailRegex.test(email)) {
                event.preventDefault();
                alert('Veuillez entrer une adresse email valide.');
                return;
            }
            
            if (!phoneRegex.test(phone)) {
                event.preventDefault();
                alert('Veuillez entrer un numéro de téléphone valide.');
                return;
            }
            
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
            
            if (!terms) {
                event.preventDefault();
                alert('Vous devez accepter les conditions générales d\'utilisation.');
                return;
            }
        });
    </script>
</body>
</html>