<?php
session_start();
require_once '../../vendor/autoload.php';
require_once '../../includes/db_connect.php';
require_once '../layouts/navbar.php';

// Vérifier si un jeton est fourni
if (!isset($_GET['token']) || empty($_GET['token'])) {
    $_SESSION['error'] = "Jeton de réinitialisation manquant.";
    header('Location: login.php');
    exit();
}

$token = $_GET['token'];

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

// Générer un jeton CSRF pour la sécurité
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Vérifier si un message d'erreur est présent
$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser le mot de passe - Laila Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
</head>
<body>
    <div class="container my-5">
        <h2 class="text-center text-primary mb-5 fw-bold">Réinitialiser le mot de passe</h2>

        <!-- Afficher un message d'erreur si présent -->
        <?php if ($error_message): ?>
            <div class="alert alert-danger text-center" role="alert">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card p-4 shadow-sm">
                    <form action="process_reset_password.php" method="POST">
                        <!-- Jeton CSRF -->
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <!-- Jeton de réinitialisation -->
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                        <!-- Champ Nouveau mot de passe -->
                        <div class="mb-3 position-relative">
                            <label for="password" class="form-label">Nouveau mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="password" name="password" required
                                       placeholder="••••••••"
                                       aria-label="Nouveau mot de passe">
                            </div>
                        </div>

                        <!-- Champ Confirmer le mot de passe -->
                        <div class="mb-3 position-relative">
                            <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required
                                       placeholder="••••••••"
                                       aria-label="Confirmer le mot de passe">
                            </div>
                        </div>

                        <!-- Bouton de soumission -->
                        <button type="submit" class="btn btn-primary w-100">Réinitialiser</button>
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