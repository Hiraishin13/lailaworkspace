<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/config.php';

// Vérifier les permissions admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/views/auth/login.php');
    exit();
}

// Récupérer les statistiques de base
$stats = array();

try {
    // Nombre total de projets
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM projects");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_projects'] = $result['count'];
    
    // Nombre d'utilisateurs
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role_id = 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_users'] = $result['count'];
    
    // Nombre de blocs BMC
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM bmc");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_bmc_blocks'] = $result['count'];
    
    // Nombre d'hypothèses
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM hypotheses");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_hypotheses'] = $result['count'];
    
    // Utilisateurs actifs (30 derniers jours)
    $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) as count FROM projects WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['active_users'] = $result['count'];
    
    // Projets avec BMC complets (au moins 5 blocs)
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM projects p 
        WHERE (
            SELECT COUNT(*) 
            FROM bmc b 
            WHERE b.project_id = p.id AND b.content != '' AND b.content != 'Non spécifié'
        ) >= 5
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['completed_projects'] = $result['count'];
    
    // Projets avec hypothèses
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT p.id) as count 
        FROM projects p 
        INNER JOIN hypotheses h ON p.id = h.project_id
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['projects_with_hypotheses'] = $result['count'];
    
    // Calculer les pourcentages
    $stats['completion_rate'] = $stats['total_projects'] > 0 ? round(($stats['completed_projects'] / $stats['total_projects']) * 100, 1) : 0;
    $stats['hypothesis_rate'] = $stats['total_projects'] > 0 ? round(($stats['projects_with_hypotheses'] / $stats['total_projects']) * 100, 1) : 0;
    
    // Projets récents
    $stmt = $pdo->query("
        SELECT 
            p.id,
            p.name,
            p.created_at,
            CONCAT(u.first_name, ' ', u.last_name) as user_name,
            (
                SELECT COUNT(*) 
                FROM bmc b 
                WHERE b.project_id = p.id AND b.content != '' AND b.content != 'Non spécifié'
            ) as completed_blocks,
            (
                SELECT COUNT(*) 
                FROM hypotheses h 
                WHERE h.project_id = p.id
            ) as hypothesis_count
        FROM projects p 
        LEFT JOIN users u ON p.user_id = u.id
        ORDER BY p.created_at DESC 
        LIMIT 10
    ");
    $recent_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // En cas d'erreur, initialiser avec des valeurs par défaut
    $stats = array(
        'total_projects' => 0,
        'total_users' => 0,
        'total_bmc_blocks' => 0,
        'total_hypotheses' => 0,
        'active_users' => 0,
        'completed_projects' => 0,
        'projects_with_hypotheses' => 0,
        'completion_rate' => 0,
        'hypothesis_rate' => 0
    );
    $recent_projects = array();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Back-office Laila Workspace - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar-dark { background-color: #2c3e50 !important; }
        .admin-sidebar { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .kpi-card { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .kpi-icon { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; }
        .admin-card { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { background: #f8f9fa; border-bottom: 1px solid #dee2e6; padding: 15px 20px; border-radius: 10px 10px 0 0; }
        .card-body { padding: 20px; }
        .nav-link { color: #495057; padding: 10px 15px; border-radius: 5px; margin-bottom: 5px; }
        .nav-link:hover { background-color: #e9ecef; }
        .nav-link.active { background-color: #2c3e50; color: white; }
    </style>
</head>
<body>
    <!-- Navbar Admin -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-shield-lock"></i> Laila Workspace - Back-office
            </a>
            
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['user_role']) ?>
                </span>
                <a href="<?= BASE_URL ?>/views/auth/logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Déconnexion
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2">
                <div class="admin-sidebar">
                    <h6 class="mb-3">Navigation</h6>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard_simple.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="bi bi-people"></i> Utilisateurs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="projects.php">
                                <i class="bi bi-kanban"></i> Projets
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="analytics.php">
                                <i class="bi bi-graph-up"></i> Analytics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="partnerships.php">
                                <i class="bi bi-handshake"></i> Partenariats B2B
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="audit.php">
                                <i class="bi bi-shield-check"></i> Audit
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Contenu principal -->
            <div class="col-md-9 col-lg-10">
                <h1 class="mb-4">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </h1>
                
                <!-- KPIs -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <div class="d-flex align-items-center">
                                <div class="kpi-icon bg-primary me-3">
                                    <i class="bi bi-kanban"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0"><?= number_format($stats['total_projects']) ?></h3>
                                    <p class="mb-0 text-muted">Projets Créés</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <div class="d-flex align-items-center">
                                <div class="kpi-icon bg-success me-3">
                                    <i class="bi bi-people"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0"><?= number_format($stats['active_users']) ?></h3>
                                    <p class="mb-0 text-muted">Utilisateurs Actifs (30j)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <div class="d-flex align-items-center">
                                <div class="kpi-icon bg-warning me-3">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0"><?= $stats['completion_rate'] ?>%</h3>
                                    <p class="mb-0 text-muted">Taux de Complétion</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <div class="d-flex align-items-center">
                                <div class="kpi-icon bg-info me-3">
                                    <i class="bi bi-lightbulb"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0"><?= $stats['hypothesis_rate'] ?>%</h3>
                                    <p class="mb-0 text-muted">Passage Hypothèses</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistiques détaillées -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="admin-card">
                            <div class="card-header">
                                <h5><i class="bi bi-bar-chart"></i> Statistiques Globales</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <p><strong>Total Utilisateurs:</strong> <?= number_format($stats['total_users']) ?></p>
                                        <p><strong>Total Blocs BMC:</strong> <?= number_format($stats['total_bmc_blocks']) ?></p>
                                    </div>
                                    <div class="col-6">
                                        <p><strong>Total Hypothèses:</strong> <?= number_format($stats['total_hypotheses']) ?></p>
                                        <p><strong>Projets Complets:</strong> <?= number_format($stats['completed_projects']) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="admin-card">
                            <div class="card-header">
                                <h5><i class="bi bi-clock-history"></i> Projets Récents</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($recent_projects)): ?>
                                    <?php foreach (array_slice($recent_projects, 0, 5) as $project): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <strong><?= htmlspecialchars(substr($project['name'], 0, 30)) ?>...</strong>
                                                <br><small class="text-muted">par <?= htmlspecialchars($project['user_name'] ?: 'Utilisateur') ?></small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-primary"><?= $project['completed_blocks'] ?>/9</span>
                                                <?php if ($project['hypothesis_count'] > 0): ?>
                                                    <span class="badge bg-success ms-1"><?= $project['hypothesis_count'] ?> hyp</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted">Aucun projet récent</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message de succès -->
                <div class="alert alert-success">
                    <h4><i class="bi bi-check-circle"></i> Dashboard opérationnel !</h4>
                    <p>Votre back-office Laila Workspace fonctionne maintenant avec les vraies données de votre application.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="mb-2">
                        <i class="bi bi-shield-lock"></i> Laila Workspace - Back-office
                    </h6>
                    <p class="mb-0 text-muted small">
                        Plateforme de gestion Business Model Canvas - Version 2.0
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="d-flex flex-column flex-md-row justify-content-md-end align-items-md-center">
                        <div class="me-md-3 mb-2 mb-md-0">
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle"></i> Dashboard Simple
                            </span>
                        </div>
                        <div class="text-muted small">
                            <i class="bi bi-clock"></i> Dernière mise à jour : <?= date('d/m/Y H:i') ?>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">
                            © 2024 Laila Workspace - Tous droits réservés
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</body>
</html> 