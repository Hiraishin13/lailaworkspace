<?php
session_start();

require_once '../../includes/db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit();
}

// Vérifier les données fournies
if (!isset($_POST['hypothesis_id']) || !is_numeric($_POST['hypothesis_id'])) {
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit();
}

$hypothesis_id = (int)$_POST['hypothesis_id'];
$user_id = $_SESSION['user_id'];

try {
    // Vérifier si l'hypothèse appartient à un projet de l'utilisateur
    $stmt = $pdo->prepare("
        SELECT h.id 
        FROM hypotheses h
        JOIN projects p ON h.project_id = p.id
        WHERE h.id = :hypothesis_id AND p.user_id = :user_id
    ");
    $stmt->execute(['hypothesis_id' => $hypothesis_id, 'user_id' => $user_id]);
    $hypothesis = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$hypothesis) {
        echo json_encode(['success' => false, 'message' => 'Hypothèse non trouvée ou accès non autorisé']);
        exit();
    }

    // Supprimer l'hypothèse
    $stmt = $pdo->prepare("DELETE FROM hypotheses WHERE id = :id");
    $stmt->execute(['id' => $hypothesis_id]);

    echo json_encode(['success' => true, 'message' => 'Hypothèse supprimée avec succès']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression : ' . $e->getMessage()]);
}

exit();