<?php
session_start();
require_once '../../includes/db_connect.php';

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

$project_id = null;
$project = null;
$user = null;
$bmc_blocks = [];
$hypotheses = [];
$financial_plans = [];

// Récupérer l'ID du projet
if (isset($_GET['id'])) {
    $project_id = (int)$_GET['id'];
}

if ($project_id) {
    // Récupérer les détails du projet
    $stmt = $pdo->prepare("
        SELECT p.*, u.first_name, u.last_name, u.email, u.role_id
        FROM projects p
        JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($project) {
        $user = [
            'name' => $project['first_name'] . ' ' . $project['last_name'],
            'email' => $project['email'],
            'role_id' => $project['role_id']
        ];
        
        // Récupérer les blocs BMC
        $stmt = $pdo->prepare("SELECT * FROM bmc WHERE project_id = ? ORDER BY block_name");
        $stmt->execute([$project_id]);
        $bmc_blocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Récupérer les hypothèses
        $stmt = $pdo->prepare("SELECT * FROM hypotheses WHERE project_id = ? ORDER BY created_at DESC");
        $stmt->execute([$project_id]);
        $hypotheses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Récupérer les plans financiers
        $stmt = $pdo->prepare("SELECT * FROM financial_plans WHERE project_id = ? ORDER BY created_at DESC");
        $stmt->execute([$project_id]);
        $financial_plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $project) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $new_status = $_POST['status'];
                $stmt = $pdo->prepare("UPDATE projects SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$new_status, $project_id]);
                header("Location: project_details.php?id=" . $project_id);
                exit();
                break;
                
            case 'archive_project':
                $stmt = $pdo->prepare("UPDATE projects SET status = 'archived', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$project_id]);
                header("Location: projects.php?success=archived");
                exit();
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du Projet - Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/admin.css" rel="stylesheet">
    <style>
        .bmc-block {
            border-left: 4px solid #007bff;
            margin-bottom: 15px;
        }
        .completion-badge {
            font-size: 0.8rem;
        }
        .user-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
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
                        <i class="fas fa-project-diagram text-primary"></i>
                        Détails du Projet
                    </h1>
                    <a href="projects.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Retour aux Projets
                    </a>
                </div>

                <?php if (!$project): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Projet introuvable.
                    </div>
                <?php else: ?>
                    <!-- Informations générales -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-info-circle"></i>
                                        Informations du Projet
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Nom :</strong> <?php echo htmlspecialchars($project['name']); ?><br>
                                            <strong>Description :</strong> <?php echo htmlspecialchars($project['description'] ?? 'Aucune description'); ?><br>
                                            <strong>Marché cible :</strong> <?php echo htmlspecialchars($project['target_market'] ?? 'Non spécifié'); ?><br>
                                            <strong>Statut :</strong> 
                                            <span class="badge bg-<?php echo $project['status'] === 'active' ? 'success' : ($project['status'] === 'archived' ? 'secondary' : 'warning'); ?>">
                                                <?php echo ucfirst($project['status']); ?>
                                            </span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Créé le :</strong> <?php echo date('d/m/Y H:i', strtotime($project['created_at'])); ?><br>
                                            <strong>Dernière mise à jour :</strong> <?php echo date('d/m/Y H:i', strtotime($project['updated_at'])); ?><br>
                                            <strong>Partage autorisé :</strong> 
                                            <span class="badge bg-<?php echo $project['share_consent'] ? 'success' : 'warning'; ?>">
                                                <?php echo $project['share_consent'] ? 'Oui' : 'Non'; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-user"></i>
                                        Propriétaire
                                    </h5>
                                </div>
                                <div class="card-body user-info">
                                    <strong><?php echo htmlspecialchars($user['name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small><br>
                                    <span class="badge bg-<?php echo $user['role_id'] == 1 ? 'primary' : 'danger'; ?>">
                                        <?php echo $user['role_id'] == 1 ? 'Utilisateur' : 'Admin'; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-cogs"></i>
                                        Actions
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" class="mb-2">
                                        <input type="hidden" name="action" value="update_status">
                                        <select name="status" class="form-select form-select-sm mb-2">
                                            <option value="active" <?php echo $project['status'] === 'active' ? 'selected' : ''; ?>>Actif</option>
                                            <option value="inactive" <?php echo $project['status'] === 'inactive' ? 'selected' : ''; ?>>Inactif</option>
                                            <option value="archived" <?php echo $project['status'] === 'archived' ? 'selected' : ''; ?>>Archivé</option>
                                        </select>
                                        <button type="submit" class="btn btn-primary btn-sm w-100">
                                            <i class="fas fa-save"></i> Mettre à jour
                                        </button>
                                    </form>
                                    
                                    <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir archiver ce projet ?')">
                                        <input type="hidden" name="action" value="archive_project">
                                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                            <i class="fas fa-archive"></i> Archiver
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Blocs BMC -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-th-large"></i>
                                        Business Model Canvas
                                        <span class="badge bg-primary ms-2"><?php echo count($bmc_blocks); ?>/9 blocs</span>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($bmc_blocks)): ?>
                                        <p class="text-muted text-center">Aucun bloc BMC créé</p>
                                    <?php else: ?>
                                        <div class="row">
                                            <?php foreach ($bmc_blocks as $block): ?>
                                                <div class="col-md-6 mb-3">
                                                    <div class="card bmc-block">
                                                        <div class="card-body">
                                                            <h6 class="card-title"><?php echo htmlspecialchars($block['block_name']); ?></h6>
                                                            <p class="card-text"><?php echo htmlspecialchars($block['content'] ?? 'Vide'); ?></p>
                                                            <small class="text-muted">
                                                                Mis à jour le <?php echo date('d/m/Y H:i', strtotime($block['updated_at'])); ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hypothèses -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-lightbulb"></i>
                                        Hypothèses
                                        <span class="badge bg-info ms-2"><?php echo count($hypotheses); ?></span>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($hypotheses)): ?>
                                        <p class="text-muted text-center">Aucune hypothèse créée</p>
                                    <?php else: ?>
                                        <?php foreach ($hypotheses as $hypothesis): ?>
                                            <div class="card mb-2">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <h6 class="card-title"><?php echo htmlspecialchars($hypothesis['title']); ?></h6>
                                                            <p class="card-text"><?php echo htmlspecialchars($hypothesis['content']); ?></p>
                                                        </div>
                                                        <span class="badge bg-<?php echo $hypothesis['status'] === 'validated' ? 'success' : ($hypothesis['status'] === 'rejected' ? 'danger' : 'warning'); ?>">
                                                            <?php echo ucfirst($hypothesis['status']); ?>
                                                        </span>
                                                    </div>
                                                    <small class="text-muted">
                                                        Créée le <?php echo date('d/m/Y H:i', strtotime($hypothesis['created_at'])); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Plans financiers -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-chart-line"></i>
                                        Plans Financiers
                                        <span class="badge bg-success ms-2"><?php echo count($financial_plans); ?></span>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($financial_plans)): ?>
                                        <p class="text-muted text-center">Aucun plan financier créé</p>
                                    <?php else: ?>
                                        <?php foreach ($financial_plans as $plan): ?>
                                            <div class="card mb-2">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-8">
                                                            <h6 class="card-title"><?php echo htmlspecialchars($plan['title']); ?></h6>
                                                            <p class="card-text"><?php echo htmlspecialchars($plan['content']); ?></p>
                                                        </div>
                                                        <div class="col-md-4 text-end">
                                                            <div class="mb-2">
                                                                <strong>Revenus :</strong> <?php echo number_format($plan['total_revenue'], 2); ?> €
                                                            </div>
                                                            <div class="mb-2">
                                                                <strong>Coûts :</strong> <?php echo number_format($plan['total_costs'], 2); ?> €
                                                            </div>
                                                            <div>
                                                                <strong>Marge :</strong> 
                                                                <span class="badge bg-<?php echo $plan['profit_margin'] > 20 ? 'success' : 'warning'; ?>">
                                                                    <?php echo $plan['profit_margin']; ?>%
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <small class="text-muted">
                                                        Créé le <?php echo date('d/m/Y H:i', strtotime($plan['created_at'])); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <?php include 'template_footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 