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
$export_type = isset($_POST['export_type']) ? $_POST['export_type'] : '';
$filters = isset($_POST['filters']) ? json_decode($_POST['filters'], true) : [];
$format = isset($_POST['format']) ? $_POST['format'] : 'csv';

// Validation des données
if (!in_array($export_type, ['users', 'projects', 'analytics', 'partnerships'])) {
    echo json_encode(['success' => false, 'message' => 'Type d\'export invalide']);
    exit();
}

if (!in_array($format, ['csv', 'json'])) {
    echo json_encode(['success' => false, 'message' => 'Format d\'export invalide']);
    exit();
}

try {
    // Créer l'enregistrement d'export
    $stmt = $pdo->prepare("
        INSERT INTO data_exports (requested_by, export_type, filters, status, expires_at) 
        VALUES (?, ?, ?, 'processing', DATE_ADD(NOW(), INTERVAL 7 DAY))
    ");
    $stmt->execute([$_SESSION['user_id'], $export_type, json_encode($filters)]);
    $export_id = $pdo->lastInsertId();
    
    // Générer les données selon le type
    $data = generateExportData($pdo, $export_type, $filters);
    
    if ($data === false) {
        // Marquer l'export comme échoué
        $stmt = $pdo->prepare("UPDATE data_exports SET status = 'failed' WHERE id = ?");
        $stmt->execute([$export_id]);
        
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la génération des données']);
        exit();
    }
    
    // Générer le fichier
    $filename = "export_{$export_type}_" . date('Y-m-d_H-i-s') . ".{$format}";
    $filepath = "../../../uploads/exports/" . $filename;
    
    // Créer le dossier s'il n'existe pas
    $upload_dir = dirname($filepath);
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_content = '';
    $file_size = 0;
    
    if ($format === 'csv') {
        $file_content = generateCSV($data);
    } else {
        $file_content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    $file_size = strlen($file_content);
    
    // Sauvegarder le fichier
    if (file_put_contents($filepath, $file_content) === false) {
        throw new Exception('Impossible de sauvegarder le fichier');
    }
    
    // Mettre à jour l'enregistrement d'export
    $stmt = $pdo->prepare("
        UPDATE data_exports 
        SET status = 'completed', file_path = ?, file_size = ? 
        WHERE id = ?
    ");
    $stmt->execute([$filepath, $file_size, $export_id]);
    
    // Enregistrer dans l'audit
    $audit_stmt = $pdo->prepare("
        INSERT INTO audit_log (action_type, user_id, resource_type, resource_id, details, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $audit_stmt->execute([
        'export',
        $_SESSION['user_id'],
        'data_export',
        $export_id,
        "Export $export_type généré: $filename ($file_size bytes)",
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Export généré avec succès',
        'export_id' => $export_id,
        'filename' => $filename,
        'file_size' => $file_size,
        'download_url' => BASE_URL . '/views/admin/download_export.php?id=' . $export_id
    ]);
    
} catch (Exception $e) {
    error_log('Erreur export_data: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'export']);
}

// Fonction pour générer les données d'export
function generateExportData($pdo, $export_type, $filters) {
    try {
        switch ($export_type) {
            case 'users':
                return exportUsers($pdo, $filters);
            case 'projects':
                return exportProjects($pdo, $filters);
            case 'analytics':
                return exportAnalytics($pdo, $filters);
            case 'partnerships':
                return exportPartnerships($pdo, $filters);
            default:
                return false;
        }
    } catch (Exception $e) {
        error_log('Erreur generateExportData: ' . $e->getMessage());
        return false;
    }
}

function exportUsers($pdo, $filters) {
    $sql = "
        SELECT 
            CONCAT('User_', id) as user_code,
            username,
            CASE 
                WHEN email LIKE '%@%' THEN CONCAT(SUBSTRING(email, 1, 2), '***', SUBSTRING_INDEX(email, '@', -1))
                ELSE '***@***'
            END as masked_email,
            status,
            created_at,
            last_login,
            (
                SELECT COUNT(*) 
                FROM projects 
                WHERE user_id = users.id
            ) as project_count
        FROM users
    ";
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function exportProjects($pdo, $filters) {
    $sql = "
        SELECT 
            CONCAT('Projet_', p.id) as project_code,
            p.name,
            p.description,
            p.status,
            p.created_at,
            p.updated_at,
            CONCAT('User_', p.user_id) as user_code,
            p.share_consent,
            (
                SELECT COUNT(*) 
                FROM bmc_blocks b 
                WHERE b.project_id = p.id 
                AND b.content != '' 
                AND b.content != 'Non spécifié'
            ) as completed_blocks,
            (
                SELECT COUNT(*) 
                FROM hypotheses h 
                WHERE h.project_id = p.id
            ) as hypothesis_count
        FROM projects p
        ORDER BY p.created_at DESC
    ";
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function exportAnalytics($pdo, $filters) {
    $data = [];
    
    // Statistiques globales
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_projects,
            COUNT(CASE WHEN share_consent = 1 THEN 1 END) as shared_projects,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as recent_projects
        FROM projects
    ");
    $data['global_stats'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Évolution temporelle
    $stmt = $pdo->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as projects,
            COUNT(DISTINCT user_id) as users
        FROM projects 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date
    ");
    $data['timeline'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Répartition par complétion
    $stmt = $pdo->query("
        SELECT 
            CASE 
                WHEN completed_blocks = 0 THEN 'Non commencé'
                WHEN completed_blocks <= 3 THEN 'Débuté'
                WHEN completed_blocks <= 6 THEN 'En cours'
                WHEN completed_blocks <= 8 THEN 'Presque terminé'
                ELSE 'Terminé'
            END as completion_status,
            COUNT(*) as count
        FROM (
            SELECT 
                p.id,
                (
                    SELECT COUNT(*) 
                    FROM bmc_blocks b 
                    WHERE b.project_id = p.id 
                    AND b.content != '' 
                    AND b.content != 'Non spécifié'
                ) as completed_blocks
            FROM projects p
        ) as project_completion
        GROUP BY completion_status
        ORDER BY count DESC
    ");
    $data['completion_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $data;
}

function exportPartnerships($pdo, $filters) {
    $sql = "
        SELECT 
            p.id,
            CONCAT('Projet_', p.project1_id) as project1_code,
            CONCAT('Projet_', p.project2_id) as project2_code,
            p.compatibility_score,
            p.status,
            p.suggested_date,
            CONCAT('User_', p.suggested_by) as suggested_by_code
        FROM partnerships p
        ORDER BY p.compatibility_score DESC
    ";
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function generateCSV($data) {
    if (empty($data)) {
        return '';
    }
    
    $output = fopen('php://temp', 'r+');
    
    // Si c'est un tableau simple
    if (isset($data[0]) && is_array($data[0])) {
        // En-têtes
        fputcsv($output, array_keys($data[0]));
        
        // Données
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    } else {
        // Si c'est un objet avec plusieurs sections
        foreach ($data as $section => $section_data) {
            fputcsv($output, [$section]);
            
            if (isset($section_data[0]) && is_array($section_data[0])) {
                fputcsv($output, array_keys($section_data[0]));
                foreach ($section_data as $row) {
                    fputcsv($output, $row);
                }
            } else {
                fputcsv($output, $section_data);
            }
            
            fputcsv($output, []); // Ligne vide entre sections
        }
    }
    
    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);
    
    return $csv;
}
?> 