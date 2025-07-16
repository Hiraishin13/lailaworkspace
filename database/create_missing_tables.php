<?php
require_once '../includes/db_connect.php';

echo "🔧 Vérification et création des tables manquantes...\n\n";

// Liste des tables nécessaires avec leurs structures
$tables = [
    'projects' => "
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
        )
    ",
    
    'bmc' => "
        CREATE TABLE IF NOT EXISTS bmc (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            block_name VARCHAR(100) NOT NULL,
            content TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            INDEX idx_project_id (project_id),
            INDEX idx_block_name (block_name)
        )
    ",
    
    'hypotheses' => "
        CREATE TABLE IF NOT EXISTS hypotheses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            content TEXT,
            status ENUM('pending', 'validated', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            INDEX idx_project_id (project_id),
            INDEX idx_status (status)
        )
    ",
    
    'financial_plans' => "
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
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            INDEX idx_project_id (project_id)
        )
    ",
    
    'users' => "
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
        )
    ",
    
    'audit_log' => "
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
        )
    ",
    
    'system_logs' => "
        CREATE TABLE IF NOT EXISTS system_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            level ENUM('INFO', 'WARNING', 'ERROR', 'DEBUG') NOT NULL,
            message TEXT NOT NULL,
            context JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_level (level),
            INDEX idx_created_at (created_at)
        )
    ",
    
    'password_resets' => "
        CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(255) UNIQUE NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_token (token),
            INDEX idx_expires_at (expires_at)
        )
    ",
    
    'partnership_suggestions' => "
        CREATE TABLE IF NOT EXISTS partnership_suggestions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project1_id INT NOT NULL,
            project2_id INT NOT NULL,
            synergy_score INT NOT NULL,
            synergy_reasons TEXT,
            status ENUM('pending', 'notified', 'active', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            notified_at TIMESTAMP NULL,
            FOREIGN KEY (project1_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (project2_id) REFERENCES projects(id) ON DELETE CASCADE,
            INDEX idx_project1_id (project1_id),
            INDEX idx_project2_id (project2_id),
            INDEX idx_status (status)
        )
    "
];

// Créer chaque table
foreach ($tables as $table_name => $sql) {
    try {
        $pdo->exec($sql);
        echo "✅ Table '$table_name' créée/vérifiée avec succès\n";
    } catch (PDOException $e) {
        echo "❌ Erreur lors de la création de la table '$table_name': " . $e->getMessage() . "\n";
    }
}

// Insérer des données de test si les tables sont vides
echo "\n📊 Insertion de données de test...\n";

// Vérifier si la table users est vide
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
$user_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

if ($user_count == 0) {
    // Créer un utilisateur admin de test
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO users (username, first_name, last_name, email, password, role_id, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['admin', 'Admin', 'User', 'admin@lailaworkspace.com', $admin_password, 1, 'active']);
    echo "✅ Utilisateur admin créé (admin@lailaworkspace.com / admin123)\n";
}

// Vérifier si la table projects est vide
$stmt = $pdo->query("SELECT COUNT(*) as count FROM projects");
$project_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

if ($project_count == 0) {
    // Créer quelques projets de test
    $test_projects = [
        ['Application Mobile', 'Une application mobile innovante', 'Consommateurs', 1],
        ['Plateforme E-commerce', 'Site de vente en ligne', 'Entreprises', 1],
        ['Service SaaS', 'Logiciel en tant que service', 'Professionnels', 1],
        ['Application Web', 'Application web moderne', 'Particuliers', 1],
        ['Service Consulting', 'Service de conseil en entreprise', 'PME', 1]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO projects (name, description, target_market, user_id) 
        VALUES (?, ?, ?, ?)
    ");
    
    foreach ($test_projects as $project) {
        $stmt->execute($project);
    }
    echo "✅ 5 projets de test créés\n";
    
    // Créer des blocs BMC pour les projets
    $bmc_blocks = [
        'Segments de clientèle',
        'Proposition de valeur',
        'Canaux',
        'Relations clients',
        'Flux de revenus',
        'Ressources clés',
        'Activités clés',
        'Partenaires clés',
        'Structure de coûts'
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO bmc (project_id, block_name, content) 
        VALUES (?, ?, ?)
    ");
    
    for ($project_id = 1; $project_id <= 5; $project_id++) {
        foreach ($bmc_blocks as $block_name) {
            $content = "Contenu de test pour $block_name - Projet $project_id";
            $stmt->execute([$project_id, $block_name, $content]);
        }
    }
    echo "✅ Blocs BMC créés pour tous les projets\n";
    
    // Créer quelques hypothèses
    $test_hypotheses = [
        ['Hypothèse marché', 'Le marché cible est prêt pour notre solution', 1],
        ['Hypothèse prix', 'Les clients sont prêts à payer ce prix', 2],
        ['Hypothèse distribution', 'Ce canal de distribution est efficace', 3],
        ['Hypothèse concurrence', 'Nous avons un avantage concurrentiel', 4],
        ['Hypothèse technologie', 'La technologie répond aux besoins', 5]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO hypotheses (title, content, project_id) 
        VALUES (?, ?, ?)
    ");
    
    foreach ($test_hypotheses as $hypothesis) {
        $stmt->execute($hypothesis);
    }
    echo "✅ Hypothèses de test créées\n";
    
    // Créer des plans financiers
    $stmt = $pdo->prepare("
        INSERT INTO financial_plans (project_id, title, content, total_revenue, total_costs, profit_margin) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    for ($project_id = 1; $project_id <= 5; $project_id++) {
        $revenue = rand(50000, 500000);
        $costs = $revenue * 0.7;
        $margin = (($revenue - $costs) / $revenue) * 100;
        
        $stmt->execute([
            $project_id,
            "Plan financier Projet $project_id",
            "Plan financier détaillé pour le projet $project_id",
            $revenue,
            $costs,
            round($margin, 2)
        ]);
    }
    echo "✅ Plans financiers de test créés\n";
}

// Insérer quelques logs système
$stmt = $pdo->prepare("
    INSERT INTO system_logs (level, message, context) 
    VALUES (?, ?, ?)
");

$test_logs = [
    ['INFO', 'Système démarré avec succès', '{"component": "dashboard", "timestamp": "' . date('Y-m-d H:i:s') . '"}'],
    ['INFO', 'Nouveau projet créé', '{"project_id": 1, "user_id": 1}'],
    ['WARNING', 'Tentative de connexion échouée', '{"ip": "192.168.1.100", "email": "test@example.com"}'],
    ['ERROR', 'Erreur de base de données', '{"query": "SELECT * FROM users", "error": "Connection timeout"}']
];

foreach ($test_logs as $log) {
    $stmt->execute($log);
}
echo "✅ Logs système de test créés\n";

// Insérer quelques entrées d'audit
$stmt = $pdo->prepare("
    INSERT INTO audit_log (user_id, action_type, resource_type, resource_id, details, ip_address) 
    VALUES (?, ?, ?, ?, ?, ?)
");

$test_audit = [
    [1, 'login', 'user', 1, 'Connexion réussie', '192.168.1.1'],
    [1, 'create', 'project', 1, 'Projet créé: Application Mobile', '192.168.1.1'],
    [1, 'update', 'bmc', 1, 'Bloc BMC mis à jour', '192.168.1.1'],
    [1, 'create', 'hypothesis', 1, 'Hypothèse créée', '192.168.1.1']
];

foreach ($test_audit as $audit) {
    $stmt->execute($audit);
}
echo "✅ Logs d'audit de test créés\n";

echo "\n🎉 Configuration terminée ! Le dashboard devrait maintenant afficher toutes les données.\n";
echo "📊 Accédez au dashboard: http://localhost/lailaworkspace/views/admin/dashboard.php\n";
?> 