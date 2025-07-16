<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../../includes/db_connect.php';
require_once '../../config/notifications_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's projects
$stmt = $pdo->prepare("SELECT id, name, sector, development_stage FROM projects WHERE user_id = ?");
$stmt->execute([$user_id]);
$user_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get partnerships where user is involved
$stmt = $pdo->prepare("
    SELECT p.*, 
           p1.name as project1_name, p1.sector as project1_sector, p1.development_stage as project1_stage,
           p2.name as project2_name, p2.sector as project2_sector, p2.development_stage as project2_stage,
           CONCAT(u1.first_name, ' ', u1.last_name) as user1_name, CONCAT(u2.first_name, ' ', u2.last_name) as user2_name
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

// Get partnership suggestions
$stmt = $pdo->prepare("
    SELECT ps.*, 
           p1.name as project1_name, p1.sector as project1_sector, p1.development_stage as project1_stage,
           p2.name as project2_name, p2.sector as project2_sector, p2.development_stage as project2_stage,
           CONCAT(u1.first_name, ' ', u1.last_name) as user1_name, CONCAT(u2.first_name, ' ', u2.last_name) as user2_name
    FROM partnership_suggestions ps
    JOIN projects p1 ON ps.project1_id = p1.id
    JOIN projects p2 ON ps.project2_id = p2.id
    JOIN users u1 ON p1.user_id = u1.id
    JOIN users u2 ON p2.user_id = u2.id
    WHERE p1.user_id = ? OR p2.user_id = ?
    ORDER BY ps.compatibility_score DESC
");
$stmt->execute([$user_id, $user_id]);
$suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle partnership actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'accept_partnership':
                $partnership_id = (int)$_POST['partnership_id'];
                $stmt = $pdo->prepare("UPDATE partnerships SET status = 'active', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$partnership_id]);
                header("Location: partnerships.php?success=partnership_accepted");
                exit();
                break;
                
            case 'reject_partnership':
                $partnership_id = (int)$_POST['partnership_id'];
                $stmt = $pdo->prepare("UPDATE partnerships SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$partnership_id]);
                header("Location: partnerships.php?success=partnership_rejected");
                exit();
                break;
                
            case 'create_partnership':
                $project1_id = (int)$_POST['project1_id'];
                $project2_id = (int)$_POST['project2_id'];
                
                // Check if partnership already exists
                $stmt = $pdo->prepare("SELECT id FROM partnerships WHERE (project1_id = ? AND project2_id = ?) OR (project1_id = ? AND project2_id = ?)");
                $stmt->execute([$project1_id, $project2_id, $project2_id, $project1_id]);
                
                if (!$stmt->fetch()) {
                    $stmt = $pdo->prepare("INSERT INTO partnerships (project1_id, project2_id, status, created_at, updated_at) VALUES (?, ?, 'pending', NOW(), NOW())");
                    $stmt->execute([$project1_id, $project2_id]);
                    header("Location: partnerships.php?success=partnership_created");
                    exit();
                } else {
                    header("Location: partnerships.php?error=partnership_exists");
                    exit();
                }
                break;
        }
    }
}

// Calculate compatibility score for a partnership
function calculateCompatibilityScore($project1, $project2) {
    $score = 0;
    
    // Sector compatibility
    if ($project1['sector'] === $project2['sector']) {
        $score += 25;
    }
    
    // Development stage compatibility
    if ($project1['development_stage'] === $project2['development_stage']) {
        $score += 20;
    } else {
        $score += 10;
    }
    
    // Partner type compatibility
    if ($project1['partner_type'] === $project2['partner_type']) {
        $score += 15;
    } else {
        $score += 5;
    }
    
    // Complementarity
    if ($project1['sector'] !== $project2['sector'] && 
        (($project1['sector'] === 'Technology' && $project2['sector'] === 'Healthcare') ||
         ($project1['sector'] === 'Healthcare' && $project2['sector'] === 'Technology') ||
         ($project1['sector'] === 'Finance' && $project2['sector'] === 'Technology') ||
         ($project1['sector'] === 'Technology' && $project2['sector'] === 'Finance'))) {
        $score += 20;
    }
    
    // Geographic compatibility
    if ($project1['geographic_scope'] === $project2['geographic_scope']) {
        $score += 20;
    } else {
        $score += 10;
    }
    
    return $score;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Partenariats - Laila Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
    <style>
        .partnership-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        .partnership-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .compatibility-score {
            font-size: 1.5rem;
            font-weight: bold;
            text-align: center;
            padding: 10px;
            border-radius: 8px;
            margin: 10px 0;
        }
        .score-excellent { background: linear-gradient(135deg, #28a745, #20c997); color: white; }
        .score-good { background: linear-gradient(135deg, #17a2b8, #6f42c1); color: white; }
        .score-fair { background: linear-gradient(135deg, #ffc107, #fd7e14); color: white; }
        .score-poor { background: linear-gradient(135deg, #dc3545, #e83e8c); color: white; }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-active { background: #d1edff; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .project-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        
        .suggestion-card {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <?php include '../../views/layouts/navbar.php'; ?>

    <div class="container mt-4">
        <!-- Messages de succès/erreur -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php
                switch ($_GET['success']) {
                    case 'partnership_accepted':
                        echo '<i class="fas fa-check-circle"></i> Partenariat accepté avec succès !';
                        break;
                    case 'partnership_rejected':
                        echo '<i class="fas fa-times-circle"></i> Partenariat refusé.';
                        break;
                    case 'partnership_created':
                        echo '<i class="fas fa-handshake"></i> Demande de partenariat envoyée avec succès !';
                        break;
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php
                switch ($_GET['error']) {
                    case 'partnership_exists':
                        echo '<i class="fas fa-exclamation-triangle"></i> Ce partenariat existe déjà.';
                        break;
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">
                    <i class="fas fa-handshake text-primary"></i>
                    Mes Partenariats
                </h1>
            </div>
        </div>

        <!-- Partenariats Actifs -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-link"></i>
                            Partenariats Actifs
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($partnerships)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-handshake fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Aucun partenariat pour le moment. Consultez les suggestions ci-dessous !</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($partnerships as $partnership): ?>
                                <div class="partnership-card p-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-3">
                                            <div class="project-info">
                                                <h6><?php echo htmlspecialchars($partnership['project1_name'] ?? ''); ?></h6>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($partnership['project1_sector'] ?? ''); ?> • 
                                                    <?php echo htmlspecialchars($partnership['project1_stage'] ?? ''); ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-md-2 text-center">
                                            <i class="fas fa-handshake fa-2x text-primary"></i>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="project-info">
                                                <h6><?php echo htmlspecialchars($partnership['project2_name'] ?? ''); ?></h6>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($partnership['project2_sector'] ?? ''); ?> • 
                                                    <?php echo htmlspecialchars($partnership['project2_stage'] ?? ''); ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-md-2 text-center">
                                            <span class="status-badge status-<?php echo strtolower($partnership['status'] ?? 'pending'); ?>">
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
                                            </span>
                                        </div>
                                        <div class="col-md-2 text-center">
                                            <a href="partnership_details.php?id=<?php echo $partnership['id']; ?>" 
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-eye"></i> Voir Détails
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <?php if ($partnership['status'] === 'pending'): ?>
                                        <div class="row mt-3">
                                            <div class="col-12 text-center">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="accept_partnership">
                                                    <input type="hidden" name="partnership_id" value="<?php echo $partnership['id']; ?>">
                                                    <button type="submit" class="btn btn-success btn-sm me-2">
                                                        <i class="fas fa-check"></i> Accepter
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="reject_partnership">
                                                    <input type="hidden" name="partnership_id" value="<?php echo $partnership['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-times"></i> Refuser
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Suggestions de Partenariats -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-lightbulb"></i>
                            Suggestions de Partenariats
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($suggestions)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-lightbulb fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Aucune suggestion de partenariat disponible pour le moment.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($suggestions as $suggestion): ?>
                                <div class="suggestion-card">
                                    <div class="row align-items-center">
                                        <div class="col-md-3">
                                            <div class="project-info">
                                                <h6><?php echo htmlspecialchars($suggestion['project1_name'] ?? ''); ?></h6>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($suggestion['project1_sector'] ?? ''); ?> • 
                                                    <?php echo htmlspecialchars($suggestion['project1_stage'] ?? ''); ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-md-2 text-center">
                                            <div class="compatibility-score score-<?php 
                                                if ($suggestion['compatibility_score'] >= 80) echo 'excellent';
                                                elseif ($suggestion['compatibility_score'] >= 60) echo 'good';
                                                elseif ($suggestion['compatibility_score'] >= 40) echo 'fair';
                                                else echo 'poor';
                                            ?>">
                                                <?php echo $suggestion['compatibility_score']; ?>%
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="project-info">
                                                <h6><?php echo htmlspecialchars($suggestion['project2_name'] ?? ''); ?></h6>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($suggestion['project2_sector'] ?? ''); ?> • 
                                                    <?php echo htmlspecialchars($suggestion['project2_stage'] ?? ''); ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-md-2 text-center">
                                            <small class="text-muted">
                                                Suggéré par : <?php echo htmlspecialchars($suggestion['user1_name'] ?? ''); ?>
                                            </small>
                                        </div>
                                        <div class="col-md-2 text-center">
                                            <form method="POST">
                                                <input type="hidden" name="action" value="create_partnership">
                                                <input type="hidden" name="project1_id" value="<?php echo $suggestion['project1_id']; ?>">
                                                <input type="hidden" name="project2_id" value="<?php echo $suggestion['project2_id']; ?>">
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-handshake"></i> Créer Partenariat
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../views/layouts/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- <script src="../../assets/js/scripts.js"></script> -->
</body>
</html> 