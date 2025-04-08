<?php
require_once '../../includes/db_connect.php';
require_once '../../views/layouts/navbar.php';
require_once '../../models/Bmc.php';

$bmcModel = new Bmc($pdo);
$bmc_id = isset($_GET['bmc_id']) ? (int)$_GET['bmc_id'] : null;
$bmc = null;
$prompt = '';

if ($bmc_id) {
    $result = $bmcModel->findById($bmc_id, isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null);
    if ($result) {
        $prompt = $result['prompt'];
        $bmc = json_decode($result['bmc_data'], true);
    }
} elseif (isset($_SESSION['temp_bmc'])) {
    $bmc = $_SESSION['temp_bmc'];
    $prompt = $_SESSION['temp_prompt'];
}

if (!$bmc) {
    header("Location: " . BASE_URL . "/views/bmc/generate_bmc.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre Business Model Canvas - Laila Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <!-- Introduction -->
        <div class="text-center mb-5">
            <h1 class="mb-4 fw-bold text-dark">
                Votre <span class="text-primary">Business Model Canvas</span>
            </h1>
            <p class="lead text-muted">
                Voici le BMP généré par notre IA basé sur votre description :
            </p>
            <p class="text-muted mb-5"><strong>Prompt :</strong> <?= $prompt ?></p>
        </div>

        <!-- Affichage du BMP -->
        <div class="row g-4 mb-5">
            <div class="col-12">
                <div class="bmc-section">
                    <h5><i class="bi bi-people me-2"></i>Segments de clients</h5>
                    <p class="text-muted"><?= $bmc['customer_segments'] ?></p>
                </div>
            </div>
            <div class="col-12">
                <div class="bmc-section">
                    <h5><i class="bi bi-star me-2"></i>Propositions de valeur</h5>
                    <p class="text-muted"><?= $bmc['value_propositions'] ?></p>
                </div>
            </div>
            <div class="col-12">
                <div class="bmc-section">
                    <h5><i class="bi bi-megaphone me-2"></i>Canaux</h5>
                    <p class="text-muted"><?= $bmc['channels'] ?></p>
                </div>
            </div>
            <div class="col-12">
                <div class="bmc-section">
                    <h5><i class="bi bi-heart me-2"></i>Relations clients</h5>
                    <p class="text-muted"><?= $bmc['customer_relationships'] ?></p>
                </div>
            </div>
            <div class="col-12">
                <div class="bmc-section">
                    <h5><i class="bi bi-currency-dollar me-2"></i>Sources de revenus</h5>
                    <p class="text-muted"><?= $bmc['revenue_streams'] ?></p>
                </div>
            </div>
            <div class="col-12">
                <div class="bmc-section">
                    <h5><i class="bi bi-tools me-2"></i>Ressources clés</h5>
                    <p class="text-muted"><?= $bmc['key_resources'] ?></p>
                </div>
            </div>
            <div class="col-12">
                <div class="bmc-section">
                    <h5><i class="bi bi-list-check me-2"></i>Activités clés</h5>
                    <p class="text-muted"><?= $bmc['key_activities'] ?></p>
                </div>
            </div>
            <div class="col-12">
                <div class="bmc-section">
                    <h5><i class="bi bi-handshake me-2"></i>Partenariats clés</h5>
                    <p class="text-muted"><?= $bmc['key_partnerships'] ?></p>
                </div>
            </div>
            <div class="col-12">
                <div class="bmc-section">
                    <h5><i class="bi bi-wallet me-2"></i>Structure de coûts</h5>
                    <p class="text-muted"><?= $bmc['cost_structure'] ?></p>
                </div>
            </div>
        </div>

        <!-- Bouton pour modifier -->
        <div class="text-center mb-5">
            <a href="<?= BASE_URL ?>/views/bmc/edit_bmc.php?bmc_id=<?= $bmc_id ?? 'temp' ?>" class="btn btn-primary rounded-3 px-4">
                <i class="bi bi-pencil-square me-2"></i>Modifier le BMP
            </a>
        </div>
    </div>

    <!-- Pop-up d'inscription -->
    <?php if (!isset($_SESSION['user_id'])): ?>
    <div class="modal fade" id="signupModal" tabindex="-1" aria-labelledby="signupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="signupModalLabel">Inscrivez-vous pour continuer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">
                        Créez un compte pour sauvegarder votre BMP et accéder à toutes les fonctionnalités de Laila Workspace.
                    </p>
                </div>
                <div class="modal-footer">
                    <a href="<?= BASE_URL ?>/views/auth/register.php" class="btn btn-primary rounded-3 px-4">
                        <i class="bi bi-person-plus me-2"></i>S'inscrire
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php include '../../views/layouts/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/scripts.js"></script>
    <script>
        // Afficher la pop-up après 3 secondes si l'utilisateur n'est pas connecté
        <?php if (!isset($_SESSION['user_id'])): ?>
        showSignupModalAfterDelay();
        <?php endif; ?>
    </script>
</body>
</html>