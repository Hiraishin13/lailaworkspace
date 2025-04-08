<?php
session_start();

require_once '../../includes/db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit();
}

// Vérifier les données fournies
if (!isset($_POST['hypothesis_id']) || !is_numeric($_POST['hypothesis_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit();
}

$hypothesis_id = (int)$_POST['hypothesis_id'];
$status = $_POST['status'];
$test_plan = isset($_POST['test_plan']) ? htmlspecialchars(trim($_POST['test_plan']), ENT_QUOTES, 'UTF-8') : '';
$user_id = $_SESSION['user_id'];

// Valider le statut
$valid_statuses = ['pending', 'confirmed', 'rejected'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Statut invalide']);
    exit();
}

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

    // Mettre à jour le statut et le plan de test (si fourni)
    $stmt = $pdo->prepare("UPDATE hypotheses SET status = :status, test_plan = :test_plan WHERE id = :id");
    $stmt->execute([
        'status' => $status,
        'test_plan' => $test_plan,
        'id' => $hypothesis_id
    ]);

    echo json_encode(['success' => true, 'message' => 'Statut mis à jour avec succès']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour : ' . $e->getMessage()]);
}

exit();