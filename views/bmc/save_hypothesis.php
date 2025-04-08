<?php
session_start();

// Définir le chemin de base pour les inclusions
define('BASE_DIR', dirname(__DIR__, 2)); // Remonte de views/bmc/ à la racine du projet (laila_workspace)

require_once BASE_DIR . '/includes/db_connect.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté.']);
    exit();
}

// Vérifier les données envoyées
if (!isset($_POST['project_id']) || !is_numeric($_POST['project_id']) || !isset($_POST['hypothesis_text']) || empty(trim($_POST['hypothesis_text']))) {
    echo json_encode(['success' => false, 'message' => 'Données invalides.']);
    exit();
}

$project_id = (int)$_POST['project_id'];
$hypothesis_text = htmlspecialchars(trim($_POST['hypothesis_text']), ENT_QUOTES, 'UTF-8');

// Vérifier si le projet appartient à l'utilisateur
try {
    $stmt = $pdo->prepare("SELECT id FROM projects WHERE id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $project_id, 'user_id' => $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Projet non trouvé ou accès non autorisé.']);
        exit();
    }

    // Enregistrer l'hypothèse
    $stmt = $pdo->prepare("INSERT INTO hypotheses (project_id, hypothesis_text) VALUES (:project_id, :hypothesis_text)");
    $stmt->execute([
        'project_id' => $project_id,
        'hypothesis_text' => $hypothesis_text
    ]);

    $hypothesis_id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'hypothesis_id' => $hypothesis_id,
        'hypothesis_text' => $hypothesis_text
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement : ' . $e->getMessage()]);
    exit();
}