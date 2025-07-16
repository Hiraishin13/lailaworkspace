-- Script SQL pour les tables du back-office Laila Workspace
-- Compatible avec MySQL 5.7+

-- 1. Table des rôles utilisateurs
CREATE TABLE IF NOT EXISTS user_roles (
    id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    permissions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- 2. Table d'audit des actions
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT NOT NULL,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values TEXT,
    new_values TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- 3. Table des consentements RGPD
CREATE TABLE IF NOT EXISTS gdpr_consents (
    id INT NOT NULL,
    user_id INT NOT NULL,
    consent_type VARCHAR(50) NOT NULL,
    granted TINYINT(1) DEFAULT 0,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- 4. Table des partenariats B2B
CREATE TABLE IF NOT EXISTS partnerships (
    id INT NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    contact_email VARCHAR(255),
    contact_phone VARCHAR(20),
    industry VARCHAR(100),
    description TEXT,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- 5. Table des notifications système
CREATE TABLE IF NOT EXISTS system_notifications (
    id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(20) DEFAULT 'info',
    target_role_id INT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- 6. Table des exports de données
CREATE TABLE IF NOT EXISTS data_exports (
    id INT NOT NULL,
    user_id INT NOT NULL,
    export_type VARCHAR(50) NOT NULL,
    file_path VARCHAR(500),
    file_size INT,
    status VARCHAR(20) DEFAULT 'pending',
    filters TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    PRIMARY KEY (id)
);

-- 7. Table des segments utilisateurs
CREATE TABLE IF NOT EXISTS user_segments (
    id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    criteria TEXT NOT NULL,
    user_count INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- 8. Table des alertes système
CREATE TABLE IF NOT EXISTS system_alerts (
    id INT NOT NULL,
    alert_type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    severity VARCHAR(20) DEFAULT 'medium',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    PRIMARY KEY (id)
);

-- 9. Table des métriques de performance
CREATE TABLE IF NOT EXISTS performance_metrics (
    id INT NOT NULL,
    metric_name VARCHAR(100) NOT NULL,
    metric_value DECIMAL(10,4) NOT NULL,
    metric_unit VARCHAR(20),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- Insérer les rôles par défaut
INSERT IGNORE INTO user_roles (id, name, description, permissions) VALUES
(1, 'user', 'Utilisateur standard', '{"bmc": ["read", "write"], "profile": ["read", "write"]}'),
(2, 'admin', 'Administrateur système', '{"all": ["read", "write", "delete"]}'),
(3, 'moderator', 'Modérateur', '{"users": ["read"], "content": ["read", "write"], "reports": ["read"]}');

-- Ajouter la colonne role_id à la table users
ALTER TABLE users ADD COLUMN role_id INT DEFAULT 1;

-- Insérer des données de test pour le back-office
INSERT IGNORE INTO system_notifications (id, title, message, type, target_role_id) VALUES
(1, 'Bienvenue dans Laila Workspace', 'Le back-office est maintenant opérationnel', 'info', 2),
(2, 'Mise à jour système', 'Nouvelles fonctionnalités disponibles', 'info', 2);

INSERT IGNORE INTO system_alerts (id, alert_type, title, message, severity) VALUES
(1, 'system', 'Système opérationnel', 'Tous les services fonctionnent normalement', 'low');

INSERT IGNORE INTO performance_metrics (id, metric_name, metric_value, metric_unit) VALUES
(1, 'response_time', 0.245, 'seconds'),
(2, 'uptime', 99.95, 'percent'),
(3, 'active_users', 150, 'users'); 