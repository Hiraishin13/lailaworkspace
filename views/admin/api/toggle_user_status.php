<?php
session_start();
require_once '../../../includes/db_connect.php';
require_once '../../../includes/config.php';

// Vérifier les permissions admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit();
}

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// Récupérer les données
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';

// Validation des données
if (!$user_id || !in_array($status, ['active', 'inactive'])) {
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit();
}

try {
    // Vérifier que l'utilisateur existe
    $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
        exit();
    }
    
    // Empêcher l'auto-désactivation
    if ($user_id == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas modifier votre propre statut']);
        exit();
    }
    
    // Mettre à jour le statut
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    $result = $stmt->execute([$status, $user_id]);
    
    if ($result) {
        // Enregistrer dans l'audit
        $audit_stmt = $pdo->prepare("
            INSERT INTO audit_log (action_type, user_id, resource_type, resource_id, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $audit_stmt->execute([
            'update',
            $_SESSION['user_id'],
            'user',
            $user_id,
            "Statut utilisateur changé vers: $status",
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        echo json_encode([
            'success' => true, 
            'message' => "Statut de l'utilisateur mis à jour avec succès",
            'new_status' => $status
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
    }
    
} catch (PDOException $e) {
    error_log('Erreur toggle_user_status: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
}
?> 