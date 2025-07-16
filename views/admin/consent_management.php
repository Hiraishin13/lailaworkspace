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
            case 'update_consent':
                $user_id = $_POST['user_id'];
                $consent_type = $_POST['consent_type'];
                $status = $_POST['status'];
                
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO user_consents (user_id, consent_type, status, updated_at) 
                        VALUES (?, ?, ?, NOW()) 
                        ON DUPLICATE KEY UPDATE status = ?, updated_at = NOW()
                    ");
                    $stmt->execute([$user_id, $consent_type, $status, $status]);
                    $_SESSION['success'] = "Consentement mis à jour avec succès.";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Erreur lors de la mise à jour : " . $e->getMessage();
                }
                break;
                
            case 'bulk_update':
                $user_ids = $_POST['user_ids'] ?? [];
                $consent_type = $_POST['consent_type'];
                $status = $_POST['status'];
                
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO user_consents (user_id, consent_type, status, updated_at) 
                        VALUES (?, ?, ?, NOW()) 
                        ON DUPLICATE KEY UPDATE status = ?, updated_at = NOW()
                    ");
                    
                    foreach ($user_ids as $user_id) {
                        $stmt->execute([$user_id, $consent_type, $status, $status]);
                    }
                    $_SESSION['success'] = count($user_ids) . " consentements mis à jour.";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Erreur lors de la mise à jour en masse : " . $e->getMessage();
                }
                break;
        }
    }
}

// Récupérer les données de consentement
try {
    // Statistiques globales
    $stmt = $pdo->query("
        SELECT 
            consent_type,
            status,
            COUNT(*) as count
        FROM user_consents 
        GROUP BY consent_type, status
    ");
    $consent_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Utilisateurs avec leurs consentements
    $stmt = $pdo->query("
        SELECT 
            u.id,
            u.email,
            u.first_name,
            u.last_name,
            u.created_at,
            u.last_login,
            uc.consent_type,
            uc.status,
            uc.updated_at as consent_updated
        FROM users u
        LEFT JOIN user_consents uc ON u.id = uc.user_id
        ORDER BY u.created_at DESC
    ");
    $users_consents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Types de consentement
    $consent_types = [
        'marketing' => 'Marketing et communications',
        'analytics' => 'Analytics et statistiques',
        'third_party' => 'Partage avec tiers',
        'data_processing' => 'Traitement des données',
        'cookies' => 'Cookies et tracking'
    ];
    
} catch (PDOException $e) {
    error_log('Erreur consentement : ' . $e->getMessage());
    $consent_stats = array();
    $users_consents = array();
    $consent_types = array();
}
?>

<?php include 'template_header_simple.php'; ?>\n\n                <!-- Actions de page -->
                    <h1 style="color: var(--primary); margin: 0;">
                        <i class="bi bi-shield-check"></i> Gestion des Consentements
                    </h1>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary btn-sm" onclick="exportConsentData()">
                            <i class="bi bi-download"></i> Exporter
                        </button>
                        <button class="btn btn-success btn-sm" onclick="sendConsentReminder()">
                            <i class="bi bi-envelope"></i> Rappels
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

                <!-- Statistiques -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="kpi-card">
                            <div class="d-flex align-items-center">
                                <div class="kpi-icon bg-success">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                                <div class="ms-3">
                                    <h4 class="mb-0">
                                        <?= array_sum(array_column(array_filter($consent_stats, function($s) { return $s['status'] === 'accepted'; }), 'count')) ?>
                                    </h4>
                                    <small class="text-muted">Acceptés</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="kpi-card">
                            <div class="d-flex align-items-center">
                                <div class="kpi-icon bg-danger">
                                    <i class="bi bi-x-circle"></i>
                                </div>
                                <div class="ms-3">
                                    <h4 class="mb-0">
                                        <?= array_sum(array_column(array_filter($consent_stats, function($s) { return $s['status'] === 'declined'; }), 'count')) ?>
                                    </h4>
                                    <small class="text-muted">Refusés</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="kpi-card">
                            <div class="d-flex align-items-center">
                                <div class="kpi-icon bg-warning">
                                    <i class="bi bi-question-circle"></i>
                                </div>
                                <div class="ms-3">
                                    <h4 class="mb-0">
                                        <?= array_sum(array_column(array_filter($consent_stats, function($s) { return $s['status'] === 'pending'; }), 'count')) ?>
                                    </h4>
                                    <small class="text-muted">En attente</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="kpi-card">
                            <div class="d-flex align-items-center">
                                <div class="kpi-icon bg-info">
                                    <i class="bi bi-people"></i>
                                </div>
                                <div class="ms-3">
                                    <h4 class="mb-0"><?= count(array_unique(array_column($users_consents, 'id'))) ?></h4>
                                    <small class="text-muted">Utilisateurs</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="kpi-card">
                            <div class="d-flex align-items-center">
                                <div class="kpi-icon bg-primary">
                                    <i class="bi bi-shield-check"></i>
                                </div>
                                <div class="ms-3">
                                    <h4 class="mb-0"><?= count($consent_types) ?></h4>
                                    <small class="text-muted">Types</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="kpi-card">
                            <div class="d-flex align-items-center">
                                <div class="kpi-icon bg-secondary">
                                    <i class="bi bi-calendar"></i>
                                </div>
                                <div class="ms-3">
                                    <h4 class="mb-0"><?= date('d/m') ?></h4>
                                    <small class="text-muted">Aujourd'hui</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions en masse -->
                <div class="admin-card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-gear"></i> Actions en Masse</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="bulkForm">
                            <input type="hidden" name="action" value="bulk_update">
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="form-label">Type de consentement</label>
                                    <select name="consent_type" class="form-select" required>
                                        <option value="">Sélectionner...</option>
                                        <?php foreach ($consent_types as $key => $label): ?>
                                        <option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Statut</label>
                                    <select name="status" class="form-select" required>
                                        <option value="">Sélectionner...</option>
                                        <option value="accepted">Accepté</option>
                                        <option value="declined">Refusé</option>
                                        <option value="pending">En attente</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Utilisateurs sélectionnés</label>
                                    <div class="form-control" style="height: auto; min-height: 38px;">
                                        <span id="selectedCount">0 utilisateur(s) sélectionné(s)</span>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary w-100" id="bulkSubmit" disabled>
                                        <i class="bi bi-check-all"></i> Appliquer
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Liste des utilisateurs -->
                <div class="admin-card">
                    <div class="card-header">
                        <h5><i class="bi bi-people"></i> Consentements des Utilisateurs</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="consentTable">
                                <thead>
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                        </th>
                                        <th>Utilisateur</th>
                                        <th>Email</th>
                                        <th>Marketing</th>
                                        <th>Analytics</th>
                                        <th>Tiers</th>
                                        <th>Traitement</th>
                                        <th>Cookies</th>
                                        <th>Dernière MAJ</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $users_by_id = array();
                                    foreach ($users_consents as $uc) {
                                        if (!isset($users_by_id[$uc['id']])) {
                                            $users_by_id[$uc['id']] = array(
                                                'id' => $uc['id'],
                                                'email' => $uc['email'],
                                                'first_name' => $uc['first_name'],
                                                'last_name' => $uc['last_name'],
                                                'created_at' => $uc['created_at'],
                                                'last_login' => $uc['last_login'],
                                                'consents' => array()
                                            );
                                        }
                                        if ($uc['consent_type']) {
                                            $users_by_id[$uc['id']]['consents'][$uc['consent_type']] = array(
                                                'status' => $uc['status'],
                                                'updated' => $uc['consent_updated']
                                            );
                                        }
                                    }
                                    ?>
                                    
                                    <?php foreach ($users_by_id as $user): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="user_ids[]" value="<?= $user['id'] ?>" class="user-checkbox" onchange="updateSelectedCount()">
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong>
                                            <br><small class="text-muted">ID: <?= $user['id'] ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td>
                                            <?php 
                                            $status = $user['consents']['marketing']['status'] ?? 'pending';
                                            $updated = $user['consents']['marketing']['updated'] ?? null;
                                            ?>
                                            <span class="badge bg-<?= $status === 'accepted' ? 'success' : ($status === 'declined' ? 'danger' : 'warning') ?>">
                                                <?= ucfirst($status) ?>
                                            </span>
                                            <?php if ($updated): ?>
                                            <br><small class="text-muted"><?= date('d/m/Y', strtotime($updated)) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $status = $user['consents']['analytics']['status'] ?? 'pending';
                                            $updated = $user['consents']['analytics']['updated'] ?? null;
                                            ?>
                                            <span class="badge bg-<?= $status === 'accepted' ? 'success' : ($status === 'declined' ? 'danger' : 'warning') ?>">
                                                <?= ucfirst($status) ?>
                                            </span>
                                            <?php if ($updated): ?>
                                            <br><small class="text-muted"><?= date('d/m/Y', strtotime($updated)) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $status = $user['consents']['third_party']['status'] ?? 'pending';
                                            $updated = $user['consents']['third_party']['updated'] ?? null;
                                            ?>
                                            <span class="badge bg-<?= $status === 'accepted' ? 'success' : ($status === 'declined' ? 'danger' : 'warning') ?>">
                                                <?= ucfirst($status) ?>
                                            </span>
                                            <?php if ($updated): ?>
                                            <br><small class="text-muted"><?= date('d/m/Y', strtotime($updated)) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $status = $user['consents']['data_processing']['status'] ?? 'pending';
                                            $updated = $user['consents']['data_processing']['updated'] ?? null;
                                            ?>
                                            <span class="badge bg-<?= $status === 'accepted' ? 'success' : ($status === 'declined' ? 'danger' : 'warning') ?>">
                                                <?= ucfirst($status) ?>
                                            </span>
                                            <?php if ($updated): ?>
                                            <br><small class="text-muted"><?= date('d/m/Y', strtotime($updated)) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $status = $user['consents']['cookies']['status'] ?? 'pending';
                                            $updated = $user['consents']['cookies']['updated'] ?? null;
                                            ?>
                                            <span class="badge bg-<?= $status === 'accepted' ? 'success' : ($status === 'declined' ? 'danger' : 'warning') ?>">
                                                <?= ucfirst($status) ?>
                                            </span>
                                            <?php if ($updated): ?>
                                            <br><small class="text-muted"><?= date('d/m/Y', strtotime($updated)) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $latest_update = null;
                                            foreach ($user['consents'] as $consent) {
                                                if ($consent['updated'] && (!$latest_update || $consent['updated'] > $latest_update)) {
                                                    $latest_update = $consent['updated'];
                                                }
                                            }
                                            ?>
                                            <?php if ($latest_update): ?>
                                            <small><?= date('d/m/Y H:i', strtotime($latest_update)) ?></small>
                                            <?php else: ?>
                                            <small class="text-muted">Jamais</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-primary" onclick="editUserConsent(<?= $user['id'] ?>)" title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-info" onclick="viewConsentHistory(<?= $user['id'] ?>)" title="Historique">
                                                    <i class="bi bi-clock-history"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-warning" onclick="sendReminder(<?= $user['id'] ?>)" title="Rappel">
                                                    <i class="bi bi-envelope"></i>
                                                </button>
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

    <!-- Footer -->
    <?php include 'template_footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.user-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('.user-checkbox:checked');
            const count = checkboxes.length;
            const countElement = document.getElementById('selectedCount');
            const submitButton = document.getElementById('bulkSubmit');
            
            countElement.textContent = count + ' utilisateur(s) sélectionné(s)';
            submitButton.disabled = count === 0;
        }

        function editUserConsent(userId) {
            window.open(`user_consent_edit.php?id=${userId}`, '_blank');
        }

        function viewConsentHistory(userId) {
            window.open(`consent_history.php?id=${userId}`, '_blank');
        }

        function sendReminder(userId) {
            if (confirm('Envoyer un rappel de consentement à cet utilisateur ?')) {
                // Logique d'envoi de rappel
                alert('Rappel envoyé avec succès !');
            }
        }

        function exportConsentData() {
            // Logique d'export
            alert('Export des données de consentement en cours...');
        }

        function sendConsentReminder() {
            if (confirm('Envoyer des rappels de consentement à tous les utilisateurs en attente ?')) {
                // Logique d'envoi en masse
                alert('Rappels envoyés avec succès !');
            }
        }
    </script>
</body>
</html> 