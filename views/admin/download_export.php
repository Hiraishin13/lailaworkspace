<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/config.php';

// Vérifier les permissions admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    die('Accès refusé');
}

// Récupérer l'ID de l'export
$export_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$export_id) {
    http_response_code(400);
    die('ID d\'export invalide');
}

try {
    // Récupérer les informations de l'export
    $stmt = $pdo->prepare("
        SELECT * FROM data_exports 
        WHERE id = ? AND requested_by = ? AND status = 'completed'
    ");
    $stmt->execute([$export_id, $_SESSION['user_id']]);
    $export = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$export) {
        http_response_code(404);
        die('Export non trouvé ou non autorisé');
    }
    
    // Vérifier que le fichier existe
    if (!file_exists($export['file_path'])) {
        http_response_code(404);
        die('Fichier non trouvé');
    }
    
    // Vérifier que le fichier n'a pas expiré
    if ($export['expires_at'] && strtotime($export['expires_at']) < time()) {
        http_response_code(410);
        die('Fichier expiré');
    }
    
    // Incrémenter le compteur de téléchargements
    $stmt = $pdo->prepare("UPDATE data_exports SET download_count = download_count + 1 WHERE id = ?");
    $stmt->execute([$export_id]);
    
    // Enregistrer dans l'audit
    $audit_stmt = $pdo->prepare("
        INSERT INTO audit_log (action_type, user_id, resource_type, resource_id, details, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $audit_stmt->execute([
        'download',
        $_SESSION['user_id'],
        'data_export',
        $export_id,
        "Export téléchargé: " . basename($export['file_path']),
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    // Déterminer le type MIME
    $extension = pathinfo($export['file_path'], PATHINFO_EXTENSION);
    $mime_type = $extension === 'csv' ? 'text/csv' : 'application/json';
    
    // En-têtes de téléchargement
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="' . basename($export['file_path']) . '"');
    header('Content-Length: ' . filesize($export['file_path']));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // Lire et envoyer le fichier
    readfile($export['file_path']);
    
} catch (Exception $e) {
    error_log('Erreur download_export: ' . $e->getMessage());
    http_response_code(500);
    die('Erreur lors du téléchargement');
}
?> 