<?php
session_start();
require_once '../includes/db_connect.php';
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['project1_id']) || !isset($input['project2_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required parameters']);
        exit();
    }
    
    $project1_id = (int)$input['project1_id'];
    $project2_id = (int)$input['project2_id'];
    
    // Validate that user owns at least one of the projects
    $stmt = $pdo->prepare("SELECT id FROM projects WHERE id IN (?, ?) AND user_id = ?");
    $stmt->execute([$project1_id, $project2_id, $user_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'You can only create partnerships involving your own projects']);
        exit();
    }
    
    // Check if partnership already exists
    $stmt = $pdo->prepare("SELECT id FROM partnerships WHERE (project1_id = ? AND project2_id = ?) OR (project1_id = ? AND project2_id = ?)");
    $stmt->execute([$project1_id, $project2_id, $project2_id, $project1_id]);
    
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Partnership already exists']);
        exit();
    }
    
    try {
        // Create partnership
        $stmt = $pdo->prepare("INSERT INTO partnerships (project1_id, project2_id, status, created_at, updated_at) VALUES (?, ?, 'pending', NOW(), NOW())");
        $stmt->execute([$project1_id, $project2_id]);
        
        $partnership_id = $pdo->lastInsertId();
        
        // Get partnership details
        $stmt = $pdo->prepare("
            SELECT p.*, 
                   p1.name as project1_name, p2.name as project2_name,
                   u1.name as user1_name, u2.name as user2_name
            FROM partnerships p
            JOIN projects p1 ON p.project1_id = p1.id
            JOIN projects p2 ON p.project2_id = p2.id
            JOIN users u1 ON p1.user_id = u1.id
            JOIN users u2 ON p2.user_id = u2.id
            WHERE p.id = ?
        ");
        $stmt->execute([$partnership_id]);
        $partnership = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Partnership created successfully',
            'partnership' => $partnership
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create partnership']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?> 