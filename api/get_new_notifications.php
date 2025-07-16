<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../config/notifications_config.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

$user_id = $_SESSION['user_id'];
$last_check = $_GET['last_check'] ?? null;

try {
    // Construire la requête pour récupérer les nouvelles notifications
    $sql = "
        SELECT n.*, 
               CASE 
                   WHEN n.type IN ('admin_broadcast', 'system_alert', 'maintenance', 'update') THEN 1
                   ELSE 0
               END as is_system_notification
        FROM notifications n 
        WHERE n.user_id = ? 
        AND n.is_read = 0
    ";
    
    $params = [$user_id];
    
    // Si une date de dernière vérification est fournie, filtrer les notifications plus récentes
    if ($last_check) {
        $sql .= " AND n.created_at > ?";
        $params[] = $last_check;
    }
    
    $sql .= " ORDER BY n.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les notifications pour l'affichage
    $formatted_notifications = [];
    foreach ($notifications as $notification) {
        $type_config = NOTIFICATION_TYPES[$notification['type']] ?? [
            'title' => 'Notification',
            'icon' => 'bi-bell',
            'color' => 'secondary'
        ];
        
        $formatted_notifications[] = [
            'id' => $notification['id'],
            'type' => $notification['type'],
            'title' => $notification['title'],
            'message' => $notification['message'],
            'icon' => $type_config['icon'],
            'color' => $type_config['color'],
            'important' => $type_config['important'] ?? false,
            'is_system' => $notification['is_system_notification'],
            'created_at' => $notification['created_at'],
            'time_ago' => getTimeAgo($notification['created_at'])
        ];
    }
    
    // Récupérer le nombre total de notifications non lues
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    $unread_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo json_encode([
        'success' => true,
        'notifications' => $formatted_notifications,
        'unread_count' => $unread_count,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}

// Fonction pour calculer le temps écoulé
function getTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return 'À l\'instant';
    } elseif ($time < 3600) {
        $minutes = floor($time / 60);
        return "Il y a $minutes min";
    } elseif ($time < 86400) {
        $hours = floor($time / 3600);
        return "Il y a $hours h";
    } else {
        $days = floor($time / 86400);
        return "Il y a $days j";
    }
}
?> 