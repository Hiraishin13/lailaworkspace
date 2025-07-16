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
    
    if (!isset($input['partnership_id']) || !isset($input['status'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required parameters']);
        exit();
    }
    
    $partnership_id = (int)$input['partnership_id'];
    $status = $input['status'];
    
    // Validate status
    $valid_statuses = ['pending', 'active', 'completed', 'cancelled'];
    if (!in_array($status, $valid_statuses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid status']);
        exit();
    }
    
    // Check if user is part of this partnership
    $stmt = $pdo->prepare("
        SELECT p.id FROM partnerships p
        JOIN projects p1 ON p.project1_id = p1.id
        JOIN projects p2 ON p.project2_id = p2.id
        WHERE p.id = ? AND (p1.user_id = ? OR p2.user_id = ?)
    ");
    $stmt->execute([$partnership_id, $user_id, $user_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'You can only update partnerships you are involved in']);
        exit();
    }
    
    try {
        // Update partnership status
        $stmt = $pdo->prepare("UPDATE partnerships SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $partnership_id]);
        
        // Get updated partnership details
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
            'message' => 'Partnership status updated successfully',
            'partnership' => $partnership
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update partnership status']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?> 