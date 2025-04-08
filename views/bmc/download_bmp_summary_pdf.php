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

    // Récupérer les sections du BMC depuis la table bmc_sections
    $stmt = $pdo->prepare("SELECT block_name, content FROM bmc_sections WHERE project_id = :project_id");
    $stmt->execute(['project_id' => $project_id]);
    $bmc_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Mapper les noms des blocs pour correspondre à ceux utilisés dans le HTML
    $bmc_sections = [
        'key_partnerships' => $bmc_data['key_partnerships'] ?? 'Non spécifié',
        'key_resources' => $bmc_data['key_resources'] ?? 'Non spécifié',
        'customer_segments' => $bmc_data['customer_segments'] ?? 'Non spécifié',
        'key_activities' => $bmc_data['key_activities'] ?? 'Non spécifié',
        'value_propositions' => $bmc_data['value_propositions'] ?? 'Non spécifié',
        'channels' => $bmc_data['channels'] ?? 'Non spécifié',
        'cost_structure' => $bmc_data['cost_structure'] ?? 'Non spécifié',
        'revenue_streams' => $bmc_data['revenue_streams'] ?? 'Non spécifié',
        'customer_relationships' => $bmc_data['customer_relationships'] ?? 'Non spécifié'
    ];
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des données du BMP : " . $e->getMessage();
    header('Location: bmp_summary.php?project_id=' . $project_id);
    exit();
}

// Initialiser TCPDF
$tcpdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Définir les métadonnées du document
$tcpdf->SetCreator(PDF_CREATOR);
$tcpdf->SetAuthor($_SESSION['user_name'] ?? 'Laila Workspace');
$tcpdf->SetTitle('Récapitulatif du BMP - ' . $project['name']);
$tcpdf->SetSubject('Résumé du Business Model Plan');
$tcpdf->SetKeywords('BMP, Résumé, Business Model');

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
    <title>Récapitulatif du BMP - Laila Workspace</title>
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
        .header img {
            max-height: 50px;
            margin-bottom: 10px;
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
        .bmc-card { 
            border: 1px solid #ddd; 
            padding: 15px; 
            margin-bottom: 10px; 
            text-align: center; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
        ul { 
            padding-left: 20px; 
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
        <!-- Si vous avez un logo, décommentez la ligne suivante et ajustez le chemin -->
        <!-- <img src="' . BASE_DIR . '/assets/images/logo.png" alt="Laila Workspace Logo"> -->
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
                        <h5>Partenaires Clés</h5>
                        <p class="text-muted">' . htmlspecialchars($bmc_sections['key_partnerships']) . '</p>
                    </div>
                </div>
                <div style="flex: 1 1 30%;">
                    <div class="bmc-card">
                        <h5>Ressources Clés</h5>
                        <p class="text-muted">' . htmlspecialchars($bmc_sections['key_resources']) . '</p>
                    </div>
                </div>
                <div style="flex: 1 1 30%;">
                    <div class="bmc-card">
                        <h5>Segments de Clientèle</h5>
                        <p class="text-muted">' . htmlspecialchars($bmc_sections['customer_segments']) . '</p>
                    </div>
                </div>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                <div style="flex: 1 1 30%;">
                    <div class="bmc-card">
                        <h5>Activités Clés</h5>
                        <p class="text-muted">' . htmlspecialchars($bmc_sections['key_activities']) . '</p>
                    </div>
                </div>
                <div style="flex: 1 1 30%;">
                    <div class="bmc-card">
                        <h5>Proposition de Valeur</h5>
                        <p class="text-muted">' . htmlspecialchars($bmc_sections['value_propositions']) . '</p>
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
                        <h5>Structure des Coûts</h5>
                        <p class="text-muted">' . htmlspecialchars($bmc_sections['cost_structure']) . '</p>
                    </div>
                </div>
                <div style="flex: 1 1 30%;">
                    <div class="bmc-card">
                        <h5>Sources de Revenus</h5>
                        <p class="text-muted">' . htmlspecialchars($bmc_sections['revenue_streams']) . '</p>
                    </div>
                </div>
                <div style="flex: 1 1 30%;">
                    <div class="bmc-card">
                        <h5>Relations Clients</h5>
                        <p class="text-muted">' . htmlspecialchars($bmc_sections['customer_relationships']) . '</p>
                    </div>
                </div>
            </div>
        </div>

        <h4>Hypothèses Validées</h4>';
if (empty($hypotheses)) {
    $html .= '<p class="text-muted">Aucune hypothèse validée pour ce projet.</p>';
} else {
    $html .= '<ul>';
    foreach ($hypotheses as $hypothesis) {
        $html .= '<li>' . htmlspecialchars($hypothesis['hypothesis_text'] ?? 'Hypothèse non spécifiée') . '</li>';
    }
    $html .= '</ul>';
}

$html .= '
        <h4>Données Financières</h4>';
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
    <div class="footer">
        Généré par Laila Workspace - ' . date('Y') . '
    </div>
</body>
</html>';

// Écrire le HTML dans le PDF
$tcpdf->writeHTML($html, true, false, true, false, '');

// Générer le PDF et le télécharger
$project_name = preg_replace('/[^A-Za-z0-9\-]/', '_', $project['name']);
$file_name = 'BMP_Summary_' . $project_name . '_' . date('Ymd') . '.pdf';
$tcpdf->Output($file_name, 'D');
exit();