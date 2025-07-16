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
$project1_id = isset($_POST['project1_id']) ? (int)$_POST['project1_id'] : 0;
$project2_id = isset($_POST['project2_id']) ? (int)$_POST['project2_id'] : 0;

// Validation des données
if (!$project1_id || !$project2_id || $project1_id === $project2_id) {
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit();
}

try {
    // Vérifier que les projets existent et ont le consentement de partage
    $stmt = $pdo->prepare("
        SELECT id, name, user_id, share_consent 
        FROM projects 
        WHERE id IN (?, ?) AND share_consent = 1
    ");
    $stmt->execute([$project1_id, $project2_id]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($projects) !== 2) {
        echo json_encode(['success' => false, 'message' => 'Un ou les deux projets ne sont pas disponibles pour le partenariat']);
        exit();
    }
    
    // Vérifier si le partenariat existe déjà
    $stmt = $pdo->prepare("
        SELECT id FROM partnerships 
        WHERE (project1_id = ? AND project2_id = ?) 
        OR (project1_id = ? AND project2_id = ?)
    ");
    $stmt->execute([$project1_id, $project2_id, $project2_id, $project1_id]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ce partenariat a déjà été suggéré']);
        exit();
    }
    
    // Calculer le score de compatibilité
    $compatibility_score = calculateCompatibilityScore($pdo, $project1_id, $project2_id);
    
    // Créer le partenariat
    $stmt = $pdo->prepare("
        INSERT INTO partnerships (project1_id, project2_id, compatibility_score, suggested_by, status) 
        VALUES (?, ?, ?, ?, 'suggested')
    ");
    $result = $stmt->execute([$project1_id, $project2_id, $compatibility_score, $_SESSION['user_id']]);
    
    if ($result) {
        $partnership_id = $pdo->lastInsertId();
        
        // Créer les notifications pour les deux utilisateurs
        $notification_stmt = $pdo->prepare("
            INSERT INTO partnership_notifications (partnership_id, user_id, notification_type, message) 
            VALUES (?, ?, 'suggestion', ?)
        ");
        
        $message1 = "Un partenariat B2B a été suggéré pour votre projet. Consultez les détails dans votre espace personnel.";
        $message2 = "Un partenariat B2B a été suggéré pour votre projet. Consultez les détails dans votre espace personnel.";
        
        $notification_stmt->execute([$partnership_id, $projects[0]['user_id'], $message1]);
        $notification_stmt->execute([$partnership_id, $projects[1]['user_id'], $message2]);
        
        // Enregistrer dans l'audit
        $audit_stmt = $pdo->prepare("
            INSERT INTO audit_log (action_type, user_id, resource_type, resource_id, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $audit_stmt->execute([
            'create',
            $_SESSION['user_id'],
            'partnership',
            $partnership_id,
            "Partenariat suggéré entre Projet_$project1_id et Projet_$project2_id (score: $compatibility_score)",
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Partenariat suggéré avec succès',
            'partnership_id' => $partnership_id,
            'compatibility_score' => $compatibility_score
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la création du partenariat']);
    }
    
} catch (PDOException $e) {
    error_log('Erreur suggest_partnership: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
}

// Fonction pour calculer le score de compatibilité
function calculateCompatibilityScore($pdo, $project1_id, $project2_id) {
    try {
        // Récupérer les blocs BMC des deux projets
        $stmt = $pdo->prepare("
            SELECT block_name, content 
            FROM bmc_blocks 
            WHERE project_id IN (?, ?) 
            AND content != '' 
            AND content != 'Non spécifié'
        ");
        $stmt->execute([$project1_id, $project2_id]);
        $blocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organiser les blocs par projet
        $project1_blocks = [];
        $project2_blocks = [];
        
        foreach ($blocks as $block) {
            if ($block['project_id'] == $project1_id) {
                $project1_blocks[$block['block_name']] = $block['content'];
            } else {
                $project2_blocks[$block['block_name']] = $block['content'];
            }
        }
        
        // Calculer le score de compatibilité
        $score = 0;
        $total_checks = 0;
        
        // Vérifier les segments de clientèle (complémentarité)
        if (isset($project1_blocks['segments_clientele']) && isset($project2_blocks['segments_clientele'])) {
            $score += checkSegmentComplementarity($project1_blocks['segments_clientele'], $project2_blocks['segments_clientele']);
            $total_checks++;
        }
        
        // Vérifier les ressources clés (synergie)
        if (isset($project1_blocks['ressources_cles']) && isset($project2_blocks['ressources_cles'])) {
            $score += checkResourceSynergy($project1_blocks['ressources_cles'], $project2_blocks['ressources_cles']);
            $total_checks++;
        }
        
        // Vérifier les canaux (complémentarité)
        if (isset($project1_blocks['canaux']) && isset($project2_blocks['canaux'])) {
            $score += checkChannelComplementarity($project1_blocks['canaux'], $project2_blocks['canaux']);
            $total_checks++;
        }
        
        // Vérifier les partenaires clés (synergie)
        if (isset($project1_blocks['partenaires_cles']) && isset($project2_blocks['partenaires_cles'])) {
            $score += checkPartnerSynergy($project1_blocks['partenaires_cles'], $project2_blocks['partenaires_cles']);
            $total_checks++;
        }
        
        return $total_checks > 0 ? round($score / $total_checks, 2) : 0.5;
        
    } catch (Exception $e) {
        error_log('Erreur calcul compatibilité: ' . $e->getMessage());
        return 0.5; // Score par défaut
    }
}

function checkSegmentComplementarity($seg1, $seg2) {
    $keywords1 = extractKeywords($seg1);
    $keywords2 = extractKeywords($seg2);
    
    $common = array_intersect($keywords1, $keywords2);
    $total = array_merge($keywords1, $keywords2);
    
    return count($common) / count($total) < 0.3 ? 0.8 : 0.2;
}

function checkResourceSynergy($res1, $res2) {
    $keywords1 = extractKeywords($res1);
    $keywords2 = extractKeywords($res2);
    
    $common = array_intersect($keywords1, $keywords2);
    return count($common) > 0 ? 0.7 : 0.3;
}

function checkChannelComplementarity($chan1, $chan2) {
    $keywords1 = extractKeywords($chan1);
    $keywords2 = extractKeywords($chan2);
    
    $common = array_intersect($keywords1, $keywords2);
    return count($common) < 2 ? 0.6 : 0.2;
}

function checkPartnerSynergy($part1, $part2) {
    $keywords1 = extractKeywords($part1);
    $keywords2 = extractKeywords($part2);
    
    $common = array_intersect($keywords1, $keywords2);
    return count($common) > 0 ? 0.8 : 0.4;
}

function extractKeywords($text) {
    $words = preg_split('/\s+/', strtolower($text));
    $stopwords = ['le', 'la', 'les', 'de', 'du', 'des', 'et', 'ou', 'avec', 'pour', 'dans', 'sur', 'par', 'un', 'une'];
    return array_filter($words, fn($word) => strlen($word) > 2 && !in_array($word, $stopwords));
}
?> 