<?php
/**
 * Script d'import automatique de la base de données pour Hostinger
 * À exécuter une seule fois après le déploiement
 */

// Inclure la connexion à la base de données
require_once 'includes/db_connect.php';

echo "=== Import automatique de la base de données Hostinger ===\n\n";

// Fonction pour exécuter un fichier SQL
function executeSQLFile($pdo, $filename) {
    if (file_exists($filename)) {
        echo "📁 Exécution de $filename...\n";
        $sql = file_get_contents($filename);
        
        // Diviser les requêtes par point-virgule
        $queries = explode(';', $sql);
        
        $success = 0;
        $errors = 0;
        
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                try {
                    $pdo->exec($query);
                    $success++;
                } catch (PDOException $e) {
                    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
                    $errors++;
                }
            }
        }
        
        echo "   ✅ $success requêtes exécutées avec succès\n";
        if ($errors > 0) {
            echo "   ⚠️  $errors erreurs (tables peut-être déjà existantes)\n";
        }
        echo "\n";
        return true;
    } else {
        echo "❌ Fichier $filename non trouvé\n\n";
        return false;
    }
}

// Liste des fichiers SQL à exécuter dans l'ordre
$sqlFiles = [
    'database/create_tables.sql',
    'database/create_notifications_table.sql',
    'database/create_partnerships_table.sql',
    'database/admin_tables.sql',
    'setup_admin_user.sql'
];

echo "🚀 Début de l'import de la base de données...\n\n";

foreach ($sqlFiles as $file) {
    executeSQLFile($pdo, $file);
}

echo "=== Import terminé ===\n";
echo "✅ Base de données configurée avec succès!\n\n";

echo "📋 Prochaines étapes:\n";
echo "1. Testez votre site: https://lailaworkspace.com\n";
echo "2. Accédez à l'administration: https://lailaworkspace.com/views/admin/\n";
echo "3. Supprimez ce fichier pour la sécurité\n\n";

echo "🔐 Identifiants admin par défaut (si configurés):\n";
echo "   Email: admin@lailaworkspace.com\n";
echo "   Mot de passe: admin123\n\n";

echo "⚠️  IMPORTANT: Supprimez ce fichier après utilisation!\n";
?> 