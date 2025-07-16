-- Script de création des tables pour Laila Workspace Dashboard
-- Exécutez ce script dans votre base de données MySQL

-- 1. Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    experience TEXT,
    password VARCHAR(255) NOT NULL,
    role_id INT DEFAULT 1,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role_id (role_id),
    INDEX idx_status (status)
);

-- 2. Table des projets
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    target_market VARCHAR(255),
    status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
    share_consent TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_status (status)
);

-- 3. Table des blocs BMC
CREATE TABLE IF NOT EXISTS bmc (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    block_name VARCHAR(100) NOT NULL,
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_project_id (project_id),
    INDEX idx_block_name (block_name)
);

-- 4. Table des hypothèses
CREATE TABLE IF NOT EXISTS hypotheses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    status ENUM('pending', 'validated', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_project_id (project_id),
    INDEX idx_status (status)
);

-- 5. Table des plans financiers
CREATE TABLE IF NOT EXISTS financial_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    total_revenue DECIMAL(15,2) DEFAULT 0,
    total_costs DECIMAL(15,2) DEFAULT 0,
    profit_margin DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_project_id (project_id)
);

-- 6. Table des logs d'audit
CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action_type VARCHAR(50) NOT NULL,
    resource_type VARCHAR(50),
    resource_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at)
);

-- 7. Table des logs système
CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level ENUM('INFO', 'WARNING', 'ERROR', 'DEBUG') NOT NULL,
    message TEXT NOT NULL,
    context JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_level (level),
    INDEX idx_created_at (created_at)
);

-- 8. Table des réinitialisations de mot de passe
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at)
);

-- 9. Table des suggestions de partenariats
CREATE TABLE IF NOT EXISTS partnership_suggestions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project1_id INT NOT NULL,
    project2_id INT NOT NULL,
    synergy_score INT NOT NULL,
    synergy_reasons TEXT,
    status ENUM('pending', 'notified', 'active', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notified_at TIMESTAMP NULL,
    INDEX idx_project1_id (project1_id),
    INDEX idx_project2_id (project2_id),
    INDEX idx_status (status)
);

-- Insertion de données de test

-- Utilisateur admin
INSERT IGNORE INTO users (id, username, first_name, last_name, email, password, role_id, status) 
VALUES (1, 'admin', 'Admin', 'User', 'admin@lailaworkspace.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'active');

-- Projets de test
INSERT IGNORE INTO projects (id, name, description, target_market, user_id) VALUES
(1, 'Application Mobile', 'Une application mobile innovante pour la gestion des tâches', 'Consommateurs', 1),
(2, 'Plateforme E-commerce', 'Site de vente en ligne avec paiement sécurisé', 'Entreprises', 1),
(3, 'Service SaaS', 'Logiciel en tant que service pour la gestion d\'entreprise', 'Professionnels', 1),
(4, 'Application Web', 'Application web moderne pour la collaboration', 'Particuliers', 1),
(5, 'Service Consulting', 'Service de conseil en transformation digitale', 'PME', 1);

-- Blocs BMC pour chaque projet
INSERT IGNORE INTO bmc (project_id, block_name, content) VALUES
-- Projet 1
(1, 'Segments de clientèle', 'Professionnels occupés, 25-45 ans, utilisateurs de smartphones'),
(1, 'Proposition de valeur', 'Gestion des tâches simplifiée et intuitive'),
(1, 'Canaux', 'App Store, Google Play, réseaux sociaux'),
(1, 'Relations clients', 'Support client 24/7, communauté utilisateurs'),
(1, 'Flux de revenus', 'Abonnement mensuel, version premium'),
(1, 'Ressources clés', 'Équipe de développement, infrastructure cloud'),
(1, 'Activités clés', 'Développement, maintenance, support'),
(1, 'Partenaires clés', 'Fournisseurs cloud, agences marketing'),
(1, 'Structure de coûts', 'Développement, marketing, infrastructure'),

-- Projet 2
(2, 'Segments de clientèle', 'Petites entreprises, commerçants en ligne'),
(2, 'Proposition de valeur', 'Plateforme e-commerce complète et sécurisée'),
(2, 'Canaux', 'Site web, SEO, publicité en ligne'),
(2, 'Relations clients', 'Support dédié, formation utilisateurs'),
(2, 'Flux de revenus', 'Commission sur ventes, abonnement'),
(2, 'Ressources clés', 'Plateforme technique, équipe support'),
(2, 'Activités clés', 'Développement, sécurité, support'),
(2, 'Partenaires clés', 'Fournisseurs de paiement, transporteurs'),
(2, 'Structure de coûts', 'Développement, sécurité, marketing'),

-- Projet 3
(3, 'Segments de clientèle', 'PME, startups, freelances'),
(3, 'Proposition de valeur', 'Gestion d\'entreprise simplifiée et automatisée'),
(3, 'Canaux', 'Site web, partenaires, événements'),
(3, 'Relations clients', 'Support personnalisé, formation'),
(3, 'Flux de revenus', 'Abonnement mensuel/annuel'),
(3, 'Ressources clés', 'Plateforme SaaS, équipe technique'),
(3, 'Activités clés', 'Développement, maintenance, support'),
(3, 'Partenaires clés', 'Comptables, experts-comptables'),
(3, 'Structure de coûts', 'Développement, infrastructure, support'),

-- Projet 4
(4, 'Segments de clientèle', 'Équipes de travail, étudiants, créateurs'),
(4, 'Proposition de valeur', 'Collaboration en temps réel simplifiée'),
(4, 'Canaux', 'Site web, réseaux sociaux, recommandations'),
(4, 'Relations clients', 'Support communautaire, tutoriels'),
(4, 'Flux de revenus', 'Freemium, abonnement premium'),
(4, 'Ressources clés', 'Plateforme web, équipe produit'),
(4, 'Activités clés', 'Développement, UX/UI, support'),
(4, 'Partenaires clés', 'Intégrations tierces, influenceurs'),
(4, 'Structure de coûts', 'Développement, design, marketing'),

-- Projet 5
(5, 'Segments de clientèle', 'PME, startups, entreprises en transformation'),
(5, 'Proposition de valeur', 'Accompagnement personnalisé en transformation digitale'),
(5, 'Canaux', 'Réseaux professionnels, événements, recommandations'),
(5, 'Relations clients', 'Accompagnement personnalisé, suivi'),
(5, 'Flux de revenus', 'Prestations de conseil, formation'),
(5, 'Ressources clés', 'Experts consultants, méthodologies'),
(5, 'Activités clés', 'Consulting, formation, accompagnement'),
(5, 'Partenaires clés', 'Écoles, cabinets de conseil'),
(5, 'Structure de coûts', 'Salaires consultants, marketing, formation');

-- Hypothèses de test
INSERT IGNORE INTO hypotheses (project_id, title, content, status) VALUES
(1, 'Hypothèse marché', 'Le marché des applications de productivité est en croissance de 15% par an', 'validated'),
(1, 'Hypothèse prix', 'Les utilisateurs sont prêts à payer 9.99€/mois pour une version premium', 'pending'),
(2, 'Hypothèse concurrence', 'Il y a de la place pour une plateforme plus simple que les solutions existantes', 'validated'),
(2, 'Hypothèse distribution', 'Le marketing digital est le canal le plus efficace pour notre cible', 'pending'),
(3, 'Hypothèse technologie', 'Les PME adoptent rapidement les solutions SaaS', 'validated'),
(3, 'Hypothèse prix', 'Les PME peuvent payer 50€/mois/utilisateur', 'pending'),
(4, 'Hypothèse utilisateurs', 'Les équipes de travail recherchent des outils de collaboration simples', 'validated'),
(4, 'Hypothèse viralité', 'Les utilisateurs recommandent naturellement l\'outil', 'pending'),
(5, 'Hypothèse demande', 'Les PME ont besoin d\'accompagnement en transformation digitale', 'validated'),
(5, 'Hypothèse prix', 'Les PME peuvent payer 5000€ pour un accompagnement complet', 'pending');

-- Plans financiers de test
INSERT IGNORE INTO financial_plans (project_id, title, content, total_revenue, total_costs, profit_margin) VALUES
(1, 'Plan financier App Mobile', 'Plan sur 3 ans avec croissance progressive', 150000, 90000, 40.00),
(2, 'Plan financier E-commerce', 'Plan avec commission sur les ventes', 300000, 180000, 40.00),
(3, 'Plan financier SaaS', 'Plan avec abonnements récurrents', 500000, 250000, 50.00),
(4, 'Plan financier Web App', 'Plan freemium avec conversion', 200000, 120000, 40.00),
(5, 'Plan financier Consulting', 'Plan avec prestations de conseil', 100000, 60000, 40.00);

-- Logs système de test
INSERT IGNORE INTO system_logs (level, message, context) VALUES
('INFO', 'Système démarré avec succès', '{"component": "dashboard", "timestamp": "2024-01-15 10:00:00"}'),
('INFO', 'Nouveau projet créé', '{"project_id": 1, "user_id": 1}'),
('WARNING', 'Tentative de connexion échouée', '{"ip": "192.168.1.100", "email": "test@example.com"}'),
('ERROR', 'Erreur de base de données', '{"query": "SELECT * FROM users", "error": "Connection timeout"}'),
('INFO', 'Utilisateur connecté', '{"user_id": 1, "ip": "192.168.1.1"}');

-- Logs d'audit de test
INSERT IGNORE INTO audit_log (user_id, action_type, resource_type, resource_id, details, ip_address) VALUES
(1, 'login', 'user', 1, 'Connexion réussie', '192.168.1.1'),
(1, 'create', 'project', 1, 'Projet créé: Application Mobile', '192.168.1.1'),
(1, 'update', 'bmc', 1, 'Bloc BMC mis à jour', '192.168.1.1'),
(1, 'create', 'hypothesis', 1, 'Hypothèse créée', '192.168.1.1'),
(1, 'export', 'data', NULL, 'Export des données utilisateurs', '192.168.1.1');

-- Suggestions de partenariats de test
INSERT IGNORE INTO partnership_suggestions (project1_id, project2_id, synergy_score, synergy_reasons, status) VALUES
(1, 2, 75, '["Segments clients complémentaires", "Ressources partagées identifiées"]', 'pending'),
(1, 4, 60, '["Segments clients complémentaires", "Canaux de distribution complémentaires"]', 'pending'),
(2, 3, 80, '["Marchés cibles différents mais compatibles", "Partenaires communs"]', 'pending'),
(3, 5, 70, '["Propositions de valeur complémentaires", "Ressources partagées"]', 'pending'),
(4, 5, 65, '["Segments clients complémentaires", "Canaux de distribution complémentaires"]', 'pending');

-- Message de confirmation
SELECT 'Tables créées avec succès !' as message; 