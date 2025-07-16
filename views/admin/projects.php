<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/config.php';

// Vérifier les permissions admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/views/auth/login.php');
    exit();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
$per_page = in_array($per_page, [10, 20, 50, 100]) ? $per_page : 20; // Limiter les options
$offset = ($page - 1) * $per_page;

// Filtres
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$completion = isset($_GET['completion']) ? $_GET['completion'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

try {
    // Construire la requête avec filtres
    $where_conditions = [];
    $params = [];
    
    if ($search) {
        $where_conditions[] = "(p.name LIKE :search OR p.description LIKE :search OR CONCAT('Projet_', p.id) LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if ($status) {
        $where_conditions[] = "p.status = :status";
        $params[':status'] = $status;
    }
    
    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Compter le total
    $count_sql = "SELECT COUNT(*) as total FROM projects p $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_projects = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_projects / $per_page);
    
    // Récupérer les projets (anonymisés)
    $sql = "
        SELECT 
            p.id,
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
            ) as hypothesis_count,
            (
                SELECT COUNT(*) 
                FROM financial_plans fp 
                WHERE fp.project_id = p.id
            ) as financial_plan_count
        FROM projects p 
        $where_clause 
        ORDER BY p.$sort $order 
        LIMIT $per_page OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistiques
    $stats_sql = "
        SELECT 
            COUNT(*) as total_projects,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_projects,
            COUNT(CASE WHEN share_consent = 1 THEN 1 END) as shared_projects,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_projects
        FROM projects
    ";
    $stats = $pdo->query($stats_sql)->fetch(PDO::FETCH_ASSOC);
    
    // Répartition par complétion
    $completion_sql = "
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
    ";
    $completion_stats = $pdo->query($completion_sql)->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log('Erreur projets admin : ' . $e->getMessage());
    $projects = [];
    $stats = [];
    $completion_stats = [];
    $total_pages = 0;
}
?>

<?php include 'template_header_simple.php'; ?>

                <!-- Actions de page -->
                <div class="d-flex gap-2 mb-3">
                        <button class="btn btn-outline-secondary" onclick="refreshData()">
                            <i class="bi bi-arrow-clockwise"></i> Actualiser
                        </button>
                </div>

                    <!-- Statistiques -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="kpi-card">
                                <div class="kpi-icon bg-primary">
                                    <i class="bi bi-kanban"></i>
                                </div>
                                <div class="kpi-content">
                                    <h3><?= number_format($stats['total_projects'] ?? 0) ?></h3>
                                    <p>Total Projets</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="kpi-card">
                                <div class="kpi-icon bg-success">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                                <div class="kpi-content">
                                    <h3><?= number_format($stats['active_projects'] ?? 0) ?></h3>
                                    <p>Projets Actifs</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="kpi-card">
                                <div class="kpi-icon bg-info">
                                    <i class="bi bi-share"></i>
                                </div>
                                <div class="kpi-content">
                                    <h3><?= number_format($stats['shared_projects'] ?? 0) ?></h3>
                                    <p>Projets Partagés</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="kpi-card">
                                <div class="kpi-icon bg-warning">
                                    <i class="bi bi-plus-circle"></i>
                                </div>
                                <div class="kpi-content">
                                    <h3><?= number_format($stats['new_projects'] ?? 0) ?></h3>
                                    <p>Nouveaux 7j</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Répartition par complétion -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="admin-card">
                                <div class="card-header">
                                    <h5><i class="bi bi-pie-chart"></i> Répartition par Niveau de Complétion</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php foreach ($completion_stats as $stat): ?>
                                        <div class="col-md-2 col-sm-4 col-6 mb-3">
                                            <div class="completion-stat">
                                                <div class="completion-number"><?= number_format($stat['count']) ?></div>
                                                <div class="completion-label"><?= htmlspecialchars($stat['completion_status']) ?></div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filtres -->
                    <div class="admin-card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                            <div class="col-md-2">
                                    <label class="form-label">Recherche</label>
                                    <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nom, description, code...">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Statut</label>
                                    <select class="form-select" name="status">
                                        <option value="">Tous</option>
                                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Actif</option>
                                        <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Terminé</option>
                                        <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archivé</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Complétion</label>
                                    <select class="form-select" name="completion">
                                        <option value="">Tous</option>
                                        <option value="0-3" <?= $completion === '0-3' ? 'selected' : '' ?>>0-3 blocs</option>
                                        <option value="4-6" <?= $completion === '4-6' ? 'selected' : '' ?>>4-6 blocs</option>
                                        <option value="7-9" <?= $completion === '7-9' ? 'selected' : '' ?>>7-9 blocs</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Tri</label>
                                    <select class="form-select" name="sort">
                                        <option value="created_at" <?= $sort === 'created_at' ? 'selected' : '' ?>>Date création</option>
                                        <option value="updated_at" <?= $sort === 'updated_at' ? 'selected' : '' ?>>Date modification</option>
                                        <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Nom</option>
                                    </select>
                                </div>
                            <div class="col-md-2">
                                <label class="form-label">Par page</label>
                                <select class="form-select" name="per_page">
                                    <option value="10" <?= $per_page === 10 ? 'selected' : '' ?>>10</option>
                                    <option value="20" <?= $per_page === 20 ? 'selected' : '' ?>>20</option>
                                    <option value="50" <?= $per_page === 50 ? 'selected' : '' ?>>50</option>
                                    <option value="100" <?= $per_page === 100 ? 'selected' : '' ?>>100</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-search"></i> Filtrer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Liste des projets -->
                    <div class="admin-card">
                        <div class="card-header">
                            <h5><i class="bi bi-list-ul"></i> Liste des Projets (<?= number_format($total_projects) ?>)</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Code Projet</th>
                                            <th>Nom</th>
                                            <th>Utilisateur</th>
                                            <th>Complétion BMC</th>
                                            <th>Hypothèses</th>
                                            <th>Plan Financier</th>
                                            <th>Partage</th>
                                            <th>Date Création</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($projects as $project): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($project['project_code']) ?></span>
                                            </td>
                                            <td>
                                                <div class="project-name"><?= htmlspecialchars($project['name']) ?></div>
                                                <?php if ($project['description']): ?>
                                                <small class="text-muted"><?= htmlspecialchars(substr($project['description'], 0, 50)) ?>...</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark"><?= htmlspecialchars($project['user_code']) ?></span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <?php $percentage = ($project['completed_blocks'] / 9) * 100; ?>
                                                    <div class="progress-bar" style="width: <?= $percentage ?>%">
                                                        <?= $project['completed_blocks'] ?>/9
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?= $project['hypothesis_count'] ?></span>
                                            </td>
                                            <td>
                                                <?php if ($project['financial_plan_count'] > 0): ?>
                                                <span class="badge bg-success">Oui</span>
                                                <?php else: ?>
                                                <span class="badge bg-secondary">Non</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($project['share_consent']): ?>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-shield-check"></i> Oui
                                                </span>
                                                <?php else: ?>
                                                <span class="badge bg-warning">
                                                    <i class="bi bi-shield-x"></i> Non
                                                </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= date('d/m/Y', strtotime($project['created_at'])) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="project_details.php?id=<?= $project['id'] ?>" class="btn btn-outline-primary" title="Voir détails">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <button class="btn btn-outline-warning" title="Modifier" onclick="editProject(<?= $project['id'] ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" title="Archiver" onclick="archiveProject(<?= $project['id'] ?>)">
                                                        <i class="bi bi-archive"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div class="text-muted">
                                    Affichage de <?= count($projects) ?> projets sur <?= $total_projects ?> au total
                                    (<?= $per_page ?> par page)
                                </div>
                            <nav aria-label="Pagination des projets">
                                    <ul class="pagination pagination-sm mb-0">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&completion=<?= urlencode($completion) ?>&sort=<?= urlencode($sort) ?>&order=<?= urlencode($order) ?>&per_page=<?= $per_page ?>">
                                                    <i class="bi bi-chevron-left"></i> Précédent
                                                </a>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">
                                                    <i class="bi bi-chevron-left"></i> Précédent
                                                </span>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php
                                        $start_page = max(1, $page - 2);
                                        $end_page = min($total_pages, $page + 2);
                                        
                                        if ($start_page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=1&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&completion=<?= urlencode($completion) ?>&sort=<?= urlencode($sort) ?>&order=<?= urlencode($order) ?>&per_page=<?= $per_page ?>">1</a>
                                            </li>
                                            <?php if ($start_page > 2): ?>
                                                <li class="page-item disabled">
                                                    <span class="page-link">...</span>
                                                </li>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&completion=<?= urlencode($completion) ?>&sort=<?= urlencode($sort) ?>&order=<?= urlencode($order) ?>&per_page=<?= $per_page ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                    <?php endfor; ?>
                                        
                                        <?php if ($end_page < $total_pages): ?>
                                            <?php if ($end_page < $total_pages - 1): ?>
                                                <li class="page-item disabled">
                                                    <span class="page-link">...</span>
                                                </li>
                                            <?php endif; ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $total_pages ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&completion=<?= urlencode($completion) ?>&sort=<?= urlencode($sort) ?>&order=<?= urlencode($order) ?>&per_page=<?= $per_page ?>">
                                                    <?= $total_pages ?>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&completion=<?= urlencode($completion) ?>&sort=<?= urlencode($sort) ?>&order=<?= urlencode($order) ?>&per_page=<?= $per_page ?>">
                                                    Suivant <i class="bi bi-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">
                                                    Suivant <i class="bi bi-chevron-right"></i>
                                                </span>
                                            </li>
                                        <?php endif; ?>
                                </ul>
                            </nav>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        function editProject(projectId) {
            // Ouvrir modal d'édition
            alert('Fonctionnalité d\'édition à implémenter pour le projet ' + projectId);
        }
        
        function archiveProject(projectId) {
            if (confirm('Êtes-vous sûr de vouloir archiver ce projet ?')) {
                // Appel AJAX pour archiver
                $.post('archive_project.php', {
                    project_id: projectId
                })
                .done(function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Erreur : ' + response.message);
                    }
                })
                .fail(function() {
                    alert('Erreur lors de l\'archivage');
                });
            }
        }
    </script>

    <style>
        .completion-stat {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 1px solid var(--admin-border);
        }
        
        .completion-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--admin-primary);
        }
        
        .completion-label {
            font-size: 0.8rem;
            color: var(--admin-secondary);
            margin-top: 5px;
        }
        
        .project-name {
            font-weight: 600;
            color: var(--admin-primary);
        }
    </style>

            </div>
        </div>
    </div>

<?php include 'template_footer_simple.php'; ?> 