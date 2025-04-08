<?php
session_start();
require_once '../../vendor/autoload.php';
require_once '../../includes/db_connect.php';
require_once '../layouts/navbar.php';

// Générer un jeton CSRF pour la sécurité
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Vérifier si un message de succès ou d'erreur est présent
$success_message = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success']);
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - Laila Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
</head>
<body>
    <div class="container my-5">
        <h2 class="text-center text-primary mb-5 fw-bold">Mot de passe oublié</h2>

        <!-- Afficher un message de succès si présent -->
        <?php if ($success_message): ?>
            <div class="alert alert-success text-center" role="alert">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <!-- Afficher un message d'erreur si présent -->
        <?php if ($error_message): ?>
            <div class="alert alert-danger text-center" role="alert">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card p-4 shadow-sm">
                    <p class="text-center text-muted mb-4">
                        Entrez votre adresse email pour recevoir un lien de réinitialisation de mot de passe.
                    </p>
                    <form action="process_forgot_password.php" method="POST">
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

                        <!-- Bouton de soumission -->
                        <button type="submit" class="btn btn-primary w-100">Envoyer le lien</button>
                    </form>

                    <!-- Lien de retour à la connexion -->
                    <p class="text-center mt-3">
                        <a href="login.php" class="text-primary">Retour à la connexion</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php include '../layouts/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>