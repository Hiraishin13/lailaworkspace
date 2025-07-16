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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Get user's partnerships
        $stmt = $pdo->prepare("
            SELECT p.*, 
                   p1.name as project1_name, p1.sector as project1_sector, p1.development_stage as project1_stage,
                   p2.name as project2_name, p2.sector as project2_sector, p2.development_stage as project2_stage,
                   u1.name as user1_name, u2.name as user2_name
            FROM partnerships p
            JOIN projects p1 ON p.project1_id = p1.id
            JOIN projects p2 ON p.project2_id = p2.id
            JOIN users u1 ON p1.user_id = u1.id
            JOIN users u2 ON p2.user_id = u2.id
            WHERE p1.user_id = ? OR p2.user_id = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$user_id, $user_id]);
        $partnerships = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate compatibility scores
        foreach ($partnerships as &$partnership) {
            $score = 0;
            
            // Sector compatibility
            if ($partnership['project1_sector'] === $partnership['project2_sector']) {
                $score += 25;
            }
            
            // Development stage compatibility
            if ($partnership['project1_stage'] === $partnership['project2_stage']) {
                $score += 20;
            } else {
                $score += 10;
            }
            
            // Partner type compatibility (assuming it exists)
            if (isset($partnership['project1_partner_type']) && isset($partnership['project2_partner_type'])) {
                if ($partnership['project1_partner_type'] === $partnership['project2_partner_type']) {
                    $score += 15;
                } else {
                    $score += 5;
                }
            }
            
            // Complementarity
            if ($partnership['project1_sector'] !== $partnership['project2_sector'] && 
                (($partnership['project1_sector'] === 'Technology' && $partnership['project2_sector'] === 'Healthcare') ||
                 ($partnership['project1_sector'] === 'Healthcare' && $partnership['project2_sector'] === 'Technology') ||
                 ($partnership['project1_sector'] === 'Finance' && $partnership['project2_sector'] === 'Technology') ||
                 ($partnership['project1_sector'] === 'Technology' && $partnership['project2_sector'] === 'Finance'))) {
                $score += 20;
            }
            
            // Geographic compatibility (assuming it exists)
            if (isset($partnership['project1_geo']) && isset($partnership['project2_geo'])) {
                if ($partnership['project1_geo'] === $partnership['project2_geo']) {
                    $score += 20;
                } else {
                    $score += 10;
                }
            }
            
            $partnership['compatibility_score'] = $score;
        }
        
        echo json_encode([
            'success' => true,
            'partnerships' => $partnerships
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch partnerships']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?> 