<?php
session_start();

require_once '../../includes/db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit();
}

// Vérifier les données fournies
if (!isset($_POST['hypothesis_text']) || empty(trim($_POST['hypothesis_text'])) || !isset($_POST['project_id']) || !is_numeric($_POST['project_id'])) {
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit();
}

$hypothesis_text = htmlspecialchars(trim($_POST['hypothesis_text']), ENT_QUOTES, 'UTF-8');
$project_id = (int)$_POST['project_id'];
$user_id = $_SESSION['user_id'];

try {
    // Vérifier si le projet appartient à l'utilisateur
    $stmt = $pdo->prepare("SELECT id FROM projects WHERE id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $project_id, 'user_id' => $user_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Projet non trouvé ou accès non autorisé']);
        exit();
    }

    // Ajouter l'hypothèse
    $stmt = $pdo->prepare("INSERT INTO hypotheses (project_id, hypothesis_text, status) VALUES (:project_id, :hypothesis_text, 'pending')");
    $stmt->execute([
        'project_id' => $project_id,
        'hypothesis_text' => $hypothesis_text
    ]);

    // Récupérer l'ID de la nouvelle hypothèse
    $new_hypothesis_id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Hypothèse ajoutée avec succès',
        'hypothesis_id' => $new_hypothesis_id,
        'hypothesis_text' => $hypothesis_text,
        'status' => 'pending'
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout de l\'hypothèse : ' . $e->getMessage()]);
}

exit();