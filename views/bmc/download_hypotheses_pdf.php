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

    // Récupérer les hypothèses
    $stmt = $pdo->prepare("
        SELECT h.* 
        FROM hypotheses h 
        WHERE h.project_id = :project_id 
        ORDER BY h.created_at DESC
    ");
    $stmt->execute(['project_id' => $project_id]);
    $hypotheses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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

// Compter les hypothèses par statut
$total_hypotheses = count($hypotheses);
$validated_count = count(array_filter($hypotheses, fn($h) => $h['status'] === 'confirmed'));
$invalidated_count = count(array_filter($hypotheses, fn($h) => $h['status'] === 'rejected'));
$in_progress_count = count(array_filter($hypotheses, fn($h) => $h['status'] === 'in_progress'));

// Créer le contenu HTML pour le PDF
$html = '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Hypothèses - ' . htmlspecialchars($project['name']) . '</title>
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

        <!-- Section Statistiques -->
        <div class="section">
            <h3 class="section-title">📊 Statistiques des Hypothèses</h3>
            <div class="grid grid-3">
                <div class="card">
                    <div class="card-title">📝 Total</div>
                    <div class="card-content">
                        <strong>' . $total_hypotheses . '</strong> hypothèses
                    </div>
                </div>
                
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
            </div>
        </div>

        <!-- Section Liste des Hypothèses -->
        <div class="section">
            <h3 class="section-title">🔍 Détail des Hypothèses</h3>';

if (empty($hypotheses)) {
    $html .= '
            <div class="card">
                <div class="card-content text-center">
                    <p>Aucune hypothèse n\'a encore été créée pour ce projet.</p>
                    <p>Commencez par créer des hypothèses testables pour valider votre Business Model Canvas.</p>
                </div>
            </div>';
} else {
    foreach ($hypotheses as $index => $hypothesis) {
        $hypothesis_text = $hypothesis['hypothesis_text'] ?? $hypothesis['content'] ?? $hypothesis['title'] ?? 'Aucun contenu';
        $html .= '
            <div class="card">
                <div class="card-title">
                    💡 Hypothèse #' . ($index + 1) . '
                    ' . getStatusBadge($hypothesis['status']) . '
                </div>
                <div class="card-content">
                    <div class="grid grid-2">
                        <div>
                            <strong>Hypothèse :</strong><br>
                            ' . nl2br(htmlspecialchars($hypothesis_text)) . '
                        </div>
                        <div>
                            <strong>Créée le :</strong> ' . date('d/m/Y à H:i', strtotime($hypothesis['created_at'])) . '
                        </div>
                    </div>';
        
        if (!empty($hypothesis['test_plan'])) {
            $html .= '
                    <div style="margin-top: 10px;">
                        <strong>Plan de test :</strong><br>
                        ' . nl2br(htmlspecialchars($hypothesis['test_plan'])) . '
                    </div>';
        }
        
        $html .= '
                </div>
            </div>';
    }
}

$html .= '
        </div>

        <!-- Section Conseils -->
        <div class="section">
            <h3 class="section-title">💡 Conseils pour Tester vos Hypothèses</h3>
            <div class="card">
                <div class="card-content">
                    <p><strong>1. Commencez par les hypothèses critiques :</strong> Testez d\'abord les hypothèses qui ont le plus d\'impact sur votre modèle.</p>
                    <p><strong>2. Utilisez des méthodes simples :</strong> Interviews, sondages, prototypes rapides sont souvent plus efficaces que des études complexes.</p>
                    <p><strong>3. Documentez vos résultats :</strong> Notez précisément ce que vous apprenez de chaque test.</p>
                    <p><strong>4. Itérez rapidement :</strong> Modifiez vos hypothèses en fonction des résultats et retestez.</p>
                    <p><strong>5. Restez objectif :</strong> Acceptez les résultats même s\'ils contredisent vos attentes initiales.</p>
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
$filename = $pdf_template->generateFileName('hypotheses', $project_id);

// Télécharger le PDF
$dompdf->stream($filename, [
    'Attachment' => true,
    'Content-Type' => 'application/pdf'
]);

exit();
?>