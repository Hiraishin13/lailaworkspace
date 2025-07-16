<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../config/notifications_config.php';
require_once '../../includes/AIService.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$partnership_id = null;
$partnership = null;

// Handle different URL parameters
if (isset($_GET['id'])) {
    $partnership_id = (int)$_GET['id'];
} elseif (isset($_GET['project1']) && isset($_GET['project2'])) {
    $project1_id = (int)$_GET['project1'];
    $project2_id = (int)$_GET['project2'];
    
    // Get partnership by project IDs
    $stmt = $pdo->prepare("
        SELECT id FROM partnerships 
        WHERE (project1_id = ? AND project2_id = ?) OR (project1_id = ? AND project2_id = ?)
    ");
    $stmt->execute([$project1_id, $project2_id, $project2_id, $project1_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $partnership_id = $result['id'];
    }
}

if ($partnership_id) {
    // Get partnership details
    $stmt = $pdo->prepare("
        SELECT p.*, 
               p1.name as project1_name, p1.description as project1_description, p1.target_market as project1_target,
               p1.user_id as project1_user_id,
               p2.name as project2_name, p2.description as project2_description, p2.target_market as project2_target,
               p2.user_id as project2_user_id,
               CONCAT(u1.first_name, ' ', u1.last_name) as user1_name, u1.email as user1_email,
               CONCAT(u2.first_name, ' ', u2.last_name) as user2_name, u2.email as user2_email
        FROM partnerships p
        JOIN projects p1 ON p.project1_id = p1.id
        JOIN projects p2 ON p.project2_id = p2.id
        JOIN users u1 ON p1.user_id = u1.id
        JOIN users u2 ON p2.user_id = u2.id
        WHERE p.id = ?
    ");
    $stmt->execute([$partnership_id]);
    $partnership = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if user is part of this partnership
    if ($partnership && $partnership['project1_user_id'] !== $user_id && $partnership['project2_user_id'] !== $user_id) {
        header('Location: partnerships.php?error=unauthorized');
        exit();
    }
}

// Handle partnership actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $partnership) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'accept_partnership':
                $stmt = $pdo->prepare("UPDATE partnerships SET status = 'active', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$partnership_id]);
                header("Location: partnership_details.php?id=" . $partnership_id . "&success=accepted");
                exit();
                break;
                
            case 'reject_partnership':
                $stmt = $pdo->prepare("UPDATE partnerships SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$partnership_id]);
                header("Location: partnership_details.php?id=" . $partnership_id . "&success=rejected");
                exit();
                break;
                
            case 'complete_partnership':
                $stmt = $pdo->prepare("UPDATE partnerships SET status = 'completed', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$partnership_id]);
                header("Location: partnership_details.php?id=" . $partnership_id . "&success=completed");
                exit();
                break;
        }
    }
}

// Analyse IA de compatibilité
$compatibility_score = 0;
$compatibility_factors = [];
$ai_analysis = null;
$ai_recommendations = [];
$ai_risks = [];

if ($partnership && AI_ENABLED) {
    $project1_id = $partnership['project1_id'] ?? null;
    $project2_id = $partnership['project2_id'] ?? null;
    
    if ($project1_id && $project2_id) {
        // Récupérer les données complètes des projets pour l'IA
        $project1_data = getProjectDataForAI($pdo, $project1_id);
        $project2_data = getProjectDataForAI($pdo, $project2_id);
        
        if ($project1_data && $project2_data) {
            try {
                $ai_service = new AIService();
                $ai_analysis = $ai_service->analyzePartnershipCompatibility($project1_data, $project2_data);
                
                if ($ai_analysis) {
                    $compatibility_score = $ai_analysis['score'] ?? 0;
                    $compatibility_factors = $ai_analysis['factors'] ?? [];
                    $ai_recommendations = $ai_analysis['recommendations'] ?? [];
                    $ai_risks = $ai_analysis['risks'] ?? [];
                }
            } catch (Exception $e) {
                error_log("Erreur IA: " . $e->getMessage());
                // Fallback vers l'analyse basique
                $compatibility_score = calculateBasicCompatibility($pdo, $project1_id, $project2_id);
            }
        }
    }
}

// Fonction pour récupérer les données complètes d'un projet pour l'IA
function getProjectDataForAI($pdo, $project_id) {
    // Récupérer les données de base du projet
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) return null;
    
    // Récupérer les blocs BMC
    $stmt = $pdo->prepare("SELECT block_name, content FROM bmc WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $bmc_blocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $bmc_data = [];
    foreach ($bmc_blocks as $block) {
        $bmc_data[$block['block_name']] = $block['content'];
    }
    
    // Récupérer les hypothèses validées
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM hypotheses WHERE project_id = ? AND status = 'validated'");
    $stmt->execute([$project_id]);
    $hypotheses = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Récupérer les données financières
    $stmt = $pdo->prepare("SELECT profit_margin, total_revenue FROM financial_plans WHERE project_id = ? LIMIT 1");
    $stmt->execute([$project_id]);
    $financial = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'name' => $project['name'],
        'description' => $project['description'],
        'target_market' => $project['target_market'],
        'segments' => $bmc_data['Segments de clientèle'] ?? 'Non défini',
        'value_proposition' => $bmc_data['Proposition de valeur'] ?? 'Non défini',
        'channels' => $bmc_data['Canaux'] ?? 'Non défini',
        'partners' => $bmc_data['Partenaires clés'] ?? 'Non défini',
        'validated_hypotheses' => $hypotheses['count'] ?? 0,
        'profit_margin' => $financial['profit_margin'] ?? 0
    ];
}

// Fonction de fallback pour l'analyse basique
function calculateBasicCompatibility($pdo, $project1_id, $project2_id) {
    $score = 0;
    
    // Analyse basique des segments
    $stmt = $pdo->prepare("
        SELECT b1.content as segment1, b2.content as segment2
        FROM bmc b1 
        JOIN bmc b2 ON b2.project_id = ?
        WHERE b1.project_id = ? 
        AND b1.block_name = 'Segments de clientèle' 
        AND b2.block_name = 'Segments de clientèle'
    ");
    $stmt->execute([$project2_id, $project1_id]);
    $segments = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($segments) {
        $segment1 = strtolower($segments['segment1']);
        $segment2 = strtolower($segments['segment2']);
        
        if (strpos($segment1, 'entreprise') !== false && strpos($segment2, 'entreprise') !== false) {
            $score += 25;
        } elseif (strpos($segment1, 'particulier') !== false && strpos($segment2, 'particulier') !== false) {
            $score += 25;
        } else {
            $score += 15;
        }
    }
    
    // Analyse basique des propositions de valeur
    $stmt = $pdo->prepare("
        SELECT b1.content as value1, b2.content as value2
        FROM bmc b1 
        JOIN bmc b2 ON b2.project_id = ?
        WHERE b1.project_id = ? 
        AND b1.block_name = 'Proposition de valeur' 
        AND b2.block_name = 'Proposition de valeur'
    ");
    $stmt->execute([$project2_id, $project1_id]);
    $values = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($values) {
        $value1 = strtolower($values['value1']);
        $value2 = strtolower($values['value2']);
        
        if (strpos($value1, 'simplifi') !== false && strpos($value2, 'simplifi') !== false) {
            $score += 20;
        } else {
            $score += 15;
        }
    }
    
    return $score;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du Partenariat - Laila Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
    <style>
        .compatibility-score {
            font-size: 2.5rem;
            font-weight: bold;
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .score-excellent { background: linear-gradient(135deg, #28a745, #20c997); color: white; }
        .score-good { background: linear-gradient(135deg, #17a2b8, #6f42c1); color: white; }
        .score-fair { background: linear-gradient(135deg, #ffc107, #fd7e14); color: white; }
        .score-poor { background: linear-gradient(135deg, #dc3545, #e83e8c); color: white; }
        
        .factor-card {
            border-left: 4px solid #007bff;
            margin-bottom: 10px;
        }
        .factor-score {
            font-weight: bold;
            color: #007bff;
        }
        
        .project-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background: #f8f9fa;
        }
        
        .partnership-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.8rem;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-active { background: #d1edff; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .user-info {
            background: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <?php include '../../views/layouts/navbar.php'; ?>

    <div class="container mt-4">
        <!-- Messages de succès -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php
                switch ($_GET['success']) {
                    case 'accepted':
                        echo '<i class="fas fa-check-circle"></i> Partenariat accepté avec succès !';
                        break;
                    case 'rejected':
                        echo '<i class="fas fa-times-circle"></i> Partenariat refusé.';
                        break;
                    case 'completed':
                        echo '<i class="fas fa-flag-checkered"></i> Partenariat marqué comme terminé !';
                        break;
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>
                        <i class="fas fa-handshake text-primary"></i>
                        Détails du Partenariat
                    </h1>
                    <a href="partnerships.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Retour aux Partenariats
                    </a>
                </div>
            </div>
        </div>

        <?php if (!$partnership): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                Partenariat introuvable ou vous n'avez pas accès à celui-ci.
            </div>
        <?php else: ?>
            <!-- Aperçu du Partenariat -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle"></i>
                                Aperçu du Partenariat
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>ID du Partenariat :</strong> #<?php echo $partnership['id']; ?><br>
                                    <strong>Statut :</strong> 
                                    <span class="partnership-status status-<?php echo strtolower($partnership['status'] ?? 'pending'); ?>">
                                        <?php 
                                        $status = $partnership['status'] ?? 'pending';
                                        switch($status) {
                                            case 'pending': echo 'En Attente'; break;
                                            case 'active': echo 'Actif'; break;
                                            case 'completed': echo 'Terminé'; break;
                                            case 'cancelled': echo 'Annulé'; break;
                                            default: echo ucfirst($status);
                                        }
                                        ?>
                                    </span><br>
                                    <strong>Créé le :</strong> <?php echo date('j M Y', strtotime($partnership['created_at'])); ?><br>
                                    <strong>Dernière mise à jour :</strong> <?php echo date('j M Y', strtotime($partnership['updated_at'] ?? $partnership['created_at'])); ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>Score de Compatibilité :</strong><br>
                                    <div class="compatibility-score score-<?php 
                                        if ($compatibility_score >= 80) echo 'excellent';
                                        elseif ($compatibility_score >= 60) echo 'good';
                                        elseif ($compatibility_score >= 40) echo 'fair';
                                        else echo 'poor';
                                    ?>">
                                        <?php echo $compatibility_score; ?>%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-cogs"></i>
                                Actions
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($partnership['status'] === 'pending'): ?>
                                <form method="POST" class="mb-2">
                                    <input type="hidden" name="action" value="accept_partnership">
                                    <button type="submit" class="btn btn-success w-100 mb-2">
                                        <i class="fas fa-check"></i> Accepter le Partenariat
                                    </button>
                                </form>
                                <form method="POST">
                                    <input type="hidden" name="action" value="reject_partnership">
                                    <button type="submit" class="btn btn-danger w-100">
                                        <i class="fas fa-times"></i> Refuser le Partenariat
                                    </button>
                                </form>
                            <?php elseif ($partnership['status'] === 'active'): ?>
                                <form method="POST">
                                    <input type="hidden" name="action" value="complete_partnership">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-flag-checkered"></i> Marquer comme Terminé
                                    </button>
                                </form>
                            <?php else: ?>
                                <p class="text-muted text-center">
                                    <i class="fas fa-info-circle"></i>
                                    Aucune action disponible pour ce statut.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comparaison des Projets -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="project-card">
                        <h5><i class="fas fa-project-diagram text-primary"></i> Projet 1</h5>
                        <h6><?php echo htmlspecialchars($partnership['project1_name'] ?? ''); ?></h6>
                        <p class="text-muted"><?php echo htmlspecialchars($partnership['project1_description'] ?? 'Aucune description disponible'); ?></p>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Secteur :</small><br>
                                <strong><?php echo htmlspecialchars($partnership['project1_sector'] ?? ''); ?></strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Étape :</small><br>
                                <strong><?php echo htmlspecialchars($partnership['project1_stage'] ?? ''); ?></strong>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-6">
                                <small class="text-muted">Type de Partenaire :</small><br>
                                <strong><?php echo htmlspecialchars($partnership['project1_partner_type'] ?? ''); ?></strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Portée :</small><br>
                                <strong><?php echo htmlspecialchars($partnership['project1_geo'] ?? ''); ?></strong>
                            </div>
                        </div>
                        <div class="user-info">
                            <small class="text-muted">Propriétaire du Projet :</small><br>
                            <strong><?php echo htmlspecialchars($partnership['user1_name'] ?? ''); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($partnership['user1_email'] ?? ''); ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="project-card">
                        <h5><i class="fas fa-project-diagram text-success"></i> Projet 2</h5>
                        <h6><?php echo htmlspecialchars($partnership['project2_name'] ?? ''); ?></h6>
                        <p class="text-muted"><?php echo htmlspecialchars($partnership['project2_description'] ?? 'Aucune description disponible'); ?></p>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Secteur :</small><br>
                                <strong><?php echo htmlspecialchars($partnership['project2_sector'] ?? ''); ?></strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Étape :</small><br>
                                <strong><?php echo htmlspecialchars($partnership['project2_stage'] ?? ''); ?></strong>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-6">
                                <small class="text-muted">Type de Partenaire :</small><br>
                                <strong><?php echo htmlspecialchars($partnership['project2_partner_type'] ?? ''); ?></strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Portée :</small><br>
                                <strong><?php echo htmlspecialchars($partnership['project2_geo'] ?? ''); ?></strong>
                            </div>
                        </div>
                        <div class="user-info">
                            <small class="text-muted">Propriétaire du Projet :</small><br>
                            <strong><?php echo htmlspecialchars($partnership['user2_name'] ?? ''); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($partnership['user2_email'] ?? ''); ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analyse IA de Compatibilité -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-robot text-primary"></i>
                                Analyse IA de Compatibilité
                                <?php if ($ai_analysis): ?>
                                    <span class="badge bg-success ms-2">
                                        <i class="fas fa-check"></i> IA Active
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning ms-2">
                                        <i class="fas fa-exclamation-triangle"></i> Analyse Basique
                                    </span>
                                <?php endif; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($ai_analysis && isset($ai_analysis['overall_assessment'])): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-brain"></i>
                                    <strong>Évaluation IA :</strong> <?php echo htmlspecialchars($ai_analysis['overall_assessment']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php foreach ($compatibility_factors as $factor): ?>
                                <div class="card factor-card">
                                    <div class="card-body py-2">
                                        <div class="row align-items-center">
                                            <div class="col-md-4">
                                                <strong><?php echo htmlspecialchars($factor['name'] ?? $factor['factor']); ?></strong>
                                            </div>
                                            <div class="col-md-2">
                                                <span class="factor-score"><?php echo $factor['score']; ?> pts</span>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted"><?php echo htmlspecialchars($factor['description']); ?></small>
                                                <?php if (isset($factor['synergy'])): ?>
                                                    <br><small class="text-primary"><i class="fas fa-lightbulb"></i> <?php echo htmlspecialchars($factor['synergy']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recommandations et Risques IA -->
            <?php if (!empty($ai_recommendations) || !empty($ai_risks)): ?>
            <div class="row mb-4">
                <div class="col-md-6">
                    <?php if (!empty($ai_recommendations)): ?>
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-lightbulb"></i>
                                Recommandations IA
                            </h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <?php foreach ($ai_recommendations as $recommendation): ?>
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <?php echo htmlspecialchars($recommendation); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6">
                    <?php if (!empty($ai_risks)): ?>
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                                <i class="fas fa-exclamation-triangle"></i>
                                Risques Identifiés
                            </h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <?php foreach ($ai_risks as $risk): ?>
                                    <li class="mb-2">
                                        <i class="fas fa-exclamation-circle text-warning me-2"></i>
                                        <?php echo htmlspecialchars($risk); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php include '../../views/layouts/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- <script src="../../assets/js/scripts.js"></script> -->
</body>
</html> 