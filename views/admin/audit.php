<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/config.php';

// Vérifier les permissions admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/views/auth/login.php');
    exit();
}

// Fonctions utilitaires pour l'audit
function getActionColor($action_type) {
    switch ($action_type) {
        case 'login':
            return 'success';
        case 'logout':
            return 'secondary';
        case 'create':
            return 'primary';
        case 'update':
            return 'warning';
        case 'delete':
            return 'danger';
        case 'export':
            return 'info';
        case 'consent':
            return 'success';
        default:
            return 'secondary';
    }
}

function getActionIcon($action_type) {
    switch ($action_type) {
        case 'login':
            return 'bi-box-arrow-in-right';
        case 'logout':
            return 'bi-box-arrow-left';
        case 'create':
            return 'bi-plus-circle';
        case 'update':
            return 'bi-pencil';
        case 'delete':
            return 'bi-trash';
        case 'export':
            return 'bi-download';
        case 'consent':
            return 'bi-shield-check';
        default:
            return 'bi-question-circle';
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Filtres
$action_type = isset($_GET['action_type']) ? $_GET['action_type'] : '';
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

try {
    // Construire la requête avec filtres
    $where_conditions = [];
    $params = [];
    
    if ($action_type) {
        $where_conditions[] = "action_type = :action_type";
        $params[':action_type'] = $action_type;
    }
    
    if ($user_id) {
        $where_conditions[] = "user_id = :user_id";
        $params[':user_id'] = $user_id;
    }
    
    if ($date_from) {
        $where_conditions[] = "created_at >= :date_from";
        $params[':date_from'] = $date_from . ' 00:00:00';
    }
    
    if ($date_to) {
        $where_conditions[] = "created_at <= :date_to";
        $params[':date_to'] = $date_to . ' 23:59:59';
    }
    
    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Compter le total
    $count_sql = "SELECT COUNT(*) as total FROM audit_log $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_logs = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_logs / $limit);
    
    // Récupérer les logs d'audit (anonymisés)
    $sql = "
        SELECT 
            id,
            action_type,
            CONCAT('User_', user_id) as user_code,
            resource_type,
            resource_id,
            details,
            ip_address,
            user_agent,
            created_at
        FROM audit_log 
        $where_clause 
        ORDER BY created_at DESC 
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $audit_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistiques d'audit
    $stats_sql = "
        SELECT 
            COUNT(*) as total_actions,
            COUNT(DISTINCT user_id) as unique_users,
            COUNT(CASE WHEN action_type = 'create' THEN 1 END) as creates,
            COUNT(CASE WHEN action_type = 'update' THEN 1 END) as updates,
            COUNT(CASE WHEN action_type = 'delete' THEN 1 END) as deletes,
            COUNT(CASE WHEN action_type = 'login' THEN 1 END) as logins,
            COUNT(CASE WHEN action_type = 'logout' THEN 1 END) as logouts
        FROM audit_log 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ";
    $stats = $pdo->query($stats_sql)->fetch(PDO::FETCH_ASSOC);
    
    // Actions par type (7 derniers jours)
    $actions_sql = "
        SELECT 
            action_type,
            COUNT(*) as count
        FROM audit_log 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY action_type 
        ORDER BY count DESC
    ";
    $actions_by_type = $pdo->query($actions_sql)->fetchAll(PDO::FETCH_ASSOC);
    
    // Top utilisateurs par actions (anonymisés)
    $top_users_sql = "
        SELECT 
            CONCAT('User_', user_id) as user_code,
            COUNT(*) as action_count
        FROM audit_log 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY user_id 
        ORDER BY action_count DESC 
        LIMIT 10
    ";
    $top_users = $pdo->query($top_users_sql)->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log('Erreur audit admin : ' . $e->getMessage());
    $audit_logs = [];
    $stats = [];
    $actions_by_type = [];
    $top_users = [];
    $total_pages = 0;
}
?>

<?php include 'template_header_simple.php'; ?>\n\n                <!-- Actions de page -->
                    <h1 style="color: var(--primary); margin: 0;">
                        <i class="bi bi-shield-check"></i> Audit et Conformité
                    </h1>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary" onclick="refreshData()">
                            <i class="bi bi-arrow-clockwise"></i> Actualiser
                        </button>
                    </div>
                </div>

                    <!-- Statistiques RGPD -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="kpi-card">
                                <div class="kpi-icon bg-success">
                                    <i class="bi bi-shield-check"></i>
                                </div>
                                <div class="kpi-content">
                                    <h3><?= number_format($stats['unique_users'] ?? 0) ?></h3>
                                    <p>Utilisateurs Uniques</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="kpi-card">
                                <div class="kpi-icon bg-warning">
                                    <i class="bi bi-trash"></i>
                                </div>
                                <div class="kpi-content">
                                    <h3><?= number_format($stats['deletes'] ?? 0) ?></h3>
                                    <p>Supprimés</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="kpi-card">
                                <div class="kpi-icon bg-info">
                                    <i class="bi bi-share"></i>
                                </div>
                                <div class="kpi-content">
                                    <h3><?= number_format($stats['total_actions'] ?? 0) ?></h3>
                                    <p>Actions Totales</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="kpi-card">
                                <div class="kpi-icon bg-primary">
                                    <i class="bi bi-activity"></i>
                                </div>
                                <div class="kpi-content">
                                    <h3><?= number_format($total_logs) ?></h3>
                                    <p>Actions Auditées</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Répartition des actions -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="admin-card">
                                <div class="card-header">
                                    <h5><i class="bi bi-pie-chart"></i> Répartition des Actions (30j)</h5>
                                </div>
                                <div class="card-body">
                                    <div class="action-stats">
                                        <?php foreach ($actions_by_type as $stat): ?>
                                        <div class="action-stat-item">
                                            <div class="action-icon">
                                                <i class="bi <?= getActionIcon($stat['action_type']) ?>"></i>
                                            </div>
                                            <div class="action-info">
                                                <div class="action-name"><?= ucfirst($stat['action_type']) ?></div>
                                                <div class="action-count"><?= number_format($stat['count']) ?> actions</div>
                                            </div>
                                            <div class="action-percentage">
                                                <?= round(($stat['count'] / array_sum(array_column($actions_by_type, 'count'))) * 100, 1) ?>%
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="admin-card">
                                <div class="card-header">
                                    <h5><i class="bi bi-shield-check"></i> Conformité RGPD</h5>
                                </div>
                                <div class="card-body">
                                    <div class="gdpr-compliance">
                                        <div class="compliance-item success">
                                            <i class="bi bi-check-circle"></i>
                                            <div>
                                                <strong>Consentement Explicite</strong>
                                                <p>Tous les partages de données nécessitent un consentement explicite</p>
                                            </div>
                                        </div>
                                        <div class="compliance-item success">
                                            <i class="bi bi-check-circle"></i>
                                            <div>
                                                <strong>Anonymisation</strong>
                                                <p>Les données personnelles sont anonymisées dans les analyses</p>
                                            </div>
                                        </div>
                                        <div class="compliance-item success">
                                            <i class="bi bi-check-circle"></i>
                                            <div>
                                                <strong>Droit à l'Oubli</strong>
                                                <p>Les utilisateurs peuvent demander la suppression de leurs données</p>
                                            </div>
                                        </div>
                                        <div class="compliance-item success">
                                            <i class="bi bi-check-circle"></i>
                                            <div>
                                                <strong>Audit Trail</strong>
                                                <p>Toutes les actions sont enregistrées pour la traçabilité</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filtres -->
                    <div class="admin-card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Type d'Action</label>
                                    <select class="form-select" name="action_type">
                                        <option value="">Toutes les actions</option>
                                        <option value="login" <?= $action_type === 'login' ? 'selected' : '' ?>>Connexion</option>
                                        <option value="logout" <?= $action_type === 'logout' ? 'selected' : '' ?>>Déconnexion</option>
                                        <option value="create" <?= $action_type === 'create' ? 'selected' : '' ?>>Création</option>
                                        <option value="update" <?= $action_type === 'update' ? 'selected' : '' ?>>Modification</option>
                                        <option value="delete" <?= $action_type === 'delete' ? 'selected' : '' ?>>Suppression</option>
                                        <option value="export" <?= $action_type === 'export' ? 'selected' : '' ?>>Export</option>
                                        <option value="consent" <?= $action_type === 'consent' ? 'selected' : '' ?>>Consentement</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Date de début</label>
                                    <input type="date" class="form-control" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Date de fin</label>
                                    <input type="date" class="form-control" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-search"></i> Filtrer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Journal d'audit -->
                    <div class="admin-card">
                        <div class="card-header">
                            <h5><i class="bi bi-journal-text"></i> Journal d'Audit (<?= number_format($total_logs) ?> entrées)</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date/Heure</th>
                                            <th>Action</th>
                                            <th>Utilisateur</th>
                                            <th>Ressource</th>
                                            <th>Détails</th>
                                            <th>IP</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($audit_logs as $log): ?>
                                        <tr>
                                            <td>
                                                <small class="text-muted">
                                                    <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= getActionColor($log['action_type']) ?>">
                                                    <i class="bi <?= getActionIcon($log['action_type']) ?>"></i>
                                                    <?= ucfirst($log['action_type']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    <?= htmlspecialchars($log['user_code']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($log['resource_type'] && $log['resource_id']): ?>
                                                <span class="badge bg-info">
                                                    <?= ucfirst($log['resource_type']) ?> #<?= $log['resource_id'] ?>
                                                </span>
                                                <?php else: ?>
                                                <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars(substr($log['details'], 0, 50)) ?>
                                                    <?= strlen($log['details']) > 50 ? '...' : '' ?>
                                                </small>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($log['ip_address']) ?>
                                                </small>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                            <nav aria-label="Pagination du journal d'audit">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&action_type=<?= urlencode($action_type) ?>&user_id=<?= $user_id ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        .action-stats {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .action-stat-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .action-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--admin-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .action-info {
            flex: 1;
        }
        
        .action-name {
            font-weight: 600;
            color: var(--admin-primary);
        }
        
        .action-count {
            font-size: 0.9rem;
            color: var(--admin-secondary);
        }
        
        .action-percentage {
            font-weight: 600;
            color: var(--admin-success);
        }
        
        .gdpr-compliance {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .compliance-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 15px;
            border-radius: 8px;
        }
        
        .compliance-item.success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border: 1px solid #28a745;
            color: #155724;
        }
        
        .compliance-item i {
            margin-top: 2px;
            font-size: 1.2rem;
        }
        
        .compliance-item strong {
            display: block;
            margin-bottom: 5px;
        }
        
        .compliance-item p {
            margin: 0;
            font-size: 0.9rem;
        }
    </style>

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
                                <i class="bi bi-check-circle"></i> Audit Actif
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