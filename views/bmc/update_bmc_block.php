<?php
session_start();
require_once '../../includes/db_connect.php';

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Créer un fichier de log pour déboguer
$log_file = __DIR__ . '/update_bmc_block.log';
function log_message($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

log_message("Début de update_bmc_block.php");

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    $response['message'] = "Vous devez être connecté pour effectuer cette action.";
    log_message("Erreur : Utilisateur non connecté.");
    echo json_encode($response);
    exit();
}

log_message("Utilisateur connecté : " . $_SESSION['user_id']);

// Vérifier les données POST
if (!isset($_POST['project_id']) || !isset($_POST['block_name']) || !isset($_POST['content'])) {
    $response['message'] = "Données manquantes.";
    log_message("Erreur : Données manquantes - " . json_encode($_POST));
    echo json_encode($response);
    exit();
}

$project_id = filter_var($_POST['project_id'], FILTER_VALIDATE_INT);
$block_name = trim($_POST['block_name']);
$content = trim($_POST['content']);

log_message("Données reçues - project_id: $project_id, block_name: $block_name, content: $content");

if (!$project_id || empty($block_name) || empty($content)) {
    $response['message'] = "Données invalides.";
    log_message("Erreur : Données invalides - project_id: $project_id, block_name: $block_name, content: $content");
    echo json_encode($response);
    exit();
}

// Vérifier que le projet appartient à l'utilisateur
try {
    $stmt = $pdo->prepare("SELECT user_id FROM projects WHERE id = :project_id");
    $stmt->execute(['project_id' => $project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project || $project['user_id'] != $_SESSION['user_id']) {
        $response['message'] = "Projet non trouvé ou accès non autorisé.";
        log_message("Erreur : Projet non trouvé ou accès non autorisé - project_id: $project_id, user_id: " . $_SESSION['user_id']);
        echo json_encode($response);
        exit();
    }

    log_message("Projet vérifié - appartient à l'utilisateur : " . $_SESSION['user_id']);

    // Mettre à jour le bloc dans la table `bmc`
    $stmt = $pdo->prepare("UPDATE bmc SET content = :content WHERE project_id = :project_id AND block_name = :block_name");
    $stmt->execute([
        'content' => $content,
        'project_id' => $project_id,
        'block_name' => $block_name
    ]);

    $rowCount = $stmt->rowCount();
    log_message("Requête UPDATE exécutée - Lignes affectées : $rowCount");

    if ($rowCount > 0) {
        $response['success'] = true;
        $response['message'] = "Bloc mis à jour avec succès.";
        log_message("Succès : Bloc mis à jour - block_name: $block_name");
    } else {
        $response['message'] = "Aucune modification effectuée ou bloc non trouvé.";
        log_message("Erreur : Aucune modification effectuée ou bloc non trouvé - block_name: $block_name");
    }
} catch (PDOException $e) {
    $response['message'] = "Erreur lors de la mise à jour : " . $e->getMessage();
    log_message("Erreur PDO : " . $e->getMessage());
}

echo json_encode($response);
log_message("Fin de update_bmc_block.php - Réponse : " . json_encode($response));
exit();