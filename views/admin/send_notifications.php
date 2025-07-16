<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../config/notifications_config.php';

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../views/auth/login.php');
    exit();
}

$success_message = '';
$error_message = '';

// Traitement de l'envoi de notification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notification_type = $_POST['notification_type'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $target_users = $_POST['target_users'] ?? 'all';
    $selected_users = $_POST['selected_users'] ?? [];
    
    if (empty($title) || empty($message)) {
        $error_message = 'Le titre et le message sont obligatoires.';
    } else {
        try {
            // Déterminer les utilisateurs cibles
            if ($target_users === 'all') {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE status = 'active'");
                $stmt->execute();
                $user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } else {
                $user_ids = $selected_users;
            }
            
            // Insérer la notification pour chaque utilisateur
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, type, title, message, is_read, created_at) 
                VALUES (?, ?, ?, ?, 0, NOW())
            ");
            
            $notifications_sent = 0;
            foreach ($user_ids as $user_id) {
                $stmt->execute([$user_id, $notification_type, $title, $message]);
                $notifications_sent++;
            }
            
            $success_message = "Notification envoyée avec succès à $notifications_sent utilisateur(s) !";
            
        } catch (PDOException $e) {
            $error_message = 'Erreur lors de l\'envoi de la notification : ' . $e->getMessage();
        }
    }
}

// Récupérer la liste des utilisateurs pour la sélection
$stmt = $pdo->prepare("SELECT id, first_name, last_name, email FROM users WHERE status = 'active' ORDER BY first_name, last_name");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les statistiques des notifications
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM notifications");
$stmt->execute();
$total_notifications = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as unread FROM notifications WHERE is_read = 0");
$stmt->execute();
$unread_notifications = $stmt->fetch(PDO::FETCH_ASSOC)['unread'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Envoyer des Notifications - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/admin.css" rel="stylesheet">
    <style>
        .notification-form {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        .user-selection {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
        }
        .notification-type-selector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .type-option {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .type-option:hover {
            border-color: #007bff;
            background: #f8f9fa;
        }
        .type-option.selected {
            border-color: #007bff;
            background: #e3f2fd;
        }
    </style>
</head>
<body>
    <?php include '../layouts/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'template_header.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-megaphone text-primary"></i>
                        Envoyer des Notifications
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Retour au Dashboard
                        </a>
                    </div>
                </div>

                <!-- Messages de succès/erreur -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistiques -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="stats-card">
                            <h5><i class="bi bi-bell"></i> Total Notifications</h5>
                            <h3><?php echo number_format($total_notifications); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="stats-card">
                            <h5><i class="bi bi-bell-fill"></i> Non Lues</h5>
                            <h3><?php echo number_format($unread_notifications); ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Formulaire d'envoi -->
                <div class="notification-form">
                    <h4 class="mb-4">
                        <i class="bi bi-send"></i>
                        Nouvelle Notification
                    </h4>

                    <form method="POST" id="notificationForm">
                        <!-- Type de notification -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Type de Notification</label>
                            <div class="notification-type-selector">
                                <?php foreach (NOTIFICATION_TYPES as $type => $config): ?>
                                    <div class="type-option" data-type="<?php echo $type; ?>">
                                        <div class="d-flex align-items-center">
                                            <i class="bi <?php echo $config['icon']; ?> fs-4 me-2 text-<?php echo $config['color']; ?>"></i>
                                            <div>
                                                <strong><?php echo $config['title']; ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo DEFAULT_NOTIFICATION_MESSAGES[$type] ?? ''; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="notification_type" id="notification_type" value="admin_broadcast">
                        </div>

                        <!-- Titre et Message -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="title" class="form-label fw-bold">Titre *</label>
                                <input type="text" class="form-control" id="title" name="title" required 
                                       placeholder="Titre de la notification">
                            </div>
                            <div class="col-md-6">
                                <label for="message" class="form-label fw-bold">Message *</label>
                                <textarea class="form-control" id="message" name="message" rows="3" required 
                                          placeholder="Contenu de la notification"></textarea>
                            </div>
                        </div>

                        <!-- Utilisateurs cibles -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Destinataires</label>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="target_users" id="all_users" value="all" checked>
                                <label class="form-check-label" for="all_users">
                                    <i class="bi bi-people-fill text-primary"></i>
                                    Tous les utilisateurs actifs (<?php echo count($users); ?>)
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="target_users" id="specific_users" value="specific">
                                <label class="form-check-label" for="specific_users">
                                    <i class="bi bi-person-check text-success"></i>
                                    Utilisateurs spécifiques
                                </label>
                            </div>
                            
                            <div id="userSelection" style="display: none;">
                                <label class="form-label">Sélectionner les utilisateurs :</label>
                                <div class="user-selection">
                                    <?php foreach ($users as $user): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="selected_users[]" 
                                                   value="<?php echo $user['id']; ?>" id="user_<?php echo $user['id']; ?>">
                                            <label class="form-check-label" for="user_<?php echo $user['id']; ?>">
                                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                                <small class="text-muted">(<?php echo htmlspecialchars($user['email']); ?>)</small>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send"></i> Envoyer la Notification
                            </button>
                            <button type="reset" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise"></i> Réinitialiser
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Historique des notifications récentes -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history"></i>
                            Notifications Récentes
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $stmt = $pdo->prepare("
                            SELECT n.*, u.first_name, u.last_name 
                            FROM notifications n 
                            JOIN users u ON n.user_id = u.id 
                            WHERE n.type IN ('admin_broadcast', 'system_alert', 'maintenance', 'update')
                            ORDER BY n.created_at DESC 
                            LIMIT 10
                        ");
                        $stmt->execute();
                        $recent_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        
                        <?php if (empty($recent_notifications)): ?>
                            <p class="text-muted text-center">Aucune notification envoyée récemment.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Titre</th>
                                            <th>Destinataire</th>
                                            <th>Date</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_notifications as $notification): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-<?php echo NOTIFICATION_TYPES[$notification['type']]['color'] ?? 'secondary'; ?>">
                                                        <?php echo NOTIFICATION_TYPES[$notification['type']]['title'] ?? $notification['type']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($notification['title']); ?></td>
                                                <td><?php echo htmlspecialchars($notification['first_name'] . ' ' . $notification['last_name']); ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?></td>
                                                <td>
                                                    <?php if ($notification['is_read']): ?>
                                                        <span class="badge bg-success">Lu</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Non lu</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include '../layouts/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sélection du type de notification
        document.querySelectorAll('.type-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.type-option').forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('notification_type').value = this.dataset.type;
            });
        });

        // Affichage/masquage de la sélection d'utilisateurs
        document.querySelectorAll('input[name="target_users"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const userSelection = document.getElementById('userSelection');
                if (this.value === 'specific') {
                    userSelection.style.display = 'block';
                } else {
                    userSelection.style.display = 'none';
                }
            });
        });

        // Validation du formulaire
        document.getElementById('notificationForm').addEventListener('submit', function(e) {
            const targetUsers = document.querySelector('input[name="target_users"]:checked').value;
            if (targetUsers === 'specific') {
                const selectedUsers = document.querySelectorAll('input[name="selected_users[]"]:checked');
                if (selectedUsers.length === 0) {
                    e.preventDefault();
                    alert('Veuillez sélectionner au moins un utilisateur.');
                }
            }
        });
    </script>
</body>
</html> 