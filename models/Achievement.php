<?php
require_once __DIR__ . '/../includes/db_connect.php';

class Achievement {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Débloquer un achievement
     */
    public function unlock($userId, $type, $title, $description = '', $icon = '', $points = 0) {
        try {
            // Vérifier si l'achievement n'est pas déjà débloqué
            $stmt = $this->pdo->prepare("
                SELECT id FROM achievements 
                WHERE user_id = ? AND type = ?
            ");
            $stmt->execute([$userId, $type]);
            
            if ($stmt->fetch()) {
                return false; // Déjà débloqué
            }
            
            // Débloquer l'achievement
            $stmt = $this->pdo->prepare("
                INSERT INTO achievements (user_id, type, title, description, icon, points) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([$userId, $type, $title, $description, $icon, $points]);
        } catch (PDOException $e) {
            error_log('Erreur déblocage achievement: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupérer les achievements d'un utilisateur
     */
    public function getUserAchievements($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM achievements 
                WHERE user_id = ? 
                ORDER BY unlocked_at DESC
            ");
            
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Erreur récupération achievements: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calculer le score total d'un utilisateur
     */
    public function getUserScore($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT SUM(points) as total_score 
                FROM achievements 
                WHERE user_id = ?
            ");
            
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['total_score'] ?? 0;
        } catch (PDOException $e) {
            error_log('Erreur calcul score: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Vérifier et débloquer les achievements automatiques
     */
    public function checkAndUnlockAchievements($userId) {
        $unlocked = [];
        
        try {
            // Achievement: Premier BMC
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count FROM projects WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] == 1) {
                if ($this->unlock($userId, 'first_bmc', 'Premier BMC', 'Vous avez créé votre premier Business Model Canvas', 'bi-rocket-takeoff', 100)) {
                    $unlocked[] = 'first_bmc';
                }
            }
            
            // Achievement: BMC Complété
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as completed_count 
                FROM projects p 
                WHERE p.user_id = ? AND (
                    SELECT COUNT(*) 
                    FROM bmc b 
                    WHERE b.project_id = p.id AND b.content != '' AND b.content != 'Non spécifié'
                ) >= 9
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['completed_count'] > 0) {
                if ($this->unlock($userId, 'bmc_completed', 'BMC Complété', 'Vous avez rempli tous les blocs de votre BMC', 'bi-check-circle', 200)) {
                    $unlocked[] = 'bmc_completed';
                }
            }
            
            // Achievement: Hypothèses Créées
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM hypotheses h 
                INNER JOIN projects p ON h.project_id = p.id 
                WHERE p.user_id = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] >= 5) {
                if ($this->unlock($userId, 'hypothesis_created', 'Hypothèses Créées', 'Vous avez créé vos premières hypothèses', 'bi-lightbulb', 150)) {
                    $unlocked[] = 'hypothesis_created';
                }
            }
            
            // Achievement: Plan Financier Créé
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM financial_plans fp 
                INNER JOIN projects p ON fp.project_id = p.id 
                WHERE p.user_id = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                if ($this->unlock($userId, 'financial_plan_created', 'Plan Financier', 'Vous avez créé votre premier plan financier', 'bi-wallet2', 300)) {
                    $unlocked[] = 'financial_plan_created';
                }
            }
            
            // Achievement: Partenariat Trouvé
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM partnership_suggestions ps 
                INNER JOIN projects p ON ps.project1_id = p.id OR ps.project2_id = p.id 
                WHERE p.user_id = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                if ($this->unlock($userId, 'partnership_found', 'Partenariat Trouvé', 'Vous avez trouvé un partenaire potentiel', 'bi-people', 250)) {
                    $unlocked[] = 'partnership_found';
                }
            }
            
            // Achievement: Streak 7 jours
            $stmt = $this->pdo->prepare("
                SELECT COUNT(DISTINCT DATE(created_at)) as days 
                FROM projects 
                WHERE user_id = ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['days'] >= 7) {
                if ($this->unlock($userId, 'streak_7_days', 'Streak 7 Jours', 'Vous avez travaillé 7 jours consécutifs', 'bi-calendar-check', 500)) {
                    $unlocked[] = 'streak_7_days';
                }
            }
            
            // Achievement: Streak 30 jours
            $stmt = $this->pdo->prepare("
                SELECT COUNT(DISTINCT DATE(created_at)) as days 
                FROM projects 
                WHERE user_id = ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['days'] >= 30) {
                if ($this->unlock($userId, 'streak_30_days', 'Streak 30 Jours', 'Vous avez travaillé 30 jours consécutifs', 'bi-calendar-star', 1000)) {
                    $unlocked[] = 'streak_30_days';
                }
            }
            
        } catch (PDOException $e) {
            error_log('Erreur vérification achievements: ' . $e->getMessage());
        }
        
        return $unlocked;
    }
    
    /**
     * Récupérer les statistiques d'achievements
     */
    public function getAchievementStats($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_achievements,
                    SUM(points) as total_points,
                    MAX(unlocked_at) as last_achievement
                FROM achievements 
                WHERE user_id = ?
            ");
            
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Erreur statistiques achievements: ' . $e->getMessage());
            return ['total_achievements' => 0, 'total_points' => 0, 'last_achievement' => null];
        }
    }
}
?> 