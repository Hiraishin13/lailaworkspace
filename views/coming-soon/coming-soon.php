<?php
session_start();
define('BASE_DIR', dirname(__DIR__, 2)); // Root of the project
require_once BASE_DIR . '/includes/config.php';
// Fallback if BASE_URL isn’t defined in config.php
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/laila_workspace/');
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fonctionnalité en développement - Laila Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
</head>
<body>
    <?php include '../layouts/navbar.php'; ?>

    <div class="container my-5 text-center">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm p-5">
                    <i class="bi bi-gear-fill text-primary mb-4" style="font-size: 4rem;"></i>
                    <h2 class="fw-bold mb-3">Cette fonctionnalité est en développement</h2>
                    <p class="text-muted mb-4">
                        Nous travaillons dur pour vous offrir cette fonctionnalité très prochainement. Revenez bientôt pour découvrir nos nouveautés !
                    </p>
                    <a href="<?= BASE_URL ?>/views/index.php" class="btn btn-primary">
                        <i class="bi bi-arrow-left me-2"></i>Retour à l'accueil
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php include '../layouts/footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>