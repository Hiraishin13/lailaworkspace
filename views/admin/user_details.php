<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/config.php';

// Vérifier les permissions admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/views/auth/login.php');
    exit();
}

// Récupérer l'ID de l'utilisateur
$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    header('Location: users.php');
    exit();
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_user':
                $first_name = trim($_POST['first_name']);
                $last_name = trim($_POST['last_name']);
                $email = trim($_POST['email']);
                $role_id = $_POST['role_id'];
                $status = $_POST['status'];
                
                try {
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET first_name = ?, last_name = ?, email = ?, role_id = ?, status = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$first_name, $last_name, $email, $role_id, $status, $user_id]);
                    $_SESSION['success'] = "Utilisateur mis à jour avec succès.";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Erreur lors de la mise à jour : " . $e->getMessage();
                }
                break;
                
            case 'reset_password':
                $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                
                try {
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$new_password, $user_id]);
                    $_SESSION['success'] = "Mot de passe réinitialisé avec succès.";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Erreur lors de la réinitialisation : " . $e->getMessage();
                }
                break;
                
            case 'delete_user':
                if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')) {
                    try {
                        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                        $stmt->execute([$user_id]);
                        $_SESSION['success'] = "Utilisateur supprimé avec succès.";
                        header('Location: users.php');
                        exit();
                    } catch (PDOException $e) {
                        $_SESSION['error'] = "Erreur lors de la suppression : " . $e->getMessage();
                    }
                }
                break;
        }
    }
}

// Récupérer les données de l'utilisateur
try {
    // Informations de base
    $stmt = $pdo->prepare("
        SELECT u.*, ur.name as role_name
        FROM users u
        LEFT JOIN user_roles ur ON u.role_id = ur.id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: users.php');
        exit();
    }
    
    // Projets de l'utilisateur
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            COUNT(b.id) as bmc_blocks,
            COUNT(h.id) as hypotheses_count,
            COUNT(fp.id) as financial_plans_count
        FROM projects p
        LEFT JOIN bmc b ON p.id = b.project_id
        LEFT JOIN hypotheses h ON p.id = h.project_id
        LEFT JOIN financial_plans fp ON p.id = fp.project_id
        WHERE p.user_id = ?
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Consentements
    $stmt = $pdo->prepare("
        SELECT consent_type, status, updated_at
        FROM user_consents
        WHERE user_id = ?
        ORDER BY updated_at DESC
    ");
    $stmt->execute([$user_id]);
    $consents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Activité récente
    $stmt = $pdo->prepare("
        SELECT 
            'project' as type,
            p.name as title,
            p.created_at as date,
            'Projet créé' as action
        FROM projects p
        WHERE p.user_id = ?
        UNION ALL
        SELECT 
            'bmc' as type,
            p.name as title,
            b.updated_at as date,
            'BMC mis à jour' as action
        FROM bmc b
        JOIN projects p ON b.project_id = p.id
        WHERE p.user_id = ?
        ORDER BY date DESC
        LIMIT 20
    ");
    $stmt->execute([$user_id, $user_id]);
    $recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistiques
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_projects,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as recent_projects,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_projects
        FROM projects
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Rôles disponibles
    $stmt = $pdo->query("SELECT id, name FROM user_roles ORDER BY id");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log('Erreur détails utilisateur : ' . $e->getMessage());
    header('Location: users.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Back-office Laila Workspace - Détails Utilisateur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
    <!-- Navbar Admin -->
    <nav class="navbar navbar-expand-lg navbar-dark" role="navigation" aria-label="Navigation principale">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php" title="Retour au dashboard principal">
                <i class="bi bi-shield-lock fs-4 me-2" aria-hidden="true"></i>
                <span class="fw-bold">Laila Workspace</span>
                <small class="ms-2 text-success">Admin</small>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin" 
                    aria-controls="navbarAdmin" aria-expanded="false" aria-label="Basculer la navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarAdmin">
                <ul class="navbar-nav me-auto" role="menubar">
                    <li class="nav-item" role="none">
                        <a class="nav-link" href="dashboard.php" role="menuitem">
                            <i class="bi bi-speedometer2" aria-hidden="true"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a class="nav-link active" href="users.php" role="menuitem" aria-current="page">
                            <i class="bi bi-people" aria-hidden="true"></i> Utilisateurs
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a class="nav-link" href="projects.php" role="menuitem">
                            <i class="bi bi-kanban" aria-hidden="true"></i> Projets
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a class="nav-link" href="analytics.php" role="menuitem">
                            <i class="bi bi-graph-up" aria-hidden="true"></i> Analytics
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a class="nav-link" href="partnerships.php" role="menuitem">
                            <i class="bi bi-handshake" aria-hidden="true"></i> Partenariats B2B
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a class="nav-link" href="audit.php" role="menuitem">
                            <i class="bi bi-shield-check" aria-hidden="true"></i> Audit
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav" role="menubar">
                    <li class="nav-item dropdown" role="none">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle" aria-hidden="true"></i>
                            <span class="ms-1 d-none d-lg-inline"><?= htmlspecialchars($_SESSION['user_role']) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" role="menu">
                            <li role="none"><a class="dropdown-item" href="profile.php" role="menuitem">Mon Profil</a></li>
                            <li role="none"><a class="dropdown-item" href="settings.php" role="menuitem">Paramètres</a></li>
                            <li role="none"><hr class="dropdown-divider"></li>
                            <li role="none"><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/views/auth/logout.php" role="menuitem">Déconnexion</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- En-tête -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 style="color: var(--primary); margin: 0;">
                    <i class="bi bi-person-circle"></i> Détails Utilisateur
                </h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="users.php">Utilisateurs</a></li>
                        <li class="breadcrumb-item active"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex gap-2">
                <a href="users.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
                <button class="btn btn-danger" onclick="deleteUser()">
                    <i class="bi bi-trash"></i> Supprimer
                </button>
            </div>
        </div>

        <!-- Messages -->
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); endif; ?>

        <div class="row">
            <!-- Informations de base -->
            <div class="col-md-4">
                <div class="admin-card">
                    <div class="card-header">
                        <h5><i class="bi bi-person"></i> Informations de Base</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <div class="avatar-placeholder">
                                <i class="bi bi-person-circle fs-1"></i>
                            </div>
                            <h5 class="mt-2"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h5>
                            <span class="badge bg-<?= $user['status'] === 'active' ? 'success' : 'danger' ?>">
                                <?= ucfirst($user['status']) ?>
                            </span>
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="update_user">
                            
                            <div class="mb-3">
                                <label class="form-label">Prénom</label>
                                <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nom</label>
                                <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Rôle</label>
                                <select name="role_id" class="form-select" required>
                                    <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['id'] ?>" <?= $role['id'] == $user['role_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($role['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Statut</label>
                                <select name="status" class="form-select" required>
                                    <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Actif</option>
                                    <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Inactif</option>
                                    <option value="suspended" <?= $user['status'] === 'suspended' ? 'selected' : '' ?>>Suspendu</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check"></i> Mettre à jour
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Réinitialisation du mot de passe -->
                <div class="admin-card mt-4">
                    <div class="card-header">
                        <h5><i class="bi bi-key"></i> Réinitialiser le Mot de Passe</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="passwordForm">
                            <input type="hidden" name="action" value="reset_password">
                            
                            <div class="mb-3">
                                <label class="form-label">Nouveau mot de passe</label>
                                <input type="password" name="new_password" class="form-control" required minlength="8">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Confirmer le mot de passe</label>
                                <input type="password" name="confirm_password" class="form-control" required minlength="8">
                            </div>
                            
                            <button type="submit" class="btn btn-warning w-100">
                                <i class="bi bi-arrow-clockwise"></i> Réinitialiser
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Statistiques et activité -->
            <div class="col-md-8">
                <!-- Statistiques -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <div class="d-flex align-items-center">
                                <div class="kpi-icon bg-primary">
                                    <i class="bi bi-kanban"></i>
                                </div>
                                <div class="ms-3">
                                    <h4 class="mb-0"><?= $stats['total_projects'] ?></h4>
                                    <small class="text-muted">Projets totaux</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <div class="d-flex align-items-center">
                                <div class="kpi-icon bg-success">
                                    <i class="bi bi-calendar-check"></i>
                                </div>
                                <div class="ms-3">
                                    <h4 class="mb-0"><?= $stats['recent_projects'] ?></h4>
                                    <small class="text-muted">30 derniers jours</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <div class="d-flex align-items-center">
                                <div class="kpi-icon bg-info">
                                    <i class="bi bi-clock"></i>
                                </div>
                                <div class="ms-3">
                                    <h4 class="mb-0"><?= $user['last_login'] ? date('d/m/Y', strtotime($user['last_login'])) : 'Jamais' ?></h4>
                                    <small class="text-muted">Dernière connexion</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <div class="d-flex align-items-center">
                                <div class="kpi-icon bg-warning">
                                    <i class="bi bi-calendar-plus"></i>
                                </div>
                                <div class="ms-3">
                                    <h4 class="mb-0"><?= date('d/m/Y', strtotime($user['created_at'])) ?></h4>
                                    <small class="text-muted">Date d'inscription</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Projets -->
                <div class="admin-card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-kanban"></i> Projets (<?= count($projects) ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($projects)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-kanban fs-1 text-muted"></i>
                            <p class="text-muted mt-2">Aucun projet créé</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Statut</th>
                                        <th>BMC</th>
                                        <th>Hypothèses</th>
                                        <th>Plan Financier</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($projects as $project): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($project['name']) ?></strong>
                                            <br><small class="text-muted"><?= htmlspecialchars(substr($project['description'], 0, 50)) ?>...</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $project['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                <?= ucfirst($project['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $project['bmc_blocks'] > 0 ? 'success' : 'secondary' ?>">
                                                <?= $project['bmc_blocks'] ?> bloc(s)
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $project['hypotheses_count'] > 0 ? 'info' : 'secondary' ?>">
                                                <?= $project['hypotheses_count'] ?> hypothèse(s)
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $project['financial_plans_count'] > 0 ? 'warning' : 'secondary' ?>">
                                                <?= $project['financial_plans_count'] ?> plan(s)
                                            </span>
                                        </td>
                                        <td>
                                            <small><?= date('d/m/Y', strtotime($project['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-primary" onclick="viewProject(<?= $project['id'] ?>)" title="Voir">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-info" onclick="viewBMC(<?= $project['id'] ?>)" title="BMC">
                                                    <i class="bi bi-grid-3x3"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Consentements -->
                <div class="admin-card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-shield-check"></i> Consentements</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($consents)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-shield-check fs-1 text-muted"></i>
                            <p class="text-muted mt-2">Aucun consentement enregistré</p>
                        </div>
                        <?php else: ?>
                        <div class="row">
                            <?php 
                            $consent_types = [
                                'marketing' => 'Marketing',
                                'analytics' => 'Analytics',
                                'third_party' => 'Tiers',
                                'data_processing' => 'Traitement',
                                'cookies' => 'Cookies'
                            ];
                            
                            foreach ($consent_types as $type => $label):
                                $user_consent = null;
                                foreach ($consents as $consent) {
                                    if ($consent['consent_type'] === $type) {
                                        $user_consent = $consent;
                                        break;
                                    }
                                }
                            ?>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                                    <div>
                                        <strong><?= $label ?></strong>
                                        <br><small class="text-muted"><?= $user_consent ? date('d/m/Y H:i', strtotime($user_consent['updated_at'])) : 'Non défini' ?></small>
                                    </div>
                                    <span class="badge bg-<?= $user_consent && $user_consent['status'] === 'accepted' ? 'success' : ($user_consent && $user_consent['status'] === 'declined' ? 'danger' : 'warning') ?>">
                                        <?= $user_consent ? ucfirst($user_consent['status']) : 'En attente' ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Activité récente -->
                <div class="admin-card">
                    <div class="card-header">
                        <h5><i class="bi bi-clock-history"></i> Activité Récente</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_activity)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-clock-history fs-1 text-muted"></i>
                            <p class="text-muted mt-2">Aucune activité récente</p>
                        </div>
                        <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($recent_activity as $activity): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-<?= $activity['type'] === 'project' ? 'primary' : 'success' ?>">
                                    <i class="bi bi-<?= $activity['type'] === 'project' ? 'plus-circle' : 'pencil' ?>"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6><?= htmlspecialchars($activity['title']) ?></h6>
                                    <p class="text-muted mb-0"><?= htmlspecialchars($activity['action']) ?></p>
                                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($activity['date'])) ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'template_footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validation du formulaire de mot de passe
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const password = document.querySelector('input[name="new_password"]').value;
            const confirm = document.querySelector('input[name="confirm_password"]').value;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas.');
            }
        });

        function deleteUser() {
            if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="delete_user">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        function viewProject(projectId) {
            window.open(`project_details.php?id=${projectId}`, '_blank');
        }

        function viewBMC(projectId) {
            window.open(`../../views/bmc/bmc.php?project_id=${projectId}`, '_blank');
        }
    </script>

    <style>
        .avatar-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        
        .timeline-marker {
            position: absolute;
            left: -35px;
            top: 0;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 10px;
        }
        
        .timeline-content {
            background: var(--light);
            padding: 15px;
            border-radius: 8px;
        }
    </style>
</body>
</html> 