<?php
session_start();

// Définir le chemin de base pour les inclusions
define('BASE_DIR', dirname(__DIR__, 2));
require_once BASE_DIR . '/includes/db_connect.php';
require_once BASE_DIR . '/includes/config.php';
require_once BASE_DIR . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

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

// Charger les données du BMP
$hypotheses = [];
$financial_data = null;
$bmc_sections = [];
try {
    // Récupérer les hypothèses validées
    $stmt = $pdo->prepare("SELECT hypothesis_text, status FROM hypotheses WHERE project_id = :project_id AND status = 'confirmed'");
    $stmt->execute(['project_id' => $project_id]);
    $hypotheses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les données financières
    $stmt = $pdo->prepare("SELECT * FROM financial_plans WHERE project_id = :project_id");
    $stmt->execute(['project_id' => $project_id]);
    $financial_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Récupérer les sections du BMC depuis la table bmc (au lieu de bmc_sections)
    $stmt = $pdo->prepare("SELECT block_name, content FROM bmc WHERE project_id = :project_id");
    $stmt->execute(['project_id' => $project_id]);
    $bmc_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Mapper les noms des blocs de la table bmc aux clés attendues (traduire les clés françaises en anglais)
    $key_mapping = [
        'proposition_valeur' => 'value_propositions',
        'segments_clientele' => 'customer_segments',
        'canaux' => 'channels',
        'relations_clients' => 'customer_relationships',
        'activites_cles' => 'key_activities',
        'ressources_cles' => 'key_resources',
        'partenaires_cles' => 'key_partnerships',
        'structure_couts' => 'cost_structure',
        'sources_revenus' => 'revenue_streams'
    ];

    // Initialiser $bmc_sections avec les clés attendues
    $bmc_sections = [
        'key_partnerships' => 'Non spécifié',
        'key_resources' => 'Non spécifié',
        'customer_segments' => 'Non spécifié',
        'key_activities' => 'Non spécifié',
        'value_propositions' => 'Non spécifié',
        'channels' => 'Non spécifié',
        'cost_structure' => 'Non spécifié',
        'revenue_streams' => 'Non spécifié',
        'customer_relationships' => 'Non spécifié'
    ];

    // Remplir $bmc_sections avec les données de bmc en utilisant le mapping
    foreach ($key_mapping as $french_key => $english_key) {
        if (isset($bmc_data[$french_key])) {
            $bmc_sections[$english_key] = $bmc_data[$french_key];
        }
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des données du BMP : " . $e->getMessage();
    header('Location: bmp_summary.php?project_id=' . $project_id);
    exit();
}

// Créer le contenu HTML avec le design aligné sur download_bmc_pdf.php et download_hypotheses_pdf.php
$html = '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Récapitulatif du BMP - Laila Workspace</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0;
            padding: 0;
        }
        .header {
            background-color: #0d6efd;
            color: white;
            padding: 15px;
            text-align: center;
            border-bottom: 3px solid #0a58ca;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .container {
            padding: 20px;
        }
        h2 { 
            text-align: center; 
            color: #0d6efd; 
            margin-bottom: 20px;
        }
        h4 { 
            color: #0d6efd; 
            margin-bottom: 10px;
        }
        .text-muted { 
            color: #6c757d; 
        }
        .bmc-container, .hypothesis-container, .financial-container { 
            margin: 20px 0; 
        }
        .bmc-card, .hypothesis-card, .financial-card { 
            border: 1px solid #ddd; 
            padding: 15px; 
            margin-bottom: 10px; 
            text-align: left; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 10px; 
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 8px; 
            text-align: left; 
        }
        th { 
            background-color: #f8f9fa; 
            color: #0d6efd; 
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            padding: 10px 0;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laila Workspace</h1>
    </div>
    <div class="container">
        <h2>Récapitulatif du BMP - ' . htmlspecialchars($project['name']) . '</h2>
        <h4>Description du Projet</h4>
        <p class="text-muted">' . htmlspecialchars($project['description'] ?? 'Aucune description disponible.') . '</p>

        <h4>Business Model Canvas</h4>
        <div class="bmc-container">
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                <div style="flex: 1 1 30%;">
                    <div class="bmc-card">
                        <h5>Proposition de valeur</h5>
                        <p class="text-muted">' . htmlspecialchars($bmc_sections['value_propositions']) . '</p>
                    </div>
                </div>
                <div style="flex: 1 1 30%;">
                    <div class="bmc-card">
                        <h5>Segments de clientèle</h5>
                        <p class="text-muted">' . htmlspecialchars($bmc_sections['customer_segments']) . '</p>
                    </div>
                </div>
                <div style="flex: 1 1 30%;">
                    <div class="bmc-card">
                        <h5>Canaux</h5>
                        <p class="text-muted">' . htmlspecialchars($bmc_sections['channels']) . '</p>
                    </div>
                </div>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                <div style="flex: 1 1 30%;">
                    <div class="bmc-card">
                        <h5>Relations clients</h5>
                        <p class="text-muted">' . htmlspecialchars($bmc_sections['customer_relationships']) . '</p>
                    </div>
                </div>
                <div style="flex: 1 1 30%;">
                    <div class="bmc-card">
                        <h5>Activités clés</h5>
                        <p class="text-muted">' . htmlspecialchars($bmc_sections['key_activities']) . '</p>
                    </div>
                </div>
                <div style="flex: 1 1 30%;">
                    <div class="bmc-card">
                        <h5>Ressources clés</h5>
                        <p class="text-muted">' . htmlspecialchars($bmc_sections['key_resources']) . '</p>
                    </div>
                </div>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                <div style="flex: 1 1 30%;">
                    <div class="bmc-card">
                        <h5>Partenaires clés</h5>
                        <p class="text-muted">' . htmlspecialchars($bmc_sections['key_partnerships']) . '</p>
                    </div>
                </div>
                <div style="flex: 1 1 30%;">
                    <div class="bmc-card">
                        <h5>Structure des coûts</h5>
                        <p class="text-muted">' . htmlspecialchars($bmc_sections['cost_structure']) . '</p>
                    </div>
                </div>
                <div style="flex: 1 1 30%;">
                    <div class="bmc-card">
                        <h5>Sources de revenus</h5>
                        <p class="text-muted">' . htmlspecialchars($bmc_sections['revenue_streams']) . '</p>
                    </div>
                </div>
            </div>
        </div>

        <h4>Hypothèses Validées</h4>
        <div class="hypothesis-container">';
if (empty($hypotheses)) {
    $html .= '<p class="text-muted">Aucune hypothèse validée pour ce projet.</p>';
} else {
    $index = 1;
    foreach ($hypotheses as $hypothesis) {
        $html .= '
            <div class="hypothesis-card">
                <p><strong>' . $index . '.</strong> ' . htmlspecialchars($hypothesis['hypothesis_text'] ?? 'Hypothèse non spécifiée') . '</p>
            </div>';
        $index++;
    }
}

$html .= '
        </div>

        <h4>Données Financières</h4>
        <div class="financial-container">';
if ($financial_data) {
    $html .= '
        <div class="financial-card">
            <table>
                <tr><td><strong>Revenus Mensuels Estimés (€)</strong></td><td>' . number_format($financial_data['revenues'] ?? 0, 2) . '</td></tr>
                <tr><td><strong>Coûts Fixes Mensuels (€)</strong></td><td>' . number_format($financial_data['fixed_costs'] ?? 0, 2) . '</td></tr>
                <tr><td><strong>Coûts Variables Mensuels (€)</strong></td><td>' . number_format($financial_data['variable_costs'] ?? 0, 2) . '</td></tr>
                <tr><td><strong>Prix de Vente Unitaire (€)</strong></td><td>' . number_format($financial_data['unit_price'] ?? 0, 2) . '</td></tr>
                <tr><td><strong>Coût Variable Unitaire (€)</strong></td><td>' . number_format($financial_data['unit_variable_cost'] ?? 0, 2) . '</td></tr>
            </table>
        </div>';
} else {
    $html .= '<p class="text-muted">Aucune donnée financière disponible.</p>';
}

$html .= '
        </div>
    </div>
    <div class="footer">
        Généré par Laila Workspace - ' . date('Y') . '
    </div>
</body>
</html>';

// Initialiser Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$dompdf = new Dompdf($options);

// Charger le HTML dans Dompdf
$dompdf->loadHtml($html);

// Définir le format de la page (A4, portrait)
$dompdf->setPaper('A4', 'portrait');

// Rendre le PDF
$dompdf->render();

// Télécharger le PDF
$project_name = preg_replace('/[^A-Za-z0-9\-]/', '_', $project['name']);
$file_name = 'BMP_Summary_' . $project_name . '_' . date('Ymd') . '.pdf';
$dompdf->stream($file_name, ['Attachment' => true]);
exit();
?>