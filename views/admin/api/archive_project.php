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
$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;

// Validation des données
if (!$project_id) {
    echo json_encode(['success' => false, 'message' => 'ID de projet invalide']);
    exit();
}

try {
    // Vérifier que le projet existe
    $stmt = $pdo->prepare("
        SELECT id, name, user_id, status 
        FROM projects 
        WHERE id = ?
    ");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Projet non trouvé']);
        exit();
    }
    
    // Vérifier que le projet n'est pas déjà archivé
    if ($project['status'] === 'archived') {
        echo json_encode(['success' => false, 'message' => 'Le projet est déjà archivé']);
        exit();
    }
    
    // Archiver le projet
    $stmt = $pdo->prepare("UPDATE projects SET status = 'archived' WHERE id = ?");
    $result = $stmt->execute([$project_id]);
    
    if ($result) {
        // Enregistrer dans l'audit
        $audit_stmt = $pdo->prepare("
            INSERT INTO audit_log (action_type, user_id, resource_type, resource_id, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $audit_stmt->execute([
            'archive',
            $_SESSION['user_id'],
            'project',
            $project_id,
            "Projet archivé: " . $project['name'],
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Projet archivé avec succès',
            'project_id' => $project_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'archivage']);
    }
    
} catch (PDOException $e) {
    error_log('Erreur archive_project: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
}
?> 