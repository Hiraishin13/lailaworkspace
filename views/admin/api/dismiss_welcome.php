<?php
session_start();
require_once '../../../includes/db_connect.php';
require_once '../../../includes/config.php';

// Vérifier les permissions admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit();
}

// Supprimer le flag de bienvenue de la session
unset($_SESSION['admin_welcome']);
unset($_SESSION['admin_name']);

// Réponse de succès
http_response_code(200);
echo json_encode(['success' => true]);
?> 