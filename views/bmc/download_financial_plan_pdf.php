<?php
session_start();

// Définir le chemin de base pour les inclusions
define('BASE_DIR', dirname(__DIR__, 2));

require_once BASE_DIR . '/includes/db_connect.php';
require_once BASE_DIR . '/includes/config.php';
require_once BASE_DIR . '/vendor/autoload.php';
require_once __DIR__ . '/pdf_template.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Vérifier si un project_id est fourni
if (!isset($_GET['project_id']) || !is_numeric($_GET['project_id'])) {
    $_SESSION['error'] = "Projet non spécifié.";
    header('Location: ../../index.php');
    exit();
}

$project_id = (int)$_GET['project_id'];

// Récupérer les données du projet et de l'utilisateur
try {
    $stmt = $pdo->prepare("
        SELECT p.name, p.description, p.created_at, 
               u.first_name, u.last_name 
        FROM projects p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.id = :id AND p.user_id = :user_id
    ");
    $stmt->execute(['id' => $project_id, 'user_id' => $_SESSION['user_id']]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        $_SESSION['error'] = "Projet non trouvé ou accès non autorisé.";
        header('Location: ../../index.php');
        exit();
    }

    // Récupérer les données financières
    $stmt = $pdo->prepare("
        SELECT * FROM financial_plans 
        WHERE project_id = :project_id 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute(['project_id' => $project_id]);
    $financial_plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des données : " . $e->getMessage();
    header('Location: ../../index.php');
    exit();
}

// Initialiser le template PDF
$user_name = trim($project['first_name'] . ' ' . $project['last_name']);
$pdf_template = new PDFTemplate($project['name'], $user_name);

// Fonction pour formater les montants
function formatAmount($amount) {
    return number_format($amount, 2, ',', ' ') . ' €';
}

// Créer le contenu HTML pour le PDF
$html = '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Plan Financier - ' . htmlspecialchars($project['name']) . '</title>
    ' . $pdf_template->getStyles() . '
</head>
<body>
    ' . $pdf_template->getHeader() . '
    
    <div class="container">
        <!-- Section Description du Projet -->
        <div class="section">
            <h3 class="section-title">📋 Description du Projet</h3>
            <div class="card">
                <div class="card-content">
                    ' . nl2br(htmlspecialchars($project['description'])) . '
                </div>
            </div>
        </div>';

if ($financial_plan) {
    $html .= '
        <!-- Section Résumé Financier -->
        <div class="section">
            <h3 class="section-title">💰 Résumé Financier</h3>
            <div class="grid grid-3">
                <div class="card">
                    <div class="card-title">📈 Revenus Annuels</div>
                    <div class="card-content">
                        <strong>' . formatAmount($financial_plan['annual_revenue'] ?? 0) . '</strong>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-title">💸 Coûts Annuels</div>
                    <div class="card-content">
                        <strong>' . formatAmount($financial_plan['annual_costs'] ?? 0) . '</strong>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-title">💎 Bénéfice Net</div>
                    <div class="card-content">
                        <strong>' . formatAmount(($financial_plan['annual_revenue'] ?? 0) - ($financial_plan['annual_costs'] ?? 0)) . '</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Détail des Revenus -->
        <div class="section">
            <h3 class="section-title">📊 Sources de Revenus</h3>
            <div class="card">
                <div class="card-content">
                    ' . nl2br(htmlspecialchars($financial_plan['revenue_sources'] ?? 'Non spécifié')) . '
                </div>
            </div>
        </div>

        <!-- Section Détail des Coûts -->
        <div class="section">
            <h3 class="section-title">💳 Structure des Coûts</h3>
            <div class="card">
                <div class="card-content">
                    ' . nl2br(htmlspecialchars($financial_plan['cost_structure'] ?? 'Non spécifié')) . '
                </div>
            </div>
        </div>

        <!-- Section Stratégie de Prix -->
        <div class="section">
            <h3 class="section-title">🏷️ Stratégie de Prix</h3>
            <div class="card">
                <div class="card-content">
                    ' . nl2br(htmlspecialchars($financial_plan['pricing_strategy'] ?? 'Non spécifié')) . '
                </div>
            </div>
        </div>

        <!-- Section Projections -->
        <div class="section">
            <h3 class="section-title">📈 Projections Financières</h3>
            <div class="card">
                <div class="card-content">
                    ' . nl2br(htmlspecialchars($financial_plan['financial_projections'] ?? 'Non spécifié')) . '
                </div>
            </div>
        </div>

        <!-- Section Besoins de Financement -->
        <div class="section">
            <h3 class="section-title">💼 Besoins de Financement</h3>
            <div class="card">
                <div class="card-content">
                    ' . nl2br(htmlspecialchars($financial_plan['funding_needs'] ?? 'Non spécifié')) . '
                </div>
            </div>
        </div>

        <!-- Section Métriques Clés -->
        <div class="section">
            <h3 class="section-title">🎯 Métriques Clés</h3>
            <div class="grid grid-2">
                <div class="card">
                    <div class="card-title">📊 Marge Brute</div>
                    <div class="card-content">
                        ' . ($financial_plan['gross_margin'] ?? 'Non spécifié') . '
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-title">⏱️ Point d\'Équilibre</div>
                    <div class="card-content">
                        ' . ($financial_plan['break_even_point'] ?? 'Non spécifié') . '
                    </div>
                </div>
            </div>
        </div>';
} else {
    $html .= '
        <!-- Section Aucun Plan Financier -->
        <div class="section">
            <h3 class="section-title">💰 Plan Financier</h3>
            <div class="card">
                <div class="card-content text-center">
                    <p>Aucun plan financier n\'a encore été créé pour ce projet.</p>
                    <p>Créez votre premier plan financier pour analyser la viabilité économique de votre projet.</p>
                </div>
            </div>
        </div>';
}

$html .= '
        <!-- Section Conseils -->
        <div class="section">
            <h3 class="section-title">💡 Conseils pour un Plan Financier Solide</h3>
            <div class="card">
                <div class="card-content">
                    <p><strong>1. Soyez réaliste :</strong> Basez vos projections sur des données de marché et des hypothèses conservatrices.</p>
                    <p><strong>2. Identifiez tous les coûts :</strong> N\'oubliez pas les coûts cachés (marketing, support, maintenance).</p>
                    <p><strong>3. Diversifiez vos revenus :</strong> Ne misez pas tout sur une seule source de revenus.</p>
                    <p><strong>4. Planifiez plusieurs scénarios :</strong> Optimiste, réaliste et pessimiste.</p>
                    <p><strong>5. Surveillez vos métriques :</strong> Suivez régulièrement vos KPIs financiers.</p>
                </div>
            </div>
        </div>
    </div>
    
    ' . $pdf_template->getFooter() . '
</body>
</html>';

// Initialiser Dompdf avec des options optimisées
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->set('isPhpEnabled', true); // Activer PHP pour la pagination
$options->set('defaultFont', 'Arial');
$options->set('defaultPaperSize', 'A4');
$options->set('defaultPaperOrientation', 'portrait');
$options->set('enableCssFloat', true);
$options->set('enableJavascript', false);

$dompdf = new Dompdf($options);

// Charger le HTML dans Dompdf
$dompdf->loadHtml($html);

// Définir le format de la page
$dompdf->setPaper('A4', 'portrait');

// Rendre le PDF
$dompdf->render();

// Générer un nom de fichier propre
$filename = $pdf_template->generateFileName('financial', $project_id);

// Télécharger le PDF
$dompdf->stream($filename, [
    'Attachment' => true,
    'Content-Type' => 'application/pdf'
]);

exit();
?>