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

// Vérifier si le projet appartient à l'utilisateur et récupérer ses informations
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

// Récupérer les hypothèses du projet
try {
    $stmt = $pdo->prepare("SELECT hypothesis_text FROM hypotheses WHERE project_id = :project_id");
    $stmt->execute(['project_id' => $project_id]);
    $hypotheses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des hypothèses : " . $e->getMessage();
    header('Location: hypotheses.php?project_id=' . $project_id);
    exit();
}

// Créer le contenu HTML pour le PDF
$html = '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Hypothèses - Laila Workspace</title>
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
        .hypothesis-container { 
            margin: 20px 0; 
        }
        .hypothesis-card { 
            border: 1px solid #ddd; 
            padding: 15px; 
            margin-bottom: 10px; 
            text-align: left; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
        <h2>Hypothèses - ' . htmlspecialchars($project['name']) . '</h2>
        <h4>Description du Projet</h4>
        <p class="text-muted">' . htmlspecialchars($project['description']) . '</p>

        <div class="hypothesis-container">
            <h4>Liste des Hypothèses</h4>';

if (empty($hypotheses)) {
    $html .= '<p class="text-muted">Aucune hypothèse disponible pour ce projet.</p>';
} else {
    $index = 1;
    foreach ($hypotheses as $hypothesis) {
        $html .= '
            <div class="hypothesis-card">
                <p><strong>' . $index . '.</strong> ' . htmlspecialchars($hypothesis['hypothesis_text']) . '</p>
            </div>';
        $index++;
    }
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
$file_name = 'Hypotheses_' . $project_name . '_' . date('Ymd') . '.pdf';
$dompdf->stream($file_name, ['Attachment' => true]);
exit();
?>