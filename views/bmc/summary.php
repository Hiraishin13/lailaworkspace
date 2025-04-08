<?php
require_once '../../includes/db_connect.php';
require_once '../../views/layouts/navbar.php';
require_once '../../models/Bmc.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/views/auth/login.php");
    exit;
}

$bmcModel = new Bmc($pdo);
$bmc_id = isset($_GET['bmc_id']) ? (int)$_GET['bmc_id'] : null;
$bmc = null;
$prompt = '';

if ($bmc_id) {
    $result = $bmcModel->findById($bmc_id, $_SESSION['user_id']);
    if ($result) {
        $prompt = $result['prompt'];
        $bmc = json_decode($result['bmc_data'], true);
    }
}

if (!$bmc) {
    header("Location: " . BASE_URL . "/views/index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Récapitulatif du BMP - Laila Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h1 class="mb-4 fw-bold text-dark">
                Récapitulatif de votre <span class="text-primary">Business Model Canvas</span>
            </h1>
            <p class="lead text-muted">
                Voici la version finale de votre BMP.
            </p>
        </div>

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

        <!-- Options d'export -->
        <div class="text-center mb-5">
            <button class="btn btn-export rounded-3 px-4 me-2">
                <i class="bi bi-file-earmark-pdf me-2"></i>Exporter en PDF
            </button>
            <button class="btn btn-outline-primary rounded-3 px-4">
                <i class="bi bi-share me-2"></i>Partager
            </button>
        </div>

        <!-- Appel à action personnalisé -->
        <div class="text-center">
            <h3 class="text-primary mb-3">Prochaine étape</h3>
            <p class="text-muted mb-4">
                Présentez votre BMP à des investisseurs ou testez vos hypothèses sur le terrain !
            </p>
            <a href="<?= BASE_URL ?>/views/index.php" class="btn btn-primary rounded-3 px-4">
                <i class="bi bi-rocket-takeoff me-2"></i>Créer un nouveau BMP
            </a>
        </div>
    </div>

    <?php include '../../views/layouts/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>