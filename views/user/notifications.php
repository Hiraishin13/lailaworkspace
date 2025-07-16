<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/config.php';
require_once '../../models/Notification.php';
require_once '../../models/Achievement.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/views/auth/login.php');
    exit();
}

$notification = new Notification($pdo);
$achievement = new Achievement($pdo);

// Marquer toutes les notifications comme lues si demandé
if (isset($_GET['mark_all_read']) && $_GET['mark_all_read'] === '1') {
    $notification->markAllAsRead($_SESSION['user_id']);
    header('Location: ' . BASE_URL . '/views/user/notifications.php');
    exit();
}

// Supprimer une notification si demandé
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $notification->delete($_GET['delete'], $_SESSION['user_id']);
    header('Location: ' . BASE_URL . '/views/user/notifications.php');
    exit();
}

// Récupérer toutes les notifications
$allNotifications = $notification->getUserNotifications($_SESSION['user_id'], 50);
$unreadCount = $notification->getUnreadCount($_SESSION['user_id']);

// Récupérer les statistiques d'achievements
$achievementStats = $achievement->getAchievementStats($_SESSION['user_id']);
$userScore = $achievement->getUserScore($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Laila Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include '../layouts/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <!-- En-tête -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-1">
                            <i class="bi bi-bell text-primary"></i> Notifications
                        </h1>
                        <p class="text-muted mb-0">
                            <?= $unreadCount ?> notification<?= $unreadCount > 1 ? 's' : '' ?> non lue<?= $unreadCount > 1 ? 's' : '' ?>
                        </p>
                    </div>
                    <div class="btn-group">
                        <?php if ($unreadCount > 0): ?>
                            <a href="?mark_all_read=1" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-check-all"></i> Tout marquer comme lu
                            </a>
                        <?php endif; ?>
                        <button class="btn btn-outline-secondary btn-sm" onclick="refreshNotifications()">
                            <i class="bi bi-arrow-clockwise"></i> Actualiser
                        </button>
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="bi bi-trophy text-warning" style="font-size: 2rem;"></i>
                                <h4 class="mt-2 mb-1"><?= $achievementStats['total_achievements'] ?></h4>
                                <p class="text-muted mb-0">Badges débloqués</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="bi bi-star text-primary" style="font-size: 2rem;"></i>
                                <h4 class="mt-2 mb-1"><?= number_format($userScore) ?></h4>
                                <p class="text-muted mb-0">Points totaux</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="bi bi-bell text-success" style="font-size: 2rem;"></i>
                                <h4 class="mt-2 mb-1"><?= count($allNotifications) ?></h4>
                                <p class="text-muted mb-0">Notifications totales</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Liste des notifications -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <?php if (empty($allNotifications)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-bell-slash text-muted" style="font-size: 4rem;"></i>
                                <h4 class="mt-3 text-muted">Aucune notification</h4>
                                <p class="text-muted">Vous n'avez pas encore reçu de notifications.</p>
                                <a href="<?= BASE_URL ?>/views/bmc/generate_bmc.php" class="btn btn-primary">
                                    <i class="bi bi-plus-circle"></i> Créer votre premier BMC
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($allNotifications as $notif): ?>
                                    <div class="list-group-item list-group-item-action border-0 <?= $notif['is_read'] ? '' : 'bg-light' ?>" 
                                         data-notification-id="<?= $notif['id'] ?>">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0 me-3">
                                                <?php
                                                $icon = match($notif['type']) {
                                                    'welcome' => 'bi-emoji-smile text-success',
                                                    'bmc_completion' => 'bi-check-circle text-success',
                                                    'hypothesis_reminder' => 'bi-lightbulb text-warning',
                                                    'financial_plan' => 'bi-wallet2 text-info',
                                                    'partnership_suggestion' => 'bi-people text-primary',
                                                    'achievement' => 'bi-trophy text-warning',
                                                    'system_alert' => 'bi-exclamation-triangle text-danger',
                                                    default => 'bi-bell text-secondary'
                                                };
                                                ?>
                                                <i class="bi <?= $icon ?>" style="font-size: 1.5rem;"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1 <?= $notif['is_read'] ? 'text-muted' : 'fw-bold' ?>">
                                                            <?= htmlspecialchars($notif['title']) ?>
                                                        </h6>
                                                        <p class="mb-1 text-muted">
                                                            <?= htmlspecialchars($notif['message']) ?>
                                                        </p>
                                                        <small class="text-muted">
                                                            <i class="bi bi-clock"></i>
                                                            <?= timeAgo($notif['created_at']) ?>
                                                        </small>
                                                    </div>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-link text-muted" 
                                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="bi bi-three-dots-vertical"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <?php if (!$notif['is_read']): ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="#" 
                                                                       onclick="markNotificationAsRead(<?= $notif['id'] ?>)">
                                                                        <i class="bi bi-check"></i> Marquer comme lu
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                            <li>
                                                                <a class="dropdown-item text-danger" href="?delete=<?= $notif['id'] ?>"
                                                                   onclick="return confirm('Supprimer cette notification ?')">
                                                                    <i class="bi bi-trash"></i> Supprimer
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
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

    <?php include '../layouts/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function markNotificationAsRead(notificationId) {
            fetch('<?= BASE_URL ?>/api/mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    notification_id: notificationId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const notificationItem = document.querySelector(`[data-notification-id="${notificationId}"]`);
                    if (notificationItem) {
                        notificationItem.classList.remove('bg-light');
                        notificationItem.classList.add('text-muted');
                        const title = notificationItem.querySelector('h6');
                        if (title) {
                            title.classList.remove('fw-bold');
                            title.classList.add('text-muted');
                        }
                    }
                    location.reload();
                }
            })
            .catch(error => console.error('Erreur:', error));
        }

        function refreshNotifications() {
            location.reload();
        }

        // Actualiser automatiquement toutes les 30 secondes
        setInterval(refreshNotifications, 30000);
    </script>
</body>
</html>

<?php
function timeAgo($dateString) {
    $date = new DateTime($dateString);
    $now = new DateTime();
    $diff = $now->diff($date);
    
    if ($diff->y > 0) {
        return "Il y a {$diff->y} an" . ($diff->y > 1 ? 's' : '');
    } elseif ($diff->m > 0) {
        return "Il y a {$diff->m} mois";
    } elseif ($diff->d > 0) {
        return "Il y a {$diff->d} jour" . ($diff->d > 1 ? 's' : '');
    } elseif ($diff->h > 0) {
        return "Il y a {$diff->h}h";
    } elseif ($diff->i > 0) {
        return "Il y a {$diff->i} min";
    } else {
        return "À l'instant";
    }
}
?> 