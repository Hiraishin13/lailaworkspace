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

if ($bmc_id) {
    $result = $bmcModel->findById($bmc_id, $_SESSION['user_id']);
    if ($result) {
        $bmc = json_decode($result['bmc_data'], true);
    }
}

if (!$bmc) {
    header("Location: " . BASE_URL . "/views/index.php");
    exit;
}

// Analyse simple (exemple)
$analysis = [
    'strengths' => 'Propositions de valeur solides, canaux bien définis.',
    'weaknesses' => 'Structure de coûts élevée, dépendance aux partenariats.',
    'opportunities' => 'Expansion vers de nouveaux marchés, intégration de nouvelles technologies.',
    'threats' => 'Concurrence accrue, fluctuations économiques.'
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analyse du BMP - Laila Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h1 class="mb-4 fw-bold text-dark">
                Analyse de votre <span class="text-primary">Business Model Canvas</span>
            </h1>
            <p class="lead text-muted">
                Voici une analyse SWOT de votre BMP.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card p-4">
                    <h5 class="text-success mb-3"><i class="bi bi-check-circle me-2"></i>Forces</h5>
                    <p class="text-muted"><?= $analysis['strengths'] ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card p-4">
                    <h5 class="text-warning mb-3"><i class="bi bi-exclamation-triangle me-2"></i>Faiblesses</h5>
                    <p class="text-muted"><?= $analysis['weaknesses'] ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card p-4">
                    <h5 class="text-primary mb-3"><i class="bi bi-lightbulb me-2"></i>Opportunités</h5>
                    <p class="text-muted"><?= $analysis['opportunities'] ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card p-4">
                    <h5 class="text-danger mb-3"><i class="bi bi-shield-exclamation me-2"></i>Menaces</h5>
                    <p class="text-muted"><?= $analysis['threats'] ?></p>
                </div>
            </div>
        </div>

        <div class="text-center mt-5">
            <a href="<?= BASE_URL ?>/views/bmc/summary.php?bmc_id=<?= $bmc_id ?>" class="btn btn-primary px-4">
                <i class="bi bi-file-earmark-text me-2"></i>Retour au récapitulatif
            </a>
        </div>
    </div>

    <?php include '../../views/layouts/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>