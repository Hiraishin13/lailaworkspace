<?php
session_start();

// Définir le chemin de base pour les inclusions
define('BASE_DIR', dirname(__DIR__, 2));
require_once BASE_DIR . '/includes/db_connect.php';
require_once BASE_DIR . '/includes/config.php';

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

// Charger les données financières existantes
$financial_data = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM financial_plans WHERE project_id = :project_id");
    $stmt->execute(['project_id' => $project_id]);
    $financial_data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des données financières : " . $e->getMessage();
}

// Gestion de l'upload de fichier et des données financières
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_financial_data'])) {
    $revenues = isset($_POST['revenues']) ? (float)$_POST['revenues'] : 0.0;
    $fixed_costs = isset($_POST['fixed_costs']) ? (float)$_POST['fixed_costs'] : 0.0;
    $variable_costs = isset($_POST['variable_costs']) ? (float)$_POST['variable_costs'] : 0.0;
    $unit_price = isset($_POST['unit_price']) ? (float)$_POST['unit_price'] : 0.0;
    $unit_variable_cost = isset($_POST['unit_variable_cost']) ? (float)$_POST['unit_variable_cost'] : 0.0;
    $uploaded_file_path = null;

    // Gestion de l'upload de fichier
    if (isset($_FILES['financial_statement']) && $_FILES['financial_statement']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = BASE_DIR . '/uploads/financial_statements/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = $project_id . '_' . time() . '_' . basename($_FILES['financial_statement']['name']);
        $uploaded_file_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['financial_statement']['tmp_name'], $uploaded_file_path)) {
            $uploaded_file_path = '/uploads/financial_statements/' . $file_name;
        } else {
            $_SESSION['error'] = "Erreur lors de l'upload du fichier.";
            header("Location: financial_plan.php?project_id=$project_id");
            exit();
        }
    }

    try {
        if ($financial_data) {
            // Mise à jour des données existantes
            $stmt = $pdo->prepare("UPDATE financial_plans SET revenues = :revenues, fixed_costs = :fixed_costs, variable_costs = :variable_costs, unit_price = :unit_price, unit_variable_cost = :unit_variable_cost, uploaded_file_path = :uploaded_file_path WHERE project_id = :project_id");
        } else {
            // Insertion de nouvelles données
            $stmt = $pdo->prepare("INSERT INTO financial_plans (project_id, revenues, fixed_costs, variable_costs, unit_price, unit_variable_cost, uploaded_file_path) VALUES (:project_id, :revenues, :fixed_costs, :variable_costs, :unit_price, :unit_variable_cost, :uploaded_file_path)");
        }
        $stmt->execute([
            'project_id' => $project_id,
            'revenues' => $revenues,
            'fixed_costs' => $fixed_costs,
            'variable_costs' => $variable_costs,
            'unit_price' => $unit_price,
            'unit_variable_cost' => $unit_variable_cost,
            'uploaded_file_path' => $uploaded_file_path
        ]);
        $_SESSION['success'] = "Données financières enregistrées avec succès !";
        header("Location: financial_plan.php?project_id=$project_id");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de l'enregistrement des données financières : " . $e->getMessage();
        header("Location: financial_plan.php?project_id=$project_id");
        exit();
    }
}

// Générer les prévisions financières
$forecast_data = [];
$break_even_point = null;
$feasibility_kpis = [];
if ($financial_data && isset($_GET['generate_forecast']) && $_GET['generate_forecast'] == 1) {
    // Algorithme simple de prévision (projection linéaire sur 12 mois)
    $revenues = (float)$financial_data['revenues'];
    $fixed_costs = (float)$financial_data['fixed_costs'];
    $variable_costs = (float)$financial_data['variable_costs'];
    $unit_price = (float)$financial_data['unit_price'];
    $unit_variable_cost = (float)$financial_data['unit_variable_cost'];

    // Prévisions sur 12 mois
    for ($month = 1; $month <= 12; $month++) {
        $forecast_data[] = [
            'month' => "Mois $month",
            'revenues' => $revenues * $month, // Croissance linéaire
            'total_costs' => ($fixed_costs * $month) + ($variable_costs * $month),
            'profit' => ($revenues * $month) - (($fixed_costs * $month) + ($variable_costs * $month))
        ];
    }

    // Calcul du seuil de rentabilité
    if ($unit_price > $unit_variable_cost && $fixed_costs > 0) {
        $contribution_margin = $unit_price - $unit_variable_cost;
        $break_even_point = $fixed_costs / $contribution_margin; // En unités
    }

    // Analyse de faisabilité (calcul des KPI)
    $gross_margin = $revenues > 0 ? (($revenues - $variable_costs) / $revenues) * 100 : 0; // Marge brute en %
    $monthly_growth_rate = $revenues > 0 ? ($revenues / 12) / $revenues * 100 : 0; // Taux de croissance mensuel
    $time_to_break_even = $break_even_point && $unit_price > 0 ? ceil($break_even_point / ($revenues / $unit_price)) : null; // Temps pour atteindre le seuil (en mois)

    $feasibility_kpis = [
        'gross_margin' => $gross_margin,
        'monthly_growth_rate' => $monthly_growth_rate,
        'time_to_break_even' => $time_to_break_even
    ];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plan Financier - Laila Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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


        <h2 class="section-title text-center mb-5">Plan Financier - <?= htmlspecialchars($project['name']) ?></h2>

        <!-- Formulaire pour saisir les données financières -->
        <div class="financial-form-section">
            <h4 class="text-primary mb-4"><i class="bi bi-wallet2 me-2"></i> Saisir les Données Financières</h4>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="submit_financial_data" value="1">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="revenues" class="form-label">Revenus Mensuels Estimés ($)</label>
                        <input type="number" step="0.01" class="form-control" id="revenues" name="revenues" value="<?= $financial_data['revenues'] ?? 0 ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="fixed_costs" class="form-label">Coûts Fixes Mensuels ($)</label>
                        <input type="number" step="0.01" class="form-control" id="fixed_costs" name="fixed_costs" value="<?= $financial_data['fixed_costs'] ?? 0 ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="variable_costs" class="form-label">Coûts Variables Mensuels ($)</label>
                        <input type="number" step="0.01" class="form-control" id="variable_costs" name="variable_costs" value="<?= $financial_data['variable_costs'] ?? 0 ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="unit_price" class="form-label">Prix de Vente Unitaire ($)</label>
                        <input type="number" step="0.01" class="form-control" id="unit_price" name="unit_price" value="<?= $financial_data['unit_price'] ?? 0 ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="unit_variable_cost" class="form-label">Coût Variable Unitaire ($)</label>
                        <input type="number" step="0.01" class="form-control" id="unit_variable_cost" name="unit_variable_cost" value="<?= $financial_data['unit_variable_cost'] ?? 0 ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="financial_statement" class="form-label">Uploader un État Financier (PDF/Excel)</label>
                        <input type="file" class="form-control" id="financial_statement" name="financial_statement" accept=".pdf,.xlsx,.xls">
                        <?php if ($financial_data && $financial_data['uploaded_file_path']): ?>
                            <p class="mt-2"><a href="<?= BASE_URL . $financial_data['uploaded_file_path'] ?>" target="_blank">Voir le fichier uploadé</a></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary px-4 py-2">
                        <i class="bi bi-save me-2"></i> Enregistrer les Données
                    </button>
                </div>
            </form>
        </div>

        <!-- Boutons d'action -->
        <div class="action-buttons my-5">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="d-flex flex-column flex-md-row justify-content-center align-items-center gap-3">
                        <?php if ($financial_data): ?>
                            <a href="financial_plan.php?project_id=<?= $project_id ?>&generate_forecast=1" class="btn btn-primary w-100 w-md-auto px-4 py-2 action-btn" id="generate-forecast-btn" style="min-width: 200px; height: 48px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-graph-up me-2"></i> Prévisions Financières
                            </a>
                            <a href="download_financial_plan_pdf.php?project_id=<?= $project_id ?>" class="btn btn-primary w-100 w-md-auto px-4 py-2 action-btn" style="min-width: 200px; height: 48px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-file-earmark-pdf me-2"></i> Expoter en PDF
                            </a>
                            <a href="bmp_summary.php?project_id=<?= $project_id ?>" class="btn btn-success w-100 w-md-auto px-4 py-2 action-btn" style="min-width: 200px; height: 48px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-file-earmark-text me-2"></i> Voir le Récapitulatif du BMC
                            </a>
                        <?php endif; ?>
                        <a href="hypotheses.php?project_id=<?= $project_id ?>" class="btn btn-outline-primary w-100 w-md-auto px-4 py-2 action-btn" style="min-width: 200px; height: 48px; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-arrow-left me-2"></i> Retour aux Hypothèses
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Affichage des prévisions financières -->
        <?php if (!empty($forecast_data)): ?>
            <div class="forecast-section">
                <h4 class="text-primary mb-4"><i class="bi bi-bar-chart-line me-2"></i> Prévisions Financières (12 mois)</h4>
                <div class="row">
                    <div class="col-md-6">
                        <canvas id="forecastChart"></canvas>
                    </div>
                    <div class="col-md-6">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Mois</th>
                                        <th>Revenus ($)</th>
                                        <th>Coûts Totaux ($)</th>
                                        <th>Bénéfice ($)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($forecast_data as $data): ?>
                                        <tr>
                                            <td><?= $data['month'] ?></td>
                                            <td><?= number_format($data['revenues'], 2) ?></td>
                                            <td><?= number_format($data['total_costs'], 2) ?></td>
                                            <td class="<?= $data['profit'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                                <?= number_format($data['profit'], 2) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Seuil de rentabilité -->
            <div class="break-even-section mt-5">
                <h4 class="text-primary mb-4"><i class="bi bi-balance-scale me-2"></i> Seuil de Rentabilité</h4>
                <?php if ($break_even_point !== null): ?>
                    <p class="lead">Vous devez vendre <strong><?= round($break_even_point) ?> unités</strong> pour atteindre le seuil de rentabilité.</p>
                <?php else: ?>
                    <p class="text-muted">Impossible de calculer le seuil de rentabilité. Vérifiez que le prix de vente unitaire est supérieur au coût variable unitaire et que les coûts fixes sont définis.</p>
                <?php endif; ?>
            </div>

            <!-- Analyse de faisabilité -->
            <div class="feasibility-section mt-5">
                <h4 class="text-primary mb-4"><i class="bi bi-check2-circle me-2"></i> Analyse de Faisabilité</h4>
                <div class="row">
                    <div class="col-md-4">
                        <div class="kpi-card">
                            <h6>Marge Brute</h6>
                            <p class="kpi-value <?= $feasibility_kpis['gross_margin'] >= 30 ? 'text-success' : 'text-danger' ?>">
                                <?= round($feasibility_kpis['gross_margin'], 2) ?>%
                            </p>
                            <p class="text-muted">Une marge brute supérieure à 30% est généralement un bon indicateur.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="kpi-card">
                            <h6>Taux de Croissance Mensuel</h6>
                            <p class="kpi-value <?= $feasibility_kpis['monthly_growth_rate'] >= 5 ? 'text-success' : 'text-danger' ?>">
                                <?= round($feasibility_kpis['monthly_growth_rate'], 2) ?>%
                            </p>
                            <p class="text-muted">Un taux de croissance supérieur à 5% est un bon signe.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="kpi-card">
                            <h6>Temps pour Atteindre le Seuil</h6>
                            <p class="kpi-value <?= $feasibility_kpis['time_to_break_even'] && $feasibility_kpis['time_to_break_even'] <= 12 ? 'text-success' : 'text-danger' ?>">
                                <?= $feasibility_kpis['time_to_break_even'] ? $feasibility_kpis['time_to_break_even'] . ' mois' : 'N/A' ?>
                            </p>
                            <p class="text-muted">Un délai inférieur à 12 mois est idéal.</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../layouts/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Gestion du spinner global pour "Générer les prévisions"
            $('#generate-forecast-btn').on('click', function(e) {
                if (window.showGlobalSpinner) {
                    window.showGlobalSpinner('Génération des prévisions financières...');
                }
                $(this).addClass('disabled').prop('disabled', true);
            });

            // Générer le graphique des prévisions
            <?php if (!empty($forecast_data)): ?>
                const ctx = document.getElementById('forecastChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: [<?php echo "'" . implode("','", array_column($forecast_data, 'month')) . "'"; ?>],
                        datasets: [
                            {
                                label: 'Revenus ($)',
                                data: [<?php echo implode(',', array_column($forecast_data, 'revenues')); ?>],
                                borderColor: '#007bff',
                                fill: false
                            },
                            {
                                label: 'Coûts Totaux ($)',
                                data: [<?php echo implode(',', array_column($forecast_data, 'total_costs')); ?>],
                                borderColor: '#dc3545',
                                fill: false
                            },
                            {
                                label: 'Bénéfice ($)',
                                data: [<?php echo implode(',', array_column($forecast_data, 'profit')); ?>],
                                borderColor: '#28a745',
                                fill: false
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Montant (€)'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Mois'
                                }
                            }
                        }
                    }
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>