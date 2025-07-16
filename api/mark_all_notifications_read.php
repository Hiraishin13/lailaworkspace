<?php
session_start();
header('Content-Type: application/json');

require_once '../includes/db_connect.php';
require_once '../models/Notification.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

try {
    $notification = new Notification($pdo);
    $success = $notification->markAllAsRead($_SESSION['user_id']);
    
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Toutes les notifications marquées comme lues' : 'Erreur lors du marquage'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?> 