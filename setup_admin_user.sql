-- Script pour configurer l'accès admin
-- À exécuter dans votre base de données MySQL

-- 1. Vérifier que la table user_roles existe et contient les rôles
INSERT IGNORE INTO user_roles (id, name, description, permissions) VALUES
(1, 'user', 'Utilisateur standard', '{"bmc": ["read", "write"], "profile": ["read", "write"]}'),
(2, 'admin', 'Administrateur système', '{"all": ["read", "write", "delete"]}'),
(3, 'moderator', 'Modérateur', '{"users": ["read"], "content": ["read", "write"], "reports": ["read"]}');

-- 2. Ajouter la colonne role_id à la table users si elle n'existe pas
ALTER TABLE users ADD COLUMN role_id INT DEFAULT 1;

-- 3. Créer un utilisateur admin (remplacez par vos informations)
INSERT INTO users (email, password, first_name, last_name, role_id, created_at) VALUES
('admin@lailaworkspace.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Laila', 2, NOW())
ON DUPLICATE KEY UPDATE role_id = 2;

-- 4. Ou mettre à jour un utilisateur existant en admin
-- Remplacez 'votre-email@example.com' par votre email
UPDATE users SET role_id = 2 WHERE email = 'votre-email@example.com';

-- 5. Vérifier les utilisateurs admin
SELECT id, email, first_name, last_name, role_id FROM users WHERE role_id = 2; 