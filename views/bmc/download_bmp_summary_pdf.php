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

    // Récupérer les données BMC (tous les blocs)
    $stmt = $pdo->prepare("SELECT block_name, content FROM bmc WHERE project_id = :project_id");
    $stmt->execute(['project_id' => $project_id]);
    $bmc_blocks = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Récupérer les hypothèses
    $stmt = $pdo->prepare("
        SELECT * FROM hypotheses
        WHERE project_id = :project_id
        ORDER BY created_at DESC
    ");
    $stmt->execute(['project_id' => $project_id]);
    $hypotheses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer le plan financier
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

// Fonction pour obtenir le statut en français avec badge
function getStatusBadge($status) {
    switch ($status) {
        case 'confirmed':
            return '<span class="badge badge-success">✅ Validée</span>';
        case 'rejected':
            return '<span class="badge badge-danger">❌ Invalidée</span>';
        case 'in_progress':
            return '<span class="badge badge-warning">🔄 En cours</span>';
        case 'pending':
            return '<span class="badge badge-info">⏳ En attente</span>';
        default:
            return '<span class="badge badge-info">⏳ En attente</span>';
    }
}

// Fonction pour formater les montants
function formatAmount($amount) {
    return number_format($amount, 2, ',', ' ') . ' €';
}

// Compter les hypothèses par statut
$total_hypotheses = count($hypotheses);
$validated_count = count(array_filter($hypotheses, fn($h) => $h['status'] === 'confirmed'));
$invalidated_count = count(array_filter($hypotheses, fn($h) => $h['status'] === 'rejected'));

// Liste officielle des blocs BMC (correspondant exactement aux noms dans la base)
$bmc_block_names = [
    'Segments de clientèle',
    'Proposition de valeur',
    'Canaux',
    'Relations clients',
    'Flux de revenus',
    'Ressources clés',
    'Activités clés',
    'Partenaires clés',
    'Structure de coûts',
    // Ajouter les variations possibles
    'Relation client',
    'Sources de revenus',
];

// Créer le contenu HTML pour le PDF
$html = '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Résumé BMP - ' . htmlspecialchars($project['name']) . '</title>
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
        </div>

        <!-- Section Vue d\'ensemble -->
        <div class="section">
            <h3 class="section-title">📊 Vue d\'ensemble du Projet</h3>
            <div class="grid grid-3">
                <div class="card">
                    <div class="card-title">📝 BMC</div>
                    <div class="card-content">
                        <strong>' . ($bmc_blocks && count($bmc_blocks) > 0 ? 'Complété' : 'Non créé') . '</strong>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-title">🔍 Hypothèses</div>
                    <div class="card-content">
                        <strong>' . $total_hypotheses . '</strong> créées
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-title">💰 Plan Financier</div>
                    <div class="card-content">
                        <strong>' . ($financial_plan ? 'Créé' : 'Non créé') . '</strong>
                    </div>
                </div>
            </div>
        </div>';

if ($bmc_blocks && count($bmc_blocks) > 0) {
    $html .= '
        <!-- Section Business Model Canvas -->
        <div class="section">
            <h3 class="section-title">🎯 Business Model Canvas</h3>
            <div class="bmc-grid">';
    
    // Créer un mapping des noms de blocs pour gérer les variations
    $block_mapping = [
        'Segments de clientèle' => 'Segments de clientèle',
        'Proposition de valeur' => 'Proposition de valeur',
        'Canaux' => 'Canaux',
        'Relations clients' => 'Relations clients',
        'Relation client' => 'Relations clients', // Variation
        'Flux de revenus' => 'Flux de revenus',
        'Sources de revenus' => 'Flux de revenus', // Variation
        'Ressources clés' => 'Ressources clés',
        'Activités clés' => 'Activités clés',
        'Partenaires clés' => 'Partenaires clés',
        'Structure de coûts' => 'Structure de coûts',
    ];
    
    // Afficher chaque bloc standard
    $displayed_blocks = [];
    foreach ($bmc_block_names as $block_name) {
        if (in_array($block_name, ['Relation client', 'Sources de revenus'])) {
            continue; // Ignorer les variations, on les traite avec les blocs principaux
        }
        
        $content = 'Non spécifié';
        // Chercher le contenu dans les blocs existants
        foreach ($bmc_blocks as $db_name => $db_content) {
            if ($db_name === $block_name || 
                (isset($block_mapping[$db_name]) && $block_mapping[$db_name] === $block_name)) {
                $content = $db_content;
                break;
            }
        }
        
        $html .= '
                <div class="bmc-block">
                    <div class="bmc-block-title">' . htmlspecialchars($block_name) . '</div>
                    <div class="bmc-block-content">' . nl2br(htmlspecialchars($content)) . '</div>
                </div>';
    }
    
    $html .= '
            </div>
        </div>';
}

if (!empty($hypotheses)) {
    $html .= '
        <!-- Section Résumé des Hypothèses -->
        <div class="section">
            <h3 class="section-title">🔍 Résumé des Hypothèses</h3>
            <div class="grid grid-3">
                <div class="card">
                    <div class="card-title">✅ Validées</div>
                    <div class="card-content">
                        <strong>' . $validated_count . '</strong> hypothèses
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-title">❌ Invalidées</div>
                    <div class="card-content">
                        <strong>' . $invalidated_count . '</strong> hypothèses
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-title">📊 Taux de Validation</div>
                    <div class="card-content">
                        <strong>' . ($total_hypotheses > 0 ? round(($validated_count / $total_hypotheses) * 100, 1) : 0) . '%</strong>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-title">📝 Hypothèses Principales</div>
                <div class="card-content">';
    
    // Afficher les 3 premières hypothèses
    $displayed_hypotheses = array_slice($hypotheses, 0, 3);
    foreach ($displayed_hypotheses as $index => $hypothesis) {
        $hypothesis_text = $hypothesis['hypothesis_text'] ?? $hypothesis['content'] ?? $hypothesis['title'] ?? 'Aucun contenu';
        $html .= '
                    <div style="margin-bottom: 10px; padding: 8px; background: #f8f9fa; border-radius: 4px;">
                        <strong>Hypothèse #' . ($index + 1) . '</strong> ' . getStatusBadge($hypothesis['status']) . '<br>
                        ' . htmlspecialchars(substr($hypothesis_text, 0, 150)) . 
                        (strlen($hypothesis_text) > 150 ? '...' : '') . '
                    </div>';
    }
    
    if (count($hypotheses) > 3) {
        $html .= '
                    <p class="text-muted">... et ' . (count($hypotheses) - 3) . ' autres hypothèses</p>';
    }
    
    $html .= '
                </div>
            </div>
        </div>';
}

if ($financial_plan) {
    $html .= '
        <!-- Section Résumé Financier -->
        <div class="section">
            <h3 class="section-title">💰 Résumé Financier</h3>
            <div class="grid grid-3">
                <div class="card">
                    <div class="card-title">📈 Revenus Totaux</div>
                    <div class="card-content">
                        <strong>' . formatAmount($financial_plan['total_revenue'] ?? 0) . '</strong>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-title">💸 Coûts Totaux</div>
                    <div class="card-content">
                        <strong>' . formatAmount($financial_plan['total_costs'] ?? 0) . '</strong>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-title">💎 Bénéfice Net</div>
                    <div class="card-content">
                        <strong>' . formatAmount(($financial_plan['total_revenue'] ?? 0) - ($financial_plan['total_costs'] ?? 0)) . '</strong>
                    </div>
                </div>
            </div>
        </div>';
}

$html .= '
        <!-- Section Recommandations -->
        <div class="section">
            <h3 class="section-title">💡 Recommandations</h3>
            <div class="card">
                <div class="card-content">';

if (!$bmc_blocks || count($bmc_blocks) === 0) {
    $html .= '
                    <p><strong>🎯 Priorité 1 :</strong> Complétez votre Business Model Canvas pour structurer votre réflexion.</p>';
}

if (empty($hypotheses)) {
    $html .= '
                    <p><strong>🔍 Priorité 2 :</strong> Créez des hypothèses testables pour valider votre modèle.</p>';
} elseif ($validated_count < 2) {
    $html .= '
                    <p><strong>🧪 Priorité 2 :</strong> Testez davantage vos hypothèses pour valider votre approche.</p>';
}

if (!$financial_plan) {
    $html .= '
                    <p><strong>💰 Priorité 3 :</strong> Développez votre plan financier pour évaluer la viabilité.</p>';
}

$html .= '
                    <p><strong>📈 Prochaines étapes :</strong> Continuez à itérer sur votre modèle en fonction des retours clients et des tests d\'hypothèses.</p>
                </div>
            </div>
        </div>

        <!-- Section Métriques de Progression -->
        <div class="section">
            <h3 class="section-title">📊 Métriques de Progression</h3>
            <div class="card">
                <div class="card-content">
                    <div class="grid grid-2">
                        <div>
                            <strong>Complétude du BMC :</strong> ' . ($bmc_blocks && count($bmc_blocks) > 0 ? '100%' : '0%') . '<br>
                            <strong>Hypothèses créées :</strong> ' . $total_hypotheses . '<br>
                            <strong>Plan financier :</strong> ' . ($financial_plan ? 'Complété' : 'À faire') . '
                        </div>
                        <div>
                            <strong>Progression globale :</strong> ' . 
                            round((($bmc_blocks && count($bmc_blocks) > 0 ? 1 : 0) + min($total_hypotheses / 5, 1) + ($financial_plan ? 1 : 0)) / 3 * 100, 1) . '%<br>
                            <strong>Dernière mise à jour :</strong> ' . date('d/m/Y à H:i', strtotime($project['created_at'])) . '
                        </div>
                    </div>
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
$filename = $pdf_template->generateFileName('summary', $project_id);

// Télécharger le PDF
$dompdf->stream($filename, [
    'Attachment' => true,
    'Content-Type' => 'application/pdf'
]);

exit();
?>