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

$input = json_decode(file_get_contents('php://input'), true);
$notificationId = $input['notification_id'] ?? null;

if (!$notificationId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de notification requis']);
    exit;
}

try {
    $notification = new Notification($pdo);
    $success = $notification->markAsRead($notificationId, $_SESSION['user_id']);
    
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Notification marquée comme lue' : 'Erreur lors du marquage'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?> 