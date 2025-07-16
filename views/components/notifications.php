<?php
if (!isset($_SESSION['user_id'])) {
    return;
}

// Définir BASE_URL si pas déjà défini
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/lailaworkspace');
}

// Fonction pour formater le temps écoulé en PHP
if (!function_exists('timeAgo')) {
    function timeAgo($dateString) {
        $date = new DateTime($dateString);
        $now = new DateTime();
        $diff = $now->diff($date);
        
        if ($diff->y > 0) {
            return 'Il y a ' . $diff->y . ' an' . ($diff->y > 1 ? 's' : '');
        } elseif ($diff->m > 0) {
            return 'Il y a ' . $diff->m . ' mois';
        } elseif ($diff->d > 0) {
            return 'Il y a ' . $diff->d . ' jour' . ($diff->d > 1 ? 's' : '');
        } elseif ($diff->h > 0) {
            return 'Il y a ' . $diff->h . 'h';
        } elseif ($diff->i > 0) {
            return 'Il y a ' . $diff->i . ' min';
        } else {
            return 'À l\'instant';
        }
    }
}

// Utiliser des chemins absolus depuis la racine du projet
$root_path = dirname(dirname(__DIR__));
require_once $root_path . '/includes/db_connect.php';
require_once $root_path . '/models/Notification.php';
require_once $root_path . '/models/Achievement.php';

$notification = new Notification($pdo);
$achievement = new Achievement($pdo);

// Vérifier et débloquer les achievements automatiques
$newAchievements = $achievement->checkAndUnlockAchievements($_SESSION['user_id']);

// Créer des notifications pour les nouveaux achievements
foreach ($newAchievements as $achievementType) {
    $achievementData = [
        'first_bmc' => ['title' => 'Premier BMC', 'points' => 100],
        'bmc_completed' => ['title' => 'BMC Complété', 'points' => 200],
        'hypothesis_created' => ['title' => 'Hypothèses Créées', 'points' => 150],
        'financial_plan_created' => ['title' => 'Plan Financier', 'points' => 300],
        'partnership_found' => ['title' => 'Partenariat Trouvé', 'points' => 250],
        'streak_7_days' => ['title' => 'Streak 7 Jours', 'points' => 500],
        'streak_30_days' => ['title' => 'Streak 30 Jours', 'points' => 1000]
    ];
    
    if (isset($achievementData[$achievementType])) {
        $notification->createAchievementNotification(
            $_SESSION['user_id'],
            $achievementType,
            $achievementData[$achievementType]['title'],
            $achievementData[$achievementType]['points']
        );
    }
}

// Récupérer les notifications non lues
$unreadCount = $notification->getUnreadCount($_SESSION['user_id']);
$recentNotifications = $notification->getUserNotifications($_SESSION['user_id'], 5, true);
?>

<!-- Composant Notifications -->
<div class="notifications-component">
    <!-- Bouton Notifications -->
    <div class="nav-item position-relative">
        <a href="#" class="nav-link position-relative notification-toggle" 
           data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
            <i class="bi bi-bell"></i>
            <?php if ($unreadCount > 0): ?>
                <span class="notification-badge"><?= $unreadCount > 99 ? '99+' : $unreadCount ?></span>
            <?php endif; ?>
        </a>
        
        <!-- Dropdown Notifications -->
        <div class="dropdown-menu dropdown-menu-end notification-dropdown" style="width: 350px; max-height: 400px; overflow-y: auto;">
            <div class="dropdown-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Notifications</h6>
                <?php if ($unreadCount > 0): ?>
                    <button class="btn btn-sm btn-link text-decoration-none" onclick="markAllNotificationsAsRead()">
                        Tout marquer comme lu
                    </button>
                <?php endif; ?>
            </div>
            
            <div class="notification-list">
                <?php if (empty($recentNotifications)): ?>
                    <div class="text-center py-3">
                        <i class="bi bi-bell-slash text-muted" style="font-size: 2rem;"></i>
                        <p class="text-muted mb-0">Aucune notification</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentNotifications as $notif): ?>
                        <div class="notification-item <?= $notif['is_read'] ? '' : 'unread' ?>" 
                             data-notification-id="<?= $notif['id'] ?>">
                            <div class="notification-icon">
                                <?php
                                $icon = match($notif['type']) {
                                    'welcome' => 'bi-emoji-smile',
                                    'bmc_completion' => 'bi-check-circle',
                                    'hypothesis_reminder' => 'bi-lightbulb',
                                    'financial_plan' => 'bi-wallet2',
                                    'partnership_suggestion' => 'bi-people',
                                    'achievement' => 'bi-trophy',
                                    'system_alert' => 'bi-exclamation-triangle',
                                    default => 'bi-bell'
                                };
                                ?>
                                <i class="bi <?= $icon ?>"></i>
                            </div>
                            <div class="notification-content">
                                <div class="notification-title"><?= htmlspecialchars($notif['title']) ?></div>
                                <div class="notification-message"><?= htmlspecialchars($notif['message']) ?></div>
                                <div class="notification-time">
                                    <?= timeAgo($notif['created_at']) ?>
                                </div>
                            </div>
                            <div class="notification-actions">
                                <button class="btn btn-sm btn-link text-decoration-none" 
                                        onclick="markNotificationAsRead(<?= $notif['id'] ?>)">
                                    <i class="bi bi-check"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="dropdown-divider"></div>
            <div class="dropdown-footer text-center">
                <a href="<?= BASE_URL ?>/views/user/notifications.php" class="btn btn-sm btn-outline-primary">
                    Voir toutes les notifications
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Script de synchronisation automatique -->
<script>
let lastCheckTime = '<?= date('Y-m-d H:i:s') ?>';
let syncInterval;

// Fonction pour synchroniser les notifications
async function syncNotifications() {
    try {
        const response = await fetch(`<?= BASE_URL ?>/api/get_new_notifications.php?last_check=${lastCheckTime}`);
        const data = await response.json();
        
        if (data.success && data.notifications.length > 0) {
            // Mettre à jour le badge
            updateNotificationBadge(data.unread_count);
            
            // Ajouter les nouvelles notifications
            addNewNotifications(data.notifications);
            
            // Mettre à jour le timestamp
            lastCheckTime = data.timestamp;
            
            // Afficher une notification toast pour les notifications importantes
            data.notifications.forEach(notification => {
                if (notification.important || notification.is_system) {
                    showNotificationToast(notification);
                }
            });
        }
    } catch (error) {
        console.error('Erreur de synchronisation:', error);
    }
}

// Fonction pour mettre à jour le badge
function updateNotificationBadge(count) {
    const badge = document.querySelector('.notification-badge');
    const toggle = document.querySelector('.notification-toggle');
    
    if (count > 0) {
        if (badge) {
            badge.textContent = count > 99 ? '99+' : count;
        } else {
            const newBadge = document.createElement('span');
            newBadge.className = 'notification-badge';
            newBadge.textContent = count > 99 ? '99+' : count;
            toggle.appendChild(newBadge);
        }
    } else {
        if (badge) {
            badge.remove();
        }
    }
}

// Fonction pour ajouter de nouvelles notifications
function addNewNotifications(notifications) {
    const notificationList = document.querySelector('.notification-list');
    
    notifications.forEach(notification => {
        const notificationItem = createNotificationElement(notification);
        notificationList.insertBefore(notificationItem, notificationList.firstChild);
    });
    
    // Supprimer les anciennes notifications si trop nombreuses
    const items = notificationList.querySelectorAll('.notification-item');
    if (items.length > 10) {
        for (let i = 10; i < items.length; i++) {
            items[i].remove();
        }
    }
}

// Fonction pour créer un élément de notification
function createNotificationElement(notification) {
    const div = document.createElement('div');
    div.className = `notification-item unread`;
    div.dataset.notificationId = notification.id;
    
    div.innerHTML = `
        <div class="notification-icon">
            <i class="bi ${notification.icon}"></i>
        </div>
        <div class="notification-content">
            <div class="notification-title">${notification.title}</div>
            <div class="notification-message">${notification.message}</div>
            <div class="notification-time">${notification.time_ago}</div>
        </div>
        <div class="notification-actions">
            <button class="btn btn-sm btn-link text-decoration-none" onclick="markNotificationAsRead(${notification.id})">
                <i class="bi bi-check"></i>
            </button>
        </div>
    `;
    
    return div;
}

// Fonction pour afficher une notification toast
function showNotificationToast(notification) {
    // Créer le toast
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = 'toast show';
    toast.innerHTML = `
        <div class="toast-header">
            <i class="bi ${notification.icon} text-${notification.color} me-2"></i>
            <strong class="me-auto">${notification.title}</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            ${notification.message}
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    // Supprimer le toast après 5 secondes
    setTimeout(() => {
        toast.remove();
    }, 5000);
}

// Fonction pour créer le conteneur de toasts
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

// Démarrer la synchronisation automatique
function startNotificationSync() {
    // Synchroniser toutes les 30 secondes
    syncInterval = setInterval(syncNotifications, 30000);
    
    // Synchroniser immédiatement au chargement
    syncNotifications();
}

// Arrêter la synchronisation
function stopNotificationSync() {
    if (syncInterval) {
        clearInterval(syncInterval);
    }
}

// Démarrer la synchronisation quand la page est chargée
document.addEventListener('DOMContentLoaded', startNotificationSync);

// Arrêter la synchronisation quand la page n'est plus visible
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        stopNotificationSync();
    } else {
        startNotificationSync();
    }
});
</script>

<style>
.notifications-component {
    position: relative;
}

.notification-toggle {
    position: relative;
    padding: 0.5rem !important;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.notification-toggle:hover {
    background-color: rgba(0, 123, 255, 0.1);
}

.notification-badge {
    position: absolute;
    top: 0;
    right: 0;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 0.7rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.notification-dropdown {
    border: none;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    border-radius: 12px;
    padding: 0;
}

.notification-dropdown .dropdown-header {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 12px 12px 0 0;
    border-bottom: 1px solid #e9ecef;
}

.notification-list {
    max-height: 300px;
    overflow-y: auto;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    padding: 1rem;
    border-bottom: 1px solid #f1f3f4;
    transition: background-color 0.2s ease;
    cursor: pointer;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.unread {
    background-color: #e3f2fd;
}

.notification-item.unread:hover {
    background-color: #bbdefb;
}

.notification-icon {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #007bff, #00c4b4);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    margin-right: 12px;
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-title {
    font-weight: 600;
    font-size: 0.9rem;
    color: #212529;
    margin-bottom: 4px;
}

.notification-message {
    font-size: 0.8rem;
    color: #6c757d;
    line-height: 1.4;
    margin-bottom: 4px;
}

.notification-time {
    font-size: 0.75rem;
    color: #adb5bd;
}

.notification-actions {
    flex-shrink: 0;
    margin-left: 8px;
}

.dropdown-footer {
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 0 0 12px 12px;
}

/* Animation pour les nouvelles notifications */
@keyframes notificationPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.notification-badge {
    animation: notificationPulse 2s infinite;
}
</style>

<script>
// Fonction pour marquer une notification comme lue
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
            // Mettre à jour l'interface
            const notificationItem = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (notificationItem) {
                notificationItem.classList.remove('unread');
            }
            
            // Mettre à jour le compteur
            updateNotificationCount();
        }
    })
    .catch(error => console.error('Erreur:', error));
}

// Fonction pour marquer toutes les notifications comme lues
function markAllNotificationsAsRead() {
    fetch('<?= BASE_URL ?>/api/mark_all_notifications_read.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mettre à jour l'interface
            document.querySelectorAll('.notification-item.unread').forEach(item => {
                item.classList.remove('unread');
            });
            
            // Mettre à jour le compteur
            updateNotificationCount();
        }
    })
    .catch(error => console.error('Erreur:', error));
}

// Fonction pour mettre à jour le compteur de notifications
function updateNotificationCount() {
    fetch('<?= BASE_URL ?>/api/get_notification_count.php')
    .then(response => response.json())
    .then(data => {
        const badge = document.querySelector('.notification-badge');
        if (data.count > 0) {
            if (badge) {
                badge.textContent = data.count > 99 ? '99+' : data.count;
            } else {
                // Créer le badge s'il n'existe pas
                const toggle = document.querySelector('.notification-toggle');
                const newBadge = document.createElement('span');
                newBadge.className = 'notification-badge';
                newBadge.textContent = data.count > 99 ? '99+' : data.count;
                toggle.appendChild(newBadge);
            }
        } else {
            if (badge) {
                badge.remove();
            }
        }
    })
    .catch(error => console.error('Erreur:', error));
}

// Actualiser les notifications toutes les 30 secondes
setInterval(updateNotificationCount, 30000);

// Fonction pour formater le temps écoulé
function timeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) {
        return 'À l\'instant';
    } else if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60);
        return `Il y a ${minutes} min`;
    } else if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        return `Il y a ${hours}h`;
    } else {
        const days = Math.floor(diffInSeconds / 86400);
        return `Il y a ${days}j`;
    }
}
</script> 