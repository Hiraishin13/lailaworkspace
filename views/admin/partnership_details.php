<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../config/notifications_config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

$partnership_id = null;
$project1_id = null;
$project2_id = null;
$partnership = null;
$project1 = null;
$project2 = null;

// Handle different URL parameters
if (isset($_GET['id'])) {
    $partnership_id = (int)$_GET['id'];
    // Get partnership by ID
    $stmt = $pdo->prepare("
        SELECT p.*, 
               p1.name as project1_name, p1.description as project1_description, p1.sector as project1_sector,
               p2.name as project2_name, p2.description as project2_description, p2.sector as project2_sector
        FROM partnerships p
        JOIN projects p1 ON p.project1_id = p1.id
        JOIN projects p2 ON p.project2_id = p2.id
        WHERE p.id = ?
    ");
    $stmt->execute([$partnership_id]);
    $partnership = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($partnership) {
        $project1_id = $partnership['project1_id'];
        $project2_id = $partnership['project2_id'];
    }
} elseif (isset($_GET['project1']) && isset($_GET['project2'])) {
    $project1_id = (int)$_GET['project1'];
    $project2_id = (int)$_GET['project2'];
    
    // Get partnership by project IDs
    $stmt = $pdo->prepare("
        SELECT p.*, 
               p1.name as project1_name, p1.description as project1_description, p1.sector as project1_sector,
               p2.name as project2_name, p2.description as project2_description, p2.sector as project2_sector
        FROM partnerships p
        JOIN projects p1 ON p.project1_id = p1.id
        JOIN projects p2 ON p.project2_id = p2.id
        WHERE (p.project1_id = ? AND p.project2_id = ?) OR (p.project1_id = ? AND p.project2_id = ?)
    ");
    $stmt->execute([$project1_id, $project2_id, $project2_id, $project1_id]);
    $partnership = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($partnership) {
        $partnership_id = $partnership['id'];
    }
}

// Get individual project details
if ($project1_id) {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$project1_id]);
    $project1 = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($project2_id) {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$project2_id]);
    $project2 = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle partnership actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $new_status = $_POST['status'];
                $stmt = $pdo->prepare("UPDATE partnerships SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$new_status, $partnership_id]);
                header("Location: partnership_details.php?id=" . $partnership_id);
                exit();
                break;
                
            case 'add_note':
                $note = trim($_POST['note']);
                if (!empty($note)) {
                    $stmt = $pdo->prepare("UPDATE partnerships SET admin_notes = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$note, $partnership_id]);
                    header("Location: partnership_details.php?id=" . $partnership_id);
                    exit();
                }
                break;
        }
    }
}

// Get partnership history
$partnership_history = [];
if ($partnership_id) {
    $stmt = $pdo->prepare("
        SELECT ph.*, u.name as user_name
        FROM partnership_history ph
        LEFT JOIN users u ON ph.user_id = u.id
        WHERE ph.partnership_id = ?
        ORDER BY ph.created_at DESC
    ");
    $stmt->execute([$partnership_id]);
    $partnership_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Calculate compatibility score
$compatibility_score = 0;
$compatibility_factors = [];

if ($project1 && $project2) {
    // Sector compatibility
    if ($project1['sector'] === $project2['sector']) {
        $compatibility_score += 25;
        $compatibility_factors[] = ['factor' => 'Sector Match', 'score' => 25, 'description' => 'Both projects are in the same sector'];
    } else {
        $compatibility_factors[] = ['factor' => 'Sector Match', 'score' => 0, 'description' => 'Different sectors: ' . $project1['sector'] . ' vs ' . $project2['sector']];
    }
    
    // Development stage compatibility
    $stage1 = $project1['development_stage'] ?? 'unknown';
    $stage2 = $project2['development_stage'] ?? 'unknown';
    
    if ($stage1 === $stage2) {
        $compatibility_score += 20;
        $compatibility_factors[] = ['factor' => 'Development Stage', 'score' => 20, 'description' => 'Same development stage: ' . $stage1];
    } else {
        $compatibility_factors[] = ['factor' => 'Development Stage', 'score' => 10, 'description' => 'Different stages: ' . $stage1 . ' vs ' . $stage2];
    }
    
    // Partner type compatibility
    $partner1 = $project1['partner_type'] ?? 'unknown';
    $partner2 = $project2['partner_type'] ?? 'unknown';
    
    if ($partner1 === $partner2) {
        $compatibility_score += 15;
        $compatibility_factors[] = ['factor' => 'Partner Type', 'score' => 15, 'description' => 'Same partner type: ' . $partner1];
    } else {
        $compatibility_factors[] = ['factor' => 'Partner Type', 'score' => 5, 'description' => 'Different partner types: ' . $partner1 . ' vs ' . $partner2];
    }
    
    // Complementarity (different but complementary)
    if ($project1['sector'] !== $project2['sector'] && 
        ($project1['sector'] === 'Technology' && $project2['sector'] === 'Healthcare') ||
        ($project1['sector'] === 'Healthcare' && $project2['sector'] === 'Technology') ||
        ($project1['sector'] === 'Finance' && $project2['sector'] === 'Technology') ||
        ($project1['sector'] === 'Technology' && $project2['sector'] === 'Finance')) {
        $compatibility_score += 20;
        $compatibility_factors[] = ['factor' => 'Complementarity', 'score' => 20, 'description' => 'Complementary sectors'];
    } else {
        $compatibility_factors[] = ['factor' => 'Complementarity', 'score' => 0, 'description' => 'No significant complementarity'];
    }
    
    // Geographic compatibility
    $geo1 = $project1['geographic_scope'] ?? 'unknown';
    $geo2 = $project2['geographic_scope'] ?? 'unknown';
    
    if ($geo1 === $geo2) {
        $compatibility_score += 20;
        $compatibility_factors[] = ['factor' => 'Geographic Scope', 'score' => 20, 'description' => 'Same geographic scope: ' . $geo1];
    } else {
        $compatibility_factors[] = ['factor' => 'Geographic Scope', 'score' => 10, 'description' => 'Different scopes: ' . $geo1 . ' vs ' . $geo2];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partnership Details - Laila Workspace Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/admin.css" rel="stylesheet">
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
        
        .history-item {
            border-left: 3px solid #007bff;
            padding-left: 15px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php include 'template_header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'template_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-handshake text-primary"></i>
                        Partnership Details
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="partnerships.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Partnerships
                        </a>
                    </div>
                </div>

                <?php if (!$partnership): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Partnership not found. Please check the URL parameters.
                    </div>
                <?php else: ?>
                    <!-- Partnership Overview -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-info-circle"></i>
                                        Partnership Overview
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Partnership ID:</strong> #<?php echo $partnership['id']; ?><br>
                                            <strong>Status:</strong> 
                                            <span class="partnership-status status-<?php echo strtolower($partnership['status'] ?? 'pending'); ?>">
                                                <?php echo ucfirst($partnership['status'] ?? 'pending'); ?>
                                            </span><br>
                                            <strong>Created:</strong> <?php echo date('M j, Y', strtotime($partnership['created_at'])); ?><br>
                                            <strong>Last Updated:</strong> <?php echo date('M j, Y', strtotime($partnership['updated_at'] ?? $partnership['created_at'])); ?>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Compatibility Score:</strong><br>
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
                                    <form method="POST" class="mb-3">
                                        <input type="hidden" name="action" value="update_status">
                                        <label class="form-label">Update Status:</label>
                                        <select name="status" class="form-select mb-2">
                                            <option value="pending" <?php echo ($partnership['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="active" <?php echo ($partnership['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="completed" <?php echo ($partnership['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo ($partnership['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" class="btn btn-primary btn-sm w-100">
                                            <i class="fas fa-save"></i> Update Status
                                        </button>
                                    </form>
                                    
                                    <form method="POST">
                                        <input type="hidden" name="action" value="add_note">
                                        <label class="form-label">Admin Notes:</label>
                                        <textarea name="note" class="form-control mb-2" rows="3" placeholder="Add admin notes..."><?php echo htmlspecialchars($partnership['admin_notes'] ?? ''); ?></textarea>
                                        <button type="submit" class="btn btn-secondary btn-sm w-100">
                                            <i class="fas fa-edit"></i> Save Notes
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Projects Comparison -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="project-card">
                                <h5><i class="fas fa-project-diagram text-primary"></i> Project 1</h5>
                                <h6><?php echo htmlspecialchars($project1['name'] ?? 'Unknown Project'); ?></h6>
                                <p class="text-muted"><?php echo htmlspecialchars($project1['description'] ?? 'No description available'); ?></p>
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">Sector:</small><br>
                                        <strong><?php echo htmlspecialchars($project1['sector'] ?? 'Unknown'); ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Stage:</small><br>
                                        <strong><?php echo htmlspecialchars($project1['development_stage'] ?? 'Unknown'); ?></strong>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-6">
                                        <small class="text-muted">Partner Type:</small><br>
                                        <strong><?php echo htmlspecialchars($project1['partner_type'] ?? 'Unknown'); ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Scope:</small><br>
                                        <strong><?php echo htmlspecialchars($project1['geographic_scope'] ?? 'Unknown'); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="project-card">
                                <h5><i class="fas fa-project-diagram text-success"></i> Project 2</h5>
                                <h6><?php echo htmlspecialchars($project2['name'] ?? 'Unknown Project'); ?></h6>
                                <p class="text-muted"><?php echo htmlspecialchars($project2['description'] ?? 'No description available'); ?></p>
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">Sector:</small><br>
                                        <strong><?php echo htmlspecialchars($project2['sector'] ?? 'Unknown'); ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Stage:</small><br>
                                        <strong><?php echo htmlspecialchars($project2['development_stage'] ?? 'Unknown'); ?></strong>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-6">
                                        <small class="text-muted">Partner Type:</small><br>
                                        <strong><?php echo htmlspecialchars($project2['partner_type'] ?? 'Unknown'); ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Scope:</small><br>
                                        <strong><?php echo htmlspecialchars($project2['geographic_scope'] ?? 'Unknown'); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Compatibility Analysis -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-chart-line"></i>
                                        Compatibility Analysis
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($compatibility_factors as $factor): ?>
                                        <div class="card factor-card">
                                            <div class="card-body py-2">
                                                <div class="row align-items-center">
                                                    <div class="col-md-4">
                                                        <strong><?php echo htmlspecialchars($factor['factor']); ?></strong>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <span class="factor-score"><?php echo $factor['score']; ?> pts</span>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <small class="text-muted"><?php echo htmlspecialchars($factor['description']); ?></small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Partnership History -->
                    <?php if (!empty($partnership_history)): ?>
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-history"></i>
                                            Partnership History
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <?php foreach ($partnership_history as $history): ?>
                                            <div class="history-item">
                                                <div class="d-flex justify-content-between">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($history['action']); ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($history['description'] ?? ''); ?></small>
                                                    </div>
                                                    <div class="text-end">
                                                        <small class="text-muted">
                                                            <?php echo date('M j, Y H:i', strtotime($history['created_at'])); ?>
                                                        </small>
                                                        <br>
                                                        <small class="text-muted">
                                                            by <?php echo htmlspecialchars($history['user_name'] ?? 'System'); ?>
                                                        </small>
                                    </div>
                                </div>
                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <?php include 'template_footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/scripts.js"></script>
</body>
</html> 