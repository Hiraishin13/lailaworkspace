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

// Charger les données financières existantes
$financial_data = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM financial_plans WHERE project_id = :project_id");
    $stmt->execute(['project_id' => $project_id]);
    $financial_data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des données financières : " . $e->getMessage();
    header('Location: financial_plan.php?project_id=' . $project_id);
    exit();
}

// Générer les prévisions financières
$forecast_data = [];
$break_even_point = null;
$feasibility_kpis = [];
if ($financial_data) {
    $revenues = (float)$financial_data['revenues'];
    $fixed_costs = (float)$financial_data['fixed_costs'];
    $variable_costs = (float)$financial_data['variable_costs'];
    $unit_price = (float)$financial_data['unit_price'];
    $unit_variable_cost = (float)$financial_data['unit_variable_cost'];

    // Prévisions sur 12 mois
    for ($month = 1; $month <= 12; $month++) {
        $forecast_data[] = [
            'month' => "Mois $month",
            'revenues' => $revenues * $month,
            'total_costs' => ($fixed_costs * $month) + ($variable_costs * $month),
            'profit' => ($revenues * $month) - (($fixed_costs * $month) + ($variable_costs * $month))
        ];
    }

    // Calcul du seuil de rentabilité
    if ($unit_price > $unit_variable_cost && $fixed_costs > 0) {
        $contribution_margin = $unit_price - $unit_variable_cost;
        $break_even_point = $fixed_costs / $contribution_margin;
    }

    // Analyse de faisabilité (calcul des KPI)
    $gross_margin = $revenues > 0 ? (($revenues - $variable_costs) / $revenues) * 100 : 0;
    $monthly_growth_rate = $revenues > 0 ? ($revenues / 12) / $revenues * 100 : 0;
    $time_to_break_even = $break_even_point && $unit_price > 0 ? ceil($break_even_point / ($revenues / $unit_price)) : null;

    $feasibility_kpis = [
        'gross_margin' => $gross_margin,
        'monthly_growth_rate' => $monthly_growth_rate,
        'time_to_break_even' => $time_to_break_even
    ];
}

// Initialiser TCPDF
$tcpdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Définir les métadonnées du document
$tcpdf->SetCreator(PDF_CREATOR);
$tcpdf->SetAuthor($_SESSION['user_name'] ?? 'Laila Workspace');
$tcpdf->SetTitle('Plan Financier - ' . $project['name']);
$tcpdf->SetSubject('Plan Financier du Projet');
$tcpdf->SetKeywords('Plan Financier, Prévisions, Seuil de Rentabilité, Faisabilité');

// Définir les marges
$tcpdf->SetMargins(20, 20, 20);
$tcpdf->SetHeaderMargin(0);
$tcpdf->SetFooterMargin(10);

// Désactiver l'en-tête et le pied de page par défaut de TCPDF
$tcpdf->setPrintHeader(false);
$tcpdf->setPrintFooter(false);

// Ajouter une page
$tcpdf->AddPage();

// Créer le contenu HTML avec le même design que download_bmc_pdf.php
$html = '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Plan Financier - Laila Workspace</title>
    <style>
        body { 
            font-family: Helvetica, sans-serif; 
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
        .financial-card { 
            border: 1px solid #ddd; 
            padding: 15px; 
            margin-bottom: 10px; 
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
        <h2>Plan Financier - ' . htmlspecialchars($project['name']) . '</h2>
        <h4>Description du Projet</h4>
        <p class="text-muted">' . htmlspecialchars($project['description']) . '</p>

        <h4>Données Financières</h4>
        <div class="financial-card">
            <table>
                <tr><td><strong>Revenus Mensuels Estimés (€)</strong></td><td>' . number_format($financial_data['revenues'] ?? 0, 2) . '</td></tr>
                <tr><td><strong>Coûts Fixes Mensuels (€)</strong></td><td>' . number_format($financial_data['fixed_costs'] ?? 0, 2) . '</td></tr>
                <tr><td><strong>Coûts Variables Mensuels (€)</strong></td><td>' . number_format($financial_data['variable_costs'] ?? 0, 2) . '</td></tr>
                <tr><td><strong>Prix de Vente Unitaire (€)</strong></td><td>' . number_format($financial_data['unit_price'] ?? 0, 2) . '</td></tr>
                <tr><td><strong>Coût Variable Unitaire (€)</strong></td><td>' . number_format($financial_data['unit_variable_cost'] ?? 0, 2) . '</td></tr>';
if ($financial_data && $financial_data['uploaded_file_path']) {
    $html .= '<tr><td><strong>Fichier Uploadé</strong></td><td>' . htmlspecialchars(basename($financial_data['uploaded_file_path'])) . '</td></tr>';
}
$html .= '</table>
        </div>';

if (!empty($forecast_data)) {
    $html .= '
        <h4>Prévisions Financières (12 mois)</h4>
        <div class="financial-card">
            <table>
                <tr>
                    <th>Mois</th>
                    <th>Revenus (€)</th>
                    <th>Coûts Totaux (€)</th>
                    <th>Bénéfice (€)</th>
                </tr>';
    foreach ($forecast_data as $data) {
        $html .= '
                <tr>
                    <td>' . $data['month'] . '</td>
                    <td>' . number_format($data['revenues'], 2) . '</td>
                    <td>' . number_format($data['total_costs'], 2) . '</td>
                    <td>' . number_format($data['profit'], 2) . '</td>
                </tr>';
    }
    $html .= '</table>
        </div>';
}

$html .= '
        <h4>Seuil de Rentabilité</h4>
        <div class="financial-card">';
if ($break_even_point !== null) {
    $html .= '<p>Vous devez vendre ' . round($break_even_point) . ' unités pour atteindre le seuil de rentabilité.</p>';
} else {
    $html .= '<p class="text-muted">Impossible de calculer le seuil de rentabilité. Vérifiez que le prix de vente unitaire est supérieur au coût variable unitaire et que les coûts fixes sont définis.</p>';
}
$html .= '</div>';

if (!empty($feasibility_kpis)) {
    $html .= '
        <h4>Analyse de Faisabilité</h4>
        <div class="financial-card">
            <table>
                <tr><td><strong>Marge Brute</strong></td><td>' . round($feasibility_kpis['gross_margin'], 2) . '%</td><td class="text-muted">Une marge brute supérieure à 30% est généralement un bon indicateur.</td></tr>
                <tr><td><strong>Taux de Croissance Mensuel</strong></td><td>' . round($feasibility_kpis['monthly_growth_rate'], 2) . '%</td><td class="text-muted">Un taux de croissance supérieur à 5% est un bon signe.</td></tr>
                <tr><td><strong>Temps pour Atteindre le Seuil</strong></td><td>' . ($feasibility_kpis['time_to_break_even'] ? $feasibility_kpis['time_to_break_even'] . ' mois' : 'N/A') . '</td><td class="text-muted">Un délai inférieur à 12 mois est idéal.</td></tr>
            </table>
        </div>';
}

$html .= '
    </div>
    <div class="footer">
        Généré par Laila Workspace - ' . date('Y') . '
    </div>
</body>
</html>';

// Écrire le HTML dans le PDF
$tcpdf->writeHTML($html, true, false, true, false, '');

// Générer le PDF et le télécharger
$tcpdf->Output('Plan_Financier_' . $project['name'] . '.pdf', 'D');
exit();