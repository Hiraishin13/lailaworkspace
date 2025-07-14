<?php
session_start();

// Définir le chemin de base pour les inclusions
define('BASE_DIR', dirname(__DIR__, 2)); // Remonte de views/bmc/ à la racine du projet (laila_workspace)

require_once BASE_DIR . '/includes/db_connect.php';
require_once BASE_DIR . '/includes/config.php';
require_once BASE_DIR . '/vendor/autoload.php';

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
    header('Location: index.php');
    exit();
}

$project_id = (int)$_GET['project_id'];

// Récupérer les données du projet
try {
    $stmt = $pdo->prepare("SELECT name, description FROM projects WHERE id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $project_id, 'user_id' => $_SESSION['user_id']]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        $_SESSION['error'] = "Projet non trouvé ou accès non autorisé.";
        header('Location: index.php');
        exit();
    }

    // Récupérer les blocs du BMC
    $stmt = $pdo->prepare("SELECT block_name, content FROM bmc WHERE project_id = :project_id");
    $stmt->execute(['project_id' => $project_id]);
    $blocks = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des données : " . $e->getMessage();
    header('Location: index.php');
    exit();
}

// Créer le contenu HTML pour le PDF
$html = '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Business Model Canvas - Laila Workspace</title>
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
        .bmc-container { 
            margin: 20px 0; 
        }
        .bmc-card { 
            border: 1px solid #ddd; 
            padding: 15px; 
            margin-bottom: 10px; 
            text-align: center; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .text-muted { 
            color: #6c757d; 
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
        <h2>Votre Business Model Canvas</h2>
        <h4>Votre idée</h4>
        <p class="text-muted">' . htmlspecialchars($project['description']) . '</p>

        <div class="bmc-container">
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                <div style="flex: 1 1 30%;">
                    <div class="bmc-card">
                        <h5>Proposition de valeur</h5>
                        <p class="text-muted">' . htmlspecialchars($blocks['proposition_valeur'] ?? 'Non spécifié') . '</p>
                    </div>
                </div>
                <div style="flex: 1 1 30%;">
                    <div class="bmc-card">
                        <h5>Clients</h5>
                        <p class="text-muted">' . htmlspecialchars($blocks['segments_clientele'] ?? 'Non spécifié') . '</p>
                    </div>
                </div>
                <div style="flex: 1 1 30%;">
                    <div class="bmc-card">
                        <h5>Canaux</h5>
                        <p class="text-muted">' . htmlspecialchars($blocks['canaux'] ?? 'Non spécifié') . '</p>
                    </div>
                </div>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                <div style="flex: 1 1 30%;">
                    <div class="bmc-card">
                        <h5>Relations clients</h5>
                        <p class="text-muted">' . htmlspecialchars($blocks['relations_clients'] ?? 'Non spécifié') . '</p>
                    </div>
                </div>
                <div style="flex: 1 1 30%;">
                    <div class="bmc-card">
                        <h5>Activité</h5>
                        <p class="text-muted">' . htmlspecialchars($blocks['activites_cles'] ?? 'Non spécifié') . '</p>
                    </div>
                </div>
                <div style="flex: 1 1 30%;">
                    <div class="bmc-card">
                        <h5>Ressources</h5>
                        <p class="text-muted">' . htmlspecialchars($blocks['ressources_cles'] ?? 'Non spécifié') . '</p>
                    </div>
                </div>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                <div style="flex: 1 1 30%;">
                    <div class="bmc-card">
                        <h5>Partenaires</h5>
                        <p class="text-muted">' . htmlspecialchars($blocks['partenaires_cles'] ?? 'Non spécifié') . '</p>
                    </div>
                </div>
                <div style="flex: 1 1 30%;">
                    <div class="bmc-card">
                        <h5>Structure de coûts</h5>
                        <p class="text-muted">' . htmlspecialchars($blocks['structure_couts'] ?? 'Non spécifié') . '</p>
                    </div>
                </div>
                <div style="flex: 1 1 30%;">
                    <div class="bmc-card">
                        <h5>Structure de pricing</h5>
                        <p class="text-muted">' . htmlspecialchars($blocks['sources_revenus'] ?? 'Non spécifié') . '</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="footer">
        Généré par Laila Workspace - ' . date('Y') . '
    </div>
</body>
</html>
';

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
$dompdf->stream('business_model_canvas_' . $project_id . '.pdf', ['Attachment' => true]);
exit();