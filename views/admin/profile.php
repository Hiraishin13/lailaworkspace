<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/config.php';

// Vérifier les permissions admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/views/auth/login.php');
    exit();
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $first_name = trim($_POST['first_name']);
                $last_name = trim($_POST['last_name']);
                $email = trim($_POST['email']);
                $bio = trim($_POST['bio']);
                
                try {
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET first_name = ?, last_name = ?, email = ?, bio = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$first_name, $last_name, $email, $bio, $_SESSION['user_id']]);
                    
                    // Mettre à jour la session
                    $_SESSION['user_email'] = $email;
                    $_SESSION['admin_name'] = $first_name;
                    
                    $_SESSION['success'] = "Profil mis à jour avec succès.";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Erreur lors de la mise à jour : " . $e->getMessage();
                }
                break;
                
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                if ($new_password !== $confirm_password) {
                    $_SESSION['error'] = "Les nouveaux mots de passe ne correspondent pas.";
                    break;
                }
                
                try {
                    // Vérifier l'ancien mot de passe
                    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!password_verify($current_password, $user['password'])) {
                        $_SESSION['error'] = "Mot de passe actuel incorrect.";
                        break;
                    }
                    
                    // Mettre à jour le mot de passe
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                    
                    $_SESSION['success'] = "Mot de passe modifié avec succès.";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Erreur lors du changement de mot de passe : " . $e->getMessage();
                }
                break;
                
            case 'upload_avatar':
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['avatar'];
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $max_size = 5 * 1024 * 1024; // 5MB
                    
                    if (!in_array($file['type'], $allowed_types)) {
                        $_SESSION['error'] = "Type de fichier non autorisé.";
                        break;
                    }
                    
                    if ($file['size'] > $max_size) {
                        $_SESSION['error'] = "Fichier trop volumineux (max 5MB).";
                        break;
                    }
                    
                    $upload_dir = '../../uploads/profile_pictures/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $filename = 'admin_' . $_SESSION['user_id'] . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filepath = $upload_dir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        try {
                            $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                            $stmt->execute(['/uploads/profile_pictures/' . $filename, $_SESSION['user_id']]);
                            $_SESSION['success'] = "Avatar mis à jour avec succès.";
                        } catch (PDOException $e) {
                            $_SESSION['error'] = "Erreur lors de la mise à jour de l'avatar : " . $e->getMessage();
                        }
                    } else {
                        $_SESSION['error'] = "Erreur lors du téléchargement du fichier.";
                    }
                }
                break;
        }
    }
}

// Récupérer les données de l'admin
try {
    $stmt = $pdo->prepare("
        SELECT u.*, ur.name as role_name
        FROM users u
        LEFT JOIN user_roles ur ON u.role_id = ur.id
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si l'admin n'est pas trouvé, créer des valeurs par défaut
    if (!$admin) {
        $admin = array(
            'id' => $_SESSION['user_id'],
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => $_SESSION['user_email'] ?? 'admin@lailaworkspace.com',
            'role_name' => 'admin',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'last_login' => date('Y-m-d H:i:s'),
            'profile_picture' => null,
            'bio' => ''
        );
    }
    
    // Statistiques de l'admin (table admin_actions peut ne pas exister)
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_actions,
                COUNT(CASE WHEN action_type = 'user_management' THEN 1 END) as user_actions,
                COUNT(CASE WHEN action_type = 'system_settings' THEN 1 END) as settings_actions,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as recent_actions
            FROM admin_actions 
            WHERE admin_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Table admin_actions n'existe pas, utiliser des valeurs par défaut
        $stats = array(
            'total_actions' => 0,
            'user_actions' => 0,
            'settings_actions' => 0,
            'recent_actions' => 0
        );
    }
    
    // Actions récentes
    try {
        $stmt = $pdo->prepare("
            SELECT action_type, description, created_at
            FROM admin_actions
            WHERE admin_id = ?
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $recent_actions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $recent_actions = array();
    }
    
} catch (PDOException $e) {
    error_log('Erreur profil admin : ' . $e->getMessage());
    $admin = array(
        'id' => $_SESSION['user_id'],
        'first_name' => 'Admin',
        'last_name' => 'User',
        'email' => $_SESSION['user_email'] ?? 'admin@lailaworkspace.com',
        'role_name' => 'admin',
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s'),
        'last_login' => date('Y-m-d H:i:s'),
        'profile_picture' => null,
        'bio' => ''
    );
    $stats = array(
        'total_actions' => 0,
        'user_actions' => 0,
        'settings_actions' => 0,
        'recent_actions' => 0
    );
    $recent_actions = array();
}
?>

<?php include 'template_header_simple.php'; ?>\n\n                <!-- Actions de page -->
            <h1 style="color: var(--primary); margin: 0;">
                <i class="bi bi-person-circle"></i> Mon Profil Administrateur
            </h1>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary btn-sm" onclick="exportProfile()">
                    <i class="bi bi-download"></i> Exporter
                </button>
                <button class="btn btn-success btn-sm" onclick="printProfile()">
                    <i class="bi bi-printer"></i> Imprimer
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
            <!-- Profil -->
            <div class="col-md-4">
                <div class="admin-card">
                    <div class="card-header">
                        <h5><i class="bi bi-person"></i> Informations Personnelles</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="avatar-container">
                                <?php if ($admin['profile_picture']): ?>
                                <img src="<?= BASE_URL . htmlspecialchars($admin['profile_picture']) ?>" alt="Avatar" class="avatar-img">
                                <?php else: ?>
                                <div class="avatar-placeholder">
                                    <i class="bi bi-person-circle fs-1"></i>
                                </div>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-primary mt-2" onclick="document.getElementById('avatarInput').click()">
                                    <i class="bi bi-camera"></i> Changer
                                </button>
                            </div>
                            <h5 class="mt-3"><?= htmlspecialchars(($admin['first_name'] ?? 'Admin') . ' ' . ($admin['last_name'] ?? 'User')) ?></h5>
                            <span class="badge bg-success"><?= htmlspecialchars($admin['role_name'] ?? 'admin') ?></span>
                        </div>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="upload_avatar">
                            <input type="file" id="avatarInput" name="avatar" accept="image/*" style="display: none;" onchange="this.form.submit()">
                        </form>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="mb-3">
                                <label class="form-label">Prénom</label>
                                <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($admin['first_name'] ?? 'Admin') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nom</label>
                                <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($admin['last_name'] ?? 'User') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($admin['email'] ?? 'admin@lailaworkspace.com') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Bio</label>
                                <textarea name="bio" class="form-control" rows="3" placeholder="Parlez-nous de vous..."><?= htmlspecialchars($admin['bio'] ?? '') ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check"></i> Mettre à jour
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Changement de mot de passe -->
                <div class="admin-card mt-4">
                    <div class="card-header">
                        <h5><i class="bi bi-key"></i> Changer le Mot de Passe</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="passwordForm">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="mb-3">
                                <label class="form-label">Mot de passe actuel</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nouveau mot de passe</label>
                                <input type="password" name="new_password" class="form-control" required minlength="8">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Confirmer le nouveau mot de passe</label>
                                <input type="password" name="confirm_password" class="form-control" required minlength="8">
                            </div>
                            
                            <button type="submit" class="btn btn-warning w-100">
                                <i class="bi bi-arrow-clockwise"></i> Changer
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
                                    <i class="bi bi-activity"></i>
                                </div>
                                <div class="ms-3">
                                    <h4 class="mb-0"><?= $stats['total_actions'] ?? 0 ?></h4>
                                    <small class="text-muted">Actions totales</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <div class="d-flex align-items-center">
                                <div class="kpi-icon bg-success">
                                    <i class="bi bi-people"></i>
                                </div>
                                <div class="ms-3">
                                    <h4 class="mb-0"><?= $stats['user_actions'] ?? 0 ?></h4>
                                    <small class="text-muted">Gestion utilisateurs</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <div class="d-flex align-items-center">
                                <div class="kpi-icon bg-warning">
                                    <i class="bi bi-gear"></i>
                                </div>
                                <div class="ms-3">
                                    <h4 class="mb-0"><?= $stats['settings_actions'] ?? 0 ?></h4>
                                    <small class="text-muted">Paramètres</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <div class="d-flex align-items-center">
                                <div class="kpi-icon bg-info">
                                    <i class="bi bi-calendar-week"></i>
                                </div>
                                <div class="ms-3">
                                    <h4 class="mb-0"><?= $stats['recent_actions'] ?? 0 ?></h4>
                                    <small class="text-muted">7 derniers jours</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informations de connexion -->
                <div class="admin-card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-clock-history"></i> Informations de Connexion</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item">
                                    <strong>Dernière connexion :</strong>
                                    <span><?= ($admin['last_login'] ?? null) ? date('d/m/Y H:i', strtotime($admin['last_login'])) : 'Jamais' ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <strong>Date d'inscription :</strong>
                                    <span><?= date('d/m/Y', strtotime($admin['created_at'] ?? date('Y-m-d H:i:s'))) ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <strong>Statut :</strong>
                                    <span class="badge bg-<?= ($admin['status'] ?? 'active') === 'active' ? 'success' : 'danger' ?>">
                                        <?= ucfirst($admin['status'] ?? 'active') ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <strong>ID Utilisateur :</strong>
                                    <span><?= $admin['id'] ?? $_SESSION['user_id'] ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions récentes -->
                <div class="admin-card">
                    <div class="card-header">
                        <h5><i class="bi bi-activity"></i> Actions Récentes</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_actions)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-activity fs-1 text-muted"></i>
                            <p class="text-muted mt-2">Aucune action récente</p>
                        </div>
                        <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($recent_actions as $action): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-<?= $action['action_type'] === 'user_management' ? 'primary' : 'success' ?>">
                                    <i class="bi bi-<?= $action['action_type'] === 'user_management' ? 'people' : 'gear' ?>"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6><?= htmlspecialchars($action['description']) ?></h6>
                                    <p class="text-muted mb-0"><?= ucfirst(str_replace('_', ' ', $action['action_type'])) ?></p>
                                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($action['created_at'])) ?></small>
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
            const newPassword = document.querySelector('input[name="new_password"]').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Les nouveaux mots de passe ne correspondent pas.');
            }
        });

        function exportProfile() {
            // Logique d'export du profil
            alert('Export du profil en cours...');
        }

        function printProfile() {
            window.print();
        }
    </script>

    <style>
        .avatar-container {
            position: relative;
            display: inline-block;
        }
        
        .avatar-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
        }
        
        .avatar-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid var(--primary);
        }
        
        .info-item {
            padding: 10px 0;
            border-bottom: 1px solid var(--light);
        }
        
        .info-item:last-child {
            border-bottom: none;
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
        
        @media print {
            .navbar, .btn, .sidebar {
                display: none !important;
            }
        }
    </style>
</body>
</html> 