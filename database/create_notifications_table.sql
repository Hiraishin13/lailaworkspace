-- Script de création de la table de notifications pour Laila Workspace
-- Exécutez ce script dans votre base de données MySQL

-- Table des notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('bmc_completion', 'hypothesis_reminder', 'financial_plan', 'partnership_suggestion', 'system_alert', 'welcome', 'achievement') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSON,
    is_read TINYINT(1) DEFAULT 0,
    is_important TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at),
    INDEX idx_expires_at (expires_at)
);

-- Table des préférences de notifications
CREATE TABLE IF NOT EXISTS notification_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    email_notifications TINYINT(1) DEFAULT 1,
    push_notifications TINYINT(1) DEFAULT 1,
    bmc_completion_notifications TINYINT(1) DEFAULT 1,
    hypothesis_reminders TINYINT(1) DEFAULT 1,
    financial_plan_notifications TINYINT(1) DEFAULT 1,
    partnership_suggestions TINYINT(1) DEFAULT 1,
    system_alerts TINYINT(1) DEFAULT 1,
    achievement_notifications TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des achievements (réalisations)
CREATE TABLE IF NOT EXISTS achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('first_bmc', 'bmc_completed', 'hypothesis_created', 'financial_plan_created', 'partnership_found', 'streak_7_days', 'streak_30_days') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    icon VARCHAR(100),
    points INT DEFAULT 0,
    unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_achievement (user_id, type),
    INDEX idx_user_id (user_id),
    INDEX idx_type (type)
);

-- Insertion de données de test pour les notifications
INSERT IGNORE INTO notifications (user_id, type, title, message, data, is_important) VALUES
(1, 'welcome', 'Bienvenue sur Laila Workspace !', 'Commencez votre aventure entrepreneuriale en créant votre premier BMC.', '{"action": "create_bmc", "url": "/views/bmc/generate_bmc.php"}', 1),
(1, 'bmc_completion', 'Félicitations ! Votre BMC est complet', 'Vous avez rempli tous les blocs de votre Business Model Canvas. Passez aux hypothèses !', '{"action": "view_hypotheses", "project_id": 1}', 0),
(1, 'hypothesis_reminder', 'Rappel : Complétez vos hypothèses', 'N\'oubliez pas de valider vos hypothèses pour avancer dans votre projet.', '{"action": "view_hypotheses", "project_id": 1}', 0),
(1, 'financial_plan', 'Plan financier disponible', 'Votre plan financier a été généré avec succès. Consultez-le maintenant !', '{"action": "view_financial_plan", "project_id": 1}', 0),
(1, 'partnership_suggestion', 'Nouvelle suggestion de partenariat', 'Nous avons trouvé un partenaire potentiel pour votre projet !', '{"action": "view_partnership", "suggestion_id": 1}', 1),
(1, 'achievement', 'Nouveau badge débloqué !', 'Vous avez débloqué le badge "Premier BMC" !', '{"achievement_type": "first_bmc", "points": 100}', 0);

-- Insertion des préférences de notifications par défaut
INSERT IGNORE INTO notification_preferences (user_id) VALUES (1);

-- Insertion d'achievements de test
INSERT IGNORE INTO achievements (user_id, type, title, description, icon, points) VALUES
(1, 'first_bmc', 'Premier BMC', 'Vous avez créé votre premier Business Model Canvas', 'bi-rocket-takeoff', 100),
(1, 'bmc_completed', 'BMC Complété', 'Vous avez rempli tous les blocs de votre BMC', 'bi-check-circle', 200),
(1, 'hypothesis_created', 'Hypothèses Créées', 'Vous avez créé vos premières hypothèses', 'bi-lightbulb', 150);

-- Message de confirmation
SELECT 'Tables de notifications créées avec succès !' as message; 