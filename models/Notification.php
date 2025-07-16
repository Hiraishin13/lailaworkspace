<?php
require_once __DIR__ . '/../includes/db_connect.php';

class Notification {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Créer une nouvelle notification
     */
    public function create($userId, $type, $title, $message, $data = null, $isImportant = false, $expiresAt = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (user_id, type, title, message, data, is_important, expires_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $jsonData = $data ? json_encode($data) : null;
            
            return $stmt->execute([
                $userId, $type, $title, $message, $jsonData, $isImportant, $expiresAt
            ]);
        } catch (PDOException $e) {
            error_log('Erreur création notification: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupérer les notifications d'un utilisateur
     */
    public function getUserNotifications($userId, $limit = 10, $unreadOnly = false) {
        try {
            $sql = "
                SELECT * FROM notifications 
                WHERE user_id = ? 
                AND (expires_at IS NULL OR expires_at > NOW())
            ";
            
            if ($unreadOnly) {
                $sql .= " AND is_read = 0";
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Erreur récupération notifications: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Marquer une notification comme lue
     */
    public function markAsRead($notificationId, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE id = ? AND user_id = ?
            ");
            
            return $stmt->execute([$notificationId, $userId]);
        } catch (PDOException $e) {
            error_log('Erreur marquage notification: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead($userId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE user_id = ? AND is_read = 0
            ");
            
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log('Erreur marquage notifications: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Compter les notifications non lues
     */
    public function getUnreadCount($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM notifications 
                WHERE user_id = ? AND is_read = 0 
                AND (expires_at IS NULL OR expires_at > NOW())
            ");
            
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            error_log('Erreur comptage notifications: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Supprimer une notification
     */
    public function delete($notificationId, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM notifications 
                WHERE id = ? AND user_id = ?
            ");
            
            return $stmt->execute([$notificationId, $userId]);
        } catch (PDOException $e) {
            error_log('Erreur suppression notification: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprimer les anciennes notifications expirées
     */
    public function cleanupExpired() {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM notifications 
                WHERE expires_at IS NOT NULL AND expires_at < NOW()
            ");
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Erreur nettoyage notifications: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Créer une notification de bienvenue
     */
    public function createWelcomeNotification($userId) {
        return $this->create(
            $userId,
            'welcome',
            'Bienvenue sur Laila Workspace !',
            'Commencez votre aventure entrepreneuriale en créant votre premier BMC.',
            ['action' => 'create_bmc', 'url' => '/views/bmc/generate_bmc.php'],
            true
        );
    }
    
    /**
     * Créer une notification de complétion BMC
     */
    public function createBMCCompletionNotification($userId, $projectId) {
        return $this->create(
            $userId,
            'bmc_completion',
            'Félicitations ! Votre BMC est complet',
            'Vous avez rempli tous les blocs de votre Business Model Canvas. Passez aux hypothèses !',
            ['action' => 'view_hypotheses', 'project_id' => $projectId],
            false
        );
    }
    
    /**
     * Créer une notification de rappel hypothèses
     */
    public function createHypothesisReminder($userId, $projectId) {
        return $this->create(
            $userId,
            'hypothesis_reminder',
            'Rappel : Complétez vos hypothèses',
            'N\'oubliez pas de valider vos hypothèses pour avancer dans votre projet.',
            ['action' => 'view_hypotheses', 'project_id' => $projectId],
            false,
            date('Y-m-d H:i:s', strtotime('+7 days'))
        );
    }
    
    /**
     * Créer une notification de plan financier
     */
    public function createFinancialPlanNotification($userId, $projectId) {
        return $this->create(
            $userId,
            'financial_plan',
            'Plan financier disponible',
            'Votre plan financier a été généré avec succès. Consultez-le maintenant !',
            ['action' => 'view_financial_plan', 'project_id' => $projectId],
            false
        );
    }
    
    /**
     * Créer une notification de suggestion de partenariat
     */
    public function createPartnershipSuggestion($userId, $suggestionId) {
        return $this->create(
            $userId,
            'partnership_suggestion',
            'Nouvelle suggestion de partenariat',
            'Nous avons trouvé un partenaire potentiel pour votre projet !',
            ['action' => 'view_partnership', 'suggestion_id' => $suggestionId],
            true
        );
    }
    
    /**
     * Créer une notification d'achievement
     */
    public function createAchievementNotification($userId, $achievementType, $title, $points = 0) {
        return $this->create(
            $userId,
            'achievement',
            'Nouveau badge débloqué !',
            $title,
            ['achievement_type' => $achievementType, 'points' => $points],
            false
        );
    }
}
?> 