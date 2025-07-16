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
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Initialiser les variables par défaut
$users = [];
$stats = [
    'total_users' => 0,
    'active_users' => 0,
    'recent_users' => 0,
    'new_users' => 0
];
$total_users = 0;
$total_pages = 0;

try {
    // Construire la requête avec filtres
    $where_conditions = [];
    $params = [];
    
    if ($search) {
        $where_conditions[] = "(email LIKE :search OR CONCAT('User_', id) LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if ($status) {
        $where_conditions[] = "status = :status";
        $params[':status'] = $status;
    }
    
    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Compter le total
    $count_sql = "SELECT COUNT(*) as total FROM users $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_users / $per_page);
    
    // Récupérer les utilisateurs (anonymisés)
    $sql = "
        SELECT 
            id,
            CONCAT('User_', id) as user_code,
            email,
            CASE 
                WHEN email LIKE '%@%' THEN CONCAT(SUBSTRING(email, 1, 2), '***', SUBSTRING_INDEX(email, '@', -1))
                ELSE '***@***'
            END as masked_email,
            role_id,
            created_at,
            (
                SELECT COUNT(*) 
                FROM projects 
                WHERE user_id = users.id
            ) as project_count,
            (
                SELECT COUNT(*) 
                FROM projects p 
                WHERE p.user_id = users.id 
                AND p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ) as recent_projects
        FROM users 
        $where_clause 
        ORDER BY $sort $order 
        LIMIT $per_page OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistiques
    $stats_sql = "
        SELECT 
            COUNT(*) as total_users,
            COUNT(CASE WHEN role_id = 1 THEN 1 END) as active_users,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as recent_users,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_users
        FROM users
    ";
    $stats_result = $pdo->query($stats_sql)->fetch(PDO::FETCH_ASSOC);
    $stats = [
        'total_users' => $stats_result['total_users'] ?? 0,
        'active_users' => $stats_result['active_users'] ?? 0,
        'recent_users' => $stats_result['recent_users'] ?? 0,
        'new_users' => $stats_result['new_users'] ?? 0
    ];
    
} catch (PDOException $e) {
    error_log('Erreur page utilisateurs admin : ' . $e->getMessage());
    // Les variables sont déjà initialisées par défaut
}

// Variables pour le template
$page_title = 'Gestion des Utilisateurs';
$page_icon = 'bi-people';
$current_page = 'users';
$sidebar_title = 'Utilisateurs';
$page_actions = '
    <button class="btn btn-outline-secondary" onclick="refreshData()">
        <i class="bi bi-arrow-clockwise"></i> Actualiser
    </button>
    <a href="add_user.php" class="btn btn-primary">
        <i class="bi bi-person-plus"></i> Ajouter Utilisateur
    </a>
';
?>

<?php include 'template_header_simple.php'; ?>

                <!-- Actions de page -->
                <div class="d-flex gap-2 mb-3">
                    <button class="btn btn-outline-secondary" onclick="refreshData()">
                        <i class="bi bi-arrow-clockwise"></i> Actualiser
                    </button>
                    <a href="add_user.php" class="btn btn-primary">
                        <i class="bi bi-person-plus"></i> Ajouter Utilisateur
                    </a>
                </div>

                <!-- Statistiques -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="kpi-card d-flex align-items-center">
                            <div class="kpi-icon bg-primary">
                                <i class="bi bi-people"></i>
                            </div>
                            <div class="kpi-content">
                                <h3><?= number_format($stats['total_users']) ?></h3>
                                <p>Total Utilisateurs</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card d-flex align-items-center">
                            <div class="kpi-icon bg-success">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div class="kpi-content">
                                <h3><?= number_format($stats['active_users']) ?></h3>
                                <p>Utilisateurs Actifs</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card d-flex align-items-center">
                            <div class="kpi-icon bg-info">
                                <i class="bi bi-clock"></i>
                            </div>
                            <div class="kpi-content">
                                <h3><?= number_format($stats['recent_users']) ?></h3>
                                <p>Connexions 30j</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card d-flex align-items-center">
                            <div class="kpi-icon bg-warning">
                                <i class="bi bi-plus-circle"></i>
                            </div>
                            <div class="kpi-content">
                                <h3><?= number_format($stats['new_users']) ?></h3>
                                <p>Nouveaux 7j</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="admin-card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Recherche</label>
                                <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Code utilisateur, email...">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Tri</label>
                                <select class="form-select" name="sort">
                                    <option value="created_at" <?= $sort === 'created_at' ? 'selected' : '' ?>>Date création</option>
                                    <option value="id" <?= $sort === 'id' ? 'selected' : '' ?>>ID</option>
                                    <option value="email" <?= $sort === 'email' ? 'selected' : '' ?>>Email</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Ordre</label>
                                <select class="form-select" name="order">
                                    <option value="DESC" <?= $order === 'DESC' ? 'selected' : '' ?>>Décroissant</option>
                                    <option value="ASC" <?= $order === 'ASC' ? 'selected' : '' ?>>Croissant</option>
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
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search"></i> Filtrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Liste des utilisateurs -->
                <div class="admin-card">
                    <div class="card-header">
                        <h5 style="margin: 0; font-weight: 600;">
                            <i class="bi bi-list-ul"></i> Liste des Utilisateurs (<?= number_format($total_users) ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($users)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-inbox display-1 text-muted"></i>
                                <p class="text-muted">Aucun utilisateur trouvé</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Code Utilisateur</th>
                                            <th>Email (Masqué)</th>
                                            <th>Rôle</th>
                                            <th>Projets</th>
                                            <th>Activité Récente</th>
                                            <th>Date Création</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($user['user_code']) ?></span>
                                            </td>
                                            <td>
                                                <span class="text-muted"><?= htmlspecialchars($user['masked_email']) ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $role_text = match($user['role_id']) {
                                                    1 => 'Utilisateur',
                                                    2 => 'Admin',
                                                    3 => 'Support',
                                                    default => 'Inconnu'
                                                };
                                                $role_class = match($user['role_id']) {
                                                    1 => 'bg-primary',
                                                    2 => 'bg-danger',
                                                    3 => 'bg-warning',
                                                    default => 'bg-secondary'
                                                };
                                                ?>
                                                <span class="badge <?= $role_class ?>"><?= $role_text ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?= $user['project_count'] ?></span>
                                                <?php if ($user['recent_projects'] > 0): ?>
                                                    <small class="text-success">(+<?= $user['recent_projects'] ?> 30j)</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= $user['recent_projects'] > 0 ? 'Actif' : 'Inactif' ?>
                                                </small>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="user_details.php?id=<?= $user['id'] ?>" class="btn btn-outline-primary" title="Voir détails">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <button class="btn btn-outline-warning" title="Modifier" onclick="editUser(<?= $user['id'] ?>)">
                                                        <i class="bi bi-pencil"></i>
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
                                    Affichage de <?= count($users) ?> utilisateurs sur <?= $total_users ?> au total
                                    (<?= $per_page ?> par page)
                                    <?php if ($search): ?>
                                        <br><small>Résultats pour : "<?= htmlspecialchars($search) ?>"</small>
                                    <?php endif; ?>
                                </div>
                                <nav aria-label="Pagination des utilisateurs">
                                    <ul class="pagination pagination-sm mb-0">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&sort=<?= urlencode($sort) ?>&order=<?= urlencode($order) ?>&per_page=<?= $per_page ?>">
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
                                                <a class="page-link" href="?page=1&search=<?= urlencode($search) ?>&sort=<?= urlencode($sort) ?>&order=<?= urlencode($order) ?>&per_page=<?= $per_page ?>">1</a>
                                            </li>
                                            <?php if ($start_page > 2): ?>
                                                <li class="page-item disabled">
                                                    <span class="page-link">...</span>
                                                </li>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&sort=<?= urlencode($sort) ?>&order=<?= urlencode($order) ?>&per_page=<?= $per_page ?>">
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
                                                <a class="page-link" href="?page=<?= $total_pages ?>&search=<?= urlencode($search) ?>&sort=<?= urlencode($sort) ?>&order=<?= urlencode($order) ?>&per_page=<?= $per_page ?>">
                                                    <?= $total_pages ?>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&sort=<?= urlencode($sort) ?>&order=<?= urlencode($order) ?>&per_page=<?= $per_page ?>">
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
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include 'template_footer_simple.php'; ?>
    
    <script>
        // Gestion des listes déroulantes de la navigation
        document.addEventListener('DOMContentLoaded', function() {
            const dropdownToggles = document.querySelectorAll('.nav-link[href*="management"], .nav-link[href*="tools"], .nav-link[href*="config"], .nav-link[href*="advanced"]');
            
            dropdownToggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const submenu = this.nextElementSibling;
                    const icon = this.querySelector('.bi-chevron-down');
                    
                    if (submenu && submenu.classList.contains('nav')) {
                        if (submenu.style.display === 'none' || submenu.style.display === '') {
                            submenu.style.display = 'block';
                            icon.classList.remove('bi-chevron-down');
                            icon.classList.add('bi-chevron-up');
                        } else {
                            submenu.style.display = 'none';
                            icon.classList.remove('bi-chevron-up');
                            icon.classList.add('bi-chevron-down');
                        }
                    }
                });
            });
        });
        
        function editUser(userId) {
            alert('Fonctionnalité d\'édition à implémenter pour l\'utilisateur ' + userId);
        }
    </script>
</body>
</html> 