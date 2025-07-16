<?php
session_start();

// Définir le chemin de base pour les inclusions
define('BASE_DIR', dirname(__DIR__, 2));
require_once BASE_DIR . '/includes/db_connect.php';
require_once BASE_DIR . '/includes/config.php';
require_once BASE_DIR . '/vendor/autoload.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

// Vérifier si un project_id est fourni
if (!isset($_GET['project_id']) || !is_numeric($_GET['project_id'])) {
    $_SESSION['error'] = "Projet non spécifié.";
    header('Location: ../dashboard.php');
    exit();
}

$project_id = (int)$_GET['project_id'];

// Vérifier si le projet appartient à l'utilisateur
try {
    $stmt = $pdo->prepare("SELECT name, description FROM projects WHERE id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $project_id, 'user_id' => $_SESSION['user_id']]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        $_SESSION['error'] = "Projet non trouvé ou accès non autorisé.";
        header('Location: ../dashboard.php');
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération du projet : " . $e->getMessage();
    header('Location: ../dashboard.php');
    exit();
}

// Charger les données du BMP (hypothèses validées et données financières)
$hypotheses = [];
$financial_data = null;
$call_to_action = '';
$bmc_blocks = [];
try {
    // Hypothèses validées
    $stmt = $pdo->prepare("SELECT hypothesis_text, status FROM hypotheses WHERE project_id = :project_id AND status = 'confirmed'");
    $stmt->execute(['project_id' => $project_id]);
    $hypotheses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Données financières
    $stmt = $pdo->prepare("SELECT * FROM financial_plans WHERE project_id = :project_id");
    $stmt->execute(['project_id' => $project_id]);
    $financial_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Récupérer les blocs du BMC
    $stmt = $pdo->prepare("SELECT block_name, content FROM bmc WHERE project_id = :project_id");
    $stmt->execute(['project_id' => $project_id]);
    $bmc_blocks = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Récupérer l'appel à action le plus récent depuis la base de données
    $stmt = $pdo->prepare("SELECT call_to_action FROM project_ctas WHERE project_id = :project_id ORDER BY created_at DESC LIMIT 1");
    $stmt->execute(['project_id' => $project_id]);
    $call_to_action = $stmt->fetchColumn() ?: '';
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des données du BMP : " . $e->getMessage();
}

// Générer un appel à action personnalisé
if (isset($_GET['generate_cta']) && $_GET['generate_cta'] == 1) {
    try {
        $client = \OpenAI::client(OPENAI_API_KEY);
        $prompt = "Génère un appel à action personnalisé pour un Business Model Plan intitulé '" . $project['name'] . "' avec la description suivante : '" . $project['description'] . "'. L'appel à action doit être motivant, clair et inciter à une action immédiate (exemple : 'Lancez votre projet dès aujourd'hui et atteignez vos objectifs financiers en moins de 6 mois !').";
        $response = $client->chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'Tu es un expert en marketing et en rédaction d’appels à action motivants.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 100,
            'temperature' => 0.7,
        ]);
        $call_to_action = $response->choices[0]->message->content;

        // Sauvegarder l'appel à action dans la base de données
        $stmt = $pdo->prepare("INSERT INTO project_ctas (project_id, call_to_action) VALUES (:project_id, :call_to_action)");
        $stmt->execute(['project_id' => $project_id, 'call_to_action' => $call_to_action]);

        $_SESSION['success'] = "Appel à action généré avec succès !";
        header("Location: bmp_summary.php?project_id=$project_id");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur lors de la génération de l'appel à action : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Récapitulatif BMP - Laila Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include '../layouts/navbar.php'; ?>

    <div class="container my-5">
        <!-- Afficher les messages d'erreur ou de succès -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>


        <h2 class="section-title text-center mb-5">Récapitulatif du BMP - <?= htmlspecialchars($project['name']) ?></h2>

        <!-- Résumé du BMP -->
        <div class="bmp-summary-section">
            <h4 class="text-primary mb-4"><i class="bi bi-file-earmark-text me-2"></i> Résumé du Business Model Plan</h4>
            <div class="card shadow-sm p-4">
                <h5>Description du Projet</h5>
                <p class="text-muted"><?= htmlspecialchars($project['description']) ?></p>

                <h5 class="mt-4">Blocs du Business Model Canvas</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="bmc-card h-100">
                            <h6>Proposition de valeur</h6>
                            <p class="text-muted"><?= htmlspecialchars($bmc_blocks['proposition_valeur'] ?? 'Non spécifié') ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bmc-card h-100">
                            <h6>Segments de clientèle</h6>
                            <p class="text-muted"><?= htmlspecialchars($bmc_blocks['segments_clientele'] ?? 'Non spécifié') ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bmc-card h-100">
                            <h6>Canaux</h6>
                            <p class="text-muted"><?= htmlspecialchars($bmc_blocks['canaux'] ?? 'Non spécifié') ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bmc-card h-100">
                            <h6>Relations clients</h6>
                            <p class="text-muted"><?= htmlspecialchars($bmc_blocks['relations_clients'] ?? 'Non spécifié') ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bmc-card h-100">
                            <h6>Sources de revenus</h6>
                            <p class="text-muted"><?= htmlspecialchars($bmc_blocks['sources_revenus'] ?? 'Non spécifié') ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bmc-card h-100">
                            <h6>Ressources clés</h6>
                            <p class="text-muted"><?= htmlspecialchars($bmc_blocks['ressources_cles'] ?? 'Non spécifié') ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bmc-card h-100">
                            <h6>Activités clés</h6>
                            <p class="text-muted"><?= htmlspecialchars($bmc_blocks['activites_cles'] ?? 'Non spécifié') ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bmc-card h-100">
                            <h6>Partenaires clés</h6>
                            <p class="text-muted"><?= htmlspecialchars($bmc_blocks['partenaires_cles'] ?? 'Non spécifié') ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bmc-card h-100">
                            <h6>Structure des coûts</h6>
                            <p class="text-muted"><?= htmlspecialchars($bmc_blocks['structure_couts'] ?? 'Non spécifié') ?></p>
                        </div>
                    </div>
                </div>

                <h5 class="mt-4">Hypothèses Validées</h5>
                <?php if (empty($hypotheses)): ?>
                    <p class="text-muted">Aucune hypothèse validée pour ce projet.</p>
                <?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($hypotheses as $hypothesis): ?>
                            <li class="list-group-item"><?= htmlspecialchars($hypothesis['hypothesis_text']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <h5 class="mt-4">Données Financières</h5>
                <?php if ($financial_data): ?>
                    <table class="table table-bordered">
                        <tr><th>Revenus Mensuels Estimés ($)</th><td><?= number_format($financial_data['revenues'], 2) ?></td></tr>
                        <tr><th>Coûts Fixes Mensuels ($)</th><td><?= number_format($financial_data['fixed_costs'], 2) ?></td></tr>
                        <tr><th>Coûts Variables Mensuels ($)</th><td><?= number_format($financial_data['variable_costs'], 2) ?></td></tr>
                        <tr><th>Prix de Vente Unitaire ($)</th><td><?= number_format($financial_data['unit_price'], 2) ?></td></tr>
                        <tr><th>Coût Variable Unitaire ($)</th><td><?= number_format($financial_data['unit_variable_cost'], 2) ?></td></tr>
                    </table>
                <?php else: ?>
                    <p class="text-muted">Aucune donnée financière disponible.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Boutons d'action -->
        <div class="action-buttons my-5">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="d-flex flex-column flex-md-row justify-content-center align-items-center gap-3">
                        <a href="download_bmp_summary_pdf.php?project_id=<?= $project_id ?>" class="btn btn-primary w-100 w-md-auto px-4 py-2 action-btn" style="min-width: 200px; height: 48px; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-file-earmark-pdf me-2"></i> Exporter en PDF
                        </a>
                        <a href="bmp_summary.php?project_id=<?= $project_id ?>&generate_cta=1" class="btn btn-primary w-100 w-md-auto px-4 py-2 action-btn" id="generate-cta-btn" style="min-width: 200px; height: 48px; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-megaphone me-2"></i> Générer un Appel à Action
                        </a>
                        <a href="financial_plan.php?project_id=<?= $project_id ?>" class="btn btn-outline-primary w-100 w-md-auto px-4 py-2 action-btn" style="min-width: 200px; height: 48px; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-arrow-left me-2"></i> Retour au Plan Financier
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Afficher l'appel à action généré -->
        <?php if (!empty($call_to_action)): ?>
            <div class="cta-section text-center mt-5">
                <h4 class="text-primary mb-4"><i class="bi bi-megaphone me-2"></i> Appel à Action</h4>
                <div class="alert alert-info">
                    <p class="lead"><?= htmlspecialchars($call_to_action) ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../layouts/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>