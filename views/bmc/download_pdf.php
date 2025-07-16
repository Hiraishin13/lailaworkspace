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

    // Récupérer les blocs du BMC
    $stmt = $pdo->prepare("SELECT block_name, content FROM bmc WHERE project_id = :project_id");
    $stmt->execute(['project_id' => $project_id]);
    $blocks = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des données : " . $e->getMessage();
    header('Location: ../../index.php');
    exit();
}

// Initialiser le template PDF
$user_name = trim($project['first_name'] . ' ' . $project['last_name']);
$pdf_template = new PDFTemplate($project['name'], $user_name);

// Créer le contenu HTML pour le PDF
$html = '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Business Model Canvas - ' . htmlspecialchars($project['name']) . '</title>
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

        <!-- Section Business Model Canvas -->
        <div class="section">
            <h3 class="section-title">🎯 Business Model Canvas</h3>
            
            <div class="bmc-grid">
                <!-- Première ligne -->
                <div class="bmc-block">
                    <div class="bmc-block-title">💡 Proposition de Valeur</div>
                    <div class="bmc-block-content">
                        ' . nl2br(htmlspecialchars($blocks['proposition_valeur'] ?? 'Non spécifié')) . '
                    </div>
                </div>
                
                <div class="bmc-block">
                    <div class="bmc-block-title">👥 Segments Clientèle</div>
                    <div class="bmc-block-content">
                        ' . nl2br(htmlspecialchars($blocks['segments_clientele'] ?? 'Non spécifié')) . '
                    </div>
                </div>
                
                <div class="bmc-block">
                    <div class="bmc-block-title">📢 Canaux</div>
                    <div class="bmc-block-content">
                        ' . nl2br(htmlspecialchars($blocks['canaux'] ?? 'Non spécifié')) . '
                    </div>
                </div>
                
                <!-- Deuxième ligne -->
                <div class="bmc-block">
                    <div class="bmc-block-title">🤝 Relations Clients</div>
                    <div class="bmc-block-content">
                        ' . nl2br(htmlspecialchars($blocks['relations_clients'] ?? 'Non spécifié')) . '
                    </div>
                </div>
                
                <div class="bmc-block">
                    <div class="bmc-block-title">⚙️ Activités Clés</div>
                    <div class="bmc-block-content">
                        ' . nl2br(htmlspecialchars($blocks['activites_cles'] ?? 'Non spécifié')) . '
                    </div>
                </div>
                
                <div class="bmc-block">
                    <div class="bmc-block-title">🔧 Ressources Clés</div>
                    <div class="bmc-block-content">
                        ' . nl2br(htmlspecialchars($blocks['ressources_cles'] ?? 'Non spécifié')) . '
                    </div>
                </div>
                
                <!-- Troisième ligne -->
                <div class="bmc-block">
                    <div class="bmc-block-title">🤲 Partenaires Clés</div>
                    <div class="bmc-block-content">
                        ' . nl2br(htmlspecialchars($blocks['partenaires_cles'] ?? 'Non spécifié')) . '
                    </div>
                </div>
                
                <div class="bmc-block">
                    <div class="bmc-block-title">💰 Structure de Coûts</div>
                    <div class="bmc-block-content">
                        ' . nl2br(htmlspecialchars($blocks['structure_couts'] ?? 'Non spécifié')) . '
                    </div>
                </div>
                
                <div class="bmc-block">
                    <div class="bmc-block-title">💎 Sources de Revenus</div>
                    <div class="bmc-block-content">
                        ' . nl2br(htmlspecialchars($blocks['sources_revenus'] ?? 'Non spécifié')) . '
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Informations du Projet -->
        <div class="section">
            <h3 class="section-title">📊 Informations du Projet</h3>
            <div class="grid grid-2">
                <div class="card">
                    <div class="card-title">📅 Date de Création</div>
                    <div class="card-content">
                        ' . date('d/m/Y à H:i', strtotime($project['created_at'])) . '
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-title">👤 Créateur</div>
                    <div class="card-content">
                        ' . htmlspecialchars($user_name) . '
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Conseils -->
        <div class="section">
            <h3 class="section-title">💡 Conseils pour la Suite</h3>
            <div class="card">
                <div class="card-content">
                    <p><strong>1. Validez vos hypothèses :</strong> Testez chaque élément de votre BMC avec vos clients potentiels.</p>
                    <p><strong>2. Créez des hypothèses :</strong> Développez des hypothèses testables pour chaque bloc de votre BMC.</p>
                    <p><strong>3. Planifiez financièrement :</strong> Élaborez un plan financier détaillé basé sur votre structure de coûts et revenus.</p>
                    <p><strong>4. Identifiez des partenaires :</strong> Recherchez des partenaires stratégiques pour renforcer votre proposition de valeur.</p>
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
$filename = $pdf_template->generateFileName('bmc', $project_id);

// Télécharger le PDF
$dompdf->stream($filename, [
    'Attachment' => true,
    'Content-Type' => 'application/pdf'
]);

exit();
?>