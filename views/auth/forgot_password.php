<?php
session_start();
require_once '../../vendor/autoload.php';
require_once '../../includes/db_connect.php';
require_once '../layouts/navbar.php';

// Générer un jeton CSRF pour la sécurité
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Vérifier si un message d'erreur est présent (après une tentative de connexion échouée)
$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']); // Effacer le message après l'affichage
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Laila Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
</head>
<body>
    <div class="container my-5">
        <h2 class="text-center text-primary mb-5 fw-bold">Connexion</h2>

        <!-- Afficher un message d'erreur si présent -->
        <?php if ($error_message): ?>
            <div class="alert alert-danger text-center" role="alert">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card p-4 shadow-sm">
                    <form action="process_login.php" method="POST" id="loginForm">
                        <!-- Jeton CSRF -->
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <!-- Champ Email -->
                        <div class="mb-3 position-relative">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-envelope"></i>
                                </span>
                                <input type="email" class="form-control" id="email" name="email" required
                                       placeholder="exemple@domaine.com"
                                       aria-label="Adresse email">
                            </div>
                        </div>

                        <!-- Champ Mot de passe -->
                        <div class="mb-3 position-relative">
                            <label for="password" class="form-label">Mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="password" name="password" required
                                       placeholder="••••••••"
                                       aria-label="Mot de passe">
                            </div>
                        </div>

                        <!-- Lien Mot de passe oublié (centré) -->
                        <div class="mb-3 text-center">
                            <a href="forgot_password.php" class="text-primary">Mot de passe oublié ?</a>
                        </div>

                        <!-- Bouton de soumission -->
                        <button type="submit" class="btn btn-primary w-100">Se connecter</button>
                    </form>

                    <!-- Lien vers l'inscription -->
                    <p class="text-center mt-3">
                        Pas de compte ? <a href="register.php" class="text-primary">Inscrivez-vous</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php include '../layouts/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('loginForm').addEventListener('submit', function(event) {
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!email || !emailRegex.test(email)) {
            event.preventDefault();
            alert('Veuillez entrer une adresse email valide.');
            return;
        }

        if (!password || password.length < 8) {
            event.preventDefault();
            alert('Le mot de passe doit contenir au moins 8 caractères.');
            return;
        }
    });
    </script>
</body>
</html>