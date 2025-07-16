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
            case 'update_system_settings':
                $settings = [
                    'site_name' => $_POST['site_name'],
                    'site_description' => $_POST['site_description'],
                    'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0,
                    'registration_enabled' => isset($_POST['registration_enabled']) ? 1 : 0,
                    'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
                    'auto_backup' => isset($_POST['auto_backup']) ? 1 : 0,
                    'session_timeout' => $_POST['session_timeout'],
                    'max_file_size' => $_POST['max_file_size'],
                    'allowed_file_types' => $_POST['allowed_file_types']
                ];
                
                try {
                    foreach ($settings as $key => $value) {
                        $stmt = $pdo->prepare("
                            INSERT INTO system_settings (setting_key, setting_value, updated_at) 
                            VALUES (?, ?, NOW()) 
                            ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
                        ");
                        $stmt->execute([$key, $value, $value]);
                    }
                    $_SESSION['success'] = "Paramètres système mis à jour avec succès.";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Erreur lors de la mise à jour : " . $e->getMessage();
                }
                break;
                
            case 'update_security_settings':
                $security_settings = [
                    'password_min_length' => $_POST['password_min_length'],
                    'password_require_special' => isset($_POST['password_require_special']) ? 1 : 0,
                    'max_login_attempts' => $_POST['max_login_attempts'],
                    'lockout_duration' => $_POST['lockout_duration'],
                    'two_factor_auth' => isset($_POST['two_factor_auth']) ? 1 : 0,
                    'session_regenerate' => isset($_POST['session_regenerate']) ? 1 : 0,
                    'csrf_protection' => isset($_POST['csrf_protection']) ? 1 : 0
                ];
                
                try {
                    foreach ($security_settings as $key => $value) {
                        $stmt = $pdo->prepare("
                            INSERT INTO system_settings (setting_key, setting_value, updated_at) 
                            VALUES (?, ?, NOW()) 
                            ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
                        ");
                        $stmt->execute([$key, $value, $value]);
                    }
                    $_SESSION['success'] = "Paramètres de sécurité mis à jour avec succès.";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Erreur lors de la mise à jour : " . $e->getMessage();
                }
                break;
                
            case 'update_email_settings':
                $email_settings = [
                    'smtp_host' => $_POST['smtp_host'],
                    'smtp_port' => $_POST['smtp_port'],
                    'smtp_username' => $_POST['smtp_username'],
                    'smtp_password' => $_POST['smtp_password'],
                    'smtp_encryption' => $_POST['smtp_encryption'],
                    'from_email' => $_POST['from_email'],
                    'from_name' => $_POST['from_name']
                ];
                
                try {
                    foreach ($email_settings as $key => $value) {
                        $stmt = $pdo->prepare("
                            INSERT INTO system_settings (setting_key, setting_value, updated_at) 
                            VALUES (?, ?, NOW()) 
                            ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
                        ");
                        $stmt->execute([$key, $value, $value]);
                    }
                    $_SESSION['success'] = "Paramètres email mis à jour avec succès.";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Erreur lors de la mise à jour : " . $e->getMessage();
                }
                break;
                
            case 'test_email':
                $test_email = $_POST['test_email'];
                // Logique d'envoi d'email de test
                $_SESSION['success'] = "Email de test envoyé à " . $test_email;
                break;
        }
    }
}

// Valeurs par défaut
$default_settings = [
    'site_name' => 'Laila Workspace',
    'site_description' => 'Plateforme de gestion Business Model Canvas',
    'maintenance_mode' => 0,
    'registration_enabled' => 1,
    'email_notifications' => 1,
    'auto_backup' => 1,
    'session_timeout' => 3600,
    'max_file_size' => 5242880,
    'allowed_file_types' => 'jpg,jpeg,png,pdf,doc,docx',
    'password_min_length' => 8,
    'password_require_special' => 1,
    'max_login_attempts' => 5,
    'lockout_duration' => 900,
    'two_factor_auth' => 0,
    'session_regenerate' => 1,
    'csrf_protection' => 1,
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_encryption' => 'tls',
    'from_email' => 'noreply@lailaworkspace.com',
    'from_name' => 'Laila Workspace'
];

// Récupérer les paramètres actuels
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    $settings_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Fusionner avec les valeurs de la base de données
    $settings = array_merge($default_settings, $settings_data);
    
} catch (PDOException $e) {
    error_log('Erreur paramètres : ' . $e->getMessage());
    $settings = $default_settings;
}
?>

<?php include 'template_header_simple.php'; ?>\n\n                <!-- Actions de page -->
            <h1 style="color: var(--primary); margin: 0;">
                <i class="bi bi-gear"></i> Paramètres Système
            </h1>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary btn-sm" onclick="exportSettings()">
                    <i class="bi bi-download"></i> Exporter
                </button>
                <button class="btn btn-success btn-sm" onclick="importSettings()">
                    <i class="bi bi-upload"></i> Importer
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
            <!-- Paramètres système -->
            <div class="col-md-6">
                <div class="admin-card">
                    <div class="card-header">
                        <h5><i class="bi bi-gear"></i> Paramètres Généraux</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_system_settings">
                            
                            <div class="mb-3">
                                <label class="form-label">Nom du site</label>
                                <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($settings['site_name'] ?? 'Laila Workspace') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description du site</label>
                                <textarea name="site_description" class="form-control" rows="3"><?= htmlspecialchars($settings['site_description'] ?? 'Plateforme de gestion Business Model Canvas') ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenance_mode" <?= ($settings['maintenance_mode'] ?? 0) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="maintenance_mode">
                                        Mode maintenance
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="registration_enabled" id="registration_enabled" <?= ($settings['registration_enabled'] ?? 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="registration_enabled">
                                        Inscriptions autorisées
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="email_notifications" id="email_notifications" <?= ($settings['email_notifications'] ?? 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="email_notifications">
                                        Notifications email
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="auto_backup" id="auto_backup" <?= ($settings['auto_backup'] ?? 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="auto_backup">
                                        Sauvegarde automatique
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Timeout de session (secondes)</label>
                                <input type="number" name="session_timeout" class="form-control" value="<?= $settings['session_timeout'] ?? 3600 ?>" min="300" max="86400">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Taille max des fichiers (octets)</label>
                                <input type="number" name="max_file_size" class="form-control" value="<?= $settings['max_file_size'] ?? 5242880 ?>" min="1024" max="10485760">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Types de fichiers autorisés</label>
                                <input type="text" name="allowed_file_types" class="form-control" value="<?= htmlspecialchars($settings['allowed_file_types'] ?? 'jpg,jpeg,png,pdf,doc,docx') ?>" placeholder="jpg,jpeg,png,pdf,doc,docx">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check"></i> Sauvegarder
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Paramètres de sécurité -->
            <div class="col-md-6">
                <div class="admin-card">
                    <div class="card-header">
                        <h5><i class="bi bi-shield-lock"></i> Sécurité</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_security_settings">
                            
                            <div class="mb-3">
                                <label class="form-label">Longueur min du mot de passe</label>
                                <input type="number" name="password_min_length" class="form-control" value="<?= $settings['password_min_length'] ?? 8 ?>" min="6" max="20">
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="password_require_special" id="password_require_special" <?= ($settings['password_require_special'] ?? 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="password_require_special">
                                        Caractères spéciaux requis
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Tentatives de connexion max</label>
                                <input type="number" name="max_login_attempts" class="form-control" value="<?= $settings['max_login_attempts'] ?? 5 ?>" min="3" max="10">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Durée de verrouillage (secondes)</label>
                                <input type="number" name="lockout_duration" class="form-control" value="<?= $settings['lockout_duration'] ?? 900 ?>" min="300" max="3600">
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="two_factor_auth" id="two_factor_auth" <?= ($settings['two_factor_auth'] ?? 0) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="two_factor_auth">
                                        Authentification à deux facteurs
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="session_regenerate" id="session_regenerate" <?= ($settings['session_regenerate'] ?? 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="session_regenerate">
                                        Régénération de session
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="csrf_protection" id="csrf_protection" <?= ($settings['csrf_protection'] ?? 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="csrf_protection">
                                        Protection CSRF
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-shield-check"></i> Sauvegarder
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Paramètres email -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="admin-card">
                    <div class="card-header">
                        <h5><i class="bi bi-envelope"></i> Configuration Email</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_email_settings">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Serveur SMTP</label>
                                        <input type="text" name="smtp_host" class="form-control" value="<?= htmlspecialchars($settings['smtp_host'] ?? 'smtp.gmail.com') ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Port SMTP</label>
                                        <input type="number" name="smtp_port" class="form-control" value="<?= $settings['smtp_port'] ?? 587 ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nom d'utilisateur SMTP</label>
                                        <input type="text" name="smtp_username" class="form-control" value="<?= htmlspecialchars($settings['smtp_username'] ?? '') ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Mot de passe SMTP</label>
                                        <input type="password" name="smtp_password" class="form-control" value="<?= htmlspecialchars($settings['smtp_password'] ?? '') ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Chiffrement</label>
                                        <select name="smtp_encryption" class="form-select" required>
                                            <option value="tls" <?= ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                                            <option value="ssl" <?= ($settings['smtp_encryption'] ?? 'tls') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                            <option value="none" <?= ($settings['smtp_encryption'] ?? 'tls') === 'none' ? 'selected' : '' ?>>Aucun</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email d'expédition</label>
                                        <input type="email" name="from_email" class="form-control" value="<?= htmlspecialchars($settings['from_email'] ?? 'noreply@lailaworkspace.com') ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nom d'expédition</label>
                                <input type="text" name="from_name" class="form-control" value="<?= htmlspecialchars($settings['from_name'] ?? 'Laila Workspace') ?>" required>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-info">
                                    <i class="bi bi-envelope"></i> Sauvegarder
                                </button>
                                <button type="button" class="btn btn-success" onclick="testEmail()">
                                    <i class="bi bi-send"></i> Tester Email
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions système -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="admin-card">
                    <div class="card-header">
                        <h5><i class="bi bi-tools"></i> Actions Système</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <button class="btn btn-outline-primary w-100 mb-2" onclick="clearCache()">
                                    <i class="bi bi-trash"></i> Vider le cache
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-outline-success w-100 mb-2" onclick="createBackup()">
                                    <i class="bi bi-download"></i> Créer sauvegarde
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-outline-warning w-100 mb-2" onclick="optimizeDatabase()">
                                    <i class="bi bi-speedometer2"></i> Optimiser DB
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-outline-info w-100 mb-2" onclick="generateLogs()">
                                    <i class="bi bi-file-text"></i> Générer logs
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Test Email -->
    <div class="modal fade" id="testEmailModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Test Email</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="testEmailForm">
                        <input type="hidden" name="action" value="test_email">
                        <div class="mb-3">
                            <label class="form-label">Email de test</label>
                            <input type="email" name="test_email" class="form-control" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" form="testEmailForm" class="btn btn-primary">Envoyer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'template_footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportSettings() {
            // Logique d'export des paramètres
            alert('Export des paramètres en cours...');
        }

        function importSettings() {
            // Logique d'import des paramètres
            alert('Import des paramètres en cours...');
        }

        function testEmail() {
            const modal = new bootstrap.Modal(document.getElementById('testEmailModal'));
            modal.show();
        }

        function clearCache() {
            if (confirm('Vider le cache système ?')) {
                // Logique de vidage du cache
                alert('Cache vidé avec succès !');
            }
        }

        function createBackup() {
            if (confirm('Créer une sauvegarde complète ?')) {
                // Logique de sauvegarde
                alert('Sauvegarde créée avec succès !');
            }
        }

        function optimizeDatabase() {
            if (confirm('Optimiser la base de données ?')) {
                // Logique d'optimisation
                alert('Base de données optimisée !');
            }
        }

        function generateLogs() {
            if (confirm('Générer les logs système ?')) {
                // Logique de génération de logs
                alert('Logs générés avec succès !');
            }
        }
    </script>
</body>
</html> 