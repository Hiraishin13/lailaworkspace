<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/config.php';

// Vérifier les permissions admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/views/auth/login.php');
    exit();
}

// Récupérer les KPIs clés avec anonymisation
try {
    // 1. KPIs de base
    $kpis = array();
    
    // Nombre total de BMC créés
    $stmt = $pdo->query("SELECT COUNT(*) as total_bmc FROM projects");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $kpis['total_bmc'] = $result['total_bmc'] ?? 0;
    
    // Taux de complétion (BMC avec au moins 5 blocs remplis)
    $stmt = $pdo->query("
        SELECT COUNT(*) as completed_bmc 
        FROM projects p 
        WHERE (
            SELECT COUNT(*) 
            FROM bmc b 
            WHERE b.project_id = p.id AND b.content != '' AND b.content != 'Non spécifié'
        ) >= 5
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $kpis['completed_bmc'] = $result['completed_bmc'] ?? 0;
    $kpis['completion_rate'] = $kpis['total_bmc'] > 0 ? round(($kpis['completed_bmc'] / $kpis['total_bmc']) * 100, 1) : 0;
    
    // Passages aux hypothèses
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT p.id) as with_hypotheses 
        FROM projects p 
        INNER JOIN hypotheses h ON p.id = h.project_id
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $kpis['with_hypotheses'] = $result['with_hypotheses'] ?? 0;
    $kpis['hypothesis_rate'] = $kpis['total_bmc'] > 0 ? round(($kpis['with_hypotheses'] / $kpis['total_bmc']) * 100, 1) : 0;
    
    // Utilisation du plan financier
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT p.id) as with_financial 
        FROM projects p 
        INNER JOIN financial_plans fp ON p.id = fp.project_id
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $kpis['with_financial'] = $result['with_financial'] ?? 0;
    $kpis['financial_rate'] = $kpis['total_bmc'] > 0 ? round(($kpis['with_financial'] / $kpis['total_bmc']) * 100, 1) : 0;
    
    // 2. Temps moyen par étape (calculé sur les 30 derniers jours)
    $stmt = $pdo->query("
        SELECT 
            AVG(TIMESTAMPDIFF(MINUTE, p.created_at, 
                (SELECT MAX(created_at) FROM bmc WHERE project_id = p.id)
            )) as avg_bmc_time,
            AVG(TIMESTAMPDIFF(MINUTE, p.created_at, 
                (SELECT MAX(created_at) FROM hypotheses WHERE project_id = p.id)
            )) as avg_hypothesis_time,
            AVG(TIMESTAMPDIFF(MINUTE, p.created_at, 
                (SELECT MAX(created_at) FROM financial_plans WHERE project_id = p.id)
            )) as avg_financial_time
        FROM projects p
        WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $kpis['avg_bmc_time'] = round($result['avg_bmc_time'] ?? 0);
    $kpis['avg_hypothesis_time'] = round($result['avg_hypothesis_time'] ?? 0);
    $kpis['avg_financial_time'] = round($result['avg_financial_time'] ?? 0);
    
    // 3. Taux d'abandon (projets créés mais non complétés après 7 jours)
    $stmt = $pdo->query("
        SELECT COUNT(*) as abandoned_projects
        FROM projects p
        WHERE p.created_at <= DATE_SUB(NOW(), INTERVAL 7 DAY)
        AND (
            SELECT COUNT(*) 
            FROM bmc b 
            WHERE b.project_id = p.id AND b.content != '' AND b.content != 'Non spécifié'
        ) < 3
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $kpis['abandoned_projects'] = $result['abandoned_projects'] ?? 0;
    $kpis['abandonment_rate'] = $kpis['total_bmc'] > 0 ? round(($kpis['abandoned_projects'] / $kpis['total_bmc']) * 100, 1) : 0;
    
    // 4. Segmentation par secteur (anonymisé)
    $stmt = $pdo->query("
        SELECT 
            CASE 
                WHEN p.target_market IS NULL OR p.target_market = '' THEN 'Non spécifié'
                ELSE p.target_market 
            END as sector,
            COUNT(*) as count
        FROM projects p 
        GROUP BY sector 
        ORDER BY count DESC 
        LIMIT 5
    ");
    $sector_segments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 5. Tous les BMC existants (anonymisés) - avec pagination
    $bmc_per_page = 15; // Limiter à 15 projets par page
    $bmc_page = isset($_GET['bmc_page']) ? (int)$_GET['bmc_page'] : 1;
    $bmc_offset = ($bmc_page - 1) * $bmc_per_page;
    
    // Compter le total
    $count_stmt = $pdo->query("SELECT COUNT(*) as total FROM projects");
    $total_bmc_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_bmc_pages = ceil($total_bmc_count / $bmc_per_page);
    
    $stmt = $pdo->query("
        SELECT 
            p.id,
            CONCAT('Projet_', p.id) as project_code,
            p.name,
            p.description,
            p.created_at,
            CONCAT('User_', p.user_id) as user_code,
            p.target_market as sector,
            p.status,
            (
                SELECT COUNT(*) 
                FROM bmc b 
                WHERE b.project_id = p.id AND b.content != '' AND b.content != 'Non spécifié'
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
            ) as financial_plan_count,
            (
                SELECT SUM(fp.total_revenue) 
                FROM financial_plans fp 
                WHERE fp.project_id = p.id
            ) as total_revenue
        FROM projects p 
        ORDER BY p.created_at DESC
        LIMIT $bmc_per_page OFFSET $bmc_offset
    ");
    $all_bmc = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 6. Projets récents (anonymisés) - 10 derniers
    $recent_projects = array_slice($all_bmc, 0, 10);
    
    // 7. Statistiques détaillées des blocs BMC
    $stmt = $pdo->query("
        SELECT 
            block_name,
            COUNT(*) as total_blocks,
            COUNT(CASE WHEN content != '' AND content != 'Non spécifié' THEN 1 END) as completed_blocks,
            ROUND(
                (COUNT(CASE WHEN content != '' AND content != 'Non spécifié' THEN 1 END) / COUNT(*)) * 100, 1
            ) as completion_rate
        FROM bmc 
        GROUP BY block_name 
        ORDER BY completion_rate DESC
    ");
    $bmc_block_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 8. Alertes proactives
    $alerts = array();
    
    // Projets abandonnés récemment
    $stmt = $pdo->query("
        SELECT COUNT(*) as recent_abandoned
        FROM projects p
        WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL 3 DAY)
        AND (
            SELECT COUNT(*) 
            FROM bmc b 
            WHERE b.project_id = p.id AND b.content != '' AND b.content != 'Non spécifié'
        ) < 2
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result['recent_abandoned'] > 0) {
        $alerts[] = array(
            'type' => 'warning',
            'message' => $result['recent_abandoned'] . ' projet(s) abandonné(s) récemment',
            'icon' => 'bi-exclamation-triangle'
        );
    }
    
    // Erreurs système (si la table existe)
    try {
        $stmt = $pdo->query("
            SELECT COUNT(*) as error_count
            FROM system_logs 
            WHERE level = 'ERROR' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result['error_count'] > 0) {
            $alerts[] = array(
                'type' => 'danger',
                'message' => $result['error_count'] . ' erreur(s) système détectée(s)',
                'icon' => 'bi-x-circle'
            );
        }
    } catch (PDOException $e) {
        // Table system_logs n'existe pas encore
    }
    
    // Projets sans hypothèses
    $stmt = $pdo->query("
        SELECT COUNT(*) as no_hypotheses
        FROM projects p
        WHERE NOT EXISTS (
            SELECT 1 FROM hypotheses h WHERE h.project_id = p.id
        )
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result['no_hypotheses'] > 0) {
        $alerts[] = array(
            'type' => 'info',
            'message' => $result['no_hypotheses'] . ' projet(s) sans hypothèses',
            'icon' => 'bi-lightbulb'
        );
    }
    
} catch (PDOException $e) {
    error_log('Erreur dashboard admin : ' . $e->getMessage());
    $kpis = array_fill_keys(['total_bmc', 'completed_bmc', 'completion_rate', 'with_hypotheses', 'hypothesis_rate', 'with_financial', 'financial_rate', 'avg_bmc_time', 'avg_hypothesis_time', 'avg_financial_time', 'abandoned_projects', 'abandonment_rate'], 0);
    $sector_segments = array();
    $all_bmc = array();
    $recent_projects = array();
    $bmc_block_stats = array();
    $alerts = array();
}
?>

<?php include 'template_header_simple.php'; ?>

                <!-- Actions de page -->
                <div class="d-flex gap-2 mb-3">
                        <button class="btn btn-outline-primary btn-sm" onclick="refreshData()">
                            <i class="bi bi-arrow-clockwise"></i> Actualiser
                        </button>
                </div>

                <!-- Message de bienvenue pour l'admin -->
                <?php if (isset($_SESSION['admin_welcome']) && $_SESSION['admin_welcome']): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <i class="bi bi-check-circle"></i>
                    <strong>Bienvenue <?= htmlspecialchars($_SESSION['admin_name']) ?> !</strong> 
                    Vous êtes connecté en tant qu'administrateur. Accédez à toutes les fonctionnalités du back-office.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" onclick="dismissWelcome()"></button>
                </div>
                <?php endif; ?>

                <!-- Alertes système -->
                <?php if (!empty($alerts)): ?>
                <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Alertes :</strong> <?= count($alerts) ?> problème(s) détecté(s)
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- KPIs Principaux -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <div class="d-flex align-items-center">
                                <div class="kpi-icon bg-primary">
                                    <i class="bi bi-kanban"></i>
                                </div>
                                <div class="kpi-content">
                                    <h3><?= number_format($kpis['total_bmc']) ?></h3>
                                    <p>BMC Créés</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <div class="d-flex align-items-center">
                                <div class="kpi-icon bg-success">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                                <div class="kpi-content">
                                    <h3><?= $kpis['completion_rate'] ?>%</h3>
                                    <p>Complétion</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <div class="d-flex align-items-center">
                                <div class="kpi-icon bg-info">
                                    <i class="bi bi-lightbulb"></i>
                                </div>
                                <div class="kpi-content">
                                    <h3><?= $kpis['hypothesis_rate'] ?>%</h3>
                                    <p>Hypothèses</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <div class="d-flex align-items-center">
                                <div class="kpi-icon bg-warning">
                                    <i class="bi bi-graph-up"></i>
                                </div>
                                <div class="kpi-content">
                                    <h3><?= $kpis['financial_rate'] ?>%</h3>
                                    <p>Plan Financier</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Métriques -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="admin-card">
                            <div class="card-header">
                                <h5><i class="bi bi-clock"></i> Temps Moyen</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6">
                                        <h4><?= $kpis['avg_bmc_time'] ?> min</h4>
                                        <small class="text-muted">BMC</small>
                                    </div>
                                    <div class="col-6">
                                        <h4><?= $kpis['avg_hypothesis_time'] ?> min</h4>
                                        <small class="text-muted">Hypothèses</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="admin-card">
                            <div class="card-header">
                                <h5><i class="bi bi-exclamation-triangle"></i> Taux d'Abandon</h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center">
                                    <h4 class="text-danger"><?= $kpis['abandonment_rate'] ?>%</h4>
                                    <small class="text-muted"><?= $kpis['abandoned_projects'] ?> projets</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Segmentation -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="admin-card">
                            <div class="card-header">
                                <h5><i class="bi bi-pie-chart"></i> Secteurs</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="sectorChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="admin-card">
                            <div class="card-header">
                                <h5><i class="bi bi-list-ul"></i> Top Secteurs</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($sector_segments as $sector): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span><?= htmlspecialchars($sector['sector']) ?></span>
                                    <span class="badge bg-primary"><?= $sector['count'] ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Projets récents -->
                <div class="admin-card">
                    <div class="card-header">
                        <h5><i class="bi bi-clock-history"></i> Projets Récents</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Nom</th>
                                        <th>Secteur</th>
                                        <th>Progression</th>
                                        <th>Hypothèses</th>
                                        <th>Plan Financier</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_projects)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="bi bi-inbox display-1 text-muted"></i>
                                            <p class="text-muted">Aucun projet récent</p>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($recent_projects as $project): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($project['project_code']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($project['name']) ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark"><?= htmlspecialchars($project['sector'] ?: 'Non spécifié') ?></span>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar" style="width: <?= ($project['completed_blocks'] / 9) * 100 ?>%">
                                                    <?= $project['completed_blocks'] ?>/9
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= $project['hypothesis_count'] ?></span>
                                        </td>
                                        <td>
                                            <?php if ($project['financial_plan_count'] > 0): ?>
                                            <span class="badge bg-success"><i class="bi bi-check"></i></span>
                                            <?php else: ?>
                                            <span class="badge bg-secondary"><i class="bi bi-x"></i></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($project['created_at'])) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewProject(<?= $project['id'] ?>)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tous les BMC existants -->
                <div class="admin-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="bi bi-grid-3x3-gap"></i> Tous les BMC Existants (<?= $total_bmc_count ?>)</h5>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="exportBMCData()">
                                <i class="bi bi-download"></i> Exporter
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="generatePartnerships()">
                                <i class="bi bi-people"></i> Matching B2B
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="allBmcTable">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Nom</th>
                                        <th>Description</th>
                                        <th>Secteur</th>
                                        <th>Statut</th>
                                        <th>Progression BMC</th>
                                        <th>Hypothèses</th>
                                        <th>Plan Financier</th>
                                        <th>Revenus (€)</th>
                                        <th>Date Création</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($all_bmc)): ?>
                                    <tr>
                                        <td colspan="11" class="text-center py-4">
                                            <i class="bi bi-inbox display-1 text-muted"></i>
                                            <p class="text-muted">Aucun BMC trouvé</p>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($all_bmc as $project): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($project['project_code']) ?></span>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($project['name']) ?></strong>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?= htmlspecialchars(substr($project['description'] ?? '', 0, 50)) ?>
                                                <?= strlen($project['description'] ?? '') > 50 ? '...' : '' ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark"><?= htmlspecialchars($project['sector'] ?: 'Non spécifié') ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = match($project['status']) {
                                                'active' => 'bg-success',
                                                'inactive' => 'bg-warning',
                                                'archived' => 'bg-secondary',
                                                default => 'bg-light text-dark'
                                            };
                                            ?>
                                            <span class="badge <?= $status_class ?>"><?= htmlspecialchars($project['status']) ?></span>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar <?= $project['completed_blocks'] >= 7 ? 'bg-success' : ($project['completed_blocks'] >= 4 ? 'bg-warning' : 'bg-danger') ?>" 
                                                     style="width: <?= ($project['completed_blocks'] / 9) * 100 ?>%">
                                                    <?= $project['completed_blocks'] ?>/9
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= $project['hypothesis_count'] ?></span>
                                        </td>
                                        <td>
                                            <?php if ($project['financial_plan_count'] > 0): ?>
                                            <span class="badge bg-success"><i class="bi bi-check"></i> <?= $project['financial_plan_count'] ?></span>
                                            <?php else: ?>
                                            <span class="badge bg-secondary"><i class="bi bi-x"></i> 0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($project['total_revenue'] > 0): ?>
                                            <span class="text-success fw-bold"><?= number_format($project['total_revenue'], 0, ',', ' ') ?> €</span>
                                            <?php else: ?>
                                            <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($project['created_at'])) ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-primary" onclick="viewProject(<?= $project['id'] ?>)" title="Voir le projet">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-info" onclick="viewBMC(<?= $project['id'] ?>)" title="Voir le BMC">
                                                    <i class="bi bi-grid-3x3"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-success" onclick="viewHypotheses(<?= $project['id'] ?>)" title="Voir les hypothèses">
                                                    <i class="bi bi-lightbulb"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination pour les BMC -->
                        <?php if ($total_bmc_pages > 1): ?>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="text-muted">
                                Affichage de <?= count($all_bmc) ?> projets sur <?= $total_bmc_count ?> au total
                                (<?= $bmc_per_page ?> par page)
                            </div>
                            <nav aria-label="Pagination des BMC">
                                <ul class="pagination pagination-sm mb-0">
                                    <?php if ($bmc_page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?bmc_page=<?= $bmc_page - 1 ?>">
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
                                    $start_page = max(1, $bmc_page - 2);
                                    $end_page = min($total_bmc_pages, $bmc_page + 2);
                                    
                                    if ($start_page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?bmc_page=1">1</a>
                                        </li>
                                        <?php if ($start_page > 2): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <li class="page-item <?= $i === $bmc_page ? 'active' : '' ?>">
                                            <a class="page-link" href="?bmc_page=<?= $i ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($end_page < $total_bmc_pages): ?>
                                        <?php if ($end_page < $total_bmc_pages - 1): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?bmc_page=<?= $total_bmc_pages ?>">
                                                <?= $total_bmc_pages ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php if ($bmc_page < $total_bmc_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?bmc_page=<?= $bmc_page + 1 ?>">
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

                <!-- Statistiques des blocs BMC -->
                <div class="admin-card">
                    <div class="card-header">
                        <h5><i class="bi bi-bar-chart"></i> Statistiques des Blocs BMC</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Bloc</th>
                                        <th>Total</th>
                                        <th>Complétés</th>
                                        <th>Taux de Complétion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bmc_block_stats as $block): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($block['block_name']) ?></strong></td>
                                        <td><?= $block['total_blocks'] ?></td>
                                        <td><?= $block['completed_blocks'] ?></td>
                                        <td>
                                            <div class="progress" style="height: 15px;">
                                                <div class="progress-bar <?= $block['completion_rate'] >= 80 ? 'bg-success' : ($block['completion_rate'] >= 50 ? 'bg-warning' : 'bg-danger') ?>" 
                                                     style="width: <?= $block['completion_rate'] ?>%">
                                                    <?= $block['completion_rate'] ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include 'template_footer_simple.php'; ?>
    <script>
        // Graphique des secteurs
        const sectorData = <?= json_encode($sector_segments) ?>;
        const ctx = document.getElementById('sectorChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: sectorData.map(item => item.sector),
                datasets: [{
                    data: sectorData.map(item => item.count),
                    backgroundColor: [
                        '#2c3e50', '#3498db', '#e74c3c', '#f39c12', '#27ae60'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Fonctions utilitaires
        function refreshData() {
            location.reload();
        }

        function viewProject(projectId) {
            // Logique de visualisation du projet
            window.open('project_details.php?id=' + projectId, '_blank');
        }

        function viewBMC(projectId) {
            // Redirection vers la page BMC
            window.open('../../views/bmc/bmc.php?project_id=' + projectId, '_blank');
        }

        function viewHypotheses(projectId) {
            // Redirection vers la page des hypothèses
            window.open('../../views/bmc/hypotheses.php?project_id=' + projectId, '_blank');
        }

        function exportBMCData() {
            // Export des données BMC
            const table = document.getElementById('allBmcTable');
            const rows = table.querySelectorAll('tbody tr');
            let csv = 'Code,Nom,Description,Secteur,Statut,Progression BMC,Hypothèses,Plan Financier,Revenus (€),Date Création\n';
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length > 1) {
                    const rowData = [];
                    cells.forEach((cell, index) => {
                        if (index < 10) { // Exclure la colonne Actions
                            let text = cell.textContent.trim();
                            // Nettoyer le texte pour CSV
                            text = text.replace(/"/g, '""');
                            rowData.push('"' + text + '"');
                        }
                    });
                    csv += rowData.join(',') + '\n';
                }
            });
            
            // Télécharger le fichier CSV
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'bmc_data_' + new Date().toISOString().split('T')[0] + '.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function generatePartnerships() {
            // Redirection vers la page de génération de partenariats
            window.open('partnerships.php', '_blank');
        }

        // Fonction pour supprimer le message de bienvenue
        function dismissWelcome() {
            // Faire une requête AJAX pour supprimer le flag de session
            fetch('<?= BASE_URL ?>/views/admin/api/dismiss_welcome.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            }).then(response => {
                if (response.ok) {
                    // Masquer l'alerte
                    const alert = document.querySelector('.alert-success');
                    if (alert) {
                        alert.style.display = 'none';
                    }
                }
            });
        }

        // Auto-refresh toutes les 5 minutes
        setInterval(refreshData, 300000);

        // Initialisation des tooltips Bootstrap
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html> 